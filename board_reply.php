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
					   <form Enctype="multipart/form-data" name=board_write id=board_write action=<?= $PHP_SELF ?> method=post onSubmit="return chk(this)">
								  <input type=hidden name=board_mode value="reply">
								  <input type=hidden name=table_id value="<?= $table_id ?>">
								  <input type=hidden name=division value="<?= $division ?>">
								  <input type=hidden name=pdx value="<?= $pdx ?>">
								  <input type=hidden name=sub value="<?= $sub ?>">
								  <input type=hidden name=no value="<?= $no ?>">
								  <input type=hidden name=mail value="<?= $mail ?>">
								  <input type=hidden name=start value="<?= $start ?>">
								  <input type=hidden name=user_id value="<?= $user_dbinfo['userid'] ?>">
								  <input type=hidden name=thread value="<?= $board_row2['thread'] ?>">
	                              <input type=hidden name=fid value="<?= $board_row2['fid'] ?>">
								  <input type=hidden name=passwd value="<?= $board_row2['passwd'] ?>">
								 <table class="table table-striped table-bordered mediaTable" width="100%" >
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">게시판이름</td>
										  <td  >&nbsp;&nbsp;<?= $board_config['board_name'] ?></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">작성자</td>
										  <td><input name="user_name" type="text" class="form-control" style="width:200px;" value="<?= $user_dbinfo['kor_name'] ?>"></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">제목 </td>
										  <td  align="left"><input name="title" type="text" class="form-control" value="RE : <?= $board_row2['title'] ?>"></td>
											 
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">내용 </td>
										  <td  align="center"><textarea name="FCKeditor1" id ="FCKeditor1"  class="form-control js-specialBenefit js-ckEditor" ><?=$board_row2['content']?><br />==============================<br /></textarea>
											</td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td>현재 파일 : <?= $board_row2['userfile1'] ?>&nbsp;<input type=checkbox name=photo_del1 value="1">(※ 첨부파일 삭제)</td>
											  </tr>
											</table></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile1" type="file" class="form-control" style="width:250px;" ></td>
											  </tr>
											</table></td>
										</tr>
										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td>현재 파일 : <?= $board_row2['userfile2'] ?>&nbsp;<input type=checkbox name=photo_del2 value="1">(※ 첨부파일 삭제)</td>
											  </tr>
											</table></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile2" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>
										<tr> 
										       <td height="25" align="center" colspan="32 bgcolor="#FBFBFB"><input type=submit class="btn btn-primary btn-sm" value='저장' > </td>
	
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
				
				// TinyMCE 초기화
				tinymce.init({
					selector: '#FCKeditor1',
					height: 700,
					language: 'ko_KR',
					
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
					
					images_upload_url: 'cupload_image.php',
					automatic_uploads: true,
					paste_data_images: true,
					images_reuse_filename: true,
					
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
									try { var e2 = JSON.parse(xhr.responseText); if (e2 && e2.error) msg = e2.error; } catch(ex) {}
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
				});

				
		});
		function chk(tf){
				
				if(!tf.user_name.value)
				{
					alert('작성자명이 빠졌습니다.');
					tf.user_name.focus();
					return false;
				}
				if(!tf.title.value)
				{
					alert('제목이 빠졌습니다.');
					tf.title.focus();
					return false;
				}	
			    return true;
	   }   
		
		
	</script>


    </body>
</html>

      
      