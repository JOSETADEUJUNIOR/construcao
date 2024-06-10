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
use sistema\Modelo\TanqueModelo;

/**
 * Classe AdminCategorias
 *
 * @author Jose Tadeu
 */
class AdminTanque extends AdminControlador
{
    /**
     * Lista categorias
     * @return void
     */
    public function listar(): void
    {
        $tanque = new TanqueModelo();
        $combustivel =  new CombustivelModelo();
        echo $this->template->renderizar('tanque/listar.html', [
            'tanques'   => $tanque->busca()->resultado(true),
            'combustiveis' => $combustivel->busca()->resultado(true),
            'total' => [
                'tanque' => $tanque->total(),
                'tanqueAtivo' => $tanque->busca('status = 1')->total(),
                'tanqueInativo' => $tanque->busca('status = 0')->total(),
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
    $combustivel = (new CombustivelModelo())->busca()->resultado(true);

    if (isset($dados)) {
        if ($this->validarDados($dados)) {
            $tanque = new TanqueModelo();
            $tanque->usuario_id = $this->usuario->id;
            $tanque->numero_tanque = $dados['numero_tanque'];
            $tanque->combustivel_id = $dados['combustivel_id'];
            $tanque->capacidade = $dados['capacidade'];
            $tanque->status = $dados['status'];
           
            if ($tanque->salvar()) {
                $this->mensagem->sucesso('Taque cadastrado com sucesso')->flash();
                Helpers::json('redirecionar', Helpers::url('admin/tanque/listar'));
            } else {
                $this->mensagem->erro($tanque->erro())->flash();
                Helpers::json('redirecionar', Helpers::url('admin/tanque/listar'));
            }
        }
    }

    echo $this->template->renderizar('tanque/formulario.html', [
        'tanque' => $dados,
        'combustivel'   => $combustivel
    ]);
}


    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $tanque = (new TanqueModelo())->buscaPorId($id);
        $combustivel = (new CombustivelModelo())->busca()->resultado(true);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $tanque->usuario_id = $this->usuario->id;
                $tanque->numero_tanque = $dados['numero_tanque'];
                $tanque->combustivel_id = $dados['combustivel_id'];
                $tanque->capacidade = $dados['capacidade'];
                $tanque->status = $dados['status'];
                if ($tanque->salvar()) {
                    $this->mensagem->sucesso('tanque editado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/tanque/listar'));
                } else {
                    $this->mensagem->erro($tanque->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/tanque/listar'));
                }
            }
        }

        echo $this->template->renderizar('tanque/formulario.html', [
            'tanque' => $tanque,
            'combustivel'  => $combustivel
        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty($dados['numero_tanque'])) {
            Helpers::json('erro', 'Informe o numero do tanque!');
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
        $tanque = new TanqueModelo();
    
        // Crie um novo objeto mPDF
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);
    
        // Renderize o arquivo HTML e adicione ao PDF
        $html = $this->template->renderizar('tanque/pdf.html', [
            'tanque' => $tanque->busca("id LIKE '%{$searchTerm}%' OR numero_tanque LIKE '%{$searchTerm}%' ")->resultado(true),
            'combustiveis' => (new CombustivelModelo())->busca()->resultado(true),
            'total' => [
                'tanque' => $tanque->total(),
                'tanqueAtivo' => $tanque->busca('status = 1')->total(),
                'tanqueInativo' => $tanque->busca('status = 0')->total(),
            ]
        ]);
        $mpdf->WriteHTML($html);
    
        // Envie o PDF para o navegador para visualização em uma nova guia
        $mpdf->Output('relatorio_tanque.pdf', 'I');
    }
    


    public function gerarExcel($searchTerm): void
{
    if ($searchTerm == 'todos') {
        $searchTerm = '';
    }

    $tanque = new TanqueModelo();
    $combustiveis = (new CombustivelModelo())->busca()->resultado(true);
    $totaltanque = $tanque->busca("id LIKE '%{$searchTerm}%' OR numero_tanque LIKE '%{$searchTerm}%' ")->total();

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

    $sheet->setCellValue('A1', 'Numero do tanque');
    $sheet->setCellValue('B1', 'Combustível');
    $sheet->setCellValue('C1', 'Estoque');
    $sheet->setCellValue('D1', 'Capacidade');
    $sheet->setCellValue('E1', 'Status');
    $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

    // Aplicar formatação para três casas decimais nas colunas C, D, E, F
    $decimalFormat = '#,##0.000';
    $sheet->getStyle('C')->getNumberFormat()->setFormatCode($decimalFormat);
    
    // Adicionar os dados dos combustíveis à planilha
    $row = 2;
    foreach ($tanque->busca("id LIKE '%{$searchTerm}%' OR numero_tanque LIKE '%{$searchTerm}%' ")->resultado(true) as $tq) {
        $sheet->setCellValue('A' . $row, $tq->numero_tanque);
        foreach ($combustiveis as $combustivel) {
            if ($tq->combustivel_id == $combustivel->id) {
                $sheet->setCellValue('B' . $row, $combustivel->descricao);
                
            }
        }
        $sheet->setCellValue('C' . $row, $tq->estoque);
        $sheet->setCellValue('D' . $row, $tq->capacidade);
        $sheet->setCellValue('E' . $row, $tq->status == 1 ? 'Ativo' : 'Inativo');
        $row++;
    }

    // Definir estilo de alinhamento automático e auto dimensionar colunas
    foreach (range('A', 'E') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Inserir total de registros
    $sheet->setCellValue('A' . ($row + 1), 'Total de Registros:');
    $sheet->setCellValue('B' . ($row + 1), $totaltanque);

    // Configurar cabeçalho do arquivo Excel para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_tanque.xlsx"');
    header('Cache-Control: max-age=0');

    // Criar o objeto Writer e salvar a planilha
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

}
