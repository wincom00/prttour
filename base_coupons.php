<?php
	include "include/header.php";
	
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	if($Mode == "del")
	{
		$qry1 = "delete from coupons_tab  where idx= '$id'";
		$rst1 = mysql_query( $qry1) ;
	}
	if($mode == "alldel")
	{
		for($i=0; $i<count($seqNo1); $i++)
		{
		   $qry1 = "delete from coupons_tab  where idx= '$seqNo1[$i]'";
		   //echo $qry1;
		   //exit;
		   $rst1 = mysql_query($qry1) ;
		}

	}
   
	if($mode == "save")
	{
		if($extra_mode == "modify")
		{
			$qry1 = "";
			//print_r($qry1);
			$rst1 = mysql_query($qry1) ;
			unset($extra_mode);
		}
		else
		{
			
			
				for ( $i=1; $i <= $cnt; $i++ ) { 
					if ($cstring1=="") {
					
						$randnum = rand(1,100000000); 
						$randnum = "C".$cstring.$randnum;
						$sql = "select * from coupons_tab where 1=1 && c_code='$randnum' ";
						$rst1 = mysql_query($sql) ;
						$result_rows=mysql_num_rows($rst1);
						if ($result_rows > 0) {
							$randnum = rand(1,10000000); 
							$randnum = "C".$cstring.$randnum;
						}
					
						$code1 = $randnum;
						$todate = $c_exdate;
						$c_amt = $c_amt;
						$useyn = "N";
						$cadte = date('Y-m-d H:i:s');
					} else  {
                        $code1 = $cstring1;
						$todate = $c_exdate;
						$c_amt = $c_amt;
						$cadte = date('Y-m-d H:i:s');

					}
						$qry = " 
										insert into coupons_tab 
											(
											P_code,
											given_id, 
											c_code, 
											c_amt,
											c_cnt,
											ex_date, 
											issuer, 
											use_yn, 
											wdate
											)
											values
											(
											'$p_code',
											'',
											'$code1', 
											'$c_amt', 
											'$c_cnt', 
											'$todate', 
											'{$user_dbinfo['userid']}', 
											'$useyn', 
											'$cadte'
											) ";
					
													
						$rst1 = mysql_query($qry) ;
					 
			  }

			  //exit;
			
			  unset($extra_mode);
		}


	}

	
	if ($start == "") $start = 0;
// $total = 0;
	$scale = 40;
	$page = 0	;
	$page_total = 0;
	$page_scale = 10;
	$page_last = 1;

	function printCoupon(){
		 
		global $dbConn,$division,$start, $page_total, $scale, $page, $page_scale;
		$qry2 = "SELECT * FROM coupons_tab order by idx,ex_date ,issuer desc ";
		$rst2 = mysql_query($qry2) ;
		$page_total = mysql_num_rows($rst2);
		$page_last = ceil($page_total / $scale); 

		$qry1 = "select * from coupons_tab  order by idx desc LIMIT $start, $scale";
		$rst1 = mysql_query($qry1) ;

		 while ( $row1 = mysql_fetch_array( $rst1 ) ){
			$prodinfo= getProductMaster($row1['p_code']);
			if ($prodinfo['p_name'] == "") {
				 $p_name = "AA";
			} else {
				 $p_name = $prodinfo['p_name'];
			}
			echo "<tr bgcolor=#FFFFFF>
			<td align=center><input type=checkbox name=seqNo1[] value='{$row1['idx']}' ></td>
			<td align=center>{$row1['idx']}</td>
			<td>&nbsp;$p_name</td>
			<td align=center>{$row1['reserveCode']}</td>
			<td height=25>&nbsp;{$row1['c_code']}</td>
			<td align=right>&nbsp;{$row1['c_amt']} %</td>
			<td>&nbsp;{$row1['c_cnt']}</td>
			<td>&nbsp;{$row1['use_yn']}</td>
			<td>&nbsp;{$row1['wdate']}</td>
			<td align=center> <a href=\"javascript:del({$row1['idx']})\">삭제</a></td>
			</tr>";


		}

	}

	function board_pageNavigation(){
		  global $page_total, $page, $start, $scale, $page_scale, $page_last, $name, $search;

		  $Parameter_value = "division=5";

		  if($page_total>$scale) //검색 결과가 페이지당 출력수보다 크면
		  {
		  if($start+1>$scale*$page_scale)
				  {
				  $pre_start=$page*$scale*$page_scale-$scale;
				  echo "<a href='$PHP_SELF?start=0&$Parameter_value'><img src=\"../images/icon_left_arrow2.gif\" align=\"absmiddle\" border=0></a>&nbsp;";
				  echo "<a href='$PHP_SELF?start=$pre_start&$Parameter_value'><img src=\"../images/arrow_left.gif\" align=\"absmiddle\" border=0></a>&nbsp;";
				  }
		  for($vj=0; $vj<$page_scale; $vj++)
			  {
			  $ln=($page * $page_scale+$vj)*$scale;
			  $vk=$page*$page_scale+$vj+1;
			  
				  if($ln<$page_total)
				  {
						  if($ln!=$start)
						  {
						  echo "<a href='$PHP_SELF?start=$ln&$Parameter_value'> $vk </a>.</font>";
						  }
						  else
						  {
						  echo "[$vk].</font>";
						  }
				  }
			  }
		  if($page_total>(($page+1)*$scale*$page_scale))
				  {
				  $n_start=($page+1)*$scale*$page_scale;
				  $last_start=$page_last*$scale;
				  echo "&nbsp;<a href='$PHP_SELF?start=$n_start&$Parameter_value'><img src=\"../images/arrow_right.gif\" align=\"absmiddle\" border=0></a></a>&nbsp;";
				  echo "<a href='$PHP_SELF?start=$last_start&$Parameter_value'><img src=\"../images/icon_right_arrow2.gif\" align=\"absmiddle\" border=0></a>";
				  }
		  }
	}// pageNavigation function end


	

	
?>
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">고객관리</a>
					</li>
					<li>
						<a href="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">쿠폰등록</a>
					</li>
					
				</ul>
			</div>

			  <br>
			  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="frmcoupon" id="frmcoupon" method="post">
			  <input type=hidden name=mode id=mode value="save">
			 
			  <input type=hidden name=extra_mode value="<?= $extra_mode ?>">
			  <input type=hidden name=seq_no value="<?= $id ?>">
			  <table id="level4" class="table table-striped table-bordered table-condensed">
			  <script>
			    $(function() {
											
			       	 $('#c_exdate').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
						
					 });
							    		
				 });
				function submtp(){
					 
					/*  if($("#p_code").val() =="")
					  {
							alert('대상상품코드를 넣으세요!');
							$("#p_code").focus();
							return false;
					  }
					  */
					  if($("#c_cnt").val() =="")
					  {
							alert('여행인원을 넣으세요!');
							$("#c_cnt").focus();
							return false;
					  }
					  
					  if($("#cnt").val() =="")
					  {
							alert('쿠폰 발급갯수를 넣으세요!');
							$("#cnt").focus();
							return false;
					  }	
					 /* if($("#cstring").val() =="")
					  {
							alert('쿠폰포함 문자열을 넣으세요!');
							$("#cstring").focus();
							return false;
					  }	
					  */
					  if($("#c_amt").val() =="")
					  {
							alert('쿠폰금액을 넣으세요!');
							$("#c_amt").focus();
							return false;
					  }	
					  if($("#c_exdate").val() =="")
					  {
							alert('유효기간을 넣으세요!');
							$("#c_exdate").focus();
							return false;
					  }
					  
					  $( "#frmcoupon" ).submit();
					  //$("#frmcoupon").submit():
					  
				}
				function searchProduct(){

						
						customer_keyword = document.all.p_code.value;
										

						window.open("search_product_c.php","customer","scrollbars=yes,width=960,height=500,left=150,top=150");
			  }
			  </script>
			  
			    <tr bgcolor=#f9f9f9 height=28>
					<td width=15% align=center>상품선택</td>
					<td width=35% bgcolor=#FFFFFF colspan=3>&nbsp;<input type=text name=p_code id=p_code size=20 class='form_box' value="" style="font-weight:bold" >&nbsp;
					<a href="javascript:void(0);" onClick="searchProduct()"><img src='img/magnifier_zoom_in.png' align=absmiddle border=0></a>&nbsp;<input type=text name=p_name id=p_name size=50 class='form_box' value="" readonly>&nbsp;<font color=red>* CODE에 AA를 입력하시면 상품지정을 안합니다.</font></td>
					
				</tr>
				<tr bgcolor=#f9f9f9 height=28>
					<td width=15% align=center>여행인원</td>
					<td width=35% bgcolor=#FFFFFF >&nbsp;<input type=text name=c_cnt id=c_cnt size=20 class="form_box" >
					&nbsp;<font color=red></font> </td>
					<td width=15% align=center>쿠폰수동문자열 </td>
					<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=cstring1 id=cstring1 size=20 class="form_box" ></td>
				</tr>
					
				</tr>
				<tr bgcolor=#f9f9f9 height=28>
					<td width=15% align=center>쿠폰 발급갯수</td>
					<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=cnt id=cnt size=20 class="form_box" > </td>
					<td width=15% align=center>쿠폰포함 문자열 </td>
					<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=cstring id=cstring size=20 class="form_box" ></td>
				</tr>
				<tr bgcolor=#f9f9f9 height=28>
					<td width=15% align=center>쿠폰할인율</td>
					<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=c_amt id=c_amt size=20 class="form_box" value=""></td>
					<td width=15% align=center>유효기간</td>
					<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=c_exdate id=c_exdate size=20 class="form_box" value="" autocomplete='false'></td>
				</tr>
				
				<tr>
					<td  colspan=4 height=35 bgcolor=#FFFFFF align=center><input type=button value="발급" class="form_box" Onclick="submtp();">&nbsp;<input type=button value="삭제" class="form_box" Onclick="alldel();"></td>
					
				</tr>
			  </table>
			  <br>
			<script>
				function del(id){
					
					if(confirm("삭제할까요?") == true)
					{
						location.replace('base_coupons.php?Mode=del&id=' + id);
					}
					else return;
				}
				function GoCheckAll() {

                   var FormChkObj = document.getElementsByName("seqNo1[]"); 
				  
				   for (var orderidx = 0; orderidx < FormChkObj.length; orderidx++) {

                       if (FormChkObj['orderidx'].checked==true) {
						  FormChkObj['orderidx'].checked=false;

				       } else {
                          FormChkObj['orderidx'].checked=true;
					   }

				   }
				}
				function alldel(){
					
					if(confirm("삭제할까요?") == true)
					{
						$("#mode").val("alldel");

						$("#frmcoupon").submit();
					}
					else return;
				}
			</script>
			  <table id="level4" class="table table-striped table-bordered table-condensed">
				<tr bgcolor=#f4f4f4 height=28>
				    <td width=5% align=center><input type=checkbox onclick="GoCheckAll();" ></td>
					<td width=5% align=center>순번</td>
					<td width=20% align=center>상품명</td>
					<td width=10% align=center>사용 예약번호</td>
					<td width=15% align=center>쿠폰코드</td>
					<td width=10% align=center>쿠폰할인율</td>
					<td width=5% align=center>여행인원</td>
					<td width=5% align=center>사용여부</td>
					<td width=15% align=center>발급일자</td>
					<td width=10% align=center>삭제</td>
				</tr>
			  <?php printCoupon(); ?>
			    <tr>
				 <td colspan=10 bgcolor=#f4f4f4 align=center> &nbsp; </td>
				</tr>
			    <tr>
				 <td colspan=10 align=center> <?php board_pageNavigation(); ?> </td>
				</tr>
			  </table>
		</form>
	 </div>
</div>
<?php
		include "include/side_m.php"
	?>
    
>