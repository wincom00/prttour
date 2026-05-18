<?php
	include "include/header.php";
	
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	if ($mode == "del")
	{
		$qry1 = "delete from memo_board where seq_no = '$no'";
		$rst1 = mysql_query($qry1,$dbConn);
	

		if($rst1)
		{
			Misc::jvAlert("Completed!","location.replace('memo_list.php')");
			exit;
		}
	}
    if ($mode == "modify")
	{
		$qry1 = "update memo_board  set  content1='$content1' ,content2='$content2' where seq_no = '$no'";
		$rst1 = mysql_query($qry1,$dbConn);
		if($rst1)
		{
			Misc::jvAlert("Completed!","location.replace('memo_list.php')");
			exit;
		}
	}

	if ($mode == "edit")
	{
		$qry1 = "select * from memo_board where seq_no = '$no'";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		$content1=$row1['content1'];
		$content2=$row1['content2'];
		$s_date=$row1['date'];
	} else if ($stdate!="") {
		$qry1 = "select * from memo_board where date = '$stdate'";
		$rst1 = mysql_query($qry1,$dbConn);
		$row1 = mysql_fetch_assoc($rst1);
		$content1=$row1['content1'];
		$content2=$row1['content2'];
		$s_date=$row1['date'];
	}
	if($board_mode == "write")
	{
		$qry2 = "select * from memo_board  where  date='$s_date'";
		$rst2 = mysql_query($qry2,$dbConn);
		$result_rows = mysql_num_rows($rst2);
		
		if ($result_rows > 0) {
			if ($no == "") {
				$row2 = mysql_fetch_assoc($rst2);
				$row2['content1'] = $row2['content1']."<br>".$content1;
				$row2['content2'] = $row2['content2']."<br>".$content2;

				$qry1 = "update memo_board  set  content1='{$row2['content1']}' ,content2='{$row2['content2']}' where  date='$s_date'";
				$rst1 = mysql_query($qry1,$dbConn);
            } else {
				$qry1 = "update memo_board  set  content1='$content1' ,content2='$content2' where seq_no = '$no'";
				$rst1 = mysql_query($qry1,$dbConn);
				if($rst1)
				{
					Misc::jvAlert("Completed!","location.replace('memo_list.php')");
					exit;
				}


			}
		} else {
			$qry1 = "insert into memo_board values ('','{$user_dbinfo['userid']}','$user_name','$s_date','$content1','$content2',now())";
			$rst1 = mysql_query($qry1,$dbConn);
		}

		if($rst1)
		{
			Misc::jvAlert("Completed!","location.replace('memo_list.php')");
			exit;
		}
	}
	if(!$start)
	{
	   $start = 0;
	}

	$board_scale = 20;
	$board_page = 10;

	$scale=$board_scale;

	$page_scale=$board_page;

    if ($sdate!="") {
		$sqry = " && date='$sdate' ";
		$s_date=$stdate;
    }
	$que = "select * from memo_board where 1=1 order by seq_no desc  LIMIT $start, $scale";


	//echo $que; 

	$page=floor($start/($scale*$page_scale));
    
	$result = mysql_query($que,$dbConn);
	$total = mysql_num_rows($rst2);
	
	$last=floor($total/$scale);


	$page_total_qry = mysql_query("SELECT count(*) FROM memo_board as a	WHERE 1=1",$dbConn);


	//$page_total = mysql_result($page_total_qry,0,0);
    //2021.03.08 7버전에서 사라진 mysql_result 함수 대체
    $page_total = @mysql_result($page_total_qry,0,0);

	$page_last = floor($page_total/$scale);

	
	$total_page_num = ceil($page_total/$scale);

	$now_page_num = floor($start/$scale) + 1;
	function printMemo1(){
		
		global $dbConn, $start, $page_total, $scale, $page, $page_scale,$page_last,$result;

		
		if($start)
		{
		     $n=$page_total-$start;
		}
		else
		{
		    $n=$page_total;
		}
		
        if($page_total != "0")
        { 
        	 
		        for($i=$start; $i<$start+$scale; $i++)
		        {
		        	
				        if($i<$page_total)
				        {
							 
							 $row1 = mysql_fetch_assoc($result);
							 $row1['content1'] = nl2br($row1['content1']);
							 $row1['content2'] = nl2br($row1['content2']);
							 echo "<tr>
										<td width=15% align=center height=22>Date</td>
										<td width=35% align=left>&nbsp;{$row1['date']}</td>
										<td width=15% align=center>Name</td>
										<td width=35% align=left>&nbsp;{$row1['name']} ({$row1['register']})</td>
									</tr>
									<tr><td colspan=4 height=1 bgcolor=#dcdcdc></td></tr><tr>
										<td width=15% align=center height=70>Memo 1</td>
										<td width=35% align=left valign=top><br />&nbsp;{$row1['content1']}</td>
										<td width=15% align=center>Memo 2</td>
										<td width=35% align=left valign=top>&nbsp;{$row1['content2']}</td>
									</tr>
									<tr><td colspan=4 height=25 align=right><a href=\"javascript:memo_edit('{$row1['seq_no']}')\">수정</a>&nbsp;||&nbsp;<a href=\"javascript:memo_del('{$row1['seq_no']}')\">삭제</a></td></tr>
									<tr><td colspan=4 height=2 bgcolor=#cccccc></td></tr>";
						
																		
						
						}
				}
						
		}
		else
		{
				$content= "";
	    }
	    return $content;

	}

	function pageNavigation(){

        global $page_total,$page,$start,$scale,$page_scale,$division,$page_last,$category,$search;

        $Parameter_value = "";

        if($page_total>$scale)
        {
			if($start+1>$scale*$page_scale)
			{
				$pre_start=$page*$scale*$page_scale-$scale;
				
				echo "<a href='$PHP_SELF?start=$pre_start&$Parameter_value'>Prev </a>";
			}
			for($vj=0; $vj<$page_scale; $vj++)
			{
				$ln=($page * $page_scale+$vj)*$scale;
				$vk=$page*$page_scale+$vj+1;
				if($ln<$page_total)
				{
						if($ln!=$start)
						{
						   
							echo "<a href='$PHP_SELF?start=$ln&$Parameter_value'>$vk. </a>";
						}
						else
						{
						    
							 echo "<a href='$PHP_SELF?start=$ln&$Parameter_value'>[$vk]. </a>";
						}
				}
			}
			if($page_total>(($page+1)*$scale*$page_scale))
			{
				$n_start=($page+1)*$scale*$page_scale;
				$last_start=$page_last*$scale;
				echo "<a href='$PHP_SELF?start=$n_start&$Parameter_value'>Next </a>";
				
			}
        }
      }// pageNavigation function end

	function printMemo(){
		
		global $dbConn;

		$qry1 = "select * from memo_board order by seq_no desc limit 50";
		$rst1 = mysql_query($qry1,$dbConn);

		while($row1 = $rst1->fetch_assoc()){
			
			$row1['content1'] = nl2br($row1['content1']);
			$row1['content2'] = nl2br($row1['content2']);

			echo "<tr>
				<td width=15% align=center height=22>Date</td>
				<td width=35% align=left>&nbsp;{$row1['date']}</td>
				<td width=15% align=center>Name</td>
				<td width=35% align=left>&nbsp;{$row1['name']} ({$row1['register']})</td>
			</tr>
			<tr><td colspan=4 height=1 bgcolor=#dcdcdc></td></tr><tr>
				<td width=15% align=center height=70>Memo 1</td>
				<td width=35% align=left valign=top><br />&nbsp;{$row1['content1']}</td>
				<td width=15% align=center>Memo 2</td>
				<td width=35% align=left valign=top>&nbsp;{$row1['content2']}</td>
			</tr>
			<tr><td colspan=4 height=25 align=right><a href=\"javascript:memo_del('{$row1['seq_no']}')\">삭제</a></td></tr>
			<tr><td colspan=4 height=2 bgcolor=#cccccc></td></tr>";

		}

	}

?>
<script>
    
	function memo_del(no){
		
		if(confirm("삭제할까요?") == true)
		{
			location.replace('memo_list.php?mode=del&no=' + no);
		}
		else return;
	}
	
	function memo_edit(no){
		
		
		location.replace('memo_list.php?mode=edit&board_mode=edit&no=' + no);
		
	}

	function chk(tf){

		if(tf.s_date.value == '')
		{
			alert('날짜 넣으세요!');
			tf.s_date.focus();
			return false;
		}
		
	return true;
	}
  </script>
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">스케줄메모등록</a>
					</li>
					
				</ul>
			</div>
			
		<div class="row">

				<div class="col-sm-12 col-md-12">
				<form Enctype="multipart/form-data" name=board_write action=<?= $PHP_SELF ?> method=post onSubmit="return chk(this)">
				 <table id="level4" class="txt_12" width="98%" align="center" border="0" cellspacing="0" cellpadding="0">
					
					<tr>
						<td colspan="4" height="50" align="center" bgcolor="#FFFFFF"><input type="submit" value="&nbsp;메모 저장&nbsp;"></td>
					</tr>
				
			    </table>
			    <br>
				
					
					
				  <table class="table table-striped table-bordered mediaTable">
					<input type=hidden name=board_mode id=board_mode value="write">
					<input type=hidden name=no value="<?= $no ?>">
					   
					  <tr bgcolor="#B0FDF9" height="28">
							<td align="left" colspan=4>&nbsp;스케줄 메모 정보
								
							</td>
						</tr>		
						<tr bgcolor="#FFFFFF"> 
							  <td width="15%" height="25" align="center" bgcolor="#FBFBFB">Date</td>
							  <td width=35%>&nbsp;&nbsp;<input type=text name=s_date id='date1' size=16 class="inpubase lg" value="<?=$s_date?>" ></td>
							  <td width="15%" height="25" align="center" bgcolor="#FBFBFB" >Name</td>
							  <td width=35%>&nbsp;<input name="user_name" type="text" class="inpubase lg" size="16" value="<?= $user_dbinfo['kor_name'] ?>"></td>
							</tr>
							<tr bgcolor="#FFFFFF"> 
							  <td width="15%" height="25" align="center" bgcolor="#FBFBFB">Memo 1</td>
							  <td width=85% colspan=3>&nbsp;<textarea name=content1 id=content1 cols=80 rows=10 class="form-control" ><?=$content1?></textarea></td>
							</tr>
							<tr bgcolor=#FFFFFF>
							  <td width="15%" height="25" align="center" bgcolor="#FBFBFB">Memo 2</td>
							  <td width=85% colspan=3>&nbsp;<textarea name=content2 id=content2 cols=80 rows=10 class="form-control" ><?=$content2?></textarea></td>
							</tr>
					   </table>
					</form>
					<table width="98%" align=center border="0" cellspacing="0" cellpadding="0">
					  <tr> 
						<td height="34" bgcolor="#eeeeee" colspan=4><div align="center"> <strong>Memo 리스트</strong> </div></td>
					  </tr>
					  <?php printMemo1(); ?>
					  <tr>
						 <td bgcolor="#ffffff" align="center" colspan=4 style="padding-left:75px; padding-right:75px; padding-bottom:30px">&nbsp;</td>
					  </tr>
					  <tr>
						 <td bgcolor="#ffffff" align="center" colspan=4 style="padding-left:75px; padding-right:75px; padding-bottom:30px"><?php pageNavigation(); ?></td>
					  </tr>
					</table>
	           </div>
		</div>
 </div>
 <?php
		include "include/side_m.php"
?>
<script>
		$(document).ready(function () {
            //$.ajaxSetup({async:false});
			$('#date1').datepicker({
				format: "yyyy-mm-dd",
				
				autoclose: true
			
			});
			
		});


</script>
<script>
	$(document).ready(function() {
		var memoEditorConfig = {
			height: 400,
			language: 'ko_KR',
			paste_data_images: true,
			automatic_uploads: true,
			images_upload_url: 'cupload_image.php',
			images_reuse_filename: true,
			plugins: [
				'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
				'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
				'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons'
			],
			toolbar: 'undo redo | blocks fontfamily fontsize | ' +
					 'bold italic underline strikethrough | link image media table | ' +
					 'align lineheight | numlist bullist indent outdent | emoticons charmap | ' +
					 'removeformat | code fullscreen preview',
			font_family_formats:
				'나눔고딕=Nanum Gothic, sans-serif;' +
				'맑은 고딕=Malgun Gothic,sans-serif;' +
				'돋움=Dotum,sans-serif;' +
				'굴림=Gulim,sans-serif;' +
				'바탕=Batang,serif;' +
				'Arial=arial,helvetica,sans-serif;' +
				'Times New Roman=times new roman,times,serif;' +
				'Courier New=courier new,courier,monospace',
			fontsize_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 28pt 30pt 32pt 34pt 36pt',
			document_base_url: 'https://myprt.org/',
			relative_urls: false,
			remove_script_host: false,
			content_style: 'body { font-family: Nanum Gothic, sans-serif; font-size: 14px; }',
			menubar: 'file edit view insert format tools table help',
			branding: false,
			resize: 'both',
			elementpath: false,
			statusbar: true,
			images_upload_handler: function (blobInfo, progress) {
				return new Promise(function(resolve, reject) {
					var xhr = new XMLHttpRequest();
					xhr.withCredentials = false;
					xhr.open('POST', 'cupload_image.php');
					xhr.upload.onprogress = function (e) {
						if (progress && e.lengthComputable) {
							progress(e.loaded / e.total * 100);
						}
					};
					xhr.onload = function() {
						if (xhr.status === 200) {
							try {
								var json = JSON.parse(xhr.responseText);
								if (json && json.location) {
									var cleanUrl = json.location.split('?')[0];
									resolve(cleanUrl);
								} else {
									reject('Invalid response');
								}
							} catch (e) {
								reject('Invalid JSON response: ' + xhr.responseText);
							}
						} else {
							var msg = 'HTTP Error: ' + xhr.status;
							try { var e = JSON.parse(xhr.responseText); if (e && e.error) msg = e.error; } catch(ex) {}
							reject(msg);
						}
					};
					xhr.onerror = function () {
						reject('Upload failed due to network error');
					};
					var formData = new FormData();
					formData.append('file', blobInfo.blob(), blobInfo.filename());
					xhr.send(formData);
				});
			}
		};

		tinymce.init(Object.assign({ selector: '#content1' }, memoEditorConfig));
		tinymce.init(Object.assign({ selector: '#content2' }, memoEditorConfig));
	});
</script>