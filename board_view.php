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
	
	if ($table_id=='01') {
		$cap = "문의게시판";
	} else if ($table_id=='02') {
		$cap = "회계문의";
	} else if ($table_id=='15') {
		$cap = "사내공지사항";
	} else if ($table_id=='25') {
		$cap = "상품공지사항";
	} else if ($table_id=='30') {
		$cap = "자료실";
	}  else if ($table_id=='85') { 
		$cap = "항공자료실";
	}  else if ($table_id=='90') {
		$cap = "비자자료실";
	}  else if ($table_id=='93') {
		$cap = "교육자료실";
	}  else if ($table_id=='95') {
		$cap = "일반자료실";
	} else {
		$cap = "";
	}


	include "inc_board.php";
?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">게시판관리</a>
					</li>
					
					<li>
						<?=$cap?>
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					   <table class="table table-striped table-bordered mediaTable" width="100%" >
						  <tr> 
							<td height="34" bgcolor="#eeeeee"><div align="center"> <strong><?= $board_row2['title'] ?></strong> </div></td>
						  </tr>
						  <tr> 
							<td> <table class="table table-striped table-bordered mediaTable" width="100%"  cellspacing="1" bgcolor="E9E9E9">
								<tr bgcolor="#FFFFFF"> 
								  <td width="100" height="25" align="center" bgcolor="#FBFBFB">순번</td>
								  <td width="250" align="center"><table width="95%" >
									  <tr> 
										<td><?= $board_row2['seq_no'] ?></td>
									  </tr>
									</table></td>
								  <td width="100" align="center" bgcolor="#FBFBFB">조회수</td>
								  <td width="250" align="center"><table width="95%" border="0" cellspacing="0" cellpadding="0">
									  <tr> 
										<td><?= $board_row2['count'] ?></td>
									  </tr>
									</table></td>
								</tr>
								<tr bgcolor="#FFFFFF"> 
								  <td height="25" align="center" bgcolor="#FBFBFB">이름</td>
								  <td align="center"><table width="95%" border="0" cellspacing="0" cellpadding="0">
									  <tr> 
										<td>
										<?php 
										if (($table_id == "01") || ($table_id == "35")) {
												if ($board_row2['phone'] <> "") {
													$contact = "&nbsp;&nbsp;<span style='font-size:7pt'>Tel: {$board_row2['phone']}";
													$contact .= "</span>";
												}
												if ($board_row2['email'] <> "") {
													$contact .= "<br><span style='color:white'>{$board_row2['name']}</span>Email: {$board_row2['email']}</span>";
												} 
											} else {
												
												if ($board_row2['email'] <> "") {
													$contact = "&nbsp;&nbsp;<span style='font-size:7pt'>Email: {$board_row2['email']}</span>";
												}
											}
										
										echo "{$board_row2['name']} $contact";
										?>
										</td>
									  </tr>
									</table></td>
								  <td align="center" bgcolor="#FBFBFB">글쓴날짜</td>
								  <td align="center"><table width="95%" border="0" cellspacing="0" cellpadding="0">
									  <tr> 
										<td><?= $today[0] ?></td>
									  </tr>
									</table></td>
								</tr>
								<?php
										if (($table_id == "35")) { ?>
								<tr>
								  <td width="100" align="center" bgcolor="#FBFBFB">통화가능시간</td>
								  <td width="250" align="center"><table width="95%" border="0" cellspacing="0" cellpadding="0">
									  <tr> 
										<td><?= $board_row2['calltime'] ?></td>
									  </tr>
									</table></td>
								</tr>
								<?php } ?>
								<tr bgcolor="#FFFFFF"> 
								  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 1</td>
								  <td >&nbsp;&nbsp;<a href="download.php?filename=<?= rawurlencode($board_row2['userfile1']) ?>"><img src='./img/database_save.png' align=absmiddle> <?= $board_row2['userfile1'] ?></a></td>
								  <td align="center" bgcolor="#FBFBFB">첨부파일 2</td>
								  <td >&nbsp;&nbsp;<a href="download.php?filename=<?= rawurlencode($board_row2['userfile2']) ?>"><img src='./img/database_save.png' align=absmiddle> <?= $board_row2['userfile2'] ?></a></td>
								</tr>
								<!-- 첨부파일 3, 4 추가 (모든 게시판에서 기본 사용) -->
								<tr bgcolor="#FFFFFF"> 
								  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 3</td>
								  <td >&nbsp;&nbsp;<?php if($board_row2['userfile3']) { ?><a href="download.php?filename=<?= rawurlencode($board_row2['userfile3']) ?>"><img src='./img/database_save.png' align=absmiddle> <?= $board_row2['userfile3'] ?></a><?php } ?></td>
								  <td align="center" bgcolor="#FBFBFB">첨부파일 4</td>
								  <td >&nbsp;&nbsp;<?php if($board_row2['userfile4']) { ?><a href="download.php?filename=<?= rawurlencode($board_row2['userfile4']) ?>"><img src='./img/database_save.png' align=absmiddle> <?= $board_row2['userfile4'] ?></a><?php } ?></td>
								</tr>
								<tr bgcolor="#FFFFFF"> 
								  <td height="25" align="center" bgcolor="#FBFBFB">내용</td>
								  <td colspan="3" align="left" height=200 valign=top><table width="98%" border="0" cellspacing="0" cellpadding="5">
									  <tr> 
										<td>
										  <?= nl2br($content); ?>							
										</td>
									  </tr>
									</table></td>
								</tr>
							  </table></td>
						  </tr>
						  <tr> 
						  
						  
						   
							   <td height="50" align="right"><table width="308" border="0" cellspacing="0" cellpadding="0">
								<tr align="right"> 
								  <td align=right><input type=button  class="btn btn-primary btn-sm" value='목록' Onclick="javascript:location.href='board_list.php?division=8&pdx=<?= $pdx ?>&sub=<?= $sub ?>&table_id=<?= $table_id ?>&start=<?= $start ?>'"></a>
								  
								  &nbsp;&nbsp;<input type=button class="btn btn-primary btn-sm" value='답글' Onclick="javascript:location.href='board_reply.php?division=<?= $division ?>&table_id=<?= $table_id ?>&pdx=<?= $pdx ?>&sub=<?= $sub ?>&board_mode=modify&no=<?= $no ?>&start=<?= $start ?>'">
								  
								  
							      &nbsp;<input type=button class="btn btn-primary btn-sm" value='수정' Onclick="javascript:location.href='board_modify.php?division=<?= $division ?>&pdx=<?= $pdx ?>&sub=<?= $sub ?>&table_id=<?= $table_id ?>&board_mode=modify&no=<?= $no ?>&start=<?= $start ?>'">
								  &nbsp;
								  </td>
								  <td>&nbsp;</td>
								</tr>
							  </table></td> 
							  
						 
						  </tr>

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
		
		function rest(id){
			
			if(confirm("입사처리할까요?") == true)
			{
				location.replace('emp_list.php?Mode=reset&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
		
		
	</script>


    </body>
</html>

      
      