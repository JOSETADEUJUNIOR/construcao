<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\BannerModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Modelo\BombaModelo;
use sistema\Modelo\CombustivelModelo;
use sistema\Modelo\TanqueModelo;

/**
 * Classe AdminCategorias
 *
 * @author Jose Tadeu
 */
class AdminBomba extends AdminControlador
{
    /**
     * Lista categorias
     * @return void
     */
    public function listar(): void
    {
        $bomba = new BombaModelo();
        echo $this->template->renderizar('bomba/listar.html', [
            'bombas'   => $bomba->busca()->resultado(true),
            'total' => [
                'bomba' => $bomba->total(),
                'bombaAtivo' => $bomba->busca('status = 1')->total(),
                'bombaInativo' => $bomba->busca('status = 0')->total(),
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
    if (isset($dados)) {
        if ($this->validarDados($dados)) {
            $bomba = new BombaModelo();
            $bomba->usuario_id = $this->usuario->id;
            $bomba->numero_bomba = $dados['numero_bomba'];
            $bomba->fabricante = $dados['fabricante'];
            $bomba->modelo = $dados['modelo'];
            $bomba->nro_serie = $dados['nro_serie'];
            $bomba->status = $dados['status'];
            $bomba->data_inativa = ($bomba->status == '0'?date('Y-m-d'):null);
           
            if ($bomba->salvar()) {
                $this->mensagem->sucesso('Bomba cadastrada com sucesso')->flash();
                Helpers::json('redirecionar', Helpers::url('admin/bomba/listar'));
            } else {
                $this->mensagem->erro($bomba->erro())->flash();
                Helpers::json('redirecionar', Helpers::url('admin/bomba/listar'));
            }
        }
    }

    echo $this->template->renderizar('bomba/formulario.html', [
        'bomba' => $dados,
    ]);
}


    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $bomba = (new BombaModelo())->buscaPorId($id);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $bomba->usuario_id = $this->usuario->id;
                $bomba->numero_bomba = $dados['numero_bomba'];
                $bomba->fabricante = $dados['fabricante'];
                $bomba->modelo = $dados['modelo'];
                $bomba->nro_serie = $dados['nro_serie'];
                $bomba->status = $dados['status'];
                $bomba->data_inativa = ($bomba->status == '0'?date('Y-m-d'):null);
                if ($bomba->salvar()) {
                    $this->mensagem->sucesso('bomba editada com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/bomba/listar'));
                } else {
                    $this->mensagem->erro($bomba->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/bomba/listar'));
                }
            }
        }

        echo $this->template->renderizar('bomba/formulario.html', [
            'bomba' => $bomba,
        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty($dados['numero_bomba'])) {
            Helpers::json('erro', 'Informe o numero da bomba!');
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
        $bomba = new BombaModelo();
    
        // Crie um novo objeto mPDF
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);
    
        // Renderize o arquivo HTML e adicione ao PDF
        $html = $this->template->renderizar('bomba/pdf.html', [
            'bombas' => $bomba->busca("id LIKE '%{$searchTerm}%' OR numero_bomba LIKE '%{$searchTerm}%' ")->resultado(true),
            'total' => [
                'bomba' => $bomba->total(),
                'bombaAtivo' => $bomba->busca('status = 1')->total(),
                'bombaInativo' => $bomba->busca('status = 0')->total(),
            ]
        ]);
        $mpdf->WriteHTML($html);
    
        // Envie o PDF para o navegador para visualização em uma nova guia
        $mpdf->Output('relatorio_bomba.pdf', 'I');
    }
    


    public function gerarExcel($searchTerm): void
{
    if ($searchTerm == 'todos') {
        $searchTerm = '';
    }

    $bomba = new BombaModelo();
    $totalbomba = $bomba->busca("id LIKE '%{$searchTerm}%' OR numero_bomba LIKE '%{$searchTerm}%' ")->total();

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

    $sheet->setCellValue('A1', 'Numero da bomba');
    $sheet->setCellValue('B1', 'Fabricante');
    $sheet->setCellValue('C1', 'Modelo');
    $sheet->setCellValue('D1', 'Nro Série');
    $sheet->setCellValue('E1', 'Data Inativação');
    $sheet->setCellValue('F1', 'Status');
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

   /*  // Aplicar formatação para três casas decimais nas colunas C, D, E, F
    $decimalFormat = '#,##0.000';
    $sheet->getStyle('C')->getNumberFormat()->setFormatCode($decimalFormat); */
    
    // Adicionar os dados dos combustíveis à planilha
    $row = 2;
    foreach ($bomba->busca("id LIKE '%{$searchTerm}%' OR numero_bomba LIKE '%{$searchTerm}%' ")->resultado(true) as $bomb) {
        $sheet->setCellValue('A' . $row, $bomb->numero_bomba);
        $sheet->setCellValue('B' . $row, $bomb->fabricante);
        $sheet->setCellValue('C' . $row, $bomb->modelo);
        $sheet->setCellValue('D' . $row, $bomb->nro_serie);
        $sheet->setCellValue('E' . $row, $bomb->data_inativa);
        $sheet->setCellValue('F' . $row, $bomb->status == 1 ? 'Ativo' : 'Inativo');
        $row++;
    }

    // Definir estilo de alinhamento automático e auto dimensionar colunas
    foreach (range('A', 'F') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Inserir total de registros
    $sheet->setCellValue('A' . ($row + 1), 'Total de Registros:');
    $sheet->setCellValue('B' . ($row + 1), $totalbomba);

    // Configurar cabeçalho do arquivo Excel para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_bomba.xlsx"');
    header('Cache-Control: max-age=0');

    // Criar o objeto Writer e salvar a planilha
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

}
