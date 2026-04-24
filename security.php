<?php
/**
 * Helpers de sécurité — à inclure après session.php.
 */

// ── CSRF ──────────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verify(?string $token): bool {
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_require(): void {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!csrf_verify($token)) {
        http_response_code(419);
        exit('Jeton de sécurité invalide. Rechargez la page et réessayez.');
    }
}

// ── Session fixation : régénérer l'ID lors du login/register ──────────────
function session_regenerate_safe(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// ── Rate limiting simple basé sur la session ──────────────────────────────
/**
 * Limite $maxTries tentatives dans une fenêtre de $windowSec secondes.
 * Retourne true si bloqué (quota dépassé), false sinon.
 */
function rate_limit_hit(string $key, int $maxTries = 5, int $windowSec = 300): bool {
    $now  = time();
    $data = $_SESSION['_rate'][$key] ?? ['count' => 0, 'first' => $now];
    if ($now - $data['first'] > $windowSec) {
        $data = ['count' => 0, 'first' => $now];
    }
    $data['count']++;
    $_SESSION['_rate'][$key] = $data;
    return $data['count'] > $maxTries;
}

function rate_limit_reset(string $key): void {
    unset($_SESSION['_rate'][$key]);
}

// ── Validation upload image stricte ───────────────────────────────────────
/**
 * Vérifie qu'un fichier uploadé est bien une image (MIME, extension,
 * contenu réel) et retourne ['ok' => bool, 'error' => ?string, 'ext' => ?string].
 */
function validate_image_upload(array $file, int $maxSizeMo = 5): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'upload_error'];
    }
    if ($file['size'] > $maxSizeMo * 1024 * 1024) {
        return ['ok' => false, 'error' => 'too_large'];
    }

    // MIME selon le contenu (pas le nom du fichier envoyé par le client)
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = @mime_content_type($file['tmp_name']);
    if (!isset($allowedMime[$mime])) {
        return ['ok' => false, 'error' => 'invalid_mime'];
    }

    // Vérifier que c'est vraiment une image décodable
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'error' => 'not_an_image'];
    }

    // Extension côté serveur (jamais ce que le client envoie)
    return ['ok' => true, 'ext' => $allowedMime[$mime]];
}
