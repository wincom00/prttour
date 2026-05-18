
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
		$qry1 = "delete from base_opt  where pick_opt= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);
	}
	
	
	function printOpt() {
			
			global $dbConn,$division,$pdx,$sub,$pick_nm;

			if ($com_nm != "") {
				$qrycom = " && opt_name like '%$opt_nm%' ";
			} else {
				$qrycom ="";

			}

			$qry1 = "select * from base_opt where 1=1  $qrycom order by opt_code asc, opt_code asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_Fetch_assoc($rst1)){
				
				    
					echo "<tr bgcolor=#FFFFFF>
					<td align=center>{$row1['opt_code']}</td>
					<td align=center>{$row1['opt_name']}</td>
					<td align=center>{$row1['opt_1desc']}</td>
					
					<td align=center><a href=base_opt_m.php?division=$division&pdx=$pdx&sub=$sub&pcode={$row1['opt_code']}>수정</a> | <a href=\"javascript:del('{$row1['opt_code']}')\">삭제</a></td>
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
						<a href="#">기초관리</a>
					</li>
					<li>
						<a href="#">기초코드관리</a>
					</li>
					<li>
						상품옵션등록
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
							      <td width=10%  class="titletd" style="vertical-align: middle;">옵션명 </td>
								  <td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="pick_nm" name="pick_nm" class="inpubase lg" value="<?=$pick_nm?>"/></td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 btnatt">검색</button> </td>
								  <td class="conttd"><a href='base_opt_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1 btnatt">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width='10%' class="essential" align="center">옵션코드</th>
								<th width='20%' class="essential" align="center">옵션명</th>
								<th width='*' class="essential">한줄설명</th>
								<th width='20%' class="essential">가격</th>
								<th width=10% class="essential">수정 | 삭제</th>
							</tr>
						</thead> 
							
						<?php printOpt(); ?>
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
				location.replace('base_pick.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&pcode=' + id);
			}
			else return;
		}
		
		
	</script>


    </body>
</html>

      
      