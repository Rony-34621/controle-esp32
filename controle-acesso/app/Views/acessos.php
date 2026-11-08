<section><h2>Log de acessos</h2>
<form method="get" action="<?= url('/acessos') ?>" class="inline" style="margin-bottom:12px">
<select name="usuario" aria-label="Usuário"><option value="">Todos os usuários</option>
<?php foreach ($usuarios as $u) echo '<option value="' . (int)$u['id'] . '"' . ($fu === (int)$u['id'] ? ' selected' : '') . '>' . h($u['nome']) . '</option>'; ?></select>
<input type="date" name="data" value="<?= h($fd) ?>" aria-label="Data"><button class="mini">Filtrar</button><a class="btn mini sec" href="<?= url('/acessos') ?>">Limpar</a></form>
<div class="tbl"><table><tr><th>Data e hora</th><th>Usuário</th><th>Chave</th><th>Catraca</th><th>Resultado</th></tr>
<?php foreach ($acessos as $a): ?><tr><td><?= h(fmt_data($a['data_hora'], 'd/m/Y H:i:s')) ?></td><td><?= h($a['usuario_nome'] ?? '—') ?></td><td><code><?= h($a['uid']) ?></code></td><td><?= h($a['catraca_nome'] ?? '—') ?></td>
<td><span class="tag <?= $a['autorizado'] ? 'sim' : 'nao' ?>"><?= h($a['motivo']) ?></span></td></tr>
<?php endforeach; if (!$acessos) echo '<tr><td colspan="5" class="mut">Nenhum acesso encontrado.</td></tr>'; ?></table></div></section>
