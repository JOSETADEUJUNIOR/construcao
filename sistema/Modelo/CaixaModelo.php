<?php

namespace sistema\Modelo;

use sistema\Nucleo\Conexao;
use sistema\Nucleo\Modelo;

/**
 * Classe CategoriaModelo
 *
 * @author Ronaldo Aires
 */
class CaixaModelo extends Modelo
{

    public function __construct()
    {
        parent::__construct('caixa');
    }

    
/**
     * Busca a categoria pelo ID
     * @return CategoriaModelo|null
     */
    public function categoria(): ?CategoriaModelo
    {
        if ($this->categoria_id) {
            return (new CategoriaModelo())->buscaPorId($this->categoria_id);
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
