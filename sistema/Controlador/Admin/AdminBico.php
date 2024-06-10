<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\BannerModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Modelo\BicoModelo;
use sistema\Modelo\BombaModelo;
use sistema\Modelo\CombustivelModelo;
use sistema\Modelo\TanqueModelo;

/**
 * Classe AdminCategorias
 *
 * @author Jose Tadeu
 */
class AdminBico extends AdminControlador
{
    /**
     * Lista categorias
     * @return void
     */
    public function listar(): void
    {
        $tanque = new TanqueModelo();
        $bico = new BicoModelo();
        $combustivel =  new CombustivelModelo();
        $bomba = new BombaModelo();
        echo $this->template->renderizar('bico/listar.html', [
            'bicos'   => $bico->busca()->resultado(true),
            'tanques'   => $tanque->busca()->resultado(true),
            'combustiveis' => $combustivel->busca()->resultado(true),
            'bombas' => $bomba->busca()->resultado(true),
            'total' => [
                'bico' => $bico->busca()->total(),
                'bicoAtivo' => $bico->busca('status = 1')->total(),
                'bicoInativo' => $bico->busca('status = 0')->total(),
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
        $bomba = (new BombaModelo())->busca()->resultado(true);
        $tanque = (new TanqueModelo())->busca()->resultado(true);

        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                // buscar o combustivel que o tanque selecionado fornece.
                $tanque_produto = (new TanqueModelo())->buscaPorId($dados['tanque_id']);
                $bico = new BicoModelo();
                $bico->usuario_id = $this->usuario->id;
                $bico->numero_bico = $dados['numero_bico'];
                $bico->combustivel_id = $tanque_produto->combustivel_id;
                $bico->bomba_id = $dados['bomba_id'];
                $bico->tanque_id = $dados['tanque_id'];
                $bico->status = $dados['status'];

                if ($bico->salvar()) {
                    $this->mensagem->sucesso('Bico cadastrado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/bico/listar'));
                } else {
                    $this->mensagem->erro($bico->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/bico/listar'));
                }
            }
        }

        echo $this->template->renderizar('bico/formulario.html', [
            'bico' => $dados,
            'bombas'   => $bomba,
            'tanques'   => $tanque,
        ]);
    }


    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $bico = (new BicoModelo())->buscaPorId($id);
        $bomba = (new BombaModelo())->busca()->resultado(true);
        $tanque = (new TanqueModelo())->busca()->resultado(true);
        // qual combustivel percente a este tanque
        $tanque_produto = (new TanqueModelo())->buscaPorId($bico->tanque_id);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $bico->usuario_id = $this->usuario->id;
                $bico->numero_bico = $dados['numero_bico'];
                $bico->bomba_id = $dados['bomba_id'];
                $bico->combustivel_id = $tanque_produto->combustivel_id;
                $bico->tanque_id = $dados['tanque_id'];
                $bico->status = $dados['status'];
                if ($bico->salvar()) {
                    $this->mensagem->sucesso('Bico editado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/bico/listar'));
                } else {
                    $this->mensagem->erro($bico->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/bico/listar'));
                }
            }
        }

        echo $this->template->renderizar('bico/formulario.html', [
            'bico' => $bico,
            'bombas'   => $bomba,
            'tanques'   => $tanque,

        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty($dados['numero_bico'])) {
            Helpers::json('erro', 'Informe o numero do bico!');
        }
        if (empty($dados['bomba_id'])) {
            Helpers::json('erro', 'Informe a bomba!');
        }
        if (empty($dados['tanque_id'])) {
            Helpers::json('erro', 'Informe o tanque!');
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
        if ($searchTerm == 'todos') {
            $searchTerm = '';
        } else {
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
