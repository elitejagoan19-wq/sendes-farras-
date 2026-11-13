<?php
declare(strict_types=1);

define('APP_NAME', 'RYZEN');
define('APP_TAGLINE', 'Portal Informasi Pendidikan');
// Deteksi otomatis nama folder project agar CSS dan tautan tetap benar saat folder diganti nama.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$baseUrl = rtrim($scriptDir, '/');
if ($baseUrl === '.') { $baseUrl = ''; }
define('BASE_URL', $baseUrl);
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ryzen_school');
define('DB_USER', 'root');
define('DB_PASS', '');

date_default_timezone_set('Asia/Makassar');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
