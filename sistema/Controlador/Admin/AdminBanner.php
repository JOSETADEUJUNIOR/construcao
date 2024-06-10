<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\BannerModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Classe AdminCategorias
 *
 * @author Ronaldo Aires
 */
class AdminBanner extends AdminControlador
{
    private string $imagem;

    /**
     * Lista categorias
     * @return void
     */
    public function listar(): void
    {
        $banners = new BannerModelo();

        echo $this->template->renderizar('banner/listar.html', [
            'banners' => $banners->busca()->ordem('titulo ASC')->resultado(true),
            'total' => [
                'banners' => $banners->total(),
                'bannersAtivo' => $banners->busca('status = 1')->total(),
                'bannersInativo' => $banners->busca('status = 0')->total(),
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
                $banner = new BannerModelo();

                $banner->usuario_id = $this->usuario->id;
                $banner->titulo = $dados['titulo'];
                $banner->status = $dados['status'];
                $banner->data_inicio = Helpers::formatarDataParaBanco($dados['data_inicio']);
                $banner->data_fim = Helpers::formatarDataParaBanco($dados['data_fim']);
                $banner->status = $dados['status'];
                $banner->slug = Helpers::slug($dados['titulo']);
                $banner->imagem = $this->imagem ?? null;
                $banner->link = $dados['link'];

                if ($banner->salvar()) {
                    $this->mensagem->sucesso('banner cadastrado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/banner/listar'));
                } else {
                    $this->mensagem->erro($banner->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/banner/listar'));
                }
            }
        }

        echo $this->template->renderizar('banner/formulario.html', [
            'banner' => $dados
        ]);
    }

    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $banner = (new BannerModelo())->buscaPorId($id);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $banner->usuario_id = $this->usuario->id;
                $banner->titulo = $dados['titulo'];
                $banner->status = $dados['status'];
                $banner->data_inicio = Helpers::formatarDataParaBanco($dados['data_inicio']);
                $banner->data_fim = Helpers::formatarDataParaBanco($dados['data_fim']);
                $banner->status = $dados['status'];
                $banner->slug = Helpers::slug($dados['titulo']);
                $banner->imagem = $this->imagem ?? null;
                $banner->link = $dados['link'];


                if ($banner->salvar()) {
                    $this->mensagem->sucesso('banner editado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/banner/listar'));
                } else {
                    $this->mensagem->erro($banner->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/banner/listar'));
                }
            }
        }

        echo $this->template->renderizar('banner/formulario.html', [
            'banner' => $banner
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
            Helpers::json('erro', 'Informe um titulo para o banner!');
        }
        if (empty($dados['data_inicio'])) {
            Helpers::json('erro', 'Informe uma data de inicio para o banner!');
        }
        if (empty($dados['data_fim'])) {
            Helpers::json('erro', 'Informe uma data de fim para o banner!');
        }
        if (!empty($_FILES['imagem'])) {
            $upload = new Upload($_FILES['imagem'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/banner/');

                if ($upload->processed) {
                    $this->imagem = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/banner/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
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
        $banners = new BannerModelo();
    
        // Crie um novo objeto mPDF
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);
    
        // Renderize o arquivo HTML e adicione ao PDF
        $html = $this->template->renderizar('banner/pdf.html', [
            'banners' => $banners->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->resultado(true),
            'total' => [
                'banners' => $banners->total(),
                'bannersAtivo' => $banners->busca('status = 1')->total(),
                'bannersInativo' => $banners->busca('status = 0')->total(),
            ]
        ]);
        $mpdf->WriteHTML($html);
    
        // Envie o PDF para o navegador para visualização em uma nova guia
        $mpdf->Output('relatorio_banners.pdf', 'I');
    }
    


    public function gerarExcel($searchTerm): void
    {
        if ($searchTerm=='todos') {
            $searchTerm = '';
        }else {
            $searchTerm;
        }
        $banners = new BannerModelo();
        $totalBanners = $banners->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->total();

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

        $sheet->setCellValue('A1', 'Título');
        $sheet->setCellValue('B1', 'Data Início');
        $sheet->setCellValue('C1', 'Data Fim');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Link');
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // Adicionar os dados dos banners à planilha
        $row = 2;
        foreach ($banners->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->resultado(true) as $banner) {
            $sheet->setCellValue('A' . $row, $banner->titulo);
            $sheet->setCellValue('B' . $row, $banner->data_inicio);
            $sheet->setCellValue('C' . $row, $banner->data_fim);
            $sheet->setCellValue('D' . $row, $banner->status == 1 ? 'Ativo' : 'Inativo');
            $sheet->setCellValue('E' . $row, $banner->link);
            $row++;
        }

        // Definir estilo de alinhamento automático e auto dimensionar colunas
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Inserir total de registros
        $sheet->setCellValue('A' . ($row + 1), 'Total de Registros:');
        $sheet->setCellValue('B' . ($row + 1), $totalBanners);

        // Configurar cabeçalho do arquivo Excel para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="relatorio_banners.xlsx"');
        header('Cache-Control: max-age=0');

        // Criar o objeto Writer e salvar a planilha
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
