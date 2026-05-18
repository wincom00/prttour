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
								  <input type=hidden name=board_mode value="write">
								  <input type=hidden name=table_id value="<?= $table_id ?>">
								  <input type=hidden name=division value="<?= $division ?>">
								  <input type=hidden name=pdx value="<?= $pdx ?>">
								  <input type=hidden name=sub value="<?= $sub ?>">
								  <input type=hidden name=user_id value="<?= $user_dbinfo['userid'] ?>">
								 <table class="table table-striped table-bordered mediaTable" width="100%" >
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">게시판이름</td>
										  <td  >&nbsp;&nbsp;<?= $board_config['board_name'] ?></td>
										</tr>
										<?php if (($table_id == "70")){ ?>
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">카테고리</td>
										  <td> <select class="inpubase md " name="cc" id="cc"><option value="">선택</option>
										  <?php
										
											$cr_qry1 = "select * from code_base where lvcode1 = 'G01' && lvcode2 <> '00'  order by lvcode2 asc";
											$cr_rst1 = mysql_query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = mysql_fetch_assoc($cr_rst1)):

												//$area_name = codebasename($cr_row1[lvcode1]);
												$tour_value2 = $cr_row1['lvcode2'];
											
										?>
												
												<option value="<?=$tour_value2?>"><?=$cr_row1['comment']?></option>
										<?php
											
											endwhile;
										?>
										    </select>
										  

										  </td>
										</tr>
										<?php } ?>
										<?php if (($table_id == "60")){ ?>
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">카테고리</td>
										  <td> <select class="inpubase md " name="cc" id="cc"><option value="">선택</option>
										  <?php
										
											$cr_qry1 = "select * from code_base where lvcode1 = 'G02' && lvcode2 <> '00'  order by lvcode2 asc";
											$cr_rst1 = mysql_query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = mysql_fetch_assoc($cr_rst1)):

												//$area_name = codebasename($cr_row1[lvcode1]);
												$tour_value2 = $cr_row1['lvcode2'];
											
										?>
												
												<option value="<?=$tour_value2?>"><?=$cr_row1['comment']?></option>
										<?php
											
											endwhile;
										?>
										    </select>
										  

										  </td>
										</tr>
										<?php } ?>
										<tr bgcolor="#FFFFFF"> 
										  <td width="100" height="25" align="center" bgcolor="#FBFBFB">작성자</td>
										  <td><input name="user_name" type="text" class="form-control" style="width:200px;" value="<?= $user_dbinfo['kor_name'] ?>[푸른투어]"></td>
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">제목 </td>
										  <td  align="left"><input name="title" type="text" class="form-control" value=""></td>
											 
										</tr>
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">내용 </td>
										  <td  align="center"><textarea name="FCKeditor1" id ="FCKeditor1"  class="form-control js-specialBenefit js-ckEditor" ></textarea>
											</td>
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
												<td><input name="userfile2" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>
										<!-- 여기에 추가 -->
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile3" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile4" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										<?php if (($table_id == "70")){ ?>
										
										

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile5" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile6" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile7" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile8" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile9" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>

										
										<tr bgcolor="#FFFFFF"> 
										  <td height="25" align="center" bgcolor="#FBFBFB">첨부파일 </td>
										  <td colspan="3" align="left"><table width="98%" border="0" cellspacing="0" cellpadding="0">
											  <tr> 
												<td><input name="userfile10" type="file" class="form-control" style="width:250px;"></td>
											  </tr>
											</table></td>
										</tr>
										<?php } ?>
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
			
			// TinyMCE 초기화
			tinymce.init({
				selector: '#FCKeditor1',
				height: 700,
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
				
				// Promise 기반으로 수정 + 쿼리 파라미터 제거
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
										// URL에서 ? 이후 쿼리 파라미터 완전 제거
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

      
      