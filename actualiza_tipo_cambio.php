<?php
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

$token = $_ENV['BANXICO_TOKEN'];
$fechaFin = date("Y-m-d");
$hoy = date("Y-m-d");

?>