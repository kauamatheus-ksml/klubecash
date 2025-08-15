<?php
// classes/ImageGenerator.php

class ImageGenerator {
    
    /**
     * Gera imagem com dados do saldo do cliente
     */
    public static function gerarImagemSaldo($dadosUsuario, $dadosSaldo) {
        try {
            // Dimensões da imagem
            $width = 800;
            $height = 600;
            
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
            // Retângulo do header (azul)
            imagefilledrectangle($image, 0, 0, $width, 120, $azulClaro);
            
            // Logo/Título
            $titulo = "KLUBE CASH";
            imagettftext($image, 28, 0, 50, 50, $branco, self::getFontPath(), $titulo);
            imagettftext($image, 16, 0, 50, 80, $branco, self::getFontPath(), "Seu Saldo de Cashback");
            
            // Data/hora atual
            $dataHora = date('d/m/Y H:i');
            imagettftext($image, 12, 0, $width - 200, 50, $branco, self::getFontPath(), $dataHora);
            
            // === SAUDAÇÃO ===
            $nome = explode(' ', $dadosUsuario['nome'])[0];
            $saudacao = "Olá, {$nome}!";
            imagettftext($image, 20, 0, 50, 180, $azulEscuro, self::getFontPath(), $saudacao);
            
            // === SALDO DISPONÍVEL ===
            $yPos = 240;
            
            // Card do saldo disponível
            imagefilledrectangle($image, 50, $yPos, 750, $yPos + 80, $verde);
            imagettftext($image, 16, 0, 70, $yPos + 30, $branco, self::getFontPath(), "SALDO DISPONÍVEL");
            $saldoDispText = "R$ " . number_format($dadosSaldo['disponivel'], 2, ',', '.');
            imagettftext($image, 24, 0, 70, $yPos + 60, $branco, self::getFontPath(), $saldoDispText);
            
            // === SALDO PENDENTE ===
            if ($dadosSaldo['pendente'] > 0) {
                $yPos += 100;
                
                // Card do saldo pendente
                imagefilledrectangle($image, 50, $yPos, 750, $yPos + 80, $laranja);
                imagettftext($image, 16, 0, 70, $yPos + 30, $branco, self::getFontPath(), "AGUARDANDO LIBERAÇÃO");
                $saldoPendText = "R$ " . number_format($dadosSaldo['pendente'], 2, ',', '.');
                imagettftext($image, 24, 0, 70, $yPos + 60, $branco, self::getFontPath(), $saldoPendText);
            }
            
            // === TOTAL ===
            $yPos += 120;
            imagettftext($image, 14, 0, 50, $yPos, $cinza, self::getFontPath(), "TOTAL ACUMULADO");
            $totalText = "R$ " . number_format($dadosSaldo['total'], 2, ',', '.');
            imagettftext($image, 22, 0, 50, $yPos + 30, $azulEscuro, self::getFontPath(), $totalText);
            
            // === RODAPÉ ===
            $yPos += 80;
            imagettftext($image, 12, 0, 50, $yPos, $cinza, self::getFontPath(), "🌐 klubecash.com");
            imagettftext($image, 12, 0, 50, $yPos + 25, $cinza, self::getFontPath(), "Seu dinheiro de volta!");
            
            // Salvar imagem
            $fileName = self::generateFileName($dadosUsuario['id']);
            $filePath = self::getUploadPath() . '/' . $fileName;
            
            // Criar diretório se não existir
            if (!is_dir(self::getUploadPath())) {
                mkdir(self::getUploadPath(), 0755, true);
            }
            
            imagepng($image, $filePath);
            imagedestroy($image);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_url' => self::getPublicUrl($fileName),
                'file_name' => $fileName
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao gerar imagem: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Caminho da fonte (usar fonte padrão se não tiver TTF)
     */
    private static function getFontPath() {
        $fontPath = __DIR__ . '/../assets/fonts/arial.ttf';
        return file_exists($fontPath) ? $fontPath : '';
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