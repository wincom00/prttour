<?php
// -----------------------------------------------------------------------------
// 협력사 전용 그룹 예약 일괄 등록 시스템 - Hello USA Partner Portal
// PHPExcel 라이브러리 사용 버전 - 향상된 Excel 처리
// PHP 5.5 호환 버전
// -----------------------------------------------------------------------------
session_start();

// 데이터베이스 연결
include "include/inc_base.php";
if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	if ($user_dbinfo['division'] == "guide") {
		echo "<meta http-equiv='refresh' content='0; url=./memo_list.php'>";
		exit;
	}
} else {
	echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
	exit;
}
$partner_info = $user_dbinfo;
$partner_id = $partner_info['userid'];

// PHPExcel 라이브러리 체크 및 로드
$phpexcel_paths = array(
    './lib/PHPExcel/Classes/PHPExcel.php',
    '../lib/PHPExcel/PHPExcel.php',
    'vendor/phpoffice/phpexcel/Classes/PHPExcel.php'
);

$phpexcel_loaded = false;
foreach ($phpexcel_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $phpexcel_loaded = true;
        break;
    }
}

// 대안으로 PhpSpreadsheet 체크 (PHPExcel의 후속 라이브러리)
if (!$phpexcel_loaded) {
    $phpspreadsheet_paths = array(
        'vendor/phpoffice/phpspreadsheet/src/Bootstrap.php',
        'lib/phpspreadsheet/src/Bootstrap.php'
    );
    
    foreach ($phpspreadsheet_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $phpexcel_loaded = true;
            $use_phpspreadsheet = true;
            break;
        }
    }
}

// 전역 변수 초기화
$error_messages = array();
$success_messages = array();
$processed_data = array();
$group_info = array();

// -----------------------------------------------------------------------------
// AJAX 요청 처리 (기존과 동일)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $response = array('success' => false, 'message' => '', 'data' => null);
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        switch ($action) {
            case 'add_traveler':
                $new_traveler = array(
                    'korean_name' => isset($_POST['korean_name']) ? $_POST['korean_name'] : '',
                    'english_name' => isset($_POST['english_name']) ? $_POST['english_name'] : '',
                    'passport_number' => isset($_POST['passport_number']) ? $_POST['passport_number'] : '',
                    'birth_date' => isset($_POST['birth_date']) ? $_POST['birth_date'] : '',
                    'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                    'email' => isset($_POST['email']) ? $_POST['email'] : '',
                    'gender' => isset($_POST['gender']) ? $_POST['gender'] : '',
                    'room_type' => isset($_POST['room_type']) ? $_POST['room_type'] : '2r1p',
                    'room_number' => isset($_POST['room_number']) ? $_POST['room_number'] : '',
                    'memo' => isset($_POST['memo']) ? $_POST['memo'] : ''
                );
                
                $validation = validateTravelerData($new_traveler);
                if (!$validation['valid']) {
                    $response['message'] = $validation['message'];
                } else {
                    $response = array(
                        'success' => true,
                        'message' => '새 여행자가 추가되었습니다.',
                        'data' => $new_traveler
                    );
                }
                break;
                
            case 'update_traveler':
                $updated_traveler = array(
                    'korean_name' => isset($_POST['korean_name']) ? $_POST['korean_name'] : '',
                    'english_name' => isset($_POST['english_name']) ? $_POST['english_name'] : '',
                    'passport_number' => isset($_POST['passport_number']) ? $_POST['passport_number'] : '',
                    'birth_date' => isset($_POST['birth_date']) ? $_POST['birth_date'] : '',
                    'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                    'email' => isset($_POST['email']) ? $_POST['email'] : '',
                    'gender' => isset($_POST['gender']) ? $_POST['gender'] : '',
                    'room_type' => isset($_POST['room_type']) ? $_POST['room_type'] : '2r1p',
                    'room_number' => isset($_POST['room_number']) ? $_POST['room_number'] : '',
                    'memo' => isset($_POST['memo']) ? $_POST['memo'] : ''
                );
                
                $validation = validateTravelerData($updated_traveler);
                if (!$validation['valid']) {
                    $response['message'] = $validation['message'];
                } else {
                    $response = array(
                        'success' => true,
                        'message' => '여행자 정보가 수정되었습니다.',
                        'data' => $updated_traveler
                    );
                }
                break;
                
            case 'delete_traveler':
                $response = array(
                    'success' => true,
                    'message' => '여행자가 삭제되었습니다.'
                );
                break;
                
            case 'duplicate_traveler':
                $original_data = json_decode($_POST['traveler_data'], true);
                $duplicated_traveler = $original_data;
                
                if (!empty($duplicated_traveler['korean_name'])) {
                    $duplicated_traveler['korean_name'] .= ' (복사본)';
                }
                if (!empty($duplicated_traveler['english_name'])) {
                    $duplicated_traveler['english_name'] .= ' COPY';
                }
                
                $response = array(
                    'success' => true,
                    'message' => '여행자가 복제되었습니다.',
                    'data' => $duplicated_traveler
                );
                break;
                
            case 'save_group_reservation':
                $group_data_json = isset($_POST['group_data']) ? $_POST['group_data'] : '{}';
                $travelers_data_json = isset($_POST['travelers_data']) ? $_POST['travelers_data'] : '[]';

                $current_group_data = json_decode($group_data_json, true);
                $current_travelers_data = json_decode($travelers_data_json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                     $response['message'] = '전송된 그룹 또는 여행자 데이터 형식이 잘못되었습니다: ' . json_last_error_msg();
                } else {
                    $result = saveGroupReservation($current_group_data, $current_travelers_data, $partner_id);
                    $response = $result;
                }
                break;
             case 'save_selected_group_reservation':
				$group_data_json = isset($_POST['group_data']) ? $_POST['group_data'] : '{}';
				$travelers_data_json = isset($_POST['travelers_data']) ? $_POST['travelers_data'] : '[]';

				$selected_group_data = json_decode($group_data_json, true);
				$selected_travelers_data = json_decode($travelers_data_json, true);

				if (json_last_error() !== JSON_ERROR_NONE) {
					$response['message'] = '전송된 그룹 또는 여행자 데이터 형식이 잘못되었습니다: ' . json_last_error_msg();
				} elseif (empty($selected_travelers_data)) {
					$response['message'] = '선택된 여행자가 없습니다.';
				} else {
					$result = saveGroupReservation($selected_group_data, $selected_travelers_data, $partner_id);
					$response = $result;
				}
				break;
			default:
				$response['message'] = '알 수 없는 요청입니다.';
		}
	} catch (Exception $e) {
		$response['message'] = '처리 중 오류가 발생했습니다: ' . $e->getMessage();
	}
	
	echo json_encode($response);
	exit;
}

// -----------------------------------------------------------------------------
// 파일 업로드 처리 - PHPExcel 사용
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['reservation_files'])) {
    $upload_dir = 'upload/partner_groups/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $files = $_FILES['reservation_files'];
    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = basename($files['name'][$i]);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, array('xlsx', 'xls', 'csv'))) {
                    $safe_name = time() . '_' . $i . '_' . preg_replace("/[^\p{L}\p{N}._-]/u", "_", $file_name);
                    $file_path = $upload_dir . $safe_name;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                        try {
                            $result = processAdvancedGroupFileWithPHPExcel($file_path, $file_name);
                            
                            if (!empty($result['sheet_errors'])) {
                                foreach ($result['sheet_errors'] as $sheet_error) {
                                    $error_messages[] = "파일 '{$file_name}': " . $sheet_error;
                                }
                            }

                            if (!empty($result['travelers'])) {
                                $current_file_travelers = $result['travelers'];
                                $processed_data = array_merge($processed_data, $current_file_travelers);

                                $current_file_group_info = $result['group_info'];
                                if ($i === 0 || empty($group_info) || empty($group_info['tour_name'])) {
                                    $group_info = $current_file_group_info;
                                } else {
                                    if ($current_file_group_info) {
                                        foreach ($current_file_group_info as $key => $value) {
                                            if (empty($group_info[$key]) && !empty($value)) {
                                                $group_info[$key] = $value;
                                            }
                                        }
                                    }
                                }
                                
                                $sheets_processed_msg = "";
                                if (isset($result['processed_sheets_count']) && $result['processed_sheets_count'] > 0) {
                                     $sheets_processed_msg = " ({$result['processed_sheets_count']}개 시트)";
                                }
                                $success_messages[] = "✅ '{$file_name}'{$sheets_processed_msg} 처리 완료 (" . count($current_file_travelers) . "명의 여행자 추가)";

                            } elseif (empty($result['sheet_errors'])) {
                                $error_messages[] = "⚠️ '{$file_name}'에서 유효한 데이터를 찾을 수 없습니다.";
                            }
                        } catch (Exception $e) {
                            $error_messages[] = "❌ '{$file_name}' 처리 오류: " . $e->getMessage();
                        }
                        unlink($file_path);
                    }
                } else {
                    $error_messages[] = "❌ '{$file_name}': 지원하지 않는 파일 형식";
                }
            }
        }
        
        if (!empty($processed_data)) {
            $processed_data = removeDuplicateTravelers($processed_data);
            if (empty($group_info)) {
                 $group_info = array('product_code' => '','tour_name' => '', 'product_code' => '', 'start_date' => '', 'end_date' => '', 'group_leader' => '', 'group_phone' => '', 'group_email' => '');
            }
             setDefaultGroupInfo($group_info, "종합 정보", $processed_data);
        }
    }
}

// -----------------------------------------------------------------------------
// PHPExcel을 사용한 고급 파일 처리 함수들
// -----------------------------------------------------------------------------

/**
 * PHPExcel을 사용한 고급 그룹 파일 처리
 */
function processAdvancedGroupFileWithPHPExcel($file_path, $original_filename) {
    global $phpexcel_loaded, $use_phpspreadsheet;
    
    if (!$phpexcel_loaded) {
        throw new Exception("PHPExcel 또는 PhpSpreadsheet 라이브러리가 필요합니다.");
    }
    
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if ($ext === 'csv') {
        return processAdvancedCSVWithPHPExcel($file_path, $original_filename);
    }
    
    if (in_array($ext, array('xlsx', 'xls'))) {
        try {
            // PHPExcel Reader 설정
            if (isset($use_phpspreadsheet) && $use_phpspreadsheet) {
                // PhpSpreadsheet 사용
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                    $ext === 'xlsx' ? 'Xlsx' : 'Xls'
                );
            } else {
                // 레거시 PHPExcel 사용
                $reader = PHPExcel_IOFactory::createReader(
                    $ext === 'xlsx' ? 'Excel2007' : 'Excel5'
                );
            }
            
            // 읽기 전용 모드로 설정하여 성능 향상
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            
            // Excel 파일 로드
            $phpExcelObject = $reader->load($file_path);
            
            $all_travelers_from_sheets = array();
            $final_group_info_from_sheets = null;
            $processed_sheets_count = 0;
            $sheet_processing_errors = array();
            
            // 모든 워크시트 처리
            $worksheet_count = $phpExcelObject->getSheetCount();
            
            for ($sheet_index = 0; $sheet_index < $worksheet_count; $sheet_index++) {
                try {
                    $worksheet = $phpExcelObject->getSheet($sheet_index);
                    $sheet_name = $worksheet->getTitle();
                    
                    // 시트 데이터를 배열로 변환 (PHPExcel의 강력한 기능 사용)
                    $sheet_data = extractDataFromWorksheet($worksheet);
                    
                    if (empty($sheet_data) || count($sheet_data) <= 1) {
                        continue; // 빈 시트 또는 헤더만 있는 시트 스킵
                    }
                    
                    $filename_for_sheet = $original_filename . " (Sheet: " . htmlspecialchars($sheet_name) . ")";
                    $sheet_data_result = extractAdvancedGroupDataFromPHPExcel($sheet_data, $filename_for_sheet, $worksheet);
                    
                    if (!empty($sheet_data_result['travelers'])) {
                        $all_travelers_from_sheets = array_merge($all_travelers_from_sheets, $sheet_data_result['travelers']);
                    }
                    
                    // 그룹 정보 병합
                    if ($final_group_info_from_sheets === null && !empty($sheet_data_result['group_info'])) {
                        $final_group_info_from_sheets = $sheet_data_result['group_info'];
                    } elseif ($final_group_info_from_sheets !== null && !empty($sheet_data_result['group_info'])) {
                        foreach ($sheet_data_result['group_info'] as $key => $value) {
                            if (empty($final_group_info_from_sheets[$key]) && !empty($value)) {
                                $final_group_info_from_sheets[$key] = $value;
                            }
                        }
                    }
                    $processed_sheets_count++;
                    
                } catch (Exception $e) {
                    $sheet_processing_errors[] = "시트 '" . htmlspecialchars($sheet_name) . "' 처리 오류: " . $e->getMessage();
                }
            }
            
            // 메모리 정리
            $phpExcelObject->disconnectWorksheets();
            unset($phpExcelObject);
            
            if ($processed_sheets_count === 0 && !empty($sheet_processing_errors)) {
                throw new Exception(implode("; ", $sheet_processing_errors));
            }
            
            if ($final_group_info_from_sheets === null) {
                $final_group_info_from_sheets = array(
                    'product_code' => '','tour_name' => '', 'product_code' => '', 'start_date' => '', 'end_date' => '',
                    'group_leader' => '', 'group_phone' => '', 'group_email' => ''
                );
            }
            
            setDefaultGroupInfo($final_group_info_from_sheets, $original_filename, $all_travelers_from_sheets);
            $all_travelers_from_sheets = removeDuplicateTravelers($all_travelers_from_sheets);
            
            return array(
                'group_info' => $final_group_info_from_sheets,
                'travelers' => $all_travelers_from_sheets,
                'processed_sheets_count' => $processed_sheets_count,
                'sheet_errors' => $sheet_processing_errors
            );
            
        } catch (Exception $e) {
            throw new Exception("Excel 파일 처리 오류: " . $e->getMessage());
        }
    }
    
    throw new Exception("지원하지 않는 파일 형식입니다.");
}

/**
 * PHPExcel 워크시트에서 데이터 추출 (병합된 셀 처리 포함)
 */
function extractDataFromWorksheet($worksheet) {
    $data = array();
    
    // 실제 사용된 범위 가져오기
    $highestRow = $worksheet->getHighestRow();
    $highestCol = $worksheet->getHighestColumn();
    $highestColIndex = PHPExcel_Cell::columnIndexFromString($highestCol);
    
    // 병합된 셀 정보 가져오기
    $mergedCells = $worksheet->getMergeCells();
    $mergedCellsMap = array();
    
    // 병합된 셀 맵 생성
    foreach ($mergedCells as $mergedRange) {
        $range = PHPExcel_Cell::splitRange($mergedRange);
        $startCell = $range[0][0];
        $endCell = $range[0][1];
        
        // 병합 범위의 모든 셀에 시작 셀의 값을 매핑
        $startCoordinate = PHPExcel_Cell::coordinateFromString($startCell);
        $endCoordinate = PHPExcel_Cell::coordinateFromString($endCell);
        
        $startCol = PHPExcel_Cell::columnIndexFromString($startCoordinate[0]);
        $startRow = $startCoordinate[1];
        $endCol = PHPExcel_Cell::columnIndexFromString($endCoordinate[0]);
        $endRow = $endCoordinate[1];
        
        // 병합된 영역의 값 (첫 번째 셀의 값)
        $mergedValue = $worksheet->getCell($startCell)->getCalculatedValue();
        
        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($col = $startCol; $col <= $endCol; $col++) {
                $cellCoordinate = PHPExcel_Cell::stringFromColumnIndex($col-1) . $row;
                $mergedCellsMap[$cellCoordinate] = $mergedValue;
            }
        }
    }
    
    // 데이터 추출
    for ($row = 1; $row <= $highestRow; $row++) {
        $rowData = array();
        $hasData = false;
        
        for ($col = 0; $col < $highestColIndex; $col++) {
            $cellCoordinate = PHPExcel_Cell::stringFromColumnIndex($col) . $row;
            
            // 병합된 셀 확인
            if (isset($mergedCellsMap[$cellCoordinate])) {
                $cellValue = $mergedCellsMap[$cellCoordinate];
            } else {
                $cell = $worksheet->getCell($cellCoordinate);
                $cellValue = $cell->getCalculatedValue();
                
                // 날짜 형식 처리
                if (PHPExcel_Shared_Date::isDateTime($cell)) {
                    $dateValue = PHPExcel_Shared_Date::ExcelToPHP($cell->getValue());
                    $cellValue = date('Y-m-d', $dateValue);
                }
            }
            
            // 값 정리
            if ($cellValue !== null && $cellValue !== '') {
                $cellValue = trim((string)$cellValue);
                if ($cellValue !== '') {
                    $hasData = true;
                }
            } else {
                $cellValue = '';
            }
            
            $rowData[] = $cellValue;
        }
        
        // 빈 행이 아닌 경우만 추가
        if ($hasData) {
            $data[] = $rowData;
        }
    }
    
    return $data;
}

/**
 * PHPExcel로 처리된 데이터에서 그룹 정보 추출
 */
function extractAdvancedGroupDataFromPHPExcel($rows, $source_identifier, $worksheet = null) {
    $travelers = array();
    $group_info = array(
        'tour_name' => '',
        'product_code' => '',
        'start_date' => '',
        'end_date' => '',
        'group_leader' => '',
        'group_phone' => '',
        'group_email' => ''
    );
    
    // 상품 코드 추출
    $group_info['product_code'] = extractProductCodeFromFilename($source_identifier);
    $arrpro = getProductInfoByCode($group_info['product_code']);
    $group_info['product_code'] = $arrpro['p_code'];
    $group_info['tour_name'] = $arrpro['p_name'];
    
    // 워크시트에서 추가 정보 추출 (PHPExcel의 고급 기능 활용)
    if ($worksheet !== null) {
        extractGroupInfoFromWorksheet($worksheet, $group_info);
    }
    
    $headers = null;
    $data_start_row = -1;
    $all_dates_found_in_sheet = array();
    
    // 헤더 및 데이터 시작 위치 찾기
    foreach ($rows as $row_idx => $row) {
        if (empty($row) || !is_array($row)) continue;
        
        // 날짜 정보 수집
        foreach ($row as $cell_content) {
            $cell_text = trim((string)$cell_content);
            if (empty($cell_text)) continue;
            
            $dates_in_cell = extractDatesFromText($cell_text);
            if (!empty($dates_in_cell['start']) || !empty($dates_in_cell['end'])) {
                $all_dates_found_in_sheet[] = $dates_in_cell;
            }
            extractGroupInfoFromCell($cell_text, $group_info, $row, array_search($cell_content, $row));
        }
        
        // 헤더 행 감지 (개선된 로직)
        if ($headers === null) {
            $header_score = calculateHeaderScore($row);
            if ($header_score >= 3) { // 헤더로 판단할 최소 점수
                $headers = array_map(function($h) { return trim((string)$h); }, $row);
                $data_start_row = $row_idx + 1;
            }
        }
    }
    
    // 최적 날짜 쌍 찾기
    if (!empty($all_dates_found_in_sheet)) {
        $best_dates = findBestDatePair($all_dates_found_in_sheet);
        if ($best_dates) {
            if (empty($group_info['start_date']) && !empty($best_dates['start'])) $group_info['start_date'] = $best_dates['start'];
            if (empty($group_info['end_date']) && !empty($best_dates['end'])) $group_info['end_date'] = $best_dates['end'];
        }
    }
    
    // 여행자 데이터 추출
    if ($headers !== null && $data_start_row !== -1) {
        for ($i = $data_start_row; $i < count($rows); $i++) {
           $data_row = $rows[$i];
           if (empty($data_row) || !is_array($data_row) || empty(array_filter($data_row, function($cell){ return trim((string)$cell) !== ''; }))) {
               continue;
           }
           
           $traveler = extractAdvancedTravelerDataFromPHPExcel($data_row, $headers);
           
           if (!empty($traveler['korean_name']) || !empty($traveler['english_name']) || !empty($traveler['passport_number'])) {
               $travelers[] = $traveler;
           }
       }
   }
   
   return array(
       'group_info' => $group_info,
       'travelers' => $travelers
   );
}

/**
 * 헤더 행 점수 계산 (PHPExcel 버전용)
 */
function calculateHeaderScore($row) {
    $score = 0;
    $header_keywords = array(
        '성명' => 2, '이름' => 2, 'name' => 2,
        '여권' => 3, 'passport' => 3,
        '번호' => 1, 'no' => 1, 'number' => 1,
        '생년월일' => 2, 'birth' => 2, 'birthday' => 2,
        '성별' => 2, 'gender' => 2, 'sex' => 2,
        '연락처' => 2, '전화' => 2, 'phone' => 2, 'mobile' => 2,
        '이메일' => 2, 'email' => 2, 'mail' => 2,
        '객실' => 1, 'room' => 1,
        '비고' => 1, 'memo' => 1, 'note' => 1
    );
    
    foreach ($row as $cell) {
        $cell_text = strtolower(trim((string)$cell));
        foreach ($header_keywords as $keyword => $points) {
            if (strpos($cell_text, $keyword) !== false) {
                $score += $points;
                break; // 한 셀당 하나의 키워드만 매칭
            }
        }
    }
    
    return $score;
}

/**
 * 워크시트에서 그룹 정보 추출 (PHPExcel 고급 기능 사용)
 */
function extractGroupInfoFromWorksheet($worksheet, &$group_info) {
    // 워크시트의 모든 셀을 순회하면서 그룹 정보 찾기
    $highestRow = min($worksheet->getHighestRow(), 20); // 상위 20행만 검사
    $highestCol = $worksheet->getHighestColumn();
    $highestColIndex = PHPExcel_Cell::columnIndexFromString($highestCol);
    
    for ($row = 1; $row <= $highestRow; $row++) {
        for ($col = 0; $col < min($highestColIndex, 10); $col++) { // 좌측 10열만 검사
            $cellCoordinate = PHPExcel_Cell::stringFromColumnIndex($col) . $row;
            $cell = $worksheet->getCell($cellCoordinate);
            $cellValue = trim((string)$cell->getCalculatedValue());
            
            if (empty($cellValue)) continue;
            
            // 다음 셀의 값도 확인 (라벨:값 패턴)
            $nextCellCoordinate = PHPExcel_Cell::stringFromColumnIndex($col + 1) . $row;
            $nextCellValue = '';
            if ($col + 1 < $highestColIndex) {
                $nextCell = $worksheet->getCell($nextCellCoordinate);
                $nextCellValue = trim((string)$nextCell->getCalculatedValue());
           }
           
           // 그룹 정보 패턴 매칭
           $cellLower = strtolower($cellValue);
           
           // 상품명/투어명 찾기
           if (empty($group_info['tour_name'])) {
               if (preg_match('/(상품명|투어명|여행명|행사명|프로그램명)/i', $cellValue)) {
                   if (!empty($nextCellValue)) {
                       $group_info['tour_name'] = $nextCellValue;
                   } elseif (strpos($cellValue, ':') !== false) {
                       $parts = explode(':', $cellValue, 2);
                       if (count($parts) > 1 && !empty(trim($parts[1]))) {
                           $group_info['tour_name'] = trim($parts[1]);
                       }
                   }
               }
           }
           
           // 상품코드 찾기
           if (empty($group_info['product_code'])) {
               if (preg_match('/(상품코드|코드|상품번호)/i', $cellValue)) {
                   if (!empty($nextCellValue)) {
                       $group_info['product_code'] = $nextCellValue;
                   } elseif (strpos($cellValue, ':') !== false) {
                       $parts = explode(':', $cellValue, 2);
                       if (count($parts) > 1 && !empty(trim($parts[1]))) {
                           $group_info['product_code'] = trim($parts[1]);
                       }
                   }
               }
           }
           
           // 대표자 정보 찾기
           if (empty($group_info['group_leader'])) {
               if (preg_match('/(대표자|인솔자|담당자|리더)/i', $cellValue)) {
                   if (!empty($nextCellValue)) {
                       $group_info['group_leader'] = $nextCellValue;
                   }
               }
           }
           
           // 연락처 찾기
           if (empty($group_info['group_phone'])) {
               if (preg_match('/(연락처|전화번호|휴대폰)/i', $cellValue) && 
                   strpos($cellLower, '여행자') === false) {
                   if (!empty($nextCellValue) && preg_match('/[\d\-\+\(\)\s]{8,}/', $nextCellValue)) {
                       $group_info['group_phone'] = $nextCellValue;
                   }
               }
           }
           
           // 이메일 찾기
           if (empty($group_info['group_email'])) {
               if (preg_match('/(이메일|메일|email)/i', $cellValue) && 
                   strpos($cellLower, '여행자') === false) {
                   if (!empty($nextCellValue) && filter_var($nextCellValue, FILTER_VALIDATE_EMAIL)) {
                       $group_info['group_email'] = $nextCellValue;
                   }
               }
           }
           
           // 날짜 정보 추출 (셀 서식 활용)
           if (PHPExcel_Shared_Date::isDateTime($cell)) {
               $dateValue = PHPExcel_Shared_Date::ExcelToPHP($cell->getValue());
               $formattedDate = date('Y-m-d', $dateValue);
               
               if (preg_match('/(출발|시작|start)/i', $cellValue) && empty($group_info['start_date'])) {
                   $group_info['start_date'] = $formattedDate;
               } elseif (preg_match('/(도착|종료|end|finish)/i', $cellValue) && empty($group_info['end_date'])) {
                   $group_info['end_date'] = $formattedDate;
               } elseif (empty($group_info['start_date'])) {
                   $group_info['start_date'] = $formattedDate;
               }
           }
       }
   }
}

/**
* PHPExcel로 처리된 데이터에서 여행자 정보 추출 (개선된 버전)
*/
function extractAdvancedTravelerDataFromPHPExcel($row, $headers) {
   $data = array(
       'korean_name' => '', 'english_name' => '', 'passport_number' => '', 'birth_date' => '',
       'phone' => '', 'email' => '', 'gender' => '', 
       'room_type' => '2r1p',
       'room_number' => '', 'memo' => ''
   );
   
   // 향상된 필드 패턴 매칭
   $field_patterns = array(
       'korean_name' => array(
           '/^(한글|국문)?이?름$/ui', '/성명/ui', '/^이름$/ui', '/한글.*명$/ui'
       ),
       'english_name' => array(
           '/영문/ui', '/english/ui', '/^eng/ui', '/passport.*name/ui', '/영어.*이름/ui'
       ),
       'passport_number' => array(
           '/여권/ui', '/passport/ui', '/^pp/ui', '/^p\.?p/ui'
       ),
       'birth_date' => array(
           '/생년월일/ui', '/생일/ui', '/birth/ui', '/생년/ui', '/출생/ui',
           '/^생$/ui', '/birthday/ui', '/date.*birth/ui', '/년.*월.*일/ui'
       ),
       'phone' => array(
           '/연락처/ui', '/전화/ui', '/phone/ui', '/mobile/ui', '/핸드폰/ui', '/휴대폰/ui',
           '/tel/ui', '/contact/ui'
       ),
       'email' => array(
           '/이메일/ui', '/email/ui', '/e-mail/ui', '/메일/ui', '/mail/ui'
       ),
       'gender' => array(
           '/성별/ui', '/gender/ui', '/sex/ui', '/^성$/ui'
       ),
       'room_type' => array(
           '/객실.*타입/ui', '/룸.*타입/ui', '/room.*type/ui', '/방.*타입/ui', '/숙박.*타입/ui'
       ),
       'room_number' => array(
           '/객실.*번호/ui', '/룸.*번호/ui', '/room.*no/ui', '/방.*번호/ui', '/객실$/ui'
       ),
       'memo' => array(
           '/비고/ui', '/메모/ui', '/memo/ui', '/note/ui', '/특이사항/ui', '/요청사항/ui',
           '/참고/ui', '/기타/ui', '/etc/ui'
       )
   );

   // 각 헤더에 대해 필드 매칭
   foreach ($headers as $col_idx => $header_text) {
       $header_text = trim((string)$header_text);
       $cell_value = isset($row[$col_idx]) ? trim((string)$row[$col_idx]) : '';
       
       if (empty($cell_value)) continue;

       // 필드별 패턴 매칭
       foreach ($field_patterns as $field => $patterns) {
           if (!empty($data[$field]) && !in_array($field, array('memo', 'korean_name', 'english_name'))) {
               continue; // 이미 채워진 필드는 스킵 (이름과 메모 제외)
           }
           
           $matched = false;
           foreach ($patterns as $pattern) {
               if (preg_match($pattern, $header_text)) {
                   $matched = true;
                   break;
               }
           }
           
           if ($matched) {
               switch ($field) {
                   case 'korean_name':
                       if (preg_match('/[가-힣]/u', $cell_value)) {
                           $data[$field] = $cell_value;
                       }
                       break;
                       
                   case 'english_name':
                       if (preg_match('/[A-Za-z\s]/', $cell_value) && !preg_match('/[가-힣]/u', $cell_value)) {
                           $data[$field] = strtoupper($cell_value);
                       }
                       break;
                       
                   case 'passport_number':
                       $passport_clean = preg_replace('/[^A-Za-z0-9]/', '', $cell_value);
                       if (strlen($passport_clean) >= 6 && strlen($passport_clean) <= 12) {
                           $data[$field] = strtoupper($passport_clean);
                       }
                       break;
                       
                   case 'birth_date':
                       $formatted_birth = formatAdvancedDateFromPHPExcel($cell_value);
                       if (!empty($formatted_birth)) {
                           $data[$field] = $formatted_birth;
                       }
                       break;
                       
                   case 'phone':
                       $formatted_phone = formatPhoneNumber($cell_value);
                       if (!empty($formatted_phone)) {
                           $data[$field] = $formatted_phone;
                       }
                       break;
                       
                   case 'email':
                       if (filter_var($cell_value, FILTER_VALIDATE_EMAIL)) {
                           $data[$field] = strtolower($cell_value);
                       }
                       break;
                       
                   case 'gender':
                       $data[$field] = normalizeGender($cell_value);
                       break;
                       
                   case 'room_type':
                       $data[$field] = normalizeRoomType($cell_value);
                       break;
                       
                   case 'memo':
                       if (!empty($data[$field])) {
                           $data[$field] .= ' | ' . $cell_value;
                       } else {
                           $data[$field] = $cell_value;
                       }
                       break;
                       
                   default:
                       if (empty($data[$field])) {
                           $data[$field] = $cell_value;
                       }
                       break;
               }
               break; // 매칭된 필드에 대해서는 더 이상 검사하지 않음
           }
       }
   }
   
   // 후처리: 이름 필드 교차 보완
   if (empty($data['korean_name']) && !empty($data['english_name'])) {
       if (preg_match('/([가-힣]{2,})/', $data['english_name'], $matches)) {
           $data['korean_name'] = $matches[1];
           $data['english_name'] = trim(str_replace($matches[1], '', $data['english_name']));
       }
   }
   
   if (empty($data['english_name']) && !empty($data['korean_name'])) {
       if (preg_match('/([A-Za-z\s]{3,})/', $data['korean_name'], $matches)) {
           $data['english_name'] = strtoupper(trim($matches[1]));
           $data['korean_name'] = trim(str_replace($matches[1], '', $data['korean_name']));
       }
   }
   
   return $data;
}

/**
* PHPExcel에 최적화된 날짜 형식 변환
*/
function formatAdvancedDateFromPHPExcel($date_value) {
   if (empty($date_value)) return '';
   
   // PHPExcel 날짜 시리얼 번호 처리
   if (is_numeric($date_value)) {
       try {
           // Excel 날짜 시리얼 번호를 Unix 타임스탬프로 변환
           $unix_timestamp = PHPExcel_Shared_Date::ExcelToPHP($date_value);
           $year = (int)date('Y', $unix_timestamp);
           
           // 유효한 연도 범위 확인
           if ($year >= 1920 && $year <= ((int)date('Y') + 20)) {
               return date('Y-m-d', $unix_timestamp);
           }
       } catch (Exception $e) {
           // Excel 날짜 변환 실패 시 기존 로직 사용
       }
   }
   
   // 기존 날짜 형식 변환 로직 사용
   return formatAdvancedDate($date_value);
}

/**
* 전화번호 형식 정리 (개선된 버전)
*/
function formatPhoneNumber($phone_input) {
   if (empty($phone_input)) return '';
   
   $phone = trim((string)$phone_input);
   
   // 기본 정리 (숫자, +, -, (, ), 공백만 유지)
   $phone = preg_replace('/[^0-9\+\-\(\)\s]/', '', $phone);
   
   // 한국 전화번호 패턴 처리
   if (preg_match('/^(\d{2,3})[-\s]?(\d{3,4})[-\s]?(\d{4})$/', $phone, $matches)) {
       return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
   }
   
   // 국제번호 (+82-10-1234-5678)
   if (preg_match('/^\+?82[-\s]?(\d{1,2})[-\s]?(\d{3,4})[-\s]?(\d{4})$/', $phone, $matches)) {
       return '+82-' . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
   }
   
   // 연속된 숫자 (01012345678)
   if (preg_match('/^(\d{3})(\d{3,4})(\d{4})$/', $phone, $matches)) {
       return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
   }
   
   // 11자리 숫자 (휴대폰)
   if (preg_match('/^(\d{3})(\d{4})(\d{4})$/', $phone, $matches)) {
       return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
   }
   
   // 해외 번호는 그대로 유지
   if (preg_match('/^\+\d{1,3}[-\s]?\d/', $phone)) {
       return preg_replace('/\s+/', '-', $phone);
   }
   
   // 최소 길이 체크
   $phone_digits = preg_replace('/[^0-9]/', '', $phone);
   if (strlen($phone_digits) >= 8 && strlen($phone_digits) <= 15) {
       if (preg_match('/^(02|0[3-9]\d|01[016789])/', $phone_digits) || 
           preg_match('/^\+/', $phone) ||
           strlen($phone_digits) >= 10) {
           return $phone;
       }
   }
   
   return '';
}

/**
* CSV 파일 PHPExcel 처리
*/
function processAdvancedCSVWithPHPExcel($file_path, $filename) {
   global $use_phpspreadsheet;
   
   try {
       // CSV Reader 생성
       if (isset($use_phpspreadsheet) && $use_phpspreadsheet) {
           $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
           $reader->setInputEncoding('UTF-8');
           $reader->setDelimiter(',');
           $reader->setEnclosure('"');
       } else {
           $reader = PHPExcel_IOFactory::createReader('CSV');
           $reader->setDelimiter(',');
           $reader->setEnclosure('"');
           $reader->setInputEncoding('UTF-8');
       }
       
       // 인코딩 감지 및 변환
       $file_content = file_get_contents($file_path);
       $encoding = mb_detect_encoding($file_content, array('UTF-8', 'EUC-KR', 'CP949'), true);
       
       if ($encoding && strtoupper($encoding) !== 'UTF-8') {
           $file_content = mb_convert_encoding($file_content, 'UTF-8', $encoding);
           file_put_contents($file_path, $file_content);
       }
       
       $phpExcelObject = $reader->load($file_path);
       $worksheet = $phpExcelObject->getActiveSheet();
       
       $csv_data = extractDataFromWorksheet($worksheet);
       
       // 메모리 정리
       $phpExcelObject->disconnectWorksheets();
       unset($phpExcelObject);
       
       return extractAdvancedGroupDataFromPHPExcel($csv_data, $filename);
       
   } catch (Exception $e) {
       throw new Exception("CSV 파일 처리 오류: " . $e->getMessage());
   }
}

// -----------------------------------------------------------------------------
// 기존 함수들 (변경사항 없음)
// -----------------------------------------------------------------------------

/**
* 상품코드로 상품 정보 조회
*/
function getProductInfoByCode($product_code) {
   global $dbConn;
   
   if (empty($product_code)) {
       return array('success' => false, 'message' => '상품코드가 없습니다.');
   }
   
   try {
       $sql_check = sprintf(
           "SELECT * FROM product_master WHERE p_code = '%s'",
           mysql_real_escape_string($product_code, $dbConn)
       );
       
       $result_check = mysql_query($sql_check, $dbConn);
       if (!$result_check) {
           return array('success' => false, 'message' => '상품 조회 중 오류가 발생했습니다: ' . mysql_error($dbConn));
       }

       $row1 = mysql_fetch_assoc($result_check);
       mysql_free_result($result_check);
       
       return $row1;
   } catch (Exception $e) {
       error_log("상품 정보 조회 오류: " . $e->getMessage());
       return array('success' => false, 'message' => '조회 중 오류가 발생했습니다.');
   }
}

/**
* 객실타입 정규화
*/
function normalizeRoomType($room_type_input) {
   if (empty($room_type_input)) return '2r1p';

   $type_str = strtolower(trim((string)$room_type_input));
   $type_str = preg_replace('/\s+/', '', $type_str);

   if (strpos($type_str, '1') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '싱글') !== false || strpos($type_str, 'single') !== false || $type_str === '1r1p')) return '1r1p';
   if (strpos($type_str, '2') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '트윈') !== false || strpos($type_str, '더블') !== false || strpos($type_str, 'twin') !== false || strpos($type_str, 'double') !== false || $type_str === '2r1p')) return '2r1p';
   if (strpos($type_str, '3') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '트리플') !== false || strpos($type_str, 'triple') !== false || $type_str === '3r1p')) return '3r1p';
   if (strpos($type_str, '4') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '쿼드') !== false || strpos($type_str, 'quad') !== false || $type_str === '4r1p')) return '4r1p';
   
   return '2r1p';
}

/**
* 날짜 추출 함수
*/
function extractDatesFromText($text) {
   $dates = array('start' => '', 'end' => '');
   if (empty($text)) return $dates;
   
   // YYYY-MM-DD ~ YYYY-MM-DD 패턴
   $pattern_range = '/(\d{4}[\.\-\/]\s?\d{1,2}[\.\-\/]\s?\d{1,2})\s*(?:~|-|부터|에서)\s*(\d{4}[\.\-\/]\s?\d{1,2}[\.\-\/]\s?\d{1,2})/';
   if (preg_match($pattern_range, $text, $matches)) {
       $start_date = formatAdvancedDate($matches[1]);
       $end_date = formatAdvancedDate($matches[2]);
       if (!empty($start_date)) $dates['start'] = $start_date;
       if (!empty($end_date)) $dates['end'] = $end_date;
       if (!empty($start_date) && !empty($end_date)) return $dates;
   }
   
   $all_single_dates = findAllDatesInText($text);
   if (!empty($all_single_dates)) {
       if (empty($dates['start'])) $dates['start'] = $all_single_dates[0];
       if (count($all_single_dates) > 1 && empty($dates['end'])) {
           $start_timestamp = strtotime($all_single_dates[0]);
           for ($i = 1; $i < count($all_single_dates); $i++) {
               $potential_end_timestamp = strtotime($all_single_dates[$i]);
               if ($potential_end_timestamp > $start_timestamp && ($potential_end_timestamp - $start_timestamp) / (60*60*24) <= 30) {
                   $dates['end'] = $all_single_dates[$i];
                   break;
               }
           }
           if(empty($dates['end']) && strtotime($all_single_dates[count($all_single_dates)-1]) > $start_timestamp) {
               $dates['end'] = $all_single_dates[count($all_single_dates)-1];
           }
       }
   }
   return $dates;
}

/**
* 텍스트에서 모든 날짜 찾기
*/
function findAllDatesInText($text) {
   $found_dates = array();
   
   $patterns = array(
       '/\b(\d{4})([\.\-\/])(\d{1,2})\2(\d{1,2})\b/',
       '/\b(\d{2})([\.\-\/])(\d{1,2})\2(\d{1,2})\b/',
       '/\b(\d{4})(\d{2})(\d{2})\b(?!\d)/',
       '/\b(\d{1,2})([\.\-\/])(\d{1,2})\2(\d{4})\b/',
       '/\b(\d{4})\s*년\s*(\d{1,2})\s*월\s*(\d{1,2})\s*일\b/u'
   );
   
   $raw_matches = array();
   foreach ($patterns as $pattern) {
       if (preg_match_all($pattern, $text, $matches_for_pattern, PREG_SET_ORDER)) {
           foreach ($matches_for_pattern as $match_set) {
               $raw_matches[] = $match_set[0];
           }
       }
   }
   
   $unique_raw_matches = array_unique($raw_matches);
   foreach($unique_raw_matches as $date_str_candidate) {
       $formatted = formatAdvancedDate($date_str_candidate);
       if (!empty($formatted) && !in_array($formatted, $found_dates)) {
           $found_dates[] = $formatted;
       }
   }
   
   sort($found_dates);
   return $found_dates;
}

/**
* 개선된 날짜 포맷 함수
*/
function formatAdvancedDate($date_value) {
   if (empty($date_value) || (is_string($date_value) && strlen(trim($date_value)) === 0)) return '';
   
   $date_str = trim((string) $date_value);
   
   // Excel 숫자 날짜 처리
   if (is_numeric($date_str) && $date_str > 20000 && $date_str < 60000) {
       $float_date = floatval($date_str);
       $unix_timestamp = ($float_date - 25569) * 86400;

       try {
           $dateTime = new DateTime("@" . floor($unix_timestamp), new DateTimeZone('UTC'));
           $year = (int)$dateTime->format('Y');
           if ($year >= 1920 && $year <= ((int)date('Y') + 5)) { 
               return $dateTime->format('Y-m-d');
           }
       } catch (Exception $e) { }
   }
   
   $date_patterns = array(
       array('regex' => '/^(\d{4})([\.\-\/])(\d{1,2})\2(\d{1,2})$/', 'y' => 1, 'm' => 3, 'd' => 4),
       array('regex' => '/^(\d{4})(\d{2})(\d{2})$/', 'y' => 1, 'm' => 2, 'd' => 3, 'is_strict_digit' => true),
       array('regex' => '/^(\d{1,2})([\.\-\/])(\d{1,2})\2(\d{4})$/', 'y' => 4, 'm' => 1, 'd' => 3),
       array('regex' => '/^(\d{4})\s*년\s*(\d{1,2})\s*월\s*(\d{1,2})\s*일$/u', 'y' => 1, 'm' => 2, 'd' => 3),
       array('regex' => '/^(\d{2})([\.\-\/])(\d{1,2})\2(\d{1,2})$/', 'y_short' => 1, 'm' => 3, 'd' => 4),
       array('regex' => '/^(\d{4})([\.\-\/])(\d{1,2})$/', 'y' => 1, 'm' => 3, 'd_fixed' => 1),
       array('regex' => '/^(\d{4})\s*년\s*(\d{1,2})\s*월$/u', 'y' => 1, 'm' => 2, 'd_fixed' => 1),
   );

   foreach ($date_patterns as $pattern_info) {
       if (isset($pattern_info['is_strict_digit']) && $pattern_info['is_strict_digit'] && !ctype_digit($date_str)) {
           continue;
       }
       if (preg_match($pattern_info['regex'], $date_str, $matches)) {
           $year = 0; $month = 0; $day = 0;

           if (isset($pattern_info['y_short'])) {
               $year_short = (int)$matches[$pattern_info['y_short']];
               $current_century_short_year = (int)date('y');
               $year = ($year_short <= ($current_century_short_year + 10)) ? (2000 + $year_short) : (1900 + $year_short);
           } elseif(isset($pattern_info['y'])) {
               $year = (int)$matches[$pattern_info['y']];
           }

           if(isset($pattern_info['m'])) $month = (int)$matches[$pattern_info['m']];
           if(isset($pattern_info['d'])) $day = (int)$matches[$pattern_info['d']];
           if(isset($pattern_info['d_fixed'])) $day = (int)$pattern_info['d_fixed'];

           if ($year >= 1920 && $year <= ((int)date('Y') + 20) && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
               if (checkdate($month, $day, $year)) {
                  return sprintf('%04d-%02d-%02d', $year, $month, $day);
              }
           }
       }
   }
   
   try {
       $normalized_date_str = str_replace(array('.', '/', '년', '월', '일'), array('-', '-', '', '', ''), $date_str);
       $normalized_date_str = preg_replace('/\s+/', '-', $normalized_date_str);
       $timestamp = strtotime($normalized_date_str);
       if ($timestamp !== false) {
           $year_from_strtotime = (int)date('Y', $timestamp);
           if ($year_from_strtotime >= 1920 && $year_from_strtotime <= ((int)date('Y') + 20)) {
               return date('Y-m-d', $timestamp);
           }
       }
   } catch(Exception $e){ }

   return '';
}

/**
* 최적의 날짜 쌍 찾기
*/
function findBestDatePair($date_candidates) {
   if (empty($date_candidates)) return null;
   
   $best_pair = null;
   $max_score = -1;

   foreach ($date_candidates as $candidate) {
       $current_score = 0;
       $has_start = !empty($candidate['start']);
       $has_end = !empty($candidate['end']);

       if ($has_start && $has_end) {
           $current_score += 10;
           $start_time = strtotime($candidate['start']);
           $end_time = strtotime($candidate['end']);
           if ($start_time && $end_time && $end_time > $start_time) {
               $days_diff = ($end_time - $start_time) / (24 * 60 * 60);
               if ($days_diff >= 0 && $days_diff <= 365) {
                   $current_score += 5;
               }
               $current_year = (int)date('Y');
               $start_year = (int)date('Y', $start_time);
               if (abs($start_year - $current_year) <= 5) {
                   $current_score += 3;
               }
           } else {
               $current_score -=5;
           }
       } elseif ($has_start) {
           $current_score += 3;
       } elseif ($has_end) {
           $current_score += 1;
       }

       if ($current_score > $max_score) {
           $max_score = $current_score;
           $best_pair = $candidate;
       }
   }
   return $best_pair;
}

/**
* 성별 정규화
*/
function normalizeGender($gender_input) {
   if (empty($gender_input)) return '';
   
   $gender_str = trim((string)$gender_input);
   $cleaned_gender = preg_replace('/[\s\(\)\[\]\*\-\_]+/', '', $gender_str);
   $gender_lower = strtolower($cleaned_gender);

   if (in_array($gender_lower, array('m', 'male', 'man', 'boy', 'gentleman', '남', '남성', '남자', '1', '0'))) return 'male';
   if (in_array($gender_lower, array('f', 'female', 'woman', 'girl', 'lady', '여', '여성', '여자', '2'))) return 'female';

   if (preg_match('/(남|male|man)/ui', $gender_str)) return 'male';
   if (preg_match('/(여|female|woman)/ui', $gender_str)) return 'female';
   
   return '';
}

/**
* 기본 그룹 정보 설정
*/
function setDefaultGroupInfo(&$group_info, $filename, $travelers) {
   if (empty($group_info['tour_name'])) {
       $group_info['tour_name'] = pathinfo($filename, PATHINFO_FILENAME);
       $group_info['tour_name'] = preg_replace('/\s*\(Sheet:[^\)]+\)/i', '', $group_info['tour_name']);
   }
   
   if (empty($group_info['product_code'])) {
       $extracted_code = extractProductCodeFromFilename($filename);
       $arr_pro = getProductInfoByCode($extracted_code);
       $group_info['product_code'] = $arr_pro['p_code'];
   }
   
   $today = date('Y-m-d');
   if (empty($group_info['start_date']) || $group_info['start_date'] === $today) {
       $group_info['start_date'] = date('Y-m-d', strtotime('+7 days'));
   }
   
   if (empty($group_info['end_date']) || $group_info['end_date'] === $today || strtotime($group_info['end_date']) <= strtotime($group_info['start_date'])) {
       $group_info['end_date'] = date('Y-m-d', strtotime($group_info['start_date'] . ' +7 days'));
   }
   
   if (empty($group_info['group_leader']) && !empty($travelers)) {
       $first_traveler_with_name = null;
       foreach($travelers as $t){
           if(!empty($t['korean_name']) || !empty($t['english_name'])){
               $first_traveler_with_name = $t;
               break;
           }
       }
       if($first_traveler_with_name){
           $group_info['group_leader'] = !empty($first_traveler_with_name['korean_name']) ? $first_traveler_with_name['korean_name'] : $first_traveler_with_name['english_name'];
           if(empty($group_info['group_phone']) && !empty($first_traveler_with_name['phone'])) $group_info['group_phone'] = $first_traveler_with_name['phone'];
           if(empty($group_info['group_email']) && !empty($first_traveler_with_name['email'])) $group_info['group_email'] = $first_traveler_with_name['email'];
       }
   }
}

/**
* 중복 여행자 제거
*/
function removeDuplicateTravelers($travelers) {
   $unique_travelers_map = array();
   $final_unique_travelers = array();

   foreach ($travelers as $traveler) {
       $k_name = trim(strtolower(isset($traveler['korean_name']) ? $traveler['korean_name'] : ''));
       $e_name = trim(strtolower(isset($traveler['english_name']) ? $traveler['english_name'] : ''));
       
       $name_key = '';
       if (!empty($k_name)) $name_key = $k_name;
       elseif (!empty($e_name)) $name_key = $e_name;
       
       if (empty($name_key)) {
           $final_unique_travelers[] = $traveler;
           continue;
       }
       
       if (!isset($unique_travelers_map[$name_key])) {
           $unique_travelers_map[$name_key] = $traveler;
       } else {
           $existing = $unique_travelers_map[$name_key];
           $current = $traveler;
           
           $existing_score = calculateDataCompleteness($existing);
           $current_score = calculateDataCompleteness($current);
           
           if ($current_score > $existing_score) {
               $unique_travelers_map[$name_key] = $current;
           }
       }
   }
   
   return array_values($unique_travelers_map);
}

/**
* 여행자 데이터 완성도 점수 계산
*/
function calculateDataCompleteness($traveler) {
   $score = 0;
   
   $fields_weight = array(
       'korean_name' => 2,
       'english_name' => 2,
       'passport_number' => 3,
       'birth_date' => 2,
       'phone' => 1,
       'email' => 1,
       'gender' => 1,
       'room_type' => 1,
       'room_number' => 1,
       'memo' => 0.5
   );
   
   foreach ($fields_weight as $field => $weight) {
       $value = trim((string)(isset($traveler[$field]) ? $traveler[$field] : ''));
       if (!empty($value)) {
           $score += $weight;
       }
   }
   
   return $score;
}

/**
* 파일명에서 상품코드 추출
*/
function extractProductCodeFromFilename($filename) {
   if (preg_match('/[\[\{]([A-Za-z0-9_@#\/.+-]+)[\]\}]/', $filename, $matches)) {
       return trim($matches[1]);
   }
   if (preg_match('/\b([A-Z]{2,5}[-_]?[0-9]{3,6})\b/i', $filename, $matches)) {
       return strtoupper(trim($matches[1]));
   }
   return '';
}

/**
* 셀에서 그룹 정보 추출
*/
function extractGroupInfoFromCell($cell_text, &$group_info, $row, $cell_idx) {
   $cell_lower = strtolower($cell_text);
   
   $next_value = '';
   for ($k = $cell_idx + 1; $k < count($row) && $k <= $cell_idx + 3; $k++) {
       $val = trim((string)(isset($row[$k]) ? $row[$k] : ''));
       if (!empty($val)) {
           $next_value = $val;
           break;
       }
   }
   
   if (empty($next_value) && strpos($cell_text, ':') !== false) {
       $parts = explode(':', $cell_text, 2);
       if (count($parts) > 1) {
           $potential_value = trim($parts[1]);
           if (!empty($potential_value)) $next_value = $potential_value;
       }
   }

   if (empty($group_info['product_code']) && (strpos($cell_lower, '상품코드') !== false || strpos($cell_lower, '코드') !== false || strpos($cell_lower, '코드명') !== false)) {
       if(!empty($next_value)) $group_info['product_code'] = $next_value;
   }

   if (empty($group_info['tour_name']) && (strpos($cell_lower, '상품명') !== false || strpos($cell_lower, '행사명') !== false || strpos($cell_lower, '투어명') !== false)) {
       if(!empty($next_value)) $group_info['tour_name'] = $next_value;
   }
   
   if (empty($group_info['group_leader']) && (strpos($cell_lower, '대표자') !== false || strpos($cell_lower, '인솔자') !== false || strpos($cell_lower, '담당자') !== false)) {
       if(!empty($next_value)) $group_info['group_leader'] = $next_value;
   }
   
   if (empty($group_info['group_phone']) && (strpos($cell_lower, '연락처') !== false || strpos($cell_lower, '전화번호') !== false) && (strpos($cell_lower, '여행자') === false && strpos($cell_lower, '참가자') === false)) {
       if(!empty($next_value)) $group_info['group_phone'] = $next_value;
   }
   
   if (empty($group_info['group_email']) && (strpos($cell_lower, '이메일') !== false || strpos($cell_lower, '메일') !== false) && (strpos($cell_lower, '여행자') === false && strpos($cell_lower, '참가자') === false)) {
       if(!empty($next_value)) $group_info['group_email'] = $next_value;
   }
}

/**
* 텍스트에서 그룹 정보 추출
*/
function extractGroupInfoFromText($text, &$group_info) {
   if (empty($group_info['product_code'])) {
       if (preg_match('/[\[\{]([A-Za-z0-9_-]+)[\]\}]/', $text, $matches)) {
           $group_info['product_code'] = $matches[1];
       } elseif (preg_match('/\b([A-Z]{2,5}-?\d{3,6}\b)/', $text, $matches)) {
           $group_info['product_code'] = $matches[1];
       }
   }
   if (empty($group_info['tour_name'])) {
       if (preg_match('/(투어명|상품명|행사명)\s*[:\-]?\s*([^\n\r\(]+)/ui', $text, $matches)) {
           $group_info['tour_name'] = trim($matches[2]);
       }
   }
}

/**
* 여행자 데이터 유효성 검사
*/
function validateTravelerData($data) {
   $errors = array();
   
   if (empty($data['korean_name']) && empty($data['english_name'])) {
       $errors[] = '한글성명 또는 영문성명 중 하나는 필수입니다.';
   }
   
   if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
       $errors[] = '이메일 형식이 올바르지 않습니다.';
   }
   
   if (!empty($data['phone']) && !preg_match('/^[0-9\+\-\s\(\)]{8,15}$/', $data['phone'])) {
       $errors[] = '전화번호 형식이 올바르지 않습니다.';
   }
   
   if (!empty($data['birth_date']) && formatAdvancedDate($data['birth_date']) === '') {
       $errors[] = '생년월일 형식이 올바르지 않습니다.';
   }
   
   return array(
       'valid' => empty($errors),
       'message' => empty($errors) ? '유효한 데이터입니다.' : implode('<br>', $errors)
   );
}

/**
* 그룹 예약 저장
*/
function saveGroupReservation($group_data, $travelers_data, $partner_id) {
   global $dbConn;
   
   if (!$dbConn) {
       return array('success' => false, 'message' => 'DB 연결을 찾을 수 없습니다.');
   }
   
   mysql_query("START TRANSACTION", $dbConn);
   
   try {
       $grandNum = time() + mt_rand(1, 10000);
       $grand_revNo = 'TU' . date('ymdHis') . mt_rand(1, 9);
       $current_reserveCode = 'PUR' . date('ymdHis') . mt_rand(1, 9);
       
       $total_amount_from_group_data = isset($group_data['total_amount']) ? floatval($group_data['total_amount']) : 0.0;
       
       // 1. grand_reserve 저장
       $sql_grand = sprintf(
           "INSERT INTO grand_reserve (
               grandNum, grand_revNo, revNo, tour_type, p_code, p_name, 
               tot_amt, revDate, stDate, wdate
           ) VALUES ('%s', '%s', '%s', '3', '%s', '%s', '%.2f', NOW(), '%s', NOW())",
           mysql_real_escape_string($grandNum, $dbConn),
           mysql_real_escape_string($grand_revNo, $dbConn),
           mysql_real_escape_string($current_reserveCode, $dbConn),
           mysql_real_escape_string($group_data['product_code'], $dbConn),
           mysql_real_escape_string($group_data['tour_name'], $dbConn),
           $total_amount_from_group_data,
           mysql_real_escape_string($group_data['start_date'], $dbConn)
       );
       
       $result_grand = mysql_query($sql_grand, $dbConn);
       if (!$result_grand) {
           throw new Exception("grand_reserve 저장 실패: " . mysql_error($dbConn));
       }
       
       // 2. reserve_info 저장
       $traveler_count = count($travelers_data);
       $group_memo = isset($group_data['memo']) ? $group_data['memo'] : '';
       $reserveNum = $grandNum;
       
       $sql_info = sprintf(
           "INSERT INTO reserve_info (
               grandNum, grand_revNo, reserveNum, reserveCode, tour_type, 
               p_code, p_name, parent, revDate, stDate, edDate, 
               p_cnt, book_pri, book_phone, book_email, 
               last_total, rev_status, userid, wdate
           ) VALUES ('%s', '%s', '%s', '%s', '3', '%s', '%s', 'MAIN', NOW(), '%s', '%s', 
                    '%d', '%s', '%s', '%s', '%.2f', 'READY', '%s', NOW())",
           mysql_real_escape_string($grandNum, $dbConn),
           mysql_real_escape_string($grand_revNo, $dbConn),
           mysql_real_escape_string($reserveNum, $dbConn),
           mysql_real_escape_string($current_reserveCode, $dbConn),
           mysql_real_escape_string($group_data['product_code'], $dbConn),
           mysql_real_escape_string($group_data['tour_name'], $dbConn),
           mysql_real_escape_string($group_data['start_date'], $dbConn),
           mysql_real_escape_string($group_data['end_date'], $dbConn),
           $traveler_count,
           mysql_real_escape_string($group_data['group_leader'], $dbConn),
           mysql_real_escape_string($group_data['group_phone'], $dbConn),
           mysql_real_escape_string($group_data['group_email'], $dbConn),
           $total_amount_from_group_data,
           mysql_real_escape_string($partner_id, $dbConn)
       );
       
       $result_info = mysql_query($sql_info, $dbConn);
       if (!$result_info) {
           throw new Exception("reserve_info 저장 실패: " . mysql_error($dbConn));
       }
       
       // 3. reserve_traveler 저장
       foreach ($travelers_data as $seq => $traveler) {
           $room_number = !empty($traveler['room_number']) ? $traveler['room_number'] : ($seq + 1);
           $birth_date_to_save = formatAdvancedDate($traveler['birth_date']);
           $gender_to_save = normalizeGender($traveler['gender']);
           $room_type_to_save = normalizeRoomType($traveler['room_type']);
           
           $sql_traveler = sprintf(
               "INSERT INTO reserve_traveler (
                   grand_revNo, reserveCode, traveler_nm, traveler_enm, 
                   traveler_phone, traveler_email, traveler_birth, traveler_room, 
                   seqint, sextype, room_type, pass_num, e_memo, wdate
               ) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', NOW())",
               mysql_real_escape_string($grand_revNo, $dbConn),
               mysql_real_escape_string($current_reserveCode, $dbConn),
               mysql_real_escape_string($traveler['korean_name'], $dbConn),
               mysql_real_escape_string($traveler['english_name'], $dbConn),
               mysql_real_escape_string($traveler['phone'], $dbConn),
               mysql_real_escape_string($traveler['email'], $dbConn),
               mysql_real_escape_string($birth_date_to_save, $dbConn),
               $room_number,
               ($seq + 1),
               mysql_real_escape_string($gender_to_save, $dbConn),
               mysql_real_escape_string($room_type_to_save, $dbConn),
               mysql_real_escape_string($traveler['passport_number'], $dbConn),
               mysql_real_escape_string($traveler['memo'], $dbConn)
           );
           
           $result_traveler = mysql_query($sql_traveler, $dbConn);
           if (!$result_traveler) {
               throw new Exception("reserve_traveler 저장 실패 (여행자 " . ($seq + 1) . "): " . mysql_error($dbConn));
           }
       }
       
       mysql_query("COMMIT", $dbConn);
       
       return array(
           'success' => true, 
           'message' => '그룹 예약이 성공적으로 등록되었습니다. 예약번호: ' . $grand_revNo,
           'grand_revNo' => $grand_revNo,
           'grandNum' => $grandNum,
           'traveler_count' => $traveler_count
       );
       
   } catch (Exception $e) {
       mysql_query("ROLLBACK", $dbConn);
       
       error_log("그룹 예약 저장 실패: " . $e->getMessage() . " | Partner ID: " . $partner_id . " | Group Data: " . json_encode($group_data));
       
       return array(
           'success' => false, 
           'message' => '예약 저장 중 오류가 발생했습니다. 관리자에게 문의해주세요. (오류: ' . $e->getMessage() . ')'
       );
   }
}

/**
* 요일 코드를 텍스트로 변환
*/
function convertWeekCodeToText($week_code) {
   if (empty($week_code)) return '';
   
   $week_names = array(
       '1' => '월', '2' => '화', '3' => '수', '4' => '목', 
       '5' => '금', '6' => '토', '7' => '일'
   );
   
   $days = array();
   for ($i = 0; $i < strlen($week_code); $i++) {
       $day_code = substr($week_code, $i, 1);
       if (isset($week_names[$day_code])) {
           $days[] = $week_names[$day_code];
       }
   }
   
   return implode(', ', $days);
}

/**
* 날짜 유효성 검사 설정
*/
function setupDateValidation() {
   // JavaScript에서 호출되므로 빈 함수
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>푸른투어-스마트등록 (PHPExcel 강화버전)</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   <style>
       body {
           background-color: #f8f9fa;
           font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
       }
       
       .navbar {
           background: linear-gradient(135deg, #131176 0%, #131176 100%);
           box-shadow: 0 2px 4px rgba(0,0,0,.1);
       }
       
       .main-container {
           max-width: 1400px;
           margin: 0 auto;
           padding: 30px 20px;
       }
       
       .page-header {
           background: white;
           border-radius: 15px;
           padding: 30px;
           margin-bottom: 30px;
           box-shadow: 0 5px 15px rgba(0,0,0,0.08);
           text-align: center;
       }
       
       .phpexcel-badge {
           background: linear-gradient(135deg, #28a745, #20c997);
           color: white;
           padding: 8px 16px;
           border-radius: 20px;
           font-size: 0.9rem;
           font-weight: 600;
           display: inline-block;
           margin-top: 10px;
       }
       
       .upload-card {
           background: white;
           border-radius: 15px;
           padding: 30px;
           box-shadow: 0 5px 15px rgba(0,0,0,0.08);
           margin-bottom: 30px;
           border: 2px dashed #dee2e6;
           transition: all 0.3s ease;
       }
       
       .upload-card.dragover {
           border-color: #131176;
           background-color: #f8f9ff;
       }
       
       .upload-zone {
           text-align: center;
           padding: 40px 20px;
           cursor: pointer;
       }
       
       .upload-icon {
           font-size: 4rem;
           color: #131176;
           margin-bottom: 20px;
       }
       
       .file-input {
           display: none;
       }
       
       .btn-upload {
           background: linear-gradient(135deg, #131176, #131176);
           border: none;
           color: white;
           padding: 12px 30px;
           border-radius: 10px;
           font-weight: 600;
           transition: transform 0.2s ease;
       }
       
       .btn-upload:hover {
           transform: translateY(-2px);
           color: white;
       }
       
       .features-grid {
           display: grid;
           grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
           gap: 20px;
           margin-top: 20px;
       }
       
       .feature-card {
           background: #f8f9fa;
           border-radius: 10px;
           padding: 20px;
           border-left: 4px solid #28a745;
       }
       
       .feature-card h6 {
           color: #131176;
           font-weight: 600;
           margin-bottom: 10px;
       }
       
       .feature-card ul {
           margin-bottom: 0;
           font-size: 0.9rem;
       }
       
       .group-info-card {
           background: white;
           border-radius: 15px;
           padding: 30px;
           box-shadow: 0 5px 15px rgba(0,0,0,0.08);
           margin-bottom: 30px;
           border-left: 5px solid #131176;
       }
       
       .travelers-card {
           background: white;
           border-radius: 15px;
           padding: 30px;
           box-shadow: 0 5px 15px rgba(0,0,0,0.08);
           margin-bottom: 30px;
       }
       
       .btn-save {
           background: linear-gradient(135deg, #28a745, #20c997);
           border: none;
           color: white;
           padding: 15px 40px;
           border-radius: 10px;
           font-weight: 600;
           font-size: 1.1rem;
       }
       
       .alert-modern {
           border: none;
           border-radius: 10px;
           padding: 20px;
           margin-bottom: 20px;
       }
       
       .stats-row {
           background: linear-gradient(135deg, #131176, #131176);
           color: white;
           border-radius: 10px;
           padding: 20px;
           margin-bottom: 20px;
       }
       
       .stats-item {
           text-align: center;
       }
       
       .stats-number {
           font-size: 2rem;
           font-weight: 700;
       }
       
       .loading {
           display: none;
           text-align: center;
           padding: 40px;
       }
       
       .spinner {
           width: 40px;
           height: 40px;
           border: 4px solid #f3f3f3;
           border-top: 4px solid #131176;
           border-radius: 50%;
           animation: spin 1s linear infinite;
       }
       
       @keyframes spin {
           0% { transform: rotate(0deg); }
           100% { transform: rotate(360deg); }
       }
       
       .form-control:focus {
           border-color: #131176;
           box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
       }
       
       .table-actions {
           display: flex;
           gap: 5px;
           justify-content: center;
       }
       
       .btn-action {
           padding: 5px 8px;
           border: none;
           border-radius: 4px;
           cursor: pointer;
           font-size: 0.8rem;
           transition: all 0.2s ease;
           width: 30px;
           height: 30px;
           display: flex;
           align-items: center;
           justify-content: center;
       }
       
       .btn-edit {
           background: linear-gradient(135deg, #74b9ff, #0984e3);
           color: white;
       }
       
       .btn-delete {
           background: linear-gradient(135deg, #fd79a8, #e84393);
           color: white;
       }
       
       .btn-duplicate {
           background: linear-gradient(135deg, #fdcb6e, #e17055);
           color: white;
       }
       
       .btn-action:hover {
           transform: translateY(-1px);
           box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
       }
       
       .modal-overlay {
           position: fixed;
           top: 0;
           left: 0;
           width: 100%;
           height: 100%;
           background: rgba(0, 0, 0, 0.7);
           display: none;
           z-index: 10000;
           backdrop-filter: blur(5px);
       }
       
       .modal-content {
           position: fixed;
           top: 50%;
           left: 50%;
           transform: translate(-50%, -50%);
           background: white;
           border-radius: 15px;
           padding: 30px;
           max-width: 600px;
           width: 90%;
           max-height: 80vh;
           overflow-y: auto;
           box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
       }
       
       .modal-header {
           display: flex;
           justify-content: space-between;
           align-items: center;
           margin-bottom: 20px;
           padding-bottom: 15px;
           border-bottom: 2px solid #f0f0f0;
       }
       
       .modal-title {
           font-size: 1.5rem;
           font-weight: 700;
           color: #333;
           margin: 0;
       }
       
       .modal-close {
           background: none;
           border: none;
           font-size: 1.5rem;
           cursor: pointer;
           color: #999;
           padding: 0;
           width: 30px;
           height: 30px;
           display: flex;
           align-items: center;
           justify-content: center;
           border-radius: 50%;
           transition: all 0.2s ease;
       }
       
       .modal-close:hover {
           background: #f0f0f0;
           color: #666;
       }
       
       .toolbar {
           background: #f8f9fa;
           padding: 15px 20px;
           border-radius: 10px;
           margin-bottom: 20px;
           display: flex;
           justify-content: space-between;
           align-items: center;
           flex-wrap: wrap;
           gap: 10px;
       }
       
       .btn-add {
           background: linear-gradient(135deg, #00b894, #00a085);
           color: white;
           border: none;
           padding: 10px 20px;
           border-radius: 8px;
           font-weight: 600;
           cursor: pointer;
           transition: all 0.2s ease;
       }
       
       .btn-add:hover {
           transform: translateY(-1px);
           box-shadow: 0 5px 15px rgba(0, 184, 148, 0.3);
       }
       
       .search-input {
           padding: 8px 12px;
           border: 2px solid #e0e0e0;
           border-radius: 6px;
           font-size: 0.9rem;
           min-width: 200px;
       }
       
       .search-input:focus {
           outline: none;
           border-color: #131176;
       }
       
       .phpexcel-status {
           background: linear-gradient(135deg, #28a745, #20c997);
           color: white;
           padding: 10px 15px;
           border-radius: 8px;
           font-size: 0.9rem;
           margin-bottom: 20px;
           display: flex;
           align-items: center;
           gap: 10px;
       }
       
       .phpexcel-status.error {
           background: linear-gradient(135deg, #dc3545, #c82333);
       }
       
       .phpexcel-status i {
           font-size: 1.2rem;
       }
   </style>
</head>
<body>
   <nav class="navbar navbar-expand-lg">
       <div class="container">
           <a class="navbar-brand text-white" href="input_batch.php">
               <i class="fas fa-arrow-left me-2"></i> 푸른투어 - 스마트등록 (PHPExcel 강화)
           </a>
           <div class="navbar-nav ms-auto">
               <span class="nav-link text-white">
                   <i class="fas fa-users"></i> 스마트 그룹 예약 등록
               </span>
           </div>
       </div>
   </nav>

   <div class="main-container">
       <div class="page-header">
           <h1><i class="fas fa-magic"></i> 스마트 그룹 예약 등록</h1>
           <p class="text-muted mb-2">PHPExcel 라이브러리로 강화된 Excel 처리 시스템</p>
           <?php if ($phpexcel_loaded): ?>
               <div class="phpexcel-badge">
                   <i class="fas fa-check-circle"></i> 
                   <?php echo isset($use_phpspreadsheet) ? 'PhpSpreadsheet' : 'PHPExcel'; ?> 라이브러리 활성화됨
               </div>
           <?php else: ?>
               <div class="phpexcel-badge" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                   <i class="fas fa-exclamation-triangle"></i> PHPExcel 라이브러리 필요
               </div>
           <?php endif; ?>
       </div>

       <?php if (!$phpexcel_loaded): ?>
       <div class="alert alert-warning alert-modern">
           <h5><i class="fas fa-exclamation-triangle"></i> PHPExcel 라이브러리가 필요합니다</h5>
           <p class="mb-2">고급 Excel 처리 기능을 사용하려면 다음 중 하나를 설치해주세요:</p>
           <ul class="mb-0">
               <li><strong>PHPExcel (레거시):</strong> <code>composer require phpoffice/phpexcel</code></li>
               <li><strong>PhpSpreadsheet (권장):</strong> <code>composer require phpoffice/phpspreadsheet</code></li>
           </ul>
           <p class="mt-2 mb-0"><small>라이브러리가 없어도 기본 처리는 가능하지만, 병합된 셀이나 복잡한 Excel 파일 처리에 제한이 있을 수 있습니다.</small></p>
       </div>
       <?php endif; ?>

       <?php if (!empty($error_messages)): ?>
       <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
           <h5><i class="fas fa-exclamation-circle"></i> 오류 발생</h5>
           <?php foreach ($error_messages as $error): ?>
               <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
           <?php endforeach; ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
       </div>
       <?php endif; ?>

       <?php if (!empty($success_messages)): ?>
       <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
           <h5><i class="fas fa-check-circle"></i> 처리 완료</h5>
           <?php foreach ($success_messages as $success): ?>
               <div><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
           <?php endforeach; ?>
           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
       </div>
       <?php endif; ?>

       <div class="alert alert-info alert-modern">
           <h5><i class="fas fa-lightbulb"></i> PHPExcel 강화 기능</h5>
           <div class="features-grid">
               <div class="feature-card">
                   <h6>🔬 정밀 Excel 분석</h6>
                   <ul class="mb-0">
                       <li>병합된 셀 완벽 처리</li>
                       <li>Excel 날짜 시리얼 번호 자동 변환</li>
                       <li>셀 서식 정보 활용</li>
                       <li>수식 계산 결과 추출</li>
                   </ul>
               </div>
               <div class="feature-card">
                   <h6>📊 향상된 데이터 인식</h6>
                   <ul class="mb-0">
                       <li>헤더 행 자동 감지 (점수 기반)</li>
                       <li>다양한 전화번호 형식 정규화</li>
                       <li>이메일 유효성 실시간 검증</li>
                       <li>성별/객실타입 스마트 매칭</li>
                   </ul>
               </div>
               <div class="feature-card">
                   <h6>🔄 메모리 최적화</h6>
                   <ul class="mb-0">
                       <li>읽기 전용 모드로 성능 향상</li>
                       <li>빈 셀 처리 최적화</li>
                       <li>워크시트 자동 해제</li>
                       <li>대용량 파일 안정 처리</li>
                   </ul>
               </div>
               <div class="feature-card">
                   <h6>🎯 스마트 추출</h6>
                   <ul class="mb-0">
                       <li>그룹 정보 자동 탐지</li>
                       <li>여행자 데이터 중복 제거</li>
                       <li>완성도 기반 데이터 선택</li>
                       <li>파일명에서 상품코드 추출</li>
                   </ul>
               </div>
           </div>
       </div>

       <div class="upload-card" id="uploadCard">
           <form method="post" enctype="multipart/form-data" id="uploadForm">
               <div class="upload-zone">
                   <div class="upload-icon">
                       <i class="fas fa-brain"></i>
                   </div>
                   <h4>PHPExcel 강화 분석 시스템</h4>
                   <p class="text-muted mb-3">Excel(xlsx, xls) 또는 CSV 파일을 업로드하세요. PHPExcel로 더 정확한 분석이 가능합니다.</p>
                   
                   <input type="file" id="fileInput" name="reservation_files[]" 
                          class="file-input" accept=".xlsx,.xls,.csv" multiple> 
                   <button type="button" class="btn btn-upload" onclick="document.getElementById('fileInput').click()">
                       <i class="fas fa-upload"></i> 파일 선택
                   </button>
                   
                   <?php if ($phpexcel_loaded): ?>
                   <div class="phpexcel-status mt-3">
                       <i class="fas fa-check-circle"></i>
                       <span>병합된 셀, Excel 날짜, 수식 등을 완벽하게 처리합니다</span>
                   </div>
                   <?php else: ?>
                   <div class="phpexcel-status error mt-3">
                       <i class="fas fa-info-circle"></i>
                       <span>기본 처리 모드 - PHPExcel 설치 시 더 정확한 분석 가능</span>
                   </div>
                   <?php endif; ?>
               </div>
               
               <div id="fileListContainer" style="margin-top: 20px;"></div>
               
               <div class="text-center mt-3">
                   <button type="submit" class="btn btn-upload" id="processBtn" style="display: none;">
                       <i class="fas fa-cogs"></i> <?php echo $phpexcel_loaded ? 'PHPExcel로 정밀 분석 시작' : '기본 분석 시작'; ?>
                   </button>
               </div>
           </form>
       </div>

       <div class="loading" id="loading">
           <div class="spinner mx-auto mb-3"></div>
           <p><?php echo $phpexcel_loaded ? 'PHPExcel로 파일을 정밀 분석하여' : 'AI가 파일을 분석하여'; ?> 그룹 정보와 여행자 목록을 추출하고 있습니다. 잠시만 기다려주세요...</p>
       </div>

       <?php if (!empty($processed_data) && !empty($group_info)): ?>
       <form id="groupReservationForm">
           <div class="group-info-card">
               <h4><i class="fas fa-cog"></i> 그룹 예약 정보 <small class="text-muted">(수정 가능)</small></h4>
               
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label"><i class="fas fa-tag"></i> 상품명/투어명 *</label>
                           <input type="text" name="tour_name" class="form-control" 
                                  value="<?php echo htmlspecialchars(isset($group_info['tour_name']) ? $group_info['tour_name'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                       </div>
                       
                       <div class="mb-3">
                           <label class="form-label"><i class="fas fa-code"></i> 상품 코드</label>
                           <input type="text" name="product_code" class="form-control" 
                                  value="<?php echo htmlspecialchars(isset($group_info['product_code']) ? $group_info['product_code'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                       </div>
                       
                       <div class="row">
                           <div class="col-6">
                               <div class="mb-3">
                                   <label class="form-label"><i class="fas fa-calendar-alt"></i> 출발일 *</label>
                                   <input type="date" name="start_date" class="form-control" 
                                          value="<?php echo htmlspecialchars(isset($group_info['start_date']) ? $group_info['start_date'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                               </div>
                           </div>
                           <div class="col-6">
                               <div class="mb-3">
                                   <label class="form-label"><i class="fas fa-calendar-check"></i> 도착일</label>
                                   <input type="date" name="end_date" class="form-control" 
                                          value="<?php echo htmlspecialchars(isset($group_info['end_date']) ? $group_info['end_date'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </div>
                           </div>
                       </div>
                   </div>
                   
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label"><i class="fas fa-user-tie"></i> 그룹 대표자 *</label>
                           <input type="text" name="group_leader" class="form-control" 
                                  value="<?php echo htmlspecialchars(isset($group_info['group_leader']) ? $group_info['group_leader'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                       </div>
                       
                       <div class="mb-3">
                           <label class="form-label"><i class="fas fa-phone"></i> 대표 연락처</label>
                           <input type="tel" name="group_phone" class="form-control" 
                                  value="<?php echo htmlspecialchars(isset($group_info['group_phone']) ? $group_info['group_phone'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                       </div>
                       
                       <div class="mb-3">
                           <label class="form-label"><i class="fas fa-envelope"></i> 대표 이메일</label>
                           <input type="email" name="group_email" class="form-control" 
                                  value="<?php echo htmlspecialchars(isset($group_info['group_email']) ? $group_info['group_email'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                       </div>
                   </div>
               </div>
               
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label"><i class="fas fa-dollar-sign"></i> 총 예약 금액</label>
                           <input type="number" name="total_amount" class="form-control" step="0.01" min="0" 
                                  value="<?php echo htmlspecialchars(isset($group_info['total_amount']) ? $group_info['total_amount'] : '0', ENT_QUOTES, 'UTF-8'); ?>">
                       </div>
                   </div>
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label"><i class="fas fa-sticky-note"></i> 그룹 메모</label>
                           <textarea name="memo" class="form-control" rows="2" placeholder="그룹 예약 관련 특이사항"><?php echo htmlspecialchars(isset($group_info['memo']) ? $group_info['memo'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                       </div>
                   </div>
               </div>
           </div>

           <div class="stats-row">
               <div class="row text-center">
                   <div class="col-md-3">
                       <div class="stats-item">
                           <div class="stats-number" id="totalCount"><?php echo count($processed_data); ?></div>
                           <div>총 여행자 수</div>
                       </div>
                   </div>
                   <div class="col-md-3">
                       <div class="stats-item">
                           <div class="stats-number" id="maleCount">0</div>
                           <div>남성</div>
                       </div>
                   </div>
                   <div class="col-md-3">
                       <div class="stats-item">
                           <div class="stats-number" id="femaleCount">0</div>
                           <div>여성</div>
                       </div>
                   </div>
                   <div class="col-md-3">
                       <div class="stats-item">
                           <div class="stats-number" id="passportCount">0</div>
                           <div>여권 정보</div>
                       </div>
                   </div>
               </div>
           </div>

           <div class="travelers-card">
               <div class="toolbar">
                   <div>
                       <h4><i class="fas fa-users"></i> 여행자 목록 <span class="badge bg-primary" id="travelersBadge"><?php echo count($processed_data); ?>명</span></h4>
                   </div>
                   <div>
                       <!-- 일괄작업 버튼들 -->
                       <div class="btn-group me-2" id="bulkActionButtons" style="display: none;">
                           <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDeleteTravelers()">
                               <i class="fas fa-trash"></i> 선택 삭제 (<span id="selectedCount">0</span>)
                           </button>
                           <button type="button" class="btn btn-outline-info btn-sm" onclick="bulkDuplicateTravelers()">
                               <i class="fas fa-copy"></i> 선택 복제
                           </button>
                           <button type="button" class="btn btn-outline-success btn-sm" onclick="saveSelectedAsNewGroup()">
                               <i class="fas fa-plus-circle"></i> 선택항목으로 새 그룹 저장
                           </button>
                           <button type="button" class="btn btn-outline-warning btn-sm" onclick="bulkEditGender()">
                               <i class="fas fa-venus-mars"></i> 성별 일괄수정
                           </button>
                           <button type="button" class="btn btn-outline-secondary btn-sm" onclick="bulkEditRoomType()">
                               <i class="fas fa-bed"></i> 객실 일괄수정
                           </button>
                       </div>
                       
                       <button type="button" id="addTravelerBtn" class="btn-add">
                           <i class="fas fa-plus"></i> 여행자 추가
                       </button>
                       <input type="text" id="searchTravelers" class="search-input" placeholder="이름, 여권번호로 검색...">
                   </div>
               </div>
               
               <div class="table-responsive">
                   <table class="table table-hover table-sm" id="travelersTable">
                       <thead class="table-light">
                           <tr>
                               <th width="3%">
                                   <input type="checkbox" id="selectAllTravelers" class="form-check-input" title="전체 선택/해제">
                               </th>
                               <th width="3%">#</th>
                               <th width="11%">한글성명</th>
                               <th width="11%">영문성명</th>
                               <th width="11%">여권번호</th>
                               <th width="9%">생년월일</th>
                               <th width="7%">성별</th>
                               <th width="11%">연락처</th>
                               <th width="7%">객실타입</th>
                               <th width="7%">객실번호</th>
                               <th width="9%">비고</th>
                               <th width="6%" class="text-center">작업</th>
                           </tr>
                       </thead>
                       <tbody id="travelersTableBody">
                           <?php foreach ($processed_data as $index => $traveler): ?>
                           <tr data-index="<?php echo $index; ?>">
                               <td>
                                   <input type="checkbox" class="form-check-input traveler-checkbox" 
                                          value="<?php echo $index; ?>" title="선택">
                               </td>
                               <td><?php echo $index + 1; ?></td>
                               <td>
                                   <input type="text" class="form-control form-control-sm traveler-field" 
                                          name="travelers[<?php echo $index; ?>][korean_name]" data-field="korean_name"
                                          value="<?php echo htmlspecialchars(isset($traveler['korean_name']) ? $traveler['korean_name'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </td>
                               <td>
                                   <input type="text" class="form-control form-control-sm traveler-field" 
                                          name="travelers[<?php echo $index; ?>][english_name]" data-field="english_name" style="text-transform: uppercase;"
                                          value="<?php echo htmlspecialchars(isset($traveler['english_name']) ? $traveler['english_name'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </td>
                               <td>
                                   <input type="text" class="form-control form-control-sm traveler-field" 
                                          name="travelers[<?php echo $index; ?>][passport_number]" data-field="passport_number" style="text-transform: uppercase;"
                                          value="<?php echo htmlspecialchars(isset($traveler['passport_number']) ? $traveler['passport_number'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </td>
                               <td>
                                   <input type="date" class="form-control form-control-sm traveler-field" 
                                          name="travelers[<?php echo $index; ?>][birth_date]" data-field="birth_date"
                                          value="<?php echo htmlspecialchars(isset($traveler['birth_date']) ? $traveler['birth_date'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </td>
                               <td>
                                   <select class="form-select form-select-sm traveler-field" 
                                           name="travelers[<?php echo $index; ?>][gender]" data-field="gender">
                                       <option value="">선택</option>
                                       <option value="male" <?php echo strtolower(isset($traveler['gender']) ? $traveler['gender'] : '') === 'male' ? 'selected' : ''; ?>>남성</option>
                                       <option value="female" <?php echo strtolower(isset($traveler['gender']) ? $traveler['gender'] : '') === 'female' ? 'selected' : ''; ?>>여성</option>
                                   </select>
                               </td>
                               <td>
                                   <input type="tel" class="form-control form-control-sm traveler-field" 
                                          name="travelers[<?php echo $index;?>][phone]" data-field="phone"
                                          value="<?php echo htmlspecialchars(isset($traveler['phone']) ? $traveler['phone'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                                   <input type="hidden" class="form-control form-control-sm traveler-field"
                                          name="travelers[<?php echo $index; ?>][email]" data-field="email"
                                          value="<?php echo htmlspecialchars(isset($traveler['email']) ? $traveler['email'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </td>
                               <td>
                                   <select class="form-select form-select-sm traveler-field" 
                                           name="travelers[<?php echo $index; ?>][room_type]" data-field="room_type">
                                       <option value="1r1p" <?php echo (isset($traveler['room_type']) ? $traveler['room_type'] : '') === '1r1p' ? 'selected' : ''; ?>>1인실</option>
                                       <option value="2r1p" <?php echo (isset($traveler['room_type']) ? $traveler['room_type'] : '2r1p') === '2r1p' ? 'selected' : ''; ?>>2인실</option>
                                       <option value="3r1p" <?php echo (isset($traveler['room_type']) ? $traveler['room_type'] : '') === '3r1p' ? 'selected' : ''; ?>>3인실</option>
                                       <option value="4r1p" <?php echo (isset($traveler['room_type']) ? $traveler['room_type'] : '') === '4r1p' ? 'selected' : ''; ?>>4인실</option>
                                   </select>
                               </td>
                               <td>
                                   <input type="text" class="form-control form-control-sm traveler-field" 
                                          name="travelers[<?php echo $index; ?>][room_number]" data-field="room_number"
                                          value="<?php echo htmlspecialchars(isset($traveler['room_number']) ? $traveler['room_number'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </td>
                               <td>
                                   <input type="text" class="form-control form-control-sm traveler-field" 
                                          name="travelers[<?php echo $index; ?>][memo]" data-field="memo"
                                          value="<?php echo htmlspecialchars(isset($traveler['memo']) ? $traveler['memo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                               </td>
                               <td class="text-center">
                                   <div class="table-actions">
                                       <button type="button" class="btn-action btn-edit" onclick="editTraveler(<?php echo $index; ?>)" title="수정">
                                           <i class="fas fa-edit"></i>
                                       </button>
                                       <button type="button" class="btn-action btn-duplicate" onclick="duplicateTraveler(<?php echo $index; ?>)" title="복제">
                                           <i class="fas fa-copy"></i>
                                       </button>
                                       <button type="button" class="btn-action btn-delete" onclick="deleteTraveler(<?php echo $index; ?>)" title="삭제">
                                           <i class="fas fa-trash"></i>
                                       </button>
                                   </div>
                               </td>
                           </tr>
                           <?php endforeach; ?>
                       </tbody>
                   </table>
               </div>
           </div>

           <div class="text-center mt-4">
               <button type="submit" class="btn btn-save btn-lg" id="saveGroupBtn">
                   <i class="fas fa-save"></i> 그룹 예약 저장
               </button>
               <div class="mt-3">
                   <small class="text-muted">
                       <i class="fas fa-info-circle"></i> 
                       저장 시 하나의 그룹 예약으로 등록되며, 
                       <span id="finalTravelerCount"><?php echo count($processed_data); ?></span>명의 여행자가 포함됩니다.
                       <?php if ($phpexcel_loaded): ?>
                       <br><span class="text-success"><i class="fas fa-check"></i> PHPExcel로 정밀 처리된 데이터</span>
                       <?php endif; ?>
                   </small>
               </div>
           </div>
       </form>
       <?php endif; ?>
   </div>

   <!-- 모달 및 JavaScript는 기존과 동일 -->
   <div class="modal-overlay" id="travelerModal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title" id="travelerModalTitle">새 여행자 추가</h2>
               <button type="button" class="modal-close" id="travelerModalCloseBtn">
                   <i class="fas fa-times"></i>
               </button>
           </div>
           
           <form id="travelerForm">
               <input type="hidden" id="editTravelerIndex" value="-1">
               
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">한글성명 *</label>
                           <input type="text" id="modalKoreanName" class="form-control">
                       </div>
                   </div>
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">영문성명</label>
                           <input type="text" id="modalEnglishName" class="form-control" style="text-transform: uppercase;">
                       </div>
                   </div>
               </div>
               
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">여권번호</label>
                           <input type="text" id="modalPassportNumber" class="form-control" style="text-transform: uppercase;">
                       </div>
                   </div>
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">생년월일</label>
                           <input type="date" id="modalBirthDate" class="form-control">
                       </div>
                   </div>
               </div>
               
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">성별</label>
                           <select id="modalGender" class="form-select">
                               <option value="">선택하세요</option>
                               <option value="male">남성</option>
                               <option value="female">여성</option>
                           </select>
                       </div>
                   </div>
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">연락처</label>
                           <input type="tel" id="modalPhone" class="form-control">
                       </div>
                   </div>
               </div>
               
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">이메일</label>
                           <input type="email" id="modalEmail" class="form-control">
                       </div>
                   </div>
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">객실타입</label>
                           <select id="modalRoomType" class="form-select">
                               <option value="1r1p">1인실</option>
                               <option value="2r1p" selected>2인실</option>
                               <option value="3r1p">3인실</option>
                               <option value="4r1p">4인실</option>
                           </select>
                       </div>
                   </div>
               </div>
               
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">객실번호</label>
                           <input type="text" id="modalRoomNumber" class="form-control">
                       </div>
                   </div>
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">비고</label>
                           <input type="text" id="modalMemo" class="form-control">
                       </div>
                   </div>
               </div>
           </form>
           
           <div class="modal-footer mt-3">
               <button type="button" class="btn btn-secondary" id="travelerModalCancelBtn">취소</button>
               <button type="button" class="btn btn-primary" id="travelerModalSaveBtn">저장</button>
           </div>
       </div>
   </div>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
   <script>
       // 전역 변수
       var travelerData = <?php echo !empty($processed_data) ? json_encode($processed_data) : '[]'; ?>;
       var groupInfoData = <?php echo !empty($group_info) ? json_encode($group_info) : '{}'; ?>;
       var phpExcelEnabled = <?php echo $phpexcel_loaded ? 'true' : 'false'; ?>;

       $(document).ready(function() {
           initializeSystem();
           showPHPExcelStatus();
       });

       function showPHPExcelStatus() {
           if (phpExcelEnabled) {
               console.log('✅ PHPExcel/PhpSpreadsheet 라이브러리 활성화됨');
               console.log('🔬 병합된 셀, Excel 날짜, 수식 등을 완벽하게 처리할 수 있습니다');
           } else {
               console.log('⚠️ PHPExcel 라이브러리가 없습니다');
               console.log('📝 기본 처리 모드로 동작합니다');
           }
       }

       function initializeSystem() {
           console.log('시스템 초기화 시작 - PHPExcel 강화 버전');
           setupFileUpload();
           setupTravelerManagement();
           setupBulkActions();
           setupSearch();
           setupDateValidation();
           updateAllStats();
           autoFormatInputs();
           console.log('시스템 초기화 완료');
       }

       function setupFileUpload() {
           var fileInput = document.getElementById('fileInput');
           var uploadForm = document.getElementById('uploadForm');
           var processBtn = document.getElementById('processBtn');
           var fileListContainer = document.getElementById('fileListContainer');

           fileInput.addEventListener('change', handleFileSelection);
           uploadForm.addEventListener('submit', handleFormSubmit);
           setupDragDrop();

           function handleFileSelection(event) {
               var files = event.target.files;
               console.log('파일 선택됨:', files.length, '개');
               
               if (files.length > 0) {
                   displaySelectedFiles(files);
                   processBtn.style.display = 'inline-block';
                   
                   // PHPExcel 상태에 따른 버튼 텍스트 업데이트
                   var btnText = phpExcelEnabled ? 
                       '<i class="fas fa-cogs"></i> PHPExcel로 정밀 분석 시작' : 
                       '<i class="fas fa-cogs"></i> 기본 분석 시작';
                   processBtn.innerHTML = btnText;
               } else {
                   fileListContainer.innerHTML = '';
                   processBtn.style.display = 'none';
               }
           }

           function displaySelectedFiles(files) {
               var html = '<div class="row align-items-center mb-3">';
               html += '<div class="col-md-8"><h6><i class="fas fa-file-alt"></i> 선택된 파일 목록:</h6></div>';
               html += '<div class="col-md-4 text-end">';
               if (phpExcelEnabled) {
                   html += '<span class="badge bg-success"><i class="fas fa-check"></i> PHPExcel 처리</span>';
               } else {
                   html += '<span class="badge bg-warning"><i class="fas fa-info-circle"></i> 기본 처리</span>';
               }
               html += '</div></div>';
               
               html += '<ul class="list-group">';
               for (var i = 0; i < files.length; i++) {
                   var file = files['i'];
                   var fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                   var fileExtension = file.name.split('.').pop().toLowerCase();
                   var iconClass = getFileIcon(fileExtension);
                   
                   html += '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                               '<span><i class="' + iconClass + ' me-2"></i>' + escapeHtml(file.name) + '</span>' +
                               '<div>' +
                                   '<span class="badge bg-secondary rounded-pill me-2">' + fileSizeMB + ' MB</span>';
                   
                   if (phpExcelEnabled && (fileExtension === 'xlsx' || fileExtension === 'xls')) {
                       html += '<span class="badge bg-success rounded-pill"><i class="fas fa-magic"></i> 고급처리</span>';
                   }
                   
                   html += '</div></li>';
               }
               html += '</ul>';
               
               if (phpExcelEnabled) {
                   html += '<div class="alert alert-info mt-3 mb-0">';
                   html += '<small><i class="fas fa-info-circle"></i> PHPExcel이 활성화되어 병합된 셀, Excel 날짜 형식, 수식 결과 등을 정확하게 처리합니다.</small>';
                   html += '</div>';
               }
               
               fileListContainer.innerHTML = html;
           }

           function getFileIcon(extension) {
               switch(extension) {
                   case 'xlsx':
                   case 'xls':
                       return 'fas fa-file-excel text-success';
                   case 'csv':
                       return 'fas fa-file-csv text-info';
                   default:
                       return 'fas fa-file';
               }
           }

           function handleFormSubmit(event) {
               console.log('폼 제출 시작 - PHPExcel 모드:', phpExcelEnabled);
               if (!fileInput.files || fileInput.files.length === 0) {
                   event.preventDefault();
                   showNotification('파일을 선택해주세요.', 'warning');
                   return false;
               }
               
               document.getElementById('loading').style.display = 'block';
               document.getElementById('loading').scrollIntoView({ behavior: 'smooth', block: 'center' });
               
               // 처리 모드에 따른 로딩 메시지 업데이트
               var loadingText = phpExcelEnabled ? 
                   'PHPExcel로 파일을 정밀 분석하여 그룹 정보와 여행자 목록을 추출하고 있습니다. 잠시만 기다려주세요...' :
                   'AI가 파일을 분석하여 그룹 정보와 여행자 목록을 추출하고 있습니다. 잠시만 기다려주세요...';
               document.querySelector('#loading p').textContent = loadingText;
           }
       }
       
       function setupDragDrop() {
           var uploadCard = document.getElementById('uploadCard');
           
           ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
               uploadCard.addEventListener(eventName, preventDefaults, false);
               document.body.addEventListener(eventName, preventDefaults, false);
           });

           ['dragenter', 'dragover'].forEach(function(eventName) {
               uploadCard.addEventListener(eventName, function() { highlight(uploadCard); }, false);
           });

           ['dragleave', 'drop'].forEach(function(eventName) {
               uploadCard.addEventListener(eventName, function() { unhighlight(uploadCard); }, false);
           });

           uploadCard.addEventListener('drop', handleDrop, false);
       }

       function preventDefaults(e) {
           e.preventDefault();
           e.stopPropagation();
       }

       function highlight(element) {
           element.classList.add('dragover');
       }

       function unhighlight(element) {
           element.classList.remove('dragover');
       }

       function handleDrop(e) {
           var dt = e.dataTransfer;
           var files = dt.files;
           var fileInput = document.getElementById('fileInput');
           fileInput.files = files; 
           
           var event = new Event('change', { bubbles: true });
           fileInput.dispatchEvent(event);
       }

       function setupTravelerManagement() {
           $('#addTravelerBtn').on('click', function() { openTravelerModal(); });
           $('#travelerModalCloseBtn, #travelerModalCancelBtn').on('click', closeTravelerModal);
           $('#travelerModalSaveBtn').on('click', saveTravelerFromModal);
           
           $('#travelerModal').on('click', function(e) {
               if (e.target === this) closeTravelerModal();
           });
           
           $('#travelersTableBody').on('input change', '.traveler-field', function() {
               var rowIndex = $(this).closest('tr').data('index');
               var field = $(this).data('field');
               var value = $(this).val();

               if (field === 'english_name' || field === 'passport_number') {
                   value = value.toUpperCase();
                   $(this).val(value);
               }

               if (travelerData['rowIndex']) {
                   travelerData['rowIndex']['field'] = value;
                   updateAllStats();
                   markUnsavedChanges(true);
               }
           });
           
           $('#groupReservationForm').on('submit', handleGroupSave);
       }

       function setupBulkActions() {
           $('#selectAllTravelers').on('change', function() {
               var isChecked = $(this).prop('checked');
               $('.traveler-checkbox').prop('checked', isChecked);
               updateBulkActionButtonsVisibility();
           });

           $(document).on('change', '.traveler-checkbox', function() {
               updateSelectAllState();
               updateBulkActionButtonsVisibility();
           });
       }

       function updateSelectAllState() {
           var totalCheckboxes = $('.traveler-checkbox').length;
           var checkedCheckboxes = $('.traveler-checkbox:checked').length;
           
           $('#selectAllTravelers').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
           $('#selectAllTravelers').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
       }

       function updateBulkActionButtonsVisibility() {
           var checkedCount = $('.traveler-checkbox:checked').length;
           $('#selectedCount').text(checkedCount);
           
           if (checkedCount > 0) {
               $('#bulkActionButtons').show();
           } else {
               $('#bulkActionButtons').hide();
           }
       }

       function getSelectedTravelerIndexes() {
           var checkedIndexes = [];
           $('.traveler-checkbox:checked').each(function() {
               var index = parseInt($(this).val(), 10);
               checkedIndexes.push(index);
           });
           return checkedIndexes;
       }

       function bulkDeleteTravelers() {
           var checkedIndexes = getSelectedTravelerIndexes();

           if (checkedIndexes.length === 0) {
               showNotification('삭제할 여행자를 선택해주세요.', 'warning');
               return;
           }

           if (!confirm('선택된 ' + checkedIndexes.length + '명의 여행자를 삭제하시겠습니까?')) {
               return;
           }

           checkedIndexes.sort(function(a, b) { return b - a; });
           
           checkedIndexes.forEach(function(index) {
               if (travelerData['index'] !== undefined) {
                   travelerData.splice(index, 1);
               }
           });

           renderAllTravelers();
           updateAllStats();
           showNotification(checkedIndexes.length + '명의 여행자가 삭제되었습니다.', 'success');
           markUnsavedChanges(true);
       }

       function bulkDuplicateTravelers() {
           var checkedIndexes = getSelectedTravelerIndexes();

           if (checkedIndexes.length === 0) {
               showNotification('복제할 여행자를 선택해주세요.', 'warning');
               return;
           }

           if (!confirm('선택된 ' + checkedIndexes.length + '명의 여행자를 복제하시겠습니까?')) {
               return;
           }

           var duplicateData = [];
           checkedIndexes.forEach(function(index) {
               if (travelerData['index'] !== undefined) {
                   var original = travelerData['index'];
                   var duplicate = JSON.parse(JSON.stringify(original));
                   
                   if (duplicate.korean_name) duplicate.korean_name += ' (복사본)';
                   else if (duplicate.english_name) duplicate.english_name += ' COPY';
                   else duplicate.korean_name = '(복사본)';
                   
                   duplicateData.push(duplicate);
               }
           });

           travelerData = travelerData.concat(duplicateData);

           renderAllTravelers();
           updateAllStats();
           showNotification(duplicateData.length + '명의 여행자가 복제되었습니다.', 'success');
           markUnsavedChanges(true);
       }

       function bulkEditGender() {
           var checkedIndexes = getSelectedTravelerIndexes();

           if (checkedIndexes.length === 0) {
               showNotification('성별을 수정할 여행자를 선택해주세요.', 'warning');
               return;
           }

           var genderOptions = [
               { value: 'male', label: '남성' },
               { value: 'female', label: '여성' },
               { value: '', label: '선택안함' }
           ];

           var optionsText = genderOptions.map(function(option, index) { 
               return (index + 1) + '. ' + option.label; 
           }).join('\n');
           var choice = prompt('선택된 ' + checkedIndexes.length + '명의 성별을 일괄 수정합니다.\n\n' + optionsText + '\n\n번호를 입력하세요:');
           
           if (choice === null) return;

           var choiceIndex = parseInt(choice, 10) - 1;
           if (choiceIndex < 0 || choiceIndex >= genderOptions.length) {
               showNotification('올바른 번호를 입력해주세요.', 'warning');
               return;
           }

           var selectedGender = genderOptions['choiceIndex'].value;

           checkedIndexes.forEach(function(index) {
               if (travelerData['index'] !== undefined) {
                   travelerData['index'].gender = selectedGender;
               }
           });

           renderAllTravelers();
           updateAllStats();
           showNotification(checkedIndexes.length + '명의 성별이 "' + genderOptions['choiceIndex'].label + '"로 수정되었습니다.', 'success');
           markUnsavedChanges(true);
       }

       function bulkEditRoomType() {
           var checkedIndexes = getSelectedTravelerIndexes();

           if (checkedIndexes.length === 0) {
               showNotification('객실타입을 수정할 여행자를 선택해주세요.', 'warning');
               return;
           }

           var roomTypeOptions = [
               { value: '1r1p', label: '1인실' },
               { value: '2r1p', label: '2인실' },
               { value: '3r1p', label: '3인실' },
               { value: '4r1p', label: '4인실' }
           ];

           var optionsText = roomTypeOptions.map(function(option, index) { 
               return (index + 1) + '. ' + option.label; 
           }).join('\n');
           var choice = prompt('선택된 ' + checkedIndexes.length + '명의 객실타입을 일괄 수정합니다.\n\n' + optionsText + '\n\n번호를 입력하세요:');
           
           if (choice === null) return;

           var choiceIndex = parseInt(choice, 10) - 1;
           if (choiceIndex < 0 || choiceIndex >= roomTypeOptions.length) {
               showNotification('올바른 번호를 입력해주세요.', 'warning');
               return;
           }

           var selectedRoomType = roomTypeOptions['choiceIndex'].value;

           checkedIndexes.forEach(function(index) {
               if (travelerData['index'] !== undefined) {
                   travelerData['index'].room_type = selectedRoomType;
               }
           });

           renderAllTravelers();
           updateAllStats();
           showNotification(checkedIndexes.length + '명의 객실타입이 "' + roomTypeOptions['choiceIndex'].label + '"로 수정되었습니다.', 'success');
           markUnsavedChanges(true);
       }

       function openTravelerModal(editIndex) {
           editIndex = editIndex || -1;
           var isEdit = editIndex >= 0 && travelerData['editIndex'] !== undefined;
           $('#travelerModalTitle').text(isEdit ? '여행자 정보 수정' : '새 여행자 추가');
           $('#editTravelerIndex').val(editIndex);
           
           if (isEdit) {
               fillTravelerForm(travelerData['editIndex']);
           } else {
               clearTravelerForm();
           }
           $('#travelerModal').show();
           $('#modalKoreanName').focus();
       }

       function closeTravelerModal() {
           $('#travelerModal').hide();
           clearTravelerForm();
       }
       
       function fillTravelerForm(data) {
           $('#modalKoreanName').val(data.korean_name || '');
           $('#modalEnglishName').val(data.english_name || '');
           $('#modalPassportNumber').val(data.passport_number || '');
           $('#modalBirthDate').val(data.birth_date || '');
           $('#modalGender').val(data.gender || '');
           $('#modalPhone').val(data.phone || '');
           $('#modalEmail').val(data.email || '');
           $('#modalRoomType').val(data.room_type || '2r1p');
           $('#modalRoomNumber').val(data.room_number || '');
           $('#modalMemo').val(data.memo || '');
       }

       function clearTravelerForm() {
           $('#travelerForm')[0].reset();
           $('#editTravelerIndex').val(-1);
           $('#modalRoomType').val('2r1p');
       }

       function saveTravelerFromModal() {
           var editIndex = parseInt($('#editTravelerIndex').val(), 10);
           var isEdit = editIndex >= 0;
           
           var newTravelerData = {
               korean_name: $('#modalKoreanName').val().trim(),
               english_name: $('#modalEnglishName').val().trim().toUpperCase(),
               passport_number: $('#modalPassportNumber').val().trim().toUpperCase(),
               birth_date: $('#modalBirthDate').val(),
               gender: $('#modalGender').val(),
               phone: $('#modalPhone').val().trim(),
               email: $('#modalEmail').val().trim(),
               room_type: $('#modalRoomType').val(),
               room_number: $('#modalRoomNumber').val().trim(),
               memo: $('#modalMemo').val().trim()
           };

           if (!newTravelerData.korean_name && !newTravelerData.english_name) {
               showNotification('한글성명 또는 영문성명 중 하나는 필수입니다.', 'warning');
               $('#modalKoreanName').focus();
               return;
           }
           if (newTravelerData.email && !validateEmail(newTravelerData.email)) {
               showNotification('올바른 이메일 형식이 아닙니다.', 'warning');
               $('#modalEmail').focus();
               return;
           }

           if (isEdit) {
               if(travelerData['editIndex']) {
                   travelerData['editIndex'] = newTravelerData;
                   showNotification('여행자 정보가 수정되었습니다.', 'success');
               } else {
                   travelerData.push(newTravelerData);
                   showNotification('수정 대상 정보를 찾을 수 없어 새 여행자로 추가했습니다.', 'info');
               }
           } else {
               travelerData.push(newTravelerData);
               showNotification('새 여행자가 추가되었습니다.', 'success');
           }
           
           renderAllTravelers();
           updateAllStats();
           closeTravelerModal();
           markUnsavedChanges(true);
       }
       
       function renderAllTravelers() {
           var tbody = $('#travelersTableBody');
           tbody.empty();
           travelerData.forEach(function(traveler, index) {
               tbody.append(createTravelerRowHtml(traveler, index));
           });
           updateRowNumbersAndActions();
       }

       function createTravelerRowHtml(data, index) {
           var trData = data || {};
           return '<tr data-index="' + index + '">' +
               '<td>' +
                   '<input type="checkbox" class="form-check-input traveler-checkbox" value="' + index + '" title="선택">' +
               '</td>' +
               '<td>' + (index + 1) + '</td>' +
               '<td><input type="text" class="form-control form-control-sm traveler-field" data-field="korean_name" value="' + escapeHtml(trData.korean_name || '') + '"></td>' +
               '<td><input type="text" class="form-control form-control-sm traveler-field" data-field="english_name" style="text-transform: uppercase;" value="' + escapeHtml(trData.english_name || '') + '"></td>' +
               '<td><input type="text" class="form-control form-control-sm traveler-field" data-field="passport_number" style="text-transform: uppercase;" value="' + escapeHtml(trData.passport_number || '') + '"></td>' +
               '<td><input type="date" class="form-control form-control-sm traveler-field" data-field="birth_date" value="' + escapeHtml(trData.birth_date || '') + '"></td>' +
               '<td>' +
                   '<select class="form-select form-select-sm traveler-field" data-field="gender">' +
                       '<option value="">선택</option>' +
                       '<option value="male" ' + (strtolower(trData.gender || '') === 'male' ? 'selected' : '') + '>남성</option>' +
                       '<option value="female" ' + (strtolower(trData.gender || '') === 'female' ? 'selected' : '') + '>여성</option>' +
                   '</select>' +
               '</td>' +
               '<td>' +
                   '<input type="tel" class="form-control form-control-sm traveler-field" data-field="phone" value="' + escapeHtml(trData.phone || '') + '">' +
                   '<input type="hidden" class="form-control form-control-sm traveler-field" data-field="email" value="' + escapeHtml(trData.email || '') + '">' +
               '</td>' +
               '<td>' +
                   '<select class="form-select form-select-sm traveler-field" data-field="room_type">' +
                       '<option value="1r1p" ' + ((trData.room_type || '2r1p') === '1r1p' ? 'selected' : '') + '>1인실</option>' +
                       '<option value="2r1p" ' + ((trData.room_type || '2r1p') === '2r1p' ? 'selected' : '') + '>2인실</option>' +
                       '<option value="3r1p" ' + ((trData.room_type || '2r1p') === '3r1p' ? 'selected' : '') + '>3인실</option>' +
                       '<option value="4r1p" ' + ((trData.room_type || '2r1p') === '4r1p' ? 'selected' : '') + '>4인실</option>' +
                   '</select>' +
               '</td>' +
               '<td><input type="text" class="form-control form-control-sm traveler-field" data-field="room_number" value="' + escapeHtml(trData.room_number || '') + '"></td>' +
               '<td><input type="text" class="form-control form-control-sm traveler-field" data-field="memo" value="' + escapeHtml(trData.memo || '') + '"></td>' +
               '<td class="text-center">' +
                   '<div class="table-actions">' +
                       '<button type="button" class="btn-action btn-edit" title="수정"><i class="fas fa-edit"></i></button>' +
                       '<button type="button" class="btn-action btn-duplicate" title="복제"><i class="fas fa-copy"></i></button>' +
                       '<button type="button" class="btn-action btn-delete" title="삭제"><i class="fas fa-trash"></i></button>' +
                   '</div>' +
               '</td>' +
           '</tr>';
       }
       
       function strtolower(str) {
           return str ? String(str).toLowerCase() : '';
       }

       function updateRowNumbersAndActions() {
           $('#travelersTableBody tr').each(function(newIndex) {
               $(this).attr('data-index', newIndex);
               $(this).find('td:nth-child(2)').text(newIndex + 1);
               
               $(this).find('.traveler-checkbox').val(newIndex);
               
               $(this).find('.traveler-field').each(function() {
                   var fieldName = $(this).data('field');
                   $(this).attr('name', 'travelers[' + newIndex + '][' + fieldName + ']');
               });

               $(this).find('.btn-edit').off('click').on('click', function() { editTraveler(newIndex); });
               $(this).find('.btn-duplicate').off('click').on('click', function() { duplicateTraveler(newIndex); });
               $(this).find('.btn-delete').off('click').on('click', function() { deleteTraveler(newIndex); });
           });
           
           updateSelectAllState();
           updateBulkActionButtonsVisibility();
       }
       
       function editTraveler(index) {
           if(travelerData['index']) {
               openTravelerModal(index);
           } else {
               showNotification('수정할 여행자 정보를 찾을 수 없습니다.', 'error');
           }
       }

       function deleteTraveler(index) {
           if (!travelerData['index']) {
               showNotification('삭제할 여행자 정보를 찾을 수 없습니다.', 'error');
               renderAllTravelers();
               return;
           }
           var traveler = travelerData['index'];
           var displayName = traveler.korean_name || traveler.english_name || ('여행자 #' + (index + 1));
           
           if (!confirm('"' + displayName + '" 여행자를 삭제하시겠습니까?')) return;
           
           travelerData.splice(index, 1);
           renderAllTravelers();
           updateAllStats();
           showNotification('여행자가 삭제되었습니다.', 'success');
           markUnsavedChanges(true);
       }

       function duplicateTraveler(index) {
           if (!travelerData['index']) {
               showNotification('복제할 여행자 정보를 찾을 수 없습니다.', 'error');
               renderAllTravelers();
               return;
           }
           var original = travelerData['index'];
           var duplicate = JSON.parse(JSON.stringify(original));
           
           if (duplicate.korean_name) duplicate.korean_name += ' (복사본)';
           else if (duplicate.english_name) duplicate.english_name += ' COPY';
           else duplicate.korean_name = '(복사본)';
           
           travelerData.splice(index + 1, 0, duplicate);
           renderAllTravelers();
           updateAllStats();
           showNotification('여행자가 복제되었습니다.', 'success');
           markUnsavedChanges(true);
       }

       function saveSelectedAsNewGroup() {
           var checkedIndexes = getSelectedTravelerIndexes();

           if (checkedIndexes.length === 0) {
               showNotification('새 그룹으로 저장할 여행자를 선택해주세요.', 'warning');
               return;
           }

           var selectedTravelers = [];
           checkedIndexes.forEach(function(index) {
               if (travelerData['index'] !== undefined) {
                   selectedTravelers.push(travelerData['index']);
               }
           });

           if (selectedTravelers.length === 0) {
               showNotification('선택된 여행자 데이터를 찾을 수 없습니다.', 'error');
               return;
           }

           var currentGroupData = collectGroupDataFromForm();
           
           var newGroupName = prompt('선택된 ' + selectedTravelers.length + '명으로 새로운 그룹을 만듭니다.\n\n새 그룹명을 입력하세요:', currentGroupData.tour_name + ' (분할그룹)');
           
           if (newGroupName === null) return;
           
           if (newGroupName.trim() === '') {
               showNotification('그룹명을 입력해주세요.', 'warning');
               return;
           }

           var newGroupData = {};
           for (var key in currentGroupData) {
               newGroupData['key'] = currentGroupData['key'];
           }
           
           newGroupData.tour_name = newGroupName.trim();
           newGroupData.product_code = currentGroupData.product_code;
           
           var confirmMsg = '새로운 그룹 예약을 생성하시겠습니까?\n\n그룹명: ' + newGroupData.tour_name + '\n출발일: ' + newGroupData.start_date + '\n대표자: ' + newGroupData.group_leader + '\n여행자 수: ' + selectedTravelers.length + '명';
           
           if (!confirm(confirmMsg)) return;

           $('#loading').show().get(0).scrollIntoView({ behavior: 'smooth' });
           
           $.ajax({
               url: '',
               type: 'POST',
               data: {
                   action: 'save_selected_group_reservation',
                   group_data: JSON.stringify(newGroupData),
                   travelers_data: JSON.stringify(selectedTravelers)
               },
               dataType: 'json',
               success: function(response) {
                   if (response.success) {
                       var successMsg = '새 그룹이 성공적으로 저장되었습니다!\n예약번호: ' + response.grand_revNo;
                       if (phpExcelEnabled) {
                           successMsg += '\n\n✅ PHPExcel로 정밀 처리된 데이터가 저장되었습니다.';
                       }
                       showNotification(successMsg, 'success', 8000);
                       
                       setTimeout(function() {
                           if (confirm('저장된 여행자들을 현재 목록에서 제거하시겠습니까?')) {
                               bulkDeleteTravelers();
                           } else {
                               $('.traveler-checkbox:checked').prop('checked', false);
                               updateBulkActionButtonsVisibility();
                           }
                       }, 2000);
                       
                   } else {
                       showNotification('새 그룹 저장 실패: ' + (response.message || '알 수 없는 오류'), 'error', 10000);
                   }
               },
               error: function(xhr, status, error) {
                   showNotification('서버 통신 오류가 발생했습니다: ' + error, 'error', 10000);
                   console.error("AJAX Error:", xhr.responseText);
               },
               complete: function() {
                   $('#loading').hide();
               }
           });
       }

       function setupSearch() {
           $('#searchTravelers').on('input', function() {
               var searchTerm = $(this).val().toLowerCase();
               $('#travelersTableBody tr').each(function() {
                   var row = $(this);
                   var korean_name = row.find('input[data-field="korean_name"]').val().toLowerCase();
                   var english_name = row.find('input[data-field="english_name"]').val().toLowerCase();
                   var passport = row.find('input[data-field="passport_number"]').val().toLowerCase();
                   var phone = row.find('input[data-field="phone"]').val().toLowerCase();
                   
                   var isMatch = korean_name.indexOf(searchTerm) !== -1 || 
                                   english_name.indexOf(searchTerm) !== -1 || 
                                   passport.indexOf(searchTerm) !== -1 ||
                                   phone.indexOf(searchTerm) !== -1;
                   row.toggle(isMatch);
               });
           });
       }

       function updateAllStats() {
           var maleCount = 0, femaleCount = 0, passportCount = 0;
           var totalCount = travelerData.length;
           
           travelerData.forEach(function(traveler) {
               if (traveler) {
                   if (strtolower(traveler.gender) === 'male') maleCount++;
                   if (strtolower(traveler.gender) === 'female') femaleCount++;
                   if (traveler.passport_number && traveler.passport_number.trim() !== '') passportCount++;
               }
           });
           
           $('#totalCount').text(totalCount);
           $('#maleCount').text(maleCount);
           $('#femaleCount').text(femaleCount);
           $('#passportCount').text(passportCount);
           $('#travelersBadge').text(totalCount + '명');
           $('#finalTravelerCount').text(totalCount);
       }
       
       function collectGroupDataFromForm() {
           return {
               tour_name: $('input[name="tour_name"]').val().trim(),
               product_code: $('input[name="product_code"]').val().trim(),
               start_date: $('input[name="start_date"]').val(),
               end_date: $('input[name="end_date"]').val(),
               group_leader: $('input[name="group_leader"]').val().trim(),
               group_phone: $('input[name="group_phone"]').val().trim(),
               group_email: $('input[name="group_email"]').val().trim(),
               total_amount: parseFloat($('input[name="total_amount"]').val()) || 0,
               memo: $('textarea[name="memo"]').val().trim()
           };
       }

       function handleGroupSave(e) {
           e.preventDefault();
           
           $('#travelersTableBody tr').each(function() {
               var rowIndex = $(this).data('index');
               if (travelerData['rowIndex']) {
                   $(this).find('.traveler-field').each(function() {
                       var field = $(this).data('field');
                       var value = $(this).val();
                       if (field === 'english_name' || field === 'passport_number') {
                           value = value.toUpperCase();
                       }
                       travelerData['rowIndex']['field'] = value;
                   });
               }
           });

           if (travelerData.length === 0) {
               showNotification('저장할 여행자가 없습니다.', 'warning');
               return;
           }
           
           var currentGroupData = collectGroupDataFromForm();

           if (!currentGroupData.tour_name) {
               showNotification('상품명/투어명을 입력해주세요.', 'warning');
               $('input[name="tour_name"]').focus();
               return;
           }
           if (!currentGroupData.start_date) {
               showNotification('출발일을 선택해주세요.', 'warning');
               $('input[name="start_date"]').focus();
               return;
           }
           if (!currentGroupData.group_leader) {
               showNotification('그룹 대표자를 입력해주세요.', 'warning');
               $('input[name="group_leader"]').focus();
               return;
           }

           var invalidTravelersMessages = [];
           travelerData.forEach(function(traveler, index) {
               if (!traveler.korean_name && !traveler.english_name) {
                   invalidTravelersMessages.push((index + 1) + '번 여행자: 이름 누락');
               }
               if (traveler.email && !validateEmail(traveler.email)) {
                   invalidTravelersMessages.push((index + 1) + '번 여행자: (' + (traveler.korean_name || traveler.english_name) + ') 이메일 형식 오류');
               }
           });

           if (invalidTravelersMessages.length > 0) {
               showNotification('여행자 정보 오류:<br>' + invalidTravelersMessages.join('<br>'), 'error', 10000);
               return;
           }
           
           var confirmMsg = '그룹 예약을 저장하시겠습니까?\n\n상품명: ' + currentGroupData.tour_name + '\n출발일: ' + currentGroupData.start_date + '\n대표자: ' + currentGroupData.group_leader + '\n여행자 수: ' + travelerData.length + '명';
           
           if (phpExcelEnabled) {
               confirmMsg += '\n\n✅ PHPExcel로 정밀 처리된 데이터를 저장합니다.';
           }
           
           confirmMsg += '\n\n저장 후에는 이 화면에서의 수정이 반영되지 않습니다.';
           
           if (!confirm(confirmMsg)) return;
           
           $('#loading').show().get(0).scrollIntoView({ behavior: 'smooth' });
           $('#saveGroupBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 저장 중...');

           $.ajax({
               url: '',
               type: 'POST',
               data: {
                   action: 'save_group_reservation',
                   group_data: JSON.stringify(currentGroupData),
                   travelers_data: JSON.stringify(travelerData)
               },
               dataType: 'json',
               success: function(response) {
                   if (response.success) {
                       var successMsg = response.message;
                       if (phpExcelEnabled) {
                           successMsg += '\n\n✅ PHPExcel로 정밀 처리된 ' + response.traveler_count + '명의 데이터가 저장되었습니다.';
                       }
                       showNotification(successMsg, 'success', 5000);
                       markUnsavedChanges(false);
                       setTimeout(function() {
                           window.location.href = 'input_batch.php';
                       }, 3000);
                   } else {
                       showNotification('저장 실패: ' + (response.message || '알 수 없는 오류'), 'error', 10000);
                   }
               },
               error: function(xhr, status, error) {
                   showNotification('서버 통신 오류가 발생했습니다: ' + error, 'error', 10000);
                   console.error("AJAX Error:", xhr.responseText);
               },
               complete: function() {
                   $('#loading').hide();
                   $('#saveGroupBtn').prop('disabled', false).html('<i class="fas fa-save"></i> 그룹 예약 저장');
               }
           });
       }

       function escapeHtml(text) {
           if (typeof text !== 'string') {
               if (text === null || typeof text === 'undefined') return '';
               text = String(text);
           }
           var map = {'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'};
           return text.replace(/[&<>"']/g, function(match) {
               return map[match];
           });
       }

       function showNotification(message, type, duration) {
           type = type || 'info';
           duration = duration || 5000;
           var alertId = 'liveAlert-' + Date.now();
           
           // PHPExcel 상태 아이콘 추가
           var statusIcon = '';
           if (phpExcelEnabled && (type === 'success' || type === 'info')) {
               statusIcon = '<i class="fas fa-magic text-success me-1" title="PHPExcel 처리"></i>';
           }
           
           var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show position-fixed" role="alert" id="' + alertId + '"' +
                   ' style="top: 20px; right: 20px; z-index: 10050; min-width: 300px; max-width: 90%;">' +
                   '<div>' + statusIcon + message + '</div>' +
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
               '</div>';
           $('body').append(alertHtml);
           
           if (duration > 0) {
               setTimeout(function() {
                   $('#' + alertId).fadeOut(500, function() { $(this).remove(); });
               }, duration);
           }
       }
       
       var unsavedChanges = false;
       function markUnsavedChanges(status) {
           unsavedChanges = status;
       }

       $(document).on('input change', '#groupReservationForm input, #groupReservationForm select, #groupReservationForm textarea', function() {
           markUnsavedChanges(true);
       });

       window.addEventListener('beforeunload', function(e) {
           if (unsavedChanges && travelerData.length > 0) {
               var confirmationMessage = '저장하지 않은 변경사항이 있습니다. 정말 페이지를 나가시겠습니까?';
               e.returnValue = confirmationMessage;
               return confirmationMessage;
           }
       });

       function validateEmail(email) {
           var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
           return emailRegex.test(String(email).toLowerCase());
       }
       
       function autoFormatInputs() {
           $('#travelersTableBody').on('input', 'input[data-field="english_name"], input[data-field="passport_number"]', function() {
               var upperVal = $(this).val().toUpperCase();
               $(this).val(upperVal);
           });
           $('#modalEnglishName, #modalPassportNumber').on('input', function() {
               var upperVal = $(this).val().toUpperCase();
               $(this).val(upperVal);
           });
       }
       
       function setupDateValidation() {
           // PHP 5.5 호환을 위한 빈 함수
       }
       
       // 페이지 로드 시 초기 렌더링
       if (travelerData && travelerData.length > 0) {
           renderAllTravelers();
           console.log('✅ 여행자 데이터 렌더링 완료:', travelerData.length + '명');
           if (phpExcelEnabled) {
               console.log('🔬 PHPExcel로 처리된 데이터입니다');
           }
       }

   </script>
</body>
</html>