<?php
require_once 'config.php';
auth_require();

$articles = read_articles();
$id       = isset($_GET['id']) ? (int)$_GET['id'] : null;
$editing  = $id !== null;
$article  = $editing ? find_article($articles, $id) : null;
$errors   = [];

if ($editing && !$article) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Actualité introuvable.'];
    header('Location: index.php'); exit;
}

$debug = []; // journal de diagnostic — affiché après le formulaire

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $titre  = trim($_POST['titre'] ?? '');
    $date   = trim($_POST['date']  ?? '');

    // Sanitisation HTML du contenu Quill (garder uniquement les balises sûres)
    $resume_raw = $_POST['resume'] ?? '';
    $resume = strip_tags($resume_raw,
        '<p><strong><em><u><s><ul><ol><li><a><h2><h3><br><span><blockquote>'
    );
    $publie = !empty($_POST['publie']);

    // Image : conserver l'existante par défaut
    $image = ($article !== null && isset($article['image'])) ? $article['image'] : '';

    // Suppression demandée
    if (!empty($_POST['remove_image'])) {
        delete_image_file($image);
        $image = '';
        $debug[] = '🗑 Suppression image demandée.';
    }

    // ── Diagnostic PHP ───────────────────────────────────────────────────────
    $upload_error_codes = [
        0 => 'UPLOAD_ERR_OK',
        1 => 'UPLOAD_ERR_INI_SIZE',
        2 => 'UPLOAD_ERR_FORM_SIZE',
        3 => 'UPLOAD_ERR_PARTIAL',
        4 => 'UPLOAD_ERR_NO_FILE',
        6 => 'UPLOAD_ERR_NO_TMP_DIR',
        7 => 'UPLOAD_ERR_CANT_WRITE',
        8 => 'UPLOAD_ERR_EXTENSION',
    ];

    $content_length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $post_max       = (int)(ini_get('post_max_size')) * 1024 * 1024;
    $upload_max     = ini_get('upload_max_filesize');
    $file_uploads   = ini_get('file_uploads');

    $debug[] = '── Paramètres PHP ──';
    $debug[] = 'file_uploads = ' . ($file_uploads ? 'On' : 'OFF ⚠️');
    $debug[] = 'upload_max_filesize = ' . $upload_max;
    $debug[] = 'post_max_size = ' . ini_get('post_max_size');
    $debug[] = 'Content-Length reçu = ' . round($content_length / 1024, 1) . ' Ko';

    if ($content_length > 0 && $post_max > 0 && $content_length > $post_max) {
        $debug[] = '⚠️ CONTENT_LENGTH > post_max_size → $_FILES et $_POST sont VIDES !';
        $errors[] = 'Fichier trop lourd pour post_max_size (' . ini_get('post_max_size') . '). Réduisez l\'image.';
    }

    $debug[] = '── $_FILES ──';
    $upload_error = $_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $debug[] = 'isset($_FILES[image_file]) = ' . (isset($_FILES['image_file']) ? 'oui' : 'NON ⚠️');
    $debug[] = 'error code = ' . $upload_error . ' (' . ($upload_error_codes[$upload_error] ?? '?') . ')';
    $debug[] = 'tmp_name = ' . ($_FILES['image_file']['tmp_name'] ?? '—');
    $debug[] = 'size = ' . round(($_FILES['image_file']['size'] ?? 0) / 1024, 1) . ' Ko';
    $debug[] = 'name = ' . htmlspecialchars($_FILES['image_file']['name'] ?? '—');

    // ── Gestion upload ────────────────────────────────────────────────────────
    if ($upload_error === UPLOAD_ERR_NO_FILE) {
        $debug[] = '→ Aucun fichier sélectionné, image existante conservée.';

    } elseif ($upload_error !== UPLOAD_ERR_OK) {
        $php_errors = [
            UPLOAD_ERR_INI_SIZE   => 'Image trop lourde (upload_max_filesize = ' . $upload_max . ').',
            UPLOAD_ERR_FORM_SIZE  => 'Image trop lourde (limite du formulaire).',
            UPLOAD_ERR_PARTIAL    => 'Upload interrompu, réessayez.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire PHP introuvable (vérifiez php.ini → upload_tmp_dir).',
            UPLOAD_ERR_CANT_WRITE => 'Erreur écriture disque (dossier tmp non accessible).',
            UPLOAD_ERR_EXTENSION  => 'Upload bloqué par une extension PHP.',
        ];
        $msg = $php_errors[$upload_error] ?? 'Erreur PHP #' . $upload_error;
        $errors[] = $msg;
        $debug[] = '❌ Erreur PHP upload : ' . $msg;

    } else {
        $file_tmp  = $_FILES['image_file']['tmp_name'];
        $file_name = $_FILES['image_file']['name'];
        $file_size = $_FILES['image_file']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $is_uploaded = is_uploaded_file($file_tmp);
        $debug[] = '── Validation ──';
        $debug[] = 'Extension : ' . $file_ext;
        $debug[] = 'is_uploaded_file() = ' . ($is_uploaded ? 'oui' : 'NON ⚠️');

        $allowed_exts  = ['jpg','jpeg','png','webp','gif'];
        $allowed_mimes = ['image/jpeg','image/png','image/webp','image/gif'];

        if (class_exists('finfo')) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file_tmp);
        } else {
            $mime = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'][$file_ext] ?? '';
        }
        $debug[] = 'MIME détecté : ' . $mime;

        if (!in_array($file_ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $errors[] = 'Format non autorisé (JPG, PNG, WebP, GIF). Détecté : ' . $mime;
            $debug[] = '❌ MIME ou extension refusé.';
        } elseif ($file_size > 5 * 1024 * 1024) {
            $errors[] = 'Image trop lourde (max 5 Mo, reçu : ' . round($file_size/1024/1024, 2) . ' Mo).';
            $debug[] = '❌ Taille dépassée.';
        } else {
            $dir_ok = ensure_upload_dir();
            $debug[] = '── Dossier destination ──';
            $debug[] = 'Chemin : ' . UPLOAD_DIR;
            $debug[] = 'exists = ' . (is_dir(UPLOAD_DIR) ? 'oui' : 'NON ⚠️');
            $debug[] = 'writable = ' . (is_writable(UPLOAD_DIR) ? 'oui' : 'NON ⚠️');
            $debug[] = 'ensure_upload_dir() = ' . ($dir_ok ? 'ok' : 'ÉCHEC ⚠️');

            if (!$dir_ok) {
                $errors[] = 'Impossible de créer le dossier : ' . UPLOAD_DIR;
            } else {
                $new_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                $dest     = UPLOAD_DIR . $new_name;
                $debug[]  = 'Destination : ' . $dest;

                $moved = move_uploaded_file($file_tmp, $dest);
                $debug[] = 'move_uploaded_file() = ' . ($moved ? '✅ OK' : '❌ ÉCHEC');
                $debug[] = 'Fichier créé = ' . (file_exists($dest) ? 'oui' : 'non');

                if ($moved) {
                    delete_image_file($image);
                    // Compression + redimensionnement via GD
                    $final_path = compress_image($dest, $mime);
                    $final_name = basename($final_path);
                    if ($final_path !== $dest) {
                        $debug[] = '🗜 Converti en JPEG : ' . $final_name;
                    }
                    $image   = UPLOAD_PATH . $final_name;
                    $debug[] = '✅ Image enregistrée : ' . $image;
                    $debug[] = 'Taille finale : ' . round(filesize($final_path) / 1024, 1) . ' Ko';
                } else {
                    $errors[] = 'move_uploaded_file a échoué. Destination : ' . $dest;
                }
            }
        }
    }

    $debug[] = '── Résultat ──';
    $debug[] = '$image final = ' . ($image ?: '(vide)');
    $debug[] = 'Erreurs = ' . (empty($errors) ? 'aucune' : implode(' | ', $errors));

    if (!$titre) $errors[] = 'Le titre est obligatoire.';
    if (!trim(strip_tags($resume))) $errors[] = 'Le contenu est obligatoire.';
    if (!$date || !strtotime($date)) $errors[] = 'La date est invalide.';

    if (empty($errors)) {
        if ($editing) {
            foreach ($articles as &$a) {
                if ((int)$a['id'] === $id) {
                    $a['titre']  = $titre;
                    $a['resume'] = $resume;
                    $a['date']   = $date;
                    $a['image']  = $image;
                    $a['publie'] = $publie;
                    break;
                }
            }
            unset($a);
            $msg = 'Actualité mise à jour.';
        } else {
            $articles[] = [
                'id'     => time(),
                'titre'  => $titre,
                'resume' => $resume,
                'date'   => $date,
                'image'  => $image,
                'publie' => $publie,
            ];
            $msg = 'Actualité créée avec succès.';
        }
        write_articles($articles);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
        header('Location: index.php'); exit;
    }

    // Repopulate on error
    $article = compact('titre', 'resume', 'date', 'image', 'publie');
}

$titre  = $article['titre']  ?? '';
$resume = $article['resume'] ?? '';
$date   = $article['date']   ?? date('Y-m-d');
$image  = $article['image']  ?? '';
$publie = $article['publie'] ?? true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $editing ? 'Modifier' : 'Nouvelle' ?> actualité — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary: #8e7cc3; --primary-dark: #7a6bc0; --border: #e8e8e8; }
        body { font-family: 'Open Sans', sans-serif; background: #f8f7fc; color: #2c2c2c; min-height: 100vh; }

        .admin-header {
            background: #fff; border-bottom: 1px solid var(--border);
            padding: 0 30px; display: flex; align-items: center; justify-content: space-between;
            height: 64px; position: sticky; top: 0; z-index: 10;
        }
        .admin-brand { font-family: 'Playfair Display', serif; color: var(--primary); font-size: 1.3rem; }
        .admin-brand span { font-family: 'Open Sans', sans-serif; font-size: .8rem; color: #999; margin-left: 10px; }
        .header-back a {
            color: #666; text-decoration: none; font-size: .875rem;
            display: flex; align-items: center; gap: 6px;
        }
        .header-back a:hover { color: var(--primary); }

        .main { max-width: 680px; margin: 40px auto; padding: 0 20px 60px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 28px; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 36px; }

        .errors {
            background: #fdecea; color: #c0392b;
            border-radius: 8px; padding: 14px 18px; margin-bottom: 24px;
            font-size: .9rem; border-left: 4px solid #e74c3c;
        }
        .errors ul { padding-left: 18px; margin-top: 6px; }

        .form-group { margin-bottom: 22px; }
        label { display: block; font-size: .85rem; font-weight: 600; color: #555; margin-bottom: 7px; }
        .hint { font-size: .78rem; color: #aaa; margin-top: 4px; }
        input[type=text], input[type=url], input[type=date], textarea {
            width: 100%; padding: 12px 15px;
            border: 1.5px solid var(--border);
            border-radius: 8px; font-size: .95rem; font-family: inherit;
            color: #2c2c2c; background: #fdfcff;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(142,124,195,.12);
        }
        textarea { height: 130px; resize: vertical; line-height: 1.6; }

        .toggle-group {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 16px; background: #f8f7fc;
            border-radius: 8px; border: 1.5px solid var(--border);
        }
        .toggle-group input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; }
        .toggle-group label { margin: 0; cursor: pointer; font-size: .95rem; }

        .form-actions { display: flex; gap: 12px; margin-top: 8px; }
        .btn-save {
            flex: 1; padding: 13px;
            background: var(--primary); color: #fff;
            border: none; border-radius: 30px;
            font-size: 1rem; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: background .2s, transform .2s;
        }
        .btn-save:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-cancel {
            padding: 13px 24px; background: #f0f0f0; color: #666;
            border-radius: 30px; text-decoration: none;
            font-size: .95rem; font-weight: 600; text-align: center;
            transition: background .2s;
        }
        .btn-cancel:hover { background: #e5e5e5; }

        .char-count { font-size: .78rem; color: #aaa; text-align: right; margin-top: 4px; }
        .char-count.warn { color: #e67e22; }
        .char-count.over { color: #e74c3c; }

        /* ── Éditeur Quill ── */
        .quill-wrap {
            border: 1.5px solid var(--border); border-radius: 8px;
            overflow: hidden; background: #fdfcff;
            transition: border-color .2s, box-shadow .2s;
        }
        .quill-wrap:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(142,124,195,.12);
        }
        .quill-wrap .ql-toolbar {
            border: none !important;
            border-bottom: 1.5px solid var(--border) !important;
            background: #f8f7fc;
            font-family: 'Open Sans', sans-serif;
        }
        .quill-wrap .ql-container {
            border: none !important;
            font-family: 'Open Sans', sans-serif;
            font-size: .95rem;
        }
        .quill-wrap .ql-editor {
            min-height: 160px; line-height: 1.65; color: #2c2c2c;
            padding: 14px 15px;
        }
        .quill-wrap .ql-editor.ql-blank::before {
            color: #aaa; font-style: normal;
        }
        .ql-toolbar .ql-stroke { stroke: #555; }
        .ql-toolbar .ql-fill  { fill:   #555; }
        .ql-toolbar button:hover .ql-stroke,
        .ql-toolbar button.ql-active .ql-stroke { stroke: var(--primary) !important; }
        .ql-toolbar button:hover .ql-fill,
        .ql-toolbar button.ql-active .ql-fill   { fill:   var(--primary) !important; }
        .ql-toolbar .ql-picker-label { color: #555; }
        .ql-toolbar .ql-picker-label:hover { color: var(--primary); }

        /* ── Upload image ── */
        .img-current {
            position: relative; margin-bottom: 12px;
            border-radius: 8px; overflow: hidden;
            border: 1.5px solid var(--border);
        }
        .img-current img {
            width: 100%; max-height: 220px;
            object-fit: cover; display: block;
        }
        .img-current-footer {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; background: #fafafa;
            border-top: 1px solid var(--border);
        }
        .img-current-footer input[type=checkbox] { accent-color: #e74c3c; width: 16px; height: 16px; cursor: pointer; }
        .img-current-footer label { font-size: .82rem; color: #c0392b; cursor: pointer; margin: 0; font-weight: 600; }

        .file-drop {
            border: 2px dashed var(--border); border-radius: 8px;
            padding: 28px 20px; text-align: center;
            cursor: pointer; transition: border-color .2s, background .2s;
            background: #fdfcff; position: relative;
        }
        .file-drop:hover, .file-drop.drag-over {
            border-color: var(--primary); background: rgba(142,124,195,.04);
        }
        .file-drop i { font-size: 2rem; color: var(--primary); margin-bottom: 8px; }
        .file-drop p { font-size: .9rem; color: #555; margin: 4px 0; }
        .file-drop .file-chosen { font-size: .82rem; color: var(--primary); font-weight: 600; margin-top: 6px; }
        .file-drop input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-preview {
            margin-top: 10px; border-radius: 8px; overflow: hidden;
            border: 1.5px solid var(--primary); display: none;
        }
        .upload-preview img { width: 100%; max-height: 200px; object-fit: cover; display: block; }

        /* ── Panneau debug ── */
        .debug-panel { margin-top: 24px; }
        .debug-panel details { background: #1e1e2e; color: #cdd6f4; border-radius: 10px; padding: 18px 20px; }
        .debug-panel summary { cursor: pointer; font-weight: 700; font-size: .9rem; color: #89b4fa; margin-bottom: 12px; }
        .debug-panel pre { font-family: 'Courier New', monospace; font-size: .8rem; line-height: 1.7; white-space: pre-wrap; margin: 0; }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="admin-brand">Malika Énergéticienne <span>/ Admin</span></div>
    <div class="header-back">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
    </div>
</header>

<main class="main">
    <h1 class="page-title"><?= $editing ? 'Modifier l\'actualité' : 'Nouvelle actualité' ?></h1>

    <div class="card">
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label for="titre">Titre <span style="color:#e74c3c">*</span></label>
                <input type="text" id="titre" name="titre" maxlength="120" required
                       value="<?= htmlspecialchars($titre) ?>">
            </div>

            <div class="form-group">
                <label>Contenu <span style="color:#e74c3c">*</span></label>
                <div class="quill-wrap">
                    <div id="quill-editor"></div>
                </div>
                <input type="hidden" name="resume" id="resume-hidden">
                <div class="hint">Gras, italique, listes, titres, liens — le contenu s'affiche sur la page d'accueil et la page Actualités.</div>
            </div>

            <div class="form-group">
                <label for="date">Date de publication <span style="color:#e74c3c">*</span></label>
                <input type="date" id="date" name="date" required
                       value="<?= htmlspecialchars($date) ?>">
            </div>

            <div class="form-group">
                <label>Image (optionnel)</label>

                <?php if ($image): ?>
                <div class="img-current">
                    <img src="../<?= htmlspecialchars($image) ?>" alt="Image actuelle">
                    <div class="img-current-footer">
                        <input type="checkbox" name="remove_image" id="remove_image" value="1">
                        <label for="remove_image"><i class="fas fa-trash-alt"></i> Supprimer cette image</label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="file-drop" id="fileDrop">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p><?= $image ? 'Choisir une nouvelle image' : 'Glisser-déposer ou cliquer pour choisir' ?></p>
                    <div class="file-chosen" id="fileName">Aucun fichier sélectionné</div>
                    <input type="file" name="image_file" id="image_file"
                           accept="image/jpeg,image/png,image/webp,image/gif">
                </div>
                <div class="upload-preview" id="uploadPreview">
                    <img id="previewImg" src="" alt="Prévisualisation">
                </div>
                <div class="hint">JPG, PNG, WebP ou GIF — max 5 Mo. Format paysage recommandé (800 × 450 px).</div>
            </div>

            <div class="form-group">
                <div class="toggle-group">
                    <input type="checkbox" id="publie" name="publie" <?= $publie ? 'checked' : '' ?>>
                    <label for="publie">Publier cette actualité (visible sur le site)</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <?= $editing ? 'Enregistrer les modifications' : 'Publier l\'actualité' ?>
                </button>
                <a href="index.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
</main>

<?php if (!empty($debug) && function_exists('is_dev') && is_dev()): ?>
    <div class="debug-panel">
        <details open>
            <summary>Diagnostic upload</summary>
            <pre><?php
                foreach ($debug as $line) {
                    $line = htmlspecialchars($line);
                    if (str_contains($line, '✅') || str_contains($line, '🗜')) {
                        echo '<span style="color:#a6e3a1">' . $line . '</span>';
                    } elseif (str_contains($line, '❌') || str_contains($line, '⚠️')) {
                        echo '<span style="color:#f38ba8">' . $line . '</span>';
                    } elseif (str_starts_with($line, '──')) {
                        echo '<span style="color:#6c7086">' . $line . '</span>';
                    } else {
                        echo $line;
                    }
                    echo "\n";
                }
            ?></pre>
        </details>
    </div>
<?php endif; ?>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// ── Éditeur Quill ─────────────────────────────────────────────────────────
var quill = new Quill('#quill-editor', {
    theme: 'snow',
    placeholder: 'Rédigez votre actualité…',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            [{ header: [2, 3, false] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link'],
            ['clean']
        ]
    }
});

// Pré-remplir avec le contenu existant (PHP → JS)
var existingHtml = <?= json_encode($resume) ?>;
if (existingHtml && existingHtml.trim() && existingHtml !== '<p><br></p>') {
    quill.root.innerHTML = existingHtml;
}

// Copier le HTML dans le champ caché avant soumission
document.querySelector('form').addEventListener('submit', function () {
    var html = quill.root.innerHTML;
    document.getElementById('resume-hidden').value =
        (html === '<p><br></p>' || html === '<p></p>') ? '' : html;
});

function updateCount(el, countId, max) {
    const len = el.value.length;
    const counter = document.getElementById(countId);
    counter.textContent = len + '/' + max + ' caractères';
    counter.className = 'char-count' + (len > max * .9 ? (len >= max ? ' over' : ' warn') : '');
}

document.addEventListener('DOMContentLoaded', () => {
    const resume = document.getElementById('resume');
    if (resume) updateCount(resume, 'count-resume', 400);

    const input    = document.getElementById('image_file');
    const drop     = document.getElementById('fileDrop');
    const fileName = document.getElementById('fileName');
    const preview  = document.getElementById('uploadPreview');
    const previewImg = document.getElementById('previewImg');

    if (!input) return;

    input.addEventListener('change', () => processFile(input.files[0]));

    drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag-over'); });
    drop.addEventListener('dragleave', () => drop.classList.remove('drag-over'));
    drop.addEventListener('drop', e => {
        e.preventDefault();
        drop.classList.remove('drag-over');
        if (e.dataTransfer.files.length) processFile(e.dataTransfer.files[0]);
    });

    // Compression Canvas avant envoi (max 1200px, JPEG 85%)
    function processFile(file) {
        if (!file) return;

        const MAX_W    = 1200;
        const MAX_H    = 900;
        const QUALITY  = 0.85;
        const origSize = (file.size / 1024 / 1024).toFixed(2);

        fileName.textContent = '⏳ Compression en cours…';
        preview.style.display = 'none';

        const reader = new FileReader();
        reader.onload = evt => {
            const img = new Image();
            img.onload = () => {
                // Calcul des dimensions cibles
                let w = img.width, h = img.height;
                const ratio = Math.min(MAX_W / w, MAX_H / h, 1);
                w = Math.round(w * ratio);
                h = Math.round(h * ratio);

                const canvas = document.createElement('canvas');
                canvas.width  = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);

                canvas.toBlob(blob => {
                    if (!blob) {
                        fileName.textContent = '⚠️ Compression échouée — fichier envoyé tel quel.';
                        setInputFile(file, file.name);
                        return;
                    }

                    const compSize = (blob.size / 1024 / 1024).toFixed(2);
                    const newName  = file.name.replace(/\.[^.]+$/, '') + '.jpg';

                    fileName.textContent = '✅ ' + newName
                        + ' — ' + origSize + ' Mo → ' + compSize + ' Mo compressé';

                    // Remplacer le fichier dans l'input
                    setInputFile(new File([blob], newName, { type: 'image/jpeg' }), newName);

                    // Prévisualisation
                    previewImg.src = URL.createObjectURL(blob);
                    preview.style.display = 'block';

                }, 'image/jpeg', QUALITY);
            };
            img.src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }

    function setInputFile(file, name) {
        try {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        } catch(e) {
            // DataTransfer non supporté (rare) — le fichier original sera envoyé
            console.warn('DataTransfer non supporté :', e);
        }
    }
});
</script>
</body>
</html>
