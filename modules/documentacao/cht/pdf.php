<?php
/**
 * PDF — Certificado de Homologação Técnica (CHT)
 */
require_once __DIR__ . '/../../../config.php';

$token_publico = $_GET['token'] ?? '';
$id = $_GET['id'] ?? '';

if (!empty($token_publico)) {
    require_once __DIR__ . '/../../../includes/functions.php';
    $stmt = $pdo->prepare("SELECT id FROM certificados_cht WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token_publico]);
    $row = $stmt->fetch();
    if (!$row) die("CHT não encontrado.");
    $id = $row['id'];
} elseif (!empty($id)) {
    require_once __DIR__ . '/../../../includes/auth.php';
    require_once __DIR__ . '/../../../includes/functions.php';
    verificar_sessao(); verificar_cargo('ADMIN');
} else {
    die("ID ou token não informado.");
}

$stmt = $pdo->prepare("SELECT * FROM certificados_cht WHERE id = :id AND ativo = 1");
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) die("CHT não encontrado.");

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

class CHT_PDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new CHT_PDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME); $pdf->SetAuthor('Amazon Naval Ltda'); $pdf->SetTitle('CHT - ' . $c['numero_relatorio_ht']);
$pdf->setPrintHeader(false); $pdf->setPrintFooter(false); $pdf->SetMargins(15, 15, 15); $pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

$logo_path = __DIR__ . '/../../../assets/img/logo.png';
if (imgOK($logo_path)) $pdf->Image($logo_path, 15, 12, 35, 20);

$pdf->SetY(14); $pdf->SetX(55);
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(240,240,240); $pdf->SetDrawColor(200,200,200);
$pdf->Cell(0, 6, h($c['numero_relatorio_ht']), 1, 1, 'R', true);

$pdf->Ln(2);
$pdf->SetFont('helvetica','B',11); $pdf->Cell(0,6,'AMAZON NAVAL LTDA',0,1,'C');
$pdf->SetFont('helvetica','',8); $pdf->Cell(0,4,'Serviços Técnicos de Engenharia Naval',0,1,'C'); $pdf->Ln(3);
$pdf->SetFont('helvetica','B',14); $pdf->Cell(0,8,'CERTIFICADO DE HOMOLOGAÇÃO TÉCNICA',0,1,'C'); $pdf->Ln(2);

// Tabela dados
$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$w1=50;$w2=140;
$pdf->Cell($w1,5,'Nº Relatório HT',1,0,'C',true); $pdf->Cell($w2,5,'Data de Emissão',1,1,'C',true);
$pdf->SetFont('helvetica','B',8); $pdf->Cell($w1,7,h($c['numero_relatorio_ht']),1,0,'C'); $pdf->Cell($w2,7,formatarDataBR($c['data_emissao']),1,1,'C');
$pdf->Ln(3);

// Profissional/Empresa
$pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(200,220,200);
$pdf->Cell(0,6,'PROFISSIONAL / EMPRESA HOMOLOGADO(A)',1,1,'C',true);

$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell($w1,5,'Nome',1,0,'C',true); $pdf->Cell($w2,5,'CPF / CNPJ',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->Cell($w1,7,h($c['profissional_empresa']),1,0,'C'); $pdf->Cell($w2,7,h($c['cpf_cnpj']),1,1,'C');

$pdf->SetFont('helvetica','B',7); $pdf->SetFillColor(220,220,220);
$pdf->Cell(0,5,'Atividade Técnica Homologada',1,1,'C',true);
$pdf->SetFont('helvetica','',8); $pdf->MultiCell(0,7,h($c['atividade_homologada']),1,'L');
$pdf->Ln(2);

// Observações
if (!empty($c['observacoes'])) {
    $pdf->SetFont('helvetica','B',8); $pdf->SetFillColor(240,240,200);
    $pdf->Cell(0,6,'OBSERVAÇÕES',1,1,'C',true);
    $pdf->SetFont('helvetica','',8); $pdf->MultiCell(0,5,h($c['observacoes']),1,'L');
    $pdf->Ln(3);
}

$pdf->SetFont('helvetica','',9);
$pdf->Cell(0,5,'Expedido em Belém-PA, '.dataPorExtenso($c['data_emissao']),0,1,'R'); $pdf->Ln(2);

// Assinatura
$sig_y = $pdf->GetY();
if ($sig_y > 230) { $pdf->AddPage(); $sig_y = $pdf->GetY(); }
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

$nome_arquivo = 'CHT_'.str_replace('/','-',$c['numero_relatorio_ht']).'.pdf';
$pdf->Output($nome_arquivo, 'I');
exit;