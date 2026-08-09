<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Methode non autorisee.'));
    exit;
}

$vote    = isset($_POST['vote'])     ? $_POST['vote']          : '';
$placeId = isset($_POST['place_id']) ? (int)$_POST['place_id'] : 0;

if (!in_array($vote, array('working', 'not_working'), true) || $placeId <= 0) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => 'Donnees invalides.'));
    exit;
}

$ip   = getClientIP();
$slot = getCurrentSlot();

try {
    $pdo = getDB();

    $chkPlace = $pdo->prepare('SELECT id FROM places WHERE id = ? AND active = 1 LIMIT 1');
    $chkPlace->execute(array($placeId));
    if (!$chkPlace->fetch()) {
        echo json_encode(array('success' => false, 'message' => 'Lieu introuvable.'));
        exit;
    }

    // Cooldown: one vote per IP per 15-min window
    $chkDup = $pdo->prepare(
        'SELECT place_id FROM votes
         WHERE ip_address = ? AND slot_date = ? AND slot_number = ?
         LIMIT 1'
    );
    $chkDup->execute(array($ip, $slot['date'], $slot['cooldown_slot']));
    if ($chkDup->fetch()) {
        echo json_encode(array(
            'success'   => false,
            'duplicate' => true,
            'message'   => 'Vous avez deja vote. Attendez 15min pour voter a nouveau.',
        ));
        exit;
    }

    // Store cooldown_slot as slot_number in DB
    $insert = $pdo->prepare(
        'INSERT INTO votes (place_id, ip_address, vote, slot_date, slot_number)
         VALUES (?, ?, ?, ?, ?)'
    );
    $insert->execute(array($placeId, $ip, $vote, $slot['date'], $slot['cooldown_slot']));

    echo json_encode(array('success' => true, 'message' => 'Vote enregistre.'));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'message' => 'Erreur serveur.'));
}
