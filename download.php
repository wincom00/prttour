<?php
	require_once __DIR__ . '/include/remote_upload.php';

	if (is_array($_GET)) extract($_GET);
	if (is_array($_POST)) extract($_POST);

	// 파일명 보안: 경로 조작 방지
	$filename = basename($filename ?? '');

	if (!$filename) {
		header('HTTP/1.0 404 Not Found');
		exit('파일명이 없습니다.');
	}

	// RFC 5987 한글/특수문자 파일명 인코딩
	$encodedName = rawurlencode($filename);
	$dispositionHeader = "attachment; filename=\"{$encodedName}\"; filename*=UTF-8''{$encodedName}";

	// 로컬 파일 경로 (본서버·개발서버 공통)
	$localDir  = rtrim(str_replace('\\', '/', __DIR__), '/');
	$filepath  = $localDir . '/upload/' . $filename;

	// 출력 버퍼가 살아있으면 바이너리 전송 전 전부 비우기
	while (ob_get_level() > 0) { ob_end_clean(); }

	if (file_exists($filepath) && filesize($filepath) > 0) {
		// 로컬에 파일 있음 → 직접 서브
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: " . $dispositionHeader);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($filepath));
		header("Pragma: no-cache");
		header("Expires: 0");
		readfile($filepath);
		exit;
	}

	// 본서버(FTP_HOST와 동일)인데 로컬에 없으면 자기 자신에게 FTP 할 이유 없음
	if (remote_is_primary_host()) {
		error_log("[download.php] Not found on primary host: {$filepath}");
		header('HTTP/1.0 404 Not Found');
		exit('파일을 찾을 수 없습니다.');
	}

	// 개발/서브 서버 → FTP로 본서버에서 파일 가져와 전송
	$ftpUrl = 'ftp://' . FTP_HOST . ':' . FTP_PORT
	        . FTP_BASEDIR . 'upload/' . rawurlencode($filename);
	$ch = curl_init($ftpUrl);
	curl_setopt_array($ch, [
		CURLOPT_USERPWD        => FTP_USER . ':' . FTP_PASS,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 30,
		CURLOPT_FTP_USE_EPSV   => false,
	]);
	$data  = curl_exec($ch);
	$errno = curl_errno($ch);
	$error = curl_error($ch);
	curl_close($ch);

	if ($data === false || $errno || $data === '') {
		error_log("[download.php] FTP fetch failed: errno={$errno} err={$error} url={$ftpUrl}");
		header('HTTP/1.0 404 Not Found');
		exit('파일을 찾을 수 없습니다.');
	}

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: " . $dispositionHeader);
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . mb_strlen($data, '8bit'));
	header("Pragma: no-cache");
	header("Expires: 0");
	echo $data;
	exit;
