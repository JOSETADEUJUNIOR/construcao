<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\BicoModelo;
use sistema\Modelo\BombaModelo;
use sistema\Nucleo\Sessao;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PostModelo;
use sistema\Modelo\UsuarioModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Modelo\CombustivelModelo;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Modelo\PopupModelo;
use sistema\Modelo\SolicitacaoModelo;
use sistema\Modelo\TanqueModelo;

/**
 * Classe AdminDashboard
 *
 * @author Ronaldo Aires
 */
class AdminDashboard extends AdminControlador
{

    /**
     * Home do admin
     * @return void
     */
    public function dashboard(): void
    {
        $posts       = new PostModelo();
        $combustivel       = new CombustivelModelo();
        $tanque = new TanqueModelo();
        $bomba = new BombaModelo();
        $bico = new BicoModelo();
        $usuarios    = new UsuarioModelo();
        $categorias  = new CategoriaModelo();
        $popup       = new PopupModelo();
        $config      = (new ConfiguracaoModelo())->busca()->resultado(true);
        $solicitacao = new SolicitacaoModelo();
        echo $this->template->renderizar('dashboard.html', [
            'combustiveis' => [
                'combustiveis' => $combustivel->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $combustivel->busca(null,'COUNT(id)','id')->total(),
                'ativo' => $combustivel->busca('status = :s','s=1 COUNT(status)','status')->total(),
                'inativo' => $combustivel->busca('status = :s','s=0 COUNT(status)','status')->total()
            ],
            'posts' => [
                'posts' => $posts->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $posts->busca(null,'COUNT(id)','id')->total(),
                'ativo' => $posts->busca('status = :s','s=1 COUNT(status)','status')->total(),
                'inativo' => $posts->busca('status = :s','s=0 COUNT(status)','status')->total()
            ],
            'categorias' => [
                'categorias' => $categorias->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $categorias->busca()->total(),
                'categoriasAtiva' => $categorias->busca('status = 1')->total(),
                'categoriasInativa' => $categorias->busca('status = 0')->total(),
            ],
            'tanques' => [
                'tanques' => $tanque->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $tanque->busca()->total(),
                'tanquesAtiva' => $tanque->busca('status = 1')->total(),
                'tanquesInativa' => $tanque->busca('status = 0')->total(),
            ],
            'bombas' => [
                'bombas' => $bomba->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $bomba->busca()->total(),
                'bombasAtiva' => $bomba->busca('status = 1')->total(),
                'bombasInativa' => $bomba->busca('status = 0')->total(),
            ],
            'bicos' => [
                'bicos' => $bico->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $bico->busca()->total(),
                'bicosAtivo' => $bico->busca('status = 1')->total(),
                'bicosInativo' => $bico->busca('status = 0')->total(),
            ],
            'solicitacao' => [
                'horasTrabalhadas' => $solicitacao->busca('horas_trabalhadas != 0.00')->totalHoras(),
                'valor_total_hora' => $solicitacao->busca('valor_total_hora != 0.00')->valorTotalHoras(),
            ],
            'popup' => [
                'popup' => $popup->busca()->ordem('id DESC')->limite(1)->resultado(true),
                'total' => $popup->busca()->total(),
               
            ],
            'usuarios' => [
                'logins' => $usuarios->busca()->ordem('ultimo_login DESC')->limite(5)->resultado(true),
                'usuarios' => $usuarios->busca('level != 3')->total(),
                'usuariosAtivo' => $usuarios->busca('status = 1 AND level != 3')->total(),
                'usuariosInativo' => $usuarios->busca('status = 0 AND level != 3')->total(),
                'admin' => $usuarios->busca('level = 3')->total(),
                'adminAtivo' => $usuarios->busca('status = 1 AND level = 3')->total(),
                'adminInativo' => $usuarios->busca('status = 0 AND level = 3')->total()
            ],
            'config' => $config[0]
        ]);
    }

    /**
     * Faz logout do usuário
     * @return void
     */
    public function sair(): void
    {
        $sessao = new Sessao();
        $sessao->limpar('usuarioId');

        $this->mensagem->informa('Você saiu do painel de controle!')->flash();
        Helpers::redirecionar('admin/login');
    }

}
