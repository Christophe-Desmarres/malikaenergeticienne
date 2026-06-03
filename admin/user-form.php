<?php
require_once 'config.php';
require_once 'db.php';
auth_require();

$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;
$editing = $id !== null;
$errors  = [];
$user    = null;

if ($editing) {
    $stmt = pdo()->prepare('SELECT id, username FROM `' . tbl('admins') . '` WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Utilisateur introuvable.'];
        header('Location: users.php'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username  = trim($_POST['username']  ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($username) < 3) {
        $errors[] = 'L\'identifiant doit faire au moins 3 caractères.';
    }
    if (!$editing && strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
    }
    if ($password && strlen($password) < 8) {
        $errors[] = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
    }
    if ($password && $password !== $password2) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        try {
            if ($editing) {
                if ($password) {
                    $stmt = pdo()->prepare('UPDATE `' . tbl('admins') . '` SET username = ?, password_hash = ? WHERE id = ?');
                    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = pdo()->prepare('UPDATE `' . tbl('admins') . '` SET username = ? WHERE id = ?');
                    $stmt->execute([$username, $id]);
                }
                // Mettre à jour la session si c'est l'utilisateur courant
                if (($_SESSION['admin_user'] ?? '') === ($user['username'] ?? '')) {
                    $_SESSION['admin_user'] = $username;
                }
                $msg = 'Utilisateur mis à jour.';
            } else {
                $stmt = pdo()->prepare('INSERT INTO `' . tbl('admins') . '` (username, password_hash) VALUES (?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                $msg = 'Utilisateur « ' . $username . ' » créé.';
            }
            $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
            header('Location: users.php'); exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = 'Cet identifiant est déjà utilisé.';
            } else {
                $errors[] = 'Erreur base de données : ' . $e->getMessage();
            }
        }
    }
    $user = ['username' => $username];
}

$username_val = $user['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $editing ? 'Modifier' : 'Nouvel' ?> utilisateur — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary: #8e7cc3; --primary-dark: #7a6bc0; --border: #e8e8e8; }
        body { font-family: 'Open Sans', sans-serif; background: #f8f7fc; color: #2c2c2c; min-height: 100vh; }
        .admin-header { background: #fff; border-bottom: 1px solid var(--border); padding: 0 30px; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 10; }
        .admin-brand { font-family: 'Playfair Display', serif; color: var(--primary); font-size: 1.3rem; }
        .admin-brand span { font-size: .8rem; color: #999; margin-left: 10px; font-family: 'Open Sans', sans-serif; }
        .header-back a { color: #666; text-decoration: none; font-size: .875rem; display: flex; align-items: center; gap: 6px; }
        .header-back a:hover { color: var(--primary); }
        .main { max-width: 560px; margin: 40px auto; padding: 0 20px 60px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 28px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 36px; }
        .errors { background: #fdecea; color: #c0392b; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; font-size: .9rem; border-left: 4px solid #e74c3c; }
        .errors ul { padding-left: 18px; margin-top: 6px; }
        .form-group { margin-bottom: 22px; }
        label { display: block; font-size: .85rem; font-weight: 600; color: #555; margin-bottom: 7px; }
        input { width: 100%; padding: 12px 15px; border: 1.5px solid var(--border); border-radius: 8px; font-size: .95rem; font-family: inherit; color: #2c2c2c; background: #fdfcff; transition: border-color .2s, box-shadow .2s; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(142,124,195,.12); }
        .hint { font-size: .78rem; color: #aaa; margin-top: 5px; }
        .separator { border: none; border-top: 1px solid var(--border); margin: 24px 0; }
        .form-actions { display: flex; gap: 12px; }
        .btn-save { flex: 1; padding: 13px; background: var(--primary); color: #fff; border: none; border-radius: 30px; font-size: 1rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background .2s; }
        .btn-save:hover { background: var(--primary-dark); }
        .btn-cancel { padding: 13px 24px; background: #f0f0f0; color: #666; border-radius: 30px; text-decoration: none; font-size: .95rem; font-weight: 600; text-align: center; transition: background .2s; }
        .btn-cancel:hover { background: #e5e5e5; }
        .password-toggle { position: relative; }
        .password-toggle input { padding-right: 44px; }
        .toggle-eye { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa; font-size: .9rem; }
        .toggle-eye:hover { color: var(--primary); }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="admin-brand">Malika Énergéticienne <span>/ Utilisateurs</span></div>
    <div class="header-back">
        <a href="users.php"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
    </div>
</header>

<main class="main">
    <h1 class="page-title"><?= $editing ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' ?></h1>

    <div class="card">
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <strong>Veuillez corriger les erreurs :</strong>
                <ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label for="username">Identifiant <span style="color:#e74c3c">*</span></label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($username_val) ?>"
                       required minlength="3" maxlength="50" autocomplete="username">
            </div>

            <hr class="separator">

            <div class="form-group">
                <label for="password">
                    <?= $editing ? 'Nouveau mot de passe' : 'Mot de passe' ?>
                    <?= !$editing ? '<span style="color:#e74c3c">*</span>' : '' ?>
                </label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password"
                           <?= !$editing ? 'required' : '' ?> minlength="8" autocomplete="new-password">
                    <i class="fas fa-eye toggle-eye" onclick="togglePwd('password', this)"></i>
                </div>
                <?php if ($editing): ?>
                    <div class="hint">Laissez vide pour conserver le mot de passe actuel.</div>
                <?php else: ?>
                    <div class="hint">Minimum 8 caractères.</div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password2">Confirmer le mot de passe <?= !$editing ? '<span style="color:#e74c3c">*</span>' : '' ?></label>
                <div class="password-toggle">
                    <input type="password" id="password2" name="password2"
                           <?= !$editing ? 'required' : '' ?> minlength="8" autocomplete="new-password">
                    <i class="fas fa-eye toggle-eye" onclick="togglePwd('password2', this)"></i>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <?= $editing ? 'Enregistrer' : 'Créer le compte' ?>
                </button>
                <a href="users.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
</main>

<script>
function togglePwd(inputId, icon) {
    var input = document.getElementById(inputId);
    var show  = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !show);
    icon.classList.toggle('fa-eye-slash', show);
}
</script>
</body>
</html>
