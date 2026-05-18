<?php

      include("include/dbconn.php");

      
      $hotel_list = $_POST['hotelSelect'];
      $hotel_etc_list = $_POST['hotelSelect_etc'];

      $etc_list = $_POST['etcCostSelect'];
      $hotelRateRm = $_POST['hotelRateRm'];
      $hotelrm = $_POST['hotelRm'];
      $hotelcost = $_POST['hotelCost'];
      $etcrate = $_POST['etcRate'];
      $etccnt = $_POST['etcCount'];
      $etcamount = $_POST['etcAmount'];
          

      $grand_eCode =  $_POST['grand_eCode'];
      $sub_eCode = $_POST['sub_eCode'];
      $m_rate = $_POST['m_rate_h'];
      $totalPayment = $_POST['totalPayment'];
      $memo = $_POST['memo'];
      $hotel_seq = $_POST['hotel_seq'];
      $etc_seq = $_POST['etc_seq'];
      $hotel_sumseq = $_POST['hotel_sumseq'];
      $mode = $_POST['mode'];
      $grandcode = $_POST['grandcode'];
      $subcode = $_POST['subcode'];

      if($mode == 'delete_hotel'){
          
          $d_seq = $_POST['seqno'];

          $query = "SELECT hotel_amt FROM hotel_settle WHERE seq_no = $d_seq ";
          $rst1 = mysql_query($query,$dbConn);
          $data_row = mysql_fetch_assoc($rst1);
          $hotelamt = $data_row['hotel_amt'];

          $query="DELETE FROM hotel_settle WHERE seq_no = $d_seq";
          mysql_query($query,$dbConn);
         
          getCalTotalamt($grandcode,$subcode,$hotelamt); //실제지불총금액
         

      }else if($mode =='delete_etc') {

          $d_seq = $_POST['seqno'];
          $number = $_POST['number'];

          $query = "SELECT etc_amt FROM hotel_settleetc WHERE seq_no = $d_seq ";
          $rst1 = mysql_query($query,$dbConn);
          $data_row = mysql_fetch_assoc($rst1);
          $etcamt = $data_row['etc_amt'];

          $query="DELETE FROM hotel_settleetc WHERE seq_no = $d_seq";
          mysql_query($query,$dbConn);

          getCalTotalamt($grandcode,$subcode,$etcamt); //실제지불총금액

      }else{

          //1. HOTEL SETTLE TABLE 
          foreach($hotel_list as $key =>$value){
              $hcode_array = explode(':::' , $value);
              $hcode = $hcode_array[0];
              $raterm = $hotelRateRm[$key];
              $hotel_rm = $hotelrm[$key];
              $hotel_cost = $hotelcost[$key];
              $seqno = $hotel_seq[$key];

              if($seqno >0) {

                  $query = "UPDATE hotel_settle SET
                  h_code = '$hcode',	
                  rate_rm = '$raterm',
                  room_cnt = '$hotel_rm',
                  hotel_amt = '$hotel_cost'
                  WHERE seq_no = $seqno ";

              }else{
              
                  $query = "INSERT INTO hotel_settle ( 
                  grand_eCode,
                  sub_eCode,	
                  h_code,	
                  rate_rm,
                  room_cnt,
                  hotel_amt,
                  wdate) VALUES (
                  '$grand_eCode','$sub_eCode','$hcode','$raterm','$hotel_rm','$hotel_cost',now()) ";
              }

              $rst0 = mysql_query($query,$dbConn);
          }

           //2. HOTEL SETTLE ETC TABLE
           foreach($etc_list as $key =>$value){
              $etccode = $value;
              $etc_rate = $etcrate[$key];
              $etc_cnt = $etccnt[$key];
              $etc_amount = $etcamount[$key];
              $etc_seqno = $etc_seq[$key];
              $hoteletc_cd = $hotel_etc_list[$key];

              if($etc_seqno >0) {

                  $query = "UPDATE hotel_settleetc SET
                  etc_code = '$etccode',	
                  rate = '$etc_rate',
                  cnt = '$etc_cnt',
                  etc_amt = '$etc_amount',
                  h_code = '$hoteletc_cd'
                  WHERE seq_no = $etc_seqno ";

              }else{

                  $query = "INSERT INTO hotel_settleetc ( 
                  grand_eCode,
                  sub_eCode,	
                  etc_code,	
                  rate,
                  cnt,
                  etc_amt,
                  h_code,
                  wdate) VALUES (
                  '$grand_eCode','$sub_eCode','$etccode','$etc_rate','$etc_cnt','$etc_amount','$hoteletc_cd',now()) ";
              }

              $rst0 = mysql_query($query,$dbConn);
          }

          //3. HOTEL SETTLE SUM TABLE

          if($hotel_sumseq >0 ){
              
              $query = "UPDATE hotel_settlesum SET
              base_rate = '$m_rate',	
              real_amt = '$totalPayment',
              memo = '$memo'
              WHERE seq_no = $hotel_sumseq ";

          }else{

              $query = "INSERT INTO hotel_settlesum ( 
              grand_eCode,
              sub_eCode,	
              base_rate,	
              real_amt,
              memo,
              status,
              wdate) VALUES (
              '$grand_eCode','$sub_eCode','$m_rate','$totalPayment','$memo','DONE',now()) ";
          }

          $rst0 = mysql_query($query,$dbConn);
      
          echo '1/';

      }
      
      //실제지불총액 재계산
      function getCalTotalamt($grandcode,$subcode,$amt){

          global $dbConn;

          //호텔 총 금액
          $query = "SELECT SUM(real_amt) real_amt FROM hotel_settlesum WHERE grand_eCode = '$grandcode' AND sub_eCode = '$subcode' ";
          $rst1 = mysql_query($query,$dbConn);
          $data_row = mysql_fetch_assoc($rst1);

          $toal_amt = (float)$data_row['real_amt'] - (float)$amt;
          $total_amt = number_format($toal_amt,2);

          /*
          //호텔 총 금액
          $query = "SELECT SUM(hotel_amt) hotel_amt FROM hotel_settle WHERE grand_eCode = '$grandcode' 
          AND sub_eCode = '$subcode' ";
          $rst2 = mysql_query($query,$dbConn);
          $hotel_row = mysql_fetch_assoc($rst2);

          //기타비용 총 금액
          $query = "SELECT SUM(etc_amt) etc_amt FROM hotel_settleetc WHERE grand_eCode = '$grandcode' 
          AND sub_eCode = '$subcode' ";
          $rst3 = mysql_query($query,$dbConn);
          $etc_row = mysql_fetch_assoc($rst3);

          $total_amt = $hotel_row[hotel_amt]+$etc_row[etc_amt]; 
          */

          $query1 = "UPDATE hotel_settlesum SET real_amt = '$total_amt' WHERE grand_eCode = '$grandcode' AND sub_eCode = '$subcode'  ";
          mysql_query($query1,$dbConn);

      }

?>