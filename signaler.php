<?php
require 'session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'non_connecte']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'methode_invalide']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$cibleType   = in_array($input['cible_type'] ?? '', ['annonce','utilisateur']) ? $input['cible_type'] : null;
$cibleId     = trim($input['cible_id'] ?? '');
$raison      = trim($input['raison'] ?? '');
$commentaire = trim($input['commentaire'] ?? '');

$raisonsValides = ['spam','contenu_inapproprie','arnaque','usurpation','harcelement','fausse_annonce','autre'];
if (!$cibleType || empty($cibleId) || !in_array($raison, $raisonsValides)) {
    echo json_encode(['ok' => false, 'error' => 'donnees_invalides']); exit;
}
if ($cibleId === $_SESSION['user_id']) {
    echo json_encode(['ok' => false, 'error' => 'auto_signalement']); exit;
}

// Anti-doublon : un même utilisateur ne peut pas signaler plusieurs fois la même cible
try {
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM signalements
        WHERE signaleur_id = ? AND cible_type = ? AND cible_id = ?
    ");
    $check->execute([$_SESSION['user_id'], $cibleType, $cibleId]);
    if ((int)$check->fetchColumn() > 0) {
        echo json_encode(['ok' => false, 'error' => 'deja_signale']); exit;
    }
} catch (PDOException $e) {}

$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

try {
    $pdo->prepare("
        INSERT INTO signalements (id_signalement, signaleur_id, cible_type, cible_id, raison, commentaire)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([
        $uuid,
        $_SESSION['user_id'],
        $cibleType,
        $cibleId,
        $raison,
        $commentaire !== '' ? $commentaire : null,
    ]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'erreur_db']);
}
