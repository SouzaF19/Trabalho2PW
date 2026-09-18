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

use App\Controllers\AuthController;
use App\Controllers\CadernoController;
use App\Controllers\PaginaController;
use App\Controllers\TelaController;
use App\Core\Response;

// --- Telas (HTML) --------------------------------------------------------
$router->get('/',              [TelaController::class, 'inicio']);
$router->get('/login',         [TelaController::class, 'login']);
$router->get('/cadastro',      [TelaController::class, 'cadastro']);
$router->get('/cadernos',      [TelaController::class, 'cadernos']);
$router->get('/cadernos/{id}', [TelaController::class, 'editor']);

// --- Diagnostico ---------------------------------------------------------
$router->get('/api/ping', function (): void {
    Response::json(['ok' => true, 'php' => PHP_VERSION]);
});

// --- Autenticacao --------------------------------------------------------
$router->post('/api/cadastro', [AuthController::class, 'cadastrar']);
$router->post('/api/login',    [AuthController::class, 'entrar']);
$router->post('/api/logout',   [AuthController::class, 'sair']);

// --- Cadernos ------------------------------------------------------------
$router->get('/api/cadernos',                  [CadernoController::class, 'listar']);
$router->post('/api/cadernos',                 [CadernoController::class, 'criar']);
$router->delete('/api/cadernos/{id}',          [CadernoController::class, 'excluir']);
$router->get('/api/cadernos/{id}/paginas',     [CadernoController::class, 'listarPaginas']);
$router->post('/api/cadernos/{id}/paginas',    [CadernoController::class, 'criarPagina']);

// --- Paginas -------------------------------------------------------------
$router->get('/api/paginas/{id}', [PaginaController::class, 'mostrar']);
$router->put('/api/paginas/{id}', [PaginaController::class, 'salvar']);

// --- A implementar -------------------------------------------------------
// $router->post('/api/upload', [UploadController::class, 'receber']);
