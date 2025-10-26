  <?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  echo "<!DOCTYPE html>
  <html>
  <head>
      <meta charset='UTF-8'>
      <title>Diagnostic Laravel</title>
      <style>
          body { font-family: Arial; max-width: 1000px; margin: 20px auto; padding: 20px; }
          .ok { color: green; font-weight: bold; }
          .error { color: red; font-weight: bold; }
          .section { background: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; }
          pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; overflow-x: auto; }
      </style>
  </head>
  <body>
      <h1>🔍 Diagnostic Laravel - ICC Munich</h1>
  ";

  // 1. Chemins
  echo "<div class='section'>";
  echo "<h2>1. Chemins</h2>";
  echo "Document Root: <strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong><br>";
  echo "Script actuel: <strong>" . __FILE__ . "</strong><br>";
  echo "Dossier parent: <strong>" . dirname(__DIR__) . "</strong><br>";
  echo "</div>";

  // 2. Permissions
  echo "<div class='section'>";
  echo "<h2>2. Permissions</h2>";

  $paths = [
      'vendor' => __DIR__ . '/../vendor',
      'bootstrap/cache' => __DIR__ . '/../bootstrap/cache',
      'storage' => __DIR__ . '/../storage',
      'storage/logs' => __DIR__ . '/../storage/logs',
      'storage/framework' => __DIR__ . '/../storage/framework',
      '.env' => __DIR__ . '/../.env',
  ];

  foreach ($paths as $name => $path) {
      $exists = file_exists($path);
      $readable = $exists && is_readable($path);
      $writable = $exists && is_writable($path);

      echo "$name: ";
      if (!$exists) {
          echo "<span class='error'>✗ N'existe pas</span>";
      } else {
          echo "<span class='ok'>✓ Existe</span>";
          echo " | Lecture: " . ($readable ? "<span class='ok'>✓</span>" : "<span class='error'>✗</span>");
          if (is_dir($path)) {
              echo " | Écriture: " . ($writable ? "<span class='ok'>✓</span>" : "<span class='error'>✗</span>");
          }
          echo " | Permissions: " . substr(sprintf('%o', fileperms($path)), -4);
      }
      echo "<br>";
  }
  echo "</div>";

  // 3. Autoload Laravel
  echo "<div class='section'>";
  echo "<h2>3. Chargement Laravel</h2>";

  $autoloadPath = __DIR__ . '/../vendor/autoload.php';
  if (!file_exists($autoloadPath)) {
      echo "<span class='error'>✗ vendor/autoload.php N'EXISTE PAS</span><br>";
      echo "Vous devez lancer: <code>composer install</code><br>";
  } else {
      echo "<span class='ok'>✓ vendor/autoload.php existe</span><br>";

      try {
          require $autoloadPath;
          echo "<span class='ok'>✓ Autoload chargé</span><br>";

          $appPath = __DIR__ . '/../bootstrap/app.php';
          if (file_exists($appPath)) {
              echo "<span class='ok'>✓ bootstrap/app.php existe</span><br>";

              try {
                  $app = require_once $appPath;
                  echo "<span class='ok'>✓ Application Laravel créée</span><br>";
                  echo "Version Laravel: <strong>" . app()->version() . "</strong><br>";

                  // Vérifier .env
                  $envPath = __DIR__ . '/../.env';
                  if (file_exists($envPath)) {
                      echo "<span class='ok'>✓ Fichier .env existe</span><br>";

                      // Vérifier APP_KEY
                      if (empty(env('APP_KEY'))) {
                          echo "<span class='error'>✗ APP_KEY non définie dans .env</span><br>";
                          echo "<strong>ACTION: Lancez php artisan key:generate</strong><br>";
                      } else {
                          echo "<span class='ok'>✓ APP_KEY définie</span><br>";
                      }

                      // Vérifier APP_DEBUG
                      echo "APP_DEBUG: <strong>" . (env('APP_DEBUG') ? 'true' : 'false') . "</strong><br>";
                      echo "APP_ENV: <strong>" . env('APP_ENV', 'non défini') . "</strong><br>";
                  } else {
                      echo "<span class='error'>✗ Fichier .env N'EXISTE PAS</span><br>";
                  }

              } catch (Exception $e) {
                  echo "<span class='error'>✗ ERREUR lors de la création de l'app:</span><br>";
                  echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
                  echo "<strong>Trace:</strong>";
                  echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
              }
          } else {
              echo "<span class='error'>✗ bootstrap/app.php N'EXISTE PAS</span><br>";
          }

      } catch (Exception $e) {
          echo "<span class='error'>✗ ERREUR lors du chargement:</span><br>";
          echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
      }
  }
  echo "</div>";

  // 4. Extensions PHP
  echo "<div class='section'>";
  echo "<h2>4. Extensions PHP</h2>";
  echo "Version PHP: <strong>" . phpversion() . "</strong><br><br>";

  $required = [
      'pdo_mysql', 'mbstring', 'openssl', 'tokenizer',
      'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'zip'
  ];

  foreach ($required as $ext) {
      $loaded = extension_loaded($ext);
      echo "$ext: " . ($loaded ? "<span class='ok'>✓</span>" : "<span class='error'>✗</span>") . "<br>";
  }
  echo "</div>";

  // 5. Test connexion DB
  echo "<div class='section'>";
  echo "<h2>5. Base de données</h2>";

  if (class_exists('PDO')) {
      echo "<span class='ok'>✓ PDO disponible</span><br>";

      $host = env('DB_HOST', 'non défini');
      $database = env('DB_DATABASE', 'non défini');
      $username = env('DB_USERNAME', 'non défini');

      echo "Host: <strong>$host</strong><br>";
      echo "Database: <strong>$database</strong><br>";
      echo "Username: <strong>$username</strong><br>";

      if ($host !== 'non défini' && $database !== 'non défini') {
          try {
              $pdo = new PDO(
                  "mysql:host=$host;dbname=$database",
                  $username,
                  env('DB_PASSWORD')
              );
              echo "<span class='ok'>✓ Connexion DB réussie</span><br>";
              echo "Version MySQL: <strong>" . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</strong><br>";
          } catch (Exception $e) {
              echo "<span class='error'>✗ Erreur connexion DB:</span><br>";
              echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
          }
      }
  } else {
      echo "<span class='error'>✗ PDO non disponible</span><br>";
  }
  echo "</div>";

  // 6. Dernières erreurs dans les logs
  echo "<div class='section'>";
  echo "<h2>6. Dernières erreurs Laravel</h2>";

  $logPath = __DIR__ . '/../storage/logs/laravel.log';
  if (file_exists($logPath) && is_readable($logPath)) {
      $lines = file($logPath);
      $lastLines = array_slice($lines, -50); // 50 dernières lignes
      echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
  } else {
      echo "Aucun log trouvé ou non accessible.<br>";
  }
  echo "</div>";

  echo "<br><p style='color: red; font-size: 18px;'><strong>⚠️ SUPPRIMEZ ce fichier debug.php immédiatement après 
  consultation !</strong></p>";
  echo "</body></html>";
  ?>