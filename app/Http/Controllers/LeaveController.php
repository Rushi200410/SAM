<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\User;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\FingerDevices;
use App\Helpers\FingerHelper;
use App\Models\Leave;
use App\Http\Requests\AttendanceEmp;
use Illuminate\Support\Facades\Hash;

class LeaveController extends Controller
{
    public function index()
    {
        return view('admin.leave')->with([
            'leaves' => Leave::with('employee.schedules')->latest('leave_date')->latest('leave_time')->get(),
        ]);
    }

    public function indexOvertime()
    {
        return view('admin.overtime')->with([
            'overtimes' => Overtime::with('employee.schedules')->latest('overtime_date')->latest('created_at')->get(),
        ]);
    }


    public static function overTime(Employee $employee)
    {
        $schedule = $employee->schedules->first();

        if (!$schedule) {
            return;
        }

        $current_t = new DateTime(date('H:i:s'));
        $end_t = new DateTime($schedule->time_out);
        $difference = $end_t->diff($current_t)->format('%H:%I:%S');

        $overtime = new Overtime();
        $overtime->emp_id = $employee->id;
        $overtime->duration = $difference;
        $overtime->overtime_date = date('Y-m-d');
        $overtime->save();
    }

    public static function overTimeDevice($att_dateTime, Employee $employee)
    {
            $schedule = $employee->schedules->first();

            if (!$schedule) {
                return;
            }

            $attendance_time =new DateTime($att_dateTime);
            $checkout = new DateTime($schedule->time_out);
            $difference = $checkout->diff($attendance_time)->format('%H:%I:%S');

            $overtime = new Overtime();
            $overtime->emp_id = $employee->id;
            $overtime->duration = $difference;
            $overtime->overtime_date = date('Y-m-d', strtotime($att_dateTime));
            $overtime->save();
        
    }
}
