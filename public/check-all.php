<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  function check($name, $condition, $error = '') {
      if ($condition) {
          echo "✓ $name<br>";
          return true;
      } else {
          echo "✗ $name" . ($error ? " - $error" : "") . "<br>";
          return false;
      }
  }

  echo "<h1>Vérifications complètes</h1>";
  echo "<h2>1. Fichiers Laravel</h2>";

  $files = [
      'vendor/autoload.php' => __DIR__.'/../vendor/autoload.php',
      'bootstrap/app.php' => __DIR__.'/../bootstrap/app.php',
      '.env' => __DIR__.'/../.env',
      'artisan' => __DIR__.'/../artisan',
  ];

  foreach ($files as $name => $path) {
      check($name, file_exists($path), "Chemin: $path");
  }

  echo "<h2>2. Permissions</h2>";

  $dirs = [
      'storage' => __DIR__.'/../storage',
      'bootstrap/cache' => __DIR__.'/../bootstrap/cache',
  ];

  foreach ($dirs as $name => $path) {
      if (file_exists($path)) {
          check("$name (lecture)", is_readable($path));
          check("$name (écriture)", is_writable($path), "chmod -R 775 $path");
      } else {
          echo "✗ $name n'existe pas<br>";
      }
  }

  echo "<h2>3. Configuration .env</h2>";

  if (file_exists(__DIR__.'/../.env')) {
      $env = file_get_contents(__DIR__.'/../.env');
      check('APP_KEY définie', strpos($env, 'APP_KEY=base64:') !== false, 'Lancez: php artisan key:generate');
      check('DB_DATABASE définie', strpos($env, 'DB_DATABASE=') !== false);
      check('DB_USERNAME définie', strpos($env, 'DB_USERNAME=') !== false);
  } else {
      echo "✗ Fichier .env manquant !<br>";
  }

  echo "<h2>4. Test chargement Laravel</h2>";

  try {
      require __DIR__.'/../vendor/autoload.php';
      echo "✓ Autoload OK<br>";

      $app = require_once __DIR__.'/../bootstrap/app.php';
      echo "✓ Bootstrap OK<br>";
      echo "Version Laravel: " . app()->version() . "<br>";

  } catch (Exception $e) {
      echo "✗ Erreur: " . $e->getMessage() . "<br>";
      echo "<pre>" . $e->getTraceAsString() . "</pre>";
  }

  echo "<br><p style='color:red;'><strong>⚠️ SUPPRIMEZ ce fichier !</strong></p>";
  ?>
