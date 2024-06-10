<?php

namespace sistema\Controlador\Admin;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Controlador\UsuarioControlador;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Nucleo\Sessao;

/**
 * Classe AdminControlador
 *
 * @author Ronaldo Aires
 */
class AdminControlador extends Controlador
{
    protected $usuario;

    public function __construct()
    {
        parent::__construct('templates/admin/views');
        
        $this->usuario = UsuarioControlador::usuario();
        
        if(!$this->usuario OR $this->usuario->level != 3){
            $this->mensagem->erro('Faça login para acessar o painel de controle!')->flash();
            
            $sessao = new Sessao();
            $sessao->limpar('usuarioId');
            
            Helpers::redirecionar('admin/login');
        }
    }

     /**
     * Busca usuário pela sessão
     * @return UsuarioModelo|null
     */
    public static function configs(): ?ConfiguracaoModelo
    {
        $configs = (new ConfiguracaoModelo())->busca()->resultado(true);
        return $configs[0];
    }
    
}
