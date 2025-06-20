<?php
if (!defined('APP_RUNNING')) {
    die('Acceso denegado.');
}

require_once __DIR__ . '/bootstrap.php';

use App\Models\Database;

$db = new Database();
$conn = $db->getConnection();
