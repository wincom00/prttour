<?php
require __DIR__.'/include/dbconn.php';

function rows($sql){ global $dbConn; $r=mysql_query($sql,$dbConn); $out=array(); if($r){ while($x=mysql_fetch_assoc($r)) $out[]=$x; } else { echo "ERR: ".mysql_error()."\n"; } return $out; }

echo "== tour_type distribution (parent=MAIN) ==\n";
foreach(rows("select tour_type, count(*) c from reserve_info where parent='MAIN' group by tour_type order by tour_type") as $x){
  echo "  tour_type=[{$x['tour_type']}] count={$x['c']}\n";
}

echo "\n== sample tour_type=3 MAIN reservations ==\n";
$s3 = rows("select reserveCode, grand_revNo, parent, tour_type, p_name, revDate from reserve_info where parent='MAIN' && tour_type='3' order by revDate desc limit 8");
foreach($s3 as $x){
  echo "  rc={$x['reserveCode']} grand=[{$x['grand_revNo']}] p_name={$x['p_name']}\n";
}

echo "\n== for each sample, siblings sharing grand_revNo ==\n";
foreach($s3 as $x){
  $g = $x['grand_revNo'];
  $rc= $x['reserveCode'];
  $sib = rows("select reserveCode, parent, tour_type from reserve_info where grand_revNo='".esc($g)."' && reserveCode != '".esc($rc)."'");
  $sibMain = rows("select reserveCode, parent, tour_type from reserve_info where grand_revNo='".esc($g)."' && reserveCode != '".esc($rc)."' && parent='MAIN'");
  echo "  rc=$rc grand=[$g] : total_other=".count($sib)." , parent=MAIN_other=".count($sibMain)."\n";
  foreach($sib as $sx){ echo "      sib rc={$sx['reserveCode']} parent={$sx['parent']} tt={$sx['tour_type']}\n"; }
}

echo "\n== sample tour_type=1 with siblings (for contrast) ==\n";
$g1 = rows("select grand_revNo, count(*) c from reserve_info where parent='MAIN' group by grand_revNo having c>1 order by c desc limit 5");
foreach($g1 as $x){ echo "  grand=[{$x['grand_revNo']}] mainCount={$x['c']}\n"; }
