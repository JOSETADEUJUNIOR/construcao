$(document).ready(function () {

    $('.formulario').submit(function (event) {
        event.preventDefault();

        var carregando = $('.ajaxLoading');
        var botao = $(':input[type="submit"]');

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function () {
                carregando.show().fadeIn(200);
                botao.prop('disable', false).addClass('disabled');
            },
            success: function (retorno) {

                if (retorno.erro) {
                    alerta(retorno.erro, 'yellow');
                }
                if (retorno.successo) {
                    
                    $('.formulario')[0].reset();
                    $('#contatoModal').modal('hide');
                    
                    alerta(retorno.successo, 'green');
                }

                if (retorno.redirecionar) {
                    window.location.href = retorno.redirecionar;
                }

            },
            complete: function () {
                carregando.hide().fadeOut(200);
                botao.removeClass('disabled');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });

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
document.addEventListener('DOMContentLoaded', (event) => {
    document.querySelectorAll('pre').forEach((el) => {
        hljs.highlightElement(el);
    });
});

// Inicializa o contador de cliques
var contagemCliques = 0;

// Função para contar cliques e abrir o link
function contaClick() {
    var url = document.getElementById('url').value;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', url);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            var resultado = xhr.responseText;
            if (resultado === "1") {
                // Abre o WhatsApp Web em uma nova aba
                window.open("https://web.whatsapp.com/send?phone=5543984230969&text=Gostaria%20de%20agendar%20um%20hor%C3%A1rio", "_blank");
            } else {
                // Exibe mensagem de erro
                alert("Erro ao processar a solicitação.");
            }
        } else {
            console.error('Erro na requisição. Status:', xhr.status);
        }
    };
    xhr.onerror = function() {
        console.error('Erro na requisição. Status:', xhr.status);
    };
    xhr.send();

    return false; // Evita que o navegador siga o link original
}



