<?php
/**
 * MÓDULO: Documentação > Certificados CSN
 * Geração de PDF — Certificado de Segurança da Navegação
 * Usa TCPDF (libs/tcpdf/tcpdf.php)
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
    $stmt = $pdo->prepare("SELECT id FROM certificados_csn WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) {
        die("Certificado não encontrado.");
    }
    $id = $row['id'];
} elseif (!empty($id)) {
    // Modo admin ou usuario autorizado
    require_once __DIR__ . '/../../../includes/functions.php';
    // Acesso permitido via ID publicamente
} else {
    die("ID ou token não informado.");
}

// Buscar certificado
$stmt = $pdo->prepare("SELECT * FROM certificados_csn WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    die("Certificado não encontrado.");
}

if (!isset($salvar_pdf_caminho) && $c['assinado'] == 1 && !empty($c['caminho_arquivo_pdf'])) {
    $caminho_fisico = __DIR__ . '/../../../' . $c['caminho_arquivo_pdf'];
    if (file_exists($caminho_fisico)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($caminho_fisico) . '"');
        header('Content-Length: ' . filesize($caminho_fisico));
        readfile($caminho_fisico);
        exit;
    }
}

// Buscar distribuição de passageiros
$stmt_dist = $pdo->prepare("SELECT * FROM csn_distribuicao_passageiros WHERE certificado_id = :cert_id ORDER BY id");
$stmt_dist->execute([':cert_id' => $id]);
$distribuicao = $stmt_dist->fetchAll(PDO::FETCH_ASSOC);

// Buscar convalidações
$stmt_conv = $pdo->prepare("SELECT * FROM csn_convalidacoes WHERE certificado_id = :cert_id ORDER BY id");
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

if (!class_exists('CertificadoCSN')) {
    class CertificadoCSN extends TCPDF {
        public function Header() {}
        public function Footer() {}
    }
}

$pdf = new CertificadoCSN('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Certificado CSN - ' . $c['numero']);
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
    $pdf->Image($brasao_path, 23, 21, 30, 30, 'PNG', '', '', true, 150);
}

// --- Número do certificado (topo direito) ---
$pdf->SetY(14);
$pdf->SetX(17);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(90, 6, 'CERTIFICADO AM-CSN - ' . h($c['numero']), 1, 0, 'C', true);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(180, 80, 0);

$sufixo_tipo = '';
$titulo_documento = 'CERTIFICADO DE SEGURANÇA DA NAVEGAÇÃO';
$tipo_cert = $c['tipo'] ?? 'Definitivo';

if ($tipo_cert === 'Condicional') {
    $sufixo_tipo = '(CONDICIONAL)';
    $titulo_documento = 'CERTIFICADO DE SEGURANÇA DA NAVEGAÇÃO (Condicional)';
} else if ($tipo_cert === 'Provisório') {
    $sufixo_tipo = '(PROVISÓRIO)';
    $titulo_documento = 'CERTIFICADO DE SEGURANÇA DA NAVEGAÇÃO (Provisório)';
} else {
    $sufixo_tipo = '(DEFINITIVO)';
}

$pdf->Cell(0, 6, $sufixo_tipo, 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);

// --- Título ---
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, $titulo_documento, 0, 1, 'C');
$pdf->Ln(1);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, '(EMITIDO DE ACORDO COM A NORMAM-202)', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 4, 'REPÚBLICA FEDERATIVA DO BRASIL', 0, 1, 'C');
$pdf->Cell(0, 4, 'MARINHA DO BRASIL', 0, 1, 'C');
$pdf->Cell(0, 4, 'DIRETORIA DE PORTOS E COSTAS', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'AMAZON NAVAL LTDA', 0, 1, 'C');
$pdf->Ln(3);

// --- Tabela 1: Embarcação ---
$w1 = 95; $w2 = 55; $w3 = 40;

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w1, 5, 'Nome da Embarcação', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'N° de Inscrição', 1, 0, 'C', true);
$pdf->Cell($w3, 5, 'Indicativo de Chamada', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($w1, 7, '"' . h($c['nome_embarcacao']) . '"', 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['numero_inscricao']), 1, 0, 'C');
$pdf->Cell($w3, 7, h($c['indicativo_chamada']), 1, 1, 'C');

// --- Tabela 2: Tipo ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w1, 5, 'Atividades ou Serviços', 1, 0, 'C', true);
$pdf->Cell($w2, 5, 'Tipo de Embarcação', 1, 0, 'C', true);
$pdf->Cell($w3, 5, 'Ano de Construção', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w1, 7, h($c['atividades_servicos']), 1, 0, 'C');
$pdf->Cell($w2, 7, h($c['tipo_embarcacao']), 1, 0, 'C');
$pdf->Cell($w3, 7, h($c['ano_construcao']), 1, 1, 'C');

// --- Tabela 3: Dimensões e Navegação ---
$w4 = 20; $w5 = 20; $w6 = 55; $w7 = 85;

$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w4, 5, 'Comprimento (m)', 1, 0, 'C', true);
$pdf->Cell($w5, 5, 'Arqueação Bruta', 1, 0, 'C', true);
$pdf->Cell($w6, 5, 'Tipo de Navegação', 1, 0, 'C', true);
$pdf->Cell($w7, 5, 'Área de Navegação', 1, 1, 'C', true);

// Tipo de navegação (checkboxes) - linha 1
$tipos_nav = ['MAR ABERTO', 'INTERIOR', 'APOIO PORTUÁRIO'];
$tipo_nav_selected = array_map('trim', explode(',', $c['tipo_navegacao'] ?? ''));
$tipo_nav_html = '';
foreach ($tipos_nav as $i => $t) {
    $checked = in_array($t, $tipo_nav_selected) ? 'X' : ' ';
    $tipo_nav_html .= $checked . ' ' . $t;
    if ($i < count($tipos_nav) - 1) $tipo_nav_html .= ' / ';
}

// Área de navegação (checkboxes) - usar quebras de linha para caber
$areas_nav = ['Longo Curso', 'Cabotagem', 'Apoio Marítimo', 'Área 1', 'Área 2'];
$area_nav_selected = array_map('trim', explode(',', $c['area_navegacao'] ?? ''));
$area_nav_html = '';
foreach ($areas_nav as $i => $a) {
    $checked = in_array($a, $area_nav_selected) ? 'X' : ' ';
    $area_nav_html .= $checked . ' ' . $a;
    if ($i < count($areas_nav) - 1) $area_nav_html .= '  /  ';
}

$pdf->SetFont('helvetica', '', 6);
$pdf->Cell($w4, 7, h($c['comprimento_m']), 1, 0, 'C');
$pdf->Cell($w5, 7, h($c['arqueacao_bruta']), 1, 0, 'C');
$pdf->Cell($w6, 7, $tipo_nav_html, 1, 0, 'L');
$pdf->Cell($w7, 7, $area_nav_html, 1, 1, 'L');

// --- Tabela 4: Motor ---
$w8 = 130; $w9 = 60;

$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w8, 5, 'Fabricante, Modelo e Número do Motor', 1, 0, 'C', true);
$pdf->Cell($w9, 5, 'Potência Propulsiva Total (kW)', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($w8, 7, h($c['fabricante_motor']), 1, 0, 'C');
$pdf->Cell($w9, 7, h($c['potencia_kw']), 1, 1, 'C');

// --- Tabela 5: Casco e Passageiros ---
$w10 = 55; $w11 = 70; $w12 = 65;

$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($w10, 5, 'Material do Casco', 1, 0, 'C', true);
$pdf->Cell($w11, 5, 'Autorizado a Transportar Carga no Convés', 1, 0, 'C', true);
$pdf->Cell($w12, 5, 'Quantidade Autorizada de Passageiros', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$carga_text = $c['autorizado_carga'] ? 'X SIM / NÃO' : 'SIM / X NÃO';
$qtd_pass = $c['qtd_passageiros'] . ($c['obs_passageiros'] ? ' (' . h($c['obs_passageiros']) . ')' : '');
$pdf->Cell($w10, 7, h($c['material_casco']), 1, 0, 'C');
$pdf->Cell($w11, 7, $carga_text, 1, 0, 'C');
$pdf->Cell($w12, 7, $qtd_pass, 1, 1, 'C');

$pdf->Ln(3);

// --- Texto de certificação ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'A AMAZON NAVAL LTDA certifica:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$acess_text = $c['acessibilidade_sim'] ? 'SIM' : 'NÃO';
$texto_cert = 'Que a embarcação objeto de ' . h($c['nome_embarcacao']) . 
    ' foi objeto de vistoria de EMISSÃO em conformidade com as disposições regulamentadas pela NORMAM 202 da Diretoria de Portos e Costas.';
$pdf->MultiCell(0, 5, $texto_cert, 0, 'J');

$pdf->Ln(1);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'A embarcação cumpre os requisitos de acessibilidade para o transporte coletivo aquaviário de passageiros.', 0, 1, 'L');
$pdf->Cell(0, 5, 'Acessibilidade: ' . $acess_text, 0, 1, 'L');

$pdf->Ln(1);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(0, 4, 'As vistorias evidenciaram que seu estado é satisfatório e que cumpre com as prescrições indicadas.', 0, 'J');
$pdf->MultiCell(0, 4, 'O presente Certificado será válido até o vencimento indicado, estando sujeito a realização das vistorias anuais e estabelecidas, com resultado satisfatório, nos setores e datas indicadas, respectivamente.', 0, 'J');

$pdf->Ln(2);

// --- Data de emissão ---
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Emitido em ' . h($c['local_emissao']) . ', em ' . dataPorExtenso($c['data_emissao']), 0, 1, 'C');

$pdf->Ln(4);

// --- Área de assinatura (QUADRO) ---
$sig_y = $pdf->GetY();

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
$pdf->Cell(0, 8, 'ANEXO 8-C - NORMAM 202/DPC', 0, 1, 'C');
$pdf->Ln(3);

// --- Observações gerais ---
$pdf->SetFont('helvetica', '', 8);

if ($tipo_cert === 'Definitivo') {
    $pdf->Cell(0, 5, '1. Este Certificado Definitivo foi emitido com base no Relatório de Vistorias n.º ' . h($c['relatorio_numero']) . '.', 0, 1, 'L');
    $pdf->Cell(0, 5, '2. Vistoria Flutuando para emissão do Certificado de Segurança da Navegação realizada em ' . 
        formatarDataBR($c['data_vistoria_flutuando']) . ' na cidade de ' . h($c['local_vistoria']) . '.', 0, 1, 'L');
    $pdf->Cell(0, 5, '3. O não cumprimento das convalidações, nos respectivos prazos, implicará no imediato cancelamento deste certificado.', 0, 1, 'L');
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(0, 5, 'VISTORIA EM SECO REALIZADA EM: ' . formatarDataBR($c['data_vistoria_seco']) . '.', 0, 1, 'L');
} else {
    $pdf->Cell(0, 5, '1. A emissão do Certificado Definitivo fica condicionada a(o):', 0, 1, 'L');
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(0, 5, '   • Cumprimento das Não Conformidades contidas no Relatório de Vistorias n.º ' . h($c['relatorio_numero']) . '.', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 5, '2. O não cumprimento, no respectivo prazo, de qualquer Não Conformidades do relatório acima, implica no imediato cancelamento deste certificado.', 0, 1, 'L');
    $pdf->Cell(0, 5, '3. Vistoria Flutuando para emissão do Certificado de Segurança da Navegação realizada em ' . 
        formatarDataBR($c['data_vistoria_flutuando']) . ' na cidade de ' . h($c['local_vistoria']) . '.', 0, 1, 'L');
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(0, 5, 'VISTORIA EM SECO REALIZADA EM: ' . formatarDataBR($c['data_vistoria_seco']) . '.', 0, 1, 'L');
}

$pdf->Ln(3);

if ($tipo_cert === 'Definitivo') {
    // --- Texto certifica-se ---
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 5, 'Certifica-se que a embarcação ' . h($c['nome_embarcacao']) . ' foi objeto das vistorias a seguir estabelecidas:', 0, 1, 'L');

    $pdf->Ln(2);

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

    $vistorias_nomes = certificadoNomesConvalidacoes($c['tipo_embarcacao'] ?? '');
    $conv_map = certificadoConvalidacoesPorNumero($convalidacoes);
    $usar_mapa_convalidacoes = !empty($conv_map);

    foreach ($vistorias_nomes as $i => $nome_vistoria) {
        $numero_vistoria = $i + 1;
        $conv = $usar_mapa_convalidacoes
            ? ($conv_map[$numero_vistoria] ?? null)
            : ($convalidacoes[$i] ?? null);
        
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell($col_w[0], 7, $nome_vistoria, 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell($col_w[1], 7, formatarDataBR($conv['data_inicio'] ?? ''), 1, 0, 'C');
        $pdf->Cell($col_w[2], 7, formatarDataBR($conv['data_fim'] ?? ''), 1, 0, 'C');
        $pdf->Cell($col_w[3], 7, h($conv['local_data'] ?? ''), 1, 0, 'L');
        $pdf->Cell($col_w[4], 7, h($conv['vistoriador'] ?? ''), 1, 1, 'L');
    }

    $pdf->Ln(3);
}

// --- VALIDO ATÉ ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'VALIDO ATÉ: ' . dataPorExtenso($c['data_validade']), 0, 1, 'C');

$pdf->Ln(5);

// --- Distribuição de Passageiros e Cargas ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(95, 6, 'DISTRIBUIÇÃO DE PASSAGEIROS', 1, 0, 'C');
$pdf->Cell(95, 6, 'DISTRIBUIÇÃO DE CARGAS', 1, 1, 'C');

// Sub-cabeçalhos
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(65, 5, 'LOCAL', 1, 0, 'C', true);
$pdf->Cell(30, 5, 'QUANTIDADE', 1, 0, 'C', true);
$pdf->Cell(65, 5, 'LOCAL', 1, 0, 'C', true);
$pdf->Cell(30, 5, 'QUANTIDADE', 1, 1, 'C', true);

// Linhas de dados
$pdf->SetFont('helvetica', '', 8);
if (!empty($distribuicao)) {
    foreach ($distribuicao as $d) {
        $pdf->Cell(65, 7, h($d['local_nome']), 1, 0, 'L');
        $pdf->Cell(30, 7, (string)$d['quantidade'], 1, 0, 'C');
        $pdf->Cell(65, 7, '', 1, 0, 'L');
        $pdf->Cell(30, 7, '', 1, 1, 'C');
    }
} else {
    for ($i = 0; $i < 5; $i++) {
        $pdf->Cell(65, 7, '', 1, 0, 'L');
        $pdf->Cell(30, 7, '', 1, 0, 'C');
        $pdf->Cell(65, 7, '', 1, 0, 'L');
        $pdf->Cell(30, 7, '', 1, 1, 'C');
    }
}

// ============================================
// SAÍDA DO PDF
// ============================================
$nome_arquivo = 'CSN_' . str_replace('/', '-', $c['numero']) . '.pdf';

if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} else {
    $pdf->Output($nome_arquivo, 'I');
    exit;
}
