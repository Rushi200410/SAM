@extends('layouts.master')

@section('css')
<link rel="stylesheet" href="{{ URL::asset('plugins/chartist/css/chartist.min.css') }}">
<style>
    .dashboard-stat-card {
        border: none;
        border-radius: 8px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .dashboard-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(42, 49, 66, 0.12);
    }
    .dashboard-stat-card .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        opacity: 0.9;
    }
    .dashboard-stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .dashboard-stat-card .stat-label {
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    .dashboard-welcome {
        background: linear-gradient(135deg, #626ed4 0%, #4a54b8 100%);
        border-radius: 8px;
        color: #fff;
    }
    .dashboard-welcome .welcome-date {
        opacity: 0.85;
        font-size: 0.9rem;
    }
    .quick-action-btn {
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
    }
    #weekly-attendance-chart .ct-series-a .ct-line,
    #weekly-attendance-chart .ct-series-a .ct-point {
        stroke: #02a499;
    }
    #weekly-attendance-chart .ct-series-a .ct-area {
        fill: #02a499;
        fill-opacity: 0.15;
    }
    #weekly-attendance-chart .ct-series-b .ct-line,
    #weekly-attendance-chart .ct-series-b .ct-point {
        stroke: #ec4561;
    }
    #weekly-attendance-chart .ct-series-b .ct-area {
        fill: #ec4561;
        fill-opacity: 0.1;
    }
    #today-breakdown-chart .ct-series-a .ct-slice-donut {
        stroke: #02a499;
    }
    #today-breakdown-chart .ct-series-b .ct-slice-donut {
        stroke: #ec4561;
    }
    #today-breakdown-chart .ct-series-c .ct-slice-donut {
        stroke: #f8b425;
    }
    .chart-legend {
        display: flex;
        gap: 1.25rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }
    .chart-legend-item {
        display: flex;
        align-items: center;
        font-size: 0.8125rem;
        color: #6c757d;
    }
    .chart-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 6px;
    }
    .recent-attendance-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        border-top: none;
        font-weight: 600;
    }
    .recent-attendance-table td {
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .empty-state {
        padding: 2.5rem 1rem;
        text-align: center;
        color: #adb5bd;
    }
    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        display: block;
    }
</style>
@endsection

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title">Dashboard</h4>
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Attendance overview &amp; insights</li>
    </ol>
</div>
@endsection

@section('content')
@include('includes.flash')

{{-- Welcome banner --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card dashboard-welcome mb-0">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="text-white mb-1">Welcome back, {{ auth()->user()->name ?? 'Admin' }}</h4>
                        <p class="welcome-date mb-0">
                            <i class="ti-calendar mr-1"></i>
                            {{ $today->format('l, F j, Y') }}
                            &mdash; Here's today's attendance snapshot.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                        <a href="{{ route('check') }}" class="btn btn-light btn-sm quick-action-btn mr-1">
                            <i class="ti-check-box mr-1"></i> Mark Attendance
                        </a>
                        <a href="{{ route('attendance') }}" class="btn btn-outline-light btn-sm quick-action-btn">
                            <i class="ti-list mr-1"></i> View Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Stat cards --}}
<div class="row">
    <div class="col-xl col-lg-4 col-md-6 mb-4">
        <div class="card dashboard-stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-1">Total Students</p>
                        <h3 class="stat-value text-primary mb-0">{{ $totalEmp }}</h3>
                    </div>
                    <div class="stat-icon bg-primary text-white">
                        <i class="ti-user"></i>
                    </div>
                </div>
                <a href="{{ route('students.index') }}" class="small text-muted d-block mt-3">
                    View all <i class="mdi mdi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl col-lg-4 col-md-6 mb-4">
        <div class="card dashboard-stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-1">On Time Today</p>
                        <h3 class="stat-value text-success mb-0">{{ $ontimeEmp }}</h3>
                    </div>
                    <div class="stat-icon bg-success text-white">
                        <i class="ti-check-box"></i>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 4px;">
                    <div class="progress-bar bg-success" style="width: {{ $percentageOntime }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-lg-4 col-md-6 mb-4">
        <div class="card dashboard-stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-1">Late Today</p>
                        <h3 class="stat-value text-danger mb-0">{{ $latetimeEmp }}</h3>
                    </div>
                    <div class="stat-icon bg-danger text-white">
                        <i class="ti-alert"></i>
                    </div>
                </div>
                @if($allAttendance > 0)
                    <small class="text-muted d-block mt-3">
                        {{ round(($latetimeEmp / $allAttendance) * 100, 1) }}% of check-ins
                    </small>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl col-lg-4 col-md-6 mb-4">
        <div class="card dashboard-stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-1">Absent Today</p>
                        <h3 class="stat-value text-warning mb-0">{{ $absentToday }}</h3>
                    </div>
                    <div class="stat-icon bg-warning text-white">
                        <i class="ti-na"></i>
                    </div>
                </div>
                @if($totalEmp > 0)
                    <small class="text-muted d-block mt-3">
                        {{ round(($absentToday / $totalEmp) * 100, 1) }}% of enrolled
                    </small>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl col-lg-4 col-md-6 mb-4">
        <div class="card dashboard-stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-1">On-Time Rate</p>
                        <h3 class="stat-value text-info mb-0">{{ $percentageOntime }}%</h3>
                    </div>
                    <div class="stat-icon bg-info text-white">
                        <i class="ti-pulse"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-3">{{ $allAttendance }} check-ins today</small>
            </div>
        </div>
    </div>
</div>

{{-- Charts row --}}
<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mt-0 header-title">Weekly Attendance</h4>
                        <p class="text-muted mb-0 small">On-time vs late over the last 7 days</p>
                    </div>
                    <div class="chart-legend mb-0">
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background:#02a499;"></span> On Time
                        </span>
                        <span class="chart-legend-item">
                            <span class="chart-legend-dot" style="background:#ec4561;"></span> Late
                        </span>
                    </div>
                </div>
                <div id="weekly-attendance-chart" style="height: 280px;"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="mt-0 header-title">Today's Breakdown</h4>
                <p class="text-muted mb-3 small">Attendance status distribution</p>
                @if($allAttendance > 0 || $absentToday > 0)
                    <div id="today-breakdown-chart" class="ct-chart ct-perfect-fourth mx-auto" style="max-width: 220px;"></div>
                @else
                    <div class="empty-state py-4">
                        <i class="ti-pie-chart"></i>
                        <p class="mb-0 small">No attendance data yet today</p>
                    </div>
                @endif
                <div class="chart-legend justify-content-center">
                    <span class="chart-legend-item">
                        <span class="chart-legend-dot" style="background:#02a499;"></span>
                        On Time ({{ $ontimeEmp }})
                    </span>
                    <span class="chart-legend-item">
                        <span class="chart-legend-dot" style="background:#ec4561;"></span>
                        Late ({{ $latetimeEmp }})
                    </span>
                    <span class="chart-legend-item">
                        <span class="chart-legend-dot" style="background:#f8b425;"></span>
                        Absent ({{ $absentToday }})
                    </span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Active Schedules</span>
                    <a href="{{ url('/schedule') }}" class="h5 mb-0 text-primary">{{ $totalSchedule }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent activity --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mt-0 header-title">Recent Check-ins</h4>
                        <p class="text-muted mb-0 small">Latest attendance records for today</p>
                    </div>
                    <a href="{{ route('attendance') }}" class="btn btn-outline-primary btn-sm">
                        View all logs <i class="mdi mdi-arrow-right ml-1"></i>
                    </a>
                </div>

                @if($recentAttendance->isEmpty())
                    <div class="empty-state">
                        <i class="ti-clipboard"></i>
                        <p class="mb-0">No check-ins recorded yet today.</p>
                        <a href="{{ route('check') }}" class="btn btn-primary btn-sm mt-3">Record attendance</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover recent-attendance-table mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAttendance as $record)
                                    <tr>
                                        <td>
                                            <strong>{{ $record->employee->name ?? 'Unknown' }}</strong>
                                            <small class="text-muted d-block">ID #{{ $record->emp_id }}</small>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($record->attendance_time)->format('h:i A') }}</td>
                                        <td>
                                            @if($record->status == 1)
                                                <span class="badge badge-success badge-pill">On Time</span>
                                            @else
                                                <span class="badge badge-danger badge-pill">Late</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('plugins/chartist/js/chartist.min.js') }}"></script>
<script src="{{ URL::asset('plugins/chartist/js/chartist-plugin-tooltip.min.js') }}"></script>
<script>
    window.dashboardData = {
        weeklyLabels: @json($weeklyLabels),
        weeklyOntime: @json($weeklyOntime),
        weeklyLate: @json($weeklyLate),
        todayOntime: {{ $ontimeEmp }},
        todayLate: {{ $latetimeEmp }},
        todayAbsent: {{ $absentToday }}
    };
</script>
<script src="{{ URL::asset('assets/pages/dashboard.js') }}"></script>
@endsection
