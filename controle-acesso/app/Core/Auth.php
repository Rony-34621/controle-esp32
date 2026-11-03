<?php
namespace App\Core;

class Auth {
  public static function check(): bool { return !empty($_SESSION['admin_id']); }
  public static function id(): ?int { return $_SESSION['admin_id'] ?? null; }
  public static function nome(): string { return $_SESSION['admin_nome'] ?? ''; }
  public static function login(array $a): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$a['id'];
    $_SESSION['admin_nome'] = $a['nome'];
    $_SESSION['admin_login'] = $a['login'];
  }
  public static function logout(): void { $_SESSION = []; session_destroy(); }
}
