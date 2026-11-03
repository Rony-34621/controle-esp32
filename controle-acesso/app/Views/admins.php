<section><h2>Administradores</h2><div class="tbl"><table><tr><th>Nome</th><th>Login</th><th>Criado em</th><th>Situação</th><th></th></tr>
<?php foreach ($admins as $a): ?><tr><td><?= h($a['nome']) ?></td><td><?= h($a['login']) ?></td><td><?= h(fmt_data($a['criado_em'], 'd/m/Y H:i')) ?></td>
<td><span class="tag <?= $a['ativo'] ? 'sim' : 'nao' ?>"><?= $a['ativo'] ? 'Ativo' : 'Desativado' ?></span></td>
<td><?php if ((int)$a['id'] !== \App\Core\Auth::id()): ?><form method="post" action="<?= url('/admins/' . (int)$a['id'] . '/status') ?>" class="inline"><?= csrf_field() ?>
<button class="mini sec"><?= $a['ativo'] ? 'Desativar' : 'Ativar' ?></button></form><?php else: ?><span class="mut">você</span><?php endif; ?></td></tr>
<?php endforeach; ?></table></div></section>
<section><h2>Novo administrador</h2><form method="post" action="<?= url('/admins') ?>"><?= csrf_field() ?>
<div class="grid"><div><label>Nome</label><input name="nome" required></div>
<div><label>Login</label><input name="login" required pattern="[a-z0-9_.]{3,30}" title="3 a 30 caracteres: letras minúsculas, números, . e _"></div>
<div><label>Senha (mín. 8)</label><input name="senha" type="password" minlength="8" required autocomplete="new-password"></div>
<div><label>Repita a senha</label><input name="senha2" type="password" minlength="8" required autocomplete="new-password"></div></div>
<p><button>Criar administrador</button></p></form></section>
