<?php
	/**
	* 게시판 시작
	*/
	require_once __DIR__ . '/include/remote_upload.php';
	$tableName = "paran_board";
	$upload_url = _WEB_BASE_DIR;


	if(!$table_id)
	{
		//Misc::jvAlert("테이블명이 없습니다.","history.go(-1)");
		$table_id = "01";
	}

	/**
	* 게시판 성격을 가져온다.
	*/
	$board_config = boardConfig($table_id);

	if($board_config['top_img'])
	{
		$board_config['board_name'] = "<img src=\"./upload/{$board_config['top_img']}\">";
	}
	else
	{
		$board_config['board_name'] = "{$board_config['board_name']}";
	}

	if(empty($board_config))
	{
		Misc::jvAlert("아직 생성되지 않은 메뉴입니다.","history.go(-1)");
	}

	//echo $board_config[board_name];

	//if(($board_config[write_level] == "2") && (!$__COOKIE[NY_memberid]))
	//{
	//	Misc::jvAlert("회원 전용입니다. 로그인 해주세요","history.go(-1)");
	//	exit;
	//}

/////7echo $mode1;
//exit;
	if($mode1 == "select_del")
	{
		
		for($i=0; $i<count($seqNo); $i++)
		{
			$qry1 = "delete from $tableName where seq_no = '$seqNo[$i]'";
			$rst1 = mysql_query($qry1,$dbConn);
		}

		if($rst1)
		{
			echo "<meta http-equiv='refresh' content='0; url=./board_list.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id'>";
			exit;
		}
        else
		{
			Misc::jvAlert('삭제실패!','history.go(-1)');
		}
	}
	if($mode1 == "adj")
	{
		//echo count($seqNo);
		//exit;
		for($i=0; $i<count($seqNo); $i++)
		{
			$qry1 = "update $tableName set front_yn='Y' where seq_no = '$seqNo[$i]'";;
			//echo $qry1 ;
			//exit;
			$rst1 = mysql_query($qry1,$dbConn);
			
		}
		for($i=0; $i<count($fYn); $i++)
		{
			$qry1 = "update $tableName set front_yn='N' where seq_no = '$fYn[$i]'";;
		//	exit;
			$rst1 = mysql_query($qry1,$dbConn);
			
		}

		if($rst1)
		{
			Misc::jvAlert('적용성공!','history.go(-1)');
			echo "<meta http-equiv='refresh' content='0; url=./board_list.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id'>";
			exit;
		}
        else
		{
			Misc::jvAlert('실패!','history.go(-1)');
		}
	}


	if(!$board_mode)
	{
		$board_mode = "list";
	}

	if($board_mode == "list")
	{
		
		if(!$start)
		{
			$start = 0;
		}

		$board_scale = 30;
		$board_page = 10;

		/**
		* 한페이지당 글수
		*/
		$scale=$board_scale;

		/*
		* 한페이지당 페이지수
		*/
		$page_scale=$board_page;
	}
	else if($board_mode == "view")
	{
		$board_qry1 = "update $tableName set count=count+1 where seq_no='$no'";
		$board_rst1 = mysql_query($board_qry1,$dbConn);

		$board_qry2 = "select * from $tableName where seq_no='$no'";
		$board_rst2 = mysql_query($board_qry2,$dbConn);
		$board_row2 = mysql_fetch_assoc($board_rst2);

		$today = explode(" ",$board_row2['wdate']);


		// 자동링크걸기
		function auto_link($text) 
		{ 
			$text = str_replace(";", "", $text);
		#******* www 
		$text = preg_replace("~([^/])www([0-9a-zA-Z./@~?&=_-]+)~i", "\\1http://www\\2", $text); 

		#******* http 
		$text = preg_replace("~http://([0-9a-zA-Z./@~?&=_-]+)~i", "<a href=\"http://\\1\" target='_blank'>http://\\1</a>", $text); 

		#******* ftp 
		$text = preg_replace("~ftp://([0-9a-zA-Z./@~?&=_-]+)~i", "<a href=\"ftp://\\1\" target='_blank'>ftp://\\1</a>", $text); 

		#******* email 
		$text = preg_replace("~([_0-9a-zA-Z-]+(\\.[_0-9a-zA-Z-]+)*)@([0-9a-zA-Z-]+(\\.[0-9a-zA-Z-]+)*)~i", "<a href=\"mailto:\\1@\\3\">\\1@\\3</a>", $text); 
    //echo htmlspecialchars($text);
		return $text; 
		}

		
		$content = auto_link($board_row2['content']);
		//$content = $board_row2[content];

		if($board_config['noname_level'] == "2")
		{
			$writer_name = "******";
		}
		else
		{
			if($board_row2['email'])
			{
				$email = "( {$board_row2['email']} )";
			}

			$writer_name = "{$board_row2['userid']} $email";
		}

		$title = Misc::cutLongString($board_row2['title'], 48, $dot=true);

		$draw_file1 = Misc::getFileExtension($board_row2['userfile1']);
		if( $draw_file1 == "pdf" || $draw_file1 == "gif" || $draw_file1 == "jpg" || $draw_file1 == "bmp" || $draw_file1 == "png" ||
		   $draw_file1 == "GIF" || $draw_file1 == "JPG" || $draw_file1 == "BMP" || $draw_file1 == "jpeg"){

			$img_size = getimagesize("./upload/{$board_row2['userfile1']}");

			$board_photo_size = 500;

			// 가로이미지가 기준값보다 작을경우 그대로 출력
			if($board_photo_size>$img_size[0])
			{
			    $board_photo_size = $img_size[0];
			}
			else
			{
				$board_photo_size = 500;
			}


			$draw1 = "<p align=center><a href=\"javascript:popimage('{$board_row2['userfile1']}',$img_size[0],$img_size[1])\"><img src=\"./upload/{$board_row2['userfile1']}\" width=$board_photo_size border=0></a></p>";
		}

		$draw_file2 = Misc::getFileExtension($board_row2['userfile2']);
		if($draw_file2 == "gif" || $draw_file2 == "jpg" || $draw_file2 == "bmp" || $draw_file1 == "png" ||
		   $draw_file2 == "GIF" || $draw_file2 == "JPG" || $draw_file2 == "BMP" || $draw_file1 == "jpeg"){

			$img_size2 = getimagesize("./upload/{$board_row2['userfile2']}");

			$board_photo_size2 = 500;

			// 가로이미지가 기준값보다 작을경우 그대로 출력
			if($board_photo_size2>$img_size2[0])
			{
			   $board_photo_size2 = $img_size2[0];
			}
			else
			{
				$board_photo_size2 = 500;
			}
			
			$draw2 = "<p align=center><a href=\"javascript:popimage('{$board_row2['userfile2']}',$img_size2[0],$img_size2[1])\"><img src=\"../upload/{$board_row2['userfile2']}\" width=$board_photo_size2 border=0></a></p>";
		}

		$content = $board_row2['content'];



	}
	else if($board_mode == "modify")
	{
		$board_qry2 = "select * from $tableName where seq_no='$no'";
		$board_rst2 = mysql_query($board_qry2,$dbConn);
		$board_row2 = mysql_fetch_assoc($board_rst2);

		if($board_config['noname_level'] == "2")
		{
			$writer_name = "******";
		}
		else
		{
			$writer_name = "{$board_row2['name']}";
		}

	}
	else if($board_mode == "modify_write")
	{
		$qry5 = "select * from $tableName where seq_no='$no'";
		$rst5 = mysql_query($qry5);
		$row5 = mysql_fetch_array($rst5);

		

		if($photo_del1 != "1")
		{
			   if(empty($_FILES['userfile1']['name']))
			   {
				   $attc1_name['savedName'] = $row5['userfile1'];
			   }
			   else
				{
				   $tmpName1 = $_FILES['userfile1']['tmp_name'];

				   if(is_uploaded_file($tmpName1)){
						$pds_file1 = $_FILES['userfile1']['name'];
						$board_pds_pos = "./upload";
						$attc1_name = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
						$_sync1_path = $board_pds_pos . '/' . $attc1_name['savedName'];
						$_sync1_err  = '';
						$_sync1_ok   = remote_sync_file($_sync1_path, 'upload', $_sync1_err);
						$_sync1_msg  = '[FTP userfile1] ' . ($_sync1_ok ? '성공' : '실패') . ' | 파일: ' . $_sync1_path . ($_sync1_err ? ' | 오류: ' . $_sync1_err : '');
						echo $_sync1_msg;
						//exit;
						error_log($_sync1_msg, 3, __DIR__ . '/ftp_sync.log');
					}
				}
		}
		else
		{
			@unlink("./upload/{$row5['userfile1']}");
			$attc1_name['savedName'] = "";
		}

		if($photo_del2 != "1")
		{
			if(empty($_FILES['userfile2']['name']))
			{
			   $attc2_name['savedName'] = $row5['userfile2'];
			}
			else
			{
				$tmpName2 = $_FILES['userfile2']['tmp_name'];

				if(is_uploaded_file(filename: $tmpName2)){
					$pds_file2 = $_FILES['userfile2']['name'];
					$board_pds_pos = "./upload";
					$attc2_name = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);
					remote_sync_file($board_pds_pos . '/' . $attc2_name['savedName'], 'upload');
				}
			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile2']}");
			$attc2_name['savedName'] = "";
		}

		//////////////////////////////////////////////////////////70//////////////////////
		if($photo_del3 != "1")
		{
			if(empty($_FILES['userfile3']['name']))
			{
			   $attc3_name['savedName'] = $row5['userfile3'];
			}
			else
			{
				$tmpName3 = $_FILES['userfile3']['tmp_name'];

				if(is_uploaded_file($tmpName3)){
					$pds_file3 = $_FILES['userfile3']['name'];
					$board_pds_pos = "./upload";
					$attc3_name = Misc::uploadFileUnsafely($tmpName3 , $pds_file3 , $board_pds_pos);
					remote_sync_file($board_pds_pos . '/' . $attc3_name['savedName'], 'upload');
				}
			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile3']}");
			$attc3_name['savedName'] = "";
		}
		if($photo_del4 != "1")
		{
			if(empty($_FILES['userfile4']['name']))
			{
			   $attc4_name['savedName'] = $row5['userfile4'];
			}
			else
			{
				$tmpName4 = $_FILES['userfile4']['tmp_name'];

				if(is_uploaded_file($tmpName4)){
					$pds_file4 = $_FILES['userfile4']['name'];
					$board_pds_pos = "./upload";
					$attc4_name = Misc::uploadFileUnsafely($tmpName4 , $pds_file4 , $board_pds_pos);
					remote_sync_file($board_pds_pos . '/' . $attc4_name['savedName'], 'upload');
				}
			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile4']}");
			$attc4_name['savedName'] = "";
		}
		if($photo_del5 != "1")
		{
			if(empty($_FILES['userfile5']['name']))
			{
			   $attc5_name['savedName'] = $row5['userfile5'];
			}
			else
			{
				$tmpName5 = $_FILES['userfile5']['tmp_name'];

				if(is_uploaded_file($tmpName5)){
					$pds_file5 = $_FILES['userfile5']['name'];
					$board_pds_pos = "./upload";
					$attc5_name = Misc::uploadFileUnsafely($tmpName5 , $pds_file5 , $board_pds_pos);
					remote_sync_file($board_pds_pos . '/' . $attc5_name['savedName'], 'upload');
				}
			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile5']}");
			$attc5_name['savedName'] = "";
		}
		if($photo_del6 != "1")
		{
			if(empty($_FILES['userfile6']['name']))
			{
			   $attc6_name['savedName'] = $row5['userfile6'];
			}
			else
			{
				$tmpName6 = $_FILES['userfile6']['tmp_name'];

				if(is_uploaded_file($tmpName6)){
					$pds_file6 = $_FILES['userfile6']['name'];
					$board_pds_pos = "./upload";
					$attc6_name = Misc::uploadFileUnsafely($tmpName6 , $pds_file6 , $board_pds_pos);
				}
			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile6']}");
			$attc6_name['savedName'] = "";
		}
		if($photo_del7 != "1")
		{
			
			if(empty($_FILES['userfile7']['name']))
			{
			   $attc7_name['savedName'] = $row5['userfile7'];
			}
			else
			{
				$tmpName7 = $_FILES['userfile7']['tmp_name'];

				if(is_uploaded_file($tmpName7)){
					$pds_file7 = $_FILES['userfile7']['name'];
					$board_pds_pos = "./upload";
					$attc7_name = Misc::uploadFileUnsafely($tmpName7 , $pds_file7 , $board_pds_pos);
				}
			}

		}
		else
		{
			@unlink("./upload/{$row5['userfile7']}");
			$attc7_name['savedName'] = "";
		}
		if($photo_del8 != "1")
		{
			if(empty($_FILES['userfile8']['name']))
			{
			   $attc8_name['savedName'] = $row5['userfile8'];
			}
			else
			{
				$tmpName8 = $_FILES['userfile8']['tmp_name'];

				if(is_uploaded_file($tmpName8)){
					$pds_file8 = $_FILES['userfile8']['name'];
					$board_pds_pos = "./upload";
					$attc8_name = Misc::uploadFileUnsafely($tmpName8 , $pds_file8 , $board_pds_pos);
				}
			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile8']}");
			$attc8_name['savedName'] = "";
		}
		if($photo_del9 != "1")
		{
			if(empty($_FILES['userfile9']['name']))
			{
			   $attc9_name['savedName'] = $row5['userfile9'];
			}
			else
			{
				$tmpName9 = $_FILES['userfile9']['tmp_name'];

				if(is_uploaded_file($tmpName9)){
					$pds_file9 = $_FILES['userfile9']['name'];
					$board_pds_pos = "./upload";
					$attc9_name = Misc::uploadFileUnsafely($tmpName9 , $pds_file9 , $board_pds_pos);
				}
			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile9']}");
			$attc9_name['savedName'] = "";
		}
		if($photo_del10 != "1")
		{
			if(empty($_FILES['userfile10']['name']))
			{
			   $attc10_name['savedName'] = $row5['userfile10'];
			}
			else
			{
				$tmpName10 = $_FILES['userfile10']['tmp_name'];

				if(is_uploaded_file($tmpName10)){
					$pds_file10 = $_FILES['userfile10']['name'];
					$board_pds_pos = "./upload";
					$attc10_name = Misc::uploadFileUnsafely($tmpName10 , $pds_file10 , $board_pds_pos); // ← 정정
				}

			}
		}
		else
		{
			@unlink("./upload/{$row5['userfile10']}");
			$attc10_name['savedName'] = "";
		}


		///////////////////////////////////////////////////////////////////////////////////

        $fckcomment = addslashes($FCKeditor1);
		$qry2 = "update $tableName set  email = '$email', title='".addslashes($title)."',category='$cc', content='$fckcomment', html_check='$html_check', userfile1='{$attc1_name['savedName']}', userfile2='{$attc2_name['savedName']}' , userfile3='{$attc3_name['savedName']}' , userfile4='{$attc4_name['savedName']}' , userfile5='{$attc5_name['savedName']}' , userfile6='{$attc6_name['savedName']}' , userfile7='{$attc7_name['savedName']}' , userfile8='{$attc8_name['savedName']}' , userfile9='{$attc9_name['savedName']}' , userfile10='{$attc10_name['savedName']}' where seq_no = '$no'";
		$rst2 = mysql_query($qry2,$dbConn);

		if($rst2)
		{
			echo "<meta http-equiv='refresh' content='0; url=./board_view.php?division=$division&pdx=$pdx&sub=$sub&table_id=$table_id&board_mode=view&no=$no&start=$start'>";
			exit;
		}
        else
		{
			Misc::jvAlert('실패!','history.go(-1)');
		}
	}
	else if($board_mode == "cmt_save")
	{
		$comment=addslashes($comment);	
		$qry1 = "insert into chan_shop_boardcomment values ('','NY','$table_id','$no','$comment',now(),'{$member_info['user_id']}')";
		$rst1 = mysql_query($qry1,$dbConn);

		if($rst1)
		{
			echo "<meta http-equiv='refresh' content='0; url=./board_view.php?board_mode=view&table_id=$table_id&division=$division&no=$no&start=0&Mode=&how=&S_content='>";
			exit;
		}
        else
		{
			Misc::jvAlert('실패!','history.go(-1)');
		}

	}
	else if($board_mode == "cmt_del")
	{
		$qry1 = "delete from chan_shop_boardcomment where seq_no = '$seqNo'";
		$rst1 = mysql_query($qry1,$dbConn);

		if($rst1)
		{
			echo "<meta http-equiv='refresh' content='0; url=./board_view.php?board_mode=view&table_id=$table_id&division=$division&no=$no&start=0&Mode=&how=&S_content='>";
			exit;
		}
        else
		{
			Misc::jvAlert('실패!','history.go(-1)');
		}
	}
	else if($board_mode == "write")
	{
        // 쓰기처리
        $str_query=mysql_query("select max(seq_no),min(fid) from $tableName",$dbConn);
        if(!$str_query)
		{
			Misc::jvAlert('고유값이 없습니다.','history.go(-1)');
		}
        $row = mysql_fetch_row($str_query);

        if($row[0]){
			$new_seq_no = $row[0] + 1;
		}
        else {
			$new_seq_no = 1;
		}

        if($row[1]){
			$new_fid = --$row[1];
		}
        else {
			$new_fid = -1;
		}


        // query insert
        $ip = $REMOTE_ADDR;
        $wdate = time();


		//$content = addslashes($content);

		$tmpName1 = $_FILES['userfile1']['tmp_name'];

		if(is_uploaded_file($tmpName1)){
			$pds_file1 = $_FILES['userfile1']['name'];
			$board_pds_pos = "./upload";
			$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
		}

		$tmpName2 = $_FILES['userfile2']['tmp_name'];

		if(is_uploaded_file($tmpName2)){
			$pds_file2 = $_FILES['userfile2']['name'];
			$board_pds_pos = "./upload";
			$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);
		}
		//////////////////////////////////////////////////////////////////////////////////////
		$tmpName3 = $_FILES['userfile3']['tmp_name'];

		if(is_uploaded_file($tmpName3)){
			$pds_file3 = $_FILES['userfile3']['name'];
			$board_pds_pos = "./upload";
			$attc_name3 = Misc::uploadFileUnsafely($tmpName3 , $pds_file3 , $board_pds_pos);
		}

		$tmpName4 = $_FILES['userfile4']['tmp_name'];

		if(is_uploaded_file($tmpName4)){
			$pds_file4 = $_FILES['userfile4']['name'];
			$board_pds_pos = "./upload";
			$attc_name4 = Misc::uploadFileUnsafely($tmpName4 , $pds_file4 , $board_pds_pos);
		}
		
		$tmpName5 = $_FILES['userfile5']['tmp_name'];

		if(is_uploaded_file($tmpName5)){
			$pds_file5 = $_FILES['userfile5']['name'];
			$board_pds_pos = "./upload";
			$attc_name5 = Misc::uploadFileUnsafely($tmpName5 , $pds_file5 , $board_pds_pos);
		}

		$tmpName6 = $_FILES['userfile6']['tmp_name'];

		if(is_uploaded_file($tmpName6)){
			$pds_file6 = $_FILES['userfile6']['name'];
			$board_pds_pos = "./upload";
			$attc_name6 = Misc::uploadFileUnsafely($tmpName6 , $pds_file6 , $board_pds_pos);
		}

		$tmpName7 = $_FILES['userfile7']['tmp_name'];

		if(is_uploaded_file($tmpName7)){
			$pds_file7 = $_FILES['userfile7']['name'];
			$board_pds_pos = "./upload";
			$attc_name7 = Misc::uploadFileUnsafely($tmpName7 , $pds_file7 , $board_pds_pos);
		}
		$tmpName8 = $_FILES['userfile8']['tmp_name'];

		if(is_uploaded_file($tmpName8)){
			$pds_file8 = $_FILES['userfile8']['name'];
			$board_pds_pos = "./upload";
			$attc_name8 = Misc::uploadFileUnsafely($tmpName8 , $pds_file8 , $board_pds_pos);
		}

		$tmpName9 = $_FILES['userfile9']['tmp_name'];

		if(is_uploaded_file($tmpName9)){
			$pds_file9 = $_FILES['userfile9']['name'];
			$board_pds_pos = "./upload";
			$attc_name9 = Misc::uploadFileUnsafely($tmpName9 , $pds_file9 , $board_pds_pos);
		}
		$tmpName10 = $_FILES['userfile10']['tmp_name'];

		if(is_uploaded_file($tmpName10)){
			$pds_file10 = $_FILES['userfile10']['name'];
			$board_pds_pos = "./upload";
			$attc_name10 = Misc::uploadFileUnsafely($tmpName10 , $pds_file10 , $board_pds_pos);
		}
        $fckcomment  = addslashes($FCKeditor1);
        $query = "insert into $tableName (
                                seq_no,
                                fid,
                                thread,
								area,
								tablename,
                                category,
                                userid,
                                name,
                                email,
                                title,
                                content,
                                userfile1,
								userfile2,
								userfile3,
								userfile4,
								userfile5,
								userfile6,
								userfile7,
								userfile8,
								userfile9,
								userfile10,
                                html_check,
                                passwd,
                                count,
                                ip,
                                vote,
                                reply_mail,
                                wdate
                                ) values (
                                '$new_seq_no',
                                '$new_fid',
                                'A',
								'$lang',
								'$table_id',
                                '$cc',
                                '{$user_dbinfo['userid']}',
                                '$user_name',
                                '$email',
                                '".addslashes($title)."',
                                '$fckcomment',
                                '{$attc_name1['savedName']}',
								'{$attc_name2['savedName']}',
								'{$attc_name3['savedName']}',
								'{$attc_name4['savedName']}',
								'{$attc_name5['savedName']}',
								'{$attc_name6['savedName']}',
								'{$attc_name7['savedName']}',
								'{$attc_name8['savedName']}',
								'{$attc_name9['savedName']}',
								'{$attc_name10['savedName']}',
                                '$html_check',
                                '$passwd',
                                0,
                                '$ip',
                                '$vote',
                                '$reply_mail',
                                now())";
		//echo $query;
		//exit;
		$result = mysql_query($query,$dbConn);


        if($result)
		{
			echo "<meta http-equiv='refresh' content='0; url=./board_list.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id'>";
			exit;
		}
        else
		{
			Misc::jvAlert('실패!','history.go(-1)');
		}
	}
	else if($board_mode == "reply")
	{
        $str1_query = "select thread,right(thread,1) from $tableName where fid=$fid and length(thread) = length('$thread')+1 and locate('$thread',thread)=1 order by thread desc limit 1";

        $result = mysql_query($str1_query,$dbConn);

        $rows = mysql_num_rows($result);

        if($rows)
		{
			$row = mysql_fetch_row($result);
			$thread_head = substr($row[0],0,-1);
			$thread_foot = ++$row[1];

			//print_r("1:".$thread_head."|".$thread_foot);
			//exit;

			$new_thread = $thread_head.$thread_foot;
		}
        else
		{
			$new_thread = $thread."A";


		}



        // query insert
        $ip = $REMOTE_ADDR;
        $wdate = time();

		$tmpName1 = $_FILES['userfile1']['tmp_name'];

		if(is_uploaded_file($tmpName1)){
			$pds_file1 = $_FILES['userfile1']['name'];
			$board_pds_pos = "./upload";
			$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
		}

		$tmpName2 = $_FILES['userfile2']['tmp_name'];

		if(is_uploaded_file($tmpName2)){
			$pds_file2 = $_FILES['userfile2']['name'];
			$board_pds_pos = "./upload";
			$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);
		}
        $fckcomment = addslashes($FCKeditor1);

        $query = "insert into $tableName (
                                seq_no,
                                fid,
                                thread,
								area,
								tablename,
                                category,
                                userid,
                                name,
                                email,
                                title,
                                content,
                                userfile1,
								userfile2,
                                html_check,
                                passwd,
                                count,
                                ip,
                                vote,
                                reply_mail,
                                wdate
                                ) values (
                                '',
                                '$fid',
                                '$new_thread',
								'',
								'$table_id',
                                '$cc',
                                '{$user_dbinfo['userid']}',
                                '$user_name',
                                '$email',
                                '".addslashes($title)."',
                                '$fckcomment',
                                '{$attc_name1['savedName']}',
								'{$attc_name2['savedName']}',
                                '$html_check',
                                '$passwd',
                                0,
                                '$ip',
                                '$vote',
                                '$reply_mail',
                                now())";

		
		$result = mysql_query($query,$dbConn);


        if($result)
		{
			echo "<meta http-equiv='refresh' content='0; url=./board_list.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id'>";
			exit;
		}
        else
		{
		    Misc::jvAlert('실패!','history.go(-1)');
		}

	}

	/**
	* 게시판 내용뿌려주기 함수
	*/
	function board_contentPrint(){

		
		global $dbConn,$page,$start,$total,$scale,$page_scale,$page_last,$result,$code,$tableName,$how,$S_date,$S_content, $mode,$page_total,$__COOKIE,$table_id,$board_config,$division,$choose_lang,$division,$user_dbinfo,$search,$pdx,$sub;
		
		if($mode == "search")
		{
		# how : 검색카테고리,
		# S_date : 검색일자.
		# category = 1 : 공지사항

				switch($how){

					case "1":
							$S_category = "name like '%$search%'";
							break;
					case "2":
							$S_category = "title like '%$search%'";
							break;
							break;
					case "3":
							$S_category = "content like '%$search%'";
							break;
					case "4":
							//$S_category = " seq_no like '%$search%'";
					        $S_category = " ((seq_no like '%$search%') || (name like '%$search%') || (title like '%$search%'))";
							break;			
				}


				$que = "select * from $tableName where tablename='$table_id' && $S_category order by fid asc,thread limit $start,$scale";
				
		}
		else
		{

				$que = "select * from $tableName where tablename='$table_id' order by fid asc,thread limit $start,$scale";

		
		}
		//echo $que;
		//exit;
		$page=floor($start/($scale*$page_scale));
		//echo $page;
		$result=mysql_query($que);
		$result_rows=mysql_num_rows($result);



		$total=mysql_affected_rows();
		$last=floor($total/$scale);

		/**
		* 페이징을 위한 토탈을 구한다.
		*/
		if($mode == "search")
		{
				$page_total_qry = mysql_query("select count(*) from $tableName where tablename='$table_id' && $S_category");
		}
		else
		{
				$page_total_qry = mysql_query("select count(*) from $tableName where tablename='$table_id'");
		}

		$page_total = mysql_result($page_total_qry,0,0);
		$page_last = floor($page_total/$scale);

		/**
		* 총 페이지수
		*/
		$total_page_num = ceil($page_total/$scale);

		$now_page_num = floor($start/$scale) + 1;

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
						//mysql_data_seek($result, $i);
						$row=mysql_fetch_array($result);
						if ($table_id != 80) {
							$row['title'] = Misc::cutLongString($row['title'], 32, $dot=true);
						}
						//Added by Ethan
						//if the message is to the current user, make title BLUE
						/*if ($user_dbinfo[userid]=="admin") {
							if (strpos($row[title], $user_dbinfo[kor_name])) {
								$row[title] = "<span style='color:#000000'>".$row[title]."</span>";
							}
						} else {
							if (strpos($row[title], substr($user_dbinfo[kor_name],2))) {
								$row[title] = "<span style='color:#000000'>".$row[title]."</span>";
							}
						}
						*/
						//$row[title] = "".$row[title]."</span>";
						$today = explode(" ",$row['wdate']);


						$img_url = _WEB_BASE_DIR;

						$yesterday1 = date("Y-m-d H:i:s",time()-86400);
						if($row['wdate'] > $yesterday1)
						{
							$new_icon = "<img src='./img/New2.gif'>";
						}
						else
						{
							$new_icon = "&nbsp;";
						}

						// reply 달기
						$spacer = "";
						$spacer = strlen($row['thread']) - 1;
						$space = "&nbsp;";
						//if($spacer > $reply_indent) $spacer = $reply_indent;					
						for($j = 0; $j < $spacer; $j++) {
							$space = $space . "&nbsp;";
						}
						if($spacer == "0")
						{
							$re_img = "";
						}
						else
						{
							$re_img = $space."&nbsp;&nbsp;<img src='./img/icon_re.gif' align=absmiddle>&nbsp;";
						}


						if($n%2 == "0")
						{ 
							$bgcolor="#F5F5F5"; 
						} 
						else 
						{ 
							$bgcolor="#FFFFFF"; 
						}


						$a_info = getinfo_dbMember($row['userid']);
						//Added by Ethan
						//if the message is posted by the current user, make user name BLUE
						if ($row['userid']==$user_dbinfo['userid']) {
							$row['name'] = "<span style='color:#000000'>".$row['name']."</span>";
						}
						//echo $row[title];
						$table_content="
							  <tr> "; 
								if ($S_category =="1") {
								$table_content=$table_content."
									<td align='center'><input type=checkbox name=seqNo[] value={$row['seq_no']}>&nbsp;<input type=checkbox name=fYn[] value={$row['front_yn']}></td>
									<td width=\"50\" align=\"center\" height=28>{$row['seq_no']}</td>
									<td>&nbsp;$re_img&nbsp;<a href=board_view.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id&no={$row['seq_no']}&start=$start&board_mode=view style='color:#000000'>{$row['title']}</a> $new_icon</td>";
								} else {

									$table_content=$table_content."
									<td align='center'><input type=checkbox name=seqNo[] value={$row['seq_no']}></td>
									<td width=\"50\" align=\"center\" height=28>{$row['seq_no']}</td>
									<td>&nbsp;$re_img&nbsp;<a href=board_view.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id&no={$row['seq_no']}&start=$start&board_mode=view style='color:#000000'>{$row['title']}</a> $new_icon</td>";
					
								}
								   $table_content=$table_content."  <td width=\"100\" align=\"center\"><a href=board_view.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id&no={$row['seq_no']}&start=$start&board_mode=view style='color:#000000'>{$row['name']}</a></td>
									<td width=\"150\" align=\"center\">{$row['wdate']}</a></td>
									<td width=\"50\" align=\"center\">{$row['count']}</td>
								  </tr>
								
							      

							";


					// table 뿌려주기
					echo $table_content;

				}
				$n--;
			}

        }
        else
        {
                echo "
                            <tr> 
                              <td align=center colspan=6 height=50>목록이 없습니다!</td>
                            </tr>
                ";
        }
    }//contentPrint function end
    function board_contentPrint1(){

		
		global $dbConn,$page,$start,$total,$scale,$page_scale,$page_last,$result,$code,$tableName,$how,$S_date,$S_content, $mode,$page_total,$__COOKIE,$table_id,$board_config,$division,$choose_lang,$division,$user_dbinfo,$search,$pdx,$sub;
		
		if($mode == "search")
		{
		# how : 검색카테고리,
		# S_date : 검색일자.
		# category = 1 : 공지사항

				switch($how){

					case "1":
							$S_category = "name like '%$search%'";
							break;
					case "2":
							$S_category = "title like '%$search%'";
							break;
							break;
					case "3":
							$S_category = "content like '%$search%'";
							break;
					case "4":
							//$S_category = " seq_no like '%$search%'";
					        $S_category = " ((seq_no like '%$search%') || (name like '%$search%') || (title like '%$search%'))";
							break;			
				}


				$que = "select * from $tableName where tablename='$table_id' && $S_category order by fid asc,thread limit $start,$scale";
				
		}
		else
		{

				$que = "select * from $tableName where tablename='$table_id' order by fid asc,thread limit $start,$scale";

		
		}
		//echo $que;
		//exit;
		$page=floor($start/($scale*$page_scale));

		$result=mysql_query($que);
		$result_rows=mysql_num_rows($result);



		$total=mysql_affected_rows();
		$last=floor($total/$scale);

		/**
		* 페이징을 위한 토탈을 구한다.
		*/
		if($mode == "search")
		{
				$page_total_qry = mysql_query("select count(*) from $tableName where tablename='$table_id' && $S_category");
		}
		else
		{
				$page_total_qry = mysql_query("select count(*) from $tableName where tablename='$table_id'");
		}

		$page_total = mysql_result($page_total_qry,0,0);
		$page_last = floor($page_total/$scale);

		/**
		* 총 페이지수
		*/
		$total_page_num = ceil($page_total/$scale);

		$now_page_num = floor($start/$scale) + 1;

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
						//mysql_data_seek($result, $i);
						$row=mysql_fetch_array($result);

						$row['title'] = Misc::cutLongString($row['title'], 32, $dot=true);
						//Added by Ethan
						//if the message is to the current user, make title BLUE
						if ($user_dbinfo['userid']=="admin") {
							if (strpos($row['title'], $user_dbinfo['kor_name'])) {
								$row['title'] = "<span style='color:#000000'>".$row['title']."</span>";
							}
						} else {
							if (strpos($row['title'], substr($user_dbinfo['kor_name'],2))) {
								$row['title'] = "<span style='color:#000000'>".$row['title']."</span>";
							}
						}

						$today = explode(" ",$row['wdate']);


						$img_url = _WEB_BASE_DIR;

						$yesterday1 = date("Y-m-d H:i:s",time()-86400);
						if($row['wdate'] > $yesterday1)
						{
							$new_icon = "<img src='./img/New2.gif'>";
						}
						else
						{
							$new_icon = "&nbsp;";
						}

						// reply 달기
						$spacer = "";
						$spacer = strlen($row['thread']) - 1;
						$space = "&nbsp;";
						//if($spacer > $reply_indent) $spacer = $reply_indent;					
						for($j = 0; $j < $spacer; $j++) {
							$space = $space . "&nbsp;";
						}
						if($spacer == "0")
						{
							$re_img = "";
						}
						else
						{
							$re_img = $space."&nbsp;&nbsp;<img src='./img/icon_re.gif' align=absmiddle>&nbsp;";
						}


						if($n%2 == "0")
						{ 
							$bgcolor="#F5F5F5"; 
						} 
						else 
						{ 
							$bgcolor="#FFFFFF"; 
						}

						$cate = codebaseName($row['category']);
						$a_info = getinfo_dbMember($row['userid']);
						//Added by Ethan
						//if the message is posted by the current user, make user name BLUE
						if ($row['userid']==$user_dbinfo['userid']) {
							$row['name'] = "<span style='color:#000000'>".$row['name']."</span>";
						}

						$table_content="
							  <tr> "; 
								if ($row['front_yn']=="Y"){
									$table_content=$table_content."
									<td align='center'><input type=checkbox name=seqNo[] class='chk'  value={$row['seq_no']} >&nbsp;</td>
									<td align='center'><input type=checkbox name=seqNo1[] value={$row['seq_no']} checked>&nbsp;</td>
									<td align='center'><input type=checkbox name=fYn[] value={$row['seq_no']}>&nbsp;</td>
									";
                                    
									
									$table_content .= "
									<td width=\"50\" align=\"center\" height=28>{$row['seq_no']}</td>
								
									<td>&nbsp;$re_img&nbsp;<a href=board_view.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id&no={$row['seq_no']}&start=$start&board_mode=view style='color:#000000'>{$row['title']}</a> $new_icon</td>";
								} else {

									$table_content=$table_content."
									<td align='center'><input type=checkbox name=seqNo[] class='chk'  value={$row['seq_no']}>&nbsp;</td>
									<td align='center'><input type=checkbox name=seqNo1[] value={$row['seq_no']}>&nbsp;</td>
									<td align='center'><input type=checkbox name=fYn[] value={$row['seq_no']} checked>&nbsp;</td>";
									
									$table_content .= "
									
									<td width=\"50\" align=\"center\" height=28>{$row['seq_no']}</td>
									
									<td>&nbsp;$re_img&nbsp;<a href=board_view.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id&no={$row['seq_no']}&start=$start&board_mode=view style='color:#000000'>{$row['title']}</a> $new_icon</td>";
								}
								   $table_content=$table_content."  <td width=\"100\" align=\"center\"><a href=board_view.php?division=8&pdx=$pdx&sub=$sub&table_id=$table_id&no={$row['seq_no']}&start=$start&board_mode=view style='color:#000000'>{$row['name']}</a></td>
									<td width=\"150\" align=\"center\">{$row['wdate']}</a></td>
									<td width=\"50\" align=\"center\">{$row['count']}</td>
								  </tr>
								
							      

							";


					// table 뿌려주기
					echo $table_content;

				}
				$n--;
			}

        }
        else
        {
                echo "
                            <tr> 
                              <td align=center colspan=10 height=50>목록이 없습니다!</td>
                            </tr>
                ";
        }
    }//contentPrint function end
	
		

        /**
        * 게시물 페이징
        */
        function board_pageNavigation(){

			global $page_total,$page,$start,$scale,$page_scale,$board_id,$page_last,$Mode,$S_date,$S_content,$how,$table_id,$division,$choose_lang,$division,$search,$pdx,$sub;
			
			$Parameter_value = "division=8&pdx=$pdx&sub=$sub&table_id=$table_id&how=$how&search=".urlencode($search);

			if($page_total>$scale) //검색 결과가 페이지당 출력수보다 크면
			{
				if($start+1>$scale*$page_scale)
				{
					$pre_start=$page*$scale*$page_scale-$scale;
					echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?start=0&$Parameter_value'>Previous</a></li>";
				//	echo "<a href='$PHP_SELF?start=0&$Parameter_value'><img src=\"../images/icon_left_arrow2.gif\" align=\"absmiddle\" border=0></a>&nbsp;";
				//	echo "<a href='$PHP_SELF?start=$pre_start&$Parameter_value'><img src=\"../images/arrow_left.gif\" align=\"absmiddle\" border=0></a>&nbsp;";
				}
				for($vj=0; $vj<$page_scale; $vj++)
				{
					$ln=($page * $page_scale+$vj)*$scale;
					$vk=$page*$page_scale+$vj+1;
					
					if($ln<$page_total)
					{
							if($ln!=$start)
							{
							echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?start=$ln&$Parameter_value'>$vk</a></li>";
							}
							else
							{
							echo "<li class='page-item'><a class='page-link' href=''><font color=blue>$vk</font></a></li>";
							}
					}
				}
				
				if($page_total>(($page+1)*$scale*$page_scale))
				{
					$tpage = ($page+1);
					$n_start=$tpage*$scale*$page_scale;
					
					$last_start=$page_last*$scale;
					echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?start=".$n_start."&$Parameter_value'>Next</a></li>";
					//echo "&nbsp;<a href='$PHP_SELF?start=$n_start&$Parameter_value'><img src=\"../images/arrow_right.gif\" align=\"absmiddle\" border=0></a></a>&nbsp;";
					//echo "<a href='$PHP_SELF?start=$last_start&$Parameter_value'><img src=\"../images/icon_right_arrow2.gif\" align=\"absmiddle\" border=0></a>";
				}
			}
        }// pageNavigation function end


		
?>
