<?php

use Pecee\SimpleRouter\SimpleRouter;
use sistema\Nucleo\Helpers;

try {
    //ROTAS SITE
    SimpleRouter::setDefaultNamespace('sistema\Controlador');

    SimpleRouter::get(URL_SITE, 'SiteControlador@index');
    SimpleRouter::get(URL_SITE . 'index', 'SiteControlador@index');
    SimpleRouter::get(URL_SITE . 'portifolio', 'SiteControlador@portifolio');
    SimpleRouter::post(URL_SITE . 'popup', 'SiteControlador@popup');
    SimpleRouter::get(URL_SITE . 'sobre-nos', 'SiteControlador@sobre');
    SimpleRouter::get(URL_SITE . 'post/{categoria}/{slug}', 'SiteControlador@post');
    SimpleRouter::get(URL_SITE . 'categoria/{slug}/{pagina?}', 'SiteControlador@categoria');
    SimpleRouter::post(URL_SITE . 'buscar', 'SiteControlador@buscar');
    SimpleRouter::get(URL_SITE . '404', 'SiteControlador@erro404');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'contato', 'SiteControlador@contato');


    //USUARIOS
    SimpleRouter::match(['get', 'post'], URL_SITE . 'cadastro', 'UsuarioControlador@cadastro');
    SimpleRouter::get(URL_SITE . 'logar', 'UsuarioControlador@logar');
    SimpleRouter::post(URL_SITE . 'login', 'UsuarioControlador@login');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'usuario/confirmar/email/{token}', 'UsuarioControlador@confirmarEmail');

    // SAAS
    SimpleRouter::get(URL_SITE . 'saas', 'SaasControlador@index');
    SimpleRouter::get(URL_SITE . 'saas/cadastrar', 'SaasControlador@cadastrar');
    SimpleRouter::get(URL_SITE . 'saas/sair', 'SaasControlador@sair');
    SimpleRouter::get(URL_SITE . 'saas/chamados', 'SaasControlador@chamados');
    SimpleRouter::get(URL_SITE . 'saas/criarchamado', 'SaasControlador@criar');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'saas/solicitacao/cadastrar', 'SaasControlador@cadastrar');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'saas/solicitacao/editar/{id}', 'SaasControlador@editar');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'saas/solicitacao/deletar/{id}', 'SaasControlador@deletar');
    SimpleRouter::post(URL_ADMIN . 'saas/solicitacao/datatable', 'SaasControlador@datatable');

    //ROTAS ADMIN
    SimpleRouter::group(['namespace' => 'Admin'], function () {

        //ADMIN LOGIN
        SimpleRouter::get(URL_ADMIN, 'AdminLogin@index');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'login', 'AdminLogin@login');

        //DASHBOAD
        SimpleRouter::get(URL_ADMIN . 'dashboard', 'AdminDashboard@dashboard');
        SimpleRouter::get(URL_ADMIN . 'sair', 'AdminDashboard@sair');

        //BANNER
        SimpleRouter::get(URL_ADMIN . 'banner/listar', 'AdminBanner@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'banner/cadastrar', 'AdminBanner@cadastrar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'banner/editar/{id}', 'AdminBanner@editar');
        SimpleRouter::get(URL_ADMIN . 'banner/deletar/{id}', 'AdminBanner@deletar');
        SimpleRouter::get(URL_ADMIN . 'banner/gerar-pdf/{searchTerm}', 'AdminBanner@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'banner/gerar-excel/{searchTerm}', 'AdminBanner@gerarExcel');

        //IRMÃS
        SimpleRouter::get(URL_ADMIN . 'irmas/listar', 'AdminIrmas@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'irmas/cadastrar', 'AdminIrmas@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'irmas/editar/{id}', 'AdminIrmas@editar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'irmas/deletar/{id}', 'AdminIrmas@deletar');
        
        //COMBUSTIVEL
        SimpleRouter::get(URL_ADMIN . 'combustivel/listar', 'AdminCombustivel@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'combustivel/cadastrar', 'AdminCombustivel@cadastrar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'combustivel/editar/{id}', 'AdminCombustivel@editar');
        SimpleRouter::get(URL_ADMIN . 'combustivel/gerar-pdf/{searchTerm}', 'Admincombustivel@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'combustivel/gerar-excel/{searchTerm}', 'Admincombustivel@gerarExcel');

        //TAQUES
        SimpleRouter::get(URL_ADMIN . 'tanque/listar', 'AdminTanque@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'tanque/cadastrar', 'AdminTanque@cadastrar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'tanque/editar/{id}', 'AdminTanque@editar');
        SimpleRouter::get(URL_ADMIN . 'tanque/gerar-pdf/{searchTerm}', 'AdminTanque@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'tanque/gerar-excel/{searchTerm}', 'AdminTanque@gerarExcel');
        
        //BOMBAS
        SimpleRouter::get(URL_ADMIN . 'bomba/listar', 'AdminBomba@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'bomba/cadastrar', 'AdminBomba@cadastrar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'bomba/editar/{id}', 'AdminBomba@editar');
        SimpleRouter::get(URL_ADMIN . 'bomba/gerar-pdf/{searchTerm}', 'AdminBomba@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'bomba/gerar-excel/{searchTerm}', 'AdminBomba@gerarExcel');

        //BICOS
        SimpleRouter::get(URL_ADMIN . 'bico/listar', 'AdminBico@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'bico/cadastrar', 'AdminBico@cadastrar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'bico/editar/{id}', 'AdminBico@editar');
        SimpleRouter::get(URL_ADMIN . 'bico/gerar-pdf/{searchTerm}', 'AdminBico@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'bico/gerar-excel/{searchTerm}', 'AdminBico@gerarExcel');

        //PRODUTOS
        SimpleRouter::get(URL_ADMIN . 'produto/listar', 'AdminProduto@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'produto/cadastrar', 'AdminProduto@cadastrar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'produto/editar/{id}', 'AdminProduto@editar');
        SimpleRouter::get(URL_ADMIN . 'produto/deletar/{id}', 'AdminProduto@deletar');
        SimpleRouter::post(URL_ADMIN . 'produto/datatable', 'AdminProduto@datatable');
        SimpleRouter::get(URL_ADMIN . 'produto/gerar-pdf/{searchTerm}', 'AdminProduto@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'produto/gerar-excel/{searchTerm}', 'AdminProduto@gerarExcel');

        //VENDAS
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/abrir', 'AdminCaixa@abrirCaixa');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/aberto', 'AdminCaixa@aberto');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/venda-produto', 'AdminCaixa@vendaProduto');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/venda-combustivel', 'AdminCaixa@vendaProduto');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/cancelar-produto', 'AdminCaixa@cancelarProduto');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/finalizar-venda', 'AdminCaixa@finalizarVenda');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/lista-produtos', 'AdminCaixa@listaProdutos');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/editar-venda-produto/{id}', 'AdminCaixa@editarVendaProduto');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'caixa/venda-produto/busca', 'AdminCaixa@buscar');
        

        //CLIENTES
        SimpleRouter::get(URL_ADMIN . 'clientes/listar', 'AdminClientes@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'clientes/cadastrar', 'AdminClientes@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'clientes/editar/{id}', 'AdminClientes@editar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'clientes/historicoAluguel', 'AdminClientes@historicoAluguel');
        SimpleRouter::get(URL_ADMIN . 'clientes/deletar/{id}', 'AdminClientes@deletar');
        SimpleRouter::get(URL_ADMIN . 'clientes/relatorioPdfCliente', 'AdminClientes@RelatorioClientePdf');

        //ADMIN USUARIOS
        SimpleRouter::get(URL_ADMIN . 'usuarios/listar', 'AdminUsuarios@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'usuarios/cadastrar', 'AdminUsuarios@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'usuarios/editar/{id}', 'AdminUsuarios@editar');
        SimpleRouter::get(URL_ADMIN . 'usuarios/deletar/{id}', 'AdminUsuarios@deletar');
        SimpleRouter::post(URL_ADMIN . 'usuarios/datatable', 'AdminUsuarios@datatable');

        //ADMIN POSTS
        SimpleRouter::get(URL_ADMIN . 'posts/listar', 'AdminPosts@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'posts/cadastrar', 'AdminPosts@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'posts/editar/{id}', 'AdminPosts@editar');
        SimpleRouter::get(URL_ADMIN . 'posts/deletar/{id}', 'AdminPosts@deletar');
        SimpleRouter::post(URL_ADMIN . 'posts/datatable', 'AdminPosts@datatable');

        //ADMIN CATEGORIAS
        SimpleRouter::get(URL_ADMIN . 'categorias/listar', 'AdminCategorias@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'categorias/cadastrar', 'AdminCategorias@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'categorias/editar/{id}', 'AdminCategorias@editar');
        SimpleRouter::get(URL_ADMIN . 'categorias/deletar/{id}', 'AdminCategorias@deletar');

        //ADMIN SOLICITAÇÔES
        SimpleRouter::get(URL_ADMIN . 'solicitacao/listar', 'AdminSolicitacao@listar');
        SimpleRouter::post(URL_ADMIN . 'solicitacao/datatable', 'AdminSolicitacao@datatable');
        SimpleRouter::post(URL_ADMIN . 'solicitacao/statusChamado/{id}', 'AdminSolicitacao@status');
        SimpleRouter::get(URL_ADMIN . 'solicitacao/deletar/{id}', 'AdminSolicitacao@deletar');

        //ADMIN CONFIGURAÇÔES
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'configuracao/listar', 'AdminConfiguracao@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'configuracao/editar/{id}', 'AdminConfiguracao@editar');
    });

    SimpleRouter::start();
} catch (Pecee\SimpleRouter\Exceptions\NotFoundHttpException $ex) {
    if (Helpers::localhost()) {
        echo $ex->getMessage();
    } else {
        Helpers::redirecionar('404');
    }
}
