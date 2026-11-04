<section class="box"><h2>Entrar</h2>
<form method="post" action="<?= url('/login') ?>"><?= csrf_field() ?>
<label for="l">Login</label><input id="l" name="login" required autofocus autocomplete="username">
<label for="s" style="margin-top:12px">Senha</label><input id="s" name="senha" type="password" required autocomplete="current-password">
<p><button>Entrar</button></p></form></section>
