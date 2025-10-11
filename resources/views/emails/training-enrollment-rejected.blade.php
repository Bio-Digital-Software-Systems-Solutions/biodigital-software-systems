<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'inscription</title>
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
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #dc2626;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
            color: #555;
        }
        .training-info {
            background-color: #f9fafb;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
        }
        .training-info h3 {
            margin-top: 0;
            color: #dc2626;
        }
        .reason-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .reason-box h4 {
            margin-top: 0;
            color: #991b1b;
        }
        .reason-box p {
            color: #7f1d1d;
            margin: 0;
        }
        .button {
            display: inline-block;
            background-color: #7c3aed;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .info-icon {
            font-size: 48px;
            color: #dc2626;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="info-icon">ⓘ</div>
            <h1>Demande d'inscription</h1>
        </div>

        <div class="content">
            <p class="greeting">Bonjour {{ $userName }},</p>

            <p class="message">
                Nous vous remercions de l'intérêt que vous portez à notre formation. Après examen de votre demande,
                nous sommes au regret de vous informer que nous ne pouvons pas donner suite favorable à votre inscription.
            </p>

            <div class="training-info">
                <h3>📚 {{ $trainingName }}</h3>
            </div>

            <div class="reason-box">
                <h4>💡 Raison de la décision</h4>
                <p>{{ $rejectionReason }}</p>
            </div>

            <p class="message">
                Cette décision ne remet pas en cause vos compétences ou votre motivation. Nous vous encourageons à
                explorer d'autres formations qui pourraient mieux correspondre à votre profil actuel.
            </p>

            <p class="message">
                N'hésitez pas à consulter notre catalogue de formations pour découvrir d'autres opportunités
                qui pourraient vous intéresser.
            </p>

            <center>
                <a href="{{ config('app.url') }}/trainings" class="button">
                    Voir toutes les formations
                </a>
            </center>

            <p class="message" style="margin-top: 30px;">
                Si vous souhaitez des précisions concernant cette décision ou si vous avez des questions,
                notre équipe reste à votre disposition.
            </p>

            <p style="margin-top: 30px; color: #666;">
                Cordialement,<br>
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
