<?php
/**
 * setup.php — Assistant de configuration initiale
 * Créez la base de données dans phpMyAdmin, puis ouvrez cette page.
 * Elle crée la table `admins` et le premier compte administrateur.
 */

// Charger le .env
(function () {
    $env_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($env_file)) return;
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key); $val = trim($val);
        if (preg_match('/^(["\'])(.*)\1$/', $val, $m)) $val = $m[2];
        if ($key && !defined($key)) define($key, $val);
    }
})();

// Valeurs par défaut si .env absent
defined('DB_HOST')    || define('DB_HOST',    '127.0.0.1');
defined('DB_NAME')    || define('DB_NAME',    'malika_db');
defined('DB_USER')    || define('DB_USER',    'root');
defined('DB_PASS')    || define('DB_PASS',    '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');
defined('DB_PREFIX')  || define('DB_PREFIX',  'malikaenergeticienne_');

$step    = 'form';
$success = '';
$error   = '';

// Tentative de connexion
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );

    // Créer la base si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    // Créer la table avec préfixe
    $tbl = DB_PREFIX . 'admins';
    $pdo->exec("CREATE TABLE IF NOT EXISTS `$tbl` (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        username      VARCHAR(50)  NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $count = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();

} catch (PDOException $e) {
    $error = 'Connexion MySQL impossible : ' . $e->getMessage();
    $count = 0;
    $pdo   = null;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $username  = trim($_POST['username']  ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$username || strlen($username) < 3) {
        $error = 'L\'identifiant doit faire au moins 3 caractères.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit faire au moins 8 caractères.';
    } elseif ($password !== $password2) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `$tbl` (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $step    = 'done';
            $success = $username;
        } catch (PDOException $e) {
            $error = 'Identifiant déjà utilisé ou erreur : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Configuration — Malika Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary: #8e7cc3; --primary-dark: #7a6bc0; }
        body { font-family: 'Open Sans', sans-serif; background: linear-gradient(135deg, #f0edf8, #faf9ff); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 8px 40px rgba(142,124,195,.15); padding: 50px 40px; width: 100%; max-width: 460px; }
        .brand { font-family: 'Playfair Display', serif; color: var(--primary); font-size: 1.4rem; text-align: center; margin-bottom: 6px; }
        .subtitle { text-align: center; color: #999; font-size: .85rem; margin-bottom: 32px; }
        .step-label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--primary); margin-bottom: 20px; }
        h2 { font-family: 'Playfair Display', serif; font-size: 1.3rem; margin-bottom: 20px; color: #2c2c2c; }
        label { display: block; font-size: .85rem; font-weight: 600; color: #555; margin-bottom: 6px; }
        input { width: 100%; padding: 12px 15px; border: 1.5px solid #e0e0e0; border-radius: 8px; font-size: 1rem; font-family: inherit; margin-bottom: 16px; transition: border-color .2s; }
        input:focus { outline: none; border-color: var(--primary); }
        button { width: 100%; padding: 13px; background: var(--primary); color: #fff; border: none; border-radius: 30px; font-size: 1rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background .2s; }
        button:hover { background: var(--primary-dark); }
        .alert { border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: .9rem; }
        .alert-error   { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }
        .alert-success { background: #eafaf1; color: #1e8449; border-left: 4px solid #27ae60; }
        .info-box { background: #f8f7fc; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; font-size: .85rem; color: #555; line-height: 1.6; }
        .info-box code { background: #e8e4f3; padding: 2px 6px; border-radius: 4px; font-size: .82rem; }
        .info-box strong { color: #2c2c2c; }
        .count-badge { display: inline-flex; align-items: center; gap: 6px; background: #eafaf1; color: #1e8449; padding: 6px 12px; border-radius: 20px; font-size: .82rem; font-weight: 600; margin-bottom: 20px; }
        a.btn-link { display: block; text-align: center; margin-top: 16px; color: var(--primary); font-weight: 600; }
        .done-icon { font-size: 3rem; text-align: center; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">Malika Énergéticienne</div>
    <div class="subtitle">Configuration de l'espace admin</div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 'done'): ?>
        <div class="done-icon">✅</div>
        <h2 style="text-align:center">Compte créé !</h2>
        <div class="alert alert-success">
            Le compte <strong><?= htmlspecialchars($success) ?></strong> a été créé avec succès.
        </div>
        <p style="text-align:center; color:#555; margin-bottom:20px; font-size:.9rem">
            Vous pouvez maintenant vous connecter à l'espace admin.<br>
            Supprimez ou protégez ce fichier <code>setup.php</code> après la configuration.
        </p>
        <a href="login.php" style="display:block; background:var(--primary); color:#fff; text-align:center; padding:13px; border-radius:30px; font-weight:600; text-decoration:none">
            Aller à la connexion →
        </a>

    <?php elseif (!$pdo): ?>
        <div class="info-box">
            <strong>Impossible de se connecter à MySQL.</strong><br>
            Vérifiez les paramètres dans <code>admin/db.php</code> et assurez-vous que WAMP est démarré.
        </div>

    <?php else: ?>
        <div class="step-label">Étape 1 — Base de données</div>
        <div class="alert alert-success">
            Base <code><?= DB_NAME ?></code> et table <code>admins</code> prêtes.
        </div>

        <?php if ($count > 0): ?>
            <div class="count-badge">
                ✓ <?= $count ?> compte<?= $count > 1 ? 's' : '' ?> administrateur<?= $count > 1 ? 's' : '' ?> existant<?= $count > 1 ? 's' : '' ?>
            </div>
        <?php endif; ?>

        <div class="step-label" style="margin-top:8px">Étape 2 — <?= $count > 0 ? 'Ajouter un compte' : 'Premier compte administrateur' ?></div>
        <form method="post">
            <label for="username">Identifiant</label>
            <input type="text" id="username" name="username" required minlength="3"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

            <label for="password">Mot de passe (min. 8 caractères)</label>
            <input type="password" id="password" name="password" required minlength="8">

            <label for="password2">Confirmer le mot de passe</label>
            <input type="password" id="password2" name="password2" required>

            <button type="submit">Créer le compte</button>
        </form>

        <?php if ($count > 0): ?>
            <a class="btn-link" href="login.php">← Retour à la connexion</a>
        <?php endif; ?>

        <div class="info-box" style="margin-top:20px">
            <strong>Paramètres actuels (.env) :</strong><br>
            Hôte : <code><?= DB_HOST ?></code> · Base : <code><?= DB_NAME ?></code> · Préfixe : <code><?= DB_PREFIX ?></code> · Utilisateur : <code><?= DB_USER ?></code>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
