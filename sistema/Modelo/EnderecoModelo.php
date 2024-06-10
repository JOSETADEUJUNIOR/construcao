<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

class EnderecoModelo extends Modelo
{
    protected $schema;

    public function __construct(string $slug = '')
    {
        $this->schema = $slug;
        parent::__construct('enderecos', $this->schema);
    }




}