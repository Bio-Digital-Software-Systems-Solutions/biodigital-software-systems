<?php

namespace App\Services;

use App\Models\PastoralCare;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;

class PastoralCareReportService
{
    private readonly PastoralCare $appointment;

    private readonly Collection $allAppointments;

    public function __construct(PastoralCare $appointment)
    {
        $this->appointment = $appointment->load(['user', 'pastor', 'parent.pastor', 'parent.user', 'followUps.pastor', 'followUps.user']);
        $this->allAppointments = $this->collectAllAppointments();
    }

    /**
     * Collect all related appointments (parent + current + follow-ups)
     */
    private function collectAllAppointments(): Collection
    {
        $appointments = collect();

        // Add parent appointment if exists
        if ($this->appointment->parent) {
            $appointments->push($this->appointment->parent);
        }

        // Add current appointment
        $appointments->push($this->appointment);

        // Add follow-up appointments
        if ($this->appointment->followUps->isNotEmpty()) {
            foreach ($this->appointment->followUps->sortBy('appointment_date') as $followUp) {
                $appointments->push($followUp);
            }
        }

        return $appointments->sortBy('appointment_date');
    }

    /**
     * Get report data structure
     */
    public function getReportData(): array
    {
        $totalDurationMinutes = $this->allAppointments->sum('duration_minutes');

        return [
            'generated_at' => now()->format('d/m/Y H:i'),
            'report_title' => 'Rapport de Suivi Pastoral',
            'pastor' => [
                'name' => $this->appointment->pastor->first_name.' '.$this->appointment->pastor->last_name,
                'email' => $this->appointment->pastor->email,
                'phone' => $this->appointment->pastor->phone ?? 'Non renseigné',
            ],
            'client' => [
                'name' => $this->appointment->client_name,
                'email' => $this->appointment->client_email ?? 'Non renseigné',
                'phone' => $this->appointment->client_phone ?? 'Non renseigné',
            ],
            'summary' => [
                'total_appointments' => $this->allAppointments->count(),
                'completed_appointments' => $this->allAppointments->where('status', 'completed')->count(),
                'total_duration_minutes' => $totalDurationMinutes,
                'total_duration_formatted' => $this->formatDuration($totalDurationMinutes),
                'first_appointment_date' => $this->allAppointments->first()->appointment_date->format('d/m/Y'),
                'last_appointment_date' => $this->allAppointments->last()->appointment_date->format('d/m/Y'),
            ],
            'appointments' => $this->allAppointments->map(fn($apt): array => [
                'id' => $apt->id,
                'date' => $apt->appointment_date->format('d/m/Y'),
                'time' => $apt->appointment_time->format('H:i'),
                'duration_minutes' => $apt->duration_minutes,
                'duration_formatted' => $this->formatDuration($apt->duration_minutes),
                'status' => $this->translateStatus($apt->status),
                'status_raw' => $apt->status,
                'location_type' => $this->translateLocationType($apt->location_type),
                'client_notes' => $apt->notes,
                'pastor_notes' => $this->formatPastorNotes($apt->pastor_notes),
                'is_current' => $apt->id === $this->appointment->id,
                'is_parent' => $apt->id === $this->appointment->parent_id,
                'is_follow_up' => $apt->parent_id === $this->appointment->id,
            ])->values()->toArray(),
            'church' => [
                'name' => config('app.church_name', 'ICC Munich'),
                'email' => config('app.church_email', 'contact@icc-munich.de'),
                'phone' => config('app.church_phone', '+49 89 123456'),
                'website' => config('app.url', 'https://icc-munich.de'),
            ],
        ];
    }

    /**
     * Format duration in hours and minutes
     */
    private function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}min";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$mins} min";
    }

    /**
     * Translate status to French
     */
    private function translateStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'no_show' => 'Absent',
            default => ucfirst($status),
        };
    }

    /**
     * Translate location type to French
     */
    private function translateLocationType(string $type): string
    {
        return match ($type) {
            'in_person' => 'En présentiel',
            'zoom' => 'Visioconférence',
            'hybrid' => 'Hybride',
            default => ucfirst($type),
        };
    }

    /**
     * Format pastor notes array to readable format
     */
    private function formatPastorNotes(?array $notes): array
    {
        if ($notes === null || $notes === []) {
            return [];
        }

        return collect($notes)->map(fn($note): array => [
            'content' => $note['note'] ?? $note['content'] ?? '',
            'date' => isset($note['created_at'])
                ? \Carbon\Carbon::parse($note['created_at'])->format('d/m/Y H:i')
                : null,
        ])->toArray();
    }

    /**
     * Generate PDF report
     */
    public function generatePdf(): \Illuminate\Http\Response
    {
        $data = $this->getReportData();

        $pdf = Pdf::loadView('exports.pastoral-care-report', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = $this->generateFilename('pdf');

        return $pdf->download($filename);
    }

    /**
     * Generate PDF report as stream (for inline viewing)
     */
    public function streamPdf(): \Illuminate\Http\Response
    {
        $data = $this->getReportData();

        $pdf = Pdf::loadView('exports.pastoral-care-report', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream($this->generateFilename('pdf'));
    }

    /**
     * Generate Excel report
     */
    public function generateExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $this->getReportData();
        $filename = $this->generateFilename('xlsx');

        return Excel::download(
            new \App\Exports\PastoralCareReportExport($data),
            $filename
        );
    }

    /**
     * Generate Word report
     */
    public function generateWord(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getReportData();
        $filename = $this->generateFilename('docx');

        $phpWord = new PhpWord;

        // Set document properties
        $phpWord->getDocInfo()->setCreator($data['pastor']['name']);
        $phpWord->getDocInfo()->setTitle($data['report_title']);
        $phpWord->getDocInfo()->setDescription('Rapport de suivi pastoral généré automatiquement');

        // Define styles
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 18, 'color' => '1a365d'], ['spaceBefore' => 0, 'spaceAfter' => 240]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 14, 'color' => '2d3748'], ['spaceBefore' => 240, 'spaceAfter' => 120]);
        $phpWord->addTitleStyle(3, ['bold' => true, 'size' => 12, 'color' => '4a5568'], ['spaceBefore' => 180, 'spaceAfter' => 60]);

        // Add section
        $section = $phpWord->addSection([
            'marginTop' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2),
            'marginLeft' => Converter::cmToTwip(2.5),
            'marginRight' => Converter::cmToTwip(2.5),
        ]);

        // Header
        $header = $section->addHeader();
        $header->addText($data['church']['name'], ['size' => 10, 'color' => '718096']);

        // Footer
        $footer = $section->addFooter();
        $footer->addText('Page {PAGE} sur {NUMPAGES} - Généré le '.$data['generated_at'], ['size' => 9, 'color' => '718096'], ['alignment' => 'center']);

        // Title
        $section->addTitle($data['report_title'], 1);

        // Pastor Information
        $section->addTitle('Pasteur Responsable', 2);
        $section->addText('Nom: '.$data['pastor']['name'], ['size' => 11]);
        $section->addText('Email: '.$data['pastor']['email'], ['size' => 11]);
        $section->addText('Téléphone: '.$data['pastor']['phone'], ['size' => 11]);

        // Client Information
        $section->addTitle('Informations du Client', 2);
        $section->addText('Nom: '.$data['client']['name'], ['size' => 11]);
        $section->addText('Email: '.$data['client']['email'], ['size' => 11]);
        $section->addText('Téléphone: '.$data['client']['phone'], ['size' => 11]);

        // Summary
        $section->addTitle('Résumé du Suivi', 2);
        $summaryTable = $section->addTable(['borderSize' => 6, 'borderColor' => 'e2e8f0']);
        $summaryTable->addRow();
        $summaryTable->addCell(4500)->addText('Nombre total de rendez-vous', ['bold' => true, 'size' => 10]);
        $summaryTable->addCell(4500)->addText((string) $data['summary']['total_appointments'], ['size' => 10]);
        $summaryTable->addRow();
        $summaryTable->addCell(4500)->addText('Rendez-vous terminés', ['bold' => true, 'size' => 10]);
        $summaryTable->addCell(4500)->addText((string) $data['summary']['completed_appointments'], ['size' => 10]);
        $summaryTable->addRow();
        $summaryTable->addCell(4500)->addText('Durée totale', ['bold' => true, 'size' => 10]);
        $summaryTable->addCell(4500)->addText($data['summary']['total_duration_formatted'], ['size' => 10]);
        $summaryTable->addRow();
        $summaryTable->addCell(4500)->addText('Période', ['bold' => true, 'size' => 10]);
        $summaryTable->addCell(4500)->addText($data['summary']['first_appointment_date'].' - '.$data['summary']['last_appointment_date'], ['size' => 10]);

        // Appointments Details
        $section->addTitle('Détails des Rendez-vous', 2);

        foreach ($data['appointments'] as $index => $apt) {
            $badgeText = '';
            if ($apt['is_parent']) {
                $badgeText = ' (Premier rendez-vous)';
            } elseif ($apt['is_current']) {
                $badgeText = ' (Rendez-vous actuel)';
            } elseif ($apt['is_follow_up']) {
                $badgeText = ' (Suivi)';
            }

            $section->addTitle('Rendez-vous '.($index + 1).$badgeText, 3);

            $aptTable = $section->addTable(['borderSize' => 4, 'borderColor' => 'e2e8f0']);
            $aptTable->addRow();
            $aptTable->addCell(2500)->addText('Date', ['bold' => true, 'size' => 10]);
            $aptTable->addCell(6500)->addText($apt['date'], ['size' => 10]);
            $aptTable->addRow();
            $aptTable->addCell(2500)->addText('Heure', ['bold' => true, 'size' => 10]);
            $aptTable->addCell(6500)->addText($apt['time'], ['size' => 10]);
            $aptTable->addRow();
            $aptTable->addCell(2500)->addText('Durée', ['bold' => true, 'size' => 10]);
            $aptTable->addCell(6500)->addText($apt['duration_formatted'], ['size' => 10]);
            $aptTable->addRow();
            $aptTable->addCell(2500)->addText('Statut', ['bold' => true, 'size' => 10]);
            $aptTable->addCell(6500)->addText($apt['status'], ['size' => 10]);
            $aptTable->addRow();
            $aptTable->addCell(2500)->addText('Type', ['bold' => true, 'size' => 10]);
            $aptTable->addCell(6500)->addText($apt['location_type'], ['size' => 10]);

            // Client Notes
            if (! empty($apt['client_notes'])) {
                $section->addText('Notes du client:', ['bold' => true, 'size' => 10], ['spaceBefore' => 120]);
                $section->addText($apt['client_notes'], ['size' => 10, 'italic' => true]);
            }

            // Pastor Notes
            if (! empty($apt['pastor_notes'])) {
                $section->addText('Notes du pasteur:', ['bold' => true, 'size' => 10], ['spaceBefore' => 120]);
                foreach ($apt['pastor_notes'] as $note) {
                    $datePrefix = $note['date'] ? "[{$note['date']}] " : '';
                    $section->addText($datePrefix.$note['content'], ['size' => 10, 'italic' => true]);
                }
            }

            $section->addTextBreak();
        }

        // Save and return
        $tempFile = tempnam(sys_get_temp_dir(), 'pastoral_report_');

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->streamDownload(function () use ($tempFile): void {
            readfile($tempFile);
            unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Generate filename for the report
     */
    private function generateFilename(string $extension): string
    {
        $clientName = str_replace(' ', '_', $this->appointment->client_name);
        $date = now()->format('Y-m-d');

        return "rapport_pastoral_{$clientName}_{$date}.{$extension}";
    }
}
