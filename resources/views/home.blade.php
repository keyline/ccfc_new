@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show autoHideAlert" role="alert">
            <span class="alert-icon"><i class="fas fa-check" aria-hidden="true"></i></span>
            <div>
                <strong>Success</strong>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="dashboard-metrics">
        <article class="dashboard-metric">
            <div class="metric-icon metric-icon-red">
                <i class="fas fa-users" aria-hidden="true"></i>
            </div>
            <div class="metric-copy">
                <span class="metric-label">Total members</span>
                <strong>{{ number_format($totalMembers) }}</strong>
                <small>Registered member accounts</small>
            </div>
            @can('user_access')
                <a href="{{ route('admin.users.index') }}" class="metric-link" aria-label="View all members">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endcan
        </article>

        <article class="dashboard-metric">
            <div class="metric-icon metric-icon-green">
                <i class="fas fa-user-check" aria-hidden="true"></i>
            </div>
            <div class="metric-copy">
                <span class="metric-label">Verified members</span>
                <strong>{{ number_format($verifiedMembers) }}</strong>
                <small>{{ $verificationRate }}% of all accounts</small>
            </div>
            <span class="metric-pill metric-pill-success">{{ $verificationRate }}%</span>
        </article>

        <article class="dashboard-metric">
            <div class="metric-icon metric-icon-blue">
                <i class="fas fa-user-plus" aria-hidden="true"></i>
            </div>
            <div class="metric-copy">
                <span class="metric-label">New this month</span>
                <strong>{{ number_format($newMembersThisMonth) }}</strong>
                <small>Member registrations</small>
            </div>
            <span class="metric-pill {{ $monthlyGrowth >= 0 ? 'metric-pill-success' : 'metric-pill-danger' }}">
                <i class="fas fa-arrow-{{ $monthlyGrowth >= 0 ? 'up' : 'down' }}" aria-hidden="true"></i>
                {{ abs($monthlyGrowth) }}%
            </span>
        </article>

        <article class="dashboard-metric">
            <div class="metric-icon metric-icon-amber">
                <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
            </div>
            <div class="metric-copy">
                <span class="metric-label">Pending dues</span>
                <strong>{{ $pendingDuesCount === null ? '—' : number_format($pendingDuesCount) }}</strong>
                <small>
                    @if($outstandingDues !== null)
                        ₹{{ number_format($outstandingDues, 2) }} outstanding
                    @else
                        No dues data available
                    @endif
                </small>
            </div>
            @can('monthly_dues_list')
                <a href="{{ route('admin.dues.list') }}" class="metric-link" aria-label="View pending dues">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endcan
        </article>
    </div>

    <div class="row dashboard-grid">
        <div class="col-xl-8">
            <section class="card dashboard-card dashboard-chart-card">
                <div class="card-header dashboard-card-header">
                    <div>
                        <span class="dashboard-card-kicker">Member activity</span>
                        <h2>Registration growth</h2>
                    </div>
                    <span class="dashboard-period">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                        Last 6 months
                    </span>
                </div>
                <div class="card-body">
                    <div class="chart-summary">
                        <div>
                            <span>Current month</span>
                            <strong>{{ number_format($newMembersThisMonth) }}</strong>
                        </div>
                        <span class="chart-trend {{ $monthlyGrowth >= 0 ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $monthlyGrowth >= 0 ? 'up' : 'down' }}" aria-hidden="true"></i>
                            {{ abs($monthlyGrowth) }}% from last month
                        </span>
                    </div>
                    <div class="dashboard-chart-wrap">
                        <canvas id="memberGrowthChart" aria-label="Member registration growth during the last six months"
                            role="img"></canvas>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card dashboard-card dashboard-health-card">
                <div class="card-header dashboard-card-header">
                    <div>
                        <span class="dashboard-card-kicker">Account health</span>
                        <h2>Member readiness</h2>
                    </div>
                    <i class="fas fa-shield-alt dashboard-header-icon" aria-hidden="true"></i>
                </div>
                <div class="card-body">
                    <div class="readiness-score">
                        <div class="readiness-ring" style="--readiness: {{ $verificationRate * 3.6 }}deg;">
                            <span>{{ $verificationRate }}%</span>
                        </div>
                        <div>
                            <strong>Email verification</strong>
                            <p>Verified member profiles improve communication reliability.</p>
                        </div>
                    </div>

                    <div class="readiness-bars">
                        <div class="readiness-row">
                            <div>
                                <span>Verified</span>
                                <strong>{{ number_format($verifiedMembers) }}</strong>
                            </div>
                            <div class="readiness-progress">
                                <span style="width: {{ $verificationRate }}%"></span>
                            </div>
                        </div>
                        <div class="readiness-row">
                            <div>
                                <span>Awaiting verification</span>
                                <strong>{{ number_format($unverifiedMembers) }}</strong>
                            </div>
                            <div class="readiness-progress muted">
                                <span style="width: {{ 100 - $verificationRate }}%"></span>
                            </div>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <span class="quick-actions-label">Quick actions</span>
                        <div class="quick-action-grid">
                            @can('user_access')
                                <a href="{{ route('admin.users.index') }}">
                                    <i class="fas fa-user-friends" aria-hidden="true"></i>
                                    <span>Members</span>
                                </a>
                            @endcan
                            @can('content_page_access')
                                <a href="{{ route('admin.content-pages.index') }}">
                                    <i class="fas fa-file-alt" aria-hidden="true"></i>
                                    <span>Pages</span>
                                </a>
                            @endcan
                            @can('gallery_access')
                                <a href="{{ route('admin.galleries.index') }}">
                                    <i class="fas fa-images" aria-hidden="true"></i>
                                    <span>Gallery</span>
                                </a>
                            @endcan
                            <a href="{{ route('admin.settinglist') }}">
                                <i class="fas fa-sliders-h" aria-hidden="true"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="card dashboard-card recent-members-card">
        <div class="card-header dashboard-card-header">
            <div>
                <span class="dashboard-card-kicker">Latest activity</span>
                <h2>Recently added members</h2>
            </div>
            @can('user_access')
                <a href="{{ route('admin.users.index') }}" class="dashboard-view-all">
                    View all members
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endcan
        </div>
        <div class="card-body p-0">
            @if($recentMembers->isEmpty())
                <div class="dashboard-empty-state">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    <strong>No members yet</strong>
                    <span>New member accounts will appear here.</span>
                </div>
            @else
                <div class="table-responsive dashboard-table-wrap">
                    <table class="table dashboard-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Member code</th>
                                <th>Verification</th>
                                <th>Joined</th>
                                @can('user_show')
                                    <th class="text-right">Action</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentMembers as $member)
                                <tr>
                                    <td>
                                        <div class="member-cell">
                                            <span class="member-avatar">
                                                {{ strtoupper(substr(trim($member->name ?: 'M'), 0, 1)) }}
                                            </span>
                                            <div>
                                                <strong>{{ $member->name ?: 'Unnamed member' }}</strong>
                                                <span>{{ $member->email ?: 'No email available' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="member-code">{{ $member->user_code ?: '—' }}</span></td>
                                    <td>
                                        @if($member->email_verified_at)
                                            <span class="member-status verified">
                                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                Verified
                                            </span>
                                        @else
                                            <span class="member-status pending">
                                                <i class="fas fa-clock" aria-hidden="true"></i>
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="joined-date">{{ optional($member->created_at)->format('d M Y') ?: '—' }}</span>
                                    </td>
                                    @can('user_show')
                                        <td class="text-right">
                                            <a href="{{ route('admin.users.show', $member->id) }}" class="member-action"
                                                aria-label="View {{ $member->name ?: 'member' }}">
                                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('scripts')
    @parent
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script>
        (function () {
            var chartElement = document.getElementById('memberGrowthChart');

            if (!chartElement || typeof Chart === 'undefined') {
                return;
            }

            var context = chartElement.getContext('2d');
            var areaFill = context.createLinearGradient(0, 0, 0, 300);
            areaFill.addColorStop(0, 'rgba(197, 31, 51, 0.22)');
            areaFill.addColorStop(1, 'rgba(197, 31, 51, 0.01)');

            new Chart(context, {
                type: 'line',
                data: {
                    labels: {!! json_encode($growthLabels) !!},
                    datasets: [{
                        label: 'New members',
                        data: {!! json_encode($growthValues) !!},
                        borderColor: '#c51f33',
                        backgroundColor: areaFill,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#c51f33',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#c51f33',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverRadius: 6,
                        pointRadius: 4,
                        lineTension: 0.35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    layout: {
                        padding: { top: 10, right: 10, bottom: 0, left: 2 }
                    },
                    tooltips: {
                        backgroundColor: '#111827',
                        titleFontFamily: 'Inter',
                        bodyFontFamily: 'Inter',
                        titleFontSize: 11,
                        bodyFontSize: 11,
                        displayColors: false,
                        cornerRadius: 8,
                        xPadding: 12,
                        yPadding: 10,
                        callbacks: {
                            label: function (tooltipItem) {
                                return tooltipItem.yLabel + ' new member' + (tooltipItem.yLabel === 1 ? '' : 's');
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                fontColor: '#98a2b3',
                                fontFamily: 'Inter',
                                fontSize: 10,
                                padding: 10
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                color: 'rgba(148, 163, 184, 0.14)',
                                drawBorder: false,
                                zeroLineColor: 'rgba(148, 163, 184, 0.18)'
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0,
                                fontColor: '#98a2b3',
                                fontFamily: 'Inter',
                                fontSize: 10,
                                padding: 10
                            }
                        }]
                    }
                }
            });
        })();
    </script>
@endsection
