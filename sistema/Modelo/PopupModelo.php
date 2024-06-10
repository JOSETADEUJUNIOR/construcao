<?php

namespace sistema\Modelo;

use sistema\Nucleo\Conexao;
use sistema\Nucleo\Modelo;

/**
 * Classe CategoriaModelo
 *
 * @author Ronaldo Aires
 */
class PopupModelo extends Modelo
{

    public function __construct()
    {
        parent::__construct('popup');
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
