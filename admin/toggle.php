<?php
require_once 'config.php';
auth_require();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

csrf_check_get();

if (!$id) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Identifiant manquant.'];
    header('Location: index.php'); exit;
}

$articles = read_articles();
$found    = false;
$nouveau  = false;

foreach ($articles as &$a) {
    if ((int)$a['id'] === $id) {
        $a['publie'] = empty($a['publie']); // inverse le statut
        $nouveau     = $a['publie'];
        $found       = true;
        break;
    }
}
unset($a);

if ($found) {
    write_articles($articles);
    $msg = $nouveau
        ? 'Actualité publiée et visible sur le site.'
        : 'Actualité masquée (non visible sur le site).';
    $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Actualité introuvable.'];
}

header('Location: index.php');
exit;
