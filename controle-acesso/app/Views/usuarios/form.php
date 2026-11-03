<?php $nasc = $u['data_nascimento'] ?? ''; $volta = "/usuarios/$id"; ?>
<section><h2><?= $id ? 'Editar usuário' : 'Novo usuário' ?></h2>
<?php if ($erro): ?><p class="msg erro"><?= h($erro) ?></p><?php endif; ?>
<form method="post" action="<?= url($id ? "/usuarios/$id" : '/usuarios') ?>"><?= csrf_field() ?>
<div class="grid">
<div><label>Nome</label><input name="nome" value="<?= h($u['nome']) ?>" maxlength="100" required></div>
<div><label>CPF</label><input name="cpf" value="<?= h(strlen((string)$u['cpf']) === 11 ? fmt_cpf($u['cpf']) : $u['cpf']) ?>" inputmode="numeric" placeholder="000.000.000-00" required></div>
<div><label>Data de nascimento</label><input type="date" name="nasc" value="<?= h($nasc) ?>"></div>
<div><label>Cadastro ativo?</label><select name="ativo"><option value="S" <?= $u['ativo'] === 'S' ? 'selected' : '' ?>>S – Ativo</option><option value="N" <?= $u['ativo'] === 'N' ? 'selected' : '' ?>>N – Desativado</option></select></div>
<div><label>Horário permitido</label><select name="periodo"><?php foreach (PERIODOS as $k => $v) echo '<option value="' . $k . '"' . ($u['periodo'] === $k ? ' selected' : '') . '>' . h($v) . '</option>'; ?></select></div>
<div><label>Mensalidade (R$)</label><input name="valor" value="<?= h(number_format((float)$u['valor_mensalidade'], 2, ',', '')) ?>"></div></div>
<h2 style="margin-top:20px">Dias com acesso</h2><div class="dias">
<?php foreach (DIAS as $k => $v): ?><div><label><?= h($v) ?></label><select name="d_<?= $k ?>"><option value="1" <?= $u[$k] ? 'selected' : '' ?>>Sim</option><option value="0" <?= !$u[$k] ? 'selected' : '' ?>>Não</option></select></div><?php endforeach; ?></div>
<p style="margin-top:18px"><button>Salvar cadastro</button> <a class="btn sec" href="<?= url('/') ?>">Voltar</a></p></form></section>
<?php if ($id): ?>
<section><h2>Pagamentos</h2>
<form method="post" action="<?= url("/usuarios/$id/pagamento") ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="pago" value="1">
<input type="month" name="comp" value="<?= h(date('Y-m')) ?>" required aria-label="Mês"><input name="valor" value="<?= h(number_format((float)$u['valor_mensalidade'], 2, ',', '')) ?>" size="8" aria-label="Valor"><button>Marcar como pago</button></form>
<div class="tbl"><table><tr><th>Mês</th><th>Valor</th><th>Marcado em</th><th></th></tr>
<?php foreach ($pagamentos as $p): ?><tr><td><?= h($p['competencia']) ?></td><td>R$ <?= number_format((float)$p['valor'], 2, ',', '.') ?></td><td><?= h(fmt_data($p['pago_em'], 'd/m/Y H:i')) ?></td>
<td><form method="post" action="<?= url("/usuarios/$id/pagamento") ?>" class="inline" onsubmit="return confirm('Desmarcar este pagamento?')"><?= csrf_field() ?><input type="hidden" name="pago" value="0"><input type="hidden" name="comp" value="<?= h($p['competencia']) ?>"><button class="mini del">Desmarcar</button></form></td></tr>
<?php endforeach; if (!$pagamentos) echo '<tr><td colspan="4" class="mut">Nenhum pagamento marcado ainda.</td></tr>'; ?></table></div></section>
<section><h2>Chaves RFID</h2>
<?php foreach ($chaves as $c): ?><p><code><?= h($c['uid']) ?></code> <form method="post" action="<?= url('/chaves/' . (int)$c['id'] . '/excluir') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="volta" value="<?= h($volta) ?>"><button class="mini del">Remover</button></form></p>
<?php endforeach; if (!$chaves) echo '<p class="mut">Nenhuma chave vinculada.</p>'; ?>
<?php if ($pendentes): ?><form method="post" action="" class="inline" onsubmit="this.action='<?= url('/chaves/') ?>'+this.chave.value+'/vincular'"><?= csrf_field() ?><input type="hidden" name="usuario" value="<?= $id ?>"><input type="hidden" name="volta" value="<?= h($volta) ?>">
<select name="chave" aria-label="Chave pendente"><?php foreach ($pendentes as $c) echo '<option value="' . (int)$c['id'] . '">' . h($c['uid']) . '</option>'; ?></select><button>Vincular chave pendente</button></form>
<?php else: ?><p class="mut">Sem chaves pendentes. Passe o cartão mestre no leitor para ativar o modo cadastro e aproxime a chave nova.</p><?php endif; ?></section>
<section><h2>Últimos acessos</h2><div class="tbl"><table><tr><th>Data e hora</th><th>Resultado</th></tr>
<?php foreach ($acessos as $a): ?><tr><td><?= h(fmt_data($a['data_hora'], 'd/m/Y H:i:s')) ?></td><td><span class="tag <?= $a['autorizado'] ? 'sim' : 'nao' ?>"><?= h($a['motivo']) ?></span></td></tr>
<?php endforeach; if (!$acessos) echo '<tr><td colspan="2" class="mut">Nenhum acesso registrado.</td></tr>'; ?></table></div>
<p><a href="<?= url('/acessos?usuario=' . $id) ?>">Ver histórico completo</a></p></section>
<section><h2>Excluir usuário</h2><p class="mut">Apaga o cadastro e o histórico de pagamentos. Para bloquear só por um tempo, use “Desativar”.</p>
<form method="post" action="<?= url("/usuarios/$id/excluir") ?>" onsubmit="return confirm('Excluir este usuário e seus pagamentos?')"><?= csrf_field() ?><button class="del">Excluir usuário</button></form></section>
<?php endif; ?>
