<?php

  include "include/inc_base.php";
  $reserveCode = $_GET['r_code'];
  $reInfo = getMusicalReserveSelfinfo($reserveCode);
  $apir = explode("@",$reInfo['api_result']);
  $ProdInfo = getMusicalBasic($_GET['m_code']);
?>


<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Voucher(바우쳐)</title>
	<style>
		body {
			font-family: Arial, verdana, sans-serif;
		}

		table,
		table tr {
			border: 1px solid #000;
			border-spacing: 0;

		}

		table tr td {
			padding: 5px;
		}

		table table {
			border: 0;
		}

		

		.text-left {
			text-align: left;
		}

		.text-center {
			text-align: center;
		}

		.text-right {
			text-align: right;
		}

		h5, h3 {
			margin: 0;
		}

		.foto {
			background-image: url("<?= UPLOAD_URL ?><?=$ProdInfo['userfile1']?>");
			background-color: #cccccc;
			height: 150px;
			margin: auto;
			width: 120px;
		}

		.voucher-code {
			border-top: 1px solid #000;
		}

		.voucher-titulo {
			border-bottom: 1px solid #000;
		}

		.voucher {
			width: 700px;
		}

		.box {
			border-top: 1px solid #000;
		}

		.box-bottom {
			border-bottom: 1px solid #000;
		}

		.box h5 {
			padding: 5px;
		}

		.logos img {
			margin-right: 5px;
		}

		.frente {
			border: 1px solid #000;
			width: 400px;
		}

		.frente table td {
			padding: 0;
		}

		.frente table td.dado {
			padding-bottom: 3px;
		}

		.frente .logos {
			float:left ;

		}

		.frente .titulo {
			float: right;
			text-align: center;
			width: 270px;
		}

		.frente .titulo h4 {
			margin: 0px;
		}

		.verso td h3 {
			background-color: #ddd;
			padding: 10px;
		}

		.footer {
			color: #777;
		}

	</style>
</head>
<body>
	<table celspacing="none" class="voucher">
		<tr>
			<td class="text-center"><h2>SHOW Voucher(공연바우쳐)</h2></td>
			<td class="logos" align="right">
				<img width="100" src="https://prttour.com/images/prt_logo2.png" alt="">
				
			</td>
		</tr>
		<tr>

		<tr>
			<td class="box" colspan="2">
				<h5>이 바우쳐는 티켓을 의미하는 것이 아닙니다</h5>
			</td>
		</tr>

		<tr>
			<td class="box"  colspan="2">
				<h5>이 바우쳐를 티켓매표소에서 신분증과 티켓을 교환하세요.</h5>
			</td>
		</tr>

		<tr>
			<td class="box box-bottom"  colspan="2">
				<h5><?=$reInfo['h_name']?>(<?=$reInfo['h_code']?>)</h5>
			</td>
		</tr>

		<tr>
			<td>
				<table class="frente">
					<tr>
						<td colspan="2">
							<div class="logos">
								<img width="80" src="https://prttour.com/images/prt_logo2.png" alt="">
								
							</div>
							<div class="titulo">
								<h4>
									<small>SHOW<br /> VOUCHER</small>
								</h4>
							</div>
						</td>
					</tr>
					<tr>
						<td valign="top">
							<div class="foto"></div>
						</td>
						<td>
							<table>
								<tr>
									<td>
										<h5>
											<small>Show name : </small>
										</h5>
									</td>
								</tr>
								<tr>
									<td class="dado">
										<h5>
											<small><?=$reInfo['h_name']?></small>
										</h5>
									</td>
								</tr>

								<tr>
									<td>
										<h5>
											<small>Product Code : </small>
										</h5>
									</td>

									<td>
										<h5>
											<small>Status : </small>
										</h5>
									</td>
								</tr>
								<tr>
									<td class="dado">
										<h5>
											<small><?=$reInfo['musical_seqNo']?></small>
										</h5>
									</td>

									<td class="dado">
										<h5>
											<small><?=$apir[1]?></small>
										</h5>
									</td>
								</tr>

								<tr>
									<td >
										<h5>
											<small>Show Time : </small>
										</h5>
										<h5>
											<small><?=$reInfo['act_date']?>   <?=$reInfo['act_time']?></small>
										</h5>
									</td>

									
								</tr>
								<tr>
									<td class="dado">
										<h5>
											<small>Seat : </small>
										</h5>
									</td>

									<td class="dado">
										<h5>
											<small><?=$apir[3]?></small>
										</h5>
									</td>
								</tr>

								<tr>
									<td width="140px">
										<h5>
											<small>Quantity</small>
										</h5>
									</td>

									<td>
										<h5>
											<small><?=$apir[5]?></small>
										</h5>
									</td>
								</tr>
								

								<tr>
									<td align="center" colspan="2">
										<br />
										<h5>푸른투어
										</h5>
									</td>
								</tr>
								
							</table>
						</td>
						
					</tr>
				</table>
				<td>
					<table class="verso">
						<tr>
							<td>
								<h3>APPOVE NUMBER<br/><?=$apir[0]?></h3>
							</td>
						</tr>

						<tr>
							<td align="center">
								<h5>
									<small>
										<small>
										승인번호는 실제티켓과 교환하는 번호이니<br /> 중요합니다!
										</small>
									</small>
								</h5>
							</td>
						</tr>

						
					</table>
				</td>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="footer">
				<h5>
					<small>
						<small>
						* 모든 쇼의 티켓은 예약 후 취소/환불/부분환불/좌석위치/좌석등급/ 날짜변경/ 시간변경이 불가능합니다.
						</small>
					</small>
				</h5>
			</td>
		</tr>

		<tr>
			<td colspan="2" class="footer">
				<h5>
					<small>
						<small>
						* 4세 미만 어린이는 입장 할 수 없습니다.
						</small>
					</small>
				</h5>
			</td>
		</tr>

		<tr>
			<td colspan="2" class="footer">
				<h5>
					<small>
						<small>
						* 핸드폰은 사용하실 수 없으며 입장 후에는 꺼 주시기 바랍니다.
						</small>
					</small>
				</h5>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="footer">
				<h5>
					<small>
						<small>
						* 4세 이상 어린이 티켓가격은 어른요금과 동일합니다.
						</small>
					</small>
				</h5>
			</td>
		</tr>
	</table>
</body>
</html>