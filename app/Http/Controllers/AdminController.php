<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Latetime;
use App\Models\Attendance;
use App\Models\Schedule;


class AdminController extends Controller
{

 
    public function index()
    {
        //Dashboard statistics 
        $today = today()->toDateString();

        $totalEmp = Employee::count();
        $allAttendance = Attendance::whereDate('attendance_date', $today)->count();
        $ontimeEmp = Attendance::whereDate('attendance_date', $today)->whereStatus('1')->count();
        $latetimeEmp = Attendance::whereDate('attendance_date', $today)->whereStatus('0')->count();
        $totalSchedule = Schedule::count();

        $percentageOntime = $allAttendance > 0
            ? round(($ontimeEmp / $allAttendance) * 100, 2)
            : 0;

        $data = [$totalEmp, $ontimeEmp, $latetimeEmp, $percentageOntime, $totalSchedule];
        
        return view('admin.index')->with(['data' => $data]);
    }

}
