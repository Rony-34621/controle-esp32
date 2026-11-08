<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\{Catraca, Chave, Auditoria};

class CatracaController extends Controller {
  public function index(): void {
    $this->auth();
    $this->view('catracas', ['titulo' => 'Catracas', 'catracas' => Catraca::todas(), 'chaves' => Chave::todasComDono()]);
  }

  public function criar(): void {
    $this->auth(); $this->csrf();
    $nome = trim((string)($_POST['nome'] ?? ''));
    $tipo = (string)($_POST['tipo'] ?? '');
    if ($nome === '' || mb_strlen($nome) > 100) { $this->flash('Informe o nome da catraca (até 100 caracteres).', 'erro'); $this->redirect('/catracas'); }
    if (!isset(Catraca::TIPOS[$tipo])) $tipo = 'esp32';
    [$id, $token] = Catraca::criar($nome, $tipo);
    Auditoria::registrar('criar', 'catraca', $id, ['nome' => $nome, 'tipo' => $tipo]);
    $this->flash("Catraca \"$nome\" cadastrada. Use a chave da API $token no firmware.");
    $this->redirect('/catracas');
  }

  /** Ativa/desativa a catraca: desativada, a API recusa tudo que vier dela. */
  public function status(int $id): void {
    $this->auth(); $this->csrf();
    $c = Catraca::find($id);
    if (!$c) { $this->flash('Catraca não encontrada.', 'erro'); $this->redirect('/catracas'); }
    $novo = (int)$c['ativo'] ? 0 : 1;
    Catraca::definirAtivo($id, $novo);
    Auditoria::registrar($novo ? 'ativar' : 'desativar', 'catraca', $id, ['nome' => $c['nome']]);
    $this->flash('Catraca ' . $c['nome'] . ($novo ? ' ativada.' : ' desativada.'));
    $this->redirect('/catracas');
  }

  /** Gera outra chave de API (a anterior deixa de valer na hora). */
  public function token(int $id): void {
    $this->auth(); $this->csrf();
    $c = Catraca::find($id);
    if (!$c) { $this->flash('Catraca não encontrada.', 'erro'); $this->redirect('/catracas'); }
    $token = Catraca::novoToken($id);
    Auditoria::registrar('novo_token', 'catraca', $id, ['nome' => $c['nome']]);
    $this->flash("Nova chave da API gerada para {$c['nome']}: $token — atualize o firmware.");
    $this->redirect('/catracas');
  }

  public function excluir(int $id): void {
    $this->auth(); $this->csrf();
    $c = Catraca::find($id);
    if ($c) { Catraca::excluir($id); Auditoria::registrar('excluir', 'catraca', $id, ['nome' => $c['nome'], 'tipo' => $c['tipo']]); }
    $this->flash('Catraca removida. O histórico de acessos dela fica no log.');
    $this->redirect('/catracas');
  }
}
