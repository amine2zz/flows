(function () {
    'use strict';

    var cart = JSON.parse(localStorage.getItem('the216_cart') || '[]');
    var categoryChart = null;
    var productsChart = null;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    var cartBtn       = document.getElementById('cartBtn');
    var cartCount     = document.getElementById('cartCount');
    var cartDrawer    = document.getElementById('cartDrawer');
    var drawerOverlay = document.getElementById('drawerOverlay');
    var drawerClose   = document.getElementById('drawerClose');
    var cartItems     = document.getElementById('cartItems');
    var cartTotal     = document.getElementById('cartTotal');
    var checkoutBtn   = document.getElementById('checkoutBtn');
    var checkoutModal = document.getElementById('checkoutModal');
    var checkoutOverlay = document.getElementById('checkoutOverlay');
    var checkoutClose = document.getElementById('checkoutClose');
    var checkoutForm  = document.getElementById('checkoutForm');
    var confirmOverlay = document.getElementById('confirmOverlay');
    var confirmOk     = document.getElementById('confirmOk');
    var nav           = document.getElementById('nav');

    // ── Nav scroll ──────────────────────────────────────────────────────────
    window.addEventListener('scroll', function () {
        if (nav) nav.classList.toggle('scrolled', window.scrollY > 40);
    });

    // ── Scroll reveal ─────────────────────────────────────────────────────────
    var revealEls = document.querySelectorAll('.product-card, .stat-card, .chart-box, .about-text, .about-visual');
    revealEls.forEach(function (el) { el.classList.add('reveal'); });
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('visible'); }
        });
    }, { threshold: 0.15 });
    revealEls.forEach(function (el) { observer.observe(el); });

    // ── Filters ───────────────────────────────────────────────────────────────
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

    // ── Add to cart ───────────────────────────────────────────────────────────
    document.querySelectorAll('.btn-add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.product-card');
            var picker = card.querySelector('.size-picker');
            if (picker.style.display === 'none') {
                picker.style.display = 'flex';
                btn.textContent = 'Choisir taille ↑';
                return;
            }
            var selected = picker.querySelector('.size-btn.selected');
            if (!selected) {
                picker.querySelector('.size-btn').classList.add('selected');
                selected = picker.querySelector('.size-btn.selected');
            }
            var product = JSON.parse(btn.getAttribute('data-product'));
            addToCart(product, selected.getAttribute('data-size'));
            btn.textContent = '✓ Ajoute';
            setTimeout(function () { btn.textContent = 'Ajouter'; }, 1500);
        });
    });

    document.querySelectorAll('.size-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.size-picker').querySelectorAll('.size-btn').forEach(function (b) { b.classList.remove('selected'); });
            btn.classList.add('selected');
        });
    });

    function addToCart(product, size) {
        var existing = cart.find(function (i) { return i.id === product.id && i.size === size; });
        if (existing) {
            existing.qty = Math.min(10, existing.qty + 1);
        } else {
            cart.push({ id: product.id, name: product.name, price: product.price, size: size, qty: 1 });
        }
        saveCart();
        renderCart();
        openDrawer();
    }

    function saveCart() {
        localStorage.setItem('the216_cart', JSON.stringify(cart));
    }

    function renderCart() {
        var total = 0;
        var count = 0;
        if (cart.length === 0) {
            cartItems.innerHTML = '<p class="cart-empty">Votre panier est vide.</p>';
            checkoutBtn.disabled = true;
        } else {
            cartItems.innerHTML = cart.map(function (item, idx) {
                var line = item.price * item.qty;
                total += line;
                count += item.qty;
                return '<div class="cart-item">' +
                    '<div class="cart-item-info">' +
                    '<div class="cart-item-name">' + esc(item.name) + '</div>' +
                    '<div class="cart-item-meta">Taille ' + esc(item.size) + ' × ' + item.qty + '</div>' +
                    '</div>' +
                    '<div class="cart-item-price">' + line.toFixed(0) + ' DT</div>' +
                    '<button class="cart-item-remove" data-idx="' + idx + '">✕</button>' +
                    '</div>';
            }).join('');
            checkoutBtn.disabled = false;
            cartItems.querySelectorAll('.cart-item-remove').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    cart.splice(parseInt(btn.getAttribute('data-idx')), 1);
                    saveCart();
                    renderCart();
                });
            });
        }
        cartCount.textContent = count;
        cartTotal.textContent = total.toFixed(0) + ' DT';
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ── Drawer ────────────────────────────────────────────────────────────────
    function openDrawer() {
        cartDrawer.classList.add('open');
        drawerOverlay.classList.add('open');
    }
    function closeDrawer() {
        cartDrawer.classList.remove('open');
        drawerOverlay.classList.remove('open');
    }
    cartBtn.addEventListener('click', openDrawer);
    drawerClose.addEventListener('click', closeDrawer);
    drawerOverlay.addEventListener('click', closeDrawer);

    // ── Checkout ──────────────────────────────────────────────────────────────
    checkoutBtn.addEventListener('click', function () {
        closeDrawer();
        checkoutModal.classList.add('open');
        checkoutOverlay.classList.add('open');
    });
    checkoutClose.addEventListener('click', closeCheckout);
    checkoutOverlay.addEventListener('click', closeCheckout);
    function closeCheckout() {
        checkoutModal.classList.remove('open');
        checkoutOverlay.classList.remove('open');
    }

    checkoutForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var submitBtn = document.getElementById('submitOrder');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';

        fetch('order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name:    document.getElementById('cName').value.trim(),
                phone:   document.getElementById('cPhone').value.trim(),
                address: document.getElementById('cAddress').value.trim(),
                city:    document.getElementById('cCity').value.trim(),
                notes:   document.getElementById('cNotes').value.trim(),
                items:   cart
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmer la commande';
            if (!data.success) {
                alert(data.message || 'Erreur.');
                return;
            }
            closeCheckout();
            showConfirmation(data);
            cart = [];
            saveCart();
            renderCart();
            checkoutForm.reset();
            loadStats();
        })
        .catch(function () {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmer la commande';
            alert('Erreur reseau. Reessayez.');
        });
    });

    function showConfirmation(data) {
        document.getElementById('confirmRef').textContent = data.order_ref;
        document.getElementById('confirmTotal').textContent = data.total.toFixed(0) + ' DT';
        var itemsEl = document.getElementById('confirmItems');
        if (data.items) {
            itemsEl.innerHTML = data.items.map(function (i) {
                return '<div>' + esc(i.product_name) + ' — ' + esc(i.size) + ' × ' + i.qty + ' (' + (i.unit_price * i.qty).toFixed(0) + ' DT)</div>';
            }).join('');
        }
        confirmOverlay.classList.add('open');
    }
    confirmOk.addEventListener('click', function () {
        confirmOverlay.classList.remove('open');
    });

    // ── Charts / Stats ────────────────────────────────────────────────────────
    function loadStats() {
        fetch('stats.php', { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) return;
            animateNum('statOrders', data.orders_count);
            animateNum('statRevenue', Math.round(data.revenue));
            renderCharts(data);
        })
        .catch(function () {});
    }

    function animateNum(id, target) {
        var el = document.getElementById(id);
        if (!el) return;
        var current = 0;
        var step = Math.max(1, Math.ceil(target / 40));
        var timer = setInterval(function () {
            current = Math.min(target, current + step);
            el.textContent = current;
            if (current >= target) clearInterval(timer);
        }, 30);
    }

    function renderCharts(data) {
        var catLabels = data.by_category.map(function (c) { return c.category; });
        var catData   = data.by_category.map(function (c) { return parseInt(c.sold) || 0; });
        var prodLabels = data.top_products.map(function (p) { return p.product_name; });
        var prodData   = data.top_products.map(function (p) { return parseInt(p.sold) || 0; });

        var colors = ['#3D2314', '#5C3317', '#8B5A2B', '#A0522D', '#6B3A2A', '#4A2C17'];

        if (categoryChart) categoryChart.destroy();
        var ctx1 = document.getElementById('categoryChart');
        if (ctx1) {
            categoryChart = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{ data: catData, backgroundColor: colors, borderWidth: 0 }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { color: '#888', font: { size: 11 } } } }
                }
            });
        }

        if (productsChart) productsChart.destroy();
        var ctx2 = document.getElementById('productsChart');
        if (ctx2) {
            productsChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: prodLabels.length ? prodLabels : ['Aucune vente'],
                    datasets: [{
                        label: 'Unites vendues',
                        data: prodData.length ? prodData : [0],
                        backgroundColor: '#3D2314',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#888', font: { size: 10 } }, grid: { display: false } },
                        y: { ticks: { color: '#888', stepSize: 1 }, grid: { color: 'rgba(61,35,20,.2)' } }
                    }
                }
            });
        }
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    renderCart();
    loadStats();

    // Stagger product card animations
    document.querySelectorAll('.product-card').forEach(function (card, i) {
        card.style.animationDelay = (i * 0.08) + 's';
    });

})();
