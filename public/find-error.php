<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Trouver l'erreur</title>
  <style>
  body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
  .error { background: #f44336; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
  .success { background: #4CAF50; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
  pre { background: #2d2d2d; padding: 15px; overflow-x: auto; }
  h2 { color: #4CAF50; }
  </style></head><body>
  <h1>🔍 Recherche de l'erreur</h1>";

  define('LARAVEL_START', microtime(true));
  require __DIR__.'/../vendor/autoload.php';

  echo "<h2>1. Chargement de Laravel avec trace complète</h2>";

  try {
      // Activer le mode debug
      putenv('APP_DEBUG=true');

      $app = require_once __DIR__.'/../bootstrap/app.php';
      echo "<div class='success'>✓ Bootstrap chargé</div>";

      // Essayer de charger la config
      echo "<h2>2. Chargement de la configuration</h2>";

      $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

      // Tester chaque fichier de config
      $configPath = __DIR__.'/../config/';
      $configFiles = scandir($configPath);

      foreach ($configFiles as $file) {
          if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
              $configName = pathinfo($file, PATHINFO_FILENAME);
              echo "Test $file ... ";
              try {
                  $config = config($configName);
                  echo "<span style='color: #4CAF50;'>✓ OK</span><br>";
              } catch (Exception $e) {
                  echo "<span style='color: #f44336;'>✗ ERREUR TROUVÉE !</span><br>";
                  echo "<div class='error'>";
                  echo "<strong>Fichier problématique : config/$file</strong><br>";
                  echo "<strong>Erreur :</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
                  echo "</div>";
                  echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
              }
          }
      }

  } catch (Exception $e) {
      echo "<div class='error'>";
      echo "<strong>ERREUR PRINCIPALE TROUVÉE :</strong><br><br>";
      echo "<strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
      echo "<strong>Fichier :</strong> " . $e->getFile() . "<br>";
      echo "<strong>Ligne :</strong> " . $e->getLine() . "<br>";
      echo "</div>";

      echo "<h2>Stack Trace :</h2>";
      echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
  }

  echo "<br><p style='color: #ff9800; font-size: 18px;'><strong>⚠️ SUPPRIMEZ ce fichier après !</strong></p>
  </body></html>";
  ?>