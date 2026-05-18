
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

   if($mode == "save")
	{
		    $qrydivi="&& divi='$divi'";
		    $qry1 = "update banner_page set content = '$pop', visible = '$visibility', test = '$test' where 1=1 $qrydivi && area = 'popup'";
			$rst1 = mysql_query($qry1,$dbConn);

		
	}
   
	$qry1 = "select * from banner_page where divi='head' && area = 'popup' ";
	$rst1 = mysql_query($qry1);
	$row1 = mysql_fetch_assoc($rst1);

	$popupvisible1 = $row1['visible'];
	
	$qry1 = "select * from banner_page where divi='west' && area = 'popup' ";
	$rst1 = mysql_query($qry1);
	$row2 = mysql_fetch_assoc($rst1);

	$popupvisible2=$row2['visible'];

	$qry1 = "select * from banner_page where divi='las' && area = 'popup' ";
	$rst1 = mysql_query($qry1);
	$row3 = mysql_fetch_assoc($rst1);

	$popupvisible3=$row3['visible'];

    $qry1 = "select * from banner_page where divi='das' && area = 'popup' ";
	$rst1 = mysql_query($qry1);
	$row4 = mysql_fetch_assoc($rst1);

	$popupvisible4=$row4['visible'];

	$qry1 = "select * from banner_page where divi='ats' && area = 'popup' ";
	$rst1 = mysql_query($qry1);
	$row5 = mysql_fetch_assoc($rst1);

	$popupvisible5=$row5['visible'];

	$qry1 = "select * from banner_page where divi='sea' && area = 'popup' ";
	$rst1 = mysql_query($qry1);
	$row6 = mysql_fetch_assoc($rst1);

	$popupvisible6=$row6['visible'];
?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">홈페이지관련설정</a>
					</li>
					<li>
						팝업관리
					</li>
				</ul>
			</div>
			
		<div class="row">
		   <ul class="nav nav-tabs">
				  <li class="active"><a data-toggle="tab" href="#home">본사</a></li>
				  <li><a data-toggle="tab" href="#west">미서부 LA</a></li>
				  <li><a data-toggle="tab" href="#las">라스베가스</a></li>
				  <li><a data-toggle="tab" href="#das">달라스</a></li>
				  <li><a data-toggle="tab" href="#ats">애틀란타</a></li>
				  <li><a data-toggle="tab" href="#sea">시애틀</a></li>
				</ul>
				<br>
				<div class="col-sm-12 col-md-12 tab-content">
				  <div id="home" class="tab-pane fade  in active">
						  
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail " width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=2&sub=15 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=divi value="head">
						    <tr bgcolor=#f9f9f9 >
								
								<td align=left width=45% >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="visibility" value="1" <?= $popupvisible1 ? "checked" : "" ?>>팝업창 보기</td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td bgcolor=#FFFFFF ><textarea name="pop" id ="pop" class="form_box js-ckEditor"><?= stripslashes((string)$row1['content']) ?></textarea>
								
								</td>
							</tr>
							<tr>
								<td  height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
				  </div> 
				  <div id="west" class="tab-pane fade">
						  
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail " width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=2&sub=15 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=divi value="west">
						    <tr bgcolor=#f9f9f9 >
								
								<td align=left width=45% >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="visibility" value="1" <?= $popupvisible2 ? "checked" : "" ?>>팝업창 보기</td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td bgcolor=#FFFFFF ><textarea name="pop" id ="pop1" class="form_box js-ckEditor"><?= stripslashes((string)$row2['content']) ?></textarea>
								
								</td>
							</tr>
							<tr>
								<td  height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
				  </div>  
				  <div id="las" class="tab-pane fade">
						  
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail " width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=2&sub=15 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=divi value="las">
						    <tr bgcolor=#f9f9f9 >
								
								<td align=left width=45% >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="visibility" value="1" <?= $popupvisible3 ? "checked" : "" ?>>팝업창 보기</td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td bgcolor=#FFFFFF ><textarea name="pop" id ="pop2" class="form_box js-ckEditor"><?= stripslashes((string)$row3['content']) ?></textarea>
								
								</td>
							</tr>
							<tr>
								<td  height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
				  </div>  
				  <div id="das" class="tab-pane fade">
						  
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail " width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=2&sub=15 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=divi value="das">
						    <tr bgcolor=#f9f9f9 >
								
								<td align=left width=45% >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="visibility" value="1" <?= $popupvisible4 ? "checked" : "" ?>>팝업창 보기</td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td bgcolor=#FFFFFF ><textarea name="pop" id ="pop3" class="form_box js-ckEditor"><?= stripslashes((string)$row4['content']) ?></textarea>
								
								</td>
							</tr>
							<tr>
								<td  height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
				  </div>  
				  <div id="ats" class="tab-pane fade">
						  
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail " width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=2&sub=15 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=divi value="ats">
						    <tr bgcolor=#f9f9f9 >
								
								<td align=left width=45% >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="visibility" value="1" <?= $popupvisible5 ? "checked" : "" ?>>팝업창 보기</td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td bgcolor=#FFFFFF ><textarea name="pop" id ="pop4" class="form_box js-ckEditor"><?= stripslashes((string)$row5['content']) ?></textarea>
								
								</td>
							</tr>
							<tr>
								<td  height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
				  </div>  

				   <div id="sea" class="tab-pane fade">
						  
						  <table id="level4" class="table table-bordered table-condensed ptTable formDetail " width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
						  
						  <form action=<?= $PHP_SELF ?>?division=9&pdx=2&sub=15 method=post onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=divi value="sea">
						    <tr bgcolor=#f9f9f9 >
								
								<td align=left width=45% >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name="visibility" value="1" <?= $popupvisible6 ? "checked" : "" ?>>팝업창 보기</td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td bgcolor=#FFFFFF ><textarea name="pop" id ="pop5" class="form_box js-ckEditor"><?= stripslashes((string)$row6['content']) ?></textarea>
								
								</td>
							</tr>
							<tr>
								<td  height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
							</tr>
							</form>
						  </table>
						  <br>
					
				  </div>  
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
				$.ajaxSetup({async:false});
				///pt.initProductDetailForm()
			    
				// TinyMCE 초기화
				tinymce.init({
					selector: '.js-ckEditor',
					forced_root_block: 'tr',
					height: 600,
					language: 'ko_KR',
					license_key: 'gpl',
					plugins: [
						'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
						'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
						'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons','code'
					],
					// 중요: readonly 모드 비활성화
				    readonly: false,
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
						var filename = blobInfo.filename();
						
						if (filename.indexOf('mceclip') === 0) {
							// 원본에서 확장자 추출
							var extension = '';
							var dotPos = filename.lastIndexOf('.');
							if (dotPos !== -1) {
								extension = filename.substring(dotPos); // .jpg, .png 등
							} else {
								extension = '.jpg'; // 기본 확장자
							}
							
							// 새로운 파일명 (확장자 제외) + 원본 확장자
							filename = 'purun_image' + extension;
						}
    
						
						var xhr = new XMLHttpRequest();
						xhr.withCredentials = false;
						xhr.open('POST', 'cupload_image.php');
						
						xhr.onload = function() {
							if (xhr.status < 200 || xhr.status >= 300) {
								var msg = 'HTTP Error: ' + xhr.status;
								try { var e2 = JSON.parse(xhr.responseText); if (e2 && e2.error) msg = e2.error; } catch(ex) {}
								reject(msg);
								return;
							}
							
							var json = JSON.parse(xhr.responseText);
							if (!json || typeof json.location != 'string') {
								reject('Invalid JSON: ' + xhr.responseText);
								return;
							}
							
							resolve(json.location);
						};
						
						var formData = new FormData();
						var file = new File([blobInfo.blob()], filename, {type: blobInfo.blob().type});
						formData.append('file', file);
						xhr.send(formData);
					});
				}
				});
				/*
				CKEDITOR.replace( 'pop1', {
					allowecContent : true,
					extraAllowedContent : 'div(indicators,hide); span(indicator,active);',
					fillEmptyBlocks : false,
					filebrowserUploadUrl: 'upload.php',
					enterMode:'2',
					height : '595px',
					
				} );
				CKEDITOR.replace( 'pop2', {
					allowecContent : true,
					extraAllowedContent : 'div(indicators,hide); span(indicator,active);',
					fillEmptyBlocks : false,
					filebrowserUploadUrl: 'upload.php',
					enterMode:'2',
					height : '595px',
					
				} );
				CKEDITOR.replace( 'pop3', {
					allowecContent : true,
					extraAllowedContent : 'div(indicators,hide); span(indicator,active);',
					fillEmptyBlocks : false,
					filebrowserUploadUrl: 'upload.php',
					enterMode:'2',
					height : '595px',
					
				} );
				CKEDITOR.replace( 'pop4', {
					allowecContent : true,
					filebrowserUploadUrl: 'upload.php',
					enterMode:'2',
					height : '595px',
					
				} );
				CKEDITOR.replace( 'pop5', {
					allowecContent : true,
					filebrowserUploadUrl: 'upload.php',
					enterMode:'2',
					height : '595px',
					
				} );
				CKEDITOR.config.allowedContent = true;
				*/
				
	     });
		
	 </script>

    </body>
</html>

      
      