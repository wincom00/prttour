<?php
    include "include/header.php";
	
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
		 if ($no=="") {

				$qry1 = "insert into member_list (division,
											userid,
											passwd,
											level,
											kor_name,
											eng_name,
											email,
											birthday,
											sex,
											phone,
											cell_phone,
											balance,
											wdate
											) values ('web-member',
																	'$userid',
																	'$passwd',
																	'10',
																	'$kor_name',
																	'$eng_name',
																	'$email',
																	'$birthday',
																	'$sex',
																	'$phone',
																	'$cell_phone',
																	'$balance',
																	now())";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);

				if($rst1)
				{
					$goUrl_1 = "client_list.php?division=$division&pdx=$pdx&sub=$sub";
					 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					 exit;
				}
		 } else {
			    $qry1 = "update member_list set passwd = '$passwd',
											kor_name = '$kor_name',
											eng_name = '$eng_name',
											email = '$email',
											company_code = '$company_code',
											birthday = '$birthday',
											sex = '$sex',
											cell_phone = '$cell_phone',
											phone = '$phone',
											access_level = '$new_access_level',
											c_part1 = '$c_part1',
											deny = '$deny',
											balance = '$balance'
											
											where seq_no = '$no'";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);
				$goUrl_1 = "client_list.php?division=$division&pdx=$pdx&sub=$sub";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
                 exit;

		 }
		  

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
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="frmemp" id="frmemp" method="post" onSubmit="return chk(this)">
			           	  <input type=hidden name=mode value="save">
						  <input type=hidden name=no value="<?= $id ?>">
						  <input type=hidden name=division value="<?= $division ?>">
						  <input type=hidden name=userfile1_tmp value="<?= $v_info['userfile1'] ?>">
						
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
										<td width=15% align=center>푸른머니</td>
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
									
									<tr>
										<td colspan=4 height=35 bgcolor=#FFFFFF align=center><input type=submit value="정보저장" class="btn btn-primary btn-sm"></td>
									</tr>	
									
									
							</tbody>
						</table>
						<br />
						<table class="table table-striped table-bordered table-condensed">
						    <thead>
                                <tr>
                                    <th scope="col" class="table_info text-center">통합예약번호</th>
									<th scope="col" class="table_info text-center">예약번호</th>
                                    <th scope="col" class="table_info text-center">예약일</th>
                                    <th scope="col" class="table_info text-center">여행명</th>
                                </tr>
                            </thead>
                            <tbody id="loop_area1">
                                <?php
									$qry1 = "SELECT reserveCode,revDate,grand_revNo,wdate,p_name,p_code,c_progress, 0 as paran_cash_amt, 0 as discount_amt FROM reserve_info WHERE book_email = '".$v_info['email']."' AND parent ='MAIN' order by wdate asc";
													
									$rst1 = mysql_query($qry1);
									while($reserve = mysql_fetch_assoc($rst1)){
                                    
                                        $paran_cash_amt = $reserve['paran_cash_amt'];
                                        $discount_amt = $reserve['discount_amt'];
                                        $grandRevNo = $reserve['grand_revNo'];
										$sreserveCode = $reserve['reserveCode'];
                                        $reg_date = substr($reserve['wdate'],0,10);
                                        $rev_date = substr($reserve['revDate'],0,10);
                                ?>
                                <tr>
									<td class="table_info text-center"><a href="#"  ><?=$grandRevNo?></a></td>
                                    <td class="table_info text-center"><a href="#" ><?=$sreserveCode?></a></td>
                                    <td class="table_info text-center"><?=$rev_date?></td>
                                    <td class="table_info text-left" style="white-space:normal;">
                                      <a href="#" ><?=$reserve['p_name']?></a></td>
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
              function chk(tf){
					
					  if(!tf.kor_name.value)
					  {
							alert('고객성명(한글)을 입력하세요!');
							tf.kor_name.focus();
							return false;
					  }			 
					  	
					  if(!tf.email.value)
					  {
							alert('이메일을 입력하세요!');
							tf.userid.focus();
							return false;
					  }	
					  if(!tf.passwd.value)
					  {
							alert('패스워드를 입력하세요!');
							tf.passwd.focus();
							return false;
					  }	
					  selectAll(document.frmemp.elements['menu2[]']);
					  return true;
				}

			
 
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

      
      