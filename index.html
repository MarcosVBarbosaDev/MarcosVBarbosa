<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Geração de API</title>

    <!-- Bootstrap e FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" />

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            background: #f2f2f2;
            font-family: sans-serif;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .modal-body {
            padding-top: 0.5rem;
            padding-bottom: 0;
        }

        .modal-body .form-group {
            margin-bottom: 0.5rem;
        }

        .container-main {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .main-title {
            font-size: 1.8rem;
            margin-bottom: 30px;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .option-card {
            flex: 1;
            min-height: 100px;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .option-card:hover {
            background: #f0f0f0;
        }

        .option-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        #resultadoConfig {
            margin-top: 15px;
        }

        /* Estilo mínimo para input file */
        .custom-file-wrapper {
            position: relative;
        }

        .custom-file-wrapper input[type="file"] {
            padding: .375rem .75rem;
            height: calc(1.5em + 1.2rem + 2px);
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container-main">
        <h1 class="main-title">Geração de API</h1>

        <div class="button-container mb-3">
            <div class="option-card" data-toggle="modal" data-target="#configModal" data-type="config">
                <i class="fas fa-database"></i>
                <div>Acesso ao Banco</div>
            </div>
            <div class="option-card" data-toggle="modal" data-target="#configModal" data-type="import">
                <i class="fas fa-upload"></i>
                <div>Importar Estrutura</div>
            </div>
        </div>

        <div id="loadingSpinner" class="spinner-border text-primary" role="status" style="display:none; margin-bottom: 15px;">
            <span class="sr-only">Carregando...</span>
        </div>

        <div id="resultadoConfig" class="text-center"></div>
    </div>

    <!-- Modal -->
    <div class="modal fade" style="top:10" id="configModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="dbConfigForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Acesso ao Banco de Dados</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="form-group ">
                            <label for="dbHost">Servidor</label>
                            <input type="text" class="form-control" id="dbHost" required placeholder="localhost" />
                        </div>
                        <div class="form-group">
                            <label for="dbName">Nome do Banco</label>
                            <input type="text" class="form-control" id="dbName" required placeholder="nome_do_banco" />
                        </div>
                        <div class="form-group">
                            <label for="dbUser">Usuário</label>
                            <input type="text" class="form-control" id="dbUser" required placeholder="root" />
                        </div>
                        <div class="form-group">
                            <label for="dbPass">Senha</label>
                            <input type="password" class="form-control" id="dbPass" placeholder="senha" />
                        </div>
                    </div>

                    <div id="importCampos" style="display: none;">
                        <div class="form-group">
                            <label for="sqlFile">Importar arquivo .sql</label>
                            <div class="custom-file-wrapper">
                                <input type="file" class="form-control" id="sqlFile" accept=".sql" required />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Gerar API</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentType = 'config'; // valor padrão

        function showSpinner() {
            $('#loadingSpinner').show();
        }

        function hideSpinner() {
            $('#loadingSpinner').hide();
        }


        $('#configModal').on('show.bs.modal', function(event) {
            currentType = $(event.relatedTarget).data('type');

            if (currentType === 'config') {
                $('.modal-title').text('Acesso ao Banco de Dados');
                $('#importCampos').hide();
                $('#dbHost, #dbName, #dbUser').attr('required', true);
                $('#sqlFile').removeAttr('required');
            } else {
                $('.modal-title').text('Importar Estrutura SQL');
                $('#importCampos').show();
                $('#dbHost, #dbName, #dbUser').removeAttr('required');
                $('#sqlFile').attr('required', true);
            }
        });

        $('#dbConfigForm').on('submit', function(e) {
            e.preventDefault();

            const isValid = this.checkValidity();
            if (!isValid) {
                this.reportValidity();
                return;
            }

            showSpinner();

            if (currentType === 'config') {
                const config = {
                    DB_HOST: $('#dbHost').val(),
                    DB_NAME: $('#dbName').val(),
                    DB_USER: $('#dbUser').val(),
                    DB_PASS: $('#dbPass').val()
                };

                $.post('createApi.php', config)
                    .done(function(response) {
                        try {
                            const res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (res.status) {
                                window.location.href = 'viewResult.php';
                            } else {
                                showError(res.result || 'Erro desconhecido');
                            }
                        } catch {
                            showError('Resposta inválida do servidor.');
                        } finally {
                            hideSpinner();
                        }
                    })
                    .fail(function(xhr) {
                        let msg = 'Erro inesperado.';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.result) msg = res.result;
                        } catch {}
                        showError(msg);
                        hideSpinner();
                    });

            } else if (currentType === 'import') {
                const fileInput = document.getElementById('sqlFile');
                const file = fileInput.files[0];

                if (!file) {
                    showError('Por favor, selecione um arquivo SQL.');
                    hideSpinner();
                    return;
                }

                const formData = new FormData();

                formData.append('sqlFile', file);
                formData.append('DB_HOST', $('#dbHost').val());
                formData.append('DB_NAME', $('#dbName').val());
                formData.append('DB_USER', $('#dbUser').val());
                formData.append('DB_PASS', $('#dbPass').val());

                $.ajax({
                    url: 'createApi.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success(response) {
                        try {
                            const res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (res.status) {
                                window.location.href = 'viewResult.php';
                            } else {
                                showError(res.result || 'Erro ao importar o arquivo SQL.');
                            }
                        } catch {
                            showError('Resposta inválida do servidor.');
                        } finally {
                            hideSpinner();
                        }
                    },
                    error() {
                        showError('Erro ao enviar o arquivo.');
                        hideSpinner();
                    }
                });
            }

            $('#configModal').modal('hide');
        });
    </script>
</body>

</html>