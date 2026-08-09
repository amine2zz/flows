<?php
// places_data.php — Clears and re-seeds the places table.
// Visit this URL once: https://flows.tn/places_data.php
// Then delete or rename this file.

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config.php';

$errors   = array();
$inserted = 0;

try {
    $pdo = getDB();

    // Force utf8mb4 for this connection
    $pdo->exec("SET NAMES 'utf8mb4'");
    $pdo->exec("SET CHARACTER SET utf8mb4");

    // Recreate places table cleanly (drop + create to fix any charset issues)
    $pdo->exec("DROP TABLE IF EXISTS places");
    $pdo->exec(
        "CREATE TABLE places (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            city       VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            name       VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            active     TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_place (city, name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    // Also recreate votes table to match
    $pdo->exec("DROP TABLE IF EXISTS votes");
    $pdo->exec(
        "CREATE TABLE votes (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            place_id    INT UNSIGNED NOT NULL,
            ip_address  VARCHAR(45) NOT NULL,
            vote        ENUM('working','not_working') NOT NULL,
            slot_date   DATE NOT NULL,
            slot_number TINYINT UNSIGNED NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (ip_address, place_id, slot_date, slot_number),
            KEY idx_slot (slot_date, slot_number),
            KEY idx_place (place_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

} catch (PDOException $e) {
    die('<p style="color:red;font-family:sans-serif;padding:20px">Erreur DB : ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// ─── All Tunisia places ───────────────────────────────────────────────────────
$places = array(

    'Tunis' => array(
        'Bab Bhar (Ville Nouvelle)', 'Bab Souika', 'Bab El Khadra', 'Bab El Fellah',
        'Medina de Tunis', 'Le Bardo', 'La Marsa', 'Carthage', 'Sidi Bou Said',
        'La Goulette', 'Le Kram', 'Ain Zaghouan', 'Cite Olympique',
        'Cite Ettadhamen', 'Cite El Khadra', 'Cite Ennasr', 'Cite Ghazela',
        'Cite Jardins', 'Cite Mahrajene', 'Cite Montplaisir', 'Cite Sportive',
        'El Menzah 1', 'El Menzah 2', 'El Menzah 3', 'El Menzah 4',
        'El Menzah 5', 'El Menzah 6', 'El Menzah 7', 'El Menzah 8', 'El Menzah 9',
        'Aouina', 'El Manar 1', 'El Manar 2', 'El Manar 3',
        'El Omrane', 'El Omrane Superieur', 'El Ouardia', 'Ezzouhour',
        'Hrairia', 'Jebel Jelloud', 'Kabaria', 'Mellassine',
        'Sejoumi', 'Sidi El Bechir', 'Sidi Hassine',
        'Hay El Khadra', 'Hay El Warraq', 'Hay Hlel',
    ),

    'Ariana' => array(
        'Ariana Ville', 'Ettadhamen', 'Mnihla', 'Kalaat el-Andalous',
        'La Soukra', 'Raoued', 'Sidi Thabet', 'Borj Louzir',
        'Cite Ennasr 1', 'Cite Ennasr 2', 'Ghazela', "Jardins d'El Menzah",
        'Borj Turki', 'Chotrana', 'Dar Fadhal',
    ),

    'Ben Arous' => array(
        'Ben Arous Ville', 'Bou Mhel el-Bassatine', 'El Mourouj', 'Ezzahra',
        'Fouchana', 'Hammam Chott', 'Hammam Lif', 'Khalidia',
        'Medina Jedida', 'Megrine', 'Mornag', 'Mohamedia',
        'Nouvelle Medina', 'Rades', 'Sidi Rezig',
    ),

    'Manouba' => array(
        'Manouba Ville', 'Borj El Amri', 'Djedeida', 'El Battan',
        'Mornaguia', 'Oued Ellil', 'Tebourba', 'Douar Hicher',
    ),

    'Nabeul' => array(
        'Nabeul Ville', 'Hammamet', 'Kelibia', 'Korba',
        'Menzel Bouzelfa', 'Menzel Temime', 'Soliman', 'Takelsa',
        'Beni Khalled', 'Beni Khiar', 'Dar Chaabane', 'El Haouaria',
        'Grombalia', 'Hammam Ghezaz', 'Korbous', 'Maamoura',
        'Menzel Horr', 'Somaa', 'Tazerka',
    ),

    'Zaghouan' => array(
        'Zaghouan Ville', 'Bir Mcherga', 'El Fahs', 'Nadhour', 'Saouaf', 'Zriba',
    ),

    'Bizerte' => array(
        'Bizerte Ville', 'El Alia', 'Ghar El Melh', 'Ghezala',
        'Joumine', 'Mateur', 'Menzel Bourguiba', 'Menzel Jemil',
        'Ras Jebel', 'Sejnane', 'Tinja', 'Utique', 'Zarzouna',
    ),

    'Beja' => array(
        'Beja Ville', 'Amdoun', 'Goubellat', 'Medjez el-Bab',
        'Nefza', 'Slouguia', 'Teboursouk', 'Testour', 'Thibar',
    ),

    'Jendouba' => array(
        'Jendouba Ville', 'Ain Draham', 'Balta-Bou Aouane', 'Bou Salem',
        'Fernana', 'Ghardimaou', 'Oued Mliz', 'Tabarka',
    ),

    'Kef' => array(
        'Le Kef Ville', 'Dahmani', 'Es Sers', 'Jerissa',
        'Kalaat Sinane', 'Kalaat Khasba', 'Nebeur', 'Sakiet Sidi Youssef', 'Tajerouine',
    ),

    'Siliana' => array(
        'Siliana Ville', 'Bargou', 'Bou Arada', 'El Aroussa',
        'El Krib', 'Gaafour', 'Kesra', 'Makthar', 'Rohia', 'Sidi Bou Rouis',
    ),

    'Sousse' => array(
        'Sousse Ville', 'Akouda', 'Bouficha', 'Enfidha',
        'Hammam Sousse', 'Hergla', 'Kalaa Kebira', 'Kalaa Seghira',
        'Kondar', 'Msaken', 'Sidi Bou Ali', 'Sidi El Hani', 'Zaouiet Sousse',
    ),

    'Monastir' => array(
        'Monastir Ville', 'Bembla', 'Beni Hassen', 'Jammel',
        'Ksar Hellal', 'Ksibet el-Mediouni', 'Moknine', 'Ouerdanine',
        'Sahline', 'Sayada-Lamta-Bou Hajar', 'Teboulba', 'Zeramdine',
    ),

    'Mahdia' => array(
        'Mahdia Ville', 'Bou Merdes', 'Chebba', 'Chorbane',
        'El Bradaa', 'Essouassi', 'Hebira', 'Ksour Essef',
        'Melloulech', 'Ouled Chamekh', 'Sidi Alouane',
    ),

    'Sfax' => array(
        'Sfax Ville', 'Agareb', 'Bir Ali Ben Khalifa', 'Djebeniana',
        'El Amra', 'El Ghraiba', 'Graiba', 'Hencha',
        'Kerkennah', 'Mahres', 'Menzel Chaker',
        'Sakiet Eddaier', 'Sakiet Ezzit', 'Skhira', 'Thyna',
    ),

    'Kairouan' => array(
        'Kairouan Ville', 'Bou Hajla', 'Chebika', 'Cherarda',
        'El Alaa', 'Haffouz', 'Hajeb El Aioun', 'Nasrallah', 'Oueslatia', 'Sbikha',
    ),

    'Kasserine' => array(
        'Kasserine Ville', 'Ain Jedey', 'El Ayoun', 'Ezzouhour',
        'Feriana', 'Foussana', 'Hassi El Ferid', 'Hidra',
        'Jedeliane', 'Majel Bel Abbes', 'Sbeitla', 'Sbiba', 'Thala',
    ),

    'Sidi Bouzid' => array(
        'Sidi Bouzid Ville', 'Ben Oun', 'Bir El Hafey', 'Cebbala Ouled Asker',
        'Jilma', 'Mazzouna', 'Meknassy', 'Menzel Bouzaiane',
        'Ouled Haffouz', 'Regueb', 'Sidi Ali Ben Aoun', 'Souk Jedid',
    ),

    'Gabes' => array(
        'Gabes Ville', 'El Hamma', 'Ghannouch', 'Mareth',
        'Matmata', 'Matmata Nouvelle', 'Menzel El Habib', 'Metouia',
        'Oudhref', 'Zarat',
    ),

    'Medenine' => array(
        'Medenine Ville', 'Ben Gardane', 'Beni Khedache', 'Djerba - Ajim',
        'Djerba - Houmt Souk', 'Djerba - Midoun', 'Sidi Makhlouf', 'Zarzis',
    ),

    'Tataouine' => array(
        'Tataouine Ville', 'Bir Lahmar', 'Dehiba', 'Ghomrassen', 'Remada', 'Smar',
    ),

    'Gafsa' => array(
        'Gafsa Ville', 'Belkhir', 'El Guettar', 'El Ksar',
        'Mdhilla', 'Metlaoui', 'Moulares', 'Redeyef', 'Sened', 'Sidi Aich',
    ),

    'Tozeur' => array(
        'Tozeur Ville', 'Degache', 'Hazoua', 'Nefta', 'Tameghza',
    ),

    'Kebili' => array(
        'Kebili Ville', 'Douz', 'El Faouar', 'Souk Lahad',
    ),
);

// ─── Insert all places ────────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare('INSERT INTO places (city, name, active) VALUES (?, ?, 1)');
    foreach ($places as $city => $names) {
        foreach ($names as $name) {
            try {
                $stmt->execute(array($city, $name));
                $inserted++;
            } catch (PDOException $e) {
                $errors[] = $city . ' / ' . $name . ' : ' . $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    die('<p style="color:red;font-family:sans-serif;padding:20px">Erreur : ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// ─── Output result ────────────────────────────────────────────────────────────
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:sans-serif;padding:20px">';
echo '<h2 style="color:green">&#10003; Seed termine</h2>';
echo '<p><strong>' . $inserted . '</strong> lieu(x) insere(s) avec succes.</p>';

if (!empty($errors)) {
    echo '<p style="color:orange"><strong>' . count($errors) . ' erreur(s) :</strong></p><ul>';
    foreach ($errors as $err) {
        echo '<li>' . htmlspecialchars($err) . '</li>';
    }
    echo '</ul>';
}

echo '<p><a href="index.php" style="color:blue">Aller sur le site &rarr;</a></p>';
echo '<p style="color:gray;font-size:12px">Vous pouvez supprimer ce fichier maintenant.</p>';
echo '</body></html>';
