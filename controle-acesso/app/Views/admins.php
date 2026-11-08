<section><h2>Administradores</h2><div class="tbl"><table><tr><th>Nome</th><th>Login</th><th>Criado em</th><th>Situação</th><th></th></tr>
<?php foreach ($admins as $a): ?><tr><td><?= h($a['nome']) ?></td><td><?= h($a['login']) ?></td><td><?= h(fmt_data($a['criado_em'], 'd/m/Y H:i')) ?></td>
<td><span class="tag <?= $a['ativo'] ? 'sim' : 'nao' ?>"><?= $a['ativo'] ? 'Ativo' : 'Desativado' ?></span></td>
<td><?php if ((int)$a['id'] === \App\Core\Auth::id()): ?><button type="button" class="mini sec" onclick="document.getElementById('modalSenha').showModal()">Trocar minha senha</button> <span class="mut">você</span><?php endif; ?>
<?php if ((int)$a['id'] === (int)$primeiro): ?> <span class="tag sim">Principal</span>
<?php elseif ((int)$a['id'] !== \App\Core\Auth::id()): ?><form method="post" action="<?= url('/admins/' . (int)$a['id'] . '/status') ?>" class="inline"><?= csrf_field() ?>
<button class="mini sec"><?= $a['ativo'] ? 'Desativar' : 'Ativar' ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table></div>
<p class="mut">Cada administrador só pode trocar a própria senha, confirmando a atual. Se alguém esqueceu a senha, desative a conta e crie outra. O administrador <strong>Principal</strong> (primeiro da instalação) nunca pode ser desativado.</p></section>

<section><h2>Novo administrador</h2><form method="post" action="<?= url('/admins') ?>"><?= csrf_field() ?>
<div class="grid"><div><label>Nome</label><input name="nome" required></div>
<div><label>Login</label><input name="login" required pattern="[a-z0-9_.]{3,30}" title="3 a 30 caracteres: letras minúsculas, números, . e _"></div>
<div><label>Senha (mín. 8)</label><input name="senha" type="password" minlength="8" required autocomplete="new-password"></div>
<div><label>Repita a senha</label><input name="senha2" type="password" minlength="8" required autocomplete="new-password"></div></div>
<p><button>Criar administrador</button></p></form></section>

<dialog id="modalSenha">
<h2>Trocar a minha senha</h2>
<form method="post" action="<?= url('/admins/' . \App\Core\Auth::id() . '/senha') ?>"><?= csrf_field() ?>
<div class="grid">
<div><label>Senha atual</label><input name="senha_atual" type="password" required autocomplete="current-password"></div>
<div><label>Nova senha (mín. 8)</label><input name="senha" type="password" minlength="8" required autocomplete="new-password"></div>
<div><label>Repita a nova senha</label><input name="senha2" type="password" minlength="8" required autocomplete="new-password"></div>
</div>
<p style="margin-top:16px"><button>Salvar nova senha</button>
<button type="button" class="sec" onclick="document.getElementById('modalSenha').close()">Cancelar</button></p>
</form></dialog>
