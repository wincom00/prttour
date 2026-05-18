<?php
// -----------------------------------------------------------------------------
// 협력사 전용 그룹 예약 일괄 등록 시스템 - 푸른투어 Partner Portal
// 비정형 데이터 처리 + 실시간 CRUD 기능 (다중 시트 처리 개선)
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

// SimpleXLSX 라이브러리 체크
$library_paths = array('SimpleXLSX.php', 'lib/SimpleXLSX.php', '../lib/SimpleXLSX.php');
$library_exists = false;

foreach ($library_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $library_exists = true;
        break;
    }  
}
// Shuchkin\SimpleXLSX → 전역 SimpleXLSX 별칭 (네임스페이스 없는 코드에서 SimpleXLSX::parse() 직접 호출 가능하도록)
if ($library_exists && class_exists('Shuchkin\\SimpleXLSX') && !class_exists('SimpleXLSX')) {
    class_alias('Shuchkin\\SimpleXLSX', 'SimpleXLSX');
}

// 전역 변수 초기화
$error_messages = array();
$success_messages = array();
$processed_data = array(); // 여러 파일에서 집계된 전체 여행자 목록
$group_info = array(); // 여러 파일 및 시트에서 집계/결정된 최종 그룹 정보
$sheets_data = array(); // 시트별 개별 데이터 [ ['sheet_name'=>..,'group_info'=>..,'travelers'=>..], ... ]

// -----------------------------------------------------------------------------
// AJAX 요청 처리 (CRUD 기능)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $response = array('success' => false, 'message' => '', 'data' => null);
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_traveler':
                $new_traveler = array(
                    'korean_name' => $_POST['korean_name'] ?? '',
                    'english_name' => $_POST['english_name'] ?? '',
                    'passport_number' => $_POST['passport_number'] ?? '',
                    'birth_date' => $_POST['birth_date'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'gender' => $_POST['gender'] ?? '',
                    'room_type' => $_POST['room_type'] ?? '2r1p',
                    'room_number' => $_POST['room_number'] ?? '',
                    'memo' => $_POST['memo'] ?? '',
                    'representative_name' => $_POST['representative_name'] ?? ''
                );
                
                $validation = validate	($new_traveler);
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
                    'korean_name' => $_POST['korean_name'] ?? '',
                    'english_name' => $_POST['english_name'] ?? '',
                    'passport_number' => $_POST['passport_number'] ?? '',
                    'birth_date' => $_POST['birth_date'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'gender' => $_POST['gender'] ?? '',
                    'room_type' => $_POST['room_type'] ?? '2r1p',
                    'room_number' => $_POST['room_number'] ?? '',
                    'memo' => $_POST['memo'] ?? '',
                    'representative_name' => $_POST['representative_name'] ?? ''
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

            case 'lookup_product_by_code':
                $product_code = strtoupper(trim((string)($_POST['product_code'] ?? '')));
                if ($product_code === '') {
                    $response['message'] = '상품코드를 입력해주세요.';
                    break;
                }

                $product_row = getProductInfoByCode($product_code);
                if (!empty($product_row['p_code']) && !empty($product_row['p_name'])) {
                    $p_day = (int)($product_row['p_day'] ?? 0);
                    $response = array(
                        'success' => true,
                        'message' => '상품 정보를 찾았습니다.',
                        'data' => array(
                            'p_code' => trim((string)$product_row['p_code']),
                            'p_name' => trim((string)$product_row['p_name']),
                            'p_day' => $p_day
                        )
                    );
                } else {
                    $response['message'] = '해당 상품코드를 찾을 수 없습니다.';
                }
                break;
                
            case 'save_group_reservation':
                $group_data_json = $_POST['group_data'] ?? '{}';
                $travelers_data_json = $_POST['travelers_data'] ?? '[]';

                $current_group_data = json_decode($group_data_json, true);
                $current_travelers_data = json_decode($travelers_data_json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                     $response['message'] = '전송된 그룹 또는 여행자 데이터 형식이 잘못되었습니다: ' . json_last_error_msg();
                } else {
                    $result = saveReservationsByRepresentative($current_group_data, $current_travelers_data, $partner_id);
                    $response = $result;
                }
                break;
             case 'save_selected_group_reservation':
				$group_data_json = $_POST['group_data'] ?? '{}';
				$travelers_data_json = $_POST['travelers_data'] ?? '[]';
				
				$selected_group_data = json_decode($group_data_json, true);
				$selected_travelers_data = json_decode($travelers_data_json, true);

				if (json_last_error() !== JSON_ERROR_NONE) {
					$response['message'] = '전송된 그룹 또는 여행자 데이터 형식이 잘못되었습니다: ' . json_last_error_msg();
				} elseif (empty($selected_travelers_data)) {
					$response['message'] = '선택된 여행자가 없습니다.';
				} else {
					// 새로운 예약번호로 저장
					$result = saveReservationsByRepresentative($selected_group_data, $selected_travelers_data, $partner_id);
					$response = $result;
				}
				break;
			// ── 시트별 개별 저장 ──────────────────────────────────────────────
			case 'save_sheet':
				$sheet_group_json    = $_POST['sheet_group_data'] ?? '';
				$sheet_travelers_json = $_POST['sheet_travelers'] ?? '';
				if (empty($sheet_group_json) || empty($sheet_travelers_json)) {
					$response['message'] = '시트 데이터가 없습니다.'; break;
				}
				$sheet_group_data    = json_decode($sheet_group_json, true);
				$sheet_travelers_arr = json_decode($sheet_travelers_json, true);
				if (json_last_error() !== JSON_ERROR_NONE || empty($sheet_travelers_arr)) {
					$response['message'] = '데이터 형식 오류: ' . json_last_error_msg(); break;
				}
				$result = saveReservationsByRepresentative($sheet_group_data, $sheet_travelers_arr, $partner_id);
				$response = $result;
				break;

			default:
				$response['message'] = '알 수 없는 요청입니다.';
		}
	} catch (Exception $e) {
		$response['message'] = '처리 중 오류가 발생했습니다: ' . $e->getMessage();
	}

	echo json_encode($response, JSON_UNESCAPED_UNICODE);
	exit;
}

// -----------------------------------------------------------------------------
// 파일 업로드 처리 (비정형 데이터 지원)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SERVER['HTTP_X_REQUESTED_WITH']) && !isset($_FILES['reservation_files'])) {
    $error_messages[] = "업로드 파일이 서버로 전달되지 않았습니다. PHP upload_max_filesize/post_max_size 또는 폼 전송 상태를 확인해주세요.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['reservation_files'])) {
    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'partner_groups' . DIRECTORY_SEPARATOR;
    
    if ((!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) || (is_dir($upload_dir) && !is_writable($upload_dir))) {
        $fallback_upload_dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'prt_partner_groups' . DIRECTORY_SEPARATOR;
        if (!is_dir($fallback_upload_dir)) {
            mkdir($fallback_upload_dir, 0755, true);
        }
        if (is_dir($fallback_upload_dir) && is_writable($fallback_upload_dir)) {
            $upload_dir = $fallback_upload_dir;
        } else {
            $error_messages[] = "업로드 임시 폴더를 사용할 수 없습니다: " . $upload_dir;
        }
    }
    
    $files = $_FILES['reservation_files'];
    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) { // 여러 파일 업로드 처리 루프
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = basename($files['name'][$i]);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, array('xlsx', 'xls', 'csv'))) {
                    $safe_name = time() . '_' . $i . '_' . preg_replace("/[^\p{L}\p{N}._-]/u", "_", $file_name);
                    $file_path = $upload_dir . $safe_name;
                    
                    if (is_dir($upload_dir) && move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                        try {
                            $result = processAdvancedGroupFile($file_path, $file_name); // 파일당 한 번 호출
                            
                            if (!empty($result['sheet_errors'])) {
                                foreach ($result['sheet_errors'] as $sheet_error) {
                                    $error_messages[] = "파일 '{$file_name}': " . $sheet_error;
                                }
                            }
                            /*
                            echo "<pre>";
                            print_r($result);
                            */
                            if (!empty($result['travelers'])) {
                                $current_file_travelers = $result['travelers'];
                                $processed_data = array_merge($processed_data, $current_file_travelers); // 누적

                                // 시트별 데이터 누적
                                if (!empty($result['sheets'])) {
                                    foreach ($result['sheets'] as $_s) {
                                        $sheets_data[] = $_s;
                                    }
                                }

                                $current_file_group_info = $result['group_info'];
								//print_r($current_file_group_info);
                                if ($i === 0 || empty($group_info) || empty($group_info['tour_name'])) { // 첫 파일이거나, 기존 group_info가 비었으면
                                    $group_info = $current_file_group_info;
                                } else {
                                    // 기존 group_info에 정보 보충
                                    if ($current_file_group_info) {
                                        foreach ($current_file_group_info as $key => $value) {
                                            if (empty($group_info[$key]) && !empty($value)) {
                                                $group_info[$key] = $value;
                                            }
                                        }
                                    }
                                }
                                //print_r($group_info);
                                $sheets_processed_msg = "";
                                if (isset($result['processed_sheets_count']) && $result['processed_sheets_count'] > 0) {
                                     $sheets_processed_msg = " ({$result['processed_sheets_count']}개 시트)";
                                }
                                $success_messages[] = "✅ '{$file_name}'{$sheets_processed_msg} 처리 완료 (" . count($current_file_travelers) . "명의 여행자 추가)";

                            } elseif (empty($result['sheet_errors'])) { // 여행자는 없지만 시트 에러도 없다면 (예: 빈 파일)
                                $error_messages[] = "⚠️ '{$file_name}'에서 유효한 데이터를 찾을 수 없습니다.";
                            }
                        } catch (Exception $e) {
                            $error_messages[] = "❌ '{$file_name}' 처리 오류: " . $e->getMessage();
                        }
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    } else {
                        $error_messages[] = "❌ '{$file_name}' 업로드 임시 저장 실패: " . $upload_dir;
                    }
                } else {
                    $error_messages[] = "❌ '{$file_name}': 지원하지 않는 파일 형식";
                }
            } else {
                $error_messages[] = "❌ '" . basename((string)$files['name'][$i]) . "' 업로드 실패: " . getUploadErrorMessage((int)$files['error'][$i]);
            }
        }
        // 모든 파일 처리 후, 전체 $processed_data에 대해 중복 제거 및 기본 그룹 정보 최종 설정
        if (!empty($processed_data)) {
            $processed_data = removeDuplicateTravelers($processed_data); // 최종 중복 제거
            if (empty($group_info)) { // 만약 어떤 파일에서도 그룹정보를 못 얻었다면 초기화
                 $group_info = array('tour_name' => '', 'product_code' => '', 'start_date' => '', 'end_date' => '', 'group_leader' => '', 'group_phone' => '', 'group_email' => '');
            }
            // setDefaultGroupInfo($group_info, "종합 정보", $processed_data); // 대표 파일명 또는 일반적인 이름 사용
        }
    }
}


// -----------------------------------------------------------------------------
// 핵심 함수들 - 비정형 데이터 처리 강화
// -----------------------------------------------------------------------------

function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'php.ini upload_max_filesize 제한 초과';
        case UPLOAD_ERR_FORM_SIZE:
            return '폼 MAX_FILE_SIZE 제한 초과';
        case UPLOAD_ERR_PARTIAL:
            return '파일이 일부만 업로드됨';
        case UPLOAD_ERR_NO_FILE:
            return '파일이 선택되지 않음';
        case UPLOAD_ERR_NO_TMP_DIR:
            return '서버 임시 폴더 없음';
        case UPLOAD_ERR_CANT_WRITE:
            return '서버 디스크 쓰기 실패';
        case UPLOAD_ERR_EXTENSION:
            return 'PHP 확장에 의해 업로드 중단';
        default:
            return '알 수 없는 업로드 오류 코드 ' . $error_code;
    }
}

/**
 * 고급 그룹 파일 처리 - 비정형 데이터 지원 (다중 시트 처리)
 */
function processAdvancedGroupFile($file_path, $original_filename) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if ($ext === 'csv') {
        // CSV는 단일 시트 개념이므로 기존 로직 유지, 반환 형식 맞춤
        $csv_result = processAdvancedCSV($file_path, $original_filename);
        return array(
            'group_info' => $csv_result['group_info'],
            'travelers' => $csv_result['travelers'],
            'processed_sheets_count' => 1, // CSV는 1개 시트로 간주
            'sheet_errors' => array()
        );
    }
    
    if (in_array($ext, array('xlsx', 'xls'))) {
        if (!class_exists('SimpleXLSX')) {
            throw new Exception("Excel 파일 처리를 위해 SimpleXLSX 라이브러리가 필요합니다.");
        }
        
        $xlsx = SimpleXLSX::parse($file_path);
        if ($xlsx) {
            $all_travelers_from_sheets = array();
            $final_group_info_from_sheets = null;
            $fallback_group_info_from_sheets = null;
            $processed_sheets_count = 0;
            $sheet_processing_errors = array();
            $sheets_per_sheet = array(); // 시트별 개별 데이터

            $sheet_names = $xlsx->sheetNames();

            foreach ($sheet_names as $sheet_index => $sheet_name) {
                try {
                    $rows = $xlsx->rows($sheet_index);
                    if (empty($rows) || (count($rows) === 1 && empty(array_filter($rows[0])))) {
                        continue;
                    }

                    // 1) xlsx 병합 정보로 정확히 채우기
                    $merge_ranges   = getMergeRanges($xlsx, $sheet_index);
                    $processed_rows = applyMergedCellValues($rows, $merge_ranges);
                    // 2) 병합 정보가 없거나 누락된 경우 heuristic 보완
                    $processed_rows = processMergedCells($processed_rows);
                    $filename_for_sheet = $original_filename . " (Sheet: " . htmlspecialchars($sheet_name) . ")";
                    $sheet_data_result  = extractAdvancedGroupData($processed_rows, $filename_for_sheet);

                    if (!empty($sheet_data_result['travelers'])) {
                        $sheet_travelers = removeDuplicateTravelers($sheet_data_result['travelers']);
                        $all_travelers_from_sheets = array_merge($all_travelers_from_sheets, $sheet_travelers);

                        // 시트별 개별 데이터 저장
                        $sheets_per_sheet[] = array(
                            'sheet_name'  => $sheet_name,
                            'sheet_index' => $sheet_index,
                            'group_info'  => $sheet_data_result['group_info'] ?? array(),
                            'travelers'   => $sheet_travelers,
                        );

                        if ($final_group_info_from_sheets === null && !empty($sheet_data_result['group_info'])) {
                            $final_group_info_from_sheets = $sheet_data_result['group_info'];
                        } elseif ($final_group_info_from_sheets !== null && !empty($sheet_data_result['group_info'])) {
                            foreach ($sheet_data_result['group_info'] as $key => $value) {
                                if (empty($final_group_info_from_sheets[$key]) && !empty($value)) {
                                    $final_group_info_from_sheets[$key] = $value;
                                }
                            }
                        }
                    } elseif (!empty($sheet_data_result['group_info'])) {
                        if ($fallback_group_info_from_sheets === null) {
                            $fallback_group_info_from_sheets = $sheet_data_result['group_info'];
                        } else {
                            foreach ($sheet_data_result['group_info'] as $key => $value) {
                                if (empty($fallback_group_info_from_sheets[$key]) && !empty($value)) {
                                    $fallback_group_info_from_sheets[$key] = $value;
                                }
                            }
                        }
                    }
                    $processed_sheets_count++;
                } catch (Exception $e) {
                    $sheet_processing_errors[] = "시트 '" . htmlspecialchars($sheet_name) . "' 처리 오류!!!: " . $e->getMessage();
                }
            }

            if ($processed_sheets_count === 0 && !empty($sheet_processing_errors)) {
                // 모든 시트 처리 실패
                throw new Exception(implode("; ", $sheet_processing_errors));
            }
            
            // 최종 그룹 정보 객체가 없으면 초기화
			
			
            if ($final_group_info_from_sheets === null && $fallback_group_info_from_sheets !== null) {
                $final_group_info_from_sheets = $fallback_group_info_from_sheets;
            }

            if ($final_group_info_from_sheets !== null && $fallback_group_info_from_sheets !== null) {
                foreach (array('start_date', 'end_date') as $date_key) {
                    if (empty($final_group_info_from_sheets[$date_key]) && !empty($fallback_group_info_from_sheets[$date_key])) {
                        $fallback_date = formatAdvancedDate($fallback_group_info_from_sheets[$date_key]);
                        if (!empty($fallback_date) && substr($fallback_date, 0, 4) >= '2000') {
                            $final_group_info_from_sheets[$date_key] = $fallback_date;
                        }
                    }
                }
            }

            if ($final_group_info_from_sheets === null) {
                $final_group_info_from_sheets = array(
                    'product_code' => '','tour_name' => '', 'start_date' => '', 'end_date' => '',
                    'group_leader' => '', 'group_phone' => '', 'group_email' => '',
                );
            }
            
            // 모든 시트의 여행자 목록과 원본 파일명을 사용하여 기본 그룹 정보 최종 설정
            //setDefaultGroupInfo($final_group_info_from_sheets, $original_filename, $all_travelers_from_sheets);
            //print_r($final_group_info_from_sheets);
            // 모든 시트에서 수집된 여행자 목록의 최종 중복 제거
            $all_travelers_from_sheets = removeDuplicateTravelers($all_travelers_from_sheets);

            return array(
                'group_info'             => $final_group_info_from_sheets,
                'travelers'              => $all_travelers_from_sheets,
                'sheets'                 => $sheets_per_sheet,
                'processed_sheets_count' => $processed_sheets_count,
                'sheet_errors'           => $sheet_processing_errors
            );

        } else {
            throw new Exception("Excel 파일 파싱 실패: " . SimpleXLSX::parseError());
        }
    }
    
    throw new Exception("지원하지 않는 파일 형식입니다.");
}


/**
 * 엑셀 열 문자 → 0-based 인덱스 변환 (A=0, B=1, Z=25, AA=26, ...)
 */
function colLetterToIndex($letters) {
    $letters = strtoupper(trim($letters));
    $idx = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $idx = $idx * 26 + (ord($letters[$i]) - 64);
    }
    return $idx - 1;
}

/**
 * SimpleXLSX 워크시트 객체에서 병합 셀 범위 배열 추출
 * 반환: [ ['row_start'=>0based, 'row_end'=>0based, 'col_start'=>0based, 'col_end'=>0based], ... ]
 */
function getMergeRanges($xlsx, $sheet_index) {
    $ranges = array();
    try {
        $ws = $xlsx->worksheet($sheet_index);
        if (!$ws || !isset($ws->mergeCells)) return $ranges;
        foreach ($ws->mergeCells->mergeCell as $mc) {
            $ref = (string)$mc['ref']; // e.g. "E12:G13"
            if (!preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/i', $ref, $m)) continue;
            $ranges[] = array(
                'col_start' => colLetterToIndex($m[1]),
                'row_start' => (int)$m[2] - 1,
                'col_end'   => colLetterToIndex($m[3]),
                'row_end'   => (int)$m[4] - 1,
            );
        }
    } catch (Exception $e) { /* 무시 */ }
    return $ranges;
}

/**
 * 실제 병합 범위 정보를 이용해 상단-좌측 셀 값을 범위 전체에 채움
 */
function applyMergedCellValues($rows, $merge_ranges) {
    if (empty($merge_ranges)) return $rows;
    foreach ($merge_ranges as $m) {
        $top_val = isset($rows[$m['row_start']][$m['col_start']])
            ? $rows[$m['row_start']][$m['col_start']] : '';
        if ((string)$top_val === '') continue; // 빈 병합은 무시
        for ($r = $m['row_start']; $r <= $m['row_end']; $r++) {
            if (!isset($rows[$r])) continue;
            for ($c = $m['col_start']; $c <= $m['col_end']; $c++) {
                if ($r === $m['row_start'] && $c === $m['col_start']) continue;
                $rows[$r][$c] = $top_val;
            }
        }
    }
    return $rows;
}

/**
 * 병합된 셀 처리 (heuristic fallback – xlsx 병합 정보 없을 때 사용)
 */
function processMergedCells($rows) {
    if (empty($rows)) return $rows;
    
    $processed_rows = array();
    $max_cols = 0;
    
    foreach ($rows as $row) {
        if (is_array($row)) {
            $max_cols = max($max_cols, count($row));
        }
    }
    
    $last_values = array_fill(0, $max_cols, '');
    
    foreach ($rows as $row_idx => $row) {
        if (!is_array($row)) {
            $processed_rows[] = $row; // Keep non-array rows as is (e.g., sheet properties)
            continue;
        }
        
        $processed_row = array();
        
        for ($col_idx = 0; $col_idx < $max_cols; $col_idx++) {
            $cell_value = isset($row[$col_idx]) ? $row[$col_idx] : '';
            $cell_value_trimmed = trim((string)$cell_value);
            
            if ($cell_value_trimmed === '' || $cell_value === null) {
                // 향상된 병합 셀 감지 로직 (shouldFillMergedCell은 예시이며, 실제 SimpleXLSX의 병합 정보 사용이 더 정확)
                // SimpleXLSX 자체는 병합된 셀의 값을 모든 해당 셀에 채워주지 않으므로, 이 로직은 필요.
                if (shouldFillMergedCell($rows, $row_idx, $col_idx, $last_values[$col_idx])) {
                    $processed_row[$col_idx] = $last_values[$col_idx];
                } else {
                    $processed_row[$col_idx] = $cell_value; // null 또는 빈 문자열 유지
                }
            } else {
                $processed_row[$col_idx] = $cell_value;
                $last_values[$col_idx] = $cell_value_trimmed; // 현재 셀 값으로 마지막 값 업데이트
            }
        }
        
        $processed_rows[] = $processed_row;
    }
    
    return $processed_rows;
}

/**
 * 병합된 셀 여부 판단 (이 함수는 근사치이며, 완벽한 병합 감지는 파일 포맷에 따라 복잡할 수 있음)
 */
function shouldFillMergedCell($rows, $current_row_idx, $col_idx, $last_non_empty_value_in_col) {
    if (empty($last_non_empty_value_in_col)) return false; // 이전 값이 없으면 채울 수 없음

    // 로직: 현재 셀이 비어있고, 이전 행의 같은 열에 값이 있었으며,
    // 현재 행의 다른 주요 열들(예: 이름, 여권번호 등)에 데이터가 있다면 병합된 것으로 간주할 수 있다.
    // 또는, 아래 몇몇 행의 같은 열이 계속 비어있고, 그 다음에 값이 나타나면, 그 사이는 병합 영역일 수 있다.
    // 이 구현은 매우 단순화된 버전입니다. 실제로는 더 정교한 로직이나 라이브러리 기능이 필요합니다.

    // 현재 행의 첫 번째 또는 두 번째 열(보통 이름이나 번호)이 비어있지 않은지 확인
    $is_data_row = false;
    if (isset($rows[$current_row_idx][0]) && trim((string)$rows[$current_row_idx][0]) !== '') $is_data_row = true;
    if (!$is_data_row && isset($rows[$current_row_idx][1]) && trim((string)$rows[$current_row_idx][1]) !== '') $is_data_row = true;

    if ($is_data_row) { // 데이터가 있는 행으로 보이면, 이전 값을 가져올 가능성 높음
         // 특정 열(예: room_type, group_leader 등)은 자주 병합됨
         // 여기서는 일반적인 상황을 가정
        if ($col_idx > 0 ) { // 첫번째 열이 아니라면 이전 값 사용 시도
            // 바로 위 행의 같은 열이 비어있지 않았는지 확인하는 것은 $last_non_empty_value_in_col로 이미 확인됨
            return true; 
        }
    }
    
    // 더 정교한 로직: 아래 몇 행을 확인하여 병합된 셀인지 추론
    $empty_count_below = 0;
    $next_value_found_below = false;
    $check_range = min(3, count($rows) - $current_row_idx - 1);

    for ($i = 1; $i <= $check_range; $i++) {
        $next_row_look_idx = $current_row_idx + $i;
        if (isset($rows[$next_row_look_idx][$col_idx])) {
            $next_cell_look = trim((string)$rows[$next_row_look_idx][$col_idx]);
            if ($next_cell_look === '') {
                $empty_count_below++;
            } else {
                $next_value_found_below = true;
                break;
            }
        }
    }
    // 아래로 빈 셀이 이어지다가 값이 나오거나, 일정 수 이상 빈 셀이 이어지면 병합으로 간주
    return $empty_count_below > 0 && ($next_value_found_below || $empty_count_below >= 2);
}


/**
 * CSV 파일 고급 처리
 */
function processAdvancedCSV($file_path, $filename) {
    $csv_data = array();
    
    $file_content = file_get_contents($file_path);
    $encoding = mb_detect_encoding($file_content, array('UTF-8', 'EUC-KR', 'CP949'), true);
    
    if ($encoding && strtoupper($encoding) !== 'UTF-8') {
        $file_content = mb_convert_encoding($file_content, 'UTF-8', $encoding);
        // 주의: 원래 파일을 직접 수정하는 것은 위험할 수 있습니다. 임시 파일 사용 고려.
        // 여기서는 제공된 코드의 동작을 유지합니다.
        file_put_contents($file_path, $file_content); 
    }
    
    $handle = fopen($file_path, "r");
    if ($handle === false) {
        throw new Exception("CSV 파일을 열 수 없습니다.");
    }
    
    // BOM 제거
    if (fgets($handle, 4) !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    
    while (($row = fgetcsv($handle, 0, ",")) !== false) {
        $csv_data[] = $row;
    }
    fclose($handle);
    
    $processed_csv = processMergedCells($csv_data); // CSV도 병합 유사 패턴이 있을 수 있으므로 적용
    return extractAdvancedGroupData($processed_csv, $filename);
}

/**
 * 고급 그룹 데이터 추출 - 비정형 데이터 지원
 * (내부의 setDefaultGroupInfo 호출 제거됨 - processAdvancedGroupFile에서 최종적으로 호출)
 */
function extractAdvancedGroupData($rows, $source_identifier_for_defaults) {
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
    $headers = null;
    $data_start_row = -1;
    $group_info['product_code'] = extractProductCodeFromFilename($source_identifier_for_defaults);
    $arrpro = getProductInfoByCode($group_info['product_code']);
    if (!empty($arrpro['p_code'])) {
        $group_info['product_code'] = $arrpro['p_code'];
    }
    if (!empty($arrpro['p_name'])) {
        $group_info['tour_name'] = $arrpro['p_name'];
    }
    $group_scan_limit_row_idx = count($rows);
    foreach ($rows as $row_idx => $row) {
        if (isLikelyHeaderRow($row)) {
            $group_scan_limit_row_idx = $row_idx;
            break;
        }
    }
    $all_dates_found_in_sheet = array();
    foreach ($rows as $row_idx => $row) {
        if (empty($row) || !is_array($row)) continue;
        if ($row_idx >= $group_scan_limit_row_idx) continue;
        
        foreach ($row as $cell_idx => $cell_content) {
            $cell_text = trim((string)$cell_content);
            if (empty($cell_text)) continue;
            
            $dates_in_cell = extractDatesFromText($cell_text);
            if (!empty($dates_in_cell['start']) || !empty($dates_in_cell['end'])) {
                $all_dates_found_in_sheet[] = $dates_in_cell;
            }
            extractGroupInfoFromCell($cell_text, $group_info, $row, $cell_idx);
        }
        
        $row_text = implode(' ', array_filter(array_map('trim', $row)));
        if (!empty($row_text)) {
            $row_dates = extractDatesFromText($row_text);
            if (!empty($row_dates['start']) || !empty($row_dates['end'])) {
                $all_dates_found_in_sheet[] = $row_dates;
            }
            extractGroupInfoFromText($row_text, $group_info);
        }
    }
    
    if (!empty($all_dates_found_in_sheet)) {
        $best_dates = findBestDatePair($all_dates_found_in_sheet);
        if ($best_dates) {
            if (empty($group_info['start_date']) && !empty($best_dates['start'])) $group_info['start_date'] = $best_dates['start'];
            if (empty($group_info['end_date']) && !empty($best_dates['end'])) $group_info['end_date'] = $best_dates['end'];
        }
    }
    //print_r($group_info);
    // 가장 가능성이 높은 헤더 행 선택 (첫 매칭 행 고정 대신 점수 기반)
    $header_keywords = array(
        '성명', '이름', 'name', '여권', 'passport', '번호', 'no', '생년월일', 'birth',
        '성별', '예약자', '예약대표자', '연락처', '영문성명', '한글성명', '구분',
        '여권만기일', '여권만료일', 'rm.no', 'rm no', 'rm.n', 'cn', 'cnt'
    );
    $best_header_row_idx = -1;
    $best_header_score = -1;

    foreach ($rows as $row_idx => $row) {
        if (empty($row) || !is_array($row)) continue;
        if (isLikelySummaryRow($row)) continue;
       
        $keyword_match_count = 0;
        $has_name_key = false;
        $has_birth_key = false;
        $has_rep_key = false;
        
        foreach ($row as $cell) {
            $cell_text = strtolower(trim((string)$cell));
            
            if ($cell_text === '') continue;
            $cell_compact = preg_replace('/\s+/u', '', $cell_text);

            foreach ($header_keywords as $keyword) {
                if (strpos($cell_text, $keyword) !== false || strpos($cell_compact, preg_replace('/\s+/u', '', $keyword)) !== false) {
                    $keyword_match_count++;
                    break;
                }
            }

            if (strpos($cell_text, '성명') !== false || strpos($cell_text, '이름') !== false || strpos($cell_text, 'name') !== false || strpos($cell_text, '영문성명') !== false || strpos($cell_text, '한글성명') !== false) {
                $has_name_key = true;
            }
            if (strpos($cell_text, '생년월일') !== false || strpos($cell_text, 'birth') !== false) {
                $has_birth_key = true;
            }
            if (strpos($cell_text, '예약대표자') !== false || strpos($cell_compact, '예약대표자') !== false || strpos($cell_text, '예약자') !== false) {
                $has_rep_key = true;
            }
        }

        if ($keyword_match_count >= 2) {
            $score = $keyword_match_count;
            if ($has_name_key) $score += 2;
            if ($has_birth_key) $score += 1;
            if ($has_rep_key) $score += 2;

            if ($score > $best_header_score) {
                $best_header_score = $score;
                $best_header_row_idx = $row_idx;
            }
        }
    }

    if ($best_header_row_idx >= 0) {
        $headers = array_map(function($h) { return trim((string)$h); }, $rows[$best_header_row_idx]);
        $data_start_row = $best_header_row_idx + 1;
    }
    
    if ($headers !== null && $data_start_row !== -1) {
        for ($i = $data_start_row; $i < count($rows); $i++) {
            $data_row = $rows[$i];
            if (empty($data_row) || !is_array($data_row) || empty(array_filter($data_row, function($cell){ return trim((string)$cell) !== ''; }))) {
                continue; // 빈 행이거나 내용 없는 행 스킵
            }
            if (isLikelyHeaderRow($data_row)) {
                continue; // 중간에 반복되는 헤더 행 스킵
            }
            if (isLikelySummaryRow($data_row)) {
                continue; // 합계/요약 행 스킵
            }
            
            $traveler = extractAdvancedTravelerData($data_row, $headers);
            $traveler['representative_name'] = normalizeRepresentativeName($traveler['representative_name'] ?? '');
            
            if (!empty($traveler['korean_name']) || !empty($traveler['english_name']) || !empty($traveler['passport_number'])) {
                $travelers[] = $traveler;
            }
        }
    }

    // 병합셀/줄바꿈 헤더로 누락된 대표예약자 보정
    $travelers = normalizeTravelerRepresentativeNames($travelers, $group_info['group_leader'] ?? '');
    
    // setDefaultGroupInfo 는 여기서 호출하지 않음. processAdvancedGroupFile 에서 최종적으로 호출.
    // $travelers = removeDuplicateTravelers($travelers); // 시트 레벨 중복제거는 최종 단계에서 한번만 하는 것으로 변경

    return array(
        'group_info' => $group_info,
        'travelers' => $travelers
    );
}


/**
 * 셀에서 그룹 정보 추출
 */
function extractGroupInfoFromCell($cell_text, &$group_info, $row, $cell_idx) {
    $cell_lower = strtolower($cell_text);
    
    $next_value = '';
    // 다음 몇 개의 셀까지 확인하여 값을 가져옴 (병합된 셀 등을 고려)
    for ($k = $cell_idx + 1; $k < count($row) && $k <= $cell_idx + 3; $k++) {
        $val = trim((string)($row[$k] ?? ''));
        if ($val === $cell_text || isGroupInfoPlaceholderValue($val)) {
            continue;
        }
        if (!empty($val)) {
            $next_value = $val;
            break;
        }
    }
    // 만약 다음 셀에 값이 없다면, 현재 셀 자체를 값으로 사용 시도 (예: "상품명: 어드벤처 투어" 형태)
    if (empty($next_value) && strpos($cell_text, ':') !== false) {
        list(, $potential_value) = explode(':', $cell_text, 2);
        $potential_value = trim($potential_value);
        if (!empty($potential_value)) $next_value = $potential_value;
    }
     if (empty($group_info['product_code']) && (strpos($cell_lower, '상품코드') !== false || strpos($cell_lower, '코드') !== false || strpos($cell_lower, '코드명') !== false)) {
       if(!empty($next_value)) $group_info['product_code'] = $next_value;
     } 
    if (empty($group_info['tour_name']) && (strpos($cell_lower, '상품명') !== false || strpos($cell_lower, '행사명') !== false || strpos($cell_lower, '투어명') !== false)) {
        if(!empty($next_value)) $group_info['tour_name'] = $next_value;
    }
    
    if (empty($group_info['product_code']) && (strpos($cell_lower, '상품코드') !== false || strpos($cell_lower, '행사코드') !== false)) {
         if(!empty($next_value)) $group_info['product_code'] = $next_value;
    }

    if (empty($group_info['group_leader']) && (strpos($cell_lower, '대표자') !== false || strpos($cell_lower, '인솔자') !== false || strpos($cell_lower, '담당자') !== false)) {
        $parsed_leader = normalizeRepresentativeName($next_value);
        if (!empty($parsed_leader)) $group_info['group_leader'] = $parsed_leader;
    }
    
    if (empty($group_info['group_phone']) && (strpos($cell_lower, '연락처') !== false || strpos($cell_lower, '전화번호') !== false) && (strpos($cell_lower, '여행자') === false && strpos($cell_lower, '참가자') === false) ) { // 여행자 연락처와 구분
         if(!empty($next_value)) $group_info['group_phone'] = $next_value;
    }
    
    if (empty($group_info['group_email']) && (strpos($cell_lower, '이메일') !== false || strpos($cell_lower, '메일') !== false) && (strpos($cell_lower, '여행자') === false && strpos($cell_lower, '참가자') === false) ) {
         if(!empty($next_value)) $group_info['group_email'] = $next_value;
    }

    // 출발일 / 도착일 라벨 → 바로 옆 셀의 날짜를 start_date / end_date 로 설정
    $is_start_label = preg_match('/출발일|출발\s*날짜|departure\s*date|dep\.?\s*date/iu', $cell_text);
    $is_end_label   = preg_match('/도착일|귀국일|종료일|arrival\s*date|return\s*date/iu', $cell_text);
    if (($is_start_label || $is_end_label) && !empty($next_value)) {
        $parsed_date = formatAdvancedDate($next_value);
        if (empty($parsed_date)) {
            // 날짜가 인접 셀 텍스트에 포함된 경우 추출 시도
            $d = extractDatesFromText($next_value);
            $parsed_date = $d['start'] ?? '';
        }
        if (!empty($parsed_date)) {
            if ($is_start_label && empty($group_info['start_date'])) {
                $group_info['start_date'] = $parsed_date;
            }
            if ($is_end_label && empty($group_info['end_date'])) {
                $group_info['end_date'] = $parsed_date;
            }
        }
    }
}

function isGroupInfoPlaceholderValue($value) {
    $text = trim((string)$value);
    if ($text === '') {
        return true;
    }

    $compact = preg_replace('/\s+/u', '', $text);
    $labels = array(
        '[행사명]', '[출발일]', '[출발편]', '[출발시간]', '[인솔자]',
        '행사명', '출발일', '출발편', '출발시간', '인솔자',
        '대표자', '담당자', '연락처', '전화번호', '비상연락처'
    );

    foreach ($labels as $label) {
        if ($compact === preg_replace('/\s+/u', '', $label)) {
            return true;
        }
    }

    return false;
}

/**
* 텍스트에서 그룹 정보 추출
*/
function extractGroupInfoFromText($text, &$group_info) {
   if (empty($group_info['product_code'])) {
       if (preg_match('/[\[\{]([A-Za-z0-9_-]+)[\]\}]/', $text, $matches)) { // 상품코드 패턴 [ABC-123] 또는 {ABC-123}
           $group_info['product_code'] = $matches[1];
       } elseif (preg_match('/\b([A-Z]{2,5}-?\d{3,6}\b)/', $text, $matches)) { // 대문자조합-숫자조합 (예: US-12345)
            $group_info['product_code'] = $matches[1];
       }
   }
   if (empty($group_info['tour_name'])) {
       if (preg_match('/(투어명|상품명|행사명)\s*[:\-]?\s*([^\n\r\(]+)/ui', $text, $matches)) {
           $group_info['tour_name'] = trim($matches[2]);
       }
   }
   // "출발일" 또는 "도착일" 라벨 + 날짜 패턴이 같은 텍스트에 있는 경우 추출
   // 예: "[출발일] 2026-02-18" 또는 "출발일: 2026.02.18"
   if (empty($group_info['start_date'])) {
       if (preg_match('/(?:출발일|출발\s*날짜|departure\s*date)\s*[\]\):\-]?\s*(\d{4}[\.\-\/]\d{1,2}[\.\-\/]\d{1,2})/iu', $text, $m)) {
           $parsed = formatAdvancedDate($m[1]);
           if (!empty($parsed)) $group_info['start_date'] = $parsed;
       }
   }
   if (empty($group_info['end_date'])) {
       if (preg_match('/(?:도착일|귀국일|종료일|arrival\s*date|return\s*date)\s*[\]\):\-]?\s*(\d{4}[\.\-\/]\d{1,2}[\.\-\/]\d{1,2})/iu', $text, $m)) {
           $parsed = formatAdvancedDate($m[1]);
           if (!empty($parsed)) $group_info['end_date'] = $parsed;
       }
   }
}

function isLikelySummaryRow($row) {
    if (!is_array($row) || empty($row)) {
        return false;
    }

    $row_text = trim(implode(' ', array_map(function($cell) {
        return trim((string)$cell);
    }, $row)));

    if ($row_text === '') {
        return false;
    }

    // 합계/총계/Total 및 비탑승객 섹션 행 제외
    if (preg_match('/(합\s*계|총\s*계|\btotal\b|pnr\s*정보|전달사항|출력일시|보안등급)/iu', $row_text)) {
        return true;
    }

    // 개인정보 보호 경고 문구 행 제외 (여행사 문서 하단/상단에 자주 포함되는 법적 고지)
    if (preg_match('/(파기\s*책임|정보통신망법|개인정보.*유출|징역|벌금|출력.*파기|operating.*문서|보호조치|출력의\s*목적)/iu', $row_text)) {
        return true;
    }

    return false;
}

function isLikelyHeaderRow($row) {
    if (!is_array($row) || empty($row)) {
        return false;
    }

    $header_keywords = array(
        'rm.no', 'rm no', 'rm.n', 'cn', 'cnt', '예약대표자', '예약자', '영문성명', '한글성명',
        '성별', '구분', '생년월일', '여권번호', '여권만기일', '여권만료일', '비고', '인솔자영문명'
    );

    $matched = 0;
    foreach ($row as $cell) {
        $cell_text = strtolower(trim((string)$cell));
        if ($cell_text === '') {
            continue;
        }
        foreach ($header_keywords as $keyword) {
            if (strpos($cell_text, $keyword) !== false) {
                $matched++;
                break;
            }
        }
    }

    return $matched >= 3;
}

function normalizeTravelerRepresentativeNames($travelers, $fallback_group_leader = '') {
    if (empty($travelers) || !is_array($travelers)) {
        return $travelers;
    }

    $fallback_group_leader = normalizeRepresentativeName($fallback_group_leader);
    $count = count($travelers);

    for ($i = 0; $i < $count; $i++) {
        $travelers[$i]['representative_name'] = normalizeRepresentativeName($travelers[$i]['representative_name'] ?? '');
    }

    // Forward fill: 동일 연락처 구간의 누락 대표예약자 채우기
    for ($i = 1; $i < $count; $i++) {
        if (!empty($travelers[$i]['representative_name'])) {
            continue;
        }
        $prev_rep = $travelers[$i - 1]['representative_name'] ?? '';
        if (empty($prev_rep)) {
            continue;
        }
        $cur_phone = preg_replace('/\D/', '', (string)($travelers[$i]['phone'] ?? ''));
        $prev_phone = preg_replace('/\D/', '', (string)($travelers[$i - 1]['phone'] ?? ''));
        // 연락처 컬럼이 없거나 비어있는 양식도 많아서, 전화번호가 비면 인접 대표예약자를 그대로 전파
        if (($cur_phone !== '' && $cur_phone === $prev_phone) || $cur_phone === '' || $prev_phone === '') {
            $travelers[$i]['representative_name'] = $prev_rep;
        }
    }

    // Backward fill: 값이 아래 행에 있는 병합셀 케이스 보정
    for ($i = $count - 2; $i >= 0; $i--) {
        if (!empty($travelers[$i]['representative_name'])) {
            continue;
        }
        $next_rep = $travelers[$i + 1]['representative_name'] ?? '';
        if (empty($next_rep)) {
            continue;
        }
        $cur_phone = preg_replace('/\D/', '', (string)($travelers[$i]['phone'] ?? ''));
        $next_phone = preg_replace('/\D/', '', (string)($travelers[$i + 1]['phone'] ?? ''));
        if (($cur_phone !== '' && $cur_phone === $next_phone) || $cur_phone === '' || $next_phone === '') {
            $travelers[$i]['representative_name'] = $next_rep;
        }
    }

    // 그래도 비어있으면 그룹 대표자 사용
    if ($fallback_group_leader !== '') {
        for ($i = 0; $i < $count; $i++) {
            if (empty($travelers[$i]['representative_name'])) {
                $travelers[$i]['representative_name'] = $fallback_group_leader;
            }
        }
    }

    return $travelers;
}


/**
 * 여행자 데이터 고급 추출
 */
function extractAdvancedTravelerData($row, $headers) {
    
	
	$data = array(
        'korean_name' => '', 'english_name' => '', 'passport_number' => '', 'birth_date' => '',
        'phone' => '', 'email' => '', 'gender' => '', 
        'room_type' => '2r1p', // 기본값
        'room_number' => '', 'memo' => '',
        'representative_name' => ''
    );
    
    $field_patterns = array(
        'korean_name' => array('/한글/ui', '/성명/ui', '/(?:국문|한글)이름/ui', '/이름/ui'), // '이름'은 다른 필드와 겹칠 수 있어 우선순위 조정 필요
        'english_name' => array('/영문/ui', '/영어이름/ui', '/english\s*name/ui', '/passport\s*name/ui'),
        'passport_number' => array('/여권(?:\s*번호)?/ui', '/passport\s*(?:no|number)?/ui', '/\bpp\b\s*no/ui'),
        'birth_date' => array(
            // 생년월일 패턴을 더 다양하게 추가
            '/생년월일/ui', '/생일/ui', '/BIRTH/ui', '/DOB/ui', 
            '/DATE\s*OF\s*BIRTH/ui', '/BIRTH\s*DATE/ui', '/생년/ui',
            '/출생일/ui', '/출생년월일/ui', '/생년\s*월일/ui', '/Birth\s*Day/ui',
            '/생\s*년\s*월\s*일/ui', '/태어난\s*날/ui', '/출생\s*날짜/ui',
            '/생\s*일/ui', '/birthday/ui', '/BIRTHDAY/ui', '/출생/ui',
            '/년\s*월\s*일/ui'),  // 단순히 "년월일"만 있어도 생년월일로 간주
        'phone' => array('/연락처/ui', '/전화번호?/ui', '/phone/ui', '/mobile/ui', '/핸드폰/ui', '/휴대폰/ui'),
        'email' => array('/이메일/ui', '/email/ui', '/e-mail/ui', '/메일주소/ui'),
        'gender' => array('/성별/ui', '/gender/ui', '/sex/ui','/성/ui'),
        'room_type' => array('/객실.*타입/ui', '/룸타입/ui', '/room\s*type/ui'),
        'room_number' => array('/객실.*번호/ui', '/룸번호/ui', '/room\s*no/ui', '/rm\.?\s*n\s*o/ui', '/rm\s*no/ui', '/방번호/ui'),
        'memo' => array('/비고/ui', '/메모/ui', '/memo/ui', '/note/ui', '/특이사항/ui', '/요청사항/ui', '/여권만기일/ui', '/여권만료일/ui', '/passport\s*expiry/ui'),
        'representative_name' => array('/예약\s*대표\s*자/ui', '/예약대\s*표자/ui', '/예약자/ui', '/예약자명/ui', '/대표\s*예약/ui', '/대표예약자/ui', '/대표자/ui', '/예약대표/ui', '/인솔자/ui', '/booker/ui')
    );

    // 이름 필드를 먼저 채우려는 시도 (한글 또는 영문)
    $name_col_idx = -1; $eng_name_col_idx = -1;
    foreach ($headers as $col_idx => $header_text_raw) {
        $header_text = trim((string)$header_text_raw);
        $header_text_compact = preg_replace('/\s+/u', '', $header_text);
        if (empty($data['korean_name'])) {
            foreach ($field_patterns['korean_name'] as $pattern) {
                if (preg_match($pattern, $header_text) || preg_match($pattern, $header_text_compact)) {
                    $cell_val = isset($row[$col_idx]) ? trim((string)$row[$col_idx]) : '';
                    if (preg_match('/[가-힣]/u', $cell_val)) {
                        $data['korean_name'] = $cell_val;
                        $name_col_idx = $col_idx;
                        break;
                    }
                }
            }
        }
        if (empty($data['english_name'])) {
             foreach ($field_patterns['english_name'] as $pattern) {
                if (preg_match($pattern, $header_text) || preg_match($pattern, $header_text_compact)) {
                    $cell_val = isset($row[$col_idx]) ? trim((string)$row[$col_idx]) : '';
                     if (preg_match('/[A-Za-z\s]/', $cell_val) && !preg_match('/[가-힣]/u', $cell_val)) { // 순수 영문자(+공백)만
                        $data['english_name'] = strtoupper($cell_val);
                        $eng_name_col_idx = $col_idx;
                        break;
                    }
                }
            }
        }
    }
    
    foreach ($headers as $col_idx => $header_text_raw) {
        $header_text = trim((string)$header_text_raw);
        $header_text_compact = preg_replace('/\s+/u', '', $header_text);
        $cell_value = isset($row[$col_idx]) ? trim((string)$row[$col_idx]) : '';
      
		
        if (empty($cell_value)) continue;

        // 이름 필드는 위에서 우선 처리했으므로, 여기서는 스킵하거나 보강
        if ($col_idx === $name_col_idx || $col_idx === $eng_name_col_idx) continue;

        foreach ($field_patterns as $field => $patterns) {
            if (!empty($data[$field]) && !in_array($field, ['memo', 'korean_name', 'english_name'])) continue; // 이름, 메모 제외하고 이미 채워졌으면 스킵
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $header_text) || preg_match($pattern, $header_text_compact)) {
                    switch ($field) {
                        case 'korean_name': // 보강 로직
                            if (empty($data['korean_name']) && preg_match('/[가-힣]/u', $cell_value)) {
                                $data[$field] = $cell_value;
                            }
                            break;
                        case 'english_name': // 보강 로직
                            if (empty($data['english_name']) && preg_match('/[A-Za-z\s]/', $cell_value) && !preg_match('/[가-힣]/u', $cell_value)) {
                                $data[$field] = strtoupper($cell_value);
                            }
                            break;
                        case 'passport_number':
							
							$cell_val = isset($row[$col_idx]) ? trim((string)$row[$col_idx]) : '';
							
							// "완료일" 등이 포함된 경우 메모로 이동
							if (preg_match('/(\d{4})[\.\-\/\s년](\d{1,2})[\.\-\/\s월](\d{1,2})/', $cell_val)) {
								if (!empty($data['memo'])) {
									$data['memo'] .= '여권만료일 | ' . $cell_val;
								} else {
									$data['memo'] = '여권만료일 | ' .$cell_val;
								}
							} else {
								// 일반적인 여권번호 처리
								$passport = preg_replace('/[^A-Za-z0-9]/', '', $cell_val);
								if (strlen($passport) >= 6 && strlen($passport) <= 12) {
									$data['passport_number'] = strtoupper($passport);
								}
							}
							break;
				        case 'birth_date':
							$birth_value = trim((string)$cell_value);
							$formatted_birth = '';
							
							// 빈 값이면 건너뛰기
							if (empty($birth_value)) break;
							
							// 1. 8자리 숫자 (19901225)
							if (preg_match('/^(\d{8})$/', $birth_value)) {
								$year = substr($birth_value, 0, 4);
								$month = substr($birth_value, 4, 2);
								$day = substr($birth_value, 6, 2);
								$formatted_birth = $year . '-' . $month . '-' . $day;
							}
							// 2. 6자리 숫자 (901225 → 1990-12-25)
							elseif (preg_match('/^(\d{6})$/', $birth_value)) {
								$year_short = substr($birth_value, 0, 2);
								$month = substr($birth_value, 2, 2);
								$day = substr($birth_value, 4, 2);
								$year = ($year_short >= 20) ? '19' . $year_short : '20' . $year_short;
								$formatted_birth = $year . '-' . $month . '-' . $day;
							}
							// 3. 구분자 있는 형태들
							elseif (preg_match('/(\d{4})[\.\-\/\s년](\d{1,2})[\.\-\/\s월](\d{1,2})/', $birth_value, $matches)) {
								$formatted_birth = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
							}
							// 4. 2자리 연도
							elseif (preg_match('/(\d{2})[\.\-\/](\d{1,2})[\.\-\/](\d{1,2})/', $birth_value, $matches)) {
								$year_short = (int)$matches[1];
								$year = ($year_short >= 20) ? 1900 + $year_short : 2000 + $year_short;
								$formatted_birth = sprintf('%04d-%02d-%02d', $year, $matches[2], $matches[3]);
							}
							// 5. 엑셀 시리얼 날짜
							elseif (is_numeric($birth_value) && $birth_value > 10000 && $birth_value < 80000) {
								$unix_timestamp = ($birth_value - 25569) * 86400;
								$formatted_birth = date('Y-m-d', $unix_timestamp);
							}
							// 6. 한글 포함 (1990년12월25일)
							elseif (preg_match('/(\d{4})\s*년\s*(\d{1,2})\s*월\s*(\d{1,2})/', $birth_value, $matches)) {
								$formatted_birth = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
							}
							
							// 유효성 검사 후 저장 (1900-2030년까지 허용)
							if (!empty($formatted_birth)) {
								$parts = explode('-', $formatted_birth);
								if (count($parts) == 3) {
									$year = (int)$parts[0];
									$month = (int)$parts[1];
									$day = (int)$parts[2];
									if ($year >= 1900 && $year <= 2030 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && checkdate($month, $day, $year)) {
										$data[$field] = $formatted_birth;
									}
								}
							}
							break;
                        case 'phone':
							$phone_value = trim((string)$cell_value);
							
							// 빈 값이면 건너뛰기
							if (empty($phone_value)) break;
							
							// 1. 기본 정리 (숫자, +, -, (, ), 공백만 유지)
							$phone = preg_replace('/[^0-9\+\-\(\)\s]/', '', $phone_value);
							
							// 2. 한국 전화번호 패턴들 처리
							// 010-1234-5678, 02-123-4567, 031-123-4567 등
							if (preg_match('/^(\d{2,3})[-\s]?(\d{3,4})[-\s]?(\d{4})$/', $phone, $matches)) {
								$phone = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
							}
							// 3. 국제번호 (+82-10-1234-5678)
							elseif (preg_match('/^\+?82[-\s]?(\d{1,2})[-\s]?(\d{3,4})[-\s]?(\d{4})$/', $phone, $matches)) {
								$phone = '+82-' . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
							}
							// 4. 연속된 숫자 (01012345678)
							elseif (preg_match('/^(\d{3})(\d{3,4})(\d{4})$/', $phone, $matches)) {
								$phone = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
							}
							// 5. 11자리 숫자 (휴대폰)
							elseif (preg_match('/^(\d{3})(\d{4})(\d{4})$/', $phone, $matches)) {
								$phone = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
							}
							// 6. 해외 번호 (+1-234-567-8901)
							elseif (preg_match('/^\+\d{1,3}[-\s]?\d/', $phone)) {
								// 국제번호는 그대로 유지
								$phone = preg_replace('/\s+/', '-', $phone);
							}
							
							// 7. 최소 길이 체크 및 유효성 검사
							$phone_digits = preg_replace('/[^0-9]/', '', $phone);
							if (strlen($phone_digits) >= 8 && strlen($phone_digits) <= 15) {
								// 한국 번호 유효성 체크
								if (preg_match('/^(02|0[3-9]\d|01[016789])/', $phone_digits) || 
									preg_match('/^\+/', $phone) ||
									strlen($phone_digits) >= 10) {
									$data[$field] = $phone;
								}
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
                        case 'room_type': // "2인실", "2R1P" 등 다양한 형식 지원 필요
                            $data[$field] = normalizeRoomType($cell_value);
                            break;
                        case 'memo':
							
							
                            if (!empty($data[$field])) $data[$field] .= ' | ' . $cell_value;
                            else $data[$field] = $cell_value;
                            break;
                        default: // room_number 등 기타 필드
						
						    if (empty($data[$field])) $data[$field] = $cell_value;
							
                            break;
							
                    }
                    // 해당 헤더에 대한 필드 매칭 성공 시, 다른 패턴/필드 검사 중단하고 다음 헤더로
                    goto next_header_iteration; 
                }
            }
        }
        next_header_iteration:;
    }
    
    // 후처리: 한글/영문 이름 교차 검증 및 자동 채우기
    if (empty($data['korean_name']) && !empty($data['english_name'])) {
        if (preg_match('/([가-힣]{2,})/', $data['english_name'], $matches_korean_in_english)) { // 영문이름 필드에 한글이름이 있다면
            $data['korean_name'] = $matches_korean_in_english[1];
            // $data['english_name'] = trim(str_replace($matches_korean_in_english[1], '', $data['english_name'])); // 선택적: 한글 부분 제거
        }
    }
    if (empty($data['english_name']) && !empty($data['korean_name'])) {
         if (preg_match('/([A-Za-z\s]{3,})/', $data['korean_name'], $matches_english_in_korean)) { // 한글이름 필드에 영문이름이 있다면
            $data['english_name'] = strtoupper(trim($matches_english_in_korean[1]));
        }
    }
	// 여권번호 필드에 완료일이 들어간 경우 메모로 이동
	if (!empty($data['passport_number'])) {
		$passport_value = $data['passport_number'];
		
		// 간단한 문자열 검색으로 변경
		if (strpos($passport_value, '완료일') !== false || 
			strpos($passport_value, '만료일') !== false || 
			strpos($passport_value, '만기일') !== false ||
			strpos($passport_value, '발급일') !== false || 
			strpos($passport_value, '유효기간') !== false ||
			strpos($passport_value, 'expiry') !== false ||
			strpos($passport_value, 'expires') !== false) {
			
			// 메모로 이동
			if (!empty($data['memo'])) {
				$data['memo'] .= ' | ' . $passport_value;
			} else {
				$data['memo'] = $passport_value;
			}
			// 여권번호 필드는 비우기
			$data['passport_number'] = '';
		}
    }
	
	// 함수 return 직전에 추가
	// 메모에서 전화번호 찾아서 연락처로 이동
	if (!empty($data['memo']) && empty($data['phone'])) {
		$memo_text = $data['memo'];
		
		// 전화번호 패턴들
		$phone_patterns = array(
			'/(\d{2,3}[-\s]?\d{3,4}[-\s]?\d{4})/',           // 010-1234-5678, 02-123-4567
			'/(\+82[-\s]?\d{1,2}[-\s]?\d{3,4}[-\s]?\d{4})/', // +82-10-1234-5678
			'/(\d{10,11})/',                                   // 01012345678 (연속숫자)
			'/(\+\d{1,3}[-\s]?\d{6,14})/'                     // 국제번호
		);
		
		foreach ($phone_patterns as $pattern) {
			if (preg_match($pattern, $memo_text, $matches)) {
				$found_phone = $matches[1];
				
				// 전화번호 형식 정리
				$phone_digits = preg_replace('/[^0-9]/', '', $found_phone);
				
				// 유효한 전화번호인지 확인 (8-15자리)
				if (strlen($phone_digits) >= 8 && strlen($phone_digits) <= 15) {
					// 한국 전화번호 형식으로 정리
					if (preg_match('/^(\d{3})(\d{3,4})(\d{4})$/', $phone_digits, $phone_matches)) {
						$formatted_phone = $phone_matches[1] . '-' . $phone_matches[2] . '-' . $phone_matches[3];
					} elseif (preg_match('/^(\d{2,3})(\d{3,4})(\d{4})$/', $phone_digits, $phone_matches)) {
						$formatted_phone = $phone_matches[1] . '-' . $phone_matches[2] . '-' . $phone_matches[3];
					} else {
						$formatted_phone = $found_phone; // 원본 유지
					}
					
					// 연락처 필드로 이동
					$data['phone'] = $formatted_phone;
					
					// 메모에서 해당 전화번호 제거
					$data['memo'] = trim(str_replace($found_phone, '', $memo_text));
					
					// 메모가 비어있으면 완전히 비우기
					if (empty(trim($data['memo']))) {
						$data['memo'] = '';
					}
					
					break; // 첫 번째 전화번호만 처리
				}
			}
		}
	}

    // 대표예약자 필드는 사람 식별값만 허용 (날짜/연락처/이메일은 제거)
    $data['representative_name'] = normalizeRepresentativeName($data['representative_name'] ?? '');

return $data;

    
    // 대표 연락처/이메일이 여행자 정보에서 발견될 경우 (그룹 정보에 없을 시 활용 가능)
    // 이 로직은	 밖에서 그룹정보와 여행자정보를 종합할 때 고려하는 것이 좋음
		

    return $data;
}
/**
 * 상품코드로 상품 정보 조회 (표시여부 Y인 것만)
 */
/**
* 상품코드로 상품 정보 조회 (표시여부 Y인 것만)
*/
function getProductInfoByCode($product_code) {
   global $dbConn;
   
   if (empty($product_code)) {
       return array('success' => false, 'message' => '상품코드가 없습니다.');
   }
  // echo $product_code.'TEST';
   try {
       // 상품 정보 조회 - p_display = 'Y'인 것만 조회
       $sql_check = sprintf(
           "SELECT *  FROM product_master WHERE p_code = '%s'",
           mysqli_real_escape_string($dbConn,$product_code)
       );
//echo $sql_check."TETS";
       $result_check = mysqli_query($dbConn,$sql_check);
       if (!$result_check) {
           return array('success' => false, 'message' => '상품 조회 중 오류가 발생했습니다: ' . mysqli_error($dbConn));
       }

       $row1 = mysqli_fetch_assoc($result_check);
       mysqli_free_result($result_check);
     //  print_r($row1);
      
       return $row1;
   } catch (Exception $e) {
       error_log("상품 정보 조회 오류: " . $e->getMessage());
       return array('success' => false, 'message' => '조회 중 오류가 발생했습니다.');
   }
}

function getProductInfoByName($tour_name) {
   global $dbConn;

   if (empty($tour_name)) {
       return array();
   }

   try {
       $sql_check = sprintf(
           "SELECT * FROM product_master WHERE p_name = '%s' LIMIT 1",
           mysqli_real_escape_string($dbConn, $tour_name)
       );
       $result_check = mysqli_query($dbConn, $sql_check);
       if (!$result_check) {
           return array();
       }

       $row = mysqli_fetch_assoc($result_check);
       mysqli_free_result($result_check);
       return is_array($row) ? $row : array();
   } catch (Exception $e) {
       error_log("상품명 조회 오류: " . $e->getMessage());
       return array();
   }
}

function resolveProductInfoForSave($group_data) {
    $input_code = trim((string)($group_data['product_code'] ?? ''));
    $input_name = trim((string)($group_data['tour_name'] ?? ''));

    $product_row = array();
    if ($input_code !== '') {
        $product_row = getProductInfoByCode($input_code);
    }
    if ((empty($product_row) || empty($product_row['p_code'])) && $input_name !== '') {
        $product_row = getProductInfoByName($input_name);
    }

    $resolved_code = trim((string)($product_row['p_code'] ?? ''));
    $resolved_name = trim((string)($product_row['p_name'] ?? ''));

    if ($resolved_code === '' || $resolved_name === '') {
        return array(
            'success' => false,
            'message' => '상품코드/상품명을 DB에서 확인할 수 없습니다. 등록된 상품코드 또는 정확한 상품명을 입력해주세요.'
        );
    }

    return array(
        'success' => true,
        'p_code' => $resolved_code,
        'p_name' => $resolved_name
    );
}
// 객실타입 정규화 함수 추가
function normalizeRoomType($room_type_input) {
    if (empty($room_type_input)) return '2r1p'; // 기본값

    $type_str = strtolower(trim((string)$room_type_input));
    $type_str = preg_replace('/\s+/', '', $type_str); // 공백제거

    if (strpos($type_str, '1') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '싱글') !== false || strpos($type_str, 'single') !== false || $type_str === '1r1p')) return '1r1p';
    if (strpos($type_str, '2') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '트윈') !== false || strpos($type_str, '더블') !== false || strpos($type_str, 'twin') !== false || strpos($type_str, 'double') !== false || $type_str === '2r1p')) return '2r1p';
    if (strpos($type_str, '3') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '트리플') !== false || strpos($type_str, 'triple') !== false || $type_str === '3r1p')) return '3r1p';
    if (strpos($type_str, '4') !== false && (strpos($type_str, '인') !== false || strpos($type_str, '쿼드') !== false || strpos($type_str, 'quad') !== false || $type_str === '4r1p')) return '4r1p';
    
    return '2r1p'; // 매칭 안되면 기본값
}


/**
 * 날짜 추출 함수
 */
function extractDatesFromText($text) {
    $dates = array('start' => '', 'end' => '');
    if (empty($text)) return $dates;
    
    // YYYY-MM-DD ~ YYYY-MM-DD (가장 명확한 범위)
    $pattern_range = '/(\d{4}[\.\-\/]\s?\d{1,2}[\.\-\/]\s?\d{1,2})\s*(?:~|-|부터|에서)\s*(\d{4}[\.\-\/]\s?\d{1,2}[\.\-\/]\s?\d{1,2})/';
    if (preg_match($pattern_range, $text, $matches)) {
        $start_date = formatAdvancedDate($matches[1]);
        $end_date = formatAdvancedDate($matches[2]);
        if (!empty($start_date)) $dates['start'] = $start_date;
        if (!empty($end_date)) $dates['end'] = $end_date;
        if (!empty($start_date) && !empty($end_date)) return $dates; // 시작/종료 모두 찾으면 반환
    }
    
    // 개별 날짜들 찾기
    $all_single_dates = findAllDatesInText($text);
    if (!empty($all_single_dates)) {
        if (empty($dates['start'])) $dates['start'] = $all_single_dates[0];
        if (count($all_single_dates) > 1 && empty($dates['end'])) {
            // 시작일과 너무 멀지 않은 날짜를 종료일로 선택 (예: 30일 이내)
            $start_timestamp = strtotime($all_single_dates[0]);
            for ($i = 1; $i < count($all_single_dates); $i++) {
                $potential_end_timestamp = strtotime($all_single_dates[$i]);
                if ($potential_end_timestamp > $start_timestamp && ($potential_end_timestamp - $start_timestamp) / (60*60*24) <= 30) {
                    $dates['end'] = $all_single_dates[$i];
                    break;
                }
            }
            if(empty($dates['end']) && strtotime($all_single_dates[count($all_single_dates)-1]) > $start_timestamp) { // 가장 마지막 날짜를 종료일로
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
    
    // 다양한 날짜 형식 패턴 (정규식 개선)
    // YYYY-MM-DD, YYYY.MM.DD, YYYY/MM/DD
    // YY-MM-DD, YY.MM.DD, YY/MM/DD (두 자리 연도)
    // YYYYMMDD
    // MM/DD/YYYY, MM-DD-YYYY
    // DD/MM/YYYY, DD-MM-YYYY (일/월/연도 순서)
    // YYYY년 MM월 DD일
    $patterns = array(
        '/\b(\d{4})([\.\-\/])(\d{1,2})\2(\d{1,2})\b/', // YYYY.MM.DD
        '/\b(\d{2})([\.\-\/])(\d{1,2})\2(\d{1,2})\b/', // YY.MM.DD
        '/\b(\d{4})(\d{2})(\d{2})\b(?!\d)/',           // YYYYMMDD (뒤에 숫자가 더 오지 않는 경우)
        '/\b(\d{1,2})([\.\-\/])(\d{1,2})\2(\d{4})\b/', // MM.DD.YYYY
        // '/\b(\d{1,2})([\.\-\/])(\d{1,2})\2(\d{2})\b/', // MM.DD.YY - 모호성 높음, 주석 처리
        '/\b(\d{4})\s*년\s*(\d{1,2})\s*월\s*(\d{1,2})\s*일\b/u' // YYYY년 MM월 DD일
    );
    
    $raw_matches = array();
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $matches_for_pattern, PREG_SET_ORDER)) {
            foreach ($matches_for_pattern as $match_set) {
                $raw_matches[] = $match_set[0]; // 매칭된 원본 문자열 저장
            }
        }
    }
    
    // 고유한 매칭 결과에 대해서만 날짜 변환 시도
    $unique_raw_matches = array_unique($raw_matches);
    foreach($unique_raw_matches as $date_str_candidate) {
        $formatted = formatAdvancedDate($date_str_candidate);
        if (!empty($formatted) && !in_array($formatted, $found_dates)) {
            $found_dates[] = $formatted;
        }
    }
    
    sort($found_dates); // 시간 순 정렬
    return $found_dates;
}


/**
 * 개선된 날짜 포맷 함수
 */
function formatAdvancedDate($date_value) {
    if (empty($date_value) || (is_string($date_value) && strlen(trim($date_value)) === 0) ) return '';
    
    $date_str = trim((string) $date_value);
    
    // Excel 숫자 날짜 처리
    if (is_numeric($date_str) && $date_str > 20000 && $date_str < 60000) { // 1900년 기준 Excel 일련번호 범위 근사치 (예: 1954-10-10은 19915, 2023-10-10은 45208)
        $float_date = floatval($date_str);
        // Excel 1900년 시스템 (Windows 기본값)
        $unix_timestamp = ($float_date - 25569) * 86400;
        // Excel 1904년 시스템 (Mac 기본값) - 필요시 활성화
        // $unix_timestamp_1904 = ($float_date - 24107) * 86400;

        try {
            $dateTime = new DateTime("@" . floor($unix_timestamp), new DateTimeZone('UTC')); // floor 추가
            $year = (int)$dateTime->format('Y');
            // 생년월일 및 여행 기간 등을 고려한 유효 연도 범위 (예: 1920년 ~ 현재연도 + 5년)
            if ($year >= 1920 && $year <= ((int)date('Y') + 5)) { 
                return $dateTime->format('Y-m-d');
            }
        } catch (Exception $e) { /* 변환 실패 시 다음 로직으로 */ }
    }
    
    // 다양한 날짜 패턴 우선순위별 적용
    $date_patterns = [
        // YYYY-MM-DD 또는 YYYY.MM.DD 또는 YYYY/MM/DD (가장 일반적)
        ['regex' => '/^(\d{4})([\.\-\/])(\d{1,2})\2(\d{1,2})$/', 'y' => 1, 'm' => 3, 'd' => 4],
        // YYYYMMDD (숫자만 8자리)
        ['regex' => '/^(\d{4})(\d{2})(\d{2})$/', 'y' => 1, 'm' => 2, 'd' => 3, 'is_strict_digit' => true],
        // MM/DD/YYYY 또는 MM-DD-YYYY (월/일/연도)
        ['regex' => '/^(\d{1,2})([\.\-\/])(\d{1,2})\2(\d{4})$/', 'y' => 4, 'm' => 1, 'd' => 3],
        // DD/MM/YYYY 또는 DD-MM-YYYY (일/월/연도) - 위의 MM/DD/YYYY와 충돌 가능성, 주의 필요. 여기선 주석처리 또는 순서 조정
        // ['regex' => '/^(\d{1,2})([\.\-\/])(\d{1,2})\2(\d{4})$/', 'y' => 4, 'm' => 3, 'd' => 1], 
        // YYYY년 MM월 DD일 (한글)
        ['regex' => '/^(\d{4})\s*년\s*(\d{1,2})\s*월\s*(\d{1,2})\s*일$/u', 'y' => 1, 'm' => 2, 'd' => 3],
        // YY-MM-DD 또는 YY.MM.DD (두 자리 연도)
        ['regex' => '/^(\d{2})([\.\-\/])(\d{1,2})\2(\d{1,2})$/', 'y_short' => 1, 'm' => 3, 'd' => 4],
        // YYYY-MM 또는 YYYY.MM (연도-월까지만, 1일로 처리)
        ['regex' => '/^(\d{4})([\.\-\/])(\d{1,2})$/', 'y' => 1, 'm' => 3, 'd_fixed' => 1],
        // YYYY년 MM월 (한글, 연도-월까지만, 1일로 처리)
        ['regex' => '/^(\d{4})\s*년\s*(\d{1,2})\s*월$/u', 'y' => 1, 'm' => 2, 'd_fixed' => 1],
    ];

    foreach ($date_patterns as $pattern_info) {
        if (isset($pattern_info['is_strict_digit']) && $pattern_info['is_strict_digit'] && !ctype_digit($date_str)) {
            continue;
        }
        if (preg_match($pattern_info['regex'], $date_str, $matches)) {
            $year = 0; $month = 0; $day = 0;

            if (isset($pattern_info['y_short'])) {
                $year_short = (int)$matches[$pattern_info['y_short']];
                $current_century_short_year = (int)date('y');
                // 기준점을 현재 연도의 두자리수 + 10년 정도로 설정하여 미래 연도 우선 (예: 25년이면 2025년)
                // 과거 연도는 19xx로 처리 (예: 80년이면 1980년)
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
    
    // strtotime을 사용한 최후의 시도 (구분자 표준화 후)
    try {
        $normalized_date_str = str_replace(array('.', '/', '년', '월', '일'), array('-', '-', '', '', ''), $date_str);
        $normalized_date_str = preg_replace('/\s+/', '-', $normalized_date_str); // 공백도 '-'로
        $timestamp = strtotime($normalized_date_str);
        if ($timestamp !== false) {
            $year_from_strtotime = (int)date('Y', $timestamp);
            if ($year_from_strtotime >= 1920 && $year_from_strtotime <= ((int)date('Y') + 20)) {
                return date('Y-m-d', $timestamp);
            }
        }
    } catch(Exception $e){ /* 무시 */ }

    return ''; // 모든 형식 변환 실패
}


if (!function_exists('monthNameToNumber')) {
    function monthNameToNumber($month_name) {
        $month_name_clean = strtolower(substr(trim($month_name), 0, 3));
        $months = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 
            'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 
            'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12
        ];
        return $months[$month_name_clean] ?? false;
    }
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
            $current_score += 10; // 시작일과 종료일이 모두 명확히 있는 경우 높은 점수
            $start_time = strtotime($candidate['start']);
            $end_time = strtotime($candidate['end']);
            if ($start_time && $end_time && $end_time > $start_time) {
                $days_diff = ($end_time - $start_time) / (24 * 60 * 60);
                if ($days_diff >= 0 && $days_diff <= 365) { // 유효한 기간 범위
                    $current_score += 5;
                }
                 // 현재 날짜로부터 너무 멀리 떨어진 과거/미래가 아닌지 확인
                $current_year = (int)date('Y');
                $start_year = (int)date('Y', $start_time);
                if (abs($start_year - $current_year) <= 5) { // 현재로부터 5년 이내의 날짜에 가산점
                    $current_score += 3;
                }

            } else {
                 $current_score -=5; // 종료일이 시작일보다 빠르면 감점
            }
        } elseif ($has_start) {
            $current_score += 3; // 시작일만 있는 경우
        } elseif ($has_end) {
             $current_score += 1; // 종료일만 있는 경우 (덜 일반적)
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
    $cleaned_gender = preg_replace('/[\s\(\)\[\]\*\-\_]+/', '', $gender_str); // 공백 및 특수문자 제거
    $gender_lower = strtolower($cleaned_gender);

    if (in_array($gender_lower, ['m', 'male', 'man', 'boy', 'gentleman', '남', '남성', '남자', '1', '0'])) return 'male';
    if (in_array($gender_lower, ['f', 'female', 'woman', 'girl', 'lady', '여', '여성', '여자', '2'])) return 'female';

    // 더 관대한 매칭 (부분 문자열)
    if (preg_match('/(남|male|man)/ui', $gender_str)) return 'male';
    if (preg_match('/(여|female|woman)/ui', $gender_str)) return 'female';
    
    return ''; // 알 수 없음
}

/**
 * 기본 그룹 정보 설정
 */

function setDefaultGroupInfo(&$group_info, $filename, $travelers) {
    // $filename 에서 tour_name, product_code 추출 시도 (기존 로직 활용)
    if (empty($group_info['tour_name'])) {
        $group_info['tour_name'] = pathinfo($filename, PATHINFO_FILENAME); // 시트명 등이 포함될 수 있음
        // 괄호 안의 시트 정보 제거 시도
        $group_info['tour_name'] = preg_replace('/\s*\(Sheet:[^\)]+\)/i', '', $group_info['tour_name']);
    }
    //echo $filename;
     $extracted_code = extractProductCodeFromFilename($filename);
      // $group_info['product_code'] = $extracted_code ? $extracted_code : 'AUTO_' . date('ymd');
	  $arr_pro=getProductInfoByCode($extracted_code);
	  if (!empty($arr_pro['p_code'])) {
	      $group_info['product_code'] = $arr_pro['p_code'];
	  }
	  if (!empty($arr_pro['p_name'])) {
	      $group_info['tour_name'] = $arr_pro['p_name'];
	  }
    
    // 날짜가 비어있거나, 너무 기본값(오늘 등)으로 설정되어 있으면 더 나은 기본값 시도
    $today = date('Y-m-d');
    if (empty($group_info['start_date']) || $group_info['start_date'] === $today) {
        $group_info['start_date'] = date('Y-m-d', strtotime('+7 days'));
		
    }
    
    if (empty($group_info['end_date']) || $group_info['end_date'] === $today || strtotime($group_info['end_date']) <= strtotime($group_info['start_date'])) {
        $group_info['end_date'] = date('Y-m-d', strtotime($group_info['start_date'] . ' +7 days')); // 시작일 기준 +7일
    }
	
	if (empty($group_info['product_code'])) {
       $extracted_code = extractProductCodeFromFilename($filename);
      // $group_info['product_code'] = $extracted_code ? $extracted_code : 'AUTO_' . date('ymd');
	  $arr_pro=getProductInfoByCode($extracted_code);
	  if (!empty($arr_pro['p_code'])) {
	      $group_info['product_code'] = $arr_pro['p_code'];
	  }
	  if (!empty($arr_pro['p_name']) && empty($group_info['tour_name'])) {
	      $group_info['tour_name'] = $arr_pro['p_name'];
	  }
   }
    
    // 대표자 정보는 여행자 목록이 있을 때만, 그리고 비어있을 때만 설정
    if (empty($group_info['group_leader']) && !empty($travelers)) {
        $first_traveler_with_name = null;
        foreach($travelers as $t){
            if(!empty($t['korean_name']) || !empty($t['english_name'])){
                $first_traveler_with_name = $t;
                break;
            }
        }
        if($first_traveler_with_name){
            $group_info['group_leader'] = $first_traveler_with_name['korean_name'] ?: $first_traveler_with_name['english_name'];
            if(empty($group_info['group_phone']) && !empty($first_traveler_with_name['phone'])) $group_info['group_phone'] = $first_traveler_with_name['phone'];
            if(empty($group_info['group_email']) && !empty($first_traveler_with_name['email'])) $group_info['group_email'] = $first_traveler_with_name['email'];
        }
    }
}

/**
 * 중복 여행자 제거 (개선된 버전 - 빈 데이터 우선 제거)
 */
function removeDuplicateTravelers($travelers) {
    $unique_travelers_map = array();
    $final_unique_travelers = array();

    foreach ($travelers as $traveler) {
        $k_name = trim(strtolower($traveler['korean_name'] ?? ''));
        $e_name = trim(strtolower($traveler['english_name'] ?? ''));
        $representative_key = normalizeRepresentativeName($traveler['representative_name'] ?? ($traveler['group_leader'] ?? ''));
        
        // 이름 기반 키 생성
        $name_key = '';
        if (!empty($k_name)) $name_key = $k_name;
        elseif (!empty($e_name)) $name_key = $e_name;
        
        if (empty($name_key)) {
            // 이름이 없으면 일단 추가 (나중에 정리)
            $final_unique_travelers[] = $traveler;
            continue;
        }
        
        $dedupe_key = $name_key . '|' . strtolower($representative_key);

        // 동일 예약자이름 기준으로 중복 제거
        if (!isset($unique_travelers_map[$dedupe_key])) {
            // 첫 번째 데이터면 추가
            $unique_travelers_map[$dedupe_key] = $traveler;
        } else {
            // 중복 이름 발견 - 더 완성된 데이터를 선택
            $existing = $unique_travelers_map[$dedupe_key];
            $current = $traveler;
            
            // 데이터 완성도 점수 계산
            $existing_score = calculateDataCompleteness($existing);
            $current_score = calculateDataCompleteness($current);
            
            // 현재 데이터가 더 완성도가 높으면 교체
            if ($current_score > $existing_score) {
                $unique_travelers_map[$dedupe_key] = $current;
            }
            // 완성도가 같으면 기존 데이터 유지 (먼저 온 것 우선)
        }
    }
    
    return array_values($unique_travelers_map);
}

/**
 * 여행자 데이터 완성도 점수 계산
 */
function calculateDataCompleteness($traveler) {
    $score = 0;
    
    // 필수 필드들의 가중치
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
        $value = trim((string)($traveler[$field] ?? ''));
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
   // {CODE} 또는 [CODE] 형식
   if (preg_match('/[\[\{]([A-Za-z0-9_@#\/.+-]+)[\]\}]/', $filename, $matches)) {
	  //echo $matches[1];
       return trim($matches[1]);
   }
   // 일반적인 상품코드 패턴 (예: ABC-12345, P001)
   if (preg_match('/\b([A-Z]{2,5}[-_]?[0-9]{3,6})\b/i', $filename, $matches)) {
       return strtoupper(trim($matches[1]));
   }
   return '';
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
    
    if (!empty($data['phone']) && !preg_match('/^[0-9\+\-\s\(\)]{8,15}$/', $data['phone'])) { // 전화번호 패턴 약간 완화
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

function isDateLikeRepresentativeText($value) {
    $text = trim((string)$value);
    if ($text === '') {
        return false;
    }

    if (formatAdvancedDate($text) !== '') {
        return true;
    }

    if (preg_match('/^\d{4}\s*[\.\-\/년]\s*\d{1,2}\s*[\.\-\/월]?\s*\d{1,2}(?:\s*일)?(?:\s*[월화수목금토일](?:요일)?)?$/u', $text)) {
        return true;
    }

    if (preg_match('/^\d{1,2}\s*[\.\-\/]\s*\d{1,2}(?:\s*[\.\-\/]\s*\d{2,4})?(?:\s*[월화수목금토일](?:요일)?)?$/u', $text)) {
        return true;
    }

    $numeric_only = preg_replace('/[^0-9]/', '', $text);
    if ($numeric_only !== '' && is_numeric($text) && intval($text) >= 10000 && intval($text) <= 80000) {
        return true;
    }

    return false;
}

function normalizeRepresentativeName($name) {
    $normalized = trim((string)$name);
    $normalized = preg_replace('/\s+/u', ' ', $normalized);

    if ($normalized === '') {
        return '';
    }
    if (isDateLikeRepresentativeText($normalized)) {
        return '';
    }
    if (strpos($normalized, '@') !== false) {
        return '';
    }
    if (preg_match('/^[\d\+\-\s\(\)]+$/', $normalized)) {
        return '';
    }

    return $normalized;
}

function getTravelerReservationName($traveler) {
    $candidate = normalizeRepresentativeName($traveler['representative_name'] ?? '');
    if ($candidate === '') {
        $candidate = normalizeRepresentativeName($traveler['korean_name'] ?? '');
    }
    if ($candidate === '') {
        $candidate = normalizeRepresentativeName($traveler['english_name'] ?? '');
    }
    if ($candidate === '') {
        $candidate = '미지정';
    }
    return $candidate;
}

function saveReservationsByRepresentative($group_data, $travelers_data, $partner_id) {
    if (empty($travelers_data) || !is_array($travelers_data)) {
        return array('success' => false, 'message' => '저장할 여행자 데이터가 없습니다.');
    }

    $resolved_product = resolveProductInfoForSave($group_data);
    if (empty($resolved_product['success'])) {
        return array('success' => false, 'message' => $resolved_product['message'] ?? 'DB 상품 조회에 실패했습니다.');
    }
    $group_data['_resolved_product_code'] = $resolved_product['p_code'];
    $group_data['_resolved_tour_name'] = $resolved_product['p_name'];

    $grouped = array();

    foreach ($travelers_data as $traveler) {
        if (!is_array($traveler)) {
            continue;
        }

        $rep_name     = normalizeRepresentativeName($traveler['representative_name'] ?? '');
        $traveler_name = normalizeRepresentativeName($traveler['korean_name'] ?? '');
        if ($traveler_name === '') {
            $traveler_name = normalizeRepresentativeName($traveler['english_name'] ?? '');
        }

        $normalized_rep      = strtolower(str_replace(' ', '', $rep_name));
        $normalized_traveler = strtolower(str_replace(' ', '', $traveler_name));

        // 대표예약자이름과 예약자이름이 같으면(또는 대표예약자가 없으면) 동일 예약으로 간주
        if ($rep_name !== '' && $normalized_rep !== $normalized_traveler) {
            // 대표예약자가 별도로 지정된 경우 → 대표예약자 기준으로 그룹핑
            $reservation_name = $rep_name;
        } else {
            // 대표예약자 == 예약자이름이거나 대표예약자가 없는 경우 → 예약자 본인이 대표
            $reservation_name = $traveler_name !== '' ? $traveler_name : ($rep_name !== '' ? $rep_name : '미지정');
        }

        $group_key = strtolower(str_replace(' ', '', $reservation_name));

        if (!isset($grouped[$group_key])) {
            $grouped[$group_key] = array(
                'group_leader' => $reservation_name,
                'travelers' => array()
            );
        }

        // 대표예약자는 기존 값 우선, 비어있을 때만 예약자이름으로 보정
        $traveler['representative_name'] = $rep_name;
        if ($traveler['representative_name'] === '') {
            $traveler['representative_name'] = $reservation_name;
        }
        $grouped[$group_key]['travelers'][] = $traveler;
    }

    if (empty($grouped)) {
        return array('success' => false, 'message' => '유효한 여행자 데이터가 없습니다.');
    }

    $save_results = array();
    $save_failures = array();

    foreach ($grouped as $group_bundle) {
        $group_data_for_save = $group_data;
        $group_data_for_save['group_leader'] = $group_bundle['group_leader'];

        $save_result = saveGroupReservation($group_data_for_save, $group_bundle['travelers'], $partner_id);
        if (!empty($save_result['success'])) {
            $save_results[] = $save_result;
        } else {
            $save_failures[] = $save_result['message'] ?? '알 수 없는 오류';
        }
    }

    if (!empty($save_failures)) {
        $success_count = count($save_results);
        $total_count = count($grouped);
        return array(
            'success' => false,
            'message' => "예약자이름 기준 저장 중 일부 실패했습니다. 성공 {$success_count}/{$total_count}. 실패사유: " . $save_failures[0]
        );
    }

    $reserve_codes = array();
    $total_traveler_count = 0;
    foreach ($save_results as $result) {
        if (!empty($result['reserveCode'])) {
            $reserve_codes[] = $result['reserveCode'];
        }
        $total_traveler_count += intval($result['traveler_count'] ?? 0);
    }

    $saved_reservation_count = count($save_results);
    $first_code = $reserve_codes[0] ?? '';

    if ($saved_reservation_count === 1) {
        return $save_results[0];
    }

    return array(
        'success' => true,
        'message' => "예약자이름 기준으로 {$saved_reservation_count}건 예약 저장 완료 (" . implode(', ', $reserve_codes) . ")",
        'saved_reservation_count' => $saved_reservation_count,
        'traveler_count' => $total_traveler_count,
        'reserve_codes' => $reserve_codes,
        'reserveCode' => $first_code,
        'revNo' => $first_code
    );
}

/**
 * 그룹 예약 저장
 */
function saveGroupReservation($group_data, $travelers_data, $partner_id) {
    global $dbConn;
    
    // 디버깅: 입력 데이터 확인
    error_log("saveGroupReservation 시작 - Partner ID: " . $partner_id);
    error_log("Group data: " . print_r($group_data, true));
    error_log("Travelers count: " . count($travelers_data));
    
    if (!$dbConn) {
        error_log("DB 연결 객체 없음");
        return array('success' => false, 'message' => 'DB 연결 객체를 찾을 수 없습니다.');
    }
    
    if ($dbConn->connect_error) {
        error_log("DB 연결 오류: " . $dbConn->connect_error);
        return array('success' => false, 'message' => 'DB 연결 오류: ' . $dbConn->connect_error);
    }
    
    // 트랜잭션 시작
    $dbConn->autocommit(false);
    
    try {
        sleep(1); // 디버깅: 트랜잭션 시작 시점 확인용 딜레이       
        $grandNum = time() + mt_rand(1, 10000);
        $grand_revNo = 'TU' . date('ymdHis') . mt_rand(1, 9);
        sleep(1); // 디버깅: 예약번호 생성 시점 확인용 딜레이   
        $current_reserveCode = 'PUR' . date('ymdHis') . mt_rand(1, 1000);
        
        error_log("생성된 예약번호: " . $grand_revNo);
        
        $total_amount_from_group_data = isset($group_data['total_amount']) ? floatval($group_data['total_amount']) : 0.0;
        
        // 상품코드/상품명은 입력값이 아니라 DB 기준으로 확정
        $resolved_product_code = trim((string)($group_data['_resolved_product_code'] ?? ''));
        $resolved_tour_name = trim((string)($group_data['_resolved_tour_name'] ?? ''));

        if ($resolved_product_code === '' || $resolved_tour_name === '') {
            $resolved_product = resolveProductInfoForSave($group_data);
            if (!empty($resolved_product['success'])) {
                $resolved_product_code = $resolved_product['p_code'];
                $resolved_tour_name = $resolved_product['p_name'];
            } else {
                // DB에 없는 상품코드도 직접 사용 허용 (일괄등록 용)
                $resolved_product_code = trim((string)($group_data['product_code'] ?? ''));
                $resolved_tour_name = trim((string)($group_data['tour_name'] ?? ''));
                if ($resolved_product_code === '') {
                    throw new Exception('상품코드를 입력해주세요.');
                }
            }
        }

        // 변수 안전하게 설정 및 이스케이프
        $product_code = $dbConn->real_escape_string($resolved_product_code);
        $tour_name = $dbConn->real_escape_string($resolved_tour_name);
        $start_date = $dbConn->real_escape_string(isset($group_data['start_date']) ? $group_data['start_date'] : '');
        $end_date = $dbConn->real_escape_string(isset($group_data['end_date']) ? $group_data['end_date'] : '');
        $group_leader = $dbConn->real_escape_string(isset($group_data['group_leader']) ? $group_data['group_leader'] : '');
        $group_phone = $dbConn->real_escape_string(isset($group_data['group_phone']) ? $group_data['group_phone'] : '');
        $group_email = $dbConn->real_escape_string(isset($group_data['group_email']) ? $group_data['group_email'] : '');
        $group_memo_raw = isset($group_data['memo']) ? trim($group_data['memo']) : '';

        // 여행자 비고(e_memo)를 수집해 c_progress 에 포함
        $traveler_memo_lines = array();
        foreach ($travelers_data as $tv) {
            $tv_memo = trim((string)($tv['memo'] ?? ''));
            if ($tv_memo === '') continue;
            $tv_name = trim((string)($tv['korean_name'] ?? $tv['english_name'] ?? ''));
            $line = $tv_name !== '' ? "[{$tv_name}] {$tv_memo}" : $tv_memo;
            $traveler_memo_lines[] = $line;
        }
        $c_progress_raw = $group_memo_raw;
        if (!empty($traveler_memo_lines)) {
            $joined = implode(' / ', $traveler_memo_lines);
            $c_progress_raw = $c_progress_raw !== ''
                ? $c_progress_raw . ' | ' . $joined
                : $joined;
        }

        $group_memo = $dbConn->real_escape_string($c_progress_raw);
        $partner_id_escaped = $dbConn->real_escape_string($partner_id);

        // product_details_local에서 단일투어 일정 정보를 가져와 progress(pmemo) 생성
        $pmemo_raw = '';
        $sql_local = "SELECT * FROM product_details_local WHERE p_code = '{$product_code}' ORDER BY day, position, seq_no ASC";
        $rst_local = $dbConn->query($sql_local);
        if ($rst_local && $rst_local->num_rows > 0) {
            $s_date = explode("-", $start_date);
            while ($r_row = $rst_local->fetch_assoc()) {
                $local_product = getProductMaster($r_row['local_code']);
                $add_date = intval($r_row['day']) - 1;
                $local_start = date("Y-m-d", mktime(0, 0, 0, intval($s_date[1]), intval($s_date[2]) + $add_date, intval($s_date[0])));
                $pmemo_raw .= $local_start . "/" . $local_product['p_name'] . "/\n";
            }
        }
        $progress = $dbConn->real_escape_string($pmemo_raw);

        // 1. grand_reserve 저장
        $sql_grand = "INSERT INTO grand_reserve (
            grandNum, grand_revNo, revNo, tour_type, p_code, p_name, 
            tot_amt, revDate, stDate, wdate
        ) VALUES (
            {$grandNum}, 
            '{$grand_revNo}', 
            '{$current_reserveCode}', 
            '3', 
            '{$product_code}', 
            '{$tour_name}', 
            {$total_amount_from_group_data}, 
            NOW(), 
            '{$start_date}', 
            NOW()
        )";
        
        error_log("grand_reserve SQL: " . $sql_grand);
        
        if (!$dbConn->query($sql_grand)) {
            error_log("grand_reserve 실행 오류: " . $dbConn->error);
            throw new Exception("grand_reserve 실행 실패: " . $dbConn->error);
        }
        
        if ($dbConn->affected_rows <= 0) {
            error_log("grand_reserve affected_rows: " . $dbConn->affected_rows);
            throw new Exception("grand_reserve 저장 실패.");
        }
        
        error_log("grand_reserve 저장 성공");
        
        // 2. reserve_info 저장
        $reserveNum = $grandNum;
        $traveler_count = count($travelers_data);
        
        $sql_info = "INSERT INTO reserve_info (
            grandNum, grand_revNo, reserveNum, reserveCode, tour_type,
            pricet, p_code, p_name, parent, revDate, stDate, edDate,
            p_cnt, book_pri, book_phone, book_email,
            last_total, rev_status, userid, progress, c_progress, wdate
        ) VALUES (
            {$grandNum},
            '{$grand_revNo}',
            {$reserveNum},
            '{$current_reserveCode}',
            '3',
            '3',
            '{$product_code}',
            '{$tour_name}',
            'MAIN',
            NOW(),
            '{$start_date}',
            '{$end_date}',
            {$traveler_count},
            '{$group_leader}',
            '{$group_phone}',
            '{$group_email}',
            {$total_amount_from_group_data},
            'READY',
            '{$partner_id_escaped}',
            '{$progress}',
            '{$group_memo}',
            NOW()
        )";
        
        error_log("reserve_info SQL: " . $sql_info);
        
        if (!$dbConn->query($sql_info)) {
            error_log("reserve_info 실행 오류: " . $dbConn->error);
            throw new Exception("reserve_info 실행 실패: " . $dbConn->error);
        }
        
        if ($dbConn->affected_rows <= 0) {
            error_log("reserve_info affected_rows: " . $dbConn->affected_rows);
            throw new Exception("reserve_info 저장 실패.");
        }
        
        error_log("reserve_info 저장 성공");
        
        // 3. payment_history 초기값 저장
        $payment_base_rate = number_format((float)$total_amount_from_group_data, 2, '.', '');
        $payment_text = $dbConn->real_escape_string($payment_base_rate . ' USD');
        $pay_info_init = $dbConn->real_escape_string('결제대상');

        $sql_payment_history = "INSERT INTO payment_history (
            reservecode, pay_method, pay_info, payment, b_rate, rate_m,
            payment_status, register, wdate
        ) VALUES (
            '{$current_reserveCode}',
            'init',
            '{$pay_info_init}',
            '{$payment_text}',
            {$payment_base_rate},
            0.0000,
            'READY',
            '{$partner_id_escaped}',
            NOW()
        )";

        error_log("payment_history SQL: " . $sql_payment_history);

        if (!$dbConn->query($sql_payment_history)) {
            error_log("payment_history 실행 오류: " . $dbConn->error);
            throw new Exception("payment_history 실행 실패: " . $dbConn->error);
        }

        if ($dbConn->affected_rows <= 0) {
            error_log("payment_history affected_rows: " . $dbConn->affected_rows);
            throw new Exception("payment_history 저장 실패.");
        }

        error_log("payment_history 저장 성공");

        // 4. reserve_traveler 저장
        $traveler_success_count = 0;
        foreach ($travelers_data as $seq => $traveler) {
            $room_number = !empty($traveler['room_number']) ? intval($traveler['room_number']) : ($seq + 1);
            $birth_date_to_save = $dbConn->real_escape_string(isset($traveler['birth_date']) ? $traveler['birth_date'] : '');
            $gender_to_save = $dbConn->real_escape_string(isset($traveler['gender']) ? $traveler['gender'] : '');
            $room_type_to_save = $dbConn->real_escape_string(isset($traveler['room_type']) ? $traveler['room_type'] : '2r1p');
            
            $korean_name = $dbConn->real_escape_string(isset($traveler['korean_name']) ? $traveler['korean_name'] : '');
            $english_name = $dbConn->real_escape_string(isset($traveler['english_name']) ? $traveler['english_name'] : '');
            $phone = $dbConn->real_escape_string(isset($traveler['phone']) ? $traveler['phone'] : '');
            $email = $dbConn->real_escape_string(isset($traveler['email']) ? $traveler['email'] : '');
            $passport_number = $dbConn->real_escape_string(isset($traveler['passport_number']) ? $traveler['passport_number'] : '');
            $memo = $dbConn->real_escape_string(isset($traveler['memo']) ? $traveler['memo'] : '');
            $seqint = $seq + 1;
            
            $sql_traveler = "INSERT INTO reserve_traveler (
                grand_revNo, reserveCode, traveler_nm, traveler_enm, 
                traveler_phone, traveler_email, traveler_birth, traveler_room, 
                seqint, sextype, room_type, pass_num, e_memo, wdate
            ) VALUES (
                '{$grand_revNo}',
                '{$current_reserveCode}',
                '{$korean_name}',
                '{$english_name}',
                '{$phone}',
                '{$email}',
                '{$birth_date_to_save}',
                {$room_number},
                {$seqint},
                '{$gender_to_save}',
                '{$room_type_to_save}',
                '{$passport_number}',
                '{$memo}',
                NOW()
            )";
            
            error_log("reserve_traveler SQL (여행자 " . ($seq + 1) . "): " . $sql_traveler);
            
            if ($dbConn->query($sql_traveler)) {
                $traveler_success_count++;
            } else {
                error_log("reserve_traveler 실행 오류 (여행자 " . ($seq + 1) . "): " . $dbConn->error);
                // 여행자 개별 저장 실패는 경고만 하고 계속 진행
            }
        }
        
        error_log("reserve_traveler 저장 완료: " . $traveler_success_count . "/" . count($travelers_data));
        
        // 트랜잭션 커밋
        $dbConn->commit();
        $dbConn->autocommit(true);
        
        error_log("트랜잭션 커밋 성공");
        
        return array(
            'success' => true, 
            'message' => '그룹 예약이 성공적으로 등록되었습니다. 예약번호: ' . $current_reserveCode,
            'grand_revNo' => $grand_revNo,
            'grandNum' => $grandNum,
            'traveler_count' => $traveler_success_count,
            'reserveCode' => $current_reserveCode,
            'revNo' => $current_reserveCode
        );
        
    } catch (Exception $e) {
        // 트랜잭션 롤백
        $dbConn->rollback();
        $dbConn->autocommit(true);
        
        error_log("그룹 예약 저장 실패: " . $e->getMessage());
        return array('success' => false, 'message' => '예약 저장 중 오류가 발생했습니다: ' . $e->getMessage());
    }
}

/**
 * 화면 표시용 그룹핑 함수 - 예약자/한글성명/대표예약자 이름 기준
 * 이름이 같으면 하나의 그룹(예약)으로 인식
 */
function groupTravelersByRepresentativeForDisplay($travelers) {
    $groups   = array();
    $bg_colors = array(
        '#e8f4fd', // 연파랑
        '#fef9e7', // 연노랑
        '#eafaf1', // 연초록
        '#fdf2f8', // 연분홍
        '#fff8f0', // 연주황
        '#e8f8f5', // 민트
        '#f5eef8', // 연보라
        '#fdfefe', // 흰회색
    );
    $color_idx = 0;

    foreach ($travelers as $original_index => $traveler) {
        $rep_name      = normalizeRepresentativeName($traveler['representative_name'] ?? '');
        $korean_name   = trim($traveler['korean_name'] ?? '');
        $english_name  = trim($traveler['english_name'] ?? '');
        $traveler_name = $korean_name !== '' ? $korean_name : $english_name;

        // 대표예약자가 없으면 여행자 본인 이름 사용
        if ($rep_name === '') {
            $rep_name = $traveler_name !== '' ? $traveler_name : '미지정';
        }

        // 대표예약자 == 여행자 이름 이면 본인이 대표인 경우
        $rep_key      = strtolower(str_replace(' ', '', $rep_name));
        $traveler_key = strtolower(str_replace(' ', '', $traveler_name));

        // 그룹 키: 대표예약자 이름 기준
        $group_key = $rep_key;

        if (!isset($groups[$group_key])) {
            $groups[$group_key] = array(
                'leader'   => $rep_name,
                'bg_color' => $bg_colors[$color_idx % count($bg_colors)],
                'travelers' => array(),
            );
            $color_idx++;
        }

        // 원본 인덱스 보존
        $groups[$group_key]['travelers'][] = array_merge(
            $traveler,
            ['_original_index' => $original_index]
        );
    }

    return $groups;
}

// 화면 표시용 그룹 데이터 생성 (파일 업로드 후 $processed_data 기준)
$grouped_for_display = !empty($processed_data)
    ? groupTravelersByRepresentativeForDisplay($processed_data)
    : array();

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>푸른투어 - 스마트등록</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: #131176 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,.2);
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
            border-top: 5px solid #131176;
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
            border-color: #3993ba;
            background-color: #f8f9ff;
        }
        
        .upload-zone {
            text-align: center;
            padding: 40px 20px;
            cursor: pointer;
        }
        
        .upload-icon {
            font-size: 4rem;
            color: #3993ba;
            margin-bottom: 20px;
        }
        
        .file-input {
            display: none;
        }
        
        .btn-upload {
            background: linear-gradient(135deg, #3993ba, #3993ba);
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
            background: linear-gradient(135deg, #3993ba, #3993ba);
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
            border-top: 4px solid #3993ba;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .form-control:focus {
            border-color: #3993ba;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        /* 그룹 헤더 행 스타일 */
        .group-header-row td {
            font-size: 0.82rem;
            font-weight: 700;
            padding: 5px 12px !important;
            border-top: 2px solid #b0b8c1 !important;
        }
        .group-header-row .group-badge {
            font-size: 0.75rem;
            vertical-align: middle;
            margin-left: 6px;
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
            border-color: #3993ba;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand text-white" href="index.php">
                <i class="fas fa-arrow-left me-2"></i> 푸른투어 - 스마트등록
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">
                    <i class="fas fa-users"></i> 그룹 예약 등록
                </span>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-magic"></i> 스마트 그룹 예약 등록</h1>
            <p class="text-muted mb-0">어떤 형태의 파일이든 자동으로 분석하여 그룹 예약으로 변환합니다 (엑셀 다중 시트 지원)</p>
        </div>

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
            <h5><i class="fas fa-lightbulb"></i> 스마트 처리 기능</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>🤖 자동 인식 기능</h6>
                    <ul class="mb-0">
						<li>파일명에 반드시<font color=red> "XXX_{코드명}.XLSX"</font>으로바꿔준다!!!</li>
                        <li>비정형 데이터 자동 분석</li>
                        <li>엑셀 파일 내 모든 시트 데이터 통합 처리</li>
                        <li>병합된 셀 자동 처리 (기본)</li>
                        <li>다양한 날짜 형식 인식</li>
                        <li>그룹 정보 자동 추출</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>✏️ 실시간 편집 기능</h6>
                    <ul class="mb-0">
                        <li>여행자 추가/수정/삭제/복제</li>
                        <li>데이터 검증 및 자동 수정 제안</li>
                        <li>실시간 통계 업데이트</li>
                        <li>그룹 단위 저장</li>
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
					<h4>스마트 분석을 위한 파일 업로드</h4>
					<p class="text-muted mb-3">엑셀(xlsx, xls) 또는 CSV 파일을 업로드하세요. 엑셀은 모든 시트를 읽습니다.</p>
					
					<input type="file" id="fileInput" name="reservation_files[]" 
						   class="file-input" accept=".xlsx,.xls,.csv" multiple> 
					<button type="button" class="btn btn-upload" onclick="document.getElementById('fileInput').click()">
						<i class="fas fa-upload"></i> 파일 선택
					</button>
				</div>
                
                <div id="fileListContainer" style="margin-top: 20px;"></div>
                
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-upload" id="processBtn" style="display: none;">
                        <i class="fas fa-cogs"></i> 선택 파일 분석 시작
                    </button>
                </div>
            </form>
        </div>

        <div class="loading" id="loading">
            <div class="spinner mx-auto mb-3"></div>
            <p>AI가 파일을 분석하여 그룹 정보와 여행자 목록을 추출하고 있습니다. 잠시만 기다려주세요...</p>
        </div>

        <?php if (!empty($sheets_data)): ?>
        <!-- ══ 시트별 그룹예약 아코디언 ══════════════════════════════════════ -->
        <div class="mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-layer-group me-2 text-primary"></i>시트별 그룹 예약 정보
                <small class="text-muted ms-2">(시트마다 독립적으로 저장됩니다)</small>
            </h5>
            <div class="accordion" id="sheetsAccordion">
            <?php foreach ($sheets_data as $_si => $_sheet): ?>
            <?php
                $_sid      = 'sheet_' . $_si;
                $_gi       = $_sheet['group_info'] ?? array();
                $_tvl      = $_sheet['travelers'] ?? array();
                $_sname    = htmlspecialchars($_sheet['sheet_name'] ?? ('시트 ' . ($_si+1)), ENT_QUOTES, 'UTF-8');
                $_gi_js    = json_encode($_gi,  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
                $_tvl_js   = json_encode($_tvl, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
            ?>
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button <?php echo $_si > 0 ? 'collapsed' : ''; ?> fw-bold"
                            type="button" data-bs-toggle="collapse"
                            data-bs-target="#<?php echo $_sid; ?>">
                        <i class="fas fa-table me-2 text-success"></i>
                        <?php echo $_sname; ?>
                        <span class="badge bg-secondary ms-2"><?php echo count($_tvl); ?>명</span>
                    </button>
                </h2>
                <div id="<?php echo $_sid; ?>"
                     class="accordion-collapse collapse <?php echo $_si === 0 ? 'show' : ''; ?>"
                     data-bs-parent="#sheetsAccordion">
                    <div class="accordion-body">

                        <!-- 그룹 정보 폼 -->
                        <div class="row g-2 mb-3 sheet-group-form" id="form_<?php echo $_sid; ?>">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">상품코드</label>
                                <input type="text" class="form-control form-control-sm sg-product_code"
                                       value="<?php echo htmlspecialchars($_gi['product_code'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">상품명/투어명</label>
                                <input type="text" class="form-control form-control-sm sg-tour_name"
                                       value="<?php echo htmlspecialchars($_gi['tour_name'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">출발일</label>
                                <input type="date" class="form-control form-control-sm sg-start_date"
                                       value="<?php echo htmlspecialchars($_gi['start_date'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">도착일</label>
                                <input type="date" class="form-control form-control-sm sg-end_date"
                                       value="<?php echo htmlspecialchars($_gi['end_date'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">대표자</label>
                                <input type="text" class="form-control form-control-sm sg-group_leader"
                                       value="<?php echo htmlspecialchars($_gi['group_leader'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">연락처</label>
                                <input type="text" class="form-control form-control-sm sg-group_phone"
                                       value="<?php echo htmlspecialchars($_gi['group_phone'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">대표 이메일</label>
                                <input type="email" class="form-control form-control-sm sg-group_email"
                                       value="<?php echo htmlspecialchars($_gi['group_email'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">총 예약금액</label>
                                <input type="number" class="form-control form-control-sm sg-total_amount" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($_gi['total_amount'] ?? '0', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">메모</label>
                                <input type="text" class="form-control form-control-sm sg-memo"
                                       value="<?php echo htmlspecialchars($_gi['memo'] ?? '', ENT_QUOTES,'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-2">
                                <button type="button" class="btn btn-success btn-sm btn-save-sheet flex-fill"
                                        data-sid="<?php echo $_sid; ?>"
                                        data-gi='<?php echo htmlspecialchars($_gi_js, ENT_QUOTES, 'UTF-8'); ?>'
                                        data-tvl='<?php echo htmlspecialchars($_tvl_js, ENT_QUOTES, 'UTF-8'); ?>'>
                                    <i class="fas fa-save me-1"></i> DB 저장
                                </button>
                            </div>
                        </div>
                        <div class="sheet-save-result mb-2" id="result_<?php echo $_sid; ?>"></div>

                        <!-- 통계 -->
                        <div class="row text-center g-2 mb-2 small">
                            <div class="col-3"><div class="border rounded py-1"><strong id="totalCount_<?php echo $_sid; ?>">0</strong><div class="text-muted">총원</div></div></div>
                            <div class="col-3"><div class="border rounded py-1"><strong id="maleCount_<?php echo $_sid; ?>">0</strong><div class="text-muted">남성</div></div></div>
                            <div class="col-3"><div class="border rounded py-1"><strong id="femaleCount_<?php echo $_sid; ?>">0</strong><div class="text-muted">여성</div></div></div>
                            <div class="col-3"><div class="border rounded py-1"><strong id="passportCount_<?php echo $_sid; ?>">0</strong><div class="text-muted">여권</div></div></div>
                        </div>
                        <!-- 툴바 -->
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <div class="btn-group bulk-action-buttons" id="bulkActionButtons_<?php echo $_sid; ?>" style="display:none;">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDeleteTravelers('<?php echo $_sid; ?>')">
                                        <i class="fas fa-trash"></i> 삭제(<span id="selectedCount_<?php echo $_sid; ?>">0</span>)
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="bulkDuplicateTravelers('<?php echo $_sid; ?>')">
                                        <i class="fas fa-copy"></i> 복제
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="bulkEditGender('<?php echo $_sid; ?>')">
                                        <i class="fas fa-venus-mars"></i> 성별
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="bulkEditRoomType('<?php echo $_sid; ?>')">
                                        <i class="fas fa-bed"></i> 객실
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="saveSelectedAsNewGroup('<?php echo $_sid; ?>')">
                                        <i class="fas fa-plus-circle"></i> 선택저장
                                    </button>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openSheetTravelerModal('<?php echo $_sid; ?>')">
                                    <i class="fas fa-plus"></i> 여행자 추가
                                </button>
                            </div>
                            <input type="text" class="form-control form-control-sm" id="searchInput_<?php echo $_sid; ?>" style="max-width:220px;"
                                   placeholder="이름/여권번호 검색..."
                                   oninput="searchTravelersInSheet('<?php echo $_sid; ?>', this.value)">
                        </div>
                        <!-- 인라인 편집 여행자 테이블 -->
                        <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                        <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.8rem;">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th width="3%"><input type="checkbox" class="form-check-input" id="selectAll_<?php echo $_sid; ?>" onclick="toggleSelectAll('<?php echo $_sid; ?>')"></th>
                                    <th width="3%">#</th>
                                    <th width="10%">한글성명</th>
                                    <th width="10%">영문성명</th>
                                    <th width="10%">여권번호</th>
                                    <th width="9%">생년월일</th>
                                    <th width="6%">성별</th>
                                    <th width="9%">연락처</th>
                                    <th width="9%">대표예약자</th>
                                    <th width="7%">객실타입</th>
                                    <th width="6%">객실번호</th>
                                    <th width="9%">비고</th>
                                    <th width="6%">작업</th>
                                </tr>
                            </thead>
                            <tbody id="travelersTableBody_<?php echo $_sid; ?>">
                                <!-- JS가 렌더링 -->
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div><!-- /accordion -->
        </div>
        <?php endif; ?>

        <?php if (!empty($processed_data) && !empty($group_info) && empty($sheets_data)): ?>
        <form id="groupReservationForm"> <div class="group-info-card">
                <h4><i class="fas fa-cog"></i> 그룹 예약 정보 <small class="text-muted">(수정 가능)</small></h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-code"></i> 상품 코드</label>
                            <input type="text" name="product_code" class="form-control" 
                                   value="<?php echo htmlspecialchars($group_info['product_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-tag"></i> 상품명/투어명 *</label>
                            <input type="text" name="tour_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($group_info['tour_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-calendar-alt"></i> 출발일 *</label>
                                    <input type="date" name="start_date" class="form-control" 
                                           value="<?php echo htmlspecialchars($group_info['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-calendar-check"></i> 도착일</label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="<?php echo htmlspecialchars($group_info['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user-tie"></i> 그룹 대표자 *</label>
                            <input type="text" name="group_leader" class="form-control" 
                                   value="<?php echo htmlspecialchars($group_info['group_leader'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-phone"></i> 대표 연락처</label>
                            <input type="tel" name="group_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($group_info['group_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-envelope"></i> 대표 이메일</label>
                            <input type="email" name="group_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($group_info['group_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-dollar-sign"></i> 총 예약 금액</label>
                            <input type="number" name="total_amount" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($group_info['total_amount'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-sticky-note"></i> 그룹 메모</label>
                            <textarea name="memo" class="form-control" rows="2" placeholder="그룹 예약 관련 특이사항"><?php echo htmlspecialchars($group_info['memo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
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
						<h4><i class="fas fa-users"></i> 여행자 목록
    <span class="badge bg-primary" id="travelersBadge"><?php echo count($processed_data); ?>명</span>
    <?php if (!empty($grouped_for_display)): ?>
    <span class="badge bg-success ms-1"><?php echo count($grouped_for_display); ?>그룹</span>
    <?php endif; ?>
</h4>
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
						<input type="text" id="searchTravelers" class="search-input" placeholder="이름, 대표예약자, 여권번호로 검색...">
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
								<th width="10%">대표예약자</th>
								<th width="7%">객실타입</th>
								<th width="7%">객실번호</th>
								<th width="9%">비고</th>
								<th width="6%" class="text-center">작업</th>
							</tr>
						</thead>
						<tbody id="travelersTableBody">
							<?php
// ── 그룹별 배경색 및 헤더 감지용 매핑 ──────────────────────────
$_prev_grp_key  = null;
$_group_no_disp = 0;
$_traveler_grp_map = array();
foreach ($grouped_for_display as $_gk => $_gv) {
    foreach ($_gv['travelers'] as $_gt) {
        if (isset($_gt['_original_index'])) {
            $_traveler_grp_map[$_gt['_original_index']] = array(
                'key'      => $_gk,
                'leader'   => $_gv['leader'],
                'bg_color' => $_gv['bg_color'],
            );
        }
    }
}
foreach ($processed_data as $index => $traveler):
    $cur_grp       = $_traveler_grp_map[$index] ?? array('key'=>'','leader'=>'','bg_color'=>'#ffffff');
    $_cur_grp_key  = $cur_grp['key'];
    $_cur_bg       = htmlspecialchars($cur_grp['bg_color'], ENT_QUOTES, 'UTF-8');
    if ($_cur_grp_key !== $_prev_grp_key):
        $_prev_grp_key = $_cur_grp_key;
        $_group_no_disp++;
        $_grp_size = count($grouped_for_display[$_cur_grp_key]['travelers'] ?? array());
?>
<tr class="group-header-row"
    style="background-color:<?php echo $_cur_bg; ?>;"
    data-group-key="<?php echo htmlspecialchars($_cur_grp_key, ENT_QUOTES, 'UTF-8'); ?>">
    <td colspan="13">
        <i class="fas fa-users text-primary me-1"></i>
        <strong>예약그룹 <?php echo $_group_no_disp; ?></strong>
        &nbsp;&mdash;&nbsp;대표예약자:
        <span class="text-primary fw-bold"><?php echo htmlspecialchars($cur_grp['leader'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="badge bg-secondary group-badge"><?php echo $_grp_size; ?>명</span>
    </td>
</tr>
<?php endif; // end group header ?>
							<tr data-index="<?php echo $index; ?>"
    data-group-key="<?php echo htmlspecialchars($_cur_grp_key, ENT_QUOTES, 'UTF-8'); ?>"
    style="background-color:<?php echo $_cur_bg; ?>;">
								<td>
									<input type="checkbox" class="form-check-input traveler-checkbox" 
										   value="<?php echo $index; ?>" title="선택">
								</td>
								<td><?php echo $index + 1; ?></td>
								<td>
									<input type="text" class="form-control form-control-sm traveler-field" 
										   name="travelers[<?php echo $index; ?>][korean_name]" data-field="korean_name"
										   value="<?php echo htmlspecialchars($traveler['korean_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td>
									<input type="text" class="form-control form-control-sm traveler-field" 
										   name="travelers[<?php echo $index; ?>][english_name]" data-field="english_name" style="text-transform: uppercase;"
										   value="<?php echo htmlspecialchars($traveler['english_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td>
									<input type="text" class="form-control form-control-sm traveler-field" 
										   name="travelers[<?php echo $index; ?>][passport_number]" data-field="passport_number" style="text-transform: uppercase;"
										   value="<?php echo htmlspecialchars($traveler['passport_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td>
									<input type="date" class="form-control form-control-sm traveler-field" 
										   name="travelers[<?php echo $index; ?>][birth_date]" data-field="birth_date"
										   value="<?php echo htmlspecialchars($traveler['birth_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td>
									<select class="form-select form-select-sm traveler-field" 
											name="travelers[<?php echo $index; ?>][gender]" data-field="gender">
										<option value="">선택</option>
										<option value="male" <?php echo strtolower($traveler['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>남성</option>
										<option value="female" <?php echo strtolower($traveler['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>여성</option>
									</select>
								</td>
								<td>
									<input type="tel" class="form-control form-control-sm traveler-field" 
										   name="travelers[<?php echo $index; ?>][phone]" data-field="phone"
										   value="<?php echo htmlspecialchars($traveler['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
									<input type="hidden" class="form-control form-control-sm traveler-field"
										   name="travelers[<?php echo $index; ?>][email]" data-field="email"
										   value="<?php echo htmlspecialchars($traveler['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td>
									<input type="text" class="form-control form-control-sm traveler-field"
										   name="travelers[<?php echo $index; ?>][representative_name]" data-field="representative_name"
										   value="<?php echo htmlspecialchars((normalizeRepresentativeName($traveler['representative_name'] ?? '') ?: ($traveler['korean_name'] ?? ($traveler['english_name'] ?? ($group_info['group_leader'] ?? '')))), ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td>
									<select class="form-select form-select-sm traveler-field" 
											name="travelers[<?php echo $index; ?>][room_type]" data-field="room_type">
										<option value="1r1p" <?php echo ($traveler['room_type'] ?? '') === '1r1p' ? 'selected' : ''; ?>>1인실</option>
										<option value="2r1p" <?php echo ($traveler['room_type'] ?? '2r1p') === '2r1p' ? 'selected' : ''; ?>>2인실</option>
										<option value="3r1p" <?php echo ($traveler['room_type'] ?? '') === '3r1p' ? 'selected' : ''; ?>>3인실</option>
										<option value="4r1p" <?php echo ($traveler['room_type'] ?? '') === '4r1p' ? 'selected' : ''; ?>>4인실</option>
									</select>
								</td>
								<td>
									<input type="text" class="form-control form-control-sm traveler-field" 
										   name="travelers[<?php echo $index; ?>][room_number]" data-field="room_number"
										   value="<?php echo htmlspecialchars($traveler['room_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								</td>
								<td>
									<input type="text" class="form-control form-control-sm traveler-field" 
										   name="travelers[<?php echo $index; ?>][memo]" data-field="memo"
										   value="<?php echo htmlspecialchars($traveler['memo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
                        저장 시 예약자이름 기준으로 예약이 생성되며, 
                        <span id="finalTravelerCount"><?php echo count($processed_data); ?></span>명의 여행자가 포함됩니다.
                    </small>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="travelerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="travelerModalTitle">새 여행자 추가</h2>
                <button type="button" class="modal-close" id="travelerModalCloseBtn"> <i class="fas fa-times"></i>
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
            
            <div class="modal-footer mt-3"> <button type="button" class="btn btn-secondary" id="travelerModalCancelBtn">취소</button> <button type="button" class="btn btn-primary" id="travelerModalSaveBtn">저장</button> </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> <script>
        // 전역 변수
        let travelerData = <?php echo !empty($processed_data) ? json_encode($processed_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]'; ?>;
        let groupInfoData = <?php echo !empty($group_info) ? json_encode($group_info, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '{}'; ?>;
        // 그룹 정보 (대표예약자 기준 그룹핑 결과)
        let groupedDisplayData = <?php
            $js_groups = array();
            foreach ($grouped_for_display as $_gk => $_gv) {
                $js_groups[$_gk] = array(
                    'leader'   => $_gv['leader'],
                    'bg_color' => $_gv['bg_color'],
                    'count'    => count($_gv['travelers']),
                );
            }
            echo json_encode($js_groups, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        ?>;
        let initialSheetsData = <?php echo !empty($sheets_data) ? json_encode($sheets_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]'; ?>;
        let sheetStates = {};
        let travelerModalContext = { type: 'global', sid: null };
        // nextTravelerIndex는 이제 travelerData.length로 동적으로 계산
		
        $(document).ready(function() {
            initializeSystem();
        });

        function initializeSystem() {
			console.log('시스템 초기화 시작');
			setupFileUpload();
			setupTravelerManagement();
			setupBulkActions(); // 체크박스 일괄작업 기능
			setupSearch();
			setupDateValidation();
			setupProductAutoFill();
			updateAllStats();
            initializeSheetsModule();
            setupSheetSaveHandlers();
			autoFormatInputs();
			console.log('시스템 초기화 완료');
		}

        function setupFileUpload() {
            const fileInput = document.getElementById('fileInput');
            const uploadForm = document.getElementById('uploadForm');
            const processBtn = document.getElementById('processBtn');
            const fileListContainer = document.getElementById('fileListContainer');

            fileInput.addEventListener('change', handleFileSelection);
            uploadForm.addEventListener('submit', handleFormSubmit);
            setupDragDrop();

            function handleFileSelection(event) {
                const files = event.target.files;
                console.log('파일 선택됨:', files.length, '개');
                
                if (files.length > 0) {
                    displaySelectedFiles(files);
                    processBtn.style.display = 'inline-block';
                } else {
                    fileListContainer.innerHTML = '';
                    processBtn.style.display = 'none';
                }
            }

            function displaySelectedFiles(files) {
                let html = '<h6><i class="fas fa-file-alt"></i> 선택된 파일 목록:</h6><ul class="list-group">';
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file me-2"></i>${escapeHtml(file.name)}</span>
                                <span class="badge bg-secondary rounded-pill">${fileSizeMB} MB</span>
                             </li>`;
                }
                html += '</ul>';
                fileListContainer.innerHTML = html;
            }


            function handleFormSubmit(event) {
                console.log('폼 제출 시작');
                if (!fileInput.files || fileInput.files.length === 0) {
                    event.preventDefault();
                    showNotification('파일을 선택해주세요.', 'warning');
                    return false;
                }
                document.getElementById('loading').style.display = 'block';
                document.getElementById('loading').scrollIntoView({ behavior: 'smooth', block: 'center' });
                // 실제 폼 제출은 여기서 막지 않고 PHP가 처리하도록 둠.
            }
        }
        
        function setupDragDrop() {
            const uploadCard = document.getElementById('uploadCard');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadCard.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false); // 전체 페이지에 대한 기본 동작 방지
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadCard.addEventListener(eventName, () => highlight(uploadCard), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadCard.addEventListener(eventName, () => unhighlight(uploadCard), false);
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
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('fileInput');
            fileInput.files = files; 
            
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }


        function setupTravelerManagement() {
            $('#addTravelerBtn').on('click', () => openTravelerModal());
            $('#travelerModalCloseBtn, #travelerModalCancelBtn').on('click', closeTravelerModal);
            $('#travelerModalSaveBtn').on('click', saveTravelerFromModal);
            
            $('#travelerModal').on('click', function(e) {
                if (e.target === this) closeTravelerModal();
            });
            
            // 테이블 내 입력 필드 변경 시 travelerData 업데이트 (이벤트 위임 사용)
            $('#travelersTableBody').on('input change', '.traveler-field', function() {
                const rowIndex = $(this).closest('tr').data('index');
                const field = $(this).data('field');
                let value = $(this).val();

                if (field === 'english_name' || field === 'passport_number') {
                    value = value.toUpperCase();
                    $(this).val(value); // 입력 필드 값도 대문자로 변경
                }

                if (travelerData[rowIndex]) {
                    travelerData[rowIndex][field] = value;
                    updateAllStats(); // 변경 시마다 통계 업데이트
                    markUnsavedChanges(true);
                }
            });
            $('#groupReservationForm').on('submit', handleGroupSave); // AJAX 저장 처리
        }
		//추가됨
		// 전체 선택/해제 체크박스 처리
		function setupBulkActions() {
			// 전체 선택 체크박스 이벤트
			$('#selectAllTravelers').on('change', function() {
				const isChecked = $(this).prop('checked');
				$('.traveler-checkbox').prop('checked', isChecked);
				updateBulkActionButtonsVisibility();
			});

			// 개별 체크박스 변경 시
			$(document).on('change', '.traveler-checkbox', function() {
				updateSelectAllState();
				updateBulkActionButtonsVisibility();
			});
		}

		// 전체 선택 체크박스 상태 업데이트
		function updateSelectAllState() {
			const totalCheckboxes = $('.traveler-checkbox').length;
			const checkedCheckboxes = $('.traveler-checkbox:checked').length;
			
			$('#selectAllTravelers').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
			$('#selectAllTravelers').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
		}

		// 일괄작업 버튼 표시/숨김 및 선택 개수 업데이트
		function updateBulkActionButtonsVisibility(sid = null) {
            if (sid) {
                updateSheetBulkActionButtonsVisibility(sid);
                return;
            }

			const checkedCount = $('.traveler-checkbox:checked').length;
            $('#selectedCount').text(checkedCount);
			
			if (checkedCount > 0) {
				$('#bulkActionButtons').show();
			} else {
				$('#bulkActionButtons').hide();
			}
		}

		// 선택된 여행자 인덱스 가져오기
		function getSelectedTravelerIndexes(sid = null) {
            if (sid) {
                return getSheetSelectedTravelerIndexes(sid);
            }

            const checkedIndexes = [];
            $('.traveler-checkbox:checked').each(function() {
                const index = parseInt($(this).val(), 10);
                checkedIndexes.push(index);
            });
            return checkedIndexes;
		}

		// 선택된 여행자들 일괄 삭제
		function bulkDeleteTravelers(sid = null) {
            if (sid) {
                bulkDeleteTravelersInSheet(sid);
                return;
            }

			const checkedIndexes = getSelectedTravelerIndexes();

			if (checkedIndexes.length === 0) {
				showNotification('삭제할 여행자를 선택해주세요.', 'warning');
				return;
			}

			if (!confirm(`선택된 ${checkedIndexes.length}명의 여행자를 삭제하시겠습니까?`)) {
				return;
			}

			// 인덱스를 역순으로 정렬하여 삭제 (뒤에서부터 삭제해야 인덱스가 꼬이지 않음)
			checkedIndexes.sort((a, b) => b - a);
			
			checkedIndexes.forEach(index => {
				if (travelerData[index] !== undefined) {
					travelerData.splice(index, 1);
				}
			});

			renderAllTravelers();
			updateAllStats();
			showNotification(`${checkedIndexes.length}명의 여행자가 삭제되었습니다.`, 'success');
			markUnsavedChanges(true);
		}

		// 선택된 여행자들 일괄 복제
		function bulkDuplicateTravelers(sid = null) {
            if (sid) {
                bulkDuplicateTravelersInSheet(sid);
                return;
            }

			const checkedIndexes = getSelectedTravelerIndexes();

			if (checkedIndexes.length === 0) {
				showNotification('복제할 여행자를 선택해주세요.', 'warning');
				return;
			}

			if (!confirm(`선택된 ${checkedIndexes.length}명의 여행자를 복제하시겠습니까?`)) {
				return;
			}

			// 복제할 데이터들을 먼저 수집
			const duplicateData = [];
			checkedIndexes.forEach(index => {
				if (travelerData[index] !== undefined) {
					const original = travelerData[index];
					const duplicate = JSON.parse(JSON.stringify(original));
					
					if (duplicate.korean_name) duplicate.korean_name += ' (복사본)';
					else if (duplicate.english_name) duplicate.english_name += ' COPY';
					else duplicate.korean_name = '(복사본)';
					
					duplicateData.push(duplicate);
				}
			});

			// 복제된 데이터들을 travelerData에 추가
			travelerData = travelerData.concat(duplicateData);

			renderAllTravelers();
			updateAllStats();
			showNotification(`${duplicateData.length}명의 여행자가 복제되었습니다.`, 'success');
			markUnsavedChanges(true);
		}

		// 성별 일괄 수정
		function bulkEditGender(sid = null) {
            if (sid) {
                bulkEditGenderInSheet(sid);
                return;
            }

			const checkedIndexes = getSelectedTravelerIndexes();

			if (checkedIndexes.length === 0) {
				showNotification('성별을 수정할 여행자를 선택해주세요.', 'warning');
				return;
			}

			const genderOptions = [
				{ value: 'male', label: '남성' },
				{ value: 'female', label: '여성' },
				{ value: '', label: '선택안함' }
			];

			let optionsText = genderOptions.map((option, index) => `${index + 1}. ${option.label}`).join('\n');
			const choice = prompt(`선택된 ${checkedIndexes.length}명의 성별을 일괄 수정합니다.\n\n${optionsText}\n\n번호를 입력하세요:`);
			
			if (choice === null) return; // 취소

			const choiceIndex = parseInt(choice, 10) - 1;
			if (choiceIndex < 0 || choiceIndex >= genderOptions.length) {
				showNotification('올바른 번호를 입력해주세요.', 'warning');
				return;
			}

			const selectedGender = genderOptions[choiceIndex].value;

			checkedIndexes.forEach(index => {
				if (travelerData[index] !== undefined) {
					travelerData[index].gender = selectedGender;
				}
			});

			renderAllTravelers();
			updateAllStats();
			showNotification(`${checkedIndexes.length}명의 성별이 "${genderOptions[choiceIndex].label}"로 수정되었습니다.`, 'success');
			markUnsavedChanges(true);
		}

		// 객실타입 일괄 수정
		function bulkEditRoomType(sid = null) {
            if (sid) {
                bulkEditRoomTypeInSheet(sid);
                return;
            }

			const checkedIndexes = getSelectedTravelerIndexes();

			if (checkedIndexes.length === 0) {
				showNotification('객실타입을 수정할 여행자를 선택해주세요.', 'warning');
				return;
			}

			const roomTypeOptions = [
				{ value: '1r1p', label: '1인실' },
				{ value: '2r1p', label: '2인실' },
				{ value: '3r1p', label: '3인실' },
				{ value: '4r1p', label: '4인실' }
			];

			let optionsText = roomTypeOptions.map((option, index) => `${index + 1}. ${option.label}`).join('\n');
			const choice = prompt(`선택된 ${checkedIndexes.length}명의 객실타입을 일괄 수정합니다.\n\n${optionsText}\n\n번호를 입력하세요:`);
			
			if (choice === null) return; // 취소

			const choiceIndex = parseInt(choice, 10) - 1;
			if (choiceIndex < 0 || choiceIndex >= roomTypeOptions.length) {
				showNotification('올바른 번호를 입력해주세요.', 'warning');
				return;
			}

			const selectedRoomType = roomTypeOptions[choiceIndex].value;

			checkedIndexes.forEach(index => {
				if (travelerData[index] !== undefined) {
					travelerData[index].room_type = selectedRoomType;
				}
			});

			renderAllTravelers();
			updateAllStats();
			showNotification(`${checkedIndexes.length}명의 객실타입이 "${roomTypeOptions[choiceIndex].label}"로 수정되었습니다.`, 'success');
			markUnsavedChanges(true);
		}
		
		//추가끝
        function openTravelerModal(editIndex = -1, context = null) {
            travelerModalContext = context && context.type ? context : { type: 'global', sid: null };
            const sourceData = travelerModalContext.type === 'sheet'
                ? ((sheetStates[travelerModalContext.sid] && sheetStates[travelerModalContext.sid].travelers) ? sheetStates[travelerModalContext.sid].travelers : [])
                : travelerData;
            const isEdit = editIndex >= 0 && sourceData[editIndex] !== undefined;
            $('#travelerModalTitle').text(isEdit ? '여행자 정보 수정' : '새 여행자 추가');
            $('#editTravelerIndex').val(editIndex);
            
            if (isEdit) {
                fillTravelerForm(sourceData[editIndex]);
            } else {
                clearTravelerForm();
            }
            $('#travelerModal').show();
            $('#modalKoreanName').focus(); // 모달 열릴 때 첫 필드에 포커스
        }

        function openSheetTravelerModal(sid, editIndex = -1) {
            if (!sheetStates[sid]) {
                showNotification('시트 정보를 찾을 수 없습니다.', 'warning');
                return;
            }
            openTravelerModal(editIndex, { type: 'sheet', sid: sid });
        }

        function closeTravelerModal() {
            $('#travelerModal').hide();
            clearTravelerForm();
            travelerModalContext = { type: 'global', sid: null };
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
            $('#modalRoomType').val('2r1p'); // 기본값 설정
        }

        function saveTravelerFromModal() {
            const editIndex = parseInt($('#editTravelerIndex').val(), 10);
            const isEdit = editIndex >= 0;
            const modalKoreanName = $('#modalKoreanName').val().trim();
            const modalEnglishName = $('#modalEnglishName').val().trim().toUpperCase();
            let targetData = travelerData;
            let groupLeaderName = $('input[name="group_leader"]').val().trim();

            if (travelerModalContext.type === 'sheet') {
                if (!sheetStates[travelerModalContext.sid]) {
                    showNotification('시트 정보를 찾을 수 없습니다.', 'error');
                    return;
                }
                targetData = sheetStates[travelerModalContext.sid].travelers;
                groupLeaderName = $(`#form_${travelerModalContext.sid} .sg-group_leader`).val().trim();
            }

            const preservedRepresentativeName = (isEdit && targetData[editIndex] && sanitizeRepresentativeName(targetData[editIndex].representative_name))
                ? sanitizeRepresentativeName(targetData[editIndex].representative_name)
                : (modalKoreanName || modalEnglishName || groupLeaderName || '');
            
            const newTravelerData = {
                korean_name: modalKoreanName,
                english_name: modalEnglishName,
                passport_number: $('#modalPassportNumber').val().trim().toUpperCase(),
                birth_date: $('#modalBirthDate').val(),
                gender: $('#modalGender').val(),
                phone: $('#modalPhone').val().trim(),
                email: $('#modalEmail').val().trim(),
                room_type: $('#modalRoomType').val(),
                room_number: $('#modalRoomNumber').val().trim(),
                memo: $('#modalMemo').val().trim(),
                representative_name: preservedRepresentativeName
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
                if(targetData[editIndex]) { // 해당 인덱스 데이터가 있는지 확인
                    targetData[editIndex] = newTravelerData;
                    showNotification('여행자 정보가 수정되었습니다.', 'success');
                } else { // 편집하려던 데이터가 사라진 경우 (드문 케이스)
                    targetData.push(newTravelerData); // 새 데이터로 추가
                     showNotification('수정 대상 정보를 찾을 수 없어 새 여행자로 추가했습니다.', 'info');
                }
            } else {
                targetData.push(newTravelerData);
                showNotification('새 여행자가 추가되었습니다.', 'success');
            }

            if (travelerModalContext.type === 'sheet') {
                const sid = travelerModalContext.sid;
                renderSheetTravelers(sid);
                updateSheetStats(sid);
                updateSheetBulkActionButtonsVisibility(sid);
                markUnsavedChanges(true);
            } else {
                renderAllTravelers(); // 전체 테이블 다시 그림
                updateAllStats();
                markUnsavedChanges(true);
            }

            closeTravelerModal();
        }

        function setupDateValidation() {
            $(document).on('change', 'input[name="start_date"], input[name="end_date"]', function() {
                const startDate = $('input[name="start_date"]').val();
                const endDate = $('input[name="end_date"]').val();
                if (startDate && endDate && startDate > endDate) {
                    showNotification('도착일은 출발일보다 빠를 수 없습니다.', 'warning');
                    $('input[name="end_date"]').val(startDate);
                }
            });

            $(document).on('change', '.sg-start_date, .sg-end_date', function() {
                const $form = $(this).closest('.sheet-group-form');
                const startDate = $form.find('.sg-start_date').val();
                const endDate = $form.find('.sg-end_date').val();
                if (startDate && endDate && startDate > endDate) {
                    showNotification('도착일은 출발일보다 빠를 수 없습니다.', 'warning');
                    $form.find('.sg-end_date').val(startDate);
                }
            });
        }

        function setupProductAutoFill() {
            const cache = {};

            function calculateEndDateByPday(startDate, pDay) {
                const days = parseInt(pDay, 10);
                if (!startDate || Number.isNaN(days) || days <= 0) return '';

                const offset = Math.max(0, days - 1);
                const parts = String(startDate).split('-').map(v => parseInt(v, 10));
                if (parts.length !== 3 || parts.some(Number.isNaN)) return '';

                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                d.setDate(d.getDate() + offset);

                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            function applyEndDateByPday($startInput, $endInput, pDay) {
                if (!$startInput || !$startInput.length || !$endInput || !$endInput.length) return;
                const startDate = String($startInput.val() || '').trim();
                const endDate = calculateEndDateByPday(startDate, pDay);
                if (endDate !== '') {
                    $endInput.val(endDate);
                }
            }

            async function fillByCode($codeInput, $nameInput, $startInput, $endInput) {
                if (!$codeInput || !$codeInput.length || !$nameInput || !$nameInput.length) return;
                const raw = String($codeInput.val() || '').trim();
                if (raw === '') return;

                const code = raw.toUpperCase();
                $codeInput.val(code);

                if (cache[code]) {
                    $codeInput.val(cache[code].p_code || code);
                    $nameInput.val(cache[code].p_name || $nameInput.val());
                    $codeInput.data('p-day', parseInt(cache[code].p_day, 10) || 0);
                    applyEndDateByPday($startInput, $endInput, cache[code].p_day);
                    markUnsavedChanges(true);
                    return;
                }

                try {
                    const response = await $.ajax({
                        url: window.location.pathname,
                        type: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        dataType: 'json',
                        data: {
                            action: 'lookup_product_by_code',
                            product_code: code
                        }
                    });

                    if (response && response.success && response.data) {
                        cache[code] = response.data;
                        $codeInput.val(response.data.p_code || code);
                        $nameInput.val(response.data.p_name || '');
                        $codeInput.data('p-day', parseInt(response.data.p_day, 10) || 0);
                        applyEndDateByPday($startInput, $endInput, response.data.p_day);
                        markUnsavedChanges(true);
                    } else {
                        $codeInput.data('p-day', 0);
                    }
                } catch (e) {
                    $codeInput.data('p-day', 0);
                    console.error('상품코드 조회 실패:', e);
                }
            }

            $(document).on('blur change', 'input[name="product_code"]', function() {
                fillByCode(
                    $(this),
                    $('input[name="tour_name"]'),
                    $('input[name="start_date"]'),
                    $('input[name="end_date"]')
                );
            });

            $(document).on('blur change', '.sg-product_code', function() {
                const $form = $(this).closest('.sheet-group-form');
                fillByCode(
                    $(this),
                    $form.find('.sg-tour_name'),
                    $form.find('.sg-start_date'),
                    $form.find('.sg-end_date')
                );
            });

            $(document).on('change', 'input[name="start_date"]', function() {
                const $codeInput = $('input[name="product_code"]');
                const pDay = parseInt($codeInput.data('p-day'), 10) || 0;
                applyEndDateByPday($(this), $('input[name="end_date"]'), pDay);
            });

            $(document).on('change', '.sg-start_date', function() {
                const $form = $(this).closest('.sheet-group-form');
                const pDay = parseInt($form.find('.sg-product_code').data('p-day'), 10) || 0;
                applyEndDateByPday($form.find('.sg-start_date'), $form.find('.sg-end_date'), pDay);
            });

            const $globalCode = $('input[name="product_code"]');
            const $globalName = $('input[name="tour_name"]');
            if ($globalCode.length && $globalName.length && String($globalCode.val() || '').trim() !== '') {
                fillByCode($globalCode, $globalName, $('input[name="start_date"]'), $('input[name="end_date"]'));
            }

            $('.sheet-group-form').each(function() {
                const $form = $(this);
                const $codeInput = $form.find('.sg-product_code');
                const $nameInput = $form.find('.sg-tour_name');
                if (String($codeInput.val() || '').trim() !== '') {
                    fillByCode($codeInput, $nameInput, $form.find('.sg-start_date'), $form.find('.sg-end_date'));
                }
            });
        }

        function initializeSheetsModule() {
            if (!Array.isArray(initialSheetsData) || initialSheetsData.length === 0) {
                return;
            }

            sheetStates = {};
            initialSheetsData.forEach((sheet, index) => {
                const sid = 'sheet_' + index;
                const travelers = Array.isArray(sheet.travelers) ? sheet.travelers.map(t => ({ ...(t || {}) })) : [];
                const groupInfo = sheet.group_info ? { ...sheet.group_info } : {};
                sheetStates[sid] = {
                    sheetName: sheet.sheet_name || ('시트 ' + (index + 1)),
                    groupInfo: groupInfo,
                    travelers: travelers
                };
                renderSheetTravelers(sid);
                updateSheetStats(sid);
                updateSheetBulkActionButtonsVisibility(sid);
            });

            $(document).on('input change', '[id^="travelersTableBody_sheet_"] .sheet-traveler-field', function() {
                const $row = $(this).closest('tr');
                const sid = String($row.data('sid') || '');
                const rowIndex = parseInt($row.data('index'), 10);
                const field = String($(this).data('field') || '');
                let value = $(this).val();

                if (!sid || !sheetStates[sid] || !sheetStates[sid].travelers[rowIndex] || !field) {
                    return;
                }

                if (field === 'english_name' || field === 'passport_number') {
                    value = String(value || '').toUpperCase();
                    $(this).val(value);
                }

                sheetStates[sid].travelers[rowIndex][field] = value;
                if (field === 'representative_name') {
                    sheetStates[sid].travelers[rowIndex][field] = sanitizeRepresentativeName(value);
                    $(this).val(sheetStates[sid].travelers[rowIndex][field]);
                }

                updateSheetStats(sid);
                markUnsavedChanges(true);
            });

            $(document).on('change', '.sheet-traveler-checkbox', function() {
                const sid = String($(this).data('sid') || '');
                if (!sid) return;
                updateSheetSelectAllState(sid);
                updateSheetBulkActionButtonsVisibility(sid);
            });
        }

        function collectSheetGroupDataFromForm(sid) {
            const $form = $('#form_' + sid);
            return {
                tour_name: $form.find('.sg-tour_name').val().trim(),
                product_code: $form.find('.sg-product_code').val().trim(),
                start_date: $form.find('.sg-start_date').val(),
                end_date: $form.find('.sg-end_date').val(),
                group_leader: $form.find('.sg-group_leader').val().trim(),
                group_phone: $form.find('.sg-group_phone').val().trim(),
                group_email: $form.find('.sg-group_email').val().trim(),
                total_amount: parseFloat($form.find('.sg-total_amount').val()) || 0,
                memo: $form.find('.sg-memo').val().trim()
            };
        }

        function renderSheetTravelers(sid) {
            const state = sheetStates[sid];
            const $tbody = $('#travelersTableBody_' + sid);
            if (!state || !$tbody.length) return;

            $tbody.empty();
            state.travelers.forEach((traveler, index) => {
                $tbody.append(createSheetTravelerRowHtml(sid, traveler, index));
            });

            updateSheetSelectAllState(sid);
            const currentKeyword = ($('#searchInput_' + sid).val() || '').trim();
            if (currentKeyword !== '') {
                searchTravelersInSheet(sid, currentKeyword);
            }
        }

        function createSheetTravelerRowHtml(sid, traveler, index) {
            const trData = traveler || {};
            const groupLeader = ($('#form_' + sid + ' .sg-group_leader').val() || '').trim();
            const repName = sanitizeRepresentativeName(trData.representative_name || '') || trData.korean_name || trData.english_name || groupLeader || '';

            return `
                <tr data-index="${index}" data-sid="${escapeHtml(sid)}">
                    <td><input type="checkbox" class="form-check-input sheet-traveler-checkbox" data-sid="${escapeHtml(sid)}" value="${index}"></td>
                    <td>${index + 1}</td>
                    <td><input type="text" class="form-control form-control-sm sheet-traveler-field" data-field="korean_name" value="${escapeHtml(trData.korean_name || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm sheet-traveler-field" data-field="english_name" style="text-transform: uppercase;" value="${escapeHtml(trData.english_name || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm sheet-traveler-field" data-field="passport_number" style="text-transform: uppercase;" value="${escapeHtml(trData.passport_number || '')}"></td>
                    <td><input type="date" class="form-control form-control-sm sheet-traveler-field" data-field="birth_date" value="${escapeHtml(trData.birth_date || '')}"></td>
                    <td>
                        <select class="form-select form-select-sm sheet-traveler-field" data-field="gender">
                            <option value="">선택</option>
                            <option value="male" ${strtolower(trData.gender || '') === 'male' ? 'selected' : ''}>남성</option>
                            <option value="female" ${strtolower(trData.gender || '') === 'female' ? 'selected' : ''}>여성</option>
                        </select>
                    </td>
                    <td><input type="tel" class="form-control form-control-sm sheet-traveler-field" data-field="phone" value="${escapeHtml(trData.phone || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm sheet-traveler-field" data-field="representative_name" value="${escapeHtml(repName)}"></td>
                    <td>
                        <select class="form-select form-select-sm sheet-traveler-field" data-field="room_type">
                            <option value="1r1p" ${(trData.room_type || '2r1p') === '1r1p' ? 'selected' : ''}>1인실</option>
                            <option value="2r1p" ${(trData.room_type || '2r1p') === '2r1p' ? 'selected' : ''}>2인실</option>
                            <option value="3r1p" ${(trData.room_type || '2r1p') === '3r1p' ? 'selected' : ''}>3인실</option>
                            <option value="4r1p" ${(trData.room_type || '2r1p') === '4r1p' ? 'selected' : ''}>4인실</option>
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm sheet-traveler-field" data-field="room_number" value="${escapeHtml(trData.room_number || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm sheet-traveler-field" data-field="memo" value="${escapeHtml(trData.memo || '')}"></td>
                    <td class="text-center">
                        <div class="table-actions">
                            <button type="button" class="btn-action btn-edit" onclick="editSheetTraveler('${escapeHtml(sid)}', ${index})" title="수정"><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn-action btn-duplicate" onclick="duplicateSheetTraveler('${escapeHtml(sid)}', ${index})" title="복제"><i class="fas fa-copy"></i></button>
                            <button type="button" class="btn-action btn-delete" onclick="deleteSheetTraveler('${escapeHtml(sid)}', ${index})" title="삭제"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
        }

        function updateSheetStats(sid) {
            const state = sheetStates[sid];
            if (!state) return;

            let maleCount = 0;
            let femaleCount = 0;
            let passportCount = 0;
            const totalCount = state.travelers.length;

            state.travelers.forEach(traveler => {
                if (!traveler) return;
                if (strtolower(traveler.gender) === 'male') maleCount++;
                if (strtolower(traveler.gender) === 'female') femaleCount++;
                if ((traveler.passport_number || '').trim() !== '') passportCount++;
            });

            $('#totalCount_' + sid).text(totalCount);
            $('#maleCount_' + sid).text(maleCount);
            $('#femaleCount_' + sid).text(femaleCount);
            $('#passportCount_' + sid).text(passportCount);
        }

        function getSheetSelectedTravelerIndexes(sid) {
            const indexes = [];
            $('#travelersTableBody_' + sid + ' .sheet-traveler-checkbox:checked').each(function() {
                const idx = parseInt($(this).val(), 10);
                if (!Number.isNaN(idx)) indexes.push(idx);
            });
            return indexes;
        }

        function updateSheetSelectAllState(sid) {
            const $all = $('#travelersTableBody_' + sid + ' .sheet-traveler-checkbox');
            const total = $all.length;
            const checked = $all.filter(':checked').length;
            $('#selectAll_' + sid).prop('indeterminate', checked > 0 && checked < total);
            $('#selectAll_' + sid).prop('checked', total > 0 && checked === total);
        }

        function updateSheetBulkActionButtonsVisibility(sid) {
            const checkedCount = getSheetSelectedTravelerIndexes(sid).length;
            $('#selectedCount_' + sid).text(checkedCount);
            if (checkedCount > 0) {
                $('#bulkActionButtons_' + sid).show();
            } else {
                $('#bulkActionButtons_' + sid).hide();
            }
        }

        function toggleSelectAll(sid) {
            const isChecked = $('#selectAll_' + sid).prop('checked');
            $('#travelersTableBody_' + sid + ' .sheet-traveler-checkbox').prop('checked', isChecked);
            updateSheetSelectAllState(sid);
            updateSheetBulkActionButtonsVisibility(sid);
        }

        function searchTravelersInSheet(sid, keyword) {
            const searchTerm = String(keyword || '').toLowerCase().trim();
            $('#travelersTableBody_' + sid + ' tr').each(function() {
                const $row = $(this);
                if (searchTerm === '') {
                    $row.show();
                    return;
                }

                const values = [
                    $row.find('input[data-field="korean_name"]').val() || '',
                    $row.find('input[data-field="english_name"]').val() || '',
                    $row.find('input[data-field="passport_number"]').val() || '',
                    $row.find('input[data-field="phone"]').val() || '',
                    $row.find('input[data-field="representative_name"]').val() || ''
                ].map(v => String(v).toLowerCase());

                const isMatch = values.some(v => v.includes(searchTerm));
                $row.toggle(isMatch);
            });
        }

        function editSheetTraveler(sid, index) {
            if (!sheetStates[sid] || !sheetStates[sid].travelers[index]) {
                showNotification('수정할 여행자 정보를 찾을 수 없습니다.', 'warning');
                return;
            }
            openSheetTravelerModal(sid, index);
        }

        function deleteSheetTraveler(sid, index) {
            if (!sheetStates[sid] || !sheetStates[sid].travelers[index]) {
                showNotification('삭제할 여행자 정보를 찾을 수 없습니다.', 'warning');
                return;
            }
            const traveler = sheetStates[sid].travelers[index];
            const displayName = traveler.korean_name || traveler.english_name || `여행자 #${index + 1}`;
            if (!confirm(`"${displayName}" 여행자를 삭제하시겠습니까?`)) return;

            sheetStates[sid].travelers.splice(index, 1);
            renderSheetTravelers(sid);
            updateSheetStats(sid);
            updateSheetBulkActionButtonsVisibility(sid);
            markUnsavedChanges(true);
            showNotification('여행자가 삭제되었습니다.', 'success');
        }

        function duplicateSheetTraveler(sid, index) {
            if (!sheetStates[sid] || !sheetStates[sid].travelers[index]) {
                showNotification('복제할 여행자 정보를 찾을 수 없습니다.', 'warning');
                return;
            }
            const duplicate = JSON.parse(JSON.stringify(sheetStates[sid].travelers[index]));
            if (duplicate.korean_name) duplicate.korean_name += ' (복사본)';
            else if (duplicate.english_name) duplicate.english_name += ' COPY';
            else duplicate.korean_name = '(복사본)';

            sheetStates[sid].travelers.splice(index + 1, 0, duplicate);
            renderSheetTravelers(sid);
            updateSheetStats(sid);
            updateSheetBulkActionButtonsVisibility(sid);
            markUnsavedChanges(true);
            showNotification('여행자가 복제되었습니다.', 'success');
        }

        function bulkDeleteTravelersInSheet(sid) {
            if (!sheetStates[sid]) return;
            const checkedIndexes = getSheetSelectedTravelerIndexes(sid);
            if (checkedIndexes.length === 0) {
                showNotification('삭제할 여행자를 선택해주세요.', 'warning');
                return;
            }
            if (!confirm(`선택된 ${checkedIndexes.length}명의 여행자를 삭제하시겠습니까?`)) return;

            checkedIndexes.sort((a, b) => b - a).forEach(index => {
                if (sheetStates[sid].travelers[index] !== undefined) {
                    sheetStates[sid].travelers.splice(index, 1);
                }
            });

            renderSheetTravelers(sid);
            updateSheetStats(sid);
            updateSheetBulkActionButtonsVisibility(sid);
            markUnsavedChanges(true);
            showNotification(`${checkedIndexes.length}명의 여행자가 삭제되었습니다.`, 'success');
        }

        function bulkDuplicateTravelersInSheet(sid) {
            if (!sheetStates[sid]) return;
            const checkedIndexes = getSheetSelectedTravelerIndexes(sid);
            if (checkedIndexes.length === 0) {
                showNotification('복제할 여행자를 선택해주세요.', 'warning');
                return;
            }
            if (!confirm(`선택된 ${checkedIndexes.length}명의 여행자를 복제하시겠습니까?`)) return;

            const copies = [];
            checkedIndexes.forEach(index => {
                const src = sheetStates[sid].travelers[index];
                if (!src) return;
                const copy = JSON.parse(JSON.stringify(src));
                if (copy.korean_name) copy.korean_name += ' (복사본)';
                else if (copy.english_name) copy.english_name += ' COPY';
                else copy.korean_name = '(복사본)';
                copies.push(copy);
            });

            sheetStates[sid].travelers = sheetStates[sid].travelers.concat(copies);
            renderSheetTravelers(sid);
            updateSheetStats(sid);
            updateSheetBulkActionButtonsVisibility(sid);
            markUnsavedChanges(true);
            showNotification(`${copies.length}명의 여행자가 복제되었습니다.`, 'success');
        }

        function bulkEditGenderInSheet(sid) {
            if (!sheetStates[sid]) return;
            const checkedIndexes = getSheetSelectedTravelerIndexes(sid);
            if (checkedIndexes.length === 0) {
                showNotification('성별을 수정할 여행자를 선택해주세요.', 'warning');
                return;
            }

            const genderOptions = [
                { value: 'male', label: '남성' },
                { value: 'female', label: '여성' },
                { value: '', label: '선택안함' }
            ];
            const optionsText = genderOptions.map((option, index) => `${index + 1}. ${option.label}`).join('\n');
            const choice = prompt(`선택된 ${checkedIndexes.length}명의 성별을 일괄 수정합니다.\n\n${optionsText}\n\n번호를 입력하세요:`);
            if (choice === null) return;

            const choiceIndex = parseInt(choice, 10) - 1;
            if (choiceIndex < 0 || choiceIndex >= genderOptions.length) {
                showNotification('올바른 번호를 입력해주세요.', 'warning');
                return;
            }

            checkedIndexes.forEach(index => {
                if (sheetStates[sid].travelers[index]) {
                    sheetStates[sid].travelers[index].gender = genderOptions[choiceIndex].value;
                }
            });

            renderSheetTravelers(sid);
            updateSheetStats(sid);
            updateSheetBulkActionButtonsVisibility(sid);
            markUnsavedChanges(true);
            showNotification(`${checkedIndexes.length}명의 성별이 "${genderOptions[choiceIndex].label}"로 수정되었습니다.`, 'success');
        }

        function bulkEditRoomTypeInSheet(sid) {
            if (!sheetStates[sid]) return;
            const checkedIndexes = getSheetSelectedTravelerIndexes(sid);
            if (checkedIndexes.length === 0) {
                showNotification('객실타입을 수정할 여행자를 선택해주세요.', 'warning');
                return;
            }

            const roomTypeOptions = [
                { value: '1r1p', label: '1인실' },
                { value: '2r1p', label: '2인실' },
                { value: '3r1p', label: '3인실' },
                { value: '4r1p', label: '4인실' }
            ];
            const optionsText = roomTypeOptions.map((option, index) => `${index + 1}. ${option.label}`).join('\n');
            const choice = prompt(`선택된 ${checkedIndexes.length}명의 객실타입을 일괄 수정합니다.\n\n${optionsText}\n\n번호를 입력하세요:`);
            if (choice === null) return;

            const choiceIndex = parseInt(choice, 10) - 1;
            if (choiceIndex < 0 || choiceIndex >= roomTypeOptions.length) {
                showNotification('올바른 번호를 입력해주세요.', 'warning');
                return;
            }

            checkedIndexes.forEach(index => {
                if (sheetStates[sid].travelers[index]) {
                    sheetStates[sid].travelers[index].room_type = roomTypeOptions[choiceIndex].value;
                }
            });

            renderSheetTravelers(sid);
            updateSheetStats(sid);
            updateSheetBulkActionButtonsVisibility(sid);
            markUnsavedChanges(true);
            showNotification(`${checkedIndexes.length}명의 객실타입이 "${roomTypeOptions[choiceIndex].label}"로 수정되었습니다.`, 'success');
        }

        function setupSheetSaveHandlers() {
            $(document).on('click', '.btn-save-sheet', function() {
                const $btn = $(this);
                const sid = String($btn.data('sid') || '');
                const $res = $('#result_' + sid);

                if (!sid || !sheetStates[sid]) {
                    showNotification('시트 데이터를 찾을 수 없습니다.', 'error');
                    return;
                }

                const gi = collectSheetGroupDataFromForm(sid);
                sheetStates[sid].groupInfo = { ...sheetStates[sid].groupInfo, ...gi };

                const defaultLeader = gi.group_leader || '';
                const tvl = sheetStates[sid].travelers.map(t => {
                    const traveler = { ...(t || {}) };
                    traveler.english_name = String(traveler.english_name || '').toUpperCase();
                    traveler.passport_number = String(traveler.passport_number || '').toUpperCase();
                    traveler.representative_name = sanitizeRepresentativeName(traveler.representative_name || '');
                    if (!traveler.representative_name) {
                        traveler.representative_name = traveler.korean_name || traveler.english_name || defaultLeader;
                    }
                    return traveler;
                });
                sheetStates[sid].travelers = tvl;

                if (!gi.start_date) {
                    alert('출발일을 입력해주세요.');
                    $('#form_' + sid + ' .sg-start_date').focus();
                    return;
                }
                if (!gi.group_leader) {
                    alert('대표자를 입력해주세요.');
                    $('#form_' + sid + ' .sg-group_leader').focus();
                    return;
                }
                if (tvl.length === 0) {
                    alert('저장할 여행자가 없습니다.');
                    return;
                }
                if (!confirm('"' + (gi.tour_name || sid) + '" 시트를 DB에 저장하시겠습니까?\n여행자 ' + tvl.length + '명')) return;

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> 저장 중...');
                $res.hide();

                $.ajax({
                    url: window.location.pathname,
                    type: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    data: {
                        action: 'save_sheet',
                        sheet_group_data: JSON.stringify(gi),
                        sheet_travelers: JSON.stringify(tvl)
                    },
                    dataType: 'json',
                    success: function(r) {
                        $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> DB 저장');
                        if (r && r.success) {
                            $btn.attr('data-gi', JSON.stringify(gi));
                            $btn.attr('data-tvl', JSON.stringify(tvl));
                            $res.html('<div class="alert alert-success py-1 mb-0 small">' +
                                '<i class="fas fa-check-circle me-1"></i>저장 완료! ' +
                                '예약번호: <strong>' + (r.reservation_number || '-') + '</strong> / ' +
                                '그룹: <strong>' + (r.group_count || '-') + '</strong>건 / ' +
                                '여행자: <strong>' + (r.traveler_count || tvl.length) + '</strong>명' +
                                '</div>').show();
                        } else {
                            $res.html('<div class="alert alert-danger py-1 mb-0 small">' +
                                '<i class="fas fa-exclamation-triangle me-1"></i>' + (r && r.message ? r.message : '저장 실패') +
                                '</div>').show();
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> DB 저장');
                        $res.html('<div class="alert alert-danger py-1 mb-0 small">서버 오류 (' + xhr.status + ')</div>').show();
                    }
                });
            });
        }
        
        function renderAllTravelers() {
            const tbody = $('#travelersTableBody');
            tbody.empty(); // 기존 내용 비우기
            travelerData.forEach((traveler, index) => {
                tbody.append(createTravelerRowHtml(traveler, index));
            });
            updateRowNumbersAndActions(); // 번호 및 액션 버튼 인덱스 업데이트
        }

        function createTravelerRowHtml(data, index) {
			const trData = data || {};
			return `
				<tr data-index="${index}">
					<td>
						<input type="checkbox" class="form-check-input traveler-checkbox" 
							   value="${index}" title="선택">
					</td>
					<td>${index + 1}</td>
					<td><input type="text" class="form-control form-control-sm traveler-field" data-field="korean_name" value="${escapeHtml(trData.korean_name || '')}"></td>
					<td><input type="text" class="form-control form-control-sm traveler-field" data-field="english_name" style="text-transform: uppercase;" value="${escapeHtml(trData.english_name || '')}"></td>
					<td><input type="text" class="form-control form-control-sm traveler-field" data-field="passport_number" style="text-transform: uppercase;" value="${escapeHtml(trData.passport_number || '')}"></td>
					<td><input type="date" class="form-control form-control-sm traveler-field" data-field="birth_date" value="${escapeHtml(trData.birth_date || '')}"></td>
					<td>
						<select class="form-select form-select-sm traveler-field" data-field="gender">
							<option value="">선택</option>
							<option value="male" ${strtolower(trData.gender || '') === 'male' ? 'selected' : ''}>남성</option>
							<option value="female" ${strtolower(trData.gender || '') === 'female' ? 'selected' : ''}>여성</option>
						</select>
					</td>
					<td>
						<input type="tel" class="form-control form-control-sm traveler-field" data-field="phone" value="${escapeHtml(trData.phone || '')}">
						<input type="hidden" class="form-control form-control-sm traveler-field" data-field="email" value="${escapeHtml(trData.email || '')}">
					</td>
					<td>
						<input type="text" class="form-control form-control-sm traveler-field" data-field="representative_name" value="${escapeHtml(sanitizeRepresentativeName(trData.representative_name) || trData.korean_name || trData.english_name || $('input[name=\"group_leader\"]').val().trim() || '')}">
					</td>
					<td>
						<select class="form-select form-select-sm traveler-field" data-field="room_type">
							<option value="1r1p" ${ (trData.room_type || '2r1p') === '1r1p' ? 'selected' : ''}>1인실</option>
							<option value="2r1p" ${ (trData.room_type || '2r1p') === '2r1p' ? 'selected' : ''}>2인실</option>
							<option value="3r1p" ${ (trData.room_type || '2r1p') === '3r1p' ? 'selected' : ''}>3인실</option>
							<option value="4r1p" ${ (trData.room_type || '2r1p') === '4r1p' ? 'selected' : ''}>4인실</option>
						</select>
					</td>
					<td><input type="text" class="form-control form-control-sm traveler-field" data-field="room_number" value="${escapeHtml(trData.room_number || '')}"></td>
					<td><input type="text" class="form-control form-control-sm traveler-field" data-field="memo" value="${escapeHtml(trData.memo || '')}"></td>
					<td class="text-center">
						<div class="table-actions">
							<button type="button" class="btn-action btn-edit" title="수정"><i class="fas fa-edit"></i></button>
							<button type="button" class="btn-action btn-duplicate" title="복제"><i class="fas fa-copy"></i></button>
							<button type="button" class="btn-action btn-delete" title="삭제"><i class="fas fa-trash"></i></button>
						</div>
					</td>
				</tr>`;
		}
        
        function strtolower(str) {
            return str ? String(str).toLowerCase() : '';
        }

        function sanitizeRepresentativeName(value) {
            const text = value ? String(value).trim() : '';
            if (!text) return '';
            if (/@/.test(text)) return '';
            if (/^[\d+\-\s()]+$/.test(text)) return '';
            if (/^\d{4}\s*[.\-\/년]\s*\d{1,2}\s*[.\-\/월]?\s*\d{1,2}(?:\s*일)?(?:\s*[월화수목금토일](?:요일)?)?$/u.test(text)) return '';
            if (/^\d{1,2}\s*[.\-\/]\s*\d{1,2}(?:\s*[.\-\/]\s*\d{2,4})?(?:\s*[월화수목금토일](?:요일)?)?$/u.test(text)) return '';
            return text.replace(/\s+/g, ' ');
        }


        function updateRowNumbersAndActions() {
			$('#travelersTableBody tr').each(function(newIndex) {
				$(this).attr('data-index', newIndex);
				$(this).find('td:nth-child(2)').text(newIndex + 1); // 번호 컬럼 (체크박스 다음)
				
				// 체크박스 value 업데이트
				$(this).find('.traveler-checkbox').val(newIndex);
				
				// 각 입력 필드의 name 속성 인덱스 업데이트
				$(this).find('.traveler-field').each(function() {
					const fieldName = $(this).data('field');
					$(this).attr('name', `travelers[${newIndex}][${fieldName}]`);
				});

				// 버튼 이벤트 핸들러 재설정
				$(this).find('.btn-edit').off('click').on('click', function() { editTraveler(newIndex); });
				$(this).find('.btn-duplicate').off('click').on('click', function() { duplicateTraveler(newIndex); });
				$(this).find('.btn-delete').off('click').on('click', function() { deleteTraveler(newIndex); });
			});
			
			// 체크박스 상태 업데이트
			updateSelectAllState();
			updateBulkActionButtonsVisibility();
		}
        
        // editTraveler 함수는 이제 openTravelerModal(index)를 호출합니다.
        function editTraveler(index) {
            if(travelerData[index]) {
                openTravelerModal(index);
            } else {
                showNotification('수정할 여행자 정보를 찾을 수 없습니다.', 'error');
            }
        }


        function deleteTraveler(index) {
            if (!travelerData[index]) {
                 showNotification('삭제할 여행자 정보를 찾을 수 없습니다.', 'error');
                 renderAllTravelers(); // 테이블 새로고침
                 return;
            }
            const traveler = travelerData[index];
            const displayName = traveler.korean_name || traveler.english_name || `여행자 #${index + 1}`;
            
            if (!confirm(`"${displayName}" 여행자를 삭제하시겠습니까?`)) return;
            
            travelerData.splice(index, 1);
            renderAllTravelers(); // 테이블 전체 재구성
            updateAllStats();
            showNotification('여행자가 삭제되었습니다.', 'success');
            markUnsavedChanges(true);
        }

        function duplicateTraveler(index) {
             if (!travelerData[index]) {
                 showNotification('복제할 여행자 정보를 찾을 수 없습니다.', 'error');
                 renderAllTravelers();
                 return;
            }
            const original = travelerData[index];
            const duplicate = JSON.parse(JSON.stringify(original)); // Deep copy
            
            if (duplicate.korean_name) duplicate.korean_name += ' (복사본)';
            else if (duplicate.english_name) duplicate.english_name += ' COPY';
            else duplicate.korean_name = '(복사본)'; // 이름이 아예 없는 경우
            
            // 복제된 데이터를 현재 행 바로 다음에 삽입
            travelerData.splice(index + 1, 0, duplicate);
            renderAllTravelers(); // 전체 다시 그림
            updateAllStats();
            showNotification('여행자가 복제되었습니다.', 'success');
            markUnsavedChanges(true);
        }

		// 선택된 여행자들로 새로운 그룹 저장
		function saveSelectedAsNewGroup(sid = null) {
            const checkedIndexes = getSelectedTravelerIndexes(sid);

            if (checkedIndexes.length === 0) {
                showNotification('새 그룹으로 저장할 여행자를 선택해주세요.', 'warning');
                return;
            }

            let selectedTravelers = [];
            let currentGroupData = {};
            if (sid) {
                if (!sheetStates[sid]) {
                    showNotification('시트 정보를 찾을 수 없습니다.', 'error');
                    return;
                }
                checkedIndexes.forEach(index => {
                    if (sheetStates[sid].travelers[index] !== undefined) {
                        selectedTravelers.push(sheetStates[sid].travelers[index]);
                    }
                });
                currentGroupData = collectSheetGroupDataFromForm(sid);
            } else {
                checkedIndexes.forEach(index => {
                    if (travelerData[index] !== undefined) {
                        selectedTravelers.push(travelerData[index]);
                    }
                });
                currentGroupData = collectGroupDataFromForm();
            }

            if (selectedTravelers.length === 0) {
                showNotification('선택된 여행자 데이터를 찾을 수 없습니다.', 'error');
                return;
            }

            const newGroupName = prompt(
                `선택된 ${selectedTravelers.length}명으로 새로운 그룹을 만듭니다.\n\n새 그룹명을 입력하세요:`,
                (currentGroupData.tour_name || '그룹') + ' (분할그룹)'
            );
            if (newGroupName === null) return;
            if (newGroupName.trim() === '') {
                showNotification('그룹명을 입력해주세요.', 'warning');
                return;
            }

            const newGroupData = {
                ...currentGroupData,
                tour_name: newGroupName.trim(),
                product_code: currentGroupData.product_code
            };

            const confirmMsg = `새로운 그룹 예약을 생성하시겠습니까?\n\n그룹명: ${newGroupData.tour_name}\n출발일: ${newGroupData.start_date}\n대표자: ${newGroupData.group_leader}\n여행자 수: ${selectedTravelers.length}명`;
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
                        showNotification(response.message || '새 그룹이 성공적으로 저장되었습니다.', 'success', 8000);
                        setTimeout(() => {
                            if (!confirm('저장된 여행자들을 현재 목록에서 제거하시겠습니까?')) {
                                if (sid) {
                                    $('#travelersTableBody_' + sid + ' .sheet-traveler-checkbox:checked').prop('checked', false);
                                    updateSheetBulkActionButtonsVisibility(sid);
                                    updateSheetSelectAllState(sid);
                                } else {
                                    $('.traveler-checkbox:checked').prop('checked', false);
                                    updateBulkActionButtonsVisibility();
                                }
                                return;
                            }

                            if (sid) {
                                bulkDeleteTravelersInSheet(sid);
                            } else {
                                bulkDeleteTravelers();
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
                const searchTerm = $(this).val().toLowerCase();
                // 검색어가 없으면 그룹 헤더 포함 전체 표시
                if (searchTerm === '') {
                    $('#travelersTableBody tr').show();
                    return;
                }
                // 그룹 헤더 행은 건드리지 않고 여행자 행만 검색
                const visibleGroups = {};
                $('#travelersTableBody tr:not(.group-header-row)').each(function() {
                    const row = $(this);
                    const korean_name       = row.find('input[data-field="korean_name"]').val().toLowerCase();
                    const english_name      = row.find('input[data-field="english_name"]').val().toLowerCase();
                    const representative_name = row.find('input[data-field="representative_name"]').val().toLowerCase();
                    const passport          = row.find('input[data-field="passport_number"]').val().toLowerCase();
                    const phone             = row.find('input[data-field="phone"]').val().toLowerCase();
                    const isMatch = korean_name.includes(searchTerm) ||
                                    english_name.includes(searchTerm) ||
                                    representative_name.includes(searchTerm) ||
                                    passport.includes(searchTerm) ||
                                    phone.includes(searchTerm);
                    row.toggle(isMatch);
                    if (isMatch) {
                        const gk = row.data('group-key');
                        if (gk !== undefined) visibleGroups[gk] = true;
                    }
                });
                // 검색 결과가 있는 그룹의 헤더만 표시
                $('#travelersTableBody tr.group-header-row').each(function() {
                    const gk = $(this).data('group-key');
                    $(this).toggle(visibleGroups[gk] === true);
                });
            });
        }

        function updateAllStats() {
            let maleCount = 0, femaleCount = 0, passportCount = 0;
            const totalCount = travelerData.length;
            
            travelerData.forEach(traveler => {
                if (traveler) { // traveler가 undefined가 아닌지 확인
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


       async  function handleGroupSave(e) {
            e.preventDefault(); // AJAX로 처리하므로 기본 폼 제출 방지
            
            // 현재 테이블에서 travelerData를 한번 더 최신화 (필수)
            // renderAllTravelers 후 input 이벤트가 바로 반영 안될 수 있으므로, 명시적 업데이트
             $('#travelersTableBody tr').each(function() {
                const rowIndex = $(this).data('index');
                if (travelerData[rowIndex]) {
                    $(this).find('.traveler-field').each(function() {
                        const field = $(this).data('field');
                        let value = $(this).val();
                         if (field === 'english_name' || field === 'passport_number') {
                            value = value.toUpperCase();
                        }
                        travelerData[rowIndex][field] = value;
                    });

                    travelerData[rowIndex].representative_name = sanitizeRepresentativeName(travelerData[rowIndex].representative_name || '');
                    if (!travelerData[rowIndex].representative_name) {
                        travelerData[rowIndex].representative_name = (
                            travelerData[rowIndex].korean_name ||
                            travelerData[rowIndex].english_name ||
                            $('input[name="group_leader"]').val().trim() ||
                            ''
                        ).trim();
                    }
                }
            });


            if (travelerData.length === 0) {
                showNotification('저장할 여행자가 없습니다.', 'warning');
                return;
            }
            
            const currentGroupData = collectGroupDataFromForm();

            if (!currentGroupData.tour_name && !currentGroupData.product_code) {
                showNotification('상품코드 또는 상품명/투어명을 입력해주세요.', 'warning');
                $('input[name="product_code"]').focus();
                return;
            }
            if (!currentGroupData.start_date) {
                showNotification('출발일을 선택해주세요.', 'warning');
                $('input[name="start_date"]').focus();
                return;
            }
            if (!currentGroupData.group_leader) {
                const firstTraveler = travelerData.find(tr => tr && (tr.korean_name || tr.english_name));
                const firstTravelerName = firstTraveler ? (firstTraveler.korean_name || firstTraveler.english_name) : '';
                if (firstTravelerName) {
                    currentGroupData.group_leader = firstTravelerName;
                    $('input[name="group_leader"]').val(firstTravelerName);
                } else {
                    showNotification('그룹 대표자를 입력해주세요.', 'warning');
                    $('input[name="group_leader"]').focus();
                    return;
                }
            }

            let invalidTravelersMessages = [];
            travelerData.forEach((traveler, index) => {
                if (!traveler.korean_name && !traveler.english_name) {
                    invalidTravelersMessages.push(`${index + 1}번 여행자: 이름 누락`);
                }
                if (traveler.email && !validateEmail(traveler.email)) {
                     invalidTravelersMessages.push(`${index + 1}번 여행자: (${traveler.korean_name || traveler.english_name}) 이메일 형식 오류`);
                }
            });

            if (invalidTravelersMessages.length > 0) {
                showNotification('여행자 정보 오류:<br>' + invalidTravelersMessages.join('<br>'), 'error', 10000); // 10초간 표시
                return;
            }
            
            const confirmMsg = `그룹 예약을 저장하시겠습니까?\n\n상품명: ${currentGroupData.tour_name}\n출발일: ${currentGroupData.start_date}\n대표자: ${currentGroupData.group_leader}\n여행자 수: ${travelerData.length}명\n\n저장 후에는 이 화면에서의 수정이 반영되지 않습니다.`;
            
            if (!confirm(confirmMsg)) return;
            
            $('#loading').show().get(0).scrollIntoView({ behavior: 'smooth' });
            $('#saveGroupBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 저장 중...');

            $.ajax({
			  url: location.href, // '' 대신 현재 페이지 명시
			  type: 'POST',
			  data: {
				action: 'save_group_reservation',
				group_data: JSON.stringify(currentGroupData),
				travelers_data: JSON.stringify(travelerData)
			  },
			  dataType: 'json'
			})
			.done(function(response) {
			  if (response && response.success) {
				showNotification(response.message, 'success', 5000);
				//markUnsavedChanges(false);
			
				
			  } else {
				showNotification('저장 실패: ' + (response?.message || '알 수 없는 오류'), 'error', 10000);
			  }
			})
			.fail(function(xhr, status, error) {
			  showNotification('서버 통신 오류가 발생했습니다: ' + (error || status), 'error', 10000);
			  console.error("AJAX Error:", xhr?.responseText);
			})
			.always(function() { // complete 대체: 성공/실패 모두 실행
			  $('#loading').hide();
			  
			  $('#saveGroupBtn').prop('disabled', false).html('<i class="fas fa-save"></i> 그룹 예약 저장');
			});

        }
        

        function escapeHtml(text) {
            if (typeof text !== 'string') {
                if (text === null || typeof text === 'undefined') return '';
                text = String(text);
            }
            return text.replace(/[&<>"']/g, function(match) {
                const map = {'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'};
                return map[match];
            });
        }

        function showNotification(message, type = 'info', duration = 5000) {
            const alertId = 'liveAlert-' + Date.now();
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show position-fixed" role="alert" id="${alertId}"
                     style="top: 20px; right: 20px; z-index: 10050; min-width: 300px; max-width: 90%;">
                    <div>${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
            $('body').append(alertHtml);
            
            const bsAlert = new bootstrap.Alert(document.getElementById(alertId));
            if (duration > 0) {
                setTimeout(() => {
                    // Bootstrap 5에서는 hide() 후 remove()를 명시적으로 호출하는 것이 더 안정적일 수 있음
                    $('#' + alertId).fadeOut(500, function() { $(this).remove(); });
                }, duration);
            }
        }
        
        let unsavedChanges = false;
        function markUnsavedChanges(status) {
            unsavedChanges = status;
        }
		
        $(document).on('input change', '#groupReservationForm input, #groupReservationForm select, #groupReservationForm textarea', function() {
            markUnsavedChanges(true);
        });


        window.addEventListener('beforeunload', function(e) {
            if (unsavedChanges && travelerData.length > 0) {
                const confirmationMessage = '저장하지 않은 변경사항이 있습니다. 정말 페이지를 나가시겠습니까?';
                e.returnValue = confirmationMessage; // 표준
                return confirmationMessage;      // 일부 구형 브라우저용
            }
        });

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(String(email).toLowerCase());
        }
        
        function autoFormatInputs() {
             // 영문 이름/여권번호 실시간 대문자화 (이벤트 위임)
            $('#travelersTableBody').on('input', 'input[data-field="english_name"], input[data-field="passport_number"]', function() {
                const upperVal = $(this).val().toUpperCase();
                $(this).val(upperVal);
            });
             $('#modalEnglishName, #modalPassportNumber').on('input', function() {
                const upperVal = $(this).val().toUpperCase();
                $(this).val(upperVal);
            });
        }
        // PHP에서 이미 travelerData와 groupInfoData를 생성했으므로, 페이지 로드 시 바로 테이블을 그립니다.
        if (travelerData && travelerData.length > 0) {
             renderAllTravelers();
        }


    </script>
</body>
</html>
