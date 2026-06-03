<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToAttendanceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['attendance_date', 'emp_id', 'type'], 'attendances_date_emp_type_index');
            $table->index(['attendance_date', 'status'], 'attendances_date_status_index');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['leave_date', 'emp_id', 'type'], 'leaves_date_emp_type_index');
            $table->index(['leave_date', 'status'], 'leaves_date_status_index');
        });

        Schema::table('checks', function (Blueprint $table) {
            $table->index('emp_id', 'checks_emp_id_index');
        });

        Schema::table('latetimes', function (Blueprint $table) {
            $table->index(['emp_id', 'latetime_date'], 'latetimes_emp_date_index');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->index(['emp_id', 'overtime_date'], 'overtimes_emp_date_index');
        });

        Schema::table('schedule_employees', function (Blueprint $table) {
            $table->unique(['emp_id', 'schedule_id'], 'schedule_employees_unique');
            $table->index('schedule_id', 'schedule_employees_schedule_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schedule_employees', function (Blueprint $table) {
            $table->dropUnique('schedule_employees_unique');
            $table->dropIndex('schedule_employees_schedule_id_index');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropIndex('overtimes_emp_date_index');
        });

        Schema::table('latetimes', function (Blueprint $table) {
            $table->dropIndex('latetimes_emp_date_index');
        });

        Schema::table('checks', function (Blueprint $table) {
            $table->dropIndex('checks_emp_id_index');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('leaves_date_emp_type_index');
            $table->dropIndex('leaves_date_status_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_date_emp_type_index');
            $table->dropIndex('attendances_date_status_index');
        });
    }
}
