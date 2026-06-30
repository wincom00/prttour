<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mbx_json(array('status' => 'error', 'message' => 'POST만 허용됩니다.'), 405);
}
mbx_require_api_auth();

function mbx_parse_email_csv($raw, &$invalid)
{
    $out = array();
    $invalid = array();
    foreach (preg_split('/[,;\r\n]+/', (string)$raw) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $email = $part;
        if (preg_match('/<([^<>]+)>$/', $part, $m)) {
            $email = trim($m[1]);
        }
        $email = trim($email, " \t\n\r\0\x0B\"'");
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $out[] = $email;
        } else {
            $invalid[] = $part;
        }
    }
    return array_values(array_unique($out));
}

function mbx_clean_upload_name($name)
{
    $name = basename((string)$name);
    return preg_replace('/[\r\n"\\\\\/]+/', '_', $name);
}

try {
    $db = mbx_db();
    $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    $account = $accountId > 0 ? mbx_get_account($db, $accountId, true) : mbx_current_account($db);
    if (!$account) {
        throw new RuntimeException('발송 계정이 없습니다.');
    }
    $badTo = array();
    $badCc = array();
    $toList = mbx_parse_email_csv(isset($_POST['to']) ? $_POST['to'] : '', $badTo);
    $ccList = mbx_parse_email_csv(isset($_POST['cc']) ? $_POST['cc'] : '', $badCc);
    $badEmails = array_merge($badTo, $badCc);
    if ($badEmails) {
        throw new RuntimeException('잘못된 이메일 주소: ' . implode(', ', $badEmails));
    }
    if (!$toList) {
        throw new RuntimeException('받는 사람 이메일이 필요합니다.');
    }
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $body = isset($_POST['body']) ? (string)$_POST['body'] : '';
    $inReplyTo = isset($_POST['in_reply_to']) ? trim($_POST['in_reply_to']) : '';

    require_once mbx_admin_path('PHPMailer/class.phpmailer.php');
    require_once mbx_admin_path('PHPMailer/class.smtp.php');

    $stored = array();
    $uploadRoot = dirname(__DIR__) . '/uploads/' . date('Ym');
    if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0755, true)) {
        throw new RuntimeException('첨부 임시 폴더를 만들 수 없습니다.');
    }
    $blocked = array('php','phtml','php3','php4','php5','php6','php7','php8','phps','pht','cgi','pl','exe','sh','bat');
    if (isset($_FILES['attach']) && is_array($_FILES['attach']['name'])) {
        $count = count($_FILES['attach']['name']);
        if ($count > MBX_MAX_ATTACH) {
            throw new RuntimeException('첨부는 최대 ' . MBX_MAX_ATTACH . '개까지 가능합니다.');
        }
        for ($i = 0; $i < $count; $i++) {
            if ((int)$_FILES['attach']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ((int)$_FILES['attach']['error'][$i] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('첨부 업로드 오류가 발생했습니다.');
            }
            if ((int)$_FILES['attach']['size'][$i] > MBX_MAX_ATTACH_SIZE) {
                throw new RuntimeException('첨부 파일은 20MB 이하만 가능합니다.');
            }
            $orig = mbx_clean_upload_name($_FILES['attach']['name'][$i]);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (in_array($ext, $blocked, true)) {
                throw new RuntimeException('허용되지 않는 첨부 확장자입니다.');
            }
            $path = $uploadRoot . '/' . uniqid('mbx_', true) . '.bin';
            if (!move_uploaded_file($_FILES['attach']['tmp_name'][$i], $path)) {
                throw new RuntimeException('첨부 파일을 저장하지 못했습니다.');
            }
            $stored[] = array('path' => $path, 'name' => $orig);
        }
    }

    $mail = new PHPMailer(true);
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = $account['smtp_host'];
    $mail->Port = (int)$account['smtp_port'];
    $mail->Username = $account['email'];
    $mail->Password = $account['app_password'];
    $mail->CharSet = 'utf-8';
    $mail->IsHTML(true);
    $mail->SetFrom($account['email'], $account['display_name'] !== '' ? $account['display_name'] : $account['email']);
    foreach ($toList as $email) {
        $mail->AddAddress($email);
    }
    foreach ($ccList as $email) {
        $mail->AddCC($email);
    }
    if ($inReplyTo !== '') {
        $mail->AddCustomHeader('In-Reply-To: ' . $inReplyTo);
        $mail->AddCustomHeader('References: ' . $inReplyTo);
    }
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    foreach ($stored as $file) {
        $mail->AddAttachment($file['path'], $file['name']);
    }
    $mail->Send();

    foreach ($stored as $file) {
        @unlink($file['path']);
    }

    global $MBX_FOLDERS;
    try {
        $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
        $sync->syncFolder('sent');
    } catch (Exception $e) {
    }
    mbx_redirect(mbx_plugin_url('index.php?folder=sent&sent=1'));
} catch (Exception $e) {
    if (isset($stored) && is_array($stored)) {
        foreach ($stored as $file) {
            @unlink($file['path']);
        }
    }
    $msg = urlencode($e->getMessage());
    mbx_redirect(mbx_plugin_url('compose.php?error=' . $msg));
}
?>
