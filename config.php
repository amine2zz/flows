<?php
date_default_timezone_set('Africa/Tunis');

define('DB_HOST', 'localhost');
define('DB_NAME', 'flowstn_db');
define('DB_USER', 'flowstn_admin');
define('DB_PASS', 'cfF5Hs3~}%RW');
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME', 'THE216');
define('CURRENCY', 'DT');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ));
        $tzOffset = (new DateTime('now', new DateTimeZone('Africa/Tunis')))->format('P');
        $pdo->exec("SET time_zone = '" . $tzOffset . "'");
    }
    return $pdo;
}

function initDB() {
    $pdo = getDB();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS products (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug        VARCHAR(80)  NOT NULL,
            name        VARCHAR(150) NOT NULL,
            description TEXT         NOT NULL,
            price       DECIMAL(10,2) NOT NULL,
            category    VARCHAR(60)  NOT NULL DEFAULT 'T-Shirts',
            badge       VARCHAR(40)  NULL,
            color_hex   CHAR(7)      NOT NULL DEFAULT '#3D2314',
            sizes       VARCHAR(80)  NOT NULL DEFAULT 'S,M,L,XL',
            stock       INT UNSIGNED NOT NULL DEFAULT 50,
            featured    TINYINT(1)   NOT NULL DEFAULT 0,
            active      TINYINT(1)   NOT NULL DEFAULT 1,
            sort_order  INT          NOT NULL DEFAULT 0,
            created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_slug (slug),
            KEY idx_active (active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS orders (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_ref    VARCHAR(20)  NOT NULL,
            customer_name VARCHAR(120) NOT NULL,
            phone        VARCHAR(30)  NOT NULL,
            address      VARCHAR(255) NOT NULL,
            city         VARCHAR(80)  NOT NULL,
            notes        TEXT         NULL,
            total        DECIMAL(10,2) NOT NULL,
            status       ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_ref (order_ref),
            KEY idx_status (status),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS order_items (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id     INT UNSIGNED NOT NULL,
            product_id   INT UNSIGNED NOT NULL,
            product_name VARCHAR(150) NOT NULL,
            size         VARCHAR(10)  NOT NULL,
            qty          INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price   DECIMAL(10,2) NOT NULL,
            KEY idx_order (order_id),
            KEY idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    seedProducts($pdo);
}

function seedProducts($pdo) {
    $count = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count > 0) return;

    $products = array(
        array('the216-classic', 'T-Shirt THE216 Classic', 'Coton premium 100%. Logo THE216 brode poitrine. Coupe regular fit.', 45.00, 'T-Shirts', 'Best Seller', '#1a1a1a', 'S,M,L,XL', 80, 1, 1),
        array('roots-hoodie', 'Hoodie Roots 216', 'Sweat capuche epais, poches kangourou. Edition limitee Tunisie.', 95.00, 'Hoodies', 'Nouveau', '#3D2314', 'S,M,L,XL', 40, 1, 2),
        array('marron-oversized', 'Oversized Tee Marron', 'Coupe oversized streetwear. Teinture marron profond, toucher doux.', 55.00, 'T-Shirts', 'Trending', '#5C3317', 'S,M,L,XL,XXL', 60, 1, 3),
        array('cap-216', 'Casquette 216', 'Casquette structurée brodee THE216. Ajustable, unisexe.', 35.00, 'Accessoires', null, '#1a1a1a', 'TU', 100, 0, 4),
        array('tote-minimal', 'Tote Bag Minimal', 'Sac tote canvas resistant. Impression serigraphie noir & marron.', 30.00, 'Accessoires', null, '#F5F0EB', 'TU', 70, 0, 5),
        array('sweatpants-216', 'Sweatpants 216', 'Jogging confort coupe droite. Bandes laterales brodees 216.', 75.00, 'Pantalons', 'Nouveau', '#2a2a2a', 'S,M,L,XL', 35, 1, 6),
        array('white-essential', 'T-Shirt White Essential', 'Blanc pur, logo discret poitrine. Basique premium everyday.', 42.00, 'T-Shirts', null, '#ffffff', 'S,M,L,XL', 90, 0, 7),
        array('zip-hoodie-marron', 'Zip Hoodie Marron', 'Hoodie zip complet. Interieur molleton, finitions premium.', 110.00, 'Hoodies', 'Premium', '#4A2C17', 'S,M,L,XL', 25, 1, 8),
    );

    $stmt = $pdo->prepare(
        'INSERT INTO products (slug, name, description, price, category, badge, color_hex, sizes, stock, featured, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($products as $p) {
        $stmt->execute($p);
    }
}

function generateOrderRef() {
    return 'T216-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
