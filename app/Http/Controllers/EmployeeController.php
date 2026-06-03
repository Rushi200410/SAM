<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Schedule;
use App\Http\Requests\EmployeeRec;
use RealRashid\SweetAlert\Facades\Alert;

class EmployeeController extends Controller
{
   
    public function index()
    {
        
        return view('admin.employee')->with([
            'employees'=> Employee::with('schedules')->latest()->get(),
            'schedules'=> Schedule::orderBy('slug')->get(),
        ]);
    }

    public function store(EmployeeRec $request)
    {
        $request->validated();

        $employee = new Employee;
        $employee->name = $request->name;
        $employee->position = $request->position;
        $employee->email = $request->email;
        $employee->pin_code = bcrypt($request->pin_code);
        $employee->save();

        if($request->schedule){

            $schedule = Schedule::whereSlug($request->schedule)->firstOrFail();

            $employee->schedules()->sync([$schedule->id]);
        }

        // $role = Role::whereSlug('emp')->first();

        // $employee->roles()->attach($role);

        flash()->success('Success','Employee Record has been created successfully !');

        return redirect()->route('employees.index')->with('success');
    }

 
    public function update(EmployeeRec $request, Employee $employee)
    {
        $request->validated();

        $employee->name = $request->name;
        $employee->position = $request->position;
        $employee->email = $request->email;
        $employee->pin_code = bcrypt($request->pin_code);
        $employee->save();

        if ($request->schedule) {

            $schedule = Schedule::whereSlug($request->schedule)->firstOrFail();

            $employee->schedules()->sync([$schedule->id]);
        }

        flash()->success('Success','Employee Record has been Updated successfully !');

        return redirect()->route('employees.index')->with('success');
    }


    public function destroy(Employee $employee)
    {
        $employee->delete();
        flash()->success('Success','Employee Record has been Deleted successfully !');
        return redirect()->route('employees.index')->with('success');
    }
}
