<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

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
                $_SESSION['user_id']  = $u['id_utilisateur'];
                $_SESSION['email']    = $u['email'];
                $_SESSION['nom']      = $u['nom'];
                $_SESSION['lastname'] = $u['lastname'];

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
