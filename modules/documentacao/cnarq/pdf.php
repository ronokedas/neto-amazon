<?php
/**
 * MÓDULO: Documentação > Certificados CNARQ
 * Geração de PDF — Certificado Nacional de Arqueação
 * Usa TCPDF (libs/tcpdf/tcpdf.php)
 * Layout conforme NORMAM-202/DPC (Anexo 7-A)
 * 
 * SUPORTA: ?id=UUID (requer login) ou ?token=TOKEN (público via assinatura)
 */

require_once __DIR__ . '/../../../config.php';

// Verificar acesso: admin logado OU token público válido
$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    require_once __DIR__ . '/../../../includes/functions.php';
    $stmt = $pdo->prepare("SELECT id FROM certificados_cnarq WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) {
        die("Certificado não encontrado.");
    }
    $id = $row['id'];
} elseif (!empty($id)) {
    require_once __DIR__ . '/../../../includes/auth.php';
    require_once __DIR__ . '/../../../includes/functions.php';
    verificar_sessao();
    verificar_cargo('ADMIN');
} else {
    die("ID ou token não informado.");
}

// Buscar certificado
$stmt = $pdo->prepare("SELECT * FROM certificados_cnarq WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    die("Certificado não encontrado.");
}

// Buscar convalidações
$stmt_conv = $pdo->prepare("SELECT * FROM cert_convalidacoes WHERE certificado_id = :cert_id AND tipo_certificado = 'CNARQ' ORDER BY id");
$stmt_conv->execute([':cert_id' => $id]);
$convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);

// Carregar autoloader do Composer
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

class CertificadoCNARQ extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new CertificadoCNARQ('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Certificado CNARQ - ' . $c['numero']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ============================================
// PÁGINA 1 — FRENTE
// ============================================
$pdf->AddPage();

// --- Brasão da República ---
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
$pdf->Cell(90, 6, 'CERTIFICADO AM-CNARQ - ' . h($c['numero']), 1, 0, 'C', true);
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
$pdf->Cell(0, 7, 'CERTIFICADO NACIONAL DE ARQUEAÇÃO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, '(EMITIDO DE ACORDO COM A NORMAM-202)', 0, 1, 'C');
$pdf->Ln(3);

// --- Tabela 1: Identificação da Embarcação ---
$w1 = 60; $w2 = 50; $w3 = 40; $w4 = 40;
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w1, 5, 'Nome da Embarcação', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'N° de Inscrição', 1, 0, 'C', true);
$pdf->Cell($w3, 5, 'Porto de Inscrição', 1, 0, 'C', true);
$pdf->Cell($w4, 5, 'Ano Construção', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($w1, 7, '"' . h($c['nome_embarcacao']) . '"', 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['numero_inscricao']), 1, 0, 'C');
$pdf->Cell($w3, 7, h($c['porto_inscricao']), 1, 0, 'C');
$pdf->Cell($w4, 7, h($c['ano_construcao']), 1, 1, 'C');

// --- Tabela 2: Tipo e Material ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w1, 5, 'Tipo de Embarcação', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'Material do Casco', 1, 0, 'C', true);
$pdf->Cell($w3, 5, 'Local de Construção', 1, 0, 'C', true);
$pdf->Cell($w4, 5, 'Indicativo Chamada', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w1, 7, h($c['tipo_embarcacao']), 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['material_casco']), 1, 0, 'C');
$pdf->Cell($w3, 7, h($c['local_construcao']), 1, 0, 'C');
$pdf->Cell($w4, 7, h($c['indicativo_chamada']), 1, 1, 'C');

$pdf->Ln(2);

// --- CARACTERÍSTICAS PRINCIPAIS ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'CARACTERÍSTICAS PRINCIPAIS', 1, 1, 'C', true);
$pdf->SetFillColor(220, 220, 220);

$col_w_dims = [63.33, 63.33, 63.33];
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($col_w_dims[0], 5, 'Comprimento de Regra (m)', 1, 0, 'C', true);
$pdf->Cell($col_w_dims[1], 5, 'Boca (m)', 1, 0, 'C', true);
$pdf->Cell($col_w_dims[2], 5, 'Pontal moldado a meia-nau até o convés superior (m)', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell($col_w_dims[0], 7, $c['comprimento_total'] ? number_format($c['comprimento_total'], 3, ',', '') : '', 1, 0, 'C');
$pdf->Cell($col_w_dims[1], 7, $c['boca_moldada'] ? number_format($c['boca_moldada'], 3, ',', '') : '', 1, 0, 'C');
$pdf->Cell($col_w_dims[2], 7, $c['pontal_moldado'] ? number_format($c['pontal_moldado'], 3, ',', '') : '', 1, 1, 'C');

$pdf->Ln(2);

// --- Texto de certificação ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'A AMAZON NAVAL LTDA certifica:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$texto_cert = 'Certifico que as arqueações desta embarcação "' . h($c['nome_embarcacao']) . 
    '" foram determinadas de acordo com as disposições da Convenção Internacional sobre Medidas de Arqueações de Embarcações (1969) e das Normas da Autoridade Marítima para Embarcações Empregadas na Navegação Interior.';
$pdf->MultiCell(0, 5, $texto_cert, 0, 'J');

$pdf->Ln(2);

// --- NOTA ---
$pdf->SetFont('helvetica', 'I', 7);
$pdf->MultiCell(0, 4, 'NOTA: data na qual a quilha foi batida ou estágio equivalente de construção, ou data na qual o navio sofreu alterações ou modificações de maior vulto.', 0, 'J');

$pdf->Ln(2);

// --- AS ARQUEAÇÕES DA EMBARCAÇÃO SÃO ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'AS ARQUEAÇÕES DA EMBARCAÇÃO SÃO:', 1, 1, 'C', true);

// Linha AB / AL
$col_w_ab = [95, 95];
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($col_w_ab[0], 5, 'ARQUEAÇÃO BRUTA (AB)', 1, 0, 'C', true);
$pdf->Cell($col_w_ab[1], 5, 'ARQUEAÇÃO LÍQUIDA (AL)', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell($col_w_ab[0], 7, $c['arqueacao_bruta'] !== null ? number_format($c['arqueacao_bruta'], 2, ',', '') . ' m³' : '_____________', 1, 0, 'C');
$pdf->Cell($col_w_ab[1], 7, $c['arqueacao_liquida'] !== null ? number_format($c['arqueacao_liquida'], 2, ',', '') . ' m³' : '_____________', 1, 1, 'C');

$pdf->Ln(1);

// --- Calado moldado e Comp ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$col_w_cl = [63.33, 63.33, 63.33];
$pdf->Cell($col_w_cl[0], 5, 'CALADO MOLDADO', 1, 0, 'C', true);
$pdf->Cell($col_w_cl[1], 5, 'COMP.', 1, 0, 'C', true);
$pdf->Cell($col_w_cl[2], 5, 'NÚMERO DE PASSAGEIROS', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell($col_w_cl[0], 5, $c['calado_maximo_m'] ? number_format($c['calado_maximo_m'], 3, ',', '') . ' m' : '', 1, 0, 'C');
$pdf->Cell($col_w_cl[1], 5, '______________', 1, 0, 'C');
$pdf->Cell($col_w_cl[2], 5, '', 1, 1, 'C');

$pdf->Ln(1);

// --- Método de Arqueação ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(0, 5, 'Método de Arqueação', 1, 0, 'C', true);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, h($c['metodo_arqueacao'] ?: 'Convenção Internacional (1969)'), 1, 1, 'C');

$pdf->Ln(2);

// --- Data e Local da Arqueação ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($col_w_ab[0], 5, 'DATA E LOCAL DA ARQUEAÇÃO ORIGINAL', 1, 0, 'C', true);
$pdf->Cell($col_w_ab[1], 5, 'DATA E LOCAL DA ÚLTIMA REARQUEAÇÃO', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell($col_w_ab[0], 7, formatarDataBR($c['data_emissao']) . ' - ' . h($c['local_emissao']), 1, 0, 'C');
$pdf->Cell($col_w_ab[1], 7, h($c['local_emissao']), 1, 1, 'C');

$pdf->Ln(2);

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

if ($sig_y > 230) {
    $pdf->AddPage();
    $sig_y = $pdf->GetY();
}

$pdf->SetDrawColor(8, 145, 178);
$pdf->SetLineWidth(0.5);
$pdf->Rect(35, $sig_y, 140, 40);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.2);
$pdf->Rect(36, $sig_y + 1, 138, 38);

$logo_path = __DIR__ . '/../../../assets/img/logo.png';
if (imgOK($logo_path)) {
    $pdf->Image($logo_path, 42, $sig_y + 7, 22, 22, 'PNG', '', '', true, 150);
}

$pdf->SetDrawColor(100, 100, 100);
$pdf->SetLineWidth(0.3);
$pdf->Line(75, $sig_y + 25, 168, $sig_y + 25);

$pdf->SetXY(75, $sig_y + 27);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(93, 4, h($c['assinante_nome']), 0, 1, 'C');

$pdf->SetX(75);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(93, 4, h($c['assinante_titulo']), 0, 1, 'C');

$pdf->SetX(75);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(8, 145, 178);
$pdf->Cell(93, 4, h($c['assinante_registro']), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

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

// --- QR Code + Link (rodapé) ---
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
$pdf->Cell(0, 8, 'ANEXO 7-A - NORMAM 202/DPC', 0, 1, 'C');
$pdf->Ln(3);

// --- Observações ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'Observações:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, '1. Este Certificado Condicional foi emitido com base no Relatório de Vistorias n.º ' . 
    h($c['relatorio_numero']) . '.', 0, 1, 'L');
$pdf->Cell(0, 5, '2. Vistoria Flutuando para emissão do Certificado de Segurança da Navegação realizada em ' . 
    formatarDataBR($c['data_vistoria']) . ' em ' . h($c['local_vistoria']) . '.', 0, 1, 'L');

$pdf->Ln(3);

// --- ESPAÇOS INCLUÍDOS NA ARQUEAÇÃO ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'ESPAÇOS INCLUÍDOS NA ARQUEAÇÃO', 1, 1, 'C', true);

$col_w_esp = [95, 47.5, 47.5];
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($col_w_esp[0], 5, 'NOME DO ESPAÇO', 1, 0, 'C', true);
$pdf->Cell($col_w_esp[1], 5, 'LOCAL', 1, 0, 'C', true);
$pdf->Cell($col_w_esp[2], 5, 'COMP. (m³)', 1, 1, 'C', true);

// Linhas de espaços incluídos
$pdf->SetFont('helvetica', '', 7);
for ($i = 0; $i < 6; $i++) {
    $pdf->Cell($col_w_esp[0], 5, '', 1, 0, 'L');
    $pdf->Cell($col_w_esp[1], 5, '', 1, 0, 'C');
    $pdf->Cell($col_w_esp[2], 5, '', 1, 1, 'C');
}

$pdf->Ln(2);

// --- ESPAÇOS EXCLUÍDOS ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'ESPAÇOS EXCLUÍDOS', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($col_w_esp[0], 5, 'NOME DO ESPAÇO', 1, 0, 'C', true);
$pdf->Cell($col_w_esp[1], 5, 'LOCAL', 1, 0, 'C', true);
$pdf->Cell($col_w_esp[2], 5, 'COMP. (m³)', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 7);
for ($i = 0; $i < 4; $i++) {
    $pdf->Cell($col_w_esp[0], 5, '', 1, 0, 'L');
    $pdf->Cell($col_w_esp[1], 5, '', 1, 0, 'C');
    $pdf->Cell($col_w_esp[2], 5, '', 1, 1, 'C');
}

$pdf->Ln(2);

$pdf->SetFont('helvetica', 'I', 7);
$pdf->MultiCell(0, 4, 'NOTA: um asterisco (*) deve ser feito naqueles espaços acima discriminados que sejam simultaneamente considerados espaços fechados e excluídos.', 0, 'J');

$pdf->Ln(2);

// --- Passageiros ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'NÚMERO DE PASSAGEIROS', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(190, 5, 'Número total de passageiros em camarotes com até 8 beliches', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, '0', 1, 1, 'C');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(190, 5, 'Número total dos demais passageiros', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, '0', 1, 1, 'C');

$pdf->Ln(3);

// --- Convalidações (se houver) ---
if (!empty($convalidacoes)) {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'CONVALIDAÇÕES', 0, 1, 'L');
    $pdf->Ln(1);

    $col_w = [35, 30, 30, 55, 40];
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell($col_w[0], 5, 'CONVALIDAÇÕES', 1, 0, 'C', true);
    $pdf->Cell($col_w[1], 5, 'A REALIZAR ENTRE', 1, 0, 'C', true);
    $pdf->Cell($col_w[2], 5, 'E', 1, 0, 'C', true);
    $pdf->Cell($col_w[3], 5, 'LUGAR E DATA DA REALIZAÇÃO', 1, 0, 'C', true);
    $pdf->Cell($col_w[4], 5, 'VISTORIADOR', 1, 1, 'C', true);

    $vistorias_nomes = ['1ª VIST. ANUAL', '2ª VIST. ANUAL', '3ª VIST. ANUAL', '4ª VIST. ANUAL'];
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
}

// ============================================
// SAÍDA DO PDF
// ============================================
$nome_arquivo = 'CNARQ_' . str_replace('/', '-', $c['numero']) . '.pdf';
$pdf->Output($nome_arquivo, 'I');
exit;