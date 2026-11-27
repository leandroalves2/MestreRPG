<?php
session_start();
require __DIR__ . '/../src/bootstrap.php';

$p = (string) ($_GET['p'] ?? 'home');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($post) {
    csrf_validar();
}

$rotasBinarias = ['img', 'arq', 'backup', 'livro_pdf', 'leitor', 'biblioteca_indice'];
if (!$post && !in_array($p, $rotasBinarias, true)) {
    importar_pacotes();
}

switch ($p) {
    case 'biblioteca':
        act_biblioteca();
        break;
    case 'biblioteca_enviar':
        $post ? act_biblioteca_enviar() : redirecionar(['p' => 'biblioteca']);
        break;
    case 'biblioteca_editar':
        $post ? act_biblioteca_editar() : redirecionar(['p' => 'biblioteca']);
        break;
    case 'biblioteca_excluir':
        $post ? act_biblioteca_excluir() : redirecionar(['p' => 'biblioteca']);
        break;
    case 'biblioteca_indexar':
        act_biblioteca_indexar();
        break;
    case 'biblioteca_indice':
        $post ? act_biblioteca_indice() : redirecionar(['p' => 'biblioteca']);
        break;
    case 'leitor':
        act_leitor();
        break;
    case 'livro_pdf':
        act_livro_pdf();
        break;
    case 'img':
        act_img();
        break;
    case 'arq':
        act_arq();
        break;
    case 'backup':
        act_backup();
        break;
    case 'lote':
        $post ? act_lote() : redirecionar(['p' => 'home']);
        break;
    case 'anexar':
        $post ? act_anexar() : redirecionar(['p' => 'home']);
        break;
    case 'anexo_excluir':
        $post ? act_anexo_excluir() : redirecionar(['p' => 'home']);
        break;
    case 'busca':
        act_busca();
        break;
    case 'panteoes':
        act_panteoes();
        break;
    case 'campanha':
        act_campanha();
        break;
    case 'campanha_nova':
    case 'campanha_editar':
        $post ? act_campanha_salvar() : act_campanha_form();
        break;
    case 'campanha_excluir':
        $post ? act_campanha_excluir() : redirecionar(['p' => 'home']);
        break;
    case 'reg_novo':
    case 'reg_editar':
        $post ? act_reg_salvar() : act_reg_form();
        break;
    case 'slot_enviar':
        $post ? act_slot_enviar() : redirecionar(['p' => 'home']);
        break;
    case 'slot_remover':
        $post ? act_slot_remover() : redirecionar(['p' => 'home']);
        break;
    case 'reordenar':
        $post ? act_reordenar() : redirecionar(['p' => 'home']);
        break;
    case 'reg_ver':
        act_reg_ver();
        break;
    case 'reg_excluir':
        $post ? act_reg_excluir() : redirecionar(['p' => 'home']);
        break;
    default:
        act_home();
}
