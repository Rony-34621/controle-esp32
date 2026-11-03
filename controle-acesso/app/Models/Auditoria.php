<?php
namespace App\Models;
use App\Core\{Database as DB, Auth};

class Auditoria {
  public static function registrar(string $acao, string $entidade, ?int $id = null, $detalhes = null): void {
    DB::run('INSERT INTO auditoria (admin_id,admin_login,acao,entidade,entidade_id,detalhes,ip,criado_em) VALUES (?,?,?,?,?,?,?,?)', [
      Auth::id(), $_SESSION['admin_login'] ?? null, $acao, $entidade, $id,
      $detalhes === null ? null : (is_string($detalhes) ? $detalhes : json_encode($detalhes, JSON_UNESCAPED_UNICODE)),
      $_SERVER['REMOTE_ADDR'] ?? null, date('Y-m-d H:i:s')]);
  }
  public static function recentes(int $limite = 200): array {
    $limite = max(1, min(1000, $limite));
    return DB::run("SELECT * FROM auditoria ORDER BY id DESC LIMIT $limite")->fetchAll();
  }
}
