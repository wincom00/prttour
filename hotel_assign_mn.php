<?php
    include "include/header.php";
    //include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

  /* if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }
	*/
	$sctour = getTourInfo2($pcode,$st);
	$pcnt = getReserveInfoCnt($pcode,$st);				
	if ($pcnt['cnt'] =="") {
		$pcnt['cnt'] = 0;
	}
	$gcode = $sctour['grand_eCode']; 
    $cartour = getbusMemo($gcode,$gscode);
		
	if ($mode == "save") {

		//print_r($hname);
		//exit;
		for($r=1;$r<=count($hname);$r++)
		{
			//echo $hname[$r-1]."TEST";
			
			
			if (($hname[$r-1]!='')) {
			
				if(strstr($pcode, "ADD")=="") { 
					echo "1";
						//for($n=0;$n<count(${'seq'.$r});$n++) {
						   //  echo ${'seq'.$r}[$n]."<br />";
							
							
							//exit;
							$qryi = "insert into hotel_assign 
												( 
												grand_eCode, 
												reserveCode, 
												hotel_code, 
												room_num, 
												sub_eCode, 
												p_code, 
												p_name, 
												stDate, 
												day, 
												tnm, 
												sex,
												pcnt,
												userid, 
												wdate
												)
												values
												( 
												'$gcode', 
												'', 
												'".$hname[$r-1]."', 
												'', 
												'$gscode', 
												'$pcode', 
												'$pname', 
												'$sdate', 
												'".$r."', 
												'', 
												'', 
												'".$roompp[$r-1]."',  
												'{$user_dbinfo['userid']}', 
												now()
												)";

							$rsti = mysql_query($qryi,$dbConn);
						 // echo  $qryi;
						//  exit;
							$qryu = "update tour_car 
											set
											h_memo = '$hotelEventMemo'  
											where
											grand_eCode = '$gcode' 
											&& sub_eCode = '$gscode' 
											";
							$rstu = mysql_query($qryu,$dbConn);

						
				} else {

						echo "2";
							
							
							$qryi = "insert into hotel_assign 
												( 
												grand_eCode, 
												reserveCode, 
												hotel_code, 
												room_num, 
												sub_eCode, 
												p_code, 
												p_name, 
												stDate, 
												day, 
												tnm, 
												sex,
												pcnt,
												userid, 
												wdate
												)
												values
												( 
												'$gcode', 
												'', 
												'', 
												'', 
												'$gscode', 
												'$pcode', 
												'$pname', 
												'$sdate', 
												'".$day[$r]."', 
												'', 
												'', 
												'".$roompp[$r]."',  
												'{$user_dbinfo['userid']}', 
												now()
												)";

							$rsti = mysql_query($qryi,$dbConn);

							$qryu = "update tour_car 
											set
											h_memo = '$hotelEventMemo'  
											where
											grand_eCode = '$gcode' 
											&& sub_eCode = '$gscode' 
											";
							$rstu = mysql_query($qryu,$dbConn);

				}
			}

			//exit;
		}
		//exit;
		Misc::jvAlert("업데이트 되었습니다!!","");
		

	}
	if ($mode == "del") {

           $qryi = "delete from hotel_assign where seq_no='".$no."'";
		   $rsti = mysql_query($qryi,$dbConn);
    }
	function buslist() {
		global $dbConn,$pcode,$st,$num1,$gscode;
		
		if(strstr($pcode, "ADD")=="") { 
			$qry1 = "select 	
						grand_eCode, 
						sub_eCode, 
						reserveCode, 
						bus_num
						from tour_car 
						where stDate = '$st' && p_code = '$pcode'  group by bus_num order by bus_num asc";
       //echo $qry1;
		} else {
			$qry1 = "select 	
						grand_eCode, 
						sub_eCode, 
						reserveCode, 
						''
						from hotelroom_assign 
						where stDate = '$st' && p_code = '$pcode'  group by sub_eCode";
						

		}
		//echo $qry1;
		$rst1 = mysql_query($qry1,$dbConn);
		$num1 = mysql_num_rows($rst1);
		$k = 0;
		while($row1 = mysql_Fetch_assoc($rst1)){
			$s = $k+1;
			$rcnt = getReserveInfoRoom2($pcode,$st,$row1['sub_eCode']);
			//echo $rcnt;
			if ($k == 0) {
				echo " <tr>
							<td colspan='16' class='active text-left formHeader'> 
								<div class='col-sm-12'>
									<div class='col-sm-5'>차량$s -{$row1['sub_eCode']} </div>
									<input type='hidden' name='gscode1' id='gscode1' value='{$row1['sub_eCode']}'>
									<div class='col-sm-3'>
										<div class='input-group input-group-sm'>       
										   <label class='radio-inline'>
										   ";
										   if ($gscode == $row1['sub_eCode']) {
										         echo "<input type='radio' name='roomnumber' value='$s' checked onClick='selectcar(\"{$row1['sub_eCode']}\")'>방갯수 : {$rcnt['rcnt']} 개";
										   } else {
											   echo "<input type='radio' name='roomnumber' value='$s' onClick='selectcar(\"{$row1['sub_eCode']}\")' >방갯수 : {$rcnt['rcnt']} 개";

										   }
										  echo" </label>
										</div>
									</div>
								</div>    
							</td>    
						</tr>";
			
			} else {
				echo " <tr>
							<td colspan='16' class='active text-left formHeader'> 
								<div class='col-sm-12'>
									<div class='col-sm-5'>차량$s -{$row1['sub_eCode']} </div>
									<input type='hidden' name='gscode1' id='gscode1' value='{$row1['sub_eCode']}'>
									<div class='col-sm-3'>
										<div class='input-group input-group-sm'>       
										   <label class='radio-inline'>";
											if ($gscode == $row1['sub_eCode']) {
										         echo "<input type='radio' name='roomnumber' value='$s' onClick='selectcar(\"{$row1['sub_eCode']}\")' checked >방갯수 : {$rcnt['rcnt']} 개";
										   } else {
											   echo "<input type='radio' name='roomnumber' value='$s'onClick='selectcar(\"{$row1['sub_eCode']}\")' >방갯수 : {$rcnt['rcnt']} 개";

										   }
										   echo "
										   </label>
										</div>
									</div>
								</div>    
							</td>    
						</tr>";


			}
			$k++;
		}
	
	}
	$gscode1 = $gscode;
	function Hotellist() {
		global $dbConn,$pcode,$st,$num1,$gcode,$gscode;
		//echo $pcode;
		if(strstr($pcode, "ADD") !== "") {  
			$qry3 = " SELECT max(day)  as days FROM product_details where p_code = '$pcode'";
			$rst3 = mysql_query($qry3,$dbConn);
			$row3 = mysql_Fetch_assoc($rst3);

			$qry4 = " SELECT p_code  FROM tour_master where grand_eCode = '$gcode' && p_code like '%ADD%'";
			$rst4 = mysql_query($qry4,$dbConn);
			$row4 = mysql_Fetch_assoc($rst4);
            //echo $qry4;
			if ($row4['p_code'] !="") {
				$qryday = "&& day";
			} else  {
				$qryday = "&& day!={$row3['days']}";
			}
			$qry1 = "select 	
			 			seq_no,p_code,day,area
						from product_details 
						where p_code = '$pcode'  $qryday order by day asc";//<> (SELECT max(day) FROM product_details where p_code = '$pcode') order by day asc";
		    //echo $qry1;
			$rst1 = mysql_query($qry1,$dbConn);
			$num1 = mysql_num_rows($rst1);
			$k = 1;
			
			while($row1 = mysql_Fetch_assoc($rst1)){

				echo "<div class='col-sm-12'>
						<table class='table table-bordered table-condensed gridSixteen reserveTable formDetail'>
						<tbody>
							<tr>
							<td colspan='2' class='text-center bg-success text-white'> {$row1['day']}일차</td>   
							<td  class='active text-left formHeader'> 
								<select class='form-control hoarea' name='harea[]'>
								<option selected>- 호텔지역선택 -</option>
								";
							echo printBaseCode_hotel();
							echo "	</select>
							</td>   
							<td  class='active text-left formHeader'> 
								<select class='form-control telarea' name='hname[]'>
								<option value='' selected>- 호텔명선택 -</option>
							
								</select>
							</td>    
							
							<td  class='active text-left formHeader'> 
								<input type=number name='roompp[]' class='form-control' placeholder='방갯수'>
							</td>
							<td  class='active text-left formHeader'> 
								<button type=button class='form-control btnadd' placeholder='추가'>추가</button>
							</td>
						</tbody>
						</table>
					</div>
					
					<div class='col-sm-12'>
						<table id='rightTableTop' class='table table-striped table-side-no-bordered table-hover table-condensed text-center rightTableTop'>
						<thead>
							<tr>
							<th width='27'width='10%' align='center'><input type='checkbox' class='form-control checkAll'></th>
							<th width='20%'>서브행사코드</th>
							<th width='*'>선택호텔명</th>
							<th width='15%'>방갯수</th>
							<th width='15%'>Action</th>
							</tr>
						</thead>
						<tbody>";

			 $qry2 ="select seq_no,grand_eCode, sub_eCode, hotel_code,p_code,p_name,day,pcnt from hotel_assign
						
						where stDate = '$st' && p_code = '$pcode' && grand_eCode='$gcode' && sub_eCode = '$gscode' && day='{$row1['day']}' order by seq_no asc";
			// echo $qry2;
			 $rst2 = mysql_query($qry2,$dbConn);
			 $j = 0;
			 while($row2 = mysql_Fetch_assoc($rst2)){
				    
					$hinfo= getHotelfInfo($row2['hotel_code']);
					//print_r($hinfo);
					echo"<tr>
							<td align='center'><input type='checkbox' class='form-control' name='seq[]' id='seq' value=".$row2['seq_no']."></td>
							<td>{$row2['sub_eCode']}<input type='hidden' name='sub[]' id='sub' value='{$row2['sub_eCode']}'></td>
							<td>{$hinfo['h_name']}</td>
							<td>{$row2['pcnt']}<input type='hidden' name='pcnt[]'  value='{$row2['pcnt']}'></td>
							<input type='hidden' name='day[]'  value='{$row2['pcnt']}'>
							<td><button type=button class='form-control btndel' placeholder='삭제' value=".$row2['seq_no'].">삭제</button></td>
						</tr>";
					$j++;  
						
			  }
			 $hcnt=getHotelCnt1($gscode,$pcode,$st,$k);
			  echo "</tbody>
						</table>
						<div class='row'>
							<div class='col-sm-1'></div>
							<div class='col-sm-10'>
								<div class='panel-group'>
									<div class='panel panel-default'>
										
										
									</div>
								</div>
							</div>    
						</div>
					</div>"; 
			 $k++;

		  }
			 
		} else {
			
			echo "<div class='col-sm-12'>
						<table class='table table-bordered table-condensed gridSixteen reserveTable formDetail'>
						<tbody>
							<tr>
							<td colspan='2' class='text-center bg-success text-white'> 추가일차</td>   
							<td  class='active text-left formHeader'> 
								<select class='form-control hoarea' name='harea[]'>
								<option selected>- 호텔지역선택 -</option>
								";
							echo printBaseCode_hotel();
							echo "	</select>
							</td>   
							<td  class='active text-left formHeader'> 
								<select class='form-control telarea' name='hname[]'>
								<option value='' selected>- 호텔명선택 -</option>
							
								</select>
							</td>
							<td  class='active text-left formHeader'> 
								<input type=number name='roompp[]' class='form-control' placeholder='방갯수'>
							</td>
							<td  class='active text-left formHeader'> 
								<button type=button class='form-control btnadd' placeholder='추가'>추가</button>
							</td>
							</tr>
						</tbody>
						</table>
					</div>
					
					<div class='col-sm-12'>
						<table id='rightTableTop' class='table table-striped table-side-no-bordered table-hover table-condensed text-center rightTableTop'>
						<thead>
							
							<tr>
							<th width='27'width='10%' align='center'><input type='checkbox' class='form-control checkAll'></th>
							<th width='20%'>서브행사코드</th>
							<th width='*'>선택호텔명</th>
							<th width='15%'>방갯수</th>
							<th width='15%'>Action</th>
							</tr>

						</thead>
						<tbody>";

			 /*$qry2 = "select distinct
			           a.grand_eCode, 
					   a.sub_eCode, 
					   a.reserveCode, 
					   a.room_num,
					   a.tnm,a.sex,
					   b.hotel_code
					   from hotelroom_assign a left outer join hotel_assign b on a.grand_eCode=b.grand_eCode && a.sub_eCode = b.sub_eCode && b.day='0'
					   where a.stDate = '$st' 
					   && a.p_code = '$pcode' && a.grand_eCode='$gcode' && a.sub_eCode = '$gscode' order by a.room_num asc"; */
		
			$qry2 ="select grand_eCode, sub_eCode, hotel_code,p_code,p_name from hotel_assign
						
						where stDate = '$st' && p_code = '$pcode' && grand_eCode='$gcode' && sub_eCode = '$gscode' && b.day='{$row1['day']}' order by seq_no asc";
			 //echo $qry2;
			 $rst2 = mysql_query($qry2,$dbConn);
			 $j = 0;
			 while($row2 = mysql_Fetch_assoc($rst2)){
				    $hinfo= getHotelfInfo($row2['hotel_code']);
					echo"<tr>
							<td align='center'><input type='checkbox' class='form-control' name='seq1[]' id='seq' value='".$row2['seq_no']."'></td>
							
							
							<td>{$row2['sub_eCode']}<input type='hidden' name='sub1[]'  value='{$row2['sub_eCode']}'></td>
							<td>{$hinfo['h_name']}</td>
							<input type='hidden' name='day1[]'  value='1'>
						</tr>";
							  

					echo"<tr>
							<td align='center'><input type='checkbox' class='form-control' name='seq1[]' id='seq' value='".$row2['seq_no']."'></td>
							<td>{$row2['sub_eCode']}<input type='hidden' name='sub1[]' id='sub1' value='{$row2['sub_eCode']}'></td>
							<td>{$hinfo['h_name']}</td>
							<td>{$row2['pcnt']}<input type='hidden' name='pcnt1[]'  value='{$row2['pcnt']}'></td>
							<input type='hidden' name='day1[]'  value='{$row1['day']}'>
							<td><button type=button class='form-control btndel' placeholder='삭제' value=".$row2['seq_no'].">삭제</button></td>
						</tr>";
					$j++;  
				   
			  }
			  echo "</tbody>
						</table>
					</div>";

		}

	}

	
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정관리</a></li>
					<li>호텔배정관리</li>
				</ul>
			</div>

			
			<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmhotel" id="frmhotel" method="post">
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="gcode" id="gcode" value="<?=$sctour['grand_eCode']?>">
				<input type="hidden" name="gscode" id="gscode" value="<?=$gscode1?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$sctour['p_code']?>">
				<input type="hidden" name="pname" id="pname" value="<?=$sctour['p_name']?>">
				<input type="hidden" name="sdate" id="sdate" value="<?=$sctour['stDate']?>">
				<input type="hidden" name="no" id="no" value="0">
				<br />
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
									    
									    <button type="button" class="btn btn-primary btn-sm js-esave1" OnClick='chksave()'>호텔배정저장</button>
                                    <!--    <button type="button" class="btn btn-primary btn-sm js-rest" id="resetcar">전체초기화</button>-->
                                        
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
				</table>
				<div class="row">
                    <div class="col-sm-5" style='overflow:auto; height:500px;'>
                        <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                
                                <?php buslist() ?>
                                <tr>
                                    <td colspan="16" ><textarea class="form-control" rows="7" name="hotelEventMemo" placeholder="호텔행사메모"><?=$cartour['h_memo']?></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-sm-7" style='overflow:auto; height:500px;'>
                        <fieldset class="guide-assign-border">
                            <legend class="guide-assign-border"><span class="pull-left small text-muted">행사호텔배정</span></legend>
                            <div class="row">
                                <?php Hotellist() ?>
                            </div>
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
			$.ajaxSetup({async: false});
			pt.initReservationList()
            var args = {paging:false, ordering:true, info:false,dom: 'Bfrtip',
					 buttons: [
						'excel'
					 ]};
           
		    $('.rightTableTop').DataTable(args);
            $('.checkAll').on('click', function () {
                $(this).closest('table').find('tbody :checkbox')
                  .prop('checked', this.checked)
                  .closest('tr').toggleClass('selected', this.checked);
            });
            $(".telarea").chosen({
					width: '100%' 
			});
            $('.js-esave1').on('click', function () {
                $("#mode").val("save") ;
				$("#frmhotel").submit();
            });
			$('.btnadd').on('click', function () {
                //$("#mode").val("add") ;
				$("#frmhotel").submit();
            });
			$('.btndel').on('click', function () {
                $("#mode").val("del") ;
				$("#no").val($(this).val()) ;
				$("#frmhotel").submit();
            })
			$('.hoarea').bind("change",function() {
				var code1 = $(this).val();
			    var sel = $(this); 
				$.getJSON("get_hotel.php?code1="+code1, function(jsonData){
					 sel.closest('tr').find(".telarea").empty();
					 sel.closest('tr').find(".telarea").append('<option value="">호텔선택</option>');
					 $.each(jsonData, function(i,data){
						  var codev = data.h_code;
						  sel.closest('tr').find(".telarea").append('<option value="'+codev+'">['+codev+'] '+data.h_name+'</option>');
											
					 });
					sel.closest('tr').find(".telarea").trigger("chosen:updated");  
				});
				
			});
            
		})
		function selectcar(subcode) {
				  
				$("#mode").val("") ;
				//alert(subcode);
				$("#gscode").val(subcode) ;
				$("#frmhotel").submit();

		}
		function chksave() {
			
			  if(confirm("호텔배정을 저장하시겠습니까?") == true)
			  {
				return true;
			  }else {
				return;
			  }

		  
		}
	</script>
    </body>
</html>
