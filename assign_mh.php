<?php
    include "include/header.php";
   // include "include/inc_base.php";
	if ($_COOKIE[MEMLOGIN_ADMIN_HELLO] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
 
	if (!function_exists('getAssignedHotelCode')) {
		function getAssignedHotelCode($grandECode, $subECode, $pCode, $stDate)
		{
			global $dbConn;

			$safeGrandECode = $dbConn->real_escape_string($grandECode);
			$safeSubECode = $dbConn->real_escape_string($subECode);
			$safePCode = $dbConn->real_escape_string($pCode);

			// 마지막 일차의 호텔: sub_eCode 기준으로 DAY 값이 가장 큰 호텔을 가져옴
			$qry = "select a.hotel_code, b.h_sname, b.h_name
					from hotel_assign a
					left join product_hotel b on a.hotel_code = b.h_code
					where a.sub_eCode = '$safeSubECode'
					  and a.p_code = '$safePCode'
					  and a.stDate = '$safeStDate'
					order by a.day desc, a.seq_no desc
					limit 1";
			$rst = $dbConn->query($qry);
			if ($rst && $rst->num_rows > 0) {
				$row = $rst->fetch_assoc();
				return $resolveHotelLabel($row);
			}

			// fallback: grand_eCode + p_code 기준으로 DAY 가장 큰 호텔
			$fallbackQry = "select a.hotel_code, b.h_sname, b.h_name
							from hotel_assign a
							left join product_hotel b on a.hotel_code = b.h_code
							where a.grand_eCode = '$safeGrandECode'
							  and a.p_code = '$safePCode'
							  and a.stDate = '$safeStDate'
							order by a.DAY+0 desc, a.seq_no desc
							limit 1";
			$fallbackRst = $dbConn->query($fallbackQry);
			if ($fallbackRst && $fallbackRst->num_rows > 0) {
				$fallbackRow = $fallbackRst->fetch_assoc();
				return $resolveHotelLabel($fallbackRow);
			}

			return "";
		}
	}

	if (!function_exists('getAssignedBusNumber')) {
		function getAssignedBusNumber($carId)
		{
			global $dbConn;

			if ($carId == "") {
				return "";
			}

			$safeCarId = $dbConn->real_escape_string($carId);
			$qry = "select bus_number from bus_list where bus_id = '$safeCarId' limit 1";
			$rst = $dbConn->query($qry);
			if ($rst && $rst->num_rows > 0) {
				$row = $rst->fetch_assoc();
				return $row['bus_number'];
			}

			return "";
		}
	}

	if (!function_exists('getAssignedHotelCodeBySubECode')) {
		function getAssignedHotelCodeBySubECode($grandECode, $subECode, $pCode, $stDate)
		{
			global $dbConn;

			$safeGrandECode = $dbConn->real_escape_string($grandECode);
			$safePCode = $dbConn->real_escape_string($pCode);
			$safeStDate = $dbConn->real_escape_string($stDate);

			$resolveHotelLabel = function($row) {
				if ($row['h_sname'] != "") {
					return $row['h_sname'];
				}
				if ($row['h_name'] != "") {
					return $row['h_name'];
				}
				if ($row['hotel_code'] != "") {
					return $row['hotel_code'];
				}

				return "";
			};

			$hotelLabels = array();
			$qry = "select a.sub_eCode, a.hotel_code, b.h_sname, b.h_name
					from hotel_assign a
					left join product_hotel b on a.hotel_code = b.h_code
					where a.grand_eCode = '$safeGrandECode'
					  and a.p_code = '$safePCode'
					  and a.stDate = '$safeStDate'
					  and a.day = (
					  	select max(h2.day)
					  	from hotel_assign h2
					  	where h2.grand_eCode = a.grand_eCode
					  	  and h2.sub_eCode = a.sub_eCode
					  	  and h2.p_code = a.p_code
					  	  and h2.stDate = a.stDate
					  )
					order by a.sub_eCode asc, a.seq_no asc";
			$rst = $dbConn->query($qry);
			if ($rst) {
				while ($row = $rst->fetch_assoc()) {
					$rowHotel = $resolveHotelLabel($row);
					appendGuideLabel($hotelLabels, $rowHotel);
				}
			}

			if (!empty($hotelLabels)) {
				return implode("+", $hotelLabels);
			}

			$fallbackQry = "select a.hotel_code, b.h_sname, b.h_name
							from hotel_assign a
							left join product_hotel b on a.hotel_code = b.h_code
							where a.grand_eCode = '$safeGrandECode'
							  and a.p_code = '$safePCode'
							  and a.stDate = '$safeStDate'
							  and a.day = (
							  	select max(h2.day)
							  	from hotel_assign h2
							  	where h2.grand_eCode = a.grand_eCode
							  	  and h2.p_code = a.p_code
							  	  and h2.stDate = a.stDate
							  )
							order by a.seq_no asc";
			$fallbackRst = $dbConn->query($fallbackQry);
			if ($fallbackRst) {
				while ($fallbackRow = $fallbackRst->fetch_assoc()) {
					$fallbackHotel = $resolveHotelLabel($fallbackRow);
					appendGuideLabel($hotelLabels, $fallbackHotel);
				}
			}

			return implode("+", $hotelLabels);
		}
	}
 
	if (!function_exists('buildAssignedGuideName')) {
		function buildAssignedGuideName($guideName, $subGuideName = "")
		{
			$guideNames = array();
			if ($guideName != "") {
				$guideNames[] = $guideName;
			}
			if ($subGuideName != "") {
				$guideNames[] = $subGuideName;
			}

			return implode("+", $guideNames);
		}
	}

	if (!function_exists('appendGuideLabel')) {
		function appendGuideLabel(&$labels, $label)
		{
			$label = trim((string)$label);
			if ($label == "" || in_array($label, $labels, true)) {
				return;
			}

			$labels[] = $label;
		}
	}

	if (!function_exists('setGuideMapLabel')) {
		function setGuideMapLabel(&$guideMap, $subECode, $guideName)
		{
			$subECode = trim((string)$subECode);
			$guideName = trim((string)$guideName);
			if ($guideName == "") {
				return;
			}

			if (!isset($guideMap[$subECode]) || !is_array($guideMap[$subECode])) {
				$guideMap[$subECode] = array();
			}

			appendGuideLabel($guideMap[$subECode], $guideName);
		}
	}

	if (!function_exists('getGuideDisplayNameByMemberId')) {
		function getGuideDisplayNameByMemberId($memberId)
		{
			if ($memberId == "") {
				return "";
			}

			$memberInfo = getinfo_dbMember($memberId);
			if ($memberInfo['kor_name'] != "") {
				return $memberInfo['kor_name'];
			}
			if ($memberInfo['name'] != "") {
				return $memberInfo['name'];
			}

			return $memberId;
		}
	}

	if (!function_exists('getGuideNameByTourGuideProduct')) {
		function getGuideNameByTourGuideProduct($grandECode, $stDate, $pCode, $subECode = "")
		{
			global $dbConn;

			$safeGrandECode = $dbConn->real_escape_string($grandECode);
			$safeStDate = $dbConn->real_escape_string($stDate);
			$safePCode = $dbConn->real_escape_string($pCode);
			$safeSubECode = $dbConn->real_escape_string($subECode);

			$qry = "select guide_id, sguide_id
					from tour_guide
					where grand_eCode = '$safeGrandECode'
					  and stDate = '$safeStDate'
					  and p_code = '$safePCode'";
			if ($safeSubECode != "") {
				$qry .= " and sub_eCode = '$safeSubECode'";
			}
			$qry .= "
					order by bus_num asc, seq_no asc";
		
			$rst = $dbConn->query($qry);
			if ((!$rst || $rst->num_rows == 0) && $safeSubECode != "") {
				$fallbackQry = "select guide_id, sguide_id
								from tour_guide
								where grand_eCode = '$safeGrandECode'
								  and stDate = '$safeStDate'
								  and p_code = '$safePCode'
								order by bus_num asc, seq_no asc
								";
				$rst = $dbConn->query($fallbackQry);
			}
			if ($rst && $rst->num_rows > 0) {
				$guideLabels = array();
				while ($row = $rst->fetch_assoc()) {
					appendGuideLabel($guideLabels, getGuideDisplayNameByMemberId($row['guide_id']));
					appendGuideLabel($guideLabels, getGuideDisplayNameByMemberId($row['sguide_id']));
				}
				return implode("+", $guideLabels);
			}

			return "";
		}
	}
	if (!function_exists('getGuideNameByTourMainGuideProduct')) {
		function getGuideNameByTourMainGuideProduct($grandECode, $stDate, $pCode, $subECode = "", $busNum = "")
		{
			global $dbConn;

			$safeGrandECode = $dbConn->real_escape_string($grandECode);
			$safeStDate = $dbConn->real_escape_string($stDate);
			$safePCode = $dbConn->real_escape_string($pCode);
			$safeSubECode = $dbConn->real_escape_string($subECode);

			$qry = "select a.guide_id, a.sguide_id
					from tour_guide a
					inner join product_master b on a.p_code = b.p_code
					where  a.stDate = '$safeStDate'
					  and a.p_code = '$safePCode'
					  and b.m_guidechk = 'V'";
			if ($safeSubECode != "") {
				$qry .= " and a.sub_eCode = '$safeSubECode'";
			}
			$qry .= "
					order by a.bus_num asc, a.seq_no asc";
           
			$rst = $dbConn->query($qry);
			if ((!$rst || $rst->num_rows == 0) && $safeSubECode != "") {
				$fallbackQry = "select a.guide_id, a.sguide_id
								from tour_guide a
								inner join product_master b on a.p_code = b.p_code
								where a.stDate = '$safeStDate'
								  and a.p_code = '$safePCode'  and b.m_guidechk = 'V'
								";
				$fallbackQry .= " order by a.bus_num asc, a.seq_no asc";
				$rst = $dbConn->query($fallbackQry);
			}
			if ($rst && $rst->num_rows > 0) {
				$guideLabels = array();
				while ($row = $rst->fetch_assoc()) {
					appendGuideLabel($guideLabels, getGuideDisplayNameByMemberId($row['guide_id']));
					appendGuideLabel($guideLabels, getGuideDisplayNameByMemberId($row['sguide_id']));
				}
				return implode("+", $guideLabels);
			}

			return "";
		}
	}
	if (!function_exists('getReservationMainGuideName')) {
		function getReservationMainGuideName($grandECode, $subECode, $pCode, $stDate, $busNum = "")
		{
			global $dbConn;

			static $mainGuideCache = array();

			$cacheKey = $grandECode."|".$subECode."|".$pCode."|".$stDate."|".$busNum;
			if (isset($mainGuideCache[$cacheKey])) {
				return $mainGuideCache[$cacheKey];
			}

			$safeGrandECode = $dbConn->real_escape_string($grandECode);
			$safeSubECode = $dbConn->real_escape_string($subECode);
			$safePCode = $dbConn->real_escape_string($pCode);
			$safeStDate = $dbConn->real_escape_string($stDate);
			$qry = "select distinct a.reserveCode, a.p_code, a.stDate, tc2.sub_eCode
					from reserve_info a
					inner join product_master b on a.p_code = b.p_code
					inner join (
						select distinct reserveCode
						from tour_car
						where grand_eCode = '$safeGrandECode'
						  and p_code = '$safePCode'
						  and stDate = '$safeStDate'
						  and reserveCode != ''
					) tc on a.reserveCode = tc.reserveCode
					left join tour_car tc2 on a.reserveCode = tc2.reserveCode
					  and a.p_code = tc2.p_code
					  and tc2.grand_eCode = '$safeGrandECode'
					where a.parent = 'SUB'
					  and b.m_guidechk = 'V'
					order by a.stDate asc, a.p_code asc, tc2.sub_eCode asc";
			$rst = $dbConn->query($qry);
			$mainGuideMap = array();
			if ($rst) { 
				$checkedProducts = array();
				while ($row = $rst->fetch_assoc()) {
					$mainGuideDate = $row['stDate'];
					if ($mainGuideDate == "") {
						$mainGuideDate = $stDate;
					}

					$mainGuideSubECode = isset($row['sub_eCode']) ? $row['sub_eCode'] : "";
					$productKey = $row['p_code']."|".$mainGuideDate."|".$mainGuideSubECode;
					if (isset($checkedProducts[$productKey])) {
						continue;
					}
					$checkedProducts[$productKey] = true;

					$mainGuideName = getGuideNameByTourMainGuideProduct(
						$grandECode,
						$mainGuideDate,
						$row['p_code'],
						$mainGuideSubECode
					);
					if ($mainGuideName != "") {
						$targetSubECode = $mainGuideSubECode != "" ? $mainGuideSubECode : $safeSubECode;
						setGuideMapLabel($mainGuideMap, $targetSubECode, $mainGuideName);
					}
				}
			}

			if (isset($mainGuideMap[$safeSubECode]) && !empty($mainGuideMap[$safeSubECode])) {
				$mainGuideCache[$cacheKey] = implode("+", $mainGuideMap[$safeSubECode]);
				return $mainGuideCache[$cacheKey];
			}

			$fallbackQry = "select distinct a.p_code, a.stDate
							from tour_guide a
							inner join product_master b on a.p_code = b.p_code
							where a.grand_eCode = '$safeGrandECode'
							 and b.m_guidechk = 'V'
							order by a.stDate asc, a.sub_eCode asc, a.p_code asc";
			$fallbackRst = $dbConn->query($fallbackQry);
			$fallbackGuideMap = array();
			if ($fallbackRst) {
				while ($fallbackRow = $fallbackRst->fetch_assoc()) {
					$fallbackDate = $fallbackRow['stDate'];
					if ($fallbackDate == "") {
						$fallbackDate = $stDate;
					}

					$mainGuideName = getGuideNameByTourMainGuideProduct(
						$grandECode,
						$fallbackDate,
						$fallbackRow['p_code'],
						$safeSubECode
					);
					if ($mainGuideName != "") {
						setGuideMapLabel($fallbackGuideMap, $safeSubECode, $mainGuideName);
					}
				}
			}

			$mainGuideCache[$cacheKey] = isset($fallbackGuideMap[$safeSubECode]) && !empty($fallbackGuideMap[$safeSubECode])
				? implode("+", $fallbackGuideMap[$safeSubECode])
				: "";
			return $mainGuideCache[$cacheKey];
		}
	}

	if (!function_exists('getTourCarAssignedCount')) {
		function getTourCarAssignedCount($grandECode, $subECode, $pCode, $stDate, $busNum = "")
		{
			global $dbConn;

			static $countCache = array();

			$cacheKey = $grandECode."|".$subECode."|".$pCode."|".$stDate."|".$busNum;
			if (isset($countCache[$cacheKey])) {
				return $countCache[$cacheKey];
			}

			$safeGrandECode = $dbConn->real_escape_string($grandECode);
			$safeSubECode   = $dbConn->real_escape_string($subECode);
			$safePCode      = $dbConn->real_escape_string($pCode);
			$safeStDate     = $dbConn->real_escape_string($stDate);
			$safeBusNum = $dbConn->real_escape_string($busNum);
			$busFilter  = ($safeBusNum !== "") ? "  and bus_num = '$safeBusNum'" : "";
			$qry = "select count(*) as cnt
					from tour_car
					where grand_eCode = '$safeGrandECode'
					  and p_code = '$safePCode'
					  and stDate = '$safeStDate'".$busFilter;
			$rst = $dbConn->query($qry);
			if ($rst && $rst->num_rows > 0) {
				$row = $rst->fetch_assoc();
				$countCache[$cacheKey] = (string)$row['cnt'];
				return $countCache[$cacheKey];
			}

			$countCache[$cacheKey] = "0";
			return $countCache[$cacheKey];
		}
	}

	if (!function_exists('getMainProductEventType')) {
		function getMainProductEventType($grandECode, $subECode, $pCode, $stDate, $fallbackName = "")
		{
			global $dbConn;

			static $eventTypeCache = array();

			$cacheKey = $grandECode."|".$subECode."|".$pCode."|".$stDate;
			if (isset($eventTypeCache[$cacheKey])) {
				return $eventTypeCache[$cacheKey];
			}

			$safeGrandECode = $dbConn->real_escape_string($grandECode);
			$safeSubECode = $dbConn->real_escape_string($subECode);
			$safePCode = $dbConn->real_escape_string($pCode);
			$safeStDate = $dbConn->real_escape_string($stDate);

			$qry = "select a.p_name
					from reserve_info a
					inner join tour_car b on a.reserveCode = b.reserveCode
					where b.grand_eCode = '$safeGrandECode'
					  and b.sub_eCode = '$safeSubECode'
					  and b.p_code = '$safePCode'
					  and b.stDate = '$safeStDate'
					  and a.parent = 'MAIN'
					order by a.seq_no asc
					limit 1";

			$rst = $dbConn->query($qry);
			if ($rst && $rst->num_rows > 0) {
				$row = $rst->fetch_assoc();
				$mainProductName = trim($row['p_name']);
				if ($mainProductName != "") {
					if (preg_match('/^(\S+)/u', $mainProductName, $matches)) {
						$eventTypeCache[$cacheKey] = $matches[1];
						return $eventTypeCache[$cacheKey];
					}
					$eventTypeCache[$cacheKey] = $mainProductName;
					return $eventTypeCache[$cacheKey];
				}
			}

			$fallbackName = trim($fallbackName);
			if ($fallbackName != "" && preg_match('/^(\S+)/u', $fallbackName, $matches)) {
				$eventTypeCache[$cacheKey] = $matches[1];
			} else {
				$eventTypeCache[$cacheKey] = $fallbackName;
			}

			return $eventTypeCache[$cacheKey];
		}
	}

	if (!function_exists('buildGuideAssignMemoHtml')) {
		function buildGuideAssignMemoHtml($stDate, $updatedAt, $isLatest = false)
		{
			global $dbConn;

			$safeStDate = $dbConn->real_escape_string($stDate);
			$week = array("일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일");
			$weekLabel = $week[date('w', strtotime($stDate))];
			$title = date('n/j', strtotime($stDate))." ".$weekLabel." 행사정리";

			$qry = "select grand_eCode, sub_eCode, p_code, p_name, stDate, bus_num, guide_id, sguide_id, c_id
					from tour_guide
					where stDate = '$safeStDate'
					order by p_name asc, bus_num+0 asc, bus_num asc";
				
			$rst = $dbConn->query($qry);
			
			$boxStyle = "border:1px solid #d5d5d5;background:#fafafa;padding:12px;margin-bottom:12px;";
			$labelStyle = "display:inline-block;background:#666;color:#fff;font-size:11px;font-weight:bold;padding:2px 8px;margin-bottom:8px;";
			$labelText = "업데이트 이력";
			if ($isLatest) {
				$boxStyle = "border:2px solid #c9302c;background:#fff7cc;padding:12px;margin-bottom:14px;";
				$labelStyle = "display:inline-block;background:#c9302c;color:#fff;font-size:11px;font-weight:bold;padding:2px 8px;margin-bottom:8px;";
				$labelText = "최신 업데이트";
			}

			$html = "";
			$html .= "<div style='".$boxStyle."'>";
			$html .= "<div style='".$labelStyle."'>".$labelText."</div>";
			$html .= "<div style='font-size:12px;color:#666;margin-bottom:6px;'>업데이트 시각: ".htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8')."</div>";
			$html .= "<div style='font-size:18px;font-weight:bold;margin-bottom:8px;'>".htmlspecialchars($title, ENT_QUOTES, 'UTF-8')."</div>";
			$html .= "<table style='width:100%;border-collapse:collapse;' border='1' cellspacing='0' cellpadding='4'>";
			$html .= "<tr style='background:#f5f5f5;'>";
			$html .= "<th style='text-align:center;'>번호</th>";
			$html .= "<th style='text-align:center;'>행사종류</th>";
			$html .= "<th style='text-align:center;'>행사</th>";
			$html .= "<th style='text-align:center;'>인원수</th>";
			$html .= "<th style='text-align:center;'>가이드차량</th>";
			$html .= "<th style='text-align:center;'>로컬가이드</th>";
			$html .= "<th style='text-align:center;'>메인가이드</th>";
			$html .= "<th style='text-align:center;'>호텔</th>";
			$html .= "</tr>";

			// 버스(tour_guide 행) 1개 = 번호 1개
			$idx = 1;
			if ($rst) {
				while ($row = $rst->fetch_assoc()) {
					$guideInfo = getinfo_dbMember($row['guide_id']);
					$guideName = $row['guide_id'];
					if ($guideInfo['kor_name'] != "") {
						$guideName = $guideInfo['kor_name'];
					} else if ($guideInfo['name'] != "") {
						$guideName = $guideInfo['name'];
					}

					$subGuideName = "";
					if ($row['sguide_id'] != "") {
						$subGuideInfo = getinfo_dbMember($row['sguide_id']);
						$subGuideName = $row['sguide_id'];
						if ($subGuideInfo['kor_name'] != "") {
							$subGuideName = $subGuideInfo['kor_name'];
						} else if ($subGuideInfo['name'] != "") {
							$subGuideName = $subGuideInfo['name'];
						}
					}
					$assignedGuideName = buildAssignedGuideName($guideName, $subGuideName);

					$eventType = getMainProductEventType($row['grand_eCode'], $row['sub_eCode'], $row['p_code'], $row['stDate'], $row['p_name']);
					$eventName = $row['p_name'];

					$busLabel = trim(getAssignedBusNumber($row['c_id']));
					if ($busLabel == "") {
						$busLabel = trim($row['bus_num']);
						if ($busLabel != "" && !preg_match('/호$/u', $busLabel)) {
							$busLabel .= "호";
						}
					}
					$guideCar = trim($guideName." ".$busLabel);
					$hotelCode = getAssignedHotelCodeBySubECode($row['grand_eCode'], $row['sub_eCode'], $row['p_code'], $row['stDate']);
					$mainGuideName = getReservationMainGuideName($row['grand_eCode'], $row['sub_eCode'], $row['p_code'], $row['stDate'], $row['bus_num']);
					$localGuideName = $assignedGuideName;
					$reserveCount = getTourCarAssignedCount($row['grand_eCode'], $row['sub_eCode'], $row['p_code'], $row['stDate'], $row['bus_num']);

					// 행사종류: grand_eCode 전체 기준 MAIN 예약의 p_cat_name (버스 무관, 행사 공통)
					$safeGrandECode2 = $dbConn->real_escape_string($row['grand_eCode']);
					$safePCode       = $dbConn->real_escape_string($row['p_code']);
					$safeStDate2     = $dbConn->real_escape_string($row['stDate']);
					$parentQry = "SELECT GROUP_CONCAT(DISTINCT pm.p_cat_name ORDER BY pm.p_cat_name SEPARATOR '+') as p_cat_name
									FROM reserve_info ri
									INNER JOIN tour_car tc ON ri.reserveCode = tc.reserveCode
									INNER JOIN product_master pm ON ri.p_code = pm.p_code
									WHERE tc.grand_eCode = '$safeGrandECode2'
									  AND tc.p_code = '$safePCode'
									  AND tc.stDate = '$safeStDate2'
									  AND ri.parent = 'MAIN'
									  AND ri.rev_status != 'CANCEL'
									  AND pm.p_cat_name != ''";
					$parentRst = $dbConn->query($parentQry);
					$parentCatName = "";
					if ($parentRst && $parentRst->num_rows > 0) {
						$parentRow = $parentRst->fetch_assoc();
						$parentCatName = trim($parentRow['p_cat_name'] ?? '');
					}
					$eventTypeDisplay = $parentCatName != '' ? $parentCatName : $eventType;

					$html .= "<tr>";
					$html .= "<td style='text-align:center;'>".$idx."</td>";
					$html .= "<td>".htmlspecialchars($eventTypeDisplay, ENT_QUOTES, 'UTF-8')."</td>";
					$html .= "<td>".htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8')."</td>";
					$html .= "<td style='text-align:center;'>".htmlspecialchars($reserveCount, ENT_QUOTES, 'UTF-8')."</td>";
					$html .= "<td>".htmlspecialchars($guideCar, ENT_QUOTES, 'UTF-8')."</td>";
					$html .= "<td>".htmlspecialchars($localGuideName, ENT_QUOTES, 'UTF-8')."</td>";
					$html .= "<td>".htmlspecialchars($mainGuideName, ENT_QUOTES, 'UTF-8')."</td>";
					$html .= "<td>".htmlspecialchars($hotelCode, ENT_QUOTES, 'UTF-8')."</td>";
					$html .= "</tr>";

					$idx++;
				}
			}

			if ($idx == 1) {
				$html .= "<tr><td colspan='8' style='text-align:center;'>배정된 가이드가 없습니다.</td></tr>";
			}

			$html .= "</table>";
			$html .= "</div>"; 

			return $html;
		} 
	} 

	if (!function_exists('downgradeGuideAssignHistoryHtml')) {
		function downgradeGuideAssignHistoryHtml($html)
		{
			$html = str_replace(
				"border:2px solid #c9302c;background:#fff7cc;padding:12px;margin-bottom:14px;",
				"border:1px solid #d5d5d5;background:#fafafa;padding:12px;margin-bottom:12px;",
				$html
			);
			$html = str_replace(
				"display:inline-block;background:#c9302c;color:#fff;font-size:11px;font-weight:bold;padding:2px 8px;margin-bottom:8px;",
				"display:inline-block;background:#666;color:#fff;font-size:11px;font-weight:bold;padding:2px 8px;margin-bottom:8px;",
				$html
			);
			$html = str_replace(">최신 업데이트<", ">업데이트 이력<", $html);

			return $html;
		}
	}

	if (!function_exists('buildGuideAssignHistoryBlock')) {
		function buildGuideAssignHistoryBlock($stDate, $existingHistoryHtml = "")
		{
			$updatedAt = date('Y-m-d H:i:s');
			$newHistoryHtml = buildGuideAssignMemoHtml($stDate, $updatedAt, true);
			$oldHistoryHtml = downgradeGuideAssignHistoryHtml($existingHistoryHtml);

			$html = "";
			$html .= "<div style='margin-bottom:14px;'>";
			$html .= "<div style='font-size:20px;font-weight:bold;margin-bottom:10px;'>가이드 배정 히스토리</div>";
			$html .= $newHistoryHtml;
			$html .= $oldHistoryHtml;
			$html .= "</div>";

			return $html;
		}
	}

	if (!function_exists('upsertGuideAssignMemoBoard')) {
		function upsertGuideAssignMemoBoard($stDate)
		{
			global $dbConn, $user_dbinfo, $user_name;

			$safeDate = $dbConn->real_escape_string($stDate);
			$qry = "select * from memo_board where date = '$safeDate' limit 1";
			$rst = $dbConn->query($qry);
			$writerId = $dbConn->real_escape_string($user_dbinfo['userid']);
			$writerName = "";
			if ($user_name != "") {
				$writerName = $user_name;
			} else if ($user_dbinfo['kor_name'] != "") {
				$writerName = $user_dbinfo['kor_name'];
			}
			$writerName = $dbConn->real_escape_string($writerName);

			if ($rst && $rst->num_rows > 0) {
				$row = $rst->fetch_assoc();
				$content1 = $row['content1'];
				$existingHistoryHtml = "";
				if (preg_match('/<!-- GUIDE_ASSIGN_AUTO_START -->(.*?)<!-- GUIDE_ASSIGN_AUTO_END -->/s', $content1, $matches)) {
					$existingHistoryHtml = $matches[1];
				}
				$memoBlock = "<!-- GUIDE_ASSIGN_AUTO_START -->".buildGuideAssignHistoryBlock($stDate, $existingHistoryHtml)."<!-- GUIDE_ASSIGN_AUTO_END -->";
				if (strpos($content1, '<!-- GUIDE_ASSIGN_AUTO_START -->') !== false && strpos($content1, '<!-- GUIDE_ASSIGN_AUTO_END -->') !== false) {
					$content1 = preg_replace('/<!-- GUIDE_ASSIGN_AUTO_START -->.*?<!-- GUIDE_ASSIGN_AUTO_END -->/s', $memoBlock, $content1, 1);
				} else if ($content1 != "") {
					$content1 = $memoBlock."<br><br>".$content1;
				} else {
					$content1 = $memoBlock;
				}
				$safeContent1 = $dbConn->real_escape_string($content1);
				$updateQry = "update memo_board set content1 = '$safeContent1' where date = '$safeDate'";
				return $dbConn->query($updateQry);
			}

			$memoBlock = "<!-- GUIDE_ASSIGN_AUTO_START -->".buildGuideAssignHistoryBlock($stDate)."<!-- GUIDE_ASSIGN_AUTO_END -->";
			$safeContent1 = $dbConn->real_escape_string($memoBlock);
			$insertQry = "insert into memo_board values ('','$writerId','$writerName','$safeDate','$safeContent1','',now())";
			return $dbConn->query($insertQry);
		}
	}

    if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }

	if ($action == "guide_history") {
		$targetDate = $st;
		if ($targetDate == "" && $sdate != "") {
			$targetDate = $sdate;
		}

		if ($targetDate == "") {
			Misc::jvAlert("출발일 정보가 없습니다.","");
		} else if (upsertGuideAssignMemoBoard($targetDate)) {
			Misc::jvAlert("가이드 배정 히스토리를 메모에 등록했습니다.","");
		} else {
			Misc::jvAlert("메모 등록에 실패했습니다.","");
		}

		echo "<meta http-equiv='refresh' content='0; url=./assign_m.php?division=$division&pdx=$pdx&sub=$sub&st=$st&pcode=$pcode'>";
		exit;
	}
	
	$sctour = getTourInfo2($pcode,$st);
	$pcnt = getReserveInfoCnt($pcode,$st);				
	if ($pcnt[cnt] =="") {
		$pcnt[cnt] = 0;
	}
    $pInfo = getProductMaster($pcode);
	// 봤다 처리 - 간단 구현
	if ($pcode && $st) {
		$qry = "UPDATE reserve_info SET is_modified = 0 WHERE p_code = '$pcode' AND stDate = '$st'";
		$dbConn->query($qry);
	}
	

?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정</a></li>
					<li>통합행사관리</li>
				</ul>
			</div>

			<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmcar" method="post" onSubmit="return chksave()">
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="gcode" id="gcode" value="<?=$sctour[grand_eCode]?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$sctour[p_code]?>">
				<input type="hidden" name="pname" id="pname" value='<?=$sctour[p_name]?>'>
				<input type="hidden" name="sdate" id="sdate" value="<?=$sctour[stDate]?>">
				<table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
                        <td colspan="2" class="active text-center formHeader">통합행사코드</td>
                        <td colspan="12"><?=$sctour[grand_eCode]?></td>
                    </tr>
					        			
                        <td colspan="2" class="active text-center formHeader">상품명</td>
                        <td colspan="12">[<?=$sctour[p_code]?>] <?=$sctour[p_name]?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">출발일</td>
                        <td colspan="2"><?=$sctour[stDate]?></td>
                        
                        <td colspan="2" class="active text-center formHeader">투어정원</td>
                        <td colspan="2"><?=$sctour[tour_pcnt]?> 명 </td>
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="2"><?=$pcnt[cnt]?> 명 </td>
                    </tr>
					
                    <tr>
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="12">
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="P" <?php if(strstr($sctour[r_status],"P")) echo "checked"; ?> disabled> 예약접수중
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="C" <?php if(strstr($sctour[r_status],"C")) echo "checked"; ?> disabled> 예약마감
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">행사상태</td>
                        <td colspan="12">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="input-group input-group-sm">
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="1" <?php if(strstr($sctour[ev_status],"1")) echo "checked"; ?> disabled> 미확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="2" <?php if(strstr($sctour[ev_status],"2")) echo "checked"; ?> disabled> 확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="3" <?php if(strstr($sctour[ev_status],"3")) echo "checked"; ?> disabled> 만차
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="4" <?php if(strstr($sctour[ev_status],"4")) echo "checked"; ?> disabled> 취소
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" <?php if(strstr($sctour[ev_status],"5")) echo "checked"; ?> disabled> 기타
                                        </label>
                                    </div>
                                </div>    
                                <div class="col-sm-8">
                                    <div>   
                                        <input type="text" name="etcMemo" class="form-control" aria-label="기타메모"  placeholder="기타메모" value="<?=$sctour[etc_memo]?>" readOnly/>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                        <tr>
                            <td colspan="16" class="text-center"><ul class="dshb_icoNav clearfix">
							   <li><a href="./event_reservation_detail.php?division=5&pdx=2&sub=10&st=<?=$sctour[stDate]?>&pcode=<?=$sctour[p_code]?>" style="background-image: url(img/gCons/multi-agents.png)" >예약자보기</a></li>
							  
							   <?php if (strpos($sctour[p_code], 'PICUP') !== false)  {  ?>
							   <li><a style="background-image: url(img/gCons/van.png)">차량배정</a>
							   </li>
							   <?php } else { ?>
							   <li><a href="./car_assign_m.php?division=5&pdx=2&sub=10&st=<?=$sctour[stDate]?>&pcode=<?=$sctour[p_code]?>"  style="background-image: url(img/gCons/van.png)">차량배정</a>
							   <?php }  ?>
							   <li><a href="./hotel_assign_m.php?division=5&pdx=2&sub=10&st=<?=$sctour[stDate]?>&pcode=<?=$sctour[p_code]?>"  style="background-image: url(img/gCons/calendar.png)">호텔배정</a></li>
							   <li><a href="./guide_assign_m.php?division=5&pdx=2&sub=10&st=<?=$sctour[stDate]?>&pcode=<?=$sctour[p_code]?>"  style="background-image: url(img/gCons/agent.png)">가이드배정</a></li>
							   <li><a href="./assign_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$sctour[stDate]?>&pcode=<?=$sctour[p_code]?>&action=guide_history" onclick="return confirm('가이드 배정 히스토리를 메모에 등록할까요?');" style="background-image: url(img/gCons/save.png)">히스토리등록</a></li>
							   <li><a href="javascript:openwin('<?=$sctour[p_code]?>','<?=$sctour[grand_eCode]?>','<?=$sctour[stDate]?>')" style="background-image: url(img/gCons/edit.png)">명단</a></li>
						   </ul>
                            </td>
                        </tr>
						
                    </tbody>
				</table>
				
			</form>
		</div>
	</div>
	
    <?php
		include "include/side_m.php"
	?>
   
   <script>
		$(document).ready(function () {
			pt.initReservationList()
			
		})

		var ctr=0;
        function openwin(pcode,g_code,st) { 
	       var winName = "all_"+(ctr++);
		   window.open("short_customer.php?gscode="+g_code+"&pcode="+pcode+"&st="+st,winName,"width=600,height=300,scrollbars=1");
	    }
	</script>
   
    </body>
</html>
