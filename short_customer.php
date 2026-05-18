<?php
    include "include//inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     
	$prodInfo = getProductMaster($p_code);
	$sctour = getTourInfo2($pcode,$st);
	function buslist() {
		global $dbConn,$pcode,$st,$num1,$gscode;
		
		
			$qry1 = "select 	
						grand_eCode, 
						sub_eCode, 
						reserveCode, 
						bus_num
						from tour_car 
						where stDate = '$st' && p_code = '$pcode'  group by bus_num order by bus_num asc";
     
		$rst1 = mysql_query($qry1,$dbConn);
		$num1 = mysql_num_rows($rst1);
		$k = 0;
		while($row1 = mysql_Fetch_assoc($rst1)){
			$s = $k+1;
			$rcnt = getReserveInfoRoom2($pcode,$st,$row1['sub_eCode']);
			//echo $rcnt[rcnt];
			if ($k == 0) {
				echo " <tr>
							<td colspan='16' class='active text-left formHeader'> 
								<div class='col-sm-12'>
									<div class='col-sm-4'>차량$s -{$row1['sub_eCode']} </div>
									<input type='hidden' name='gscode1' id='gscode1' value='{$row1['sub_eCode']}'>
									<div class='col-sm-3'>
										<div class='input-group input-group-sm'>       
										   <label class='radio-inline'>
										   ";
										   if ($gscode == $row1['sub_eCode']) {
										         echo "<input type='radio' name='roomnumber' value='$s' checked onClick='openwin(\"{$row1['grand_eCode']}\",\"{$row1['sub_eCode']}\",\"$pcode\",\"$st\")'>방갯수 : {$rcnt['rcnt']} 개";
										   } else {
											   echo "<input type='radio' name='roomnumber' value='$s'  onClick='openwin(\"{$row1['grand_eCode']}\",\"{$row1['sub_eCode']}\",\"$pcode\",\"$st\")'>방갯수 : {$rcnt['rcnt']} 개";

										   }
										  echo" </label>
										</div>
									</div>
								</div>    
							</td>    
						</tr>";
			
			} else {
				echo " <tr>
							<td colspan='16' class='active text-left formHeader'> 
								<div class='col-sm-12'>
									<div class='col-sm-4'>차량$s -{$row1['sub_eCode']} </div>
									<input type='hidden' name='gscode1' id='gscode1' value='{$row1['sub_eCode']}'>
									<div class='col-sm-3'>
										<div class='input-group input-group-sm'>       
										   <label class='radio-inline'>";
											if ($gscode == $row1['sub_eCode']) {
										         echo "<input type='radio' name='roomnumber' value='$s' checked onClick='openwin(\"{$row1['grand_eCode']}\",\"{$row1['sub_eCode']}\",\"$pcode\",\"$st\")'>방갯수 : {$rcnt['rcnt']} 개";
										   } else {
											   echo "<input type='radio' name='roomnumber' value='$s'  onClick='openwin(\"{$row1['grand_eCode']}\",\"{$row1['sub_eCode']}\",\"$pcode\",\"$st\")'>방갯수 : {$rcnt['rcnt']} 개";

										   }
										   echo "
										   </label>
										</div>
									</div>
								</div>    
							</td>    
						</tr>";


			}
			$k++;
		}
	
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

<style>
    /*div.dt-buttons {
        float: right; 
        padding-bottom: 10px;
    }*/
    
</style>
<body>
	<div id="contentwrapper" class="reservationDetailForm">
        
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정관리</a></li>
					<li>행사</li>
				</ul>
			</div>
		
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
					<div class="row">
						<div class="col-sm-12">
						    <form action="" name="frmName" method="post">
						      <input type="hidden" name="mode" value="down">
                              <fieldset class="guide-assign-border">
                                <legend class="guide-assign-border"><span class="pull-left small text-muted">행사차량현황</span></legend>
								
								<div class="col-sm-12" id='printarea'>
								  <div class="row">
									<div class="col-sm-12" style='overflow:auto; height:300px;'>
										<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
											<tbody>
												
												<?php buslist() ?>
												
											</tbody>
										</table>
									</div>
								  </div>
								</div>
                            </fieldset>
							</form>
						</div>
					</div>
				</div><!-- -->
			</div>                
	

	</div>
  
    <script>
		
		var ctr=0;
        function openwin(grand_eCode,s_code,p_code,st) { 
	       var winName = "all_"+(ctr++);
		   window.open("guide_assign_customer3.php??division=4&pdx=2&sub=30&grand_eCode="+grand_eCode+"&s_code="+s_code+"&p_code="+p_code+"&stdate="+st,"customer","width=1050,height=700,scrollbars=1");
	    }
      
	</script>
    </body>
</html>
