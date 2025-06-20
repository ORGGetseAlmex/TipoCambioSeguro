<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../app/helpers.php';

use App\Router;
use App\Controllers\HomeController;
use App\Controllers\FestivosController;
use App\Controllers\ApiController;

$router = new Router();

$router->get('/', [new HomeController(), 'index']);
$router->post('/', [new HomeController(), 'index']);
$router->get('/festivos', [new FestivosController(), 'index']);
$router->post('/festivos', [new FestivosController(), 'index']);
$router->get('/api/price', [new ApiController(), 'priceToday']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
