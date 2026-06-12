<?php
   

   function codebaseName($code){
			
			global $dbConn;
			if (strlen($code) == "8") {
				$lvcode1 = substr($code,0,3);
				$lvcode2 = substr($code,3,2);
				$lvcode3 = substr($code,5,2);
				$lvcode4 = '00';
				
			} elseif (strlen($code) == "9") {
				$lvcode1 = substr($code,0,3);
				$lvcode2 = substr($code,3,2);
				$lvcode3 = substr($code,5,2);
				$lvcode4 = substr($code,7,2);
				
			} else {
				$lvcode1 = substr($code,0,3);
				$lvcode2 = substr($code,3,2);
				$lvcode3 = substr($code,5,2);
				$lvcode4 = '00';
			}

			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 = '$lvcode3' && lvcode4='$lvcode4'";
			//print_r($qry1);
			$rst1 = mysql_query($qry1,$dbConn);

			$row1 = mysql_fetch_assoc($rst1);
			
			return $row1;
	}
	function pickBaseCode2($code = false){
			
		global $dbConn;

		$qry1 = "select pick_code,pick_name from base_pick where pick_m = 'M' 
		         union
				 select h_code as pick_code,h_name as pick_name from product_hotel order by pick_name asc
				 ";
		$rst1 = mysql_query($qry1,$dbConn);
		
		while($row1 = mysql_fetch_assoc($rst1)){
			
			
			$selectValueInput = $row1['pick_code'];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>{$row1['pick_name']} ";
			} else 
			{
				$option.= "<option value=$selectValueInput >{$row1['pick_name']}";
			}
			

		}

		return $option;

	}
	function get_html($id) {
			$qry3 = "select * from html_page where id = '$id'";
			$rst3 = mysql_query($qry3);
			$row3 = mysql_Fetch_assoc($rst3);
			return $row3;

	}
    function getConsultInfo($cCode){
		
				global $dbConn;

				$qry1 = "select * from consult_info where consultCode = '$cCode'";
				$rst1 = mysql_query($qry1);
				$row1 = mysql_fetch_assoc($rst1);

				return $row1;

	}
	// 새로운상담 번호 가져오기
	function getConsultNum($code){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(consultNum) from consult_info where consultCode='$consultCode'";
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
	function getRConsultNum($code){
			
			global $dbConn;

			$start_date = date('Y-m-d 00:00:01');
			$stop_date = date('Y-m-d 23:59:59');

			$qry1 = "select max(substr(consultCode,19,3)) as cmax,substr(consultCode,1,17) from consult_info where substr(consultCode,1,17)='$code'";
			$rst1 = mysql_query($qry1);
			$row1 = @mysql_fetch_assoc($rst1);

			return $row1;
			
	}
	function printBaseCode_first1($lvcode1,$code = false){
			
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' && lvcode3 = '00' order by lvcode2 asc";
				
		$rst1 = mysql_query($qry1,$dbConn);
		//echo $code;
		while($row1 = mysql_fetch_assoc($rst1)){
			
			$selectValue = $row1['lvcode1'].$row1['lvcode2'];
			$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>{$row1['comment']} ";
			} else 
			{
				$option.= "<option value=$selectValueInput >{$row1['comment']}";
			}
			

		}

		return $option;

	}
	function printBaseCode_first($lvcode1,$code = false){
			
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' && lvcode3 = '00' order by lvcode2 asc";
				
		$rst1 = mysql_query($qry1,$dbConn);
		//echo $code;
		while($row1 = mysql_fetch_assoc($rst1)){
			
			$selectValue = $row1['lvcode1'].$row1['lvcode2'];
			$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>{$row1['comment']} ";
			} else 
			{
				$option.= "<option value=$selectValueInput >{$row1['comment']}";
			}
			

		}

		return $option;

	}
	
	function printBaseCode_second($lvcode1,$lvcode2,$code = false){
			
		global $dbConn;
		$lvcode1 = substr($code,0,3);
		$lvcode2 = substr($code,3,2);
		$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 <> '00' && lvcode4 = '00' order by lvcode3 asc";
		$rst1 = mysql_query($qry1,$dbConn);
		//echo $qry1."TEST";
		///exit;
		while($row1 = mysql_fetch_assoc($rst1)){
			
			$selectValue = $row1['lvcode1'].$row1['lvcode2'];
			$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'].$row1['lvcode4'];
			//echo 	$selectValueInput."||".$code."<br>";
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>{$row1['comment']} ";
			} else 
			{
				$option.= "<option value=$selectValueInput >{$row1['comment']}";
			}
			

		}

		return $option;

	}
	function printBaseCode_hsecond($lvcode1,$lvcode2,$code = false){
			
		global $dbConn;
		$lvcode1 = substr($code,0,3);
		$lvcode2 = substr($code,3,2);
		$lvcode3 = substr($code,3,2);
		$qry1 = "select * from code_base where active='yes' && lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 <> '00' &&  lvcode4 = '00' order by lvcode3 asc";
		$rst1 = mysql_query($qry1,$dbConn);
		
		while($row1 = mysql_fetch_assoc($rst1)){
			
			$selectValue = $row1['lvcode1'].$row1['lvcode2'];
			$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>{$row1['comment']} ";
			} else 
			{
				$option.= "<option value=$selectValueInput >{$row1['comment']}";
			}
			

		}

		return $option;

	}
	function printBaseCode_hotel($code=false){
			
		global $dbConn;
		$lvcode1 = substr($code,0,3);
		$lvcode2 = substr($code,3,2);
		$qry1 = "select * from code_base where active='yes' && lvcode1 = 'H09' && lvcode2 !='00' && lvcode3 ='00' order by lvcode2 asc";
		$rst1 = mysql_query($qry1,$dbConn);
		 
		while($row1 = mysql_fetch_assoc($rst1)){
			
			$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'].$row1['lvcode4'];
		
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>{$row1['comment']} ";
			} else 
			{
				$option.= "<option value=$selectValueInput >{$row1['comment']}";
			}
			
	
		}

		return $option;

	}	
	function printBaseCodeCategory($pjCategory = false){
			
			global $dbConn;

			$qry1 = "select * from code_base where lvcode1 = 'C01' && lvcode2 <> '00' && lvcode3 = '00' && lvcode4 = '00' && lvcode5 = '00' order by lvcode1,lvcode2,lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];

				if($pjCategory == $selectValueInput)
				{
					echo "<option value=$selectValueInput selected>{$row1['comment']}";		
				}
				else
				{
					echo "<option value=$selectValueInput>{$row1['comment']}";		
				}
					

			}
	}

	function pickBaseCode($code = false){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_m = 'M' order by pick_code asc";
		$rst1 = mysql_query($qry1,$dbConn);
		
		while($row1 = mysql_fetch_assoc($rst1)){
			
			
			$selectValueInput = $row1['pick_code'];
				
			if($selectValueInput == $code)
			{
				$option.= "<option value=$selectValueInput selected>{$row1['pick_name']} ";
			} else 
			{
				$option.= "<option value=$selectValueInput >{$row1['pick_name']}";
			}
			

		}

		return $option;

	}

	function pickBaseCode4($code = false){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_m = 'M' && pick_code='$code' order by pick_code asc";
		$rst1 = mysql_query($qry1,$dbConn);
		
		$row3 = mysql_Fetch_assoc($rst1);
		return $row3;

	}
	function pickBaseCodeSencond($pickcode,$picktt){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_code='$pickcode' order by pick_time asc";
		$rst1 = mysql_query($qry1,$dbConn);
		
		while($row1 = mysql_fetch_assoc($rst1)){
			
			
			$selectValueInput = $row1['pick_time'];
				
			if($selectValueInput == $picktt)
			{
				$option.= "<option value='$selectValueInput' selected>{$row1['pick_time']} ";
			} else 
			{
				$option.= "<option value='$selectValueInput' >{$row1['pick_time']}";
			}
			

		}

		return $option;

	}
	
	function getProductMaster($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code = '$p_code'";
		//print_r($qry1);
		//exit;
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getProductHMaster($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_hotel where h_code = '$p_code' && u_type in ('1','3') ";
		//print_r($qry1);
		//exit;
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getProductPick($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code = '$p_code' && p_code like '%PICKUP%'";
		//print_r($qry1);
		//exit;
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getProductSend($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code = '$p_code' && p_code like '%SENDING%'";
		//print_r($qry1);
		//exit;
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
   function getProductPickup($code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code like '%PICKUP%'";
		//print_r($qry1);
		//exit;
		$rst1 = mysql_query($qry1);
		while($row1 = mysql_fetch_assoc($rst1)){
			
			

			if($code == $row1['p_code'])
			{
				$content .= "<option value='{$row1['p_code']}' selected>{$row1['p_name']}";
			}
			else
			{
				$content .= "<option value='{$row1['p_code']}'>{$row1['p_name']}";
			}
			

		}
		return $content;

	}
	function getProductSending($code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code like '%SENDING%'";
		//print_r($qry1);
		//exit;
		$rst1 = mysql_query($qry1);
		while($row1 = mysql_fetch_assoc($rst1)){
			
			

			if($code == $row1['p_code'])
			{
				$content .= "<option value='{$row1['p_code']}' selected>{$row1['p_name']}";
			}
			else
			{
				$content .= "<option value='{$row1['p_code']}'>{$row1['p_name']}";
			}
			

		}
		return $content;

	}
	function printRandSelect($rand_id = false){
	
	    global $dbConn;

		$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N' && set_pro ='C' order by company_area,kor_name asc";
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
	
	function randname($rand_id = false){
	
		global $dbConn;

		$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N'  && userid = '$rand_id'  ";
		$rst1 = mysql_query($qry1);

		$row1 = mysql_fetch_assoc($rst1);

		return $row1;
	}
	
	/**
	* @ 아이디로 개인정보 뽑아오기
	*/
	function getinfo_dbMember($user_info){
		
		global $dbConn;

		$qry1 = "select * from member_list where userid = '$user_info' ";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		//echo $qry1;exit;
		return $row1;

	}
	function boardConfig($table_id){
	
		global $dbConn;

		$qry1 = "select * from paran_board_setup where table_id = '$table_id'";
		$rst1 = mysql_query($qry1,$dbConn);

		$row1 = mysql_fetch_assoc($rst1);

		return $row1;
	}

	function getinfo_dbHotel_bycode($hcode){
	
		global $dbConn;

		$qry1 = "select * from product_hotel where h_code = '$hcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$row1 = mysql_fetch_assoc($rst1);

		return $row1;
	}

	// 상품 번호 가져오기
	function getHnumber(){
		
		global $dbConn;

		$qry1 = "select max(num) from product_hotel";
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

    function printPickSelect($prodcode ,$prodseq= false,$ty){
	
	    global $dbConn;
		if ($ty !="5") {
			$qry1 = "select a.pick_code,b.seq,a.pick_name,b.pick_time from base_pick a, product_pick b where a.pick_code=b.pick_area && a.pick_time = b.pick_time &&  b.p_code='$prodcode'";
		} else {
			$qry1 = "select distinct a.pick_code,b.seq,a.pick_name,b.pick_time from base_pick a, product_pick b where a.pick_code=b.pick_area  &&  b.p_code='$prodcode'";
		}
		$rst1 = mysql_query($qry1);
		//echo $qry1."<br />";
		//exit;
		//echo $prodseq;
		//exit;
		while($row1 = mysql_fetch_assoc($rst1)){
			if ($ty !="5") {
				$curcode =$row1['pick_code']."/".$row1['pick_time'];
				
				if($prodseq == $curcode)
				{
					$content .= "<option value='$curcode' selected>{$row1['pick_name']} ({$row1['pick_time']})";
				}
				else
				{
					$content .= "<option value='$curcode' >{$row1['pick_name']} ({$row1['pick_time']})";
				}
			} else {
				$curcode =$row1['pick_code']."/".$row1['pick_time'];
				
				if($prodseq == $curcode)
				{
					$content .= "<option value='$curcode' selected>{$row1['pick_name']}";
				}
				else
				{
					$content .= "<option value='$curcode' >{$row1['pick_name']}";
				}


			}
			

		}

		return $content;
	}

	function printBaseCode2_without($lvcode1,$code = false){
			
			global $dbConn;
			
			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' order by lvcode2 asc";
			$rst1 = mysql_query($qry1,$dbConn);
			
			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];
				
				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']} ";
				} else 
				{
					$option.= "<option value=$selectValueInput >{$row1['comment']}";
				}
				

			}

			return $option;

		}
		function printBaseCode3_without($lvcode1,$code = false){
			
			global $dbConn;
			$lvcode1 = substr($code,0,3);
			$lvcode2 = substr($code,3,2);
			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 <> '00' order by lvcode3 asc";
			$rst1 = mysql_query($qry1,$dbConn);
			
			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];
					
				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']} ";
				} else 
				{
					$option.= "<option value=$selectValueInput >{$row1['comment']}";
				}
				

			}

			return $option;

		}

		function printBaseCode4_without($lvcode1,$code){
			
			global $dbConn;
			
			$qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 <> '00' && lvcode3 <> '00' order by lvcode2 asc";
			$rst1 = mysql_query($qry1,$dbConn);
			///echo $qry1;
			//exit;
			while($row1 = mysql_fetch_assoc($rst1)){
				
				$selectValue = $row1['lvcode1'].$row1['lvcode2'];
				$selectValueInput = $row1['lvcode1'].$row1['lvcode2'].$row1['lvcode3'];
				echo $code."<br />";	
				if($selectValueInput == $code)
				{
					$option.= "<option value=$selectValueInput selected>{$row1['comment']}";
				} else 
				{;
					$option.= "<option value=$selectValueInput >{$row1['comment']}";
				}
				

			}

			return $option;

		}
        //가이드정산 기초코드
		function getGuideBaseCode($lvcode){
			global $dbConn;

			$query = "SELECT * FROM code_base WHERE lvcode1 ='$lvcode' AND lvcode2 !='00' AND lvcode3 ='00' ";

			$rst1 = mysql_query($query,$dbConn);
			
			return $rst1;

		}
		function getReserveInfo($rCode){
		
				global $dbConn;

				$qry1 = "select * from reserve_info where reserveCode = '$rCode' && parent = 'MAIN'";
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveInfo2($rCode,$st){
		
				global $dbConn;

				$qry1 = "select * from reserve_info where reserveCode = '$rCode' && stDate='$st' && parent = 'SUB'";
				//echo $qry1."<br />";	
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReservePSInfo($rCode,$pcode){
		
				global $dbConn;

				$qry1 = "select * from reserve_info where reserveCode = '$rCode' && dis_code = '$pcode'";
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
			//echo $qry1;
				return $row1;

	    }
		
		function getReserveHInfo($rCode){
		
				global $dbConn;

				$qry1 = "select * from reserve_hotel where reserveCode = '$rCode' ";
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		if (!function_exists('normalizeSqlDateValue')) {
			function normalizeSqlDateValue($dateValue) {
				$dateValue = trim((string)$dateValue);
				if ($dateValue == "") return "";

				if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
					return $dateValue;
				}

				if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateValue, $m)) {
					$month = (int)$m[1];
					$day = (int)$m[2];
					$year = (int)$m[3];
					if (checkdate($month, $day, $year)) {
						return sprintf('%04d-%02d-%02d', $year, $month, $day);
					}
				}

				return $dateValue;
			}
		}

		function getReserveInfoGCnt($pcode,$sdate){
		
				global $dbConn;

				$sdate = normalizeSqlDateValue($sdate);
				if (empty($pcode) || empty($sdate)) return ['pcnt' => 0];

				$qry1 = "select tour_pcnt as pcnt from reserve_info where p_code = '$pcode' && stDate='$sdate'";
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveInfoCnt($pcode,$sdate){

				global $dbConn;

				$sdate = normalizeSqlDateValue($sdate);
				if (empty($pcode) || empty($sdate)) return ['cnt' => 0];

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && (  rev_status ='DONE')";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveInfoCntguide($pcode,$sdate){
		
				global $dbConn;

				$sdate = normalizeSqlDateValue($sdate);
				if (empty($pcode) || empty($sdate)) return ['cnt' => 0];

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && (  rev_status ='DONE')";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		//가이드정산보고 본행사인원
		function getGuideMainPcnt($p_code,$stDate){
			global $dbConn;

			$query = "SELECT SUM(p_cnt) p_cnt FROM reserve_info WHERE p_code ='$p_code' AND stDate ='$stDate' AND parent ='MAIN'
			AND ( rev_status!='CANCEL' && rev_status!='WAIT') ";

			$rst1 = mysql_query($query);
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;
		}

		//가이드정산보고 복합행사인원
		function getGuideSubPcnt($p_code,$stDate){
			global $dbConn;

			$query = "SELECT SUM(p_cnt) p_cnt FROM reserve_info WHERE p_code ='$p_code' AND stDate ='$stDate' AND parent ='SUB'
			AND ( rev_status!='CANCEL')";

			$rst1 = mysql_query($query);
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;
		}
		function getReserveInfoCntG($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && rev_status ='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveInfoBal($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(last_bal) as bal from reserve_info where p_code = '$pcode' && stDate='$sdate' && rev_status='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveInfoBalSS($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(last_bal) as bal from reserve_info where p_code = '$pcode' && stDate='$sdate' && rev_status='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveInfoSal($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(last_total) as tot from reserve_info where p_code = '$pcode' && stDate='$sdate' && rev_status='DONE' ";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveInfoRoom($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(room_cnt) as rcnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && rev_status in ('DONE') ";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }
		function getReserveWaitCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && parent = 'MAIN' && rev_status='WAIT' ";
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }

		function getReserveInfoSCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && p_code not in ('SPICKUP003','SSEND007') && rev_status ='ORDER'";
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }

		function getReserveWaitSCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && p_code not in ('SPICKUP003','SSEND007') && rev_status='WAIT' ";
				//echo $qry1."<br>";
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	    }

		function getTourInfo2($pcode,$st){
		
			global $dbConn;

			$st = normalizeSqlDateValue($st);
			if (empty($pcode) || empty($st)) return array();

			$qry1 = "select b.* from tour_master b where  b.p_code='$pcode'
			&& b.stDate ='$st'";
			//echo $qry1;
			//exit;
			$rst1 = mysql_query($qry1);
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;

		}
		function printCarSelect($gid = false){
	
			global $dbConn;
			
			if ($gid) {
				$ggid  = " bus_id = '$gid'";
			} else {
				$ggid  = "";
			}
			$qry1 = "select * from bus_list where 1=1   order by bus_team asc";
			$rst1 = mysql_query($qry1);
			
			while($row1 = mysql_fetch_assoc($rst1)) {
				$comp=codebaseName($row1['bus_team']);
				if($gid == $row1['bus_id'])
				{
					$content .= "<option value='{$row1['bus_id']}' selected>{$comp['comment']} ({$row1['bus_id']})";
				}
				else
				{
					$content .= "<option value='{$row1['bus_id']}' >{$comp['comment']} ({$row1['bus_id']})";
				}
			

			}

			return $content;
		}
        function getPicGr5($pcode,$st) {

				global $dbConn;

				$qry1 = "select count(b.pick_area) cnt,b.pick_area from reserve_info a,reserve_traveler b 
				where a.reserveCode=b.reserveCode && a.p_code = '$pcode' && a.stDate ='$st' && a.rev_status not in ('CANCEL') group by b.pick_area";	//	echo $qry1;
				$rst1 = mysql_query($qry1);

				while($row1 = mysql_fetch_assoc($rst1)){
			
					    $pickarr = explode("/",$row1['pick_area']);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						if ($picknm['pick_name'] != "") {
						$content .= $picknm['pick_name'] . '-' . $picknm['pick_time'] . ' : ' . $row1['cnt'] . '인&nbsp;&nbsp;';
						}
					    
					
					
				}


				return $content;

	}


	    
		function printCompanySelect($rand_id){

			    global $dbConn;

				// 발권처(issue_airline='YES') 업체도 지불/수금업체 목록에 표시한다.
				// 단, 수금/지급 맥락에서는 '발권업체'가 아니라 수금/지급업체이므로 발권 표기를 하지 않는다.
				// (항공 발권처 드롭다운 printRandSelectAirlie() 의 발권업체와 역할이 구분됨)
				$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N'
						 order by company_area,kor_name asc";
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

	// 예약 최근 번호 가져오기
	function getNumReserve_total(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(grandNum) from grand_reserve where wdate between '$start_date' and '$stop_date'";
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
   // 예약 최근 번호 가져오기
   function getNumReserve(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(reserveNum) from reserve_info where wdate between '$start_date' and '$stop_date'";
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

	// 예약 최근 번호 가져오기
	function getNumHReserve(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(reserveNum) from reserve_hotel where wdate between '$start_date' and '$stop_date'";
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
			$num1 = "001";
		}

		return $num1;
	}
	
	// 예약 최근 번호 가져오기
	function getNumReserve_ctotal(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(grandNum) from grand_reserve where 1=1";
		
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0);

		}

		return $rNum;
	}

	// 예약 최근 번호 가져오기//토탈행사번호
    function getNumTevent(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(grand_eNum) from tour_master where wdate between '$start_date' and '$stop_date'";
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

	// 예약 최근 번호 가져오기//서브행사번호
    function getNumSevent($gcode,$st){
		
		global $dbConn;

		$start_date = $st;
		$stop_date =  $st	;

		$qry1 = "select max(sub_eNum) from tour_car where grand_eCode='$gcode' && stDate between '$start_date' and '$stop_date'";
		//print_r($qry1);
		///exit;
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

	// 가이드정산 최근 번호 가져오기
	function getNumguide(){
		
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(s_num+1) from guide_setmaster where 1=1 && stDate between '$start_date' and '$stop_date'";
		
		$rst1 = mysql_query($qry1);
		$num1 = mysql_num_rows($rst1);

		if($num1>0)
		{
			$rNum = @mysql_result($rst1,0,0);

		}

		return $rNum;
	}
	function getCRandInfo($rCode){
		
				global $dbConn;
               //&& (settle_memo not like '%발권합계%' && settle_memo not like '%발권정산%')
				$qry2 = "SELECT * FROM  rand_company WHERE reserveCode='$rCode' && money_type='credit' && p_memo !='항공발권'";
		        $rst2 = mysql_query($qry2);
				$row1 = @mysql_fetch_assoc($rst2);
				
				return $row1;

	}
	function getDRandInfo($rCode){
		
				global $dbConn;
				//&& (settle_memo not like '%발권합계%' && settle_memo not like '%발권정산%')
				$qry2 = "SELECT * FROM  rand_company WHERE reserveCode='$rCode' && money_type='debit' && p_memo !='항공발권'";
				//echo $qry2;
				//exit;
		        $rst2 = mysql_query($qry2);
				$row1 = @mysql_fetch_assoc($rst2);
				
				return $row1;

	}

	function getRandInfo($seq){
		
				global $dbConn;

				$qry2 = "SELECT * FROM  rand_company WHERE seq_no='$seq'";
		        $rst2 = mysql_query($qry2);
				$row1 = @mysql_fetch_assoc($rst2);
				
				return $row1;

	}

	function getPaymethod($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select* from payment_history where reserveCode = '$reserveCode' && payment_status='DONE' limit 1";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);
		
		
		return $row1;
	}
	function getPayment($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select sum(payment) as pay1 from payment_history where reserveCode = '$reserveCode' && payment_status='DONE' && pay_method not in ('init')";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		
		
		return $row1;
	}
	function getPayment2($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select sum(payment) as pay1 from payment_history where reserveCode = '$reserveCode' && payment_status='DONE' && pay_method not in ('init')";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		$qry2 = "select sum(payment) as pay2 from payment_history where reserveCode = '$reserveCode' && payment_status='RETURN' && pay_method not in ('init')";
		$rst2 = mysql_query($qry2);
		$row2 = mysql_fetch_assoc($rst2);
		
		$pay = $row1['pay1']-$row2['pay2'];
		
		return $pay;
	}
	function getRPaymethod($reserveCode){
		
		global $dbConn;

		
		$qry1 = "select* from payment_history where reserveCode = '$reserveCode' && (payment_status='RRQUEST' || payment_status='RETURN') limit 1";
		$rst1 = mysql_query($qry1);
		
		$row1 = mysql_fetch_assoc($rst1);
		
		
		return $row1;
	}
    
	function printHotelList($h_code = false){
		
		global $dbConn;

		$qry1 = "select * from product_hotel order by h_code asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($h_code == $row1['h_code'])
			{
				$content .= "<option value={$row1['h_code']} selected>{$row1['h_name']}";
			}
			else
			{
				$content .= "<option value={$row1['p_code']}>{$row1['h_name']}";
			}
			
		}

		return $content;
	}
	function getReserveTrPic($rev){
		
				global $dbConn;

				$qry1 = "select   count(*) cnt from reserve_traveler 
									 where reserveCode = '$rev' 
									
								";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function getReserveCnt($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select   c.reserveCode from reserve_info a,product_master b,reserve_traveler c
									 where a.p_code=b.p_code && a.reserveCode = c.reserveCode 
									 && a.stDate = '$sdate' && a.p_code = '$pcode' group by c.reserveCode
								";
				$rst1 = mysql_query($qry1);
				$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $num1;

	}

	function getReserveTr($rev){
		
				global $dbConn;

				$qry1 = "select   count(*) cnt from reserve_traveler 
									 where reserveCode = '$rev' 
									
								";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function getReserveTrRepre($rev){
		
				global $dbConn;

				$qry1 = "select   *  from reserve_traveler 
									 where reserveCode = '$rev' && seqint = '0'
									
								";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function getReserveHRepre($rev){
		
				global $dbConn;

				$qry1 = "select r_kname  from reserve_hotel 
									 where reserveCode = '$rev' 
									
								";
				//echo $qry1;
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function getReserveSum($rev){
		
				global $dbConn;

				$qry1 = "select   sum(dis_pay) amt from reserve_traveler 
									 where reserveCode = '$rev' 
									
								";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function pickBaseInfo($pickcode,$picktt){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_code='$pickcode' && pick_time='$picktt'";
		$rst1 = mysql_query($qry1,$dbConn);
				
		$row1 = mysql_fetch_assoc($rst1);
		
		
		return $row1;

	}
    function pickBaseInfo2($pickcode){
			
		global $dbConn;

		$qry1 = "select * from base_pick where pick_code='$pickcode' ";
		$rst1 = mysql_query($qry1,$dbConn);
				
		$row1 = mysql_fetch_assoc($rst1);
		
		
		return $row1;

	}
	function getRoomTr($rev,$nm){
		
				global $dbConn;

				$qry1 = "select  * from hotelroom_assign 
									 where reserveCode = '$rev' && tnm ='$nm'
									
								";
				//echo $qry1 ."<br />";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}



	function getReserveRoomCnt($pcode,$sdate){
		
				global $dbConn;

				/*$qry1 = "select   room_num from hotelroom_assign
							where stDate = '$sdate' && p_code = '$pcode' && room_num <> '99' 
							&& tnm not in (select rev_nm from tour_car where stDate = '$sdate' && p_code = '$pcode')
							group by room_num
								";
								*/
				$qry1 = "select  sum(room_cnt) as rcnt  from reserve_info
							where stDate = '$sdate' && p_code = '$pcode' && rev_status != 'CANCEL'
							&& rev_status = 'DONE'
							
								";
				//echo $qry1."<br >";
				$rst1 = mysql_query($qry1);
				//$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function getReserveRoomCnt1($pcode,$sdate){
		
				global $dbConn;

				$qry1 = "select   room_num from reserve_info,
							where stDate = '$sdate' && p_code = '$pcode' 
							&& tnm not in (select rev_nm from tour_car where stDate = '$sdate' && p_code = '$pcode')
							group by room_num
								";
								
				
				//echo $qry1."<br >";
				$rst1 = mysql_query($qry1);
				$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $num1;

	}
	function getbusass($gcode) {

				global $dbConn;

				$qry1 = "select   count(*) cnt from tour_car 
									 where  grand_eCode='$gcode'  && p_code not like '%ADD%'";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;


	}

	function getbusInfo($pcode,$stdate,$rcode) {

				global $dbConn;

				$qry1 = "select * from tour_car 
									 where  p_code='$pcode' && reserveCode='$rcode' && stDate='$stdate'";
			    //echo $qry1."<br />";
				$rst1 = mysql_query($qry1);
				
				///$row1 = @mysql_fetch_assoc($rst1);
				while($row1 = mysql_fetch_assoc($rst1)){
                     $g_dbinfo1 = getguideInfor($row1['grand_eCode'],$row1['sub_eCode'],$row1['bus_num']);
					 $g_dbinfo = getinfo_dbMemberg($g_dbinfo1['guide_id']);
					 $g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1['sguide_id']);
					 $rstmsg = $g_dbinfo['kor_name']."@".$g_dbinfo2['kor_name'];
					 ;

				}
				return $rstmsg;


	}
	function getbusInfo8($stdate,$rcode) {

				global $dbConn;

				$qry1 = "select * from tour_car 
									 where   reserveCode='$rcode' && stDate='$stdate' order by stDate asc";
			    //echo $qry1."<br />";
				$rst1 = mysql_query($qry1);
				
				$row1 = mysql_fetch_assoc($rst1);
				
				return $row1;


	}
	function getbusInfo5($rcode,$stDate) {

				global $dbConn;

				$qry1 = "SELECT DISTINCT
									b.grand_eCode,
									b.sub_eCode,
									a.p_code,
									b.stDate,
									a.p_name,c.guide_id 
								FROM
									reserve_info AS a  
								INNER JOIN
									tour_car AS b ON a.p_code = b.p_code AND a.stDate = b.stDate 
								INNER JOIN
									tour_guide AS c ON b.grand_eCode = c.grand_eCode AND b.sub_eCode = c.sub_eCode 
								WHERE
									a.reserveCode = '".$rcode."' && b.bus_num = c.bus_num && b.stDate='".$stDate."' order by b.stDate asc";
			    
				
				$rst1 = mysqli_query($dbConn,$qry1);
				$php_total_count = mysqli_num_rows($rst1);
				///echo $php_total_count."<br />";
				//$row1 = $rst1->fetch_assoc();
                $rstmsg ="";
				$i=0;
				while($row1 = mysqli_fetch_assoc($rst1)){
					 $i++;
					 //var_dump($row1);
					 $g_dbinfo = getinfo_dbMemberg(trim($row1['guide_id']));
					 $rstmsg .= $g_dbinfo['kor_name']." ".$g_dbinfo['company_phone']."/";
					 

				}
			    //echo $rstmsg."<br />";
				//exit;
				return $rstmsg;
				


	    }
	function getbusperson($r,$gcode,$st=false) {

				global $dbConn;
				if ($st!=false) {
					$stqry = "&& stDate='$st'";
				} else  {
					$stqry = "";
				}
				$qry1 = "select   count(*) cnt from tour_car 
									 where bus_num ='$r' $stqry && grand_eCode='$gcode' && p_code not like '%ADD%'";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;


	}

	function getbusRoom($r,$gcode,$st=false) {

				global $dbConn;
				if ($st!=false) {
					$stqry = "&& stDate='$st'";
				} else  {
					$stqry = "";
				}
				$qry1 = "select  romm_num from tour_car 
                            where bus_num ='$r' $stqry && grand_eCode='$gcode' && p_code not like '%ADD%' group by romm_num";
				
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function getbusMemo($gcode,$gscode) {

				global $dbConn;

				$qry1 = "select  distinct h_memo from tour_car 
                            where grand_eCode='$gcode' && sub_eCode ='$gscode'";
				//echo $qry1."<br />";
				$rst1 = mysql_query($qry1);
				$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
    function pickBaseCode3($code = false){
			
		global $dbConn;

		$qry1 = "select pick_code,pick_name from base_pick where pick_m = 'M' && pick_code ='$code'
		         union
				 select h_code as pick_code,h_name as pick_name from product_hotel where h_code='$code' order by pick_name asc
				 ";
		$rst1 = mysql_query($qry1,$dbConn);
		
		$row1 = @mysql_fetch_assoc($rst1);

		return $row1;

	}
	function getBusCnt($gcode) {

				global $dbConn;

				$qry1 = "select bus_num from tour_car 
                            where grand_eCode='$gcode' && p_code not like '%ADD%' group by bus_num";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $num1;

	}
	function getguideass($gcode) {

				global $dbConn;

				$qry1 = "select   count(*) cnt from tour_guide 
									 where  grand_eCode='$gcode' && p_code not like '%ADD%'";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;


	}
	function gethotelass($gcode,$scode) {

				global $dbConn;

				$qry1 = "select   count(*) cnt from hotel_assign 
									 where  grand_eCode='$gcode' && sub_eCode='$scode' ";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;


	}
	function getPicGr($gcode,$bnum) {

				global $dbConn;

				$qry1 = "select count(picCode) cnt,picCode from tour_car 
				where grand_eCode = '$gcode' && bus_num ='$bnum' && p_code not like '%ADD%' group by picCode";
				
				$rst1 = mysql_query($qry1);
				while($row1 = mysql_fetch_assoc($rst1)){
			
					    $pickarr = explode("/",$row1['picCode']);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						$content .= "{$picknm['pick_name']}-{$picknm['pick_time']} : {$row1['cnt']}개&nbsp;&nbsp;&nbsp;&nbsp;";
					
					
				}

				return $content;

	}
	function getPicGr2($scode,$stdate) {

				global $dbConn;

				$qry1 = "select count(pick_area) cnt,pick_area from reserve_traveler a, reserve_info b
				where a.reserveCode = b.reserveCode && b.p_code= '$scode' && b.stDate='$stdate'  group by pick_area";
				//echo $qry1;
				$rst1 = mysql_query($qry1);
				while($row1 = mysql_fetch_assoc($rst1)){
			
					    $pickarr = explode("/",$row1['pick_area']);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						$content .= "{$picknm['pick_name']}-{$picknm['pick_time']} : {$row1['cnt']}개 // ";
					
					
				}

				return $content;

	}
	function getPicGr3($scode,$tnm="") {

				global $dbConn;

				$qry1 = "select count(pick_area) cnt,pick_area from reserve_traveler 
				where reserveCode = '$scode' && traveler_nm = '$tnm' group by pick_area";
				//echo $qry1;
				$rst1 = mysql_query($qry1);
				while($row1 = mysql_fetch_assoc($rst1)){
			
					    $pickarr = explode("/",$row1['pick_area']);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						if ($picknm['pick_time'] =="") {
							$picknm=pickBaseInfo2($pickarr[0]);
							$content = "{$picknm['pick_name']}";
						} else {
							$content = "{$picknm['pick_name']}-{$picknm['pick_time']}";
						}
					
					
				}


				return $content;

	}
	function getPicGr6($scode) {

				global $dbConn;

				$qry1 = "select pick_area from reserve_traveler 
				where reserveCode = '$scode' && seqint=0 limit 1";
				//echo $qry1;
				$rst1 = mysql_query($qry1);
				while($row1 = mysql_fetch_assoc($rst1)){
			
					    $pickarr = explode("/",$row1['pick_area']);
						$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
						if ($picknm['pick_time'] =="") {
							$picknm=pickBaseInfo2($pickarr[0]);
							$content = "{$picknm['pick_name']}";
						} else {
							$content = "{$picknm['pick_name']}-{$picknm['pick_time']}";
						}
					
					
				}


				return $content;

	}
	function getPicSub($scode,$p_code,$stdate) {

				global $dbConn;

				$qry1 = "select meet_area from reserve_info 
				where reserveCode = '$scode' && stDate='$stdate' && p_code = '$p_code'";
				//echo $qry1;
				$rst1 = mysql_query($qry1);
				while($row1 = mysql_fetch_assoc($rst1)){
			
					    
						$picknm=pickBaseCode4($row1['meet_area']);
						$content .= "{$picknm['comment']}";
					
					
				}


				return $content;

	}
	function getPicSub2($scode,$p_code,$stdate) {

				global $dbConn;

				$qry1 = "select meet_area from reserve_info 
				where reserveCode = '$scode' && stDate='$stdate' && p_code = '$p_code'";
				//echo $qry1;
				$rst1 = mysql_query($qry1);
				while($row1 = mysql_fetch_assoc($rst1)){
			
					    
						$picknm=pickBaseCode4($row1['meet_area']);
						$content = "{$picknm['pick_name']}";
					
					
				}


				return $content;

	}
	function printSubHotelList($p_code){
		
		global $dbConn;

		$qry1 = "select * from product_master where p_code like '%ADD%' order by p_code asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($p_code == $row1['p_code'])
			{
				$content .= "<option value={$row1['p_code']} selected>{$row1['p_name']}";
			}
			else
			{
				$content .= "<option value={$row1['p_code']}>{$row1['p_name']}";
			}
			
		}

		return $content;
	}

	function getHRoomCnt($gcode,$gscode) {

				global $dbConn;

				$qry1 = "select romm_num from tour_car 
                            where grand_eCode='$gcode' && sub_eCode = '$gscode' group by romm_num";
				
				$rst1 = mysql_query($qry1);
				$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				///echo $qry1;
				return $num1;

	}

	function printGuideSelect($gid = false){
	
	    global $dbConn;

		$qry1 = "select * from member_list where division = 'guide' && guide_status  ='GOOD' order by kor_name asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)) {
			
			if($gid == $row1['userid'])
			{
				$content .= "<option value='{$row1['userid']}' selected>{$row1['kor_name']} ({$row1['userid']})";
			}
			else
			{
				$content .= "<option value='{$row1['userid']}' >{$row1['kor_name']} ({$row1['userid']})";
			}
		

		}

		return $content;
	}
    function getGuideInfo($gcode,$gscode,$bnum) {

				global $dbConn;

				$qry1 = "select   * from tour_guide 
                            where grand_eCode='$gcode' && sub_eCode ='$gscode' && bus_num='$bnum'";
				//echo $qry1."<br />";
				$rst1 = mysql_query($qry1);
				$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
    
	function mailsend_a($to,$subj,$contents,$attachment1,$attachment2, $attachment3='', $attachment4='') {
		
				$mail = new PHPMailer(true);
				
				$mail->IsSMTP();
				//echo "111";
			
				$mail->CharSet = "utf-8"; 
				$mail->SMTPDebug = 2; // debugging: 1 = errors and messages, 2 = messages only
				$mail->SMTPAuth = true; // authentication enabled
				//$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
				//$mail->Host = 'email-smtp.us-east-1.amazonaws.com';
				//$mail->Port = 587; 
				///$mail->Username = "AKIA6KEWEPAHWOJ3TGR5";
				///$mail->Password = "BJOGF+hc6oSrNXufFiSZD0B1z8Xk5iIZ768GiSX1gLRB";
				$mail->Host = 'in-v3.mailjet.com';
				$mail->Port = 587; 
				/*$mail->Username = "282e8c9efc95ca3560bacf3a92e5e162";
				$mail->Password = "639fc882c20d382357d7102ed1dee309";*/
				$mail->Username = "07bbf03e1ae56b6cd099a2caf83a01ec";
				$mail->Password = "3ecfee99887ea961898b4b7b0fcbe848";
				$mail->IsHTML(true);
				$mail->SetFrom("online@prttour.com","PRUNTOUR");
				$mail->AltBody = '';
				$mail->Subject = $subj;
				
				$mail->MsgHTML($contents);
				
				$emails = explode(',',$to);
				foreach ($emails as $email){
					$mail->AddAddress($email);
				}
				/*
				foreach($attachments as $attachment) {
				        //$mail->AddAttachment("images/phpmailer.gif");      // attachment example
				        $mail->AddAttachment($attachment);
			    }
				*/
				///echo $attachment2;
				
				if ($attachment1 !="") {
					$mail->AddAttachment("upload/".$attachment1."");

				}
				if ($attachment2 !="") {
					$mail->AddAttachment("upload/".$attachment2."");

				}
				if ($attachment3 !="") {
					$mail->AddAttachment("upload/".$attachment3."");

				}
				if ($attachment4 !="") {
					$mail->AddAttachment("upload/".$attachment4."");

				}
				
				if(!$mail->Send()){
					
				  return $mail->ErrorInfo();
				} else {
					
				   return true;
				}
	}
	function mailsend_k($to,$subj,$contents,$attachment1,$attachment2) {
		
				
				$mail = new PHPMailer(true);
				
				$mail->IsSMTP();
				//echo "111";
			
				$mail->CharSet = "utf-8"; 
				$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
				$mail->SMTPAuth = true; // authentication enabled
				//$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
				$mail->Host = 'smtp.gmail.com';
				$mail->Port = 587; 
				$mail->Username = "prunetour2@gmail.com";
				$mail->Password = "lee10011!!";
				$mail->IsHTML(true);
				$mail->SetFrom("online@prttour.com","PRUNETOUR");
				$mail->AddReplyTo("online@prttour.com","PRUNETOUR");
				$mail->AltBody = '';
				$mail->Subject = $subj;
				
				$mail->MsgHTML($contents);
				
				$mail->AddAddress($to);
				
				
				
				if ($attachment1 !="") {
					//$mail->AddAttachment("upload/".$attachment1."");

				}
				if ($attachment2 !="") {
					//$mail->AddAttachment("upload/".$attachment2."");

				}
				
				
				if(!$mail->Send()){
					echo $mail->ErrorInfo();
				  return $mail->ErrorInfo();
				} else {
					
				   return true;
				}
	}
	function mailsend_h($to, $subj, $contents, $attachment1='', $attachment2='', $attachment3='', $attachment4='') {
		$mail = new PHPMailer(true); // 예외모드
		$uploadRoot = '/var/www/html/upload';  // 실제 업로드 절대경로에 맞게
		$logFile = __DIR__ . '/mail_smtp_debug.log';

		try {
			// ===== SMTP 기본 =====
			$mail->isSMTP();
			$mail->CharSet    = 'utf-8';
			$mail->SMTPAuth   = true;

			// 진단 중엔 2~3 / 정상 운영은 0
			$mail->SMTPDebug  = 0;

				

			// 587(STARTTLS) 경로
			$mail->Host       = 'in-v3.mailjet.com';
			$mail->SMTPSecure = 'ssl'; // 5.x는 ssl
			$mail->Port       = 465;
			$mail->Username   = '07bbf03e1ae56b6cd099a2caf83a01ec';
			$mail->Password   = '3ecfee99887ea961898b4b7b0fcbe848';

			// ===== 본문/제목/발신자 =====
			$mail->isHTML(true);
			$mail->setFrom('online@prttour.com', '=?UTF-8?B?'.base64_encode('PRUNTOUR').'?=');
			$mail->Subject = $subj;
			$mail->Body    = $contents;
			$mail->AltBody = strip_tags($contents);

			// ===== 수신자 =====
			foreach (explode(',', $to) as $email) {
				$email = trim($email);
				if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$mail->addAddress($email);
				}
			}

			// ===== 첨부파일(절대경로/파일명 유지) =====
			foreach ([$attachment1, $attachment2, $attachment3, $attachment4] as $att) {
				if (!$att) continue;
				$abs = $uploadRoot . '/' . basename($att);
				if (is_readable($abs)) {
					$mail->addAttachment($abs, basename($att));
				} else {
					file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] ATTACH READ FAIL: $abs\n", FILE_APPEND);
				}
			}

			// ===== 발송 =====
			$mail->send();
			// 성공이라도 로그 위치를 호출부에서 보여줄 수 있게 반환
			return ['ok' => true, 'log' => $logFile];

		} catch (Exception $e) {
			// PHPMailer 예외 메시지 + 마지막 오류 사유 반환
			$err = $mail->ErrorInfo ?: $e->getMessage();
			file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] SEND FAIL: $err\n", FILE_APPEND);
			return ['ok' => false, 'error' => $err, 'log' => $logFile];
		}
	}

	function getRandBalance($rand_id){

		global $dbConn;

		
				$qry1 = "select sum(amt) from rand_pay where reserveCode is not null && reserveCode <> ''  && rand_id = '$rand_id' 
				&& date_format(tr_date ,'%Y-%m-%d') >= '2015-01-01'";
				$rst1 = mysql_query($qry1);
		//echo $qry1;
				$balance = @mysql_result($rst1,0,0);

				if(empty($balance))
				{
					$balance = "0";
				}

				return $balance;
	}

    function getRandBalance2($rand_id,$reserveCode){

				global $dbConn;

		
				$qry1 = "select sum(amt) from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id'";
				$rst1 = mysql_query($qry1);
		//echo $qry1;
				$balance = @mysql_result($rst1,0,0);

				if(empty($balance))
				{
					$balance = "0";
				}

				return $balance;
	 }

	function getCRandBalance2($rand_id,$reserveCode){

				global $dbConn;

		
				$qry1 = "select sum(amt) from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id' && tr_type='credit'";
				$rst1 = mysql_query($qry1);
		//echo $qry1;
				$balance = @mysql_result($rst1,0,0);

				if(empty($balance))
				{
					$balance = "0";
				}

				return $balance;
	}
    function getDRandBalance2($rand_id,$reserveCode){

				global $dbConn;

		
				$qry1 = "select sum(amt) from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id' && tr_type='debit'";
				$rst1 = mysql_query($qry1);
		//echo $qry1;
				$balance = @mysql_result($rst1,0,0);

				if(empty($balance))
				{
					$balance = "0";
				}

				return $balance;
	 }

	function getPaymemo($rand_id,$reserveCode,$seq){

				global $dbConn;

		
				$qry1 = "select set_memo from rand_pay where reserveCode= '$reserveCode'  && rand_id = '$rand_id' && seq_rand='$seq'";
				$rst1 = mysql_query($qry1);
		//echo $qry1;
				$mm = @mysql_result($rst1,0,0);

			

				return $mm;
	 }
		
	function printRandSelectAirlie($rand_id = false){
	
		  global $dbConn;

			$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N' && issue_airline = 'YES' order by company_area,kor_name asc";
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
	if (!function_exists('printRandSelectCruise')) {
	function printRandSelectCruise($rand_id = false){
	
		  global $dbConn;

			$content = "";
			$qry1 = "select * from member_list where division = 'comp' && del_yn  ='N' && issue_cruise = 'YES' order by company_area,kor_name asc";
			$rst1 = mysql_query($qry1);

			if (!$rst1) {
				return $content;
			}

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
	}
	function getMembercnt($r_code){
		
			global $dbConn;

			$qry1 = "select reserveCode from reserve_traveler where reserveCode = '$r_code' ";
			$rst1 = mysql_query($qry1);
			$num1 = mysql_num_rows($rst1);

			return $num1;
	 }
	function getReserveInfoCntS($pcode,$sdate){
	
			global $dbConn;

			$qry1 = "select sum(p_cnt) as cnt from reserve_info where p_code = '$pcode' && stDate='$sdate' && ( rev_status!='CANCEL') && rev_status='DONE'";
			//echo $qry1."<br>";
			//exit;
			$rst1 = mysql_query($qry1);
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;

	}
	function getAirlineinfo($estimateCode){
		
			global $dbConn;

			$qry1 = "select * from reserve_airline_pnr where reserveCode = '$estimateCode'";
			$rst1 = mysql_query($qry1);
			$row1 = @mysql_fetch_assoc($rst1);
		//echo $qry1;
			return $row1;

	}

    function getAirlineSum($estimateCode){
		
			global $dbConn;

			$qry1 = "select sum(a_airline_amt) as samt from reserve_airline_pnr where reserveCode = '$estimateCode' group by reserveCode ";
			$rst1 = mysql_query($qry1);
			$row1 = @mysql_fetch_assoc($rst1);
		//echo $qry1;
			return $row1;

	 }
	 function getReserveTrInfo($rev,$nm){
		
				global $dbConn;

				$qry1 = "select   *  from reserve_traveler 
									 where reserveCode = '$rev' && traveler_nm = '$nm'
									
								";
								//echo $qry1."<br/>";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	 }

	function getHotelfInfo($h_code){
		
		global $dbConn;

		$qry1 = "select * from product_hotel where h_code='$h_code'";
		$rst1 = mysql_query($qry1);
		$row1 = @mysql_fetch_assoc($rst1);
		
		return $row1;

	 }
	   
	 function getHotelCnt1($gscode,$pcode,$st,$day){
		
			global $dbConn;

			$qry1 = "select count(*) as cnt ,hotel_code from hotel_assign a where p_code='$pcode' && stDate='$st' && day = '$day' && sub_eCode = '$gscode' group by hotel_code";
			$rst1 = mysql_query($qry1);
			while($row1 = mysql_fetch_assoc($rst1)){
				
				$hinfo=getHotelfInfo($row1['hotel_code']);
				
				$content .= "{$hinfo['h_name']} : {$row1['cnt']}개<br>";
						
						
			}
			return $content;

	}
	/**
	* @ 아이디로 개인정보 뽑아오기
	*/
	function getinfo_dbMemberg($user_info){
		
		global $dbConn;

		$qry1 = "select * from member_list where division='guide' && userid = '$user_info' ";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		//echo $qry1;exit;
		return $row1;

	}
	function getguideInfor($gcode,$scode) {

			global $dbConn;

			$qry1 = "select   * from tour_guide 
								 where  grand_eCode ='$gcode' && sub_eCode='$scode'  && p_code not like '%ADD%' limit 1";
			$rst1 = mysql_query($qry1);
			//echo $qry1."<br/>";
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;


	}
	function getguideInfor3($scode) {

				global $dbConn;

				$qry1 = "select   * from tour_guide 
									 where  sub_eCode='$scode' && p_code not like '%ADD%'";
				$rst1 = mysql_query($qry1);
				
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;


	}
	function getPicGrM($gcode) {

			global $dbConn;

			$qry1 = "select count(picCode) cnt,picCode from tour_car 
			where sub_eCode = '$gcode'  group by picCode";
			
			$rst1 = mysql_query($qry1);
			while($row1 = mysql_fetch_assoc($rst1)){
		
					$pickarr = explode("/",$row1['picCode']);
					$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
					$content .= $picknm['pick_name'] . '-' . $picknm['pick_time'] . ' : ' . $row1['cnt'] . '인&nbsp;&nbsp;&nbsp;&nbsp;';
				
				
			}


			return $content;

	}
	function getCCInfor($scode) {

			global $dbConn;

			$qry1 = "select   * from tour_car 
								 where  sub_eCode='$scode' ";
			$rst1 = mysql_query($qry1);
			
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;


	}
	function getGuideInfo2($gscode) {

			global $dbConn;

			$qry1 = "select   * from tour_guide 
						where sub_eCode ='$gscode' ";
			//echo $qry1."<br />";
			$rst1 = mysql_query($qry1);
			$num1 = mysql_num_rows($rst1);
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;

	}
	function getPicGr4($scode,$tnm) {

			global $dbConn;

			$qry1 = "select count(pick_area) cnt,pick_area from reserve_traveler 
			where reserveCode = '$scode' group by pick_area"; //&& traveler_nm = '$tnm' 
			//echo $qry1;
			$rst1 = mysql_query($qry1);
			while($row1 = mysql_fetch_assoc($rst1)){
		
					$pickarr = explode("/",$row1['pick_area']);
					$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
					$content .= "{$picknm['pick_name']}-{$picknm['pick_time']}<br /><br />";
				
				
			}


			return $content;

	}
	function getCarInfo($gscode) {

			global $dbConn;

			$qry1 = "select   * from bus_list 
						where  bus_id ='$gscode' ";
			//echo $qry1."<br />";
			$rst1 = mysql_query($qry1);
			$num1 = mysql_num_rows($rst1);
			$row1 = @mysql_fetch_assoc($rst1);
			
			return $row1;

	}
	function getReserveInfo3($pCode,$st){
		
				global $dbConn;

				$qry1 = "select * from reserve_info where p_code = '$pCode' && stDate='$st' ";
				//echo $qry1."<br />";	
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	function getReserveInfoRoom2($pcode,$sdate,$ecode){
		
				global $dbConn;

				$qry1 = "SELECT  SUM(room_cnt) rcnt  FROM reserve_info  WHERE p_code = '$pcode' && stDate='$sdate' && (rev_status ='ORDER' || rev_status ='DONE')"; //&& reserveCode IN (SELECT reserveCode FROM tour_car WHERE sub_eCode='$ecode' && stDate='$sdate' && p_code='$pcode' )";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;
    }
	function getReserveInfoCntG2($pcode,$sdate,$scode){
		
				global $dbConn;

			
				$qry1 = "SELECT COUNT(*) rcnt  FROM tour_car WHERE p_code = '$pcode' && stDate='$sdate' && sub_eCode='$scode'";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

   }
	function pickBaseCodeC($code = false){
		
		global $dbConn;

		$qry1 = "select pick_code,pick_name from base_pick where pick_m = 'M' && pick_code='$code'
				 union
				 select h_code as pick_code,h_name as pick_name from product_hotel where h_code='$code' order by pick_name asc
				 ";
		$rst1 = mysql_query($qry1,$dbConn);
		
		while($row1 = mysql_fetch_assoc($rst1)){
			
			$content= $row1['pick_name'];
			
		}

		return $content;

	}
	function getTrSex($rev,$scode) {

			global $dbConn;

			$qry1 = "select   *  from reserve_traveler a,tour_car b 
					 where a.reserveCode=b.reserveCode && a.reserveCode = '$rev' && b.sub_eCode='$scode' && a.traveler_nm = b.rev_nm
					 group by a.sextype";
			
			$rst1 = mysql_query($qry1);
			while($row1 = mysql_fetch_assoc($rst1)){
					if ($row1['sextype'] == "man") {
						$sex= "남자";
					} else if ($row1['sextype'] == "female") {
						$sex = "여자";

					} else if ($row1['sextype'] == "mfemale") {
						 $sex = "혼성";
					}
					$sexcc .= $sex."/";
				
			}


			return $sexcc;

	}
	function getHotelass2($r_code) {
			global $dbConn;

			$qry1 = "select * from hotel_assign where reserveCode='$r_code' order by day asc";		
			$rst1 = mysql_query($qry1);
			$i = 1;
			while($row1 = mysql_fetch_assoc($rst1)){
		
					
					$content .= "$i 일차호텔 : {$row1['hotel_code']} <br />";
					
					
					$i++;
				
			}


			return $content;



	}
	function getHotelrnum($rcode,$trname,$sdate){
		
				global $dbConn;

				/*$qry1 = "select   room_num from hotelroom_assign
							where stDate = '$sdate' && p_code = '$pcode' && room_num <> '99' 
							&& tnm not in (select rev_nm from tour_car where stDate = '$sdate' && p_code = '$pcode')
							group by room_num
								";
								*/
				$qry1 = "select   room_num from hotelroom_assign
							where stDate = '$sdate' && reserveCode = '$rcode' && tnm='$trname' && room_num <> '99'
							group by room_num
								";
			    /////<!-- // -->echo $qry1."<br >";
				$rst1 = mysql_query($qry1);
				//$num1 = mysql_num_rows($rst1);
				$row1 = @mysql_fetch_assoc($rst1);
				
				return $row1;

	}
	//호텔별 정산 행사기간
	function getPeriodbyhotel($p_code,$stDate){

		  global $dbConn;

		  $query = "SELECT b.p_day FROM reserve_info  a , product_master b 
		  WHERE a.p_code = b.p_code  
		  AND a.p_code = '$p_code' 
		  AND a.stDate='$stDate' 
		  AND ( rev_status!='CANCEL' AND rev_status!='WAIT') LIMIT 1";
		  $rst1 = mysql_query($query,$dbConn);
		  $data_row = mysql_fetch_assoc($rst1);
		  $data_row['p_day'] = $data_row['p_day']-1;
		  $c_day = '+'.$data_row['p_day'].' day';
		  $period = $stDate."~".date( "Y-m-d", strtotime( "$stDate $c_day" ));

		  return $period;

	}
	//호텔별정산 상태가져오기
	function getHotelStStatus($grand_eCode,$sub_eCode,$stDate){

		  global $dbConn;

		  $day_before = date( 'Y-m-d', strtotime($stDate . ' -7 day' ) );

		  $query = "SELECT COUNT(*) cnt FROM tour_guide WHERE 
		   grand_eCode = '$grand_eCode' 
		  AND sub_eCode = '$sub_eCode' ";

		  $rst1 = mysql_query($query,$dbConn);
		  $data_row = mysql_fetch_assoc($rst1);
		  
		  if($data_row['cnt'] >0) {
			  
			  $query = "SELECT COUNT(*) cnt FROM hotel_settlesum WHERE grand_eCode = '$grand_eCode' 
			  AND sub_eCode = '$sub_eCode' ";
			  $rst1 = mysql_query($query,$dbConn);
			  $data_row = mysql_fetch_assoc($rst1);

			  if($data_row['cnt'] >0) $status = '<font color=red>정산등록</font>';
			  else $status = '미등록'; 

		  }else{
			  $status = '미등록';
		  }

		  return $status; 

	}
	//호텔리스트
	function getHotelList(){
		  global $dbConn;

		  $query = "SELECT * FROM product_hotel";
		  $rst1 = mysql_query($query,$dbConn);
		  
		  return $rst1;
	}
	//호텔별 정산 기타비용 호텔관련리스트
   function getEtcCostSelect(){
	  global $dbConn;

	  $query = "SELECT * FROM code_base WHERE lvcode1 = 'E01' and lvcode2 !='00' ";
	  $rst1 = mysql_query($query,$dbConn);
	  
	  return $rst1;

   }
   function getMusicalReserveSelfinfo($cCode){
	
		global $dbConn;

		$qry1 = "select * from musical_self_info where reserveCode = '$cCode'";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);

		return $row1;

	}

	function getMusicalBasic($m_code){
		
		global $dbConn;

		$qry1 = "select * from api_musical where m_code = '$m_code'";
		
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		
		return $row1;
		

	}
	
	function getNumMusicalReserveSelf(){
	
		global $dbConn;

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59');

		$qry1 = "select max(reserveNum) from musical_self_info where wdate between '$start_date' and '$stop_date'";
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

	function getBalance1($eCode){
		
		global $dbConn;

		// debit 합계
		$qry1 = "select sum(payment) from payment_history where pay_method = 'init' && reserveCode = '$eCode' order by wdate asc";
		$rst1 = mysql_query($qry1);
		$debit = @mysql_result($rst1,0,0);

		// credit 합계
		$qry2 = "select sum(payment) from payment_history where  status = 'DONE' && reserveCode = '$eCode' order by wdate asc";
		$rst2 = mysql_query($qry2);
		$credit = @mysql_result($rst2,0,0);
		
		$total = $debit - $credit;
	

		return $total;
	}
	//가이드정산코드
	function getGuideCode($grand_eCode,$sub_eCode){
        global $dbConn;

		$query = "SELECT settle_code FROM guide_setmaster WHERE grand_eCode = '$grand_eCode' AND sub_eCode = '$sub_eCode' ";

		$rst1 = mysql_query($query,$dbConn);
		$data_row = mysql_fetch_assoc($rst1);
          
        return $data_row;
	}
	//행사별 정산 행사기간
      function getPeriodbyrev($p_code,$stDate){

          global $dbConn;

          $query = "SELECT b.p_day FROM reserve_info  a , product_master b 
          WHERE a.p_code = b.p_code  
          AND a.p_code = '$p_code' 
          AND a.stDate='$stDate' 
          AND ( rev_status!='CANCEL' AND rev_status!='WAIT') LIMIT 1";
          $rst1 = mysql_query($query,$dbConn);
          $data_row = mysql_fetch_assoc($rst1);
          $data_row['p_day'] = $data_row['p_day']-1;
          $c_day = '+'.$data_row['p_day'].' day';
          $period = $stDate." ~ ".date( "Y-m-d", strtotime( "$stDate $c_day" ));

          return $period;

      }
	 
	  //가이드정산 상태가져오기
	 /*
     * tour_guide(o), guide_setmaster(x)  : 미등록
	 * 저장시 등록
	 * 정산보고완료시 정산완료

	 */
	 function getGuideStatus($grand_eCode,$sub_eCode,$stDate){

		global $dbConn;

		$query = "SELECT COUNT(*) cnt FROM tour_guide WHERE grand_eCode = '$grand_eCode' AND sub_eCode = '$sub_eCode' ";

		$rst1 = mysql_query($query,$dbConn);
		$data_row = mysql_fetch_assoc($rst1);
		
		if($data_row['cnt'] >0) {
			
			$query = "SELECT * FROM guide_setmaster WHERE grand_eCode = '$grand_eCode' AND sub_eCode = '$sub_eCode' ";
			$rst1 = mysql_query($query,$dbConn);
			$data_row = mysql_fetch_assoc($rst1);

			if($data_row['reg_status'] == 'COMPLETE') $status = '정산보고완료';
			else if($data_row['reg_status'] == 'DONE') $status = '등록';
			else $status = '미등록'; 

		}else{
			$status = '미등록';
		}

		return $status; 

	}
	function between($sdate,$edate){
		if( time() >= strtotime($sdate) && time() <= strtotime($edate)){
			return true;
		}else{
			return false;
		}
	}

	function getVStatus($userid){

		global $dbConn;

		$query = "SELECT * FROM emp_vacation WHERE user_id = '$userid' order by wdate desc limit 1";


		$rst1 = mysql_query($query,$dbConn);
		$data_row = mysql_fetch_assoc($rst1);
		
		

		return $data_row; 

	}
	
	
	/*
	function mailsend_g($to,$subj,$contents,$attachment1,$attachment2) {
	
			$mail = new PHPMailer(true);
			
			$mail->IsSMTP();
			//echo "111";
		
			$mail->CharSet = "utf-8"; 
			$mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
			$mail->SMTPAuth = true; // authentication enabled
			$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
			$mail->Host = 'smtp.gmail.com';
			$mail->Port = 587; 
			$mail->Username = "prunetour1@gmail.com";
			$mail->Password = "prtprt0899"
			$mail->IsHTML(true);
			$mail->SetFrom("local@prttour.com","PRUNTOUR");
			$mail->AddReplyTo("local@prttour.com","PRUNTOUR");
			$mail->AltBody = '';
			$mail->Subject = $subj;
			
			$mail->MsgHTML($contents);
			
			$mail->AddAddress($to);
			
			///echo $attachment2;
			
			if ($attachment1 !="") {
				$mail->AddAttachment("upload/".$attachment1."");

			}
			if ($attachment2 !="") {
				$mail->AddAttachment("upload/".$attachment2."");

			}
			
			
			if(!$mail->Send()){
				
			  return $mail->ErrorInfo();
			} else {
				
			   return true;
			}
	}
	*/
