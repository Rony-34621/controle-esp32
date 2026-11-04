<!doctype html><html lang="pt-br"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($titulo ?? 'Controle de Acesso') ?></title>
<style>
/* Tema escuro elegante: fundo grafite-azulado, texto branco em negrito para contraste, detalhes em dourado */
:root{color-scheme:dark;
--bg:#080b12;--bg2:#0d1220;--surface:#111827;--surface2:#172033;--surface3:#1e2a42;
--line:#26334d;--line2:#33436a;
--ink:#ffffff;--mut:#a8b5c9;--link:#dbe5f3;--hov:#ffffff;
--gold:#e6c35c;--gold-d:#b8962f;--ac:#2f6fe0;--ac-h:#4584f5;
--okbg:#0f3d22;--okfg:#c2f7d1;--okbd:#1f7a45;
--badbg:#4d1514;--badfg:#ffd5d2;--badbd:#b13632}
*{box-sizing:border-box}
html{-webkit-font-smoothing:antialiased}
body{margin:0;background:radial-gradient(1100px 480px at 50% -160px,#14203c 0,var(--bg) 72%) no-repeat,var(--bg);color:var(--ink);font:15px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-weight:600;letter-spacing:.15px;min-height:100vh}
a{color:var(--link);font-weight:700;text-underline-offset:3px}a:hover{color:var(--hov)}
::selection{background:var(--gold);color:#000}

/* Cabeçalho */
header{position:sticky;top:0;z-index:10;background:rgba(13,18,32,.88);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-bottom:1px solid var(--line);padding:12px 28px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 6px 24px rgba(0,0,0,.5)}
header a{color:var(--link);text-decoration:none}header a:hover{color:var(--hov)}
header a strong{color:var(--ink);font-size:1.15rem;font-weight:800;letter-spacing:.6px;text-transform:uppercase;border-left:3px solid var(--gold);padding-left:10px}
header nav{display:flex;align-items:center;flex-wrap:wrap;gap:6px}
header nav a{padding:7px 14px;border:1px solid transparent;border-radius:8px;font-weight:700;color:var(--link);transition:background .15s,border-color .15s,color .15s}
header nav a:hover{background:var(--surface2);border-color:var(--gold);color:var(--ink)}

/* Conteúdo */
main{max-width:1080px;margin:32px auto;padding:0 18px}
section{background:linear-gradient(180deg,var(--surface2) 0,var(--surface) 100%);border:1px solid var(--line);border-radius:14px;padding:24px;margin-bottom:24px;box-shadow:0 10px 30px rgba(0,0,0,.45),inset 0 1px 0 rgba(255,255,255,.04)}
h1,h2,h3{color:var(--ink);font-weight:800}
h2{margin:0 0 18px;font-size:1.2rem;letter-spacing:.3px;padding-bottom:10px;border-bottom:1px solid var(--line)}

/* Tabelas */
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:13px 12px;border-bottom:1px solid var(--line);vertical-align:middle}
th{font-weight:800;color:var(--ink);font-size:.78rem;text-transform:uppercase;letter-spacing:.8px;background:var(--bg2);border-bottom:2px solid var(--gold-d)}
td{font-weight:600}
tr:last-child td{border-bottom:0}
tr:hover td{background:rgba(47,111,224,.10)}
.tbl{overflow-x:auto;border-radius:10px;border:1px solid var(--line)}

/* Formulários */
label{display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);text-transform:uppercase;letter-spacing:.5px}
input,select{width:100%;padding:11px 13px;border:1px solid var(--line2);border-radius:9px;font:inherit;font-weight:600;background:var(--bg);color:var(--ink);transition:border-color .15s,box-shadow .15s}
input::placeholder{color:#6f7f99;font-weight:500}
input:hover,select:hover{border-color:#4e66a0}
input:focus,select:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(230,195,92,.2)}

/* Botões */
button,.btn{display:inline-block;padding:10px 20px;border:1px solid rgba(255,255,255,.08);border-radius:9px;background:var(--ac);color:#fff;font:inherit;font-weight:800;letter-spacing:.3px;cursor:pointer;text-decoration:none;line-height:1.3;box-shadow:0 2px 8px rgba(0,0,0,.35);transition:border-color .15s,background .15s,transform .1s,box-shadow .15s}
button:hover,.btn:hover{border-color:var(--gold);color:#fff;background:var(--ac-h);box-shadow:0 4px 14px rgba(47,111,224,.35)}
button:active,.btn:active{transform:translateY(1px)}
button.sec,.btn.sec{background:var(--surface3);border-color:var(--line2);color:#fff}
button.sec:hover,.btn.sec:hover{background:#27385a;border-color:var(--gold);box-shadow:0 4px 14px rgba(0,0,0,.4)}
button.del{background:#8f2a27;color:#fff}button.del:hover{background:#ab3430;border-color:var(--gold);box-shadow:0 4px 14px rgba(171,52,48,.35)}
button.mini,.btn.mini{padding:6px 13px;font-size:.84rem}
:focus-visible{outline:3px solid var(--gold);outline-offset:2px}

/* Layout auxiliar */
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px}
.dias{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:12px}
.tag{padding:3px 11px;border-radius:99px;font-size:.78rem;font-weight:800;white-space:nowrap;letter-spacing:.3px;border:1px solid transparent}
.sim{background:var(--okbg);color:var(--okfg);border-color:var(--okbd)}
.nao{background:var(--badbg);color:var(--badfg);border-color:var(--badbd)}
.msg{padding:13px 18px;border-radius:10px;margin-bottom:22px;background:var(--okbg);border:1px solid var(--okbd);border-left:4px solid #3fc778;color:var(--okfg);font-weight:700}
.msg.erro{background:var(--badbg);border-color:var(--badbd);border-left-color:#ff6b63;color:var(--badfg)}
form.inline{display:inline-flex;gap:10px;align-items:center;margin:3px 10px 3px 0;flex-wrap:wrap}
form.inline select,form.inline input{width:auto}
td form.inline:last-child,td .btn:last-child{margin-right:0}
section p form.inline{margin-left:8px}
.mut{color:var(--mut);font-weight:500}
code{background:var(--bg);color:var(--gold);padding:2px 8px;border-radius:6px;border:1px solid var(--line);font-weight:700}
.box{max-width:400px;margin:56px auto}
</style></head><body>
<header><a href="<?= url('/') ?>"><strong>Controle de Acesso</strong></a>
<?php if (\App\Core\Auth::check()): ?><nav>
  <a href="<?= url('/') ?>">Usuários</a><a href="<?= url('/acessos') ?>">Acessos</a>
  <a href="<?= url('/admins') ?>">Administradores</a><a href="<?= url('/auditoria') ?>">Auditoria</a>
  <form method="post" action="<?= url('/logout') ?>" class="inline"><?= csrf_field() ?><button class="mini sec">Sair (<?= h(\App\Core\Auth::nome()) ?>)</button></form>
</nav><?php endif; ?></header>
<main><?php if ($f = flash_get()): ?><p class="msg <?= $f[0] === 'erro' ? 'erro' : '' ?>"><?= h($f[1]) ?></p><?php endif; ?>
<?= $content ?></main></body></html>
