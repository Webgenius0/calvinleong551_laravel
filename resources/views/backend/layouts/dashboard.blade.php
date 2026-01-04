@extends('backend.app')
@push('styles')
    <style>
        /* Avatar styling */
        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            background: linear-gradient(45deg, #5b7fff, #9a4dff);
        }

        /* Counter icon styling */
        .counter-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #5b7fff, #9a4dff);
        }

        /* Chart container */
        #user-registration-chart {
            width: 100%;
            min-height: 350px;
        }

        /* Recent Users Container - Enhanced Scrollbar */
        .recent-users-container {
            max-height: calc(100vh - 400px);
            min-height: 200px;
            overflow-y: auto;
            padding: 10px 15px;
            scroll-behavior: smooth;
            position: relative;
        }

        /* Enhanced scrollbar styling */
        .recent-users-container::-webkit-scrollbar {
            width: 10px;
            background-color: #f8f9fa;
        }

        .recent-users-container::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #f8f9fa;
        }

        .recent-users-container::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #5b7fff, #9a4dff);
            border-radius: 10px;
            border: 2px solid #f8f9fa;
            box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.1);
        }

        .recent-users-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #4a6cf7, #8a3cf7);
        }

        .recent-users-container::-webkit-scrollbar-button {
            display: none;
        }

        /* Firefox scrollbar */
        .recent-users-container {
            scrollbar-width: thin;
            scrollbar-color: #5b7fff #f8f9fa;
        }

        /* Scroll indicators */
        .scroll-indicator {
            position: absolute;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.95), transparent);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            color: #5b7fff;
            font-size: 20px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .scroll-indicator.show {
            opacity: 1;
            pointer-events: auto;
        }

        .scroll-indicator.top {
            top: 0;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.95), transparent);
            cursor: pointer;
        }

        .scroll-indicator.bottom {
            bottom: 0;
            background: linear-gradient(to top, rgba(255, 255, 255, 0.95), transparent);
            cursor: pointer;
        }

        .scroll-indicator i {
            animation: bounce 2s infinite;
            background: rgba(91, 127, 255, 0.1);
            border-radius: 50%;
            padding: 5px;
            transition: transform 0.3s;
        }

        .scroll-indicator:hover i {
            transform: scale(1.2);
            background: rgba(91, 127, 255, 0.2);
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-5px);
            }

            60% {
                transform: translateY(-3px);
            }
        }

        /* Scroll hint */
        .recent-users-container::after {
            content: 'Scroll for more';
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: linear-gradient(45deg, #5b7fff, #9a4dff);
            color: white;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .recent-users-container:hover::after {
            opacity: 0.8;
        }

        /* User item styling */
        .user-item {
            transition: all 0.2s ease;
            padding: 12px 15px;
            border-left: 3px solid transparent;
        }

        .user-item:hover {
            background-color: rgba(91, 127, 255, 0.05);
            transform: translateX(2px);
            border-left-color: #5b7fff;
        }

        /* Monthly table scrollbar */
        .monthly-table-scroll {
            max-height: 400px;
            scrollbar-width: thin;
            scrollbar-color: #5b7fff #f5f5f5;
        }

        .monthly-table-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .monthly-table-scroll::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        .monthly-table-scroll::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #5b7fff, #9a4dff);
            border-radius: 10px;
        }

        .monthly-table-scroll::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #4a6cf7, #8a3cf7);
        }

        /* Sticky table header */
        .sticky-top {
            z-index: 1;
        }

        /* Progress bar gradient */
        .bg-gradient-primary {
            background: linear-gradient(45deg, #5b7fff, #9a4dff);
        }

        /* Card full height */
        .card.h-100 {
            display: flex;
            flex-direction: column;
        }

        .card.h-100 .card-body {
            flex: 1;
            overflow: hidden;
        }

        /* New user badge */
        .position-relative .badge {
            transform: translate(50%, -50%);
        }

        /* Empty state styling */
        .list-group-item.text-center {
            border: none !important;
        }
    </style>
@endpush
@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app">

            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Dashboard</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ROW-1: Summary Cards -->
                <div class="row">
                    <!-- Total Users -->
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">{{ $totalUsers ?? 0 }}</h3>
                                        <p class="text-muted fs-13 mb-0">Total Users</p>
                                    </div>
                                    <div class="col col-auto">
                                        <div class="counter-icon bg-primary box-shadow-primary rounded-circle p-3">
                                            <i class="fe fe-users fs-20 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="fe fe-arrow-up"></i>
                                        {{ $userGrowthPercentage ?? 0 }}%</span>
                                    <span class="text-muted ms-2">Since last month</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Users -->
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">{{ $activeUsers ?? 0 }}</h3>
                                        <p class="text-muted fs-13 mb-0">Active Users</p>
                                    </div>
                                    <div class="col col-auto">
                                        <div class="counter-icon bg-success box-shadow-success rounded-circle p-3">
                                            <i class="fe fe-user-check fs-20 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="fe fe-arrow-up"></i>
                                        {{ $activeUsersPercentage ?? 0 }}%</span>
                                    <span class="text-muted ms-2">of total users</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- This Month Registrations -->
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">{{ $thisMonthUsers ?? 0 }}</h3>
                                        <p class="text-muted fs-13 mb-0">This Month</p>
                                    </div>
                                    <div class="col col-auto">
                                        <div class="counter-icon bg-info box-shadow-info rounded-circle p-3">
                                            <i class="fe fe-calendar fs-20 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="{{ ($monthlyGrowth ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fe fe-arrow-{{ ($monthlyGrowth ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($monthlyGrowth ?? 0) }}%
                                    </span>
                                    <span class="text-muted ms-2">Since last month</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- This Week Registrations -->
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">{{ $thisWeekUsers ?? 0 }}</h3>
                                        <p class="text-muted fs-13 mb-0">This Week</p>
                                    </div>
                                    <div class="col col-auto">
                                        <div class="counter-icon bg-warning box-shadow-warning rounded-circle p-3">
                                            <i class="fe fe-clock fs-20 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="{{ ($weeklyGrowth ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fe fe-arrow-{{ ($weeklyGrowth ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($weeklyGrowth ?? 0) }}%
                                    </span>
                                    <span class="text-muted ms-2">Since last week</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ROW-1 END -->

                <!-- ROW-2: Charts and User List -->
                <div class="row">
                    <!-- User Registration Chart -->
                    <div class="col-lg-8 col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">User Registration Analytics</h3>
                                <div class="ms-auto">
                                    <select class="form-control form-select" id="chartPeriod">
                                        <option value="yearly">Yearly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="weekly">Weekly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Chart Container -->
                                <div id="user-registration-chart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users with Enhanced Scrollbar -->
                    <div class="col-lg-4 col-md-12">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">Recent Registrations</h3>
                                <div>
                                    <a href="" class="btn btn-sm btn-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body p-0 position-relative">
                                <!-- Scroll up indicator -->
                                <div class="scroll-indicator top" style="display: none;">
                                    <i class="fe fe-chevron-up"></i>
                                </div>

                                <!-- Scrollable container -->
                                <div class="recent-users-container" id="recentUsersScroll">
                                    <!-- Users list -->
                                    <div class="list-group list-group-flush">
                                        @forelse($recentUsers ?? [] as $index => $user)
                                            <div class="list-group-item border-bottom user-item">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3 position-relative">
                                                        <span
                                                            class="avatar avatar-md rounded-circle bg-primary d-flex align-items-center justify-content-center">
                                                            {{ substr($user->name ?? 'U', 0, 1) }}
                                                        </span>
                                                        @if ($index < 3)
                                                            <span
                                                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success"
                                                                style="font-size: 8px; padding: 2px 4px;">
                                                                New
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0">{{ $user->name ?? 'N/A' }}</h6>
                                                        <small class="text-muted d-block">
                                                            {{ $user->email ?? 'No email' }}
                                                        </small>
                                                    </div>
                                                    <div class="text-end ms-2">
                                                        <small class="text-muted d-block">
                                                            <i
                                                                class="fe fe-calendar me-1"></i>{{ $user->created_at ? $user->created_at->format('M d') : 'N/A' }}
                                                        </small>
                                                        <small class="text-muted">
                                                            <i
                                                                class="fe fe-clock me-1"></i>{{ $user->created_at ? $user->created_at->format('h:i A') : 'N/A' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="list-group-item text-center py-5">
                                                <div class="mb-3">
                                                    <i class="fe fe-users fs-40 text-muted"></i>
                                                </div>
                                                <p class="text-muted mb-0">No users found</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Scroll down indicator -->
                                <div class="scroll-indicator bottom" style="display: none;">
                                    <i class="fe fe-chevron-down"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Showing {{ min(count($recentUsers ?? []), 10) }} of {{ $totalUsers ?? 0 }} users
                                    </small>
                                    <small class="text-muted">
                                        <i class="fe fe-refresh-cw me-1"></i>Updated just now
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ROW-2 END -->

                <!-- ROW-3: User Statistics -->
                <div class="row">
                    <!-- Monthly Breakdown -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Monthly User Registration Breakdown ({{ date('Y') }})</h3>
                            </div>
                            <div class="card-body p-0">
                                <!-- Table with fixed header and scrollable body -->
                                <div class="table-responsive monthly-table-scroll" style="max-height: 400px;">
                                    <table class="table table-bordered text-nowrap border-bottom mb-0">
                                        <thead class="sticky-top bg-light" style="top: 0;">
                                            <tr>
                                                <th class="wd-15p border-bottom-0">Month</th>
                                                <th class="wd-15p border-bottom-0">New Users</th>
                                                <th class="wd-20p border-bottom-0">Growth</th>
                                                <th class="wd-25p border-bottom-0">Percentage</th>
                                                <th class="wd-25p border-bottom-0">Trend</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($monthlyData ?? [] as $data)
                                                <tr>
                                                    <td><strong>{{ $data['month'] ?? 'N/A' }}</strong></td>
                                                    <td><span
                                                            class="badge bg-primary rounded-pill px-3">{{ $data['count'] ?? 0 }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i
                                                                class="fe fe-arrow-{{ ($data['growth'] ?? 0) >= 0 ? 'up text-success' : 'down text-danger' }} me-2"></i>
                                                            <span
                                                                class="{{ ($data['growth'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                                {{ abs($data['growth'] ?? 0) }}%
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                                <div class="progress-bar bg-gradient-primary"
                                                                    style="width: {{ $data['percentage'] ?? 0 }}%">
                                                                </div>
                                                            </div>
                                                            <span class="text-muted"
                                                                style="font-size: 12px; min-width: 45px;">
                                                                {{ round($data['percentage'] ?? 0, 1) }}%
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $growth = $data['growth'] ?? 0;
                                                        @endphp
                                                        @if ($growth > 20)
                                                            <span class="badge bg-success rounded-pill px-3">High
                                                                Growth</span>
                                                        @elseif($growth > 0)
                                                            <span class="badge bg-info rounded-pill px-3">Growing</span>
                                                        @elseif($growth == 0)
                                                            <span
                                                                class="badge bg-secondary rounded-pill px-3">Stable</span>
                                                        @else
                                                            <span
                                                                class="badge bg-danger rounded-pill px-3">Declining</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-5">
                                                        <i class="fe fe-database fs-40 text-muted mb-3 d-block"></i>
                                                        <p class="text-muted">No data available</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ROW-3 END -->

            </div>
        </div>
    </div>
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        $(document).ready(function() {
            console.log('Document ready, initializing chart...');

            // Initialize the chart
            initChart('yearly');

            // Handle period change
            $('#chartPeriod').change(function() {
                console.log('Period changed to:', $(this).val());
                initChart($(this).val());
            });

            function initChart(period) {
                console.log('Initializing chart with period:', period);

                // Check if chart container exists
                var chartElement = document.querySelector("#user-registration-chart");
                if (!chartElement) {
                    console.error('Chart container #user-registration-chart not found!');
                    return;
                }

                // Destroy existing chart if any
                if (window.userChart && typeof window.userChart.destroy === 'function') {
                    console.log('Destroying existing chart');
                    window.userChart.destroy();
                }

                // Get data based on period
                let chartData = getChartData(period);
                console.log('Chart data:', chartData);

                // Chart options
                var options = {
                    series: [{
                        name: 'New Users',
                        data: chartData.data
                    }],
                    chart: {
                        type: 'area',
                        height: 350,
                        toolbar: {
                            show: true
                        },
                        zoom: {
                            enabled: true
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    colors: ['#5b7fff'],
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3,
                        colors: ['#5b7fff']
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.2,
                            stops: [0, 90, 100]
                        }
                    },
                    markers: {
                        size: 5,
                        colors: ['#5b7fff'],
                        strokeColors: '#fff',
                        strokeWidth: 2,
                        hover: {
                            size: 7
                        }
                    },
                    xaxis: {
                        categories: chartData.categories,
                        labels: {
                            style: {
                                colors: '#6b7280',
                                fontSize: '12px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#6b7280',
                                fontSize: '12px'
                            },
                            formatter: function(val) {
                                return Math.floor(val);
                            }
                        },
                        title: {
                            text: 'Number of Users',
                            style: {
                                color: '#6b7280',
                                fontSize: '12px'
                            }
                        },
                        min: 0
                    },
                    grid: {
                        borderColor: '#e5e7eb',
                        strokeDashArray: 4,
                        padding: {
                            top: 20,
                            right: 20,
                            bottom: 0,
                            left: 20
                        }
                    },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function(val) {
                                return val + " users"
                            }
                        }
                    }
                };

                try {
                    console.log('Creating new chart instance...');
                    window.userChart = new ApexCharts(chartElement, options);
                    window.userChart.render();
                    console.log('Chart rendered successfully');
                } catch (error) {
                    console.error('Error creating chart:', error);
                }
            }

            function getChartData(period) {
                // Sample data - In production, fetch this from your API
                var sampleData = {
                    yearly: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct',
                            'Nov', 'Dec'
                        ],
                        data: [65, 78, 66, 89, 92, 105, 120, 135, 148, 165, 182, 210]
                    },
                    monthly: {
                        categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                        data: [45, 68, 72, 89]
                    },
                    weekly: {
                        categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        data: [25, 38, 42, 55, 68, 72, 85]
                    }
                };

                return sampleData[period] || sampleData.yearly;
            }

            // Enhanced scroll functionality for Recent Registrations
            function initRecentUsersScroll() {
                const container = document.getElementById('recentUsersScroll');
                const topIndicator = document.querySelector('.scroll-indicator.top');
                const bottomIndicator = document.querySelector('.scroll-indicator.bottom');

                if (!container) {
                    console.error('Recent users container not found!');
                    return;
                }

                function updateScrollIndicators() {
                    if (!container || !topIndicator || !bottomIndicator) return;

                    const hasScroll = container.scrollHeight > container.clientHeight + 5;

                    if (hasScroll) {
                        // Show/hide top indicator
                        if (container.scrollTop > 20) {
                            topIndicator.style.display = 'flex';
                            topIndicator.classList.add('show');
                        } else {
                            topIndicator.style.display = 'none';
                            topIndicator.classList.remove('show');
                        }

                        // Show/hide bottom indicator
                        if (container.scrollTop + container.clientHeight < container.scrollHeight - 20) {
                            bottomIndicator.style.display = 'flex';
                            bottomIndicator.classList.add('show');
                        } else {
                            bottomIndicator.style.display = 'none';
                            bottomIndicator.classList.remove('show');
                        }
                    } else {
                        // Hide both indicators if no scroll needed
                        topIndicator.style.display = 'none';
                        bottomIndicator.style.display = 'none';
                        topIndicator.classList.remove('show');
                        bottomIndicator.classList.remove('show');
                    }
                }

                // Initial check
                setTimeout(updateScrollIndicators, 100);

                // Update on scroll
                container.addEventListener('scroll', updateScrollIndicators);

                // Update on resize
                window.addEventListener('resize', updateScrollIndicators);

                // Click indicators to scroll
                topIndicator.addEventListener('click', () => {
                    container.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });

                bottomIndicator.addEventListener('click', () => {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                });

                // Add keyboard navigation
                container.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowDown') {
                        container.scrollBy({
                            top: 50,
                            behavior: 'smooth'
                        });
                        e.preventDefault();
                    } else if (e.key === 'ArrowUp') {
                        container.scrollBy({
                            top: -50,
                            behavior: 'smooth'
                        });
                        e.preventDefault();
                    } else if (e.key === 'Home') {
                        container.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                        e.preventDefault();
                    } else if (e.key === 'End') {
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: 'smooth'
                        });
                        e.preventDefault();
                    }
                });

                // Make container focusable for keyboard navigation
                container.setAttribute('tabindex', '0');

                // Add scroll snapping for better UX
                const userItems = container.querySelectorAll('.user-item');
                if (userItems.length > 0) {
                    container.style.scrollSnapType = 'y proximity';
                    userItems.forEach(item => {
                        item.style.scrollSnapAlign = 'start';
                    });
                }
            }

            // Auto-adjust recent users container height
            function adjustRecentUsersHeight() {
                var container = document.getElementById('recentUsersScroll');
                if (!container) return;

                // Calculate available height
                var cardHeader = container.closest('.card').querySelector('.card-header');
                var cardFooter = container.closest('.card').querySelector('.card-footer');
                var headerHeight = cardHeader ? cardHeader.offsetHeight : 0;
                var footerHeight = cardFooter ? cardFooter.offsetHeight : 0;
                var cardHeight = container.closest('.card').offsetHeight;

                // Set max height (card height minus header and footer)
                var maxHeight = cardHeight - headerHeight - footerHeight - 60; // Extra padding for indicators
                var minHeight = 200; // Minimum height

                container.style.maxHeight = Math.max(minHeight, maxHeight) + 'px';

                // Update scroll indicators after resize
                setTimeout(() => {
                    const event = new Event('scroll');
                    container.dispatchEvent(event);
                }, 150);
            }

            // Initialize all functions
            adjustRecentUsersHeight();
            initRecentUsersScroll();

            // Re-adjust on window resize
            window.addEventListener('resize', adjustRecentUsersHeight);

            // Re-initialize after dynamic content loads (if any)
            setTimeout(() => {
                adjustRecentUsersHeight();
                if (window.userChart) {
                    window.userChart.update();
                }
            }, 500);
        });
    </script>
@endsection

