document.addEventListener("DOMContentLoaded", function() {
    var sidebarMenuItems = document.querySelectorAll('.sidebarMenu li');

    sidebarMenuItems.forEach(function(item) {
        item.addEventListener('click', function(event) {
            var submenu = this.querySelector('ul');
            var target = event.target;

            // Fecha todos os submenus abertos, exceto o submenu do item clicado
            closeAllSubmenus(submenu);

            if (submenu) {
                event.preventDefault();
                if (submenu.style.display === 'block') {
                    submenu.style.display = 'none';
                } else {
                    submenu.style.display = 'block';
                }
            }

            // Verifica se o item clicado é um link dentro de um submenu
            if (target.tagName === 'A' && target.closest('.submenu')) {
                // Navega para o link
                window.location.href = target.getAttribute('href');
            }
        });
    });

    function closeAllSubmenus(exceptSubmenu) {
        var submenus = document.querySelectorAll('.sidebarMenu li ul');
        submenus.forEach(function(submenu) {
            if (submenu !== exceptSubmenu) {
                submenu.style.display = 'none';
            }
        });
    }
});



    function LimparCamposEndereco() {
        // Limpa valores do formulário de cep.
        $("#rua").val("");
        $("#bairro").val("");
        $("#cidade").val("");
        $("#estado").val("");
        
    }

    
    function TravarCamposEndereco(readonly){
       
           /*  $("#cidade").attr("readonly",readonly );
            $("#estado").attr("readonly",readonly ); */
           
    }
    
    
     //Quando o campo cep perde o foco.
    function BuscarCep(){
       
        //Nova variável "cep" somente com dígitos.
        var cep = $(".cep").val().replace(/\D/g, '');
    
        //Verifica se campo cep possui valor informado.
        if (cep != "") {
    
            //Expressão regular para validar o CEP.
            var validacep = /^[0-9]{8}$/;
    
            //Valida o formato do CEP.
            if(validacep.test(cep)) {
    
                //Preenche os campos com "..." enquanto consulta webservice.
                $(".rua").val("...");
                $(".bairro").val("...");
                $(".cidade").val("...");
                $(".estado").val("...");
               
                //Consulta o webservice viacep.com.br/
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {
                    if (!("erro" in dados)) {
                        //Atualiza os campos com os valores da consulta.
                        $(".rua").val(dados.logradouro);
                        $(".bairro").val(dados.bairro);
                        $(".cidade").val(dados.localidade);
                        $(".estado").val(dados.uf);
                        TravarCamposEndereco(true);
                    } //end if.
                    else {
                        //CEP pesquisado não foi encontrado.
                        LimparCamposEndereco();
                        TravarCamposEndereco(false);
                    }
                });
            } //end if.
            else {
                //cep é inválido.
                LimparCamposEndereco();
            }
        } //end if.
        else {
            //cep sem valor, limpa formulário.
            limpa_formulário_cep();
        }
    }


    function BASE_URL_AJAX(dataview) {
        return "http://localhost/arthorius/admin/aluguel/" + dataview;
    }
 

function ListarEquipamentosLocados()
{
    alert('chamou');
    var url = 'http://localhost/arthorius/admin/aluguel/listarEquipamentosAlocados/2';
    $.ajax({
        type: "POST",
        url: url,
        data:{
            btn_consultar: 'ajax'
        },success: function (tabela_preenchida){
           
        }
    });

}

/* $(document).ready(function () {

    $('#inserirEquipamento').click(function (event) {
    event.preventDefault();
    var carregando = $('.ajaxLoading');
    var url = $("#url").val();
    console.log(url);
    var formulario = $(this);
    var formData = new FormData();
    formData.append('equipamentoId', $("#equipamentoId").val());
    formData.append('qtd', $("#qtd").val());
    formData.append('dataDevolucao', $("#dataDevolucao").val());
    formData.append('horaDevolucao', $("#horaDevolucao").val());
    $.ajax({
        type: 'POST',
        url:url,
        data: formData,
        dataType: 'json',
        contentType: false,
        processData: false,
        beforeSend: function () {
            carregando.show().fadeIn(200);
          
          // Remover a classe 'is-invalid' de todos os campos antes de enviar o formulário
          formulario.find('.is-invalid').removeClass('is-invalid');
        },
        success: function (retorno) {
            if (Array.isArray(retorno)) {
                console.log(retorno);
                // Se o retorno for uma matriz de objetos JSON
                retorno.forEach(function (equipamento) {
                    var equipamentoHtml = '<tr>' +
                        '<td>' + equipamento.id + '</td>' +
                        '<td>' + equipamento.nome + '</td>' +
                        '<td class="text-center">' + equipamento.valorLocacao + '</td>' +
                        '<td class="text-center">' + equipamento.quantidade + '</td>' +
                        '<td class="text-center">' + equipamento.valorTotal + '</td>' +
                        '<td class="text-center">' + equipamento.dataDevolucao + '</td>' +
                        '<td class="text-center">' +
                        '<button class="btn btn-danger excluir-equipamento" data-id="' + equipamento.id + '">Excluir</button>' +
                        '</td>' +
                        '</tr>';
                        
                    $('#equipamentos-tbody').append(equipamentoHtml);
                });
            } else {
                // Se o retorno for um único objeto JSON
                var equipamentoHtml = '<tr>' +
                    '<td>' + retorno.id + '</td>' +
                    '<td>' + retorno.nome + '</td>' +
                    '<td class="text-center">' + retorno.valorLocacao + '</td>' +
                    '<td class="text-center">' + retorno.quantidade + '</td>' +
                    '<td class="text-center">' + retorno.valorTotal + '</td>' +
                    '<td class="text-center">' + retorno.dataDevolucao + '</td>' +
                    '<td class="text-center">' +
                    '<button class="btn btn-danger excluir-equipamento" data-id="' + retorno.id + '">Excluir</button>' +
                    '</td>' +
                    '</tr>';
                $('#equipamentos-tbody').append(equipamentoHtml);
            }
        },
        complete: function () {
            carregando.hide().fadeOut(200);
         
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR, textStatus, errorThrown);
        }
    });

});

});
 */


$(document).ready(function () {

    $('#inserirEquipamento').click(function (event) {
        event.preventDefault();
        var dataDevolucao = $("#dataDevolucao").val();
        var horaDevolucao = $("#horaDevolucao").val();
        var hoje = new Date().toISOString().split('T')[0]; // Obter a data de hoje no formato 'yyyy-mm-dd'
        var agora = new Date().toLocaleTimeString('en-US', {hour12: false}); // Obter a hora atual no formato 'HH:mm'

        if (dataDevolucao == '' || horaDevolucao == '') {
            alerta('Preencher campos obrigatórios', 'yellow');
            $("#dataDevolucao").addClass('is-invalid');
            $("#horaDevolucao").addClass('is-invalid');
            return;
        } else if (dataDevolucao < hoje || (dataDevolucao == hoje && horaDevolucao <= agora)) {
            alerta('A data e hora de devolução devem ser iguais ou posteriores à data e hora atuais', 'yellow');
            $("#dataDevolucao").addClass('is-invalid');
            $("#horaDevolucao").addClass('is-invalid');
            return;
        } else {
            $("#dataDevolucao").removeClass('is-invalid');
            $("#horaDevolucao").removeClass('is-invalid');
            $("#dataDevolucao").addClass('is-valid');
            $("#horaDevolucao").addClass('is-valid');
        }

        var carregando = $('.ajaxLoading');
        var url = $("#url").val();
        var formulario = $(this);
        var formData = new FormData();
        formData.append('equipamentoId', $("#equipamentoId").val());
        formData.append('dataDevolucao', dataDevolucao);
        formData.append('horaDevolucao', horaDevolucao);
        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function () {
                carregando.show().fadeIn(200);
                // Remover a classe 'is-invalid' de todos os campos antes de enviar o formulário
                formulario.find('.is-invalid').removeClass('is-invalid');
            },
            success: function (retorno) {
                console.log(retorno);
                alerta('Inserido com sucesso', 'green');
                $('#equipamentos-tbody').html(retorno);
            },
            complete: function () {
                carregando.hide().fadeOut(200);
            },
            error: function (jqXHR, textStatus, errorThrown, responseText) {
                console.log(jqXHR, textStatus, errorThrown);
                $('#equipamentos-tbody').html(responseText);
            }
        });
    });
});






$(document).ready(function () {

    $(document).ready(function () {
        $('#equipamentos-tbody').on('click', '.deletarEquipamentoAlocado', function (event) {
            event.preventDefault();
            var url = $(this).data('url');
            var botaoClicado = $(this); // Captura o botão clicado para manipulação posterior
    
            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                contentType: false,
                processData: false,
                success: function (retorno) {
                    if (retorno.erro) {
                        alerta(retorno.erro, 'yellow');
                        if (retorno.erro.includes('preencha os campos obrigatórios')) {
                            formulario.find('.obg').addClass('is-invalid');
                        }
                    }
                    if (retorno) {
                        $('#equipamentos-tbody').html(retorno);
                        alerta('Deletado com sucesso', 'green');
                        // Remove a linha da tabela onde o botão foi clicado
                        botaoClicado.closest('tr').remove();
                    }
                },
                complete: function () {
                    carregando.hide().fadeOut(200);
                },
                error: function (jqXHR, textStatus, errorThrown, responseText) {
                    console.log(jqXHR, textStatus, errorThrown);
                    $('#equipamentos-tbody').html(responseText);
                }
            });
        });
    });
});

$(document).ready(function() {
    $(".toggle-details").click(function() {
        var modalId = $(this).data("target");
        $(modalId).modal("show");
    });
});



function alerta(mensagem, cor) {
    new jBox('Notice', {
        content: mensagem,
        color: cor,
        animation: 'pulse',
        showCountdown: true
    });
}

