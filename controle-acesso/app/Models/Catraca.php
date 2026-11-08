<?php
namespace App\Models;
use App\Core\Database as DB;

class Catraca {
  public const TIPOS = ['esp32' => 'ESP32', 'esp8266' => 'ESP8266', 'outro' => 'Outro'];

  public static function find(int $id): ?array { return DB::run('SELECT * FROM catracas WHERE id=?', [$id])->fetch() ?: null; }
  public static function porToken(string $token): ?array { return DB::run('SELECT * FROM catracas WHERE token=?', [$token])->fetch() ?: null; }
  public static function todas(): array { return DB::run('SELECT * FROM catracas ORDER BY nome')->fetchAll(); }
  public static function criar(string $nome, string $tipo): array {
    $token = bin2hex(random_bytes(16));
    DB::run('INSERT INTO catracas (nome,tipo,token,ativo,criado_em) VALUES (?,?,?,1,?)', [$nome, $tipo, $token, date('Y-m-d H:i:s')]);
    return [(int)DB::pdo()->lastInsertId(), $token];
  }
  public static function definirAtivo(int $id, int $ativo): void { DB::run('UPDATE catracas SET ativo=? WHERE id=?', [$ativo, $id]); }
  public static function novoToken(int $id): string {
    $token = bin2hex(random_bytes(16));
    DB::run('UPDATE catracas SET token=? WHERE id=?', [$token, $id]);
    return $token;
  }
  /** Marca o último contato (a catraca "deu sinal de vida" na API). */
  public static function tocar(int $id): void { DB::run('UPDATE catracas SET ultimo_contato=? WHERE id=?', [date('Y-m-d H:i:s'), $id]); }
  public static function excluir(int $id): void { DB::run('DELETE FROM catracas WHERE id=?', [$id]); }
}
