<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

/**
 * Classe PostModelo
 *
 * @author Ronaldo Aires
 */
class SolicitacaoDetalheModelo extends Modelo
{

    public function __construct()
    {
        parent::__construct('detalhes_solicitacao');
    }

    /**
     * Busca o usuário pelo ID
     * @return UsuarioModelo|null
     */
    public function usuario(): ?UsuarioModelo
    {
        if ($this->usuario_id) {
            return (new UsuarioModelo())->buscaPorId($this->usuario_id);
        }
        return null;
    }
    
    /**
     * Salva o post com slug
     * @return bool
     */
    public function salvar(): bool
    {
        return parent::salvar();
    }

}
