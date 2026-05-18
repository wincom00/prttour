
<?php
    include "include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

    if ($Mode == "del") {
		$qry1 = "delete from html_page where seq_no= '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}

	if ($mode == "save") {
		$FCKeditor1 = addslashes($page);

		$qry1 = "update html_page set content = '".$FCKeditor1."' where id = '$id'";
		$rst1 = mysql_query($qry1,$dbConn);

		if ($rst1) {
			echo "<meta http-equiv='refresh' content='0; url=./page_regi.php?division=9&pdx=1&sub=10&Mode=modify&id=$id'>";
			exit;
		}					
	}

	if ($Mode == "modify") {
		$qry1 = "select * from html_page where id = '$id'";
		$rst1 = mysql_query($qry1);
		$row1 = mysql_fetch_assoc($rst1);
	}


?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">컨텐츠 페이지 편집</a>
					</li>
					<li>
						페이지 편집
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
						  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
							<tr bgcolor=#FFFFFF>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=ab_1>회사소개</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=pp_1>개인정보처리방침</a></td>
								
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								
							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=cp_1>여행약관</a></td>
								<td width=33% height=30 class="malgun" >&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=qq_1>단체여행</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=in_1>고객인보이스약관</a></td>
								<!--
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>이용약관</a></td>
								<td width=33% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=use_1>제휴안내</a></td>-->

							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun" colspan='1'>&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=you_1>메인유투브이미지</a></td>
								
								<td width=33% height=30 class="malgun" colspan='1'>&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=you_2>메인유투브슬라이드이미지</a></td>

                                <td width=33% height=30 class="malgun" colspan='1'>&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=you_3>메인유투브슬라이드이미지2</a></td>
							</tr>
							<tr bgcolor=#FFFFFF>
							    
								<td width=33% height=30 class="malgun" colspan='3'>&nbsp;<i class="glyphicon glyphicon-folder-close"></i> <a href=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10&Mode=modify&id=info_1>안내메일주의사항</a></td>
								
								
							</tr>
							
							<!--<tr bgcolor=#FFFFFF>
								<td height=30 class="malgun">&nbsp;<img src='../images/page_white_text.png' align=absmiddle> <a href=<?= $PHP_SELF ?>?division=6&Mode=modify&id=privacy>개인정보 취급방침</a></td>
								<td class="malgun">&nbsp;<img src='../images/page_white_text.png' align=absmiddle> <a href=<?= $PHP_SELF ?>?division=6&Mode=modify&id=join>제휴문의</a></td>
								<td class="malgun">&nbsp;<img src='../images/page_white_text.png' align=absmiddle> <a href=<?= $PHP_SELF ?>?division=6&Mode=modify&id=email>이메일 주소수집거부</a></td>
							</tr> -->
							
							
						  </table>
						  <br><br>
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  <tr>
							<td height=40 bgcolor=#FFFFFF class="malgun">&nbsp;&nbsp;상단 페이지중 편집하실 메뉴를 선택하세요. </td>
						  </tr>
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=1&sub=10 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=division value="<?= $division ?>">
						  <input type=hidden name=extra_mode value="<?= $extra_mode ?>">
						  <input type=hidden name=id value="<?= $id ?>">
							<tr bgcolor=#f9f9f9 height=28>
								<td colspan=4 bgcolor=#FFFFFF class="malgun">
									
										<textarea class="form-control js-specialBenefit js-ckEditor" name="page" id ="page" ><?= $row1['content'] ?></textarea>
									
								</td>
							</tr>

							<tr>
								<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
					  
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
	<!-- TinyMCE 에디터 -->
	<script src="/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>

	<script>
	// TinyMCE 초기화
	tinymce.init({
		selector: '#page',
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
		// 이미지 삽입 시 스타일 제거
		setup: function(editor) {
			editor.on('ExecCommand', function(e) {
				if (e.command === 'mceInsertContent' || e.command === 'mceInsertImage') {
					setTimeout(function() {
						var imgs = editor.dom.select('img');
						imgs.forEach(function(img) {
							var style = editor.dom.getAttrib(img, 'style');
							if (style && style.includes('width: 100%') && style.includes('height: 100%')) {
								editor.dom.setAttrib(img, 'style', '');
							}
						});
					}, 10);
				}
			});
		},
		
		document_base_url: 'https://myprt.org/',
		relative_urls: false,
		remove_script_host: false,
		content_style: 'body { font-family: Malgun Gothic, sans-serif; font-size: 14px; }',
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
		 </script>

    </body>
</html>

      
      