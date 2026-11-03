<section class="box"><h2>Criar primeiro administrador</h2>
<p class="mut">Ainda não existe nenhum administrador. Esta tela some assim que o primeiro for criado.</p>
<form method="post" action="<?= url('/instalar') ?>"><?= csrf_field() ?>
<label>Nome</label><input name="nome" required>
<label style="margin-top:10px">Login</label><input name="login" required pattern="[a-z0-9_.]{3,30}" title="3 a 30 caracteres: letras minúsculas, números, . e _">
<label style="margin-top:10px">Senha (mín. 8 caracteres)</label><input name="senha" type="password" minlength="8" required autocomplete="new-password">
<label style="margin-top:10px">Repita a senha</label><input name="senha2" type="password" minlength="8" required autocomplete="new-password">
<p><button>Criar e entrar</button></p></form></section>
