#!/usr/bin/php
<?php
          define('mail_addr', 'mail_addr'); 
		  define('from', 'from'); 
		  define('subject', 'subject'); 
		  define('content', 'content'); 
		  define('_BASE_DIR', '/var/www/html'); 
		  include _BASE_DIR."/PHPMailer/class.phpmailer.php";
		  
		  $from= "DONGBUTOUR<admin@dongbutour.com>";
		  $subject = "[동부투어] 동부투어가 추천하는 최고의 겨울 여행지";
		  $contentm = file_get_contents("http://dongbutour.online/news/111623_dongbu.html");

		  $content  = addslashes($contentm);
		  $db_host = "74.208.178.245:3306";
		  $db_user = "wincom00";

		  $db_passwd = 'dong1234lee';
		  $db_name = "dbdb_1021";
//echo $contentm."TETS";

		   $dbConn = mysql_connect($db_host,$db_user,$db_passwd) or die ("Don't Connect MySQL Server");
		   mysql_select_db($db_name);

		   mysql_query("set names euckr");
		   
		   		
		   $qry3 = "SELECT seq_no,mail_addr FROM dong_mlist2 AS a WHERE chk_sub = '0' && chk_send='0'  order by seq_no desc "; // mail_addr = 'wincom00@gmail.com'"; 
		   $rst3 = mysql_query($qry3,$dbConn);
	 
		   $i=1;


		  //echo mysql_affected_rows()."test\n";
		   $error = 0;
		   while ($row3 = mysql_fetch_assoc($rst3)) {
			     
				 if( !filter_var($row3['mail_addr'], FILTER_VALIDATE_EMAIL) ){



				   $error = 1;
				   //echo $row3[mail_addr].'||'.$error.'TEST<br/>';
				   
				}
				
				 //echo $row3[mail_addr].'||'.$error.'TEST<br/>';
				 //exit;
				 if (($row3['mail_addr'] != "") && ($error == "0")) {	
					     /*   $mail = explode(",",$row3[mail_addr]);
					  
							$m = new SimpleEmailServiceMessage();
						    if (count($mail) > 1) { 
								 for ($j=1 ; count($mail) >= $j ; $j++) {
									$arr = array();
									$arr[$j] = $mail[$j-1];

								 }
							} else {
								$m->addTo($row3[mail_addr]);
								
							}	
															
						
							$m->setFrom($from);
					  
							$m->setSubject(iconv("UTF-8","UTF-8//IGNORE", $subject)); 
							$value = stripslashes((string)$content);	
							$value = str_replace("emailunsubscribe",$row3[mail_addr],$value);
							$m->setMessageFromString("TEST",iconv("UTF-8","UTF-8//IGNORE", $value));
						
							$result=$ses->sendEmail($m);
					  
							$status =$result['MessageId']; 
							
						    if ($status!="") {
							
							 $qry2 = "update dong_mlist2  set chk_send=1  WHERE mail_addr = '".$row3[mail_addr]."' limit 1";
							 $rst2 = mysql_query($qry2, $dbConn); 
							
						    } else {
								 echo $row3[mail_addr]."-sent fail !!!\n";
								 

						   
							}
							
								
							
								$subj=iconv("UTF-8","UTF-8//IGNORE", $subject);

								$value = stripslashes((string)$content);	
							    $value = str_replace("emailunsubscribe",$row3[mail_addr],$value);

							    $mail = new PHPMailer(true);
								
								$mail->IsSMTP();
								
								$mail->CharSet = "utf-8"; 
								$mail->Encoding = "base64"; 
								$mail->SMTPDebug = false; // debugging: 1 = errors and messages, 2 = messages only
								$mail->SMTPAuth = true; // authentication enabled
								$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
								$mail->Host = 'mail.smtp2go.com';
								$mail->Port = 2525; 
								$mail->Username = "dongbutour.com";
								$mail->Password = "MmJyZHFzNGJucGcw";
								$mail->IsHTML(true);
								$mail->SetFrom("admin@dongbutour.com","DONGBUTOUR");
								$mail->AltBody = '';
								$mail->Subject = $subj;
								
								$mail->MsgHTML($value);
								
								$mail->AddAddress($row3[mail_addr]);
							
								
							
								
								
								if(!$mail->Send()){
									
								  ECHO $mail->ErrorInfo;
								} else {
								   $qry2 = "update dong_mlist2  set chk_send=1  WHERE mail_addr = '".$row3[mail_addr]."' limit 1";
								   $rst2 = mysql_query($qry2, $dbConn); 	
								   echo true;
								}
				
				*/
						//echo $row3[mail_addr].'||'.$error.'TEST<br/>';
						$subj=iconv("UTF-8","UTF-8//IGNORE", $subject);

						$value = stripslashes((string)$content);	
						$value = str_replace("emailunsubscribe",$row3['mail_addr'],$value);

						$mail = new PHPMailer(true);
						$mail->IsSMTP();
						
						$mail->CharSet = "UTF-8"; 
						$mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
						$mail->SMTPAuth = true; // authentication enabled
						$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
						/*$mail->Host = 'in-v3.mailjet.com';
						$mail->Port = 587; 
						$mail->Username = "06643e796bc5619a980f0f38f948bd90";
						$mail->Password = "7e250f6756285c98fa6bd97622ae150b";
						*/
						$mail->Host = 'in-v3.mailjet.com';
						$mail->Port = 587; 
						$mail->Username = "06643e796bc5619a980f0f38f948bd90";
						$mail->Password = "7e250f6756285c98fa6bd97622ae150b";
						$mail->SetFrom("admin@dongbutour.com","DONGBUTOUR");
						$mail->Subject = $subj;
										
						$mail->MsgHTML($value);
						
						$mail->AddAddress($row3['mail_addr']);
					
					
						foreach($attachments as $attachment) {
								//$mail->AddAttachment("images/phpmailer.gif");      // attachment example
								$mail->AddAttachment($attachment);
					    }
					
						
						if(!$mail->Send()){
						//echo $mail->ErrorInfo."111";
						  return $mail->ErrorInfo;
						} else {
							
						   $qry2 = "update dong_mlist2  set chk_send=1  WHERE mail_addr = '".$row3['mail_addr']."' limit 1";
						   $rst2 = mysql_query($qry2, $dbConn); 	
						   echo true;
						}











				  } else {
					$error = 0;
					//echo $error;
					//exit;

				  }
					 
					
			}
		   

?>
