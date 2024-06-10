<?php

namespace sistema\Controlador\Admin;

use DateTime;
use sistema\Controlador\UsuarioControlador;
use sistema\Modelo\CategoriaModelo;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PostModelo;
use sistema\Modelo\SolicitacaoModelo;
use sistema\Modelo\UsuarioModelo;

/**
 * Classe AdminCategorias
 *
 * @author Ronaldo Aires
 */
class AdminSolicitacao extends AdminControlador
{


    public function datatable(): void
    {
        $datatable = $_REQUEST;
        $datatable = filter_var_array($datatable, FILTER_SANITIZE_SPECIAL_CHARS);

        $limite = $datatable['length'];
        $offset = $datatable['start'];
        $busca = $datatable['search']['value'];

        $colunas = [
            0 => 'id',
            2 => 'titulo',
            3 => 'usuario_id',
            4 => 'Status',
            5 => 'prioridade',
        ];

        $ordem = " " . $colunas[$datatable['order'][0]['column']] . " ";
        $ordem .= " " . $datatable['order'][0]['dir'] . " ";

        $posts = new SolicitacaoModelo();

        if (empty($busca)) {
            $posts->busca()->ordem($ordem)->limite($limite)->offset($offset);
            $total = (new SolicitacaoModelo())->busca(null, 'COUNT(id)', 'id')->total();
        } else {
            $posts->busca("id LIKE '%{$busca}%' OR titulo LIKE '%{$busca}%' ")->limite($limite)->offset($offset);
            $total = $posts->total();
        }

        $dados = [];

        if ($posts->resultado(true)) {
            $usuarios = (new UsuarioModelo())->busca()->resultado(true);
            foreach ($posts->resultado(true) as $post) {
                $nomeUsuario = '';
                foreach ($usuarios as $usuario) {
                    if ($usuario->id == $post->usuario_id) {
                        $nomeUsuario = $usuario->nome;
                        break; // Uma vez que encontramos o usuário correspondente, podemos parar de iterar
                    }
                }
                $dados[] = [
                    $post->id,
                    $post->titulo,
                    $nomeUsuario, // Aqui atribuímos o nome do usuário ao invés do ID
                    $post->status,
                    $post->prioridade,
                    Helpers::contarTempo($post->cadastrado_em),
                    $post->descricao,
                    $post->fotos,
                    $post->video,
                    $post->parecer_tecnico,
                    $post->valor_hora,
                ];
            }
        }
        


        $retorno = [
            "draw" => $datatable['draw'],
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $dados
        ];

        echo json_encode($retorno);
    }


    public function status($id)
    {
        $solicitacao = (new SolicitacaoModelo())->buscaPorId($id);
    
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDadosParecer($dados)) {
                $solicitacao->status = $dados['status'];
                $solicitacao->parecer_tecnico = $dados['parecer'];
                $solicitacao->atualizado_em = date('Y-m-d H:i:s');
    
                if ($dados['status'] == '2') {
                    // Registra a hora inicial
                    $solicitacao->hora_inicial = date('H:i'); // Apenas horas e minutos
                } elseif ($dados['status'] == '3') {
                    // Calcula a diferença de tempo entre a hora inicial e a hora final
                    list($hora_inicial, $minuto_inicial) = explode(':', $solicitacao->hora_inicial);
                    list($hora_final, $minuto_final) = explode(':', date('H:i'));
            
                    // Calcula a diferença de horas e minutos
                    $horas_diferenca = $hora_final - $hora_inicial;
                    $minutos_diferenca = $minuto_final - $minuto_inicial;
            
                    // Se os minutos resultarem em um valor negativo, ajusta as horas
                    if ($minutos_diferenca < 0) {
                        $minutos_diferenca += 60;
                        $horas_diferenca--;
                    }
                    
                    // Armazena as horas trabalhadas no banco de dados
                    $solicitacao->hora_final = date('H:i'); // Apenas horas e minutos
                    $solicitacao->horas_trabalhadas = $horas_diferenca + ($minutos_diferenca / 60);
                    $solicitacao->valor_total_hora = $solicitacao->horas_trabalhadas * 60;
                }
              
                if ($solicitacao->salvar()) {
                    $this->mensagem->sucesso('Parecer inserido com sucesso')->flash();
                    Helpers::redirecionar('admin/solicitacao/listar');
                } else {
                    $this->mensagem->erro($solicitacao->erro())->flash();
                    Helpers::redirecionar('admin/solicitacao/listar');
                }
            }
        }
    }
    

    /**
     * Lista categorias
     * @return void
     */
    public function listar(): void
    {
        $solicitacoes = new SolicitacaoModelo();
        $usuario = new UsuarioModelo();

        echo $this->template->renderizar('solicitacao/listar.html', [
            'solicitacoes' => $solicitacoes->busca()->ordem('titulo ASC')->resultado(true),
            'usuarios' => $usuario->busca()->resultado(true),
            'total' => [
                'solicitacao'               => $solicitacoes->busca()->total(),
                'solicitacaoAndamento'      => $solicitacoes->busca("status = 2")->total(),
                'solicitacaoAberto'         => $solicitacoes->busca("status = 1")->total(),
                'solicitacaoEncerrado'      => $solicitacoes->busca("status = 3")->total(),
            ]
        ]);
    }

    /**
     * Cadastra uma categoria
     * @return void
     */
    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $categoria = new CategoriaModelo();

                $categoria->usuario_id = $this->usuario->id;
                $categoria->slug = Helpers::slug($dados['titulo']);
                $categoria->titulo = $dados['titulo'];
                $categoria->texto = $dados['texto'];
                $categoria->status = $dados['status'];

                if ($categoria->salvar()) {
                    $this->mensagem->sucesso('Categoria cadastrada com sucesso')->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                } else {
                    $this->mensagem->erro($categoria->erro())->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                }
            }
        }

        echo $this->template->renderizar('categorias/formulario.html', [
            'categoria' => $dados
        ]);
    }

    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $categoria = (new CategoriaModelo())->buscaPorId($id);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $categoria = (new CategoriaModelo())->buscaPorId($categoria->id);

                $categoria->usuario_id = $this->usuario->id;
                $categoria->slug = Helpers::slug($dados['titulo']);
                $categoria->titulo = $dados['titulo'];
                $categoria->texto = $dados['texto'];
                $categoria->status = $dados['status'];
                $categoria->atualizado_em = date('Y-m-d H:i:s');

                if ($categoria->salvar()) {
                    $this->mensagem->sucesso('Categoria atualizada com sucesso')->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                } else {
                    $this->mensagem->erro($categoria->erro())->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                }
            }
        }

        echo $this->template->renderizar('categorias/formulario.html', [
            'categoria' => $categoria
        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty($dados['titulo'])) {
            $this->mensagem->alerta('Escreva um título para a Categoria!')->flash();
            return false;
        }
        return true;
    }
    private function validarDadosParecer(array $dados): bool
    {
        if (empty($dados['parecer'])) {
            $this->mensagem->alerta('Escreva um parecer para o chamado!')->flash();
            return false;
        }
        return true;
    }

    /**
     * Deleta uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function deletar(int $id): void
    {
        if (is_int($id)) {
            $solicitacao = (new SolicitacaoModelo())->buscaPorId($id);
            $solicitacao->status = '4';//cancelado
          
            if (!$solicitacao) {
                $this->mensagem->alerta('O solicitacao que você está tentando deletar não existe!')->flash();
                Helpers::redirecionar('admin/solicitacao/listar');
            } else {
                if ($solicitacao->salvar()) {
                    $this->mensagem->sucesso('solicitacão deletada com sucesso!')->flash();
                    Helpers::redirecionar('admin/solicitacao/listar');
                } else {
                    $this->mensagem->erro($solicitacao->erro())->flash();
                    Helpers::redirecionar('admin/solicitacao/listar');
                }
            }
        }
    }

}
