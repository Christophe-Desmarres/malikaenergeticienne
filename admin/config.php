<?php
// Les identifiants admin sont désormais gérés dans la base de données.
// Lancez admin/setup.php pour créer la base et le premier compte.

define('DATA_FILE',   dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data'    . DIRECTORY_SEPARATOR . 'articles.json');
define('UPLOAD_DIR',  dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'  . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'articles' . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', 'assets/images/articles/'); // chemin URL relatif
define('SITE_URL',    'https://malikaenergeticienne.fr');

// ─── Session ─────────────────────────────────────────────────────────────────

function auth_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        session_name('malika_admin');
        session_start();
    }
}

function auth_require(): void {
    auth_start();
    if (empty($_SESSION['admin_logged'])) {
        header('Location: login.php');
        exit;
    }
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────

function csrf_token(): string {
    auth_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Vérifie le token POST — redirige vers le formulaire en cas d'échec
function csrf_check(): void {
    auth_start();
    $token_session = $_SESSION['csrf'] ?? '';
    $token_post    = $_POST['csrf']    ?? '';

    if ($token_session === '' || !hash_equals($token_session, $token_post)) {
        // Session expirée ou token manquant → on recharge la page
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Session expirée. Veuillez resoumettre le formulaire.'];
        $back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . $back);
        exit;
    }
}

// Vérifie le token GET (pour les liens de suppression)
function csrf_check_get(): void {
    auth_start();
    $token_session = $_SESSION['csrf'] ?? '';
    $token_get     = $_GET['csrf']     ?? '';

    if ($token_session === '' || !hash_equals($token_session, $token_get)) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token invalide. Veuillez réessayer.'];
        header('Location: index.php');
        exit;
    }
}

// ─── Dossier upload ───────────────────────────────────────────────────────────

function ensure_upload_dir(): bool {
    if (is_dir(UPLOAD_DIR)) return true;
    return mkdir(UPLOAD_DIR, 0755, true);
}

// ─── Articles ─────────────────────────────────────────────────────────────────

function read_articles(): array {
    if (!file_exists(DATA_FILE)) return [];
    return json_decode(file_get_contents(DATA_FILE), true) ?: [];
}

function write_articles(array $articles): void {
    file_put_contents(
        DATA_FILE,
        json_encode(array_values($articles), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

function find_article(array $articles, int $id): ?array {
    foreach ($articles as $a) {
        if ((int)$a['id'] === $id) return $a;
    }
    return null;
}

function delete_image_file(string $image_path): void {
    if ($image_path && strpos($image_path, UPLOAD_PATH) === 0) {
        $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image_path);
        if (file_exists($abs)) @unlink($abs);
    }
}

// ─── Compression image ────────────────────────────────────────────────────────
// Redimensionne à max 1200px de large et recompresse en JPEG 82%.
// Retourne le chemin final (peut changer d'extension si converti en jpg).
function compress_image(string $path, string $mime): string {
    if (!function_exists('imagecreatefromjpeg')) return $path; // GD absent

    $max_w = 1200;
    $max_h = 900;

    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
        case 'image/png':  $src = @imagecreatefrompng($path);  break;
        case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null; break;
        default: return $path;
    }

    if (!$src) return $path;

    $orig_w = imagesx($src);
    $orig_h = imagesy($src);

    // Calculer les nouvelles dimensions (ne jamais agrandir)
    $ratio = min($max_w / $orig_w, $max_h / $orig_h, 1.0);
    $new_w = (int)round($orig_w * $ratio);
    $new_h = (int)round($orig_h * $ratio);

    $dst = imagecreatetruecolor($new_w, $new_h);

    // Fond blanc pour PNG transparents convertis en JPEG
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
    unset($src);

    if ($mime === 'image/png') {
        imagepng($dst, $path, 8);
        unset($dst);
        return $path;
    }

    // Convertir tout le reste en JPEG
    $jpg_path = preg_replace('/\.[a-z]+$/i', '.jpg', $path);
    imagejpeg($dst, $jpg_path, 82);
    unset($dst);

    if ($jpg_path !== $path && file_exists($jpg_path)) {
        @unlink($path); // supprime l'original non-jpeg
        return $jpg_path;
    }

    return $path;
}

function format_date_fr(string $date): string {
    $ts = strtotime($date);
    if (!$ts) return $date;
    $months = ['','janvier','février','mars','avril','mai','juin',
               'juillet','août','septembre','octobre','novembre','décembre'];
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
