<?php if ($pendentes): ?><section><h2>Chaves aguardando vínculo</h2>
<?php foreach ($pendentes as $c): ?><p><code><?= h($c['uid']) ?></code>
<form method="post" action="<?= url('/chaves/' . (int)$c['id'] . '/vincular') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="volta" value="/">
<select name="usuario" aria-label="Usuário" required><option value="">Escolha o usuário</option><?php foreach ($usuarios as $u) echo '<option value="' . (int)$u['id'] . '">' . h($u['nome']) . '</option>'; ?></select><button class="mini">Vincular</button></form>
<form method="post" action="<?= url('/chaves/' . (int)$c['id'] . '/excluir') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="volta" value="/"><button class="mini sec">Descartar</button></form></p>
<?php endforeach; ?></section><?php endif; ?>
<section><h2>Usuários <a class="btn" style="float:right" href="<?= url('/usuarios/novo') ?>">Novo usuário</a></h2>
<div class="tbl"><table><tr><th>Nome</th><th>CPF</th><th>Situação</th><th>Horário</th><th>Dias</th><th>Chaves</th><th>Mensalidade <?= h(date('m/Y')) ?></th><th>Ações</th></tr>
<?php foreach ($usuarios as $u): $id = (int)$u['id']; ?><tr>
<td><?= h($u['nome']) ?></td><td><?= h(fmt_cpf($u['cpf'])) ?></td>
<td><span class="tag <?= $u['ativo'] === 'S' ? 'sim' : 'nao' ?>"><?= $u['ativo'] === 'S' ? 'Ativo' : 'Desativado' ?></span></td>
<td><?= h(PERIODOS[$u['periodo']]) ?></td><td><?= h(dias_txt($u)) ?></td><td><?= (int)$u['nchaves'] ?></td>
<td><form method="post" action="<?= url("/usuarios/$id/pagamento") ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="volta" value="/">
<input type="hidden" name="comp" value="<?= h($mes) ?>"><input type="hidden" name="valor" value="<?= h($u['valor_mensalidade']) ?>"><input type="hidden" name="pago" value="<?= $u['pago'] ? 0 : 1 ?>">
<span class="tag <?= $u['pago'] ? 'sim' : 'nao' ?>"><?= $u['pago'] ? 'Pago' : 'Em aberto' ?></span><button class="mini sec"><?= $u['pago'] ? 'Desmarcar' : 'Marcar pago' ?></button></form></td>
<td><a class="btn mini sec" href="<?= url("/usuarios/$id") ?>">Abrir</a>
<a class="btn mini sec" href="<?= url("/usuarios/$id") ?>#tags">Tags</a>
<form method="post" action="<?= url("/usuarios/$id/status") ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="volta" value="/"><button class="mini sec"><?= $u['ativo'] === 'S' ? 'Desativar' : 'Ativar' ?></button></form></td></tr>
<?php endforeach; if (!$usuarios) echo '<tr><td colspan="8" class="mut">Nenhum usuário ainda. Clique em “Novo usuário” para começar.</td></tr>'; ?></table></div></section>
