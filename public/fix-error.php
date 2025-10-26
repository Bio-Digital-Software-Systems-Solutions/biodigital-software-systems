<?php
  echo "<h1>🔧 Réparation de la configuration</h1>";

  // Vérifier et corriger les fichiers de config courants
  $fixes = [];

  // 1. config/cache.php
  $cacheConfig = __DIR__.'/../config/cache.php';
  if (file_exists($cacheConfig)) {
      $content = file_get_contents($cacheConfig);

      // Chercher les erreurs courantes
      if (strpos($content, "'default' => 'env'") !== false) {
          $content = str_replace(
              "'default' => 'env'",
              "'default' => env('CACHE_DRIVER', 'file')",
              $content
          );
          file_put_contents($cacheConfig, $content);
          $fixes[] = "✓ Corrigé : config/cache.php";
      }
  }

  // 2. config/session.php
  $sessionConfig = __DIR__.'/../config/session.php';
  if (file_exists($sessionConfig)) {
      $content = file_get_contents($sessionConfig);

      if (strpos($content, "'driver' => 'env'") !== false) {
          $content = str_replace(
              "'driver' => 'env'",
              "'driver' => env('SESSION_DRIVER', 'file')",
              $content
          );
          file_put_contents($sessionConfig, $content);
          $fixes[] = "✓ Corrigé : config/session.php";
      }
  }

  // 3. config/queue.php
  $queueConfig = __DIR__.'/../config/queue.php';
  if (file_exists($queueConfig)) {
      $content = file_get_contents($queueConfig);

      if (strpos($content, "'default' => 'env'") !== false) {
          $content = str_replace(
              "'default' => 'env'",
              "'default' => env('QUEUE_CONNECTION', 'sync')",
              $content
          );
          file_put_contents($queueConfig, $content);
          $fixes[] = "✓ Corrigé : config/queue.php";
      }
  }

  if (count($fixes) > 0) {
      echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
      echo "<h2>Corrections appliquées :</h2><ul>";
      foreach ($fixes as $fix) {
          echo "<li>$fix</li>";
      }
      echo "</ul></div>";

      // Clear cache
      require __DIR__.'/../vendor/autoload.php';
      $app = require_once __DIR__.'/../bootstrap/app.php';
      $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

      $kernel->call('config:clear');
      echo "<p>✓ Cache nettoyé</p>";

      echo "<p style='color: green; font-size: 18px;'><strong>✅ Corrections terminées ! Essayez maintenant : <a 
  href='/'>Page d'accueil</a></strong></p>";
  } else {
      echo "<p>Aucune correction automatique trouvée. Consultez find-error.php pour plus de détails.</p>";
  }

  echo "<br><p style='color: red;'><strong>⚠️ SUPPRIMEZ ce fichier maintenant !</strong></p>";
  ?>
