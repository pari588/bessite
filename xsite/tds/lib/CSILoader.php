<?php
require_once __DIR__.'/helpers.php';
class CSILoader {
  public static function ingest($firm_id, $path){
    global $pdo; $txt=file_get_contents($path);
    $lines=preg_split('/\r?\n/', $txt);
    $ins=$pdo->prepare('INSERT IGNORE INTO challans (firm_id,bsr_code,challan_date,challan_serial_no,minor_head,amount_total,amount_tds,amount_interest,amount_fee,fy,quarter,csi_filename,csi_text) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?)');
    $added=0;
    foreach($lines as $ln){
      if(preg_match('/^(\d{7})\|(\d{2}\/\d{2}\/\d{4})\|(\d{5,7})\|([\d\.]+)\|(\d{3})/',$ln,$m)){
        $date = DateTime::createFromFormat('d/m/Y',$m[2])->format('Y-m-d');
        [$fy,$q]=fy_quarter_from_date($date);
        $ins->execute([$firm_id,$m[1],$date,$m[3],$m[5],$m[4],$m[4],0,0,$fy,$q,basename($path),$ln]);
        $added++;
      }
    }
    return [$added,count($lines)];
  }
}
