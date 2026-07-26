<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'inscription soumise</title>
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
        .status-box {
            background-color: #FBE9EB;
            border: 1px solid #D41F32;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .status-box p {
            margin: 0;
            color: #A8182A;
            font-weight: bold;
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
        .info-icon {
            font-size: 48px;
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
                <div class="info-icon">📩</div>
                <h1>Demande d'inscription soumise</h1>
            </div>

            <p class="greeting">Bonjour {{ $userName }},</p>

            <p class="message">
                Nous avons bien reçu votre demande d'inscription à la formation suivante.
                Votre candidature est actuellement en cours d'examen par notre équipe.
            </p>

            <div class="training-info">
                <h3>📚 {{ $trainingName }}</h3>
                <p><strong>Mode de paiement choisi :</strong> {{ $paymentMethod }}</p>
            </div>

            <div class="status-box">
                <p>⏳ Statut : En attente de validation</p>
            </div>

            <p class="message">
                Vous recevrez un email de confirmation dès que votre inscription aura été examinée.
                Ce processus prend généralement quelques jours ouvrables.
            </p>

            <center>
                <a href="{{ config('app.url') }}/trainings" class="button">
                    Voir nos formations
                </a>
            </center>

            <p class="message" style="margin-top: 30px;">
                Si vous avez des questions concernant votre inscription, n'hésitez pas à nous contacter.
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
