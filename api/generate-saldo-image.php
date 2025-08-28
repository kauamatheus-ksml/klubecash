
<?php
// api/generate-saldo-image.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validar secret
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autorizado']);
        exit;
    }
    
    $phone = $data['phone'] ?? '';
    
    // Buscar usuário
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE telefone = ? AND tipo = ?");
    $stmt->execute([$phone, USER_TYPE_CLIENT]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Dados padrão para usuário não encontrado
        $user = ['id' => 999, 'nome' => 'Cliente'];
        $saldos = ['disponivel' => 0, 'pendente' => 0, 'total' => 0];
    } else {
        // Buscar saldos reais
        $saldoStmt = $db->prepare("SELECT COALESCE(SUM(saldo_disponivel), 0) as disponivel FROM cashback_saldos WHERE usuario_id = ?");
        $saldoStmt->execute([$user['id']]);
        $disponivel = $saldoStmt->fetch(PDO::FETCH_ASSOC)['disponivel'] ?? 0;
        
        $pendenteStmt = $db->prepare("SELECT COALESCE(SUM(valor_cliente), 0) as pendente FROM transacoes_cashback WHERE usuario_id = ? AND status IN ('pendente', 'pagamento_pendente')");
        $pendenteStmt->execute([$user['id']]);
        $pendente = $pendenteStmt->fetch(PDO::FETCH_ASSOC)['pendente'] ?? 0;
        
        $saldos = [
            'disponivel' => floatval($disponivel),
            'pendente' => floatval($pendente),
            'total' => floatval($disponivel) + floatval($pendente)
        ];
    }
    
    // Gerar imagem simples
    $width = 600;
    $height = 400;
    $image = imagecreatetruecolor($width, $height);
    
    // Cores
    $branco = imagecolorallocate($image, 255, 255, 255);
    $azul = imagecolorallocate($image, 13, 110, 253);
    $verde = imagecolorallocate($image, 40, 167, 69);
    $laranja = imagecolorallocate($image, 255, 193, 7);
    $preto = imagecolorallocate($image, 0, 0, 0);
    
    // Fundo branco
    imagefill($image, 0, 0, $branco);
    
    // Header azul
    imagefilledrectangle($image, 0, 0, $width, 80, $azul);
    imagestring($image, 5, 20, 20, "KLUBE CASH", $branco);
    imagestring($image, 3, 20, 50, "Seu Saldo de Cashback", $branco);
    
    // Nome
    $nome = explode(' ', $user['nome'])[0];
    imagestring($image, 4, 20, 100, "Ola, {$nome}!", $preto);
    
    // Saldo disponível
    imagefilledrectangle($image, 20, 140, $width-20, 200, $verde);
    imagestring($image, 3, 30, 155, "SALDO DISPONIVEL", $branco);
    imagestring($image, 5, 30, 175, "R$ " . number_format($saldos['disponivel'], 2, ',', '.'), $branco);
    
    // Saldo pendente
    if ($saldos['pendente'] > 0) {
        imagefilledrectangle($image, 20, 220, $width-20, 280, $laranja);
        imagestring($image, 3, 30, 235, "AGUARDANDO LIBERACAO", $branco);
        imagestring($image, 5, 30, 255, "R$ " . number_format($saldos['pendente'], 2, ',', '.'), $branco);
    }
    
    // Total
    imagestring($image, 3, 20, 320, "TOTAL: R$ " . number_format($saldos['total'], 2, ',', '.'), $preto);
    
    // Rodapé
    imagestring($image, 2, 20, 360, "klubecash.com - " . date('d/m/Y H:i'), $preto);
    
    // Salvar
    $fileName = "saldo_force_" . time() . ".png";
    $filePath = __DIR__ . "/../uploads/whatsapp_images/{$fileName}";
    
    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0755, true);
    }
    
    imagepng($image, $filePath);
    imagedestroy($image);
    
    echo json_encode([
        'success' => true,
        'image_url' => SITE_URL . "/uploads/whatsapp_images/{$fileName}",
        'user_name' => $nome,
        'saldos' => $saldos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>