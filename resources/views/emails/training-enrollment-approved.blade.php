<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Approuvée</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .brand-header {
            background-color: #171717;
            text-align: center;
            padding: 28px 40px;
        }
        .brand-wordmark {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff;
        }
        .brand-wordmark .brand-accent {
            color: #EB5462;
        }
        .brand-descriptor {
            margin: 6px 0 0;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: rgba(255, 255, 255, 0.7);
        }
        .content {
            padding: 30px;
        }
        .intro-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .intro-title h1 {
            margin: 0;
            font-size: 24px;
            color: #18181b;
        }
        .greeting {
            font-size: 18px;
            color: #D41F32;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
            color: #555;
        }
        .training-info {
            background-color: #FBE9EB;
            border-left: 4px solid #D41F32;
            padding: 15px;
            margin: 20px 0;
        }
        .training-info h3 {
            margin-top: 0;
            color: #D41F32;
        }
        .training-info p {
            margin: 8px 0;
        }
        .schedule-section {
            margin-top: 20px;
        }
        .schedule-item {
            background-color: #f3f4f6;
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
        }
        .schedule-item strong {
            color: #D41F32;
        }
        .button {
            display: inline-block;
            background-color: #D41F32;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #A8182A;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .success-icon {
            font-size: 48px;
            color: #10b981;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-header">
            <h1 class="brand-wordmark">Bio-<span class="brand-accent">Digital</span></h1>
            <p class="brand-descriptor">Software Systems Solutions UG (haftungsbeschränkt)</p>
        </div>

        <div class="content">
            <div class="intro-title">
                <div class="success-icon">✓</div>
                <h1>Félicitations !</h1>
            </div>

            <p class="greeting">Bonjour {{ $userName }},</p>

            <p class="message">
                Nous avons le plaisir de vous informer que votre demande d'inscription a été <strong>approuvée</strong> !
            </p>

            <div class="training-info">
                <h3>📚 {{ $trainingName }}</h3>

                @if($trainingClass)
                    <p><strong>Enseignant :</strong> {{ $trainingClass->teacher_name ?? 'À déterminer' }}</p>
                    <p><strong>Salle :</strong> {{ $trainingClass->room ?? 'À déterminer' }}</p>
                    @if($trainingClass->date)
                        <p><strong>Date :</strong> {{ \Carbon\Carbon::parse($trainingClass->date)->format('d/m/Y') }}</p>
                    @endif
                    @if($trainingClass->start_time && $trainingClass->end_time)
                        <p><strong>Horaire :</strong> {{ \Carbon\Carbon::parse($trainingClass->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($trainingClass->end_time)->format('H:i') }}</p>
                    @endif
                    @if($trainingClass->max_students)
                        <p><strong>Capacité max :</strong> {{ $trainingClass->max_students }} étudiants</p>
                    @endif
                @endif
            </div>

            @if($schedules && count($schedules) > 0)
                <div class="schedule-section">
                    <h3>📅 Emploi du temps</h3>
                    @foreach($schedules as $schedule)
                        <div class="schedule-item">
                            <strong>{{ $schedule->day_name }}</strong> :
                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="message">
                Vous pouvez dès maintenant accéder à votre espace étudiant pour consulter les détails de la formation,
                les ressources pédagogiques et votre progression.
            </p>

            <center>
                <a href="{{ config('app.url') }}/student/dashboard" class="button">
                    Accéder à mon espace
                </a>
            </center>

            <p class="message" style="margin-top: 30px;">
                Si vous avez des questions, n'hésitez pas à nous contacter.
            </p>

            <p style="margin-top: 30px; color: #666;">
                Bonne formation !<br>
                L'équipe pédagogique
            </p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} AIG-App. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
