<?php
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function redirect(string $page = 'dashboard'): never {
    header('Location: ' . BASE_URL . '/index.php?page=' . urlencode($page));
    exit;
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function logged_in(): bool { return current_user() !== null; }
function require_login(): void {
    if (!logged_in()) {
        set_flash('error', 'Silakan masuk terlebih dahulu.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}
function require_role(array $roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        set_flash('error', 'Akses tidak diizinkan untuk akun ini.');
        redirect('dashboard');
    }
}
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type'=>$type, 'message'=>$message];
}
function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}
function post(string $key, string $default = ''): string {
    return trim((string)($_POST[$key] ?? $default));
}
function role_label(string $role): string {
    return ['admin'=>'Administrator','guru'=>'Guru','siswa'=>'Siswa','orangtua'=>'Orang Tua'][$role] ?? ucfirst($role);
}
function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $part) $out .= strtoupper(substr($part, 0, 1));
    return $out ?: 'R';
}
