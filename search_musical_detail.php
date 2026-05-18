<?php
    include "include/inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	//echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
    require_once('libs/nusoap.php');
	$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
	$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
	$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
	$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
	$client = new nusoap_client('BIWSSimple_WSDL.wsdl', 'wsdl',
							$proxyhost, $proxyport, $proxyusername, $proxypassword);
	$err = $client->getError();


	if ($err) {
		echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
	}
    if (isset($_GET['show_code'])) {
		$show_db = Print_ShowDetail_list($_GET['show_code']);
	} else {
		$show_db = Print_ShowDetail_list($show_code);
	}
    
	// 뮤지컬 인포
	if (isset($_GET['show_code'])) {
		$m_info = getMusicalBasic($_GET['show_code']);
	} else {
		$m_info = getMusicalBasic($show_code);
	}
	


	function Print_ShowDetail_list($show_code = false){

			global $client;

			// Doc/lit parameters get wrapped
			$param = array(
				'SaleTypesCode'         => 'F',
				'LastChangeDate'          => '2001-12-17T09:30:47.0Z',
				'ShowCodes'          => $show_code,
				'ShowCityCode'          => 'NYCA'
				
			   
			);
			$param = array(
			   'SaleTypesCode'         => 'F',
			   'DateBegins'          => $_POST['start_date'],
			   'DateEnds'          => $_POST['start_date'],
			   'OneShowCode'          => $_POST['mcode'],
			   'ShowCityCode'       =>  $_POST['mcity'], //NYCA
			   'AvailabilityType'	=> 'F',
			   'BestSeatsOnly'          => '',
			   'LastChangeDate'	=> '2001-12-17T09:30:47.0Z'
			   );
			//'ShowAddedDate'          => '1900-12-17T09:30:47.0Z'
			$headers = '
			<m:AuthHeader xmlns:m="http://tempuri.org/">
			<m:username>285578618</m:username>
			<m:password>W3#Q0xim</m:password>
			</m:AuthHeader>
			';


			$result = $client->call('ShowDetails', array('parameters' => $param),'','',$headers);
			// Check for a fault
			if ($client->fault) {
				echo '<h2>Fault</h2><pre>';
				print_r($result);
				echo '</pre>';
			} else {
				// Check for errors
				$err = $client->getError();
				if ($err) {
					// Display the error
					$data .= '<h2>Error</h2><pre>' . $err . '</pre>';
				} else {
					// Display the result
					//echo '<h2>Result</h2><pre>';
					//print_r($result);
					//echo '</pre>';

					$data = $result['ShowDetailsResult']['diffgram']['NewDataSet']['Table'];
					
				}

			return $data;
			}

	}
    
	

    


	function Print_Performances(){

			global $client,$SaleTypesCode,$ShowCodes,$ShowCityCode,$DateBegins,$DateEnds;

			
			$param = array(
				'SaleTypesCode'         => $SaleTypesCode,
				'DateBegins'          => $DateBegins,
				'DateEnds'          => $DateBegins,
				'ShowCodes'          => $ShowCodes,
				'ShowCityCode'          => $ShowCityCode,
				'AvailabilityType'          => '',
				'BestSeatsOnly'          => '',
				'LastChangeDate'          => '2001-12-17T09:30:47.0Z'
				);


			//'ShowAddedDate'          => '1900-12-17T09:30:47.0Z'
			$headers = '
			<m:AuthHeader xmlns:m="http://tempuri.org/">
			<m:username>285578618</m:username>
			<m:password>W3#Q0xim</m:password>
			</m:AuthHeader>
			';

			$result = $client->call('Performances', array('parameters' => $param),'','',$headers);
			// Check for a fault
			if ($client->fault) {
				echo '<h2>Fault</h2><pre>';
				print_r($result);
				echo '</pre>';
			} else {
				// Check for errors
				$err = $client->getError();
				if ($err) {
					// Display the error
					$data .= '<h2>Error1</h2><pre>' . $err . '</pre>';
				} else {
					// Display the result
					//echo '<h2>Result</h2><pre>';
					//print_r($result);
					//echo '</pre>';
					//exit;
					$musical_cnt = count($result['PerformancesResult']['diffgram']['NewDataSet']['Table']);

					if($musical_cnt>0)
					{
					//echo "Count:".$musical_cnt;

					$data .= "<tr><td width=150>공연일:</td><td width=450>$DateBegins</td></tr>";
					$data .= "<tr><td width=150>시간대</td><td>";

						for($i=0; $i<$musical_cnt; $i++)
						{
							
							$ProductCode = $result['PerformancesResult']['diffgram']['NewDataSet']['Table'][$i]['ProductCode'];
							$data .= $result['PerformancesResult']['diffgram']['NewDataSet']['Table'][$i]['ProductTime']."&nbsp;".$result['PerformancesResult']['diffgram']['NewDataSet']['Table'][$i]['Availability']."&nbsp;".$result['PerformancesResult']['diffgram']['NewDataSet']['Table'][$i]['BestSeats']."&nbsp;".$result['PerformancesResult']['diffgram']['NewDataSet']['Table'][$i]['Seats']."<br>";
							
						}

					$data .= "</td></tr>";
					
					}
					else
					{
						$data .= "<tr><td colspan=2>찾을수 없습니다.</td></tr>";
					}
					
				}

			return $data;
			}

	}


	function Print_PerformancesPOHPricesAvailability(){

			global $client,$SaleTypesCode,$ShowCodes,$ShowCityCode,$DateBegins,$DateEnds,$show_db,$m_info;

			// Doc/lit parameters get wrapped
			/*
			$param = array(
				'SaleTypesCode'         => 'F',
				'ShowCityCode'          => 'NYCA',
				'DateBegins'          => '2012-02-12',
				'DateEnds'          => '2012-02-13',
				'OneShowCode'          => 'MAMMAMIA',
				'AvailabilityType'          => 'F',
				'BestSeatsOnly'          => '1',
				'LastChangeDate'          => '2001-12-17'
				);
			*/
			//echo $DateBegins;
			//echo $DateEnds;

			$param = array(
				'SaleTypesCode'         => $SaleTypesCode,
				'DateBegins'          => $DateBegins,
				'DateEnds'          => $DateBegins,
				'OneShowCode'          => $ShowCodes,
				'ShowCityCode'          => $ShowCityCode,
				'AvailabilityType'          => 'F',
				'BestSeatsOnly'          => '',
				'LastChangeDate'          => '2001-12-17T09:30:47.0Z'
				);


			//'ShowAddedDate'          => '1900-12-17T09:30:47.0Z'
			$headers = '
			<m:AuthHeader xmlns:m="http://tempuri.org/">
			<m:username>285578618</m:username>
			<m:password>W3#Q0xim</m:password>
			</m:AuthHeader>
			';
            
			//print_r($m_info);
			//exit;
			$result = $client->call('PerformancesPOHPricesAvailability', array('parameters' => $param),'','',$headers);
			// Check for a fault
			if ($client->fault) {
				echo '<h2>Fault2</h2><pre>';
				print_r($result);
				echo '</pre>';
			} else {
				// Check for errors
				$err = $client->getError();
				if ($err) {
					// Display the error
					$data .= '<h2>Error2</h2><pre>' . $err . '</pre>';
				} else {
					// Display the result
					//echo '<h2>Result</h2><pre>';
					//print_r($result);
					//echo '</pre>';
                    //exit;

					if($result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet'])
					{

								$musical_cnt = @count($result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']);

								//echo $musical_cnt;



								if($result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['ProductId'])
								{
										$data .= "<tr><td width=15%>공연일:</td><td width=85% HEIGHT=35 bgcolor=#FFFFFF>&nbsp;$DateBegins</td></tr>";
										$data .= "<tr><td width=15%>시간대</td><td width=85% HEIGHT=35 bgcolor=#FFFFFF style=\"padding-left:5px\">";

										$ProductId = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['ProductId'];
										$ProductCode = trim($result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['ProductCode']);
										$ProductDate = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['ProductDate'];
										$ProductTime = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['ProductTime'];


										$Price = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['Price'];

										if($m_info['sale_flag'] == "flat")
										{
											$our_price = $Price + $m_info['m_rate'];
										}
										else
										{
											$our_price = $Price + (($Price*$m_info['m_rate'])/100);
										}


										//$reserveBtn = "[<a href=\"chan_musical_reserve.php?p_iid=$ProductId&p_code=$ProductCode&r_date=$ProductDate&r_time=$ProductTime&price=$Price\">선택하기</a>]";

										$show_db['ShowName'] = addslashes($show_db['ShowName']);

										$reserveBtn = "[<a href=\"javascript:go_musical('{$show_db['ShowName']}','$ProductId','$ProductCode','$ProductDate','$ProductTime','$Price','$our_price')\">선택하기</a>]";

										
										$data .= $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['ProductTime']."&nbsp;".$result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['Availability']."&nbsp;".$result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['BestSeats']."&nbsp;".$result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table']['Seats']."&nbsp;$".$Price."&nbsp;&nbsp;$reserveBtn<br>";

										$data .= "</td></tr>";
								}
								else
								{
										$data .= "<tr><td width=15%>공연일:</td><td width=85% HEIGHT=35 bgcolor=#FFFFFF>&nbsp;$DateBegins</td></tr>";
										$data .= "<tr><td width=15%>시간대</td><td width=85% HEIGHT=35 bgcolor=#FFFFFF style=\"padding-left:5px\">";

											for($i=0; $i<$musical_cnt; $i++)
											{
												
												$ProductId = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['ProductId'];
												$ProductCode = trim($result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['ProductCode']);
												$ProductDate = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['ProductDate'];
												$ProductTime = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['ProductTime'];


												$Price = $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['Price'];

												if($m_info['sale_flag'] == "flat")
												{
													$our_price = $Price + $m_info['m_rate'];
												}
												else
												{
													$our_price = $Price + (($Price*$m_info['m_rate'])/100);
												}


												//$reserveBtn = "[<a href=\"chan_musical_reserve.php?p_iid=$ProductId&p_code=$ProductCode&r_date=$ProductDate&r_time=$ProductTime&price=$Price\">선택하기</a>]";

												$show_db['ShowName'] = addslashes($show_db['ShowName']);

												$reserveBtn = "[<a href=\"javascript:go_musical('{$show_db['ShowName']}','$ProductId','$ProductCode','$ProductDate','$ProductTime','$Price','$our_price')\">선택하기</a>]";

												
												$data .= $result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['ProductTime']."&nbsp;".$result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['Availability']."&nbsp;".$result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['BestSeats']."&nbsp;".$result['PerformancesPOHPricesAvailabilityResult']['diffgram']['NewDataSet']['Table'][$i]['Seats']."&nbsp;$".$Price."&nbsp;&nbsp;$reserveBtn<br>";
												
											}

										$data .= "</td></tr>";
								}



					}
					else
					{
										$data .= "<tr><td width=15%>공연일:</td><td width=85% HEIGHT=35 bgcolor=#FFFFFF>&nbsp;$DateBegins</td></tr>";
										$data .= "<tr><td width=15%>시간대</td><td width=85% HEIGHT=35 bgcolor=#FFFFFF style=\"padding-left:5px\"> Sold Out";

										$data .= "</td></tr>";
					}


					
				}

				return $data;
			}

	}
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
           
			<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.css" rel="stylesheet" type="text/css" />
		<!-- timepicker -->
            <!-- <link rel="stylesheet" href="lib/timepicker/css/bootstrap-timepicker.css" /> -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css" />
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
		<!--<link id="link_theme" rel="stylesheet" href="css/green.css">-->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.11/css/all.css" integrity="sha384-p2jx59pefphTFIpeqCcISO9MdVfIm4pNnsL08A6v5vaQc4owkQqxMV8kg4Yvhaw/" crossorigin="anonymous">
		<!-- main styles -->
            <link rel="stylesheet" href="css/style.css" />
		<!-- purun css -->
			<link rel="stylesheet" href="css/purun.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
		<!-- favicon -->
            <link rel="apple-touch-icon" sizes="180x180" href="img/favi/apple-touch-icon.png">
			<link rel="icon" type="image/png" sizes="32x32" href="img/favi/favicon-32x32.png">
			<link rel="icon" type="image/png" sizes="16x16" href="img/favi/favicon-16x16.png">
			<link rel="manifest" href="/site.webmanifest">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.css">
			<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedcolumns/3.2.2/css/fixedColumns.dataTables.min.css"/>

        <!--[if lte IE 8]>
            <link rel="stylesheet" href="css/ie.css" />
        <!['endif']-->

        <!--[if lt IE 9]>
			<script src="js/ie/html5.js"></script>
			<script src="js/ie/respond.min.js"></script>
			<script src="lib/flot/excanvas.min.js"></script>
        <!['endif']-->  
		<!-- <script src="js/jquery.min.js"></script> -->
		<!-- <script src="js/jquery-migrate.min.js"></script> -->
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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.js"></script>
		<!-- timepicker -->
			<!-- <script src="lib/timepicker/js/bootstrap-timepicker.min.js"></script> -->
			<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>
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
			<script type="text/javascript"src="https://cdn.datatables.net/fixedcolumns/3.2.2/js/dataTables.fixedColumns.min.js"></script>	
		<!-- purun js -->
			<script src="js/purun.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- purun_lee js -->
			<script src="js/purun_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>
	</head>
<?php
  
	
   
?>


<body>
    <br />
	<br />
	<div id="contentwrapper" class="reservationDetailForm">
         
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약관리</a></li>
					<li>예약관리</li>
					<li>상품정보</li>
				</ul>
			</div>
		
			
			
			<div class="row">
				<div class="col-sm-12">
					
					<table width="98%" align=center border="1" cellspacing="1" bgcolor=#cccccc cellpadding="0" class="txt_12">
						<form name=search method=post action=<?= $PHP_SELF ?>>
						<input type=hidden name=Mode value="SEARCH">
						<input type=hidden name=show_code value="<?= $show_code ?>">
						<input type=hidden name=show_name value="<?= $show_code ?>">

							<tr>
								<td colspan=2 height=30 bgcolor=#f4f4f4>뮤지컬 검색</td>
							</tr>
							<tr>
								<td width=25% bgcolor=#f9f9f9>SaleTypesCode :</td>
								<td width=75% bgcolor=#FFFFFF><input type=radio name=SaleTypesCode value="F" checked>FIT <input type=radio name=SaleTypesCode value="G" >Groups </td>
							</tr>
							<tr>
								<td bgcolor=#f9f9f9>City :</td>
								<td bgcolor=#FFFFFF><select name=ShowCityCode><option value="NYCA">NewYork<option value="LASV">Las Vegas<option value="NYCS">NewYork Sports</select></td>
							</tr>
							<tr>
								<td bgcolor=#f9f9f9>Date :</td>
								<td bgcolor=#FFFFFF><input type=text name=DateBegins size=15 value="<?= $DateBegins; ?>" id="datepicker"> </td>
							</tr>
							<tr>
								<td bgcolor=#f9f9f9>Show Code :</td>
								<td bgcolor=#FFFFFF><input type=text name=ShowCodes size=10 value="<?= $show_code ?>"></td>
							</tr>
							<tr>
								<td bgcolor=#f9f9f9>Flag :</td>
								<td bgcolor=#FFFFFF><input type=radio name=search_flag value="2" checked> PricesAvailability </td>
							</tr>
							<tr>
								<td colspan=2 bgcolor=#FFFFFF align=center height=40><input type=submit value="이용날짜&가격 검색">&nbsp;&nbsp;<a href="javascript:history.go(-1)">Go Back</a></td>
							</tr></form>
						</table>
						<br><br>
						<?php if($Mode == "SEARCH"): ?>
						<table width="98%" align=center border="1" cellspacing="0" cellpadding="0" class="txt_12">
							<tr>
								<td colspan=2>&nbsp;&nbsp;&nbsp;&nbsp;<B>Available Time</B></td>
							</tr>
							<!-- <tr>
								<td>Search : <select name=city><option value="NYCA">NewYork<option value="LASV">Las Vegas</select>&nbsp;&nbsp;</td>
								<td></td>
							</tr> -->
							<tr>
								<td colspan=2>
									<table width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0" class="txt_12">
									<?php if($search_flag == "1"): ?>
									<?= Print_Performances(); ?>
									<?php else: ?>
									<?= Print_PerformancesPOHPricesAvailability(); ?>
									<?php endif; ?>
									</table>
								</td>
							</tr>
						</table>
						<br><br>
						<?php endif; ?>
						<table width="98%" align=center border="1" cellspacing="0" cellpadding="0" class="txt_12">
							<tr>
								<td colspan=2>
									<table width="100%" align=center border="1" cellspacing="1" bgcolor=#cccccc cellpadding="0" class="txt_12">
										<tr>
											<td width=15% bgcolor=#F9F9F9>Code</td>
											<td width=35% bgcolor=#FFFFFF><?= $show_db['ShowCode'] ?><?= $show_db['ProductId'] ?></td>
											<td width=15% bgcolor=#F9F9F9>Name</td>
											<td width=35% bgcolor=#FFFFFF><?= $show_db['ShowName'] ?></td>
										</tr>
										<tr>
											<td bgcolor=#F9F9F9>ShowAreaCode</td>
											<td bgcolor=#FFFFFF><?= $show_db['ShowAreaCode'] ?></td>
											<td bgcolor=#F9F9F9>ShowTypeCode</td>
											<td bgcolor=#FFFFFF><?= $show_db['ShowTypeCode'] ?></td>
										</tr>
										<tr>
											<td bgcolor=#F9F9F9>VenueName</td>
											<td bgcolor=#FFFFFF><?= $show_db['VenueName'] ?></td>
											<td bgcolor=#F9F9F9>StateCode</td>
											<td bgcolor=#FFFFFF><?= $show_db['StateCode'] ?></td>
										</tr>
										<tr>
											<td bgcolor=#F9F9F9>Address</td>
											<td bgcolor=#FFFFFF><?= $show_db['Address'] ?></td>
											<td bgcolor=#F9F9F9>City</td>
											<td bgcolor=#FFFFFF><?= $show_db['City'] ?></td>
										</tr>
										<tr>
											<td bgcolor=#F9F9F9>Synopsis</td>
											<td bgcolor=#FFFFFF colspan=3><?= $show_db['Synopsis'] ?></td>
										</tr>
										<tr>
											<td bgcolor=#F9F9F9>RatingCode</td>
											<td bgcolor=#FFFFFF><?= $show_db['RatingCode'] ?></td>
											<td bgcolor=#F9F9F9>RunTime</td>
											<td bgcolor=#FFFFFF><?= $show_db['RunTime'] ?></td>
										</tr>
										<tr>
											<td bgcolor=#F9F9F9>OpeningDate</td>
											<td bgcolor=#FFFFFF><?= $show_db['OpeningDate'] ?></td>
											<td bgcolor=#F9F9F9>FirstPreviewDate</td>
											<td bgcolor=#FFFFFF><?= $show_db['FirstPreviewDate'] ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						

					
					
				</div>
			</div>
			
						          
	

	</div>
  
    <script language="javascript">
	$(document).ready(function () {
	   $.ajaxSetup({async:false});
		pt.initReservationList()
		pt1.initProductDetailForm2()
		var dateToday = new Date()
		$('#datepicker').datepicker({
			format: "yyyy-mm-dd",
			autoclose: true,
			startDate: dateToday
		});

	});
		function total_sum(){
			
			tf = opener.document.product;

			// 최종 합계
			tf.last_total_amt.value = parseFloat(tf.total_adult_sum.value) + parseFloat(tf.total_baby_sum.value) + parseFloat(tf.total_child_sum.value) + parseFloat(tf.sum_airline.value) + parseFloat(tf.sum_hotel.value) + parseFloat(tf.sum_ticket.value) + parseFloat(tf.sum_pick.value) + parseFloat(tf.sum_send.value) + parseFloat(tf.sum_meal.value) + parseFloat(tf.sum_etc.value);

		}

		function go_musical(ShowName,ProductId,ProductCode,ProductDate,ProductTime,Price,our_price){

			var opform = opener.document.product;
			
			opform.h_name.value = ShowName;
			opform.musical_seqNo.value = ProductId;
			opform.h_code.value = ProductCode;
			opform.act_date.value = ProductDate;
			opform.act_time.value = ProductTime;
			opform.musical_price.value = Price;
			opform.musical_sale_price.value = our_price;

			window.close();
		}

		function status_choice(f)
		{
			//opener.location.replace('hotel_reserve_status.php?division=4&h_code=' + f.p_code.value);

			var opform = opener.document.product;

			opform.h_code.value = f.p_code.value;
			opform.h_name.value = f.p_name.value;

			window.close();
		}

		//$("#datepicker").datepicker({ dateFormat: "yy-mm-dd" }).val();
		
		//-->
		</script>
    </body>
</html>
