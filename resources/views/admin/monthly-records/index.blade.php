@extends('layouts.admin')

@section('admin-content')
@php
    $periodNoun   = ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$period];
    $bookingCount = $records->count();
    $venuesUsed   = $venueSummary->filter(fn ($c) => $c > 0)->count();
    $busiestVenue = $venueSummary->filter(fn ($c) => $c > 0)->sortDesc()->keys()->first();
    $busiestCount = $busiestVenue ? $venueSummary[$busiestVenue] : 0;
@endphp

<div class="records-page">
    <section class="records-hero" style="--records-hero-image: url('{{ asset('img/facilities/paghiusa-hall.jpg') }}')">
        <div>
            <p class="records-eyebrow">{{ ucfirst($period) }} Report</p>
            <div class="records-period">
                <a class="records-step" href="{{ route('admin.monthly-records.index', $prevQuery) }}" aria-label="Previous {{ $periodNoun }}: {{ $prevLabel }}" title="{{ $prevLabel }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                </a>
                <h1>{{ $periodLabel }}</h1>
                <a class="records-step" href="{{ route('admin.monthly-records.index', $nextQuery) }}" aria-label="Next {{ $periodNoun }}: {{ $nextLabel }}" title="{{ $nextLabel }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </div>
            <p class="records-hero-sub">Facility bookings, equipment, rental fees and occupancy for the {{ $periodNoun }}.</p>
        </div>

        <div class="records-tools">
            <!-- Report type + period filter -->
            <form method="GET" action="{{ route('admin.monthly-records.index') }}" class="records-filter">
                <div>
                    <label for="records-period">Report</label>
                    <select id="records-period" name="period" onchange="this.form.submit()">
                        @foreach(['monthly' => 'Monthly', 'weekly' => 'Weekly', 'daily' => 'Daily'] as $value => $text)
                            <option value="{{ $value }}" {{ $period === $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                @if($period === 'monthly')
                <div>
                    <label for="records-month">Month</label>
                    <select id="records-month" name="month">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="records-year">Year</label>
                    <select id="records-year" name="year">
                        {{-- Always includes the year being viewed (the report accepts 2000-2100) --}}
                        @foreach(range(min(2024, (int) $year), max((int) now()->year + 3, (int) $year)) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div>
                    <label for="records-date">{{ $period === 'weekly' ? 'Week of' : 'Date' }}</label>
                    <input id="records-date" type="date" name="date" value="{{ $reportDate }}" min="2000-01-01" max="2100-12-31">
                </div>
                @endif
                <button type="submit">View</button>
            </form>
            <a href="{{ route('admin.monthly-records.export', $period === 'monthly' ? ['period' => $period, 'month' => $month, 'year' => $year] : ['period' => $period, 'date' => $reportDate]) }}" class="records-print">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
                Download Excel
            </a>
            <button type="button" onclick="window.print()" class="records-print">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>
                Print
            </button>
        </div>
    </section>

    <!-- Key figures -->
    <section class="records-stats" aria-label="Key figures for {{ $periodLabel }}">
        <div class="records-stat is-dark">
            <p class="records-stat-label">Bookings</p>
            <p class="records-stat-value">{{ $bookingCount }}</p>
            <p class="records-stat-hint">Reserved this {{ $periodNoun }}</p>
        </div>
        <div class="records-stat">
            <p class="records-stat-label">Estimated rental fees</p>
            <p class="records-stat-value">₱{{ number_format($totalRentalFees, 2) }}</p>
            <p class="records-stat-hint">Based on published venue rates</p>
        </div>
        <div class="records-stat">
            <p class="records-stat-label">Venues used</p>
            <p class="records-stat-value">{{ $venuesUsed }}</p>
            <p class="records-stat-hint">With at least one booking</p>
        </div>
        <div class="records-stat">
            <p class="records-stat-label">Busiest venue</p>
            <p class="records-stat-value is-text">{{ $busiestVenue ?? '—' }}</p>
            <p class="records-stat-hint">{{ $busiestVenue ? $busiestCount . ' ' . \Illuminate\Support\Str::plural('booking', $busiestCount) : 'No bookings yet' }}</p>
        </div>
    </section>

    <!-- Records Table -->
    <div>
        <!-- Charts Section -->
        <h2 class="records-section-title">Trends</h2>
        <div class="records-charts">
            <!-- Monthly Trend Chart -->
            <div class="records-card">
                <h3>Booking Trend <span class="records-card-sub">{{ $trendLabel }}</span></h3>
                <div class="records-chart"><canvas id="monthlyTrendChart"></canvas></div>
            </div>

            <!-- Facility Usage Chart -->
            <div class="records-card">
                <h3>Facility Usage ({{ $periodLabel }})</h3>
                <div class="records-chart"><canvas id="facilityUsageChart"></canvas></div>
            </div>
        </div>

        <h2 class="records-section-title">Booking ledger</h2>
        {{-- Print-only gold banner, as on the paper report --}}
        <div class="records-print-banner">{{ mb_strtoupper($period === 'monthly' ? $periodLabel : "{$period} report: {$periodLabel}") }}</div>
        @php
            $equipmentCols = ['chairs' => 'Chairs', 'tables' => 'Long Tables', 'fans' => 'Electric Fan', 'rostrum' => 'Rostrum', 'flags' => 'Flag', 'led' => 'Led Wall', 'sound' => 'Sound'];
        @endphp
        @if($records->count() > 0)
            <div class="records-table-wrap">
            <table class="records-ledger">
                <thead>
                    <tr>
                        <th rowspan="2" class="is-formno">Form No.</th>
                        <th rowspan="2">Date Requested</th>
                        <th rowspan="2">Date of Activity</th>
                        <th rowspan="2">Time of Activity</th>
                        <th rowspan="2" class="is-purpose">Purpose</th>
                        <th rowspan="2">Requester</th>
                        <th rowspan="2">Contact Number</th>
                        <th rowspan="2">Venue</th>
                        <th colspan="{{ count($equipmentCols) }}" class="is-group">Facilities to be used <span>(qty.)</span></th>
                        <th rowspan="2">Remarks</th>
                    </tr>
                    <tr>
                        @foreach($equipmentCols as $label)
                            <th class="is-qty">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td class="is-formno"></td>
                            <td>{{ $record['date_requested'] }}</td>
                            <td>{{ $record['date_of_activity'] }}</td>
                            <td class="u-nowrap">{{ $record['time'] }}</td>
                            <td class="is-purpose">
                                {{ $record['purpose'] }}
                                @if($record['rental_fee'] > 0)
                                    <strong><em>(Rental Fee: PhP{{ number_format($record['rental_fee'], 2) }})</em></strong>
                                @endif
                            </td>
                            <td class="is-center">{{ $record['requester'] }}</td>
                            <td class="is-center">{{ $record['contact_no'] }}</td>
                            <td class="is-center">{{ $record['venue'] }}</td>
                            @foreach($equipmentCols as $key => $label)
                                <td class="is-qty">{{ $record['equipment'][$key] > 0 ? $record['equipment'][$key] : '' }}</td>
                            @endforeach
                            <td>{{ $record['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="records-empty">
                <p>No bookings found for {{ $periodLabel }}</p>
            </div>
        @endif

        {{-- Print-only footer: venue summary chart, rental total, signatories --}}
        @php
            $summaryLabel = $period === 'monthly' ? \Carbon\Carbon::create($year, $month, 1)->format('F, Y') : $periodLabel;
        @endphp
        <div class="records-print-summary">
            <h3>Summary of Venues/Facilities Utilized as of {{ $summaryLabel }}</h3>
            <canvas id="venueSummaryPrintChart" width="1100" height="380" role="img" aria-label="Bookings per venue, {{ $summaryLabel }}"></canvas>
            <p class="records-print-rental"><em>Rental of Venues:</em> <strong><em>PhP{{ number_format($totalRentalFees, 2) }}</em></strong></p>
            <div class="records-print-signs">
                <div>
                    <p class="records-print-sign-label">Prepared by:</p>
                    <p class="records-print-sign-name">{{ mb_strtoupper(auth()->user()->name) }}</p>
                    <p>{{ auth()->user()->position ?: 'GSU Administrator' }}</p>
                </div>
                <div>
                    <p class="records-print-sign-label">Noted by:</p>
                    <p class="records-print-sign-name">&nbsp;</p>
                    <p>Name / Position</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Venue Usage Summary -->
    <div>
        <h3 class="records-section-title">Venues utilized · {{ $periodLabel }}</h3>
        @php
            $maxVenueCount = max(1, (int) $allVenues->max());
        @endphp
        <div class="records-venues">
            @foreach($allVenues as $venue => $count)
                <div class="records-venue {{ $count > 0 ? '' : 'is-unused' }}">
                    <p class="records-venue-name" title="{{ $venue }}">{{ $venue }}</p>
                    <p class="records-venue-count">{{ $count }}</p>
                    <div class="records-venue-track" aria-hidden="true"><div class="records-venue-fill" style="width: {{ round($count / $maxVenueCount * 100) }}%"></div></div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Facility occupancy (formerly the Monthly overview page) -->
    <div class="records-occupancy">
        <h3 class="records-section-title">Facility occupancy · {{ $periodLabel }}</h3>
        <p class="records-note">Utilization is booked hours ÷ {{ number_format($calendarHours, 0) }} operating hours this {{ $periodNoun }} ({{ $periodDays }} {{ \Illuminate\Support\Str::plural('day', $periodDays) }} × 13 hours, 6:00 AM–7:00 PM).</p>
        <div class="pk-table-wrap">
            <table class="pk-table">
                <thead><tr><th>Facility</th><th class="u-text-right">Booked hours</th><th class="u-text-right">Utilization</th></tr></thead>
                <tbody>
                    @foreach ($occupancy as $row)
                        <tr>
                            <td class="pk-strong">{{ $row['name'] }}</td>
                            <td class="u-text-right">{{ number_format($row['booked_hours'], 2) }}</td>
                            <td class="u-text-right"><strong>{{ number_format($row['utilization'], 2) }}%</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<script>
window.addEventListener('load', function () {
// Monthly Trend Chart
const monthlyTrendData = {
    labels: @json(array_column($monthlyTrend, 'month')),
    datasets: [{
        label: 'Number of Bookings',
        data: @json(array_column($monthlyTrend, 'count')),
        borderColor: '#0f172a',
        backgroundColor: 'rgba(15, 23, 42, 0.08)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#0f172a',
        tension: 0.3,
        fill: true
    }]
};

new Chart(document.getElementById('monthlyTrendChart'), {
    type: 'line',
    data: monthlyTrendData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Print-only venue summary (fixed size so it renders while hidden on screen)
const printVenues = @json($allVenues->sortKeys());
const printPalette = ['#F4B183', '#9DC3E6', '#FFD966', '#A9D18E', '#C9C9C9', '#5B9BD5', '#ED7D31', '#BF9000', '#70AD47', '#4472C4', '#A5A5A5', '#FFC000'];
new Chart(document.getElementById('venueSummaryPrintChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(printVenues),
        datasets: [{ data: Object.values(printVenues), backgroundColor: Object.keys(printVenues).map((_, i) => printPalette[i % printPalette.length]) }]
    },
    options: {
        responsive: false,
        animation: false,
        layout: { padding: { top: 18 } },
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, suggestedMax: Math.max(...Object.values(printVenues), 0) + 2, ticks: { stepSize: 2 } }, x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } } }
    },
    plugins: [{
        id: 'valueLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            ctx.save();
            ctx.font = '600 12px Calibri, Arial, sans-serif';
            ctx.fillStyle = '#000';
            ctx.textAlign = 'center';
            chart.getDatasetMeta(0).data.forEach((bar, i) => ctx.fillText(chart.data.datasets[0].data[i], bar.x, bar.y - 6));
            ctx.restore();
        }
    }]
});

// Facility Usage Chart
const facilityLabels = @json($venueSummary->keys()->toArray());
const facilityCounts = @json($venueSummary->values()->toArray());


new Chart(document.getElementById('facilityUsageChart'), {
    type: 'bar',
    data: {
        labels: facilityLabels,
        datasets: [{
            label: 'Number of Bookings',
            data: facilityCounts,
            backgroundColor: '#334155',
            hoverBackgroundColor: '#0f172a',
            borderRadius: { topLeft: 4, topRight: 4 },
            borderSkipped: 'bottom',
            maxBarThickness: 40
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                ticks: {
                    autoSkip: false,
                    maxRotation: 45,
                    minRotation: 45,
                    font: {
                        size: 10
                    }
                }
            }
        }
    }
});
});
</script>
@endsection
