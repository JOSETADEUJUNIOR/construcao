<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\PostModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Modelo\GaleriaModelo;
use sistema\Modelo\ProdutoModelo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;

/**
 * Classe AdminPosts
 *
 * @author Ronaldo Aires
 */
class AdminProduto extends AdminControlador
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
    public function listar(): void
    {
        $produto = new ProdutoModelo();

        echo $this->template->renderizar('produto/listar.html', [
            'total' => [
                'produto' => $produto->busca(null, 'COUNT(id)', 'id')->total(),
                'produtoAtivo' => $produto->busca('status = :s', 's=1 COUNT(status))', 'status')->total(),
                'produtoInativo' => $produto->busca('status = :s', 's=0 COUNT(status)', 'status')->total(),
            ]
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

        if (empty(trim($dados['nome']))) {
            Helpers::json('erro', 'Informe o nome do produto!');
        }
        if (empty(trim($dados['precoCusto']))) {
            Helpers::json('erro', 'Informe o preço de custo para o Produto!');
        }
        if (empty(trim($dados['precoVenda']))) {
            Helpers::json('erro', 'Informe o preço de venda para o Produto!');
        }
        return true;
    }

    /**
     * Deleta posts por ID
     * @param int $id
     * @return void
     */
    public function deletar(int $id): void
    {
        if (!is_int($id)) {
            $this->mensagem->alerta('ID inválido!')->flash();
            Helpers::redirecionar('admin/produto/listar');
            return;
        }
    
        $produto = (new ProdutoModelo())->buscaPorId($id);
        if (!$produto) {
            $this->mensagem->alerta('O produto que você está tentando deletar não existe!')->flash();
            Helpers::redirecionar('admin/produto/listar');
            return;
        }
    
        // Verifique se um novo status foi passado via GET
        $novoStatus = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);
        if ($novoStatus !== null) {
            $produto->status = $novoStatus;
            if ($produto->salvar()) {
                $this->mensagem->sucesso('Status do produto atualizado com sucesso!')->flash();
            } else {
                $this->mensagem->erro('Erro ao atualizar o status do produto!')->flash();
            }
        } else {
            $produto->status = 0;
            if ($produto->salvar()) {
                $this->mensagem->sucesso('Produto inativado com sucesso!')->flash();
            } else {
                $this->mensagem->erro($produto->erro())->flash();
            }
        }
    
        Helpers::redirecionar('admin/produto/listar');
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
