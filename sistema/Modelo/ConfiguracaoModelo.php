<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

/**
 * Classe para as configurações do sistema e site
 * @author Jose Tadeu
 */

 class ConfiguracaoModelo extends Modelo
 {
    protected $schema;

    public function __construct(string $slug = 'fuelcontrol')
    {
        $this->schema = $slug;
        parent::__construct('config', $this->schema);
        
    }

    
 }