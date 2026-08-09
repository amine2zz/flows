<?php
require_once 'config.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
initDB();

$slot = getCurrentSlot();
$ip   = getClientIP();

$places         = array();
$myVotedPlaceId = null;
$myVoteValue    = null;
$slotVoteCounts = array();

try {
    $pdo  = getDB();
    $rows = $pdo->query(
        'SELECT id, city, name FROM places WHERE active = 1 ORDER BY city ASC, name ASC'
    )->fetchAll();
    foreach ($rows as $row) {
        $places[$row['city']][] = array('id' => (int)$row['id'], 'name' => $row['name']);
    }

    $stmt = $pdo->prepare(
        'SELECT place_id, vote FROM votes
         WHERE ip_address = ? AND slot_date = ? AND slot_number = ?
         LIMIT 1'
    );
    $stmt->execute(array($ip, $slot['date'], $slot['cooldown_slot']));
    $myRow = $stmt->fetch();
    if ($myRow) {
        $myVotedPlaceId = (int)$myRow['place_id'];
        $myVoteValue    = $myRow['vote'];
    }

    // Counts for the last 1 hour
    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $cntStmt = $pdo->prepare(
        'SELECT place_id, vote, COUNT(*) AS cnt FROM votes
         WHERE created_at >= ?
         GROUP BY place_id, vote'
    );
    $cntStmt->execute(array($oneHourAgo));
    foreach ($cntStmt->fetchAll() as $row) {
        $pid = (int)$row['place_id'];
        if (!isset($slotVoteCounts[$pid])) {
            $slotVoteCounts[$pid] = array('working' => 0, 'not_working' => 0);
        }
        $slotVoteCounts[$pid][$row['vote']] = (int)$row['cnt'];
    }

    // Get last vote for each place
    $lastVotes = array();
    $lastStmt = $pdo->query(
        'SELECT place_id, vote, created_at 
         FROM votes 
         ORDER BY created_at DESC'
    );
    foreach ($lastStmt->fetchAll() as $row) {
        $pid = (int)$row['place_id'];
        if (!isset($lastVotes[$pid])) {
            $lastVotes[$pid] = array(
                'vote' => $row['vote'],
                'created_at' => $row['created_at']
            );
        }
    }
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$hasVotedSlot = $myVotedPlaceId !== null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Etat de l'electricite en Tunisie.">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <link rel="icon" href="data:,">
    <title>Electricite Tunisie</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container">

    <header>
        <h1>Electricite en Tunisie</h1>
        <p class="subtitle">Signalez la situation dans votre region</p>
    </header>

    <div class="slot-box">
        <div class="slot-left">
            <span class="slot-label">Creneau actuel</span>
            <span class="slot-time">
                <?php echo htmlspecialchars($slot['day_name'].' '.$slot['day'].' '.$slot['month_name'].' '.$slot['year']); ?>
                &nbsp;<strong><?php echo htmlspecialchars($slot['start'].' - '.$slot['end']); ?></strong>
            </span>
        </div>
        <div class="slot-right">
            <span class="slot-label">Prochain dans</span>
            <span id="countdown" class="countdown">--:--</span>
            <span class="server-time">Serveur : <span id="serverTime"><?php echo htmlspecialchars($slot['server_time']); ?></span></span>
        </div>
    </div>

    <div class="stats-box" id="statsBox">
        <div class="stats-header">
            <span class="stats-label">Statistiques globales</span>
            <span class="stats-time" id="statsTime">Chargement...</span>
        </div>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-value" id="statTotalVotes">-</span>
                <span class="stat-label">Votes (1h)</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="statAllTimeVotes">-</span>
                <span class="stat-label">Votes totaux</span>
            </div>
            <div class="stat-item">
                <span class="stat-value stat-green" id="statWorkingVotes">-</span>
                <span class="stat-label">Marche (total)</span>
            </div>
            <div class="stat-item">
                <span class="stat-value stat-red" id="statNotWorkingVotes">-</span>
                <span class="stat-label">Ne marche pas (total)</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="statPlacesWithVotes">-</span>
                <span class="stat-label">Lieux avec votes</span>
            </div>
            <div class="stat-item">
                <span class="stat-value stat-green" id="statPlacesWorking">-</span>
                <span class="stat-label">Lieux fonctionnels</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="statTotalPlaces">-</span>
                <span class="stat-label">Lieux totaux</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="statCoverage">-</span>
                <span class="stat-label">Couverture %</span>
            </div>
        </div>
    </div>

    <?php if ($hasVotedSlot) { ?>
    <div class="voted-notice">
        Vous avez deja vote. Attendez <strong id="countdownNotice">--:--</strong> pour voter a nouveau.
    </div>
    <?php } ?>

    <div class="main-layout">
        <div class="main-col">
            <div class="search-wrap">
                <input type="search" id="searchInput" class="search-input"
                       placeholder="Rechercher une ville ou un lieu..."
                       autocomplete="off" spellcheck="false">
                <button class="search-clear" id="searchClear" style="display:none">x</button>
            </div>

            <div id="citiesContainer">
            <?php if (isset($dbError)) { ?>
                <div class="empty-state" style="color:red"><p>Erreur DB: <?php echo htmlspecialchars($dbError); ?></p></div>
            <?php } elseif (empty($places)) { ?>
                <div class="empty-state"><p>Aucun lieu disponible.</p></div>
            <?php } else { ?>
            <?php foreach ($places as $city => $cityPlaces) { ?>
                <div class="city-block" data-city="<?php echo htmlspecialchars(mb_strtolower($city, 'UTF-8')); ?>">

                    <button class="city-header" aria-expanded="false">
                        <span class="city-name"><?php echo htmlspecialchars($city); ?></span>
                        <span class="city-count"><?php echo count($cityPlaces); ?> lieux</span>
                        <span class="city-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg></span>
                    </button>

                    <div class="city-body">
                        <ul class="place-list">
                        <?php foreach ($cityPlaces as $place) {
                            $pid    = $place['id'];
                            $counts = isset($slotVoteCounts[$pid]) ? $slotVoteCounts[$pid] : array('working' => 0, 'not_working' => 0);
                            $total  = $counts['working'] + $counts['not_working'];
                            $pctW   = $total > 0 ? round($counts['working']    / $total * 100) : 0;
                            $pctN   = $total > 0 ? round($counts['not_working'] / $total * 100) : 0;

                            $isMyPlace = ($myVotedPlaceId === $pid);
                            if ($isMyPlace && $myVoteValue === 'working') {
                                $rowCls = 'row--yes';
                            } elseif ($isMyPlace && $myVoteValue === 'not_working') {
                                $rowCls = 'row--no';
                            } else {
                                $rowCls = '';
                            }

                            $lastVote = isset($lastVotes[$pid]) ? $lastVotes[$pid] : null;
                            $lastVoteTime = '';
                            $lastVoteStatus = '';
                            $lastVoteTimeAgo = '';
                            if ($lastVote) {
                                $lastVoteTime = $lastVote['created_at'];
                                $lastVoteStatus = $lastVote['vote'] === 'working' ? 'Marche' : 'Ne marche pas';
                                // Calculate time ago using server time
                                $voteTime = new DateTime($lastVote['created_at']);
                                $now = new DateTime('now', new DateTimeZone('Africa/Tunis'));
                                $diff = $now->diff($voteTime);
                                if ($diff->s < 60 && $diff->i == 0 && $diff->h == 0 && $diff->d == 0) {
                                    $lastVoteTimeAgo = 'ilya ' . $diff->s . 's';
                                } elseif ($diff->i < 60 && $diff->h == 0 && $diff->d == 0) {
                                    $lastVoteTimeAgo = 'ilya ' . $diff->i . 'min';
                                } elseif ($diff->h < 24 && $diff->d == 0) {
                                    $lastVoteTimeAgo = 'ilya ' . $diff->h . 'h';
                                } else {
                                    $lastVoteTimeAgo = 'ilya ' . $diff->d . 'j';
                                }
                            }
                        ?>
                            <li class="place-row <?php echo $rowCls; ?>"
                                id="card-<?php echo $pid; ?>"
                                data-place="<?php echo htmlspecialchars(mb_strtolower($place['name'], 'UTF-8')); ?>"
                                data-id="<?php echo $pid; ?>">

                                <span class="row-name" title="Cliquez pour voir l'historique">
                                    <svg class="icon-link" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 13a5 5 0 0 1 0-7l2-2a5 5 0 1 1 7 7l-1.5 1.5"/>
                                        <path d="M14 11a5 5 0 0 1 0 7l-2 2a5 5 0 1 1-7-7L6.5 11.5"/>
                                    </svg>

                                    <?php echo htmlspecialchars($place['name']); ?>
                                </span>

                                <div class="row-bar-wrap" id="bars-<?php echo $pid; ?>">
                                    <div class="row-bar-track<?php echo $total > 0 ? ' has-votes' : ''; ?>">
                                        <div class="row-bar-fill" style="width:<?php echo $pctW; ?>%"></div>
                                    </div>
                                    <span class="row-stat yes-stat" id="pctW-<?php echo $pid; ?>"><?php echo $pctW; ?>%</span>
                                    <span class="row-sep">/</span>
                                    <span class="row-stat no-stat"  id="pctN-<?php echo $pid; ?>"><?php echo $pctN; ?>%</span>
                                    <span class="row-total" id="tot-<?php echo $pid; ?>"><?php echo $total; ?>v</span>
                                </div>

                                <?php if ($isMyPlace) { ?>
                                    <span class="row-voted <?php echo $myVoteValue === 'working' ? 'rv-yes' : 'rv-no'; ?>">
                                        <?php echo $myVoteValue === 'working' ? 'Marche' : 'Ne marche pas'; ?>
                                    </span>
                                <?php } elseif ($hasVotedSlot) { ?>
                                    <span class="row-locked" title="Deja vote. Attendez 15min." onclick="showVotedMessage()">
                                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    </span>
                                <?php } else { ?>
                                    <div class="row-btns">
                                        <button class="rbtn rbtn-yes" onclick="castVote(<?php echo $pid; ?>, 'working')">Marche</button>
                                        <button class="rbtn rbtn-no"  onclick="castVote(<?php echo $pid; ?>, 'not_working')">Ne marche pas</button>
                                    </div>
                                <?php } ?>

                                <div class="row-last-vote-banner" id="last-<?php echo $pid; ?>" data-time="<?php echo htmlspecialchars($lastVoteTime); ?>" data-status="<?php echo htmlspecialchars($lastVoteStatus); ?>">
                                    <?php if ($lastVote) { ?>
                                        <span class="last-vote-status <?php echo $lastVote['vote'] === 'working' ? 'last-yes' : 'last-no'; ?>"><?php echo $lastVoteStatus; ?></span>
                                        <span class="last-vote-time"><?php echo htmlspecialchars($lastVoteTimeAgo); ?></span>
                                    <?php } else { ?>
                                        <span class="last-vote-none">-</span>
                                    <?php } ?>
                                </div>

                            </li>
                        <?php } ?>
                        </ul>
                    </div>

                </div>
            <?php } ?>
            <?php } ?>
            </div>

            <div class="suggest-box">
                <button class="suggest-toggle" id="suggestToggle">+ Suggerer un lieu manquant</button>
                <div class="suggest-form" id="suggestForm">
                    <p class="suggest-note">Votre suggestion sera visible apres validation.</p>
                    <div class="form-row">
                        <input type="text" id="suggestCity" placeholder="Ville (ex: Tunis)" maxlength="100">
                        <input type="text" id="suggestName" placeholder="Lieu (ex: Cite Ennasr 3)" maxlength="150">
                        <button class="btn-suggest-send" id="suggestSend">Envoyer</button>
                    </div>
                    <div id="suggestMsg" class="suggest-msg" aria-live="polite"></div>
                </div>
            </div>
        </div>

        <aside class="hist-panel" id="histPanel">
            <div class="hist-panel-inner" id="histPanelInner">
                <p class="hist-placeholder">Cliquez sur un lieu pour voir son historique.</p>
            </div>
        </aside>
    </div>

</div>

<footer>
    <p>Developpe par <strong>Med Amine Ghariani</strong> &mdash; <a href="https://flows.tn" target="_blank" rel="noopener">flows.tn</a></p>
</footer>

<div id="histModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modalTitle">Historique</span>
            <button class="modal-close" id="modalClose">Fermer</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>
<div id="modalOverlay" class="modal-overlay"></div>

<script>
    var SECS_LEFT       = <?php echo (int)$slot['secs_left']; ?>;
    var HAS_VOTED_SLOT  = <?php echo $hasVotedSlot ? 'true' : 'false'; ?>;
    var MY_VOTED_PLACE  = <?php echo $myVotedPlaceId ? (int)$myVotedPlaceId : 'null'; ?>;
    var MY_VOTE_VALUE   = <?php echo $myVoteValue ? json_encode($myVoteValue) : 'null'; ?>;
</script>
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
