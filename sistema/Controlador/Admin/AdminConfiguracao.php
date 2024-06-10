<?php

namespace sistema\Controlador\Admin;

use sistema\Controlador\Admin\AdminControlador;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;

class AdminConfiguracao extends AdminControlador
{

    private $logo;
    private $favicon;
    private $imagem_slide_um;
    private $imagem_slide_segundo;
    private $imagem_slide_terceiro;

   public function listar()
   {
    $config = new ConfiguracaoModelo();

    if ($config) {
        $ultimoId = $config->ultimoId();
        $dados = $config->buscaPorId($ultimoId);

    }
    echo $this->template->renderizar('configuracao/listar.html', ['dados'=>$dados]);
   }
   
   public function editar(int $id):void
   {
    $config = (new ConfiguracaoModelo())->buscaPorId($id);
    $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
  
    if (isset($dados)) {
        if ($this->validarDados($dados)) {
            $config = (new ConfiguracaoModelo())->buscaPorId($config->id);
            $config->config_nome = $dados['config_nome'];
            $config->config_endereco = $dados['config_endereco'];
            $config->config_cep = $dados['config_cep'];
            $config->config_horario = $dados['config_horario'];
            $config->config_fone1 = $dados['config_fone1'];
            $config->config_fone2 = $dados['config_fone2'];
            $config->config_email = $dados['config_email'];
            $config->config_site_keywords = $dados['config_site_keywords'];
            $config->config_site_description = $dados['config_site_description'];
            $config->config_site_ga = $dados['config_sobre'];
            $config->rodape = $dados['config_rodape'];
            $config->config_facebook = $dados['config_site_facebook'];
            $config->config_twitter = $dados['config_site_twitter'];
            $config->config_linkedin = $dados['config_site_linkedin'];
            $config->config_instagram = $dados['config_site_instagram'];
             #dados do slide 1
            $config->config_texto_slide1 = $dados['config_site_texto_slide1'];
            $config->config_descricao_botao_slide1 = $dados['config_site_descricao_botao_slide1'];
            $config->config_link_botao_slide1 = $dados['config_site_link_slide1'];
            #final dados slide 1 

            #dados do slide 2
            $config->config_texto_slide2 = $dados['config_site_texto_slide2'];
            $config->config_descricao_botao_slide2 = $dados['config_site_descricao_botao_slide2'];
            $config->config_link_botao_slide2 = $dados['config_site_link_slide2'];
            #final dados slide 2
                        
           #dados do slide 3
            $config->config_texto_slide3 = $dados['config_site_texto_slide3'];
            $config->config_descricao_botao_slide3 = $dados['config_site_descricao_botao_slide3'];
            $config->config_link_botao_slide3 = $dados['config_site_link_slide3'];
            #final dados slide 3 

            if (!empty($_FILES['logo']['name'])) {
                if ($config->config_foto && file_exists("uploads/empresa/{$config->config_foto}")) {
                    unlink("uploads/empresa/{$config->config_foto}");
                    unlink("uploads/empresa/thumbs/{$config->config_foto}");
                }
                $config->config_foto = $this->logo ?? null;
            }
            
            if (!empty($_FILES['favicon']['name'])) {
                if ($config->config_favicon && file_exists("uploads/empresa/favicon/{$config->config_favicon}")) {
                    unlink("uploads/empresa/favicon/{$config->config_favicon}");
                   
                }
                $config->config_favicon = $this->favicon ?? null;
            }
            if (!empty($_FILES['slide']['name'])) {
                if ($config->config_foto_slide1 && file_exists("uploads/slides/slide1/{$config->config_foto_slide1}")) {
                    unlink("uploads/slides/slide1/{$config->config_foto_slide1}");
                   
                }
                $config->config_foto_slide1 = $this->imagem_slide_um ?? null;
            }

            if (!empty($_FILES['slide_segundo']['name'])) {
                if ($config->config_foto_slide2 && file_exists("uploads/slides/slide_segundo/{$config->config_foto_slide2}")) {
                    unlink("uploads/slides/slide_segundo/{$config->config_foto_slide2}");
                   
                }
                $config->config_foto_slide2 = $this->imagem_slide_segundo ?? null;
            }
           if (!empty($_FILES['slide_terceiro']['name'])) {
                if ($config->config_foto_slide3 && file_exists("uploads/slides/slide_terceiro/{$config->config_foto_slide3}")) {
                    unlink("uploads/slides/slide_terceiro/{$config->config_foto_slide3}");
                   
                }
                $config->config_foto_slide3 = $this->imagem_slide_terceiro ?? null;
            } 
            if ($config->salvar()) {
                $this->mensagem->sucesso('Post atualizado com sucesso')->flash();
                Helpers::redirecionar('admin/configuracao/listar');
            }else {
                    $this->mensagem->erro($config->erro())->flash();
                    Helpers::redirecionar('admin/configuracao/listar');
            }
        }
    }

   }



   public function validarDados(array $dados): bool
   {
       
       if (empty($dados['config_nome'])) {
           $this->mensagem->alerta('Digite um nome para a empresa!')->flash();
           return false;
        }
        
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            
            $upload = new Upload($_FILES['logo'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['config_nome']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process("uploads/empresa/");
                
                if ($upload->processed) {
                   $this->logo = $upload->file_dst_name;
                   $upload->file_new_name_body = $titulo;
                   $upload->image_resize = true;
                   $upload->image_x = 540;
                   $upload->image_y = 304;
                   $upload->jpeg_quality = 70;
                   $upload->image_convert = 'jpg';
                   $upload->process("uploads/empresa/thumbs/");
                   $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        }
        if (!empty($_FILES['favicon'])) {

            $upload_file = new Upload($_FILES['favicon'], 'pt_BR');
            
            if ($upload_file->uploaded) {
                $titulo = $upload_file->file_new_name_body = Helpers::slug($dados['config_nome']);
                $upload_file->jpeg_quality = 90;
                $upload_file->image_convert = 'jpg';
                $upload_file->process("uploads/empresa/favicon/");
                
                if ($upload_file->processed) {
                   $this->favicon = $upload_file->file_dst_name;
                   $upload_file->file_new_name_body = $titulo;
                   $upload_file->image_resize = true;
                   $upload_file->image_x = 540;
                   $upload_file->image_y = 304;
                   $upload_file->jpeg_quality = 70;
                   $upload_file->image_convert = 'jpg';
                   $upload_file->process("uploads/empresa/favicon/thumbs/");
                   $upload_file->clean();
                } else {
                    $this->mensagem->alerta($upload_file->error)->flash();
                    return false;
                }
            }
        }


        if (!empty($_FILES['slide'])) {

            $slide_um = new Upload($_FILES['slide'], 'pt_BR');
            if ($slide_um->uploaded) {
                $titulo = $slide_um->file_new_name_body = Helpers::slug($dados['config_site_texto_slide1']);
                $slide_um->jpeg_quality = 90;
                $slide_um->image_convert = 'jpg';
                $slide_um->process("uploads/slides/slide_um/");
                
                if ($slide_um->processed) {
                   $this->imagem_slide_um = $slide_um->file_dst_name;
                   $slide_um->file_new_name_body = $titulo;
                   $slide_um->image_resize = true;
                   $slide_um->image_x = 540;
                   $slide_um->image_y = 304;
                   $slide_um->jpeg_quality = 70;
                   $slide_um->image_convert = 'jpg';
                   $slide_um->process("uploads/slides/slide_um/thumbs/");
                   $slide_um->clean();
                } else {
                    $this->mensagem->alerta($slide_um->error)->flash();
                    return false;
                }
            }
        }
        if (!empty($_FILES['slide_segundo'])) {

            $slide_segundo = new Upload($_FILES['slide_segundo'], 'pt_BR');
            if ($slide_segundo->uploaded) {
                $titulo = $slide_segundo->file_new_name_body = Helpers::slug($dados['config_site_texto_slide2']);
                $slide_segundo->jpeg_quality = 90;
                $slide_segundo->image_convert = 'jpg';
                $slide_segundo->process("uploads/slides/slide_segundo/");
                
                if ($slide_segundo->processed) {
                   $this->imagem_slide_segundo = $slide_segundo->file_dst_name;
                   $slide_segundo->file_new_name_body = $titulo;
                   $slide_segundo->image_resize = true;
                   $slide_segundo->image_x = 540;
                   $slide_segundo->image_y = 304;
                   $slide_segundo->jpeg_quality = 70;
                   $slide_segundo->image_convert = 'jpg';
                   $slide_segundo->process("uploads/slides/slide_segundo/thumbs/");
                   $slide_segundo->clean();
                } else {
                    $this->mensagem->alerta($slide_segundo->error)->flash();
                    return false;
                }
            }
        }
        if (!empty($_FILES['slide_terceiro'])) {

            $slide_terceiro = new Upload($_FILES['slide_terceiro'], 'pt_BR');
            if ($slide_terceiro->uploaded) {
                $titulo = $slide_terceiro->file_new_name_body = Helpers::slug($dados['config_site_texto_slide3']);
                $slide_terceiro->jpeg_quality = 90;
                $slide_terceiro->image_convert = 'jpg';
                $slide_terceiro->process("uploads/slides/slide_terceiro/");
                
                if ($slide_terceiro->processed) {
                   $this->imagem_slide_terceiro = $slide_terceiro->file_dst_name;
                   $slide_terceiro->file_new_name_body = $titulo;
                   $slide_terceiro->image_resize = true;
                   $slide_terceiro->image_x = 540;
                   $slide_terceiro->image_y = 304;
                   $slide_terceiro->jpeg_quality = 70;
                   $slide_terceiro->image_convert = 'jpg';
                   $slide_terceiro->process("uploads/slides/slide_terceiro/thumbs/");
                   $slide_terceiro->clean();
                } else {
                    $this->mensagem->alerta($slide_terceiro->error)->flash();
                    return false;
                }
            }
        }
       return true;
   }

    
}