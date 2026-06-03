<?php

namespace App\Http\Controllers;

use DateTime;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Latetime;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AttendanceEmp;

class AttendanceController extends Controller
{   
    //show attendance 
    public function index()
    {  
        return view('admin.attendance')->with([
            'attendances' => Attendance::with(['employee.schedules'])->latest('attendance_date')->latest('attendance_time')->get(),
        ]);
    }

    //show late times
    public function indexLatetime()
    {
        return view('admin.latetime')->with([
            'latetimes' => Latetime::with('employee.schedules')->latest('latetime_date')->get(),
        ]);
    }

    

    public static function lateTime(Employee $employee)
    {
        $schedule = $employee->schedules->first();

        if (!$schedule) {
            return;
        }

        $current_t = new DateTime(date('H:i:s'));
        $start_t = new DateTime($schedule->time_in);
        $difference = $start_t->diff($current_t)->format('%H:%I:%S');

        $latetime = new Latetime();
        $latetime->emp_id = $employee->id;
        $latetime->duration = $difference;
        $latetime->latetime_date = date('Y-m-d');
        $latetime->save();
    }

    public static function lateTimeDevice($att_dateTime, Employee $employee)
    {
        $schedule = $employee->schedules->first();

        if (!$schedule) {
            return;
        }

        $attendance_time = new DateTime($att_dateTime);
        $checkin = new DateTime($schedule->time_in);
        $difference = $checkin->diff($attendance_time)->format('%H:%I:%S');

        $latetime = new Latetime();
        $latetime->emp_id = $employee->id;
        $latetime->duration = $difference;
        $latetime->latetime_date = date('Y-m-d', strtotime($att_dateTime));
        $latetime->save();
    }

    public function markFaceAttendance(Request $request)
    {
        $validated = $request->validate([
            'emp_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $employee = null;

        if (!empty($validated['emp_id'])) {
            $employee = Employee::with('schedules')->find($validated['emp_id']);
        }

        if (!$employee && !empty($validated['name'])) {
            $employee = Employee::with('schedules')
                ->where('name', $validated['name'])
                ->first();
        }

        if (!$employee) {
            Log::warning('Face attendance request could not resolve an employee.', [
                'emp_id' => $validated['emp_id'] ?? null,
                'name' => $validated['name'] ?? null,
            ]);

            return response()->json([
                'message' => 'Employee not found.',
            ], 404);
        }

        $attendanceAt = Carbon::now();
        $attendanceDate = $attendanceAt->toDateString();
        $attendanceTime = $attendanceAt->format('H:i:s');

        $alreadyMarked = Attendance::where('emp_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->whereType(0)
            ->exists();

        if ($alreadyMarked) {
            Log::info('Duplicate face attendance prevented.', [
                'emp_id' => $employee->id,
                'date' => $attendanceDate,
            ]);

            return response()->json([
                'message' => 'Attendance already marked for today.',
            ], 409);
        }

        $attendance = new Attendance();
        $attendance->uid = 0;
        $attendance->emp_id = $employee->id;
        $attendance->state = 1;
        $attendance->attendance_time = $attendanceTime;
        $attendance->attendance_date = $attendanceDate;
        $attendance->status = 1;
        $attendance->type = 0;

        $schedule = $employee->schedules->first();

        if ($schedule && $attendanceTime > $schedule->time_in) {
            $attendance->status = 0;
            self::lateTimeDevice($attendanceAt->toDateTimeString(), $employee);
        }

        try {
            $attendance->save();
        } catch (QueryException $exception) {
            Log::warning('Face attendance insert failed.', [
                'emp_id' => $employee->id,
                'date' => $attendanceDate,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Attendance already marked for today.',
            ], 409);
        }

        return response()->json([
            'message' => 'Attendance marked successfully.',
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
            ],
            'attendance' => [
                'date' => $attendance->attendance_date,
                'time' => $attendance->attendance_time,
                'status' => $attendance->status,
                'type' => $attendance->type,
            ],
        ], 200);
    }
  
}
