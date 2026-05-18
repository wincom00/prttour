<?php
if (file_exists(__DIR__ . '/remote_upload.php')) {
    require_once __DIR__ . '/remote_upload.php';
}
if (!function_exists('remote_sync_file')) {
    function remote_sync_file($p, $f) { return false; }
    function remote_detect_folder($p) { return null; }

    function remote_ftp_test(&$e='') { return true; }
}

		
		function mailsend_f($to,$subj,$contents,$attachments=false) {
		
				$mail = new PHPMailer(true);
				$mail->IsSMTP();
				$mail->CharSet = "euc-kr"; 
				$mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
				$mail->SMTPAuth = true; // authentication enabled
				$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
				$mail->Host = 'smtp.gmail.com';
				$mail->Port = 587; 
				$mail->Username = "dongbutour";
				$mail->Password = "db1038tour";
				$mail->SetFrom("admin@dongbutour.com","DONGBUTOUR");
				$mail->AddReplyTo("admin@dongbutour.com","DONGBUTOUR");
				$mail->AltBody = '답장메일은 admin@dongbutour.com 입니다.';
				$mail->Subject = $subj;
				$mail->MsgHTML($contents);
				$mail->AddAddress($to);
	
				foreach($attachments as $attachment) {
				        //$mail->AddAttachment("images/phpmailer.gif");      // attachment example
				        $mail->AddAttachment($attachment);
			    }
			   
				
				if(!$mail->Send()){
					
				  return $mail->ErrorInfo;
				} else {
				    $mail->ClearAddresses();
					$mail->ClearCCs();
					$mail->ClearBCCs();
					$mail->ClearAttachments();
				   return true;
				}
				

				

		}

		
      

	function hr_display($the_time) {
		$total_hrs = (int) ($the_time / 3600);
		$total_min = (int) ($the_time / 60) - ($total_hrs * 60);
		if ($total_min < 10) {
			$total_min = "0".$total_min;
		}

		return "$total_hrs:$total_min";
	}
		
		
    function file_save($the_file, $directory) {
		//$allowedExts = array("jpg", "jpeg", "gif", "png");
		$extension = end(explode(".", $the_file["name"]));
		$msg1 = "";

		
			if ($the_file["error"] > 0) {
				$msg1 = $msg1 . "Return Code: " . $the_file["error"] . "<br>";
			} else {
				$msg1 = $msg1 . "Upload: " . $the_file["name"] . "<br>";
				$msg1 = $msg1 . "Type: " . $the_file["type"] . "<br>";
				$msg1 = $msg1 . "Size: " . ($the_file["size"] / 1024) . " kB<br>";
				$msg1 = $msg1 . "Temp file: " . $the_file["tmp_name"] . "<br>";

				$i = 1;
				$file_name = $the_file["name"];
				while (file_exists($directory . $file_name)) {
					$file_name = basename($the_file["name"], "." . $extension) . "_" . $i++ . "." . $extension;
				}
				//$new_name = md5(microtime()) . '.' . $extension;
				if (is_uploaded_file($the_file['tmp_name'])) {
					move_uploaded_file($the_file["tmp_name"], $directory . $file_name);
					$_rf = remote_detect_folder($directory); if ($_rf) remote_sync_file($directory . $file_name, $_rf);
					$msg1 = $msg1 . "Stored in: " . $directory . $file_name;
				} else {
					$msg1 = $msg1 . "Not uploaded file";
				}
			}
		
		return $file_name;
	}
function dateDiff($sStartDate, $sEndDate)
{
    $sStartTime = strtotime($sStartDate);
    $sEndTime = strtotime($sEndDate);

    if($sStartTime > $sEndTime)
        return false;

    $sDiffTime = $sEndTime - $sStartTime;

    $aReturnValue['d'] = floor($sDiffTime/60/60/24);
    //$aReturnValue['d'] = $sDiffTime/60/60/24;
    $aReturnValue['H'] = sprintf("%02d", ($sDiffTime/60/60)%24);
    $aReturnValue['i'] = sprintf("%02d", ($sDiffTime/60)%60);

    return $aReturnValue;
}

function credit_process($xType,$address,$zipcode,$card_num,$cvv2,$month,$year,$last_total,$first_name,$last_name,$invoice_num)
{
		$DEBUGGING					= 1;				# Display additional information to track down problems
		$TESTING					= 1;				# Set the testing flag so that transactions are not live
		$ERROR_RETRIES				= 2;				# Number of transactions to post if soft errors occur

		$auth_net_login_id			= "9Jwe8M4D";
		$auth_net_tran_key			= "4RSQ4hQ3c4W598nJ";
		#$auth_net_url				= "https://certification.authorize.net/gateway/transact.dll";
		#  Uncomment the line ABOVE for shopping cart test accounts or BELOW for live merchant accounts
		$auth_net_url				= "https://secure.authorize.net/gateway/transact.dll";

		$expire_date = $month.$year;

		$ship_address = explode("NaN",$address);

		$authnet_values				= array
		(
			"x_login"				=> $auth_net_login_id,
			"x_version"				=> "3.1",
			"x_delim_char"			=> "|",
			"x_delim_data"			=> "TRUE",
			"x_url"					=> "FALSE",
			"x_type"				=> $xType,
			"x_method"				=> "CC",
			"x_tran_key"			=> $auth_net_tran_key,
			"x_relay_response"		=> "FALSE",
			"x_card_num"			=> $card_num,
			"x_exp_date"			=> $expire_date,
			"x_description"			=> "PURUNTOUR",
			"x_amount"				=> $last_total,
			"x_first_name"			=> $first_name,
			"x_last_name"			=> $last_name,
			"x_address"				=> $ship_address[1],
			"x_city"				=> $ship_address[2],
			"x_state"				=> $ship_address[3],
			"x_zip"					=> $zipcode,
			"x_invoice_num"					=> $invoice_num,
			"CustomerBirthMonth"	=> "&nbsp;",
			"CustomerBirthDay"		=> "&nbsp;",
			"CustomerBirthYear"		=> "&nbsp;",
			"SpecialCode"			=> "&nbsp;",
		);

		$fields = "";
		foreach( $authnet_values as $key => $value ) $fields .= "$key=" . urlencode( $value ) . "&";

		$ch = curl_init("https://secure.authorize.net/gateway/transact.dll"); // URL of gateway for cURL to post to

		//$ch = curl_init("https://certification.authorize.net/gateway/transact.dll");

		/**
		* Go daddy 땜시 특별히 넣음

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt ($ch, CURLOPT_PROXY, '64.202.165.130:3128');
		*/

		#$ch = curl_init("https://secure.authorize.net/gateway/transact.dll"); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data

		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		curl_error($ch);
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		//echo $resp;
		return $result_value = explode("|",$resp);
		
}


	
	
   
	

	/**
	* @ 토탈예약 정보보 가져오기
	*/
	function getTotalReserveBasic($reserveCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_info_total where reserveCode = '$reserveCode'";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		
		return $row1;

	}

	



	


    function employeelist($userid){
			
			global $dbConn;

			$qry1 = "select * from member_list where division = 'admin' && out_yn is null order by userid asc";
			$rst1 = mysql_query($qry1,$dbConn);
			
			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['userid'];
				
				if($selectValue == $userid)
				{
					$option.= "<option value=$selectValue selected>{$row1['kor_name']}({$row1['userid']})";
				}
				else
				{
					$option.= "<option value=$selectValue>{$row1['kor_name']}({$row1['userid']})";
				}
				

			}

			return $option;

		}
    
    function update_time($user_info,$v){

		global $dbConn;

		$qry1 = "update member_list set time_yn='$v'  where userid = '$user_info'";
		
		$rst1 = mysql_query($qry1);
		
	
	
	}
	function printBannerSpot($spot){
		
		global $dbConn;

		$left_banner_qry1 = "select * from banner_page where area = '$spot'";
		 
		$left_banner_rst1 = mysql_query($left_banner_qry1);
		$left_banner_row1 = mysql_fetch_assoc($left_banner_rst1);
    
		return $left_banner_row1['content'];

	}



	
	
  

	function printEmployeeSelect($employee_id = false){
	
	global $dbConn;

		$qry1 = "select * from member_list where division in ('admin','normal') && del_yn  ='N' order by userid asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			$company_area = codebaseName($row1['company_area']);

			if($employee_id == $row1['userid'])
			{
				$content .= "<option value='{$row1['userid']}' selected> {$row1['kor_name']} ({$row1['userid']})";
			}
			else
			{
				$content .= "<option value='{$row1['userid']}'> {$row1['kor_name']} ({$row1['userid']})";
			}
			

		}

		return $content;
	}
  
	
	function printRandSelectByAuth($rand_id = false){
		global $dbConn, $division;

		if (hasMenuAccess($division, '3', '1')) {
			if (! hasMenuAccess($division, '3', '2')) {
				$sqlSelection = "and substring(company_area, 4, 1) <> '4'";
			}
			if (! hasMenuAccess($division, '3', '3')) {
				$sqlSelection .= " and substring(company_area, 4, 2) <> '*0'";
			}
			if (! hasMenuAccess($division, '3', '4')) {
				$sqlSelection .= " and substring(company_area, 4, 2) <> '60'";
			}
			if (! hasMenuAccess($division, '3', '5')) {
				$sqlSelection .= " and substring(company_area, 4, 2) <> '*2'";
			}
			if (! hasMenuAccess($division, '3', '6')) {
				$sqlSelection .= " and substring(company_area, 4, 2) <> '50'";
			}
		} else {
			if (hasMenuAccess($division, '3', '2')) {
				$sqlSelection = "and (substring(company_area, 4, 1) = '4'";
			}
			if (hasMenuAccess($division, '3', '3')) {
				$sqlSelection .= " or substring(company_area, 4, 2) = '*0'";
			}
			if (hasMenuAccess($division, '3', '4')) {
				$sqlSelection .= " or substring(company_area, 4, 2) = '60'";
			}
			if (hasMenuAccess($division, '3', '5')) {
				$sqlSelection .= " or substring(company_area, 4, 2) = '*2'";
			}
			if (hasMenuAccess($division, '3', '6')) {
				$sqlSelection .= " or substring(company_area, 4, 2) = '50'";
			}
			$sqlSelection .= ")";
		}

		$qry1 = "select * from member_list where division = 'rand' && del_yn  ='N' && company_area <> 'A019000' $sqlSelection order by company_area,kor_name asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			$company_area = codebaseName($row1['company_area']);

			if($rand_id == $row1['userid'])
			{
				$content .= "<option value='{$row1['userid']}' selected>[{$company_area['comment']}] {$row1['kor_name']} ({$row1['userid']})";
			}
			else
			{
				$content .= "<option value='{$row1['userid']}'>[{$company_area['comment']}] {$row1['kor_name']} ({$row1['userid']})";
			}
			

		}

		return $content;
	}

	
	
	function printRandSelectCruise($rand_id = false){
	
	global $dbConn;

		$qry1 = "select * from member_list where division = 'rand' && del_yn  ='N' && userid like 'CRUISE%' && company_area <> 'A019000' order by company_area,kor_name asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			$company_area = codebaseName($row1['company_area']);

			if($rand_id == $row1['userid'])
			{
				$content .= "<option value='{$row1['userid']}' selected>[{$company_area['comment']}] {$row1['kor_name']} ({$row1['userid']})";
			}
			else
			{
				$content .= "<option value='{$row1['userid']}'>[{$company_area['comment']}] {$row1['kor_name']} ({$row1['userid']})";
			}
			

		}

		return $content;
	}

	
	// 메인 사진 게시판
	function printGallery($table_id){
		
		global $dbConn;

		// 사진이 있는것만 가져온다.
		$qry1 = "select * from chan_board where tablename = '$table_id' && thread = 'A' && userfile1 is not null order by seq_no desc limit 3";
		$rst1 = mysql_query($qry1,$dbConn);


		$num1 = 1;

		while($row1 = mysql_fetch_assoc($rst1)){


			$title = Misc::cutLongString($row1['title'], 30, $dot=true);
			
			if($num1%3 == "0")
			{
				$add_td = "";
			}
			else
			{
				$add_td = "<td><img src=images/main/line_photo.gif width=20 height=125></td>";
			}

			$content .= "
						  <td valign=top><table width=104 border=0 cellspacing=0 cellpadding=0>
                              <tr>
                                <td><table width=104 border=0 cellpadding=0 cellspacing=2 bgcolor=#ebebeb>
                                    <tr>
                                      <td height=76 bgcolor=#FFFFFF><a href=board_view.php?board_mode=view&table_id=$table_id&division=&no={$row1['seq_no']}&start=0&Mode=&how=&S_content=><img src=".UPLOAD_URL."{$row1['userfile1']}\" width=100 height=56 border=0></a></td>
                                    </tr>
                                </table></td>
                              </tr>
                              <tr>
                                <td height=8></td>
                              </tr>
                              <tr>
                                <td><div align=center><span class=b>$title</span></div></td>
                              </tr>
                          </table></td>
                          $add_td
			";

			$num1++;

		}

		echo $content;

	}

	// 탑 노티스
	function printTopNotice($table_id){
		
		global $dbConn;
		if(($table_id == "notice") || ($table_id == "story")) {
			$qry1 = "select * from chan_board where tablename = '$table_id' and front_yn='N' order by seq_no desc limit 4";
	    } else {
			$qry1 = "select * from chan_board where tablename = '$table_id'  order by seq_no desc limit 4";
		}
		$rst1 = mysql_query($qry1,$dbConn);


		$num1 = 0;

		while($row1 = mysql_fetch_assoc($rst1)){



			$title = Misc::cutLongString($row1['title'], 30, $dot=true);

			if($table_id == "notice")
			{
				$flag = "[공지사항]";
			}
			else
			{
				$flag = "[여행후기]";
			}

			$content .= "<div style='width:300px; height:25px;'>$flag $title</div>";

			$num1++;

		}

		if($num1 == "0")
		{
			$content = "<div style='width:300px; height:25px;'>&nbsp;&nbsp;</div>";
		}

		echo $content;

	}

	// 메인 노티스
	function printNotice($table_id){
		
		global $dbConn;
		if($table_id == "notice") {
		  $qry1 = "select * from chan_board where tablename = '$table_id' and front_yn='N' order by seq_no desc limit 4";
		 // echo $qry1;
		} else {

		  $qry1 = "select * from chan_board where tablename = '$table_id'  order by seq_no desc limit 4";
		}
		$rst1 = mysql_query($qry1,$dbConn);


		$num1 = 0;

		while($row1 = mysql_fetch_assoc($rst1)){

			$today = explode(" ",$row1['wdate']);

			$yesterday1 = date("Y-m-d H:i:s",time()-86400);
			if($row1['wdate'] > $yesterday1)
				{
				$new_icon = "<img src='./img/New2.gif'>";
				}
			else
				{
				$new_icon = "&nbsp;";
				}

			$title = Misc::cutLongString($row1['title'], 30, $dot=true);

			$content .= "
                          <tr>
                            <td height=25><table width=100% border=0 cellspacing=0 cellpadding=0>
                                <tr>
                                  <td><img src=images/comm/blt_list.gif width=12 height=11 align=absmiddle></td>
                                  <td><a href=board_view.php?board_mode=view&table_id=$table_id&division=&no={$row1['seq_no']}&start=0&Mode=&how=&S_content=><span class=stxt><font color=black>$title</font></span></a> $new_icon</td>
                                  <td align=right><span class=stxt>$today[0]</span></td>
                                </tr>
                            </table></td>
                          </tr>
                          <tr>
                            <td height=1 background=images/comm/line_dot02.gif></td>
                          </tr>
			";

			$num1++;

		}

		echo $content;

	}

	function printBestTour($flag,$start,$stop){
		
		global $dbConn;

		$qry1 = "select * from main_display where view_position = '$flag' order by pos asc limit $start,$stop";
		$rst1 = mysql_query($qry1,$dbConn);

		$num = "1";

		echo "<table border=0 cellspacing=0 cellpadding=0><tr><td width=10></td>";


		while($row1 = mysql_fetch_assoc($rst1))
		{


		// 상품정보 가져오기
		$productInfor = getProductMaster($row1['p_code']);

		// 출발일 관리
		$week1 = array("0", "1", "2","3","4","5","6","9","/");
		$week2   = array("일","월", "화", "수","목","금","토","매일","&nbsp;");
		$productInfor['startWeek'] = str_replace($week1, $week2, $productInfor['startWeek']);



		if($productInfor['userfile1'])
		{
			$mainImg = "<img src=".UPLOAD_URL."{$productInfor['userfile1']} width=132 height=84 border=0 class=\"quote\">";
		}
		else
		{
			$mainImg = "<img src=img/no_image_thumb.gif>";
		}



		if($productInfor['display_price'])
		{
			$priceMsg = "{$productInfor['display_price']}";
		}
		else
		{
			$today = date("Y-m-d");

			if($today >= $productInfor['eventStart'] && $today <= $productInfor['eventStop'])
			{
				$adult_price = number_Format($productInfor['event_adult_price']);
				$child_price = number_Format($productInfor['event_child_price']);
				$baby_price = number_Format($productInfor['event_baby_price']);

				$eventMsg = "[이벤트]";
			}
			else
			{
				$adult_price = number_Format($productInfor['web_adult_price']);
				$child_price = number_Format($productInfor['web_child_price']);
				$baby_price = number_Format($productInfor['web_baby_price']);

				$eventMsg = "";
			}

			$priceMsg = "성인 : $$adult_price ";
		}		

		$productInfor['p_name'] = Misc::cutLongStringWithTag($productInfor['p_name'],12,true);

		if($num == "4")
			{
				$right_line = "";
			}
		else
			{
				$right_line = "<td width=1 background=images/comm/line_dot01.gif></td>";
			}

		$link = "detail.php?division={$productInfor['c_code2']}&c_code1={$productInfor['c_code3']}&p_code={$productInfor['p_code']}";

		echo "
					  <td width=160><div align=center>
                          <table width=138 border=0 cellspacing=0 cellpadding=0>
                            <tr>
                              <td><table width=138 border=0 cellpadding=0 cellspacing=3 bgcolor=#ebebeb>
                                  <tr>
                                    <td height=84 bgcolor=#FFFFFF align=center><a href=$link>$mainImg</a></td>
                                  </tr>
                              </table></td>
                            </tr>
                            <tr>
                              <td height=8></td>
                            </tr>
                            <tr>
                              <td>{$productInfor['p_name']}</td>
                            </tr>
                            <tr>
                              <td class=green_b>$priceMsg</td>
                            </tr>
                            <tr>
                              <td class=orange>출발 : {$productInfor['startWeek']}</td>
                            </tr>
                          </table>
                      </div></td>
                      $right_line
			";
		
		$num++;

		}

		echo "</tr></table>";
	}


	function getPickamt($tourCode){
		
		global $dbConn;

		$qry1 = "select * from tour_schedule where tourCode = '$tourCode' group by reserveCode";
		
		$rst1 = mysql_query($qry1);

		$sum = 0;

		while($row1 = mysql_Fetch_assoc($rst1)){
			
			// 현지수금할 금액 
			$p_qry1 = "select balance from reserve_info where reserveCode = '{$row1['reserveCode']}'";
			$p_rst1 = mysql_query($p_qry1);
			$p_result = @mysql_result($p_rst1,0,0);
			
			$sum = $sum + $p_result;
		}

		return $sum;

	}


	function getGuideBalance($guide){
		
		global $dbConn;

		// debit 합계
		$qry1 = "select sum(amt) from guide_payment_history where guide_id = '$guide' order by wdate asc";
		$rst1 = mysql_query($qry1);
		$amt = @mysql_result($rst1,0,0);

		return $amt;
	}


    

	function getBalance($eCode){
		
		global $dbConn;

		// debit 합계
		$qry1 = "select sum(amt) from payment_history where division = 'debit' && reserveCode = '$eCode' order by wdate asc";
		$rst1 = mysql_query($qry1);
		$debit = @mysql_result($rst1,0,0);

		// credit 합계
		$qry2 = "select sum(amt) from payment_history where division = 'credit' && status = 'DONE' && reserveCode = '$eCode' order by wdate asc";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		$total = $debit - $credit;

		return $total;
	}
    
	function RtngetBalance($eCode){
		
		global $dbConn;

		// debit 합계
		$qry1 = "select sum(amt) from rtnpayment_history where division = 'debit' && reserveCode = '$eCode'  order by wdate asc";
		$rst1 = mysql_query($qry1);
		$debit = @mysql_result($rst1,0,0);

		// credit 합계
		$qry2 = "select sum(amt) from rtnpayment_history where division = 'credit'  && reserveCode = '$eCode'  order by wdate asc";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		$total = $debit - $credit;

		return $total;
	}
	function RtngetBalance1($eCode){
		
		global $dbConn;

		// debit 합계
		$qry1 = "select sum(amt) from rtnpayment_history where division = 'debit' && reserveCode = '$eCode' && status='APPROVE' order by wdate asc";
		$rst1 = mysql_query($qry1);
		$debit = @mysql_result($rst1,0,0);

		// credit 합계
		$qry2 = "select sum(amt) from rtnpayment_history where division = 'credit'  && reserveCode = '$eCode' && status='APPROVE' order by wdate asc";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		$total = $debit - $credit;

		return $total;
	}
	function RtngetBalance2($eCode){
		
		global $dbConn;

		

		// credit 합계
		$qry2 = "select count(amt) from rtnpayment_history where  reserveCode = '$eCode'  order by wdate asc";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		$total = $credit;

		return $total;
	}
	function Rtngetprofit($eCode){
		
		global $dbConn;

		
		// credit 합계
		$qry2 = "select sum(air_fee) from rtnpayment_history where division = 'credit'  && reserveCode = '$eCode' order by wdate asc";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		$total = $credit;

		return $total;
	}
	function RtnGetStatus($eCode){
		
		global $dbConn;

	
		$qry1 = "select status from rtnpayment_history where  reserveCode = '$eCode' ";
		$rst1 = mysql_query($qry1);
		while($row1 = mysql_fetch_assoc($rst1)){

              if ($row1['status'] == "REQUEST") {

                   $status = $row1['status'];

			  } else {
				  $status = $row1['status'];

			  }

		}
		//echo $status."test";


		return $status;
	}

	function RtnGetType($eCode){
		
		global $dbConn;

	
		$qry1 = "select refund_type from rtnpayment_history where  reserveCode = '$eCode' ";
		$rst1 = mysql_query($qry1);
		while($row1 = mysql_fetch_assoc($rst1)){

            	  $rtype = $row1['refund_type'];

	    }

		
		//echo $status."test";


		return $rtype;
	}

 	function getInputAmt($eCode){
		
		global $dbConn;

		// credit 합계
		$qry2 = "SELECT sum(cur_balamt) FROM  `rand_account_history` WHERE reserveCode='$eCode'";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		//echo $qry2;
		return $credit;
	}
	
	function getInputAmt_hist($eCode,$seq){
		
		global $dbConn;

		// credit 합계
		$qry2 = "SELECT sum(cur_balamt) FROM  `rand_account_historyhist` WHERE reserveCode='$eCode' && seq_hist='$seq'";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		//echo $qry2;
		return $credit;
	}


	function getCategory($c_code1,$c_code2,$c_code3 = false){
		
		global $dbConn;

		if($c_code3)
		{
			$c_code3_qry = "&& lvcode3 = '$c_code3'";
		}

		$qry1 = "select * from code_base where lvcode1 = '$c_code1' && lvcode2 = '$c_code2' $c_code3_qry";
		
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;

	}
    function getCategory_new($c_code1,$code1){
		
		global $dbConn;

		
		$c_code3_qry = "&& lvcode3 = '00'";

		$qry1 = "select * from code_base where lvcode1 = '$c_code1' && lvcode2 <> '00' $c_code3_qry";

		$rst1 = mysql_query($qry1);
		
        while($row1 = mysql_fetch_assoc($rst1)){
			
			    $selectValueInput = $row1['lvcode2'];
//echo $code1 ."|".$selectValueInput;
				if($code1 == $selectValueInput)
				{
					$content .= "<option value=$selectValueInput selected>{$row1['comment']}";		
				}
				else
				{
					$content .="<option value=$selectValueInput>{$row1['comment']}";		
				}
			
		}

		return $content;

	}
	function getCategory1($c_code1,$c_code2,$c_code3 ){
		
		global $dbConn;

		if($c_code3)
		{
			$c_code3_qry = "&& lvcode3 = '$c_code3' && lvcode4 = '00'";
		} else {
			$c_code3_qry = "&& lvcode3 = '00' && lvcode4 = '00'";
		}

		$qry1 = "select * from code_base where lvcode1 = '$c_code1' && lvcode2 = '$c_code2' $c_code3_qry";
	
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getPaySummary($estimateCode){

		global $dbConn;

		$qry1 = "select * from reserve_pay_summary where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	
	
	
	function getPaySummaryEmp($p_code,$emp,$date1,$date2,$gb){

		global $dbConn;
    if ($gb ==1) {
    	$qrygb = " && a.start_date between '$date1' and '$date2'";
    }
    if ($gb ==2) {
    	$qrygb = " && a.reserve_date between '$date1' and '$date2'";
    }
		$qry1 = "select sum(b.last_sum_amt) as last_sum_amt,sum(b.sum_airline) as sum_airline ,sum(b.sum_cruise) as sum_cruise from reserve_info a,reserve_pay_summary b
     where a.reserveCode = b.reserveCode && a.register='$emp'  
    && p_code ='$p_code' $qrygb ";
    
    
   // echo $qry1;
    ///exit;
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getPaySummary_hist($estimateCode,$seq){

		global $dbConn;

		$qry1 = "select * from reserve_pay_summaryhist where reserveCode = '$estimateCode' && seq_hist='$seq' ";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getLocalinfo($estimateCode,$play_day){

		global $dbConn;

		$qry1 = "select * from reserve_local where reserveCode = '$estimateCode' && play_day = '$play_day'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getTicketinfo($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_ticket where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	
	function getTicketinfo_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_ticket_hist where reserveCode = '$estimateCode' && seq_hist='$seq' ";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getMealinfo($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_meal where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	
	function getCouponinfo($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from coupons_tab where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getMealinfo_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_meal_hist where reserveCode = '$estimateCode' && seq_hist='$seq' ";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getEtcinfocheck($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_etc where reserveCode = '$estimateCode' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	
	function getEtcinfocheck_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_etc_hist where reserveCode = '$estimateCode' && seq_hist='$seq' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getExpinfocheck($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_exp where reserveCode = '$estimateCode' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getExpinfocheck_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_exp_hist where reserveCode = '$estimateCode' && seq_hist='$seq'  limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getPickinfo($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_pickup where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
        //echo $qry1;
		return $row1;

	}


	function getPickinfo_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_pickup_hist where reserveCode = '$estimateCode' && seq_hist='$seq' ";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getSendinfo($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_sending where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	
	function getSendinfo_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_sending where reserveCode = '$estimateCode' && seq_hist='$seq' ";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	
	function getAirlineinfohist($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_airline_pnrhist2 where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
    //echo $qry1;
		return $row1;

	}

  function getCruiseinfo($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_cruise where reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getCruiseinfohist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_cruise_hist where reserveCode = '$estimateCode' && seq_hist='$seq'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
		
	function getAirlineinfo_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_airline_pnrhist2 where seq_hist='$seq' && reserveCode = '$estimateCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getHotelinfocheck($estimateCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_hotel where reserveCode = '$estimateCode' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	
	
	function getHotelinfocheck_hist($estimateCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_hotel_hist where seq_hist='$seq' && reserveCode = '$estimateCode' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getProductDetails($p_code,$day){
		
		global $dbConn;

		$qry1 = "select * from product_details where p_code = '$p_code' && day = '$day'";
		//print_r($qry1);
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
    function getProductDetails_new($p_code,$day){
		
		global $dbConn;

		$qry1 = "select * from product_details_new where p_code = '$p_code' && day = '$day'";
		//print_r($qry1);
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}



	

	
	function getReserveInfohist($rCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_info_hist2 where reserveCode = '$rCode' && parent = 'MAIN' && seq_hist='$seq'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
        
		return $row1;

	}
	function getReserveRoom($rCode){
		
		global $dbConn;

		$qry1 = "select room_cnt from reserve_info where reserveCode = '$rCode' && parent = 'MAIN'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
       // print_r($qry1."<br>");
       // exit;
		return $row1;

	}
		function getReserveBus($rCode){
		
		global $dbConn;

		$qry1 = "select * from busreserve_info where reserveCode = '$rCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
        //print_r($qry1);
		return $row1;

	}
	
	function getReserveBus_hist($rCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from busreserve_info_hist where reserveCode = '$rCode' && seq_hist='$seq'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
        //print_r($qry1);
		return $row1;

	}
  function getReserveInfo_hist($rCode,$seq){
		
		global $dbConn;

		$qry1 = "select * from reserve_info_hist where reserveCode = '$rCode' && parent = 'MAIN' && seq_hist='$seq'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
        //print_r($qry1);
		return $row1;

	}

	function getSettleInfo($sCode){
		
		global $dbConn;

		$qry1 = "select * from settle_info where settleCode = '$sCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}


	function getReserveInfosearch($content){

		global $dbConn;

		//|| member_phone like '%$content%'
		$qry1 = "select * from reserve_info where reserveCode like '%$content%' && parent = 'MAIN' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}


	function getReserveInfobypcode($rCode,$pCode){
		
		global $dbConn;

		$qry1 = "select * from reserve_info where reserveCode = '$rCode' && p_code = '$pCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	
	function getBusReserveInfobypcode($rCode,$pCode){
		
		global $dbConn;

		$qry1 = "select * from busreserve_info where reserveCode = '$rCode' && p_code = '$pCode'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}


	function printBusList2($bus_id = false){
		
		global $dbConn;

		$qry1 = "select * from bus_list order by seq_no asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			$bus_team = codebasename($row1['bus_team']);

			if($bus_id == $row1['bus_id'])
			{
				$content .= "<option value={$row1['bus_id']} selected>{$bus_team['comment']} ({$row1['bus_number']})";
			}
			else
			{
				$content .= "<option value={$row1['bus_id']}>{$bus_team['comment']} ({$row1['bus_number']})";
			}
			
		}

		return $content;
	}


	

	function printHotelRoom($h_code, $room_code = false){

		global $dbConn;

		$qry1 = "select * from hotel_price where h_code = '$h_code' order by room_type asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($room_code == $row1['seq_no'])
			{
				$content .= "<option value={$row1['seq_no']} selected>{$row1['room_type']}";
			}
			else
			{
				$content .= "<option value={$row1['seq_no']}>{$row1['room_type']}";
			}
			

		}

		return $content;

	}

	function getHotelChoose_solo(){

		global $dbConn;
		
		$zip_qry1 = "select 
								A.p_code,
								A.p_name,
								B.room_type,
								B.room_price,
								B.seq_no
							from 
								hotel_master as A left join hotel_price as B on A.p_code = B.h_code order by B.seq_no desc";

		//print_r($zip_qry1);
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);
		
		while($zip_row1 = mysql_fetch_assoc($zip_rst1))
		{
			$content .= "<option value='{$zip_row1['p_code']}/{$zip_row1['seq_no']}/{$zip_row1['room_price']}/{$zip_row1['room_type']}'>{$zip_row1['p_code']} - {$zip_row1['room_type']} (${$zip_row1['room_price']})";
		}

		return $content;
	}



	function getHotelChoose($h_code,$StartYMD,$room_code = false){

		global $dbConn;
		
		$zip_qry1 = "select 
								*
							from 
								hotel_master as A where view_opt='YES'  order by A.p_code asc";

		
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);
		
		while($zip_row1 = mysql_fetch_assoc($zip_rst1))
		{
			
			$content .= "<option value=\"{$zip_row1['p_code']}/{$zip_row1['seq_no']}\"> {$zip_row1['p_code']} - {$zip_row1['p_name']}";
		

		}

		return $content;
	}
	function getHotelarea1($h_area){

		global $dbConn;
		
		$zip_qry1 = "select 
								*
							from 
								code_base as A where lvcode1='H02' && lvcode2 <> '00' && lvcode3 = '00'  order by lvcode2 asc";

		
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);
		
		while($zip_row1 = mysql_fetch_assoc($zip_rst1))
		{
			$code = $zip_row1['lvcode1'].$zip_row1['lvcode2'];
			
			if ($h_area == $code) {
			     $content .= "<option value='$code' selected> $code- {$zip_row1['comment']}";
			} else {
				 $content .= "<option value='$code'> $code - {$zip_row1['comment']}";

			}
		

		}

		return $content;
	}
	function getHotelarea2($h_area1,$h_area2){

		global $dbConn;
		$lvcode2 = substr($h_area1,3,2);
		$zip_qry1 = "select 
								*
							from 
								code_base as A where lvcode1='H02' && lvcode2 = '$lvcode2' && lvcode3 <> '00'  order by lvcode2 asc";

		
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);
		
		while($zip_row1 = mysql_fetch_assoc($zip_rst1))
		{
			$code = $zip_row1['lvcode1'].$zip_row1['lvcode2'].$zip_row1['lvcode3'];
			if ($h_area2 == $code) {
			     $content .= "<option value='$code' selected> $code- {$zip_row1['comment']}";
			} else {
				 $content .= "<option value='$code'> $code - {$zip_row1['comment']}";

			}
		

		}

		return $content;
	}
    function getHotelbasearea1($h_area,$day){

		global $dbConn;
		
		$zip_qry1 = "select 
								*
							from 
								code_base as A where lvcode1='H02' && lvcode2 <> '00' && lvcode3 = '00'  order by lvcode2 asc";

		
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);
		//echo $zip_qry1 ;
		while($zip_row1 = mysql_fetch_assoc($zip_rst1))
		{
			$code = $zip_row1['lvcode1'].$zip_row1['lvcode2'];
			if ($h_area == $code) {
			     $content .= "<option value=\"$code\" selected> $code- {$zip_row1['comment']}";
			} else {
				 $content .= "<option value=\"$code\"> $code - {$zip_row1['comment']}";

			}
		

		}

		return $content;
	}
	function getHotelbasearea2($h_area1,$h_area2,$day2){

		global $dbConn;
		$lvcode2 = substr($h_area1,3,2);
		$zip_qry1 = "select 
								*
							from 
								code_base as A where lvcode1='H02' && lvcode2 = '$lvcode2' && lvcode3 <> '00'  order by lvcode2 asc";

		
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);
		
		while($zip_row1 = mysql_fetch_assoc($zip_rst1))
		{
			$code = $zip_row1['lvcode1'].$zip_row1['lvcode2'].$zip_row1['lvcode3'];
			if ($h_area2 == $code) {
			     $content .= "<option value=\"$code\" selected> $code- {$zip_row1['comment']}";
			} else {
				 $content .= "<option value=\"$code\"> $code - {$zip_row1['comment']}";

			}
		

		}

		return $content;
	}
	function getHotelDetail($h_code,$r_code){

		global $dbConn;
		
		$zip_qry1 = "select 
								A.p_code,
								A.p_name,
								B.room_type,
								B.room_price,
								B.seq_no
							from 
								hotel_master as A left join hotel_price as B on A.p_code = B.h_code
							where 
								A.p_code = '$h_code' && B.seq_no = '$r_code'";

		//print_r($zip_qry1);
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);
		$zip_row1 = mysql_fetch_assoc($zip_rst1);
		
		return $zip_row1;
	}


	

	function getHotelReserveSelfinfo($cCode){
		
		global $dbConn;

		$qry1 = "select * from hotel_self_info where reserveCode = '$cCode'";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getHotelinfo($h_code){

		global $dbConn;

		$qry1 = "select * from hotel_master where p_code = '$h_code'";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;
	}
	

	function getHotelroominfo($h_code){

		global $dbConn;

		$qry1 = "select * from hotel_price where seq_no = '$h_code'";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;
	}

	function getTourinfo($rCode){
		
		global $dbConn;

		$qry1 = "select * from tour_schedule where reserveCode = '$rCode'";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;
	}

	function getHinfor($h_code){
		
		global $dbConn;

		$qry1 = "select * from tour_schedule where tourCode = '$h_code' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;
	}

	

	// 숙박예약 최근 번호 가져오기
	function getNumHotelReserveSelf(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(reserveNum) from hotel_self_info where wdate between '$start_date' and '$stop_date'";
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}
	
	// 버스예약 최근 번호 가져오기
	function getNumBusReserve(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(reserveNum) from busreserve_info where wdate between '$start_date' and '$stop_date'";
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}


	// 차량배치 최근 번호 가져오기
	function getNumCarBatch(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(batchNum) from car_schedule where wdate between '$start_date' and '$stop_date'";
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}

	// 호텔배치 최근 번호 가져오기
	function u(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(batchNum) from hotel_schedule where wdate between '$start_date' and '$stop_date'";
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}



	// 행사 코드 최근 번호 가져오기
	function getNumTour(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(tourNum) from tour_schedule where wdate between '$start_date' and '$stop_date'";
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}
	// 행사 코드 최근 번호 가져오기
	function getNumPicSend(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(tourNum) from picksend_schedule where wdate between '$start_date' and '$stop_date'";
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}


	// 상담 최근 번호 가져오기
	function getNumConsult(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(consultNum) from consult_info where wdate between '$start_date' and '$stop_date'";
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
			
		}
		else
		{
			$num1 = "001";
		}

		return $num1;
	}
    
	


	// 정산 최근 번호 가져오기
	function getNumSettle(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(settleNum) from settle_info where wdate between '$start_date' and '$stop_date'";
		//print_r($qry1);
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}
		}
		else
		{
			$num1 = 1;
		}

		return $num1;
	}


	// 상품 번호 가져오기
	function getPnumber(){
		
		global $dbConn;

		$qry1 = "select max(num) from product_master";
		//print_r($qry1);
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0) + 1;

			if(strlen($rNum) == "1")
			{
				$num1 = "00".$rNum;
			}
			elseif(strlen($rNum) == "2")
			{
				$num1 = "0".$rNum;
			}
			else
			{
				$num1 = $rNum;
			}

			$numInt = $rNum;
		}
		else
		{
			$num1 = "001";

			$numInt = "1";
		}

		$num['num'] = $numInt;
		$num['numChar'] = $num1;

		return $num;
	}


	


	// 메뉴접근
	function printLeftMenu($division,$userid,$pdx,$sub){
	
		global $dbConn;

		$qry1 = "select * from menu_info_user where division='$division' && sub_idx='0' && userid='$userid' order by parent_idx asc";
		$rst1 = mysql_query($qry1);
		$i=0;
		
		while($row1 = mysql_fetch_assoc($rst1)){
			$content  = "";
			if (($pdx == $row1['parent_idx']) && ($division == $division)) {
				$in = "in";
			} else {
				$in = "";
			}
		    $content .= "<div class='panel-heading'>
							<a href='#C$i' data-parent='#side_accordion' data-toggle='collapse' class='accordion-toggle' >
								<i class='glyphicon glyphicon-folder-close'></i> {$row1['menu_name']}
							</a>
						</div>
						<div class='accordion-body collapse $in' id='C$i'>
								<div class='panel-body'>
									<ul class='nav nav-pills nav-stacked'>";
			$qry2 = "select * from menu_info_user where division='$division' && parent_idx='{$row1['parent_idx']}' && sub_idx!='0' && userid='$userid' order by parent_idx,sub_idx asc";

		    $rst2 = mysql_query($qry2);
			
			while($row2 = mysql_fetch_assoc($rst2)){
				    if (($pdx == $row2['parent_idx']) && ($sub == $row2['sub_idx'])) {
						$class = "class='active'";
					} else {
						$class = "";
					}
					$content .="<li $class><a href='{$row2['menu_link']}'>{$row2['menu_name']}</a></li>";
								
			}
			$content .="</ul>
						</div>
					</div>
					";
			

			echo "<div class='panel panel-default'>
			      $content
			     </div>";

			$i++;

		}


	}
	// 메뉴접근
	function printLeftMenu_b($division,$userid,$pdx,$sub,$table_id){
	
		global $dbConn;

		$qry1 = "select * from menu_info_user where division='$division' && sub_idx='0' && userid='$userid' order by parent_idx asc";
		$rst1 = mysql_query($qry1);
		$i=0;
		
		while($row1 = mysql_fetch_assoc($rst1)){
			$content  = "";
			if (($pdx == $row1['parent_idx']) && ($division == $division)) {
				$in = "in";
			} else {
				$in = "";
			}
		    $content .= "<div class='panel-heading'>
							<a href='#C$i' data-parent='#side_accordion' data-toggle='collapse' class='accordion-toggle' >
								<i class='glyphicon glyphicon-folder-close'></i> {$row1['menu_name']}
							</a>
						</div>
						<div class='accordion-body collapse $in' id='C$i'>
								<div class='panel-body'>
									<ul class='nav nav-pills nav-stacked'>";
			$qry2 = "select * from menu_info_user where division='$division' && parent_idx='{$row1['parent_idx']}' && sub_idx!='0' && userid='$userid' order by parent_idx,sub_idx asc";

		    $rst2 = mysql_query($qry2);
			
			while($row2 = mysql_fetch_assoc($rst2)){
					$ids = substr($row2['menu_link'],48,2);
					//echo $ids."|".$row2[parent_idx]."|".$row2[sub_idx]."<br>";
				    if (($pdx == $row2['parent_idx']) && ($sub == $row2['sub_idx']) && ($table_id == $ids)) {
						$class = "class='active'";
						
					} else {
						$class = "";
					}
					$content .="<li $class><a href='{$row2['menu_link']}'>{$row2['menu_name']}</a></li>";
								
			}
			$content .="</ul>
						</div>
					</div>
					";
			

			echo "<div class='panel panel-default'>
			      $content
			     </div>";

			$i++;

		}


	}
	
	


		// AJAX 용 카테고리 가져오기
		function printBaseCodeHotel_ajax($c_code1){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = 'H02' && lvcode2 <> '00' && lvcode3 = '00' order by lvcode1,lvcode2,lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValueInput = $row1['lvcode2'];

				if($c_code1 == $selectValueInput)
				{
					echo "<option value=$selectValueInput selected>{$row1['comment']}";		
				}
				else
				{
					echo "<option value=$selectValueInput>{$row1['comment']}";		
				}
					

			}
		}

		function printBaseCodeHotel_ajax2($c_code1,$c_code2){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = 'H02' && lvcode2 = '$c_code1' && lvcode3 <> '00' order by lvcode1,lvcode2,lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValueInput = $row1['lvcode3'];

				if($c_code2 == $selectValueInput)
				{
					$content .="<option value=$selectValueInput selected>{$row1['comment']}";		
				}
				else
				{
					$content .= "<option value=$selectValueInput>{$row1['comment']}";		
				}
					

			}
			return $content;
		}

		
		
   function productLimit($pCode){
		
		global $dbConn;

		$regi_qry1 = "select limit_date  from `product_limitdate` where p_code = '$pCode'";
		$regi_rst1 = mysql_query($regi_qry1);
		$i = 1;
		
		while($r_row = mysql_fetch_assoc($regi_rst1)) 
		{
		  if ($i==1) 
		  {	
		  	
			   $content .= $r_row['limit_date'];
			  
		  } 
		  else 
		  {
		  	 $content .= "|{$r_row['limit_date']}";
		  	
		  }
			$i++;
		//	print_r($regi_qry1);
		}

		return $content;
	}

	
	

	
		

	

		function print_thumimg($image){
			
			global $img_url,$itemCode;

			$photo_arr = explode("NaN",$image);

			$total_tr = ceil(count($photo_arr)/3);

			$k = 0;
			for($m=1; $m<=$total_tr; $m++)
			{
				echo "<tr>";

					for($p=0; $p<3; $p++)
					{

						if($photo_arr[$k])
						{
						echo "
								  <td width=80 valign=top><table width=100% border=0 cellpadding=0 cellspacing=1 bgcolor=eeeeee>
									  <tr> 
										<td align=center valign=middle bgcolor=#FFFFFF ><a href=\"javascript:pic_viewnow('$photo_arr[$k]')\"><img src=\"./thum_upload/thum_".$photo_arr[$k]."\" border=1 style='border-color=#CCCCCC' width=75 height=75></a></td>
									  </tr>
									</table></td>";
						}

						$k++;
					}

				echo "</tr>";
			}
		
		}

		function getinfo_dbPick_bycode($id){

			global $dbConn;

			$qry1 = "select * from base_pick where pick_m = 'M' && pick_code='$id'";
			$rst1 = mysql_query($qry1,$dbConn);
			$row1 = mysql_fetch_assoc($rst1);
			
		return $row1;
	    }
		/**
		* 상품 사진 가져오기
		*/
		function get_firstpic($images){
		
			$photo_arr = explode("NaN",$images);

			return $photo_arr[0];
		}

		// 파일업로드 확장자 바꾸지 않음
		function uploadFileUnsafely($attachFile, $attachFileName, $saveDir = '.'){
			
			$saveDir = ereg_replace("/$", "", $saveDir);
			$saveDir .= '/';
			
			/*
			$ext = strrchr($attachFileName, '.');
			$tName = substr($attachFileName, 0, strlen($attachFileName) - strlen($ext));
			$saveFileName = $tName . $ext;
			$i = 0;
			while (file_exists($saveDir . $saveFileName)) {
				$i++;
				$saveFileName =  $tName . $i . $ext;
			}
			*/
			
			$saveFileName = $attachFileName;

			if(!is_dir($saveDir)){	// 파일 저장디렉토리가 존재하지 않으면
				@mkdir($saveDir, 0755);
			}
			
			move_uploaded_file($attachFile, $saveDir . $saveFileName);
			$_rf = remote_detect_folder($saveDir); if ($_rf) remote_sync_file($saveDir . $saveFileName, $_rf);
			chmod($saveDir . $saveFileName, 0744);
			$attc['size'] = filesize($saveDir . $saveFileName);		//byte 
			$attc['savedName'] = $saveFileName;		// 저장되는 파일 이름
			$attc['upName'] = $attachFileName;		// 업로드시 파일네임
	   
			return $attc;
		} 

		function resize_image($destination, $departure, $size, $quality='80', $ratio='false'){ 

			if($size[2] == 1)    //-- GIF 
				$src = imageCreateFromGIF($departure); 
			elseif($size[2] == 2) //-- JPG 
				$src = imageCreateFromJPEG($departure); 
			else    //-- $size[2] == 3, PNG 
				$src = imageCreateFromPNG($departure); 

			$dst = imagecreatetruecolor($size['w'], $size['h']); 


			$dstX = 0; 
			$dstY = 0; 
			$dstW = $size['w']; 
			$dstH = $size['h']; 

			if($ratio != 'false' && $size['w']/$size['h'] <= $size[0]/$size[1]){ 
				$srcX = ceil(($size[0]-$size[1]*($size['w']/$size['h']))/2); 
				$srcY = 0; 
				$srcW = $size[1]*($size['w']/$size['h']); 
				$srcH = $size[1]; 
			}elseif($ratio != 'false'){ 
				$srcX = 0; 
				$srcY = ceil(($size[1]-$size[0]*($size['h']/$size['w']))/2); 
				$srcW = $size[0]; 
				$srcH = $size[0]*($size['h']/$size['w']); 
			}else{ 
				$srcX = 0; 
				$srcY = 0; 
				$srcW = $size[0]; 
				$srcH = $size[1]; 
			} 

			@imagecopyresampled($dst, $src, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH); 
			@imagejpeg($dst, $destination, $quality); 
			@imagedestroy($src); 
			@imagedestroy($dst); 

			return TRUE; 
		} 

		
	/**
	* @ 멤버 로그인 펑션
	*/
	function Member_login($user_id,$password,$division = false){
		
		global $dbConn,$c_domain;


		$qry1 = "select * from member_list where userid = '$user_id' && passwd = '$password' && division = '$division' && out_yn is null ";

		if($division == "admin" || $division == "guide")
		{
			$cookie_name = "MEMLOGIN_ADMIN_PURUN";
		}
		else
		{
			$cookie_name = "MEMLOGIN_ADMIN_PURUN";
		}
		

		$rst1 = mysql_query($qry1,$dbConn);

		if(mysql_num_rows($rst1) <= 0)
		{
			
			
			$Result = 0;
		}
		else
		{
			$row1 = mysql_fetch_assoc($rst1);

			// 로그인 정보가 있다면... 이 사람의 정보로 쿠키를 굽니다.
			$login_info = array(sitename => "puruntouradmin1", user_id => "$user_id", user_pw => "$password", user_level => $row1['level'], division => $row1['division']);
			$login_info = base64_encode(serialize($login_info));
			
			SetCookie("MEMLOGIN_ADMIN_PURUN",$login_info,0,'/');

			
			
			//exit;
			$Result = 1;
		}

		// 성공/실패 결과 리턴
		return $Result;
	}

	
	/**
	* @ 로그인 쿠키로 개인정보 뽑아오기
	*/
	function getinfo_Member($user_info){
		
		$user_info = unserialize(base64_decode($user_info));
		
		return $user_info;

	}

	

	/**
	* @ 아이디로 Expire date
	*/
	function getinfo_dbExMember($user_info){
		
		global $dbConn;

		$qry1 = "select * from member_list where (division= 'admin'  or division= 'guide') and  userid = '$user_info'";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
	//	echo $qry1;
	//	exit;
		return $row1;

	}
   
	function getinfo_dbMember_byid($id){

		global $dbConn;

		$qry1 = "select * from member_list where seq_no = '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		
		return $row1;
	}
	
	function update_infolog($user_info,$cnt){

		global $dbConn;

		$qry1 = "update member_list set log_cnt = '$cnt'  where userid = '$user_info'";
		$rst1 = mysql_query($qry1);
	
	}
	
	function update_infoinit($user_info){

		global $dbConn;

		$qry1 = "update member_list set log_cnt = '0',passwd = 'purun1',expire_date= now()  where userid = '$user_info'";
		$rst1 = mysql_query($qry1);
		
	
	
	}
	
	
	function update_infoinit2($user_info){

		global $dbConn;

		$qry1 = "update member_list set log_cnt = '0',login_date=now() where userid = '$user_info'";
		$rst1 = mysql_query($qry1);
		
	
	
	}



	function getinfo_db_byid($tbl, $fieldid, $id) {
		global $dbConn;

		$qry1 = "select * from $tbl where $fieldid = '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		
		return $row1;
	}



	
  function getinfo_menufst($id,$divi){

		global $dbConn;

		$qry1 = "select * from menu_info_user  where division= '$divi' && userid='$id' order by seq_no";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		
		return $row1;
	}
   function printMenu($id){

		global $dbConn;

		$qry1 = "select * from menu_info_user  where division= 'm' && userid='$id' order by seq_no";
		$rst1 = mysql_query($qry1,$dbConn);
		//echo $qry1;
		while($row1 = mysql_fetch_assoc($rst1))
		{
			
				$content .= "<li class='dropdown'><a href='/".$row1['menu_link']."'><span class='{$row1['menu_icon']}'></span> {$row1['menu_name']}</a></li>";
			
		}

		return $content;
	}
	function printSetMenu(){

		global $dbConn;

		$qry1 = "select * from menu_info where division= 'm'";
		$rst1 = mysql_query($qry1,$dbConn);
		//echo $qry1;
		while($row1 = mysql_fetch_assoc($rst1))
		{
			
				//$content .= "<li id='nav-1'><a href=$row1[menu_link] title=''>$row1[menu_name]</a></li>";
				$content .= "<option value={$row1['menu_name']} selected>{$row1['menu_name']}";
		}

		return $content;
	}
	
	

	
		function printBaseCode_5($lvcode1,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' order by lvcode2 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'];

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}

		function printBaseCode_5_direct($lvcode1,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' order by lvcode2 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'];

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}

		function printBaseCode_7($lvcode1,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' order by lvcode2 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}

		function printBaseCode($lvcode1,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' order by lvcode2 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];

				if($selectValue == $code || $selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}

		function printBaseCode2($lvcode1,$lvcode2,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' order by lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}
    
    
    function printBaseCode9($lvcode1,$lvcode2,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = 'T04' && lvcode3 <> '00' && lvcode4 = '00' order by lvcode2 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];
				//$selectValueInput = $row1[lvcode1].$row1[lvcode2].$row1[lvcode3];

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}
    
		function printBaseCode3($lvcode1,$lvcode2,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' order by lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);
		//	echo $qry1;
			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}
		function printBaseCode4($lvcode1){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' order by lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);
		//	echo $qry1;
			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				}
				else
				{
					$option.= "<option value=$selectValueInput>{$row1['comment']}";
				}
				

			}

			return $option;

		}

        
		function printBaseCode2_special($lvcode1,$lvcode2,$code = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' order by lvcode1,lvcode2,lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];

				if($row1['lvcode3'] == "00")
				{
					$msg = "==";
				}
				else
				{
					$msg = "";
				}

				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>$msg {$row1['comment']} ";
				}
				else
				{
					$option.= "<option value=$selectValueInput>$msg {$row1['comment']}";
				}
				

			}

			return $option;

		}


		
		
	
	        
		function getLocaltour(){
			
			global $dbConn;

			// 상품정보를 먼저 가져온다.
			$qry1 = "select * from product_master where c_code1 = 'T01' && c_code2 = '03' order by p_name asc";
			$rst1 = mysql_query($qry1);

			while($row1 = mysql_fetch_assoc($rst1))
			{
				$content .= "<option value={$row1['p_code']}>{$row1['p_name']}";
			}

			return $content;

		}
       function getLocaltourData($p_code,$k){
			
			global $dbConn;

			// 상품정보를 먼저 가져온다.
			$qry1 = "select * from product_details_local where p_code = '$p_code' && day = '$k'";
			$rst1 = mysql_query($qry1);

			while($row1 = mysql_fetch_assoc($rst1))
			{
				$content .= "{$row1['local_code']}\r";
			}

			return $content;

		}
        
		function getLocaltourData_new($p_code,$k){
			
			global $dbConn;

			// 상품정보를 먼저 가져온다.
			$qry1 = "select * from product_details_local where p_code = '$p_code' && day = '$k'";
			$rst1 = mysql_query($qry1);

			while($row1 = mysql_fetch_assoc($rst1))
			{
				$content .= "{$row1['local_code']}\r";
			}

			return $content;

		}

 		function getLocalInclude($p_code,$k){

			global $dbConn;

			// 상품정보를 먼저 가져온다.
			$qry1 = "select * from product_details_local where p_code = '$p_code' && day = '$k'";
			$rst1 = mysql_query($qry1);

			while($row1 = mysql_fetch_assoc($rst1))
			{
				// 로컬상품명 
				$productInfo = getProductMaster($row1['local_code']);

				$content .= "&nbsp;<input type=checkbox name=local_item value=\"{$row1['local_code']}/{$productInfo['normal_adult_price']}\" checked> {$productInfo['p_name']} - ${$productInfo['normal_adult_price']}<br>";
			}

			return $content;

		}

		function getLocalReserve($p_code,$k,$estimateCode){

			global $dbConn;

			// 상품정보를 먼저 가져온다.
			$qry1 = "select * from reserve_local where reserveCode = '$estimateCode' && day = '$k'";
			$rst1 = mysql_query($qry1);

			while($row1 = mysql_fetch_assoc($rst1))
			{
				// 로컬상품명 
				$productInfo = getProductMaster($row1['local_code']);

				$content .= "&nbsp;<input type=checkbox name=local_item value=\"{$row1['local_code']}/{$productInfo['normal_adult_price']}\" checked> {$productInfo['p_name']} - ${$productInfo['normal_adult_price']}<br>";
			}

			return $content;

		}

		function getLocalInclude_text($p_code,$k){

			global $dbConn;

			// 상품정보를 먼저 가져온다.
			$qry1 = "select * from product_details_local where p_code = '$p_code' && day = '$k'";
			$rst1 = mysql_query($qry1);

			while($row1 = mysql_fetch_assoc($rst1))
			{
				// 로컬상품명 
				$productInfo = getProductMaster($row1['local_code']);

				$content .= "{$row1['local_code']}\r";
			}

			return $content;

		}

        
 
	

   
	function hasMenuAccess($division, $par_idx, $sub_idx) {
		global $user_dbinfo;

		$qry = "select userid from menu_info_user where division = $division && parent_idx = $par_idx && sub_idx = $sub_idx";
		$rst = mysql_query($qry);
		$numRows = mysql_num_rows($rst);
		///echo $qry."|".$user_dbinfo[userid];
		//exit;
		//$hasAccess = false;
		for ($i = 1; $i <= $numRows; $i++) {
			$theUser = mysql_fetch_assoc($rst);
			if ($theUser['userid'] == $user_dbinfo['userid']) {
				return true;
			}
		}

		return false;
	}

	function hasMenuAccess2($filename) {
		global $user_dbinfo;
		
		$curr_user = $user_dbinfo['userid'];
		$qry = "select userid from menu_info_user where menu_link like '%$filename%' && userid = '$curr_user'";
		$rst = mysql_query($qry);
		$numRows = mysql_num_rows($rst);

		if ($numRows >= 1) {
			return true;
		}
		
		return false;
	}
	
	 function RemoveXSS($val) { 
   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed 
		   // this prevents some character re-spacing such as <java\0script> 
		   // note that you have to handle splits with \n, \r, and \t later since they *are* 
		   // allowed in some inputs 
		   $val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val); 
			
		   // straight replacements, the user should never need these since they're normal characters 
		   // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&
		   // #X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29> 
		   $search = 'abcdefghijklmnopqrstuvwxyz'; 
		   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
		   $search .= '1234567890!@#$%^&*()'; 
		   $search .= '~`";:?+/={}[]-_|\'\\'; 
		   for ($i = 0; $i < strlen($search); $i++) { 
		   // ;? matches the ;, which is optional 
		   // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars 
			
		   // &#x0040 @ search for the hex values 
			  $val = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); 
			  // with a ; 

			  // &#00064 @ 0{0,7} matches '0' zero to seven times 
			  $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ; 
		   } 
			
		   // now the only remaining whitespace attacks are \t, \n, and \r 
		   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 
		'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'); 
		   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'); 
		   $ra = array_merge($ra1, $ra2); 
			
		   $found = true; // keep replacing as long as the previous round replaced something 
		   
		   while ($found == true) { 
			
			  $val_before = $val; 
			  for ($i = 0; $i < sizeof($ra); $i++) { 
				 $pattern = '/'; 
				 for ($j = 0; $j < strlen($ra[$i]); $j++) { 
					if ($j > 0) { 
					   $pattern .= '('; 
					   $pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?'; 
					   $pattern .= '|(&#0{0,8}([9][10][13]);?)?'; 
					   $pattern .= ')?'; 
					} 
					$pattern .= $ra[$i][$j]; 
				 } 
				 $pattern .= '/i'; 
				 $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag 
				 $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags 
				 if ($val_before == $val) { 
					// no replacements were made, so exit the loop 
					$found = false; 
				 } 
			  } 
		   } 
		  
		   return $val; 
	}
	
    	
?>