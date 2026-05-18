
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
		$qry1 = "delete from bus_list where seq_no= '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}

	function printVendor(){
			
			global $dbConn,$division,$pdx,$sub,$bus_team;
			
			if ($bus_team != "") {
				$qrybus = " && bus_team = '$bus_team' ";
			} else {
				$qrybus ="";

			}

			$qry1 = "select * from bus_list where 1=1 $qrybus order by seq_no asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_Fetch_assoc($rst1)){
				
			$bus_team = codebaseName($row1['bus_team']);
			echo "<tr bgcolor=#FFFFFF>
			<td align=center>{$bus_team['comment']}</td>
			<td height=25>&nbsp;{$row1['bus_id']}</td>
			<td>&nbsp;{$row1['bus_driver']}</td>
			<td>&nbsp;{$row1['bus_number']}</td>
			<td align=center><a href=base_bus_m.php?division=$division&pdx=$pdx&sub=$sub&id={$row1['seq_no']}>수정</a> | <a href=\"javascript:del({$row1['seq_no']})\">삭제</a></td>
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
						버스등록
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
							      <td width=10%  class="titletd" style="vertical-align: middle;">차량소속</td>
								  <td width=20% style='border:0;' class="conttd"><select name=bus_team class="inpubase md"><?= printBaseCode2_without('B01','00',$bus_team); ?></select></td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='base_bus_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width=30% class="essential" align="center">소속</td>
								<th width=20% class="essential" align="center">차량 ID</td>
								<th width=20% class="essential" align="center">기사명</td>
								<th width=20% class="essential" align="center">차량번호</td>
								<th width=10% class="essential" align="center">수정|삭제</td>
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
		function del(id){
			
			if(confirm("삭제할까요?") == true)
			{
				location.replace('base_bus.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
		
		
	</script>


    </body>
</html>

      
      