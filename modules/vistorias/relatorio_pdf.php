<?php
/**
 * MÓDULO: Vistorias
 * Geração de PDF — Relatório de Vistoria
 * Usa TCPDF (libs/tcpdf/tcpdf.php)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

// O PDF é público e acessível via ID (UUID), portanto não é necessário verificar a sessão.
// require_once __DIR__ . '/../../includes/auth.php';
// verificar_sessao();

$id = $_GET['id'] ?? '';

if (empty($id)) {
    die("ID não informado.");
}

// Buscar vistoria
$stmt = $pdo->prepare("
    SELECT v.*, 
           e.nome AS embarcacao_nome, e.porto_inscricao,
           c.nome AS cliente_nome,
           arm.nome AS armador_nome,
           a.local AS local_vistoria, a.data_vistoria AS a_data_vistoria,
           va.numero AS relatorio_anterior_numero,
           u.nome AS assinante_nome, '' AS assinante_registro, 'Engenheiro Naval' AS assinante_titulo
    FROM vistorias v
    JOIN embarcacoes e ON v.embarcacao_id = e.id
    LEFT JOIN clientes c ON v.pessoa_id = c.id
    LEFT JOIN clientes arm ON v.armador_id = arm.id
    LEFT JOIN agendamentos a ON v.agendamento_id = a.id
    LEFT JOIN vistorias va ON v.relatorio_anterior_id = va.id
    LEFT JOIN usuarios u ON v.criado_por = u.id
    WHERE v.id = :id
");
$stmt->execute([':id' => $id]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$v) {
    die("Relatório não encontrado.");
}

// Buscar exigências
$stmtE = $pdo->prepare("
    SELECT ex.*, COALESCE(ex.item_normam, c.item_normam) AS item_normam
    FROM vistoria_exigencias ex
    LEFT JOIN exigencias_catalogo c ON ex.catalogo_id = c.id
    WHERE ex.vistoria_id = :id
    ORDER BY ex.ordem ASC
");
$stmtE->execute([':id' => $id]);
$exigencias = $stmtE->fetchAll(PDO::FETCH_ASSOC);

// Carregar autoloader do Composer (inclui TCPDF automaticamente)
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
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

// Buscar assinante responsável técnico ativo do banco de dados
$assinante_nome = $v['assinante_nome'] ?? 'RESPONSÁVEL TÉCNICO';
$assinante_titulo = 'Engenheiro Naval';
$assinante_registro = '';

try {
    $stmtResp = $pdo->query("SELECT nome_completo, cargo_titulo, registro_profissional FROM responsaveis_assinatura WHERE ativo = 1 ORDER BY id ASC LIMIT 1");
    $respRow = $stmtResp->fetch(PDO::FETCH_ASSOC);
    if ($respRow) {
        $assinante_nome = $respRow['nome_completo'];
        $assinante_titulo = $respRow['cargo_titulo'];
        $assinante_registro = $respRow['registro_profissional'];
    }
} catch (Exception $e) {
    // Mantém fallback do criador
}

// Determinar as datas de cada bloco de vistoria de forma inteligente
$blocos = [
    'seco' => 'Vistoria em Seco',
    'flutuando' => 'Vistoria Flutuando',
    'borda_livre' => 'Vistoria de Borda Livre',
    'arqueacao' => 'Vistoria de Arqueação'
];

$datas_blocos = [];
foreach (array_keys($blocos) as $b_id) {
    $tem_na_atual = false;
    foreach ($exigencias as $ex) {
        $b = $ex['bloco_vistoria'] ?? 'flutuando';
        if ($b === $b_id) {
            $tem_na_atual = true;
            break;
        }
    }
    
    if ($tem_na_atual) {
        $datas_blocos[$b_id] = $v['data_vistoria'];
    } else {
        try {
            $stmtDataB = $pdo->prepare("
                SELECT v.data_vistoria 
                FROM vistorias v
                INNER JOIN vistoria_exigencias ex ON ex.vistoria_id = v.id
                WHERE v.embarcacao_id = :embarcacao_id 
                  AND v.id != :id_atual
                  AND ex.bloco_vistoria = :bloco
                ORDER BY v.data_vistoria DESC LIMIT 1
            ");
            $stmtDataB->execute([
                ':embarcacao_id' => $v['embarcacao_id'],
                ':id_atual' => $id,
                ':bloco' => $b_id
            ]);
            $resDataB = $stmtDataB->fetchColumn();
            if ($resDataB) {
                $datas_blocos[$b_id] = $resDataB;
            }
        } catch (Exception $e) {
            // Ignora erro de banco
        }
    }
    
    if (empty($datas_blocos[$b_id])) {
        $datas_blocos[$b_id] = $v['data_vistoria'];
    }
}

// ============================================
// CRIAR PDF
// ============================================

if (!class_exists('RelatorioVistoriaPDF')) {
    class RelatorioVistoriaPDF extends TCPDF {
        protected $numero;
        public function __construct($numero) {
            parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
            $this->numero = $numero;
        }
        public function Header() {
            if ($this->PageNo() == 1) {
                // Logo no cabeçalho (igual ao original: x=13, y=10, w=34)
                $logo_path = __DIR__ . '/../../assets/img/logo.png';
                if (file_exists($logo_path) && filesize($logo_path) > 100) {
                    $this->Image($logo_path, 13, 10, 34, 0, 'PNG', '', '', true, 150);
                }

                // Linha superior (depois do logo até o fim da margem direita)
                $this->Line(48, 14, 195, 14);

                $this->SetY(17);
                $this->SetFont('helvetica', 'B', 14);
                $this->SetTextColor(0, 0, 0);
                
                // RELATÓRIO DE VISTORIAS (x=50)
                $this->SetX(48);
                $this->Cell(90, 8, 'RELATÓRIO DE VISTORIAS', 0, 0, 'L');
                
                // Número do relatório (x=157)
                $this->SetFont('helvetica', 'B', 11);
                $this->SetX(157);
                $this->Cell(38, 8, $this->numero, 0, 1, 'R');
                
                // Linha inferior
                $this->Line(48, 26, 195, 26);
                
                // Garante que o Y fique posicionado abaixo do Header para não vazar texto
                $this->SetY(35);
            }
        }
        public function Footer() {
            // Vazio para não imprimir rodapé
        }
        
        // Remove a marca d'água invisível nativa do TCPDF
        protected function _puttcpdfbadge() {
            return;
        }
    }
}

$pdf = new RelatorioVistoriaPDF(h($v['numero']));
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor('Amazon Naval Ltda');
$pdf->SetTitle('Relatório de Vistoria - ' . $v['numero']);
$pdf->SetMargins(15, 28, 15);
$pdf->SetAutoPageBreak(true, 18);

$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0);

// Informações básicas (Embarcação, Armador, etc) formatadas em duas colunas (rótulo e valor)
$pdf->Ln(5);

$pdf->SetTextColor(0, 0, 0);

$label_w = 65; // Largura para o rótulo
$value_w = 115; // Largura para o valor

// Embarcação
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Embarcação:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . mb_strtoupper(h($v['embarcacao_nome'])), 0, 1, 'L');
$pdf->Ln(1);

// Armador
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Armador:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . mb_strtoupper(h($v['armador_nome'] ?? $v['cliente_nome'])), 0, 1, 'L');
$pdf->Ln(1);

// Porto de Inscrição
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Porto de Inscrição:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . h($v['porto_inscricao'] ?? 'BELÉM - PA'), 0, 1, 'L');
$pdf->Ln(1);

// Local da Vistoria
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($label_w, 5, 'Local da Vistoria:', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($value_w, 5, ' ' . h($v['local_vistoria'] ?? 'BELÉM - PA'), 0, 1, 'L');

$pdf->Ln(6);

// Tabela de Exigências
$col_w = [15, 100, 35, 30];

// Função para desenhar cabeçalho da tabela
function printTableHeader($pdf, $col_w) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    // Borda superior e inferior
    $pdf->Cell($col_w[0], 6, 'ITEM', 1, 0, 'C', true);
    $pdf->Cell($col_w[1], 6, 'Descrição das Exigências', 1, 0, 'L', true);
    $pdf->Cell($col_w[2], 6, 'Item da NORMAM', 1, 0, 'C', true);
    $pdf->Cell($col_w[3], 6, 'Vencimento', 1, 1, 'C', true);
}

printTableHeader($pdf, $col_w);

foreach ($blocos as $bloco_id => $bloco_nome) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $data_v = formatarDataBR($datas_blocos[$bloco_id]);
    $pdf->Cell(array_sum($col_w), 6, $bloco_nome . ' - ' . $data_v, 1, 1, 'L', true);
    
    $itens_bloco = array_filter($exigencias, function($e) use ($bloco_id) {
        $b = $e['bloco_vistoria'] ?? 'flutuando';
        return $b === $bloco_id;
    });

    if (empty($itens_bloco)) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(array_sum($col_w), 6, 'Sem Exigências', 1, 1, 'C');
    } else {
        foreach ($itens_bloco as $item) {
            $vencimento = empty($item['vencimento']) ? "Sem Prazo\nVer Obs. 2" : formatarDataBR($item['vencimento']);
            $normam = $item['item_normam'] ?? '';
            
            $pdf->SetFont('helvetica', '', 8);
            
            // Calcular altura necessária
            $nb_desc = $pdf->getNumLines($item['descricao'], $col_w[1]);
            $nb_normam = $pdf->getNumLines($normam, $col_w[2]);
            $nb_venc = $pdf->getNumLines($vencimento, $col_w[3]);
            $h = max($nb_desc, $nb_normam, $nb_venc) * 4;
            if ($h < 6) $h = 6;
            
            // Verifica quebra de página
            if ($pdf->GetY() + $h > $pdf->getPageHeight() - $pdf->getBreakMargin() - 10) {
                $pdf->AddPage();
                printTableHeader($pdf, $col_w);
            }
            
            $startY = $pdf->GetY();
            
            // Desenhar linhas com borda completa
            $pdf->MultiCell($col_w[0], $h, $item['ordem'], 1, 'C', false, 0);
            $pdf->MultiCell($col_w[1], $h, $item['descricao'], 1, 'J', false, 0);
            $pdf->MultiCell($col_w[2], $h, $normam, 1, 'C', false, 0);
            $pdf->MultiCell($col_w[3], $h, $vencimento, 1, 'C', false, 1);
        }
    }
}

$pdf->Ln(6);

// Observações
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'OBSERVAÇÕES', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 9);

// Lógica de Observações Comparativas (Baseado no histórico real)
$obs_counter = 1;

if (!empty($v['relatorio_anterior_id'])) {
    // Buscar exigências do relatório anterior para encontrar as que foram cumpridas
    $stmtAnt = $pdo->prepare("SELECT id, ordem, status_item FROM vistoria_exigencias WHERE vistoria_id = :id");
    $stmtAnt->execute([':id' => $v['relatorio_anterior_id']]);
    $exig_ant = $stmtAnt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obter agrupamentos da vistoria ATUAL
    $transcritas = [];
    $reescritas = [];
    $inseridas = [];
    
    foreach ($exigencias as $ex) {
        if ($ex['status_item'] === 'nao_cumprida_transcrita') {
            $transcritas[] = $ex['ordem'];
        } elseif ($ex['status_item'] === 'cumprida_parcial_reescrita') {
            $reescritas[] = $ex['ordem'];
        } elseif ($ex['status_item'] === 'inserida') {
            $inseridas[] = $ex['ordem'];
        }
    }
    
    // Obter cumpridas (exigências da vistoria anterior que agora estão marcadas como cumprida nela mesma)
    // Em muitos sistemas, quando você cumpre, você altera o status na vistoria anterior.
    // Vamos varrer a vistoria anterior e pegar as que estão como "cumprida"
    $cumpridas = [];
    foreach ($exig_ant as $ant) {
        if ($ant['status_item'] === 'cumprida') {
            $cumpridas[] = $ant['ordem'];
        }
    }
    
    // Simplificando a exibição para bater com o layout. Se tiver dados comparativos, os exibimos
    $pdf->Ln(2); // Espaço antes para evitar colar
    $pdf->Cell(0, 5, "{$obs_counter}. Em relação ao relatório de vistorias anterior, evidencia-se:", 0, 1, 'L');
    $obs_counter++;
    $pdf->Ln(1); // Espaço explícito para não vazar texto
    
    // Separamos por bloco para ser mais fiel (opcional), mas vamos listar num fluxo simples e limpo
    if (count($cumpridas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $cumpridas) . " foram CUMPRIDAS.", 0, 'J');
    }
    if (count($transcritas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $transcritas) . " não foram cumpridas e, portanto, foram TRANSCRITAS neste relatório, e receberam novo sequencial.", 0, 'J');
    }
    if (count($reescritas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $reescritas) . " foram cumpridas parcialmente e, portanto, foram REESCRITAS neste relatório, e receberam novo sequencial.", 0, 'J');
    }
    if (count($inseridas) > 0) {
        $pdf->MultiCell(0, 5, "- As exigências n.º " . implode(', ', $inseridas) . " foram INSERIDAS neste relatório.", 0, 'J');
    }
    
    $pdf->Ln(2);
}

// Observações Técnicas (livre)
if (!empty($v['observacoes_tecnicas'])) {
    $pdf->MultiCell(0, 4.5, "{$obs_counter}. " . $v['observacoes_tecnicas'], 0, 'J');
    $obs_counter++;
    $pdf->Ln(2);
}

if (!empty($v['texto_observacoes_geradas'])) {
    $pdf->MultiCell(0, 4.5, "{$obs_counter}. " . $v['texto_observacoes_geradas'], 0, 'J');
    $obs_counter++;
    $pdf->Ln(2);
}

// Observação fixa (Obs. 2)
$obs_2 = "Em função da data de realização da vistoria essas exigências não possuem mais prazo para o cumprimento e, portanto, o armador fica ciente que deverá cumpri-las prontamente para a obtenção dos respectivos Certificados Estatutários ou receber as convalidações pertinentes.";
$pdf->MultiCell(0, 4.5, "Obs. 2: " . $obs_2, 0, 'J');
$pdf->Ln(8);

// Rodapé de emissão e termo de responsabilidade
$pdf->SetFont('helvetica', 'B', 8);
$emissao = $v['data_emissao'] ?? date('Y-m-d');
$pdf->Cell(0, 6, 'RELATÓRIO EMITIDO EM: ' . mb_strtoupper(dataPorExtenso($emissao)), 0, 1, 'L');

if (!empty($v['relatorio_anterior_numero'])) {
    $pdf->Cell(0, 6, 'Este relatório substitui o de número: ' . $v['relatorio_anterior_numero'], 0, 1, 'L');
}

$pdf->Ln(4);

$texto_responsabilidade = "A aprovação das vistorias realizadas para a emissão ou validação de um Certificado serão válidas apenas para o momento em que forem efetuadas. A partir de então, e durante todo o período de validade do Certificado, os proprietários, armadores, comandantes ou mestres segundo as circunstâncias do caso, serão os responsáveis pela manutenção das condições de segurança, de maneira a garantirem que a embarcação e seus equipamentos não constituam um perigo para sua própria segurança, para a de terceiros ou do meio ambiente.";
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 4, $texto_responsabilidade, 0, 'J');

$pdf->Ln(12);

// Assinatura Responsável
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, h($assinante_nome), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 4, h($assinante_titulo), 0, 1, 'C');
if (!empty($assinante_registro)) {
    $pdf->Cell(0, 4, h($assinante_registro), 0, 1, 'C');
}

$pdf->Ln(8);

// QRCode de Validação
$link_validacao = APP_URL . 'vistorias/relatorio_pdf.php?id=' . $v['id'];
$qr_y = $pdf->GetY();

// Verifica quebra de página
if ($qr_y > $pdf->getPageHeight() - $pdf->getBreakMargin() - 20) {
    $pdf->AddPage();
    $qr_y = $pdf->GetY();
}

try {
    $qr = new TCPDF2DBarcode($link_validacao, 'QRCODE,M');
    $qr_png = $qr->getBarcodePngData(3, 3, array(0, 0, 0));
    $f = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
    file_put_contents($f, $qr_png);
    $pdf->Image($f, 15, $qr_y, 18, 18);
    @unlink($f);
    $pdf->SetXY(35, $qr_y + 6);
} catch (Exception $e) {
    $pdf->SetXY(15, $qr_y);
}

$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(0, 4, 'Para validar este documento acesse:', 0, 1, 'L');
$pdf->SetX(35);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 4, $link_validacao, 0, 1, 'L');

$nome_arquivo_amigavel = 'Relatorio-' . str_replace('/', '-', h($v['numero'])) . '.pdf';

if (isset($salvar_pdf_caminho) && !empty($salvar_pdf_caminho)) {
    $pdf->Output($salvar_pdf_caminho, 'F');
} elseif (isset($return_pdf_string) && $return_pdf_string) {
    $pdf_content = $pdf->Output('', 'S');
} else {
    $pdf->Output($nome_arquivo_amigavel, 'I');
}
