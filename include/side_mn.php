<?php
    $new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
	$dates=date("Y-m-d", $new_date);

    $qry2 = "select max(id) as mxid from att_log where userid='{$user_dbinfo['userid']}' && date_format( login_date, '%Y-%m-%d' ) = '$dates'";
	
	$rst2 = mysql_query($qry2);
	$row0 = mysql_Fetch_assoc($rst2);
  
    $m_qry1 = "select * from att_log where userid='{$user_dbinfo['userid']}' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='{$row0['mxid']}' ";
	$m_rst1 = mysql_query($m_qry1);
	$m_row1 = mysql_fetch_assoc($m_rst1);
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
							
							<a href="logout.php" class="btn btn-primary btn-sm btng"><strong>로그아웃</strong></a>
						</li>
					</ul>
				</div>
                <div id="side_accordion" class="panel-group">
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

                </div>

                <div class="push"></div>
            </div>

           <!-- <i class="fas fa-circle" style="color:#70A415;font-size:28px;box-shadow: 1px 0 0 0 #fff;"></i>-->
        </div>
.
    </div>

	<script>
		$( document ).ready(function() {
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