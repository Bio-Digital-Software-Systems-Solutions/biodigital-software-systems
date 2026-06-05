  <?php
  error_reporting(E_ALL);
ini_set('display_errors', 1);

$appName = 'Laravel';
$envFile = __DIR__.'/../.env';
if (file_exists($envFile)) {
    $parsed = parse_ini_file($envFile);
    if (is_array($parsed) && ! empty($parsed['APP_NAME'])) {
        $appName = $parsed['APP_NAME'];
    }
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnostic Complet</title>
  <style>
  body { font-family: Arial; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
  .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
   }
  .success { color: #28a745; font-weight: bold; }
  .error { color: #dc3545; font-weight: bold; }
  pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; overflow-x: auto; }
  h1 { color: #333; }
  h2 { color: #555; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
  </style></head><body>
  <h1>🔍 Diagnostic Complet ".htmlspecialchars($appName).'</h1>';

// 1. Environnement
echo "<div class='section'><h2>1. Environnement</h2>";
echo 'PHP Version : <strong>'.phpversion().'</strong><br>';
echo 'Server : '.$_SERVER['SERVER_SOFTWARE'].'<br>';
echo 'Document Root : '.$_SERVER['DOCUMENT_ROOT'].'<br>';
echo '</div>';

// 2. Laravel
echo "<div class='section'><h2>2. Laravel</h2>";
try {
    define('LARAVEL_START', microtime(true));
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

    echo "<span class='success'>✓ Laravel chargé</span><br>";
    echo 'Version : <strong>'.app()->version().'</strong><br>';
    echo 'Environment : <strong>'.app()->environment().'</strong><br>';
    echo 'Debug Mode : <strong>'.(config('app.debug') ? 'ON' : 'OFF').'</strong><br>';
} catch (Exception $e) {
    echo "<span class='error'>✗ Erreur : ".$e->getMessage().'</span>';
}
echo '</div>';

// 3. Base de données
echo "<div class='section'><h2>3. Base de données</h2>";
try {
    $pdo = DB::connection()->getPdo();
    echo "<span class='success'>✓ Connexion DB OK</span><br>";
    echo 'Database : <strong>'.DB::connection()->getDatabaseName().'</strong><br>';

    $tables = DB::select('SHOW TABLES');
    echo 'Nombre de tables : <strong>'.count($tables).'</strong><br>';

    // Vérifier telescope_entries
    $hasTelescopeTables = false;
    foreach ($tables as $table) {
        $tableName = array_values((array) $table)[0];
        if (strpos($tableName, 'telescope_') === 0) {
            $hasTelescopeTables = true;
            break;
        }
    }
    echo 'Tables Telescope : '.($hasTelescopeTables ? "<span class='success'>Présentes</span>" : 'Absentes').
  '<br>';

} catch (Exception $e) {
    echo "<span class='error'>✗ Erreur DB : ".$e->getMessage().'</span>';
}
echo '</div>';

// 4. Configuration
echo "<div class='section'><h2>4. Configuration (.env)</h2>";
$envVars = [
    'APP_ENV' => env('APP_ENV'),
    'APP_DEBUG' => env('APP_DEBUG') ? 'true' : 'false',
    'APP_URL' => env('APP_URL'),
    'TELESCOPE_ENABLED' => env('TELESCOPE_ENABLED', 'non défini'),
    'DB_CONNECTION' => env('DB_CONNECTION'),
    'CACHE_DRIVER' => env('CACHE_DRIVER'),
    'SESSION_DRIVER' => env('SESSION_DRIVER'),
];

foreach ($envVars as $key => $value) {
    echo "$key = <strong>$value</strong><br>";
}
echo '</div>';

// 5. Tenter une requête réelle
echo "<div class='section'><h2>5. Test requête HTTP</h2>";
try {
    echo "Tentative de traitement d'une requête...<br>";

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $kernel->handle($request);

    echo "<span class='success'>✓ Requête traitée</span><br>";
    echo 'Code HTTP : <strong>'.$response->getStatusCode().'</strong><br>';

    if ($response->getStatusCode() == 200) {
        echo "<span class='success'>✓✓ L'APPLICATION FONCTIONNE !</span><br>";
    }

} catch (Exception $e) {
    echo "<span class='error'>✗ ERREUR DÉTECTÉE :</span><br>";
    echo '<strong>Type :</strong> '.get_class($e).'<br>';
    echo '<strong>Message :</strong> '.htmlspecialchars($e->getMessage()).'<br>';
    echo '<strong>Fichier :</strong> '.$e->getFile().':'.$e->getLine().'<br>';
    echo '<pre>'.htmlspecialchars($e->getTraceAsString()).'</pre>';

    if ($previous = $e->getPrevious()) {
        echo '<h3>Cause originale :</h3>';
        echo '<strong>Message :</strong> '.htmlspecialchars($previous->getMessage()).'<br>';
        echo '<pre>'.htmlspecialchars($previous->getTraceAsString()).'</pre>';
    }
}
echo '</div>';

// 6. Derniers logs
echo "<div class='section'><h2>6. Derniers logs (50 lignes)</h2>";
$logPath = __DIR__.'/../storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    $lastLines = array_slice($lines, -50);
    echo '<pre>'.htmlspecialchars(implode('', $lastLines)).'</pre>';
} else {
    echo 'Aucun log trouvé.';
}
echo '</div>';

echo "<p style='color: red; font-size: 20px; text-align: center; margin-top: 30px;'>
  <strong>⚠️ SUPPRIMEZ TOUS LES FICHIERS DE TEST MAINTENANT !</strong></p>
  </body></html>";
?>