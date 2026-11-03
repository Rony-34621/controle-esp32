<?php
namespace App\Controllers;
use App\Core\{Controller, Auth};
use App\Models\{Admin, Auditoria};

class AuthController extends Controller {
  public function loginForm(): void {
    if (Admin::total() === 0) $this->redirect('/instalar');
    if (Auth::check()) $this->redirect('/');
    $this->view('login', ['titulo' => 'Entrar']);
  }
  public function login(): void {
    $this->csrf();
    $login = trim((string)($_POST['login'] ?? ''));
    $a = Admin::porLogin($login);
    if ($a && $a['ativo'] && password_verify((string)($_POST['senha'] ?? ''), $a['senha_hash'])) {
      Auth::login($a);
      Auditoria::registrar('login', 'admin', (int)$a['id']);
      $this->redirect('/');
    }
    usleep(500000); // atrasa tentativas de força bruta
    Auditoria::registrar('login_falhou', 'admin', null, ['login' => mb_substr($login, 0, 30)]);
    $this->flash('Login ou senha incorretos.', 'erro');
    $this->redirect('/login');
  }
  public function logout(): void {
    $this->csrf();
    if (Auth::check()) Auditoria::registrar('logout', 'admin', Auth::id());
    Auth::logout();
    $this->redirect('/login');
  }
  /** Cria o primeiro administrador. Só funciona enquanto não existir nenhum. */
  public function instalarForm(): void {
    if (Admin::total() > 0) $this->redirect('/login');
    $this->view('instalar', ['titulo' => 'Primeiro acesso']);
  }
  public function instalar(): void {
    if (Admin::total() > 0) $this->redirect('/login');
    $this->csrf();
    [$nome, $login, $senha, $erro] = AdminController::validar();
    if ($erro) { $this->flash($erro, 'erro'); $this->redirect('/instalar'); }
    $id = Admin::criar($nome, $login, password_hash($senha, PASSWORD_DEFAULT));
    Auth::login(['id' => $id, 'nome' => $nome, 'login' => $login]);
    Auditoria::registrar('criar', 'admin', $id, ['login' => $login, 'primeiro_admin' => true]);
    $this->redirect('/');
  }
}
