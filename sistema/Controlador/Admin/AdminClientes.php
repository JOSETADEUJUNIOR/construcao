<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\ClienteModelo;
use sistema\Modelo\EnderecoModelo;
use sistema\Nucleo\Helpers;
use Dompdf\Dompdf;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class AdminClientes extends AdminControlador
{
    public function listar(): void
    {
        $clientes = new ClienteModelo();
        echo $this->template->renderizar('clientes/listar.html', [
            'clientes' => $clientes->busca()->ordem('Nome ASC')->resultado(true),
            'total'    => [
                'clientes'           => $clientes->total(),
                'clientesAtivo'      => $clientes->busca('status = 1')->total(),
                'clientesInativo'    => $clientes->busca('status = 0')->total(),
            ]

        ]);
    }


    public function RelatorioClientePdf()
    {
        // Renderize o template Twig para HTML
        $clientes = new ClienteModelo();
        $html = $this->template->renderizar('clientes/relatorioPdfCliente.html', [
            'clientes' => $clientes->busca()->ordem('Nome ASC')->resultado(true),
            'total'    => [
                'clientes'           => $clientes->total(),
                'clientesAtivo'      => $clientes->busca('status = 1')->total(),
                'clientesInativo'    => $clientes->busca('status = 0')->total(),
            ]

        ]);

        // Crie uma instância do Dompdf
        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->setDefaultFont('Courier');
        $dompdf->setOptions($options);
        // Carregue o HTML no Dompdf
        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'landscape');

        // Renderize o PDF
        $dompdf->render();

        // Exiba o PDF no navegador
        $dompdf->stream(null, array("Attachment" => false));
    }


    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $cliente = new ClienteModelo();
                $endereco = new EnderecoModelo();

                $cliente->Nome = $dados['nome'];
                $cliente->sobrenome = $dados['sobrenome'];
                $cliente->dataNascimento = Helpers::formatarDataParaBanco($dados['dataNascimento']);
                $cliente->cpf = $dados['cpf'];
                $cliente->email = $dados['email'];
                $cliente->status = $dados['status'];
                $cliente->observacao = $dados['observacoes'];

                if ($cliente->salvar()) {
                    $cliente_id = $cliente->ultimoId();

                    if ($cliente_id) {
                        //Salvando o endereco do cliente
                        $endereco->endereco = $dados['endereco'];
                        $endereco->clienteId = $cliente_id;
                        $endereco->numero = $dados['numero'];
                        $endereco->bairro = $dados['bairro'];
                        $endereco->cep = $dados['cep'];
                        $endereco->estado = $dados['estado'];
                        $endereco->cidade = $dados['cidade'];
                        $endereco->complemento = $dados['complemento'];
                        $endereco->salvar();
                    }
                    $this->mensagem->sucesso("Cliente cadastrado com sucesso")->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/clientes/listar'));
                }
            }
        }

        echo $this->template->renderizar('clientes/formulario.html', [
            'cliente' => $dados
        ]);
    }

    public function historicoAluguel(): void
    {
        $historicoAlugueis = new ClienteModelo();
        echo $this->template->renderizar('clientes/historicoAluguel.html', [
            'clientes' => $historicoAlugueis->busca()->ordem('Nome ASC')->resultado(true),
            'total'    => [
                'clientes'           => $historicoAlugueis->total(),
                'clientesAtivo'      => $historicoAlugueis->busca('status = 1')->total(),
                'clientesInativo'    => $historicoAlugueis->busca('status = 0')->total(),
            ]

        ]);
    }

    public function validarDados(array $dados): bool
    {
        if (empty($dados['nome']) || empty($dados['dataNascimento']) || empty($dados['cpf'])) {
            Helpers::json('erro', 'preencha os campos obrigatórios');
            return false;
        }
        return true;
    }
    public function deletar(int $id): void
    {
        if (is_int($id)) {
            $categoria = (new ClienteModelo())->buscaPorId($id);

            if (!$categoria) {
                $this->mensagem->alerta('O cliente que você está tentando deletar não existe!')->flash();
                Helpers::redirecionar('admin/clientes/listar');
            } else {
                if ($categoria->deletar()) {
                    $this->mensagem->sucesso('Cliente deletado com sucesso!')->flash();
                    Helpers::redirecionar('admin/clientes/listar');
                } else {
                    $this->mensagem->erro($categoria->erro())->flash();
                    Helpers::redirecionar('admin/clientes/listar');
                }
            }
        }
    }
}
