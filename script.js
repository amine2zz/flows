(function () {
'use strict';

var cart = JSON.parse(localStorage.getItem('the216_cart') || '[]');
var currentUser = USER_DATA || null;
var categoryChart = null;
var productsChart = null;

// ── DOM ───────────────────────────────────────────────────────────────────────
var nav            = document.getElementById('nav');
var cartBtn        = document.getElementById('cartBtn');
var cartCountEl    = document.getElementById('cartCount');
var cartDrawer     = document.getElementById('cartDrawer');
var drawerOverlay  = document.getElementById('drawerOverlay');
var drawerClose    = document.getElementById('drawerClose');
var cartItemsEl    = document.getElementById('cartItems');
var cartTotalEl    = document.getElementById('cartTotal');
var checkoutBtn    = document.getElementById('checkoutBtn');
var authBtn        = document.getElementById('authBtn');
var authBtnLabel   = document.getElementById('authBtnLabel');

// ── Nav scroll ────────────────────────────────────────────────────────────────
window.addEventListener('scroll', function () {
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 40);
});

// ── Scroll reveal ─────────────────────────────────────────────────────────────
var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });

// ── Filters ───────────────────────────────────────────────────────────────────
document.querySelectorAll('.filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var cat = btn.getAttribute('data-cat');
        document.querySelectorAll('.product-card').forEach(function (card) {
            card.style.display = (cat === 'all' || card.getAttribute('data-category') === cat) ? '' : 'none';
        });
    });
});

// ── Cart ──────────────────────────────────────────────────────────────────────
function saveCart() { localStorage.setItem('the216_cart', JSON.stringify(cart)); }

function renderCart() {
    var total = 0, count = 0;
    if (!cart.length) {
        cartItemsEl.innerHTML = '<p class="cart-empty">Votre panier est vide.</p>';
        checkoutBtn.disabled = true;
    } else {
        cartItemsEl.innerHTML = cart.map(function (item, idx) {
            var line = item.price * item.qty;
            total += line; count += item.qty;
            return '<div class="cart-item">' +
                '<div class="cart-item-color" style="background:' + (item.color || '#3D2314') + '"></div>' +
                '<div class="cart-item-info"><div class="cart-item-name">' + esc(item.name) + '</div>' +
                '<div class="cart-item-meta">Taille ' + esc(item.size) + ' &times; ' + item.qty + '</div></div>' +
                '<div class="cart-item-right"><span class="cart-item-price">' + line.toFixed(0) + ' DT</span>' +
                '<button class="cart-item-remove" data-idx="' + idx + '">&times;</button></div>' +
                '</div>';
        }).join('');
        checkoutBtn.disabled = false;
        cartItemsEl.querySelectorAll('.cart-item-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                cart.splice(parseInt(btn.getAttribute('data-idx')), 1);
                saveCart(); renderCart();
            });
        });
    }
    cartCountEl.textContent = count;
    cartTotalEl.textContent = total.toFixed(0) + ' DT';
}

function addToCart(product, size) {
    var ex = cart.find(function (i) { return i.id === product.id && i.size === size; });
    if (ex) { ex.qty = Math.min(10, ex.qty + 1); }
    else cart.push({ id: product.id, name: product.name, price: product.price, size: size, qty: 1, color: product.color_hex });
    saveCart(); renderCart(); openDrawer();
}

// ── Add to cart buttons ───────────────────────────────────────────────────────
document.querySelectorAll('.btn-add').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var card   = btn.closest('.product-card');
        var picker = card.querySelector('.size-picker');
        if (picker.style.display === 'none') {
            picker.style.display = 'flex';
            btn.textContent = 'Choisir taille ↑';
            return;
        }
        var sel = picker.querySelector('.size-btn.selected') || picker.querySelector('.size-btn');
        if (sel) sel.classList.add('selected');
        var product = JSON.parse(btn.getAttribute('data-product'));
        addToCart(product, sel.getAttribute('data-size'));
        btn.textContent = '✓ Ajoute';
        setTimeout(function () { btn.textContent = 'Ajouter'; picker.style.display = 'none'; }, 1500);
    });
});
document.querySelectorAll('.size-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        btn.closest('.size-picker').querySelectorAll('.size-btn').forEach(function (b) { b.classList.remove('selected'); });
        btn.classList.add('selected');
    });
});

// ── Drawer ────────────────────────────────────────────────────────────────────
function openDrawer()  { cartDrawer.classList.add('open'); drawerOverlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeDrawer() { cartDrawer.classList.remove('open'); drawerOverlay.classList.remove('open'); document.body.style.overflow = ''; }
cartBtn.addEventListener('click', openDrawer);
drawerClose.addEventListener('click', closeDrawer);
drawerOverlay.addEventListener('click', closeDrawer);

// ── Auth modal ────────────────────────────────────────────────────────────────
function updateAuthBtn() {
    if (currentUser) {
        authBtnLabel.textContent = currentUser.name.split(' ')[0];
        authBtn.classList.add('logged-in');
    } else {
        authBtnLabel.textContent = 'Connexion';
        authBtn.classList.remove('logged-in');
    }
}

window.openAuth = function () {
    if (currentUser) {
        openModal('userModal');
        renderUserDash();
    } else {
        openModal('authModal');
        switchTab('login');
    }
};
window.closeAuth = function () { closeModal('authModal'); };

window.switchTab = function (tab) {
    document.getElementById('loginForm').style.display   = tab === 'login'    ? '' : 'none';
    document.getElementById('registerForm').style.display = tab === 'register' ? '' : 'none';
    document.getElementById('tabLogin').classList.toggle('active',    tab === 'login');
    document.getElementById('tabRegister').classList.toggle('active', tab === 'register');
};

window.doLogin = function (e) {
    e.preventDefault();
    var err = document.getElementById('loginErr');
    err.textContent = '';
    var btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Connexion...';
    var fd = new FormData();
    fd.append('email',    document.getElementById('lEmail').value.trim());
    fd.append('password', document.getElementById('lPass').value);
    fetch('auth.php?action=login', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        btn.disabled = false; btn.textContent = 'Se connecter';
        if (!d.ok) { err.textContent = d.message; return; }
        currentUser = d.user;
        updateAuthBtn();
        closeModal('authModal');
        openModal('userModal');
        renderUserDash();
    })
    .catch(function () { btn.disabled = false; btn.textContent = 'Se connecter'; err.textContent = 'Erreur reseau.'; });
};

window.doRegister = function (e) {
    e.preventDefault();
    var err = document.getElementById('registerErr');
    err.textContent = '';
    var btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Inscription...';
    var fd = new FormData();
    fd.append('name',     document.getElementById('rName').value.trim());
    fd.append('email',    document.getElementById('rEmail').value.trim());
    fd.append('phone',    document.getElementById('rPhone').value.trim());
    fd.append('password', document.getElementById('rPass').value);
    fetch('auth.php?action=register', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        btn.disabled = false; btn.textContent = 'Creer mon compte';
        if (!d.ok) { err.textContent = d.message; return; }
        currentUser = d.user;
        updateAuthBtn();
        closeModal('authModal');
        openModal('userModal');
        renderUserDash();
    })
    .catch(function () { btn.disabled = false; btn.textContent = 'Creer mon compte'; err.textContent = 'Erreur reseau.'; });
};

window.doLogout = function () {
    fetch('auth.php?action=logout').then(function () {
        currentUser = null;
        updateAuthBtn();
        closeModal('userModal');
    });
};

// ── User dashboard ────────────────────────────────────────────────────────────
function renderUserDash() {
    if (!currentUser) return;
    document.getElementById('userName').textContent  = currentUser.name;
    document.getElementById('userEmail').textContent = currentUser.email;
    document.getElementById('userAvatar').textContent = currentUser.name.charAt(0).toUpperCase();
}

window.loadMyOrders = function () {
    var el = document.getElementById('myOrdersList');
    el.innerHTML = '<p class="orders-loading">Chargement...</p>';
    fetch('track.php?action=my')
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (!d.ok || !d.orders.length) { el.innerHTML = '<p class="orders-empty">Aucune commande.</p>'; return; }
        el.innerHTML = d.orders.map(function (o) {
            var statusClass = { pending:'s-pending', confirmed:'s-confirmed', shipped:'s-shipped', delivered:'s-delivered', cancelled:'s-cancelled' }[o.status] || '';
            return '<div class="order-card">' +
                '<div class="order-card-head">' +
                '<span class="order-ref">' + esc(o.order_ref) + '</span>' +
                '<span class="order-status ' + statusClass + '">' + esc(o.status_label) + '</span>' +
                '</div>' +
                '<div class="order-card-body">' +
                o.items.map(function (i) { return '<span>' + esc(i.product_name) + ' (' + esc(i.size) + ') &times;' + i.qty + '</span>'; }).join('') +
                '</div>' +
                '<div class="order-card-foot">' +
                '<span class="order-total">' + parseFloat(o.total).toFixed(0) + ' DT</span>' +
                '<span class="order-date">' + o.created_at.substring(0,10) + '</span>' +
                '</div></div>';
        }).join('');
    })
    .catch(function () { el.innerHTML = '<p class="orders-empty">Erreur reseau.</p>'; });
};

// ── Checkout ──────────────────────────────────────────────────────────────────
checkoutBtn.addEventListener('click', function () {
    closeDrawer();
    if (currentUser) {
        document.getElementById('cName').value    = currentUser.name  || '';
        document.getElementById('cPhone').value   = currentUser.phone || '';
        document.getElementById('cCity').value    = currentUser.city  || '';
        document.getElementById('cAddress').value = currentUser.address || '';
    }
    openModal('checkoutModal');
});

window.closeCheckout = function () { closeModal('checkoutModal'); };

window.doOrder = function (e) {
    e.preventDefault();
    var err = document.getElementById('orderErr');
    err.textContent = '';
    var btn = document.getElementById('submitOrder');
    btn.disabled = true; btn.textContent = 'Envoi...';
    fetch('order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name:    document.getElementById('cName').value.trim(),
            phone:   document.getElementById('cPhone').value.trim(),
            city:    document.getElementById('cCity').value.trim(),
            address: document.getElementById('cAddress').value.trim(),
            notes:   document.getElementById('cNotes').value.trim(),
            items:   cart
        })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        btn.disabled = false; btn.textContent = 'Confirmer la commande';
        if (!d.success) { err.textContent = d.message || 'Erreur.'; return; }
        closeModal('checkoutModal');
        showConfirmation(d);
        cart = []; saveCart(); renderCart();
        document.getElementById('checkoutForm').reset();
        loadStats();
    })
    .catch(function () { btn.disabled = false; btn.textContent = 'Confirmer la commande'; err.textContent = 'Erreur reseau.'; });
};

function showConfirmation(data) {
    document.getElementById('confirmRef').textContent   = data.order_ref;
    document.getElementById('confirmTotal').textContent = parseFloat(data.total).toFixed(0) + ' DT';
    var el = document.getElementById('confirmItems');
    el.innerHTML = (data.items || []).map(function (i) {
        return '<div>' + esc(i.product_name) + ' — ' + esc(i.size) + ' &times;' + i.qty + ' <strong>' + (i.unit_price * i.qty).toFixed(0) + ' DT</strong></div>';
    }).join('');
    document.getElementById('confirmOverlay').classList.add('open');
}
document.getElementById('confirmOk').addEventListener('click', function () {
    document.getElementById('confirmOverlay').classList.remove('open');
});

// ── Modal helpers ─────────────────────────────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.getElementById(id + 'Overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.getElementById(id + 'Overlay').classList.remove('open');
    document.body.style.overflow = '';
}
['authModal','checkoutModal','userModal'].forEach(function (id) {
    var ov = document.getElementById(id + 'Overlay');
    if (ov) ov.addEventListener('click', function () { closeModal(id); });
});
document.getElementById('closeUserModal').addEventListener('click', function () { closeModal('userModal'); });

// ── Charts ────────────────────────────────────────────────────────────────────
function loadStats() {
    fetch('stats.php', { cache: 'no-store' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (d.error) return;
        animateNum('statOrders',  d.orders_count);
        animateNum('statRevenue', Math.round(d.revenue));
        renderCharts(d);
    }).catch(function () {});
}

function animateNum(id, target) {
    var el = document.getElementById(id);
    if (!el) return;
    var cur = 0, step = Math.max(1, Math.ceil(target / 40));
    var t = setInterval(function () {
        cur = Math.min(target, cur + step);
        el.textContent = cur;
        if (cur >= target) clearInterval(t);
    }, 30);
}

function renderCharts(data) {
    var colors = ['#3D2314','#5C3317','#8B5A2B','#A0522D','#6B3A2A','#4A2C17'];
    if (categoryChart) categoryChart.destroy();
    var c1 = document.getElementById('categoryChart');
    if (c1) categoryChart = new Chart(c1, {
        type: 'doughnut',
        data: { labels: data.by_category.map(function(c){return c.category;}), datasets: [{ data: data.by_category.map(function(c){return parseInt(c.sold)||0;}), backgroundColor: colors, borderWidth: 0 }] },
        options: { responsive: true, plugins: { legend: { labels: { color: '#888', font: { size: 11 } } } } }
    });
    if (productsChart) productsChart.destroy();
    var c2 = document.getElementById('productsChart');
    var pl = data.top_products.map(function(p){return p.product_name;});
    var pd = data.top_products.map(function(p){return parseInt(p.sold)||0;});
    if (c2) productsChart = new Chart(c2, {
        type: 'bar',
        data: { labels: pl.length ? pl : ['Aucune vente'], datasets: [{ data: pd.length ? pd : [0], backgroundColor: '#3D2314', borderRadius: 6 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { color:'#888', font:{size:10} }, grid:{display:false} }, y: { ticks:{color:'#888',stepSize:1}, grid:{color:'rgba(61,35,20,.2)'} } } }
    });
}

function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

// ── Init ──────────────────────────────────────────────────────────────────────
renderCart();
updateAuthBtn();
loadStats();
document.querySelectorAll('.product-card').forEach(function (c, i) { c.style.animationDelay = (i * 0.07) + 's'; });

})();
