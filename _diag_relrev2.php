<?php
require __DIR__.'/include/dbconn.php';
function rows($sql){ global $dbConn; $r=mysql_query($sql,$dbConn); $out=array(); if($r){ while($x=mysql_fetch_assoc($r)) $out[]=$x; } else { echo "ERR: ".mysql_error()."\n"; } return $out; }

$s3 = rows("select reserveCode, grand_revNo, book_pri, book_email, book_phone, userid, c_code, stDate from reserve_info where parent='MAIN' && tour_type='3' && book_email!='' order by revDate desc limit 6");
foreach($s3 as $x){
  echo "rc={$x['reserveCode']} booker={$x['book_pri']} email={$x['book_email']} phone={$x['book_phone']} userid={$x['userid']}\n";
  $byEmail = rows("select count(*) c from reserve_info where parent='MAIN' && book_email='".esc($x['book_email'])."' && reserveCode!='".esc($x['reserveCode'])."'");
  $byPhone = rows("select count(*) c from reserve_info where parent='MAIN' && book_phone='".esc($x['book_phone'])."' && book_phone!='' && reserveCode!='".esc($x['reserveCode'])."'");
  $byUserid= rows("select count(*) c from reserve_info where parent='MAIN' && userid='".esc($x['userid'])."' && userid!='' && reserveCode!='".esc($x['reserveCode'])."'");
  echo "   others same email=".$byEmail[0]['c']."  same phone=".$byPhone[0]['c']."  same userid=".$byUserid[0]['c']."\n";
}
