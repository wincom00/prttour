<?php
	
   ini_set('display_errors', 1);
   ini_set('display_startup_errors', 0);
   error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
   
	ob_start();
	require_once __DIR__ . "/php74_82_compat.php";
	date_default_timezone_set('America/NEW_YORK');
    $WEB_BASE_PATH = "http://myprt.biz";
    if (!defined('UPLOAD_URL')) define('UPLOAD_URL', 'https://myprt.org/upload/');
    if (!defined('PRODUCT_IMG_URL')) define('PRODUCT_IMG_URL', 'https://myprt.org/product_img/');
	$baseDir = str_replace('\\', '/', rtrim(realpath(dirname(__DIR__)), '/\\'));
	$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
	define('_BASE_DIR', $baseDir);
	define(
		'_WEB_BASE_DIR',
		($documentRoot !== '' && strpos($baseDir, $documentRoot) === 0)
			? substr($baseDir, strlen($documentRoot))
			: ''
	);
	header('X-Robots-Tag: noindex, nofollow');
	
	if (is_array($_GET)) extract($_GET);
	if (is_array($_POST)) extract($_POST);
	if (is_array($_SERVER)) extract($_SERVER);
	if (is_array($_COOKIE)) extract($_COOKIE);
	if (is_array($_FILES)) extract($_FILES); 
	foreach (['_GET','_POST','_COOKIE'] as $sg) {
	  foreach ($$sg as $k => $v) {
		if (preg_match('/^[A-Za-z_]\w*$/', $k) && !isset($GLOBALS[$k])) {
		  $GLOBALS[$k] = $v;
		}
	  }
	}
    
	include __DIR__ . "/dbconn.php";
	include __DIR__ . "/c_misc_inc.php";
	include __DIR__ . "/func_list.php";
	include __DIR__ . "/purun_func.php";
	include _BASE_DIR."/PHPMailer/class.phpmailer.php";
	include _BASE_DIR."/ses.php";
    
	$pre_doamin =  $HTTP_HOST;
	$pre_doamin = str_replace("www.","",$pre_doamin);
	$c_domain = (in_array($pre_doamin, ['localhost', '127.0.0.1']) || strpos($pre_doamin, '_') !== false || preg_match('/\.(test|local|dev)$/', $pre_doamin)) ? '' : '.'.$pre_doamin;

	$G_Current_Url = $_SERVER["SCRIPT_URI"];
	$G_Current_Page = $_SERVER["REQUEST_URI"];	

	@extract($_GET);
	@extract($_POST);
	@extract($_SERVER);
	@extract($_COOKIE);
	//print_r($_COOKIE);
	//exit;
	//아이디가 있다면 회원정보 분석해라
	if( $_COOKIE['MEMLOGIN_ADMIN_PURUN'])
	{
		$user_info = getinfo_Member($_COOKIE['MEMLOGIN_ADMIN_PURUN']);
		$user_dbinfo = getinfo_dbMember($user_info['user_id']);
        
		
	} 
	
  
    
	
?>


