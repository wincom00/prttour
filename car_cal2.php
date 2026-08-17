<?php
    include "include/header.php";

	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

    $seqno = $_GET['number'];

    //투어 기본 정보 (서브코드 포함 데이터 추출)
    $query = "SELECT a.*, (SELECT kor_name FROM member_list where userid = a.guide_id AND division ='guide') AS kr_name,
    (SELECT base_rate FROM product_master b where a.p_code = b.p_code) AS base_rate
    FROM tour_guide a WHERE a.seq_no = $seqno ";
    $rst1 = mysql_query($query,$dbConn);
    $data_row = mysql_fetch_assoc($rst1);

    //행사기간
    $period = getPeriodbyhotel($data_row['p_code'],$data_row['stDate']);
    //행사인원
    $p_cnt = getReserveInfoCnt($data_row['p_code'],$data_row['stDate']);

    //CAR SETTLE 정보
    $query = "SELECT * FROM car_settlebus WHERE grand_eCode = '{$data_row["grand_eCode"]}' AND sub_eCode = '{$data_row["sub_eCode"]}' ";
    $rst2 = mysql_query($query,$dbConn);

    //CAR SETTLE ETC 정보
    $query = "SELECT * FROM car_settleetc WHERE grand_eCode = '{$data_row["grand_eCode"]}' AND sub_eCode = '{$data_row["sub_eCode"]}' ";
    $rst3 = mysql_query($query,$dbConn);

    //CAR SETTLE SUM 정보
    $query = "SELECT * FROM car_settlesum WHERE grand_eCode = '{$data_row["grand_eCode"]}' AND sub_eCode = '{$data_row["sub_eCode"]}' ";
    $rst4 = mysql_query($query,$dbConn);
    $hotel_sum_data = mysql_fetch_assoc($rst4);

?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">정산관리</a></li>
					<li>차량정산 (<?=$data_row['sub_eCode']?>)</li>
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
							<td colspan="6" class="m_rate"><?=$data_row['base_rate']?></td>
                            <td colspan="2" class="active text-center formHeader">서브코드</td>
							<td colspan="6"><b style="color:blue;"><?=$data_row['sub_eCode']?></b></td>
                        </tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">행사코드</td>
							<td colspan="6"><b style="color:blue;"><?=$data_row['grand_eCode']?></b></td>
                            <td colspan="2" class="active text-center formHeader">행사명</td>
							<td colspan="6"><?=$data_row['p_name']?></td>
                        </tr>
                        <tr>
							<td colspan="2" class="active text-center formHeader">행사기간</td>
							<td colspan="6"><?=$period?></td>
                            <td colspan="2" class="active text-center formHeader">행사인원</td>
							<td colspan="6"><?=$p_cnt['cnt']?>명&nbsp;/<?=$data_row['bus_num']?>호차</td>
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
                            <?php if($ii ==0) { ?><td colspan="2" <?=$rowspan?> class="active text-center formHeader">차량선택&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td> <?php }?>

                            <input type="hidden" name="hotel_seq[]" class="hotel_seq" value="<?=$row1['seq_no']?>">

                            <td colspan="14">
                                <div class="row hotel_div">
									<div class="col-sm-2">
										<select class="form-control hotellist" name="hotelSelect[]">
                                            <option value="">- 차량을 선택하세요 -</option>
                                            <?php echo printCarSelect($row1['c_code']); ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE</span>
                                            <input type="text" name="hotelRateRm[]" class="form-control rate" aria-label="RATE" value="<?=$row1['rate_rm']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">차량비</span>
                                            <input type="text" name="hotelCost[]" class="form-control hotelCost" aria-label="차량비" value="<?=$row1['bus_amt']?>"/>
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

                        <?php if($rst2_cnt <= 0) { // 차량정산 데이터가 없는 경우 ?>
						<?php
								  // 배정 정보 조회 시에도 sub_eCode 조건 추가
								  $qry00 = "SELECT * FROM tour_guide WHERE grand_eCode = '".$data_row['grand_eCode']."'
								  AND sub_eCode = '".$data_row['sub_eCode']."' ORDER BY bus_num ASC";
								  $rst00 = mysql_query($qry00,$dbConn);
								  while($row00 = mysql_Fetch_assoc($rst00)){
							?>
                        <tr class="basic-class" param="tr-parent">
                            <td colspan="2" class="active text-center formHeader">차량선택&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>

                            <input type="hidden" name="hotel_seq[]" class="hotel_seq" value="">

                            <td colspan="14">
                                <div class="row hotel_div">
									<div class="col-sm-2">
										<select class="form-control hotellist" name="hotelSelect[]">
                                            <option value="">- 차량을 선택하세요 -</option>
                                            <?php echo printCarSelect($row00['c_id']); ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">RATE</span>
                                            <input type="text" name="hotelRateRm[]" class="form-control rate" aria-label="RATE" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">차량비</span>
                                            <input type="text" name="hotelCost[]" class="form-control hotelCost" aria-label="차량비" value=""/>
                                        </div>
                                    </div>

                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>

								</div>
                            </td>
                        </tr>
							<?php } ?>
                        <?php } ?>


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
                                            <option value="">- 차량을 선택하세요 -</option>
                                            <?php echo printCarSelect($row1['c_code']); ?>
                                        </select>
                                    </div>

									<div class="col-sm-2">
										<select class="form-control" name="etcCostSelect[]">
                                            <option value="">- 기타비용을 선택하세요 -</option>
                                            <?php $etcexpense = getEtcCostSelect3();
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

                        <?php if($rst3_cnt <=0) { //기타비용 데이터가 없는경우 ?>
                        <tr class="cost-class" param ="tr-parent">
                            <td colspan="2" class="active text-center formHeader">기타비용&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <input type="hidden" name="etc_seq[]" class="etc_seq" value="">

                            <td colspan="14">
                                <div class="row">
                                    <div class="col-sm-2">
										<select class="form-control hotellist" name="hotelSelect_etc[]">
                                            <option value="">- 차량을 선택하세요 -</option>
                                            <?php echo printCarSelect($data_row['c_id']); ?>
                                        </select>
                                    </div>

									<div class="col-sm-2">
										<select class="form-control" name="etcCostSelect[]">
                                            <option value="">- 기타비용을 선택하세요 -</option>
                                            <?php $etcexpense = getEtcCostSelect3();
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
                            <td colspan="2" class="active text-center formHeader">차량총액</td>
                            <td colspan="6">차량 총액-기준통화기준</td>
                            <td colspan="2" class="active text-center formHeader">실제지불총액</td>

                            <td colspan="3">
                                <input type="text" name="totalPayment" id="totalPayment" class="form-control" readonly value="<?=$hotel_sum_data['real_amt']?>"/>
                            </td>

                            <td colspan="3" class="text-center" style="vertical-align:middle;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-default" onclick="location.href='hotel_cal2.php?division=6&pdx=1&sub=15&number=<?=$seqno?>'">호텔 정산 등록</button>
                                    <button type="button" class="btn btn-primary" disabled>차량 정산 등록</button>
                                </div>
                            </td>

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

                calcuAmount();
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
            calcuAmount();
        }

        $(".hotellist").chosen({width: '100%' });

        $(document).on("click",".js-save",function(e) {
            var form = $("#frnName").closest("form");
            var formData = new FormData(form[0]);

            $.ajax({
                type: 'POST',
                url: 'cal_save.php',
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
                        location.href = "car_cal2.php?division=6&pdx=1&sub=15&number="+number;
                    }
                }
            });
            document.getElementById("frnName").submit();
		});

        $(document).on('focusout',".rate, .rm, .hotelCost, .etcAmount", function () {
            var row =$(this).closest("tr");
			var rate = parseFloat(row.find('.rate').val() || 0);
            // 차량은 RM수가 보통 1이므로 rate가 바로 cost가 되는 경우가 많으나 구조 유지
            calcuAmount();
        })

        function calcuAmount(){
            var hotelcost = 0;
            var etcamount = 0;
            $("input[name='hotelCost[]']").each(function(){ hotelcost = Number(hotelcost) + Number(this.value); });
            $("input[name='etcAmount[]']").each(function(){ etcamount = Number(etcamount) + Number(this.value); });
            $("#totalPayment").val((hotelcost+etcamount).toFixed(2));
        }

        $(document).on("click", ".js-deleteHotelButton", function(){
            if(confirm("삭제하시겠습니까?") == true) {
                var seqno = $(this).attr("data-id");
                var grandcode = $("#grand_eCode").val();
                var subcode = $("#sub_eCode").val();

                $.ajax({
                    type: 'POST',
                    url: 'cal_save.php',
                    data:{seqno:seqno,grandcode:grandcode,subcode:subcode,mode:'delete_hotel'},
                    success: function (response) {
                        location.href = "car_cal2.php?division=6&pdx=1&sub=15&number="+number;
                    }
                });
            }
        });

        $(document).on("click", ".js-delete1HotelButton", function(){
            if(confirm("삭제하시겠습니까?") == true) {
                var seqno = $(this).attr("data-id");
                var grandcode = $("#grand_eCode").val();
                var subcode = $("#sub_eCode").val();

                $.ajax({
                    type: 'POST',
                    url: 'cal_save.php',
                    data:{seqno:seqno,grandcode:grandcode,subcode:subcode,mode:'delete_etc'},
                    success: function (response) {
                        location.href = "car_cal2.php?division=6&pdx=1&sub=15&number="+number;
                    }
                });
            }
        });
	</script>
    </body>
</html>
