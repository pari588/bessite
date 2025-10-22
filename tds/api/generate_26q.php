<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';

function ddmmyyyy($d){ $t=strtotime($d); return date('dmY',$t); }
function amt2($n){ return number_format((float)$n,2,'.',''); }
function rate4($n){ return number_format((float)$n,4,'.',''); }
function pad_fields($arr, $count){ $n = count($arr); if($n<$count){ $arr = array_merge($arr, array_fill(0, $count-$n, '')); } return array_slice($arr,0,$count); }
function line($fields){ return implode('^',$fields) . "\r\n"; }
function back($msg){ header('Location: /tds/admin/returns.php?msg='.urlencode($msg)); exit; }

$fy = $_POST['fy'] ?? ($_GET['fy'] ?? '');
$quarter = $_POST['quarter'] ?? ($_GET['quarter'] ?? '');
if(!$fy || !$quarter){ back('Missing FY/Quarter'); }

// Firm / Deductor
$firm = $pdo->query('SELECT * FROM firms ORDER BY id ASC LIMIT 1')->fetch();
if(!$firm){ back('Settings missing: Add firm in Settings.'); }

$dedName = $firm['display_name'] ?: ($firm['legal_name'] ?: 'Firm');
$dedPAN = strtoupper($firm['pan'] ?: '');
$dedTAN = strtoupper($firm['tan'] ?: '');
$addr1 = $firm['address1'] ?? '';
$addr2 = $firm['address2'] ?? '';
$addr3 = $firm['address3'] ?? '';
$state = $firm['state_code'] ?? '';
$pin   = $firm['pincode'] ?? '';
$email = $firm['email'] ?? '';
$std   = $firm['std_code'] ?? '';
$phone = $firm['phone'] ?? '';
$rp_name = $firm['rp_name'] ?? '';
$rp_des  = $firm['rp_designation'] ?? '';
$rp_mobile = $firm['rp_mobile'] ?? '';
$rp_email  = $firm['rp_email'] ?? '';

// Minimal mandatory checks
$missing = [];
if(!$addr1)  $missing[]='Address1';
if(!$state)  $missing[]='State Code';
if(!$pin)    $missing[]='PIN Code';
if(!$email)  $missing[]='Email';
if(!$rp_name)$missing[]='Responsible Person Name';
if(!$rp_mobile)$missing[]='Responsible Person Mobile';
if($missing){ back('Missing mandatory fields: '.implode(', ',$missing).'. Fill them in Settings.'); }

// AY/FY in numeric format e.g. FY 2025-26 => 202526, AY 2026-27 => 202627
if(preg_match('/^(\d{4})-(\d{2})$/',$fy,$m)){
  $fy_num = $m[1].$m[2];
  $ay_num = ((int)$m[1]+1).sprintf('%02d', ((int)$m[2]+1)%100);
}else{
  back('Invalid FY format');
}

// Pull challans & invoices of the FY/Q
$ch = $pdo->prepare('SELECT * FROM challans WHERE fy=? AND quarter=? ORDER BY challan_date,id');
$ch->execute([$fy,$quarter]); $challans = $ch->fetchAll();

$in = $pdo->prepare('SELECT i.*, v.name vname, v.pan vpan, v.category vcat FROM invoices i JOIN vendors v ON v.id=i.vendor_id WHERE i.fy=? AND i.quarter=? ORDER BY i.invoice_date, i.id');
$in->execute([$fy,$quarter]); $invoices = $in->fetchAll();

if(!$challans || !$invoices){ back('No challans or invoices found for '.$fy.' '.$quarter); }

// Greedy allocation: map invoices to challans
$alloc = []; // challan_id => list of invoice rows
$remaining = [];
foreach($challans as $c){ $remaining[$c['id']] = (float)$c['amount_tds']; $alloc[$c['id']] = []; }
foreach($invoices as $inv){
  $need = (float)$inv['total_tds'];
  foreach($challans as $c){
    $rem = $remaining[$c['id']];
    if($rem<=0) continue;
    $use = min($need, $rem);
    if($use>0){
      $inv_copy = $inv; $inv_copy['alloc_tds'] = $use; $inv_copy['challan_id'] = $c['id'];
      $alloc[$c['id']][] = $inv_copy;
      $remaining[$c['id']] -= $use;
      $need -= $use;
    }
    if($need<=0.0001) break;
  }
  if($need>0.0001){ back('Unallocated TDS for invoice '.$inv['invoice_no'].' : add/adjust challans.'); }
}

// Create output .txt with NS1 '^' delimiter
$outdir = __DIR__.'/../uploads';
if(!is_dir($outdir)) mkdir($outdir,0750,true);
$stamp = time();
$basename = "26Q_{$fy}_{$quarter}_{$stamp}";
$txt = $outdir . "/{$basename}.txt";
$zip = $outdir . "/{$basename}.zip";

$fh = fopen($txt,'w'); if(!$fh){ back('Cannot write output file'); }
$line_no = 1;

// ---------------- FH (18 fields)
$FH = [$line_no++, 'FH', 'NS1', 'R', date('dmY'), '1', 'D', $dedTAN, '1', 'TDS-AutoFile', '', '', '', '', '', '', '', ''];
$FH = pad_fields($FH, 18); fwrite($fh, line($FH));

// ---------------- BH (72 fields)
$total_challans = count($challans);
$period = $quarter; // Q1/Q2/Q3/Q4
$income_total = 0.00; foreach($challans as $c){ $income_total += (float)$c['amount_tds']; }
$BH = [
  $line_no++, 'BH', '1', $total_challans, '26Q', '', '', '', '', '', '', '', $dedTAN, '', ($dedPAN?:'PANNOTREQD'),
  $ay_num, $fy_num, $period, $dedName,
  '', $addr1, $addr2, $addr3, '', '',
  $state, $pin, $email, $std, $phone, 'N', '', // basic contacts
  $rp_name, $rp_des, $addr1, $addr2, $addr3, '', '',
  $state, $pin, ($rp_email?:$email), $rp_mobile, $std, $phone, 'N',
  amt2($income_total), '0', '', '', 'N', 'N', '', $state, '', '', '', '', '', 
  '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
];
$BH = pad_fields($BH, 72); fwrite($fh, line($BH));

// ---------------- CD & DD
$batch_no = 1; $cd_serial = 0;
foreach($challans as $c){
  $cd_serial++;
  $cd_total_dd = count($alloc[$c['id']]);
  $cd_amt = (float)$c['amount_tds'];

  $CD = [
    $line_no++, 'CD', $batch_no, $cd_serial, $cd_total_dd, 'N', '', '', '', '', '',
    $c['challan_serial_no'], '', '', '',
    $c['bsr_code'], '', ddmmyyyy($c['challan_date']),
    '', '', '', '', '', '',
    amt2($cd_amt),
    '', '', '', '', '', '', '', '', '', '', '', '', '', ''
  ];
  $CD = pad_fields($CD, 40); fwrite($fh, line($CD));

  $dd_serial = 0;
  foreach($alloc[$c['id']] as $inv){
    $dd_serial++;
    $is_company = (isset($inv['vcat']) && $inv['vcat']==='company');
    $ded_code = $is_company ? '01' : '02';
    $rate = rate4($inv['tds_rate']);
    $income_tax = amt2($inv['tds_amount']); $surcharge = '0.00'; $cess = '0.00'; $total_tax = amt2($inv['total_tds']);
    $amount_paid = amt2($inv['base_amount']); $date_pay = ddmmyyyy($inv['invoice_date']); $date_ded = $date_pay; $book_cash='N';

    $DD = [
      $line_no++, 'DD', $batch_no, $cd_serial, $dd_serial, 'O', '', $ded_code, '', strtoupper($inv['vpan'] ?: 'PANNOTAVBL'),
      '', $inv['vname'],
      $income_tax, $surcharge, $cess, $total_tax, '', '', '', 
      $amount_paid, $date_pay, $date_ded, '', $rate, '', $book_cash,
      '', '', '', '',
      $inv['section_code'],
      '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
    ];
    $DD = pad_fields($DD, 53); fwrite($fh, line($DD));
  }
}

fclose($fh);

// zip
$z = new ZipArchive(); if($z->open($zip, ZipArchive::CREATE)!==true){ back('Zip creation failed'); }
$z->addFile($txt, basename($txt)); $z->close();
@unlink($txt);

back('FVU text generated: '.basename($zip));
