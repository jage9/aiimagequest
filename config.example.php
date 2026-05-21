<?php
// Copy this file to config.php and fill in your values.

// Database credentials
define('DB_SERVER',   'localhost');
define('DB_USERNAME', 'your_db_username');
define('DB_PASSWORD', 'your_db_password');
define('DB_NAME',     'your_db_name');

// Project root (no trailing slash) — derived automatically, no need to change
define('BASE_DIR',         dirname(__FILE__));
define('TEMP_UPLOADS_DIR', BASE_DIR . '/temp_uploads');
define('SCRIPTS_DIR',      BASE_DIR . '/scripts');
define('PYTHON_BIN',       BASE_DIR . '/scripts/.venv/bin/python');
define('IMAGES_DIR',       BASE_DIR . '/public_html/images');

// Admin password hash — generate with: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', '');
?>

