<?php
namespace App\Models;
use App\Core\Database as DB;

class Admin {
  public static function find(int $id): ?array { return DB::run('SELECT id,nome,login,ativo FROM admins WHERE id=?', [$id])->fetch() ?: null; }
  public static function porLogin(string $l): ?array { return DB::run('SELECT * FROM admins WHERE login=?', [$l])->fetch() ?: null; }
  public static function total(): int { return (int)DB::run('SELECT COUNT(*) FROM admins')->fetchColumn(); }
  public static function todos(): array { return DB::run('SELECT id,nome,login,ativo,criado_em FROM admins ORDER BY nome')->fetchAll(); }
  public static function criar(string $nome, string $login, string $hash): int {
    DB::run('INSERT INTO admins (nome,login,senha_hash,ativo,criado_em) VALUES (?,?,?,1,?)', [$nome, $login, $hash, date('Y-m-d H:i:s')]);
    return (int)DB::pdo()->lastInsertId();
  }
  public static function definirAtivo(int $id, int $a): void { DB::run('UPDATE admins SET ativo=? WHERE id=?', [$a, $id]); }
}
