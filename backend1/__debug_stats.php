<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
try {
    $c = new App\Controllers\UserManagementController();
    $req = new Slim\Psr7\ServerRequest('GET', '/api/user-management/stats');
    $res = new Slim\Psr7\Response();
    $resp = $c->getUserStats($req, $res);
    echo $resp->getStatusCode(), "\n";
    echo (string) $resp->getBody(), "\n";
} catch (Throwable $e) {
    echo 'ERR: ', $e->getMessage(), "\n";
    echo $e->getTraceAsString(), "\n";
}
