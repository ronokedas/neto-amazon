<?php
/**
 * MÓDULO: Documentação > Licença Provisória (LP)
 * Geração de PDF — Licença Provisória
 * Usa TCPDF (libs/tcpdf/tcpdf.php)
 * Layout: Cabeçalho Amazon Naval, dados da licença em tabela, observações e assinatura
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
    $stmt = $pdo->prepare("SELECT id FROM certificados_lp WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) {
        die("Licença não encontrada.");
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

// Buscar licença
$stmt = $pdo->prepare("SELECT * FROM certificados_lp WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    die("Licença não encontrada.");
}

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

class CertificadoLP extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new CertificadoLP('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Licença Provisória - ' . $c['numero_lp']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ============================================
// PÁGINA 1 — FRENTE
// ============================================
$pdf->AddPage();

// --- Logo / Brasão (canto superior esquerdo) ---
$logo_path = __DIR__ . '/../../../assets/img/logo.png';
if (imgOK($logo_path)) {
    $pdf->Image($logo_path, 15, 12, 35, 20, 'PNG', '', '', true, 150);
}

// --- Número da licença (topo direito) ---
$pdf->SetY(14);
$pdf->SetX(55);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(0, 6, 'LP - ' . h($c['numero_lp']), 1, 1, 'R', true);

// --- Título principal ---
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 6, 'AMAZON NAVAL LTDA', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, 'Serviços Técnicos de Engenharia Naval', 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'LICENÇA PROVISÓRIA', 0, 1, 'C');
$pdf->Ln(2);

// --- Tipo de Licença ---
$tipo_labels = [
    'construção' => 'LICENÇA DE CONSTRUÇÃO',
    'alteração' => 'LICENÇA DE ALTERAÇÃO',
    'reclassificação' => 'LICENÇA DE RECLASSIFICAÇÃO',
    'lcec' => 'LICENÇA DE CONSTRUÇÃO / EXPLORAÇÃO COMERCIAL (LCEC)'
];
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(0, 7, h($tipo_labels[$c['tipo_licenca']] ?? strtoupper($c['tipo_licenca'])), 1, 1, 'C', true);
$pdf->Ln(3);

// --- Dados da Licença em Tabela ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);

$w1 = 55; $w2 = 135;

// Linha 1: Número da LP e Data de Emissão
$pdf->Cell($w1, 5, 'Número da Licença', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'Data de Emissão', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($w1, 7, h($c['numero_lp']), 1, 0, 'C');
$pdf->Cell($w2, 7, formatarDataBR($c['data_emissao']), 1, 1, 'C');

// Linha 2: Validade
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w1, 5, 'Validade (dias)', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'Data de Validade', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w1, 7, $c['validade_dias'] ? $c['validade_dias'] . ' dias' : '', 1, 0, 'C');
$pdf->Cell($w2, 7, formatarDataBR($c['validade_data']), 1, 1, 'C');

$pdf->Ln(3);

// --- Dados da Embarcação ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'DADOS DA EMBARCAÇÃO', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);

$dados_emb = [
    ['Nome da Embarcação', h($c['nome_embarcacao']), 'Tipo', h($c['tipo_embarcacao'])],
];

foreach ($dados_emb as $d) {
    $pdf->Cell(60, 5, $d[0], 1, 0, 'C', true);
    $pdf->Cell(130, 5, $d[2], 1, 1, 'C', true);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(60, 7, $d[1], 1, 0, 'C');
    $pdf->Cell(130, 7, $d[3], 1, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(220, 220, 220);
}

// Dimensões
$w_dim = 190 / 4;
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w_dim, 5, 'Nº Casco', 1, 0, 'C', true);
$pdf->Cell($w_dim, 5, 'Material Casco', 1, 0, 'C', true);
$pdf->Cell($w_dim, 5, 'Comp. Total (m)', 1, 0, 'C', true);
$pdf->Cell($w_dim, 5, 'Boca Mold. (m)', 1, 0, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w_dim, 7, h($c['numero_casco']), 1, 0, 'C');
$pdf->Cell($w_dim, 7, h($c['material_casco']), 1, 0, 'C');
$pdf->Cell($w_dim, 7, $c['comprimento_total'] ? number_format($c['comprimento_total'], 2, ',', '') . ' m' : '', 1, 0, 'C');
$pdf->Cell($w_dim, 7, $c['boca_moldada'] ? number_format($c['boca_moldada'], 2, ',', '') . ' m' : '', 1, 1, 'C');

$pdf->SetFillColor(220, 220, 220);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w_dim, 5, 'Pontal Mold. (m)', 1, 0, 'C', true);
$pdf->Cell($w_dim, 5, '', 1, 0, 'C', true);
$pdf->Cell($w_dim, 5, '', 1, 0, 'C', true);
$pdf->Cell($w_dim, 5, '', 1, 0, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w_dim, 7, $c['pontal_moldado'] ? number_format($c['pontal_moldado'], 2, ',', '') . ' m' : '', 1, 0, 'C');
$pdf->Cell($w_dim, 7, '', 1, 0, 'C');
$pdf->Cell($w_dim, 7, '', 1, 0, 'C');
$pdf->Cell($w_dim, 7, '', 1, 0, 'C');

$pdf->Ln(3);

// --- Proprietário/Armador ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'PROPRIETÁRIO / ARMADOR', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w1, 5, 'Nome', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'CPF / CNPJ', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w1, 7, h($c['proprietario_nome']), 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['proprietario_cpf_cnpj']), 1, 1, 'C');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 5, 'Endereço', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 7, h($c['proprietario_endereco']), 1, 1, 'L');

$pdf->Ln(2);

// --- Estaleiro/Construtor ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(200, 220, 200);
$pdf->Cell(0, 6, 'ESTALEIRO / CONSTRUTOR', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w1, 5, 'Nome', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'CPF / CNPJ', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w1, 7, h($c['estaleiro_nome']), 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['estaleiro_cpf_cnpj']), 1, 1, 'C');

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 5, 'Endereço', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 7, h($c['estaleiro_endereco']), 1, 1, 'L');

$pdf->Ln(3);

// --- Observações / Exigências ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(240, 240, 200);
$pdf->Cell(0, 6, 'OBSERVAÇÕES / EXIGÊNCIAS', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$obs_texto = !empty($c['observacoes_exigencias']) ? h($c['observacoes_exigencias']) : 'Nenhuma observação registrada.';
$pdf->MultiCell(0, 5, $obs_texto, 1, 'L');

$pdf->Ln(3);

// --- Data e Local ---
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Expedido em Belém-PA, ' . dataPorExtenso($c['data_emissao']), 0, 1, 'R');
$pdf->Ln(2);

// --- Área de assinatura ---
$sig_y = $pdf->GetY();

// Verificar se cabe na página
if ($sig_y > 220) {
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

// Logo dentro do quadro
if (imgOK($logo_path)) {
    $pdf->Image($logo_path, 42, $sig_y + 7, 22, 22, 'PNG', '', '', true, 150);
}

// Linha para assinatura
$pdf->SetDrawColor(100, 100, 100);
$pdf->SetLineWidth(0.3);
$pdf->Line(75, $sig_y + 25, 168, $sig_y + 25);

// Nome do assinante
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

// Se assinado, sobrepor imagem
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
// SAÍDA DO PDF
// ============================================
$nome_arquivo = 'LP_' . str_replace('/', '-', $c['numero_lp']) . '.pdf';
$pdf->Output($nome_arquivo, 'I');
exit;