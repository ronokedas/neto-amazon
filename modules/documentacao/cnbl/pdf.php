<?php
/**
 * MÓDULO: Documentação > Certificados CNBL
 * Geração de PDF — Certificado Nacional de Borda Livre para Navegação Interior
 * Layout conforme NORMAM-202/DPC (Anexo 6-A)
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Verificar acesso
$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    $stmt = $pdo->prepare("SELECT id FROM certificados_cnbl WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) die("Certificado não encontrado.");
    $id = $row['id'];
} elseif (!empty($id)) {
    require_once __DIR__ . '/../../../includes/auth.php';
    verificar_sessao();
    verificar_cargo('ADMIN');
} else {
    die("ID ou token não informado.");
}

// Buscar dados
$stmt = $pdo->prepare("SELECT * FROM certificados_cnbl WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) die("Certificado não encontrado.");

// Convalidações
$stmt_conv = $pdo->prepare("SELECT * FROM cert_convalidacoes WHERE certificado_id = :cert_id AND tipo_certificado = 'CNBL' ORDER BY id");
$stmt_conv->execute([':cert_id' => $id]);
$convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);

// Autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

function dataPorExtenso($data) {
    if (empty($data)) return '___/___/______';
    $meses = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
              7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}

function formatarDataBR($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

function imgOK($p) { 
    return file_exists($p) && filesize($p) > 100; 
}

function converterImagemParaJpeg($dados) {
    if (!function_exists('imagecreatefromstring')) return $dados;
    
    $img = imagecreatefromstring($dados);
    if (!$img) return $dados;
    
    ob_start();
    imagejpeg($img, null, 90);
    $jpeg = ob_get_clean();
    imagedestroy($img);
    
    return $jpeg ?: $dados;
}

// ============================================
// CLASSE PDF PERSONALIZADA
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
$pdf->SetMargins(18, 18, 18);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ============================================
// PÁGINA 1
// ============================================
$pdf->AddPage();

// --- Brasão da República (canto superior esquerdo) ---
$brasao_path = __DIR__ . '/../../../assets/img/brasao.png';
if (imgOK($brasao_path)) {
    $pdf->Image($brasao_path, 23, 21, 30, 30, 'PNG', '', '', true, 150);
}

// --- Número do certificado (topo direito) ---
$numero_limpo = str_replace('AM-CNBL-', '', $c['numero']);
$pdf->SetY(14);
$pdf->SetX(17);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(90, 6, 'CERTIFICADO AM-CNBL - ' . h($numero_limpo), 1, 0, 'C', true);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(180, 80, 0);
$pdf->Cell(0, 6, '(CONDICIONAL)', 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);

// --- Título ---
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'CERTIFICADO NACIONAL DE BORDA LIVRE', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'PARA NAVEGAÇÃO INTERIOR', 0, 1, 'C');
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

// --- Tabela 4 colunas ---
$col_w = [58, 35, 45, 42];
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($col_w[0], 6, 'Nome da Embarcação', 1, 0, 'C', true);
$pdf->Cell($col_w[1], 6, 'N° de Inscrição', 1, 0, 'C', true);
$pdf->Cell($col_w[2], 6, 'Porto de Inscrição', 1, 0, 'C', true);
$pdf->Cell($col_w[3], 6, 'Arqueação Bruta', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell($col_w[0], 8, h($c['nome_embarcacao']), 1, 0, 'C');
$pdf->Cell($col_w[1], 8, h($c['numero_inscricao'] ?: 'Não Fornecido'), 1, 0, 'C');
$pdf->Cell($col_w[2], 8, h($c['local_emissao']), 1, 0, 'C');
$pdf->Cell($col_w[3], 8, h($c['arqueacao_bruta'] ?: 'Não Fornecido'), 1, 1, 'C');
$pdf->Ln(3);

// --- Atividade ou Serviço (tabela com borda) ---
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 6, 'Atividade ou Serviço', 1, 1, 'C', true);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 7, h($c['atividades_servicos']), 1, 1, 'C');
$pdf->Ln(2);

// --- Tipo de Embarcação (tabela com borda, checkbox A-E) ---
$tipo_emb = strtoupper(trim($c['tipo_embarcacao'] ?? ''));
$mapa_tipos = ['A'=>'CARGA GERAL','B'=>'TANQUE','C'=>'GRANELEIRO','D'=>'PASSAGEIROS','E'=>'EMPURRADOR/EMPURRADO'];
$letra_selecionada = '';
foreach ($mapa_tipos as $letra => $desc) {
    if (stripos($tipo_emb, $desc) !== false) {
        $letra_selecionada = $letra;
        break;
    }
}
if (empty($letra_selecionada) && !empty($tipo_emb)) {
    $primeira = substr($tipo_emb, 0, 1);
    if (isset($mapa_tipos[$primeira])) $letra_selecionada = $primeira;
}

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$col_tipo = [30, 30, 30, 30, 30, 30];
$pdf->Cell($col_tipo[0], 6, 'Tipo de Embarcação', 1, 0, 'C', true);
$pdf->Cell($col_tipo[1], 6, 'A', 1, 0, 'C', true);
$pdf->Cell($col_tipo[2], 6, 'B', 1, 0, 'C', true);
$pdf->Cell($col_tipo[3], 6, 'C', 1, 0, 'C', true);
$pdf->Cell($col_tipo[4], 6, 'D', 1, 0, 'C', true);
$pdf->Cell($col_tipo[5], 6, 'E', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($col_tipo[0], 7, '', 1, 0, 'C');
$letras = ['A', 'B', 'C', 'D', 'E'];
foreach ($letras as $i => $letra) {
    $marcado = ($letra === $letra_selecionada) ? 'X' : '';
    $pdf->Cell($col_tipo[$i + 1], 7, $marcado, 1, 0, 'C');
}
$pdf->Ln(7);

$pdf->Ln(2);

// --- Área de Navegação Interior (tabela com borda, checkbox) ---
$area_nav_selected = array_map('trim', explode(',', $c['area_navegacao'] ?? ''));

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$col_area = [60, 60, 60];
$pdf->Cell($col_area[0], 6, 'Área de Navegação Interior', 1, 0, 'C', true);
$pdf->Cell($col_area[1], 6, 'Área 1', 1, 0, 'C', true);
$pdf->Cell($col_area[2], 6, 'Área 2', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($col_area[0], 7, '', 1, 0, 'C');
$pdf->Cell($col_area[1], 7, in_array('Área 1', $area_nav_selected) ? 'X' : '', 1, 0, 'C');
$pdf->Cell($col_area[2], 7, in_array('Área 2', $area_nav_selected) ? 'X' : '', 1, 1, 'C');
$pdf->Ln(8);

// --- DISTÂNCIA DA PARTE SUPERIOR DA LINHA DO CONVÉS ATÉ: ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 6, 'DISTÂNCIA DA PARTE SUPERIOR DA LINHA DO CONVÉS ATÉ:', 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetX(25);
$pdf->Cell(0, 5, 'CENTRO DO DISCO: ' . h($c['centro_disco_situado'] ?: ' mm'), 0, 1, 'L');
$pdf->SetX(25);
$pdf->Cell(0, 5, 'MARCA DA LINHA DE CARGA PARA A ÁREA 1: ' . h($c['marca_linha_carga_area1'] ?: ' mm'), 0, 1, 'L');
$pdf->SetX(25);
$pdf->Cell(0, 5, 'MARCA DA LINHA DE CARGA PARA A ÁREA 2: ' . h($c['marca_linha_carga_area2'] ?: ' mm'), 0, 1, 'L');

$pdf->Ln(5);

// ============================================
// DIAGRAMA DO DISCO DE PLIMSOLL (imagem estática)
// ============================================
$diag_y = $pdf->GetY();
$disco_img_path = __DIR__ . '/../../../img/imagem-pdf.png';
if (file_exists($disco_img_path) && filesize($disco_img_path) > 100) {
    // Proporção original: 574x173 pixels (~3.32:1)
    $img_largura_mm = 90;
    $img_altura_mm = round(90 * 173 / 574); // ~27mm mantendo proporção
    $pdf->Image($disco_img_path, 55, $diag_y, $img_largura_mm, $img_altura_mm, 'PNG', '', '', true, 150);
    $pdf->SetY($diag_y + $img_altura_mm + 5);
} else {
    // Fallback: texto indicando que a imagem não foi encontrada
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, '[Diagrama do Disco de Plimsoll não disponível]', 0, 1, 'C');
    $pdf->SetY($diag_y + 10);
}

// --- Parágrafo narrativo (CAIXA ALTA) ---
$pdf->SetFont('helvetica', 'B', 8);
$aresta_val = h($c['aresta_superior_linha_conves'] ?: '0 mm');
$centro_val = h($c['centro_disco_situado'] ?: ' mm');
$acrescimo_val = h($c['acrescimo_agua_salgada'] ?: '0 mm');

$texto_narrativo = 'A ARESTA SUP. DA LINHA DO CONVÉS ESTÁ SITUADA A ' . $aresta_val . ' DA FACE SUPERIOR DO CONVÉS AO LADO. ';
$texto_narrativo .= 'O CENTRO DO DISCO ESTÁ SITUADO A ' . $centro_val . ' DO BICO DE PROA. ';
$texto_narrativo .= 'ACRÉSCIMO PARA NAVEGAÇÃO EM ÁGUA SALGADA ' . $acrescimo_val . ' ABAIXO DO DISCO DE PLIMSOLL.';

$pdf->MultiCell(0, 5, $texto_narrativo, 0, 'J');
$pdf->Ln(3);

// --- Parágrafo de certificação ---
$pdf->SetFont('helvetica', 'B', 8);
$texto_cert = 'O PRESENTE CERTIFICADO É EXPEDIDO PARA ATESTAR QUE A EMBARCAÇÃO FOI VISTORIADA E QUE A SUA BORDA LIVRE E LINHA DE CARGA INDICADAS FORAM APOSTAS E SERÃO CONTROLADAS CONFORME AS DISPOSIÇÕES EM VIGOR.';
$pdf->MultiCell(0, 5, $texto_cert, 0, 'J');

$pdf->Ln(5);

// --- VÁLIDO até ---
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'VÁLIDO até: ' . dataPorExtenso($c['data_validade']), 0, 1, 'C');

$pdf->Ln(2);

// --- Expedido em ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'Expedido em ' . h($c['local_emissao']) . ', em ' . dataPorExtenso($c['data_emissao']), 0, 1, 'C');

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
    
    // Remover header data URI se existir
    if (preg_match('/^data:image\/(\w+);base64,/', $img_data, $type)) {
        $img_data = substr($img_data, strpos($img_data, ',') + 1);
    }
    
    $decoded = base64_decode($img_data);
    
    if ($decoded !== false && strlen($decoded) > 100) {
        // Salvar como PNG temporário (melhor qualidade e transparência)
        $tmp_file = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
        file_put_contents($tmp_file, $decoded);
        
        // Verificar se o arquivo foi criado corretamente
        if (file_exists($tmp_file) && filesize($tmp_file) > 100) {
            // Usar PNG diretamente sem conversão
            $pdf->Image($tmp_file, 75, $sig_y + 7, 55, 16, 'PNG', '', '', true, 150);
        }
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
$pdf->SetY(20);

// --- Título ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'ANEXO 6-A - NORMAM 202/DPC', 0, 1, 'C');
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

// --- Texto certifica-se ---
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'Certifica-se que a embarcação "' . h($c['nome_embarcacao']) . '" foi objeto das vistorias a seguir estabelecidas:', 0, 1, 'L');

$pdf->Ln(3);

// --- Tabela de Convalidações ---
$col_w = [32, 32, 28, 48, 40];

$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($col_w[0], 6, 'CONVALIDAÇÕES', 1, 0, 'C', true);
$pdf->Cell($col_w[1], 6, 'A REALIZAR ENTRE', 1, 0, 'C', true);
$pdf->Cell($col_w[2], 6, 'E', 1, 0, 'C', true);
$pdf->Cell($col_w[3], 6, 'LUGAR E DATA DA REALIZAÇÃO', 1, 0, 'C', true);
$pdf->Cell($col_w[4], 6, 'VISTORIADOR', 1, 1, 'C', true);

$vistorias_nomes = ['1ª VIST. ANUAL', '2ª VIST. ANUAL', '3ª VIST. ANUAL', '4ª VIST. ANUAL'];

$conv_map = [];
foreach ($convalidacoes as $conv) {
    $conv_map[$conv['numero_vistoria']] = $conv;
}

for ($i = 0; $i < 4; $i++) {
    $nome_vistoria = $vistorias_nomes[$i];
    $conv = $conv_map[$nome_vistoria] ?? $convalidacoes[$i] ?? null;
    
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell($col_w[0], 8, $nome_vistoria, 1, 0, 'L');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell($col_w[1], 8, formatarDataBR($conv['data_inicio'] ?? ''), 1, 0, 'C');
    $pdf->Cell($col_w[2], 8, formatarDataBR($conv['data_fim'] ?? ''), 1, 0, 'C');
    $pdf->Cell($col_w[3], 8, h($conv['local_data'] ?? ''), 1, 0, 'L');
    $pdf->Cell($col_w[4], 8, h($conv['vistoriador'] ?? ''), 1, 1, 'L');
}

$pdf->Ln(3);

// --- Texto final ---
$pdf->SetFont('helvetica', 'B', 8);
$pdf->MultiCell(0, 5, 'Este documento é para certificar que a vistoria requerida pela NORMAM-202/DPC foi efetuada e que a embarcação se encontrava de acordo com as prescrições relevantes da Norma.', 0, 'J');

$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, 'Carga Geral sobre o Convés:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'Autorizado a transportar carga no convés conforme regulamentação aplicável.', 0, 1, 'L');

// ============================================
// SAÍDA
// ============================================
$nome_arquivo = 'CNBL_' . str_replace('/', '-', $c['numero']) . '.pdf';
$pdf->Output($nome_arquivo, 'I');
exit;