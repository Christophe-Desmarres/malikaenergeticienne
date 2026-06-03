<?php
require_once 'config.php';
auth_require();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

csrf_check_get(); // redirige vers index.php si token invalide

if (!$id) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Identifiant manquant.'];
    header('Location: index.php'); exit;
}

$articles = read_articles();
$target   = find_article($articles, $id);

if ($target) {
    delete_image_file($target['image'] ?? ''); // supprime le fichier image si uploadé
    $articles = array_filter($articles, fn($a) => (int)$a['id'] !== $id);
    write_articles(array_values($articles));
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Actualité supprimée.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Actualité introuvable.'];
}

header('Location: index.php');
exit;
