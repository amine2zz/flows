<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=2');

require_once 'config.php';

$slot = getCurrentSlot();

try {
    $pdo = getDB();

    // Counts for the current 30-min results window (both 15-min vote slots)
    $stmt = $pdo->prepare(
        'SELECT place_id, vote, COUNT(*) AS cnt FROM votes
         WHERE slot_date = ? AND (slot_number = ? OR slot_number = ?)
         GROUP BY place_id, vote'
    );
    $cs1 = $slot['number'] * 2;
    $cs2 = $slot['number'] * 2 + 1;
    $stmt->execute(array($slot['date'], $cs1, $cs2));

    $counts = array();
    foreach ($stmt->fetchAll() as $row) {
        $pid = (int)$row['place_id'];
        if (!isset($counts[$pid])) $counts[$pid] = array('w' => 0, 'n' => 0);
        if ($row['vote'] === 'working')     $counts[$pid]['w'] += (int)$row['cnt'];
        if ($row['vote'] === 'not_working') $counts[$pid]['n'] += (int)$row['cnt'];
    }

    $out = array();
    foreach ($counts as $pid => $c) {
        $total = $c['w'] + $c['n'];
        $out[$pid] = array(
            'w'   => $c['w'],
            'n'   => $c['n'],
            'tot' => $total,
            'pw'  => $total > 0 ? round($c['w'] / $total * 100) : 0,
            'pn'  => $total > 0 ? round($c['n'] / $total * 100) : 0,
        );
    }

    echo json_encode(array(
        'counts'      => $out,
        'secs_left'   => $slot['secs_left'],
        'server_time' => $slot['server_time'],
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'Erreur serveur.'));
}
