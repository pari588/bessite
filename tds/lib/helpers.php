<?php
date_default_timezone_set('Asia/Kolkata');

function fy_quarter_from_date($date){
  $d=new DateTime($date); $m=(int)$d->format('n'); $y=(int)$d->format('Y');
  if($m>=4){ $fy=sprintf('%d-%02d',$y,($y+1)%100); } else { $fy=sprintf('%d-%02d',$y-1,$y%100); }
  $q = ($m>=4 && $m<=6)?'Q1':(($m<=9)?'Q2':(($m<=12)?'Q3':'Q4')); if($m<=3){ $q='Q4'; }
  return [$fy,$q];
}

function clean_csv_row($row){ return array_map(fn($v)=>trim((string)$v), $row); }

function money($n){ return number_format((float)$n,2,'.',''); }

function get_tds_sections(PDO $pdo){
  $res=$pdo->query("SELECT section_code, MAX(description) AS descn FROM tds_rates GROUP BY section_code ORDER BY section_code");
  return $res->fetchAll();
}

function fy_list($span=7){
  $now = new DateTime('now');
  $y = (int)$now->format('Y'); $m=(int)$now->format('n');
  $start = ($m>=4)?$y:($y-1);
  $half = intdiv($span,2);
  $from = $start - $half; $to = $start + $half;
  $out=[];
  for($yy=$to; $yy>=$from; $yy--){
    $out[] = sprintf('%d-%02d', $yy, ($yy+1)%100);
  }
  return $out;
}

function ay_from_fy($fy){
  if(preg_match('/^(\d{4})-(\d{2})$/',$fy,$m)){
    $y1=(int)$m[1]; $y2=(($y1+2)%100);
    return sprintf('%d-%02d', $y1+1, $y2);
  }
  return '';
}
