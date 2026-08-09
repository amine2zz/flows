<?php
require_once 'config.php';
initDB();
startSession();

$user = getAuthUser();
$products = array(); $categories = array();
try {
    $rows = getDB()->query('SELECT id,slug,name,description,price,category,badge,color_hex,sizes,stock,featured FROM products WHERE active=1 ORDER BY sort_order ASC,id ASC')->fetchAll();
    foreach ($rows as $row) {
        $row['id']    = (int)$row['id'];
        $row['price'] = (float)$row['price'];
        $row['stock'] = (int)$row['stock'];
        $row['sizes'] = array_map('trim', explode(',', $row['sizes']));
        $products[] = $row;
        if (!in_array($row['category'], $categories, true)) $categories[] = $row['category'];
    }
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="description" content="THE216 — Streetwear tunisien. T-shirts, hoodies et accessoires livres partout en Tunisie.">
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
    <a href="#" class="nav-logo">THE<span>216</span></a>
    <div class="nav-links">
      <a href="#products">Boutique</a>
      <a href="#stats">Stats</a>
      <a href="#about">A propos</a>
    </div>
    <div class="nav-actions">
      <button class="nav-auth-btn" id="authBtn" onclick="openAuth()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span id="authBtnLabel"><?php echo $user ? htmlspecialchars($user['name']) : 'Connexion'; ?></span>
      </button>
      <button class="cart-btn" id="cartBtn" aria-label="Panier">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        <span class="cart-count" id="cartCount">0</span>
      </button>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-content">
    <div class="hero-eyebrow">Streetwear Tunisien</div>
    <h1 class="hero-title">THE<span>216</span></h1>
    <p class="hero-sub">Qualite premium. Designs uniques.<br>Livraison partout en Tunisie.</p>
    <a href="#products" class="btn-primary hero-cta">Decouvrir la collection</a>
  </div>
  <div class="hero-scroll"><span>Scroll</span><div class="scroll-line"></div></div>
</section>

<!-- Marquee -->
<div class="marquee">
  <div class="marquee-track">
    <?php for($i=0;$i<4;$i++): ?><span>THE216</span><span>•</span><span>TUNISIA</span><span>•</span><span>STREETWEAR</span><span>•</span><?php endfor; ?>
  </div>
</div>

<!-- Products -->
<section class="section" id="products">
  <div class="container">
    <div class="section-head">
      <span class="section-tag">Collection</span>
      <h2>Nos Produits</h2>
    </div>
    <?php if (!empty($products)): ?>
    <div class="filters">
      <button class="filter-btn active" data-cat="all">Tous</button>
      <?php foreach ($categories as $cat): ?>
      <button class="filter-btn" data-cat="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
      <?php endforeach; ?>
    </div>
    <div class="products-grid" id="productsGrid">
      <?php foreach ($products as $p): ?>
      <article class="product-card reveal" data-category="<?php echo htmlspecialchars($p['category']); ?>">
        <?php if ($p['badge']): ?><span class="product-badge"><?php echo htmlspecialchars($p['badge']); ?></span><?php endif; ?>
        <div class="product-visual" style="--pc:<?php echo htmlspecialchars($p['color_hex']); ?>">
          <div class="product-shirt"><span class="product-num">216</span></div>
        </div>
        <div class="product-info">
          <span class="product-cat"><?php echo htmlspecialchars($p['category']); ?></span>
          <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
          <p class="product-desc"><?php echo htmlspecialchars($p['description']); ?></p>
          <div class="product-footer">
            <span class="product-price"><?php echo number_format($p['price'],0); ?> DT</span>
            <button class="btn-add" data-product='<?php echo json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>Ajouter</button>
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

<!-- Stats -->
<section class="section section-dark" id="stats">
  <div class="container">
    <div class="section-head"><span class="section-tag">Live</span><h2>Statistiques</h2></div>
    <div class="stats-grid">
      <div class="stat-card reveal"><span class="stat-num" id="statOrders">0</span><span class="stat-label">Commandes</span></div>
      <div class="stat-card reveal"><span class="stat-num" id="statRevenue">0</span><span class="stat-label">Revenus (DT)</span></div>
      <div class="stat-card reveal"><span class="stat-num" id="statProducts"><?php echo count($products); ?></span><span class="stat-label">Produits</span></div>
    </div>
    <div class="charts-row">
      <div class="chart-box reveal"><h3>Ventes par categorie</h3><canvas id="categoryChart"></canvas></div>
      <div class="chart-box reveal"><h3>Top produits</h3><canvas id="productsChart"></canvas></div>
    </div>
  </div>
</section>

<!-- About -->
<section class="section" id="about">
  <div class="container about-grid">
    <div class="about-text reveal">
      <span class="section-tag">Notre histoire</span>
      <h2>Made in Tunisia</h2>
      <p>THE216 est ne de l'amour pour la culture tunisienne et le streetwear. Le 216, c'est le code pays qui nous unit.</p>
      <p>Qualite premium, designs uniques, livraison rapide. Paiement a la livraison disponible.</p>
      <div class="about-features">
        <div class="feat"><span>🚚</span> Livraison 24-72h</div>
        <div class="feat"><span>💳</span> Paiement a la livraison</div>
        <div class="feat"><span>✨</span> Qualite premium</div>
        <div class="feat"><span>🔒</span> Compte client securise</div>
      </div>
    </div>
    <div class="about-visual reveal">
      <div class="about-logo-text">THE<span>216</span></div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-brand">THE<span>216</span></div>
    <p>&copy; <?php echo date('Y'); ?> THE216 — Streetwear Tunisie</p>
    <p class="footer-dev">Developpe par <strong>Med Amine Ghariani</strong></p>
  </div>
</footer>

<!-- Cart Drawer -->
<div class="overlay" id="drawerOverlay"></div>
<aside class="cart-drawer" id="cartDrawer">
  <div class="drawer-head">
    <h3>Panier</h3>
    <button class="icon-btn" id="drawerClose">&times;</button>
  </div>
  <div class="drawer-body" id="cartItems"><p class="cart-empty">Votre panier est vide.</p></div>
  <div class="drawer-foot">
    <div class="cart-total-row"><span>Total</span><strong id="cartTotal">0 DT</strong></div>
    <button class="btn-primary btn-full" id="checkoutBtn" disabled>Commander</button>
  </div>
</aside>

<!-- Auth Modal -->
<div class="overlay" id="authModalOverlay"></div>
<div class="modal" id="authModal">
  <div class="modal-head">
    <div class="modal-tabs">
      <button class="modal-tab active" id="tabLogin" onclick="switchTab('login')">Connexion</button>
      <button class="modal-tab" id="tabRegister" onclick="switchTab('register')">Inscription</button>
    </div>
    <button class="icon-btn" onclick="closeAuth()">&times;</button>
  </div>
  <form id="loginForm" class="modal-form" onsubmit="doLogin(event)">
    <div class="form-group">
      <label>Email</label>
      <input type="email" id="lEmail" required placeholder="votre@email.com" autocomplete="email">
    </div>
    <div class="form-group">
      <label>Mot de passe</label>
      <input type="password" id="lPass" required placeholder="••••••••" autocomplete="current-password">
    </div>
    <button type="submit" class="btn-primary btn-full">Se connecter</button>
    <p class="form-switch">Pas de compte ? <button type="button" class="link-btn" onclick="switchTab('register')">S'inscrire</button></p>
    <div class="form-err" id="loginErr"></div>
  </form>
  <form id="registerForm" class="modal-form" style="display:none" onsubmit="doRegister(event)">
    <div class="form-group">
      <label>Nom complet</label>
      <input type="text" id="rName" required placeholder="Votre nom" autocomplete="name">
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" id="rEmail" required placeholder="votre@email.com" autocomplete="email">
    </div>
    <div class="form-group">
      <label>Telephone</label>
      <input type="tel" id="rPhone" required placeholder="+216 XX XXX XXX" autocomplete="tel">
    </div>
    <div class="form-group">
      <label>Mot de passe</label>
      <input type="password" id="rPass" required placeholder="6 caracteres minimum" autocomplete="new-password">
    </div>
    <button type="submit" class="btn-primary btn-full">Creer mon compte</button>
    <p class="form-switch">Deja un compte ? <button type="button" class="link-btn" onclick="switchTab('login')">Se connecter</button></p>
    <div class="form-err" id="registerErr"></div>
  </form>
</div>

<!-- User Dashboard Modal -->
<div class="overlay" id="userModalOverlay"></div>
<div class="modal" id="userModal">
  <div class="modal-head">
    <h3>Mon compte</h3>
    <button class="icon-btn" id="closeUserModal">&times;</button>
  </div>
  <div class="modal-form">
    <div class="user-info">
      <div class="user-avatar" id="userAvatar">A</div>
      <div><div class="user-name" id="userName">—</div><div class="user-email" id="userEmail">—</div></div>
    </div>
    <button class="btn-outline btn-full" onclick="loadMyOrders()">Mes commandes</button>
    <div id="myOrdersList"></div>
    <button class="btn-ghost btn-full" onclick="doLogout()" style="margin-top:.5rem">Deconnexion</button>
  </div>
</div>

<!-- Checkout Modal -->
<div class="overlay" id="checkoutModalOverlay"></div>
<div class="modal" id="checkoutModal">
  <div class="modal-head"><h3>Finaliser la commande</h3><button class="icon-btn" onclick="closeCheckout()">&times;</button></div>
  <form id="checkoutForm" class="modal-form" onsubmit="doOrder(event)">
    <div class="form-row">
      <div class="form-group">
        <label>Nom complet *</label>
        <input type="text" id="cName" required maxlength="120" placeholder="Votre nom">
      </div>
      <div class="form-group">
        <label>Telephone *</label>
        <input type="tel" id="cPhone" required maxlength="30" placeholder="+216 XX XXX XXX">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Ville *</label>
        <input type="text" id="cCity" required maxlength="80" placeholder="Tunis, Sousse...">
      </div>
      <div class="form-group">
        <label>Adresse *</label>
        <input type="text" id="cAddress" required maxlength="255" placeholder="Rue, quartier...">
      </div>
    </div>
    <div class="form-group">
      <label>Notes (optionnel)</label>
      <textarea id="cNotes" maxlength="500" placeholder="Instructions de livraison..."></textarea>
    </div>
    <button type="submit" class="btn-primary btn-full" id="submitOrder">Confirmer la commande</button>
    <div class="form-err" id="orderErr"></div>
  </form>
</div>

<!-- Order Confirmation -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <div class="confirm-icon">✓</div>
    <h2>Commande confirmee !</h2>
    <p class="confirm-ref">Reference : <strong id="confirmRef">—</strong></p>
    <p class="confirm-total">Total : <strong id="confirmTotal">—</strong></p>
    <p class="confirm-msg">Nous vous contacterons bientot pour confirmer la livraison.</p>
    <div class="confirm-items" id="confirmItems"></div>
    <button class="btn-primary" id="confirmOk">Continuer le shopping</button>
  </div>
</div>

<script>var USER_DATA = <?php echo $user ? json_encode(array('id'=>(int)$user['id'],'name'=>$user['name'],'email'=>$user['email'],'phone'=>$user['phone'],'city'=>$user['city'],'address'=>$user['address'],'role'=>$user['role'])) : 'null'; ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
