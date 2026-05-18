<?php
    include "include/inc_base.php";
	header("Content-Type: text/html; charset=UTF-8");

	
	
	if($mode == "save")
	{
			//$division = 'admin';
			if(Member_login($userid,$passwd,$division))
			{
				$log_cnt=getinfo_dbExMember($userid);
				
				
				if ($log_cnt['log_cnt'] > 3 ) {
					Misc::jvAlert("패스워드가 3번 틀렸습니다. 유저아이디가 잠겼습니다.!","history.go(-1)");
				    exit;
				}
				$goUrl_1 = "index.php";
				$ex_date=getinfo_dbExMember($userid);
				//echo $goUrl_1;
				//exit;
				//echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
				$end = strtotime($ex_date['expire_date']);
				$now = time();
				$time = $now - $end;
	
				$on1 = floor($time / 86400);
				//echo $now."day:".$ex_date[expire_date]."day:".$on1 ;
				//exit;
				if ($log_cnt['passwd'] == "purun1") 
				{
					$goUrl_1 = "change_pass.php";
					$log_cnt=getinfo_dbExMember($userid);
					$cnt = $log_cnt['log_cnt'] + 1;
					if ($log_cnt['log_cnt'] > 3 ) {
							Misc::jvAlert("기본패스워드 정보를 바꾸지 않아 아이디가 잠겼습니다.","history.go(-1)");
							exit;
					}
					update_infolog($userid,$cnt);
					Misc::jvAlert("반드시 기본패스워드를 변경하셔야 합니다.","");
					echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					exit;
				}
				
				if($on1 >= 26)
				{
				  $resttime = $on1	- 26;
					if ($resttime > 6) {
						update_infolog($userid,4);
						Misc::jvAlert("Your Id is Locked !!","");
						
					}
					$goUrl_1 = "change_pass.php";
					update_infoinit2($userid);
					Misc::jvAlert("패스워드 변경주기입니다.변경해주세요!!","");
					echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					exit;
				}
				else
				{
					update_infoinit2($userid);
					
					$goUrl_1 = "index.php";
					echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					exit;
				}
			
			}
			else
			{
				$log_cnt=getinfo_dbExMember($userid);
				$cnt = $log_cnt['log_cnt'] + 1;
				
				if ($log_cnt['log_cnt'] > 3 ) {
					Misc::jvAlert("패스워드가 3번 틀렸습니다. 유저아이디가 잠겼습니다.!","history.go(-1)");
					
				}
				update_infolog($userid,$cnt);
				Misc::jvAlert("로그인 정보를 확인하세요!","history.go(-1)");
				exit;
			}
	

	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    

    <title>푸른투어 인트라넷 - Login Page</title>

    <!-- Bootstrap framework -->
            <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
        <!-- theme color-->
            <link rel="stylesheet" href="css/blue.css" />
        <!-- tooltip -->    
			<link rel="stylesheet" href="lib/qtip2/jquery.qtip.min.css" />
        <!-- main styles -->
            <link rel="stylesheet" href="css/style.css" />
    
        <!-- favicon -->
            <link rel="apple-touch-icon" sizes="180x180" href="img/favi/apple-touch-icon.png">
			<link rel="icon" type="image/png" sizes="32x32" href="img/favi/favicon-32x32.png">
			<link rel="icon" type="image/png" sizes="16x16" href="img/favi/favicon-16x16.png">
			<link rel="manifest" href="/site.webmanifest">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
		    <link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
		<!-- Custom styles -->
			<link href="css/style_login.css" rel="stylesheet">
			<link href="css/style-responsive.css" rel="stylesheet" />

<style>    
  #togglePassword {
  position: absolute;
  right: 3px;
  top: 40%;
  transform: translateY(-50%);
  cursor: pointer;
}
</style>
</head>

  <body class="login-img3-body">

    <div class="container">

      <form class="login-form" method='post' action="<?=$PHP_SELF ?>"> 
	  <input type=hidden name=mode value="save">
        <div class="login-wrap">
            <p class="login-img"><img src='img/t_logo.png'></p>
			
			<div class="input-group">
				<input type=radio name=division value="admin" checked> 직원&nbsp; &nbsp;&nbsp;<input type=radio name=division value="guide"> 가이드 
			</div>
				
            <div class="input-group">
              <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
              <input type="text" class="form-control" id="userid" name="userid" placeholder="사용자아이디" value="<?= $userid ?>" autofocus>
            </div>
            <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                <input type="password" class="form-control" name="passwd" id="password" placeholder="패스워드">
				<span class="input-group-addon"><i class="fa fa-eye" id="togglePassword"></i>
            </div></span>
            <label class="checkbox">
               <!-- <input type="checkbox" value="remember-me" <?= $save_id_status ?>> Remember me
                -->
            </label>
            <button class="btn btn-primary btn-bd btn-block" type="submit">로그인</button>
            
        </div>
      </form>

    </div>


  </body>
</html>
<script>
	// 1. 비밀번호 입력 필드와 눈 아이콘 요소를 가져옵니다.
const passwordInput = document.querySelector('#password');
const togglePasswordIcon = document.querySelector('#togglePassword');

// 2. 눈 아이콘에 클릭 이벤트 리스너를 추가합니다.
togglePasswordIcon.addEventListener('click', function() {
  // 3. 비밀번호 입력 필드의 type 속성 값을 가져옵니다.
  const type = passwordInput.getAttribute('type');

  // 4. type 속성 값이 'password'이면 'text'로, 'text'이면 'password'로 변경합니다.
  if (type === 'password') {
    passwordInput.setAttribute('type', 'text');
  } else {
    passwordInput.setAttribute('type', 'password');
  }

  // 5. 눈 아이콘의 클래스를 변경하여 아이콘 모양을 바꿉니다. (Font Awesome 사용 시)
  this.classList.toggle('fa-eye-slash');
});
</script>