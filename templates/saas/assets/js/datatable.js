$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
    var url = $('table').attr('url');

    $.extend($.fn.dataTable.defaults, {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.3/i18n/pt-BR.json'
        },
        initComplete: function (settings, json) {
            $('[tooltip="tooltip"]').tooltip( );
        }
    });

    $('#tabelaCategorias').DataTable({
        paging: false,
        columnDefs: [
            {
                targets: [-1, -2],
                orderable: false
            }
        ],
        order: [[1, 'asc']]
    });

    
    //TABELA USUÁRIOS
    $('#tabelaUsuarios').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url + 'admin/usuarios/datatable',
            type: 'POST',
            error: function (xhr, resp, text) {
                console.log(xhr, resp, text);
            }
        },

        columns: [
            null, null, null,
            {
                data: null,
                render: function (data, type, row) {
                    if (row[3] === 3) {
                        return '<span class="text-danger">Administrador</span>';
                    } else {
                        return '<span class="text-secondary">Usuário</span>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    if (row[4] === 1) {
                        return '<i class="fa-solid fa-circle text-success" tooltip="tooltip" title="Ativo"></i>';
                    } else {
                        return '<i class="fa-solid fa-circle text-danger" tooltip="tooltip" title="Inativo"></i>';
                    }
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    var html = '';

                    html += ' <a href=" ' + url + 'admin/usuarios/editar/' + row[0] + ' " tooltip="tooltip" title="Editar"><i class="fa-solid fa-pen m-1"></i></a> ';

                    html += '<a href=" ' + url + 'admin/usuarios/deletar/' + row[0] + ' "><i class="fa-solid fa-trash m-1" tooltip="tooltip" title="Deletar"></i></a>';

                    return html;
                }
            }
        ],
        columnDefs: [
            {
                className: 'dt-body-left',
                targets: [1, 2]
            },
            {
                className: 'dt-center',
                targets: [3, 4, 5]
            },
            {
                orderable: false,
                targets: [-1]
            }

        ]
    });

});

$('#botaoExportarPDF').on('click', function() {
    // Capturar o termo de busca do DataTables
    var searchTerm = $('#tabelaCategorias_filter input').val();
    alert(searchTerm);
    var urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('searchTerm')) {
            // Se searchTerm não estiver presente na URL, adicionar como parâmetro e abrir em uma nova aba
            window.open('gerar-pdf/' + (searchTerm==''?'todos':searchTerm), '_blank');
        } else {
            // Se searchTerm já estiver presente na URL, abrir em uma nova aba sem adicionar novamente o parâmetro
            window.open(url + 'admin/banner/gerar-pdf/todos', '_blank');
        }
   
});

$('#botaoExportarExcel').on('click', function() {
    // Capturar o termo de busca do DataTables
    var searchTerm = $('#tabelaCategorias_filter input').val();
    var urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('searchTerm')) {
            // Se searchTerm não estiver presente na URL, adicionar como parâmetro e abrir em uma nova aba
            window.open('gerar-excel/' + (searchTerm==''?'todos':searchTerm));
        } else {
            // Se searchTerm já estiver presente na URL, abrir em uma nova aba sem adicionar novamente o parâmetro
            window.open(url + 'admin/banner/gerar-excel/todos', '_blank');
        }
   
});