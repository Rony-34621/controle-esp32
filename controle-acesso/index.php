<?php
declare(strict_types=1);
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (!str_contains($_SERVER['REQUEST_URI'], '/api/')) session_start(); // a API do ESP32 não usa sessão

require __DIR__ . '/config/config.php';
spl_autoload_register(function (string $c) {
  $f = __DIR__ . '/app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
  if (str_starts_with($c, 'App\\') && is_file($f)) require $f;
});
require __DIR__ . '/app/Core/helpers.php';

$r = new App\Core\Router();
// Painel (administradores)
$r->add('GET',  '/login',    'AuthController@loginForm');
$r->add('POST', '/login',    'AuthController@login');
$r->add('POST', '/logout',   'AuthController@logout');
$r->add('GET',  '/instalar', 'AuthController@instalarForm');
$r->add('POST', '/instalar', 'AuthController@instalar');
$r->add('GET',  '/',                       'UsuarioController@index');
$r->add('GET',  '/usuarios/novo',          'UsuarioController@novo');
$r->add('GET',  '/usuarios/{id}',          'UsuarioController@editar');
$r->add('POST', '/usuarios',               'UsuarioController@salvar');
$r->add('POST', '/usuarios/{id}',          'UsuarioController@salvar');
$r->add('POST', '/usuarios/{id}/status',   'UsuarioController@status');
$r->add('POST', '/usuarios/{id}/excluir',  'UsuarioController@excluir');
$r->add('POST', '/usuarios/{id}/pagamento','UsuarioController@pagamento');
$r->add('POST', '/chaves/{id}/vincular',   'UsuarioController@vincularChave');
$r->add('POST', '/chaves/{id}/excluir',    'UsuarioController@excluirChave');
$r->add('GET',  '/acessos',                'AcessoController@index');
$r->add('GET',  '/admins',                 'AdminController@index');
$r->add('POST', '/admins',                 'AdminController@criar');
$r->add('POST', '/admins/{id}/status',     'AdminController@status');
$r->add('GET',  '/auditoria',              'AuditoriaController@index');
// API do ESP32 (mesmos caminhos e JSON do firmware)
$r->add('POST', '/api/acesso',          'ApiController@acesso');
$r->add('POST', '/api/chaves/pendente', 'ApiController@pendente');
$r->dispatch();
