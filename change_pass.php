
<?php
    include "include/header.php";
	
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    $mode = $_POST['mode'] ?? '';
    $passwd = $_POST['passwd'] ?? '';
    $npasswd = $_POST['npasswd'] ?? '';
    $cpasswd = $_POST['cpasswd'] ?? '';

    if($mode == "save")
	{
		if (($npasswd != "") && ($cpasswd !="")) {
			if ($cpasswd == $passwd) {

				Misc::jvAlert("이미사용된 비밀번호입니다.새로운 비밀번호를 확인하세요!!","history.back(-1);");

			} else if ($cpasswd == $npasswd) {
				$chkstr = $user_info['user_id'];
				if (sizeof(explode($chkstr, $npasswd))> 1)
				{ 
				  Misc::jvAlert("비밀번호안에 아이디가 포함되어 있습니다!! 다시하세요!!","history.back(-1);");
				  exit;
				}
				$num = strlen($npasswd);
				
				 if ($num > 6) {
				 	    $rcnt = substr_count($npasswd, $user_info['user_id']) ;
				 	    if ($rcnt > 2) {
				 	    	Misc::jvAlert("비밀번호안에 두번이상같은 문자가 반복됩니다 !!","history.back(-1);");
								exit; 
				 	    }
						if (!preg_match('/[0-9]/i',$npasswd)) {
							Misc::jvAlert("Password mismatch type 1!!","history.back(-1);");
							exit; 
						} else if (!preg_match('/[A-Za-z]/i',$npasswd)) {
							Misc::jvAlert("Password mismatch type 2!!","history.back(-1);");
							exit;
						} else {
							$qry1 = "update member_list set passwd = '$cpasswd',
																			expire_date= now() where userid = '{$user_info['user_id']}'";
			
							$rst1 = mysql_query($qry1);
			
							//echo $qry1;
							if($rst1)
							{
								
								Misc::jvAlert("비밀번호 변경완료!!","location.href= './index.php';");
								exit;
							}
						}
			  } else {
			  	Misc::jvAlert("비밀번호가 6자이상되어야합니다.!!","history.back(-1);");
				exit; 
			  }
			} else {
				
				Misc::jvAlert("비밀번호 확인이 틀렸습니다 .비밀번호를 확인하세요!!","history.back(-1);");

			}
		} else {
			Misc::jvAlert("비밀번호 항목이 비었습니다.","history.back(-1);");

		}
	}
	$qry1 = "select * from member_list where userid = '{$user_info['user_id']}'";
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
						<a href="#">비밀번호 변경</a>
					</li>
					
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>" name="frmchg" id="frmchg" method="post">
			            <input type=hidden name=mode value="save">
			            <input type=hidden name=no value="<?= $seq_no ?>">
						
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>아이디</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=text name=userid  class="inpubase md" value="<?= $row1['userid'] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>비밀번호</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=password name=passwd class="inpubase md" value="<?= $row1['passwd'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>신규 비밀번호</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=password name=npasswd class="inpubase md" value=""></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>비밀번호 확인</td>
										<td width=35% align=left bgcolor=#FFFFFF>&nbsp;<input type=password name=cpasswd class="inpubase md" value=""></td>
									</tr>
									
									<tr>
										<td colspan=2 height=35 bgcolor=#FFFFFF class="titletd" style="vertical-align: middle;"><input type="submit"  value="저장" class="btn btn-primary btn-sm"></td>
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
		   
			
	   });
	</script>


    </body>
</html>

      
      