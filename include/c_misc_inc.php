<?php
if (file_exists(__DIR__ . '/remote_upload.php')) {
    require_once __DIR__ . '/remote_upload.php';
}
if (!function_exists('remote_sync_file')) {
    function remote_sync_file($p, $f) { return false; }
    function remote_detect_folder($p) { return null; }

    function remote_ftp_test(&$e='') { return true; }
}

/**
* class Misc
* 기타 유용한 것을 두서없이 만듦
* class 로 만드는 것이 대부분 비효율적이지만 api 문서 작성 위해 만듦.
* @access public
* @package util
*/

class Misc {
	
	
	/**
	* 쿠키 굽기	
	* 자바 스크립트 출력은 임시로 작성되어서 다시 코딩되어야함.
	* $expire 파라미터에 주의 :  현재시간부터 추가될 시간(초단위) 으로 설정한다.
	* 자바스크립트로 출력할때로 php로 사용할때가 다름.
	* @access public
	* @param stirng $name
	* @param string $value
	* @param int $expire 현재시간부터 쿠키가 소멸될 시간(초단위)
	* 
	*
	*/
	
 static function setCookie($name, $value = '', $expire = 0, $path = '', $domain= '', $secure = 0) {
          if (headers_sent()) {           // 이미 헤더가 출력되었으면 자바스크립트로 굽는다.
             			$value = urlencode($value);
                        $expire = $expire * 1000;               // 자바스크립트는 밀리세컨즈 단위
                        $optionString = '';
                        if (!empty($path)) {
                                $optionString .= ";path=$path";
                        }
                        if (!empty($domain)) {
                                $optionString .= ";domain=$domain";
                        }
                        if ($secure) {
                                $optionString .= ";secure=$secure";
                        }
                        if (!empty($optionString)) {
                                $optionJS = "document.cookie += \"$optionString\";";
                        }
                        echo "<Script Language='JavaScript'>
                                        document.cookie=\"$name={$value}\";";
                        if($expire !=0) {
                                echo "var today = new Date();
                                          var expire = new Date(today.getTime() + $expire);
                                          document.cookie +=\";expires=\" + expire.toGMTString();";

                        }
                        echo $optionJS;
                        echo "</Script>";

                } else {
                        if ($expire != 0) {
                                setcookie($name, $value, time()+$expire, $path, $domain, $secure);
                        } else {
                                setcookie($name, $value, $expire, $path, $domain, $secure);
                        }
                }
        }
	
	/** 
	* 전자우편 체크
	* @access public
	* @param string $email  체크할 전자우편 주소
	* @return boolean
	*/
	
	static function checkEmail($email) {
		if (!eregi("^[^@ ]+@[a-zA-Z0-9\-]+\.+[a-zA-Z0-9\-]", $email)) {
			return false;
  		}
  		/* 한글이 포함되었는지 체크 */
    	for($i = 1; $i <= strlen($email); $i++) {
			if ((Ord(substr("$email", $i - 1, $i)) & 0x80)) {
		    	return false;
			}
    	}
    	return true;
	}
	
	
	/**
	* java script alert 출력
	* 자바스크립트로 리턴값 없이 바로 사용자 브라우져로 출력한다.
	* @access public
	* @param string $msg 자바스크립트 alert()로 출력할 메세지
	* @param string $nextCmd alert()이후 자바스크립트 명령
	*/
	
	static function jvAlert($msg, $nextCmd = '') {
		echo "<meta http-equiv='Content-Type' content='text/html; charset=euc-kr'>
  				<Script Language='JavaScript'>
  					alert(\"$msg\");
  					$nextCmd;
  				</Script> \n";
	}	// end of function jvAlert
	
	
	
	/**
	* url redirect 헤더가 출력된경우와 아닌경우
	* 헤더가 출력된 경우 자바 스크립트로 처리
	* $replace가 true인 경우 자바스크립트 location.replace()함수 사용
	* @access public
	* @param $url	이동할 주소
	* @param $replace true일때 자바스크립트 location.replace()함수 사용
	*/
	
	static function redirectUrl($url, $replace = false) {
		
		if ($replace) {
			echo "<Script Language='JavaScript'>
					location.replace('{$url}');
				</Script>";	
		} else {
			if (headers_sent()) {
				echo "<Script Language='JavaScript'>
						location.href='{$url}';
					</Script>";				
			} else {
				header("Location:{$url}");
			}
		}	
	
	}
	
	// 파일 다운로드	
	static function fileDownload($fileDir='.', $fileName, $realFileName='') {
		global $HTTP_USER_AGENT;
		
		$fileDir = ereg_replace("/$", "", $fileDir);
		$fileDir .= '/';
		
		if (empty($realFileName)) {
			$realFileName = $fileName;
		}
		 
		$download_file_name=$fileDir.$fileName;
    	$download_file_size=filesize($download_file_name);
	    $ie_v=ereg_replace("^.+MSIE ","",$HTTP_USER_AGENT);
    	$ie_v=strtok($ie_v,';');
     
     	if($ie_v<5.0) {			// 5.0 이하 ... IE 가 아닌 브라우져는 고려하지 않았슴.
       		$c_type="application/octet-stream";
       		$c_disp="attachment;";
      	}else{				
       		$c_type="application/octet-stream";	
       		$c_disp="inline";
     	}
      
     	$fp = fopen($download_file_name, 'r');
     	if (!$fp) {
     		return false;
     	} else {
     		$download_file=fread($fp, $download_file_size);
        	header("Content-type: $c_type"); //파일 타입이 file/unknown 일경우 무조건 다운로드 
     		header("Content-length: $download_file_size"); //파일의 크기 
     		header("Content-Disposition: $c_disp;filename=$realFileName"); //파일 이름에 원래 realname 을 적어주면 다운로드시 그 이름으로 다운 
     		header("Content-Transfer-Encoding: binary"); 
  
     		print $download_file; //파일의 실제 내용을 전송 
     		return true;
     	}	
	}
	
	
	
	
	
	// 파일업로드 확장자 바꿈
	// $attachFile : 임시저장 디렉토리 파일명
	// $attachFileName : 업로드시 파일이름.
 	static function uploadFile($attachFile, $attachFileName, $saveDir = '.'){
   		
   		$saveDir = ereg_replace("/$", "", $saveDir);
		$saveDir .= '/';
		
       	//$ext = date("YmdHis");
     	$saveFileName = $attachFileName;

     	/*
     	while (file_exists($saveDir . $saveFileName)) {
     		$ext++;
     		$saveFileName = $attachFileName . '.' . $ext;
     	}
       	*/

      	if(!is_dir($saveDir)){	// 파일 저장디렉토리가 존재하지 않으면
       		@mkdir($saveDir, 0755);
     	}
       	move_uploaded_file($attachFile, $saveDir . $saveFileName);
       	chmod($saveDir . $saveFileName, 0744);
     	$_rf = remote_detect_folder($saveDir); if ($_rf) remote_sync_file($saveDir . $saveFileName, $_rf);
       	$attc['size'] = filesize($saveDir . $saveFileName);		//byte 
     	$attc['savedName'] = $saveFileName;		// 저장되는 파일 이름
     	$attc['upName'] = $attachFileName;		// 업로드시 파일네임
  
     	return $attc;
 	}  
 
 	// 파일업로드 확장자 바꾸지 않음
 	static function uploadFileUnsafely($attachFile, $attachFileName, $saveDir = '.'){
   		
   		$saveDir = @ereg_replace("/$", "", $saveDir);
		$saveDir .= '/';
		
		/*
		$ext = strrchr($attachFileName, '.');
	 	$tName = substr($attachFileName, 0, strlen($attachFileName) - strlen($ext));
	 	$saveFileName = $tName . $ext;
     	$i = 0;
     	while (file_exists($saveDir . $saveFileName)) {
     		$i++;
     		$saveFileName =  $tName . $i . $ext;
      	}
		*/

		$saveFileName = $attachFileName;
       	
       	if(!is_dir($saveDir)){	// 파일 저장디렉토리가 존재하지 않으면
       		@mkdir($saveDir, 0777);
     	}
       	//echo $attachFile;
		//exit;
       	move_uploaded_file($attachFile, $saveDir . $saveFileName);
     	$_rf = remote_detect_folder($saveDir); if ($_rf) remote_sync_file($saveDir . $saveFileName, $_rf);

       //	chmod($saveDir . $saveFileName, 0744);
       	$attc['size'] = filesize($saveDir . $saveFileName);		//byte 
     	$attc['savedName'] = $saveFileName;		// 저장되는 파일 이름
     	$attc['upName'] = $attachFileName;		// 업로드시 파일네임
   
     	return $attc;
 	}  
 
  	// 파일업로드 확장자 바꿈
 	static function uploadFileUnsafely_change($attachFile, $attachFileName, $saveDir = '.'){
   		
   		$saveDir = @ereg_replace("/$", "", $saveDir);
		$saveDir .= '/';
		
		$ext = strrchr($attachFileName, '.');
	 	$tName = substr($attachFileName, 0, strlen($attachFileName) - strlen($ext));
	 	$saveFileName = $tName . $ext;
     	$i = 0;
     	while (file_exists($saveDir . $saveFileName)) {
     		$i++;
     		$saveFileName =  $tName . $i . $ext;
      	}

		//$saveFileName = $attachFileName;
       	
       	if(!is_dir($saveDir)){	// 파일 저장디렉토리가 존재하지 않으면
       		@mkdir($saveDir, 0755);
     	}
       	
       	move_uploaded_file($attachFile, $saveDir . $saveFileName);
     	$_rf = remote_detect_folder($saveDir); if ($_rf) remote_sync_file($saveDir . $saveFileName, $_rf);
       //	chmod($saveDir . $saveFileName, 0744);
       	$attc['size'] = filesize($saveDir . $saveFileName);		//byte 
     	$attc['savedName'] = $saveFileName;		// 저장되는 파일 이름
     	$attc['upName'] = $attachFileName;		// 업로드시 파일네임
   
     	return $attc;
 	}  
 
 
 	## 홈페이지 체크
 
 	static function checkUrl($url){
    	if (!eregi("[a-zA-Z0-9\-\.]+\.[a-zA-Z0-9\-\.]+.*", $url)) {
			return;
    	}
    	
    	$url = eregi_replace("^http.*://", "", $url);
    	$url = eregi_replace("^", "http://", $url);

    	return $url;
 	}
 
 
 
 	## 문자열 자르기
 	static function cutLongString($str, $length, $dot=false){
   
		$str = strip_tags($str); //Added by Ethan to removed any HTML tags.
   		if (strlen($str) <= $length){
     		return $str;
    	}else{
     		$k=0;
     		for ($i=0; $i<$length*2; $i++) {
     	    	if( ord(substr($str, $i, 1)) > 127) {		## 한글포함
        			$i++;
         			$k++;
        		}else{
         			$k++;
       			}
       			if ($k >= $length)
         			break;
     		}
    		if ($dot) {
    			return substr($str, 0, $i+1)." ..";
    		} else {
       			return substr($str, 0,$i+1);
     		} 
      
   		}
 	}

	static function cutLongStringWithTag($str, $length, $dot=false){

		$str1 = strip_tags($str);
		$dblByteChar = 0;
		for ($i = 0; $i < strlen($str1); $i++) {
			if (ord(substr($str1, $i, 1)) > 127) {
				$i++;
				$dblByteChar++;
			}
		}
		if ((strlen($str1) - $dblByteChar) <= $length){
			return $str;
		} else {
			$k = 0;
			$i = 0;
			$brCounter = 0;
			while (($k <= $length) && ($i < strlen($str))) {
				if (substr($str, $i, 1) == '<') {
					$brCounter++;
				} elseif (substr($str, $i, 1) == '>') {
					$brCounter--;
				} elseif ($brCounter == 0) {
					if( ord(substr($str, $i, 1)) > 127) {		## 한글포함
						$i++;
					}
					$k++;
				}
				$i++;
			}

			if ($dot) {
				return substr($str, 0, $i) . " ..";
			} else {
				return substr($str, 0, $i);
			} 
	  
		}
	}

		// 간단 html 메일 보내기
	
	static function sendSimpleMail($mailTo, $mailFrom, $subject, $message) {
				 
		$mailHeader = "from:{$mailFrom}\n";
		$mailHeader .= "Return-Path:{$mailFrom}\n";
		$mailHeader .= "Reply-To:{$mailFrom}\n";
		$mailHeader .= "MIME-Version:1.0\n";
		$mailHeader .= "Content-Type:text/html\n";
	
		$flag = mail($mailTo, $subject, $message, $mailHeader); 
		return flag;
	}

	
	// 디렉토리내에 디렉토리와 파일 리스트 리턴
	// $arrEntry[dir] : 디렉토리배열, $arrEntry[file] : 파일배열
	static function getDirectoryEntry($dir = '') {
		if (empty($dir)) {			// 주어진 디렉토리가 없을경우 현재 디렉토리
			$dir = '.';
		}
		$dir = ereg_replace("/$", '', $dir);
		$objDir = dir($dir);			// 디렉토리 읽어오기
		$arrEntry['dir'] = array();		// 디렉토리저장
		$arrEntry['file'] = array();		// 파일저장
		while ($entry = $objDir->read()) {
			if (is_dir("{$dir}/{$entry}") && ($entry != '.' && $entry != '..')) {
				$arrEntry['dir'][] = $entry;
			} else {
				$arrEntry['file'][] = $entry;
			}
		}
		
		return $arrEntry;
	}

	



	// 직렬화.... 디비에 넣을 경우는 꼭 addSlashes()를 하여 넣는다.
	static function serialize(&$mix) {
		$serMix = serialize($mix);
		$serMix = addslashes($serMix);

		return $serMix;
	}

	// 역직렬화.... 디비에서 불러올때는 stripslashes() 한다.
	static function unserialize(&$mix) {
		//$stripMix = stripslashes($mix);
		$unserMix = unserialize($mix);

		return $unserMix;
	}


	
	
	/**
	* magic_quotes 설정이 off 일경우 addslashes()로 "\"를 붙인다.
	*/

	static function getQueryStringWithMagicQuote($value) {
		if (get_magic_quotes_gpc() == 0) {
			$value = addslashes($value);
		}
		return $value;
	}

	
	static function getFileExtension($fileName, $toLower = false) {
		$ext = strrchr($fileName, '.');
		$ext = substr($ext, 1);
		if ($toLower) {
			$ext = strtolower($ext);
		}
	
		return $ext;
	}
	
	
	
	
}	// end of class Misc

?>
