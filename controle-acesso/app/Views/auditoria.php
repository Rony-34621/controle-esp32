<section><h2>Auditoria (últimas 200 alterações)</h2><div class="tbl"><table><tr><th>Quando</th><th>Admin</th><th>Ação</th><th>Item</th><th>Detalhes</th><th>IP</th></tr>
<?php foreach ($logs as $l): ?><tr><td><?= h(fmt_data($l['criado_em'], 'd/m/Y H:i:s')) ?></td><td><?= h($l['admin_login'] ?? 'dispositivo') ?></td>
<td><?= h($l['acao']) ?></td><td><?= h($l['entidade'] . ($l['entidade_id'] ? ' #' . $l['entidade_id'] : '')) ?></td>
<td class="mut" style="max-width:340px;word-break:break-word"><?= h($l['detalhes']) ?></td><td><?= h($l['ip']) ?></td></tr>
<?php endforeach; if (!$logs) echo '<tr><td colspan="6" class="mut">Nada registrado ainda.</td></tr>'; ?></table></div></section>
