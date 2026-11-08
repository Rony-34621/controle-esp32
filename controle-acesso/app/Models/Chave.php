<?php
namespace App\Models;
use App\Core\Database as DB;

class Chave {
  public static function find(int $id): ?array { return DB::run('SELECT * FROM chaves WHERE id=?', [$id])->fetch() ?: null; }
  public static function porUid(string $uid): ?array { return DB::run('SELECT * FROM chaves WHERE uid=?', [$uid])->fetch() ?: null; }
  public static function pendentes(): array { return DB::run('SELECT * FROM chaves WHERE usuario_id IS NULL ORDER BY id DESC')->fetchAll(); }
  public static function doUsuario(int $uid): array { return DB::run('SELECT * FROM chaves WHERE usuario_id=?', [$uid])->fetchAll(); }
  public static function todasComDono(): array {
    return DB::run('SELECT c.uid, c.ativo, u.nome AS dono FROM chaves c LEFT JOIN usuarios u ON u.id=c.usuario_id ORDER BY u.nome, c.uid')->fetchAll();
  }
  public static function criarPendente(string $uid): void { DB::run('INSERT INTO chaves (uid,criado_em) VALUES (?,?)', [$uid, date('Y-m-d H:i:s')]); }
  public static function criarVinculada(string $uid, int $usuario): void { DB::run('INSERT INTO chaves (uid,usuario_id,ativo,criado_em) VALUES (?,?,1,?)', [$uid, $usuario, date('Y-m-d H:i:s')]); }
  public static function vincular(int $id, int $usuario): void { DB::run('UPDATE chaves SET usuario_id=? WHERE id=? AND usuario_id IS NULL', [$usuario, $id]); }
  public static function definirAtivo(int $id, int $ativo): void { DB::run('UPDATE chaves SET ativo=? WHERE id=?', [$ativo, $id]); }
  public static function excluir(int $id): void { DB::run('DELETE FROM chaves WHERE id=?', [$id]); }
}
