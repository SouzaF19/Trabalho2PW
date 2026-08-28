<?php
declare(strict_types=1);

/**
 * Mapa de rotas da aplicacao.
 *
 * Este arquivo e incluido pelo front controller, que ja definiu $router.
 * Manter as rotas em um arquivo so da um "indice" do sistema inteiro
 * em uma tela.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\PaginaController;
use App\Core\Response;

// --- Diagnostico ---------------------------------------------------------
$router->get('/api/ping', function (): void {
    Response::json(['ok' => true, 'php' => PHP_VERSION]);
});

// --- Paginas -------------------------------------------------------------
$router->get('/api/paginas/{id}', [PaginaController::class, 'mostrar']);
$router->put('/api/paginas/{id}', [PaginaController::class, 'salvar']);

// --- A implementar (Pessoa A) -------------------------------------------
// $router->post('/api/cadastro',     [AuthController::class, 'cadastrar']);
// $router->post('/api/login',        [AuthController::class, 'entrar']);
// $router->post('/api/logout',       [AuthController::class, 'sair']);
// $router->get('/api/cadernos',      [CadernoController::class, 'listar']);
// $router->post('/api/cadernos',     [CadernoController::class, 'criar']);
// $router->delete('/api/cadernos/{id}', [CadernoController::class, 'excluir']);
// $router->post('/api/upload',       [UploadController::class, 'receber']);
