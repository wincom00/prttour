
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
    if ($mode == "save") {
		  $issue_cruise = (isset($issue_cruise) && $issue_cruise == "YES") ? "YES" : "NO";
		  if ($seq_no == "") {
		        $keychars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
				$length = 8;

				// RANDOM KEY GENERATOR
				$randkey = "";
				$max=strlen($keychars)-1;
				for ($i=0;$i<=$length;$i++) {
				  $randkey .= substr($keychars, rand(0, $max), 1);
				}

				$qry1 = "insert into member_list (division,
																ruserid,
																userid,
																passwd,
																kor_name,
																eng_name,
																company_type,
																company_division,
																company_homepage,
																zipcode,
																address,
																city,
																state,
																country,
																company_boss,
																company_manager,
																company_phone,
																company_fax,
																company_email,
																company_area,
																issue_airline,
																issue_cruise,
																balance_alert,
																tax_id ,
																bank_info ,
																ata_arc ,
																build_date,
																employee_ch,
																cc_type,
															    a_color,
															    pos,
																set_acc ,
																set_pro,
																agent_rate,
																fee_type) values ('comp',           '$ruserid',
																							'$userid',
																							'$randkey',
																							'$kor_name',
																							'$eng_name',
																							'$company_type',
																							'$company_division',
																							'$company_homepage',
																							'$zipcode',
																							'$address',
																							'$city',
																							'$state',
																							'$country',
																							'$company_boss',
																							'$company_manager',
																							'$company_phone',
																							'$company_fax',
																							'$company_email',
																							'$company_area',
																							'$issue_airline',
																							'$issue_cruise',
																							'$balance_alert',
																							'$tax_id',
																							'$bank_info',
																							'$ata_arc',
																							'$build_date',
																							'$employee_ch',
																							'$chkcc',
																							'$a_color',
																							'$pos',
																							'$set_a',
																							'$set_pro',
																							'$agent_rate',
																							'$feetype')";

				//PRINT_R($qry1);
				 $rst1 = mysql_query($qry1,$dbConn);
				 
				 $goUrl_1 = "base_agent.php?division=$division&pdx=$pdx&sub=$sub";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		  } else {

			     $qry1 = "update member_list set ruserid = '$ruserid', passwd='$passwd',
															kor_name = '".mysql_real_escape_string($kor_name)."',
															eng_name = '".mysql_real_escape_string($eng_name)."',
															company_type = '$company_type',
															company_division = '$company_division',
															company_homepage = '$company_homepage',
															zipcode = '$zipcode',
															address = '$address',
															city = '$city',
															state = '$state',
															country = '$country',
															company_boss = '$company_boss',
															company_manager = '$company_manager',
															company_phone = '$company_phone',
															company_fax = '$company_fax',
															company_email = '$company_email',
															company_area = '$company_area',
															issue_airline = '$issue_airline',
															issue_cruise = '$issue_cruise',
															balance_alert = '$balance_alert',
															tax_id = '$tax_id',
															bank_info = '$bank_info',
															ata_arc = '$ata_arc',
															build_date='$build_date',
															employee_ch='$employee_ch',
															cc_type='$chkcc',
															a_color = '$a_color',
															pos = '$pos',
															set_acc = '$set_a',
															set_pro = '$set_pro',
															agent_rate ='$agent_rate',
                                                            fee_type='$feetype'
												where seq_no = '$seq_no'";
			//echo $qry1;
			//exit;
				$rst1 = mysql_query($qry1,$dbConn);
				$goUrl_1 = "base_agent.php?division=$division&pdx=$pdx&sub=$sub";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";



		  }

	} 
	$v_info = getinfo_dbMember_byid($id);


?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">기초관리</a>
					</li>
					<li>
						<a href="#">업체관리</a>
					</li>
					<li>
						업체등록
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
			            <input type=hidden name=mode value="save">
						<input type=hidden name=seq_no value="<?= $id ?>">
						
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd">이용 ID</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=ruserid  class="inpubase sm1" value="<?= $v_info['ruserid'] ?>"> </td>
										<td width=15% class="titletd" style="vertical-align: middle;">패스워드</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=password name=passwd  class="inpubase md" placeholder="자동생성" value="<?= $v_info['passwd'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">회계 ID</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=userid  class="inpubase sm1" value="<?= $v_info['userid'] ?>"> </td>
										<td width=15% class="titletd" style="vertical-align: middle;">지역선택</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<select name=company_area  class="inpubase md"><?= printBaseCode4_without('A01',$v_info['company_area']); ?></select></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">회사명(한글)</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=kor_name  class="inpubase lg" value="<?= $v_info['kor_name'] ?>"></td>
										<td width=15% class="titletd" style="vertical-align: middle;">회사명(영문)</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=eng_name   class="inpubase lg" value="<?= $v_info['eng_name'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">홈페이지</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=company_homepage  class="inpubase lg" value="<?= $v_info['company_homepage'] ?>"></td>
										<td width=15% class="titletd" style="vertical-align: middle;">이메일</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=company_email   class="inpubase md" value="<?= $v_info['company_email'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">전화번호</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=company_phone  class="inpubase md" value="<?= $v_info['company_phone'] ?>"></td>
										<td width=15% class="titletd" style="vertical-align: middle;">팩스</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=company_fax   class="inpubase md" value="<?= $v_info['company_fax'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">대표자명</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=company_boss  class="inpubase sm1" value="<?= $v_info['company_boss'] ?>"></td>
										<td width=15% class="titletd" style="vertical-align: middle;">담당자명</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=company_manager   class="inpubase sm1" value="<?= $v_info['company_manager'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">주소</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=address  class="inpubase lg" value="<?= $v_info['address'] ?>"></td>
										<td width=15% class="titletd" style="vertical-align: middle;">도시</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=city   class="inpubase sm1" value="<?= $v_info['city'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">주</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=state  class="inpubase sm1" value="<?= $v_info['state'] ?>"></td>
										<td width=15% class="titletd" style="vertical-align: middle;">우편번호</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=zipcode  size=7 class="inpubase sm1" value="<?= $v_info['zipcode'] ?>"> &nbsp;<input type=radio name=country value="CAN" <?php if($v_info['country'] == "CAN") echo "checked"; ?>> CAN&nbsp;<input type=radio name=country value="USA" <?php if($v_info['country'] == "USA") echo "checked"; ?>> USA&nbsp;<input type=radio name=country value="KOR" <?php if($v_info['country'] == "KOR") echo "checked"; ?>> KOR </td>
									</tr>
									
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">Tax ID</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=tax_id  class="inpubase md" value="<?= $v_info['tax_id'] ?>"></td>
										<td width=15% class="titletd" style="vertical-align: middle;">은행정보</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=bank_info  size=40  class="inpubase md" value="<?= $v_info['bank_info'] ?>">  </td>
									</tr> 
									<!--
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">담당직원정보</td>
										<td width=35% bgcolor=#FFFFFF colspan=3>&nbsp;<input type=text name=employee_ch size=60 class="inpubase md" value="<?= $v_info['employee_ch'] ?>">
											 &nbsp;&nbsp;&nbsp;<input type=checkbox name=chkcc id=chkcc <?php if ($v_info['cc_type'] == "C") { ?> checked <?php } ?> > &nbsp;거래처결제여부</td>
										 </td>
									</tr>
									-->
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">지역컬러배경선택</td>
										<td bgcolor=#FFFFFF colspan=3>&nbsp;HEX# : <input type=text name=a_color size=10 class="inpubase sm1" value="<?= $v_info['a_color'] ?>"> (정산현황을 위한 배경칼라색 - <A HREF='https://htmlcolorcodes.com/' target=_blank><U>[칼라 차트 보기]</U></A>)</td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">셋틀 위치선정</td>
										<td bgcolor=#FFFFFF >&nbsp;위치&nbsp;&nbsp; : <input type=text name=pos size=10 class="inpubase sm1" value="<?= $v_info['pos'] ?>"> </td>
										<td width=15% class="titletd" style="vertical-align: middle;">회계 노출여부</td>
										<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=checkbox class="bs-switch"  data-size="mini" name=set_a id=set_a <?php if ($v_info['set_acc'] == "C") { ?> checked <?php } ?> value="C">  </td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">상품소유사지정여부</td>
										<td width=35% bgcolor=#FFFFFF >&nbsp;<input type=checkbox class="bs-switch"  data-size="mini" name=set_pro id=set_pro <?php if ($v_info['set_pro'] == "C") { ?> checked <?php } ?> value="C">  </td>

										<td width=15% class="titletd" style="vertical-align: middle;">발권처여부</td>
										<td width=35% bgcolor=#FFFFFF >&nbsp;<input type=checkbox class="bs-switch"  data-size="mini" name=issue_airline id=issue_airline <?php if ($v_info['issue_airline'] == "YES") { ?> checked <?php } ?> value="YES">  </td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">크루즈업체여부</td>
										<td width=35% bgcolor=#FFFFFF >&nbsp;<input type=checkbox class="bs-switch"  data-size="mini" name=issue_cruise id=issue_cruise <?php if ($v_info['issue_cruise'] == "YES") { ?> checked <?php } ?> value="YES">  </td>
										<td colspan=2 bgcolor=#FFFFFF>&nbsp;</td>
									</tr>
									<tr>
										<td colspan=4 height=35 bgcolor=#FFFFFF class="titletd" style="vertical-align: middle;"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
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
				// bootstrap switch
		        paran_bs_switch.init();

	     });

		 // bootstrap switch
		paran_bs_switch = {
			init: function() {
				if($('.bs-switch').length) {
					$('.bs-switch').bootstrapSwitch();
				}
			}
		};
	 </script>

    </body>
</html>

      
      
