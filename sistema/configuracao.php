<?php

use sistema\Nucleo\Helpers;

//Arquivo de configuração do sistema
//define o fuso horario
date_default_timezone_set('America/Sao_Paulo');

//informações do sistema
define('SITE_NOME', 'construcao');
define('SITE_DESCRICAO', 'Construção - Congregação Jardim Monte Carlo ');

//urls do sistema
define('URL_PRODUCAO', 'https://arthoriustecnologias.com/');
define('URL_DESENVOLVIMENTO', 'http://localhost/construcao');

if (Helpers::localhost()) {
    //dados de acesso ao banco de dados em localhost
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', 'construcao');
    define('DB_USUARIO', 'root');
    define('DB_SENHA', '');

    define('URL_SITE', 'construcao/');
    define('URL_ADMIN', 'construcao/admin/');
} else {
    //dados de acesso ao banco de dados na hospedagem
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', '');
    define('DB_USUARIO', '');
    define('DB_SENHA', '');

    define('URL_SITE', '/');
    define('URL_ADMIN', '/admin/');
}

//autenticação do servidor de emails
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORTA', '465');
define('EMAIL_USUARIO', 'josetadeu33217610@gmail.com');
define('EMAIL_DESTINATARIO', 'josetadeu33217610@gmail.com');
define('EMAIL_SENHA', 'pywzxdzggruasrku');
define('EMAIL_REMETENTE', ['email' => EMAIL_DESTINATARIO, 'nome' => SITE_NOME]);
