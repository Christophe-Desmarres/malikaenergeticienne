<?php
require_once 'config.php';
auth_start();

if (!empty($_SESSION['admin_logged'])) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    if ($user && $pass && auth_verify($user, $pass)) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user']   = $user;
        $_SESSION['csrf']         = bin2hex(random_bytes(32));
        header('Location: index.php'); exit;
    }
    $error = 'Identifiants incorrects.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion — Admin Malika</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary: #8e7cc3; --primary-dark: #7a6bc0; }
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #f0edf8 0%, #faf9ff 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(142,124,195,.15);
            padding: 50px 40px;
            width: 100%; max-width: 400px;
        }
        .brand {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            font-size: 1.4rem;
            text-align: center;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: #999;
            font-size: .85rem;
            margin-bottom: 32px;
        }
        label { display: block; font-size: .85rem; font-weight: 600; color: #555; margin-bottom: 6px; }
        input[type=text], input[type=password] {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            margin-bottom: 18px;
            transition: border-color .2s;
        }
        input:focus { outline: none; border-color: var(--primary); }
        button {
            width: 100%; padding: 13px;
            background: var(--primary); color: #fff;
            border: none; border-radius: 30px;
            font-size: 1rem; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: background .2s, transform .2s;
        }
        button:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .alert {
            background: #fdecea; color: #c0392b;
            border-radius: 8px; padding: 12px 15px;
            font-size: .9rem; margin-bottom: 20px;
        }
        .back { text-align: center; margin-top: 20px; }
        .back a { color: var(--primary); font-size: .85rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">Malika Énergéticienne</div>
    <div class="subtitle">Espace Administration</div>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
        <label for="user">Identifiant</label>
        <input type="text" id="user" name="user" required autocomplete="username"
               value="<?= htmlspecialchars($_POST['user'] ?? '') ?>">

        <label for="pass">Mot de passe</label>
        <input type="password" id="pass" name="pass" required autocomplete="current-password">

        <button type="submit">Se connecter</button>
    </form>

    <div class="back"><a href="../index.html">← Retour au site</a></div>
</div>
</body>
</html>
