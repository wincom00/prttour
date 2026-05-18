<?php
include "include/inc_base.php";

// SQL 쿼리 (이전에 제공한 쿼리 사용)
$qry1 = "SELECT t1.*
FROM (
    SELECT 
        CASE
            WHEN a.tour_type = 1 THEN '직접예약'
            WHEN a.tour_type = 2 THEN '웹예약'
            WHEN a.tour_type = 3 THEN '협력사예약'
            ELSE '기타'
        END AS tour_type,
        b.p_type,
        a.grand_revNo,
        a.reserveCode,
        a.p_cnt,
        a.revDate,
        a.p_code,
        a.p_name,
        a.stDate,
        a.book_pri,
        a.book_email,
        a.rev_status,
        a.payment_st,
        a.last_total,
        a.muser_id,
        d.kor_name AS '수정자',
        a.wdate AS '수정일',
        a.userid,
        CASE
            WHEN e.kor_name IS NULL THEN '웹결제자'
            ELSE e.kor_name
        END AS '최초작성자',
        CASE
            WHEN b.p_own = 'purun' THEN '푸른투어'
            ELSE c.kor_name
        END AS p_own_kor_name
    FROM reserve_info a
    JOIN product_master b ON a.p_code = b.p_code
    LEFT JOIN member_list c ON b.p_own = c.userid
    LEFT JOIN member_list d ON a.muser_id = d.userid
    LEFT JOIN member_list e ON a.userid = e.userid
    WHERE a.parent = 'MAIN'
      AND a.rev_status != 'CANCEL'
      AND a.rev_status != 'READY'
       AND a.tour_type !=3
      AND a.revDate BETWEEN '2023-01-01' AND '2023-12-31'
) AS t1
INNER JOIN (
    SELECT a.book_pri, COUNT(*) AS book_pri_count
    FROM reserve_info a
    WHERE a.parent = 'MAIN'
      AND a.rev_status != 'CANCEL'
      AND a.rev_status != 'READY'
      AND a.tour_type !=3
      AND a.revDate BETWEEN '2023-01-01' AND '2023-12-31'
    GROUP BY a.book_pri
    HAVING COUNT(*) >= 2
) AS t2 ON t1.book_pri = t2.book_pri
ORDER BY t1.revDate ASC";

$rst1 = mysql_query($qry1);

  echo "<table border=1>";
  // 테이블 헤더
  echo "<thead>";
  echo "<tr>";
  echo "<th>예약 유형</th>";
  echo "<th>상품 타입</th>";
  echo "<th>Grand Rev No</th>";
  echo "<th>Reserve Code</th>";
  echo "<th>인원 수</th>";
  echo "<th>예약일</th>";
  echo "<th>상품 코드</th>";
  echo "<th>상품명</th>";
  echo "<th>출발일</th>";
  echo "<th>고객명</th>";
  echo "<th>이메일</th>";
  echo "<th>예약 상태</th>";
  echo "<th>결제 상태</th>";
  echo "<th>결제 총금액</th>";
  echo "<th>수정자 ID</th>";
  echo "<th>수정자</th>";
  echo "<th>수정일</th>";
  echo "<th>최초작성자 ID</th>";
  echo "<th>최초작성자</th>";
  echo "<th>소유사</th>";
  echo "</tr>";
  echo "</thead>";

  // 테이블 본문
  echo "<tbody>";
  while($row = mysql_fetch_assoc($rst1)){
    echo "<tr>";
    echo "<td>" . $row["tour_type"] . "</td>";
    echo "<td>" . $row["p_type"] . "</td>";
    echo "<td>" . $row["grand_revNo"] . "</td>";
    echo "<td>" . $row["reserveCode"] . "</td>";
    echo "<td>" . $row["p_cnt"] . "</td>";
    echo "<td>" . $row["revDate"] . "</td>";
    echo "<td>" . $row["p_code"] . "</td>";
    echo "<td>" . $row["p_name"] . "</td>";
    echo "<td>" . $row["stDate"] . "</td>";
    echo "<td>" . $row["book_pri"] . "</td>";
    echo "<td>" . $row["book_email"] . "</td>";
    echo "<td>" . $row["rev_status"] . "</td>";
    echo "<td>" . $row["payment_st"] . "</td>";
	echo "<td>" . $row["last_total"] . "</td>";
    echo "<td>" . $row["muser_id"] . "</td>";
    echo "<td>" . $row["수정자"] . "</td>";
    echo "<td>" . $row["수정일"] . "</td>";
    echo "<td>" . $row["userid"] . "</td>";
    echo "<td>" . $row["최초작성자"] . "</td>";
    echo "<td>" . $row["p_own_kor_name"] . "</td>";
    echo "</tr>";
  }
  echo "</tbody>";
  echo "</table>";


?>