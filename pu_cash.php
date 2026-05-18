<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
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
	
    if ($mode == "save") {
		 
			$qry1 = "insert into pcash_hist 
										(
										userid, 
										gr_code, 
										r_code, 
										p_date, 
										p_cont, 
										p_cash, 
										p_yn, 
										m_usser, 
										wdate
										)
										values
										(
										'$email', 
										'$grnum', 
										'$rnum', 
										now(), 
										'$r_cont', 
										'$r_amt', 
										'n', 
										'{$user_dbinfo['userid']}', 
										now()
										);
									";

			$rst1 = mysql_query($qry1);

			

			$goUrl_1 = "pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id=$no";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;


		  

	} else if ($mode == "usave") {
		 
			$qry1 = "insert into pcash_hist 
										(
										userid, 
										gr_code, 
										r_code, 
										p_date, 
										p_cont, 
										p_cash, 
										p_yn, 
										m_usser, 
										wdate
										)
										values
										(
										'$email', 
										'$grnum', 
										'$rnum', 
										now(), 
										'$r_cont', 
										'$r_amt', 
										'e', 
										'{$user_dbinfo['userid']}', 
										now()
										);
									";

			$rst1 = mysql_query($qry1);

			

			$goUrl_1 = "pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id=$no";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;


		  

	} else if ($mode == "rpay") {
			$rseq = explode("/",$rid);

			$qry1 = "update pcash_hist 
								set
								
								p_yn = 'y' , 
								m_usser = '{$user_dbinfo['userid']}' 
								where
								seq_no = '$rseq[0]' ;
							";

			$rst1 = mysql_query($qry1);

			$qry1 = "update reserve_info 
								set
								
								rp_cash = '$rseq[3]'
							
								where
								reserveCode = '$rseq[2]' ;
							";

			$rst1 = mysql_query($qry1);
			$qry1 = "select balance from member_list where seq_no = '$no'";
			$rst1 = mysql_query($qry1);
			$row1 = mysql_fetch_assoc($rst1);

			$totbal= $row1['balance']+$rseq[3];

			$qry1 = "update member_list 
								set
								
								balance = '$totbal'
							
								where
								seq_no = '$no' ;
							";

			$rst1 = mysql_query($qry1);

			$goUrl_1 = "pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id=$no";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;



	} else if ($mode == "rrpay") {
			$rseq = explode("/",$rid);

			$qry1 = "update pcash_hist 
								set
								
								p_yn = 'n' , 
								m_usser = '{$user_dbinfo['userid']}' 
								where
								seq_no = '$rseq[0]' ;
							";

			$rst1 = mysql_query($qry1);

			$qry1 = "update reserve_info 
								set
								
								rp_cash = '0.00'
							
								where
								reserveCode = '$rseq[2]' ;
							";

			$rst1 = mysql_query($qry1);
			$qry1 = "select balance from member_list where seq_no = '$no'";
			$rst1 = mysql_query($qry1);
			$row1 = mysql_fetch_assoc($rst1);

			$totbal= $row1['balance']-$rseq[3];

			$qry1 = "update member_list 
								set
								
								balance = '$totbal'
							
								where
								seq_no = '$no' ;
							";

			$rst1 = mysql_query($qry1);

			$goUrl_1 = "pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id=$no";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;



	}  else if ($mode == "urpay") {
			$rseq = explode("/",$rid);

			$qry1 = "update pcash_hist 
								set
								
								p_yn = 'u' , 
								m_usser = '{$user_dbinfo['userid']}' 
								where
								seq_no = '$rseq[0]' ;
							";

			$rst1 = mysql_query($qry1);

			
			$qry1 = "select balance from member_list where seq_no = '$no'";
			$rst1 = mysql_query($qry1);
			$row1 = mysql_fetch_assoc($rst1);

			$totbal= $row1['balance']-$rseq[3];

			$qry1 = "update member_list 
								set
								
								balance = '$totbal'
							
								where
								seq_no = '$no' ;
							";

			$rst1 = mysql_query($qry1);

			$goUrl_1 = "pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id=$no";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;



	} else if ($mode == "urrpay") {
			$rseq = explode("/",$rid);

			$qry1 = "update pcash_hist 
								set
								
								p_yn = 'e' , 
								m_usser = '{$user_dbinfo['userid']}' 
								where
								seq_no = '$rseq[0]' ;
							";

			$rst1 = mysql_query($qry1);

			
			$qry1 = "select balance from member_list where seq_no = '$no'";
			$rst1 = mysql_query($qry1);
			$row1 = mysql_fetch_assoc($rst1);

			$totbal= $row1['balance']+$rseq[3];

			$qry1 = "update member_list 
								set
								
								balance = '$totbal'
							
								where
								seq_no = '$no' ;
							";

			$rst1 = mysql_query($qry1);

			$goUrl_1 = "pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id=$no";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;



	} else if ($mode == "delpay") {
			$rseq = $rid;

			$qry1 = "delete from  pcash_hist 
								where
								seq_no = '$rseq' ;
							";

			$rst1 = mysql_query($qry1);

			
			
			$goUrl_1 = "pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id=$no";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;



	}
	$v_info = getinfo_dbMember_byid($id);
   
	$qry1 = "select * from member_list where seq_no = '$id'";
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
						<a href="#">고객관리</a>
					</li>
					<li>
						<a href="#">고객정보</a>
					</li>
					
					<li>
						<a href="client_list.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">고객정보리스트</a>
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="frmemp" id="frmemp" method="post" >
			           	  <input type=hidden name=mode id='mode' value="save">
						  <input type=hidden name=no id='no' value="<?= $id ?>">
						  <input type=hidden name=rid id='rid' value="">
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							        <tr>
										<td colspan=4 bgcolor=#F9F9F9 height=25>&nbsp;기본정보</td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>한글이름</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=kor_name size=30 class='inpubase md' value="<?= $v_info['kor_name'] ?>"></td>
										<td width=15% align=center>영문이름</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=eng_name size=30 class='inpubase md' value="<?= $v_info['eng_name'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>이메일</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=email size=40 class='inpubase md' value="<?= $v_info['email'] ?>"></td>
										<td width=15% align=center>패스워드</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=passwd size=30 class='inpubase md' value="<?= $v_info['passwd'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>전화번호</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=phone id='mask_phone' size=30 class='inpubase md' value="<?= $v_info['phone'] ?>"></td>
										<td width=15% align=center>푸른포인트</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=balance size=30 class='inpubase md' value="<?= $v_info['balance'] ?>"></td>
										
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>생년월일</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=birthday id="mask_date1" class='inpubase md' value="<?= $v_info['birthday'] ?>">&nbsp;</td>
										<td width=15% align=center>성별</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type="radio" name="sex" value = "M" <?php if($v_info['sex'] == "M") echo "checked"; ?>> 남 &nbsp;&nbsp;
										  <input type="radio" name="sex" value = "F" <?php if($v_info['sex'] == "F") echo "checked"; ?>> 여</td>
									</tr>
									
									
									
									<!--
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>소속부서</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<select name='c_part1' class='inpubase md'><?=printBaseCode2_without('D01','',$v_info['c_part1'])?></select><br />&nbsp;<input type=text name=c_part size=15 class='inpubase md' value="<?= $v_info['c_part'] ?>">&nbsp;</td>
										<td width=15% align=center>사진</td>
										<td width=35% align=left bgcolor=#FFFFFF style="vertical-align : middle;">&nbsp;<?php if($v_info['userfile1']): ?><IMG SRC="<?= UPLOAD_URL ?><?= $v_info['userfile1'] ?>" width=120><?php endif; ?><input type=file name=userfile1 size=30 class='inpubase md' value="<?= $v_info['userfile1'] ?>"></td>
									</tr>
									-->
									
									
									
							</tbody>
						</table>
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							        <tr>
										<td colspan=8 bgcolor=#F9F9F9 height=25>&nbsp;푸른포인트적립/사용 추가</td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>

									    <td width=7% align=center>통합예약번호</td>
										<td width=20% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=grnum size=15 class='inpubase md' value='관리자입력'>
										<td width=5% align=center>예약번호</td>
										<td width=20% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=rnum size=15 class='inpubase md' value='관리자입력'>
										<td width=5% align=center>적립/사용내용</td>
										<td width=20% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=r_cont size=15 class='inpubase md' value=''></td>
										<td width=5% align=center>적립/사용포인트</td>
										<td width=18% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=r_amt size=15 class='inpubase md' value=""></td>
									</tr>
									
									<tr>
										<td colspan=8 height=35 bgcolor=#FFFFFF align=center><input type=submit value="푸른포인트 추가" class="btn btn-primary btn-sm addpa"> | <input type=button value="푸른포인트 사용" class="btn btn-primary btn-sm usepa">
										</td>
									</tr>	
									
									
							</tbody>
						</table>
						<br />
						<table class="table table-striped table-bordered table-condensed">
						    <thead>
                                <tr>
                                    <th scope="col" class="table_info text-center">통합예약번호</th>
									<th scope="col" class="table_info text-center">예약번호</th>
                                    <th scope="col" class="table_info text-center">적립일</th>
                                    <th scope="col" class="table_info text-center">적립/사용내용</th>
									<th scope="col" class="table_info text-center">적립/사용포인트</th>
									<th scope="col" class="table_info text-center">적립/사용여부</th>
                                </tr>
                            </thead>
                            <tbody id="loop_area1">
                                <?php
									$qry1 = "SELECT * FROM pcash_hist WHERE userid = '".$v_info['email']."' order by wdate asc";
									//echo $qry1;				
									$rst1 = mysql_query($qry1);
									while($reserve = mysql_fetch_assoc($rst1)){
                                    
                                        $paran_cash_amt = $reserve['p_cash'];
                                        if ($reserve['p_yn']=="y") {
											$cont = "적립완료 | <button type='button' class='rbtnrp' value='{$reserve['seq_no']}/{$reserve['gr_code']}/{$reserve['r_code']}/$paran_cash_amt'>지급환불</button>";
										} else if ($reserve['p_yn']=="e") {
											

											$cont = "<button type='button' class='ubtnrp' value='{$reserve['seq_no']}/{$v_info['gr_code']}/{$reserve['r_code']}/$paran_cash_amt'><font color=red>사용하기</font></button>";

											
										} else if ($reserve['p_yn']=="u") {
											$cont = "<font color=red>사용완료</font> | <button type='button' class='urbtnrp' value='{$reserve['seq_no']}/{$reserve['gr_code']}/{$reserve['r_code']}/$paran_cash_amt'>사용취소</button>";
										} else {
											$cont = "<button type='button' class='btnrp' value='{$reserve['seq_no']}/{$v_info['gr_code']}/{$reserve['r_code']}/$paran_cash_amt'>지급하기</button> | <button type='button' class='btndel' value='{$reserve['seq_no']}'>삭제하기</button> ";
										}
                                        $grandRevNo = $reserve['gr_code'];
										$sreserveCode = $reserve['r_code'];
										$rev_date = $reserve['p_date'];
                                        $reg_date = substr($reserve['wdate'],0,10);
                                       
                                ?>
                                <tr>
									<td class="table_info text-center"><a href="#"  ><?=$grandRevNo?></a></td>
                                    <td class="table_info text-center"><a href="#" ><?=$sreserveCode?></a></td>
                                    <td class="table_info text-center"><?=$rev_date?></td>
                                    <td class="table_info text-left" style="white-space:normal;">
                                     <?=$reserve['p_cont']?></td>
									<td class="table_info text-center">P <?=$paran_cash_amt?></td>
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
					paran_mask_input.init();
					$(document).on('click', '.usepa', function(){ 
						 if (confirm("사용 추가하시겠습니까?"))
						 {
							 $("#mode").val("usave");
						
							 $("#rid").val($(this).val());
							  
							 $("#frmemp").submit();
						 } else {
							 return;
						 }
						 
						 
					});
					$(document).on('click', '.btnrp', function(){ 
						 if (confirm("지급하시겠습니까?"))
						 {
							 $("#mode").val("rpay");
						
							 $("#rid").val($(this).val());
							  
							 $("#frmemp").submit();
						 } else {
							 return;
						 }
						 
						 
					});
					$(document).on('click', '.rbtnrp', function(){ 
						 if (confirm("지급환불 하시겠습니까?"))
						 {
							 $("#mode").val("rrpay");
						
							 $("#rid").val($(this).val());
							  
							 $("#frmemp").submit();
						 } else {
							 return;
						 }
						 
						 
					});


					$(document).on('click', '.ubtnrp', function(){ 
						 if (confirm("사용 하시겠습니까?"))
						 {
							 $("#mode").val("urpay");
						
							 $("#rid").val($(this).val());
							  
							 $("#frmemp").submit();
						 } else {
							 return;
						 }
						 
						 
					});
					$(document).on('click', '.urbtnrp', function(){ 
						 if (confirm("사용환불 하시겠습니까?"))
						 {
							 $("#mode").val("urrpay");
						
							 $("#rid").val($(this).val());
							  
							 $("#frmemp").submit();
						 } else {
							 return;
						 }
						 
						 
					});
					$(document).on('click', '.btndel', function(){ 
						 if (confirm("삭제 하시겠습니까?"))
						 {
							 $("#mode").val("delpay");
						
							 $("#rid").val($(this).val());
							// alert($("#rid").val());
							 $("#frmemp").submit();
						 } else {
							 return;
						 }
						 
						 
					});
	        });

		
			//* masked input
			paran_mask_input = {
				init: function() {
					$("#mask_date").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_phone").inputmask("(999) 999-9999");
					$("#mask_phone1").inputmask("(999) 999-9999");
					$("#mask_phone2").inputmask("(999) 999-9999");
					$("#mask_ssn").inputmask("999-999-999");
					$("#mask_date1").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_date2").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_date3").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#mask_date4").inputmask("9999-99-99",{placeholder:"____-__-__"});
					$("#sindate").inputmask("9999-99-99",{placeholder:"____-__-__"});
				}
			};
              
 
			 
</script>

	</script>
</html>

      
      