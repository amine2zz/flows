<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once 'config.php';

$slot = getCurrentSlot();

try {
    $pdo = getDB();

    // Calculate time 1 hour ago
    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));

    // Total votes in last 1 hour
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total_votes,
                SUM(vote = "working") AS working_votes,
                SUM(vote = "not_working") AS not_working_votes
         FROM votes
         WHERE created_at >= ?'
    );
    $stmt->execute(array($oneHourAgo));
    $row = $stmt->fetch();

    $totalVotes = (int)$row['total_votes'];
    $workingVotes = (int)$row['working_votes'];
    $notWorkingVotes = (int)$row['not_working_votes'];

    // All-time total votes
    $allTimeTotalVotes = $pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();
    $allTimeWorkingVotes = $pdo->query('SELECT COUNT(*) FROM votes WHERE vote = "working"')->fetchColumn();
    $allTimeNotWorkingVotes = $pdo->query('SELECT COUNT(*) FROM votes WHERE vote = "not_working"')->fetchColumn();

    // Total active places
    $totalPlaces = $pdo->query('SELECT COUNT(*) FROM places WHERE active = 1')->fetchColumn();

    // All-time places with votes
    $placesWithVotes = (int)$pdo->query('SELECT COUNT(DISTINCT place_id) FROM votes')->fetchColumn();

    // Calculate percentage of places working (all-time)
    $placesWorking = 0;
    $placesNotWorking = 0;
    if ($placesWithVotes > 0) {
        $stmt = $pdo->query(
            'SELECT place_id, vote, COUNT(*) AS cnt
             FROM votes
             GROUP BY place_id, vote'
        );

        $placeVotes = array();
        foreach ($stmt->fetchAll() as $r) {
            $pid = (int)$r['place_id'];
            if (!isset($placeVotes[$pid])) {
                $placeVotes[$pid] = array('working' => 0, 'not_working' => 0);
            }
            $placeVotes[$pid][$r['vote']] = (int)$r['cnt'];
        }

        foreach ($placeVotes as $votes) {
            if ($votes['working'] > $votes['not_working']) {
                $placesWorking++;
            } elseif ($votes['not_working'] > $votes['working']) {
                $placesNotWorking++;
            }
        }
    }

    $pctWorking = $placesWithVotes > 0 ? round($placesWorking / $placesWithVotes * 100) : 0;
    $pctNotWorking = $placesWithVotes > 0 ? round($placesNotWorking / $placesWithVotes * 100) : 0;
    $coveragePct = $totalPlaces > 0 ? round($placesWithVotes / $totalPlaces * 100) : 0;

    echo json_encode(array(
        'total_votes' => $totalVotes,
        'working_votes' => $workingVotes,
        'not_working_votes' => $notWorkingVotes,
        'all_time_total_votes' => $allTimeTotalVotes,
        'all_time_working_votes' => $allTimeWorkingVotes,
        'all_time_not_working_votes' => $allTimeNotWorkingVotes,
        'total_places' => $totalPlaces,
        'places_with_votes' => $placesWithVotes,
        'places_working' => $placesWorking,
        'places_not_working' => $placesNotWorking,
        'pct_working' => $pctWorking,
        'pct_not_working' => $pctNotWorking,
        'coverage_pct' => $coveragePct,
        'server_time' => $slot['server_time'],
        'secs_left' => $slot['secs_left'],
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'Erreur serveur.'));
}
