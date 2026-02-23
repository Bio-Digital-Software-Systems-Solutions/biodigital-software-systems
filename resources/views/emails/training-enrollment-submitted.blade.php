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
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
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
            color: #2563eb;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
            color: #555;
        }
        .training-info {
            background-color: #f9fafb;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
        }
        .training-info h3 {
            margin-top: 0;
            color: #2563eb;
        }
        .training-info p {
            margin: 8px 0;
        }
        .status-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .status-box p {
            margin: 0;
            color: #1e40af;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
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
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="info-icon">📩</div>
            <h1>Demande d'inscription soumise</h1>
        </div>

        <div class="content">
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
