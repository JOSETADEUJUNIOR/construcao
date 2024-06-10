<?php

namespace sistema\Controlador;

use sistema\Controlador\UsuarioControlador;
use sistema\Modelo\GaleriaModelo;
use sistema\Modelo\SolicitacaoModelo;
use sistema\Modelo\UsuarioModelo;
use sistema\Nucleo\Controlador;
use sistema\Nucleo\Sessao;
use sistema\Nucleo\Helpers;
use sistema\Suporte\Email;
use Verot\Upload\Upload;

/**
 * Classe SaasControlador
 *
 * @author Ronaldo Aires
 */
class SaasControlador extends Controlador
{

   



    private $usuario;
    private string $capa;
    private string $video;
    
    public function __construct()
    {
        parent::__construct('templates/saas/views');
        
        $this->usuario = UsuarioControlador::usuario();
        
        if(!$this->usuario OR $this->usuario->level < 1){
            $this->mensagem->alerta('Faça login para acessar o painel do usuário!')->flash();
            
            $sessao = new Sessao();
            $sessao->limpar('usuarioId');
            
            Helpers::redirecionar();
        }
    }

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
                    $post->parecer_tecnico
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


    public function index(): void
    {
        $this->usuario = UsuarioControlador::usuario();
        echo $this->template->renderizar('conta.html', [
            'titulo' => 'Minha Conta',
            'usuario' => $this->usuario
            
        ]); 
    }

    
    public function chamados(): void
    {
        $solicitacoes = new SolicitacaoModelo();
        echo $this->template->renderizar('chamados.html', [
            'titulo' => 'Chamados',
            'solicitacoes' => $solicitacoes->busca("usuario_id = {$this->usuario->id}")->resultado(true),
            'total' => [
                'solicitacao'               => $solicitacoes->busca("usuario_id = {$this->usuario->id}")->total(),
                'solicitacaoAndamento'      => $solicitacoes->busca("status = 2 and usuario_id = {$this->usuario->id}")->total(),
                'solicitacaoAberto'         => $solicitacoes->busca("status = 1 and usuario_id = {$this->usuario->id}")->total(),
                'solicitacaoEncerrado'      => $solicitacoes->busca("status = 3 and usuario_id = {$this->usuario->id} ")->total(),
            ]
           
            
        ]); 
    }

    public function criar(): void
    {
        echo $this->template->renderizar('chamado-criar.html', [
            'titulo' => 'Chamados',
           
            
        ]); 
    }
    
    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        if (isset($dados)) {

            if ($this->validarDados($dados)) {
                $post = new SolicitacaoModelo();

                $post->titulo = $dados['titulo'];
                $post->usuario_id = $this->usuario->id;
                $post->descricao = $dados['texto'];
                $post->prioridade = $dados['prioridade'];
                $post->slug = Helpers::slug($dados['titulo']);
                $post->fotos = $this->capa ?? null;
                $post->video = $this->video ?? null;
                $post->status = 1;
                if ($post->salvar()) {
                        $chamado = $post->ultimoId();
                        $email = new Email();
                        $view = $this->template->renderizar('envia-email-chamado.html', [
                            'chamado'       => $post,
                            'chamado_numero'=> $chamado,
                            'data'          => date('d/m/Y'),
                            'usuario'       => $this->usuario
                        ]);

                        $email->criar(
                                'Novo Chamado Aberto - ' . SITE_NOME,
                                $view,
                                $this->usuario->email,
                                $this->usuario->nome,
                                EMAIL_USUARIO,
                                SITE_NOME
                                
                               
                                
                        );

                        $email->enviar(EMAIL_REMETENTE['email'], EMAIL_REMETENTE['nome'], EMAIL_REMETENTE['email'], EMAIL_REMETENTE['nome']);

                       
                    
                    $this->mensagem->sucesso('Chamado registrado com sucesso')->flash();
                    Helpers::redirecionar('saas/chamados');
                } else {
                    $this->mensagem->erro($post->erro())->flash();
                    Helpers::redirecionar('saas/chamados');
                }
            }
        }

        echo $this->template->renderizar('saas/chamados.html', [
            'solicitacoes' => (new SolicitacaoModelo())->busca()->resultado(true),
            'post' => $dados
        ]);
    }

    public function validarDados(array $dados): bool
    {

        if (empty($dados['titulo'])) {
            $this->mensagem->alerta('Escreva um título para o Post!')->flash();
            return false;
        }
        if (empty($dados['texto'])) {
            $this->mensagem->alerta('Escreva um texto para o Post!')->flash();
            return false;
        }

         if (!empty($_FILES['capa'])) {
            $upload = new Upload($_FILES['capa'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/cliente/');

                if ($upload->processed) {
                    $this->capa = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/cliente/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        } 

        if (!empty($_FILES['video'])) {
            $upload = new Upload($_FILES['video'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/videos/');

                if ($upload->processed) {
                    $this->video = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/videos/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        }

        return true;
    }


    public function editar(int $id): void
    {
        $solicitacao = (new SolicitacaoModelo())->buscaPorId($id);
        // Verificar se o post existe
        if (!$solicitacao) {
            Helpers::redirecionar('404');
        }
       /*  // Buscar as imagens da galeria associadas ao post
        $imagens = (new GaleriaModelo())->busca("post_id = $post->id")->resultado(true); */
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $post = (new SolicitacaoModelo())->buscaPorId($id);
                $post->titulo = $dados['titulo'];
                $post->usuario_id = $this->usuario->id;
                $post->descricao = $dados['texto'];
                $post->prioridade = $dados['prioridade'];
                $post->slug = Helpers::slug($dados['titulo']);
                $post->fotos = $this->capa ?? null;
                $post->video = $this->video ?? null;
                $post->atualizado_em = date('Y-m-d H:i:s');
                $post->status = 1;

               /*  if (!empty($_POST['remover_fotos'])) {
                    foreach ($_POST['remover_fotos'] as $idFoto) {
                        $imagemDeletar = (new GaleriaModelo())->buscaPorId($idFoto);
                        $caminho_imagem = "uploads/imagens/{$imagemDeletar->foto}";
                        $caminho_thumb = "uploads/imagens/thumbs/{$imagemDeletar->foto}";
                        
                        if (file_exists($caminho_imagem)) {
                            unlink($caminho_imagem);
                        }
        
                        if (file_exists($caminho_thumb)) {
                            unlink($caminho_thumb);
                        }
        
                        $imagemDeletar->deletar();

                    }
                } */
    
                // Gravar as novas fotos

               /*  foreach ($_FILES['fotos']['tmp_name'] as $indice => $tmp_name) {
                    $foto_nome = $_FILES['fotos']['name'][$indice];
                    $foto_tmp = $_FILES['fotos']['tmp_name'][$indice];

                    // Realizar o upload da foto
                    $upload = new Upload($foto_tmp, 'pt_BR');
                    if ($upload->uploaded) {
                        $titulo_foto = Helpers::slug($dados['titulo'] . '-' . $foto_nome);
                        $upload->file_new_name_body = $titulo_foto;
                        $upload->image_resize = true;
                        $upload->image_x = 800;
                        $upload->image_y = 600;
                        $upload->jpeg_quality = 90;
                        $upload->image_convert = 'jpg';
                        $upload->process('uploads/imagens/');

                        if ($upload->processed) {
                            // Salvar o nome da foto na galeria

                            $upload->file_new_name_body = $titulo_foto;
                            $upload->image_resize = true;
                            $upload->image_x = 540;
                            $upload->image_y = 304;
                            $upload->jpeg_quality = 70;
                            $upload->image_convert = 'jpg';
                            $upload->process('uploads/imagens/thumbs/');
                            $upload->clean();
                            $foto = new GaleriaModelo();
                            $foto->foto = $upload->file_dst_name;
                            $foto->post_id = $post->id; // Relacionar a foto com o post
                            $foto->usuario_id = $this->usuario->id;
                            $foto->status = 'ativo'; // Definir o status da foto
                            $foto->salvar();
                        } else {
                            // Lidar com erros de upload, se necessário
                            $this->mensagem->alerta($upload->error)->flash();
                        }
                    }
                } */

                 //atualizar a capa no DB e no servidor, se um novo arquivo de imagem for enviado
                 if (!empty($_FILES['capa']["name"])) {
                    if ($post->fotos && file_exists("uploads/cliente/{$post->fotos}")) {
                        unlink("uploads/imagens/{$post->fotos}");
                        unlink("uploads/imagens/thumbs/{$post->fotos}");
                    }
                    $post->fotos = $this->capa ?? null;
                }

                if (!empty($_FILES['video']["name"])) {
                    if ($post->video && file_exists("uploads/videos/{$post->video}")) {
                        unlink("uploads/videos/{$post->video}");
                        unlink("uploads/videos/thumbs/{$post->video}");
                    }
                    $post->video = $this->video ?? null;
                }
    
                if ($post->salvar()) {
                    $this->mensagem->sucesso('Chamado atualizado com sucesso')->flash();
                    Helpers::redirecionar('saas/chamados');
                } else {
                    $this->mensagem->erro($post->erro())->flash();
                    Helpers::redirecionar('saas/chamados');
                }
            }
        }
        echo $this->template->renderizar('chamado-criar.html', [
            'solicitacao'          => $solicitacao,
        ]);
    }

    public function sair(): void
    {
        $sessao = new Sessao();
        $sessao->limpar('usuarioId');

        $this->mensagem->informa('Você saiu do painel de controle!')->flash();
        Helpers::redirecionar('/logar');
    }

    public function deletar(int $id): void
    {
        if (is_int($id)) {
            $post = (new SolicitacaoModelo())->buscaPorId($id);
            if (!$post) {
                $this->mensagem->alerta('O chamado que você está tentando deletar não existe!')->flash();
                Helpers::redirecionar('saas/chamados');
            } else {
                if ($post) {
                    # code...
                }
                if ($post->deletar()) {

                    if ($post->capa && file_exists("uploads/cliente/{$post->fotos}")) {
                        unlink("uploads/cliente/{$post->fotos}");
                        unlink("uploads/cliente/thumbs/{$post->fotos}");
                    }

                    $this->mensagem->sucesso('Chamado deletado com sucesso!')->flash();
                    Helpers::redirecionar('saas/chamados');
                } else {
                    $this->mensagem->erro($post->erro())->flash();
                    Helpers::redirecionar('saas/chamados');
                }
            }
        }
    }
}
