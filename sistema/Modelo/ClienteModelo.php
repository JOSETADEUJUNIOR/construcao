<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

class ClienteModelo extends Modelo
{
    protected $schema;

    public function __construct(string $slug = '')
    {
        $this->schema = $slug;
        parent::__construct('clientes', $this->schema);
    }




}