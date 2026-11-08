<?php
namespace App\Controllers;
use App\Core\{Controller, Auth, Validator};
use App\Models\{Usuario, Chave, Pagamento, Acesso, Auditoria};
use PDOException;

class UsuarioController extends Controller {
  public function index(): void {
    $this->auth();
    $mes = date('Y-m');
    $this->view('usuarios/lista', ['titulo' => 'Usuários', 'mes' => $mes,
      'usuarios' => Usuario::todos($mes), 'pendentes' => Chave::pendentes()]);
  }
  public function novo(): void {
    $this->auth();
    $this->form(0, Usuario::padrao());
  }
  public function editar(int $id): void {
    $this->auth();
    $this->form($id, Usuario::find($id) ?? $this->naoEncontrado());
  }
  private function form(int $id, array $u, ?string $erro = null): void {
    $this->view('usuarios/form', ['titulo' => $id ? 'Editar usuário' : 'Novo usuário', 'id' => $id, 'u' => $u, 'erro' => $erro,
      'pagamentos' => $id ? Pagamento::doUsuario($id) : [], 'chaves' => $id ? Chave::doUsuario($id) : [],
      'pendentes' => $id ? Chave::pendentes() : [], 'acessos' => $id ? Acesso::buscar($id, null, 15) : []]);
  }
  private function naoEncontrado(): never { $this->flash('Usuário não encontrado.', 'erro'); $this->redirect('/'); }

  private function coletar(): array {
    $d = []; $e = [];
    $d['nome'] = trim((string)($_POST['nome'] ?? ''));
    if ($d['nome'] === '' || mb_strlen($d['nome']) > 100) $e[] = 'Informe o nome (até 100 caracteres).';
    $d['cpf'] = preg_replace('/\D/', '', (string)($_POST['cpf'] ?? ''));
    if (!Validator::cpf($d['cpf'])) $e[] = 'CPF inválido.';
    $n = (string)($_POST['nasc'] ?? '');
    $d['data_nascimento'] = $n === '' ? null : $n;
    if ($n !== '' && !Validator::data($n)) $e[] = 'Data de nascimento inválida.';
    $d['ativo'] = (($_POST['ativo'] ?? 'S') === 'N') ? 'N' : 'S';
    $p = (string)($_POST['periodo'] ?? '');
    $d['periodo'] = isset(PERIODOS[$p]) ? $p : 'dia_todo';
    foreach (DIAS as $k => $_) $d[$k] = (($_POST["d_$k"] ?? '0') === '1') ? 1 : 0;
    $v = str_replace(',', '.', (string)($_POST['valor'] ?? '0'));
    if (!is_numeric($v) || $v < 0 || $v > 99999) $e[] = 'Valor da mensalidade inválido.';
    $d['valor_mensalidade'] = round((float)$v, 2);
    return [$d, $e];
  }

  public function salvar(int $id = 0): void {
    $this->auth(); $this->csrf();
    $antigo = $id ? (Usuario::find($id) ?? $this->naoEncontrado()) : null;
    [$d, $e] = $this->coletar();
    if ($e) { $this->form($id, $d + ['nasc' => $d['data_nascimento']], implode(' ', $e)); return; }
    try {
      if ($id) {
        Usuario::atualizar($id, $d);
        $mud = [];
        foreach ($d as $k => $v) if ($antigo[$k] != $v) $mud[$k] = [$antigo[$k], $v];
        Auditoria::registrar('editar', 'usuario', $id, $mud);
      } else {
        $id = Usuario::criar($d);
        Auditoria::registrar('criar', 'usuario', $id, $d);
      }
    } catch (PDOException $ex) {
      if ($ex->getCode() === '23000') { $this->form($id, $d, 'Esse CPF já está cadastrado.'); return; }
      throw $ex;
    }
    $this->flash('Cadastro salvo.');
    $this->redirect("/usuarios/$id");
  }

  /** Ativa/desativa temporariamente (a exclusão é outra ação). */
  public function status(int $id): void {
    $this->auth(); $this->csrf();
    $u = Usuario::find($id) ?? $this->naoEncontrado();
    $novo = $u['ativo'] === 'S' ? 'N' : 'S';
    Usuario::definirAtivo($id, $novo);
    Auditoria::registrar($novo === 'S' ? 'ativar' : 'desativar', 'usuario', $id, ['nome' => $u['nome']]);
    $this->flash($u['nome'] . ($novo === 'S' ? ' ativado.' : ' desativado.'));
    $this->redirect($this->volta("/usuarios/$id"));
  }
  public function excluir(int $id): void {
    $this->auth(); $this->csrf();
    $u = Usuario::find($id) ?? $this->naoEncontrado();
    Usuario::excluir($id);
    Auditoria::registrar('excluir', 'usuario', $id, ['nome' => $u['nome'], 'cpf' => $u['cpf']]);
    $this->flash('Usuário excluído.');
    $this->redirect('/');
  }

  public function pagamento(int $id): void {
    $this->auth(); $this->csrf();
    $u = Usuario::find($id) ?? $this->naoEncontrado();
    $comp = (string)($_POST['comp'] ?? '');
    if (!Validator::competencia($comp)) { $this->flash('Mês inválido.', 'erro'); $this->redirect("/usuarios/$id"); }
    if (($_POST['pago'] ?? '0') === '1') {
      $v = str_replace(',', '.', (string)($_POST['valor'] ?? $u['valor_mensalidade']));
      $valor = is_numeric($v) && $v >= 0 ? round((float)$v, 2) : (float)$u['valor_mensalidade'];
      Pagamento::marcar($id, $comp, $valor, Auth::id());
      Auditoria::registrar('pagamento_marcado', 'usuario', $id, ['competencia' => $comp, 'valor' => $valor]);
      $this->flash("Pagamento de $comp marcado para {$u['nome']}.");
    } else {
      Pagamento::desmarcar($id, $comp);
      Auditoria::registrar('pagamento_desmarcado', 'usuario', $id, ['competencia' => $comp]);
      $this->flash("Pagamento de $comp desmarcado para {$u['nome']}.");
    }
    $this->redirect($this->volta("/usuarios/$id"));
  }

  /** Cadastro manual de tag/cartão pelo modal da tela do usuário. */
  public function criarChave(int $id): void {
    $this->auth(); $this->csrf();
    $u = Usuario::find($id) ?? $this->naoEncontrado();
    $uid = strtoupper(preg_replace('/[^0-9a-fA-F]/', '', (string)($_POST['uid'] ?? '')));
    $n = strlen($uid);
    if ($n < 8 || $n > 20 || $n % 2) { $this->flash('UID inválido. Informe de 4 a 10 bytes em hexadecimal (ex.: A1:B2:C3:D4).', 'erro'); $this->redirect("/usuarios/$id#tags"); }
    $uid = implode(':', str_split($uid, 2));
    if (Chave::porUid($uid)) { $this->flash("A chave $uid já está cadastrada.", 'erro'); $this->redirect("/usuarios/$id#tags"); }
    Chave::criarVinculada($uid, $id);
    Auditoria::registrar('criar_chave', 'usuario', $id, ['uid' => $uid]);
    $this->flash("Tag $uid cadastrada para {$u['nome']}.");
    $this->redirect("/usuarios/$id#tags");
  }

  /** Bloqueia/desbloqueia a chave sem removê-la (a exclusão é outra ação). */
  public function statusChave(int $id): void {
    $this->auth(); $this->csrf();
    $c = Chave::find($id);
    if (!$c) { $this->flash('Chave não encontrada.', 'erro'); $this->redirect($this->volta('/')); }
    $novo = (int)$c['ativo'] ? 0 : 1;
    Chave::definirAtivo($id, $novo);
    Auditoria::registrar($novo ? 'ativar_chave' : 'desativar_chave', 'chave', $id, ['uid' => $c['uid'], 'usuario_id' => $c['usuario_id']]);
    $this->flash('Chave ' . $c['uid'] . ($novo ? ' ativada.' : ' desativada.'));
    $this->redirect($this->volta('/') . '#tags');
  }

  public function vincularChave(int $id): void {
    $this->auth(); $this->csrf();
    $c = Chave::find($id);
    $u = Usuario::find((int)($_POST['usuario'] ?? 0));
    if (!$c || !$u) { $this->flash('Escolha o usuário para vincular a chave.', 'erro'); $this->redirect($this->volta('/')); }
    Chave::vincular($id, (int)$u['id']);
    Auditoria::registrar('vincular_chave', 'usuario', (int)$u['id'], ['uid' => $c['uid']]);
    $this->flash('Chave vinculada a ' . $u['nome'] . '.');
    $this->redirect($this->volta('/'));
  }
  public function excluirChave(int $id): void {
    $this->auth(); $this->csrf();
    $c = Chave::find($id);
    if ($c) { Chave::excluir($id); Auditoria::registrar('excluir', 'chave', $id, ['uid' => $c['uid'], 'usuario_id' => $c['usuario_id']]); }
    $this->flash('Chave removida.');
    $this->redirect($this->volta('/') . '#tags');
  }
}
