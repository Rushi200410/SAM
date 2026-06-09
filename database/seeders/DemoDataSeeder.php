<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Latetime;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private const EMPLOYEE_COUNT = 150;

    /**
     * Seed a realistic demo dataset that looks like the system has been used for months.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {
            $adminRole = $this->seedAdminRole();
            $this->seedAdminUser($adminRole);

            $schedules = $this->seedSchedules();
            $employees = $this->seedEmployees($schedules);

            $this->assignSchedules($employees, $schedules);
            $employees = Employee::whereIn('id', $employees->pluck('id')->all())
                ->with('schedules')
                ->get();
            $this->seedHistoricalActivity($employees);
        });
    }

    private function seedAdminRole(): Role
    {
        $role = Role::where('slug', 'admin')->first();

        if (!$role) {
            $role = new Role();
            $role->slug = 'admin';
            $role->name = 'Administrator';
            $role->permissions = null;
            $role->save();
        }

        return $role;
    }

    private function seedAdminUser(Role $role): User
    {
        $user = User::where('email', 'admin@mail.com')->first();

        if (!$user) {
            $user = new User();
            $user->name = 'Admin';
            $user->email = 'admin@mail.com';
            $user->password = Hash::make('svkm1234');
            $user->save();
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function seedSchedules()
    {
        $definitions = [
            ['slug' => 'general-day', 'time_in' => '09:00:00', 'time_out' => '17:30:00'],
            ['slug' => 'early-shift', 'time_in' => '08:00:00', 'time_out' => '16:30:00'],
            ['slug' => 'late-shift', 'time_in' => '10:00:00', 'time_out' => '18:30:00'],
            ['slug' => 'support-shift', 'time_in' => '11:00:00', 'time_out' => '20:00:00'],
        ];

        $schedules = collect();

        foreach ($definitions as $definition) {
            $schedule = Schedule::where('slug', $definition['slug'])->first();

            if (!$schedule) {
                $schedule = new Schedule();
                $schedule->slug = $definition['slug'];
                $schedule->time_in = $definition['time_in'];
                $schedule->time_out = $definition['time_out'];
                $schedule->save();
            }

            $schedules->push($schedule);
        }

        return $schedules;
    }

    private function seedEmployees($schedules)
    {
        $faker = Faker::create('en_US');
        $positions = [
            'Accountant',
            'Admin Officer',
            'Business Analyst',
            'Cashier',
            'Customer Support',
            'Data Entry Operator',
            'HR Executive',
            'Junior Developer',
            'Office Assistant',
            'Operations Lead',
            'Project Coordinator',
            'Sales Executive',
        ];

        $employees = collect();

        for ($i = 1; $i <= self::EMPLOYEE_COUNT; ++$i) {
            $email = sprintf('employee%03d@demo.test', $i);
            $name = sprintf('%s %s %03d', $faker->firstName, $faker->lastName, $i);

            $employee = Employee::where('email', $email)->first();

            if (!$employee) {
                $employee = new Employee();
                $employee->name = $name;
                $employee->position = $positions[array_rand($positions)];
                $employee->email = $email;
                $employee->pin_code = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
                $employee->save();
            }

            $employees->push($employee);
        }

        return $employees;
    }

    private function assignSchedules($employees, $schedules): void
    {
        DB::table('schedule_employees')
            ->whereIn('emp_id', $employees->pluck('id')->all())
            ->delete();

        foreach ($employees as $index => $employee) {
            $schedule = $schedules[$index % $schedules->count()];

            DB::table('schedule_employees')->insert([
                'emp_id' => $employee->id,
                'schedule_id' => $schedule->id,
            ]);
        }
    }

    private function seedHistoricalActivity($employees): void
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subMonths(4)->startOfDay();

        // Seed month-by-month history so the app feels like it has been in use for a while.
        for ($date = $startDate->copy(); $date->lte($today); $date->addDay()) {
            $isWeekend = $date->isWeekend();
            $isToday = $date->isSameDay($today);

            foreach ($employees as $employee) {
                $schedule = $employee->schedules->first();

                if (!$schedule) {
                    continue;
                }

                $shouldBeLeave = !$isToday && !$isWeekend && $this->roll(4);

                if ($shouldBeLeave) {
                    $this->upsertLeave($employee, $date, $schedule);
                    continue;
                }

                $attendanceChance = $isToday ? 94 : ($isWeekend ? 22 : 88);

                if (!$this->roll($attendanceChance)) {
                    continue;
                }

                $isLate = !$isWeekend && $this->roll($isToday ? 18 : 22);
                $attendanceTime = $this->buildAttendanceTime($schedule->time_in, $isLate);

                $this->upsertAttendance($employee, $date, $attendanceTime, $isLate);

                if ($isLate) {
                    $this->upsertLatetime($employee, $date, $schedule->time_in, $attendanceTime);
                }

                $shouldOvertime = !$isWeekend && $this->roll($isToday ? 12 : 16);

                if ($shouldOvertime) {
                    $overtimeTime = $this->buildOvertimeDuration($schedule->time_out);
                    $this->upsertOvertime($employee, $date, $overtimeTime);
                }
            }
        }
    }

    private function upsertAttendance(Employee $employee, Carbon $date, string $attendanceTime, bool $isLate): void
    {
        $record = Attendance::where('emp_id', $employee->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->where('type', 0)
            ->first();

        if (!$record) {
            $record = new Attendance();
            $record->uid = 0;
            $record->emp_id = $employee->id;
            $record->state = 1;
            $record->attendance_date = $date->toDateString();
            $record->type = 0;
        }

        $record->attendance_time = $attendanceTime;
        $record->status = $isLate ? 0 : 1;
        $record->save();
    }

    private function upsertLatetime(Employee $employee, Carbon $date, string $scheduledTime, string $actualTime): void
    {
        $record = Latetime::where('emp_id', $employee->id)
            ->whereDate('latetime_date', $date->toDateString())
            ->first();

        if (!$record) {
            $record = new Latetime();
            $record->emp_id = $employee->id;
            $record->latetime_date = $date->toDateString();
        }

        $record->duration = Carbon::createFromFormat('H:i:s', $scheduledTime)
            ->diff(Carbon::createFromFormat('H:i:s', $actualTime))
            ->format('%H:%I:%S');
        $record->save();
    }

    private function upsertOvertime(Employee $employee, Carbon $date, string $scheduledOut): void
    {
        $record = Overtime::where('emp_id', $employee->id)
            ->whereDate('overtime_date', $date->toDateString())
            ->first();

        if (!$record) {
            $record = new Overtime();
            $record->emp_id = $employee->id;
            $record->overtime_date = $date->toDateString();
        }

        $overtimeHours = random_int(1, 3);
        $overtimeMinutes = random_int(10, 45);

        $record->duration = Carbon::createFromFormat('H:i:s', $scheduledOut)
            ->addHours($overtimeHours)
            ->addMinutes($overtimeMinutes)
            ->diff(Carbon::createFromFormat('H:i:s', $scheduledOut))
            ->format('%H:%I:%S');
        $record->save();
    }

    private function upsertLeave(Employee $employee, Carbon $date, Schedule $schedule): void
    {
        $record = Leave::where('emp_id', $employee->id)
            ->whereDate('leave_date', $date->toDateString())
            ->where('type', 1)
            ->first();

        if (!$record) {
            $record = new Leave();
            $record->uid = 0;
            $record->emp_id = $employee->id;
            $record->state = 1;
            $record->type = 1;
            $record->leave_date = $date->toDateString();
        }

        $record->leave_time = $schedule->time_out;
        $record->status = 1;
        $record->save();
    }

    private function buildAttendanceTime(string $scheduledTime, bool $isLate): string
    {
        $baseTime = Carbon::createFromFormat('H:i:s', $scheduledTime);

        if ($isLate) {
            return $baseTime->copy()->addMinutes(random_int(5, 55))->format('H:i:s');
        }

        return $baseTime->copy()->subMinutes(random_int(0, 12))->format('H:i:s');
    }

    private function buildOvertimeDuration(string $scheduledOut): string
    {
        $baseTime = Carbon::createFromFormat('H:i:s', $scheduledOut);
        $checkout = $baseTime->copy()->addHours(random_int(1, 3))->addMinutes(random_int(10, 45));

        return $baseTime->diff($checkout)->format('%H:%I:%S');
    }

    private function roll(int $chance): bool
    {
        return random_int(1, 100) <= $chance;
    }
}
