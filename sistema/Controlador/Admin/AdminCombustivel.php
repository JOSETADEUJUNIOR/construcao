<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\BannerModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Modelo\CombustivelModelo;

/**
 * Classe AdminCategorias
 *
 * @author Jose Tadeu
 */
class AdminCombustivel extends AdminControlador
{
    private string $imagem;

    /**
     * Lista categorias
     * @return void
     */
    public function listar(): void
    {
        $combustivel = new CombustivelModelo();
        $categoria =  new CategoriaModelo();
        echo $this->template->renderizar('combustivel/listar.html', [
            'combustivel' => $combustivel->busca()->ordem('descricao ASC')->resultado(true),
            'categoria'   => $categoria->busca()->resultado(true),
            'total' => [
                'combustivel' => $combustivel->total(),
                'combustivelAtivo' => $combustivel->busca('status = 1')->total(),
                'combustivelInativo' => $combustivel->busca('status = 0')->total(),
            ]
        ]);
    }

    /**
     * Cadastra uma categoria
     * @return void
     */
    public function cadastrar()
{
    $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    $categoria = (new CategoriaModelo())->busca()->resultado(true);

    if (isset($dados)) {
        if ($this->validarDados($dados)) {
            $combustivel = new CombustivelModelo();
            $combustivel->usuario_id = $this->usuario->id;
            $combustivel->grupo = $dados['grupo'];
            $combustivel->descricao = $dados['descricao'];
            $combustivel->vlr_venda = $dados['vlr_venda'];
            $combustivel->custo = $dados['custo'];

            // Calcular markup valor e percentual
            $markupValor = $dados['vlr_venda'] - $dados['custo'];
            $markupPercentual = ($markupValor / $dados['custo']) * 100;

            $combustivel->markup_valor = $markupValor;
            $combustivel->markup_percentual = $markupPercentual;
            $combustivel->status = $dados['status'];
           
            if ($combustivel->salvar()) {
                $this->mensagem->sucesso('Combustível cadastrado com sucesso')->flash();
                Helpers::json('redirecionar', Helpers::url('admin/combustivel/listar'));
            } else {
                $this->mensagem->erro($combustivel->erro())->flash();
                Helpers::json('redirecionar', Helpers::url('admin/combustivel/listar'));
            }
        }
    }

    echo $this->template->renderizar('combustivel/formulario.html', [
        'combustivel' => $dados,
        'categorias'   => $categoria
    ]);
}


    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $combustivel = (new CombustivelModelo())->buscaPorId($id);
        $categoria = (new CategoriaModelo())->busca()->resultado(true);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $combustivel->usuario_id = $this->usuario->id;
                $combustivel->grupo = $dados['grupo'];
                $combustivel->descricao = $dados['descricao'];
                $combustivel->vlr_venda = $dados['vlr_venda'];
                $combustivel->custo = $dados['custo'];
    
                // Calcular markup valor e percentual
                $markupValor = $dados['vlr_venda'] - $dados['custo'];
                $markupPercentual = ($markupValor / $dados['custo']) * 100;
    
                $combustivel->markup_valor = $markupValor;
                $combustivel->markup_percentual = $markupPercentual;
                $combustivel->status = $dados['status'];


                if ($combustivel->salvar()) {
                    $this->mensagem->sucesso('combustivel editado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/combustivel/listar'));
                } else {
                    $this->mensagem->erro($combustivel->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/combustivel/listar'));
                }
            }
        }

        echo $this->template->renderizar('combustivel/formulario.html', [
            'combustivel' => $combustivel,
            'categorias'  => $categoria
        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty($dados['descricao'])) {
            Helpers::json('erro', 'Informe uma descrição para o combustível!');
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
            $banner = (new BannerModelo())->buscaPorId($id);
            if (!$banner) {
                $this->mensagem->sucesso('banner a ser excluido não existe')->flash();
                Helpers::redirecionar('admin/banner/listar');
            } else {
                if ($banner->deletar()) {
                    if ($banner->capa && file_exists("uploads/banner/{$banner->imagem}")) {
                        unlink("uploads/banner/{$banner->imagem}");
                        unlink("uploads/banner/thumbs/{$banner->imagem}");
                    }

                    $this->mensagem->sucesso('Banner deletado com sucesso!')->flash();
                    Helpers::redirecionar('admin/banner/listar');
                } else {
                    $this->mensagem->erro($banner->erro())->flash();
                    Helpers::redirecionar('admin/banner/listar');
                }
            }
        }
    }
    public function gerarPDF($searchTerm)
    {
        // Use o valor do searchTerm na consulta ao banco de dados para buscar apenas os registros filtrados
        if ($searchTerm=='todos') {
            $searchTerm = '';
        }else {
            $searchTerm;
        }
        $combustivel = new CombustivelModelo();
    
        // Crie um novo objeto mPDF
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);
    
        // Renderize o arquivo HTML e adicione ao PDF
        $html = $this->template->renderizar('combustivel/pdf.html', [
            'combustivel' => $combustivel->busca("id LIKE '%{$searchTerm}%' OR descricao LIKE '%{$searchTerm}%' ")->resultado(true),
            'total' => [
                'combustivel' => $combustivel->total(),
                'combustivelAtivo' => $combustivel->busca('status = 1')->total(),
                'combustivelInativo' => $combustivel->busca('status = 0')->total(),
            ]
        ]);
        $mpdf->WriteHTML($html);
    
        // Envie o PDF para o navegador para visualização em uma nova guia
        $mpdf->Output('relatorio_combustivel.pdf', 'I');
    }
    


    public function gerarExcel($searchTerm): void
{
    if ($searchTerm == 'todos') {
        $searchTerm = '';
    }

    $combustivel = new CombustivelModelo();
    $totalcombustivel = $combustivel->busca("id LIKE '%{$searchTerm}%' OR descricao LIKE '%{$searchTerm}%' ")->total();

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

    $sheet->setCellValue('A1', 'Grupo');
    $sheet->setCellValue('B1', 'Descrição');
    $sheet->setCellValue('C1', 'Valor Venda');
    $sheet->setCellValue('D1', 'Custo');
    $sheet->setCellValue('E1', 'Markup(R$)');
    $sheet->setCellValue('F1', 'Markup(%)');
    $sheet->setCellValue('G1', 'Status');
    $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

    // Aplicar formatação para três casas decimais nas colunas C, D, E, F
    $decimalFormat = '#,##0.000';
    $sheet->getStyle('C')->getNumberFormat()->setFormatCode($decimalFormat);
    $sheet->getStyle('D')->getNumberFormat()->setFormatCode($decimalFormat);
    $sheet->getStyle('E')->getNumberFormat()->setFormatCode($decimalFormat);
    $sheet->getStyle('F')->getNumberFormat()->setFormatCode($decimalFormat);

    // Adicionar os dados dos combustíveis à planilha
    $row = 2;
    foreach ($combustivel->busca("id LIKE '%{$searchTerm}%' OR descricao LIKE '%{$searchTerm}%' ")->resultado(true) as $comb) {
        $sheet->setCellValue('A' . $row, $comb->grupo);
        $sheet->setCellValue('B' . $row, $comb->descricao);
        $sheet->setCellValue('C' . $row, $comb->vlr_venda);
        $sheet->setCellValue('D' . $row, $comb->custo);
        $sheet->setCellValue('E' . $row, $comb->markup_valor);
        $sheet->setCellValue('F' . $row, $comb->markup_percentual);
        $sheet->setCellValue('G' . $row, $comb->status == 1 ? 'Ativo' : 'Inativo');
        $row++;
    }

    // Definir estilo de alinhamento automático e auto dimensionar colunas
    foreach (range('A', 'G') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Inserir total de registros
    $sheet->setCellValue('A' . ($row + 1), 'Total de Registros:');
    $sheet->setCellValue('B' . ($row + 1), $totalcombustivel);

    // Configurar cabeçalho do arquivo Excel para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_combustivel.xlsx"');
    header('Cache-Control: max-age=0');

    // Criar o objeto Writer e salvar a planilha
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

}
