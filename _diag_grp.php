<?php
require __DIR__.'/include/dbconn.php';
function rows($sql){ global $dbConn; $r=mysql_query($sql,$dbConn); $out=array(); if($r){ while($x=mysql_fetch_assoc($r)) $out[]=$x; } else { echo "ERR: ".mysql_error()."\n"; } return $out; }

// grand groups that contain at least one tour_type=3 AND have more than 1 row total
echo "== grand_revNo groups containing tour_type=3 with >1 total rows ==\n";
$g = rows("select grand_revNo, count(*) c, sum(tour_type='3') t3, sum(parent='MAIN') mains
           from reserve_info
           where grand_revNo in (select grand_revNo from reserve_info where tour_type='3' && parent='MAIN')
           group by grand_revNo having c>1 order by c desc limit 15");
if(!count($g)) echo "  (none)\n";
foreach($g as $x){ echo "  grand=[{$x['grand_revNo']}] total={$x['c']} tour_type3={$x['t3']} parentMAIN={$x['mains']}\n"; }

echo "\n== detail of first such group ==\n";
if(count($g)){
  $gr=$g[0]['grand_revNo'];
  foreach(rows("select reserveCode, parent, tour_type, p_name, stDate from reserve_info where grand_revNo='".esc($gr)."' order by revDate") as $x){
    echo "  rc={$x['reserveCode']} parent={$x['parent']} tt={$x['tour_type']} st={$x['stDate']} name=".substr($x['p_name'],0,40)."\n";
  }
}
