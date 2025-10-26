
  <?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Vraie erreur</title>
  <style>
  body { font-family: Arial; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
  .error { background: #f44336; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
  pre { background: #2d2d2d; padding: 15px; overflow-x: auto; white-space: pre-wrap; }
  h1 { color: #4CAF50; }
  </style></head><body>
  <h1>🔍 Recherche de la VRAIE erreur</h1>";

  try {
      define('LARAVEL_START', microtime(true));

      putenv('APP_DEBUG=true');
      putenv('APP_ENV=local');

      require __DIR__.'/../vendor/autoload.php';

      echo "<p>✓ Autoload OK</p>";

      $app = require_once __DIR__.'/../bootstrap/app.php';

      echo "<p>✓ App créée</p>";

      // MAINTENANT on essaie de booter complètement
      echo "<p>Tentative de boot complet de Laravel...</p>";

      $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

      // Simuler une requête réelle
      $request = Illuminate\Http\Request::create('/', 'GET');
      $response = $kernel->handle($request);

      echo "<p style='color: #4CAF50; font-size: 20px;'><strong>✅ SUCCÈS ! L'application fonctionne !</strong></p>";
      echo "<p>Code HTTP : " . $response->getStatusCode() . "</p>";

  } catch (Exception $e) {
      echo "<div class='error'>";
      echo "<h2>❌ VRAIE ERREUR TROUVÉE</h2>";
      echo "<p><strong>Type :</strong> " . get_class($e) . "</p>";
      echo "<p><strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
      echo "<p><strong>Fichier :</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
      echo "</div>";

      echo "<h2>Stack Trace complète :</h2>";
      echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";

      if ($previous = $e->getPrevious()) {
          echo "<h2>⚠️ Cause d'origine :</h2>";
          echo "<div class='error'>";
          echo "<p><strong>Message :</strong> " . htmlspecialchars($previous->getMessage()) . "</p>";
          echo "</div>";
          echo "<pre>" . htmlspecialchars($previous->getTraceAsString()) . "</pre>";
      }
  }

  echo "<br><p style='color: #ff9800; font-size: 18px;'><strong>⚠️ SUPPRIMEZ ce fichier !</strong></p>
  </body></html>";
  ?>