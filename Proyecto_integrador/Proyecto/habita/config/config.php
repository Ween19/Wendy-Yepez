<?php
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

if ($host === 'localhost') {
    define('BASE_URL', '/HABITA');
} else {
    define('BASE_URL', '/HABITA');
}
?>