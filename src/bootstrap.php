<?php
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

define('RAIZ', dirname(__DIR__));
define('DADOS', RAIZ . '/data');
define('VIEWS', RAIZ . '/views');

require __DIR__ . '/helpers.php';
require __DIR__ . '/Store.php';
require __DIR__ . '/Imagens.php';
require __DIR__ . '/Uploads.php';
require __DIR__ . '/Anexos.php';
require __DIR__ . '/acoes.php';
require __DIR__ . '/biblioteca.php';
require __DIR__ . '/importar.php';
