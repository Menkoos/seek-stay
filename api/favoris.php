<?php
require __DIR__ . '/../session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'non_connecte']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'methode_invalide']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$annonceId = trim($input['annonce_id'] ?? '');
$uid = $_SESSION['user_id'];

if (empty($annonceId)) {
    echo json_encode(['ok' => false, 'error' => 'id_manquant']); exit;
}

try {
    // Check if already favori
    $s = $pdo->prepare("SELECT id_favoris FROM favoris WHERE utilisateur_id = ? AND annonce_id = ?");
    $s->execute([$uid, $annonceId]);
    $existing = $s->fetchColumn();

    if ($existing) {
        // Retirer
        $pdo->prepare("DELETE FROM favoris WHERE id_favoris = ?")->execute([$existing]);
        echo json_encode(['ok' => true, 'action' => 'removed', 'favori' => false]);
    } else {
        // Ajouter
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $pdo->prepare("INSERT INTO favoris (id_favoris, utilisateur_id, annonce_id) VALUES (?, ?, ?)")
            ->execute([$uuid, $uid, $annonceId]);
        echo json_encode(['ok' => true, 'action' => 'added', 'favori' => true]);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'erreur_db', 'msg' => $e->getMessage()]);
}
