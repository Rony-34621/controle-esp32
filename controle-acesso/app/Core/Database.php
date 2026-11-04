<?php
namespace App\Core;
use PDO, PDOStatement;

final class Database {
  private static ?PDO $pdo = null;
  public static function pdo(): PDO {
    return self::$pdo ??= new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
  }
  /** TRATAMENTO */
  public static function run(string $sql, array $params = []): PDOStatement {
    $s = self::pdo()->prepare($sql);
    $s->execute($params);
    return $s;
  }
}
