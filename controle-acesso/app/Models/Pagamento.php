<?php
namespace App\Models;
use App\Core\Database as DB;

class Pagamento {
  public static function doUsuario(int $id): array { return DB::run('SELECT * FROM pagamentos WHERE usuario_id=? ORDER BY competencia DESC', [$id])->fetchAll(); }
  public static function pago(int $id, string $comp): bool { return (bool)DB::run('SELECT 1 FROM pagamentos WHERE usuario_id=? AND competencia=?', [$id, $comp])->fetch(); }
  public static function marcar(int $id, string $comp, float $valor, int $admin): void {
    DB::run('INSERT INTO pagamentos (usuario_id,competencia,valor,pago_em,admin_id) VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE valor=VALUES(valor), pago_em=VALUES(pago_em), admin_id=VALUES(admin_id)',
            [$id, $comp, $valor, date('Y-m-d H:i:s'), $admin]);
  }
  public static function desmarcar(int $id, string $comp): void { DB::run('DELETE FROM pagamentos WHERE usuario_id=? AND competencia=?', [$id, $comp]); }
}
