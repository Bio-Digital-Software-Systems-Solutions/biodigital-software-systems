<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation au rendez-vous : {{ $appointment->title }}</title>
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
        .invitation-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .invitation-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
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
        .actions-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        .actions-title {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 20px 0;
        }
        .actions-subtitle {
            color: rgba(255, 255, 255, 0.9);
            margin: 0 0 30px 0;
            font-size: 16px;
        }
        .buttons-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            font-size: 16px;
            transition: all 0.3s ease;
            min-width: 140px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-confirm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
        }
        .btn-confirm:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        .btn-decline {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff;
        }
        .btn-decline:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
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
        .fallback-links {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .fallback-links h4 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 14px;
        }
        .fallback-links a {
            color: #3b82f6;
            word-break: break-all;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        @media (max-width: 480px) {
            .buttons-container {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                max-width: 280px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>ICC München</h1>
        </div>

        <div class="invitation-header">
            <h3>📅 Invitation au rendez-vous</h3>
        </div>

        <h2>Bonjour {{ $participant->first_name }},</h2>

        <p>Vous êtes invité(e) au rendez-vous suivant :</p>

        <div class="appointment-card">
            <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 20px;">{{ $appointment->title }}</h3>

            @if($appointment->description)
            <p style="margin: 0 0 20px 0; padding: 15px; background-color: #f0f9ff; border-radius: 6px; color: #0c4a6e; border-left: 3px solid #0ea5e9;">
                <strong>📝 Description :</strong><br>
                {{ $appointment->description }}
            </p>
            @endif

            <div class="appointment-details">
                <div class="detail-row">
                    <span class="detail-icon">📅</span>
                    <span class="detail-label">Date :</span>
                    <span class="detail-value">{{ $startDate->format('l j F Y') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">🕐</span>
                    <span class="detail-label">Heure :</span>
                    <span class="detail-value">{{ $startDate->format('H:i') }} - {{ $endDate->format('H:i') }}</span>
                </div>

                @if($appointment->location)
                <div class="detail-row">
                    <span class="detail-icon">📍</span>
                    <span class="detail-label">Lieu :</span>
                    <span class="detail-value">{{ $appointment->location }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-icon">🏷️</span>
                    <span class="detail-label">Type :</span>
                    <span class="detail-value">{{ ucfirst($appointment->type) }}</span>
                </div>
            </div>
        </div>

        <div class="organizer-info">
            <h4>👤 Organisateur</h4>
            <p style="margin: 0;">
                <strong>{{ $appointment->organizer->first_name }} {{ $appointment->organizer->last_name }}</strong><br>
                📧 {{ $appointment->organizer->email }}
            </p>
        </div>

        <div class="actions-section">
            <h3 class="actions-title">⚡ Actions rapides</h3>
            <p class="actions-subtitle">Merci de confirmer votre participation en cliquant sur l'un des boutons ci-dessous :</p>

            <div class="buttons-container">
                <a href="{{ $confirmUrl }}" class="btn btn-confirm">
                    ✅ Confirmer ma participation
                </a>
                <a href="{{ $declineUrl }}" class="btn btn-decline">
                    ❌ Décliner l'invitation
                </a>
            </div>
        </div>

        <div class="fallback-links">
            <h4>🔗 Liens alternatifs</h4>
            <p style="margin: 5px 0;">Si vous ne pouvez pas cliquer sur les boutons, copiez et collez les liens suivants dans votre navigateur :</p>
            <p style="margin: 10px 0 5px 0;"><strong>Confirmer :</strong><br><a href="{{ $confirmUrl }}">{{ $confirmUrl }}</a></p>
            <p style="margin: 5px 0;"><strong>Décliner :</strong><br><a href="{{ $declineUrl }}">{{ $declineUrl }}</a></p>
        </div>

        <p>Merci de répondre à cette invitation !</p>

        <p>Cordialement,<br>
        <strong>L'équipe ICC München</strong></p>

        <div class="footer">
            <p>© {{ date('Y') }} ICC München. Tous droits réservés.</p>
            <p style="margin-top: 10px;">
                📧 Cet email concerne le rendez-vous : {{ $appointment->title }}<br>
                📅 Prévu pour le {{ $startDate->format('d/m/Y à H:i') }}
            </p>
        </div>
    </div>
</body>
</html>