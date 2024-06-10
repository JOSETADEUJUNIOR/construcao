<?php

namespace sistema\Modelo;

use sistema\Nucleo\Conexao;
use sistema\Nucleo\Modelo;

/**
 * Classe CategoriaModelo
 *
 * @author Ronaldo Aires
 */
class TanqueModelo extends Modelo
{

    public function __construct()
    {
        parent::__construct('tanques');
    }

    /**
     * Retorna o total de posts de uma categoria
     * @param int $categoriaId
     * @return int
     */
    /**
     * Salva o post com slug
     * @return bool
     */
    public function salvar(): bool
    {
        return parent::salvar();
    }

}
