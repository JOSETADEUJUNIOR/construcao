//################# BOOTSTRAP #####################

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[tooltip="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

//################# FIM BOOTSTRAP #################

document.addEventListener('keydown', function (event) {
    if (event.key === 'F4') {
        event.preventDefault(); // Impede o comportamento padrão da tecla F4
        document.getElementById('btnClienteCPF').click(); // Aciona o clique no botão
    }
});


$(document).ready(function () {

    $('.formularioAjax').submit(function (event) {
        event.preventDefault();
        var carregando = $('.ajaxLoading');
        var botao = $(':input[type="submit"]');
        var url = $(this).attr('action');
        var formulario = $(this);
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
                // Remover a classe 'is-invalid' de todos os campos antes de enviar o formulário
                formulario.find('.is-invalid').removeClass('is-invalid');
            },
            success: function (retorno) {

                if (retorno.erro) {
                    alerta(retorno.erro, 'yellow');
                    console.log(retorno.erro);
                    // Se a mensagem de erro indicar campos obrigatórios
                    if (retorno.erro.includes('preencha os campos obrigatórios')) {
                        // Adicione a classe 'is-invalid' aos campos obrigatórios
                        formulario.find('.obg').addClass('is-invalid');
                    }
                }
                if (retorno.successo) {
                    $('.formularioAjax')[0].reset();
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


$(document).ready(function () {
    // Manter o código anterior...

    // Nova função para cancelar o produto
    $('#form-cancelar-produto').submit(function (event) {
        event.preventDefault();

        var formData = $(this).serialize();
        var carregando = $('.ajaxLoading');
        var modal = $('#modalCancelarItem');

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            dataType: 'json',
            beforeSend: function () {
                carregando.show().fadeIn(200);
            },
            success: function (retorno) {
                console.log(retorno);
                if (retorno.successo) {
                    alerta(retorno.successo, 'green');
                    // Atualizar a lista de produtos
                    atualizarListaProdutos();
                } else if (retorno.erro) {
                    alerta(retorno.erro, 'yellow');
                }
                modal.modal('hide');
            },
            complete: function () {
                carregando.hide().fadeOut(200);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    });

    // Função para atualizar a lista de produtos
    function atualizarListaProdutos() {
        $.ajax({
            url: '{{ url("lista-produtos") }}', // Endpoint para obter a lista de produtos
            dataType: 'json', // Alterado para 'json' pois estamos esperando um JSON como resposta
            success: function (data) {
                console.log(data);
                $('#lista-itens').html($(data).find('#lista-itens').html());
                $('#total-itens').text($(data).find('#total-itens').text());
                $('#subtotal-geral').text($(data).find('#subtotal-geral').text());
                $('#total-geral').text($(data).find('#total-geral').text());

                // Atualizar o número da venda, se estiver presente nos dados retornados
                if (data.numero_venda) {
                    $('#numero-venda .text-success').text(data.numero_venda);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    }
});

$(document).ready(function () {
    $("#produto").keyup(function () {
        var busca = $(this).val();
        var url = $("#form-venda-produto").attr('action');

        if (busca.length > 0) {
            $.ajax({
                url: url + '/busca', // Substitua pelo caminho da sua rota
                method: 'POST',
                data: {
                    busca: busca
                },
                dataType: 'json',
                success: function (resultado) {
                    console.log(resultado); // Log da resposta para verificação
                    $('#produto-list').empty(); // Limpa a lista antes de adicionar novos itens
                    if (Array.isArray(resultado) && resultado.length > 0) {
                        resultado.forEach(function (item) {
                            $('#produto-list').append('<li style="background-color: #414e6c; color:white" class="list-group-item list-group-item-action" data-id="' + item.id + '">' + item.nome + ' / R$:' + item.preco + '</li>');
                        });
                    } else {
                        $('#produto-list').html('<div class="alert alert-warning">Nenhum resultado encontrado!</div>');
                    }
                }
            });
            $('#produto-list').show();
        } else {
            $('#produto-list').hide();
        }
    });

    // Quando um item é clicado
    $(document).on("click", "#produto-list li", function () {
        var selectedText = $(this).text();
        var selectedId = $(this).attr('data-id');
        $("#produto").val(selectedText);
        $("#produto-id").val(selectedId);
        $("#produto-list").empty();

        // Defina a quantidade padrão como 1
        $("#quantidade").val(1);

        // Envie o formulário automaticamente
        $("#form-venda-produto").submit();
    });

    // Esconder a lista quando clicar fora do input
    $(document).click(function (event) {
        if (!$(event.target).closest('#produto-list').length && !$(event.target).is('#produto')) {
            $("#produto-list").hide();
        }
    });
});

$(document).ready(function () {
    $('.formularioAjaxPDV').submit(function (event) {
        event.preventDefault();
        var carregando = $('.ajaxLoading');
        var botao = $(':input[type="submit"]');
        var url = $(this).attr('action');
        var formulario = $(this);
        $.ajax({
            type: 'POST',
            url: url,
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function () {
                carregando.show().fadeIn(200);
                botao.prop('disabled', true).addClass('disabled');
                formulario.find('.is-invalid').removeClass('is-invalid');
            },
            success: function (retorno) {
                if (retorno.erro) {
                    alerta(retorno.erro, 'yellow');
                    console.log(retorno.erro);
                    if (retorno.erro.includes('preencha os campos obrigatórios')) {
                        formulario.find('.obg').addClass('is-invalid');
                    }
                }
                if (retorno.successo) {
                    formulario[0].reset();
                    alerta(retorno.successo, 'green');
                    atualizarListaProdutos(retorno.numero_venda); // Passa o número da venda
                }
                if (retorno.redirecionar) {
                    window.location.href = retorno.redirecionar;
                }
            },
            complete: function () {
                carregando.hide().fadeOut(200);
                botao.prop('disabled', false).removeClass('disabled');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    });

    function atualizarListaProdutos(numeroVenda) {
        $.ajax({
            type: 'GET',
            url: 'lista-produtos',
            dataType: 'json',
            success: function (produtos) {
                console.log(produtos);
                var tabelaCorpo = $('table tbody');
                tabelaCorpo.empty(); // Limpa a tabela atual

                var totalItens = 0;
                var subtotalGeral = 0;

                produtos.forEach(function (produto) {
                    tabelaCorpo.append(
                        '<tr>' +
                        '<td>' + produto.id + '</td>' +
                        '<td>' + produto.produto + '</td>' +
                        '<td>' + produto.quantidade + '</td>' +
                        '<td>R$ <span class="subtotal">' + produto.preco_venda + '</span></td>' +
                        '<td class="text-right">R$ <span class="total">' + produto.total + '</span></td>' +
                        '</tr>'
                    );
                    totalItens += parseInt(produto.quantidade);
                    subtotalGeral += parseFloat(produto.total.replace('.', '').replace(',', '.'));
                });

                $('#total-itens').text(totalItens);
                $('#subtotal-geral').text(subtotalGeral.toFixed(2).replace('.', ','));
                $('#total-geral').text(subtotalGeral.toFixed(2).replace('.', ','));

                // Atualizar o número da venda, se estiver presente nos dados retornados
                if (produtos[0].numero_venda) {
                    $('#numero-venda .text-success').text(produtos[0].numero_venda);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    }
});


//venda de combustiveis
$(document).ready(function () {
    // Manipulador para formularios de venda de combustivel
    $('.formularioAjaxCombustivel').submit(function (event) {
        event.preventDefault();
        console.log('chamou');
        var form = $(this);
        var url = form.attr('action');
        var formData = form.serialize();
        console.log(url);

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            dataType: 'json',
            beforeSend: function () {
                form.find(':input').prop('disabled', true);
            },
            success: function (response) {
                if (response.erro) {
                    alerta(response.erro, 'yellow');
                } else if (response.successo) {
                    form[0].reset();
                    alerta(response.successo, 'green');
                    form.closest('.modalCombustivel').modal('hide');
                    atualizarListaCombustivel(response.numero_venda);
                }
            },
            complete: function () {
                form.find(':input').prop('disabled', false);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    });

    // Atualizar a lista de combustiveis
    function atualizarListaCombustivel(numeroVenda) {
        $.ajax({
            type: 'GET',
            url: 'lista-produtos',
            dataType: 'json',
            success: function (combustiveis) {
                console.log(combustiveis);
                var tabelaCorpo = $('table tbody');
                tabelaCorpo.empty(); // Limpa a tabela atual

                var totalItens = 0;
                var subtotalGeral = 0;

                combustiveis.forEach(function (combustivel) {
                    tabelaCorpo.append(
                        '<tr>' +
                        '<td>' + combustivel.id + '</td>' +
                        '<td>' + combustivel.produto + '</td>' +
                        '<td>' + combustivel.quantidade + '</td>' +
                        '<td>R$ <span class="subtotal">' + combustivel.preco_venda + '</span></td>' +
                        '<td class="text-right">R$ <span class="total">' + combustivel.total + '</span></td>' +
                        '</tr>'
                    );
                    totalItens += parseInt(combustivel.quantidade);
                    subtotalGeral += parseFloat(combustivel.total.replace('.', '').replace(',', '.'));
                });

                $('#total-itens').text(totalItens);
                $('#subtotal-geral').text(subtotalGeral.toFixed(2).replace('.', ','));
                $('#total-geral').text(subtotalGeral.toFixed(2).replace('.', ','));

                // Atualizar o número da venda, se estiver presente nos dados retornados
                if (combustiveis[0].numero_venda) {
                    $('#numero-venda .text-success').text(combustiveis[0].numero_venda);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    }
});







$(document).ready(function () {
    // Atualizar o valor total e o valor final no modal
    $('#modalFinalizarCompra').on('show.bs.modal', function () {
        var totalGeral = parseFloat($('#total-geral').text().replace(',', '.'));
        $('#total-compra').val(totalGeral.toFixed(2));
        $('#total-final').val(totalGeral.toFixed(2));
    });

    // Atualizar o valor final quando o desconto for alterado
    $('#desconto').on('input', function () {
        var totalCompra = parseFloat($('#total-compra').val());
        var desconto = parseFloat($(this).val());
        var totalFinal = totalCompra - desconto;
        $('#total-final').val(totalFinal.toFixed(2));
    });

    // Atualizar o valor final quando a forma de pagamento for alterada, se necessário
    $('#forma-pagamento').on('change', function () {
        // Pode ser usado para aplicar taxas específicas de forma de pagamento
    });
});
// Atualiza o total final ao alterar o desconto
document.getElementById('desconto').addEventListener('input', function () {
    var totalCompra = parseFloat(document.getElementById('total-compra').value.replace(',', '.'));
    var desconto = parseFloat(this.value.replace(',', '.'));
    var totalFinal = totalCompra - desconto;
    document.getElementById('total-final').value = totalFinal.toFixed(2).replace('.', ',');
});

// Set the total compra value from the existing total_geral variable
$(document).ready(function () {
    var totalGeral = "{{ total_geral|number_format(2, ',', '.') }}";
    $('#total-compra').val(totalGeral);
    $('#total-final').val(totalGeral);
});

$(document).on('click', '#selecionar-cliente', function () {
    var clienteId = $('#clienteSelect').val(); // obtém o valor selecionado no select
    var clienteNome = $('#clienteSelect option:selected').text(); // obtém o texto da opção selecionada

    // Fechar o modal
    $('#modalSelecionarCliente').modal('hide');

    // Atualizar campos ocultos com os dados do cliente
    $('#id_cliente').val(clienteId);
    $('#id_cliente_combustivel').val(clienteId);
    $('#nome_cliente').val(clienteNome);

    // Atualizar a interface com o nome do cliente
    $('#nome_cliente_text').text(clienteNome);
});


document.getElementById('forma-pagamento').addEventListener('change', function () {
    var multiPagamentoDiv = document.getElementById('multi-pagamento');
    if (this.value === 'mais') {
        multiPagamentoDiv.style.display = 'block';
    } else {
        multiPagamentoDiv.style.display = 'none';
    }
});

document.getElementById('valor_recebido').addEventListener('blur', function () {
    calcularTroco();
});

document.getElementById('desconto').addEventListener('blur', function () {
    calcularTroco();
});

function calcularTroco() {
    var valorRecebido = parseFloat(document.getElementById('valor_recebido').value.replace(',', '.')) || 0;
    var totalGeral = parseFloat(document.getElementById('valor_total').innerText.replace(',', '.')) || 0; // O valor total da venda
    var desconto = parseFloat(document.getElementById('desconto').value.replace(',', '.')) || 0;
    var totalComDesconto = totalGeral - desconto;
    var troco = valorRecebido - totalComDesconto;

    if (isNaN(troco)) {
        troco = 0;
    }

    document.getElementById('troco').value = troco.toFixed(2);
    document.getElementById('valor-troco').innerText = troco.toFixed(2);
}

function calcularTrocoMultiPagamento() {
    var valorDinheiro = parseFloat(document.getElementById('vlr_dinheiro').value.replace(',', '.')) || 0;
    var valorCartao = parseFloat(document.getElementById('vlr_cartao').value.replace(',', '.')) || 0;
    var valorPix = parseFloat(document.getElementById('vlr_pix').value.replace(',', '.')) || 0;
    var totalPago = valorDinheiro + valorCartao + valorPix;

    var totalGeral = parseFloat(document.getElementById('valor_total').innerText.replace(',', '.')) || 0; // O valor total da venda
    var desconto = parseFloat(document.getElementById('desconto').value.replace(',', '.')) || 0;
    var totalComDesconto = totalGeral - desconto;
    var troco = totalPago - totalComDesconto;

    if (isNaN(troco)) {
        troco = 0;
    }

    document.getElementById('troco').value = troco.toFixed(2);
    document.getElementById('valor-troco').innerText = troco.toFixed(2);
}

document.getElementById('vlr_dinheiro').addEventListener('blur', calcularTrocoMultiPagamento);
document.getElementById('vlr_cartao').addEventListener('blur', calcularTrocoMultiPagamento);
document.getElementById('vlr_pix').addEventListener('blur', calcularTrocoMultiPagamento);

$(document).ready(function () {
    $('.formularioAjaxProd').submit(function (event) {
        event.preventDefault();
        var carregando = $('.ajaxLoading');
        var botao = $(':input[type="submit"]');
        var formulario = $(this);
        $.ajax({
            type: 'POST',
            url: formulario.attr('action'),
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function () {
                carregando.show().fadeIn(200);
                botao.prop('disabled', true).addClass('disabled');
                formulario.find('.is-invalid').removeClass('is-invalid');
            },
            success: function (retorno) {
                if (retorno.erro) {
                    alerta(retorno.erro, 'yellow');
                    if (retorno.erro.includes('preencha os campos obrigatórios')) {
                        formulario.find('.obg').addClass('is-invalid');
                    }
                }
                if (retorno.successo) {
                    $('.formularioAjaxProd')[0].reset();
                    alerta(retorno.successo, 'green');
                    // Atualizar a lista de produtos
                    atualizarListaProdutos(retorno.produtos);
                }

                if (retorno.redirecionar) {
                    window.location.href = retorno.redirecionar;
                }
            },
            complete: function () {
                carregando.hide().fadeOut(200);
                botao.prop('disabled', false).removeClass('disabled');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    });



    function atualizarListaProdutos(produtos) {
        var listaItens = $('#lista-itens');
        listaItens.empty(); // Limpar a lista atual
        var quantidadeTotal = 0;
        var subtotal = 0;

        produtos.forEach(function (produto) {
            // Verificar se produto.preco_venda é um número
            var precoVenda = parseFloat(produto.preco_venda);
            if (isNaN(precoVenda)) {
                precoVenda = 0.0;
            }

            var quantidade = parseInt(produto.quantidade);
            if (isNaN(quantidade)) {
                quantidade = 0;
            }

            var totalProduto = precoVenda * quantidade;
            if (isNaN(totalProduto)) {
                totalProduto = 0.0;
            }

            var itemHtml = `
                <div class="pdv-item">
                    <div>${produto.produto}</div>
                    <div>${quantidade} x R$ ${precoVenda.toFixed(2)} = R$ ${totalProduto.toFixed(2)}</div>
                </div>
            `;
            listaItens.append(itemHtml);
            quantidadeTotal += quantidade;
            subtotal += totalProduto;
        });

        $('#quantidade-total').text(quantidadeTotal);
        $('#subtotal').text(subtotal.toFixed(2));
        $('#total').text(subtotal.toFixed(2));
    }
});

function alerta(mensagem, cor) {
    new jBox('Notice', {
        content: mensagem,
        color: cor,
        animation: 'pulse',
        showCountdown: true
    });
}

