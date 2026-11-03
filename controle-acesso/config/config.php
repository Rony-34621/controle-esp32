<?php
date_default_timezone_set('America/Sao_Paulo');

// Deixei o login padrão, caso queiram mudar é só alterar o USER e o PASS. Importe database/schema.sql no phpMyAdmin.
const DB_HOST = 'localhost';
const DB_NAME = 'controle_acesso';
const DB_USER = 'root';
const DB_PASS = '';

const DIAS = ['dom'=>'Dom','seg'=>'Seg','ter'=>'Ter','qua'=>'Qua','qui'=>'Qui','sex'=>'Sex','sab'=>'Sáb'];
const PERIODOS = ['manha'=>'Manhã (05h–11h59)','tarde'=>'Tarde (12h–17h59)','noite'=>'Noite (18h–04h59)','dia_todo'=>'Todo o dia'];
