<?php
// suggest.php — Saves a user-suggested place as inactive (pending approval)
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Méthode non autorisée.'));
    exit;
}

$city = trim(isset($_POST['city']) ? $_POST['city'] : '');
$name = trim(isset($_POST['name']) ? $_POST['name'] : '');

if (mb_strlen($city) < 2 || mb_strlen($city) > 100 ||
    mb_strlen($name) < 2 || mb_strlen($name) > 150) {
    echo json_encode(array('success' => false, 'message' => 'Ville et lieu requis (2–150 caractères).'));
    exit;
}

if (!preg_match('/^[\p{L}\s\'\-\.]+$/u', $city) ||
    !preg_match('/^[\p{L}\s\'\-\.0-9]+$/u', $name)) {
    echo json_encode(array('success' => false, 'message' => 'Caractères non autorisés.'));
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO places (city, name, active) VALUES (?, ?, 0)'
    );
    $stmt->execute(array($city, $name));

    if ($stmt->rowCount() > 0) {
        echo json_encode(array('success' => true, 'message' => 'Suggestion envoyée. Elle sera visible après validation.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Ce lieu existe déjà dans notre base.'));
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'message' => 'Erreur serveur.'));
}
