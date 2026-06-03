<?php
require_once 'config.php';
require_once 'db.php';
auth_require();

$admins = pdo()->query("SELECT id, username, created_at FROM `" . tbl('admins') . "` ORDER BY created_at ASC")->fetchAll();
$total  = count($admins);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Utilisateurs — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary: #8e7cc3; --primary-dark: #7a6bc0; --gray: #f5f5f5; --border: #e8e8e8; }
        body { font-family: 'Open Sans', sans-serif; background: #f8f7fc; color: #2c2c2c; min-height: 100vh; }
        .admin-header { background: #fff; border-bottom: 1px solid var(--border); padding: 0 30px; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 10; }
        .admin-brand { font-family: 'Playfair Display', serif; color: var(--primary); font-size: 1.3rem; }
        .admin-brand span { font-family: 'Open Sans', sans-serif; font-size: .8rem; color: #999; margin-left: 10px; }
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .header-actions a { font-size: .875rem; color: #666; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: color .2s; }
        .header-actions a:hover { color: var(--primary); }
        .header-actions a.btn-primary { background: var(--primary); color: #fff; padding: 8px 18px; border-radius: 20px; font-weight: 600; }
        .header-actions a.btn-primary:hover { background: var(--primary-dark); color: #fff; }
        .main { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 24px; }
        .flash { padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: .9rem; }
        .flash.success { background: #eafaf1; color: #1e8449; border-left: 4px solid #27ae60; }
        .flash.error   { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }
        .table-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: var(--gray); text-align: left; padding: 14px 18px; font-size: .8rem; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: .04em; }
        tbody tr { border-bottom: 1px solid var(--border); }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fdfcff; }
        td { padding: 16px 18px; vertical-align: middle; }
        .td-user { font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; flex-shrink: 0; }
        .td-date { color: #888; font-size: .875rem; }
        .td-actions { white-space: nowrap; }
        .td-actions a { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 6px; font-size: .82rem; text-decoration: none; margin-right: 6px; font-weight: 600; transition: opacity .2s; }
        .td-actions a:hover { opacity: .8; }
        .btn-edit   { background: #eef0ff; color: var(--primary); }
        .btn-delete { background: #fdecea; color: #c0392b; }
        .btn-delete-disabled { background: #f5f5f5; color: #bbb; cursor: not-allowed; pointer-events: none; }
        .badge-you { background: var(--primary); color: #fff; font-size: .7rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="admin-brand">Malika Énergéticienne <span>/ Utilisateurs</span></div>
    <div class="header-actions">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Retour</a>
        <a href="user-form.php" class="btn-primary"><i class="fas fa-plus"></i> Nouvel utilisateur</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</header>

<main class="main">
    <h1 class="page-title">Administrateurs</h1>

    <?php if ($flash): ?>
        <div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Identifiant</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $u): ?>
                <?php
                    $isSelf = ($_SESSION['admin_user'] ?? '') === $u['username'];
                    $initial = mb_strtoupper(mb_substr($u['username'], 0, 1));
                ?>
                <tr>
                    <td>
                        <div class="td-user">
                            <div class="avatar"><?= htmlspecialchars($initial) ?></div>
                            <?= htmlspecialchars($u['username']) ?>
                            <?php if ($isSelf): ?>
                                <span class="badge-you">Vous</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="td-date"><?= date('d/m/Y à H:i', strtotime($u['created_at'])) ?></td>
                    <td class="td-actions">
                        <a href="user-form.php?id=<?= (int)$u['id'] ?>" class="btn-edit">
                            <i class="fas fa-pen"></i> Modifier
                        </a>
                        <?php if ($total > 1 && !$isSelf): ?>
                            <a href="user-delete.php?id=<?= (int)$u['id'] ?>&csrf=<?= csrf_token() ?>"
                               class="btn-delete"
                               onclick="return confirm('Supprimer l\'utilisateur « <?= htmlspecialchars(addslashes($u['username'])) ?> » ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        <?php else: ?>
                            <span class="td-actions">
                                <a class="btn-delete-disabled" title="<?= $isSelf ? 'Vous ne pouvez pas vous supprimer' : 'Dernier administrateur' ?>">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
