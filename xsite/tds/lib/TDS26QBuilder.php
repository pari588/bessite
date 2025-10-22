<?php
class TDS26QBuilder {
  public static function build($firm_id,$fy,$quarter,$outDir){
    global $pdo; if(!is_dir($outDir)) mkdir($outDir,0775,true);
    $firm=$pdo->query("SELECT * FROM firms WHERE id=".(int)$firm_id)->fetch();
    $stmt=$pdo->prepare("SELECT i.*,v.pan,v.name FROM invoices i JOIN vendors v ON v.id=i.vendor_id WHERE i.firm_id=? AND i.fy=? AND i.quarter=? ORDER BY i.invoice_date");
    $stmt->execute([$firm_id,$fy,$quarter]); $rows=$stmt->fetchAll();
    $alloc=$pdo->prepare("SELECT ca.*, c.bsr_code,c.challan_date,c.challan_serial_no FROM challan_allocations ca JOIN challans c ON c.id=ca.challan_id WHERE ca.invoice_id=?");
    $deductee=[]; $challanLines=[]; $controlAmount=0; $controlRecords=0;
    foreach($rows as $r){
      $controlAmount += (float)$r['total_tds']; $controlRecords++;
      $alloc->execute([$r['id']]); $as=$alloc->fetchAll();
      foreach($as as $a){
        $challanLines[]=['invoice_no'=>$r['invoice_no'],'section'=>$r['section_code'],'bsr'=>$a['bsr_code'],'challan_no'=>$a['challan_serial_no'],'challan_date'=>$a['challan_date'],'allocated_tds'=>$a['allocated_tds']];
      }
      $key=$r['pan'].'|'.$r['section_code'];
      if(!isset($deductee[$key])) $deductee[$key]=['pan'=>$r['pan'],'name'=>$r['name'],'section'=>$r['section_code'],'gross'=>0,'tds'=>0,'count'=>0];
      $deductee[$key]['gross'] += (float)$r['base_amount'];
      $deductee[$key]['tds']   += (float)$r['total_tds'];
      $deductee[$key]['count']++;
    }
    file_put_contents($outDir.'/control_totals.json', json_encode(['fy'=>$fy,'quarter'=>$quarter,'firm'=>$firm,'records'=>$controlRecords,'tds_total'=>round($controlAmount,2)], JSON_PRETTY_PRINT));
    $fp=fopen($outDir.'/deductee_details.csv','w'); fputcsv($fp,['PAN','Name','Section','Gross','TDS','Count']);
    foreach($deductee as $d){ fputcsv($fp,[$d['pan'],$d['name'],$d['section'],$d['gross'],$d['tds'],$d['count']]); } fclose($fp);
    $fp=fopen($outDir.'/challan_deductions.csv','w'); fputcsv($fp,['Invoice','Section','BSR','ChallanNo','ChallanDate','AllocatedTDS']);
    foreach($challanLines as $cl){ fputcsv($fp,[$cl['invoice_no'],$cl['section'],$cl['bsr'],$cl['challan_no'],$cl['challan_date'],$cl['allocated_tds']]); } fclose($fp);
    $stub = "FORM:26Q\nFY:$fy\nQTR:$quarter\nDEDUCTOR_TAN:{$firm['tan']}\nDEDUCTOR_PAN:{$firm['pan']}\nRECORDS:$controlRecords\nTDS_TOTAL:".number_format($controlAmount,2,'.','')."\n";
    file_put_contents($outDir.'/statement.txt',$stub);
    return ['records'=>$controlRecords,'tds_total'=>$controlAmount,'out'=>$outDir];
  }
}
