<?php
/**
 * PDF — Licença de Construção / LCEC
 */
require_once __DIR__ . '/../../../config.php';

$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    require_once __DIR__ . '/../../../includes/functions.php';
    $stmt = $pdo->prepare("SELECT id FROM certificados_lc WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) die("Licença não encontrada.");
    $id = $row['id'];
} elseif (!empty($id)) {
    require_once __DIR__ . '/../../../includes/auth.php';
    require_once __DIR__ . '/../../../includes/functions.php';
    verificar_sessao(); verificar_cargo('ADMIN');
} else {
    die("ID ou token não informado.");
}

$stmt = $pdo->prepare("SELECT * FROM certificados_lc WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) die("Licença não encontrada.");

require_once __DIR__ . '/../../../vendor/autoload.php';

function dataPorExtenso($data) {
    if (empty($data)) return '___/___/______';
    $meses = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
    $dt = new DateTime($data);
    return $dt->format('d') . ' de ' . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y');
}
function formatarDataBR($d) { return $d ? date('d/m/Y', strtotime($d)) : ''; }
function converterImagemParaJpeg($dados) {
    if (!function_exists('imagecreatefromstring')) return $dados;
    $img = @imagecreatefromstring($dados);
    if ($img === false) { $p=imagecreatetruecolor(400,80); $b=imagecolorallocate($p,255,255,255); imagefill($p,0,0,$b); ob_start(); imagejpeg($p,null,85); $j=ob_get_clean(); imagedestroy($p); return $j; }
    $w=imagesx($img);$h=imagesy($img);$n=imagecreatetruecolor($w,$h);$b=imagecolorallocate($n,255,255,255);imagefill($n,0,0,$b);imagecopy($n,$img,0,0,0,0,$w,$h);
    ob_start();imagejpeg($n,null,90);$j=ob_get_clean();imagedestroy($img);imagedestroy($n);return $j;
}
function imgOK($p) { return file_exists($p) && filesize($p) > 100; }

class CertificadoLC extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$tipo_labels = ['LC'=>'LICENÇA DE CONSTRUÇÃO','LA'=>'LICENÇA DE ALTERAÇÃO','LR'=>'LICENÇA DE RECLASSIFICAÇÃO','LCEC'=>'LICENÇA DE CONSTRUÇÃO / EXPLORAÇÃO COMERCIAL (LCEC)'];

$pdf = new CertificadoLC('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME); $pdf->SetAuthor('Amazon Naval Ltda'); $pdf->SetTitle('LC - ' . $c['numero_lc']);
$pdf->setPrintHeader(false); $pdf->setPrintFooter(false); $pdf->SetMargins(15, 15, 15); $pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

$logo_path = __DIR__ . '/../../../assets/img/logo.png';
if (imgOK($logo_path)) $pdf->Image($logo_path, 15, 12, 35, 20);

$pdf->SetY(14); $pdf->SetX(55);
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(240,240,240); $pdf->SetDrawColor(200,200,200);
$pdf->Cell(0, 6, h($c['numero_lc']), 1, 1, 'R', true);

$pdf->Ln(2);
$pdf->SetFont('helvetica','B',11); $pdf->Cell(0,6,'AMAZON NAVAL LTDA',0,1,'C');
$pdf->SetFont('helvetica','',8); $pdf->Cell(0,4,'Serviços Técnicos de Engenharia Naval',0,1,'C'); $pdf->Ln(3);
$pdf->SetFont('helvetica','B',14); $pdf->Cell(0,8,'LICENÇA DE CONSTRUÇÃO / LCEC',0,1,'C'); $pdf->Ln(2);

$pdf->SetFont('helvetica','B',10); $pdf->SetFillColor(230,230,230);
$pdf->Cell(0, 7, h($tipo_labels[$c['tipo_licenca']] ?? $c['tipo_licenca']), 1, 1, 'C', true); $pdf->Ln(3);

// Tabela dados
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$w1=50;$w2=140;
$pdf->Cell($w1,5,'Número da Licença',1,0,'C',true); $pdf->Cell($w2,5,'Data de Emissão',1,1,'C',true);
$pdf->SetFont('helvetica','B',8); $pdf->Cell($w1,7,h($c['numero_lc']),1,0,'C'); $pdf->Cell($w2,7,formatarDataBR($c['data_emissao']),1,1,'C');

$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell($w1,5,'Validade',1,0,'C',true); $pdf->Cell($w2,5,'Local de Emissão',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell($w1,7,formatarDataBR($c['data_validade']),1,0,'C'); $pdf->Cell($w2,7,h($c['local_emissao']),1,1,'C');

if ($c['tipo_licenca'] === 'LCEC' && !empty($c['data_termino_construcao'])) {
    $pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
    $pdf->Cell($w1,5,'Término Construção',1,0,'C',true); $pdf->Cell($w2,5,'',1,1,'C',true);
    $pdf->SetFont('helvetica','',8); $pdf->Cell($w1,7,formatarDataBR($c['data_termino_construcao']),1,0,'C'); $pdf->Cell($w2,7,'',1,1,'C');
}

$pdf->Ln(3);

// Dados da Embarcação
$pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(200,220,200);
$pdf->Cell(0,6,'DADOS DA EMBARCAÇÃO',1,1,'C',true);

$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell(60,5,'Nome da Embarcação',1,0,'C',true); $pdf->Cell(70,5,'Tipo',1,0,'C',true); $pdf->Cell(60,5,'Nº Casco',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell(60,7,'"'.h($c['nome_embarcacao']).'"',1,0,'C'); $pdf->Cell(70,7,h($c['tipo_embarcacao']),1,0,'C'); $pdf->Cell(60,7,h($c['numero_casco']),1,1,'C');

$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell(95,5,'Material do Casco',1,0,'C',true); $pdf->Cell(95,5,'Sociedade Classificadora',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell(95,7,h($c['material_casco']),1,0,'C'); $pdf->Cell(95,7,h($c['sociedade_classificadora']),1,1,'C');

// Dimensões
$pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(200,220,200);
$pdf->Cell(0,6,'DIMENSÕES',1,1,'C',true);
$wd=38;
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell($wd,5,'Comp. Total',1,0,'C',true); $pdf->Cell($wd,5,'Comp. PP',1,0,'C',true); $pdf->Cell($wd,5,'Boca Mold.',1,0,'C',true); $pdf->Cell($wd,5,'Pontal Mold.',1,0,'C',true); $pdf->Cell($wd,5,'Calado Máx.',1,1,'C',true);
$pdf->SetFont('helvetica','',8);
foreach(['comprimento_total','comprimento_pp','boca_moldada','pontal_moldado','calado_maximo'] as $f)
    $pdf->Cell($wd,7,$c[$f] ? number_format($c[$f],2,',','').' m' : '',1,0,'C');
$pdf->Ln();

// Capacidades
$pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(200,220,200);
$pdf->Cell(0,6,'CAPACIDADES',1,1,'C',true);
$wc=63.3;
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell($wc,5,'Porte Bruto',1,0,'C',true); $pdf->Cell($wc,5,'Nº Tripulantes',1,0,'C',true); $pdf->Cell($wc,5,'Nº Passageiros',1,1,'C',true);
$pdf->SetFont('helvetica','',8);
$pdf->Cell($wc,7,$c['porte_bruto'] ? number_format($c['porte_bruto'],2,',','') : '',1,0,'C');
$pdf->Cell($wc,7,$c['numero_tripulantes'] ?? '',1,0,'C');
$pdf->Cell($wc,7,$c['numero_passageiros'] ?? '',1,1,'C');

// Navegação e Atividade
$pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(200,220,200);
$pdf->Cell(0,6,'NAVEGAÇÃO E ATIVIDADE',1,1,'C',true);
$wn=47.5;
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell($wn,5,'Tipo Navegação',1,0,'C',true); $pdf->Cell($wn,5,'Área Navegação',1,0,'C',true); $pdf->Cell($wn,5,'Atividade',1,0,'C',true); $pdf->Cell($wn,5,'Propulsão',1,1,'C',true);
$pdf->SetFont('helvetica','',7);
$pdf->Cell($wn,7,h($c['tipo_navegacao']),1,0,'C'); $pdf->Cell($wn,7,h($c['area_navegacao']),1,0,'C'); $pdf->Cell($wn,7,h($c['atividade_servico']),1,0,'C'); $pdf->Cell($wn,7,h($c['propulsao']),1,1,'C');

$pdf->Ln(3);

// Proprietário
$pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(200,220,200);
$pdf->Cell(0,6,'PROPRIETÁRIO / ARMADOR',1,1,'C',true);
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell($w1,5,'Nome',1,0,'C',true); $pdf->Cell($w2,5,'CPF / CNPJ',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell($w1,7,h($c['proprietario_nome']),1,0,'C'); $pdf->Cell($w2,7,h($c['proprietario_cpf_cnpj']),1,1,'C');
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell(0,5,'Endereço',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell(0,7,h($c['proprietario_endereco']),1,1,'L'); $pdf->Ln(2);

// Estaleiro
$pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(200,220,200);
$pdf->Cell(0,6,'ESTALEIRO / CONSTRUTOR',1,1,'C',true);
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell($w1,5,'Nome',1,0,'C',true); $pdf->Cell($w2,5,'CPF / CNPJ',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell($w1,7,h($c['estaleiro_nome']),1,0,'C'); $pdf->Cell($w2,7,h($c['estaleiro_cpf_cnpj']),1,1,'C');
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell(0,5,'Endereço',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell(0,7,h($c['estaleiro_endereco']),1,1,'L'); $pdf->Ln(3);

$pdf->SetFont('helvetica','',9);
$pdf->Cell(0,5,'Expedido em Belém-PA, '.dataPorExtenso($c['data_emissao']),0,1,'R'); $pdf->Ln(2);

// Assinatura
$sig_y = $pdf->GetY();
if ($sig_y > 220) { $pdf->AddPage(); $sig_y = $pdf->GetY(); }
$pdf->SetDrawColor(8,145,178); $pdf->SetLineWidth(0.5); $pdf->Rect(35,$sig_y,140,40);
$pdf->SetDrawColor(200,200,200); $pdf->SetLineWidth(0.2); $pdf->Rect(36,$sig_y+1,138,38);
if (imgOK($logo_path)) $pdf->Image($logo_path,42,$sig_y+7,22,22);
$pdf->SetDrawColor(100,100,100); $pdf->SetLineWidth(0.3); $pdf->Line(75,$sig_y+25,168,$sig_y+25);
$pdf->SetXY(75,$sig_y+27); $pdf->SetFont('helvetica','B',9); $pdf->Cell(93,4,h($c['assinante_nome']),0,1,'C');
$pdf->SetX(75); $pdf->SetFont('helvetica','',8); $pdf->Cell(93,4,h($c['assinante_titulo']),0,1,'C');
$pdf->SetX(75); $pdf->SetFont('helvetica','B',8); $pdf->SetTextColor(8,145,178); $pdf->Cell(93,4,h($c['assinante_registro']),0,1,'C'); $pdf->SetTextColor(0,0,0);

if (!empty($c['assinatura_imagem'])) {
    $img_data=$c['assinatura_imagem']; if(preg_match('/^data:image\/(\w+);base64,/',$img_data,$t)) $img_data=substr($img_data,strpos($img_data,',')+1);
    $decoded=base64_decode($img_data); if($decoded!==false){ $decoded=converterImagemParaJpeg($decoded); $f=tempnam(sys_get_temp_dir(),'sig_').'.jpg'; file_put_contents($f,$decoded); $pdf->Image($f,75,$sig_y+7,55,16); @unlink($f); }
}
$pdf->SetY($sig_y+44);

$link_assinatura = APP_URL.'assinar/'.$c['token_assinatura'];
$qr_y=$pdf->GetY();
try{ $qr=new TCPDF2DBarcode($link_assinatura,'QRCODE,M'); $qr_png=$qr->getBarcodePngData(3,3,array(0,0,0)); $f=tempnam(sys_get_temp_dir(),'qr_').'.png'; file_put_contents($f,$qr_png); $pdf->Image($f,10,$qr_y,15,15); @unlink($f); $pdf->SetXY(27,$qr_y); }catch(Exception$e){ $pdf->SetXY(10,$qr_y); }
$pdf->SetFont('helvetica','',6); $pdf->Cell(80,5,'Link de assinatura: '.$link_assinatura,0,1,'L');
if($c['assinado']){ $pdf->SetFont('helvetica','B',7); $pdf->SetX(27); $pdf->SetTextColor(0,100,0); $pdf->Cell(0,5,'Documento assinado por '.h($c['assinante_nome']).' em '.formatarDataCompleta($c['assinatura_em']),0,1,'L'); $pdf->SetTextColor(0,0,0); }
else { $pdf->SetFont('helvetica','I',7); $pdf->SetX(27); $pdf->Cell(0,5,'Acesse o link para assinar este documento.',0,1,'L'); }

$nome_arquivo = 'LC_'.str_replace('/','-',$c['numero_lc']).'.pdf';
$pdf->Output($nome_arquivo, 'I');
exit;