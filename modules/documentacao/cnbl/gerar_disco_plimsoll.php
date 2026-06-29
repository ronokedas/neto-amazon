<?php
/**
 * Gera o diagrama do Disco de Plimsoll e Marcas de Borda Livre
 * Usando GD Library nativa do PHP
 * Retorna uma imagem PNG com fundo transparente em Base64
 * 
 * Layout conforme NORMAM-202/DPC:
 *   Círculo com linha horizontal cruzando o centro
 *   Letras A (esquerda), 2 (centro), M (direita) dentro do círculo
 *   À direita: duas linhas horizontais (1 e 2) formando "L" até AS
 */

function gerarDiscoPlimsoll($largura_mm = 80, $altura_mm = 50, $dpi = 72) {
    // Converter mm para pixels (1 mm = DPI/25.4 pixels)
    $scale = $dpi / 25.4;
    $largura_px = round($largura_mm * $scale);
    $altura_px = round($altura_mm * $scale);
    
    // Criar imagem com fundo transparente
    $img = imagecreatetruecolor($largura_px, $altura_px);
    
    // Configurar alpha blending para transparência
    imagealphablending($img, false);
    imagesavealpha($img, true);
    
    // Cores
    $transparente = imagecolorallocatealpha($img, 0, 0, 0, 127);
    $preto = imagecolorallocate($img, 0, 0, 0);
    $branco = imagecolorallocate($img, 255, 255, 255);
    
    // Preencher fundo com transparente
    imagefill($img, 0, 0, $transparente);
    
    // Reativar alpha blending para desenho
    imagealphablending($img, true);
    
    // Centro do círculo (posicionado mais à esquerda para dar espaço às linhas)
    $cx = round(22 * $scale);
    $cy = round(22 * $scale);
    $raio = round(8 * $scale);
    
    // --- Linha horizontal principal que cruza o centro do círculo ---
    imagesetthickness($img, round(1.2 * $scale));
    $linha_esq = $cx - $raio - round(8 * $scale);
    $linha_dir = $cx + $raio + round(28 * $scale);
    imageline($img, $linha_esq, $cy, $linha_dir, $cy, $preto);
    
    // --- Círculo do disco de Plimsoll ---
    imagesetthickness($img, round(1 * $scale));
    imagearc($img, $cx, $cy, $raio * 2, $raio * 2, 0, 360, $preto);
    
    // --- Letras dentro do círculo ---
    $font_size = round(7 * $scale);
    $font_file = __DIR__ . '/../../../assets/fonts/arial.ttf';
    $font_used = 5; // fallback: built-in font
    
    // Tentar usar TTF, senão usar built-in
    if (file_exists($font_file)) {
        $font_used = $font_file;
    }
    
    // "A" à esquerda
    $texto_a = 'A';
    $a_x = $cx - $raio + round(2 * $scale);
    $a_y = $cy + round(3 * $scale);
    if (is_string($font_used)) {
        $bbox = imagettfbbox($font_size, 0, $font_used, $texto_a);
        $a_x = $cx - $raio + round(2 * $scale);
        $a_y = $cy + round(3 * $scale);
        imagettftext($img, $font_size, 0, $a_x, $a_y, $preto, $font_used, $texto_a);
    } else {
        imagestring($img, $font_used, $cx - $raio + round(2 * $scale), $cy - round(4 * $scale), 'A', $preto);
    }
    
    // "2" no centro
    if (is_string($font_used)) {
        imagettftext($img, $font_size, 0, $cx - round(3 * $scale), $cy + round(3 * $scale), $preto, $font_used, '2');
    } else {
        imagestring($img, $font_used, $cx - round(3 * $scale), $cy - round(4 * $scale), '2', $preto);
    }
    
    // "M" à direita
    if (is_string($font_used)) {
        imagettftext($img, $font_size, 0, $cx + round(3 * $scale), $cy + round(3 * $scale), $preto, $font_used, 'M');
    } else {
        imagestring($img, $font_used, $cx + round(3 * $scale), $cy - round(4 * $scale), 'M', $preto);
    }
    
    // --- Linhas curtas à direita formando o "L" até AS ---
    imagesetthickness($img, round(0.8 * $scale));
    $linha_x = $cx + $raio + round(14 * $scale);
    $linha_comp = round(10 * $scale);
    
    // Linha 1 (superior)
    $linha1_y = $cy - round(6 * $scale);
    imageline($img, $linha_x, $linha1_y, $linha_x + $linha_comp, $linha1_y, $preto);
    
    // Rótulo "1"
    if (is_string($font_used)) {
        imagettftext($img, round(6 * $scale), 0, $linha_x - round(6 * $scale), $linha1_y + round(3 * $scale), $preto, $font_used, '1');
    } else {
        imagestring($img, 4, $linha_x - round(6 * $scale), $linha1_y - round(6 * $scale), '1', $preto);
    }
    
    // Linha 2 (inferior)
    $linha2_y = $cy + round(6 * $scale);
    imageline($img, $linha_x, $linha2_y, $linha_x + $linha_comp, $linha2_y, $preto);
    
    // Rótulo "2"
    if (is_string($font_used)) {
        imagettftext($img, round(6 * $scale), 0, $linha_x - round(6 * $scale), $linha2_y + round(3 * $scale), $preto, $font_used, '2');
    } else {
        imagestring($img, 4, $linha_x - round(6 * $scale), $linha2_y - round(6 * $scale), '2', $preto);
    }
    
    // Descida vertical da linha 2 até AS
    $as_y = $linha2_y + round(12 * $scale);
    imageline($img, $linha_x + $linha_comp, $linha2_y, $linha_x + $linha_comp, $as_y, $preto);
    
    // Rótulo "AS"
    if (is_string($font_used)) {
        imagettftext($img, round(6 * $scale), 0, $linha_x + $linha_comp + round(2 * $scale), $as_y + round(2 * $scale), $preto, $font_used, 'AS');
    } else {
        imagestring($img, 4, $linha_x + $linha_comp + round(2 * $scale), $as_y - round(6 * $scale), 'AS', $preto);
    }
    
    // --- Capturar como PNG em memória e converter para Base64 ---
    ob_start();
    imagepng($img);
    $png_data = ob_get_clean();
    imagedestroy($img);
    
    return base64_encode($png_data);
}

// Se chamado diretamente (não incluído), exibir a imagem
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    header('Content-Type: image/png');
    $base64 = gerarDiscoPlimsoll();
    echo base64_decode($base64);
    exit;
}