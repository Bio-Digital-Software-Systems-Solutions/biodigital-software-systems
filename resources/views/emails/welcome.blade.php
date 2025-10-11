<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue à ICC München</title>
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
        .info-box {
            background-color: #f3f4f6;
            border-left: 4px solid #3b82f6;
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
        .warning {
            color: #dc2626;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>ICC München</h1>
        </div>

        <h2>Bienvenue, {{ $user->first_name }} {{ $user->last_name }} !</h2>

        <p>Nous sommes ravis de vous accueillir dans la communauté ICC München. Votre inscription a été effectuée avec succès !</p>

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
        <strong>L'équipe ICC München</strong></p>

        <div class="footer">
            <p>© {{ date('Y') }} ICC München. Tous droits réservés.</p>
            <p>Si vous avez des problèmes avec le bouton ci-dessus, copiez et collez ce lien dans votre navigateur :<br>
            <span style="color: #3b82f6; word-break: break-all;">{{ $verificationUrl }}</span></p>
        </div>
    </div>
</body>
</html>
