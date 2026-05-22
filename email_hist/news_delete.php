<?php
include "../include/inc_base.php";

// 단일 삭제
$seq_no = isset($_GET['seq_no']) ? (int)$_GET['seq_no'] : 0;
// 다중 삭제
$seq_nos = isset($_GET['seq_nos']) ? $_GET['seq_nos'] : '';

$success_count = 0;
$error_messages = array();

try {
    if ($seq_no > 0) {
        // 단일 삭제 처리
        $sql = "DELETE FROM news_hist WHERE seq_no = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(array($seq_no));
        
        if ($result && $stmt->rowCount() > 0) {
            $success_count = 1;
        } else {
            $error_messages[] = "삭제할 뉴스를 찾을 수 없습니다.";
        }
        
    } elseif (!empty($seq_nos)) {
        // 다중 삭제 처리
        $seq_no_array = explode(',', $seq_nos);
        $seq_no_array = array_filter(array_map('intval', $seq_no_array));
        
        if (!empty($seq_no_array)) {
            $placeholders = str_repeat('?,', count($seq_no_array) - 1) . '?';
            $sql = "DELETE FROM news_hist WHERE seq_no IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($seq_no_array);
            
            if ($result) {
                $success_count = $stmt->rowCount();
            } else {
                $error_messages[] = "삭제 중 오류가 발생했습니다.";
            }
        } else {
            $error_messages[] = "유효한 뉴스 번호가 없습니다.";
        }
    } else {
        $error_messages[] = "삭제할 뉴스를 선택해주세요.";
    }
    
} catch (PDOException $e) {
    $error_messages[] = "데이터베이스 오류: " . $e->getMessage();
}

// 결과 메시지 출력 및 리다이렉트
if ($success_count > 0) {
    $message = $success_count . "개의 뉴스가 성공적으로 삭제되었습니다.";
    echo "<script>alert('$message'); location.href='news_list.php?division=10&pdx=2&sub=15';</script>";
} else {
    $message = "삭제 실패: " . implode(', ', $error_messages);
    echo "<script>alert('$message'); history.back();</script>";
}
exit;
?>