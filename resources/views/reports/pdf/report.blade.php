<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $report->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        /* Header */
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header-subtitle {
            font-size: 12px;
            color: #6b7280;
        }
        .header-department {
            font-size: 14px;
            color: #374151;
            margin-top: 5px;
        }
        .header-period {
            font-size: 12px;
            color: #4b5563;
            margin-top: 3px;
        }
        /* Meta info */
        .meta-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            background: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
        }
        .meta-row {
            display: table-row;
        }
        .meta-cell {
            display: table-cell;
            padding: 3px 10px;
            width: 50%;
        }
        .meta-label {
            font-weight: bold;
            color: #4b5563;
        }
        /* Summary section */
        .executive-summary {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin-bottom: 20px;
        }
        .executive-summary h2 {
            font-size: 14px;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .executive-summary p {
            color: #374151;
        }
        /* Metrics grid */
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .metrics-row {
            display: table-row;
        }
        .metric-card {
            display: table-cell;
            width: 33.33%;
            padding: 5px;
            vertical-align: top;
        }
        .metric-inner {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }
        .metric-label {
            font-size: 10px;
            color: #6b7280;
            margin-top: 3px;
        }
        .metric-trend {
            font-size: 9px;
            margin-top: 3px;
        }
        .trend-up { color: #16a34a; }
        .trend-down { color: #dc2626; }
        .trend-stable { color: #6b7280; }
        /* Section */
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .section-content {
            padding-left: 10px;
        }
        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table.data-table th {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #374151;
        }
        table.data-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) {
            background: #f9fafb;
        }
        /* Lists */
        .activity-list {
            list-style: none;
        }
        .activity-item {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .activity-title {
            font-weight: bold;
            color: #1f2937;
        }
        .activity-meta {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }
        /* Progress bar */
        .progress-container {
            background: #e5e7eb;
            border-radius: 10px;
            height: 8px;
            margin: 5px 0;
        }
        .progress-bar {
            background: #2563eb;
            height: 8px;
            border-radius: 10px;
        }
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-at_risk { background: #fef3c7; color: #92400e; }
        .status-not_started { background: #f3f4f6; color: #4b5563; }
        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #6b7280;
        }
        .footer-left {
            float: left;
        }
        .footer-right {
            float: right;
        }
        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-title">{{ $report->title }}</div>
            <div class="header-department">{{ $department->name }}</div>
            <div class="header-period">
                Période: {{ \Carbon\Carbon::parse($report->period_start)->format('d/m/Y') }}
                - {{ \Carbon\Carbon::parse($report->period_end)->format('d/m/Y') }}
                ({{ $report->period_label }})
            </div>
        </div>

        <!-- Meta Information -->
        <div class="meta-info">
            <div class="meta-row">
                <div class="meta-cell">
                    <span class="meta-label">Type:</span> {{ $report->type->label() }}
                </div>
                <div class="meta-cell">
                    <span class="meta-label">Statut:</span> {{ $report->status->label() }}
                </div>
            </div>
            <div class="meta-row">
                <div class="meta-cell">
                    <span class="meta-label">Auteur:</span> {{ $author?->full_name ?? 'N/A' }}
                </div>
                <div class="meta-cell">
                    <span class="meta-label">Généré le:</span> {{ $generatedAt->format('d/m/Y à H:i') }}
                </div>
            </div>
            @if($report->approver)
            <div class="meta-row">
                <div class="meta-cell">
                    <span class="meta-label">Approuvé par:</span> {{ $report->approver->full_name }}
                </div>
                <div class="meta-cell">
                    <span class="meta-label">Date d'approbation:</span>
                    {{ $report->approved_at ? \Carbon\Carbon::parse($report->approved_at)->format('d/m/Y') : 'N/A' }}
                </div>
            </div>
            @endif
        </div>

        <!-- Executive Summary -->
        @if($report->executive_summary)
        <div class="executive-summary">
            <h2>Résumé Exécutif</h2>
            <p>{{ $report->executive_summary }}</p>
        </div>
        @endif

        <!-- Key Metrics -->
        @if(isset($aggregatedData['summary']))
        <div class="section">
            <div class="section-title">Indicateurs Clés</div>
            <div class="metrics-grid">
                <div class="metrics-row">
                    <div class="metric-card">
                        <div class="metric-inner">
                            <div class="metric-value">{{ $aggregatedData['summary']['total_activities'] ?? 0 }}</div>
                            <div class="metric-label">Activités</div>
                            @if(isset($aggregatedData['trends']['activities']))
                            <div class="metric-trend {{ $aggregatedData['trends']['activities']['direction'] === 'up' ? 'trend-up' : ($aggregatedData['trends']['activities']['direction'] === 'down' ? 'trend-down' : 'trend-stable') }}">
                                {{ $aggregatedData['trends']['activities']['percentage'] > 0 ? '+' : '' }}{{ $aggregatedData['trends']['activities']['percentage'] }}%
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-inner">
                            <div class="metric-value">{{ $aggregatedData['summary']['total_hours'] ?? 0 }}h</div>
                            <div class="metric-label">Heures Travaillées</div>
                            @if(isset($aggregatedData['trends']['hours']))
                            <div class="metric-trend {{ $aggregatedData['trends']['hours']['direction'] === 'up' ? 'trend-up' : ($aggregatedData['trends']['hours']['direction'] === 'down' ? 'trend-down' : 'trend-stable') }}">
                                {{ $aggregatedData['trends']['hours']['percentage'] > 0 ? '+' : '' }}{{ $aggregatedData['trends']['hours']['percentage'] }}%
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-inner">
                            <div class="metric-value">{{ $aggregatedData['summary']['completion_rate'] ?? 0 }}%</div>
                            <div class="metric-label">Taux de Réalisation</div>
                        </div>
                    </div>
                </div>
                <div class="metrics-row">
                    <div class="metric-card">
                        <div class="metric-inner">
                            <div class="metric-value">{{ $aggregatedData['summary']['objectives_completed'] ?? 0 }}/{{ $aggregatedData['summary']['objectives_total'] ?? 0 }}</div>
                            <div class="metric-label">Objectifs Complétés</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-inner">
                            <div class="metric-value">{{ $aggregatedData['summary']['unique_participants'] ?? 0 }}</div>
                            <div class="metric-label">Participants</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-inner">
                            <div class="metric-value">{{ $aggregatedData['summary']['projects_active'] ?? 0 }}</div>
                            <div class="metric-label">Projets Actifs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Objectives Section -->
        @if(isset($aggregatedData['objectives']) && count($aggregatedData['objectives']['list'] ?? []) > 0)
        <div class="section">
            <div class="section-title">Objectifs ({{ $aggregatedData['objectives']['total'] ?? 0 }})</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 35%">Objectif</th>
                        <th style="width: 15%">Statut</th>
                        <th style="width: 15%">Progrès</th>
                        <th style="width: 15%">Échéance</th>
                        <th style="width: 20%">Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aggregatedData['objectives']['list'] as $objective)
                    <tr>
                        <td>{{ $objective['title'] }}</td>
                        <td>
                            <span class="status-badge status-{{ $objective['status'] }}">
                                {{ $objective['status_label'] }}
                            </span>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: {{ $objective['progress'] }}%"></div>
                            </div>
                            {{ $objective['progress'] }}%
                        </td>
                        <td>{{ $objective['target_date'] ? \Carbon\Carbon::parse($objective['target_date'])->format('d/m/Y') : '-' }}</td>
                        <td>{{ $objective['assignee'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Activities by Category -->
        @if(isset($aggregatedData['activities']['by_category']))
        <div class="section">
            <div class="section-title">Activités par Catégorie</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Nombre</th>
                        <th>Heures</th>
                        <th>Participants</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aggregatedData['activities']['by_category'] as $category => $stats)
                    @if($stats['count'] > 0)
                    <tr>
                        <td>{{ $stats['label'] }}</td>
                        <td>{{ $stats['count'] }}</td>
                        <td>{{ $stats['hours'] }}h</td>
                        <td>{{ $stats['participants'] }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Recent Activities -->
        @if(isset($aggregatedData['activities']['recent']) && count($aggregatedData['activities']['recent']) > 0)
        <div class="section">
            <div class="section-title">Activités Récentes</div>
            <ul class="activity-list">
                @foreach(array_slice($aggregatedData['activities']['recent'], 0, 10) as $activity)
                <li class="activity-item">
                    <div class="activity-title">{{ $activity['title'] }}</div>
                    <div class="activity-meta">
                        {{ $activity['category_label'] }} | {{ \Carbon\Carbon::parse($activity['date'])->format('d/m/Y') }}
                        @if($activity['duration'])
                        | {{ $activity['duration'] }}h
                        @endif
                        @if($activity['user'])
                        | {{ $activity['user'] }}
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- KPIs -->
        @if(isset($aggregatedData['kpis']) && count($aggregatedData['kpis']) > 0)
        <div class="section">
            <div class="section-title">Indicateurs de Performance (KPIs)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Indicateur</th>
                        <th>Valeur Actuelle</th>
                        <th>Cible</th>
                        <th>Statut</th>
                        <th>Tendance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aggregatedData['kpis'] as $kpi)
                    <tr>
                        <td>{{ $kpi['name'] }}</td>
                        <td>{{ $kpi['current'] ?? '-' }} {{ $kpi['unit'] }}</td>
                        <td>{{ $kpi['target'] }} {{ $kpi['unit'] }}</td>
                        <td>
                            <span class="status-badge" style="background: {{ $kpi['status_color'] === 'green' ? '#dcfce7' : ($kpi['status_color'] === 'yellow' ? '#fef3c7' : '#fee2e2') }}; color: {{ $kpi['status_color'] === 'green' ? '#166534' : ($kpi['status_color'] === 'yellow' ? '#92400e' : '#991b1b') }}">
                                {{ ucfirst($kpi['performance_status']) }}
                            </span>
                        </td>
                        <td class="{{ $kpi['trend']['direction'] === 'up' ? 'trend-up' : ($kpi['trend']['direction'] === 'down' ? 'trend-down' : 'trend-stable') }}">
                            {{ $kpi['trend']['percentage'] > 0 ? '+' : '' }}{{ $kpi['trend']['percentage'] }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Projects -->
        @if(isset($aggregatedData['projects']['list']) && count($aggregatedData['projects']['list']) > 0)
        <div class="section">
            <div class="section-title">Projets ({{ $aggregatedData['projects']['total'] ?? 0 }})</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Projet</th>
                        <th>Statut</th>
                        <th>Progrès</th>
                        <th>Tâches</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aggregatedData['projects']['list'] as $project)
                    <tr>
                        <td>{{ $project['name'] }}</td>
                        <td>{{ ucfirst($project['status']) }}</td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: {{ $project['progress'] }}%"></div>
                            </div>
                            {{ $project['progress'] }}%
                        </td>
                        <td>{{ $project['tasks_completed'] }}/{{ $project['tasks_total'] }}</td>
                        <td>{{ $project['manager'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Custom Sections -->
        @foreach($sections as $section)
        @if($section->is_visible && $section->content)
        <div class="section">
            <div class="section-title">{{ $section->title }}</div>
            <div class="section-content">
                @if($section->type->value === 'text' && isset($section->content['text']))
                    <p>{{ $section->content['text'] }}</p>
                @elseif($section->type->value === 'checklist' && isset($section->content['items']))
                    <ul>
                    @foreach($section->content['items'] as $item)
                        <li>
                            {{ $item['completed'] ? '✓' : '○' }} {{ $item['label'] }}
                            @if(isset($item['progress']))
                            ({{ $item['progress'] }}%)
                            @endif
                        </li>
                    @endforeach
                    </ul>
                @elseif($section->type->value === 'list' && isset($section->content['items']))
                    <ul class="activity-list">
                    @foreach($section->content['items'] as $item)
                        <li class="activity-item">
                            <div class="activity-title">{{ $item['title'] }}</div>
                            @if(isset($item['subtitle']))
                            <div class="activity-meta">{{ $item['subtitle'] }}</div>
                            @endif
                        </li>
                    @endforeach
                    </ul>
                @endif
            </div>
        </div>
        @endif
        @endforeach

        <!-- Tags -->
        @if($report->tags && $report->tags->count() > 0)
        <div class="section">
            <div class="section-title">Tags</div>
            <p>
            @foreach($report->tags as $tag)
                <span style="background: #e5e7eb; padding: 2px 8px; border-radius: 10px; margin-right: 5px; font-size: 10px;">
                    {{ $tag->tag }}
                </span>
            @endforeach
            </p>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <span class="footer-left">{{ $department->name }} - {{ $report->title }}</span>
        <span class="footer-right">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</span>
    </div>
</body>
</html>
