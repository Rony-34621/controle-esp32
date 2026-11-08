<?php
/**
 * Dados de demonstração: usuários, tags/cartões e um feed de
 * tentativas de abertura (permitidas e negadas) em 06/11/2026, 20h às 21h.
 *
 * Rodar na linha de comando, a partir de qualquer pasta:
 *   php database/seed_demo.php
 *
 * Pode rodar mais de uma vez: usuários e chaves são reaproveitados pelo
 * CPF/UID e o feed da janela de demonstração é apagado e recriado.
 */
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/Database.php';

use App\Core\Database as DB;

$DIA = '2026-11-06';
$COMP = substr($DIA, 0, 7); // competência da mensalidade (2026-11)

// nome, cpf, cadastro ativo, período, mensalidade, pagou o mês?
$usuarios = [
  ['Ana Souza',    '10000000001', 'S', 'dia_todo', 120.00, true],
  ['Bruno Lima',   '10000000002', 'S', 'dia_todo', 120.00, false], // mensalidade em aberto
  ['Carla Mendes', '10000000003', 'S', 'noite',    150.00, true],
  ['Diego Rocha',  '10000000004', 'N', 'dia_todo', 120.00, true],  // cadastro desativado
  ['Elisa Prado',  '10000000005', 'S', 'manha',    100.00, true],  // só manhã: à noite dá "Fora do horario"
];

// nome => tipo — a chave da API é gerada aleatória no primeiro insert (aparece na tela de Catracas)
$catracas = [
  'Catraca Entrada Principal' => 'esp32',
  'Porta dos Fundos'          => 'esp8266',
];

// uid => [cpf do dono (null = pendente), chave ativa?]
$chaves = [
  'A1:B2:C3:D4' => ['10000000001', 1],
  '5E:6F:70:81' => ['10000000002', 1],
  '92:A3:B4:C5' => ['10000000003', 1],
  'D6:E7:F8:09' => ['10000000003', 0], // segunda tag da Carla, desativada no painel
  '1A:2B:3C:4D' => ['10000000004', 1],
  '5F:60:71:82' => ['10000000005', 1],
  '93:A4:B5:C6' => [null, 1],          // pendente, como se viesse do modo cadastro do ESP32
];

// hora, uid, cpf do usuário (null quando a chave não é de ninguém), autorizado, motivo, catraca (E=entrada, F=fundos)
$feed = [
  ['20:02:41', 'A1:B2:C3:D4', '10000000001', 1, 'Acesso permitido',      'E'],
  ['20:05:17', '5E:6F:70:81', '10000000002', 0, 'Mensalidade em aberto', 'E'],
  ['20:06:02', '5E:6F:70:81', '10000000002', 0, 'Mensalidade em aberto', 'E'],
  ['20:09:55', '92:A3:B4:C5', '10000000003', 1, 'Acesso permitido',      'E'],
  ['20:14:28', 'FF:00:AA:11', null,          0, 'Chave desconhecida',    'F'],
  ['20:15:03', 'FF:00:AA:11', null,          0, 'Chave desconhecida',    'F'],
  ['20:18:36', '1A:2B:3C:4D', '10000000004', 0, 'Cadastro inativo',      'E'],
  ['20:22:10', 'A1:B2:C3:D4', '10000000001', 1, 'Acesso permitido',      'E'],
  ['20:26:49', '93:A4:B5:C6', null,          0, 'Chave pendente',        'E'],
  ['20:31:22', 'D6:E7:F8:09', '10000000003', 0, 'Chave desativada',      'F'],
  ['20:32:05', 'D6:E7:F8:09', '10000000003', 0, 'Chave desativada',      'F'],
  ['20:33:40', '92:A3:B4:C5', '10000000003', 1, 'Acesso permitido',      'E'],
  ['20:38:11', '5F:60:71:82', '10000000005', 0, 'Fora do horario',       'E'],
  ['20:41:57', 'A1:B2:C3:D4', '10000000001', 1, 'Acesso permitido',      'E'],
  ['20:45:33', '1A:2B:3C:4D', '10000000004', 0, 'Cadastro inativo',      'F'],
  ['20:49:08', 'FF:00:AA:11', null,          0, 'Chave desconhecida',    'E'],
  ['20:52:46', '5E:6F:70:81', '10000000002', 0, 'Mensalidade em aberto', 'E'],
  ['20:55:19', '5F:60:71:82', '10000000005', 0, 'Fora do horario',       'F'],
  ['20:57:51', '92:A3:B4:C5', '10000000003', 1, 'Acesso permitido',      'E'],
  ['20:59:34', 'A1:B2:C3:D4', '10000000001', 1, 'Acesso permitido',      'E'],
];

echo "Semeando usuários e pagamentos...\n";
$ids = []; $nomes = [];
foreach ($usuarios as [$nome, $cpf, $ativo, $periodo, $valor, $pagou]) {
  $nomes[$cpf] = $nome;
  $u = DB::run('SELECT id FROM usuarios WHERE cpf=?', [$cpf])->fetch();
  if ($u) {
    $ids[$cpf] = (int)$u['id'];
  } else {
    DB::run('INSERT INTO usuarios (nome,cpf,ativo,periodo,dom,seg,ter,qua,qui,sex,sab,valor_mensalidade,criado_em)
             VALUES (?,?,?,?,1,1,1,1,1,1,1,?,?)', [$nome, $cpf, $ativo, $periodo, $valor, "$DIA 10:00:00"]);
    $ids[$cpf] = (int)DB::pdo()->lastInsertId();
  }
  if ($pagou) DB::run('INSERT IGNORE INTO pagamentos (usuario_id,competencia,valor,pago_em) VALUES (?,?,?,?)',
                      [$ids[$cpf], $COMP, $valor, "$DIA 10:05:00"]);
}

echo "Semeando tags e cartões...\n";
foreach ($chaves as $uid => [$cpf, $ativa]) {
  $c = DB::run('SELECT id FROM chaves WHERE uid=?', [$uid])->fetch();
  if ($c) DB::run('UPDATE chaves SET usuario_id=?, ativo=? WHERE id=?', [$cpf ? $ids[$cpf] : null, $ativa, $c['id']]);
  else    DB::run('INSERT INTO chaves (uid,usuario_id,ativo,criado_em) VALUES (?,?,?,?)',
                  [$uid, $cpf ? $ids[$cpf] : null, $ativa, "$DIA 10:10:00"]);
}

echo "Semeando catracas...\n";
$catIds = []; // E => id da primeira, F => id da segunda
foreach ($catracas as $nome => $tipo) {
  $c = DB::run('SELECT id FROM catracas WHERE nome=?', [$nome])->fetch();
  if (!$c) {
    $token = bin2hex(random_bytes(16));
    DB::run('INSERT INTO catracas (nome,tipo,token,ativo,criado_em) VALUES (?,?,?,1,?)', [$nome, $tipo, $token, "$DIA 09:00:00"]);
    $c = ['id' => DB::pdo()->lastInsertId()];
    echo "  $nome — chave da API: $token\n";
  }
  $catIds[count($catIds) ? 'F' : 'E'] = (int)$c['id'];
}

echo "Recriando o feed de {$DIA} 20h–21h...\n";
$uids = array_unique(array_merge(array_keys($chaves), array_column($feed, 1)));
$in = implode(',', array_fill(0, count($uids), '?'));
// apaga só as linhas da própria demonstração (UIDs do seed) dentro da janela
DB::run("DELETE FROM acessos WHERE data_hora BETWEEN ? AND ? AND uid IN ($in)",
        ["$DIA 20:00:00", "$DIA 21:00:00", ...$uids]);
foreach ($feed as [$hora, $uid, $cpf, $ok, $motivo, $cat]) {
  DB::run('INSERT INTO acessos (uid,usuario_id,usuario_nome,catraca_id,autorizado,motivo,data_hora) VALUES (?,?,?,?,?,?,?)',
          [$uid, $cpf ? $ids[$cpf] : null, $cpf ? $nomes[$cpf] : null, $catIds[$cat], $ok, $motivo, "$DIA $hora"]);
}
// último contato coerente com o feed (a catraca "deu sinal" na última leitura)
DB::run('UPDATE catracas SET ultimo_contato=? WHERE id=?', ["$DIA 20:59:34", $catIds['E']]);
DB::run('UPDATE catracas SET ultimo_contato=? WHERE id=?', ["$DIA 20:55:19", $catIds['F']]);

$ok  = count(array_filter($feed, fn($f) => $f[3]));
$neg = count($feed) - $ok;
echo "Pronto: " . count($usuarios) . " usuários, " . count($chaves) . " chaves, " . count($feed) . " acessos ($ok permitidos, $neg negados).\n";
