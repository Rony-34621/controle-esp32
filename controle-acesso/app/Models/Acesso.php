<?php
namespace App\Models;
use App\Core\Database as DB;

class Acesso {
  public static function registrar(string $uid, ?int $uid_user, ?string $nome, bool $ok, string $motivo, ?int $catraca = null): void {
    DB::run('INSERT INTO acessos (uid,usuario_id,usuario_nome,catraca_id,autorizado,motivo,data_hora) VALUES (?,?,?,?,?,?,?)',
            [$uid, $uid_user, $nome, $catraca, (int)$ok, $motivo, date('Y-m-d H:i:s')]);
  }
  public static function buscar(?int $usuario = null, ?string $data = null, int $limite = 200): array {
    $w = []; $p = [];
    if ($usuario) { $w[] = 'a.usuario_id=?'; $p[] = $usuario; }
    if ($data)    { $w[] = 'DATE(a.data_hora)=?'; $p[] = $data; }
    $limite = max(1, min(1000, $limite));
    return DB::run('SELECT a.*, c.nome AS catraca_nome FROM acessos a LEFT JOIN catracas c ON c.id=a.catraca_id'
                   . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . " ORDER BY a.id DESC LIMIT $limite", $p)->fetchAll();
  }
}
