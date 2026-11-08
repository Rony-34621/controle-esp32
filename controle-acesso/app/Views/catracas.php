<section><h2>Catracas cadastradas</h2>
<div class="tbl"><table><tr><th>Nome</th><th>Tipo</th><th>Chave da API</th><th>Situação</th><th>Último contato</th><th>Ações</th></tr>
<?php foreach ($catracas as $c): $id = (int)$c['id']; $online = $c['ultimo_contato'] && strtotime($c['ultimo_contato']) >= time() - 120; ?><tr>
<td><?= h($c['nome']) ?></td>
<td><?= h(\App\Models\Catraca::TIPOS[$c['tipo']] ?? $c['tipo']) ?></td>
<td><code><?= h($c['token']) ?></code>
<form method="post" action="<?= url("/catracas/$id/token") ?>" class="inline" onsubmit="return confirm('Gerar outra chave? A atual deixa de valer e o firmware precisa ser atualizado.')"><?= csrf_field() ?><button class="mini sec">Nova chave</button></form></td>
<td><span class="tag <?= (int)$c['ativo'] ? 'sim' : 'nao' ?>"><?= (int)$c['ativo'] ? 'Ativa' : 'Desativada' ?></span></td>
<td><?= $c['ultimo_contato'] ? h(fmt_data($c['ultimo_contato'], 'd/m/Y H:i:s')) : '—' ?> <?php if ($online): ?><span class="tag sim">On-line</span><?php endif; ?></td>
<td><form method="post" action="<?= url("/catracas/$id/status") ?>" class="inline"><?= csrf_field() ?><button class="mini sec"><?= (int)$c['ativo'] ? 'Desativar' : 'Ativar' ?></button></form>
<form method="post" action="<?= url("/catracas/$id/excluir") ?>" class="inline" onsubmit="return confirm('Excluir esta catraca? O firmware dela para de ser aceito.')"><?= csrf_field() ?><button class="mini del">Excluir</button></form></td></tr>
<?php endforeach; if (!$catracas) echo '<tr><td colspan="6" class="mut">Nenhuma catraca cadastrada ainda.</td></tr>'; ?></table></div>
<p class="mut">Desativar faz a API recusar tudo que vier daquela catraca ("Catraca desativada"). No firmware, envie a chave da API no campo <code>token</code> do JSON — requisição sem token segue aceita para firmware antigo.</p></section>

<section><h2>Nova catraca</h2>
<form method="post" action="<?= url('/catracas') ?>"><?= csrf_field() ?>
<div class="grid">
<div><label>Nome / local</label><input name="nome" maxlength="100" placeholder="Catraca entrada principal" required></div>
<div><label>Controladora</label><select name="tipo"><?php foreach (\App\Models\Catraca::TIPOS as $k => $v) echo '<option value="' . $k . '">' . h($v) . '</option>'; ?></select></div>
</div>
<p><button>Cadastrar catraca</button> <span class="mut">A chave da API é gerada na hora e aparece na mensagem de confirmação e na lista.</span></p></form></section>

<section><h2>Catraca virtual (simulador)</h2>
<p class="mut">Simula a passagem de um cartão chamando a mesma API que o ESP32/ESP8266 usa — o resultado entra no log de acessos e a catraca escolhida marca presença (On-line).</p>
<div class="grid">
<div><label>Catraca</label><select id="simCatraca">
<?php foreach ($catracas as $c) echo '<option value="' . h($c['token']) . '">' . h($c['nome']) . ((int)$c['ativo'] ? '' : ' (desativada)') . '</option>'; ?>
<option value="">Sem identificação (firmware antigo)</option>
<option value="token-invalido-de-teste-00000000">Token inválido (teste de recusa)</option>
</select></div>
<div><label>Cartão / tag cadastrado</label><select id="simChave">
<?php foreach ($chaves as $k) echo '<option value="' . h($k['uid']) . '">' . h($k['uid']) . ' — ' . h($k['dono'] ?? 'pendente') . ((int)$k['ativo'] ? '' : ' (desativada)') . '</option>'; ?>
<?php if (!$chaves) echo '<option value="">nenhum cadastrado</option>'; ?>
</select></div>
<div><label>Ou digite um UID</label><input id="simUid" placeholder="vazio = usa o cartão escolhido"></div>
</div>
<p><button type="button" id="simBtn" onclick="simular()">Passar cartão</button> <span id="simResultado"></span></p></section>

<section><h2>Testar via curl</h2>
<p class="mut">O mesmo teste do simulador, pela linha de comando — o comando abaixo acompanha a catraca e o cartão escolhidos acima. O campo <code>token</code> é a chave da API da catraca; sem ele a requisição é aceita como firmware antigo.</p>
<p><label style="display:inline">Formato para</label> <select id="simShell" style="width:auto">
<option value="bash">Linux / macOS / Git Bash</option>
<option value="win">Windows (cmd / PowerShell)</option>
</select></p>
<p><strong>Verificar acesso</strong> <button type="button" class="mini sec" onclick="copiarCurl('curlAcesso', this)">Copiar</button></p>
<div class="tbl"><pre id="curlAcesso" style="margin:0;padding:14px;white-space:pre-wrap;word-break:break-all"></pre></div>
<p style="margin-top:14px"><strong>Enviar chave para cadastro (modo cadastro)</strong> <button type="button" class="mini sec" onclick="copiarCurl('curlPendente', this)">Copiar</button></p>
<div class="tbl"><pre id="curlPendente" style="margin:0;padding:14px;white-space:pre-wrap;word-break:break-all"></pre></div>
<p class="mut">No Windows (PowerShell), use <code>curl.exe</code> no lugar de <code>curl</code>. Resposta esperada: <code>{"authorized":true,"message":"Acesso permitido"}</code> — e a tentativa aparece no log de acessos.</p></section>

<script>
var API_ACESSO   = <?= json_encode((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('/api/acesso')) ?>;
var API_PENDENTE = <?= json_encode((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('/api/chaves/pendente')) ?>;

function montarCurl() {
  var uid = (document.getElementById('simUid').value.trim() || document.getElementById('simChave').value || 'A1:B2:C3:D4').toUpperCase();
  var token = document.getElementById('simCatraca').value;
  var corpo = token ? '{"uid":"' + uid + '","token":"' + token + '"}' : '{"uid":"' + uid + '"}';
  // cmd/PowerShell não entendem aspas simples: lá o corpo vai entre aspas duplas, com as internas escapadas
  var dados = document.getElementById('simShell').value === 'win'
    ? '"' + corpo.replace(/"/g, '\\"') + '"'
    : "'" + corpo + "'";
  document.getElementById('curlAcesso').textContent =
    "curl -X POST " + API_ACESSO + " -H \"Content-Type: application/json\" -d " + dados;
  document.getElementById('curlPendente').textContent =
    "curl -X POST " + API_PENDENTE + " -H \"Content-Type: application/json\" -d " + dados;
}
function copiarCurl(id, btn) {
  navigator.clipboard.writeText(document.getElementById(id).textContent).then(function () {
    var t = btn.textContent; btn.textContent = 'Copiado!';
    setTimeout(function () { btn.textContent = t; }, 1500);
  });
}
['simCatraca', 'simChave', 'simUid', 'simShell'].forEach(function (id) {
  document.getElementById(id).addEventListener('input', montarCurl);
});
montarCurl();

async function simular() {
  var btn = document.getElementById('simBtn'), out = document.getElementById('simResultado');
  var uid = (document.getElementById('simUid').value.trim() || document.getElementById('simChave').value).toUpperCase();
  var token = document.getElementById('simCatraca').value;
  if (!uid) { out.innerHTML = '<span class="tag nao">Escolha ou digite um UID</span>'; return; }
  btn.disabled = true; out.textContent = 'consultando...';
  try {
    var corpo = { uid: uid };
    if (token) corpo.token = token;
    var r = await fetch('<?= url('/api/acesso') ?>', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(corpo) });
    var j = await r.json();
    var span = document.createElement('span');
    span.className = 'tag ' + (j.authorized ? 'sim' : 'nao');
    span.textContent = (j.authorized ? 'LIBERADO' : 'NEGADO') + ' — ' + (j.message || '');
    out.innerHTML = '';
    out.appendChild(span);
  } catch (e) {
    out.innerHTML = '<span class="tag nao">Falha ao chamar a API</span>';
  }
  btn.disabled = false;
}
</script>
