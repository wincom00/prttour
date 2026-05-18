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
		 
		  
				$qry1 = "INSERT INTO emp_vacation (
													  
													  user_id,
													  v_type,
													  v_sdate,
													  v_edate,
													  r_vcnt,
													  r_date,
													  r_status,
													  r_memo,
													  wdate
													)
													VALUES
													  (
														
														'$userid',
														'$types',
														'$v_date3',
														'$v_date4',
														'$s_vcnt',
														'',
														'신청중',
														'$rmemo',
														now()
													  );";    
		        
              
                ///cho $qry1;
				//exit;
				$rst1 = mysql_query($qry1);

				if($rst1)
				{
					 $goUrl_1 = "emp_vm.php?division=$division&pdx=$pdx&sub=$sub";
					 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					 exit;
				}
				 
				 
		  
	}
	if ($mode == "rcan") {
			$rseq = $rid;

			$qry1 = "update emp_vacation set r_status='취소'
								where
								seq_no = '$rseq' ;
							";

			$rst1 = mysql_query($qry1);

			
			
			$goUrl_1 = "emp_vm.php?division=$division&pdx=$pdx&sub=$sub";
			 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;



	}

	if ($mode == "req") {
			$rseq = $rid;

			$qry1 = "update emp_vacation set r_status='신청중',wdate=now(),r_date=now()
								where
								seq_no = '$rseq' ;
							";

			$rst1 = mysql_query($qry1);

			
			
			$goUrl_1 = "emp_vm.php?division=$division&pdx=$pdx&sub=$sub";
			 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;



	}
	if ($mode == "rdel") {
			$rseq = $rid;

			$qry1 = "delete from  emp_vacation 
								where
								seq_no = '$rseq' ;
							";


			$rst1 = mysql_query($qry1);

			
			
			$goUrl_1 = "emp_vm.php?division=$division&pdx=$pdx&sub=$sub";
			 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;



	}
	$v_info = getinfo_dbMember($user_dbinfo['userid']);
   
	$qry1 = "select * from member_list where seq_no = '{$user_dbinfo['userid']}'";
	$rst1 = mysql_query($qry1);
	$row1 = mysql_fetch_assoc($rst1);


	
?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">인사관리</a>
					</li>
					<li>
						<a href="emp_vm.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">직원휴가관리</a>
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="frmemp" id="frmemp" method="post" >
			           	  <input type=hidden name=mode id=mode value="save">
						 
						  <input type=hidden name=division value="<?= $division ?>">
						  <input type=hidden name=userid value="<?= $v_info['userid']?>">
						  <input type=hidden name=rid id='rid' value="">
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							        <tr>
										<td colspan=4 bgcolor=#F9F9F9 height=25>&nbsp;<b>기본정보</b></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>부서/직급</td>
										<td width=35% align=left bgcolor=#FFFFFF colspan="3">&nbsp;<select name='area_comp' class='inpubase md'><?=printBaseCode_first('D02',$v_info['area_comp'])?></select>&nbsp;<select name='c_part1' class='inpubase md'><?=printBaseCode_first('D01',$v_info['c_part1'])?></select>&nbsp;<input type=text name=c_part size=15 class='inpubase md' value="<?= $v_info['c_part'] ?>">&nbsp;</td>
										

									</tr>
								    <tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>이름(한글)</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=kor_name size=30 class='inpubase md' value="<?= $v_info['kor_name'] ?>"></td>
										<td width=15% align=center>이름(영문)</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=eng_name size=30 class='inpubase md' value="<?= $v_info['eng_name'] ?>"></td>
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>휴대폰</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=cell_phone id='mask_phone' size=30 class='inpubase md' value="<?= $v_info['cell_phone'] ?>"></td>
										<td width=15% align=center>일반전화</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=phone size=30 id='mask_phone1'  class='inpubase md' value="<?= $v_info['phone'] ?>"></td>
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>비상연락처</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=reference id='mask_phone2'  class='inpubase md' value="<?= $v_info['reference'] ?>"></td>
										<td width=15% align=center>입사일</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=join_date id="mask_date" class='inpubase md' value="<?= $v_info['join_date'] ?>"></td>
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>휴가기간</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=v_date1 id='mask_date2'  class='inpubase md' value="<?= $v_info['v_date1'] ?>">&nbsp;~&nbsp;<input type=text name=v_date2 id='mask_date3'  class='inpubase md' value="<?= $v_info['v_date2'] ?>"></td>
										<td width=15% align=center>휴가총일수</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=tot_vdate class='inpubase md' value="<?= $v_info['tot_vdate'] ?>"></td>
									</tr>
									<tr >
										<td width=15% align=center>휴가잔여일수</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=r_vdate class='inpubase md' value="<?= $v_info['r_vdate'] ?>">일</td>
										<td width=15% align=center>미리사용휴가일수</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=use_vdate class='inpubase md' value="<?= $v_info['use_vdate'] ?>">일</td>
									</tr>
									<tr >
										<td width=15% align=center>병가총일수</td>
										<td width=35% align=left bgcolor=#FFFFFF colspan=3>&nbsp;<input type=text name=tot_sdate class='inpubase md' value="<?= $v_info['tot_sdate'] ?>"></td>
									</tr>
									<tr >
										<td width=15% align=center>병가잔여일수</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=r_sdate class='inpubase md' value="<?= $v_info['r_sdate'] ?>">일</td>
										<td width=15% align=center>미리사용병가일수</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=use_sdate class='inpubase md' value="<?= $v_info['use_sdate'] ?>">일</td>
									</tr>
									
									
									
							</tbody>
						</table>
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							        <tr>
										<td colspan=4 bgcolor=#F9F9F9 height=25>&nbsp;<b>신청사항</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="types" id="types1" value="V">휴가신청 &nbsp;<input type="radio" name="types" id="types2" value="S">병가신청&nbsp;<input type="radio" name="types" id="types3" value="O">무급휴가신청</td>
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>휴가신청기간</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=v_date3 id='mask_date4'  class='inpubase md' value="">&nbsp;~&nbsp;<input type=text name=v_date4 id='mask_date5'  class='inpubase md' value=""></td>
										<td width=15% align=center>휴가신청일수</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=s_vcnt class='inpubase md' value=""></td>
									</tr>
									<tr>
										<td colspan=4 bgcolor=#F9F9F9 height=25 >&nbsp;<b>신청사항사유</b></td>
									</tr>
									<tr>
										<td colspan=4 bgcolor=#F9F9F9 height=25 ><textarea class="form-control" name="rmemo" rows=10></textarea></td>
									</tr>
									
									
									
							</tbody>
						</table>
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							       
									
									<tr>
										<td colspan=3 height=35 bgcolor=#FFFFFF align=center><input type=submit value="휴가신청저장" class="btn btn-primary btn-sm"></td>
									</tr>
									
							</tbody>
						</table>
						<table class="table table-striped table-bordered table-condensed">
						    <thead>
                                <tr>
                                    <th scope="col" class="table_info text-center">사용자ID</th>
									<th scope="col" class="table_info text-center">직원명</th>
                                    <th scope="col" class="table_info text-center">신청일</th>
									<th scope="col" class="table_info text-center">신청타입</th>
                                    <th scope="col" class="table_info text-center">신청기간</th>
									<th scope="col" class="table_info text-center">신청사유</th>
									<th scope="col" class="table_info text-center">신청상태</th>
									<th scope="col" class="table_info text-center">취소/삭제</th>
                                </tr>
                            </thead>
                            <tbody id="loop_area1">
                                <?php
									$qry1 = "SELECT * FROM emp_vacation WHERE user_id = '".$v_info['userid']."' order by wdate desc";
									//echo $qry1;				
									$rst1 = mysql_query($qry1);
									while($request = mysql_fetch_assoc($rst1)){
                                    
                                      
                                        if ($request['r_status']=="신청중") {
											$cont = "<button type='button' class='btnrp' value='{$request['seq_no']}'>취소하기</button> | <button type='button' class='btndel' value='{$request['seq_no']}'>삭제하기</button> ";
										} else if ($request['r_status']=="취소") {
											$cont = "<button type='button' class='btnrr' value='{$request['seq_no']}'>신청하기</button> | <button type='button' class='btndel' value='{$request['seq_no']}'>삭제하기</button> ";
										} else if ($request['r_status']=="승인완료") {
											$cont = "<button type='button' class='btnrr' value='{$request['seq_no']}' disabled >취소하기</button> | <button type='button' class='btndel' value='{$request['seq_no']}' disabled>삭제하기</button> ";
										} 
                                        if ($request['v_type']== "V") {
											$cap = "휴가신청";
										
                                        } else if ($request['v_type']== "S") {
											$cap = "병가신청";
										
										} else if ($request['v_type']== "O") {
											$cap = "무급휴가신청";
											
										}
                                        $reg_date = substr($request['wdate'],0,10);
                                       
                                ?>
                                <tr>
									<td class="table_info text-center"><a href="#"  ><?=$v_info['userid']?></a></td>
                                    <td class="table_info text-center"><a href="#" ><?=$v_info['kor_name']?></a></td>
                                    <td class="table_info text-center"><?=$reg_date?></td>
									<td class="table_info text-center"><?=$cap?></td>
                                    <td class="table_info text-left" style="white-space:normal;">
                                     <?=$request['v_sdate']?> ~ <?=$request['v_edate']?></td>
									<td class="table_info text-center"><?=$request['r_memo']?></td>
									<td class="table_info text-center"><?=$request['r_status']?></td>
									<td class="table_info text-center"><?=$cont?></td>
                                </tr>

                                <?php }?>

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
   

    </body>
	<script>
	         $(document).ready(function() {
		   
					$('#mask_date').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
					$('#mask_date1').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
					$('#mask_date2').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
					$('#mask_date3').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
					$('#mask_date4').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
					$('#mask_date5').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
					paran_mask_input.init();
	        });

		
			//* masked input
			paran_mask_input = {
				init: function() {
					$("#mask_date").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_phone").inputmask("(999) 999-9999");
					$("#mask_phone1").inputmask("(999) 999-9999");
					$("#mask_phone2").inputmask("(999) 999-9999");
					$("#mask_ssn").inputmask("999-99-9999");
					$("#mask_date1").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_date2").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_date3").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_date4").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_date5").inputmask("9999-99-99",{placeholder:"____-__-__"});
				}
			};
             
			 $(document).on('click', '.btnrp', function(){ 
				 if (confirm("취소하시겠습니까?"))
				 {
					 $("#mode").val("rcan");
				
					 $("#rid").val($(this).val());
					  
					 $("#frmemp").submit();
				 } else {
					 return;
				 }
						 
						 
			});

			$(document).on('click', '.btnrr', function(){ 
				 if (confirm("신청하시겠습니까?"))
				 {
					 $("#mode").val("req");
				
					 $("#rid").val($(this).val());
					  
					 $("#frmemp").submit();
				 } else {
					 return;
				 }
						 
						 
			});
			$(document).on('click', '.btndel', function(){ 
				 if (confirm("삭제 하시겠습니까?"))
				 {
					 $("#mode").val("rdel");
				
					 $("#rid").val($(this).val());
					 //alert($("#rid").val());
					 $("#frmemp").submit();
				 } else {
					 return;
				 }
						 
						 
			});
			 function move(fbox, tbox) {
					for(i=0; i<fbox.options.length; i++) 
				{ 
					if(fbox.options['i'].selected) 
					{ 
						tbox.options[tbox.options.length] = new Option(fbox.options['i'].text, fbox.options['i'].value); 
						fbox.options['i'] = null; 
						i--; 
					} 
				} 

			  }
			  function selectAll(box) {
				
						for(var i=0; i<box.length; i++) {
							box.options['i'].selected = true;

						  }		
			
				
			   }
</script>

	</script>
</html>

      
      