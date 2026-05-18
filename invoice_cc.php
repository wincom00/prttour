<?php
    include "include/inc_base.php";
    

?>
<!DOCTYPE html>
<html>
    <head>
	
	   <?php
	    if($mode!='down') {
             echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
		 } ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>푸른투어 인트라넷</title>
        
	</head>



<?php 
	$g_dbinfo = getinfo_dbMember($rand_id);
	//print_r($_POST);
	function custlist() {
		 global $dbConn,$s_date1,$s_date2,$_POST,$rand_id,$table_content1,$totpmt,$totpmt1,$totcnt;
		 extract($_POST);
		 //print_r($_POST[seqNo]);
		 //echo "!111";
		 for($i=0; $i<count($_POST['seqNo']); $i++)
		 {
			$s = $_POST['seqNo'][$i];
			
			$qry1 = "select a.seq_no as seqr,a.*,b.*
				from rand_company a, reserve_info b
				where a.reserveCode=b.reserveCode && a.part_id = '$rand_id'  && b.parent='MAIN'
				&& a.reserveCode='$sreserveCode[$s]' && a.seq_no = '$seq[$s]' 
				order by a.wdate desc";
			//echo $qry1;
			$rst1 = mysql_query($qry1,$dbConn);
			
			while($row1 = mysql_Fetch_assoc($rst1)){
				$recus = getReserveTrRepre($row1['reserveCode']);
				
				$sdate = $row1['stDate'];
				

				if (($row1['cur_amt'] == "") || ($row1['cur_amt'] == "0")) {
					$pamt = "$0";

					$pamt1 = "0";
				} else {
					$pamt = "${$row1['cur_amt']}";
					$pamt1 = "{$row1['cur_amt']}";
				}

				$pamtc = $row1['amt'] - $pamt1;
				$totpmt =$totpmt + $pamtc;
				$totpmt1 = $totpmt1 + $pamt1;
				$totcnt = $totcnt + $row1['p_cnt'];
				//echo $pmatc;
				echo "<tr>
							<td align='center' style='text-align: center;border: 1px solid #aaa;'>$sdate</td>
							<td align='center' style='text-align: center;border: 1px solid #aaa;'>{$row1['p_name']}</td>
							<td align='center' style='text-align: center;border: 1px solid #aaa;'>{$recus['traveler_nm']}</td>
							<td align='center' style='text-align: center;border: 1px solid #aaa;'>{$row1['p_cnt']}</td>
							<td align='right' style='text-align: center;border: 1px solid #aaa;'>$$pamtc</td>
							
							<td align='right' style='text-align: center;border: 1px solid #aaa;'>$pamt</td>
							
						</tr>";


			}

		 }

		$table_content1 = "<table id='level4' style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;'>
			<th>
				
				<td width=20% align=center style='text-align: center;border: 1px solid #aaa;'>총 인원수</td>
				<td width=13% align=center style='text-align: center;border: 1px solid #aaa;'>총 요청금액</td>
				<td width=13% align=center style='text-align: center;border: 1px solid #aaa;'>총 페이먼트</td>
				
			</th>
			<tr height=35 bgcolor=#FFFFFF>
				<td colspan=2 align=right style='text-align: center;border: 1px solid #aaa;'>$totcnt</td>
				<td width=13% align=right style='text-align: center;border: 1px solid #aaa;'><b>$$totpmt</b></td>
				
				<td width=13% align=right style='text-align: center;border: 1px solid #aaa;'><b>$$totpmt1</b></td>
			</tr>
			</table>";
			 
		    
	}
	
?>
<body>
    <br />
	<br />
	<div id="contentwrapper" class="reservationDetailForm">
         
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
					
						<div class="col-sm-12">
						    <form action="" name="frmName" method="post">
						      <input type="hidden" name="mode" value="send_email">
							  <input type="hidden" name="rand_id" value="<?=$rand_id?>">
							  <input type="hidden" name="s_date1" value="<?=$s_date1?>">
							  <input type="hidden" name="s_date2" value="<?=$s_date2?>">
                                
                                
									
										<legend class="guide-assign-border"><span class="pull-left small text-muted">행사고객현황</span></legend>
										
										<br/>
											<table class="table table-bordered table-condensed">
										
													<tr>
														<td width="10%" style="border: 1px solid #ddd" align='center'>업체명</td>
														<td width="40%" style="border: 1px solid #ddd" align='center'><?=$g_dbinfo['kor_name']?>
															
														</td>
														
													</tr>
													<!--<tr>
														<td width="10%" class="titletd text-center">픽업장소</td>
														<td width="40%" class="">	</td>
														<td width="10%" class="titletd text-center">가이드</td>
														<td width="40%" class="">									
														</td>
													</tr>-->
												
											</table>
											<br/>
											<table id="custom_table" style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;'>
												<thead>
													<tr>  
													  
													  <th align="center" style='text-align: center;border: 1px solid #aaa;' width='7%'>출발일</th>
													  <th align="center" style='text-align: center;border: 1px solid #aaa;' width='15%'>상품명</th>
													  <th align="center" style='text-align: center;border: 1px solid #aaa;' width='7%'>대표고객명</th>
													  <th align="center" style='text-align: center;border: 1px solid #aaa;' width='5%'>인원</th>
													  <th align="center" style='text-align: center;border: 1px solid #aaa;' width='10%'>요청금액</th> 
													  <th align="center" style='text-align: center;border: 1px solid #aaa;' width='10%'>페이먼트</th>
													  
													  
													 
													 
													</tr>
												</thead>
												<tbody>
													<?php custlist(); ?>
												</tbody>
											</table>
											<?php
												echo $table_content1;
											?>
											<table id="custom_table" style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;'>
											<tbody>
											   <tr>
												<td>{ADDINFO}</td>
											   </tr>
											   <tr>
												<td style='text-align:left;padding-left:50px'><b>
												    PLEASE MAKE THE CHECK or MONEY ORDER PAYABLE TO <font color='blue'>"PRT AGENCY"</font>.<br />  
													THANK YOU. <br /><br />          
													
													ADDRESS:324 BROAD AVE RIDGEFIELD NJ 07657<br />                      
													T: 201-778-4000 I F: 201-313-0890<br /><br />      
												    Bank Name      : CHASE BANK<br /> 
													Bank Address      : 188-190 MAIN ST FORT LEE, NJ07024<br />
													ABA #         : 021000021<br /><br />

													Beneficiary Name   : PRUNE WORLD INC  OR PRT AGENCY<br /> 
													Address      : 324 BROAD AVE RIDGEFIELD ,NJ07657<br />
													Account #      : 617168526<br />
													SWIFT CODE  CHASE BANK #CHASUS33</b></td>
											   </tr>
									        </tbody>
											</table>
							</form>
						</div>
					
				</div><!-- -->
			</div>                
	

	</div>
  
    
</html>