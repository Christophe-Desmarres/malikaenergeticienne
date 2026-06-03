<?php
require_once 'config.php';
require_once 'db.php';
auth_require();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

csrf_check_get();

if (!$id) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Identifiant manquant.'];
    header('Location: users.php'); exit;
}

// Vérifier qu'il reste au moins 2 admins avant de supprimer
$total = (int)pdo()->query("SELECT COUNT(*) FROM `" . tbl('admins') . "`")->fetchColumn();

if ($total <= 1) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Impossible de supprimer le dernier administrateur.'];
    header('Location: users.php'); exit;
}

// Interdire de se supprimer soi-même
$stmt = pdo()->prepare("SELECT username FROM `" . tbl('admins') . "` WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if ($row && ($row['username'] === ($_SESSION['admin_user'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Vous ne pouvez pas supprimer votre propre compte.'];
    header('Location: users.php'); exit;
}

$stmt = pdo()->prepare("DELETE FROM `" . tbl('admins') . "` WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash'] = $stmt->rowCount()
    ? ['type' => 'success', 'msg' => 'Utilisateur supprimé.']
    : ['type' => 'error',   'msg' => 'Utilisateur introuvable.'];

header('Location: users.php');
exit;
