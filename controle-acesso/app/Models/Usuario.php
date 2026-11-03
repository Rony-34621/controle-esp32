<?php
namespace App\Models;
use App\Core\Database as DB;

class Usuario {
  const COLS = ['nome','cpf','data_nascimento','ativo','periodo','dom','seg','ter','qua','qui','sex','sab','valor_mensalidade'];

  public static function padrao(): array {
    return ['nome'=>'','cpf'=>'','data_nascimento'=>'','ativo'=>'S','periodo'=>'dia_todo','valor_mensalidade'=>'0',
            'dom'=>0,'seg'=>1,'ter'=>1,'qua'=>1,'qui'=>1,'sex'=>1,'sab'=>0];
  }
  public static function find(int $id): ?array { return DB::run('SELECT * FROM usuarios WHERE id=?', [$id])->fetch() ?: null; }
  public static function todos(string $mes): array {
    return DB::run('SELECT u.*, (SELECT COUNT(*) FROM pagamentos p WHERE p.usuario_id=u.id AND p.competencia=?) AS pago,
                    (SELECT COUNT(*) FROM chaves c WHERE c.usuario_id=u.id) AS nchaves FROM usuarios u ORDER BY u.nome', [$mes])->fetchAll();
  }
  public static function criar(array $d): int {
    $ph = implode(',', array_fill(0, count(self::COLS), '?'));
    DB::run('INSERT INTO usuarios (' . implode(',', self::COLS) . ',criado_em) VALUES (' . $ph . ',?)',
            [...array_map(fn($c) => $d[$c], self::COLS), date('Y-m-d H:i:s')]);
    return (int)DB::pdo()->lastInsertId();
  }
  public static function atualizar(int $id, array $d): void {
    $set = implode(',', array_map(fn($c) => "$c=?", self::COLS));
    DB::run("UPDATE usuarios SET $set WHERE id=?", [...array_map(fn($c) => $d[$c], self::COLS), $id]);
  }
  public static function definirAtivo(int $id, string $a): void { DB::run('UPDATE usuarios SET ativo=? WHERE id=?', [$a, $id]); }
  public static function excluir(int $id): void { DB::run('DELETE FROM usuarios WHERE id=?', [$id]); }
}
