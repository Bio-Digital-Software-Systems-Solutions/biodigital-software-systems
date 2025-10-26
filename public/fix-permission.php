  <?php
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Correction Permissions</title>
  <style>
  body { font-family: Arial; padding: 20px; background: #f5f5f5; }
  .container { background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto; }
  .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
  .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
  .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
  h1 { color: #333; }
  </style></head><body><div class='container'>
  <h1>🔧 Correction des permissions</h1>";

  $basePath = __DIR__ . '/..';

  // Dossiers à corriger
  $directories = [
      'storage',
      'storage/app',
      'storage/framework',
      'storage/framework/cache',
      'storage/framework/sessions',
      'storage/framework/views',
      'storage/logs',
      'bootstrap/cache',
  ];

  echo "<h2>1. Vérification des dossiers</h2>";

  $errors = [];
  $fixed = [];

  foreach ($directories as $dir) {
      $path = $basePath . '/' . $dir;

      echo "<p><strong>$dir</strong>: ";

      if (!file_exists($path)) {
          // Créer le dossier s'il n'existe pas
          if (@mkdir($path, 0775, true)) {
              echo "<span style='color: #28a745;'>✓ Créé</span>";
              $fixed[] = $dir;
          } else {
              echo "<span style='color: #dc3545;'>✗ Impossible de créer</span>";
              $errors[] = $dir;
          }
      } else {
          // Vérifier les permissions
          $perms = fileperms($path);
          $permsOctal = substr(sprintf('%o', $perms), -4);

          echo "Permissions actuelles: $permsOctal";

          if (!is_writable($path)) {
              // Essayer de corriger
              if (@chmod($path, 0775)) {
                  echo " → <span style='color: #28a745;'>✓ Corrigé (775)</span>";
                  $fixed[] = $dir;
              } else {
                  echo " → <span style='color: #dc3545;'>✗ Non modifiable (utilisez SSH)</span>";
                  $errors[] = $dir;
              }
          } else {
              echo " → <span style='color: #28a745;'>✓ OK</span>";
          }
      }
      echo "</p>";
  }

  echo "<h2>2. Résumé</h2>";

  if (count($fixed) > 0) {
      echo "<div class='success'>";
      echo "<strong>✓ Corrections appliquées (" . count($fixed) . ") :</strong><ul>";
      foreach ($fixed as $f) {
          echo "<li>$f</li>";
      }
      echo "</ul></div>";
  }

  if (count($errors) > 0) {
      echo "<div class='error'>";
      echo "<strong>✗ Erreurs (" . count($errors) . ") - Utilisez SSH :</strong><ul>";
      foreach ($errors as $e) {
          echo "<li>$e</li>";
      }
      echo "</ul>";
      echo "<pre style='background: #2d2d2d; color: #f8f8f2; padding: 15px;'>cd 
  /var/www/vhosts/hosting217535.a2e58.netcup.net/icc-munich.de/httpdocs/icc-munich\n";
      echo "chmod -R 775 storage bootstrap/cache</pre>";
      echo "</div>";
  }

  // Test d'écriture
  echo "<h2>3. Test d'écriture</h2>";

  $testFile = $basePath . '/storage/logs/test-write.txt';
  $canWrite = @file_put_contents($testFile, 'Test ' . date('Y-m-d H:i:s'));

  if ($canWrite !== false) {
      echo "<div class='success'>✓ Écriture dans storage/logs/ : <strong>FONCTIONNE</strong></div>";
      @unlink($testFile);
  } else {
      echo "<div class='error'>✗ Écriture dans storage/logs/ : <strong>ÉCHOUE</strong><br>";
      echo "Vous DEVEZ utiliser SSH pour corriger les permissions.</div>";
  }

  // Nettoyer le cache
  echo "<h2>4. Nettoyage du cache</h2>";

  try {
      require $basePath . '/vendor/autoload.php';
      $app = require_once $basePath . '/bootstrap/app.php';
      $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

      $kernel->call('config:clear');
      echo "<p>✓ Config cleared</p>";

      $kernel->call('cache:clear');
      echo "<p>✓ Cache cleared</p>";

      $kernel->call('view:clear');
      echo "<p>✓ Views cleared</p>";

      echo "<div class='success'>✓ Cache nettoyé avec succès</div>";

  } catch (Exception $e) {
      echo "<div class='warning'>⚠ Impossible de nettoyer le cache automatiquement : " . $e->getMessage() . "</div>";
  }

  if (count($errors) === 0) {
      echo "<div class='success' style='margin-top: 30px;'>";
      echo "<h2>✅ Terminé !</h2>";
      echo "<p>Essayez maintenant : <a href='/' style='color: #007bff; font-size: 18px;'>Page d'accueil</a></p>";
      echo "</div>";
  }

  echo "<br><p style='color: red; font-size: 18px;'><strong>⚠️ SUPPRIMEZ tous les fichiers de test maintenant 
  !</strong></p>";
  echo "<ul><li>fix-permissions.php</li><li>find-real-error.php</li><li>find-error.php</li><li>check-all.php</li><li>clea
  r-cache.php</li></ul>";
  echo "</div></body></html>";
  ?>