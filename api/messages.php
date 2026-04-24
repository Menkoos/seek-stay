<?php
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$uid    = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

// ── Nombre total de messages non lus ──────────────────────────────────────
if ($action === 'unread') {
    $s = $pdo->prepare("SELECT COUNT(*) FROM messagerie WHERE recepteur_id = ? AND lu = 0");
    $s->execute([$uid]);
    echo json_encode(['count' => (int)$s->fetchColumn()]);
    exit;
}

// ── Recherche d'utilisateurs (nouvelle conversation) ─────────────────────
if ($action === 'search') {
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $s = $pdo->prepare("
        SELECT id_utilisateur, nom, lastname, photo_profil
        FROM utilisateur_
        WHERE id_utilisateur != ?
          AND (nom LIKE ? OR lastname LIKE ? OR email LIKE ?)
        LIMIT 10
    ");
    $s->execute([$uid, $q, $q, $q]);
    echo json_encode($s->fetchAll());
    exit;
}

// ── Liste des conversations ───────────────────────────────────────────────
if ($action === 'conversations') {
    // Contacts distincts avec qui l'utilisateur a échangé
    $s = $pdo->prepare("
        SELECT DISTINCT IF(emetteur_id = ?, recepteur_id, emetteur_id) AS contact_id
        FROM messagerie
        WHERE emetteur_id = ? OR recepteur_id = ?
    ");
    $s->execute([$uid, $uid, $uid]);
    $contactIds = $s->fetchAll(PDO::FETCH_COLUMN);

    $conversations = [];
    foreach ($contactIds as $cid) {
        $s = $pdo->prepare("
            SELECT m.contenu, m.date_emission, m.emetteur_id,
                   u.nom, u.lastname, u.photo_profil,
                   (SELECT COUNT(*) FROM messagerie
                    WHERE emetteur_id = ? AND recepteur_id = ? AND lu = 0) AS non_lus
            FROM messagerie m
            JOIN utilisateur_ u ON u.id_utilisateur = ?
            WHERE (m.emetteur_id = ? AND m.recepteur_id = ?)
               OR (m.emetteur_id = ? AND m.recepteur_id = ?)
            ORDER BY m.date_emission DESC
            LIMIT 1
        ");
        $s->execute([$cid, $uid, $cid, $uid, $cid, $cid, $uid]);
        $row = $s->fetch();
        if ($row) {
            $row['contact_id'] = $cid;
            $conversations[]   = $row;
        }
    }

    usort($conversations, fn($a,$b) => strtotime($b['date_emission']) - strtotime($a['date_emission']));
    echo json_encode($conversations);
    exit;
}

// ── Thread d'une conversation ─────────────────────────────────────────────
if ($action === 'thread') {
    $cid = $_GET['contact_id'] ?? '';
    if (empty($cid)) { echo json_encode([]); exit; }

    // Marquer comme lus
    $pdo->prepare("UPDATE messagerie SET lu = 1 WHERE emetteur_id = ? AND recepteur_id = ? AND lu = 0")
        ->execute([$cid, $uid]);

    $s = $pdo->prepare("
        SELECT m.id, m.contenu, m.date_emission, m.emetteur_id, m.lu,
               u.nom, u.lastname, u.photo_profil
        FROM messagerie m
        JOIN utilisateur_ u ON u.id_utilisateur = m.emetteur_id
        WHERE (m.emetteur_id = ? AND m.recepteur_id = ?)
           OR (m.emetteur_id = ? AND m.recepteur_id = ?)
        ORDER BY m.date_emission ASC
    ");
    $s->execute([$uid, $cid, $cid, $uid]);
    echo json_encode($s->fetchAll());
    exit;
}

// ── Nouveaux messages (polling) ───────────────────────────────────────────
if ($action === 'poll') {
    $cid   = $_GET['contact_id'] ?? '';
    $since = $_GET['since']      ?? '1970-01-01 00:00:00';
    if (empty($cid)) { echo json_encode([]); exit; }

    $pdo->prepare("UPDATE messagerie SET lu = 1 WHERE emetteur_id = ? AND recepteur_id = ? AND lu = 0")
        ->execute([$cid, $uid]);

    $s = $pdo->prepare("
        SELECT m.id, m.contenu, m.date_emission, m.emetteur_id, m.lu,
               u.nom, u.lastname, u.photo_profil
        FROM messagerie m
        JOIN utilisateur_ u ON u.id_utilisateur = m.emetteur_id
        WHERE ((m.emetteur_id = ? AND m.recepteur_id = ?)
            OR (m.emetteur_id = ? AND m.recepteur_id = ?))
          AND m.date_emission > ?
        ORDER BY m.date_emission ASC
    ");
    $s->execute([$uid, $cid, $cid, $uid, $since]);
    echo json_encode($s->fetchAll());
    exit;
}

// ── Envoyer un message ────────────────────────────────────────────────────
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $cid     = trim($body['contact_id'] ?? '');
    $contenu = trim($body['contenu']    ?? '');

    if (empty($cid) || empty($contenu)) {
        http_response_code(400);
        echo json_encode(['error' => 'Paramètres manquants']);
        exit;
    }
    if (mb_strlen($contenu) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message trop long (2000 caractères max)']);
        exit;
    }

    // Vérifier que le destinataire existe
    $check = $pdo->prepare("SELECT id_utilisateur FROM utilisateur_ WHERE id_utilisateur = ?");
    $check->execute([$cid]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Destinataire introuvable']);
        exit;
    }

    $id = uuid();
    $pdo->prepare("
        INSERT INTO messagerie (id, emetteur_id, recepteur_id, contenu, date_emission, lu)
        VALUES (?, ?, ?, ?, NOW(), 0)
    ")->execute([$id, $uid, $cid, $contenu]);

    $s = $pdo->prepare("
        SELECT m.id, m.contenu, m.date_emission, m.emetteur_id, m.lu,
               u.nom, u.lastname, u.photo_profil
        FROM messagerie m
        JOIN utilisateur_ u ON u.id_utilisateur = m.emetteur_id
        WHERE m.id = ?
    ");
    $s->execute([$id]);
    echo json_encode($s->fetch());
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Action inconnue']);
