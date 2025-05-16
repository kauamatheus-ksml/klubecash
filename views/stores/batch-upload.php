<?php
// views/stores/batch-upload.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../controllers/TransactionController.php';

// Iniciar sessão e verificar autenticação
session_start();

// Verificar se o usuário está logado
if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
    exit;
}

// Verificar se o usuário é do tipo loja
if (!AuthController::isStore()) {
    header('Location: ' . CLIENT_DASHBOARD_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
    exit;
}

// Obter ID do usuário logado
$userId = AuthController::getCurrentUserId();

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT * FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

// Verificar se o usuário tem uma loja associada
if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

// Obter os dados da loja
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];

// Inicializar variáveis para feedback
$success = false;
$error = '';
$result = null;

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // Processar o upload de arquivo
    $file = $_FILES['csv_file'];
    
    // Verificar se houve erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'O arquivo enviado é muito grande. Tamanho máximo permitido: 2MB.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'O upload do arquivo foi interrompido. Por favor, tente novamente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = 'Nenhum arquivo foi enviado. Por favor, selecione um arquivo CSV.';
                break;
            default:
                $error = 'Ocorreu um erro no upload do arquivo. Código: ' . $file['error'];
        }
    } else {
        // Verificar o tipo de arquivo (apenas CSV é permitido)
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($fileType) !== 'csv') {
            $error = 'Apenas arquivos CSV são permitidos.';
        } else {
            // Chamar o controlador para processar o lote
            $result = TransactionController::processBatchTransactions($file, $storeId);
            
            if ($result['status']) {
                $success = true;
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Definir menu ativo
$activeMenu = 'batch-upload';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload em Lote - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/views/stores/batch-upload.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir sidebar/menu lateral -->
        <?php include_once '../components/sidebar-store.php'; ?>
        
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Upload em Lote</h1>
                    <p class="welcome-user">Importe várias transações de uma só vez através de um arquivo CSV</p>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <h4>Processamento concluído com sucesso!</h4>
                    <p>Total processado: <?php echo $result['data']['total_processado']; ?><br>
                    Transações registradas: <?php echo $result['data']['sucesso']; ?></p>
                </div>
                <button onclick="window.location.reload()" class="btn btn-success">Novo Upload</button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert error">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <h4>Erro no processamento</h4>
                    <p><?php echo $error; ?></p>
                    
                    <?php if (isset($result['data']['detalhes_erros']) && count($result['data']['detalhes_erros']) > 0): ?>
                    <div class="error-details">
                        <h5>Detalhes dos erros:</h5>
                        <ul>
                            <?php foreach($result['data']['detalhes_erros'] as $errorDetail): ?>
                            <li><?php echo $errorDetail; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="upload-wrapper">
                    <div class="upload-steps">
                        <div class="step-item active">
                            <div class="step-number">1</div>
                            <div class="step-text">
                                <h3>Baixe o modelo</h3>
                                <p>Use nosso template para preencher os dados</p>
                            </div>
                        </div>
                        <div class="step-separator"></div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-text">
                                <h3>Prepare o arquivo</h3>
                                <p>Preencha com suas transações</p>
                            </div>
                        </div>
                        <div class="step-separator"></div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-text">
                                <h3>Faça o upload</h3>
                                <p>Envie o arquivo para processamento</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <h2>Modelo de Arquivo CSV</h2>
                        <p>Baixe nosso modelo e preencha com os dados das suas transações. O arquivo deve seguir exatamente este formato:</p>
                        
                        <div class="template-preview">
                            <table class="csv-template">
                                <thead>
                                    <tr>
                                        <th>email</th>
                                        <th>valor</th>
                                        <th>codigo_transacao</th>
                                        <th>data</th>
                                        <th>descricao</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>cliente@exemplo.com</td>
                                        <td>100.00</td>
                                        <td>VENDA12345</td>
                                        <td>2025-05-15 14:30:00</td>
                                        <td>Compra na loja física</td>
                                    </tr>
                                    <tr>
                                        <td>outro@exemplo.com</td>
                                        <td>250.50</td>
                                        <td>VENDA12346</td>
                                        <td>2025-05-15 15:45:00</td>
                                        <td>Compra online</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="template-instructions">
                            <div class="instruction">
                                <strong>email</strong>
                                <span>Email do cliente cadastrado no Klube Cash (obrigatório)</span>
                            </div>
                            <div class="instruction">
                                <strong>valor</strong>
                                <span>Valor total da compra em Reais, sem o símbolo R$ (obrigatório)</span>
                            </div>
                            <div class="instruction">
                                <strong>codigo_transacao</strong>
                                <span>Identificador único da venda no seu sistema (obrigatório)</span>
                            </div>
                            <div class="instruction">
                                <strong>data</strong>
                                <span>Data e hora da transação no formato AAAA-MM-DD HH:MM:SS (opcional)</span>
                            </div>
                            <div class="instruction">
                                <strong>descricao</strong>
                                <span>Descrição adicional da venda (opcional)</span>
                            </div>
                        </div>
                        
                        <div class="template-download">
                            <a href="../../assets/templates/modelo_transacoes.csv" download class="btn btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Baixar Modelo CSV
                            </a>
                        </div>
                    </div>
                    
                    <div class="upload-section">
                        <h2>Upload de Arquivo</h2>
                        <p>Selecione o arquivo CSV com suas transações para processamento:</p>
                        
                        <form id="uploadForm" method="POST" enctype="multipart/form-data" action="">
                            <div class="file-upload-container">
                                <div class="file-upload-area" id="dropArea">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p class="upload-text">Arraste e solte seu arquivo CSV aqui<br>ou clique para selecionar</p>
                                    <p class="file-selected" style="display: none;"></p>
                                    <input type="file" id="csv_file" name="csv_file" accept=".csv" class="file-input" />
                                </div>
                            </div>
                            
                            <div class="upload-notes">
                                <ul>
                                    <li>Tamanho máximo do arquivo: 2MB</li>
                                    <li>Apenas arquivos CSV são aceitos</li>
                                    <li>O cliente deve estar cadastrado no Klube Cash</li>
                                    <li>Valor mínimo por transação: R$ <?php echo number_format(MIN_TRANSACTION_VALUE, 2, ',', '.'); ?></li>
                                    <li>Cada código de transação deve ser único</li>
                                </ul>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" id="uploadButton" class="btn btn-primary" disabled>
                                    Processar Arquivo
                                </button>
                                <a href="<?php echo STORE_DASHBOARD_URL; ?>" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="help-section">
                <h3>Dúvidas Frequentes</h3>
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>Posso importar transações de qualquer período?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Sim, você pode importar transações de qualquer data, desde que especifique corretamente a data e hora no formato adequado (AAAA-MM-DD HH:MM:SS). Se a data não for especificada, será considerada a data atual.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>O que acontece se o cliente não estiver cadastrado?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Se o email do cliente não corresponder a nenhum usuário cadastrado no Klube Cash, a transação não será processada. Apenas clientes já cadastrados podem receber cashback. Você receberá um relatório detalhado com os erros encontrados.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>Como sei se o processamento foi bem-sucedido?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Após o upload, você receberá um relatório detalhado informando quantas transações foram processadas com sucesso e quais apresentaram problemas. Você também pode verificar as transações registradas na página "Comissões Pendentes".</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>Posso cancelar transações após o processamento?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Não é possível cancelar transações diretamente após o processamento. Em caso de erro, entre em contato com o suporte administrativo do Klube Cash para solicitar o cancelamento de transações específicas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('csv_file');
            const dropArea = document.getElementById('dropArea');
            const uploadButton = document.getElementById('uploadButton');
            const fileSelected = document.querySelector('.file-selected');
            const uploadText = document.querySelector('.upload-text');
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            // Função para destacar a área de drop quando o arquivo é arrastado sobre ela
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropArea.classList.add('highlight');
                });
            });
            
            // Função para remover o destaque quando o arquivo sai da área
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropArea.classList.remove('highlight');
                });
            });
            
            // Função para processar o arquivo quando ele é solto na área
            dropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                const dt = e.dataTransfer;
                fileInput.files = dt.files;
                updateFileInfo();
            });
            
            // Abrir o seletor de arquivo quando clicar na área
            dropArea.addEventListener('click', () => {
                fileInput.click();
            });
            
            // Atualizar informações do arquivo quando selecionado
            fileInput.addEventListener('change', updateFileInfo);
            
            function updateFileInfo() {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    
                    // Verificar se é um arquivo CSV
                    if (file.name.toLowerCase().endsWith('.csv')) {
                        // Verificar tamanho (máximo 2MB)
                        if (file.size <= 2 * 1024 * 1024) {
                            fileSelected.textContent = `Arquivo selecionado: ${file.name} (${formatFileSize(file.size)})`;
                            fileSelected.style.display = 'block';
                            uploadText.style.display = 'none';
                            uploadButton.disabled = false;
                            dropArea.classList.add('file-ready');
                        } else {
                            alert('O arquivo é muito grande. Tamanho máximo permitido: 2MB.');
                            fileInput.value = '';
                            resetFileInput();
                        }
                    } else {
                        alert('Por favor, selecione um arquivo CSV válido.');
                        fileInput.value = '';
                        resetFileInput();
                    }
                } else {
                    resetFileInput();
                }
            }
            
            function resetFileInput() {
                fileSelected.style.display = 'none';
                uploadText.style.display = 'block';
                uploadButton.disabled = true;
                dropArea.classList.remove('file-ready');
            }
            
            function formatFileSize(bytes) {
                if (bytes < 1024) {
                    return bytes + ' bytes';
                } else if (bytes < 1024 * 1024) {
                    return (bytes / 1024).toFixed(2) + ' KB';
                } else {
                    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
                }
            }
            
            // Accordion para a seção de ajuda
            accordionItems.forEach(item => {
                const header = item.querySelector('.accordion-header');
                const content = item.querySelector('.accordion-content');
                const icon = item.querySelector('.accordion-icon');
                
                header.addEventListener('click', () => {
                    // Toggle active class
                    item.classList.toggle('active');
                    
                    // Update icon
                    if (item.classList.contains('active')) {
                        icon.textContent = '-';
                        content.style.maxHeight = content.scrollHeight + 'px';
                    } else {
                        icon.textContent = '+';
                        content.style.maxHeight = '0';
                    }
                });
            });
            
            // Atualizar passos do processo
            const steps = document.querySelectorAll('.step-item');
            
            steps.forEach((step, index) => {
                step.addEventListener('click', () => {
                    updateSteps(index);
                });
            });
            
            function updateSteps(activeIndex) {
                steps.forEach((step, index) => {
                    if (index <= activeIndex) {
                        step.classList.add('active');
                    } else {
                        step.classList.remove('active');
                    }
                });
            }
            
            // Validação do formulário
            document.getElementById('uploadForm').addEventListener('submit', function(e) {
                if (fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Por favor, selecione um arquivo CSV para upload.');
                }
            });
        });
    </script>
</body>
</html>