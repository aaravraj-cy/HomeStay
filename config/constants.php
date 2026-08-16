<?php
// Sonam Homestay - Basic settings (BCA level)

define('APP_NAME', 'Sonam Homestay');
define('BASE_URL', 'http://localhost/HomeStay/');
define('BASE_PATH', dirname(__DIR__) . '/');

define('DB_HOST', 'localhost');
define('DB_NAME', 'sonamDB');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_PROFILES', BASE_PATH . 'assets/uploads/profiles/');
define('UPLOAD_HOMESTAYS', BASE_PATH . 'assets/uploads/homestays/');
define('UPLOAD_ROOMS', BASE_PATH . 'assets/uploads/rooms/');

define('ITEMS_PER_PAGE', 12);

date_default_timezone_set('Asia/Kolkata');
