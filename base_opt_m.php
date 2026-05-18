
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
			 $qry1 = "delete  from base_pick where pick_code='$pcode'";
			 $rst1 = mysql_query($qry1,$dbConn);
			  for ($i=0;$i<count($ptp_2);$i++) {
						if ($i == 0) {
							$parent = "M";
						} else {
							$parent = "S";
						}
						$qry1 = "insert into base_pick 
													( 
													pick_m, 
													pick_code, 
													pick_name, 
													pick_time,
													pick_1desc,
													pick_addr, 
													pick_map, 
													wdate
													)
													values
													( 
													'$parent', 
													'$scode', 
													 '".mysql_real_escape_string($stname)."', 
													'$ptp_2[$i]', 
													'$desc1', 
													'$saddress', 
													'".addslashes($s_map)."',
													now()
													);
												";
						
						
						 $rst1 = mysql_query($qry1,$dbConn);
			 }
			 
			 $goUrl_1 = "base_pick.php?division=$division&pdx=$pdx&sub=$sub";
			 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		  

	} 
	$v_info = getinfo_dbPick_bycode($pcode);


?>
<script src="ckeditor/ckeditor.js"></script>
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
						<a href="#">기초관리</a>
					</li>
					<li>
						탑승지등록
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>"  name="base_pick" id="base_pick" method="post">
			            <input type=hidden name=mode value="save">
						<input type=hidden name=pcode value="<?= $pcode ?>">
						
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
								<tr>
										<td colspan=4 height=35 bgcolor=#FFFFFF class="titletd" style="vertical-align: middle;"><input type=submit value="저장" class="btn btn-primary btn-sm btnatt"></td>
									</tr> 
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd">출발지명</td>
										<td  bgcolor=#FFFFFF>&nbsp;<input type=text name=stname  class="inpubase lg" value="<?= $v_info['pick_name'] ?>"> </td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">출발지코드</td>
										<td  bgcolor=#FFFFFF>&nbsp;<input type=text name=scode  class="inpubase md" value="<?= $v_info['pick_code'] ?>"> </td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">출발시간&nbsp;<a href='#' class="addtime"><span class="label label-default">추가</span></a></td>
										<td  bgcolor=#FFFFFF >
											<Table border='0' cellpadding='0' cellspacing='0' id='sTime'> 
											<?php
													$qry1 = "select * from base_pick where pick_code='$pcode' order by pick_time asc";
													$rst1 = mysql_query($qry1,$dbConn);
													$k = 0;
													 if (mysql_affected_rows() > "0") { 
			                                             while($row1 = mysql_Fetch_assoc($rst1)) {
															  if ($k != 0) {
																	$delbtn= "<button type='button' class='btn btn-danger btn-xs delBtn'>삭제</button>";
															   } else {
																	$delbtn= "";
															   }
													   
											?>
																<tr>
																	<td class="bootstrap-timepicker">&nbsp;<input type=text name="ptp_2[]"  id="ptp_2" class="inpubase sm1" value="<?=$row1['pick_time']?>">&nbsp; <?=$delbtn?>

																	</td>
																</tr>
											<?php
											                    $k++;
														 }
													} else {
											?>
																 <tr>
																	<td class="bootstrap-timepicker">&nbsp;<input type=text name="ptp_2[]"  id="ptp_2" class="inpubase sm1" value=""></td>
																</tr>
											<?php
											       }
											?>
											       
											 </table> 
										</td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">한줄설명</td>
										<td  bgcolor=#FFFFFF>&nbsp;<input type=text name=desc1  class="inpubase lg" value="<?= $v_info['pick_1desc'] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">탑승장소주소</td>
										<td  bgcolor=#FFFFFF>&nbsp;<input type=text name=saddress  class="inpubase lg" value="<?= $v_info['pick_addr'] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">탐승장소 지도</td>
										<td  bgcolor=#FFFFFF><textarea name="s_map" id="s_map" class="form-control"><?= $v_info['pick_map'] ?></textarea></td>
										
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
				//* bootstrap timepicker
		        paran_timepicker.init();
				$('.addtime').click(function(e) {
					
					 var tbl = $("#sTime");
					 var sHtml = "";
					 sHtml = "<tr > "+
									   "  <td class='bootstrap-timepicker'>"+
										"	 &nbsp; <input type=text name='ptp_2[]'  id='ptp_2' class='inpubase sm1' >&nbsp;"+
										"	  <button type='button' class='btn btn-danger btn-xs delBtn'>삭제</button>"+
										"  </td>"+
										"</tr><br /> ";
					 tbl.append(sHtml);
					
					$("input[name='ptp_2[]']").timepicker({
						defaultTime: 'current',
						minuteStep: 1,
						disableFocus: true,
						template: 'dropdown'
				     });
					
				});
				$('#sTime').on('click', '.delBtn', function() {
					
					var par = $(this).parent().parent(); //tr
					
					par.remove();
					

				});
	     });

		
		paran_timepicker = {
			init: function() {
				
				$('#ptp_2').timepicker({
					defaultTime: 'current',
					minuteStep: 1,
					disableFocus: true,
					template: 'dropdown'
				});
				
				
			}
		};
		CKEDITOR.replace( 's_map' );
	</script>

    </body>
</html>

      
      