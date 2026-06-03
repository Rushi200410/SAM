<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;

class CheckController extends Controller
{
    private function monthlyGridData(): array
    {
        $today = today();
        $dates = [];

        for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
            $dates[] = \Carbon\Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
        }

        $startDate = $dates[0];
        $endDate = $dates[count($dates) - 1];

        $employees = Employee::with('schedules')->orderBy('name')->get();
        $attendanceRecords = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->whereType(0)
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->emp_id.'|'.$attendance->attendance_date;
            });
        $leaveRecords = Leave::whereBetween('leave_date', [$startDate, $endDate])
            ->whereType(1)
            ->get()
            ->keyBy(function ($leave) {
                return $leave->emp_id.'|'.$leave->leave_date;
            });

        return compact('today', 'dates', 'employees', 'attendanceRecords', 'leaveRecords');
    }

    public function index()
    {
        return view('admin.check')->with($this->monthlyGridData());
    }

    public function CheckStore(Request $request)
    {
        $attendanceInput = collect($request->input('attd', []));
        $leaveInput = collect($request->input('leave', []));

        $employeeIds = $attendanceInput->flatMap(function ($employees) {
            return array_keys($employees);
        })->merge($leaveInput->flatMap(function ($employees) {
            return array_keys($employees);
        }))->unique()->filter()->values();

        $employees = Employee::with('schedules')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $attendanceDates = $attendanceInput->keys()->values();
        $leaveDates = $leaveInput->keys()->values();

        $existingAttendance = Attendance::whereIn('attendance_date', $attendanceDates)
            ->whereIn('emp_id', $employeeIds)
            ->whereType(0)
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->emp_id.'|'.$attendance->attendance_date;
            });

        $existingLeave = Leave::whereIn('leave_date', $leaveDates)
            ->whereIn('emp_id', $employeeIds)
            ->whereType(1)
            ->get()
            ->keyBy(function ($leave) {
                return $leave->emp_id.'|'.$leave->leave_date;
            });

        foreach ($attendanceInput as $date => $employeesForDate) {
            foreach (array_keys($employeesForDate) as $employeeId) {
                $employee = $employees->get((int) $employeeId);

                if (!$employee || $employee->schedules->isEmpty()) {
                    continue;
                }

                $key = $employee->id.'|'.$date;

                if ($existingAttendance->has($key)) {
                    continue;
                }

                $schedule = $employee->schedules->first();

                $data = new Attendance();
                $data->emp_id = $employee->id;
                $data->attendance_time = $schedule->time_in;
                $data->attendance_date = $date;
                $data->status = 1;
                $data->save();
            }
        }

        foreach ($leaveInput as $date => $employeesForDate) {
            foreach (array_keys($employeesForDate) as $employeeId) {
                $employee = $employees->get((int) $employeeId);

                if (!$employee || $employee->schedules->isEmpty()) {
                    continue;
                }

                $key = $employee->id.'|'.$date;

                if ($existingLeave->has($key)) {
                    continue;
                }

                $schedule = $employee->schedules->first();

                $data = new Leave();
                $data->emp_id = $employee->id;
                $data->leave_time = $schedule->time_out;
                $data->leave_date = $date;
                $data->status = 1;
                $data->save();
            }
        }

        flash()->success('Success', 'You have successfully submited the attendance !');
        return back();
    }
    public function sheetReport()
    {
        return view('admin.sheet-report')->with($this->monthlyGridData());
    }
}
