<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isOrganizer ? 'Confirmation de création de rendez-vous' : 'Nouveau rendez-vous' }}</title>
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
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        .btn:hover {
            background: linear-gradient(to right, #2563eb, #7c3aed);
        }
        .appointment-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .appointment-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .appointment-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 5px 0;
        }
        .appointment-type {
            display: inline-block;
            padding: 4px 8px;
            background-color: #ddd6fe;
            color: #7c3aed;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .appointment-details {
            display: grid;
            gap: 10px;
        }
        .detail-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-icon {
            width: 16px;
            height: 16px;
            color: #64748b;
        }
        .detail-label {
            font-weight: 500;
            color: #475569;
            min-width: 80px;
        }
        .detail-value {
            color: #1e293b;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .organizer-info {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>ICC München</h1>
        </div>

        <h2>
            @if($isOrganizer)
                Confirmation de création de rendez-vous
            @else
                Nouveau rendez-vous planifié
            @endif
        </h2>

        <p>Bonjour {{ $recipient->first_name }},</p>

        @if($isOrganizer)
            <p>Votre rendez-vous a été créé avec succès. Voici un récapitulatif :</p>
        @else
            <p>Un nouveau rendez-vous a été planifié avec votre participation. Voici les détails :</p>
        @endif

        <div class="appointment-card">
            <div class="appointment-header">
                <h3 class="appointment-title">{{ $appointment->title }}</h3>
                <span class="appointment-type">
                    @switch($appointment->type)
                        @case('individual')
                            Individuel
                            @break
                        @case('group')
                            Groupe
                            @break
                        @case('consultation')
                            Consultation
                            @break
                        @case('meeting')
                            Réunion
                            @break
                        @default
                            {{ $appointment->type }}
                    @endswitch
                </span>
            </div>

            <div class="appointment-details">
                <div class="detail-row">
                    <span class="detail-icon">📅</span>
                    <span class="detail-label">Date :</span>
                    <span class="detail-value">{{ $appointment->start_datetime->format('l j F Y') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">⏰</span>
                    <span class="detail-label">Heure :</span>
                    <span class="detail-value">{{ $appointment->start_datetime->format('H:i') }} - {{ $appointment->end_datetime->format('H:i') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">⏳</span>
                    <span class="detail-label">Durée :</span>
                    <span class="detail-value">{{ $appointment->duration_minutes }} minutes</span>
                </div>

                @if($appointment->location)
                <div class="detail-row">
                    <span class="detail-icon">📍</span>
                    <span class="detail-label">Lieu :</span>
                    <span class="detail-value">{{ $appointment->location }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-icon">👤</span>
                    <span class="detail-label">Organisateur :</span>
                    <span class="detail-value">{{ $appointment->organizer->first_name }} {{ $appointment->organizer->last_name }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">📊</span>
                    <span class="detail-label">Statut :</span>
                    <span class="detail-value">
                        <span class="status-badge status-{{ $appointment->status }}">
                            @switch($appointment->status)
                                @case('pending')
                                    En attente
                                    @break
                                @case('confirmed')
                                    Confirmé
                                    @break
                                @case('cancelled')
                                    Annulé
                                    @break
                                @case('completed')
                                    Terminé
                                    @break
                                @default
                                    {{ $appointment->status }}
                            @endswitch
                        </span>
                    </span>
                </div>

                @if($appointment->participants_count > 0)
                <div class="detail-row">
                    <span class="detail-icon">👥</span>
                    <span class="detail-label">Participants :</span>
                    <span class="detail-value">{{ $appointment->participants_count }} personne(s)</span>
                </div>
                @endif
            </div>

            @if($appointment->description)
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <strong>Description :</strong><br>
                <span style="color: #475569;">{{ $appointment->description }}</span>
            </div>
            @endif
        </div>

        @if(!$isOrganizer)
        <div class="organizer-info">
            <strong>Contact de l'organisateur :</strong><br>
            📧 {{ $appointment->organizer->email }}<br>
            👤 {{ $appointment->organizer->first_name }} {{ $appointment->organizer->last_name }}
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ route('appointments.show', $appointment->uuid) }}" class="btn">Voir le rendez-vous</a>
        </div>

        @if(!$isOrganizer && $appointment->status === 'pending')
        <p style="background-color: #fef3c7; padding: 15px; border-radius: 8px; color: #92400e; margin: 20px 0;">
            ⚠️ <strong>Action requise :</strong> Ce rendez-vous est en attente de votre confirmation. Veuillez consulter votre tableau de bord pour confirmer ou décliner votre participation.
        </p>
        @endif

        <p>
            @if($isOrganizer)
                Vous pouvez modifier ou annuler ce rendez-vous depuis votre tableau de bord si nécessaire.
            @else
                Si vous avez des questions concernant ce rendez-vous, n'hésitez pas à contacter l'organisateur.
            @endif
        </p>

        <p>Cordialement,<br>
        <strong>L'équipe ICC München</strong></p>

        <div class="footer">
            <p>© {{ date('Y') }} ICC München. Tous droits réservés.</p>
            <p style="margin-top: 10px;">
                📧 Cet email concerne le rendez-vous : {{ $appointment->title }}<br>
                📅 Prévu pour le {{ $appointment->start_datetime->format('d/m/Y à H:i') }}
            </p>
        </div>
    </div>
</body>
</html>