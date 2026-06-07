<?php
// ─── Chargement du fichier .env ───────────────────────────────────────────────
(function () {
    $env_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($env_file)) return;

    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Supprimer les guillemets éventuels
        if (preg_match('/^(["\'])(.*)\1$/', $val, $m)) $val = $m[2];
        if ($key && !defined($key)) define($key, $val);
        $_ENV[$key] = $val;
    }
})();

// ─── Constantes avec valeurs par défaut si .env absent ───────────────────────
defined('APP_ENV')    || define('APP_ENV',    'production');
// defined('APP_ENV')    || define('APP_ENV',    'development');
// defined('DB_HOST')    || define('DB_HOST',    'localhost');
// defined('DB_NAME')    || define('DB_NAME',    'malikavchrisdmar');
// defined('DB_USER')    || define('DB_USER',    'root');
// defined('DB_PASS')    || define('DB_PASS',    '');
// defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');
// defined('DB_PREFIX')  || define('DB_PREFIX',  'malikaenergeticienne_');

// ─── Affichage des erreurs PHP selon l'environnement ─────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

/** Retourne le nom complet d'une table (avec préfixe). */
function tbl(string $name): string {
    return DB_PREFIX . $name;
}

/** Retourne true si l'application tourne en mode développement. */
function is_dev(): bool {
    return APP_ENV === 'development';
}

/**
 * Retourne l'instance PDO partagée (singleton).
 * Affiche un message clair en cas d'erreur de connexion.
 */
function pdo(): PDO {
    static $instance = null;
    if ($instance === null) {
        try {
            $instance = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            
        } catch (PDOException $e) {
            http_response_code(500);
            die(
                '<style>body{font-family:sans-serif;padding:40px;background:#fdecea;color:#c0392b}</style>'
                . '<h2>❌ Connexion base de données échouée</h2>'
                . '<p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>'
                . '<p>Vérifiez le fichier <code>.env</code> à la racine du site,<br>'
                . 'puis ouvrez <a href="setup.php">setup.php</a> si la base n\'existe pas encore.</p>'
            );
        }
    }
    return $instance;
}

/**
 * Vérifie les identifiants d'un admin contre la base.
 */
function auth_verify(string $username, string $password): bool 
{
    try {
        $stmt = pdo()->prepare(
            'SELECT password_hash FROM `' . tbl('admins') . '` WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row && password_verify($password, $row['password_hash']);
    } catch (Exception $e) {
        return false;
    }
}
