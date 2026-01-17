<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $report_title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1a202c;
            background: #fff;
        }

        .container {
            padding: 20px 30px;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #3182ce;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .report-title {
            font-size: 22px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 5px;
        }

        .church-name {
            font-size: 14px;
            color: #4a5568;
            font-weight: 600;
        }

        .generated-date {
            font-size: 10px;
            color: #718096;
            margin-top: 10px;
        }

        /* Sections */
        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2d3748;
            background: #edf2f7;
            padding: 8px 12px;
            margin-bottom: 12px;
            border-left: 4px solid #3182ce;
        }

        /* Info Cards */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-card {
            display: table-cell;
            width: 50%;
            padding-right: 15px;
            vertical-align: top;
        }

        .info-card:last-child {
            padding-right: 0;
            padding-left: 15px;
        }

        .info-card-title {
            font-size: 12px;
            font-weight: bold;
            color: #4a5568;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            display: inline-block;
            width: 80px;
        }

        .info-value {
            color: #1a202c;
        }

        /* Summary Box */
        .summary-box {
            background: #ebf8ff;
            border: 1px solid #90cdf4;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }

        .summary-number {
            font-size: 24px;
            font-weight: bold;
            color: #2b6cb0;
        }

        .summary-label {
            font-size: 10px;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Appointments Table */
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .appointments-table th {
            background: #2d3748;
            color: #fff;
            font-weight: 600;
            text-align: left;
            padding: 10px 8px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .appointments-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .appointments-table tr:nth-child(even) {
            background: #f7fafc;
        }

        .appointments-table tr.current-appointment {
            background: #ebf8ff;
            border-left: 3px solid #3182ce;
        }

        .appointments-table tr.parent-appointment {
            background: #f0fff4;
            border-left: 3px solid #38a169;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-confirmed {
            background: #bee3f8;
            color: #2a4365;
        }

        .status-pending {
            background: #fefcbf;
            color: #744210;
        }

        .status-cancelled {
            background: #fed7d7;
            color: #742a2a;
        }

        .status-no_show {
            background: #e2e8f0;
            color: #4a5568;
        }

        /* Appointment Type Badge */
        .type-badge {
            font-size: 9px;
            color: #718096;
        }

        /* Notes Section */
        .appointment-detail {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .appointment-header {
            background: #edf2f7;
            padding: 10px 12px;
            border-left: 4px solid #3182ce;
            margin-bottom: 10px;
        }

        .appointment-header.parent {
            border-left-color: #38a169;
        }

        .appointment-header.current {
            border-left-color: #805ad5;
            background: #faf5ff;
        }

        .appointment-header.followup {
            border-left-color: #ed8936;
        }

        .appointment-number {
            font-size: 12px;
            font-weight: bold;
            color: #2d3748;
        }

        .appointment-badge {
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
        }

        .badge-parent {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-current {
            background: #e9d8fd;
            color: #553c9a;
        }

        .badge-followup {
            background: #feebc8;
            color: #7b341e;
        }

        .appointment-meta {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .meta-item {
            display: table-cell;
            padding: 5px 10px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }

        .meta-label {
            font-size: 9px;
            color: #718096;
            text-transform: uppercase;
            display: block;
        }

        .meta-value {
            font-size: 11px;
            font-weight: 600;
            color: #2d3748;
        }

        .notes-container {
            margin-top: 10px;
        }

        .notes-title {
            font-size: 10px;
            font-weight: bold;
            color: #4a5568;
            margin-bottom: 5px;
        }

        .notes-content {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px;
            font-style: italic;
            color: #4a5568;
        }

        .note-item {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .note-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .note-date {
            font-size: 9px;
            color: #a0aec0;
            font-style: normal;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 9px;
        }

        .footer-church {
            font-weight: 600;
            color: #4a5568;
        }

        /* Page break utility */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <div class="report-title">{{ $report_title }}</div>
                    <div class="church-name">{{ $church['name'] }}</div>
                </div>
                <div class="header-right">
                    <div class="generated-date">
                        Document généré le {{ $generated_at }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Pastor & Client Info -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-title">Pasteur Responsable</div>
                <div class="info-row">
                    <span class="info-label">Nom:</span>
                    <span class="info-value">{{ $pastor['name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $pastor['email'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tél:</span>
                    <span class="info-value">{{ $pastor['phone'] }}</span>
                </div>
            </div>
            <div class="info-card">
                <div class="info-card-title">Informations du Client</div>
                <div class="info-row">
                    <span class="info-label">Nom:</span>
                    <span class="info-value">{{ $client['name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $client['email'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tél:</span>
                    <span class="info-value">{{ $client['phone'] }}</span>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="section">
            <div class="section-title">Résumé du Suivi</div>
            <div class="summary-box">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-number">{{ $summary['total_appointments'] }}</div>
                        <div class="summary-label">Rendez-vous</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">{{ $summary['completed_appointments'] }}</div>
                        <div class="summary-label">Terminés</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">{{ $summary['total_duration_formatted'] }}</div>
                        <div class="summary-label">Durée Totale</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">{{ $summary['first_appointment_date'] }}</div>
                        <div class="summary-label">Premier RDV</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Overview Table -->
        <div class="section">
            <div class="section-title">Vue d'ensemble des Rendez-vous</div>
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">#</th>
                        <th style="width: 20%;">Date</th>
                        <th style="width: 15%;">Heure</th>
                        <th style="width: 15%;">Durée</th>
                        <th style="width: 20%;">Type</th>
                        <th style="width: 20%;">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $index => $apt)
                    <tr class="{{ $apt['is_current'] ? 'current-appointment' : ($apt['is_parent'] ? 'parent-appointment' : '') }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $apt['date'] }}</td>
                        <td>{{ $apt['time'] }}</td>
                        <td>{{ $apt['duration_formatted'] }}</td>
                        <td><span class="type-badge">{{ $apt['location_type'] }}</span></td>
                        <td>
                            <span class="status-badge status-{{ $apt['status_raw'] }}">
                                {{ $apt['status'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Detailed Appointments -->
        <div class="section">
            <div class="section-title">Détails des Rendez-vous et Notes</div>

            @foreach($appointments as $index => $apt)
            <div class="appointment-detail">
                <div class="appointment-header {{ $apt['is_parent'] ? 'parent' : ($apt['is_current'] ? 'current' : ($apt['is_follow_up'] ? 'followup' : '')) }}">
                    <span class="appointment-number">Rendez-vous {{ $index + 1 }}</span>
                    @if($apt['is_parent'])
                        <span class="appointment-badge badge-parent">Premier rendez-vous</span>
                    @elseif($apt['is_current'])
                        <span class="appointment-badge badge-current">Rendez-vous actuel</span>
                    @elseif($apt['is_follow_up'])
                        <span class="appointment-badge badge-followup">Suivi</span>
                    @endif
                </div>

                <div class="appointment-meta">
                    <div class="meta-item">
                        <span class="meta-label">Date</span>
                        <span class="meta-value">{{ $apt['date'] }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Heure</span>
                        <span class="meta-value">{{ $apt['time'] }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Durée</span>
                        <span class="meta-value">{{ $apt['duration_formatted'] }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Type</span>
                        <span class="meta-value">{{ $apt['location_type'] }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Statut</span>
                        <span class="meta-value">{{ $apt['status'] }}</span>
                    </div>
                </div>

                @if(!empty($apt['client_notes']))
                <div class="notes-container">
                    <div class="notes-title">Notes du client</div>
                    <div class="notes-content">
                        {{ $apt['client_notes'] }}
                    </div>
                </div>
                @endif

                @if(!empty($apt['pastor_notes']))
                <div class="notes-container">
                    <div class="notes-title">Notes du pasteur</div>
                    <div class="notes-content">
                        @foreach($apt['pastor_notes'] as $note)
                        <div class="note-item">
                            @if($note['date'])
                            <span class="note-date">{{ $note['date'] }}</span><br>
                            @endif
                            {{ $note['content'] }}
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-church">{{ $church['name'] }}</div>
            <div>{{ $church['email'] }} | {{ $church['phone'] }}</div>
            <div>{{ $church['website'] }}</div>
            <div style="margin-top: 10px;">
                Ce document est strictement confidentiel et destiné uniquement à l'usage pastoral interne.
            </div>
        </div>
    </div>
</body>
</html>
