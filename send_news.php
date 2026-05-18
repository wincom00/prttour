#!/usr/bin/php
<?php
          define('mail_addr', 'mail_addr'); 
		  define('from', 'from'); 
		  define('subject', 'subject'); 
		  define('content', 'content'); 
		  define('_BASE_DIR', '/var/www/html'); 
		  include _BASE_DIR."/PHPMailer/class.phpmailer.php";
		  
		  $from= "푸른투어<online@prttour.com>";
		  $subject = "[VVIP 특선👑] 미국 대륙을 횡단하는 단 1회의 프리미엄 투어✨";
		  $contentm = file_get_contents("http://www.myprt.online/news/2025/news_052225.html");

		  $content  = addslashes($contentm);
		  $db_host = "3.229.229.247:3306";
		  $db_user = "prtdbu";

		  $db_passwd = 'lee10011';
		  $db_name = "prtadmindb";


		   $dbConn = mysql_connect($db_host,$db_user,$db_passwd) or die ("Don't Connect MySQL Server");
		   mysql_select_db($db_name);

		   mysql_query("set names utf-8");
		   
		   //(area='all' || area='head' || area='카카오')	
		   //area='las'
		   //area='la'
		   //(area='las' || area='la')
		   //(area='all' || area='las' || area='la')
		   $qry3 = "SELECT seq_no,mail_addr FROM prt_mlist AS a WHERE   chk_sub = '0' && chk_send='0'  order by seq_no desc "; // mail_addr = 'wincom00@gmail.com'"; 
		   $rst3 = mysql_query($qry3,$dbConn);
	 
		   $i=1;

		  //echo $content;
		  //exit;
		 // echo mysql_affected_rows()."test\n";
		   
		   while ($row3 = mysql_fetch_assoc($rst3)) {
			     $error = 0;
				 if( !filter_var($row3['mail_addr'], FILTER_VALIDATE_EMAIL) ){

				   $error = 1;
				   
				}
				// echo $row3[mail_addr].'||'.$error.'TEST<br/>';
				// exit;
				 if (($row3['mail_addr'] != "") && ($error == "0")) {	
					    
						$subj=iconv("UTF-8","UTF-8//IGNORE", $subject);

						$value = stripslashes((string)$content);	
						$value = str_replace("emailunsubscribe",$row3['mail_addr'],$value);

						$mail = new PHPMailer(true);
						$mail->IsSMTP();
						
						$mail->CharSet = "UTF-8"; 
						$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
						$mail->SMTPAuth = true; // authentication enabled
						$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
						$mail->Host = 'in-v3.mailjet.com';
						$mail->Port = 587; 
						$mail->Username = "282e8c9efc95ca3560bacf3a92e5e162";
						$mail->Password = "639fc882c20d382357d7102ed1dee309"; 
						$mail->SetFrom("online@prttour.com","PRUNTOUR");
						
						$mail->Subject = $subj;
										
						$mail->MsgHTML($value);
						
						$mail->AddAddress($row3['mail_addr']);
					
					
						foreach($attachments as $attachment) {
								//$mail->AddAttachment("images/phpmailer.gif");      // attachment example
								$mail->AddAttachment($attachment);
					    }
					
						
						if(!$mail->Send()){
							//echo "1111";
							//exit;
						  
						  return $mail->ErrorInfo;
						} else {
							
						   $qry2 = "update prt_mlist  set chk_send=1  WHERE mail_addr = '".$row3['mail_addr']."' limit 1";
						   $rst2 = mysql_query($qry2, $dbConn); 	
						   //echo "3333";
						   //exit;
						   echo true;
						}



				  }
					 
					
			}
		   

?>
