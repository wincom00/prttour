<?php
    include "include/header.php";
    
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
/*
    if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }
	*/

    $seqno = $_GET['number'];

    //투어 기본 정보
    $query = "SELECT a.*, (SELECT kor_name FROM member_list where userid = a.guide_id AND division ='guide') AS kr_name,
    (SELECT base_rate FROM product_master b where a.p_code = b.p_code) AS base_rate
    FROM tour_guide a WHERE a.seq_no = $seqno ";
    $rst1 = mysql_query($query,$dbConn);
    $data_row = mysql_fetch_assoc($rst1);

    //행사기간
    $period = getPeriodbyhotel($data_row['p_code'],$data_row['stDate']);
    //행사인원
    $p_cnt = getReserveInfoCnt($data_row['p_code'],$data_row['stDate']);

    //HOTEL SETTLE 정보
    $query = "SELECT * FROM hotel_settle WHERE grand_eCode = '{$data_row["grand_eCode"]}' AND sub_eCode = '{$data_row["sub_eCode"]}' ";
    $rst2 = mysql_query($query,$dbConn);

    //HOTEL SETTLE ETC 정보
    $query = "SELECT * FROM hotel_settleetc WHERE grand_eCode = '{$data_row["grand_eCode"]}' AND sub_eCode = '{$data_row["sub_eCode"]}' ";
    $rst3 = mysql_query($query,$dbConn);

    //HOTEL SETTLE SUM 정보
    $query = "SELECT * FROM hotel_settlesum WHERE grand_eCode = '{$data_row["grand_eCode"]}' AND sub_eCode = '{$data_row["sub_eCode"]}' ";
    $rst4 = mysql_query($query,$dbConn);
    $hotel_sum_data = mysql_fetch_assoc($rst4);

?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">정산관리</a></li>
					<li>호텔별정산</li>
				</ul>
			</div>

			<form name="frnName" id="frnName" method="post" action="">
				<input type="hidden" name="mode" id="mode" value="save">
                <input type="hidden" name="grand_eCode" id="grand_eCode" value="<?=$data_row['grand_eCode']?>">
                <input type="hidden" name="sub_eCode" id="sub_eCode" value="<?=$data_row['sub_eCode']?>">
                <input type="hidden" name="m_rate_h" id="m_rate_h" value="<?=$data_row['base_rate']?>">
                <input type="hidden" name="hotel_sumseq" value="<?=$hotel_sum_data['seq_no']?>">

				<div class="row no-nav">
					<div class="col-sm-12 text-center">
						<button type="button" class="btn btn-xs btn-default js-save">저장</button>
					</div>
				</div>
				<br />
				<table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
                        <tr>
							<td colspan="2" class="active text-center formHeader">기준통화</td>
							<td colspan="14" class="m_rate"><?=$data_row['base_rate']?></td>
                           
                        </tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">행사코드</td>
							<td colspan="6"><?=$data_row['grand_eCode']?> <font color="red"><?=$data_row['sub_eCode']?></font></td>
                            <td colspan="2" class="active text-center formHeader">행사명</td>
							<td colspan="6"><?=$data_row['p_name']?></td>
                        </tr>
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">행사기간</td>
							<td colspan="6"><?=$period?></td>
                            <td colspan="2" class="active text-center formHeader">행사인원</td>
							<td colspan="6"><?=$p_cnt['cnt']?></td>
                        </tr>
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">가이드</td>
							<td colspan="14"><?=$data_row['kr_name']?></td>
                        </tr>

                        <?php $ii=0; $rst2_cnt = mysql_num_rows($rst2);
                        while($row1 = mysql_Fetch_assoc($rst2)){ 
                            if($ii ==0) $rowspan = "rowspan='$rst2_cnt'";  
                        ?>
                            
                        <tr class="basic-class" param ="tr-parent"> 
                            <?php if($ii ==0) { ?><td colspan="2" <?=$rowspan?> class="active text-center formHeader">호텔선택&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td> <?php }?>
                            
                            <input type="hidden" name="hotel_seq[]" class="hotel_seq" value="<?=$row1['seq_no']?>">
                           
                            <td colspan="14">
							   
                                <div class="row hotel_div">
									<div class="col-sm-2">
										<select class="form-control hotellist" name="hotelSelect[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();

                                            while($row11 = mysql_Fetch_assoc($hotelList)){

                                            ?>
                                            <option value="<?=$row11['h_code']?>" <?php if($row1['h_code'] == $row11['h_code']) echo 'selected'; ?> ><?=$row11['h_name']?></option>
                                            <?php }?>

                                        </select>
                                    </div>    
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE/RM</span>
                                            <input type="text" name="hotelRateRm[]" class="form-control" aria-label="RATE/RM" value="<?=$row1['rate_rm']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RM수</span>
                                            <input type="text" name="hotelRm[]" class="form-control" aria-label="RM수" value="<?=$row1['room_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">호텔비</span>
                                            <input type="text" name="hotelCost[]" class="form-control hotelCost" aria-label="호텔비" value="<?=$row1['hotel_amt']?>"/>
                                        </div>
                                    </div>
                                    <?php if($row1['seq_no']>0) {?>
                                    <div class="col-sm-1 show button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-deleteHotelButton" data-id="<?=$row1['seq_no']?>" ><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

                                    <?php }else{ ?>

                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                    <?php }?>
								
								</div>
                            </td>

                        </tr>
                        <?php $ii++;}  
						   
						?>

                        <?php if($rst2_cnt <=0) { //호텔정보 리스트 데어터가 없는 경우 ?>
						<?php
								  
								  $qry00 = "SELECT * FROM hotel_assign WHERE grand_eCode='".$data_row['grand_eCode']."'";
							     
								  $rst00 = mysql_query($qry00);
								  $jj=0;
								  while($row00 = mysql_Fetch_assoc($rst00)){
								  if($jj ==0) $rowspan = "rowspan='$rst2_cnt'"; 
								  //echo $row00[hotel_code] ;
							?>
                        <tr class="basic-class" param ="tr-parent"> 
                            <td colspan="2" class="active text-center formHeader" >호텔선택&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            
                            <input type="hidden" name="hotel_seq[]" class="hotel_seq" value="<?=$row1['seq_no']?>">
                           
									
                            <td colspan="14">
                                <div class="row hotel_div">
									<div class="col-sm-2">
									  
										<select class="form-control hotellist" name="hotelSelect[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();

                                            while($row11 = mysql_Fetch_assoc($hotelList)){
												
                                            ?>
											
                                            <option value="<?=$row11['h_code']?>" <?php if($row00['hotel_code'] == $row11['h_code']) echo 'selected'; ?> ><?=$row11['h_name']?></option>
                                            <?php }?>

                                        </select>
                                    </div>    
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE/RM</span>
                                            <input type="text" name="hotelRateRm[]" class="form-control rate" aria-label="RATE/RM" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RM수</span>
                                            <input type="text" name="hotelRm[]" class="form-control rm" aria-label="RM수" value="<?=$row00['pcnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">호텔비</span>
                                            <input type="text" name="hotelCost[]" class="form-control hotelCost" aria-label="호텔비" value=""/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

								</div>
                            </td>
							
                        </tr>
							<?php } ?>
                        <?php }?>


                        <?php $jj=0; $rst3_cnt = mysql_num_rows($rst3);
                        while($row1 = mysql_Fetch_assoc($rst3)){ 
                            if($jj ==0) $rowspan = "rowspan='$rst3_cnt'";  
                        ?>

                        <tr class="cost-class" param ="tr-parent">
                            <?php if($jj ==0) { ?><td colspan="2" <?=$rowspan?> class="active text-center formHeader">기타비용&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td> <?php }?>

                            <input type="hidden" name="etc_seq[]" class="etc_seq" value="<?=$row1['seq_no']?>">

                            <td colspan="14">
                                <div class="row">
                                    <div class="col-sm-2">
										<select class="form-control hotellist" name="hotelSelect_etc[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();

                                            while($row11 = mysql_Fetch_assoc($hotelList)){

                                            ?>
                                            <option value="<?=$row11['h_code']?>" <?php if($row1['h_code'] == $row11['h_code']) echo 'selected'; ?>><?=$row11['h_name']?></option>
                                            <?php }?>

                                        </select>
                                    </div>    

									<div class="col-sm-2">
										<select class="form-control" name="etcCostSelect[]">
                                            <option value="">- 기타비용을 선택하세요 -</option>
                                            <?php $etcexpense = getEtcCostSelect();
                                            while($row11 = mysql_Fetch_assoc($etcexpense)){
                                                $code = $row11['lvcode2'].$row11['lvcode3'];

                                            ?>
                                            <option value="<?=$code?>" <?php if($row1['etc_code'] == $code) echo 'selected'; ?>><?=$row11['comment']?></option>
                                            <?php }?>
                                        </select>
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE</span>
                                            <input type="text" name="etcRate[]" class="form-control" aria-label="RATE" value="<?=$row1['rate']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">수량</span>
                                            <input type="text" name="etcCount[]" class="form-control" aria-label="수량" value="<?=$row1['cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="etcAmount[]" class="form-control etcAmount" aria-label="금액" value="<?=$row1['etc_amt']?>"/>
                                        </div>
                                    </div>

                                    <?php if($row1['seq_no']>0) {?>
                                    <div class="col-sm-1 show button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-delete1HotelButton" data-id="<?=$row1['seq_no']?>"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

                                    <?php }else{ ?>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                    <?php }?>

								</div>
                            </td>
                        </tr>
                        <?php $jj++;}?>

                        <?php if($rst3_cnt <=0) { //기본비용 데이터가 없는경우 ?>
                        <tr class="cost-class" param ="tr-parent">
                            <td colspan="2" class="active text-center formHeader">기타비용&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td> 
                            <input type="hidden" name="etc_seq[]" class="etc_seq" value="<?= $row1['seq_no'] ?>">

                            <td colspan="14">
                                <div class="row">
                                    <div class="col-sm-2">
										<select class="form-control hotellist" name="hotelSelect_etc[]">
                                            <option value="">- 호텔을 선택하세요 -</option>
                                            <?php $hotelList = getHotelList();

                                            while($row11 = mysql_Fetch_assoc($hotelList)){

                                            ?>
                                            <option value="<?=$row11['h_code']?>"><?=$row11['h_name']?></option>
                                            <?php }?>

                                        </select>
                                    </div>    


									<div class="col-sm-2">
										<select class="form-control" name="etcCostSelect[]">
                                            <option value="">- 기타비용을 선택하세요 -</option>
                                            <?php $etcexpense = getEtcCostSelect();
                                            while($row11 = mysql_Fetch_assoc($etcexpense)){
                                                $code = $row11['lvcode2'].$row11['lvcode3'];

                                            ?>
                                            <option value="<?=$code?>"><?=$row11['comment']?></option>
                                            <?php }?>
                                        </select>
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE</span>
                                            <input type="text" name="etcRate[]" class="form-control" aria-label="RATE" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">수량</span>
                                            <input type="text" name="etcCount[]" class="form-control" aria-label="수량" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="etcAmount[]" class="form-control etcAmount" aria-label="금액" value=""/>
                                        </div>
                                    </div>

                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

								</div>
                            </td>
                        </tr>

                        <?php }?>

                        <tr>
                            <td colspan="2" class="active text-center formHeader">호텔총액</td>
                            <td colspan="6">호텔별 총액-기준통화기준</td>
                            <td colspan="2" class="active text-center formHeader">실제지불총액</td>
                            <td colspan="6"><input type="text" name="totalPayment" id="totalPayment" class="form-control" aria-label="실제지불총액" readonly value="<?=$hotel_sum_data['real_amt']?>"/></td>
                            
                        </tr>
						<tr>
							<td colspan="16">
								<textarea class="form-control" rows="7" name="memo" placeholder="메모"><?=$hotel_sum_data['memo']?></textarea>
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
		
        var number = "<?=$_GET['number']?>";

        $(document).ready(function () {
			pt.initReservationList() ;

            
            $('.js-addPlusRow').on( 'click', function () {
                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table ."+cls+":last"));
                //$('.hotel-minus').not( ':first' ).removeClass('hide');

                newrow.find('button:last').addClass('js-removeHotelButton');
                newrow.find('button:last').removeClass('js-deleteHotelButton');
                newrow.find('button:last').removeClass('js-delete1HotelButton');
                
                newrow.find('.hotel_seq').val('');
                newrow.find('.etc_seq').val('');
                newrow.find(".hotellist").chosen({width: '100%' });
                newrow.find($('.chosen-container-single:last')).css('display','none');
                newrow.find($('.hotel_div .chosen-container-single:last')).css('display','none');
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);

                calcuAmount(); //실제지불금액

            });
            
            $(document).on("click", ".js-removeHotelButton", function(){
                var clickedRow = $(this).closest('tr').remove();
                var cls = clickedRow.attr("class");
                resizeRowspan(cls);
            });

		})
        
        function resizeRowspan(cls){
            var rowspan = $("."+cls).length;
            $("."+cls+":first td:eq(0)").attr("rowspan", rowspan); 
            calcuAmount(); //실제지불금액
        }

        $(".hotellist").chosen({width: '100%' });

        //호텔리스트 체인지 : 셋팅 기본통화
        /*$(document).on('change', '.hotellist', function(){ 
            var r_v = $(this).val();
            var s_v = r_v.split(':::');

            $(".m_rate").html("<font color='red'>"+s_v[1]+"</font>");
            $("#m_rate_h").val(s_v[1]);
             
        });*/

        //저장클릭
        $(document).on("click",".js-save",function(e) { 
        	
            var form = $("#frnName").closest("form");
            var formData = new FormData(form[0]);

            $.ajax({
                type: 'POST',
                url: 'hotel_save.php',
                data: formData,
                cache:false,
                processData: false,
                contentType: false,
                success: function (response) {
                    var msg = response.split("/");

                    if(msg[0] =='0') {
                        alert(msg[1]);

                        return false;
                    }else{
                        //location.href = "hotel_settle.php?division=6&pdx=1&sub=15";
                        location.href = "hotel_cal2.php?division=6&pdx=1&sub=15&number="+number;
                    }
                }
            });


            //$("select[name='hotelSelect[]']").each(function(){
                //alert(this.value);
            //});

            document.getElementById("frnName").submit();  
		});
        
        //호텔비 포커스아웃시
        $(document).on('focusout',".rate",function () {
            var row =$(this).closest("tr");
			var rate = parseFloat(row.find('.rate').val() || 0);
			var rm = parseFloat(row.find('.rm').val() || 0);
			
            row.find('.hotelCost').val(rate*rm);
			calcuAmount();
            
        })
		$(document).on('focusout',".rm",function () {
            var row =$(this).closest("tr");
			var rate = parseFloat(row.find('.rate').val() || 0);
			var rm = parseFloat(row.find('.rm').val() || 0);
			
            row.find('.hotelCost').val(rate*rm);
			calcuAmount();
            
        })
        //호텔비 포커스아웃시
        $(document).on('focusout',".hotelCost",function () {
            calcuAmount();
            
        });

        //기타비용 금액 포커스아웃시
        $(document).on('focusout',".etcAmount",function () {
            calcuAmount();
        });

        //실제지불총액
        function calcuAmount(){

            var hotelcost = 0;
            var etcamount = 0;
            
            $("input[name='hotelCost[]']").each(function(){
                hotelcost = Number(hotelcost) + Number(this.value);
            });

            $("input[name='etcAmount[]']").each(function(){
                etcamount = Number(etcamount) + Number(this.value);
            });
           // alert(hotelcost);
            var totalamount = (hotelcost+etcamount).toFixed(2);

            $("#totalPayment").val(totalamount); //실제지불총액

        }

        //호텔관련 데이터 삭제
        $(document).on("click", ".js-deleteHotelButton", function(){
            if(confirm("삭제하시겠습니까?") == true) {
                var seqno = $(this).attr("data-id");
                var grandcode = $("#grand_eCode").val();
                var subcode = $("#sub_eCode").val();

                $.ajax({
                    type: 'POST',
                    url: 'hotel_save.php',
                    data:{seqno:seqno,grandcode:grandcode,subcode:subcode,mode:'delete_hotel'},
                    success: function (response) {
                        location.href = "hotel_cal2.php?division=6&pdx=1&sub=15&number="+number;
                    }
                }); 
        

            }

        });

        //기타비용 삭제
        $(document).on("click", ".js-delete1HotelButton", function(){
            if(confirm("삭제하시겠습니까?") == true) {
                var seqno = $(this).attr("data-id");
                var grandcode = $("#grand_eCode").val();
                var subcode = $("#sub_eCode").val();

                $.ajax({
                    type: 'POST',
                    url: 'hotel_save.php',
                    data:{seqno:seqno,grandcode:grandcode,subcode:subcode,mode:'delete_etc'},
                    success: function (response) {
                        location.href = "hotel_cal2.php?division=6&pdx=1&sub=15&number="+number;
                    }
                }); 
            }

        });


	</script>
    </body>
</html>
