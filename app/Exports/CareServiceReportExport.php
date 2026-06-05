<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CareServiceReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(protected array $data) {}

    public function sheets(): array
    {
        return [
            new SummarySheet($this->data),
            new AppointmentsSheet($this->data),
            new NotesSheet($this->data),
        ];
    }
}

class SummarySheet implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    public function __construct(protected array $data) {}

    public function array(): array
    {
        return [
            ['RAPPORT DE SUIVI PASTORAL'],
            [''],
            ['Généré le:', $this->data['generated_at']],
            [''],
            ['PASTEUR RESPONSABLE'],
            ['Nom:', $this->data['pastor']['name']],
            ['Email:', $this->data['pastor']['email']],
            ['Téléphone:', $this->data['pastor']['phone']],
            [''],
            ['INFORMATIONS DU CLIENT'],
            ['Nom:', $this->data['client']['name']],
            ['Email:', $this->data['client']['email']],
            ['Téléphone:', $this->data['client']['phone']],
            [''],
            ['RÉSUMÉ DU SUIVI'],
            ['Nombre total de rendez-vous:', $this->data['summary']['total_appointments']],
            ['Rendez-vous terminés:', $this->data['summary']['completed_appointments']],
            ['Durée totale:', $this->data['summary']['total_duration_formatted']],
            ['Premier rendez-vous:', $this->data['summary']['first_appointment_date']],
            ['Dernier rendez-vous:', $this->data['summary']['last_appointment_date']],
            [''],
            ['ÉGLISE'],
            [$this->data['church']['name']],
            [$this->data['church']['email']],
            [$this->data['church']['phone']],
            [$this->data['church']['website']],
        ];
    }

    public function title(): string
    {
        return 'Résumé';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 40,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1a365d']],
            ],
            5 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '2d3748']]],
            10 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '2d3748']]],
            15 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '2d3748']]],
            22 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '2d3748']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                // Merge title cell
                $event->sheet->mergeCells('A1:B1');

                // Set background for section headers
                $sectionRows = [5, 10, 15, 22];
                foreach ($sectionRows as $row) {
                    $event->sheet->getStyle("A{$row}:B{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('edf2f7');
                }
            },
        ];
    }
}

class AppointmentsSheet implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected array $data) {}

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Heure',
            'Durée',
            'Type',
            'Statut',
            'Type de RDV',
        ];
    }

    public function array(): array
    {
        return collect($this->data['appointments'])->map(function (array $apt, $index): array {
            $type = '';
            if ($apt['is_parent']) {
                $type = 'Premier RDV';
            } elseif ($apt['is_current']) {
                $type = 'RDV Actuel';
            } elseif ($apt['is_follow_up']) {
                $type = 'Suivi';
            }

            return [
                $index + 1,
                $apt['date'],
                $apt['time'],
                $apt['duration_formatted'],
                $apt['location_type'],
                $apt['status'],
                $type,
            ];
        })->toArray();
    }

    public function title(): string
    {
        return 'Rendez-vous';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2d3748'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $lastRow = count($this->data['appointments']) + 1;

                // Add borders to the table
                $event->sheet->getStyle("A1:G{$lastRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('e2e8f0');

                // Center align all cells
                $event->sheet->getStyle("A1:G{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Highlight current appointment row
                foreach ($this->data['appointments'] as $index => $apt) {
                    $row = $index + 2; // +2 because of header row and 0-index
                    if ($apt['is_current']) {
                        $event->sheet->getStyle("A{$row}:G{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('ebf8ff');
                    } elseif ($apt['is_parent']) {
                        $event->sheet->getStyle("A{$row}:G{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('f0fff4');
                    }
                }
            },
        ];
    }
}

class NotesSheet implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    public function __construct(protected array $data) {}

    public function array(): array
    {
        $rows = [
            ['NOTES DES RENDEZ-VOUS'],
            [''],
        ];

        foreach ($this->data['appointments'] as $index => $apt) {
            $badge = '';
            if ($apt['is_parent']) {
                $badge = ' (Premier RDV)';
            } elseif ($apt['is_current']) {
                $badge = ' (RDV Actuel)';
            } elseif ($apt['is_follow_up']) {
                $badge = ' (Suivi)';
            }

            $rows[] = ['Rendez-vous '.($index + 1).$badge.' - '.$apt['date']];
            $rows[] = [''];

            // Client notes
            $rows[] = ['Notes du client:'];
            $rows[] = empty($apt['client_notes']) ? ['(Aucune note)'] : [$apt['client_notes']];
            $rows[] = [''];

            // Pastor notes
            $rows[] = ['Notes du pasteur:'];
            if (! empty($apt['pastor_notes'])) {
                foreach ($apt['pastor_notes'] as $note) {
                    $datePrefix = $note['date'] ? "[{$note['date']}] " : '';
                    $rows[] = [$datePrefix.$note['content']];
                }
            } else {
                $rows[] = ['(Aucune note)'];
            }

            $rows[] = [''];
            $rows[] = ['─────────────────────────────────────────────────────────────'];
            $rows[] = [''];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Notes';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 80,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1a365d']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                // Find and style appointment headers
                $currentRow = 1;
                foreach ($this->data['appointments'] as $index => $apt) {
                    // Calculate the row for this appointment header
                    // Row 1: Title, Row 2: empty, then for each appointment:
                    // header, empty, client label, client note, empty, pastor label, pastor notes..., empty, separator, empty
                    $appointmentStartRow = 3 + ($index * (6 + count($apt['pastor_notes'] ?: [['dummy']])));

                    // This is simplified - in practice we'd need to track exact rows
                    // For now, let's just set basic styling
                }

                // Wrap text in all cells
                $event->sheet->getStyle('A:A')->getAlignment()->setWrapText(true);
            },
        ];
    }
}
