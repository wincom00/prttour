<?php
    include "include/header.php";
	
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
	if($Mode == "del")
	{
		$qry1 = "delete from product_hotel where seq_no= '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}
	
	function printVendor(){
			
			global $dbConn,$division,$g_nm,$pdx,$sub;
			if ($g_nm != "") {

				$gudnm = "&& h_name like '%$g_nm%' ";
			} else {

				$gudnm = "";

			}
			
			$qry1 = "select * from product_hotel where 1=1 $gudnm  order by h_name asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_Fetch_assoc($rst1)){
			
				$cinfo1=codebaseName($row1['p_typem']);
			    $cinfo2=codebaseName($row1['p_types']);
				echo "<tr bgcolor=#FFFFFF>
				<td align=left>{$cinfo1['comment']}:{$cinfo2['comment']}</td>
				<td height=25>&nbsp;{$row1['h_code']}</td>
				<td align=center>{$row1['h_name']}</td>
				<td align=center>{$row1['h_grade']}</td>
				<td align=center>{$row1['hd_price']}</td>
				<td align=center><a href=hotel_regi_m.php?division=$division&pdx=$pdx&sub=$sub&pcode=".$row1['h_code'].">수정</a> | <a href=\"javascript:del({$row1['seq_no']})\">삭제</a></td>
				</tr>";


			}

	}
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
						호텔등록
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
			          <input type="hidden" name="mode" value="search">
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   <tr>
							      <td width=10%  class="titletd" style="vertical-align: middle;">호텔명 </td>
								  <td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="g_nm" name="g_nm" class="inpubase lg" value="<?=$g_nm?>"/></td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='hotel_regi_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table class="table table-striped table-bordered mediaTable js-hotelListTable">
						<thead>
							<tr>
							    <th width=10% class="essential">호텔분류</th>
								<th width=10% class="essential">호텔코드</th>
								<th width=25% class="essential">호텔명</th>
								<th width=10% class="essential">호텔등급</td>
								<th width=10% class="essential">호텔표시용<br />가격</td>
								<th width=15% class="essential">수정|삭제</td>

							    
							</tr>
						</thead> 
							
						<?php printVendor(); ?>
					  </table>
                     
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
    
    <script>
	    $(document).ready(function () {
			pt1.initHotelList()
		})
		function del(id){
			
			if(confirm("삭제할까요?") == true)
			{
				location.replace('hotel_regi.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
		
		
	</script>


    </body>
</html>

      
      