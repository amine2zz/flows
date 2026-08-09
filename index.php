<?php
require_once 'config.php';
initDB();

$products = array();
$categories = array();
try {
    $pdo = getDB();
    $rows = $pdo->query(
        'SELECT id, slug, name, description, price, category, badge, color_hex, sizes, stock, featured
         FROM products WHERE active = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
    foreach ($rows as $row) {
        $row['id']       = (int)$row['id'];
        $row['price']    = (float)$row['price'];
        $row['stock']    = (int)$row['stock'];
        $row['featured'] = (int)$row['featured'];
        $row['sizes']    = array_map('trim', explode(',', $row['sizes']));
        $products[] = $row;
        if (!in_array($row['category'], $categories, true)) {
            $categories[] = $row['category'];
        }
    }
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="THE216 — Streetwear & mode tunisienne. T-shirts, hoodies et accessoires livrés partout en Tunisie.">
    <title>THE216 — Streetwear Tunisie</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Nav -->
<nav class="nav" id="nav">
    <div class="nav-inner">
        <a href="#" class="nav-logo">
            <img src="assets/logo.png" alt="THE216" width="48" height="48">
            <span>THE216</span>
        </a>
        <div class="nav-links">
            <a href="#products">Boutique</a>
            <a href="#stats">Stats</a>
            <a href="#about">A propos</a>
        </div>
        <button class="cart-btn" id="cartBtn" aria-label="Panier">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            <span class="cart-count" id="cartCount">0</span>
        </button>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <div class="hero-logo-wrap">
            <img src="assets/logo.png" alt="THE216" class="hero-logo" width="200" height="200">
            <div class="hero-sparkle"></div>
        </div>
        <h1 class="hero-title">THE<span>216</span></h1>
        <p class="hero-sub">Streetwear tunisien. Qualite premium.<br>Livraison partout en Tunisie.</p>
        <a href="#products" class="btn-primary hero-cta">Decouvrir la collection</a>
    </div>
    <div class="hero-scroll">
        <span>Scroll</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- Marquee -->
<div class="marquee">
    <div class="marquee-track">
        <span>THE216</span><span>•</span><span>TUNISIA</span><span>•</span><span>STREETWEAR</span><span>•</span>
        <span>THE216</span><span>•</span><span>TUNISIA</span><span>•</span><span>STREETWEAR</span><span>•</span>
        <span>THE216</span><span>•</span><span>TUNISIA</span><span>•</span><span>STREETWEAR</span><span>•</span>
    </div>
</div>

<!-- Products -->
<section class="section" id="products">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Collection</span>
            <h2>Nos Produits</h2>
        </div>

        <?php if (isset($dbError)): ?>
        <p class="error-msg">Erreur DB: <?php echo htmlspecialchars($dbError); ?></p>
        <?php elseif (empty($products)): ?>
        <p class="empty-msg">Aucun produit disponible.</p>
        <?php else: ?>

        <div class="filters" id="filters">
            <button class="filter-btn active" data-cat="all">Tous</button>
            <?php foreach ($categories as $cat): ?>
            <button class="filter-btn" data-cat="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $p): ?>
            <article class="product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-id="<?php echo $p['id']; ?>">
                <?php if ($p['badge']): ?>
                <span class="product-badge"><?php echo htmlspecialchars($p['badge']); ?></span>
                <?php endif; ?>
                <div class="product-visual" style="--prod-color: <?php echo htmlspecialchars($p['color_hex']); ?>">
                    <div class="product-shirt">
                        <span class="product-num">216</span>
                    </div>
                </div>
                <div class="product-info">
                    <span class="product-cat"><?php echo htmlspecialchars($p['category']); ?></span>
                    <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <p class="product-desc"><?php echo htmlspecialchars($p['description']); ?></p>
                    <div class="product-footer">
                        <span class="product-price"><?php echo number_format($p['price'], 0); ?> <?php echo CURRENCY; ?></span>
                        <button class="btn-add" data-product='<?php echo json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                            Ajouter
                        </button>
                    </div>
                    <div class="size-picker" style="display:none">
                        <span class="size-label">Taille :</span>
                        <?php foreach ($p['sizes'] as $sz): ?>
                        <button class="size-btn" data-size="<?php echo htmlspecialchars($sz); ?>"><?php echo htmlspecialchars($sz); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Stats / Chart -->
<section class="section section-dark" id="stats">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Live</span>
            <h2>Statistiques Boutique</h2>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-num" id="statOrders">0</span>
                <span class="stat-label">Commandes</span>
            </div>
            <div class="stat-card">
                <span class="stat-num" id="statRevenue">0</span>
                <span class="stat-label">Revenus (DT)</span>
            </div>
            <div class="stat-card">
                <span class="stat-num" id="statProducts"><?php echo count($products); ?></span>
                <span class="stat-label">Produits</span>
            </div>
        </div>
        <div class="charts-row">
            <div class="chart-box">
                <h3>Ventes par categorie</h3>
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Top produits</h3>
                <canvas id="productsChart"></canvas>
            </div>
        </div>
    </div>
</section>

<!-- About -->
<section class="section" id="about">
    <div class="container about-grid">
        <div class="about-text">
            <span class="section-tag">Notre histoire</span>
            <h2>Made in Tunisia</h2>
            <p>THE216 est ne de l'amour pour la culture tunisienne et le streetwear. Le 216, c'est le code pays qui nous unit — on le porte fièrement sur chaque piece.</p>
            <p>Qualite premium, designs uniques, livraison rapide dans toute la Tunisie. Paiement a la livraison disponible.</p>
            <div class="about-features">
                <div class="feat"><span>🚚</span> Livraison 24-72h</div>
                <div class="feat"><span>💳</span> Paiement a la livraison</div>
                <div class="feat"><span>✨</span> Qualite premium</div>
            </div>
        </div>
        <div class="about-visual">
            <img src="assets/logo.png" alt="THE216" class="about-logo">
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <img src="assets/logo.png" alt="THE216" width="40" height="40">
            <span>THE216</span>
        </div>
        <p>&copy; <?php echo date('Y'); ?> THE216 — Streetwear Tunisie. Tous droits reserves.</p>
        <p class="footer-dev">Developpe par <strong>Med Amine Ghariani</strong></p>
    </div>
</footer>

<!-- Cart Drawer -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<aside class="cart-drawer" id="cartDrawer">
    <div class="drawer-head">
        <h3>Panier</h3>
        <button class="drawer-close" id="drawerClose">&times;</button>
    </div>
    <div class="drawer-body" id="cartItems">
        <p class="cart-empty">Votre panier est vide.</p>
    </div>
    <div class="drawer-foot">
        <div class="cart-total">
            <span>Total</span>
            <strong id="cartTotal">0 <?php echo CURRENCY; ?></strong>
        </div>
        <button class="btn-primary btn-full" id="checkoutBtn" disabled>Commander</button>
    </div>
</aside>

<!-- Checkout Modal -->
<div class="modal-overlay" id="checkoutOverlay"></div>
<div class="modal" id="checkoutModal">
    <div class="modal-head">
        <h3>Finaliser la commande</h3>
        <button class="modal-close" id="checkoutClose">&times;</button>
    </div>
    <form id="checkoutForm" class="checkout-form">
        <div class="form-group">
            <label for="cName">Nom complet *</label>
            <input type="text" id="cName" required maxlength="120" placeholder="Votre nom">
        </div>
        <div class="form-group">
            <label for="cPhone">Telephone *</label>
            <input type="tel" id="cPhone" required maxlength="30" placeholder="+216 XX XXX XXX">
        </div>
        <div class="form-group">
            <label for="cCity">Ville *</label>
            <input type="text" id="cCity" required maxlength="80" placeholder="Tunis, Sousse, Sfax...">
        </div>
        <div class="form-group">
            <label for="cAddress">Adresse *</label>
            <input type="text" id="cAddress" required maxlength="255" placeholder="Rue, quartier, code postal">
        </div>
        <div class="form-group">
            <label for="cNotes">Notes (optionnel)</label>
            <textarea id="cNotes" maxlength="500" placeholder="Instructions de livraison..."></textarea>
        </div>
        <button type="submit" class="btn-primary btn-full" id="submitOrder">Confirmer la commande</button>
    </form>
</div>

<!-- Order Confirmation -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon">✓</div>
        <h2>Commande confirmee !</h2>
        <p class="confirm-ref">Reference : <strong id="confirmRef">T216-XXXXXXXX</strong></p>
        <p class="confirm-total">Total : <strong id="confirmTotal">0 DT</strong></p>
        <p class="confirm-msg">Merci ! Nous vous contacterons bientot pour confirmer la livraison.</p>
        <div class="confirm-items" id="confirmItems"></div>
        <button class="btn-primary" id="confirmOk">Continuer le shopping</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
