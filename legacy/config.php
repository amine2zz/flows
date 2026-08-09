<?php
date_default_timezone_set('Africa/Tunis');
define('DB_HOST', 'localhost');
define('DB_NAME', 'flowstn_db');
define('DB_USER', 'flowstn_admin');
define('DB_PASS', 'cfF5Hs3~}%RW');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ));
        $pdo->exec("SET time_zone = '+01:00'");
    }
    return $pdo;
}

function initDB() {
    $pdo = getDB();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS places (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            city       VARCHAR(100) NOT NULL,
            name       VARCHAR(150) NOT NULL,
            active     TINYINT(1)   NOT NULL DEFAULT 1,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_place (city, name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // One vote per IP per slot (any place) — UNIQUE on ip+slot only
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS votes (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            place_id    INT UNSIGNED NOT NULL,
            ip_address  VARCHAR(45)  NOT NULL,
            vote        ENUM('working','not_working') NOT NULL,
            slot_date   DATE         NOT NULL,
            slot_number TINYINT UNSIGNED NOT NULL,
            created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (ip_address, slot_date, slot_number),
            KEY idx_slot  (slot_date, slot_number),
            KEY idx_place (place_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function getCurrentSlot() {
    // Prefer MySQL NOW() (same clock as vote timestamps); fallback to PHP
    try {
        $pdo = getDB();
        $row = $pdo->query("SELECT NOW() AS now_dt")->fetch();
        $now = new DateTime($row['now_dt'], new DateTimeZone('Africa/Tunis'));
    } catch (Exception $e) {
        $now = new DateTime('now', new DateTimeZone('Africa/Tunis'));
    }

    $hour       = (int)$now->format('H');
    $minute     = (int)$now->format('i');
    $second     = (int)$now->format('s');

    // 15-min cooldown (0-95) — one vote per IP per window
    $cooldownSlot = $hour * 4 + (int)floor($minute / 15);
    $secsLeft     = 900 - (($minute % 15) * 60 + $second);

    // 30-min results bucket (0-47) — displayed counts + history
    $slotInHour = (int)floor($minute / 30);
    $slotNumber = $hour * 2 + $slotInHour;
    $startMin   = $slotInHour * 30;

    return array(
        'date'         => $now->format('Y-m-d'),
        'number'       => $slotNumber,
        'cooldown_slot'=> $cooldownSlot,
        'start'        => sprintf('%02d:%02d', $hour, $startMin),
        'end'          => sprintf('%02d:%02d', $hour, $startMin + 29),
        'secs_left'    => $secsLeft,
        'server_time'  => $now->format('H:i:s'),
        'day_name'     => getDayNameFr($now->format('N')),
        'month_name'   => getMonthNameFr($now->format('n')),
        'day'          => $now->format('j'),
        'year'         => $now->format('Y'),
    );
}

function getClientIP() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip    = trim($parts[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function getDayNameFr($n) {
    $days = array('1'=>'Lundi','2'=>'Mardi','3'=>'Mercredi','4'=>'Jeudi',
                  '5'=>'Vendredi','6'=>'Samedi','7'=>'Dimanche');
    return isset($days[$n]) ? $days[$n] : '';
}

function getMonthNameFr($n) {
    $months = array('1'=>'Janvier','2'=>'Fevrier','3'=>'Mars','4'=>'Avril',
                    '5'=>'Mai','6'=>'Juin','7'=>'Juillet','8'=>'Aout',
                    '9'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Decembre');
    return isset($months[$n]) ? $months[$n] : '';
}
