<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaceAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.attendance_face.token' => 'testing-token']);
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_rejects_requests_without_the_token()
    {
        $response = $this->postJson('/api/mark-face-attendance', [
            'emp_id' => 111,
        ]);

        $response->assertStatus(401);
    }

    public function test_it_marks_attendance_for_a_known_employee()
    {
        $employeeId = $this->createEmployeeWithSchedule('23:59:59');

        $response = $this->postJson('/api/mark-face-attendance', [
            'emp_id' => $employeeId,
        ], [
            'X-ATTENDANCE-TOKEN' => 'testing-token',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Attendance marked successfully.',
            ]);

        $this->assertDatabaseHas('attendances', [
            'emp_id' => $employeeId,
            'attendance_date' => '2026-06-03',
            'attendance_time' => '12:00:00',
            'status' => 1,
            'type' => 0,
        ]);
    }

    public function test_it_marks_late_attendance_and_creates_late_time_record()
    {
        $employeeId = $this->createEmployeeWithSchedule('00:00:00');

        $response = $this->postJson('/api/mark-face-attendance', [
            'emp_id' => $employeeId,
        ], [
            'X-ATTENDANCE-TOKEN' => 'testing-token',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('attendances', [
            'emp_id' => $employeeId,
            'attendance_date' => '2026-06-03',
            'attendance_time' => '12:00:00',
            'status' => 0,
            'type' => 0,
        ]);

        $this->assertDatabaseHas('latetimes', [
            'emp_id' => $employeeId,
            'latetime_date' => '2026-06-03',
        ]);
    }

    public function test_it_rejects_duplicate_attendance_for_the_same_day()
    {
        $employeeId = $this->createEmployeeWithSchedule('23:59:59');

        $headers = ['X-ATTENDANCE-TOKEN' => 'testing-token'];

        $firstResponse = $this->postJson('/api/mark-face-attendance', [
            'emp_id' => $employeeId,
        ], $headers);

        $firstResponse->assertOk();

        $secondResponse = $this->postJson('/api/mark-face-attendance', [
            'emp_id' => $employeeId,
        ], $headers);

        $secondResponse->assertStatus(409);

        $this->assertSame(1, DB::table('attendances')->where('emp_id', $employeeId)->count());
    }

    private function createEmployeeWithSchedule(string $timeIn): int
    {
        $now = Carbon::now();

        $employeeId = DB::table('employees')->insertGetId([
            'name' => 'Test Employee '.uniqid(),
            'position' => 'Staff',
            'email' => 'test-'.uniqid().'@example.com',
            'pin_code' => null,
            'permissions' => null,
            'email_verified_at' => null,
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $scheduleId = DB::table('schedules')->insertGetId([
            'slug' => 'schedule-'.uniqid(),
            'time_in' => $timeIn,
            'time_out' => '18:00:00',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('schedule_employees')->insert([
            'emp_id' => $employeeId,
            'schedule_id' => $scheduleId,
        ]);

        return $employeeId;
    }
}
