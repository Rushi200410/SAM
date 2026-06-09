<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Schedule;

class AdminController extends Controller
{
    public function index()
    {
        $today = today();

        $totalEmp = Employee::count();
        $allAttendance = Attendance::whereDate('attendance_date', $today)->count();
        $ontimeEmp = Attendance::whereDate('attendance_date', $today)->whereStatus('1')->count();
        $latetimeEmp = Attendance::whereDate('attendance_date', $today)->whereStatus('0')->count();
        $totalSchedule = Schedule::count();
        $absentToday = max($totalEmp - $allAttendance, 0);

        $percentageOntime = $allAttendance > 0
            ? round(($ontimeEmp / $allAttendance) * 100, 1)
            : 0;

        $weeklyLabels = [];
        $weeklyOntime = [];
        $weeklyLate = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $weeklyLabels[] = $date->format('D');
            $weeklyOntime[] = Attendance::whereDate('attendance_date', $date)->whereStatus('1')->count();
            $weeklyLate[] = Attendance::whereDate('attendance_date', $date)->whereStatus('0')->count();
        }

        $recentAttendance = Attendance::with('employee')
            ->whereDate('attendance_date', $today)
            ->orderByDesc('attendance_time')
            ->limit(8)
            ->get();

        return view('admin.index', compact(
            'today',
            'totalEmp',
            'allAttendance',
            'ontimeEmp',
            'latetimeEmp',
            'absentToday',
            'percentageOntime',
            'totalSchedule',
            'weeklyLabels',
            'weeklyOntime',
            'weeklyLate',
            'recentAttendance'
        ));
    }
}
