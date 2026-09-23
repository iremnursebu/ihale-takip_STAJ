<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


date_default_timezone_set('Europe/Istanbul');


define('DB_HOST', 'localhost');
define('DB_NAME', 'ihale-takip');
define('DB_USER', 'root');
define('DB_PASS', '');


define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); 
define('SMTP_SECURE', 'tls'); 
define('SMTP_USER', 'yozgatvalilikbilgiislem@gmail.com'); 
define('SMTP_PASS', 'snqsscvbfdceodaj'); 
define('SMTP_FROM_NAME', 'İhale Takip Sistemi');

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}


function json_response($status, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}
