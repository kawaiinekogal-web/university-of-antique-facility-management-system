<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\FormSubmission;
use App\Support\VenueRates;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyRecordsController extends Controller
{
    public const REPORTABLE_BOOKING_STATUSES = ['reserved', 'rescheduled', 'completed'];

    /** GSU operating hours per day (reservations must start and end between 6:00 AM and 7:00 PM; see OperatingHours). */
    public const OPERATING_HOURS_PER_DAY = 13;

    public function index(Request $request)
    {
        [$period, $startDate, $endExclusive] = $this->resolvePeriod($request);
        $month = $startDate->month;
        $year = $startDate->year;
        $reportDate = $startDate->toDateString();
        $periodLabel = $this->periodLabel($period, $startDate, $endExclusive);
        $step = ['daily' => 'addDay', 'weekly' => 'addWeek', 'monthly' => 'addMonth'][$period];
        $prevStart = $startDate->copy()->{$step}(-1);
        $nextStart = $startDate->copy()->{$step}();
        $prevQuery = $this->periodQuery($period, $prevStart);
        $nextQuery = $this->periodQuery($period, $nextStart);
        $prevLabel = $this->periodLabel($period, $prevStart, $startDate);
        $nextLabel = $this->periodLabel($period, $nextStart, $nextStart->copy()->{$step}());

        ['bookings' => $bookings, 'records' => $records, 'venueSummary' => $venueSummary, 'allVenues' => $allVenues, 'totalRentalFees' => $totalRentalFees]
            = $this->reportData($startDate, $endExclusive);
        $allVenues = $allVenues->sortByDesc(fn ($count) => $count);

        // Occupancy: booked hours (clipped to the period) out of the period's operating hours (13 h a day, 6 AM-7 PM)
        $periodDays = (int) round($startDate->diffInDays($endExclusive));
        $calendarHours = $periodDays * self::OPERATING_HOURS_PER_DAY;
        $bookedHours = [];
        foreach ($bookings as $booking) {
            $from = $booking->start_time->max($startDate);
            $to = $booking->end_time->min($endExclusive);
            $hours = $from->lt($to) ? $from->diffInSeconds($to) / 3600 : 0;
            foreach ($booking->facilities as $facility) {
                $bookedHours[$facility->name] = ($bookedHours[$facility->name] ?? 0) + $hours;
            }
        }
        $occupancy = $allVenues->keys()->map(fn ($name) => [
            'name' => $name,
            'booked_hours' => round($bookedHours[$name] ?? 0, 2),
            'utilization' => $calendarHours > 0 ? round(($bookedHours[$name] ?? 0) / $calendarHours * 100, 2) : 0,
        ])->sortByDesc('booked_hours')->values();

        // The 12 months ending with the month being viewed (not today's month), so the chart matches the report.
        // Step back from the 1st: subMonths() from a 29th-31st overflows (Oct 31 - 1 month = Oct 1)
        $thisMonth = $startDate->copy()->startOfMonth();
        $trendStart = $thisMonth->copy()->subMonths(11);
        $trendEnd = $thisMonth->copy()->endOfMonth();
        $trendLabel = $trendStart->format('M Y') . ' – ' . $thisMonth->format('M Y');
        $trendCounts = Booking::query()
            ->selectRaw('YEAR(start_time) as report_year, MONTH(start_time) as report_month, COUNT(*) as booking_count')
            ->whereBetween('start_time', [$trendStart, $trendEnd])
            ->whereIn('status', self::REPORTABLE_BOOKING_STATUSES)
            ->groupByRaw('YEAR(start_time), MONTH(start_time)')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->report_year, $row->report_month));

        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $trendMonth = $thisMonth->copy()->subMonths($i);
            $key = $trendMonth->format('Y-m');

            $monthlyTrend[] = [
                'month' => $trendMonth->format('M Y'),
                'count' => (int) ($trendCounts->get($key)->booking_count ?? 0),
            ];
        }

        return view('admin.monthly-records.index', compact(
            'records',
            'venueSummary',
            'allVenues',
            'totalRentalFees',
            'month',
            'year',
            'monthlyTrend',
            'occupancy',
            'calendarHours',
            'periodDays',
            'trendLabel',
            'period',
            'reportDate',
            'periodLabel',
            'prevQuery',
            'nextQuery',
            'prevLabel',
            'nextLabel'
        ));
    }

    /**
     * Excel download laid out like the GSU paper report: gold period banner, ledger with
     * "Facilities to be used (qty.)" columns, venue summary chart, rental total and signatories.
     */
    public function export(Request $request): StreamedResponse
    {
        [$period, $startDate, $endExclusive] = $this->resolvePeriod($request);
        $periodLabel = $this->periodLabel($period, $startDate, $endExclusive);
        ['records' => $records, 'allVenues' => $allVenues, 'totalRentalFees' => $totalRentalFees]
            = $this->reportData($startDate, $endExclusive);

        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 ]+/', ' ', $periodLabel), 0, 31));
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $thin = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

        // Banner
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', $this->bannerLabel($period, $periodLabel));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4A017']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Two-row header; single columns span both rows
        $headers = ['A' => 'Form No.', 'B' => 'Date Requested', 'C' => 'Date of Activity', 'D' => 'Time of Activity',
            'E' => 'Purpose', 'F' => 'Requester', 'G' => 'Contact Number', 'H' => 'Venue', 'P' => 'Remarks'];
        foreach ($headers as $col => $text) {
            $sheet->mergeCells("{$col}2:{$col}3");
            $sheet->setCellValue("{$col}2", $text);
        }
        $sheet->mergeCells('I2:O2');
        $sheet->setCellValue('I2', 'Facilities to be used (qty.)');
        $equipmentCols = ['I' => ['chairs', 'Chairs'], 'J' => ['tables', 'Long Tables'], 'K' => ['fans', 'Electric Fan'],
            'L' => ['rostrum', 'Rostrum'], 'M' => ['flags', 'Flag'], 'N' => ['led', 'Led Wall'], 'O' => ['sound', 'Sound']];
        foreach ($equipmentCols as $col => [, $text]) {
            $sheet->setCellValue("{$col}3", $text);
        }
        $sheet->getStyle('A2:P3')->applyFromArray($thin + [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle('I2')->getFont()->getColor()->setRgb('B45309');

        // Ledger
        $row = 4;
        foreach ($records as $record) {
            $sheet->setCellValue("B{$row}", $record['date_requested']);
            $sheet->setCellValue("C{$row}", $record['date_of_activity']);
            $sheet->setCellValue("D{$row}", $record['time']);
            $purpose = new RichText();
            $purpose->createText($record['purpose']);
            if ($record['rental_fee'] > 0) {
                $fee = $purpose->createTextRun(' (Rental Fee: PhP' . number_format($record['rental_fee'], 2) . ')');
                $fee->getFont()->setBold(true)->setItalic(true);
            }
            $sheet->setCellValue("E{$row}", $purpose);
            $sheet->setCellValue("F{$row}", $record['requester']);
            $sheet->setCellValueExplicit("G{$row}", (string) $record['contact_no'], DataType::TYPE_STRING);
            $sheet->setCellValue("H{$row}", $record['venue']);
            $sheet->setCellValue("P{$row}", $record['remarks']);
            foreach ($equipmentCols as $col => [$key]) {
                if ((int) $record['equipment'][$key] > 0) {
                    $sheet->setCellValue("{$col}{$row}", (int) $record['equipment'][$key]);
                }
            }
            $row++;
        }
        $lastLedgerRow = max(4, $row - 1);
        $sheet->getStyle("A4:P{$lastLedgerRow}")->applyFromArray($thin + [
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle("B4:D{$lastLedgerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F4:O{$lastLedgerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if ($records->isEmpty()) {
            $sheet->mergeCells('A4:P4');
            $sheet->setCellValue('A4', 'No bookings found for ' . $periodLabel);
            $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        foreach (['A' => 7, 'B' => 16, 'C' => 16, 'D' => 17, 'E' => 46, 'F' => 18, 'G' => 15, 'H' => 20,
            'I' => 7, 'J' => 7, 'K' => 8, 'L' => 8, 'M' => 6, 'N' => 6, 'O' => 6, 'P' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Venue summary: data table (chart source) + bar chart beside it
        $summaryTitleRow = $lastLedgerRow + 3;
        $sheet->setCellValue("B{$summaryTitleRow}", 'Summary of Venues/Facilities Utilized as of ' . $this->summaryLabel($period, $startDate, $endExclusive));
        $sheet->getStyle("B{$summaryTitleRow}")->getFont()->setBold(true)->setSize(12);
        $headRow = $summaryTitleRow + 1;
        $sheet->setCellValue("B{$headRow}", 'Venue / Facility');
        $sheet->setCellValue("C{$headRow}", 'Bookings');
        $dataRow = $headRow + 1;
        foreach ($allVenues as $venue => $count) {
            $sheet->setCellValue("B{$dataRow}", $venue);
            $sheet->setCellValue("C{$dataRow}", (int) $count);
            $dataRow++;
        }
        $lastSummaryRow = max($headRow + 1, $dataRow - 1);
        $sheet->getStyle("B{$headRow}:C{$lastSummaryRow}")->applyFromArray($thin);
        $sheet->getStyle("B{$headRow}:C{$headRow}")->getFont()->setBold(true);
        $sheet->getStyle("C{$headRow}:C{$lastSummaryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($allVenues->isNotEmpty()) {
            $sheetRef = "'" . str_replace("'", "''", $sheet->getTitle()) . "'";
            $series = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                [0],
                [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetRef}!\$C\${$headRow}", null, 1)],
                [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetRef}!\$B\$" . ($headRow + 1) . ":\$B\${$lastSummaryRow}", null, $allVenues->count())],
                [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetRef}!\$C\$" . ($headRow + 1) . ":\$C\${$lastSummaryRow}", null, $allVenues->count())],
            );
            $series->setPlotDirection(DataSeries::DIRECTION_COL);
            $layout = new Layout();
            $layout->setShowVal(true);
            $plotArea = new PlotArea($layout, [$series]);
            $chart = new Chart('venueSummary', new Title('Summary of Venues/Facilities Utilized'), null, $plotArea);
            $chart->setTopLeftPosition("E{$summaryTitleRow}");
            $chart->setBottomRightPosition('M' . max($lastSummaryRow, $summaryTitleRow + 20));
            $sheet->addChart($chart);
        }

        // Rental total + signatories
        $footerRow = max($lastSummaryRow, $summaryTitleRow + 20) + 2;
        $sheet->setCellValue("B{$footerRow}", 'Rental of Venues:');
        $sheet->setCellValue("C{$footerRow}", 'PhP' . number_format($totalRentalFees, 2));
        $sheet->getStyle("B{$footerRow}:C{$footerRow}")->getFont()->setBold(true)->setItalic(true);

        $signRow = $footerRow + 3;
        $sheet->setCellValue("B{$signRow}", 'Prepared by:');
        $sheet->setCellValue("K{$signRow}", 'Noted by:');
        $sheet->getStyle("B{$signRow}:K{$signRow}")->getFont()->setItalic(true)->setBold(true);
        $preparer = $request->user();
        $sheet->setCellValue('C' . ($signRow + 2), mb_strtoupper((string) $preparer?->name));
        $sheet->setCellValue('C' . ($signRow + 3), (string) ($preparer?->position ?: 'GSU Administrator'));
        $sheet->setCellValue('L' . ($signRow + 2), '______________________________');
        $sheet->setCellValue('L' . ($signRow + 3), 'Name / Position');
        $sheet->getStyle('C' . ($signRow + 2))->getFont()->setBold(true);

        // Print like the paper copy: landscape, one page wide, header repeated on each page
        $setup = $sheet->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 3);
        $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.3)->setRight(0.3);
        $sheet->freezePane('A4');

        $fileName = sprintf('%s-records-%s.xlsx', $period, $period === 'monthly' ? $startDate->format('Y-m') : $startDate->toDateString());

        return response()->streamDownload(function () use ($book) {
            $writer = new Xlsx($book);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // Monthly banner is just the month ("MAY 2026"), like the paper; weekly/daily name the report type.
    private function bannerLabel(string $period, string $periodLabel): string
    {
        return mb_strtoupper($period === 'monthly' ? $periodLabel : "{$period} report: {$periodLabel}");
    }

    // "May, 2026" for monthly (as on the paper report), otherwise the period label.
    private function summaryLabel(string $period, Carbon $start, Carbon $endExclusive): string
    {
        return $period === 'monthly' ? $start->format('F, Y') : $this->periodLabel($period, $start, $endExclusive);
    }

    /**
     * Bookings in [$start, $endExclusive) shaped for the ledger, plus venue counts and rental total.
     * Shared by the page, print view and Excel download so all three show the same figures.
     */
    private function reportData(Carbon $startDate, Carbon $endExclusive): array
    {
        $bookings = Booking::with(['facilities', 'requester'])
            ->where('start_time', '>=', $startDate)
            ->where('start_time', '<', $endExclusive)
            ->whereIn('status', self::REPORTABLE_BOOKING_STATUSES)
            ->orderBy('start_time', 'asc')
            ->get();

        // Each booking records the exact source submission, avoiding accidental matches
        // when the same requester has multiple reservations.
        $submissionIds = $bookings
            ->pluck('additional_details.form_submission_id')
            ->filter()
            ->unique();
        $formSubmissions = FormSubmission::whereIn('id', $submissionIds)->get()->keyBy('id');

        // Format bookings for display
        $records = $bookings->map(function ($booking) use ($formSubmissions) {
            $facilities = $booking->facilities;
            $details = $booking->additional_details ?? [];
            $formSubmission = $formSubmissions->get($details['form_submission_id'] ?? null);
            $payload = $formSubmission?->payload ?? [];
            $equipment = $details['equipment'] ?? $payload['equipment'] ?? [];

            return [
                'date_requested' => $booking->requested_at ? $booking->requested_at->format('F j, Y') : ($booking->created_at ? $booking->created_at->format('F j, Y') : '-'),
                'date_of_activity' => $booking->start_time->format('F j, Y'),
                'time' => $this->formatTime($booking->start_time) . '-' . $this->formatTime($booking->end_time),
                'purpose' => $booking->purpose ?? '-',
                'requester' => $payload['client_name'] ?? $booking->requester->name ?? '-',
                'contact_no' => $payload['contact_number'] ?? $booking->requester?->contact_number ?? '-',
                'venue' => $facilities->isNotEmpty() ? $facilities->pluck('name')->join(', ') : '-',
                'equipment' => [
                    'chairs' => $equipment['monobloc_chair'] ?? $payload['qty_monobloc'] ?? 0,
                    'tables' => $equipment['table'] ?? $payload['qty_table'] ?? 0,
                    'fans' => $equipment['electric_fan'] ?? $payload['qty_fan'] ?? 0,
                    'rostrum' => $equipment['rostrum'] ?? $payload['qty_rostrum'] ?? 0,
                    'flags' => $equipment['flag'] ?? $payload['qty_flag'] ?? 0,
                    'sound' => $equipment['sound'] ?? $payload['qty_sound'] ?? 0,
                    'led' => $equipment['led'] ?? $payload['qty_led'] ?? 0,
                ],
                // Only guests rent venues; college, org and admin reservations carry no fee
                // Paper report's Remarks column: equipment not in the fixed columns
                'remarks' => collect($details['equipment_others'] ?? $payload['equipment_others'] ?? [])
                    ->map(fn ($row) => trim(($row['qty'] ?? 1) . ' ' . ($row['name'] ?? '')))->filter()->join(', '),
                'rental_fee' => ($formSubmission?->requester_type ?? $booking->requester?->role) === 'guest'
                    // per-head venues (classrooms) are rate × number of persons
                    ? $facilities->sum(fn ($facility) => VenueRates::dailyFeeFor($facility->name, (int) ($details['number_of_persons'] ?? $payload['number_of_persons'] ?? 0) ?: null) ?? 0)
                    : 0,
            ];
        });

        // Calculate venue usage summary
        $venueSummary = $bookings
            ->flatMap(fn ($booking) => $booking->facilities->pluck('name'))
            ->countBy()
            ->sortKeys();

        $allVenues = Facility::orderBy('name')
            ->pluck('name')
            ->mapWithKeys(fn ($name) => [$name => (int) $venueSummary->get($name, 0)]);
        foreach ($venueSummary as $venue => $count) {
            $allVenues[$venue] = $count;
        }

        return [
            'bookings' => $bookings,
            'records' => $records,
            'venueSummary' => $venueSummary,
            'allVenues' => $allVenues->sortKeys(),
            'totalRentalFees' => $records->sum('rental_fee'),
        ];
    }

    // Paper reports write noon as "12:00nn".
    private function formatTime(Carbon $time): string
    {
        return $time->format('H:i') === '12:00' ? '12:00nn' : $time->format('g:ia');
    }

    /**
     * Report period from the query string: monthly (month/year), weekly (Mon-Sun week of `date`)
     * or daily (`date`). Invalid input falls back to the current period.
     *
     * @return array{0: string, 1: Carbon, 2: Carbon} [period, start, exclusive end]
     */
    private function resolvePeriod(Request $request): array
    {
        $period = in_array($request->query('period'), ['daily', 'weekly'], true) ? $request->query('period') : 'monthly';

        if ($period === 'monthly') {
            $month = filter_var($request->query('month', now()->month), FILTER_VALIDATE_INT);
            $year = filter_var($request->query('year', now()->year), FILTER_VALIDATE_INT);
            $month = $month !== false && $month >= 1 && $month <= 12 ? $month : now()->month;
            $year = $year !== false && $year >= 2000 && $year <= 2100 ? $year : now()->year;
            $start = Carbon::create($year, $month, 1)->startOfDay();

            return [$period, $start, $start->copy()->addMonth()];
        }

        $raw = (string) $request->query('date');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) && checkdate((int) substr($raw, 5, 2), (int) substr($raw, 8, 2), (int) substr($raw, 0, 4))
            ? Carbon::createFromFormat('Y-m-d', $raw)->startOfDay()
            : now()->startOfDay();
        if ($date->year < 2000 || $date->year > 2100) {
            $date = now()->startOfDay();
        }

        return $period === 'daily'
            ? [$period, $date, $date->copy()->addDay()]
            : [$period, $date->copy()->startOfWeek(Carbon::MONDAY), $date->copy()->startOfWeek(Carbon::MONDAY)->addWeek()];
    }

    private function periodLabel(string $period, Carbon $start, Carbon $endExclusive): string
    {
        return match ($period) {
            'daily' => $start->format('F d, Y'),
            'weekly' => $start->format($start->year === $endExclusive->copy()->subDay()->year ? 'M d' : 'M d, Y') . ' – ' . $endExclusive->copy()->subDay()->format('M d, Y'),
            default => $start->format('F Y'),
        };
    }

    private function periodQuery(string $period, Carbon $start): array
    {
        return $period === 'monthly'
            ? ['period' => 'monthly', 'month' => $start->month, 'year' => $start->year]
            : ['period' => $period, 'date' => $start->toDateString()];
    }
}
