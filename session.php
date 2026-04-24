<?php
// Session valide 8 heures + cookies durcis (HttpOnly, SameSite)
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.use_strict_mode',  1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly',  1);
session_set_cookie_params([
    'lifetime' => 28800,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

// Auto-login via cookie "Se souvenir de moi"
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['ss_remember'])) {
    $parts = explode(':', $_COOKIE['ss_remember'], 2);
    if (count($parts) === 2) {
        [$uid, $tok] = $parts;
        try {
            $s = $pdo->prepare(
                "SELECT * FROM utilisateur_ WHERE id_utilisateur = ? AND remember_expires > NOW()"
            );
            $s->execute([$uid]);
            $u = $s->fetch();

            if ($u && !empty($u['remember_token']) && hash_equals($u['remember_token'], hash('sha256', $tok))) {
                $role = $u['role_type'] ?? 'loueur';

                $_SESSION['user_id']   = $u['id_utilisateur'];
                $_SESSION['email']     = $u['email'];
                $_SESSION['nom']       = $u['nom'];
                $_SESSION['lastname']  = $u['lastname'];
                $_SESSION['role_type'] = $role;

                // Rotation du token
                $newTok = bin2hex(random_bytes(32));
                $exp    = date('Y-m-d H:i:s', strtotime('+30 days'));
                $pdo->prepare(
                    "UPDATE utilisateur_ SET remember_token = ?, remember_expires = ? WHERE id_utilisateur = ?"
                )->execute([hash('sha256', $newTok), $exp, $uid]);
                setcookie('ss_remember', $uid . ':' . $newTok, time() + 86400 * 30, '/', '', false, true);
            } else {
                setcookie('ss_remember', '', time() - 3600, '/');
            }
        } catch (PDOException $e) { /* silencieux */ }
    }
}

// Rafraîchir le rôle et le statut admin depuis la DB à chaque requête
if (isset($_SESSION['user_id'])) {
    try {
        $s = $pdo->prepare("SELECT role_type, is_admin FROM utilisateur_ WHERE id_utilisateur = ?");
        $s->execute([$_SESSION['user_id']]);
        $r = $s->fetch();
        if ($r) {
            $_SESSION['role_type'] = $r['role_type'] ?? 'loueur';
            $_SESSION['is_admin']  = (int)($r['is_admin'] ?? 0);
            setcookie('ss_role', $_SESSION['role_type'], time() + 86400 * 30, '/');
        }
    } catch (PDOException $e) {}
}
