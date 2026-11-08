<?php
namespace App\Controllers;
use App\Core\{Controller, Auth};
use App\Models\{Admin, Auditoria};

class AdminController extends Controller {
  public function index(): void {
    $this->auth();
    $this->view('admins', ['titulo' => 'Administradores', 'admins' => Admin::todos(), 'primeiro' => Admin::primeiroId()]);
  }
  public static function validar(): array {
    $nome  = trim((string)($_POST['nome'] ?? ''));
    $login = strtolower(trim((string)($_POST['login'] ?? '')));
    $senha = (string)($_POST['senha'] ?? '');
    $erro = '';
    if ($nome === '' || mb_strlen($nome) > 100)             $erro = 'Informe o nome (até 100 caracteres).';
    elseif (!preg_match('/^[a-z0-9_.]{3,30}$/', $login))    $erro = 'Login: 3 a 30 caracteres, só letras minúsculas, números, ponto e _.';
    elseif (strlen($senha) < 8)                             $erro = 'A senha precisa ter pelo menos 8 caracteres.';
    elseif ($senha !== (string)($_POST['senha2'] ?? ''))    $erro = 'As senhas não conferem.';
    return [$nome, $login, $senha, $erro];
  }
  public function criar(): void {
    $this->auth(); $this->csrf();
    [$nome, $login, $senha, $erro] = self::validar();
    if (!$erro && Admin::porLogin($login)) $erro = 'Esse login já existe.';
    if ($erro) { $this->flash($erro, 'erro'); $this->redirect('/admins'); }
    $id = Admin::criar($nome, $login, password_hash($senha, PASSWORD_DEFAULT));
    Auditoria::registrar('criar', 'admin', $id, ['login' => $login, 'nome' => $nome]);
    $this->flash("Administrador $login criado.");
    $this->redirect('/admins');
  }
  /** Troca a senha da própria conta, confirmando a senha atual. Ninguém troca a senha de outro admin. */
  public function senha(int $id): void {
    $this->auth(); $this->csrf();
    if ($id !== Auth::id()) {
      Auditoria::registrar('trocar_senha_recusada', 'admin', $id, ['motivo' => 'tentativa de trocar senha de outra conta']);
      $this->flash('Cada administrador só pode trocar a própria senha.', 'erro');
      $this->redirect('/admins');
    }
    $a = Admin::find($id);
    if (!$a) { $this->flash('Conta não encontrada.', 'erro'); $this->redirect('/admins'); }
    $hashAtual = Admin::senhaHash(Auth::id());
    if (!$hashAtual || !password_verify((string)($_POST['senha_atual'] ?? ''), $hashAtual)) {
      usleep(500000); // atrasa tentativas de força bruta
      Auditoria::registrar('trocar_senha_recusada', 'admin', $id, ['login' => $a['login'], 'motivo' => 'senha atual incorreta']);
      $this->flash('Sua senha atual não confere — nada foi alterado.', 'erro');
      $this->redirect('/admins');
    }
    $senha = (string)($_POST['senha'] ?? '');
    if (strlen($senha) < 8) { $this->flash('A senha nova precisa ter pelo menos 8 caracteres.', 'erro'); $this->redirect('/admins'); }
    if ($senha !== (string)($_POST['senha2'] ?? '')) { $this->flash('As senhas novas não conferem.', 'erro'); $this->redirect('/admins'); }
    Admin::definirSenha($id, password_hash($senha, PASSWORD_DEFAULT));
    Auditoria::registrar('trocar_senha', 'admin', $id, ['login' => $a['login'], 'propria' => $id === Auth::id()]);
    $this->flash('Senha de ' . $a['login'] . ' alterada.');
    $this->redirect('/admins');
  }

  public function status(int $id): void {
    $this->auth(); $this->csrf();
    $a = Admin::find($id);
    if (!$a || $id === Auth::id()) { $this->flash('Você não pode alterar a própria conta.', 'erro'); $this->redirect('/admins'); }
    if ($a['ativo'] && $id === Admin::primeiroId()) {
      Auditoria::registrar('desativar_recusada', 'admin', $id, ['login' => $a['login'], 'motivo' => 'primeiro administrador é protegido']);
      $this->flash('O administrador principal (primeiro da instalação) não pode ser desativado.', 'erro');
      $this->redirect('/admins');
    }
    $novo = $a['ativo'] ? 0 : 1;
    Admin::definirAtivo($id, $novo);
    Auditoria::registrar($novo ? 'ativar' : 'desativar', 'admin', $id, ['login' => $a['login']]);
    $this->flash('Conta ' . $a['login'] . ($novo ? ' ativada.' : ' desativada.'));
    $this->redirect('/admins');
  }
}
