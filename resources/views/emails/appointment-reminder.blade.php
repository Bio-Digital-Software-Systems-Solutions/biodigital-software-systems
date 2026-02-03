<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel : {{ $appointment->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            background: linear-gradient(to right, #3b82f6, #8b5cf6, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 32px;
            margin: 0;
            font-weight: bold;
        }
        h2 {
            color: #1f2937;
            margin-top: 0;
        }
        .reminder-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .reminder-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .reminder-header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .appointment-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .appointment-details {
            display: grid;
            gap: 12px;
        }
        .detail-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-icon {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        .detail-label {
            font-weight: 600;
            color: #475569;
            min-width: 90px;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 500;
        }
        .view-button {
            display: block;
            width: 100%;
            max-width: 280px;
            margin: 30px auto;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            font-size: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .organizer-info {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .organizer-info h4 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 16px;
        }
        .participants-section {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .participants-section h4 {
            margin: 0 0 10px 0;
            color: #166534;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>ICC M&uuml;nchen</h1>
        </div>

        <div class="reminder-header">
            <h3>Rappel de rendez-vous</h3>
            <p>Votre rendez-vous est pr&eacute;vu pour demain !</p>
        </div>

        <h2>Bonjour {{ $recipient->first_name }},</h2>

        @if($isOrganizer)
        <p>Ceci est un rappel pour le rendez-vous que vous avez organis&eacute; :</p>
        @else
        <p>Ceci est un rappel pour votre rendez-vous :</p>
        @endif

        <div class="appointment-card">
            <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 20px;">{{ $appointment->title }}</h3>

            @if($appointment->description)
            <p style="margin: 0 0 20px 0; padding: 15px; background-color: #f0f9ff; border-radius: 6px; color: #0c4a6e; border-left: 3px solid #0ea5e9;">
                <strong>Description :</strong><br>
                {{ $appointment->description }}
            </p>
            @endif

            <div class="appointment-details">
                <div class="detail-row">
                    <span class="detail-icon">date</span>
                    <span class="detail-label">Date :</span>
                    <span class="detail-value">{{ $startDate->format('l j F Y') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">time</span>
                    <span class="detail-label">Heure :</span>
                    <span class="detail-value">{{ $startDate->format('H:i') }} - {{ $endDate->format('H:i') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">duration</span>
                    <span class="detail-label">Dur&eacute;e :</span>
                    <span class="detail-value">{{ $appointment->duration_minutes }} minutes</span>
                </div>

                @if($appointment->location)
                <div class="detail-row">
                    <span class="detail-icon">location</span>
                    <span class="detail-label">Lieu :</span>
                    <span class="detail-value">{{ $appointment->location }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-icon">type</span>
                    <span class="detail-label">Type :</span>
                    <span class="detail-value">{{ ucfirst($appointment->type) }}</span>
                </div>

                @if($appointment->meeting_mode && $appointment->meeting_mode !== 'in_person')
                <div class="detail-row">
                    <span class="detail-icon">mode</span>
                    <span class="detail-label">Mode :</span>
                    <span class="detail-value">
                        @if($appointment->meeting_mode === 'online')
                            En ligne
                        @else
                            Hybride
                        @endif
                    </span>
                </div>
                @endif
            </div>

            @if($appointment->meeting_link && in_array($appointment->meeting_mode, ['online', 'hybrid']))
            <div style="margin-top: 20px; padding: 15px; background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%); border-radius: 8px; border: 1px solid #667eea40;">
                <p style="margin: 0 0 10px 0; font-weight: 600; color: #4c1d95;">
                    Rejoindre la r&eacute;union en ligne
                    @if($appointment->meeting_platform)
                        ({{ $appointment->meeting_platform === 'google_meet' ? 'Google Meet' : ($appointment->meeting_platform === 'ms_teams' ? 'Microsoft Teams' : ucfirst($appointment->meeting_platform)) }})
                    @endif
                </p>
                <a href="{{ $appointment->meeting_link }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                    Cliquer ici pour rejoindre
                </a>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #6b7280; word-break: break-all;">
                    {{ $appointment->meeting_link }}
                </p>
            </div>
            @endif
        </div>

        @if(!$isOrganizer)
        <div class="organizer-info">
            <h4>Organisateur</h4>
            <p style="margin: 0;">
                <strong>{{ $appointment->organizer->first_name }} {{ $appointment->organizer->last_name }}</strong><br>
                {{ $appointment->organizer->email }}
            </p>
        </div>
        @endif

        @if($isOrganizer && $confirmedParticipantsCount > 0)
        <div class="participants-section">
            <h4>Participants confirm&eacute;s ({{ $confirmedParticipantsCount }})</h4>
            <p style="margin: 0;">
                {{ $confirmedParticipantsCount }} participant(s) ont confirm&eacute; leur pr&eacute;sence.
            </p>
        </div>
        @endif

        <a href="{{ $detailUrl }}" class="view-button">
            Voir les d&eacute;tails du rendez-vous
        </a>

        <p>En cas d'emp&ecirc;chement, veuillez pr&eacute;venir l'organisateur d&egrave;s que possible.</p>

        <p>Cordialement,<br>
        <strong>L'&eacute;quipe ICC M&uuml;nchen</strong></p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ICC M&uuml;nchen. Tous droits r&eacute;serv&eacute;s.</p>
            <p style="margin-top: 10px;">
                Cet email concerne le rendez-vous : {{ $appointment->title }}<br>
                Pr&eacute;vu pour le {{ $startDate->format('d/m/Y') }} &agrave; {{ $startDate->format('H:i') }}
            </p>
        </div>
    </div>
</body>
</html>
