#!/usr/bin/php
<?php
		  // 파일 인코딩 설정
	      ini_set('default_charset', 'utf-8');
		 // mb_internal_encoding('UTF-8');
	     // mb_http_output('UTF-8');
		  		  // 이메일 유효성 검사 함수
		  function isValidEmail($email) {
			  // 빈 값 체크
			  if (empty($email) || trim($email) == '') {
				  return false;
			  }
			  
			  // 기본 이메일 형식 검증
			  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				  return false;
			  }
			  
			  // 추가 검증 (선택사항)
			  // 도메인이 최소 2글자 이상인지 확인
			  $parts = explode('@', $email);
			  if (count($parts) != 2) {
				  return false;
			  }
			  
			  $domain = $parts[1];
			  if (strlen($domain) < 3 || !strpos($domain, '.')) {
				  return false;
			  }
			  
			  return true;
		  }
			  

		  
          define('mail_addr', 'mail_addr'); 
		  define('from', 'from'); 
		  define('subject', 'subject'); 
		  define('content', 'content'); 
		  define('_BASE_DIR', '/var/www/html'); 
		  include _BASE_DIR."/PHPMailer/class.phpmailer.php";
		  
		  
		  $content  = addslashes($contentm);
		  $db_host = "3.229.229.247:3306";
		  $db_user = "prtdbu";

		  $db_passwd = 'lee10011';
		  $db_name = "prtadmindb";


		   $dbConn = mysql_connect($db_host,$db_user,$db_passwd) or die ("Don't Connect MySQL Server");
		   mysql_select_db($db_name);

		   mysql_query("SET NAMES utf8");
		   mysql_query("SET CHARACTER SET utf8");
		   mysql_query("SET character_set_connection=utf8");
		   
		   include "/var/www/html/include/function.php";
		   include "/var/www/html/include/purun_func.php";
		   
		   
		   
		   // 현재 날짜
		   $today = date('Y-m-d');

		   // 현재 날짜로부터 3일 후의 날짜
		   $tow_days_later = date('Y-m-d', strtotime('+2 days'));
		   $five_days_later = date('Y-m-d', strtotime('+2 days'));

		   // 출발일이 3일 이내인 모든 예약 조회 쿼리 (오늘부터 3일 후까지)
		   $query = "SELECT r.reserveCode, r.p_name, r.p_code, r.book_pri, r.book_email, 
					   r.book_phone, r.s_mail, r.parent, r.stDate, r.edDate, r.p_cnt, r.room_cnt
					  FROM reserve_info r
					  WHERE r.tour_type != '3'
					  AND (r.rev_status = 'READY' OR r.rev_status = 'ORDER' OR r.rev_status = 'DONE') 
					  AND r.s_mail != 'y'
					  AND r.stDate >= '$tow_days_later'
					  AND r.stDate <= '$five_days_later'
					  AND r.parent = 'MAIN'";

		  //echo $query;
		// exit;
		  $result = mysql_query($query);

		  while($row1 = mysql_fetch_assoc($result)){
			        $refo=getReserveInfo($row1['reserveCode']);
			        $prodInfo=getProductMaster($refo['p_code']);
					$gdmsg=getbusInfo8($refo['stDate'],$refo['reserveCode']);
					$g_dbinfo1 = getguideInfor($gdmsg['grand_eCode'],$gdmsg['sub_eCode']);
					$g_dbinfo = getinfo_dbMemberg($g_dbinfo1['guide_id']);
					$picnum = getPicGr4($refo['reserveCode'],$refo['book_pri']);
					
					
					$sbj = "[푸른투어] {nm1} 고객님 푸른투어입니다! {pm} 상품에 대한 출발 전 안내메일 드립니다.";
					///////////////////////////////////////////////////////////////////
					$arrContextOptions=array(
						"ssl"=>array(
							"verify_peer"=>false,
							"verify_peer_name"=>false,
						),
						'http' => array( 
						'method' => 'POST' 
						) 
					);  
					//$refo=getReserveInfo($row1['reserveCode']);
					 // 이메일 주소 유효성 검사
					if (!isValidEmail($refo['book_email'])) {
						echo "스킵: {$row1['reserveCode']} - 잘못된 이메일 주소: '{$refo['book_email']}'\n";
						$skip_count++;
						continue; // 다음 레코드로 넘어감
					}
					
					//$row1['book_pri'] = iconv('EUC-KR', 'UTF-8', $row1['book_pri']);
					//$row1['p_name'] = iconv('EUC-KR', 'UTF-8', $row1['p_name']);
					//$refo['p_name'] = iconv('EUC-KR', 'UTF-8', $refo['p_name']);
                    $sbj = str_replace('{nm1}',$refo['book_pri'],$sbj);
					$sbj= str_replace('{pm}',$refo['p_name'],$sbj);
				    $sbj = strip_tags($sbj);
					$sbj = htmlspecialchars($sbj, ENT_QUOTES, 'UTF-8');
					
					$content =  file_get_contents( "https://www.myprt.online/ginfo.html", false, stream_context_create($arrContextOptions));
					
					$content = str_replace('{img2}',UPLOAD_URL.$prodInfo['p_img1'],$content);			
					$content = str_replace('{nm}',$refo['book_pri'],$content);
					//echo $content;
					$date_parts = explode("-", $row1['stDate']);	
					$year = $date_parts[0];
					$month = $date_parts[1];
					$day = $date_parts[2];
					$date_string = $year." 년".$month." 월".$day." 일";
					$content = str_replace('{sdate}',$date_string,$content);
					$content = str_replace('{p_name}',$refo['p_name'],$content);
					$content = str_replace('{pick}',$picnum,$content);
					$content = str_replace('{gnm}',$g_dbinfo['kor_name'],$content);
					$content = str_replace('{gtel}',$g_dbinfo['company_phone'],$content);
					$content = str_replace('{kaka}',$g_dbinfo['kakao'],$content);
					$content = str_replace('{info}',$refo['sprogress'],$content);
					$msg = $content;
				    //echo $msg;
					//exit;
					//$ret= mailsend_a('wincom00@gmail.com',$sbj,$msg,$attachment1,$attachment2);
				   //echo "<br>$row1[reserveCode]:$row1[book_pri]";
					$qry2 = "Update reserve_info set s_mail='y' where reserveCode='{$row1['reserveCode']}'";
					$rst2 = mysql_query($qry2, $dbConn);
					
					
				    $ret= mailsend_a('online@prttour.com',$sbj,$msg,$attachment1,$attachment2);
					$ret= mailsend_a(trim($refo['book_email']),$sbj,$msg,$attachment1,$attachment2);
					//$ret= mailsend_a('wincom00@gmail.com',$sbj,$msg,$attachment1,$attachment2);
			 }
			
			echo "이메일 전송완료!";
		   

?>
