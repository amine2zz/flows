<?php
require_once 'config.php';
initDB();
startSession();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'me') {
    $u = getAuthUser();
    jsonResponse($u ? array('ok'=>true,'user'=>$u) : array('ok'=>false));
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse(array('ok'=>true));
}

if ($action === 'login') {
    $email    = trim(isset($_POST['email'])    ? $_POST['email']    : '');
    $password =      isset($_POST['password']) ? $_POST['password'] : '';
    if (!$email || !$password) jsonResponse(array('ok'=>false,'message'=>'Champs requis.'));
    try {
        $stmt = getDB()->prepare('SELECT id,name,email,phone,city,address,role,password FROM the216_users WHERE email=? LIMIT 1');
        $stmt->execute(array($email));
        $u = $stmt->fetch();
        if (!$u || !password_verify($password, $u['password']))
            jsonResponse(array('ok'=>false,'message'=>'Email ou mot de passe incorrect.'));
        $_SESSION['user_id'] = $u['id'];
        unset($u['password']);
        jsonResponse(array('ok'=>true,'user'=>$u));
    } catch (PDOException $e) { jsonResponse(array('ok'=>false,'message'=>'Erreur serveur.'),500); }
}

if ($action === 'register') {
    $name     = trim(isset($_POST['name'])     ? $_POST['name']     : '');
    $email    = trim(isset($_POST['email'])    ? $_POST['email']    : '');
    $phone    = trim(isset($_POST['phone'])    ? $_POST['phone']    : '');
    $password =      isset($_POST['password']) ? $_POST['password'] : '';
    if (!$name || !$email || !$phone || !$password)
        jsonResponse(array('ok'=>false,'message'=>'Tous les champs sont requis.'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(array('ok'=>false,'message'=>'Email invalide.'));
    if (strlen($password) < 6)
        jsonResponse(array('ok'=>false,'message'=>'Mot de passe: 6 caracteres minimum.'));
    try {
        $pdo = getDB();
        $chk = $pdo->prepare('SELECT id FROM the216_users WHERE email=? LIMIT 1');
        $chk->execute(array($email));
        if ($chk->fetch()) jsonResponse(array('ok'=>false,'message'=>'Email deja utilise.'));
        $ins = $pdo->prepare('INSERT INTO the216_users (name,email,phone,password) VALUES (?,?,?,?)');
        $ins->execute(array($name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)));
        $id = (int)$pdo->lastInsertId();
        $_SESSION['user_id'] = $id;
        jsonResponse(array('ok'=>true,'user'=>array('id'=>$id,'name'=>$name,'email'=>$email,'phone'=>$phone,'city'=>'','address'=>'','role'=>'customer')));
    } catch (PDOException $e) { jsonResponse(array('ok'=>false,'message'=>'Erreur serveur.'),500); }
}

jsonResponse(array('ok'=>false,'message'=>'Action inconnue.'), 400);
