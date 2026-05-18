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
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&table_id=<?=$table_id?>" enctype="multipart/form-data" name="frmbrlist" id="frmbrlist" method="post">
			          <input type="hidden" name="mode" id="mode" value="search">
					  <input type="hidden" name="how" value="4">
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   <tr>
							      <td width=10%  class="titletd" style="vertical-align: middle;">검색어 </td>
								  <td width=20% style='border:0;' class="conttd">
								      <input width=30%  type="text" id="search" name="search" class="inpubase lg" value="<?=$search?>"/>
								  </td>
								  <td width=10%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <?php if ($table_id == "35") { ?>
								  <td class="conttd"><b>* 반드시 답변은 이메일로 해주세요!!</b></td>
								  <?php } ?>
                               </tr> 
							</tbody>
						</table>
					 </form>
					 <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&table_id=<?=$table_id?>" enctype="multipart/form-data" name="frmbrlist2" id="frmbrlist2" method="post">
					 <input type="hidden" name="mode1" id="mode1" value="">
					  <table class="table table-striped table-bordered mediaTable">
						<thead>
						<?php if ($table_id != "60") { ?>
							<tr>
							    <th width=5% class="essential"><input type=checkbox name="selectall" id="selectall" ></th>
							    <th width=10% class="essential">순번</th>
								<th width=* class="essential">제목</th>
								<th width=10% class="essential">작성자</th>
								<th width=15% class="essential">작성일</td>
								<th width=10% class="essential">조회수</td>
							</tr>

						<?php } else { ?>
						     <tr>
								<th width=5% class="essential"><input type=checkbox name="selectall" id="selectall" ></th>
								<th width=5% class="essential"><strong>&nbsp;Y &nbsp;<strong></th>
								<th width=5% class="essential"><strong>&nbsp;N &nbsp;<strong></th>
								<th width=10% class="essential"><strong>순번<strong></th>
					     		<th width=* class="essential">제목</th>
								<th width=10% class="essential">작성자</th>
								<th width=15% class="essential">작성일</td>
								<th width=10% class="essential">조회수</td>
						     <tr>
							<?php } ?>
							</thead> 
						<?php if ( ($table_id != "60") ) { ?>
								<?=board_contentPrint()?>
							<?php } else { ?>
								<?=board_contentPrint1()?>
							<?php } ?>
						</thead> 
						<?php if ($scale < $page_total) { ?>
						<tr> 
						    <td height="5" align="center" colspan=6><table width="308" border="0" cellspacing="0" cellpadding="0">
								<tr align="center"> 
								  <td align=center><nav aria-label="Page navigation example">
												  <ul class="pagination">
													<?=board_pageNavigation()?>
												  </ul>
												</nav>
								  </td>
								  <td>&nbsp;</td>
								</tr>
							  </table></td> 
						 </tr>
						 <?php } ?>
					    <tr> 

							   <td height="50" align="center" colspan=6><table width="308" border="0" cellspacing="0" cellpadding="0">
								<tr align="center"> 
								<?php if ( ($table_id == "60")) { ?>
								  <td>&nbsp;<button type="button" class="btn btn-primary btn-sm btn1 adj">홈페이지적용</button></td>
								  <?php } ?>
								  <td align=center><a href='board_write.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&table_id=<?=$table_id?>' class="btn btn-primary btn-sm btn1">글쓰기</a> &nbsp; &nbsp; &nbsp;<a href='javascript:del()' class="btn btn-primary btn-sm btn1">삭제</a>
								  &nbsp;
								  </td>
								  <td>&nbsp;</td>
								</tr>
							  </table></td> 
						  </tr>
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
		
		$(document).ready(function () {
				$.ajaxSetup({async:false});
				
				// Listen for click on toggle checkbox
				$('#selectall').click(function(event) {   
					if(this.checked) {
						// Iterate each checkbox
						$(':checkbox').each(function() {
							this.checked = true;                        
						});
					} else {
						$(':checkbox').each(function() {
							this.checked = false;                       
						});
					}
				});
				$('.adj').click(function(event) { 
					$("#mode1").val("adj");
					$("#frmbrlist2").submit();
				});
		});

		function del() {
			var chk = 0;
			$(':checkbox').each(function() {
				if (this.checked == true)
				{
					chk = 1;
				} 
			 });
			 if (chk == 0)
			 {
				 alert("적어도 한개라도 선택되어야 합니다.");
				 return;
			 }
			 $("#mode1").val("select_del");
			 $("#frmbrlist2").submit();

		}
		
		
	</script>


    </body>
</html>

      
      
