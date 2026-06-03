<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Check;
use App\Models\Leave;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\AttendanceEmp;
use Illuminate\Http\Request;


class ApiController extends Controller
{
    
    public function check(AttendanceEmp $request)
    {
        $request->validated();
        $email = $request->input('email');

        if ($employee = Employee::with('schedules')->whereEmail($email)->first()) {
            $schedule = $employee->schedules->first();

            if (Hash::check($request->pin_code, $employee->pin_code)) {
                $latestCheck = Check::whereEmp_id($employee->id)->latest()->first();

                if (null === $latestCheck) {
                    ApiController::newAttandance($employee);
                }else{

                    if($latestCheck->leave_time !== null){
                        ApiController::newAttandance($employee);
                    } else {
                        $check = $latestCheck;
                        $check->leave_time = date("Y-m-d H:i:s");
                        $check->save();
                        return response()->json(['success' => 'Successful in assign the leave'], 200);
                    }

                }

            } else {
                return response()->json(['error' => 'Failed to assign the attendance'], 404);
            }
        }
        return response()->json(['success' => 'Successful in assign the attendance'], 200);
    }


    public function newAttandance($employee){
        $check = new Check;
        $check->emp_id = $employee->id;
        $check->attendance_time = date("Y-m-d H:i:s");
        $check->leave_time = null;
        $check->save();
    }



    public function attendance(AttendanceEmp $request)
    {
         $request->validated();
        $today = date("Y-m-d");

        if ($employee = Employee::with('schedules')->whereEmail($request->input('email'))->first()) {
            $schedule = $employee->schedules->first();

            if (Hash::check($request->pin_code, $employee->pin_code)) {
                if (!Attendance::whereAttendance_date($today)->whereEmp_id($employee->id)->exists()) {
                    $attendance = new Attendance;
                    $attendance->emp_id = $employee->id;
                    $attendance->attendance_time = date("H:i:s");
                    $attendance->attendance_date = $today;

                    if ($schedule && $schedule->time_in < $attendance->attendance_time) {
                        $attendance->status = 0;
                        AttendanceController::lateTime($employee);
                    };

                    $attendance->save();
                 } else {
                    return response()->json(['error' => 'you assigned your attendance before'], 404);
                }
            } else {
                return response()->json(['error' => 'Failed to assign the attendance'], 404);
            }
        }
        return response()->json(['success' => 'Successful in assign the attendance'], 200);

    }



    public function leave(AttendanceEmp $request)
    {
        $request->validated();
        $today = date("Y-m-d");

        if ($employee = Employee::with('schedules')->whereEmail($request->input('email'))->first()) {
            $schedule = $employee->schedules->first();

            if (Hash::check($request->pin_code, $employee->pin_code)) {
                if (!Leave::whereLeave_date($today)->whereEmp_id($employee->id)->exists()) {
                    $leave = new Leave;
                    $leave->emp_id = $employee->id;
                    $leave->leave_time = date("H:i:s");
                    $leave->leave_date = $today;
                    // ontime + overtime if true , else "early go" ....
                    if ($schedule && $leave->leave_time >= $schedule->time_out) {
                        LeaveController::overTime($employee);
                    } else {
                        $leave->status = 0;
                    }

                    $leave->save();
                } else {
                    return response()->json(['error' => 'you assigned your leave before'], 404);
                }
            } else {
                return response()->json(['error' => 'Failed to assign the leave'], 404);
            }
        }

        return response()->json(['success' => 'Successful in assign the leave'], 200);
    }

}
