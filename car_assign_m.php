<?php
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE);
    
    include "include/header.php";
    //include "include/inc_base.php"; // 필요에 따라 주석 해제

    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    if (!hasMenuAccess($division, $pdx, $sub)) {
        $goUrl_1 = "index.php";
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
        exit;
    }

    $sctour = getTourInfo2($pcode,$st);
    $pcnt = getReserveInfoCnt($pcode,$st);              
    if ($pcnt['cnt'] =="") {
        $pcnt['cnt'] = 0;
    }
    $pInfo = getProductMaster($pcode);

    // ====================================================================================
    // [수정됨] 저장 로직: 트랜잭션 처리 추가 (데이터 무결성 보장)
    // ====================================================================================
    if ($mode == "save") {
        
        // 1. 트랜잭션 시작
        mysql_query("START TRANSACTION", $dbConn);
        $error_flag = false; // 에러 발생 여부 체크

        $eventcnt = count($rnum);

        // 2. 기존 차량별 서브코드 맵 로드 (신규 차량만 코드 생성하기 위함)
        $busCodeMap = array();
        $qry_bus_map = "select bus_num, sub_eNum, sub_eCode
                        from tour_car
                        where grand_eCode = '$gcode'
                          && stDate = '$sdate'
                          && p_code = '$pcode'
                          && bus_num != ''
                        order by seq_no asc";
        $rst_bus_map = mysql_query($qry_bus_map, $dbConn);
        if ($rst_bus_map) {
            while ($row_bus_map = mysql_fetch_assoc($rst_bus_map)) {
                $busKeyMap = trim((string)$row_bus_map['bus_num']);
                if ($busKeyMap !== "" &&
                    $row_bus_map['sub_eNum'] !== "" &&
                    $row_bus_map['sub_eCode'] !== "" &&
                    !isset($busCodeMap[$busKeyMap])) {
                    $busCodeMap[$busKeyMap] = array(
                        'num'  => $row_bus_map['sub_eNum'],
                        'code' => $row_bus_map['sub_eCode']
                    );
                }
            }
        }
        
        for($r=0; $r<$eventcnt; $r++)
        {
            if ($bnum[$r] != "") {
                // 기존 차량은 기존 서브코드 유지, 신규 차량만 서브코드 생성
                $busKey = trim((string)$bnum[$r]);
                if (!isset($busCodeMap[$busKey])) {
                    $sub_eventNum = getNumSevent($gcode,$sdate);
                    $mt = microtime(true);
					$sec = floor($mt);
					$hs  = sprintf("%03d", ($mt - $sec) * 1000); 
					$sub_eventCode = "GSE".date("His", $sec) . $hs."-".$sub_eventNum;
					$busCodeMap[$busKey] = array(
                        'num'  => $sub_eventNum,
                        'code' => $sub_eventCode
                    );
                }
                $sub_eventNum = $busCodeMap[$busKey]['num'];
                $sub_eventCode = $busCodeMap[$busKey]['code'];

                // 3. 차량 배정 데이터 저장 (tour_car)
                // 기존 고객 데이터는 UPDATE, 신규 고객은 INSERT
                $revnm_esc = addslashes($revnm[$r]);
                $pick_esc = addslashes($pick[$r]);
                $qry_exist = "select seq_no from tour_car
                              where grand_eCode = '$gcode'
                                && stDate = '$sdate'
                                && p_code = '$pcode'
                                && reserveCode = '$rev[$r]'
                                && (h_seq = '$hseq[$r]' || rev_nm = '$revnm_esc')
                              limit 1";
                $rst_exist = mysql_query($qry_exist, $dbConn);
                $row_exist = ($rst_exist) ? mysql_fetch_assoc($rst_exist) : null;

                if ($row_exist && $row_exist['seq_no']) {
                    $qry2 = "update tour_car
                                set sub_eNum = '$sub_eventNum',
                                    sub_eCode = '$sub_eventCode',
                                    p_name = '$pname',
                                    bus_num = '$bnum[$r]',
                                    romm_num = '$rnum[$r]',
                                    rev_nm = '$revnm_esc',
                                    room_type = '$roomt[$r]',
                                    sex = '$rsex[$r]',
                                    picCode = '$pick_esc',
                                    userid = '{$user_dbinfo['userid']}',
                                    h_seq = '$hseq[$r]',
                                    wdate = now()
                              where seq_no = '{$row_exist['seq_no']}'";
                } else {
                    $qry2 ="insert into tour_car
                                (
                                grand_eCode,
                                sub_eNum,
                                sub_eCode,
                                reserveCode,
                                p_code,
                                p_name,
                                stDate,
                                bus_num,
                                romm_num,
                                rev_nm,
                                room_type,
                                sex,
                                picCode,
                                userid,
                                h_seq,
                                wdate
                                )
                                values
                                (
                                '$gcode',
                                '$sub_eventNum',
                                '$sub_eventCode',
                                '$rev[$r]',
                                '$pcode',
                                '$pname',
                                '$sdate',
                                '$bnum[$r]',
                                '$rnum[$r]',
                                '$revnm_esc',
                                '$roomt[$r]',
                                '$rsex[$r]',
                                '$pick_esc',
                                '{$user_dbinfo['userid']}',
                                '$hseq[$r]',
                                now()
                                )";
                }
                if(!mysql_query($qry2, $dbConn)) $error_flag = true;
                
                // 4. 호텔 룸 배정 정보 업데이트 (동기화)
                // hotelroom_assign 테이블 업데이트
                $qry3 ="update hotelroom_assign 
                        set sub_eCode = '$sub_eventCode'
                        where p_code = '$pcode' && stDate= '$sdate' && tnm='$revnm[$r]'";
                if(!mysql_query($qry3, $dbConn)) $error_flag = true;

                // 5. 추가 숙박(Add-on) 처리 로직
                if ($productSub != "") {
                    $psInfo = getProductMaster($productSub);
                    $adddate = $pInfo['p_day'] - 1;
                    $adddate2 = date('Ymd', strtotime($sdate. ' + '.$adddate.' day'));
                    $sub_eventCode2 = "SVE".$adddate2."-".$sub_eventNum;

                    $qry_exist2 = "select seq_no from tour_car
                                   where grand_eCode = '$gcode'
                                     && stDate = '$adddate2'
                                     && p_code = '$productSub'
                                     && reserveCode = '$rev[$r]'
                                     && (h_seq = '$hseq[$r]' || rev_nm = '$revnm_esc')
                                   limit 1";
                    $rst_exist2 = mysql_query($qry_exist2, $dbConn);
                    $row_exist2 = ($rst_exist2) ? mysql_fetch_assoc($rst_exist2) : null;

                    if ($row_exist2 && $row_exist2['seq_no']) {
                        $qry4 = "update tour_car
                                    set sub_eNum = '$sub_eventNum',
                                        sub_eCode = '$sub_eventCode2',
                                        p_name = '{$psInfo['p_name']}',
                                        bus_num = '$bnum[$r]',
                                        romm_num = '$rnum[$r]',
                                        rev_nm = '$revnm_esc',
                                        room_type = '$roomt[$r]',
                                        sex = '$rsex[$r]',
                                        picCode = '$pick_esc',
                                        userid = '{$user_dbinfo['userid']}',
                                        h_seq = '$hseq[$r]',
                                        wdate = now()
                                  where seq_no = '{$row_exist2['seq_no']}'";
                    } else {
                        $qry4 ="insert into tour_car
                                (grand_eCode, sub_eNum, sub_eCode, reserveCode, p_code, p_name, stDate,
                                 bus_num, romm_num, rev_nm, room_type, sex, picCode, userid, h_seq, wdate)
                                values
                                ('$gcode', '$sub_eventNum', '$sub_eventCode2', '$rev[$r]', '$productSub',
                                 '{$psInfo['p_name']}', '$adddate2', '$bnum[$r]', '$rnum[$r]', '$revnm_esc',
                                 '$roomt[$r]', '$rsex[$r]', '$pick_esc', '{$user_dbinfo['userid']}', '$hseq[$r]', now())";
                    }
                    if(!mysql_query($qry4, $dbConn)) $error_flag = true;
                    
                    $qry5 ="update hotelroom_assign 
                            set sub_eCode = '$sub_eventCode2'
                            where p_code = '$productSub' && stDate= '$adddate2' && tnm='$revnm[$r]'";
                    if(!mysql_query($qry5, $dbConn)) $error_flag = true;
                }
            }
        }
        
        // 6. 결과 처리 (Commit / Rollback)
        if ($error_flag) {
            mysql_query("ROLLBACK", $dbConn);
            Misc::jvAlert("오류가 발생하여 저장이 취소되었습니다. 다시 시도해 주세요.", "");
        } else {
            mysql_query("COMMIT", $dbConn);
            Misc::jvAlert("정상적으로 업데이트 되었습니다!!", "");
        }
    }
    // ====================================================================================

    function reservelist2() {
        global $dbConn,$pcode,$st,$num1;

        $qry1 = "select a.grand_eCode, 
                                a.p_code, 
                                a.p_name, 
                                a.bus_cnt, 
                                a.tour_pcnt, 
                                a.stDate, 
                                b.reserveCode,
                                c.traveler_nm,
                                c.pick_area,
                                c.sextype,
                                c.seqint,
                                c.traveler_room,
                                c.traveler_nm
                                from 
                                tour_master a, reserve_info b ,(select reserveCode,traveler_nm,pick_area,seqint,sextype,traveler_room from reserve_traveler) c
                             where a.stDate=b.stDate &&  a.p_code =  b.p_code && b.reserveCode=c.reserveCode  && a.stDate =  '$st' && a.p_code = '$pcode' 
                             && c.traveler_nm not in (select rev_nm from tour_car where stDate = '$st' && p_code = '$pcode') && (b.rev_status='DONE' && b.rev_status!='CANCEL')  order by b.reserveCode,c.seqint asc";
        
        $rst1 = mysql_query($qry1,$dbConn);
        $num1 = mysql_num_rows($rst1);
        while($row1 = mysql_Fetch_assoc($rst1)){
                $reserve_info2 = getReserveTrInfo($row1['reserveCode'],$row1['traveler_nm']);
                if ($reserve_info2['room_type'] == "1r1p") {
                    $fimg = "1인1실"; $fmn = "1r1p";
                } else if ($reserve_info2['room_type'] === "1r2p") {
                    $fimg = "2인1실"; $fmn = "1r2p";
                } else if ($reserve_info2['room_type'] == "1r3p") {
                    $fimg = "3인1실"; $fmn = "1r3p";
                } else if ($reserve_info2['room_type'] == "1r4p") {
                    $fimg = "4인1실"; $fmn = "1r4p";
                } else if ($reserve_info2['room_type'] == "1r5p") {
                    $fimg = "5인1실"; $fmn = "1r5p";
                }
                
                $reserve_info = getReserveInfo($row1['reserveCode']);
                $prodInfo = getProductMaster($reserve_info['p_code']);
                if ($prodInfo['p_day'] > 1) {
                    if ($row1['sextype'] == "man") {
                        $sex= $fimg."<br />/남자";
                    } else if ($row1['sextype'] == "female") {
                        $sex= $fimg."<br />/여자";
                    } else if ($row1['sextype'] == "mfemale") {
                         $sex= $fimg."<br />/혼성";
                    }
                } else {
                    if ($row1['sextype'] == "man") {
                        $sex= "남자";
                    } else if ($row1['sextype'] == "female") {
                        $sex = "여자";
                    } else if ($row1['sextype'] == "mfemale") {
                         $sex = "혼성";
                    }
                }

                if ($prodInfo['p_type'] == 1) { $pcap = "로컬"; } 
                else if ($prodInfo['p_type'] == 2) { $pcap = "인바운드"; } 
                else if ($prodInfo['p_type'] == 4) { $pcap = "인센티브"; } 
                else if ($prodInfo['p_type'] == 5) { $pcap = "아웃바운드"; }
                
                if ($reserve_info['tour_type'] == 3) { $rcap = "협력사"; } 
                else { $rcap = "자사"; }
                
                $rname=randname($reserve_info['rand_id']);
                
                if ($prodInfo['p_type'] == 2) {
                      $reserve_info2 = getReserveInfo2($reserve_info['reserveCode'],$st);
                      $picknm = pickBaseCode3($reserve_info2['meet_area']);
                } else {
                        $pickarr = explode("/",$row1['pick_area']);
                        $picknm['pick_code'] = $row1['picCode'];
                }
                
                echo " <tr>
                            <td align='center'><input type='checkbox' class='form-control' value='{$row1['seq_no']}'><input type='hidden' name='hseq[]' id='hseq' value='{$row1['seqint']}'><input type='hidden' name='bnum[]' id='bnum1' value=''></td>
                            <td>{$row1['traveler_room']}<input type='hidden' name='rnum[]' value='{$row1['traveler_room']}'></td>
                            <td title='{$reserve_info['p_name']}'>{$row1['reserveCode']}<input type='hidden' name='rev[]' value='{$row1['reserveCode']}'></td>
                            <td align='center' title='{$reserve_info['p_name']}'>$pcap</td>
                            <td align='center' title='{$rname['kor_name']}'>$rcap</td>
                            <td align='center'>{$row1['traveler_nm']}<input type='hidden' name='revnm[]' value='{$row1['traveler_nm']}'></td>
                            <td align='center'>$sex<input type='hidden' name='roomt[]' value='$fmn'><input type='hidden' name='rsex[]' value='{$row1['sextype']}'></td>
                            <td>{$picknm['pick_name']}-{$picknm['pick_time']} <input type='hidden' name='pick[]' value='".addslashes($row1['pick_area'])."'></td>
                        </tr>";
        }
    }
    
    $troomcnt = 0;
    $troomcnt=getReserveRoomCnt($pcode,$st);
    $troomcnt3=getReserveRoomCnt($pcode,$st);
    $Buscnt=getBusCnt($sctour['grand_eCode']);
   // if ($num1 != 0) {
        $troomcnt3= $troomcnt3['rcnt'];
   // }
   // $troomcnt1= $troomcnt[rcnt];
    if (($Buscnt == "") || ($Buscnt == 0)){
        $troomcnt2 = 0;
    } else {
        $troomcnt2 = $troomcnt1-1;
    }

    function totbuslist() {
        global $dbConn,$pcode,$st,$Buscnt,$sctour,$troomcntr,$gcode;
        
        // 실제 사용된 최대 차량 번호 조회 (차량 수 동적 계산용)
        $qry_max = "select MAX(bus_num) as max_bus from tour_car where grand_eCode='{$sctour['grand_eCode']}' && p_code not like '%ADD%'";
        $rst_max = mysql_query($qry_max,$dbConn);
        $row_max = mysql_fetch_assoc($rst_max);
        $maxBusNum = $row_max['max_bus'] ? $row_max['max_bus'] : 0;
        
        // 최소 1대는 보장하거나, DB설정값, 실제 데이터 중 큰 값 사용
        $loopCnt = max(1, $Buscnt, $maxBusNum);

        for($r=1; $r<=$loopCnt; $r++)
        {
              $content .= "<div class='row'>
                                <div class='col-sm-1'>
                                    <div class='row'>$troomcnt</div>
                                    <div class='row text-center moveR' id='topRight_$r'><i class='splashy-arrow_medium_right'></i></div>
                                    <div class='row text-center moveL' id='topLeft_$r'><i class='splashy-arrow_medium_left'></i></div>
                                </div>
                                <div class='col-sm-11'>
                                    <table id='rightTableTop$r' class='table table-striped table-side-no-bordered table-hover table-condensed text-center rtab'>
                                        <thead>
                                            <tr>
                                                <th scope='col' colspan ='8'>차량$r</th>
                                            </tr>
                                            <tr>
                                                <th align='center'><input type='checkbox' class='form-control checkAll'></th>
                                                <th width='10%'>룸넘버</th>
                                                <th width='8%'>예약번호</th>
                                                <th>구분</th>
                                                <th>예약</th>
                                                <th>고객명</th>
                                                <th width='10%'>성별</th>
                                                <th>탑승지</th>
                                            </tr>
                                        </thead>
                                        <tbody>";
                
                $qry2= "select  
                                    grand_eCode, 
                                    sub_eNum, 
                                    sub_eCode, 
                                    reserveCode, 
                                    p_code, 
                                    p_name, 
                                    stDate, 
                                    bus_num, 
                                    romm_num, 
                                    rev_nm,
                                    room_type,
                                    sex, 
                                    picCode, 
                                    userid, 
                                    h_seq,
                                    wdate
                                    
                                    from 
                                    tour_car 
                                    where grand_eCode='{$sctour['grand_eCode']}' && stDate='$st' && bus_num ='$r' && p_code not like '%ADD%'";
                
                $rst2 = mysql_query($qry2,$dbConn);
                $hasData = false;
                $totp_cnt = 0;

                while($row1 = mysql_Fetch_assoc($rst2)){
                    $hasData = true;

                    if ($row1['room_type'] == "1r1p") { $fimg = "독방"; $fmn = "1r1p"; } 
                    else if ($row1['room_type'] === "1r2p") { $fimg = "2인1실"; $fmn = "1r2p"; } 
                    else if ($row1['room_type'] == "1r3p") { $fimg = "3인1실"; $fmn = "1r3p"; } 
                    else if ($row1['room_type'] == "1r4p") { $fimg = "4인1실"; $fmn = "1r4p"; } 
                    else if ($row1['room_type'] == "1r5p") { $fimg = "5인1실"; $fmn = "1r5p"; }
                    
                    $reserve_info = getReserveInfo($row1['reserveCode']);
                    $prodInfo = getProductMaster($reserve_info['p_code']);
                    
                    if ($prodInfo['p_day'] > 1) {
                        if ($row1['sex'] == "man") { $sex= $fimg."<br />/남자"; } 
                        else if ($row1['sex'] == "female") { $sex = $fimg."<br />/여자"; } 
                        else if ($row1['sex'] == "mfemale") { $sex = $fimg."<br />/혼성"; }
                    } else {
                        if ($row1['sex'] == "man") { $sex= "남자"; } 
                        else if ($row1['sex'] == "female") { $sex = "여자"; } 
                        else if ($row1['sex'] == "mfemale") { $sex = "혼성"; }
                    }

                    if ($prodInfo['p_type'] == 1) { $pcap = "로컬"; } 
                    else if ($prodInfo['p_type'] == 2) { $pcap = "인바운드"; } 
                    else if ($prodInfo['p_type'] == 4) { $pcap = "인센티브"; } 
                    else if ($prodInfo['p_type'] == 5) { $pcap = "아웃바운드"; }
                    
                    $rrnum = ($row1['romm_num'] == 1) ? "1" : $row1['romm_num'];
                    $rcap = ($reserve_info['tour_type'] == 3) ? "업체" : "자사";
                    
                    if ($prodInfo['p_type'] == 2) {
                             $reserve_info2 = getReserveInfo2($reserve_info['reserveCode'],$st);
                             $picknm = pickBaseCode3($reserve_info2['meet_area']);
                    } else {
                            $pickarr = explode("/",$row1['picCode']);
                            $picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
                            $picknm['pick_code'] = $row1['picCode'];
                    }

                    $content .= " <tr>
                                <td align='center'><input type='checkbox' class='form-control' value='{$row1['seq_no']}'><input type='hidden' name='hseq[]' id='hseq' value='{$row1['h_seq']}'><input type='hidden' name='bnum[]' id='bnum1' value='$r'></td>
                                <td>$rrnum<input type='hidden' name='rnum[]' value='{$row1['romm_num']}'></td>
                                <td>{$row1['reserveCode']}<input type='hidden' name='rev[]' value='{$row1['reserveCode']}'></td>
                                <td align='center'>$pcap</td>
                                <td align='center'>$rcap</td>
                                <td align='center'>{$row1['rev_nm']}<input type='hidden' name='revnm[]' value='{$row1['rev_nm']}'></td>
                                <td align='center'>$sex<input type='hidden' name='roomt[]' value='{$row1['room_type']}'><input type='hidden' name='rsex[]' value='{$row1['sex']}'></td>
                                <td>{$picknm['pick_name']}-{$picknm['pick_time']}<input type='hidden' name='pick[]' value='{$picknm['pick_code']}'></td>
                            </tr>";
                }   
                
                // 데이터가 있을 때만 통계 계산
                if ($hasData) {
                    $totp = getbusperson($r,$sctour['grand_eCode'],$st);
                    $totroom = getbusRoom($r,$sctour['grand_eCode'],$st);
                    $totp_cnt = $totp['cnt'];
                    $trnum = ($psInfo['p_day'] == 1) ? 0 : $totroom['r_cnt'];
                    $piccnt = getPicGr3($sctour['grand_eCode'],$r);
                } else {
                    $totp_cnt = 0;
                    $trnum = 0;
                    $piccnt = "탑승지 정보 없음";
                }
                
                $content .= "</tbody>
                                    </table>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-1'></div>
                                    <div class='col-sm-10'>
                                        <div class='panel-group'>
                                            <div class='panel panel-default'>
                                                <div class='panel-body custom_padding bg-info' id='sumtxt{$r}'>총인원 : $totp_cnt 인 &nbsp;&nbsp;&nbsp;&nbsp;총 객실수 : $trnum 개<br /> 
                                                 $piccnt
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>    
                                </div>
                            </div>";
        }
        return $content;
    }
?>
    <div id="contentwrapper" class="reservationDetailForm">
        <div class="main_content">
            <div id="jCrumbs" class="breadCrumb module">
                <ul>
                    <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                    <li><a href="#">행사배정관리</a></li>
                    <li>차량배정관리</li>
                </ul>
            </div>

            <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmcar" method="post" onSubmit="return chksave()">
                <input type="hidden" name="mode" id="mode" value="save">
                <input type="hidden" name="gcode" id="gcode" value="<?=$sctour['grand_eCode']?>">
                <input type="hidden" name="pcode" id="pcode" value="<?=$sctour['p_code']?>">
                <input type="hidden" name="pname" id="pname" value='<?=$sctour['p_name']?>'>
                <input type="hidden" name="sdate" id="sdate" value="<?=$sctour['stDate']?>">
                <table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                    <tbody>
                        <tr>
                            <td colspan="2" class="active text-center formHeader">통합행사코드</td>
                            <td colspan="12"><?=$sctour['grand_eCode']?></td>
                        </tr>
                                            
                            <td colspan="2" class="active text-center formHeader">상품명</td>
                            <td colspan="12">[<?=$sctour['p_code']?>] <?=$sctour['p_name']?></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="active text-center formHeader">출발일</td>
                            <td colspan="2"><?=$sctour['stDate']?></td>
                            
                            <td colspan="2" class="active text-center formHeader">투어정원</td>
                            <td colspan="2"><?=$sctour['tour_pcnt']?> 명 </td>
                            <td colspan="2" class="active text-center formHeader">예약인원</td>
                            <td colspan="2"><?=$pcnt['cnt']?> 명 </td>
                        </tr>
                        
                        <tr>
                            <td colspan="2" class="active text-center formHeader">예약인원</td>
                            <td colspan="12">
                                <label class="radio-inline">
                                    <input type="radio" name="bookNumber" value="P" <?php if(strstr($sctour['r_status'],"P")) echo "checked"; ?> disabled> 예약접수중
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="bookNumber" value="C" <?php if(strstr($sctour['r_status'],"C")) echo "checked"; ?> disabled> 예약마감
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
                                                <input type="radio" name="eventStatus" value="1" <?php if(strstr($sctour['ev_status'],"1")) echo "checked"; ?> disabled> 미확정
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="2" <?php if(strstr($sctour['ev_status'],"2")) echo "checked"; ?> disabled> 확정
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="3" <?php if(strstr($sctour['ev_status'],"3")) echo "checked"; ?> disabled> 만차
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="4" <?php if(strstr($sctour['ev_status'],"4")) echo "checked"; ?> disabled> 취소
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" <?php if(strstr($sctour['ev_status'],"5")) echo "checked"; ?> disabled> 기타
                                            </label>
                                        </div>
                                    </div>    
                                    <div class="col-sm-8">
                                        <div>    
                                            <input type="text" name="etcMemo" class="form-control" aria-label="기타메모"  placeholder="기타메모" value="<?=$sctour['etc_memo']?>" readOnly/>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>    
                           <td colspan="16" class="text-center">
                                <div class="row no-nav">
                                    <div class="col-sm-12 text-center">
                                        <button type="button" class="btn btn-primary btn-sm js-car" id="add_room">차량추가</button>
                                        <button type="button" class="btn btn-success btn-sm js-move-all" id="move_all_right">일괄배정(▶)</button>
                                        <button type="submit" class="btn btn-primary btn-sm js-esave" >차량배정저장</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="row">
                    <div class="col-sm-6" style='overflow:auto; height:500px;'>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="col-sm-12 text-success"><h5><strong>&nbsp;&nbsp;예약고객현황</strong></h5></div>    
                                <div class="col-sm-12" >
                                    <table id="leftTable" class="table table-striped table-side-no-bordered table-hover text-center">
                                        <thead>
                                            <tr>
                                                <th align="center"><input type="checkbox" class="form-control" id="selectAll"></th>
                                                <th >룸넘버</th>
                                                <th >예약번호</th>
                                                <th >구분</th>
                                                <th>예약</th>
                                                <th>고객명</th>
                                                <th >성별</th>
                                                <th>탑승지</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                reservelist2() ;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="panel-group">
                                    <div class="panel panel-default">
                                        <div class="panel-body custom_padding bg-info">총인원 : <?php echo $num1;?>인 &nbsp;&nbsp;&nbsp;&nbsp;총객실수 : <?php echo $troomcnt3;?>개</div>
                                    </div>
                                </div>
                            </div>
                        </div>    
                    </div>
                    <div class="col-sm-6" style='overflow:auto; height:1000px;'>
                        <fieldset class="guide-assign-border" id="busass">
                            <legend class="guide-assign-border"><span class="pull-left small text-muted">행사차량배정</span></legend>
                            <?php echo totbuslist(); ?>
                        </fieldset>  
                    </div>  
                </div>
            </form>
        </div>
    </div>
    <?php
        include "include/side_m.php"
    ?>
    <script>
        $(document).ready(function () {
            pt.initReservationList()
            
            //$.fn.dataTable.ext.errMode = 'none';
            var args = {paging:false, ordering:true, info:false,dom: 'Bfrtip',
                     buttons: [
                        'excel'
                     ]};
            
            
           var  _leftTable = $('#leftTable').DataTable(args);
           var  _rightTableTop = $('.rtab').DataTable(args);

            // [추가됨] 인원수 및 객실수 업데이트 (화면 리프레시 없이 계산)
            function updatePersonCount() {
                // 좌측
                var leftTable = $('#leftTable').DataTable();
                var leftCount = leftTable.rows().count();
                $('#leftTable').closest('.col-sm-6').find('.bg-info').html('총인원 : ' + leftCount + '인 (저장 전)');

                // 우측 차량 전체 루프
                $('.rtab').each(function() {
                    var tableId = $(this).attr('id');
                    var busNum = tableId.replace('rightTableTop', '');
                    var table = $('#' + tableId).DataTable();
                    var count = table.rows().count();
                    
                    // 객실 수 계산 (중복 룸넘버 제거)
                    var rooms = {};
                    table.rows().every(function() {
                         var data = this.data();
                         var roomRaw = data[1]; // 룸넘버 컬럼
                         var roomTxt = $(roomRaw).val() || $(roomRaw).text() || roomRaw; // input value 혹은 text
                         if(roomTxt && roomTxt != '0') rooms['roomTxt'] = true;
                    });
                    var roomCnt = Object.keys(rooms).length;

                    // 정보 업데이트
                    $('#sumtxt' + busNum).html('총인원 : ' + count + '인 &nbsp;&nbsp;&nbsp;&nbsp;총객실수 : ' + roomCnt + '개 (수정중)<br />저장 후 탑승지 갱신됨');
                });
            }

            $(document).on("click",".moveR",function(){
                 var id1 = $(this).attr('id');
                 var result=id1.split('_');
                 var targettab = "rightTableTop"+result[1]+"";
                 if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
                     _rightTableTop = $('#'+targettab+'').DataTable(args);
                 } else {
                    _rightTableTop = $('#'+targettab+'').DataTable(); 

                 }
                 
                 drawData('leftTable',targettab,_rightTableTop,_leftTable,result[1]);
            }); 
            $(document).on("click",".moveL",function(){
                var id1 = $(this).attr('id');
                //alert(id1);
                var result=id1.split('_');
                var targettab = "rightTableTop"+result[1]+"";
                if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
                     _rightTableTop = $('#'+targettab+'').DataTable(args);
                } else {
                    
                     _rightTableTop = $('#'+targettab+'').DataTable(); 

                }
                
                drawData2(targettab,'leftTable',_rightTableTop,_leftTable);
            }); 

            // [추가됨] 일괄 배정 로직 (좌측 목록 전체 -> 1호차)
            $('#move_all_right').click(function() {
                var leftTable = $('#leftTable').DataTable();
                if (!leftTable.data().any()) {
                    alert("배정할 인원이 없습니다.");
                    return;
                }

                // 전체 선택
                $('#leftTable tbody input[type="checkbox"]').prop('checked', true);

                // 1호차(혹은 첫번째 차량) 찾기
                var targetBus = 1; 
                var targetTabId = "rightTableTop" + targetBus;
                
                // 만약 1호차가 없으면 생성된 차량 중 가장 앞번호 찾기
                if ($('#' + targetTabId).length == 0) {
                     // 예외처리: 차량이 하나도 없으면 1호차 생성 트리거하거나 경고
                     alert("배정할 차량(1호차)이 없습니다. 차량추가를 먼저 해주세요.");
                     $('#leftTable tbody input[type="checkbox"]').prop('checked', false);
                     return;
                }

                var _rightTab;
                 if ( ! $.fn.DataTable.isDataTable( '#' + targetTabId ) ) {
                     _rightTab = $('#' + targetTabId).DataTable(args);
                 } else {
                    _rightTab = $('#' + targetTabId).DataTable(); 
                 }

                 // 이동 실행
                 drawData('leftTable', targetTabId, _rightTab, _leftTable, targetBus);
                 
                 // 체크 해제
                 $('#selectAll').prop('checked', false);
            });
                
            $(".js-rest").click(function(){
                $("#formId")[0].reset();
            });
            $('.checkAll').on('click', function () {
                $(this).closest('table').find('tbody :checkbox')
                  .prop('checked', this.checked)
                  .closest('tr').toggleClass('selected', this.checked);
            });
            $('#selectAll').click(function(e){
                var table= $(e.target).closest('table');
                $('td input:checkbox',table).prop('checked',this.checked);
            });

            var flex_cnt =0;
            
            var counter = parseInt('<?=$Buscnt?>');
            // 만약 버스가 없으면 1부터 시작
            if(isNaN(counter) || counter < 1) counter = 1;

            $('#add_room').click(function() {
                tableDraw();
            });
             var tableDraw = function(){
                counter++; 
               
                var sHtml = "<div class='row'>"+
                      "          <div class='col-sm-1'>  "+
                      "              <div class='row'></div>  "+
                      "              <div class='row text-center moveR' id='topRight_"+counter+"'><i class='splashy-arrow_medium_right'></i></div>                  "+
                      "              <div class='row text-center moveL' id='topLeft_"+counter+"'><i class='splashy-arrow_medium_left'></i></div>                      "+
                      "          </div>                  "+
                      "          <div class='col-sm-11 rightDiv'>  "+
                      "              <table id='rightTableTop"+counter+"' name='bustab[]' class='table table-striped table-side-no-bordered table-hover table-condensed text-center'>   "+
                      "                  <thead>                 "+
                      "                      <tr><input type='hidden' name='bus[]' id='cbus'  value='"+counter+"'>                     "+
                      "                          <th scope='col' colspan ='6'>차량"+counter+"-미배정</th>   "+
                      "                      </tr>                                                                   "+
                      "                      <tr>                                                                    "+
                      "                          <th align='center'></th>                                                        "+
                      "                          <th>룸넘버</th>                                                              "+
                      "                          <th>예약번호</th>                                                              "+
                      "                        <th >구분</th> "+    
                      "                         <th>예약</th> "+  
                      "                         <th>고객명</th> "+
                      "                          <th>성별</th>   "+
                      "                          <th>탑승지</th>                                                              "+
                      "                      </tr>                                                                   "+
                      "                  </thead>                                                                    "+
                      "                  <tbody>                                                                     "+
                      "                                                                                      "+
                      "                  </tbody>                                                                    "+
                      "              </table>                                                                    "+
                      "          </div>                                                                          "+
                      "          <div class='row'>                                                                   "+
                      "              <div class='col-sm-1'></div>                                                       "+
                      "              <div class='col-sm-10'>                                                                 "+
                      "                  <div class='panel-group'>                                                               "+
                      "                      <div class='panel panel-default'>                                                   "+
                      "                          <div class='panel-body custom_padding bg-info' id='sumtxt"+counter+"'>총인원 : 0명         총객실수 : 0개 <br />                  "+
                      "                          탑승지                                                                  "+
                      "                          </div>                                                                  "+
                      "                                                                                          "+
                      "                      </div>                                                                      "+
                      "                  </div>                                                                      "+
                      "              </div>                                                                          "+
                      "          </div>                                                                          "+
                      "      </div>                                                                                ";
                                
                $("#busass").append(sHtml);
                
                
          
                $(document).on("click",".moveR",function(){
                     var id1 = $(this).attr('id');
                     var result=id1.split('_');
                     var targettab = "rightTableTop"+result[1]+"";
                     if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
                         _rightTableTop = $('#'+targettab+'').DataTable(args);
                     } else {
                        _rightTableTop = $('#'+targettab+'').DataTable(); 

                     }
                     
                     drawData('leftTable',targettab,_rightTableTop,_leftTable,result[1]);
                }); 
                $(document).on("click",".moveL",function(){
                    var id1 = $(this).attr('id');
                    var result=id1.split('_');
                    var targettab = "rightTableTop"+result[1]+"";
                    if ( ! $.fn.DataTable.isDataTable( '#'+targettab+'' ) ) {
                         _rightTableTop = $('#'+targettab+'').DataTable(args);
                    } else {
                        
                         _rightTableTop = $('#'+targettab+'').DataTable(); 

                    }
                    
                    drawData2(targettab,'leftTable',_rightTableTop,_leftTable);
                }); 
                
                
            }
            
            // 데이터 이동 함수들 (업데이트 로직 추가됨)
            function drawData(name1,name2,_rightTableTop,_leftTable,bnum){
               
               var _selTable_1 = _leftTable;  
               var _selTable_2 = _rightTableTop;  
                  
               var tr,row,rowData;
               var moved = false;
               
               $('#'+name1+' td input[type=checkbox]').each(function () {
                   if ($(this).is(':checked')) {
                       $(this).attr('checked', 'checked'); // 유지보수용
                       tr = $(this).closest('tr');
                       row = _selTable_1.row(tr);
                       rowData = [];
                       tr.find('td').each(function(i, td) {
                           $(this).closest('tr').find("#bnum1").val(bnum);
                           rowData.push($(td).html());
                       });    
                       row.remove().draw();
                       _selTable_2.row.add(rowData).draw();
                       moved = true;
                   }    
               });    
               
               if(moved) updatePersonCount();
            }

            function drawData2(name1,name2,_rightTableTop,_leftTable,counter){
               
               var _selTable_1 = _rightTableTop;  
               var _selTable_2 = _leftTable;  
                 
               var tr,row,rowData;
               var moved = false;

               $('#'+name1+' td input[type=checkbox]').each(function () {
                   if ($(this).is(':checked')) {
                       $(this).attr('checked', 'checked');
                       tr = $(this).closest('tr');
                       row = _selTable_1.row(tr);
                       rowData = [];
                       tr.find('td').each(function(i, td) {
                           $(this).closest('tr').find("#bnum1").val("");
                           rowData.push($(td).html());
                       });    
                       row.remove().draw();
                       _selTable_2.row.add(rowData).draw();
                       moved = true;
                   }    
               });    
               
               if(moved) updatePersonCount();
            }

        }); // End ready

        function chksave() {
              if(confirm("차량배정을 저장하시겠습니까?") == true)
              {
                return true;
              }else {
                return false;
              }
        }
    </script>
    </body>
</html>
