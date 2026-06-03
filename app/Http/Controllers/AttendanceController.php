<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Employee;
use App\Models\Latetime;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
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
  
}
