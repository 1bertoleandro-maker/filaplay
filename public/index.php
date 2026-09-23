<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Quando o app é acessado sem "/public" na URL (ver .htaccess na raiz do
// projeto, que reenvia tudo pra cá), o SCRIPT_NAME real ainda aponta pra
// ".../public/index.php" e o Symfony calcula a rota errada (acha que a URL
// visível inclui "/public"). Corrigimos aqui só quando a URL pedida
// não veio explicitamente com "/public/".
if (
    isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])
    && ! str_contains($_SERVER['REQUEST_URI'], '/public/')
    && str_ends_with($_SERVER['SCRIPT_NAME'], '/public/index.php')
) {
    $_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/public/index.php')).'/index.php';
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
