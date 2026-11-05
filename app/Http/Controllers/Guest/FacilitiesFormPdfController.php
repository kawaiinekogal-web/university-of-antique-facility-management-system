<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Services\GuestFacilitiesDocxFiller;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Throwable;

class FacilitiesFormPdfController extends Controller
{
    /**
     * Generate a Guest Facilities and Utilization Form PDF from an approved FormSubmission.
     *
     * Uses the GUEST-FACILITIES-AND-UTILIZATION-FROM-TEMPLATE.docx template
     * and fills values from the JSON payload.
     */
    public function generate(Request $request, FormSubmission $submission): BinaryFileResponse
    {
        // Only allow PDF generation for approved guest submissions
        if ($submission->type !== 'facilities_utilization'
            || ! in_array($submission->status, ['approved', 'reserved'], true)) {
            abort(403, 'PDF generation is only available for approved requests.');
        }

        // Verify the user owns this submission or is an admin
        $user = $request->user();
        if (! $request->hasValidSignature(false) && ! ($user?->managesFacilities() || $submission->requester_id === $user?->id)) {
            abort(403, 'Unauthorized access.');
        }

        $payload = $submission->payload ?? [];
        $quote = $submission->rentalQuote();
        $facilities = $quote['facilities'];
        $facilityRows = $quote['rows'];
        $rentalTotal = $quote['total'];
        $startDate = $payload['date_activity'] ?? '';
        $endDate = $payload['date_end'] ?? $startDate;
        $timeRange = $payload['time_range'] ?? [];

        $controlNo = $payload['control_no'] ?? '';
        $dateRequest = $payload['date_request'] ?? now()->toDateString();
        $clientName = $payload['client_name'] ?? '';
        $contactNumber = $payload['contact_number'] ?? '';
        $email = $payload['email'] ?? '';
        $organization = $payload['organization'] ?? '';
        $address = $payload['address'] ?? '';
        $purpose = $payload['purpose'] ?? '';
        $equipment = $payload['equipment'] ?? [];
        $notedName = $payload['noted_signatory_name'] ?? $payload['noted_signatory_custom'] ?? '';

        // Load the Guest DOCX template
        $templatePath = base_path('FORMS/GUEST-FACILITIES-AND-UTILIZATION-FROM-TEMPLATE.docx');
        if (!file_exists($templatePath)) {
            $templatePath = storage_path('app/templates/GUEST-FACILITIES-AND-UTILIZATION-FROM-TEMPLATE.docx');
        }

        if (!file_exists($templatePath)) {
            abort(500, 'Guest Facilities Utilization Form template not found.');
        }

        $temporaryBase = tempnam(sys_get_temp_dir(), 'guest_facilities_');
        @unlink($temporaryBase);
        $docxPath = $temporaryBase . '.docx';
        if (! copy($templatePath, $docxPath)) {
            abort(500, 'Could not copy the guest form template.');
        }
        app(GuestFacilitiesDocxFiller::class)->fill($docxPath, [
            'control_no' => $controlNo ?: $submission->ticket_code,
            'date_request' => $dateRequest,
            'client_name' => $clientName,
            'contact_number' => $contactNumber,
            'email' => $email,
            'address' => $address,
            'organization' => $organization,
            'facility_names' => $facilities->pluck('name')->all(),
            'date_activity' => $startDate,
            'date_end' => $endDate,
            'time_range' => $timeRange,
            'purpose' => $purpose,
            'equipment' => $equipment,
            'equipment_others' => $payload['equipment_others'] ?? [],
            'noted_name' => $notedName,
            'facility_rows' => $facilityRows,
            'rental_total' => $rentalTotal,
        ]);

        // Convert to PDF using LibreOffice
        return $this->convertAndDownload($docxPath, 'Guest-Facilities-Form-'.$controlNo);
    }

    /**
     * DOCX → PDF via LibreOffice (same as admin controller).
     */
    private function convertAndDownload(string $docxPath, string $baseName): BinaryFileResponse
    {
        $outDir  = sys_get_temp_dir();
        $pdfPath = $outDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';

        try {
            $process = new Process(['soffice', '--headless', '--convert-to', 'pdf', '--outdir', $outDir, $docxPath]);
            $process->setTimeout(60);
            $process->run();
        } catch (Throwable) {
        }

        if (! file_exists($pdfPath)) {
            $htmlPath = tempnam(sys_get_temp_dir(), 'guest_form_html_') . '.html';
            try {
                $word = IOFactory::load($docxPath);
                IOFactory::createWriter($word, 'HTML')->save($htmlPath);
                $html = file_get_contents($htmlPath);
                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4');
                $dompdf->render();
                file_put_contents($pdfPath, $dompdf->output());
            } catch (Throwable $exception) {
                report($exception);
            } finally {
                @unlink($htmlPath);
            }
        }

        if (!file_exists($pdfPath)) {
            // Fallback: return the filled DOCX if PDF conversion fails
            return response()
                ->download($docxPath, $baseName . '.docx')
                ->deleteFileAfterSend(true);
        }

        // Remove the temp DOCX file
        @unlink($docxPath);

        // Display inline so the PDF opens in the browser's viewer
        return response()
            ->file($pdfPath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $baseName . '.pdf"',
            ])
            ->deleteFileAfterSend(true);
    }
}
