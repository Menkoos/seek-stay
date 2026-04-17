<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$conditions = [];
$params     = [];

if (!empty($_GET['search'])) {
    $conditions[] = "(LOWER(ville) LIKE LOWER(:search) OR LOWER(description) LIKE LOWER(:search) OR LOWER(adresse) LIKE LOWER(:search))";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

if (!empty($_GET['ville'])) {
    $conditions[] = "LOWER(ville) LIKE LOWER(:ville)";
    $params[':ville'] = '%' . $_GET['ville'] . '%';
}

if (!empty($_GET['prix_min']) && is_numeric($_GET['prix_min'])) {
    $conditions[] = "prix >= :prix_min";
    $params[':prix_min'] = (float)$_GET['prix_min'];
}

if (!empty($_GET['prix_max']) && is_numeric($_GET['prix_max'])) {
    $conditions[] = "prix <= :prix_max";
    $params[':prix_max'] = (float)$_GET['prix_max'];
}

if (!empty($_GET['type_offre'])) {
    $conditions[] = "type_offre = :type_offre";
    $params[':type_offre'] = $_GET['type_offre'];
}

if (!empty($_GET['type_immeuble'])) {
    $conditions[] = "type_immeuble = :type_immeuble";
    $params[':type_immeuble'] = $_GET['type_immeuble'];
}

if (!empty($_GET['surface_min']) && is_numeric($_GET['surface_min'])) {
    $conditions[] = "superficie >= :surface_min";
    $params[':surface_min'] = (float)$_GET['surface_min'];
}

$sql = "SELECT * FROM annonces";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY nb_vues DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'annonces' => $annonces, 'total' => count($annonces)]);
