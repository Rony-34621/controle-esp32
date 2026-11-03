<?php
namespace App\Core;
use App\Models\Admin;

abstract class Controller {
  protected function view(string $v, array $d = []): void {
    extract($d);
    ob_start();
    require __DIR__ . "/../Views/$v.php";
    $content = ob_get_clean();
    require __DIR__ . '/../Views/layout.php';
  }
  protected function redirect(string $p): never { header('Location: ' . url($p)); exit; }
  protected function flash(string $m, string $tipo = 'ok'): void { $_SESSION['flash'] = [$tipo, $m]; }
  /** Exige admin logado e ativo (revalidado a cada requisição). */
  protected function auth(): void {
    $a = Auth::check() ? Admin::find(Auth::id()) : null;
    if (!$a || !$a['ativo']) {
      if (Auth::check()) { Auth::logout(); session_start(); }
      $this->redirect('/login');
    }
  }
  /** Exige token CSRF válido em todo POST do painel. */
  protected function csrf(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Sessão expirada. Volte e tente de novo.'); }
  }
  protected function volta(string $padrao): string {
    $v = (string)($_POST['volta'] ?? '');
    return preg_match('#^/[a-z0-9/]*$#i', $v) ? $v : $padrao;
  }
}
