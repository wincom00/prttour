<?php
    include "include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    /*if (!hasMenuAccess($division, $pdx, $sub)) {
    	 $goUrl_1 = "index.php";
		   Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		 	 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;
    }
	*/
	if($mode == "save")
	{
		for($i=0; $i<count($seq_no); $i++)
		{
			$qry1 = "update api_musical set ranking = '$ranking[$i]', best_musical = '$best_musical[$i]' where seq_no = '$seq_no[$i]'";
			$rst1 = mysql_query($qry1);
		}
	}

	function printProduct(){
		
		global $dbConn,$division,$m_city,$pdx,$sub;

		if($m_city)
		{
			$m_city_qry = "where m_city = '$m_city'";
		}

		$qry1 = "select * from api_musical $m_city_qry order by view_opt desc,ranking asc";
		$rst1 = mysql_query($qry1);

		while($row1 = mysql_fetch_assoc($rst1)){
			

			$roomType = codebaseName($row1['room_type']);

			if($row1['view_opt'] == "YES")
			{
				$display = "<font color=blue>진열중</font>";
			}
			else
			{
				$display = "<font color=red>숨김</font>";
			}


			echo "<tr bgcolor=#FFFFFF>
				<td align=center height=25><input type=checkbox name=seqNo[] value={$row1['m_code']}></td>
				<td align=center height=70><input type=hidden name=seq_no[] value={$row1['seq_no']}><input type=text name=ranking[] value=\"{$row1['ranking']}\" size=4></td>
				<td align=center><input type=text name=best_musical[] value=\"{$row1['best_musical']}\" size=4></td>
				
				<td align=center ><a href=api_musical_modify.php?division=$division&pdx=$pdx&sub=$sub&no={$row1['seq_no']}><b><u>{$row1['m_code']}</u></b></a></td>
				<td>&nbsp;{$row1['m_name_eng']}<br>&nbsp;$display<br>&nbsp;{$row1['our_price_msg']}</td>
				<td align=center>{$row1['theater_name']}</td>
				<td align=center>{$row1['m_city']}</td>
			</tr>";

		}

	}

?>
<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">상품관리</a></li>
					<li>상품등록</li>
					<li>뮤지컬등록</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
				 <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
				  <input type="hidden" name="mode" value="search">
					<table class="table table-striped table-bordered table-condensed">
						<tbody>
						   <tr>
							  <td width=10%  class="titletd" style="vertical-align: middle;">뮤지컬명 </td>
							  <td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="g_nm" name="g_nm" class="inpubase lg" value="<?=$g_nm?>"/></td>
							  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
							  <td class="conttd"><a href='api_musical_modify.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
						   </tr> 
						</tbody>
					</table>
				  </form>
				  
			  
				  <table class="table table-striped table-bordered mediaTable js-MListTable">
				  <script>
						function GoCheckAll() {
							var FormChkObj = document.getElementsByName("seqNo[]"); 	
								
							for (var orderidx = 0; orderidx<FormChkObj.length; orderidx++) {						
								if (FormChkObj['orderidx'].checked==true) {
									FormChkObj['orderidx'].checked=false;
								}
								else {
									FormChkObj['orderidx'].checked=true;
								}		
							}		
						}	
						function go_bbdisplay2(){

							var FormChkObj = document.getElementsByName("seqNo[]"); 	
							var strAnySelectedValue = false;	
							
							for (var orderidx = 0; orderidx<FormChkObj.length; orderidx++) {						
								if (FormChkObj['orderidx'].checked==true) {
									strAnySelectedValue = true;	
									break;
								}
							}				
							
							if (strAnySelectedValue==false) {
								alert("적어도 한개는 선택하세요.");	
								return;
							}	

							tf = document.musical;


							if(confirm("해당 상품을  진열할까요?") == true)
							{
								tf.view_position.value = tf.ad_spot.value;
								tf.action = 'todaydeal_list.php';
								tf.submit();
							}
							else return;

						}	
					</script>
				  <form name=musical action=<?= $PHP_SELF ?>?division=2&pdx=1&sub=35&m_city=<?=$m_city?> method=post>
				  <input type=hidden name=mode value=save>
				  <input type=hidden name=mCode value="<?= $mCode ?>">
				  <input type=hidden name=item_type value="musical">
				  <input type=hidden name=view_position value="">
					<thead>
						<tr>
							<td bgcolor=#FFFFFF height=30 colspan=8>&nbsp;&nbsp;<a href=<?= $PHP_SELF ?>?division=2&pdx=1&sub=35&m_city=NYCA>뉴욕</a> | <a href=<?= $PHP_SELF ?>?division=2&pdx=1&sub=35&m_city=LASV>라스베가스</a> | <a href=<?= $PHP_SELF ?>?division=2&pdx=1&sub=35&m_city=NYCS>뉴욕스포츠</a></td>
						</tr>
						<tr bgcolor=#b2dcca height=28>
							<th width=5% align=center><input type=checkbox onclick="GoCheckAll();" ></span></td>
							<th width=10% align=center>인기순위</td>
							<th width=10% align=center>베스트</td>
							<th width=15% align=center>코드</td>
							<th width=25% align=center>뮤지컬명</td>
							<th width=20% align=center>공연극장</td>
							<th width=10% align=center>지역</td>
						</tr>
					</thead>
				  <?php printProduct(); ?>
					<tr>
						<td height=35 bgcolor=#FFFFFF colspan=8>&nbsp;<button type=submit class="btn btn-primary btn-sm btng">순위조절</button></td>
					</tr>
					</form>
				  </table>
				</div>
			</div>
		</div>
	</div>
	<?php
		include "include/side_m.php"
	?>
    </body>
</html>
			  

?>