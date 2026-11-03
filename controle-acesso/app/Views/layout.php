<!doctype html><html lang="pt-br"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($titulo ?? 'Controle de Acesso') ?></title>
<style>
/* Paleta azul escura inspirada no e621, versão refinada */
:root{color-scheme:dark;--bg:#020f23;--fg:#152f56;--sec:#1b3760;--sec5:#25477b;--sec10:#2b538e;--sec-d:#16294a;
--ink:#f2f6fa;--mut:#a9bdd1;--link:#b4c7d9;--hov:#ffffff;--gold:#e8c446;--ac:#2e76b4;
--okbg:#134b27;--okfg:#a6ecb4;--badbg:#6a1f1c;--badfg:#ffc2bf}
*{box-sizing:border-box}
body{margin:0;background:radial-gradient(1200px 500px at 50% -150px,#0b2447 0,var(--bg) 70%) no-repeat,var(--bg);color:var(--ink);font:15px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif;letter-spacing:.1px}
a{color:var(--link)}a:hover{color:var(--hov)}
header{background:var(--fg);border-bottom:1px solid var(--sec10);padding:12px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 4px 18px rgba(0,0,0,.35)}
header a{color:var(--link);text-decoration:none}header a:hover{color:var(--hov)}header a strong{color:var(--gold);font-size:1.1rem;letter-spacing:.3px}
header nav{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
header nav a{padding:6px 12px;border:1px solid transparent;border-radius:8px}
header nav a:hover{border-color:var(--gold);color:var(--hov);text-shadow:0 0 .7px currentColor,0 0 .7px currentColor}
main{max-width:1080px;margin:28px auto;padding:0 18px}
section{background:var(--sec);border:1px solid var(--sec10);border-radius:12px;padding:22px;margin-bottom:22px;box-shadow:0 8px 24px rgba(0,0,0,.28)}
h2{margin:0 0 16px;font-size:1.15rem;font-weight:600}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:12px 10px;border-bottom:1px solid rgba(43,83,142,.7);vertical-align:middle}
th{font-weight:600;color:var(--mut);font-size:.85rem;background:var(--sec-d)}
tr:last-child td{border-bottom:0}tr:hover td{background:rgba(37,71,123,.55)}.tbl{overflow-x:auto;border-radius:8px}
label{display:block;font-weight:600;font-size:.9rem;margin-bottom:6px;color:var(--mut)}
input,select{width:100%;padding:10px 12px;border:1px solid var(--sec10);border-radius:8px;font:inherit;background:var(--bg);color:var(--ink)}
input:hover,select:hover{border-color:#4a7dc0}
/* Botões: separados, e no hover ficam em negrito com borda dourada */
button,.btn{display:inline-block;padding:9px 18px;border:1px solid transparent;border-radius:8px;background:var(--ac);color:#fff;font:inherit;font-weight:500;cursor:pointer;text-decoration:none;line-height:1.3;transition:border-color .15s,background .15s,transform .1s}
button:hover,.btn:hover{border-color:var(--gold);color:#fff;background:#3b86c7;text-shadow:0 0 .7px currentColor,0 0 .7px currentColor}
button:active,.btn:active{transform:translateY(1px)}
button.sec,.btn.sec{background:var(--sec5);border-color:var(--sec10);color:var(--hov)}
button.sec:hover,.btn.sec:hover{background:#2d568f;border-color:var(--gold)}
button.del{background:#7d2623;color:#fff}button.del:hover{background:#963530;border-color:var(--gold)}
button.mini,.btn.mini{padding:5px 12px;font-size:.85rem}
:focus-visible{outline:3px solid var(--gold);outline-offset:2px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px}
.dias{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:12px}
.tag{padding:3px 10px;border-radius:99px;font-size:.8rem;font-weight:600;white-space:nowrap}
.sim{background:var(--okbg);color:var(--okfg)}.nao{background:var(--badbg);color:var(--badfg)}
.msg{padding:12px 16px;border-radius:10px;margin-bottom:20px;background:var(--okbg);border:1px solid #1d743c;color:var(--okfg)}
.msg.erro{background:var(--badbg);border-color:#b13632;color:var(--badfg)}
form.inline{display:inline-flex;gap:10px;align-items:center;margin:3px 10px 3px 0;flex-wrap:wrap}
form.inline select,form.inline input{width:auto}
td form.inline:last-child,td .btn:last-child{margin-right:0}
section p form.inline{margin-left:8px}
.mut{color:var(--mut)}code{background:var(--sec-d);color:var(--hov);padding:2px 7px;border-radius:6px}
.box{max-width:400px;margin:48px auto}
</style></head><body>
<header><a href="<?= url('/') ?>"><strong>Controle de Acesso</strong></a>
<?php if (\App\Core\Auth::check()): ?><nav>
  <a href="<?= url('/') ?>">Usuários</a><a href="<?= url('/acessos') ?>">Acessos</a>
  <a href="<?= url('/admins') ?>">Administradores</a><a href="<?= url('/auditoria') ?>">Auditoria</a>
  <form method="post" action="<?= url('/logout') ?>" class="inline"><?= csrf_field() ?><button class="mini sec">Sair (<?= h(\App\Core\Auth::nome()) ?>)</button></form>
</nav><?php endif; ?></header>
<main><?php if ($f = flash_get()): ?><p class="msg <?= $f[0] === 'erro' ? 'erro' : '' ?>"><?= h($f[1]) ?></p><?php endif; ?>
<?= $content ?></main></body></html>
