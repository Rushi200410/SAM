<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\FingerHelper;

use App\Http\Controllers\Controller;

use App\Http\Requests\FingerDevice\StoreRequest;

use App\Http\Requests\FingerDevice\UpdateRequest;

use App\Jobs\GetAttendanceJob;

use App\Models\FingerDevices;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;

use Gate;

use Illuminate\Http\RedirectResponse;

use Carbon\Carbon;
use Rats\Zkteco\Lib\ZKTeco;

use Symfony\Component\HttpFoundation\Response;

class BiometricDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $devices = FingerDevices::all();

        return view('admin.fingerDevices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.fingerDevices.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $helper = new FingerHelper();

        $device = $helper->init($request->input('ip'));

        if ($device->connect()) {
            // Serial Number Sample CDQ9192960002\x00

            $serial = $helper->getSerial($device);

            FingerDevices::create($request->validated() + ['serialNumber' => $serial]);

            flash()->success('Success', 'Biometric Device created successfully !');
        } else {
            flash()->error('Oops', ' Failed connecting to Biometric Device !');
        }

        return redirect()->route('finger_device.index');
    }

    public function show(FingerDevices $fingerDevice)
    {
        return view('admin.fingerDevices.show', compact('fingerDevice'));
    }

    public function edit(FingerDevices $fingerDevice)
    {
        return view('admin.fingerDevices.edit', compact('fingerDevice'));
    }

    public function update(UpdateRequest $request, FingerDevices $fingerDevice): RedirectResponse
    {
        $fingerDevice->update($request->validated());

        flash()->success('Success', 'Biometric Device Updated successfully !');

        return redirect()->route('finger_device.index');
    }
    public function destroy(FingerDevices $fingerDevice): RedirectResponse
    {
        try {
            $fingerDevice->delete();
        } catch (\Exception $e) {
            toast("Failed to delete {$fingerDevice->name}", 'error');
        }

        flash()->success('Success', 'Biometric Device deleted successfully !');

        return back();
    }

    public function addEmployee(FingerDevices $fingerDevice): RedirectResponse
    {
        $device = new ZKTeco($fingerDevice->ip, 4370);

        $device->connect();

        $deviceUsers = collect($device->getUser())->pluck('uid');

        $employees = Employee::select('name', 'id')
            ->whereNotIn('id', $deviceUsers)
            ->get();

        $i = 1;

        foreach ($employees as $employee) {
            $device->setUser($i++, $employee->id, $employee->name, '', '0', '0');
        }
        flash()->success('Success', 'All Employees added to Biometric device successfully!');

        return back();
    }

    public function getAttendance(FingerDevices $fingerDevice)
    {
        $device = new ZKTeco($fingerDevice->ip, 4370);

        $device->connect();

        $data = collect($device->getAttendance() ?: []);
        $employeeIds = $data->pluck('id')->filter()->unique()->values();

        if ($employeeIds->isEmpty()) {
            flash()->info('Info', 'No attendance records were found on the device.');

            return back();
        }

        $employees = Employee::with('schedules')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $attendanceRows = $data->where('type', 0);
        $attendanceDates = $attendanceRows->map(function ($value) {
            return Carbon::parse($value['timestamp'])->toDateString();
        })->unique()->values();
        $existingAttendance = Attendance::whereIn('emp_id', $employeeIds)
            ->whereType(0)
            ->whereIn('attendance_date', $attendanceDates)
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->emp_id.'|'.$attendance->attendance_date;
            });

        $leaveRows = $data->where('type', 1);
        $leaveDates = $leaveRows->map(function ($value) {
            return Carbon::parse($value['timestamp'])->toDateString();
        })->unique()->values();
        $existingLeave = Leave::whereIn('emp_id', $employeeIds)
            ->whereType(1)
            ->whereIn('leave_date', $leaveDates)
            ->get()
            ->keyBy(function ($leave) {
                return $leave->emp_id.'|'.$leave->leave_date;
            });

        foreach ($data as $value) {
            $employee = $employees->get((int) $value['id']);

            if (!$employee || $employee->schedules->isEmpty()) {
                continue;
            }

            $schedule = $employee->schedules->first();
            $timestamp = Carbon::parse($value['timestamp']);
            $date = $timestamp->toDateString();
            $time = $timestamp->format('H:i:s');
            $key = $employee->id.'|'.$date;

            if ((int) $value['type'] === 0) {
                if ($existingAttendance->has($key)) {
                    continue;
                }

                $att_table = new Attendance();
                $att_table->uid = $value['uid'];
                $att_table->emp_id = $employee->id;
                $att_table->state = $value['state'];
                $att_table->attendance_time = $time;
                $att_table->attendance_date = $date;
                $att_table->type = $value['type'];

                if ($time > $schedule->time_in) {
                    $att_table->status = 0;
                    AttendanceController::lateTimeDevice($value['timestamp'], $employee);
                }

                $att_table->save();
                continue;
            }

            if ($existingLeave->has($key)) {
                continue;
            }

            $lve_table = new Leave();
            $lve_table->uid = $value['uid'];
            $lve_table->emp_id = $employee->id;
            $lve_table->state = $value['state'];
            $lve_table->leave_time = $time;
            $lve_table->leave_date = $date;
            $lve_table->type = $value['type'];

            if ($time < $schedule->time_out) {
                $lve_table->status = 0;
            } else {
                LeaveController::overTimeDevice($value['timestamp'], $employee);
            }

            $lve_table->save();
        }

        
        flash()->success('Success', 'Attendance Queue will run in a minute!');

        return back();
    }
}
