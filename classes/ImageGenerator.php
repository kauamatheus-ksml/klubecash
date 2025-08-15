<?php
// classes/ImageGenerator.php

class ImageGenerator {
    
    /**
     * Gera imagem com dados do saldo do cliente (VERSÃO SIMPLES SEM TTF)
     */
    public static function gerarImagemSaldo($dadosUsuario, $dadosSaldo) {
        try {
            // Verificar se GD está instalado
            if (!extension_loaded('gd')) {
                throw new Exception('Extensão GD não está instalada');
            }
            
            // Dimensões da imagem
            $width = 600;
            $height = 415;
            
            // Criar imagem
            $image = imagecreatetruecolor($width, $height);
            
            // Cores
            $branco = imagecolorallocate($image, 255, 255, 255);
            $azulEscuro = imagecolorallocate($image, 33, 37, 41);
            $verde = imagecolorallocate($image, 40, 167, 69);
            $laranja = imagecolorallocate($image, 255, 193, 7);
            $cinza = imagecolorallocate($image, 108, 117, 125);
            $azulClaro = imagecolorallocate($image, 13, 110, 253);
            
            // Preencher fundo branco
            imagefill($image, 0, 0, $branco);
            
            // === HEADER ===
            imagefilledrectangle($image, 0, 0, $width, 80, $azulClaro);
            
            // USAR imagestring() ao invés de imagettftext()
            imagestring($image, 5, 20, 20, "KLUBE CASH", $branco);
            imagestring($image, 3, 20, 50, "Seu Saldo de Cashback", $branco);
            
            // Data/hora
            $dataHora = date('d/m/Y H:i');
            imagestring($image, 2, $width - 120, 20, $dataHora, $branco);
            
            // === SAUDAÇÃO ===
            $nome = explode(' ', $dadosUsuario['nome'])[0];
            $saudacao = "Ola, {$nome}!";
            imagestring($image, 4, 20, 100, $saudacao, $azulEscuro);
            
            // === SALDO DISPONÍVEL ===
            $yPos = 140;
            
            // Card do saldo disponível
            imagefilledrectangle($image, 20, $yPos, $width - 20, $yPos + 60, $verde);
            imagestring($image, 3, 30, $yPos + 15, "SALDO DISPONIVEL", $branco);
            $saldoDispText = "R$ " . number_format($dadosSaldo['disponivel'], 2, ',', '.');
            imagestring($image, 5, 30, $yPos + 35, $saldoDispText, $branco);
            
            // === SALDO PENDENTE ===
            if ($dadosSaldo['pendente'] > 0) {
                $yPos += 80;
                
                // Card do saldo pendente
                imagefilledrectangle($image, 20, $yPos, $width - 20, $yPos + 60, $laranja);
                imagestring($image, 3, 30, $yPos + 15, "AGUARDANDO LIBERACAO", $branco);
                $saldoPendText = "R$ " . number_format($dadosSaldo['pendente'], 2, ',', '.');
                imagestring($image, 5, 30, $yPos + 35, $saldoPendText, $branco);
            }
            
            // === TOTAL ===
            $yPos += 100;
            imagestring($image, 3, 20, $yPos, "TOTAL ACUMULADO", $cinza);
            $totalText = "R$ " . number_format($dadosSaldo['total'], 2, ',', '.');
            imagestring($image, 5, 20, $yPos + 20, $totalText, $azulEscuro);
            
            // === RODAPÉ ===
            $yPos += 60;
            imagestring($image, 2, 20, $yPos, "klubecash.com", $cinza);
            imagestring($image, 2, 20, $yPos + 15, "Seu dinheiro de volta!", $cinza);
            
            // Salvar imagem
            $fileName = self::generateFileName($dadosUsuario['id']);
            $filePath = self::getUploadPath() . '/' . $fileName;
            
            // Criar diretório se não existir
            if (!is_dir(self::getUploadPath())) {
                mkdir(self::getUploadPath(), 0755, true);
            }
            
            // Salvar como PNG
            imagepng($image, $filePath);
            imagedestroy($image);
            
            // Log de sucesso
            error_log("ImageGenerator: Imagem gerada com sucesso - {$filePath}");
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_url' => self::getPublicUrl($fileName),
                'file_name' => $fileName
            ];
            
        } catch (Exception $e) {
            error_log('ImageGenerator: Erro - ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Gera nome único para o arquivo
     */
    private static function generateFileName($userId) {
        return "saldo_whatsapp_{$userId}_" . time() . ".png";
    }
    
    /**
     * Diretório para salvar imagens temporárias
     */
    private static function getUploadPath() {
        return __DIR__ . '/../uploads/whatsapp_images';
    }
    
    /**
     * URL pública da imagem
     */
    private static function getPublicUrl($fileName) {
        return SITE_URL . '/uploads/whatsapp_images/' . $fileName;
    }
    
    /**
     * Limpa imagens antigas (mais de 24h)
     */
    public static function limparImagensAntigas() {
        $dir = self::getUploadPath();
        if (!is_dir($dir)) return;
        
        $files = glob($dir . '/saldo_whatsapp_*.png');
        foreach ($files as $file) {
            if (filemtime($file) < time() - 86400) { // 24 horas
                unlink($file);
            }
        }
    }
}
?>