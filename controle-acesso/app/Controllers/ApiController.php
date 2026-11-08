<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\{Catraca, Chave, Usuario, Pagamento, Acesso, Auditoria};

class ApiController extends Controller {
  private function json(array $a, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
  }
  private function corpo(): array {
    return json_decode((string)file_get_contents('php://input'), true) ?: [];
  }
  private function uid(array $in): string {
    $uid = strtoupper(trim((string)($in['uid'] ?? '')));
    if (!preg_match('/^[0-9A-F]{2}(:[0-9A-F]{2}){3,9}$/', $uid)) $this->json(['authorized' => false, 'message' => 'Requisicao invalida'], 400);
    return $uid;
  }
  /**
   * Identifica a catraca pela chave da API ("token" no JSON).
   * Sem token a requisição segue anônima (compatível com firmware antigo);
   * com token inválido a requisição morre aqui.
   */
  private function catraca(array $in): ?array {
    $token = trim((string)($in['token'] ?? ''));
    if ($token === '') return null;
    $c = Catraca::porToken($token);
    if (!$c) $this->json(['authorized' => false, 'message' => 'Catraca nao cadastrada'], 403);
    Catraca::tocar((int)$c['id']);
    return $c;
  }

  public function acesso(): void {
    $in  = $this->corpo();
    $uid = $this->uid($in);
    try {
      $cat = $this->catraca($in);
      if ($cat && !(int)$cat['ativo']) {
        Acesso::registrar($uid, null, null, false, 'Catraca desativada', (int)$cat['id']);
        $this->json(['authorized' => false, 'message' => 'Catraca desativada']);
      }
      [$ok, $msg, $u] = $this->decidir($uid);
      Acesso::registrar($uid, $u ? (int)$u['id'] : null, $u['nome'] ?? null, $ok, $msg, $cat ? (int)$cat['id'] : null);
    } catch (\Throwable $e) {
      error_log($e->getMessage());
      $this->json(['authorized' => false, 'message' => 'Erro no servidor'], 500);
    }
    $this->json(['authorized' => $ok, 'message' => $msg]);
  }

  private function decidir(string $uid): array { // [autorizado, mensagem, usuario|null]
    $c = Chave::porUid($uid);
    if (!$c)                  return [false, 'Chave desconhecida', null];
    if (!$c['usuario_id'])    return [false, 'Chave pendente', null];
    $u = Usuario::find((int)$c['usuario_id']);
    if (!$u)                  return [false, 'Chave sem usuario', null];
    if (!(int)$c['ativo'])    return [false, 'Chave desativada', $u];
    if ($u['ativo'] !== 'S')  return [false, 'Cadastro inativo', $u];
    if (!Pagamento::pago((int)$u['id'], date('Y-m'))) return [false, 'Mensalidade em aberto', $u];
    if (!(int)$u[['dom','seg','ter','qua','qui','sex','sab'][(int)date('w')]]) return [false, 'Dia nao permitido', $u];
    $h = (int)date('G');
    $ok = match ($u['periodo']) {
      'manha' => $h >= 5 && $h < 12,
      'tarde' => $h >= 12 && $h < 18,
      'noite' => $h >= 18 || $h < 5,
      default => true,
    };
    return $ok ? [true, 'Acesso permitido', $u] : [false, 'Fora do horario', $u];
  }

  public function pendente(): void {
    $in  = $this->corpo();
    $uid = $this->uid($in);
    try {
      $cat = $this->catraca($in);
      if ($cat && !(int)$cat['ativo']) $this->json(['message' => 'Catraca desativada'], 403);
      $c = Chave::porUid($uid);
      if ($c) $this->json(['message' => $c['usuario_id'] ? 'Chave ja vinculada' : 'Ja esta pendente']);
      Chave::criarPendente($uid);
      Auditoria::registrar('chave_pendente', 'chave', null, ['uid' => $uid, 'origem' => $cat ? $cat['nome'] : 'esp32']);
    } catch (\Throwable $e) {
      error_log($e->getMessage());
      $this->json(['message' => 'Erro no servidor'], 500);
    }
    $this->json(['message' => 'Chave enviada']);
  }
}
