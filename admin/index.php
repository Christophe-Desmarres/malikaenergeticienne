<?php
require_once 'config.php';
auth_require();

$articles = read_articles();
usort($articles, fn($a, $b) => strcmp($b['date'], $a['date']));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin — Actualités</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary: #8e7cc3; --primary-dark: #7a6bc0; --gray: #f5f5f5; --border: #e8e8e8; }
        body { font-family: 'Open Sans', sans-serif; background: #f8f7fc; color: #2c2c2c; min-height: 100vh; }

        /* Header */
        .admin-header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 30px;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px; position: sticky; top: 0; z-index: 10;
        }
        .admin-brand {
            font-family: 'Playfair Display', serif;
            color: var(--primary); font-size: 1.3rem;
        }
        .admin-brand span { font-family: 'Open Sans', sans-serif; font-size: .8rem; color: #999; margin-left: 10px; }
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .header-actions a {
            font-size: .875rem; color: #666; text-decoration: none;
            display: flex; align-items: center; gap: 6px; transition: color .2s;
        }
        .header-actions a:hover { color: var(--primary); }
        .header-actions a.btn-primary {
            background: var(--primary); color: #fff;
            padding: 8px 18px; border-radius: 20px; font-weight: 600;
        }
        .header-actions a.btn-primary:hover { background: var(--primary-dark); }

        /* Main */
        .main { max-width: 960px; margin: 40px auto; padding: 0 20px; }

        .page-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 24px; }

        /* Flash */
        .flash {
            padding: 14px 18px; border-radius: 8px;
            margin-bottom: 24px; font-size: .9rem;
        }
        .flash.success { background: #eafaf1; color: #1e8449; border-left: 4px solid #27ae60; }
        .flash.error   { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }

        /* Table */
        .table-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: var(--gray); text-align: left;
            padding: 14px 18px; font-size: .8rem; font-weight: 600;
            color: #888; text-transform: uppercase; letter-spacing: .04em;
        }
        tbody tr { border-bottom: 1px solid var(--border); }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fdfcff; }
        td { padding: 16px 18px; vertical-align: middle; }
        .td-title { font-weight: 600; font-size: .95rem; }
        .td-date { color: #888; font-size: .875rem; white-space: nowrap; }
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600;
        }
        .badge-pub   { background: #eafaf1; color: #1e8449; }
        .badge-draft { background: #f5f5f5;  color: #999; }
        .td-actions { white-space: nowrap; }
        .td-actions a {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 14px; border-radius: 6px; font-size: .82rem;
            text-decoration: none; margin-right: 6px; font-weight: 600; transition: opacity .2s;
        }
        .td-actions a:hover { opacity: .8; }
        .btn-edit    { background: #eef0ff; color: var(--primary); }
        .btn-hide    { background: #fff3e0; color: #d35400; }
        .btn-publish { background: #eafaf1; color: #1e8449; }
        .btn-delete  { background: #fdecea; color: #c0392b; }

        /* Empty state */
        .empty {
            text-align: center; padding: 60px 20px; color: #aaa;
        }
        .empty i { font-size: 3rem; margin-bottom: 16px; display: block; }
        .empty p { margin-bottom: 20px; }
        .empty a {
            background: var(--primary); color: #fff;
            padding: 10px 24px; border-radius: 20px;
            text-decoration: none; font-weight: 600; font-size: .9rem;
        }

        @media (max-width: 640px) {
            .td-date, thead th:nth-child(3), td:nth-child(3) { display: none; }
        }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="admin-brand">
        Malika Énergéticienne <span>/ Admin</span>
    </div>
    <div class="header-actions">
        <a href="../index.html" target="_blank"><i class="fas fa-external-link-alt"></i> Voir le site</a>
        <a href="users.php"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="form.php" class="btn-primary"><i class="fas fa-plus"></i> Nouvelle actualité</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</header>

<main class="main">
    <h1 class="page-title">Actualités</h1>

    <?php if ($flash): ?>
        <div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (empty($articles)): ?>
        <div class="table-wrap">
            <div class="empty">
                <i class="fas fa-newspaper"></i>
                <p>Aucune actualité pour le moment.</p>
                <a href="form.php"><i class="fas fa-plus"></i> Créer ma première actualité</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $a): ?>
                    <tr>
                        <td class="td-title"><?= htmlspecialchars($a['titre']) ?></td>
                        <td class="td-date"><?= format_date_fr($a['date']) ?></td>
                        <td>
                            <?php if (!empty($a['publie'])): ?>
                                <span class="badge badge-pub"><i class="fas fa-circle" style="font-size:.5rem"></i> Publié</span>
                            <?php else: ?>
                                <span class="badge badge-draft"><i class="fas fa-circle" style="font-size:.5rem"></i> Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-actions">
                            <a href="form.php?id=<?= (int)$a['id'] ?>" class="btn-edit">
                                <i class="fas fa-pen"></i> Éditer
                            </a>
                            <?php if (!empty($a['publie'])): ?>
                                <a href="toggle.php?id=<?= (int)$a['id'] ?>&csrf=<?= csrf_token() ?>"
                                   class="btn-hide"
                                   title="Masquer du site">
                                    <i class="fas fa-eye-slash"></i> Masquer
                                </a>
                            <?php else: ?>
                                <a href="toggle.php?id=<?= (int)$a['id'] ?>&csrf=<?= csrf_token() ?>"
                                   class="btn-publish"
                                   title="Publier sur le site">
                                    <i class="fas fa-eye"></i> Publier
                                </a>
                            <?php endif; ?>
                            <a href="delete.php?id=<?= (int)$a['id'] ?>&csrf=<?= csrf_token() ?>"
                               class="btn-delete"
                               onclick="return confirm('Supprimer définitivement cette actualité ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
