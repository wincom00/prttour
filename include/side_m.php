<?php
    $new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
	$dates=date("Y-m-d", $new_date);

    // 로그인 사용자 정보는 전역(본체 header.php/inc_base.php가 준비한 값)을 먼저 상속한다.
    // 메일함처럼 header.php 를 함수 스코프에서 require 한 진입점에서는 지역 $user_dbinfo 가
    // 비어 있고 실제 값은 $GLOBALS['user_dbinfo'] 에만 들어 있다. 기본값을 먼저 채우면
    // 상속 조건이 죽어버리므로(빈 배열도 is_array 라 true), 상속을 반드시 먼저 수행한다.
    if ((!isset($user_dbinfo) || !is_array($user_dbinfo) || empty($user_dbinfo['userid']))
        && isset($GLOBALS['user_dbinfo']) && is_array($GLOBALS['user_dbinfo']) && !empty($GLOBALS['user_dbinfo']['userid'])) {
        $user_dbinfo = $GLOBALS['user_dbinfo'];
    }
    if (!isset($user_dbinfo) || !is_array($user_dbinfo)) {
        $user_dbinfo = array();
    }
    if (!isset($user_dbinfo['userid'])) {
        $user_dbinfo['userid'] = '';
    }
    if (!isset($user_dbinfo['kor_name'])) {
        $user_dbinfo['kor_name'] = '';
    }
    if ((!isset($division) || $division === '') && !empty($user_dbinfo['division'])) {
        $division = $user_dbinfo['division'];
    }
    if (!isset($division)) {
        $division = '';
    }
    if (!isset($pdx)) {
        $pdx = '';
    }
    if (!isset($sub)) {
        $sub = '';
    }
    if (!isset($table_id)) {
        $table_id = '';
    }
    $adminPluginPaths = array(
        dirname(__DIR__) . '/mailbox/plugin.php',
        dirname(dirname(__DIR__)) . '/mailbox/plugin.php',
    );
    foreach ($adminPluginPaths as $adminPluginPath) {
        if (file_exists($adminPluginPath)) {
            require_once $adminPluginPath;
            break;
        }
    }
    $mbxPluginState = function_exists('mbx_plugin_prepare_sidebar')
        ? mbx_plugin_prepare_sidebar(array(
            'request_path' => isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '',
            'accounts' => isset($accounts) && is_array($accounts) ? $accounts : null,
            'account' => isset($account) && is_array($account) ? $account : null,
            'folder' => isset($folder) ? $folder : null,
            'row' => isset($row) && is_array($row) ? $row : null,
            'unread' => isset($unread) && is_array($unread) ? $unread : null,
        ))
        : array('active' => false, 'hide_default_menu' => false, 'ready' => false);
    $m_row1 = array('status' => '', 'login_date' => '');
    if ($user_dbinfo['userid'] !== '') {
        $sideUserid = function_exists('mysql_real_escape_string') ? mysql_real_escape_string($user_dbinfo['userid']) : addslashes($user_dbinfo['userid']);
        $qry2 = "select max(id) as mxid from att_log where userid='{$sideUserid}' && date_format( login_date, '%Y-%m-%d' ) = '$dates'";
        $rst2 = mysql_query($qry2);
        $row0 = $rst2 ? mysql_Fetch_assoc($rst2) : null;
        if (is_array($row0) && isset($row0['mxid']) && $row0['mxid'] !== null && $row0['mxid'] !== '') {
            $mxid = (int)$row0['mxid'];
            $m_qry1 = "select * from att_log where userid='{$sideUserid}' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='{$mxid}' ";
            $m_rst1 = mysql_query($m_qry1);
            $m_row1 = $m_rst1 ? mysql_fetch_assoc($m_rst1) : $m_row1;
            if (!is_array($m_row1)) {
                $m_row1 = array('status' => '', 'login_date' => '');
            }
        }
    }

?>
<a href="javascript:void(0)" class="sidebar_switch on_switch bs_ttip" data-placement="auto right" data-viewport="body" title="Hide Sidebar">사이드바 안보이기</a>
    <div class="sidebar">
    
        <div class="sidebar_inner_scroll">
            <div class="sidebar_inner">
                <form action="#" class="input-group input-group-sm" method="post">
				   <div style="text-align:center;">
				   <?php  if ($m_row1['status']=="2") { ?>
							<button name=kkind id=kkind type="button" class="btn btn-primary btnatt text-center" value="1">
							   <span id="instat" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
							</button>
				   <?php } else  if ($m_row1['status']=="1") { ?>
							<button name=kkind id=kkind type="button" class="btn btn-primary btnatt" value="2">
							   <span id="instat">OUT <?=$m_row1['login_date']?></span>
							</button>

				   <?php } else  if ($m_row1['status']=="") { ?>
							<button name=kkind id=kkind type="button" class="btn btn-primary btnatt" value="1">
							   <span id="instat" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
							</button>
				   <?php }  ?>
					</div>
                </form>
				<br />
				
				<div class="sidebar_info">
					<ul class="list-unstyled">
						<li>
							<span class="act act-warning"><?=$user_dbinfo['kor_name']?></span>
							<strong>로그인 사용자</strong>
						</li>
						<li>
							<span class="act act-success" id="clock"></span>
							<strong>시간</strong>
						</li>
						<li align="center" style='padding-top :5px;'>
							
							<a href="/logout.php" class="btn btn-primary btn-sm btng"><strong>로그아웃</strong></a>
						</li>
					</ul>
				</div>
                <div id="side_accordion" class="panel-group">
                   <?php if (empty($mbxPluginState['hide_default_menu'])): ?>
				   <?php 
				          if ($table_id != "") { 
				              printLeftMenu_b($division,$user_dbinfo['userid'],$pdx,$sub,$table_id);
						  } else {
				              printLeftMenu($division,$user_dbinfo['userid'],$pdx,$sub);
						  }
                   		//echo date_default_timezone_get();
						//$date = new DateTime("now", new DateTimeZone(date_default_timezone_get()) );
                        //echo $date->format('Y-m-d H:i:s');
					?>
                   <?php endif; ?>
                   <?php if (function_exists('mbx_plugin_render_sidebar')) { mbx_plugin_render_sidebar($mbxPluginState); } ?>

                </div>

                <div class="push"></div>
            </div>

           <!-- <i class="fas fa-circle" style="color:#70A415;font-size:28px;box-shadow: 1px 0 0 0 #fff;"></i>-->
        </div>

    </div>

	<script>
		$( document ).ready(function() {
            <?php if (function_exists('mbx_plugin_render_sidebar_script')) { mbx_plugin_render_sidebar_script($mbxPluginState); } ?>
			$( ".btnatt" ).click(function() {
				//alert(gdatetime);
				//return;
			    $.getJSON("post_att.php?kind="+$(this).val()+"&gdate="+gdatetime, function(data1) {
					 $("#instat").html("");
					 $(".btnatt").val("");
					 $.each(data1, function(i,data2) {
						 
						
						 if (data2.status==1)
						 {
							 alert('IN 보고가 되었습니다.!!');
							 $("#instat").html("OUT "+data2.login_date+"");
							 $(".btnatt").val("2");
						 } else {
							 alert('OUT 보고가 되었습니다.!!');
							 $("#instat").html("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
							 $(".btnatt").val("1");

						 }
						 
						 

					 });
					


			   });
			});
		});

	</script>
