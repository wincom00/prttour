<?php
require __DIR__.'/include/dbconn.php';
function rows($sql){ global $dbConn; $r=mysql_query($sql,$dbConn); $out=array(); if($r){ while($x=mysql_fetch_assoc($r)) $out[]=$x; } else { echo "ERR: ".mysql_error()."\n"; } return $out; }
echo "== reserve_info columns ==\n";
foreach(rows("show columns from reserve_info") as $c){ echo "  {$c['Field']} ({$c['Type']})\n"; }
echo "\n== full row of one tour_type=3 ==\n";
$r = rows("select * from reserve_info where parent='MAIN' && tour_type='3' order by revDate desc limit 1");
foreach($r[0] as $k=>$v){ if($v!=='' && $v!==null) echo "  $k = $v\n"; }
