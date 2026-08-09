<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once 'config.php';

$placeId = isset($_GET['place_id']) ? (int)$_GET['place_id'] : 0;
if ($placeId <= 0) {
    echo json_encode(array('error' => 'place_id requis.'));
    exit;
}

$slot   = getCurrentSlot();
$ip     = getClientIP();
$limit  = isset($_GET['limit'])  ? max(1, min(48, (int)$_GET['limit']))  : 6;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
// hours lookback: preview ~6h, full modal up to 24h
$hours  = isset($_GET['hours'])  ? max(1, min(48, (int)$_GET['hours']))  : 6;

try {
    $pdo = getDB();

    $cs1 = $slot['number'] * 2;
    $cs2 = $slot['number'] * 2 + 1;

    // Current 30-min results window (both 15-min vote slots)
    $stmt = $pdo->prepare(
        'SELECT vote, COUNT(*) AS cnt FROM votes
         WHERE place_id = ? AND slot_date = ? AND (slot_number = ? OR slot_number = ?)
         GROUP BY vote'
    );
    $stmt->execute(array($placeId, $slot['date'], $cs1, $cs2));

    $working = 0; $notWorking = 0;
    foreach ($stmt->fetchAll() as $row) {
        if ($row['vote'] === 'working')     $working    = (int)$row['cnt'];
        if ($row['vote'] === 'not_working') $notWorking = (int)$row['cnt'];
    }
    $total      = $working + $notWorking;
    $pctWorking = $total > 0 ? round($working    / $total * 100) : 0;
    $pctNot     = $total > 0 ? round($notWorking / $total * 100) : 0;

    // Cooldown check: did this IP vote in the current 15-min window?
    $chk = $pdo->prepare(
        'SELECT place_id, vote FROM votes
         WHERE ip_address = ? AND slot_date = ? AND slot_number = ?
         LIMIT 1'
    );
    $chk->execute(array($ip, $slot['date'], $slot['cooldown_slot']));
    $myRow        = $chk->fetch();
    $myVote       = ($myRow && (int)$myRow['place_id'] === $placeId) ? $myRow['vote'] : null;
    $hasVotedSlot = $myRow ? true : false;

    // History grouped by 30-min slots (exclude current 30-min window)
    $histStmt = $pdo->prepare(
        "SELECT FLOOR(slot_number / 2) AS sn30, slot_date,
                SUM(vote = 'working')     AS w,
                SUM(vote = 'not_working') AS n
         FROM votes
         WHERE place_id = ?
           AND created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
           AND NOT (slot_date = ? AND (slot_number = ? OR slot_number = ?))
         GROUP BY slot_date, sn30
         ORDER BY slot_date DESC, sn30 DESC
         LIMIT " . (int)$limit . " OFFSET " . (int)$offset
    );
    $histStmt->execute(array($placeId, $slot['date'], $cs1, $cs2));

    $history = array();
    foreach ($histStmt->fetchAll() as $row) {
        $sn     = (int)$row['sn30'];
        $h      = (int)floor($sn / 2);
        $mStart = ($sn % 2) * 30;
        $w      = (int)$row['w'];
        $n      = (int)$row['n'];
        if ($w > $n)     $status = 'working';
        elseif ($n > $w) $status = 'not_working';
        else             $status = 'unknown';
        $history[] = array(
            'period'      => sprintf('%02d:%02d-%02d:%02d', $h, $mStart, $h, $mStart + 29),
            'status'      => $status,
            'working'     => $w,
            'not_working' => $n,
            'total'       => $w + $n,
        );
    }

    echo json_encode(array(
        'working'        => $working,
        'not_working'    => $notWorking,
        'total'          => $total,
        'pct_working'    => $pctWorking,
        'pct_not'        => $pctNot,
        'secs_left'      => $slot['secs_left'],
        'server_time'    => $slot['server_time'],
        'my_vote'        => $myVote,
        'has_voted_slot' => $hasVotedSlot,
        'history'        => $history,
        'has_more'       => count($history) >= $limit,
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'Erreur serveur.'));
}
