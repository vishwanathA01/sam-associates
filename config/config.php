<?php
declare(strict_types=1);
session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'sam_associates';
const DB_USER = 'root';
const DB_PASS = '';

function base_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $projectRoot = realpath(__DIR__ . '/..');
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $base = '';
        if ($docRoot && $projectRoot && str_starts_with(strtolower($projectRoot), strtolower($docRoot))) {
            $relative = str_replace('\\','/', substr($projectRoot, strlen($docRoot)));
            $base = rtrim($relative, '/');
        } elseif (!empty($_SERVER['SCRIPT_NAME'])) {
            $scriptDir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME']));
            $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
        }
    }
    return $base . '/' . ltrim($path, '/');
}

function db(bool $serverOnly=false): PDO {
    static $connections = [];
    $connectionKey = $serverOnly ? 'server' : 'database';
    if (isset($connections[$connectionKey])) return $connections[$connectionKey];
    $dsn = $serverOnly ? "mysql:host=".DB_HOST.";charset=utf8mb4" : "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
    $connections[$connectionKey] = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $connections[$connectionKey];
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid security token.');
    }
}
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function flash(?string $set=null): ?string {
    if ($set !== null) { $_SESSION['flash']=$set; return null; }
    $x=$_SESSION['flash']??null; unset($_SESSION['flash']); return $x;
}
