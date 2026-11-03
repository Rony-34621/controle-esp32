<?php
namespace App\Core;

class Validator {
  public static function cpf(string $c): bool {
    if (strlen($c) !== 11 || preg_match('/^(\d)\1{10}$/', $c)) return false;
    for ($t = 9; $t < 11; $t++) {
      for ($d = 0, $i = 0; $i < $t; $i++) $d += (int)$c[$i] * (($t + 1) - $i);
      if ((int)$c[$t] !== ((10 * $d) % 11) % 10) return false;
    }
    return true;
  }
  public static function data(string $s): bool {
    $d = \DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s && $s <= date('Y-m-d');
  }
  public static function competencia(string $s): bool { return (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $s); }
}
