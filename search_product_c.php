<?php
	include "include/inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}



?>
<!DOCTYPE html>
<html>
    <head>
	   
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>푸른투어 인트라넷</title>
		
        <!-- Bootstrap framework -->
            <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
            <link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" />
            <link rel="stylesheet" href="css/normalize.css" />
        <!-- jQuery UI theme -->
            <link rel="stylesheet" href="lib/jquery-ui/css/Aristo/Aristo.css" />
        <!-- breadcrumbs -->
            <link rel="stylesheet" href="lib/jBreadcrumbs/css/BreadCrumb.css" />
        <!-- tooltips-->
            <link rel="stylesheet" href="lib/qtip2/jquery.qtip.min.css" />
		<!-- colorbox -->
            <link rel="stylesheet" href="lib/colorbox/colorbox.css" />
        <!-- code prettify -->
            <link rel="stylesheet" href="lib/google-code-prettify/prettify.css" />
        <!-- sticky notifications -->
            <link rel="stylesheet" href="lib/sticky/sticky.css" />
        <!-- aditional icons -->
            <link rel="stylesheet" href="img/splashy/splashy.css" />
		<!-- flags -->
            <link rel="stylesheet" href="img/flags/flags.css" />
        <!-- datatables -->
            <!-- <link rel="stylesheet" href="lib/datatables/extras/TableTools/media/css/TableTools.css"> -->
			<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.css"/>
            <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" />
        <!-- datepicker -->
            <!-- <link rel="stylesheet" href="lib/datepicker/datepicker.css" /> -->
            <link rel="stylesheet" href="lib/bootstrap-datepicker-1.6.4-dist/css/bootstrap-datepicker.min.css" />
		<!-- timepicker -->
            <!-- <link rel="stylesheet" href="lib/timepicker/css/bootstrap-timepicker.css" /> -->
            <link rel="stylesheet" href="lib/bootstrap-timepicker/css/bootstrap-timepicker.min.css" />
		<!-- clockpicker -->
            <link rel="stylesheet" href="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.css" />

        <!-- switch buttons -->
            <link rel="stylesheet" href="lib/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css" />

        <!-- font-awesome -->
            <link rel="stylesheet" href="img/font-awesome/css/font-awesome.min.css" />
        <!-- calendar -->
            <link rel="stylesheet" href="lib/fullcalendar/fullcalendar_gebo.css" />
			<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
        
		<!-- theme color-->
            <link rel="stylesheet" href="css/blue.css" id="link_theme" />

        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.11/css/all.css" integrity="sha384-p2jx59pefphTFIpeqCcISO9MdVfIm4pNnsL08A6v5vaQc4owkQqxMV8kg4Yvhaw/" crossorigin="anonymous">
		<!-- main styles -->
            <link rel="stylesheet" href="css/style.css" />
		<!-- paran css -->
			<link rel="stylesheet" href="css/paran.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
		<!-- favicon -->
            <link rel="shortcut icon" href="favicon1.ico" />
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.css">
		
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.0.1/jquery-migrate.min.js"></script>
		<script src="lib/jquery-ui/jquery-ui-1.10.0.custom.min.js"></script>

		<!-- touch events for jquery ui-->
			<script src="js/forms/jquery.ui.touch-punch.min.js"></script>
		<!-- easing plugin -->
			<script src="js/jquery.easing.1.3.min.js"></script>
		<!-- smart resize event -->
			<script src="js/jquery.debouncedresize.min.js"></script>
		<!-- js cookie plugin -->
			<script src="js/jquery_cookie_min.js"></script>
		<!-- main bootstrap js -->
			<script src="bootstrap/js/bootstrap.min.js"></script>
		<!-- bootstrap plugins -->
			<script src="js/bootstrap.plugins.min.js"></script>
		<!-- typeahead -->
			<script src="lib/typeahead/typeahead.min.js"></script>
		<!-- code prettifier -->
			<script src="lib/google-code-prettify/prettify.min.js"></script>
		<!-- sticky messages -->
			<script src="lib/sticky/sticky.min.js"></script>
		<!-- lightbox -->
			<script src="lib/colorbox/jquery.colorbox.min.js"></script>
		<!-- masked inputs -->
			<script src="js/forms/jquery.inputmask.min.js"></script>
		<!-- jBreadcrumbs -->
			<script src="lib/jBreadcrumbs/js/jquery.jBreadCrumb.1.1.min.js"></script>
		<!-- hidden elements width/height -->
			<script src="js/jquery.actual.min.js"></script>
		<!-- custom scrollbar -->
			<script src="lib/slimScroll/jquery.slimscroll.js"></script>
		<!-- fix for ios orientation change -->
			<script src="js/ios-orientationchange-fix.js"></script>
		<!-- to top -->
			<script src="lib/UItoTop/jquery.ui.totop.min.js"></script>
		<!-- mobile nav -->
			<script src="js/selectNav.js"></script>
		<!-- moment.js date library -->
			<script src="lib/moment/moment.min.js"></script>

		<!-- common functions -->
			<script src="js/pages/gebo_common.js"></script>

		<!-- multi-column layout -->
			<script src="js/jquery.imagesloaded.min.js"></script>
		<script src="js/jquery.wookmark.js"></script>
		<!-- responsive table -->
			<script src="js/jquery.mediaTable.min.js"></script>
		<!-- small charts -->
			<script src="js/jquery.peity.min.js"></script>
		<!-- charts -->
			<script src="lib/flot/jquery.flot.min.js"></script>
			<script src="lib/flot/jquery.flot.resize.min.js"></script>
			<script src="lib/flot/jquery.flot.pie.min.js"></script>
			<script src="lib/flot.tooltip/jquery.flot.tooltip.min.js"></script>
		<!-- calendar -->
			<script src="lib/fullcalendar/fullcalendar.min.js"></script>
		<!-- sortable/filterable list -->
			<script src="lib/list_js/list.min.js"></script>
			<script src="lib/list_js/plugins/paging/list.paging.min.js"></script>

		<!-- datepicker -->
			<!-- <script src="lib/datepicker/bootstrap-datepicker.min.js"></script> -->
			<script src="lib/bootstrap-datepicker-1.6.4-dist/js/bootstrap-datepicker.min.js"></script>
		<!-- timepicker -->
			<!-- <script src="lib/timepicker/js/bootstrap-timepicker.min.js"></script> -->
			<script src="lib/bootstrap-timepicker/js/bootstrap-timepicker.min.js"></script>
		<!-- clockpicker -->
			<script src="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.js"></script>

		<!-- switch buttons -->
			<script src="lib/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>
        <!-- datatables -->
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
			<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
            <script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

		<!-- paran js -->
			<script src="js/paran.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- paran_lee js -->
			<script src="js/paran_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
	</head>



<script language="javascript">
<!--
var prod_item = "";

function getchklimit(f)
{
	
   choice(f);
	
	
}
function choice(f)
{

	var opform = opener.document.all;

	opform.p_code.value = f.p_code.value;
	opform.p_name.value = f.p_name.value;
		
	window.close();
	
	
}

function choice_hotel(f)
{

	var opform = opener.document.product;
	/*
	opform.p_code.value = f.p_code.value;
	opform.p_name.value = f.p_name.value;

	opform.product_price_adult.value = f.normal_adult_price.value;
	opform.product_price_child.value = f.normal_child_price.value;
	opform.product_price_baby.value = f.normal_baby_price.value;
	*/

	opener.location.replace('hotel_batch_status.php?division=4&p_code=' + f.p_code.value);


	window.close();
}

function choice_carbatch(f)
{

	var opform = opener.document.product;

	

	opener.location.replace('car_batch.php?division=4&p_code=' + f.p_code.value + '&StartYMD=' + f.start_date.value);
	
	//opener.location.replace('car_batch.php?division=4&p_code=' + f.p_code.value);


	window.close();
}



function choice_guide(f)
{

	var opform = opener.document.product;
	
	opener.location.replace('guide_choose.php?division=4&p_code=' + f.p_code.value);


	window.close();
}




//-->
</script>

<body bgcolor="#FFFFFF" text="#464646" leftmargin="10" topmargin="10" marginwidth="10" marginheight="10">
			
		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="txt_12">
			<tr>
				<td valign="top">
				<br>
					<table class="table table-striped table-bordered table-condensed">
					
					<form action=<?= $PHP_SELF ?> method=post name=product >
					<input type=hidden name=mode value="SEARCH">
					<input type=hidden name=page value="<?= $page ?>">
					<input type=hidden name=totalCode value="<?= $totalCode ?>">
						<tr>
							<td colspan="2" align="left">&nbsp;&nbsp;상품타입
							<select name=tour_type>
							<option value="1" <?php if($p_type == "1") echo "selected"; ?>>Local
							<option value="2" <?php if($p_type == "2") echo "selected"; ?>>In-Bound
							<option value="5" <?php if($p_type == "5") echo "selected"; ?>>Out-Bound
							<option value="" >전체상품
							
							</select>
							&nbsp;&nbsp;
							검색어 : <input type=text name=customer_keyword size=32 class=form_box value="<?= $customer_keyword ?>">&nbsp;&nbsp;<input type=submit value="검색" class="form_box"></td>
						<tr></form>
							<td colspan="2" height="10"></td>
						</tr>
						<tr>
							<td colspan="2" align="center">
								
								<table class="table table-striped table-bordered table-condensed">

									<?php
									$i=1;
									

									if($customer_keyword)
									{
										$keyword_qry = "&& (p_code like '%$customer_keyword%' || p_name like '%$customer_keyword%')";
									}

									
									if($tour_type)
									{
										switch($tour_type)
										{
											case "1":
												$tour_type_qry = "&& p_type = '1'";
												break;
											case "2":
												$tour_type_qry = "&& p_type = '2'";
												break;
											case "5":
												$tour_type_qry = "&& p_type = '5'";
												break;
											
												
										}
									}


									

									$zip_qry1 = "select * from product_master where 1=1 $startWeek_qry $keyword_qry $tour_type_qry  order by p_type desc, p_name asc";
								
									$zip_rst1 = mysql_query($zip_qry1);
									?>
									<tr>
										<td width="10%" align="center" height="20" bgcolor="#E3E3E3">구분</td>
										<td width="15%" align="center" height="20" bgcolor="#E3E3E3">코드</td>
										<td width="35%" align="center" height="20" bgcolor="#E3E3E3">상품명</td>
										<td width="15%" align="center" height="20" bgcolor="#E3E3E3">출발일</td>
										
									</tr>
									

									<?php
									while($row = mysql_Fetch_assoc($zip_rst1)){ 

									// 요일날짜

									$week1 = array("0", "1", "2","3","4","5","6","9");
									$week2   = array("일","월", "화", "수","목","금","토","매일");

									$row['p_week'] = str_replace($week1, $week2, $row['p_week']);



									switch($row['p_type'])
									{
										case "2":
											$tour_type = "<font color=#000000>인바운드</font>";
											break;
										case "5":
											$tour_type = "<font color=green>아웃바운드</font>";
											break;
										case "1":
											$tour_type = "<font color=red>로컬</font>";
											break;
										
									}

									// 시작일 StartYMD + 추가일 $row[p_day_cnt] 
									$start_date = explode("-",$StartYMD);
									$add_date = $row['p_day']-1;

									$stop_date  = date("Y-m-d",mktime (0,0,0,$start_date[1]  , $start_date[2]+$add_date, $start_date[0]));	

							 
									?>


									<form name="form<?=$i?>" method='post'>
								
									<input type="hidden" name="p_code" value="<?=$row['p_code']?>">
									<input pattern="[A-Za-z]{1,25}" type="hidden" name="p_name" value="<?=htmlspecialchars($row['p_name'])?>">
									
									
									<input type=hidden name=page value="<?= $page ?>">
									<tr bgcolor="#FFFFFF"  style="cursor:pointer;" onMouseOver="this.style.backgroundColor='#E3E3E3'" onMouseOut="this.style.backgroundColor=''" <?php if($page == "guide"): ?>onclick="javascript:choice_guide(document.form<?=$i?>);"<?php elseif($page == "hotel"): ?>onclick="javascript:choice_hotel(document.form<?=$i?>);"<?php elseif($page == "carbatch"): ?>onclick="javascript:choice_carbatch(document.form<?=$i?>);"<?php else: ?>onclick="javascript:getchklimit(document.form<?=$i?>);"<?php endif; ?> >
										<td width="10%" align="left" height="28">&nbsp;<b><?=$tour_type?></b></td>
										<td width="15%" align="center" height="28"><?= $row['p_code']?></td>
										<td width="35%" height="20">&nbsp;&nbsp;<?=$row['p_name']?>  (<?= $row['p_day'] ?>일)
										<td width="15%">&nbsp;<?= $row['p_week'] ?></td>
										
										
									</tr>
									</form>
									<?php
									$i++;
									}

									if($i == "1")
									{
										echo "<tr><td colspan=5 bgcolor=#F6F6F6 height=50 align=center>검색결과가 없습니다.</td></tr>";
									}

									?>
								</table>
								
							</td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

</body>
</html>