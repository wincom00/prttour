
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
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
	if ($estimateCode) {
		$crev =$estimateCode;

	}
	if ($ty == 1) {
        $pcap = "직접예약현황";
	} else if ($ty == 2) {
        $pcap = "웹예약현황";
	} else if ($ty == 3) {
        $pcap = "업체예약현황";
	}
	
	
?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약관리</a></li>
					<li><a href="#">예약관리</a></li>
					<li><?= $pcap ?></li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post" >
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
						    
							<tr>
								<td width="10%" class="titletd text-center">예약고객명</td>
								<td width="40%" class="">
									<input type="text" id="cname" name="cname" class="inpubase" value="<?=$cname?>"/>
								</td>
								<td width="10%" class="titletd text-center">예약번호</td>
								<td width="40%" class="">
									<input type="text" id="crev" name="crev" class="inpubase" value="<?=$crev?>"/>
								</td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">이메일</td>
								<td width="40%" class=""><input type="text" id="cemail" name="cphone" class="inpubase" value="<?=$cemail?>" autocomplete=off /></td>
								<td width="10%" class="titletd text-center">출발일</td>
								<td width="40%" class="">
									<div class="row">
										<div class="col-sm-6">
											<input type="text" id="startDate1" name="startDate1" class="inpubase tourDate1" value="<?=$startDate1?>" autocomplete=off />
										</div>
										
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
							</tr>
						</table>

						
					</form>

					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-revTable">
								<thead>
								   
									<tr>
										<th>투어종류</th>
										<th>대표예약번호</th>
										<th>예약번호</th>
										<th>상품명</th>
										<th>예약자</th>
										<th>인원</th>
										<th>최종결제금액</th>
										<th>잔액</th>
										<th>소유사</th>
										<th>출발일</th>
										<th>접수일</th>
										<th>최종수정일</th>
										<th>접수상태</th>
										<th>담당자</th>
									</tr>
								</thead>
								<tbody>
									
								</tbody>
							</table>
						</div>
					</div>
					
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
		       $.ajaxSetup({async:false});
				pt.initReservationList()
				pt1.initProductDetailForm2()
				var dateToday = new Date()
			    $('.tourDate1').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true,
					startDate: dateToday
			    });
				$( ".btnchk" ).click(function() {
					
					if ($("#startDate1").val() == "") {
						alert("출발일을 입력하세요!");
						$("#startDate1").focus();
						return false;
				    }
					

				});
				$('.js-revTable').DataTable({
					 "bProcessing": true,
					 "bServerSide": true,
					 "pageLength": 50, 
					  bFilter: false,
					 dom: 'Bfrtip',
					 buttons: [
						'copy', 'csv', 'excel', 'print'
					 ],
					"sServerMethod": "POST",
					 "order": [[ 11, "desc" ]],
					"sAjaxSource": "allmyprocess.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>&cname=<?=$cname?>&startDate1=<?=$startDate1?>&crev=<?=$crev?>&cemail=<?=$cemail?>",
					"aoColumns": [ 
                        {"sClass": "tcenter"},
                        {"sClass": "tleft"},
                        {"sClass": "tleft"},
                        {"sClass": "tleft"},
                        {"sClass": "tcenter"},
						  {"sClass": "tcenter"},
						 {"sClass": "tright"},
						 {"sClass": "tright"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tleft"},
						]
		 
			   });
		})
		
	</script>
    </body>
</html>
