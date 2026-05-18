<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
   /* if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }
	*/
    if ($start == "") $start = 0;
	// $total = 0;
	$scale = 200;
	$page = 0	;
	$page_total = 0;
	$page_scale = 90;
	$page_last = 1;

	if ($mode=='edit') {
		  //echo $chkvalue;
		  //echo $seq;
			if ($chkvalue == 1) {
			   $qry1 = "update prt_mlist set chk_sub = '1' where seq_no = '$seq'";
		  } else {
		  	   $qry1 = "update prt_mlist set chk_sub = '0' where seq_no = '$seq'";
		  }
		 // echo $qry1;
		 // exit;
		  	$rst1 = mysql_query($qry1);
			
	  echo "<script> alert('Complete!'); </script>";
			  
			
		
		
	} else if ($mode=='edit1') {
		  //echo $chkvalue;
		  //echo $seq;
			if ($chkvalue == 1) {
			   $qry1 = "update prt_mlist set chk_send = '1' where seq_no = '$seq'";
		  } else {
		  	   $qry1 = "update prt_mlist set chk_send = '0' where seq_no = '$seq'";
		  }
		  //echo $qry1;
		  //exit;
		  $rst1 = mysql_query($qry1);
			
	  echo "<script> alert('Complete!'); </script>";
			  
			
		
		
	} else if ($mode == "reset") {
		  //echo $chkvalue;
		  //echo $seq;
		
		 $qry1 = "update prt_mlist set chk_send = '0' ";
		 $rst1 = mysql_query($qry1);
		 echo "<script> alert('Complete!'); </script>";
	
	} else if ($mode == "del") {
		
		$qry1 = "delete from prt_mlist  where seq_no = '$seq' ";
		
		$rst1 = mysql_query($qry1);
		echo "<script> alert('Deleted!'); </script>";
	} else if ($mode == "add") {
		  $qry1 ="
							insert into prt_mlist 
								( 
								m_name, 
								mail_addr, 
								chk_sub,
								tel_num,
								wdate,
								area
								)
								values
								( 
								'".$cname."', 
								'".$emailad."', 
								'0',
								'$phonenum',
								now(),
								'$country'
								); ";
							 
										
						
		 $rst1 = mysql_query($qry1, $dbConn); 
		 
		 echo "<script> alert('Saved!'); </script>";
		 $search = $emailad;
	} else if ($mode=='edit2') {
		 
			
	  $qry1 = "update prt_mlist set m_name = '$cname',mail_addr='$emailad',area='$country' ,tel_num='$phonenum' where seq_no = '$seq'";
	  $rst1 = mysql_query($qry1);
			
	  echo "<script> alert('Complete!'); </script>";
	  
	  
	}

	
	
	
	function printContentlist() {
		
		global  $dbConn, $start, $page_total, $scale, $page, $page_scale,$search;

	
		
		
		if ($search) {
			$name_qry = "&& (mail_addr  like '%$search%')";
			$name_qry1 = "|| (m_name  like '%$search%')";
		}

		$qry1 = "SELECT * FROM prt_mlist AS a WHERE 1=1 $name_qry $name_qry1 order by wdate desc";
		///echo $qry1;
		$rst1 = mysql_query($qry1, $dbConn);
		$page_total = mysql_num_rows($rst1);
		$page_last = ceil($page_total / $scale);


//echo $qry1;
		
		$qry1 = "SELECT * FROM prt_mlist AS a WHERE 1=1 $name_qry $name_qry1 order by wdate desc LIMIT $start, $scale";
		
//echo $qry1;
		$rst1 = mysql_query($qry1, $dbConn);
		// $page_total = mysql_num_rows($rst1);

		$found = false;
		$i=1;
		while ($row1 = mysql_fetch_assoc($rst1)) {
			$found = true;
			if ($row1['chk_sub'] == 1) {
                 $checkyn ="checked";
               
			} else {

                 $checkyn ="";
               
			}
			//echo $row1[chk_send] ."tree";
			if ($row1['chk_send'] == 1) {
                 $checky1 ="checked";
               
			} else {

                 $checky1 ="";
               
			}
			
			
			echo "<tr>
				
				<td align='center'>".$i."</td>
				<td align='left'>".$row1['m_name']."</td>
				<td align='left'>".$row1['mail_addr']."</td>
				<td>".$row1['tel_num']."</td>
				<td>".$row1['area']."</td>
				<td align='center'><input type=checkbox name=chksub[] id=chksub value='".$row1['chk_sub']."' class='chk' onclick='GoCheck({$row1['seq_no']});' $checkyn></td>
				<td align='center'><input type=checkbox name=chksend[]  value='".$row1['chk_send']."' class='chk1'  onclick='GoCheck1({$row1['seq_no']});' $checky1></td>
				<td align='center'>&nbsp;&nbsp;<input type='button' value='삭제' onClick=go_del('{$row1['seq_no']}');>&nbsp;
				<input type='button' value='수정' onClick=\"go_edit3('".$row1['seq_no']."','".$row1['m_name']."','".$row1['mail_addr']."','".$row1['tel_num']."');\"></td>
			</tr>	";
			$i++;
		}
		if (!$found) echo "<tr bgcolor=#FFFFFF><td colspan='7' align='center' style='font-size:18px;'>데이터없음!!!</td></tr>";
		
	}

	function board_pageNavigation(){
      global $page_total, $page, $start, $scale, $page_scale, $page_last,$search;

      $Parameter_value = "search=$search";

      if($page_total>$scale) //검색 결과가 페이지당 출력수보다 크면
      {
      if($start+1>$scale*$page_scale)
              {
              $pre_start=$page*$scale*$page_scale-$scale;
              echo "<a href='$PHP_SELF?start=0&$Parameter_value'><img src=\"../images/icon_left_arrow2.gif\" align=\"absmiddle\" border=0></a>&nbsp;";
              echo "<a href='$PHP_SELF?start=$pre_start&$Parameter_value'><img src=\"../images/arrow_left.gif\" align=\"absmiddle\" border=0></a>&nbsp;";
              }
      for($vj=0; $vj<$page_scale; $vj++)
          {
          $ln=($page * $page_scale+$vj)*$scale;
          $vk=$page*$page_scale+$vj+1;
          
              if($ln<$page_total)
              {
                      if($ln!=$start)
                      {
                      echo "<a href='$PHP_SELF?start=$ln&$Parameter_value'> $vk </a>.</font>";
                      }
                      else
                      {
                      echo "[$vk].</font>";
                      }
              }
          }
      if($page_total>(($page+1)*$scale*$page_scale))
              {
              $n_start=($page+1)*$scale*$page_scale;
              $last_start=$page_last*$scale;
              echo "&nbsp;<a href='$PHP_SELF?start=$n_start&$Parameter_value'><img src=\"../images/arrow_right.gif\" align=\"absmiddle\" border=0></a></a>&nbsp;";
              echo "<a href='$PHP_SELF?start=$last_start&$Parameter_value'><img src=\"../images/icon_right_arrow2.gif\" align=\"absmiddle\" border=0></a>";
              }
      }
   }// pageNavigation function end
	$qry2 = "select count(*) as cnt from prt_mlist where chk_sub='0' && chk_send = 1";
		
    $rst2 = mysql_query($qry2, $dbConn);
    $row2 = mysql_fetch_assoc($rst2)
    
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">고객관리</a></li>
					<li>고객메일링리스트</li>
				</ul>
			</div>
			<form action="<?= $PHP_SELF ?>" id="form_mail" name="form_mail" method="post" Enctype="multipart/form-data">
			    <input type="hidden" name="mode" id="mode" value="<?= $mode ?>">
				<input type="hidden" name="seq" id="seq" value="">
				<input type="hidden" name="chkvalue" id="chkvalue" value="">
			    		
				<div class="row">
					<div class="col-sm-12 col-md-12">
						
							
				 
					<table id="level4" class="txt_12" width="98%" align="center" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td bgcolor="#FFFFFF" height="30"><?php if ($row2['cnt'] !="0") {?> | <font color=red>보낸이메일갯수 <?=$row2['cnt']?></font><?php } ?></td>
						</tr>
						
						<tr bgcolor="#f9f9f9" height="28">
							
							<td bgcolor="#FFFFFF">&nbsp;
							<input type="button" value="초기화" onClick="reset_form()" style="float:right">&nbsp;
							<input type="button" value=이메일주소 style="float:right;" onClick="go_search()">&nbsp;&nbsp;
							&nbsp;<input type="text" id="search" name="search" class="inpubase md" size="30" value="<?= $search ?>" style="float:right;">
							
							</td>
						</tr>
						
					
				   </table>
				   <br>
				  <table class="table table-bordered table-condensed">
					
				   <tr>
						<td bgcolor="#FFFFFF" height="30" colspan=6>&nbsp;|&nbsp;<b>이메일추소 추가</b></td>
					</tr>
					<tr>
						<td bgcolor="#FFFFFF" height="30">&nbsp;고객명</td>
						<td bgcolor="#FFFFFF" height="30">&nbsp;<input type="text" id="cname" name="cname" class="inpubase md" size="20" ></td>
						<td bgcolor="#FFFFFF" height="30">&nbsp;이메일 주소</td>
						<td bgcolor="#FFFFFF" height="30">&nbsp;<input type="text" id="emailad" name="emailad" class="inpubase md" size="20" ></td>
						<td bgcolor="#FFFFFF" height="30">&nbsp;전화번호</td>
						<td bgcolor="#FFFFFF" height="30">&nbsp;<input type="text" id="phonenum" name="phonenum" class="inpubase md" size="20" ></td>
						
					</tr>
					<tr>
					<td bgcolor="#FFFFFF" height="30">&nbsp;지역별</td>
					<td bgcolor="#FFFFFF" height="30" colspan=5>
					    <select name="country" id="country" class="inpubase md" >
					         <option value="all"> 상관없음 </option>
							 <option value="head"> 본사 </option>
							 <option value="las"> 라스베가스 </option>
							 <option value="la"> LA </option>
							 <option value="카카오">카카오</option>
					   </select>
					</td>
					
					
				</tr>
					<tr>
						<td bgcolor="#FFFFFF" height="30" colspan=6 align='center'><input type="button" value="추가이메일" onClick="go_add()" >&nbsp;<input type="button" value="수정" onClick="go_edit2()" ></td>
					</tr>
					
					
				  
					<?php if ($user_dbinfo['userid'] == "admin" || $user_dbinfo['userid'] == "suhyunhwang") { ?>
					<tr>
						<td bgcolor="#FFFFFF" height="30" colspan=6 align='center'>
							<input type="button" value="보낸 이메일 초기화" onClick="go_reset()">&nbsp;<input type="button" value="Export CSV" onClick="go_csv()">
						</td>
					</tr>
					<?php } ?>
					
				  </table>
						

						<br />
						<div class="row">
							<div class="col-sm-12">
								<table name="ctable" id="ctable"  class="table table-striped table-bordered table-hover table-condensed js-productTable1">
									<thead>
										<tr>
											
											<th>번호</th>
											<th>이름</th>
											<th>이메일주소</th>
											<th>전화번호</th>
											<th>지역</th>
											<th>메일수신거부</th>
											<th>보낸메일확인</th>
											<th>삭제</th>
										</tr>
									</thead>
									<tbody>
									<?php 
									
										 echo printContentlist();
												
									   
									?>
									<tr>
									  &nbsp;&nbsp;&nbsp; <?php board_pageNavigation(); ?>
									</tr>
									</tbody>
								</table>
							</div>

						</div>
					</div><!-- -->
					
					
				</div> 
			</form>
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
            
            pt.initReservationDetail()
			var dateToday = new Date()
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});
            
			pt.initReservationList()
			/*
			$('#selectAll').click(function(e){
				var table= $(e.target).closest('table');
				$('td input:checkbox',table).attr('checked',e.target.checked);
			});
			*/
			var oTable = $('#ctable').dataTable({
				stateSave: true,
				pageLength: 100,
				"order": [[ 6, "desc" ]]
			});

			
			 
			$(".dataTables_length").css({ "display" :"none" });

		});
		function reset_form() {
				// $("#main_category :selected").attr({'selected':''});
				
				$("#search").val('');
		}	
		function go_reset() {
			  $("#mode").val("reset");
			  $("#form_mail").submit();
		}	
		function go_submit() { 

			$("#mode").val("send");
			$("#form_mail").submit();
		}
		function go_csv() { 
			$("#mode").val("csv");
			$('#form_mail').attr('action', 'email_manage_csv.php');
				$("#form_mail").submit();
		}
		function go_search() {
			$("#mode").val("");
			$("#form_mail").submit();
		}
		function go_add() { 
				
				
				//alert("1");
				
			$("#mode").val("add");
			$("#form_mail").submit();
		}
		function go_edit3(seq,mailnm,mailad,phone) { 
				
				
			$("#seq").val(seq);
			$("#cname").val(mailnm);
			$("#emailad").val(mailad);
			$("#phonenum").val(phone);
				
		}
		function go_edit2() { 
				
				
				//alert("1");
				$("#mode").val("edit2");
				$("#form_mail").submit();
				
		}
		function go_del(delval) { 
				
				
				//alert("1");
				if (confirm("Are you sure delete?")) {
				$("#mode").val("del");
				$("#seq").val(delval);
					$("#form_mail").submit();
			  }
		}
		function GoCheck(seq) { 
				
				
				$(".chk:checked").each(function() {
					if  ($(this).val() == 0) {
					  $("#chkvalue").val("1"); 
					} else {
						
					  $("#chkvalue").val("0"); 
					}
			  });
			
			$("#seq").val(seq);
			$("#mode").val("edit");
			$("#form_mail").submit();
		}
		function GoCheck1(seq) { 
				
				
				$(".chk1:checked").each(function() {
					  if  ($(this).val() == 0) {
					  $("#chkvalue1").val("1"); 
					} else {
						
						$("#chkvalue1").val("0"); 
					}
			  });
			
			$("#seq").val(seq);
			$("#mode").val("edit1");
			  $("#form_mail").submit();
		}

		function stripslashes(str) {
		  //       discuss at: http://phpjs.org/functions/stripslashes/
		  //      original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		  //      improved by: Ates Goral (http://magnetiq.com)
		  //      improved by: marrtins
		  //      improved by: rezna
		  //         fixed by: Mick@el
		  //      bugfixed by: Onno Marsman
		  //      bugfixed by: Brett Zamir (http://brett-zamir.me)
		  //         input by: Rick Waldron
		  //         input by: Brant Messenger (http://www.brantmessenger.com/)
		  // reimplemented by: Brett Zamir (http://brett-zamir.me)
		  //        example 1: stripslashes('Kevin\'s code');
		  //        returns 1: "Kevin's code"
		  //        example 2: stripslashes('Kevin\\\'s code');
		  //        returns 2: "Kevin\'s code"

		  return (str + '')
			.replace(/\\(.?)/g, function(s, n1) {
			  switch (n1) {
				case '\\':
				  return '\\';
				case '0':
				  return '\u0000';
				case '':
				  return '';
				default:
				  return n1;
			  }
			});
		}
	</script>
    </body>
</html>
