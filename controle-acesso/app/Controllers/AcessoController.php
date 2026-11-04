<?php
namespace App\Controllers;
use App\Core\{Controller, Validator};
use App\Models\{Acesso, Usuario};

class AcessoController extends Controller {
  public function index(): void {
    $this->auth();
    $uid  = (int)($_GET['usuario'] ?? 0) ?: null;
    $data = (string)($_GET['data'] ?? '');
    $data = Validator::data($data) ? $data : null;
    $this->view('acessos', ['titulo' => 'Log de acessos', 'acessos' => Acesso::buscar($uid, $data),
      'usuarios' => Usuario::todos(date('Y-m')), 'fu' => $uid, 'fd' => $data]);
  }
}
