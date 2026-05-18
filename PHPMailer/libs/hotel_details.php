<?
	include "./include/inc_base.php";

	function printReviewComment($show_code){
		
		global $dbConn;

		$qry1 = "select * from chan_reviewboard where p_code = '$show_code' order by wdate desc";
		$rst1 = mysql_query($qry1);

		$num1 = 0;

		while($row1 = mysql_fetch_assoc($rst1)){
			
			//$write_id = substr($row1[user_id],0,3)."***";




			$date = explode(" ",$row1[wdate]);

			if($row1[userfile1])
			{
				$userfile1 = "<img src=\"upload/$row1[userfile1]\"><br>";
			}
			else
			{
				$userfile1 = "";
			}

			$row1[content] = nl2br($row1[content]);

			$content .= "
			<tr bgcolor=#FFFFFF >
				<td width=20% height=40 valign=top style='padding-left:3px;padding-top:5px'>$row1[user_id]<br><span style=\"font-size:8pt;color:#cccccc\">$date[0]</span></td>
				<td width=80% bgcolor=#FFFFFF height=40 valign=top style='padding-left:3px;padding-top:5px'>$userfile1 $row1[content]</td>
			</tr>	
			<tr><td height=1 colspan=2 bgcolor=#cccccc></td></tr>
			";

			$num1++;
		}

		if($num1 == "0")
		{
			$content .= "
			<tr>
				<td bgcolor=#FFFFFF height=40 valign=top style='padding-left:3px;padding-top:5px' align=center>첫 리뷰를 남겨주세요!</td>
			</tr>
			";
		}

		return $content;

	}


	function printComment($p_code){
		
		global $dbConn;

		$qry1 = "select * from chan_shop_cmtboard where p_code = '$p_code' order by wdate desc";
		$rst1 = mysql_query($qry1);

		$num1 = 0;

		while($row1 = mysql_fetch_assoc($rst1)){
			
			$write_id = substr($row1[user_id],0,3)."***";


			// 답변글이 있는지 체크
			$qry2 = "select * from chan_shop_cmtboard where parent = '$row1[seq_no]'";
			$rst2 = mysql_query($qry2);
			$row2 = mysql_fetch_assoc($rst2);
		
			if($row2[content])
			{
				$row2[content] = "답변 : ".$row2[content];
			}
			$content .= "
			<tr>
				<td bgcolor=#f4f4f4 align=right>&nbsp;&nbsp;<b>$write_id</b> 님이 $row1[wdate] 작성&nbsp;&nbsp;</td>
			</tr>
			<tr><td height=1 bgcolor=#cccccc></td></tr>
			<tr>
				<td bgcolor=#FFFFFF height=40 valign=top style='padding-left:3px;padding-top:5px'>$row1[content]</td>
			</tr>
			<tr>
				<td bgcolor=#F9F9F9 height=40 valign=top style='padding-left:23px;padding-top:5px'>$row2[content]</td>
			</tr>			
			";

			$num1++;
		}

		if($num1 == "0")
		{
			$content .= "
			<tr>
				<td bgcolor=#FFFFFF height=40 valign=top style='padding-left:3px;padding-top:5px' align=center>이 상품에 궁금사항이 있으세요?</td>
			</tr>
			";
		}

		return $content;

	}

	if($hotel_id)
	{
		require_once('./libs/nusoap.php');
		$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
		$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
		$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
		$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
		$client = new nusoap_client('http://api.ean.com/ean-services/ws/hotel/v3?wsdl&apiKey=yremf74gpr2sfjxvzrw7q52e',true);
		$err = $client->getError();

		$client->soap_defencoding = "UTF-8"; 
		$client->decode_utf8 = false; 

		$apiKey = 'yremf74gpr2sfjxvzrw7q52e';
		$secret = 'pYpmvm35';
		$timestamp = gmdate('U'); // 1200603038 (Thu, 17 Jan 2008 20:50:38 +0000)
		$sig = md5($apiKey . $secret . '1359684413');
		//echo $sig;
		if ($err) {
			echo '<h2>Constructor  error</h2><pre>' . $err . '</pre>';
		}

		$param = array(
			'apiKey'         => 'yremf74gpr2sfjxvzrw7q52e',
			'cid'          => '55505',
			'sig'          => '$sig',
			'minorRev'          => '1',
			'customerIpAddress'          => '50.62.130.169',
			'customerSessionId'          => 'dongbu',
			'customerUserAgent'          => '',
			'locale'          => 'ko_KR',
			'currencyCode'          => 'USD',
			'hotelId'           => $hotel_id
			
		);

		//'ShowAddedDate'          => '1900-12-17T09:30:47.0Z'
		$headers = '

		';

		 //$result= $client->setEndpoint("http://api.ean.com/ean-services/ws/hotel/v3");
		 
		 $result = $client->call('getInformation', array('HotelInformationRequest' => $param),'', '', false, true); 


		$hotel_info = getHotelinfo($p_code);


		if($mode == "available_check")
		{
			// 룸 가능여부 체크하기


			// 참가인원 배열만들기

			for($a=0; $a<$roomCnt; $a++)
			{
				$array_add = $a+1;

				$child_arr_name1 = ${'child_age'.$array_add}[0];
				$child_arr_name2 = ${'child_age'.$array_add}[1];
				$child_arr_name3 = ${'child_age'.$array_add}[2];

				// 성인,아동,아동1나이,아동2나이,아동3나이

				$join_member .= $adult[$a].",".$child[$a].",".$child_arr_name1.",".$child_arr_name2.",".$child_arr_name3."NaN";
			
				//echo $join_member."<br>";
			}




			//echo $roomResult;
//'RoomGroup' => array("Room" => array(array('numberOfAdults'	=> '2','numberOfChildren'	 => '1','childAges'	=> '5'),array('numberOfAdults'	=> '1','numberOfChildren'	 => '1','childAges'	=> '5'))),

			switch($roomCnt)
			{
				case "1":
					$RoomGroup = array("Room" => array(array('numberOfAdults'	=> $adult[0],'numberOfChildren'	 => $child[0],'childAges'	=> $child_age1[0])));
					break;
				case "2":
					$RoomGroup = array("Room" => array(array('numberOfAdults'	=> $adult[0],'numberOfChildren'	 => $child[0],'childAges'	=> $child_age1[0]),array('numberOfAdults'	=> $adult[1],'numberOfChildren'	 => $child[1],'childAges'	=> $child_age1[1])));
					break;
				case "3":
					$RoomGroup = array("Room" => array(array('numberOfAdults'	=> $adult[0],'numberOfChildren'	 => $child[0],'childAges'	=> $child_age1[0]),array('numberOfAdults'	=> $adult[1],'numberOfChildren'	 => $child[1],'childAges'	=> $child_age1[1]),array('numberOfAdults'	=> $adult[2],'numberOfChildren'	 => $child[2],'childAges'	=> $child_age1[2])));
					break;
				case "4":
					$RoomGroup = array("Room" => array(array('numberOfAdults'	=> $adult[0],'numberOfChildren'	 => $child[0],'childAges'	=> $child_age1[0]),array('numberOfAdults'	=> $adult[1],'numberOfChildren'	 => $child[1],'childAges'	=> $child_age1[1]),array('numberOfAdults'	=> $adult[2],'numberOfChildren'	 => $child[2],'childAges'	=> $child_age1[2]),array('numberOfAdults'	=> $adult[3],'numberOfChildren'	 => $child[3],'childAges'	=> $child_age1[3])));
					break;
			}



			$param2 = array(
				'apiKey'         => 'yremf74gpr2sfjxvzrw7q52e',
				'cid'          => '55505',
				'sig'          => '$sig',
				'minorRev'          => '1',
				'customerIpAddress'          => '184.74.229.115',
				'customerSessionId'          => 'dongbu',
				'customerUserAgent'          => '',
				'locale'          => 'ko_KR',
				'currencyCode'          => 'USD',
				'hotelId'           => $hotel_id,
				'arrivalDate'	=> $arrivalDate,
				'departureDate'	=> $departureDate,
				'RoomGroup' => $RoomGroup,

			);


			//'ShowAddedDate'          => '1900-12-17T09:30:47.0Z'
			$headers = '

			';

			 //$result= $client->setEndpoint("http://api.ean.com/ean-services/ws/hotel/v3");
			 
			 $result2 = $client->call('getAvailability', array('HotelRoomAvailabilityRequest' => $param2),'', '', false, true); 

			//echo '<h2>Request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>'; 

			//echo '<h2>Result</h2><pre>';
			//print_r($result2);
			//echo '</pre>';

			// Cancel Policy
			$checkInInstructions = $result2['HotelRoomAvailabilityResponse'] ['checkInInstructions'];

			 // Room Type 배열가져오기
			 $HotelListCnt =  count($result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse']);

			//echo "<hr>";
			//echo $result2['HotelRoomAvailabilityResponse'] ['!size'];
			//echo "<hr>";

			if($result2['HotelRoomAvailabilityResponse'] ['!size']>1)
			{
					 for($i=0; $i<$result2['HotelRoomAvailabilityResponse'] ['!size']; $i++)
					{
						 $rateCode = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['rateCode'];
						 $roomTypeCode = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['roomTypeCode'];
						 $roomTypeDescription = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['roomTypeDescription'];
						 $supplierType = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['supplierType'];

						 $taxRate = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['taxRate'];
						 $nonRefundable = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['nonRefundable'];
						 $depositRequired = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['depositRequired'];
						 $supplierType = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['supplierType'];




						// Rate 
						$chargeableRate = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']['ChargeableRateInfo']['Surcharges']['Surcharge']['!amount'];
						
						$totalAmt = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']['ChargeableRateInfo']['!total'];

						if($hotel_info[sale_flag] == "flat")
						{
							$totalAmt = $totalAmt + $hotel_info[hotels_margin];
						}
						else
						{
							$totalAmt = $totalAmt + (($totalAmt*$hotel_info[hotels_margin])/100);
						}

						


						$hotel_name = $result['HotelInformationResponse'] ['HotelSummary'] ['name'];
						$hotel_name = addslashes($hotel_name);
						$roomTypeDescription = addslashes($roomTypeDescription);
						$hotel_array_value = $hotel_id."@".$hotel_info[p_name]."@".$roomTypeDescription."@".$arrivalDate."@".$departureDate."@".$supplierType."@".$roomTypeCode."@".$rateCode."@".$totalAmt."@".$join_member."@".$roomCnt;

						//$roomName .= "<tr bgcolor=#f4f4f4 ><td width=80% height=30>&nbsp;<b>".$roomTypeDescription."</b></td><td width=20%><a href=\"javascript:go_cart('hotel','$hotel_array_value')\">예약하기</a></td></tr>";

						$rateInfoCnt = count($result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']);
						//$roomName .= "<tr><td colspan=2>NightlyRate : ".$result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']['ChargeableRateInfo']['NightlyRatesPerRoom']['NightlyRate']['!rate']."</td></tr>";
						
						//$roomName .= "<tr><td colspan=2>Surcharge : ".$result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']['ChargeableRateInfo']['Surcharges']['Surcharge']['!amount']."</td></tr>";

						//$roomName .= "<tr><td colspan=2 bgcolor=#f4f4f4>Total : ".$result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']['ChargeableRateInfo']['!total']."</td></tr>";

						//$roomName .= "<tr><td colspan=2 bgcolor=#f4f4f4>".$result['HotelInformationResponse'] ['RoomTypes'] ['RoomType'][$i]['descriptionLong']."</td></tr>";
						//$roomName .= "<tr><td colspan=2 bgcolor=#f4f4f4>surchargeTotal : ".$result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']['ChargeableRateInfo']['!surchargeTotal']."</td></tr>";
						//$roomName .= "<tr><td colspan=2 bgcolor=#f4f4f4>nightlyRateTotal : ".$result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RateInfo']['ChargeableRateInfo']['!nightlyRateTotal']."</td></tr>";

						// <tr><td colspan=3 bgcolor=#FFFFFF>옵션 : ".$result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] [$i]['RoomType']."</td></tr>
						$roomName .= "<tr bgcolor=#eeeeee>
						<td height=28 class=\"gray_s\" align=center>$roomTypeCode</td>
						<td><b class=\"blue_b\">".$roomTypeDescription."</b></td>
						<td><img src=images/hotel/btn_booking02.gif align=absmiddle onClick=\"javascript:go_cart('hotel','$hotel_array_value')\" style=\"cursor:pointer\"></td>
						</tr>
						<tr><td colspan=3 height=28 bgcolor=#FFFFFF align=right style=\"font-size:14pt;font-weight:bold\">Total : $".$totalAmt."</td></tr>
						<tr><td colspan=3 bgcolor=#FFFFFF>".$result['HotelInformationResponse'] ['RoomTypes'] ['RoomType'][$i]['descriptionLong']."</td></tr>
						";


					}
			}
			else
			{

						 $rateCode = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['rateCode'];
						 $roomTypeCode = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['roomTypeCode'];
						 $roomTypeDescription = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['roomTypeDescription'];
						 $supplierType = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['supplierType'];

						 $taxRate = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['taxRate'];
						 $nonRefundable = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['nonRefundable'];
						 $depositRequired = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['depositRequired'];
						 $supplierType = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['supplierType'];




						// Rate 
						$chargeableRate = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['RateInfo']['ChargeableRateInfo']['Surcharges']['Surcharge']['!amount'];
						
						$totalAmt = $result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['RateInfo']['ChargeableRateInfo']['!total'];

						if($hotel_info[sale_flag] == "flat")
						{
							$totalAmt = $totalAmt + $hotel_info[hotels_margin];
						}
						else
						{
							$totalAmt = $totalAmt + (($totalAmt*$hotel_info[hotels_margin])/100);
						}


						$hotel_name = $result['HotelInformationResponse'] ['HotelSummary'] ['name'];
						$hotel_name = addslashes($hotel_name);
						$roomTypeDescription = addslashes($roomTypeDescription);
						$hotel_array_value = $hotel_id."@".$hotel_info[p_name]."@".$roomTypeDescription."@".$arrivalDate."@".$departureDate."@".$supplierType."@".$roomTypeCode."@".$rateCode."@".$totalAmt."@".$join_member."@".$roomCnt;


						$rateInfoCnt = count($result2['HotelRoomAvailabilityResponse'] ['HotelRoomResponse'] ['RateInfo']);

						$roomName .= "<tr bgcolor=#eeeeee>
						<td height=28 class=\"gray_s\" align=center>$roomTypeCode</td>
						<td><b class=\"blue_b\">".$roomTypeDescription."</b></td>
						<td><img src=images/hotel/btn_booking02.gif align=absmiddle onClick=\"javascript:go_cart('hotel','$hotel_array_value')\" style=\"cursor:pointer\"></td>
						</tr>
						<tr><td colspan=3 height=28 bgcolor=#FFFFFF align=right style=\"font-size:14pt;font-weight:bold\">Total : $".$totalAmt."</td></tr>
						<tr><td colspan=3 bgcolor=#FFFFFF>".$result['HotelInformationResponse'] ['RoomTypes'] ['RoomType']['descriptionLong']."</td></tr>
						";


			}



			if($HotelListCnt == 0)
			{
				$errorMsg = $result2['HotelRoomAvailabilityResponse']['EanWsError']['category'];
				$errorMsg_detail = $result2['HotelRoomAvailabilityResponse']['EanWsError']['presentationMessage'];

				$roomName = "<tr bgcolor=#eeeeee><td colspan=3 align=center height=45><b>$errorMsg</b></td></tr>";
				$roomName .= "<tr bgcolor=#ffffff><td colspan=3 align=center height=90>$errorMsg_detail</td></tr>";
			}
		}
		else
		{
			 $roomTypeCnt =  count($result['HotelInformationResponse'] ['RoomTypes'] ['RoomType']);

			 for($i=0; $i<$roomTypeCnt; $i++)
				{
					$roomRoomAmenityCnt =  count($result['HotelInformationResponse'] ['RoomTypes'] ['RoomType'][$i]['roomAmenities']);

					for($k=0; $k<$roomRoomAmenityCnt; $k++)
					{
						$roomAmentity .= $result['HotelInformationResponse'] ['RoomTypes'] ['RoomType'][$i]['roomAmenities']['RoomAmenity']['amenity'];
					}

					 $roomName .= "<tr><td bgcolor=#f4f4f4 height=30>&nbsp;<b class=\"blue_b\">".$result['HotelInformationResponse'] ['RoomTypes'] ['RoomType'][$i]['description']."</b></td></tr>";
					 $roomName .= "<tr><td bgcolor=#FFFFFF style=\"padding-left:5px;padding-top:5px\">".$result['HotelInformationResponse'] ['RoomTypes'] ['RoomType'][$i]['descriptionLong']."</td></tr>";
					 $roomName .= "<tr><td bgcolor=#FFFFFF style=\"padding-left:5px;padding-top:5px\">[객실정보 더보기]</td></tr>";
					 $roomName .= "<tr><td bgcolor=#FFFFFF style=\"padding-left:5px;padding-top:5px\">".$roomAmentity."</td></tr>";

					 unset($roomAmentity);
				}
		}

		// Hotel Image 가져오기
		 $HotelImageCnt =  count($result['HotelInformationResponse'] ['HotelImages'] ['HotelImage']);

		$hotelImage = "<tr>";

		 for($i=1; $i<$HotelImageCnt; $i++)
			{
				$image_alt = $result['HotelInformationResponse'] ['HotelImages'] ['HotelImage'][$i]['caption'];

				if($i%4 == 0)
				{
					 $hotelImage .= "<td width=25% height=73 align=center bgcolor=#f4f4f4><a href=\"".$result['HotelInformationResponse'] ['HotelImages'] ['HotelImage'][$i]['url']."\" title=\"thumnail\"><img alt=".$image_alt." src=".$result['HotelInformationResponse'] ['HotelImages'] ['HotelImage'][$i]['thumbnailUrl']." border=0></a></td></tr><tr>";
				}
				else
				{
					 $hotelImage .= "<td width=25% height=73  align=center bgcolor=#f4f4f4><a href=\"".$result['HotelInformationResponse'] ['HotelImages'] ['HotelImage'][$i]['url']."\" title=\"thumnail\"><img  alt=".$image_alt." src=".$result['HotelInformationResponse'] ['HotelImages'] ['HotelImage'][$i]['thumbnailUrl']." border=0></a></td>";
				}
				
			}




		 /**
		 * @ 호텔변수를 우리 호텔테이블과 매치시키기
		 */ 
		
		 $hotel_info[p_name] = $result['HotelInformationResponse'] ['HotelSummary'] ['name'];
		 $hotel_info[address] = $result['HotelInformationResponse'] ['HotelSummary'] ['address1'];
		 $hotel_info[city] = $result['HotelInformationResponse'] ['HotelSummary'] ['city'];
		 $hotel_info[state] = $result['HotelInformationResponse'] ['HotelSummary'] ['stateProvinceCode'];
		 $hotel_info[zipcode] = $result['HotelInformationResponse'] ['HotelSummary'] ['postalCode'];

		 // 호텔분류
		 $hotel_info[propertyCategory] = $result['HotelInformationResponse'] ['HotelSummary'] ['propertyCategory'];

		 switch($hotel_info[propertyCategory])
		 {
			 case "1":
				 $hotel_category = "hotel";
				 break;
			 case "2":
				 $hotel_category = "motel";
				 break;
			 case "3":
				 $hotel_category = "resort";
				 break;
			 case "4":
				 $hotel_category = "inn or lodge";
				 break;
			 case "5":
				 $hotel_category = "bed & breakfast";
				 break;
			 case "6":
				 $hotel_category = "guest house";
				 break;
		 }

		 // 호텔등급
		 $hotel_info[hotelRating] = $result['HotelInformationResponse'] ['HotelSummary'] ['hotelRating'];

		$ratio_value = $hotel_info[hotelRating];

		for($a=0; $a<$ratio_value; $a++)
		{
			$star_icon_a .= "<img src=images/icon_star.png >&nbsp;";
		}



		// 최저 최고가
		 $hotel_info[highRate] = $result['HotelInformationResponse'] ['HotelSummary'] ['highRate'];
		 $hotel_info[lowRate] = $result['HotelInformationResponse'] ['HotelSummary'] ['lowRate'];

		 $middle_price = number_format((($hotel_info[highRate]+$hotel_info[lowRate])/2),2);
	     $mainImg = "<img  src=".$result['HotelInformationResponse'] ['HotelImages'] ['HotelImage'][0]['url']." width=300>";

		  // 위도,경도
		 $hotel_info[latitude] = $result['HotelInformationResponse'] ['HotelSummary'] ['latitude'];
		 $hotel_info[longitude] = $result['HotelInformationResponse'] ['HotelSummary'] ['longitude'];

		 //$hotel_info[phone] = $result['HotelInformationResponse'] ['HotelSummary'] ['name'];
		 //$hotel_info[p_name] = $result['HotelInformationResponse'] ['HotelSummary'] ['name'];
		 //$hotel_info[p_name] = $result['HotelInformationResponse'] ['HotelSummary'] ['name'];
		 //$hotel_info[p_name] = $result['HotelInformationResponse'] ['HotelSummary'] ['name'];

	}
	else
	{
		$hotel_info = getHotelinfo($p_code);

		// 지역코드
		$hotel_area_code = "H02".$hotel_info[c_code1].$hotel_info[c_code2];

		$hotel_area = codebasename($hotel_area_code);

		$hotel_type_name = codebasename($hotel_info[hotel_type]);

		 switch($hotel_info[hotel_type])
		 {
			 case "hotel":
				 $hotel_category = "호텔";
				 break;
			 case "gh":
				 $hotel_category = "게스트하우스";
				 break;
			 case "s_hotel":
				 $hotel_category = "특급호텔";
				 break;
			 case "u_hotel":
				 $hotel_category = "유스호스텔";
				 break;
			 case "resort":
				 $hotel_category = "리조트";
				 break;
		 }


		$mainImg = "<img src=\"./product_img/$hotel_info[userfile1]\" width=300 height=225>";

		if($hotel_info[userfile2])
		{
			$userfile2 = "<a href=\"./product_img/$hotel_info[userfile2]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile2]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile3])
		{
			$userfile3 = "<a href=\"./product_img/$hotel_info[userfile3]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile3]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile4])
		{
			$userfile4 = "<a href=\"./product_img/$hotel_info[userfile4]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile4]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile5])
		{
			$userfile5 = "<a href=\"./product_img/$hotel_info[userfile5]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile5]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile6])
		{
			$userfile6 = "<a href=\"./product_img/$hotel_info[userfile6]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile6]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile7])
		{
			$userfile7 = "<a href=\"./product_img/$hotel_info[userfile7]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile7]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile8])
		{
			$userfile8 = "<a href=\"./product_img/$hotel_info[userfile8]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile8]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile9])
		{
			$userfile9 = "<a href=\"./product_img/$hotel_info[userfile9]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile9]\" border=0 width=70 height=70></a>";
		}
		if($hotel_info[userfile10])
		{
			$userfile10 = "<a href=\"./product_img/$hotel_info[userfile10]\" border=0 title=\"thumnail\"><img src=\"./product_img/$hotel_info[userfile10]\" border=0 width=70 height=70></a>";
		}


		$hotelImage = "<tr>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile2</td>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile3</td>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile4</td>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile5</td>
		</tr>";

		$hotelImage .= "<tr>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile6</td>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile7</td>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile8</td>
		<td width=25% height=73 align=center bgcolor=#f4f4f4>$userfile9</td>
		</tr>";


	}

	for($c=0; $c<18; $c++)
	{
		$child_select .= "<option value=$c>$c"; 
	}

	
	include "inc_top.php";
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
  <tr>
    <td align="center" background="images/comm/topmenu_bg.gif">
      <table width="910" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="15" background="images/hotel/topmenu_bg01.gif"></td>
          <td><A href="today_deal.php"  onmouseout="changeSearch_out()" onmouseover="MM_swapImage('Image31','','images/hotel/topmenu_01_on.gif',1)"><img src="images/hotel/topmenu_01.gif" name=Image31 border=0 id=Image31></a></td>
          <td><A href="tour.php"  onmouseout="changeSearch_out()" onmouseover="MM_swapImage('Image32','','images/hotel/topmenu_02_on.gif',1)"><img src="images/hotel/topmenu_02.gif" name=Image32 border=0 id=Image32></a></td>
          <td><A href="hotel.php"  onmouseout="changeSearch_out()" onmouseover="MM_swapImage('Image33','','images/hotel/topmenu_03_on.gif',1)"><img src="images/hotel/topmenu_03_on.gif" name=Image33 border=0 id=Image33></a></td>
          <td><A href="musical.php"  onmouseout="changeSearch_out()" onmouseover="MM_swapImage('Image34','','images/hotel/topmenu_04_on.gif',1)"><img src="images/hotel/topmenu_04.gif" name=Image34 border=0 id=Image34></a></td>
          <td><A href="cart.php"  onmouseout="changeSearch_out()" onmouseover="MM_swapImage('Image35','','images/hotel/topmenu_05_on.gif',1)"><img src="images/hotel/topmenu_05.gif" name=Image35 border=0 id=Image35></a></td>
          <td width="15" background="images/hotel/topmenu_bg01.gif"></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<!------------------[E] TOP TABLE ------------------>

<!------------------[S] Contents TABLE ------------------>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center"><table width="910" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
      <tr>
        <td height="15"></td>
      </tr>
      <tr>
        <td align="center"><table width="880" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="200" valign=top>
			<? include "include_hotel_left.php"; ?>
			</td>
            <td width="20"></td>
            <td width="660" valign="top">
			<!-- 호텔 컨텐츠 시작 -->
			<script>
				function print_content(p_code,hotel_id){

					window.open("print_hotel.php?p_code=" + p_code + "&hotel_id=" + hotel_id,"hotel","width=800,height=600,scrollbars=1");
				
				}
			</script>
						<table width="100%" border="0" cellpadding="0" cellspacing="3" bgcolor="#dedede">
							<tr>
								<td height="60" align="center" bgcolor="#FFFFFF"><table width="95%" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td height="30" class="blue_tl"><?= $hotel_info[p_name] ?></td>
											<td rowspan="2" align="right" class="stxt"><a href="javascript:print_content('<?= $p_code ?>','<?= $hotel_id ?>')"><img src="images/comm/btn_print.gif" width="60" height="20" border=0></a></td>
										</tr>
										<tr>
											<td class="grey">- <?= $hotel_area[comment] ?> / <?= $hotel_category ?></td>
										</tr>
								</table></td>
							</tr>
						</table>
						<br>
						<table width="660" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td width="138" valign="top">
								<table width="100%" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td><table width="306" border="0" cellpadding="0" cellspacing="3" bgcolor="#dedede">
													<tr>
														<td bgcolor="#FFFFFF"><?= $mainImg ?></td>
													</tr>
											</table></td>
										</tr>
								</table>
								</td>
								<td width="20"></td>
								<td width="334" valign="top">
									<div id="gallery">
									<table width=100% border=0 cellpadding=0 cellspacing=0>
									<?= $hotelImage ?>
									</table>	
									</div>
								</td>
							</tr>
						</table>
						<br>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td colspan=2 height="2" bgcolor="#0073e6"></td>
							</tr>
							<tr>
								<td colspan=2 height="8" bgcolor="#f4f4f4"></td>
							</tr>
							<tr bgcolor="#f4f4f4">
								<td width=230 align=center valign=top>
									<table width="220" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td align=left>&nbsp;<span style="font-size:10pt;">호텔등급 : </span><?= $star_icon_a ?>&nbsp;&nbsp;</td>
										</tr>
										<tr>
											<td align=left>&nbsp;<span style="font-size:10pt;">평균가 :</span> <span style="font-size:14pt;font-weight:bold;">$<?= $middle_price ?></span>&nbsp;&nbsp;</td>
										</tr>
									</table>
								</td>
								<td width=430 align="center" ><table width="100%" border="0" cellspacing="0" cellpadding="0">
								<form name=search action=<?= $PHP_SELF ?> method=post>
								<input type=hidden name=p_code value="<?= $p_code ?>">
								<input type=hidden name=hotel_id value="<?= $hotel_id ?>">
								<input type=hidden name=mode value=available_check>
										<tr>
											<td height="25" colspan="2" class="blue_b"><img src="images/comm/blt_detail.gif" width="10" height="10"><?= $hotel_info[p_name] ?></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr>
										<tr>
											<td height="25" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">호텔코드 </td>
											<td><?= $hotel_info[p_code] ?></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr>
										<tr>
											<td height="25" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">호텔등급 </td>
											<td><?= $hotel_info[hotelRating] ?></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr>
										<tr>
											<td width="100" height="25" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">주소  </td>
											<td width="220"><?= $hotel_info[address] ?><br><?= $hotel_info[city] ?>, <?= $hotel_info[state] ?> <?= $hotel_info[zipcode] ?></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr>
										<!-- <tr>
											<td height="25" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">전화 </td>
											<td><?= $hotel_info[phone] ?></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr> -->
										<tr>
											<td height="25" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">평균요금 </td>
											<td>$<?= number_format($hotel_info[lowRate],2) ?> ~ $<?= number_format($hotel_info[highRate],2) ?></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr>
										<tr>
											<td height="25" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">체크인 </td>
											<td><input type=text name=arrivalDate size=10 class="txt" id="datepicker111" value="<?= $arrivalDate ?>"></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr>
										<tr>
											<td height="25" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">체크아웃 </td>
											<td><input type=text name=departureDate size=10 class="txt" id="datepicker112" value="<?= $departureDate ?>"></td>
										</tr>
										<tr>
											<td height="1" colspan="2" background="images/comm/line_dot02.gif"></td>
										</tr>
										<? if($mode == "available_check"): ?>
										<tr id="roomResult">
											<td colspan=2>
												<table width=100% border=0 cellpadding=0 cellspacing=0>
													<tr>
														<td width="100" height="35" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">객실수
														</td>
														<td width="220" ><b><?= $roomCnt ?></b>&nbsp;&nbsp;[<a href="javascript:roomModify()">객실수 수정</a>]</td>
													</tr>
													<tr>
														<td colspan=2>
														<table width=100% border=0 cellpadding=0 cellspacing=0 bgcolor=#cccccc>
															<tr>
																<td width=10% align=center>Room</td>
																<td width=20% align=center>성인(18+) </td>
																<td width=20% align=center>아동(0-17)</td>
																<td width=50% align=center>아동 연령</td>
															</tr>
															<?
															$roomArray = explode("NaN",$join_member);

															for($r=0; $r<count($roomArray)-1; $r++)
															{
																$roomValue = explode(",",$roomArray[$r]);

																$roomNum = $r+1;
																echo "
																<tr bgcolor=#FFFFFF>
																	<td width=10% align=center>$roomNum</td>
																	<td width=20% align=center>$roomValue[0]</td>
																	<td width=20% align=center>$roomValue[1]</td>
																	<td width=50% align=center>$roomValue[2] $roomValue[3] $roomValue[4]</td>
																</tr>";
															}
															?>
														</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id="roomOpen" style="display:none">
											<td colspan=2>
												<table width=100% border=0 cellpadding=0 cellspacing=0>
													<tr>
														<td width="100" height="35" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">객실수
														</td>
														<td width="220">
														<select name=roomCnt onChange="javascript:go_room(this.value)">
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
														</select>
														</td>
													</tr>
													<tr>
														<td colspan=2>
														<table width=100% border=0 cellpadding=0 cellspacing=0 bgcolor=#cccccc>
															<tr>
																<td width=10% align=center>Room</td>
																<td width=20% align=center>성인(18+) </td>
																<td width=20% align=center>아동(0-17)</td>
																<td width=50% align=center>아동 연령</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td colspan=2>
														<table width=100% border=0 cellpadding=0 cellspacing=1 >
															<tr id="room1">
																<td width=10% align=center>&nbsp;1</td>
																<td width=20% align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td width=20% align=center><select name=child[] onChange="javascript:go_child_age1(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td width=50% align=left>
																<div id="child_1"></div>
																</td>
															</tr>
															<tr id="room2" style="display:none">
																<td align=center>&nbsp;2</td>
																<td align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td align=center><select name=child[] onChange="javascript:go_child_age2(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td align=left>
																<div id="child_2"></div>
																</td>
															</tr>
															<tr id="room3" style="display:none">
																<td align=center>&nbsp;3</td>
																<td align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td align=center><select name=child[] onChange="javascript:go_child_age3(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td align=left>
																<div id="child_3"></div>
																</td>
															</tr>
															<tr id="room4" style="display:none">
																<td align=center>&nbsp;4</td>
																<td align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td align=center><select name=child[] onChange="javascript:go_child_age4(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td align=left>
																<div id="child_4"></div>
																</td>
															</tr>
														</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<? else: ?>
										<tr>
											<td colspan=2>
												<table width=100% border=0 cellpadding=0 cellspacing=0>
													<tr>
														<td width=100 height="35" class="b"><img src="images/comm/blt_detail.gif" width="10" height="10">객실수
														</td>
														<td width=220>
														<select name=roomCnt onChange="javascript:go_room(this.value)">
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
														</select>
														</td>
													</tr>
													<tr>
														<td colspan=2>
														<table width=100% border=0 cellpadding=0 cellspacing=0 bgcolor=#cccccc>
															<tr>
																<td width=10% align=center>Room</td>
																<td width=20% align=center>성인(18+) </td>
																<td width=20% align=center>아동(0-17)</td>
																<td width=50% align=center>아동 연령</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td colspan=2>
														<table width=100% border=0 cellpadding=0 cellspacing=1 >
															<tr id="room1">
																<td width=10% align=center>&nbsp;1</td>
																<td width=20% align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td width=20% align=center><select name=child[] onChange="javascript:go_child_age1(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td width=50% align=left>
																<div id="child_1"></div>
																</td>
															</tr>
															<tr id="room2" style="display:none">
																<td align=center>&nbsp;2</td>
																<td align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td align=center><select name=child[] onChange="javascript:go_child_age2(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td align=left>
																<div id="child_2"></div>
																</td>
															</tr>
															<tr id="room3" style="display:none">
																<td align=center>&nbsp;3</td>
																<td align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td align=center><select name=child[] onChange="javascript:go_child_age3(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td align=left>
																<div id="child_3"></div>
																</td>
															</tr>
															<tr id="room4" style="display:none">
																<td align=center>&nbsp;4</td>
																<td align=center><select name=adult[]>
																<option value="1">1
																<option value="2">2
																<option value="3">3
																<option value="4">4
																</select></td>
																<td align=center><select name=child[] onChange="javascript:go_child_age4(this.value)">
																<option value="0">0
																<option value="1">1
																<option value="2">2
																<option value="3">3
																</select></td>
																<td align=left>
																<div id="child_4"></div>
																</td>
															</tr>
														</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<? endif; ?>
								</table></td>

							</tr>
							<tr>
								<td colspan=2 height="8" bgcolor="#f4f4f4"></td>
							</tr>
							<tr>
								<td colspan=2 height="2" bgcolor="#0073e6"></td>
							</tr>
						</table>
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td height="10"></td>
								</tr>
								<tr>
									<td align="right"> <input type=image src=images/hotel/btn_dealsearch.gif><!-- <img src="images/comm/btn_cart.gif" width="105" height="32"> --></td>
								</tr>
								</form>
							</table>
						<br>
	<SCRIPT language="javascript">
		function roomModify(){
				document.getElementById('roomResult').style.display = 'none';
				document.getElementById('roomOpen').style.display = 'block';
		}

		function go_room(num){
			
			if(num == '1')
			{
				document.getElementById('room1').style.display = 'block';
				document.getElementById('room2').style.display = 'none';
				document.getElementById('room3').style.display = 'none';
				document.getElementById('room4').style.display = 'none';
			}
			else if(num == '2')
			{
				document.getElementById('room1').style.display = 'block';
				document.getElementById('room2').style.display = 'block';
				document.getElementById('room3').style.display = 'none';
				document.getElementById('room4').style.display = 'none';
			}
			else if(num == '3')
			{
				document.getElementById('room1').style.display = 'block';
				document.getElementById('room2').style.display = 'block';
				document.getElementById('room3').style.display = 'block';
				document.getElementById('room4').style.display = 'none';
			}
			else if(num == '4')
			{
				document.getElementById('room1').style.display = 'block';
				document.getElementById('room2').style.display = 'block';
				document.getElementById('room3').style.display = 'block';
				document.getElementById('room4').style.display = 'block';
			}
		}

		function go_child_age1(num){
			
			if(num == '0')
			{
				document.getElementById('child_1').innerHTML = '';
			}
			else if(num == '1')
			{
				document.getElementById('child_1').innerHTML = '<select name=child_age1[]><?= $child_select ?></select>';
			}
			else if(num == '2')
			{
				document.getElementById('child_1').innerHTML = '<select name=child_age1[]><?= $child_select ?></select>&nbsp;<select name=child_age1[]><?= $child_select ?></select>';
			}		
			else if(num == '3')
			{
				document.getElementById('child_1').innerHTML = '<select name=child_age1[]><?= $child_select ?></select>&nbsp;<select name=child_age1[]><?= $child_select ?></select>&nbsp;<select name=child_age1[]><?= $child_select ?></select>';
			}		
		}

		function go_child_age2(num){
			
			if(num == '0')
			{
				document.getElementById('child_2').innerHTML = '';
			}
			else if(num == '1')
			{
				document.getElementById('child_2').innerHTML = '<select name=child_age2[]><?= $child_select ?></select>';
			}
			else if(num == '2')
			{
				document.getElementById('child_2').innerHTML = '<select name=child_age2[]><?= $child_select ?></select>&nbsp;<select name=child_age2[]><?= $child_select ?></select>';
			}		
			else if(num == '3')
			{
				document.getElementById('child_2').innerHTML = '<select name=child_age2[]><?= $child_select ?></select>&nbsp;<select name=child_age2[]><?= $child_select ?></select>&nbsp;<select name=child_age2[]><?= $child_select ?></select>';
			}		
		}

		function go_child_age3(num){
			
			if(num == '0')
			{
				document.getElementById('child_3').innerHTML = '';
			}
			else if(num == '1')
			{
				document.getElementById('child_3').innerHTML = '<select name=child_age3[]><?= $child_select ?></select>';
			}
			else if(num == '2')
			{
				document.getElementById('child_3').innerHTML = '<select name=child_age3[]><?= $child_select ?></select>&nbsp;<select name=child_age3[]><?= $child_select ?></select>';
			}		
			else if(num == '3')
			{
				document.getElementById('child_3').innerHTML = '<select name=child_age3[]><?= $child_select ?></select>&nbsp;<select name=child_age3[]><?= $child_select ?></select>&nbsp;<select name=child_age3[]><?= $child_select ?></select>';
			}		
		}

		function go_child_age4(num){
			
			if(num == '0')
			{
				document.getElementById('child_4').innerHTML = '';
			}
			else if(num == '1')
			{
				document.getElementById('child_4').innerHTML = '<select name=child_age4[]><?= $child_select ?></select>';
			}
			else if(num == '2')
			{
				document.getElementById('child_4').innerHTML = '<select name=child_age4[]><?= $child_select ?></select>&nbsp;<select name=child_age4[]><?= $child_select ?></select>';
			}		
			else if(num == '3')
			{
				document.getElementById('child_4').innerHTML = '<select name=child_age4[]><?= $child_select ?></select>&nbsp;<select name=child_age4[]><?= $child_select ?></select>&nbsp;<select name=child_age4[]><?= $child_select ?></select>';
			}		
		}

	</SCRIPT>
						<script>
								function view_descrip(str){
									
									if(str == 'room'){
										document.getElementById('room').style.display = 'block';
										document.getElementById('map').style.display = 'none';
										document.getElementById('review').style.display = 'none';
										document.getElementById('etc').style.display = 'none';
										document.getElementById('qna').style.display = 'none';

										document.item_view2.main_img1.src = 'images/hotel/tab01_on.gif';
										document.item_view2.main_img2.src = 'images/hotel/tab02_off.gif';
										document.item_view2.main_img3.src = 'images/hotel/tab03_off.gif';
										document.item_view2.main_img4.src = 'images/hotel/tab04_off.gif';
										document.item_view2.main_img5.src = 'images/hotel/tab05_off.gif';
									}
									else if(str == 'map'){
										document.getElementById('room').style.display = 'none';
										document.getElementById('map').style.display = 'block';
										document.getElementById('review').style.display = 'none';
										document.getElementById('etc').style.display = 'none';
										document.getElementById('qna').style.display = 'none';

										document.item_view2.main_img1.src = 'images/hotel/tab01_off.gif';
										document.item_view2.main_img2.src = 'images/hotel/tab02_on.gif';
										document.item_view2.main_img3.src = 'images/hotel/tab03_off.gif';
										document.item_view2.main_img4.src = 'images/hotel/tab04_off.gif';
										document.item_view2.main_img5.src = 'images/hotel/tab05_off.gif';
									}
									else if(str == 'review'){
										document.getElementById('room').style.display = 'none';
										document.getElementById('map').style.display = 'none';
										document.getElementById('review').style.display = 'block';
										document.getElementById('etc').style.display = 'none';
										document.getElementById('qna').style.display = 'none';

										document.item_view2.main_img1.src = 'images/hotel/tab01_off.gif';
										document.item_view2.main_img2.src = 'images/hotel/tab02_off.gif';
										document.item_view2.main_img3.src = 'images/hotel/tab03_on.gif';
										document.item_view2.main_img4.src = 'images/hotel/tab04_off.gif';
										document.item_view2.main_img5.src = 'images/hotel/tab05_off.gif';
									}
									else if(str == 'etc'){
										document.getElementById('room').style.display = 'none';
										document.getElementById('map').style.display = 'none';
										document.getElementById('review').style.display = 'none';
										document.getElementById('etc').style.display = 'block';
										document.getElementById('qna').style.display = 'none';

										document.item_view2.main_img1.src = 'images/hotel/tab01_off.gif';
										document.item_view2.main_img2.src = 'images/hotel/tab02_off.gif';
										document.item_view2.main_img3.src = 'images/hotel/tab03_off.gif';
										document.item_view2.main_img4.src = 'images/hotel/tab04_on.gif';
										document.item_view2.main_img5.src = 'images/hotel/tab05_off.gif';
									}
									else if(str == 'qna'){
										document.getElementById('room').style.display = 'none';
										document.getElementById('map').style.display = 'none';
										document.getElementById('review').style.display = 'none';
										document.getElementById('etc').style.display = 'none';
										document.getElementById('qna').style.display = 'block';

										document.item_view2.main_img1.src = 'images/hotel/tab01_off.gif';
										document.item_view2.main_img2.src = 'images/hotel/tab02_off.gif';
										document.item_view2.main_img3.src = 'images/hotel/tab03_off.gif';
										document.item_view2.main_img4.src = 'images/hotel/tab04_off.gif';
										document.item_view2.main_img5.src = 'images/hotel/tab05_on.gif';

									}
								}

							var isworking = 0;

							function viewWrite(){

							if(isworking == '0')
								{
								//alert('block');
								WRITE.style.display = "block";	
								isworking = '1';
								}
							else
								{
								//alert('view');
								WRITE.style.display = "none";
								isworking = '0';
								}

							}

							function pic_view(str){
								//alert(str);
								mainImg.src = 'product_img/' + str;
							}
							
							function viewWrite2(){
								
								if(confirm("상품문의는 회원전용입니다.\r\n로그인 페이지로 이동할까요?") == true)
								{
									location.replace('login.php?goUrl=hotel_details.php?p_code=<?= $p_code ?>&hotels_id=<?= $hotels_id ?>');
								}
								else return;
							}

							var isworking2 = 0;

							function viewReviewWrite(){

							if(isworking2 == '0')
								{
								//alert('block');
								REVEWWRITE.style.display = "block";	
								isworking2 = '1';
								}
							else
								{
								//alert('view');
								REVEWWRITE.style.display = "none";
								isworking2 = '0';
								}

							}
							
							function viewReviewWrite2(){
								
								if(confirm("이용후기 남기기는 회원전용입니다.\r\n로그인 페이지로 이동할까요?") == true)
								{
									location.replace('login.php?goUrl=hotel_details.php?p_code=<?= $p_code ?>&hotels_id=<?= $hotels_id ?>');
								}
								else return;
							}

						</script>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<form name=item_view2>
							<tr>
								<td><img src="images/hotel/tab01_on.gif" name=main_img1 onClick="javascript:view_descrip('room')" style="cursor:pointer"></td>
								<td><img src="images/hotel/tab02_off.gif" name=main_img2 onClick="javascript:view_descrip('map')" style="cursor:pointer"></td>
								<td><img src="images/hotel/tab03_off.gif" name=main_img3 onClick="javascript:view_descrip('review')" style="cursor:pointer"></td>
								<td><img src="images/hotel/tab04_off.gif" name=main_img4 onClick="javascript:view_descrip('etc')" style="cursor:pointer"></td>
								<td><img src="images/hotel/tab05_off.gif" name=main_img5 onClick="javascript:view_descrip('qna')" style="cursor:pointer"></td>
							</tr>
							<tr>
								<td height="5" colspan="5"></td>
							</tr></form>
						</table>
						<? if($hotel_id): ?>
						<table width="100%" border="0" cellpadding="0" cellspacing="3" bgcolor="#e9e9e9">
							<tr id="room">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top>	
									<br>
									<table width=98% cellpadding=0 cellspacing=0 bgcolor=#cccccc>
										<? if($mode == "available_check"): ?>
											<?= $roomName ?>
										<? else: ?>
											<?= $roomName ?>
										<? endif; ?>
									</table>
									<br>
								</td>
							</tr>
							<tr id="map" style="display:none">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top>
									<iframe frameborder=0 src=google_map.php?a=<?= $hotel_info[latitude] ?>&b=<?= $hotel_info[longitude] ?> width=640 height=400></iframe>
								</td>
							</tr>
							<tr id="review" style="display:none">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top>
						<table width="650" border="0" cellpadding="0" cellspacing="0" >
							<tr>
								<td colspan=5 valign=top>
									<table width="100%" align=center border="0" cellpadding="3" cellspacing="0" bgcolor="#e9e9e9">
									<?= printReviewComment($p_code); ?>
									</table>
								</td>
							</tr>
						</table>
						<table width="650" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td colspan=5 height=35 align=right bgcolor=#FFFFFF class="b"><? if($HTTP_COOKIE_VARS[MEMLOGIN_INFO_DONGBU]): ?><span style='cursor:hand;' onclick="javascript:viewReviewWrite()"><FONT COLOR=BLACK>[이용후기 남기기]</FONT></span><? else: ?><A href="javascript:viewReviewWrite2()"><FONT COLOR=BLACK>[이용후기 남기기]</FONT></a><? endif; ?>&nbsp;&nbsp;</td>
							</tr>
							<tr ID="REVEWWRITE" style="display:none">
								<td colspan=5>
								<table width=100% border=0 cellpadding=5 cellspacing=0>
								<script>
									function reviewchk(tf){

										if(!tf.review_content.value)
										{
											alert('문의사항을 넣어주세요!');
											tf.review_content.focus();
											return false;
										}

									return true;
									}
								</script>
								<form action=review_save.php name=review_cmt onSubmit="return reviewchk(this)" method=post Enctype="multipart/form-data">
								<input type=hidden name=mode value="cmt_save">
								<input type=hidden name=cmt_spot value="hotel">
								<input type=hidden name=p_code value="<?= $p_code ?>">
								<input type=hidden name=hotel_id value="<?= $hotel_id ?>">
									<tr>
										<td width=80 align=center valign=top><br><b>내용입력</b></td>
										<td width=470 align=left><textarea name="review_content" rows="5" cols="60" class="form_box"></textarea><br>이미지첨부 : <input type=file name=userfile1 size=40></td>
										<td width=100 align=center><input type=submit value="SAVE" style='cursor:hand;width:80;height:75;border:solid 0 #e5e5e5;background-color:#669900;color:white;'></td>
									</tr></form>
								</table>
							</tr>
						</table>
								</td>
							</tr>
							<tr id="etc" style="display:none">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top>
									<br>
									<table width=98% align=center cellpadding=0 cellspacing=0 >
										<tr>
											<td>
											<?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['propertyDescription']); ?>
											<hr>
											<?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['areaInformation']); ?>
											<hr>
											<?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['propertyInformation']); ?>
											</td>
										</tr>
									</table>
									<br>
								</td>
							</tr>
							<tr id="qna" style="display:none">
								<td height="400" bgcolor="#FFFFFF" valign=top>
						<table width="650" border="0" cellpadding="0" cellspacing="3" bgcolor="#e9e9e9">
							<tr>
								<td colspan=5 valign=top>
									<table width="100%" align=center border="0" cellpadding="3" cellspacing="0" bgcolor="#e9e9e9">
									<?= printComment($p_code); ?>
									</table>
								</td>
							</tr>
						</table>
						<table width="650" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td colspan=5 height=35 align=right bgcolor=#FFFFFF class="b"><? if($HTTP_COOKIE_VARS[MEMLOGIN_INFO_DONGBU]): ?><span style='cursor:hand;' onclick="javascript:viewWrite()"><FONT COLOR=BLACK>[질문하기]</FONT></span><? else: ?><A href="javascript:viewWrite2()"><FONT COLOR=BLACK>[질문하기]</FONT></a><? endif; ?>&nbsp;&nbsp;</td>
							</tr>
							<tr ID="WRITE" style="display:none">
								<td colspan=5>
								<table width=100% border=0 cellpadding=5 cellspacing=0>
								<script>
									function reviewchk(tf){

										if(!tf.review_content.value)
										{
											alert('문의사항을 넣어주세요!');
											tf.review_content.focus();
											return false;
										}

									return true;
									}
								</script>
								<form action=cmt_save.php name=review_cmt onSubmit="return reviewchk(this)" method=post >
								<input type=hidden name=mode value="cmt_save">
								<input type=hidden name=cmt_spot value="hotel">
								<input type=hidden name=p_code value="<?= $p_code ?>">
								<input type=hidden name=hotel_id value="<?= $hotel_id ?>">
									<tr>
										<td width=80 align=center valign=top><br><b>내용입력</b></td>
										<td width=470 align=left><textarea name="review_content" rows="5" cols="60" class="form_box"></textarea></td>
										<td width=100 align=center><input type=submit value="SAVE" style='cursor:hand;width:80;height:75;border:solid 0 #e5e5e5;background-color:#669900;color:white;'></td>
									</tr></form>
								</table>
							</tr>
						</table>
								</td>
							</tr>
						</table>
						<? else: ?>
						<table width="100%" border="0" cellpadding="0" cellspacing="3" bgcolor="#e9e9e9">
							<tr id="room">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top>
									<br>
									<? if($mode == "available_check"): ?>
									<table width=98% cellpadding=0 cellspacing=0 bgcolor=#cccccc>
									<?
									$gh_qry1 = "select * from hotel_price where h_code = '$p_code' order by seq_no asc";
									$gh_rst1 = mysql_query($gh_qry1);

									while($gh_row1 = mysql_fetch_assoc($gh_rst1)):

	
									$totalAmt = $gh_row1[room_price];

									$hotel_array_value = $p_code."@".$hotel_info[p_name]."@".$gh_row1[room_type]."@".$arrivalDate."@".$departureDate."@".$supplierType."@".$gh_row1[seq_no]."@".$rateCode."@".$totalAmt."@".$adult."@".$child;
									?>
										<tr bgcolor=#FFFFFF >
											<td height=45 width=80%>&nbsp;<b><?= $gh_row1[room_type] ?></b><br>&nbsp;1박요금 $<?= $gh_row1[room_price] ?></td>
											<td width=20%>&nbsp;&nbsp;&nbsp;<img src="images/comm/btn_booking01.gif" width="105" height="32" border=0 onClick="javascript:go_cart('hotel','<?= $hotel_array_value ?>')" style="cursor:pointer"></a></td>
										</tr>
									<?
									endwhile;
									?>
									</table>
									<? endif; ?>
									<br>
								</td>
							</tr>
							<tr id="map" style="display:none">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top style="padding-top:10px">
								<? if($hotel_info[userfile10]): ?>
								<img src="./product_img/<?= $hotel_info[userfile10] ?>">
								<? else: ?>
								<br>
								<div align=center>호텔 지도맵 준비중입니다. 이용에 불편을 드려서 죄송합니다.</div>
								<? endif; ?>

								</td>
							</tr>
							<tr id="review" style="display:none">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top>
						<table width="650" border="0" cellpadding="0" cellspacing="0" >
							<tr>
								<td colspan=5 valign=top>
									<table width="100%" align=center border="0" cellpadding="3" cellspacing="0" bgcolor="#e9e9e9">
									<?= printReviewComment($p_code); ?>
									</table>
								</td>
							</tr>
						</table>
						<table width="650" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td colspan=5 height=35 align=right bgcolor=#FFFFFF class="b"><? if($HTTP_COOKIE_VARS[MEMLOGIN_INFO_DONGBU]): ?><span style='cursor:hand;' onclick="javascript:viewReviewWrite()"><FONT COLOR=BLACK>[이용후기 남기기]</FONT></span><? else: ?><A href="javascript:viewReviewWrite2()"><FONT COLOR=BLACK>[이용후기 남기기]</FONT></a><? endif; ?>&nbsp;&nbsp;</td>
							</tr>
							<tr ID="REVEWWRITE" style="display:none">
								<td colspan=5>
								<table width=100% border=0 cellpadding=5 cellspacing=0>
								<script>
									function reviewchk(tf){

										if(!tf.review_content.value)
										{
											alert('문의사항을 넣어주세요!');
											tf.review_content.focus();
											return false;
										}

									return true;
									}
								</script>
								<form action=review_save.php name=review_cmt onSubmit="return reviewchk(this)" method=post Enctype="multipart/form-data">
								<input type=hidden name=mode value="cmt_save">
								<input type=hidden name=cmt_spot value="hotel">
								<input type=hidden name=p_code value="<?= $p_code ?>">
								<input type=hidden name=hotel_id value="<?= $hotel_id ?>">
									<tr>
										<td width=80 align=center valign=top><br><b>내용입력</b></td>
										<td width=470 align=left><textarea name="review_content" rows="5" cols="60" class="form_box"></textarea><br>이미지첨부 : <input type=file name=userfile1 size=40></td>
										<td width=100 align=center><input type=submit value="SAVE" style='cursor:hand;width:80;height:75;border:solid 0 #e5e5e5;background-color:#669900;color:white;'></td>
									</tr></form>
								</table>
							</tr>
						</table>
								</td>
							</tr>
							<tr id="etc" style="display:none">
								<td height="400" bgcolor="#FFFFFF" valign=top style="padding-left:10px;padding-top:10px">

									<?= $hotel_info[description] ?>
									<br>
									<?= $hotel_info[description2] ?>

								</td>
							</tr>
							<tr id="qna" style="display:none">
								<td height="400" align="center" bgcolor="#FFFFFF" valign=top>
						<table width="650" border="0" cellpadding="0" cellspacing="3" bgcolor="#e9e9e9">
							<tr>
								<td colspan=5 valign=top>
									<table width="100%" align=center border="0" cellpadding="3" cellspacing="0" bgcolor="#e9e9e9">
									<?= printComment($p_code); ?>
									</table>
								</td>
							</tr>
						</table>
						<table width="650" border="0" cellpadding="0" cellspacing="0" bgcolor="#e9e9e9">
							<tr>
								<td colspan=5 height=35 align=right bgcolor=#FFFFFF class="b"><? if($HTTP_COOKIE_VARS[MEMLOGIN_INFO_DONGBU]): ?><span style='cursor:hand;' onclick="javascript:viewWrite()"><FONT COLOR=BLACK>[질문하기]</FONT></span><? else: ?><A href="javascript:viewWrite2()"><FONT COLOR=BLACK>[질문하기]</FONT></a><? endif; ?>&nbsp;&nbsp;</td>
							</tr>
							<tr ID="WRITE" style="display:none">
								<td colspan=5>
								<table width=100% border=0 cellpadding=5 cellspacing=0>
								<script>
									function reviewchk(tf){

										if(!tf.review_content.value)
										{
											alert('문의사항을 넣어주세요!');
											tf.review_content.focus();
											return false;
										}

									return true;
									}
								</script>
								<form action=cmt_save.php name=review_cmt onSubmit="return reviewchk(this)" method=post Enctype="multipart/form-data">
								<input type=hidden name=mode value="cmt_save">
								<input type=hidden name=cmt_spot value="hotel">
								<input type=hidden name=p_code value="<?= $p_code ?>">
								<input type=hidden name=hotel_id value="<?= $hotel_id ?>">
									<tr>
										<td width=80 align=center valign=top><br><b>내용입력</b></td>
										<td width=470 align=left><textarea name="review_content" rows="5" cols="60" class="form_box"></textarea></td>
										<td width=100 align=center><input type=submit value="SAVE" style='cursor:hand;width:80;height:75;border:solid 0 #e5e5e5;background-color:#669900;color:white;'></td>
									</tr></form>
								</table>
							</tr>
						</table>
								</td>
							</tr>
						</table>
						<? endif; ?>
			<!-- 호텔 컨텐츠 종료 -->
						<br>
						<? if($hotel_id): ?>
						<table width="98%" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td height="1" background="images/comm/line_dot02.gif"></td>
							</tr>
						</table>
						<br>
						<table width="98%" border="0" cellspacing="1" bgcolor=#dddddd cellpadding="0">
							<tr>
								<td colspan=4 height="25" bgcolor=#FFFFFF>&nbsp;<b class="blue_b">호텔 기본정보</b></td>
							</tr>
							<tr>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">호텔 방수</td>
								<td width=35% bgcolor=#FFFFFF style="padding:5px"><?= $result['HotelInformationResponse'] ['HotelDetails'] ['numberOfRooms'] ?> </td>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">호텔 층수</td>
								<td width=35% bgcolor=#FFFFFF style="padding:5px"><?= $result['HotelInformationResponse'] ['HotelDetails'] ['numberOfFloors'] ?></td>
							</tr>
							<tr>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">체크인</td>
								<td width=35% bgcolor=#FFFFFF style="padding:5px"><?= $result['HotelInformationResponse'] ['HotelDetails'] ['checkInTime'] ?></td>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">체크아웃</td>
								<td width=35% bgcolor=#FFFFFF style="padding:5px"><?= $result['HotelInformationResponse'] ['HotelDetails'] ['checkOutTime'] ?></td>
							</tr>
							<tr>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">지역정보</td>
								<td width=85% bgcolor=#FFFFFF style="padding:5px" colspan=3><?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['areaInformation']); ?></td>
							</tr>
							<tr>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">위치정보</td>
								<td width=85% bgcolor=#FFFFFF style="padding:5px" colspan=3><?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['propertyDescription']); ?></td>
							</tr>
							<tr>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">호텔규정</td>
								<td width=85% bgcolor=#FFFFFF style="padding:5px" colspan=3><?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['hotelPolicy']); ?></td>
							</tr>
							<tr>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">룸 정보</td>
								<td width=85% bgcolor=#FFFFFF style="padding:5px" colspan=3><?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['roomInformation']); ?></td>
							</tr>
							<tr>
								<td width=15% align="center" bgcolor=#f4f4f4 class="b">교통안내</td>
								<td width=85% bgcolor=#FFFFFF style="padding:5px" colspan=3><?= htmlspecialchars_decode($result['HotelInformationResponse'] ['HotelDetails'] ['drivingDirections']); ?></td>
							</tr>
						</table>
						<br>
						<? endif; ?>
			</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="20"></td>
      </tr>
      <tr>
        <td align="center">
        <!-- Footer 메뉴-->
<?
include "inc_bottom.php";
?>