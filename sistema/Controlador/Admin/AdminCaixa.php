<?php

namespace sistema\Controlador\Admin;

use LDAP\Result;
use sistema\Modelo\PostModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Modelo\GaleriaModelo;
use sistema\Modelo\ProdutoModelo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Modelo\BicoModelo;
use sistema\Modelo\CaixaModelo;
use sistema\Modelo\ClienteModelo;
use sistema\Modelo\CombustivelModelo;
use sistema\Modelo\VendaModelo;
use sistema\Modelo\VendaProdutoModelo;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;

/**
 * Classe AdminPosts
 *
 * @author Ronaldo Aires
 */
class AdminCaixa extends AdminControlador
{

    private string $capa;
    private string $video;

    /**
     * Método responsável por exibir os dados tabulados utilizando o plugin datatables
     * @return void
     */
    public function datatable(): void
    {
        $datatable = $_REQUEST;
        $datatable = filter_var_array($datatable, FILTER_SANITIZE_SPECIAL_CHARS);

        $limite = $datatable['length'];
        $offset = $datatable['start'];
        $busca = $datatable['search']['value'];

        $colunas = [
            0 => 'id',
            1 => 'Nome',
            2 => 'PrecoCusto',
            3 => 'PrecoVenda',
            4 => 'status',
        ];

        $ordem = " " . $colunas[$datatable['order'][0]['column']] . " ";
        $ordem .= " " . $datatable['order'][0]['dir'] . " ";

        $produto = new ProdutoModelo();

        if (empty($busca)) {
            $produto->busca()->ordem($ordem)->limite($limite)->offset($offset);
            $total = (new ProdutoModelo())->busca(null, 'COUNT(id)', 'id')->total();
        } else {
            $produto->busca("id LIKE '%{$busca}%' OR Nome LIKE '%{$busca}%' ")->limite($limite)->offset($offset);
            $total = $produto->total();
        }

        $dados = [];
        if ($produto->resultado(true)) {
            foreach ($produto->resultado(true) as $post) {
                $dados[] = [
                    $post->id,
                    $post->Nome,
                    $post->categoria()->titulo ?? '-----',
                    $post->PrecoCusto,
                    $post->PrecoVenda,
                    $post->estoque,
                    $post->status
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

    /**
     * Lista posts
     * @return void
     */
    public function aberto(): void
    {
        $caixaAberto = (new CaixaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1")->resultado(true);
        $vendaAberta = (new VendaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1")->resultado(true);
        $totais = new VendaProdutoModelo();
        $combustivelModelo = new CombustivelModelo();
        $bicos = new BicoModelo();
        // Busca todas as informações necessárias
        $combustiveis = $combustivelModelo->busca()->resultado(true);
        $bicos = $bicos->busca()->resultado(true);

        // Cria um array associativo para mapear combustivel_id aos dados do combustível
        $combustiveisMap = [];
        foreach ($combustiveis as $combustivel) {
            $combustiveisMap[$combustivel->id] = $combustivel;
        }

        if (isset($vendaAberta)) {
            echo $this->template->renderizar('caixa/aberto.html', [
                'caixa'             => $caixaAberto[0],
                'vendaAberta'       => $vendaAberta[0],
                'operador'          => $vendaAberta[0],
                'produtosVenda'     => (new VendaProdutoModelo())->busca("id_venda = {$vendaAberta[0]->id}")->resultado(true),
                'total'             => $totais->busca("id_venda = {$vendaAberta[0]->id} AND status = 1")->valorTotal(),
                'combustiveis'      => $combustiveis,
                'produtos'          => (new ProdutoModelo())->busca()->resultado(true),
                'clientes'          => (new ClienteModelo())->busca()->resultado(true),
                'bicos'             => $bicos,
                'combustiveisMap'   => $combustiveisMap, // Adicionando o mapa de combustíveis
            ]);
        } else if (isset($caixaAberto)) {
            echo $this->template->renderizar('caixa/aberto.html', [
                'caixa' => $caixaAberto[0],
                'vendasProdutos' => (new VendaProdutoModelo())->busca("id_caixa = {$caixaAberto[0]->id}")->resultado(true),
                'combustiveis' => $combustiveis,
                'produtos' => (new ProdutoModelo())->busca()->resultado(true),
                'clientes' => (new ClienteModelo())->busca()->resultado(true),
                'bicos' => $bicos,
                'combustiveisMap' => $combustiveisMap, // Adicionando o mapa de combustíveis
            ]);
        } else {
            $this->mensagem->alerta('Você precisa abrir o caixa!')->flash();
            Helpers::redirecionar('admin/caixa/abrir');
        }
    }


    public function vendaProduto(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        $caixaAberto = (new CaixaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1")->resultado(true);
        $vendaAberta = (new VendaModelo())->busca("status = 1 AND IDOperador = {$this->usuario->id} ")->resultado(true);
        if (!empty($dados['combustivel_id'])) {
            $produto = (new CombustivelModelo())->busca("id = {$dados['combustivel_id']}")->resultado(true);
        } else {
            $produto = (new ProdutoModelo())->busca("id = {$dados['produto_id']}")->resultado(true);
        }

        $cliente = (new ClienteModelo())->busca("id = {$dados['id_cliente']}")->resultado(true);

        if (isset($dados)) {
            if (empty($cliente) && empty($dados['id_cliente'])) {
                Helpers::json('erro', 'Informe o cliente da venda!');
            }

            if (empty($produto)) {
                Helpers::json('erro', 'nenhum produto inserido!');
            }

            $quantidade = (!empty($dados['combustivel_id']) ? (float)$dados['quantidade'] : 1);
            $quantidade = ($quantidade <= 0) ? 1 : $quantidade; // Verificação para garantir que quantidade não seja zero ou negativa

            $preco_custo = (!empty($dados['combustivel_id']) ? (float)$produto[0]->custo : (float)$produto[0]->PrecoCusto);
            $preco_venda = (!empty($dados['combustivel_id']) ? (float)$produto[0]->vlr_venda : (float)$produto[0]->PrecoVenda);

            if (!isset($vendaAberta)) {
                if ($this->validarDados($dados)) {
                    $realizarVenda = new VendaModelo();
                    $realizarVenda->id_cliente = $cliente[0]->id;
                    $realizarVenda->nome_cliente = $cliente[0]->nome;
                    $realizarVenda->data_hora = date('Y-m-d H:i:s');
                    $realizarVenda->status = 1;
                    $realizarVenda->IDOperador = $this->usuario->id;

                    if ($realizarVenda->salvar()) {
                        $vendaID = $realizarVenda->ultimoId();
                        $vendaProduto = new VendaProdutoModelo();
                        $vendaProduto->id_caixa = $caixaAberto[0]->id;
                        $vendaProduto->tipo_venda = (!empty($dados['combustivel_id']) ? 'combustivel' : 'produto');
                        $vendaProduto->produto = (!empty($dados['combustivel_id']) ? $produto[0]->descricao : $produto[0]->Nome);
                        $vendaProduto->quantidade = $quantidade;
                        $vendaProduto->preco_custo = $preco_custo;
                        $vendaProduto->preco_venda = $preco_venda;
                        $vendaProduto->valor_total_venda = $preco_venda * $quantidade;
                        $vendaProduto->valor_total_custo = $preco_custo * $quantidade;
                        $vendaProduto->lucro_bruto = $vendaProduto->valor_total_venda - $vendaProduto->valor_total_custo;
                        $vendaProduto->margem_reais = $vendaProduto->lucro_bruto / $quantidade;
                        $vendaProduto->margem_porcentagem = ($vendaProduto->valor_total_venda != 0) ? ($vendaProduto->lucro_bruto / $vendaProduto->valor_total_venda * 100) : 0;
                        $vendaProduto->data_hora = date('Y-m-d H:i:s');
                        $vendaProduto->status = 1;
                        $vendaProduto->id_venda = $vendaID;

                        if ($vendaProduto->salvar()) {
                            echo json_encode(['successo' => 'Item inserido com sucesso!']);
                            return;
                        }
                    }
                }
            } else {
                $vendaID = $vendaAberta[0]->id;
                $vendaProduto = new VendaProdutoModelo();
                $vendaProduto->id_caixa = $caixaAberto[0]->id;
                $vendaProduto->tipo_venda = (!empty($dados['combustivel_id']) ? 'combustivel' : 'produto');
                $vendaProduto->produto = (!empty($dados['combustivel_id']) ? $produto[0]->descricao : $produto[0]->Nome);
                $vendaProduto->quantidade = $quantidade;
                $vendaProduto->preco_custo = $preco_custo;
                $vendaProduto->preco_venda = $preco_venda;
                $vendaProduto->valor_total_venda = $preco_venda * $quantidade;
                $vendaProduto->valor_total_custo = $preco_custo * $quantidade;
                $vendaProduto->lucro_bruto = $vendaProduto->valor_total_venda - $vendaProduto->valor_total_custo;
                $vendaProduto->margem_reais = $vendaProduto->lucro_bruto / $quantidade;
                $vendaProduto->margem_porcentagem = ($vendaProduto->valor_total_venda != 0) ? ($vendaProduto->lucro_bruto / $vendaProduto->valor_total_venda * 100) : 0;
                $vendaProduto->data_hora = date('Y-m-d H:i:s');
                $vendaProduto->status = 1;
                $vendaProduto->id_venda = $vendaID;

                if ($vendaProduto->salvar()) {
                    echo json_encode(['successo' => 'Item inserido com sucesso!']);
                    return;
                }
            }
        }

        echo json_encode(['erro' => 'Ocorreu um erro ao processar a venda.']);
    }




    public function listaProdutos(): void
    {
        $vendaAberta = (new VendaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1")->resultado(true);

        if (isset($vendaAberta[0])) {
            $idVenda = $vendaAberta[0]->id; // Obtém o ID da venda aberta
            $produtosVenda = (new VendaProdutoModelo())->busca("id_venda = {$vendaAberta[0]->id}")->resultado(true);

            $response = [];
            foreach ($produtosVenda as $produtoVenda) {
                $response[] = [
                    'numero_venda' => $idVenda,
                    'id' => $produtoVenda->id,
                    'produto' => $produtoVenda->produto,
                    'quantidade' => $produtoVenda->quantidade,
                    'preco_venda' => number_format($produtoVenda->preco_venda, 2, ',', '.'),
                    'total' => number_format($produtoVenda->preco_venda * $produtoVenda->quantidade, 2, ',', '.')
                ];
            }

            echo json_encode($response);
        } else {
            echo json_encode([]);
        }
    }




    public function editarVendaProduto($id): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $caixaAberto = (new CaixaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1")->resultado(true);
        $produto = (new ProdutoModelo())->busca("id = {$dados['produto']}")->resultado(true);
        $cliente = (new ClienteModelo())->busca("id = {$dados['id_cliente']}")->resultado(true);
        r($dados);
        exit;
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $realizarVenda = new VendaModelo();
                $realizarVenda->id_cliente = $cliente[0]->id;
                $realizarVenda->nome_cliente = $cliente[0]->nome;
                $realizarVenda->data_hora = date('Y-m-d H:i:s');
                $realizarVenda->status = 1;

                if ($realizarVenda->salvar()) {
                    $vendaID = $realizarVenda->ultimoId();
                    $vendaProduto = new VendaProdutoModelo();
                    $vendaProduto->id_caixa             = $caixaAberto[0]->id;
                    $vendaProduto->tipo_venda           = 'produto';
                    $vendaProduto->produto              = $produto[0]->Nome;
                    $vendaProduto->quantidade           = $dados['quantidade'];
                    $vendaProduto->preco_custo          = $produto[0]->PrecoCusto;
                    $vendaProduto->preco_venda          = $produto[0]->PrecoVenda;
                    $vendaProduto->valor_total_venda    = $produto[0]->PrecoVenda * $dados['quantidade'];
                    $vendaProduto->valor_total_custo    = $produto[0]->PrecoCusto * $dados['quantidade'];
                    $vendaProduto->lucro_bruto          = $vendaProduto->valor_total_venda - $vendaProduto->valor_total_custo;
                    $vendaProduto->margem_reais         = $vendaProduto->lucro_bruto / $dados['quantidade'];
                    $vendaProduto->margem_porcentagem   = $vendaProduto->lucro_bruto / $vendaProduto->valor_total_venda * 100;
                    $vendaProduto->data_hora            = date('Y-m-d H:i:s');
                    $vendaProduto->status               = 1;
                    $vendaProduto->id_venda             = $vendaID;

                    if ($vendaProduto->salvar()) {
                        // Buscar a lista de produtos da venda atual
                        $produtosVenda = (new VendaProdutoModelo())->busca("id_caixa = {$caixaAberto[0]->id} AND status = 1 AND id_venda = {$vendaID}")->resultado(true);

                        // Garantir que o JSON esteja correto
                        foreach ($produtosVenda as $produtoVenda) {
                            $produtoVenda->preco_venda = (float)$produtoVenda->preco_venda;
                            $produtoVenda->quantidade = (int)$produtoVenda->quantidade;
                        }

                        echo json_encode([
                            'successo' => 'Produto inserido com sucesso',
                            'produtos' => $produtosVenda
                        ]);
                        return;
                    } else {
                        echo json_encode(['erro' => $vendaProduto->erro()]);
                        return;
                    }
                } else {
                    echo json_encode(['erro' => 'erro ao processar a venda']);
                    return;
                }
            }
        }
        echo json_encode(['erro' => 'Erro ao processar a venda']);
    }



    public function venda1(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($dados['adicionarItem'])) {
            // Processar a adição de um item
            if ($this->validarDados($dados)) {
                $itemId = $dados['itemVenda'];
                $tipoVenda = $dados['tipoVenda'];
                $quantidade = $dados['quantidade'];

                // Simular uma função de busca pelo item no banco de dados
                $itemInfo = $this->buscarItem($itemId, $tipoVenda);
                if ($itemInfo && is_array($itemInfo)) {
                    $itemNome = $itemInfo['descricao'] ?? $itemInfo['Nome'] ?? 'N/A';
                    $itemValor = $itemInfo['vlr_venda'] ?? $itemInfo['PrecoVenda'] ?? 0;
                    $valorTotal = $itemValor * $quantidade;
                    $itemId = $itemInfo['id'] ?? 0;
                    $itemEstoque = $itemInfo['estoque'] ?? 0;

                    // Adicionar item à sessão
                    $_SESSION['itensVenda'][] = [
                        'id' => $itemId,
                        'tipo' => $tipoVenda,
                        'nome' => $itemNome,
                        'valor' => $itemValor,
                        'quantidade' => $quantidade,
                        'quantidade_disponivel' => $itemEstoque,
                        'valor_total' => number_format($valorTotal, 2, '.', '')
                    ];

                    $this->mensagem->sucesso('Item adicionado com sucesso')->flash();
                    echo $this->template->renderizar('caixa/aberto.html', [
                        'combustiveis' => (new CombustivelModelo())->busca('status = 1')->resultado(true),
                        'produtos' => (new ProdutoModelo())->busca('status = 1')->resultado(true),
                        'itensVenda' => $_SESSION['itensVenda'] ?? []
                    ]);
                } else {
                    $this->mensagem->erro('Item não encontrado')->flash();
                }
            }
        } elseif (isset($dados['finalizarVenda'])) {
            // Processar a finalização da venda
            if (!empty($_SESSION['itensVenda'])) {
                // Exemplo de inserção no banco de dados:
                foreach ($_SESSION['itensVenda'] as $item) {
                    $venda = new VendaModelo();
                    $venda->usuario_id = $this->usuario->id;
                    $venda->item_id = $item['id'];
                    $venda->tipo = $item['tipo'];
                    $venda->quantidade = $item['quantidade'];
                    $venda->nome = $item['nome'];
                    $venda->valor = $item['valor'];
                    $venda->valor_total = $item['valor_total'];

                    if (!$venda->salvar()) {
                        $this->mensagem->erro($venda->erro())->flash();
                        Helpers::json('redirecionar', Helpers::url('admin/caixa/aberto'));
                        return;
                    }
                }

                // Limpar a sessão de itens de venda
                unset($_SESSION['itensVenda']);

                $this->mensagem->sucesso('Venda finalizada com sucesso')->flash();
                Helpers::json('redirecionar', Helpers::url('admin/caixa/aberto'));
                return;
            } else {
                $this->mensagem->erro('Nenhum item na venda')->flash();
            }
        }

        echo $this->template->renderizar('caixa/aberto.html', [
            'combustiveis' => (new CombustivelModelo())->busca('status = 1')->resultado(true),
            'produtos' => (new ProdutoModelo())->busca('status = 1')->resultado(true),
            'itensVenda' => $_SESSION['itensVenda'] ?? []
        ]);
    }



    private function buscarItem($id, $tipo)
    {

        // Simulação de consulta ao banco de dados, substitua com sua lógica real
        if ($tipo == 'combustivel') {
            return (new CombustivelModelo())->busca("id = {$id}")->resultado(true);
        } else {
            return (new ProdutoModelo())->busca("id = {$id}")->resultado(true);
        }
    }

    public function buscar(): void
    {
        $busca = filter_input(INPUT_POST, 'busca', FILTER_DEFAULT);
        $resultado = [];
        if (isset($busca)) {
            $produtos = (new ProdutoModelo())->busca("status = 1 AND nome LIKE '%{$busca}%'")->resultado(true);
            if ($produtos) {
                foreach ($produtos as $produto) {
                    $resultado[] = [
                        'id' => $produto->id,
                        'nome' => $produto->Nome,
                        'preco' => $produto->PrecoVenda // Ajuste conforme o nome do atributo de preço
                    ];
                }
            }
        }
        echo json_encode($resultado);
    }


    /**
     * Abrir Caixa
     * @return void
     */

    public function abrirCaixa()
    {
        $caixaAberto = (new CaixaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1")->resultado(true);
        if (isset($caixaAberto)) {
            $this->mensagem->alerta('Este usuário ja possui um caixa aberto!')->flash();
            Helpers::redirecionar('admin/caixa/aberto');
        }
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $caixa = new CaixaModelo();

                $caixa->IDOperador = $this->usuario->id;
                $caixa->DataHoraAbertura = date('Y-m-d H:i:s');
                $caixa->ValorAbertura = $dados['valorAbertura'];
                $caixa->Status = 1;
                $caixa->ValorAtualCaixa = $dados['valorAbertura'];

                if ($caixa->salvar()) {
                    $this->mensagem->sucesso('Caixa Iniciado com sucesso')->flash();
                    Helpers::redirecionar('admin/caixa/aberto');
                } else {
                    $this->mensagem->erro($caixa->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/caixa/abrir'));
                }
            }
        }

        echo $this->template->renderizar('caixa/formulario.html', [
            'caixa' => (new CaixaModelo())->busca("IDOperador = {$this->usuario->nome}")->resultado(true),
        ]);
    }

    /**
     * Cadastra posts
     * @return void
     */
    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {


            if ($this->validarDados($dados)) {
                $produto = new ProdutoModelo();

                $produto->usuario_id = $this->usuario->id;
                $produto->categoria_id = $dados['categoria_id'];
                $produto->Nome = $dados['nome'];
                $produto->PrecoCusto = $dados['precoCusto'];
                $produto->PrecoVenda = $dados['precoVenda'];
                $produto->estoque = $dados['estoque'];
                $produto->status = $dados['status'];


                if ($produto->salvar()) {
                    $this->mensagem->sucesso('produto cadastrado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/produto/listar'));
                } else {
                    $this->mensagem->erro($produto->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/produto/listar'));
                }
            }
        }

        echo $this->template->renderizar('produto/formulario.html', [
            'categorias' => (new CategoriaModelo())->busca('status = 1')->resultado(true),
            'produto' => $dados
        ]);
    }

    /**
     * Edita post pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $produto = (new ProdutoModelo())->buscaPorId($id);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $produto->usuario_id = $this->usuario->id;
                $produto->categoria_id = $dados['categoria_id'];
                $produto->Nome = $dados['nome'];
                $produto->PrecoCusto = $dados['precoCusto'];
                $produto->PrecoVenda = $dados['precoVenda'];
                $produto->estoque = $dados['estoque'];
                $produto->status = $dados['status'];

                if ($produto->salvar()) {
                    $this->mensagem->sucesso('produto editado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/produto/listar'));
                } else {
                    $this->mensagem->erro($produto->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/produto/listar'));
                }
            }
        }

        echo $this->template->renderizar('produto/formulario.html', [
            'produto'          => $produto,
            'categorias'    => (new CategoriaModelo())->busca('status = 1')->resultado(true),
        ]);
    }


    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    public function validarDados(array $dados): bool
    {



        return true;
    }

    /**
     * Deleta posts por ID
     * @param int $id
     * @return void
     */
    public function cancelarProduto()
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $produto = (new VendaProdutoModelo())->busca("id = {$dados['id_produto']} AND status = 1 AND id_venda = {$dados['id_caixa']}")->resultado(true);
        $itemAlterar = (new VendaProdutoModelo)->buscaPorId($dados['id_produto']);
        if (empty($itemAlterar)) {
            Helpers::json('erro', 'Produto informado não existe!');
        }
        if (isset($dados)) {
            $produto = (new VendaProdutoModelo())->busca("id = {$dados['id_produto']} AND status = 1 AND id_venda = {$dados['id_caixa']}")->resultado(true);
            if (!$produto) {
                //$this->mensagem->alerta('O produto que você está tentando deletar não existe!')->flash();
                Helpers::json('erro', 'Produto informado não encontrado!');
                return;
            }

            $itemAlterar->status = '0';
            if ($itemAlterar->salvar()) {
                Helpers::json('successo', 'com sucesso');
            } else {
                $this->mensagem->erro($produto->erro())->flash();
            }
        }
    }

    public function finalizarVenda()
{
    $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if (isset($dados)) {
        // Busca os dados da venda para finaliza-la
        $dadosVenda = (new VendaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1 AND id = {$dados['vendaID']}")->resultado(true);
        $itensVenda = (new VendaProdutoModelo())->busca("status = 1 AND id_venda = {$dados['vendaID']}")->resultado(true);

        if (empty($itensVenda)) {
            $this->mensagem->alerta('Nenhum item encontrado para a venda.')->flash();
            Helpers::redirecionar('admin/caixa/aberto');
            return;
        }

        // Calcular o valor total dos produtos
        $totalProdutos = array_reduce($itensVenda, function ($carry, $item) {
            return $carry + $item->valor_total_venda;
        }, 0);

        if (empty($dadosVenda)) {
            $this->mensagem->alerta('A venda a ser finalizada não existe!')->flash();
            Helpers::redirecionar('admin/caixa/aberto');
            return;
        }

        $cliente = (new ClienteModelo())->buscaPorId($dadosVenda[0]->id_cliente);

        $vendaFinalizar = (new VendaModelo())->buscaPorId($dados['vendaID']);
        $vendaFinalizar->status = '0';
        $vendaFinalizar->desconto = $dados['desconto'];
        $vendaFinalizar->valor_total = $totalProdutos;
        $vendaFinalizar->valor_liquido = $totalProdutos - $dados['desconto'];
        $vendaFinalizar->troco = $dados['troco'];
        $vendaFinalizar->forma_pagamento = $dados['forma_pagamento'];
        
        $atualizaCaixa = (new CaixaModelo())->busca("IDOperador = {$this->usuario->id} AND status = 1")->resultado(true);
        r($itensVenda); exit;
        if ($vendaFinalizar->salvar()) {
            foreach ($itensVenda as $item) {
                if ($item->tipo_venda === 'produto') {
                    // Atualizar estoque de produtos
                    $produto = (new ProdutoModelo())->buscaPorId($item->id_produto);
                    $produto->estoque -= $item->quantidade;
                    $produto->salvar();
                } elseif ($item->tipo_venda === 'combustivel') {
                    // Atualizar estoque de combustíveis
                    $combustivel = (new CombustivelModelo())->buscaPorId($item->id_produto);
                    $combustivel->estoque -= $item->quantidade;
                    $combustivel->salvar();
                }
            }

            if (!empty($atualizaCaixa)) {
                // Atualizar caixa com os valores atuais, valores de fechamento, data e hora de fechamento e status 0
                $caixa = $atualizaCaixa[0];
                $caixa->DataHoraFechamento = date('Y-m-d H:i:s');
                $caixa->ValorAtualCaixa = $caixa->ValorAtualCaixa + $totalProdutos - $dados['desconto'];
                $caixa->Status = 0;
                if ($dados['forma_pagamento']=== 'dinheiro') {
                    $caixa->ValorDinheiro = $totalProdutos - $dados['desconto'];
                }else if($dados['forma_pagamento']=== 'debito'){
                    $caixa->ValorCartaoDebito = $totalProdutos - $dados['desconto'];
                }
                else if($dados['forma_pagamento']=== 'debito'){
                    $caixa->ValorCartaoCredito = $totalProdutos - $dados['desconto'];
                }
                else if($dados['forma_pagamento']=== 'pix'){
                    $caixa->ValorPix = $totalProdutos - $dados['desconto'];
                }
                $caixa->salvar();
            }

            $items = (new VendaProdutoModelo())->busca("id_venda = {$dados['vendaID']}")->resultado(true);
            if (empty($items)) {
                $this->mensagem->alerta('Nenhum item encontrado para a venda.')->flash();
                Helpers::redirecionar('admin/caixa/aberto');
                return;
            }

            $data_hora = date('d/m/Y H:i:s');
            $operador = $this->usuario->nome;
            $clienteNome = $cliente->nome;
            $telefoneCliente = $cliente->telefone;
            $enderecoEntrega = $cliente->endereco;

            $total = array_reduce($items, function ($carry, $item) {
                return $carry + ($item->preco_venda * $item->quantidade);
            }, 0);
            $total -= $dados['desconto'];

            $logo = 'path/to/logo.png'; // Caminho do logo

            // Calcular valores de pagamento e troco
            $forma_pagamento = $dados['forma_pagamento'];
            $troco = 0.00;
            $pago = $total;
            if ($forma_pagamento === 'Dinheiro') {
                $pago = $dados['td_troco'];
                $troco = $pago - $total;
                if ($troco < 0.1) {
                    $pago = $total;
                    $troco = 0.00;
                }
            }

            $html = $this->template->renderizar('caixa/pdf.html', [
                'id_venda' => $dados['vendaID'],
                'data_hora' => $data_hora,
                'operador' => $operador,
                'cliente' => $clienteNome,
                'telefone_cliente' => $telefoneCliente,
                'endereco_entrega' => $enderecoEntrega,
                'items' => array_map(function ($item) {
                    return [
                        'produto' => $item->produto,
                        'preco_venda' => is_numeric($item->preco_venda) ? $item->preco_venda : 0,
                        'quantidade' => is_numeric($item->quantidade) ? $item->quantidade : 0
                    ];
                }, $items),
                'desconto' => number_format($dados['desconto'], 2, ',', '.'),
                'total' => number_format($total, 2, ',', '.'),
                'total_pago' => number_format($total, 2, ',', '.'),
                'troco' => number_format($troco, 2, ',', '.'),
                'forma_pagamento' => $forma_pagamento,
                'pago' => number_format($pago, 2, ',', '.'),
                'logo' => $logo,
                'observacoes' => 'Observações do pedido' // Ajuste conforme necessário
            ]);

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => [58, 297], // Tamanho de papel para impressora térmica
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_top' => 5,
                'margin_bottom' => 5
            ]);
            $mpdf->WriteHTML($html);
            $mpdf->Output('cupom_venda.pdf', 'I'); // Envia o PDF para o navegador

            $this->mensagem->sucesso('Venda Finalizada com sucesso!')->flash();
            Helpers::redirecionar('admin/caixa/aberto');
        } else {
            $this->mensagem->erro($vendaFinalizar->erro())->flash();
        }
    }
}

    





    public function gerarPDF($searchTerm)
    {
        // Use o valor do searchTerm na consulta ao banco de dados para buscar apenas os registros filtrados
        if ($searchTerm == 'todos') {
            $searchTerm = '';
        } else {
            $searchTerm;
        }
        $produto = new ProdutoModelo();

        // Crie um novo objeto mPDF
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);

        // Renderize o arquivo HTML e adicione ao PDF
        $html = $this->template->renderizar('produto/pdf.html', [
            'produto' => $produto->busca("id LIKE '%{$searchTerm}%' OR Nome LIKE '%{$searchTerm}%' ")->resultado(true),
            'categorias' => (new CategoriaModelo())->busca()->resultado(true),
            'total' => [
                'produto' => $produto->total(),
                'produtoAtivo' => $produto->busca('status = 1')->total(),
                'produtoInativo' => $produto->busca('status = 0')->total(),
            ]
        ]);
        $mpdf->WriteHTML($html);

        // Envie o PDF para o navegador para visualização em uma nova guia
        $mpdf->Output('relatorio_estoque.pdf', 'I');
    }



    public function gerarExcel($searchTerm): void
    {
        if ($searchTerm == 'todos') {
            $searchTerm = '';
        }

        $produto = new ProdutoModelo();
        $categorias = (new CategoriaModelo())->busca()->resultado(true);
        $totalproduto = $produto->busca("id LIKE '%{$searchTerm}%' OR Nome LIKE '%{$searchTerm}%' ")->total();

        // Criar uma nova planilha Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Definir os cabeçalhos e pintar a primeira linha
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '337AB7'],
            ],
        ];

        $sheet->setCellValue('A1', 'Nome');
        $sheet->setCellValue('B1', 'Categoria');
        $sheet->setCellValue('C1', 'Preço Custo');
        $sheet->setCellValue('D1', 'Preço Venda');
        $sheet->setCellValue('E1', 'Estoque');
        $sheet->setCellValue('F1', 'Status');
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Aplicar formatação para três casas decimais nas colunas C, D, E, F
        $decimalFormat = '#,##0.000';
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode($decimalFormat);
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode($decimalFormat);

        // Adicionar os dados dos combustíveis à planilha
        $row = 2;
        foreach ($produto->busca("id LIKE '%{$searchTerm}%' OR Nome LIKE '%{$searchTerm}%' ")->resultado(true) as $pd) {
            $sheet->setCellValue('A' . $row, $pd->Nome);
            foreach ($categorias as $categoria) {
                if ($pd->categoria_id == $categoria->id) {
                    $sheet->setCellValue('B' . $row, $categoria->titulo);
                }
            }
            $sheet->setCellValue('C' . $row, $pd->PrecoCusto);
            $sheet->setCellValue('D' . $row, $pd->PrecoVenda);
            $sheet->setCellValue('E' . $row, $pd->estoque);
            $sheet->setCellValue('F' . $row, $pd->status == 1 ? 'Ativo' : 'Inativo');
            $row++;
        }

        // Definir estilo de alinhamento automático e auto dimensionar colunas
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Inserir total de registros
        $sheet->setCellValue('A' . ($row + 1), 'Total de Registros:');
        $sheet->setCellValue('B' . ($row + 1), $totalproduto);

        // Configurar cabeçalho do arquivo Excel para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="relatorio_tanque.xlsx"');
        header('Cache-Control: max-age=0');

        // Criar o objeto Writer e salvar a planilha
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
