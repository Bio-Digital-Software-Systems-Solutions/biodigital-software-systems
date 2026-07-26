<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue à {{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #27272a;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            padding: 40px;
        }
        h2 {
            color: #18181b;
            margin-top: 0;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background-color: #D41F32;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        .btn:hover {
            background-color: #A8182A;
        }
        .info-box {
            background-color: #FBE9EB;
            border-left: 4px solid #D41F32;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .footer a,
        .link {
            color: #D41F32;
            word-break: break-all;
        }
        .warning {
            color: #A8182A;
            font-size: 14px;
            margin-top: 20px;
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
            <h2>Bienvenue, {{ $user->first_name }} {{ $user->last_name }} !</h2>

            <p>Nous sommes ravis de vous accueillir chez {{ config('app.name') }}. Votre inscription a été effectuée avec succès !</p>

            <div class="info-box">
                <strong>Informations de votre compte :</strong><br>
                📧 Email : {{ $user->email }}<br>
                👤 Nom : {{ $user->first_name }} {{ $user->last_name }}<br>
                📅 Date d'inscription : {{ now()->format('d/m/Y') }}
            </div>

            <p>Pour commencer à utiliser votre compte, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :</p>

            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="btn">Confirmer mon email</a>
            </div>

            <p class="warning">⚠️ Ce lien est valide pendant 60 minutes. Si vous n'avez pas créé ce compte, veuillez ignorer cet email.</p>

            <p>Une fois votre email confirmé, vous pourrez :</p>
            <ul>
                <li>Accéder à votre tableau de bord personnel</li>
                <li>Participer aux événements et formations</li>
                <li>Emprunter des livres de notre bibliothèque</li>
                <li>Lire et créer des articles</li>
                <li>Communiquer avec la communauté</li>
            </ul>

            <p>Si vous avez des questions, n'hésitez pas à nous contacter à tout moment.</p>

            <p>Cordialement,<br>
            <strong>L'équipe {{ config('app.name') }}</strong></p>

            <div class="footer">
                <p>© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
                <p>Si vous avez des problèmes avec le bouton ci-dessus, copiez et collez ce lien dans votre navigateur :<br>
                <span class="link">{{ $verificationUrl }}</span></p>
            </div>
        </div>
    </div>
</body>
</html>
