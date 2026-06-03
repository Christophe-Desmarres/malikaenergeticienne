<?php
header('Content-Type: application/json; charset=utf-8');

$file = dirname(__DIR__) . '/data/articles.json';

if (!file_exists($file)) {
    echo isset($_GET['id']) ? 'null' : '[]'; exit;
}

$articles  = json_decode(file_get_contents($file), true) ?: [];
$published = array_values(array_filter($articles, fn($a) => !empty($a['publie'])));

// ?id=xxx → article unique
if (!empty($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $result = null;
    foreach ($published as $a) {
        if ((int)$a['id'] === $id) { $result = $a; break; }
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

usort($published, fn($a, $b) => strcmp($b['date'], $a['date']));

// ?all=1 → tous, sinon les 3 derniers
if (empty($_GET['all'])) {
    $limit     = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 3;
    $published = array_slice($published, 0, $limit);
}

echo json_encode($published, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
