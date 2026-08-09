<?php
require_once 'config.php';
initDB();
startSession();

// Secret URL guard
if (!isset($_GET['k']) || $_GET['k'] !== ADMIN_PATH) {
    http_response_code(404); exit('Page introuvable.');
}

// Handle AJAX actions
if (!empty($_GET['action'])) {
    requireAdmin();
    $pdo = getDB();
    $action = $_GET['action'];

    if ($action === 'orders') {
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $sql = 'SELECT o.id,o.order_ref,o.customer_name,o.phone,o.city,o.total,o.status,o.created_at,u.email
                FROM orders o LEFT JOIN users u ON u.id=o.user_id';
        if ($status) $sql .= ' WHERE o.status='.getDB()->quote($status);
        $sql .= ' ORDER BY o.created_at DESC LIMIT 100';
        $rows = $pdo->query($sql)->fetchAll();
        foreach ($rows as &$r) $r['status_label'] = statusLabel($r['status']);
        jsonResponse(array('ok'=>true,'orders'=>$rows));
    }

    if ($action === 'order_items') {
        $id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
        $items = $pdo->prepare('SELECT product_name,size,qty,unit_price FROM order_items WHERE order_id=?');
        $items->execute(array($id));
        jsonResponse(array('ok'=>true,'items'=>$items->fetchAll()));
    }

    if ($action === 'update_order' && $_SERVER['REQUEST_METHOD']==='POST') {
        $id     = (int)(isset($_POST['id'])     ? $_POST['id']     : 0);
        $status =       isset($_POST['status']) ? $_POST['status'] : '';
        $allowed = array('pending','confirmed','shipped','delivered','cancelled');
        if (!$id || !in_array($status, $allowed, true)) jsonResponse(array('ok'=>false,'message'=>'Invalide.'));
        $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute(array($status,$id));
        jsonResponse(array('ok'=>true));
    }

    if ($action === 'users') {
        $rows = $pdo->query('SELECT id,name,email,phone,city,role,created_at FROM users ORDER BY created_at DESC LIMIT 200')->fetchAll();
        jsonResponse(array('ok'=>true,'users'=>$rows));
    }

    if ($action === 'stats') {
        $orders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status!='cancelled'")->fetchColumn();
        $revenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='cancelled'")->fetchColumn();
        $users   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
        jsonResponse(array('ok'=>true,'orders'=>$orders,'revenue'=>$revenue,'users'=>$users,'pending'=>$pending));
    }

    jsonResponse(array('ok'=>false,'message'=>'Action inconnue.'), 400);
}

$user = getAuthUser();
$isAdmin = $user && $user['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — THE216</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--black:#0a0a0a;--black2:#141414;--marron:#3D2314;--marron2:#5C3317;--cream:#F5F0EB;--gray:#888;--border:rgba(61,35,20,.35);--green:#22c55e;--red:#ef4444;--yellow:#f59e0b}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--black);color:var(--cream);min-height:100vh}
a{color:var(--cream);text-decoration:none}
input,select,textarea{background:var(--black2);border:1px solid var(--border);color:var(--cream);padding:.55rem .75rem;border-radius:8px;font-size:.875rem;width:100%;outline:none}
input:focus,select:focus{border-color:var(--marron2)}
button{cursor:pointer;font-family:inherit}
.btn{padding:.5rem 1.2rem;border-radius:8px;border:none;font-size:.8rem;font-weight:600;transition:.2s}
.btn-marron{background:var(--marron);color:#fff}.btn-marron:hover{background:var(--marron2)}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--gray)}.btn-ghost:hover{border-color:var(--marron);color:var(--cream)}
.btn-sm{padding:.3rem .75rem;font-size:.75rem}

/* Login */
#loginWrap{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem}
.login-box{background:var(--black2);border:1px solid var(--border);border-radius:16px;padding:2.5rem;width:100%;max-width:380px;text-align:center}
.login-box img{width:64px;border-radius:12px;margin-bottom:1rem}
.login-box h1{font-size:1.5rem;margin-bottom:.25rem}
.login-box p{color:var(--gray);font-size:.85rem;margin-bottom:1.5rem}
.login-box .form-g{text-align:left;margin-bottom:1rem}
.login-box label{font-size:.75rem;color:var(--gray);display:block;margin-bottom:.3rem}
.login-err{color:var(--red);font-size:.8rem;margin-top:.75rem;min-height:1rem}

/* Layout */
#adminWrap{display:none}
.sidebar{position:fixed;top:0;left:0;bottom:0;width:220px;background:var(--black2);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:1.5rem 1rem;gap:.25rem;z-index:10}
.sidebar-logo{display:flex;align-items:center;gap:.6rem;margin-bottom:1.5rem;padding:.5rem}
.sidebar-logo img{width:36px;border-radius:8px}
.sidebar-logo span{font-size:1.2rem;font-weight:800;letter-spacing:.05em}
.nav-item{display:flex;align-items:center;gap:.6rem;padding:.6rem .75rem;border-radius:8px;font-size:.85rem;color:var(--gray);transition:.15s;border:none;background:none;width:100%;text-align:left}
.nav-item:hover{background:rgba(61,35,20,.3);color:var(--cream)}
.nav-item.active{background:var(--marron);color:#fff}
.nav-item svg{width:16px;height:16px;flex-shrink:0}
.sidebar-foot{margin-top:auto}

.main{margin-left:220px;padding:2rem;min-height:100vh}
.page-title{font-size:1.5rem;font-weight:700;margin-bottom:1.5rem}

/* Stats cards */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem}
.scard{background:var(--black2);border:1px solid var(--border);border-radius:12px;padding:1.25rem}
.scard-num{font-size:2rem;font-weight:800;color:#fff}
.scard-label{font-size:.75rem;color:var(--gray);margin-top:.2rem}
.scard-pending .scard-num{color:var(--yellow)}

/* Table */
.table-wrap{background:var(--black2);border:1px solid var(--border);border-radius:12px;overflow:hidden}
.table-toolbar{display:flex;align-items:center;gap:.75rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border)}
.table-toolbar h3{font-size:.95rem;font-weight:700;flex:1}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{padding:.65rem 1rem;text-align:left;font-size:.7rem;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:.65rem 1rem;border-bottom:1px solid rgba(61,35,20,.15);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(61,35,20,.08)}

.badge{display:inline-block;padding:.2rem .6rem;border-radius:99px;font-size:.7rem;font-weight:700}
.b-pending{background:rgba(245,158,11,.15);color:var(--yellow)}
.b-confirmed{background:rgba(34,197,94,.15);color:var(--green)}
.b-shipped{background:rgba(59,130,246,.15);color:#60a5fa}
.b-delivered{background:rgba(34,197,94,.25);color:#4ade80}
.b-cancelled{background:rgba(239,68,68,.15);color:var(--red)}

select.status-sel{width:auto;padding:.25rem .5rem;font-size:.75rem;border-radius:6px}

/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:50;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-bg.open{display:flex}
.modal-box{background:var(--black2);border:1px solid var(--border);border-radius:14px;width:100%;max-width:500px;max-height:85vh;overflow-y:auto}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--border)}
.modal-head h3{font-size:.95rem;font-weight:700}
.modal-body{padding:1.25rem}
.item-row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(61,35,20,.15);font-size:.82rem}
.item-row:last-child{border-bottom:none}

@media(max-width:768px){
.sidebar{width:100%;height:auto;position:relative;flex-direction:row;flex-wrap:wrap;padding:.75rem}
.main{margin-left:0}
.stats-row{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<!-- Login -->
<div id="loginWrap">
  <div class="login-box">
    <img src="assets/logo.png" alt="THE216">
    <h1>Admin Panel</h1>
    <p>THE216 — Acces restreint</p>
    <div class="form-g"><label>Email</label><input type="email" id="aEmail" placeholder="admin@the216.tn"></div>
    <div class="form-g"><label>Mot de passe</label><input type="password" id="aPass" placeholder="••••••••"></div>
    <button class="btn btn-marron" style="width:100%" onclick="adminLogin()">Connexion</button>
    <div class="login-err" id="loginErr"></div>
  </div>
</div>

<!-- Admin UI -->
<div id="adminWrap">
  <nav class="sidebar">
    <div class="sidebar-logo">
      <img src="assets/logo.png" alt="THE216">
      <span>THE216</span>
    </div>
    <button class="nav-item active" onclick="showPage('dashboard')" id="nav-dashboard">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Dashboard
    </button>
    <button class="nav-item" onclick="showPage('orders')" id="nav-orders">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
      Commandes
    </button>
    <button class="nav-item" onclick="showPage('users')" id="nav-users">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Clients
    </button>
    <div class="sidebar-foot">
      <button class="nav-item" onclick="adminLogout()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Deconnexion
      </button>
    </div>
  </nav>

  <main class="main">
    <!-- Dashboard -->
    <div id="page-dashboard">
      <div class="page-title">Dashboard</div>
      <div class="stats-row">
        <div class="scard"><div class="scard-num" id="ds-orders">—</div><div class="scard-label">Commandes totales</div></div>
        <div class="scard scard-pending"><div class="scard-num" id="ds-pending">—</div><div class="scard-label">En attente</div></div>
        <div class="scard"><div class="scard-num" id="ds-revenue">—</div><div class="scard-label">Revenus (DT)</div></div>
        <div class="scard"><div class="scard-num" id="ds-users">—</div><div class="scard-label">Clients</div></div>
      </div>
    </div>

    <!-- Orders -->
    <div id="page-orders" style="display:none">
      <div class="page-title">Commandes</div>
      <div class="table-wrap">
        <div class="table-toolbar">
          <h3>Toutes les commandes</h3>
          <select id="filterStatus" onchange="loadOrders()" style="width:auto">
            <option value="">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="confirmed">Confirme</option>
            <option value="shipped">Expedie</option>
            <option value="delivered">Livre</option>
            <option value="cancelled">Annule</option>
          </select>
        </div>
        <div style="overflow-x:auto"><table>
          <thead><tr><th>Ref</th><th>Client</th><th>Ville</th><th>Total</th><th>Statut</th><th>Date</th><th>Details</th></tr></thead>
          <tbody id="ordersBody"><tr><td colspan="7" style="text-align:center;color:var(--gray);padding:2rem">Chargement...</td></tr></tbody>
        </table></div>
      </div>
    </div>

    <!-- Users -->
    <div id="page-users" style="display:none">
      <div class="page-title">Clients</div>
      <div class="table-wrap">
        <div class="table-toolbar"><h3>Comptes clients</h3></div>
        <div style="overflow-x:auto"><table>
          <thead><tr><th>Nom</th><th>Email</th><th>Telephone</th><th>Ville</th><th>Role</th><th>Inscription</th></tr></thead>
          <tbody id="usersBody"><tr><td colspan="6" style="text-align:center;color:var(--gray);padding:2rem">Chargement...</td></tr></tbody>
        </table></div>
      </div>
    </div>
  </main>
</div>

<!-- Items Modal -->
<div class="modal-bg" id="itemsModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="itemsModalTitle">Articles</h3>
      <button class="btn btn-ghost btn-sm" onclick="closeItemsModal()">Fermer</button>
    </div>
    <div class="modal-body" id="itemsModalBody"></div>
  </div>
</div>

<script>
var ADMIN_KEY = '<?php echo ADMIN_PATH; ?>';

function adminLogin() {
    var email = document.getElementById('aEmail').value.trim();
    var pass  = document.getElementById('aPass').value;
    var err   = document.getElementById('loginErr');
    err.textContent = '';
    var fd = new FormData();
    fd.append('email', email); fd.append('password', pass);
    fetch('auth.php?action=login', { method:'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok || d.user.role !== 'admin') { err.textContent = d.message || 'Acces refuse.'; return; }
        document.getElementById('loginWrap').style.display = 'none';
        document.getElementById('adminWrap').style.display = 'block';
        loadDashboard();
    })
    .catch(function(){ err.textContent = 'Erreur reseau.'; });
}

document.getElementById('aPass').addEventListener('keydown', function(e){ if(e.key==='Enter') adminLogin(); });

function adminLogout() {
    fetch('auth.php?action=logout').then(function(){ location.reload(); });
}

function showPage(name) {
    ['dashboard','orders','users'].forEach(function(p){
        document.getElementById('page-'+p).style.display = p===name ? '' : 'none';
        document.getElementById('nav-'+p).classList.toggle('active', p===name);
    });
    if (name==='orders') loadOrders();
    if (name==='users')  loadUsers();
}

function loadDashboard() {
    fetch('admin.php?k='+ADMIN_KEY+'&action=stats')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok) return;
        document.getElementById('ds-orders').textContent  = d.orders;
        document.getElementById('ds-pending').textContent = d.pending;
        document.getElementById('ds-revenue').textContent = Math.round(d.revenue);
        document.getElementById('ds-users').textContent   = d.users;
    });
}

function loadOrders() {
    var status = document.getElementById('filterStatus').value;
    fetch('admin.php?k='+ADMIN_KEY+'&action=orders&status='+encodeURIComponent(status))
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok) return;
        var tbody = document.getElementById('ordersBody');
        if (!d.orders.length) { tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:var(--gray);padding:2rem">Aucune commande.</td></tr>'; return; }
        tbody.innerHTML = d.orders.map(function(o){
            return '<tr>'+
                '<td><strong>'+o.order_ref+'</strong></td>'+
                '<td>'+esc(o.customer_name)+'<br><small style="color:var(--gray)">'+esc(o.email||'')+'</small></td>'+
                '<td>'+esc(o.city)+'</td>'+
                '<td><strong>'+parseFloat(o.total).toFixed(0)+' DT</strong></td>'+
                '<td><span class="badge b-'+o.status+'">'+o.status_label+'</span><br>'+
                '<select class="status-sel" onchange="updateStatus('+o.id+',this.value)">'+
                ['pending','confirmed','shipped','delivered','cancelled'].map(function(s){
                    return '<option value="'+s+'"'+(s===o.status?' selected':'')+'>'+s+'</option>';
                }).join('')+
                '</select></td>'+
                '<td style="color:var(--gray);font-size:.75rem">'+o.created_at.substring(0,16)+'</td>'+
                '<td><button class="btn btn-ghost btn-sm" onclick="showItems('+o.id+',\''+o.order_ref+'\')">Voir</button></td>'+
            '</tr>';
        }).join('');
    });
}

function updateStatus(id, status) {
    var fd = new FormData(); fd.append('id',id); fd.append('status',status);
    fetch('admin.php?k='+ADMIN_KEY+'&action=update_order', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if(!d.ok) alert('Erreur mise a jour.'); });
}

function showItems(id, ref) {
    document.getElementById('itemsModalTitle').textContent = 'Articles — '+ref;
    document.getElementById('itemsModalBody').innerHTML = '<p style="color:var(--gray)">Chargement...</p>';
    document.getElementById('itemsModal').classList.add('open');
    fetch('admin.php?k='+ADMIN_KEY+'&action=order_items&id='+id)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok || !d.items.length) { document.getElementById('itemsModalBody').innerHTML='<p style="color:var(--gray)">Aucun article.</p>'; return; }
        document.getElementById('itemsModalBody').innerHTML = d.items.map(function(i){
            return '<div class="item-row"><span>'+esc(i.product_name)+' — '+esc(i.size)+'</span><span>×'+i.qty+' &nbsp; <strong>'+parseFloat(i.unit_price*i.qty).toFixed(0)+' DT</strong></span></div>';
        }).join('');
    });
}

function closeItemsModal() { document.getElementById('itemsModal').classList.remove('open'); }
document.getElementById('itemsModal').addEventListener('click', function(e){ if(e.target===this) closeItemsModal(); });

function loadUsers() {
    fetch('admin.php?k='+ADMIN_KEY+'&action=users')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.ok) return;
        var tbody = document.getElementById('usersBody');
        if (!d.users.length) { tbody.innerHTML='<tr><td colspan="6" style="text-align:center;color:var(--gray);padding:2rem">Aucun client.</td></tr>'; return; }
        tbody.innerHTML = d.users.map(function(u){
            return '<tr>'+
                '<td><strong>'+esc(u.name)+'</strong></td>'+
                '<td>'+esc(u.email)+'</td>'+
                '<td>'+esc(u.phone)+'</td>'+
                '<td>'+esc(u.city||'—')+'</td>'+
                '<td><span class="badge '+(u.role==='admin'?'b-confirmed':'b-pending')+'">'+u.role+'</span></td>'+
                '<td style="color:var(--gray);font-size:.75rem">'+u.created_at.substring(0,10)+'</td>'+
            '</tr>';
        }).join('');
    });
}

function esc(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

// Check if already logged in
fetch('auth.php?action=me')
.then(function(r){ return r.json(); })
.then(function(d){
    if (d.ok && d.user && d.user.role==='admin') {
        document.getElementById('loginWrap').style.display='none';
        document.getElementById('adminWrap').style.display='block';
        loadDashboard();
    }
});
</script>
</body>
</html>
