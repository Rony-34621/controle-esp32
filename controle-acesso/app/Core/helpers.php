<?php
function base_url(): string { return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'); }
function url(string $p = ''): string { return base_url() . $p; }
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function flash_get(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function fmt_cpf(string $c): string { return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $c); }
function fmt_data(?string $d, string $f = 'd/m/Y'): string { return $d ? date($f, strtotime($d)) : '—'; }
function dias_txt(array $u): string {
  $on = array_filter(DIAS, fn($k) => (int)$u[$k], ARRAY_FILTER_USE_KEY);
  return count($on) === 7 ? 'Todos' : (count($on) ? implode(', ', $on) : 'Nenhum');
}
