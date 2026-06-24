<?php
/**
 * MÓDULO: Documentação > Certificados CNBL
 * Geração de PDF — Certificado Nacional de Borda Livre para Navegação Interior
 * Usa TCPDF (libs/tcpdf/tcpdf.php)
 * Layout conforme NORMAM-202/DPC (Anexo 6-A)
 * 
 * SUPORTA: ?id=UUID (requer login) ou ?token=TOKEN (público via assinatura)
 */

require_once __DIR__ . '/../../../config.php';

// Verificar acesso: admin logado OU token público válido
$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    // Modo público (acesso via link de assinatura)
    require_once __DIR__ . '/../../../includes/functions.php';
    $stmt = $pdo->prepare("SELECT id FROM certificados_cnbl WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) {
        die("Certificado não encontrado.");
    }
    $id = $row['id'];
} elseif (!empty($id)) {
    // Modo admin
    require_once __DIR__ . '/../../../includes/auth.php';
    require_once __DIR__ . '/../../../includes/functions.php';
    verificar_sessao();
    verificar_cargo('ADMIN');
} else {
    die("ID ou token não informado.");
}

// Buscar certificado
$stmt = $pdo->prepare("SELECT * FROM certificados_cnbl WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    die("Certificado não encontrado.");
}

// Buscar convalidações
$stmt_conv = $pdo->prepare("SELECT * FROM cert_convalidacoes WHERE certificado_id = :cert_id AND tipo_certificado = 'CNBL' ORDER BY id");
$stmt_conv->execute([':cert_id' => $id]);
$convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);

// Carregar autoloader do Composer (inclui TCPDF automaticamente)
$autoload_path = __DIR__ . '/../../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die("Autoloader do Composer não encontrado.");
}
require_once $autoload_path;

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

function dataPorExtenso($data) {
    if (empty($data)) return '___/___/______';
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

function formatarDataBR($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

function converterImagemParaJpeg($dados) {
    if (!function_exists('imagecreatefromstring')) {
        return $dados;
    }
    $img = @imagecreatefromstring($dados);
    if ($img === false) {
        $placeholder = imagecreatetruecolor(400, 80);
        $branco = imagecolorallocate($placeholder, 255, 255, 255);
        imagefill($placeholder, 0, 0, $branco);
        ob_start();
        imagejpeg($placeholder, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($placeholder);
        return $jpeg;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $nova = imagecreatetruecolor($w, $h);
    $branco = imagecolorallocate($nova, 255, 255, 255);
    imagefill($nova, 0, 0, $branco);
    imagecopy($nova, $img, 0, 0, 0, 0, $w, $h);
    ob_start();
    imagejpeg($nova, null, 90);
    $jpeg = ob_get_clean();
    imagedestroy($img);
    imagedestroy($nova);
    return $jpeg;
}

function imgOK($p) { 
    return file_exists($p) && filesize($p) > 100; 
}

// ============================================
// CRIAR PDF
// ============================================

class CertificadoCNBL extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new CertificadoCNBL('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Certificado CNBL - ' . $c['numero']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ============================================
// PÁGINA 1 — FRENTE
// ============================================
$pdf->AddPage();

// --- Brasão da República (canto superior esquerdo) ---
$brasao_path = __DIR__ . '/../../../assets/img/brasao.png';
if (imgOK($brasao_path)) {
    $pdf->Image($brasao_path, 23, 18, 28, 28, 'PNG', '', '', true, 150);
}

// --- Número do certificado (topo direito) ---
$pdf->SetY(14);
$pdf->SetX(17);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(90, 6, 'CERTIFICADO AM-CNBL - ' . h($c['numero']), 1, 0, 'C', true);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(180, 80, 0);
$pdf->Cell(0, 6, '(CONDICIONAL)', 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);

// --- Título ---
$pdf->Ln(6);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'REPÚBLICA FEDERATIVA DO BRASIL', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 4, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->Cell(0, 4, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'AMAZON NAVAL LTDA', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'CERTIFICADO NACIONAL DE BORDA LIVRE', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'PARA NAVEGAÇÃO INTERIOR', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, '(EMITIDO DE ACORDO COM A NORMAM-202)', 0, 1, 'C');
$pdf->Ln(2);

// --- Tabela 1: Embarcação (Nome | Inscrição) ---
$w1 = 95; $w2 = 95;
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w1, 5, 'Nome da Embarcação', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'N° de Inscrição', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($w1, 7, '"' . h($c['nome_embarcacao']) . '"', 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['numero_inscricao']), 1, 1, 'C');

// --- Tabela 2: Atividade/Serviço | Tipo ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w1, 5, 'Atividade ou Serviço', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'Tipo de Embarcação', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w1, 7, h($c['atividades_servicos']), 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['tipo_embarcacao']), 1, 1, 'C');

// --- Tabela 3: Arqueação | Porto de Inscrição | Área Navegação ---
$pdf->SetFont('helvetica', 'B', 7);
$w3a = 40; $w3b = 70; $w3c = 80;
$pdf->Cell($w3a, 5, 'Arqueação Bruta', 1, 0, 'C', true);
$pdf->Cell($w3b, 5, 'Porto de Inscrição', 1, 0, 'C', true);
$pdf->Cell($w3c, 5, 'Área de Navegação', 1, 1, 'C', true);

$area_nav_selected = array_map('trim', explode(',', $c['area_navegacao'] ?? ''));
$area_nav_html = '';
$areas_nav = ['Longo Curso', 'Cabotagem', 'Apoio Marítimo', 'Área 1', 'Área 2'];
foreach ($areas_nav as $i => $a) {
    $checked = in_array($a, $area_nav_selected) ? 'X' : ' ';
    $area_nav_html .= $checked . ' ' . $a;
    if ($i < count($areas_nav) - 1) $area_nav_html .= '  /  ';
}

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell($w3a, 7, h($c['arqueacao_bruta']), 1, 0, 'C');
$pdf->Cell($w3b, 7, h($c['local_emissao']), 1, 0, 'C');
$pdf->Cell($w3c, 7, $area_nav_html, 1, 1, 'L');

$pdf->Ln(2);

// --- Dimensões da Embarcação ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$col_w_dims = [47.5, 47.5, 47.5, 47.5];
$pdf->Cell($col_w_dims[0], 5, 'Comprimento Total (m)', 1, 0, 'C', true);
$pdf->Cell($col_w_dims[1], 5, 'Comprimento Casco (m)', 1, 0, 'C', true);
$pdf->Cell($col_w_dims[2], 5, 'Boca Moldada (m)', 1, 0, 'C', true);
$pdf->Cell($col_w_dims[3], 5, 'Pontal Moldado (m)', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($col_w_dims[0], 7, $c['comprimento_total'] ? number_format($c['comprimento_total'], 2, ',', '') . ' m' : '', 1, 0, 'C');
$pdf->Cell($col_w_dims[1], 7, $c['comprimento_casco'] ? number_format($c['comprimento_casco'], 2, ',', '') . ' m' : '', 1, 0, 'C');
$pdf->Cell($col_w_dims[2], 7, $c['boca_moldada'] ? number_format($c['boca_moldada'], 2, ',', '') . ' m' : '', 1, 0, 'C');
$pdf->Cell($col_w_dims[3], 7, $c['pontal_moldado'] ? number_format($c['pontal_moldado'], 2, ',', '') . ' m' : '', 1, 1, 'C');

// --- Material do Casco e Ano ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w1, 5, 'Material do Casco', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'Ano de Construção', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w1, 7, h($c['material_casco']), 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['ano_construcao']), 1, 1, 'C');

// --- Tipo de Navegação ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 5, 'Tipo de Navegação', 1, 1, 'C', true);

$tipos_nav = ['MAR ABERTO', 'INTERIOR', 'APOIO PORTUÁRIO'];
$tipo_nav_selected = array_map('trim', explode(',', $c['tipo_navegacao'] ?? ''));
$tipo_nav_html = '';
foreach ($tipos_nav as $i => $t) {
    $checked = in_array($t, $tipo_nav_selected) ? 'X' : ' ';
    $tipo_nav_html .= $checked . ' ' . $t;
    if ($i < count($tipos_nav) - 1) $tipo_nav_html .= ' / ';
}

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 6, $tipo_nav_html, 1, 1, 'C');

$pdf->Ln(2);

// --- Configuração de Borda Livre ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'CONFIGURAÇÃO DE BORDA LIVRE', 1, 1, 'C', true);
$pdf->SetFillColor(220, 220, 220);

$pdf->SetFont('helvetica', 'B', 7);
$col_w_bl = [50, 70, 70];
$pdf->Cell($col_w_bl[0], 5, 'Borda Livre (mm)', 1, 0, 'C', true);
$pdf->Cell($col_w_bl[1], 5, 'Tipo de Borda Livre', 1, 0, 'C', true);
$pdf->Cell($col_w_bl[2], 5, 'Calado Máximo (m)', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($col_w_bl[0], 7, $c['borda_livre_mm'] ? $c['borda_livre_mm'] . ' mm' : '', 1, 0, 'C');
$pdf->Cell($col_w_bl[1], 7, h($c['borda_livre_tipo']), 1, 0, 'C');
$pdf->Cell($col_w_bl[2], 7, $c['calado_maximo_m'] ? number_format($c['calado_maximo_m'], 2, ',', '') . ' m' : '', 1, 1, 'C');

$pdf->Ln(2);

// --- Texto de certificação ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'A AMAZON NAVAL LTDA certifica:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$texto_cert = 'O presente certificado é expedido para atestar que a embarcação "' . 
    h($c['nome_embarcacao']) . 
    '" foi vistoriada e que a sua borda livre e linha de carga indicadas foram apostas e serão controladas conforme as disposições em vigor.';
$pdf->MultiCell(0, 5, $texto_cert, 0, 'J');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'Este documento é para certificar que a vistoria requerida pela NORMAM-202/DPC foi efetuada e que a embarcação se encontrava de acordo com as prescrições relevantes da Norma.', 0, 1, 'L');

$pdf->Ln(2);

// --- Marcas da Linha de Carga ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 5, 'MARCAS DA LINHA DE CARGA', 1, 1, 'C', true);

$linhas_marcas = [
    ['A Aresta Superior da Linha do Convés está situada a', '0 mm'],
    ['O Centro do Disco está situado a', ''],
    ['Distância da Parte Superior da Linha do Convés até o Bico de Proa', ''],
    ['Distância da Parte Superior da Linha do Convés abaixo do Disco de Plimsoll', ''],
    ['Marca da Linha de Carga para a Área 1', ''],
    ['Marca da Linha de Carga para a Área 2', ''],
    ['Acréscimo para Navegação em Água Salgada', '0 mm'],
];

$pdf->SetFont('helvetica', '', 7);
foreach ($linhas_marcas as $linha) {
    $pdf->Cell(140, 5, $linha[0], 1, 0, 'L');
    $pdf->Cell(50, 5, $linha[1], 1, 1, 'C');
}

$pdf->Ln(3);

// --- Data de emissão ---
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Expedido em ' . h($c['local_emissao']) . ', em ' . dataPorExtenso($c['data_emissao']), 0, 1, 'R');

$pdf->Ln(3);

// --- Validade ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'VÁLIDO até: ' . dataPorExtenso($c['data_validade']), 0, 1, 'C');

$pdf->Ln(4);

// --- Área de assinatura (QUADRO) ---
$sig_y = $pdf->GetY();

// Verificar se cabe na página, senão adiciona nova página
if ($sig_y > 230) {
    $pdf->AddPage();
    $sig_y = $pdf->GetY();
}

// Retângulo da assinatura
$pdf->SetDrawColor(8, 145, 178);
$pdf->SetLineWidth(0.5);
$pdf->Rect(35, $sig_y, 140, 40);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.2);
$pdf->Rect(36, $sig_y + 1, 138, 38);

// Logo Amazon Naval (dentro do quadro)
$logo_path = __DIR__ . '/../../../assets/img/logo.png';
if (imgOK($logo_path)) {
    $pdf->Image($logo_path, 42, $sig_y + 7, 22, 22, 'PNG', '', '', true, 150);
}

// Linha para assinatura
$pdf->SetDrawColor(100, 100, 100);
$pdf->SetLineWidth(0.3);
$pdf->Line(75, $sig_y + 25, 168, $sig_y + 25);

// Nome do assinante (abaixo da linha)
$pdf->SetXY(75, $sig_y + 27);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(93, 4, h($c['assinante_nome']), 0, 1, 'C');

// Título
$pdf->SetX(75);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(93, 4, h($c['assinante_titulo']), 0, 1, 'C');

// Registro
$pdf->SetX(75);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(8, 145, 178);
$pdf->Cell(93, 4, h($c['assinante_registro']), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

// Se assinado, sobrepor imagem da assinatura
if (!empty($c['assinatura_imagem'])) {
    $img_data = $c['assinatura_imagem'];
    if (preg_match('/^data:image\/(\w+);base64,/', $img_data, $type)) {
        $img_data = substr($img_data, strpos($img_data, ',') + 1);
    }
    $decoded = base64_decode($img_data);
    if ($decoded !== false) {
        $decoded = converterImagemParaJpeg($decoded);
        $tmp_file = tempnam(sys_get_temp_dir(), 'sig_') . '.jpg';
        file_put_contents($tmp_file, $decoded);
        $pdf->Image($tmp_file, 75, $sig_y + 7, 55, 16);
        @unlink($tmp_file);
    }
}

$pdf->SetY($sig_y + 44);

// --- QR Code + Link (rodapé da página 1) ---
$link_assinatura = APP_URL . 'assinar/' . $c['token_assinatura'];

$qr_y = $pdf->GetY();
try {
    $qr = new TCPDF2DBarcode($link_assinatura, 'QRCODE,M');
    $qr_png = $qr->getBarcodePngData(3, 3, array(0, 0, 0));
    $qr_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
    file_put_contents($qr_file, $qr_png);
    $pdf->Image($qr_file, 10, $qr_y, 15, 15, 'PNG');
    @unlink($qr_file);
    $pdf->SetXY(27, $qr_y);
} catch (Exception $e) {
    $pdf->SetXY(10, $qr_y);
}
$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(80, 5, 'Link de assinatura: ' . $link_assinatura, 0, 1, 'L');

if ($c['assinado']) {
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetX(27);
    $pdf->SetTextColor(0, 100, 0);
    $pdf->Cell(0, 5, 'Documento assinado por ' . h($c['assinante_nome']) . ' em ' . 
        formatarDataCompleta($c['assinatura_em']), 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
} else {
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->SetX(27);
    $pdf->Cell(0, 5, 'Acesse o link para assinar este documento.', 0, 1, 'L');
}

// ============================================
// PÁGINA 2 — VERSO
// ============================================
$pdf->AddPage();
$pdf->SetY(10);

// --- Título ---
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'ANEXO 6-A - NORMAM 202/DPC', 0, 1, 'C');
$pdf->Ln(3);

// --- Observações gerais ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'Observações:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, '1. Este Certificado Condicional foi emitido com base no Relatório de Vistorias n.º ' . 
    h($c['relatorio_numero']) . '.', 0, 1, 'L');
$pdf->Cell(0, 5, '2. Vistoria Flutuando para emissão do Certificado de Segurança da Navegação realizada em ' . 
    formatarDataBR($c['data_vistoria']) . ' em ' . h($c['local_vistoria']) . '.', 0, 1, 'L');

$pdf->Ln(3);

// --- Texto certifica-se ---
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'Certifica-se que a embarcação "' . h($c['nome_embarcacao']) . '" foi objeto das vistorias a seguir estabelecidas:', 0, 1, 'L');

$pdf->Ln(3);

// --- Tabela de Convalidações (ORDEM CORRETA: 1ª, 2ª, 3ª, 4ª) ---
$col_w = [35, 30, 30, 55, 40];

// Cabeçalho
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($col_w[0], 5, 'CONVALIDAÇÕES', 1, 0, 'C', true);
$pdf->Cell($col_w[1], 5, 'A REALIZAR ENTRE', 1, 0, 'C', true);
$pdf->Cell($col_w[2], 5, 'E', 1, 0, 'C', true);
$pdf->Cell($col_w[3], 5, 'LUGAR E DATA DA REALIZAÇÃO', 1, 0, 'C', true);
$pdf->Cell($col_w[4], 5, 'VISTORIADOR', 1, 1, 'C', true);

// Usar sempre a ordem correta: 1ª, 2ª, 3ª, 4ª
$vistorias_nomes = ['1ª VIST. ANUAL', '2ª VIST. ANUAL', '3ª VIST. ANUAL', '4ª VIST. ANUAL'];

// Mapear convalidações do banco por nome para preservar dados existentes
$conv_map = [];
foreach ($convalidacoes as $conv) {
    $conv_map[$conv['numero_vistoria']] = $conv;
}

for ($i = 0; $i < 4; $i++) {
    $nome_vistoria = $vistorias_nomes[$i];
    $conv = $conv_map[$nome_vistoria] ?? $convalidacoes[$i] ?? null;
    
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell($col_w[0], 7, $nome_vistoria, 1, 0, 'L');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell($col_w[1], 7, formatarDataBR($conv['data_inicio'] ?? ''), 1, 0, 'C');
    $pdf->Cell($col_w[2], 7, formatarDataBR($conv['data_fim'] ?? ''), 1, 0, 'C');
    $pdf->Cell($col_w[3], 7, h($conv['local_data'] ?? ''), 1, 0, 'L');
    $pdf->Cell($col_w[4], 7, h($conv['vistoriador'] ?? ''), 1, 1, 'L');
}

$pdf->Ln(3);

// --- Texto final ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->MultiCell(0, 5, 'Este documento é para certificar que a vistoria requerida pela NORMAM-202/DPC foi efetuada e que a embarcação se encontrava de acordo com as prescrições relevantes da Norma.', 0, 'J');

$pdf->Ln(5);

// --- Carga Geral sobre o Convés ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'Carga Geral sobre o Convés:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'Autorizado a transportar carga no convés conforme regulamentação aplicável.', 0, 1, 'L');

// ============================================
// SAÍDA DO PDF
// ============================================
$nome_arquivo = 'CNBL_' . str_replace('/', '-', $c['numero']) . '.pdf';
$pdf->Output($nome_arquivo, 'I');
exit;