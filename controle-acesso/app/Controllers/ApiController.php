<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\{Chave, Usuario, Pagamento, Acesso, Auditoria};

/**
 * API do ESP32:
 *   POST /api/acesso           {"uid":"A1:B2:C3:D4"} -> {"authorized":bool,"message":"..."}
 *   POST /api/chaves/pendente  {"uid":"A1:B2:C3:D4"} -> {"message":"..."}
 * Mensagens curtas para caberem no OLED. Qualquer dúvida => negado (fail-closed).
 */
class ApiController extends Controller {
  private function json(array $a, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
  }
  private function uid(): string {
    $in  = json_decode((string)file_get_contents('php://input'), true);
    $uid = strtoupper(trim((string)($in['uid'] ?? '')));
    if (!preg_match('/^[0-9A-F]{2}(:[0-9A-F]{2}){3,9}$/', $uid)) $this->json(['authorized' => false, 'message' => 'Requisicao invalida'], 400);
    return $uid;
  }

  public function acesso(): void {
    $uid = $this->uid();
    try {
      [$ok, $msg, $u] = $this->decidir($uid);
      Acesso::registrar($uid, $u ? (int)$u['id'] : null, $u['nome'] ?? null, $ok, $msg);
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
    $uid = $this->uid();
    try {
      $c = Chave::porUid($uid);
      if ($c) $this->json(['message' => $c['usuario_id'] ? 'Chave ja vinculada' : 'Ja esta pendente']);
      Chave::criarPendente($uid);
      Auditoria::registrar('chave_pendente', 'chave', null, ['uid' => $uid, 'origem' => 'esp32']);
    } catch (\Throwable $e) {
      error_log($e->getMessage());
      $this->json(['message' => 'Erro no servidor'], 500);
    }
    $this->json(['message' => 'Chave enviada']);
  }
}
