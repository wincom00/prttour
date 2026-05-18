
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
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
	

    if ($mode == "save") {
		  if ($seq_no == "") {
			     $qry1 = "insert into bus_list (bus_team,
																bus_id,
																bus_buy,
																bus_number,
																bus_driver,
																bus_manager,
																bus_ezpass,
																bus_vin,
																bus_loan) values ('$bus_team',
																									'$bus_id',
																									'$bus_buy',
																									'$bus_number',
																									'$bus_driver',
																									'$bus_manager',
																									'$bus_ezpass',
																									'$bus_vin',
																									'$bus_loan')";

				 $rst1 = mysql_query($qry1,$dbConn);
		         
				 $goUrl_1 = "base_bus.php?division=$division&pdx=$pdx&sub=$sub";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		  } else {

			     $qry1 = "update bus_list set bus_team = '$bus_team',
															bus_id = '$bus_id',
															bus_buy = '$bus_buy',
															bus_number = '$bus_number',
															bus_driver = '$bus_driver',
															bus_manager = '$bus_manager',
															bus_ezpass = '$bus_ezpass',
															bus_vin = '$bus_vin',
															bus_loan = '$bus_loan' where seq_no = '$seq_no'";
				 $rst1 = mysql_query($qry1,$dbConn);
				 
				 $goUrl_1 = "base_bus.php?division=$division&pdx=$pdx&sub=$sub";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";



		  }

		  

	} 
	
	$qry1 = "select * from bus_list where seq_no = '$id'";
	$rst1 = mysql_query($qry1);
	$v_info = mysql_fetch_assoc($rst1);


?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">상품관리</a>
					</li>
					<li>
						<a href="#">상품등록</a>
					</li>
					<li>
						버스등록
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="busfrm" id="busfrm" method="post">
			            <input type=hidden name=mode value="save">
						<input type=hidden name=seq_no value="<?= $id ?>">
						
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>차량소속</td>
										<td colspan=3 bgcolor=#FFFFFF>&nbsp;<select name=bus_team id=bus_team class="inpubase md">
										<option value="">차량소속선택
										<?= printBaseCode2_without('B01',$v_info['bus_team']); ?></select></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>차량아이디</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name="bus_id"  id="bus_id" class="inpubase sm1" value="<?= $v_info['bus_id'] ?>"></td>
										<td width=15% align=center>차량구입일자</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name="bus_buy"  id="bus_buy" class="inpubase sm1" value="<?= $v_info['bus_buy'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>차량번호</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=bus_number id="bus_number"  class="inpubase md" value="<?= $v_info['bus_number'] ?>"></td>
										<td width=15% align=center>담당운전기사</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=bus_driver   class="inpubase md" value="<?= $v_info['bus_driver'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>차량담당자</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=bus_manager  class="inpubase md" value="<?= $v_info['bus_manager'] ?>"></td>
										<td width=15% align=center>EZ-PASS #</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=bus_ezpass   class="inpubase md" value="<?= $v_info['bus_ezpass'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>차량등록번호</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=bus_vin id="bus_vin"  class="inpubase md" value="<?= $v_info['bus_vin'] ?>"></td>
										<td width=15% align=center>모게지</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=bus_loan   class="inpubase sm1" value="<?= $v_info['bus_loan'] ?>"></td>
									</tr>
									
									<tr>
										<td colspan=4 height=35 bgcolor=#FFFFFF class="titletd" style="vertical-align: middle;"><input type="submit" class="submit" value="저장" class="btn btn-primary btn-sm"></td>
									</tr> 
							</tbody>
						</table>
					 </form>
					  
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
    
    <script>
       $(document).ready(function() {
		   
			$('#bus_buy').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});

			$("#bus_buy").inputmask("9999-99-99",{placeholder:"yyyy-mm-dd"});

			$('.submit').click(function(){
				
				var rtn =validateForm(); 
				return rtn;

			});

			function validateForm(){

				
				var bus_team = $('#bus_team').val();
				var bus_id = $('#bus_id').val();
				var bus_buy = $('#bus_buy').val();
				var bus_number = $('#bus_number').val();
				var bus_vin = $('#bus_vin').val();
				//var email = $('#emailInput').val();
				//var telephone = $('#telInput').val();
				//var message = $('#messageInput').val();

				var inputVal = new Array(bus_team, bus_id,bus_buy,bus_number,bus_vin);

				var inputMessage = new Array("버스회사", "버스고유아이디","차량구입일자","차량번호","차량등록번호");

				 $('.error').hide();

				if(inputVal[0] == ""){
					$('#bus_team').after('<span class="error"> '+inputMessage[0] + ' 를 입력하세요!</span>');
					return false;
				} 
				
				if(inputVal[1] == ""){
					$('#bus_id').after('<span class="error"> ' + inputMessage[1] + ' 를 입력하세요!</span>');
					return false;
				} 

				if(inputVal[2] == ""){
					$('#bus_buy').after('<span class="error"> ' + inputMessage[2] + ' 를 입력하세요!</span>');
					return false;
				} 

                if(inputVal[3] == ""){
					$('#bus_number').after('<span class="error"> ' + inputMessage[3] + ' 를 입력하세요!</span>');
					return false;
				}
				
				if(inputVal[4] == ""){
					$('#bus_vin').after('<span class="error"> ' + inputMessage[4] + ' 를 입력하세요!</span>');
					return false;
				} 

				

					
			}   
		 });
	</script>


    </body>
</html>

      
      