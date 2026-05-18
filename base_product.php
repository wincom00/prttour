
<?php
    include "include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }

	// 선택된 p_code 목록을 이스케이프해서 IN절용 문자열 생성
	function _buildInList($seqNo, $pcode) {
		global $dbConn;
		$codes = array();
		foreach ((array)$seqNo as $s) {
			if (isset($pcode[$s])) {
				$codes[] = "'" . mysql_real_escape_string($pcode[$s], $dbConn) . "'";
			}
		}
		return implode(',', $codes);
	}

	function _normalizeProductSortDir($sortDir) {
		return (strtolower($sortDir) === 'desc') ? 'desc' : 'asc';
	}

	function _getProductOrderBy($sortCol, $sortDir) {
		$sortDir = _normalizeProductSortDir($sortDir);
		$numericPos = "CASE WHEN REPLACE(IFNULL(pos, ''), ',', '') REGEXP '^[0-9]+$' THEN CAST(REPLACE(pos, ',', '') AS UNSIGNED) ELSE 999999999 END";
		$numericDay = "CASE WHEN REPLACE(IFNULL(p_day, ''), ',', '') REGEXP '^[0-9]+$' THEN CAST(REPLACE(p_day, ',', '') AS UNSIGNED) ELSE 999999999 END";
		$priceExpr = "CASE WHEN p_day = '1' THEN REPLACE(IFNULL(price_0dadult, ''), ',', '') ELSE REPLACE(IFNULL(price_2dadult, ''), ',', '') END";
		$priceFlag = "CASE WHEN $priceExpr REGEXP '^[0-9]+(\\\\.[0-9]+)?$' THEN 0 ELSE 1 END";
		$priceValue = "CASE WHEN $priceExpr REGEXP '^[0-9]+(\\\\.[0-9]+)?$' THEN CAST($priceExpr AS DECIMAL(12,2)) ELSE 0 END";

		switch ($sortCol) {
			case 'pos':
				return "$numericPos $sortDir, p_code asc";
			case 'region':
				return "c_code1 $sortDir, c_code2 $sortDir, p_code asc";
			case 'p_name':
				return "p_name $sortDir, p_code asc";
			case 'p_day':
				return "$numericDay $sortDir, p_code asc";
			case 'end_yn':
				return "end_yn $sortDir, p_code asc";
			case 'm_dept':
				return "m_dept $sortDir, p_code asc";
			case 'display_price':
				return "$priceFlag asc, $priceValue $sortDir, p_code asc";
			case 'p_code':
			default:
				return "p_code $sortDir, p_name asc";
		}
	}

	function _renderProductSortLabel($label, $sortKey) {
		global $_sortCol, $_sortDir;

		if ($_sortCol === $sortKey) {
			return $label . ' ' . ($_sortDir === 'asc' ? '&#9650;' : '&#9660;');
		}

		return $label;
	}

	function _buildProductListQuery($extra = array()) {
		global $division, $pdx, $sub, $ty;

		$params = array(
			'division' => $division,
			'pdx' => $pdx,
			'sub' => $sub,
			'ty' => $ty,
			'page' => max(1, intval($_REQUEST['page'] ?? 1)),
			'sort_col' => isset($_REQUEST['sort_col']) && $_REQUEST['sort_col'] !== '' ? $_REQUEST['sort_col'] : 'region',
			'sort_dir' => isset($_REQUEST['sort_dir']) && strtolower($_REQUEST['sort_dir']) === 'desc' ? 'desc' : 'asc',
			'search' => isset($_REQUEST['search']) ? $_REQUEST['search'] : ''
		);

		foreach ((array)$extra as $key => $value) {
			$params[$key] = $value;
		}

		return http_build_query($params);
	}

	if ($mode1 =="save") {
		// pos는 행마다 다른 값 → CASE WHEN으로 단일 쿼리
		$cases = '';
		$inList = array();
		foreach ((array)$seqNo as $s) {
			if (!isset($pcode[$s])) continue;
			$pc  = mysql_real_escape_string($pcode[$s], $dbConn);
			$pv  = mysql_real_escape_string($pos[$s], $dbConn);
			$cases  .= " WHEN '$pc' THEN '$pv'";
			$inList[] = "'$pc'";
		}
		if ($cases !== '') {
			$inStr = implode(',', $inList);
			mysql_query("UPDATE product_master SET pos = CASE p_code $cases END WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("저장 완료!!!");
	}
	if ($mode1 =="gsave") {
		$inStr = _buildInList($seqNo, $pcode);
		if ($inStr) {
			$gv = mysql_real_escape_string($grp, $dbConn);
			mysql_query("UPDATE product_master SET grp='$gv' WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("그룹지정 완료!!!");
	}

	if ($mode1 =="bsave") {
		$inStr = _buildInList($seqNo, $pcode);
		if ($inStr) {
			$bv = mysql_real_escape_string($bgcolor, $dbConn);
			mysql_query("UPDATE product_master SET bgcolor='$bv' WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("배경색지정 완료!!!");
	}
	if ($mode1 =="esave") {
		$inStr = _buildInList($seqNo, $pcode);
		if ($inStr) {
			$ev = mysql_real_escape_string($endyn, $dbConn);
			mysql_query("UPDATE product_master SET end_yn='$ev' WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("마감지정 완료!!!");
	}
	if ($mode1 =="ddsave") {
		$inStr = _buildInList($seqNo, $pcode);
		if ($inStr) {
			$dv = mysql_real_escape_string($disyn, $dbConn);
			mysql_query("UPDATE product_master SET dis_yn='$dv' WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("할인대상여부지정 완료!!!");
	}
	if ($mode1 =="tsave") {
		$inStr = _buildInList($seqNo, $pcode);
		if ($inStr) {
			$tv = mysql_real_escape_string($p_display, $dbConn);
			mysql_query("UPDATE product_master SET p_display='$tv' WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("노출지정 완료!!!");
	}
	if ($mode1 =="scbsave") {
		$inStr = _buildInList($seqNo, $pcode);
		if ($inStr) {
			$sv = mysql_real_escape_string($scsel, $dbConn);
			mysql_query("UPDATE product_master SET sc_grp='$sv' WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("지역그룹지정 완료!!!");
	}

	if ($mode1 =="pbsave") {
		$inStr = _buildInList($seqNo, $pcode);
		if ($inStr) {
			$pv = mysql_real_escape_string($psel, $dbConn);
			mysql_query("UPDATE product_master SET p_cate='$pv' WHERE p_code IN ($inStr)", $dbConn);
		}
		Misc::jvAlert("인기지역지정 완료!!!");
	}
	if($Mode == "del")
	{
		$qry1 = "delete from product_master where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_details where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);
		
		$qry1 = "delete from product_limit where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn); 

		$qry1 = "delete from product_details_local where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_pick where p_code= '$pcode'";
		$rst1 = mysql_query($qry1,$dbConn);

		$_returnQuery = _buildProductListQuery();
		$_returnUrl = './base_product.php?' . $_returnQuery;

	}
	$_curPage = max(1, intval($_REQUEST['page'] ?? 1));
	$_perPage = 150;
	$_sortCol = isset($_REQUEST['sort_col']) && $_REQUEST['sort_col'] !== '' ? $_REQUEST['sort_col'] : 'region';
	$_sortDir = isset($_REQUEST['sort_dir']) ? _normalizeProductSortDir($_REQUEST['sort_dir']) : 'asc';

	function printProdut($ty) {
		global $dbConn, $division, $pdx, $sub, $search,$grp,$endyn,$scsel,$psel,$bgcolor,$p_display,$_curPage,$_perPage,$_sortCol,$_sortDir;

		$where = "1=1 && p_type='$ty'";
		if ($search)    $where .= " && (p_code like '%$search%' || p_name like '%$search%')";
		if ($grp)       $where .= " && grp='$grp'";
		if ($bgcolor)   $where .= " && bgcolor='$bgcolor'";
		if ($endyn)     $where .= " && end_yn='$endyn'";
		if ($p_display) $where .= " && p_display='$p_display'";
		if ($scsel)     $where .= " && sc_grp='$scsel'";
		if ($psel)      $where .= " && p_cate='$psel'";

		// 전체 건수
		$cntRst = mysql_query("select count(*) as cnt from product_master where $where", $dbConn);
		$totalCount = ($cntRst) ? (int)mysql_result($cntRst, 0, 'cnt') : 0;
		$totalPages = max(1, (int)ceil($totalCount / $_perPage));
		$_curPage   = min($_curPage, $totalPages);
		$offset     = ($_curPage - 1) * $_perPage;

		$orderBy = _getProductOrderBy($_sortCol, $_sortDir);
		$qry1 = "select p_code, p_name, pos, c_code1, c_code2, m_dept, base_rate, p_day, price_0dadult, price_2dadult, end_yn, r_rate
		         from product_master where $where order by $orderBy
		         LIMIT $offset, $_perPage";
		$rst1 = mysql_query($qry1,$dbConn);

		$k = $offset; // checkbox index는 전체 기준으로 유지
		while($row1 = mysql_Fetch_assoc($rst1)){
			$cinfo1=codebaseName($row1['c_code1']);
			$cinfo2=codebaseName($row1['c_code2']);
			$dept=codebaseName($row1['m_dept']);
			if ($row1['base_rate'] == "USD") {
				$sign = "U$";
			} else if ($row1['base_rate'] == "CAD") {
				$sign = "C$";
			} else {
				$sign = "";
			}
			if ($row1['p_day']==1) {
				$day = "당일";
				if ($row1['price_0dadult'] == "" || $row1['price_0dadult'] == "문의") $sign = "";
				$dprice = $row1['price_0dadult'];
			} else {
				if ($row1['price_2dadult'] == "" || $row1['price_2dadult'] == "문의") $sign = "";
				$dprice = $row1['price_2dadult'];
				$day = $row1['p_day'];
			}
			$endcap = ($row1['end_yn'] == "y") ? "<font color=red>상품마감</font>" : "상품진행중";
			$editUrl = 'base_product_m.php?' . _buildProductListQuery(array('pcode' => $row1['p_code']));
			echo "<tr bgcolor=#FFFFFF>
			    <td> <input type='checkbox' name='seqNo[]' value='$k' /></td>
				<td align=center><input type='hidden' name='pcode[$k]' value='{$row1['p_code']}'><input type=text name='pos[$k]' class='form-control text-right' value='{$row1['pos']}'></td>
				<td align=center>{$cinfo1['comment']}/{$cinfo2['comment']}</td>
				<td align=center>{$row1['p_code']}</td>
				<td align=left>&nbsp;{$row1['p_name']}</td>
				<td align=center>$day</td>
				<td align=center>$endcap</td>
				<td align=center>{$dept['comment']}</td>
				<td align=center>&nbsp;$sign $dprice</td>
				<td align=center style='white-space:nowrap;'><a href='{$editUrl}'>수정</a> | <a href=\"javascript:del('{$row1['p_code']}','$ty')\">삭제</a> | <a href=\"javascript:copy('{$row1['p_code']}','$ty')\">복사</a></td>
			</tr>";
			$k++;
		}

		// 페이징 UI - 테이블 바깥에서 출력하기 위해 전역변수에 저장
		global $_pagingHtml;
		$_pagingHtml = '';
		if ($totalPages > 1) {
			$startPage = max(1, $_curPage - 5);
			$endPage   = min($totalPages, $_curPage + 4);
			$_pagingHtml .= "<div class='text-center' style='margin:8px 0;'>";
			$_pagingHtml .= "<span style='margin-right:10px'>총 {$totalCount}건 / {$totalPages}페이지</span>";
			if ($_curPage > 1) {
				$_pagingHtml .= "<button type='button' class='btn btn-default btn-xs js-page' data-page='1'>&laquo;</button> ";
				$_pagingHtml .= "<button type='button' class='btn btn-default btn-xs js-page' data-page='" . ($_curPage-1) . "'>&lsaquo;</button> ";
			}
			for ($p = $startPage; $p <= $endPage; $p++) {
				$act = ($p == $_curPage) ? " btn-primary" : " btn-default";
				$_pagingHtml .= "<button type='button' class='btn{$act} btn-xs js-page' data-page='{$p}'>{$p}</button> ";
			}
			if ($_curPage < $totalPages) {
				$_pagingHtml .= "<button type='button' class='btn btn-default btn-xs js-page' data-page='" . ($_curPage+1) . "'>&rsaquo;</button> ";
				$_pagingHtml .= "<button type='button' class='btn btn-default btn-xs js-page' data-page='{$totalPages}'>&raquo;</button>";
			}
			$_pagingHtml .= "</div>";
		}
	}
	if ($ty == 1) {
        $pcap = "단일상품등록";
	} else if ($ty == 2) {
        $pcap = "복합상품등록";
	} else if ($ty == 3) {
        $pcap = "인바운드";
	} else if ($ty == 4) {
        $pcap = "인센티브";
	} else if ($ty == 5) {
        $pcap = "아웃바운드";
	}
	
?>
     
	<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">상품관리</a></li>
					<li><a href="#">상품등록</a></li>
					<li><?=$pcap?></li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<input type="hidden" name="sort_col" value="<?= htmlspecialchars($_sortCol, ENT_QUOTES) ?>">
						<input type="hidden" name="sort_dir" value="<?= htmlspecialchars($_sortDir, ENT_QUOTES) ?>">
						<table class="table table-striped table-bordered table-condensed js-prod1">
							<tbody>
							<tr>
								<td width=10%  class="titletd" style="vertical-align: middle;">검색어 </td>
								<td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="prod_code" name="search" class="inpubase lg" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"/></td>
								<td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								<td class="conttd"><a href='base_product_m.php?<?= htmlspecialchars(_buildProductListQuery(), ENT_QUOTES) ?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
							</tr> 
							</tbody>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>" enctype="multipart/form-data" id="frmprod"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="save">
					<input type="hidden" name="page" id="page" value="<?= $_curPage ?>">
					<input type="hidden" name="sort_col" id="sort_col" value="<?= htmlspecialchars($_sortCol, ENT_QUOTES) ?>">
					<input type="hidden" name="sort_dir" id="sort_dir" value="<?= htmlspecialchars($_sortDir, ENT_QUOTES) ?>">
					<input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
					<input type=hidden name=view_position value="">
					   <table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
								    <td width="5%" class="titletd text-center ">
									   <select class="form-control" name="displaydivi">
											<option value="" selected>- 지역선택 -</option>
											<option value="head" >본사</option>
											<option value="west" >미서부LA</option>
											<option value="las" >라스베가스</option>
											<option value="das" >달라스</option>
											<option value="sea" >시애틀</option>
											<option value="ats" >애틀란타</option>
											
											
											
										</select> </td>
									<td width="5%" class="titletd text-center ">
									   <select class="form-control" name="displaysel">
											<option value="" selected>- 진열선택 -</option>
											<option value="MAIN_BEST"  >[메인]BEST상품</option>
											<option value="MAIN_POP" >[메인]인기상품</option>
											
											<option value="CUSTOM" >[업체]추천상품</option>
											
											
											
										</select> </td>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-psave">일괄지정</button> </td>
									<td class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-usave">업데이트</button> </td>
								</tr> 
								<tr>
									<td width="5%" class="titletd text-center ">
										   <select name=grp class="form-control ">
											<option value="0">그룹선택
											<option value="1"> 그룹1
											<option value="2"> 그룹2
											<option value="3"> 그룹3
											<option value="4"> 그룹4
											<option value="5"> 그룹5
											<option value="6"> 그룹6
											<option value="7"> 그룹7
											<option value="8"> 그룹8
											<option value="9"> 그룹9
											<option value="10">그룹10
											<option value="11">그룹11
											<option value="12">그룹12
											<option value="13">그룹13
											<option value="14">그룹14
											<option value="15">그룹15
											<option value="16">그룹16
											<option value="17">그룹17
											<option value="18">그룹18
											<option value="19">그룹19
											<option value="20">그룹20
											<option value="21">그룹21
											<option value="22">그룹22
											<option value="23">그룹23
											<option value="24">그룹24
											<option value="25">그룹25
											<option value="26">그룹26
											<option value="27">그룹27
											<option value="28">그룹28
											<option value="29">그룹29
											<option value="30">그룹30
											<option value="31">그룹31
											<option value="32">그룹32
											<option value="33">그룹33
											<option value="34">그룹34
											<option value="35">그룹35
											<option value="36">그룹36
											<option value="37">그룹37
											<option value="38">그룹38
											<option value="39">그룹39
											<option value="40">그룹40
											<option value="41">그룹41
											<option value="42">그룹42
											<option value="43">그룹43
											<option value="44">그룹44
											<option value="45">그룹45
											<option value="46">그룹46
											<option value="47">그룹47
											<option value="48">그룹48
											<option value="49">그룹49
											<option value="50">그룹50
										</select>
									</td>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-gsave">그룹일괄지정</button> </td>
									<td class="conttd" colspan=2><button type='button' class="btn btn-primary btn-sm btn1 js-ssave">검색</button>
								</tr>
								<tr>
									<td width="5%" class="titletd text-center ">
									<input type="text" class="inpubase md" id="bgcolor" name="bgcolor" placeholder="그룹배경색" value=''>&nbsp;&nbsp;<a href="https://htmlcolorcodes.com/" target="_blank">배경색(#00000)</a>
									 </td>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-bsave">배경일괄지정</button> </td>
									<td class="conttd" colspan=2><button type='button' class="btn btn-primary btn-sm btn1 js-bgsave">검색</button> </td>
								</tr> 
								<tr>
						
									<td width=5% >
										<label class="radio-inline">
											<input type="radio" name="disyn" id="disyn" value="y" <?php if ($disyn == "y" ) echo "checked"; ?>> 할인비대상
										</label>
										<br />
										<label class="radio-inline">
											<input type="radio" name="disyn" id="disynn" value="n" <?php if ($disyn == "n" ) echo "checked"; ?>> 할인대상
										</label>
									</td>
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-ddsave">일괄지정</button> </td>
									<td class="conttd" colspan=2><button type='button' class="btn btn-primary btn-sm btn1 js-ddssave">검색</button>
								</tr>
								<tr>
						
									<td width=5% >
										<label class="radio-inline">
											<input type="radio" name="endyn" id="endyn" value="y" <?php if ($endyn == "y" ) echo "checked"; ?>> 상품마감
										</label>
										<br />
										<label class="radio-inline">
											<input type="radio" name="endyn" id="endynn" value="n" <?php if ($endyn == "n" ) echo "checked"; ?>> 상품진행중
										</label>
									</td>
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-esave">마감일괄지정</button> </td>
									<td class="conttd" colspan=2><button type='button' class="btn btn-primary btn-sm btn1 js-essave">검색</button>
								</tr>
								<tr>
						
									<td width=5% >
										<label class="radio-inline">
											<input type="radio" name="p_display" id="p_display" value="y" <?php if ($p_display == "y" ) echo "checked"; ?>> 바로노출
										</label>
										<br />
										<label class="radio-inline">
											<input type="radio" name="p_display" id="p_display" value="n" <?php if ($p_display == "n" ) echo "checked"; ?>> 임시저장
										</label>
									</td>
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-tsave">노출일괄지정</button> </td>
									<td class="conttd" colspan=2><button type='button' class="btn btn-primary btn-sm btn1 js-tssave">검색</button>
								</tr>
								<tr>
									<td width="5%" class="titletd text-center ">
									   <select class="form-control" name="scsel">
											<option value="">전체스케줄표그룹선택
											<?=printBaseCode_first('G03',$scsel)?>
										</select> 
									</td>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-scbsave">그룹일괄지정</button> </td>
									<td class="conttd" colspan=2><button type='button' class="btn btn-primary btn-sm btn1 js-scsave">검색</button> </td>
								</tr> 
								<tr>
									<td width="5%" class="titletd text-center ">
									   <select class="form-control" name="psel">
											<option value="">인기지역선택
											<?=printBaseCode_first('S04',$psel)?>
										</select> 
									</td>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-pbsave">인기지역지정</button> </td>
									<td class="conttd" colspan=2><button type='button' class="btn btn-primary btn-sm btn1 js-psave1">검색</button> </td>
								</tr> 
								</tbody>
					  </table>
					<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
						<thead>
							<tr>
							    <th width='2%' style="white-space:nowrap;"><input type="checkbox" id="selectAll" /></th>
							    <th width='6%' class="essential js-sort-header" align="center" data-sort-key="pos" style="cursor:pointer; white-space:nowrap;"><?= _renderProductSortLabel('상품위치', 'pos') ?></th>
							    <th width='11%' class="essential js-sort-header" align="center" data-sort-key="region" style="cursor:pointer; white-space:nowrap;"><?= _renderProductSortLabel('지역분류', 'region') ?></th>
								<th width='10%' class="essential js-sort-header" align="center" data-sort-key="p_code" style="cursor:pointer;"><?= _renderProductSortLabel('상품코드', 'p_code') ?></th>
								<th width='25%' class="essential js-sort-header" align="center" data-sort-key="p_name" style="cursor:pointer;"><?= _renderProductSortLabel('상품명', 'p_name') ?></th>
								<th width='7%' class="essential js-sort-header" align="center" data-sort-key="p_day" style="cursor:pointer; white-space:nowrap;"><?= _renderProductSortLabel('여행기간', 'p_day') ?></th>
								<th width='9%' class="essential js-sort-header" align="center" data-sort-key="end_yn" style="cursor:pointer; white-space:nowrap;"><?= _renderProductSortLabel('상품진행상태', 'end_yn') ?></th>
								<th width='9%' class="essential js-sort-header" align="center" data-sort-key="m_dept" style="cursor:pointer; white-space:nowrap;"><?= _renderProductSortLabel('상품관리지사', 'm_dept') ?></th>
								<th width='12%' class="essential js-sort-header" align="center" data-sort-key="display_price" style="cursor:pointer;">표시용성인가격<br />(2인1실/당일) <?= ($_sortCol === 'display_price') ? ($_sortDir === 'asc' ? '&#9650;' : '&#9660;') : '' ?></th>
								<th width='9%' class="essential" data-orderable="false" style="white-space:nowrap;">수정 | 삭제 | 복사</th>
							</tr>
						</thead> 
						<?php printProdut($ty); ?>
					</table>
					<?php if (!empty($_pagingHtml)) echo $_pagingHtml; ?>
					</form>
				</div><!-- -->

			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
			//pt.initProductList()
			var oTable = $('#ctable').dataTable({
				paging:    false,
				info:      false,
				searching: false,
				ordering:  false
			});

			$('body').on('click', '#ctable thead th.js-sort-header', function () {
				var sortKey = $(this).data('sortKey');
				var currentSortKey = $('#sort_col').val();
				var currentSortDir = $('#sort_dir').val();
				var nextSortDir = 'asc';

				if (sortKey === currentSortKey) {
					nextSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
				}

				$('#sort_col').val(sortKey);
				$('#sort_dir').val(nextSortDir);
				$('#page').val(1);
				$('#mode1').val('');
				$('#frmprod').submit();
			});

			$('body').on('click', '#selectAll', function () {
				var checked = !$(this).hasClass('allChecked');
				$('#ctable input[type="checkbox"]').prop('checked', checked);
				$(this).toggleClass('allChecked', checked);
			});

			// 페이징 버튼
			var _isPageNav = false;
			$('body').on('click', '.js-page', function () {
				_isPageNav = true;
				$('#page').val($(this).data('page'));
				$('#mode1').val('');
				$('#frmprod').submit();
			});

			// 필터 검색 시 page 초기화 (페이지 이동은 제외)
			$('#frmprod').on('submit', function () {
				if ($('#mode1').val() === '' && !_isPageNav) {
					$('#page').val(1);
				}
				_isPageNav = false;
			});
			$('.js-usave').click(function(e){
				if (confirm("업데이트 하시겠습니까?"))
				{
					$("#mode1").val("save");
				    $("#frmprod").submit();
				}
				

			});
			$('.js-psave').click(function(e){
				if (confirm("지정 하시겠습니까?"))
				{
					$("#mode1").val("save");
					$("#frmprod").attr('action', 'p_display.php?division=9&pdx=2&sub=10'); 
				    $("#frmprod").submit();
				}
				

			});
			$('.js-gsave').click(function(e){
				if (confirm("그룹지정 하시겠습니까?"))
				{
					$("#mode1").val("gsave");
					
				    $("#frmprod").submit();
				}
				

			});
			$('.js-ssave').click(function(e){
				
					$("#mode1").val("");
					
				    $("#frmprod").submit();
				
				

			});

			$('.js-esave').click(function(e){
				if (confirm("마감지정 하시겠습니까?"))
				{
					$("#mode1").val("esave");
					
				    $("#frmprod").submit();
				}
				

			});
			$('.js-essave').click(function(e){
				
					$("#mode1").val("");
					
				    $("#frmprod").submit();
				
				

			});



			$('.js-ddsave').click(function(e){
				if (confirm("할인여부를지정 하시겠습니까?"))
				{
					$("#mode1").val("ddsave");
					
				    $("#frmprod").submit();
				}
				

			});
			$('.js-ddssave').click(function(e){
				
					$("#mode1").val("");
					
				    $("#frmprod").submit();
				
				

			});
			$('.js-tsave').click(function(e){
				if (confirm(" 노출지정 하시겠습니까?"))
				{
					$("#mode1").val("esave");
					
				    $("#frmprod").submit();
				}
				

			});
			$('.js-tssave').click(function(e){
				
					$("#mode1").val("");
					
				    $("#frmprod").submit();
				
				

			});
			$('.js-scbsave').click(function(e){
				if (confirm("그룹지정 하시겠습니까?"))
				{
					$("#mode1").val("scbsave");
					
				    $("#frmprod").submit();
				}
				

			});
			$('.js-bsave').click(function(e){
				if (confirm("배경색지정 하시겠습니까?"))
				{
					$("#mode1").val("bsave");
					
				    $("#frmprod").submit();
				}
				

			});
			$('.js-pbsave').click(function(e){
				if (confirm("인기지역지정 하시겠습니까?"))
				{
					$("#mode1").val("pbsave");
					
				    $("#frmprod").submit();
				}
				

			});
			$('.js-scsave').click(function(e){
				
					$("#mode1").val("");
					
				    $("#frmprod").submit();
				
				

			});
			$('.js-psave1').click(function(e){
				
					$("#mode1").val("");
					
				    $("#frmprod").submit();
				
				

			});
			$('.js-bgsave').click(function(e){
				
					$("#mode1").val("");
					
				    $("#frmprod").submit();
				
				

			});
		})
		function del(id,ty) {
			if (confirm("삭제할까요?") == true) {
				var url = 'base_product.php?Mode=del'
					+ '&division=<?= rawurlencode($division) ?>'
					+ '&pdx=<?= rawurlencode($pdx) ?>'
					+ '&sub=<?= rawurlencode($sub) ?>'
					+ '&ty=' + encodeURIComponent(ty)
					+ '&pcode=' + encodeURIComponent(id)
					+ '&page=' + encodeURIComponent($('#page').val())
					+ '&sort_col=' + encodeURIComponent($('#sort_col').val())
					+ '&sort_dir=' + encodeURIComponent($('#sort_dir').val())
					+ '&search=' + encodeURIComponent($('input[name=\"search\"]').first().val() || '');
				location.replace(url);
			}
			else return;
		}
		function copy(id,ty) {
			if (confirm("복사할까요?") == true) {
				var url = 'base_product_m.php?Mode=copy'
					+ '&division=<?= rawurlencode($division) ?>'
					+ '&pdx=<?= rawurlencode($pdx) ?>'
					+ '&sub=<?= rawurlencode($sub) ?>'
					+ '&ty=' + encodeURIComponent(ty)
					+ '&pcode=' + encodeURIComponent(id)
					+ '&page=' + encodeURIComponent($('#page').val())
					+ '&sort_col=' + encodeURIComponent($('#sort_col').val())
					+ '&sort_dir=' + encodeURIComponent($('#sort_dir').val())
					+ '&search=' + encodeURIComponent($('input[name=\"search\"]').first().val() || '');
				location.replace(url);
			}
			else return;
		}
		
	</script>
    </body>
</html>
