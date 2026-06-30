<?php
final class MailboxSync
{
    private $db;
    private $account;
    private $folderMap;
    private $isGmail;

    public function __construct(mysqli $db, array $account, array $folderMap)
    {
        $this->db = $db;
        $this->account = $account;
        $this->folderMap = self::normalizeFolderMap($account, $folderMap);
        $this->isGmail = self::accountIsGmail($account);
    }

    private static function normalizeFolderMap(array $account, array $folderMap)
    {
        if (isset($folderMap['inbox'])) {
            return $folderMap;
        }
        $provider = isset($account['provider']) ? strtolower(trim((string)$account['provider'])) : '';
        if ($provider !== '' && isset($folderMap[$provider]) && is_array($folderMap[$provider])) {
            return $folderMap[$provider];
        }
        if (self::accountIsGmail($account) && isset($folderMap['gmail']) && is_array($folderMap['gmail'])) {
            return $folderMap['gmail'];
        }
        if (isset($folderMap['outlook']) && is_array($folderMap['outlook'])) {
            $host = isset($account['imap_host']) ? strtolower((string)$account['imap_host']) : '';
            if (strpos($host, 'outlook') !== false || strpos($host, 'office365') !== false || strpos($host, 'hotmail') !== false) {
                return $folderMap['outlook'];
            }
        }
        if (isset($folderMap['gmail']) && is_array($folderMap['gmail'])) {
            return $folderMap['gmail'];
        }
        return array('inbox' => 'INBOX', 'sent' => 'Sent Items', 'trash' => 'Deleted Items');
    }

    // X-GM-THRID 등 Gmail 전용 IMAP 확장은 Gmail 계정에서만 사용한다.
    // Outlook 등 다른 서버에 X-GM-THRID 를 보내면 FETCH 전체가 BAD 로 실패한다.
    // provider 컬럼이 비어 있는 기존 데이터는 호스트명으로 추정한다.
    private static function accountIsGmail(array $account)
    {
        $provider = isset($account['provider']) ? strtolower(trim((string)$account['provider'])) : '';
        if ($provider !== '') {
            return $provider === 'gmail';
        }
        $host = isset($account['imap_host']) ? strtolower((string)$account['imap_host']) : '';
        return strpos($host, 'gmail') !== false || strpos($host, 'google') !== false;
    }

    // Gmail 이면 'X-GM-THRID '(뒤 공백 포함), 아니면 '' 를 돌려 FETCH 항목 목록에 끼워 넣는다.
    private function gmailThreadItem()
    {
        return $this->isGmail ? 'X-GM-THRID ' : '';
    }

    public static function ensureTables(mysqli $db)
    {
        $sqls = array(
            "CREATE TABLE IF NOT EXISTS mailbox_accounts (
              id INT AUTO_INCREMENT PRIMARY KEY,
              email VARCHAR(255) NOT NULL,
              display_name VARCHAR(100) DEFAULT '',
              imap_host VARCHAR(100) NOT NULL DEFAULT 'imap.gmail.com',
              imap_port INT NOT NULL DEFAULT 993,
              smtp_host VARCHAR(100) NOT NULL DEFAULT 'smtp.gmail.com',
              smtp_port INT NOT NULL DEFAULT 587,
              app_password VARCHAR(255) NOT NULL,
              provider VARCHAR(20) NOT NULL DEFAULT 'gmail',
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              sort_order INT NOT NULL DEFAULT 0,
              owner_userid VARCHAR(100) NOT NULL DEFAULT '',
              is_common TINYINT(1) NOT NULL DEFAULT 0,
              UNIQUE KEY uk_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS mailbox_admins (
              userid VARCHAR(100) NOT NULL PRIMARY KEY,
              added_by VARCHAR(100) NOT NULL DEFAULT '',
              created_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS mailbox_folders (
              id INT AUTO_INCREMENT PRIMARY KEY,
              account_id INT NOT NULL,
              folder_key VARCHAR(20) NOT NULL,
              imap_name VARCHAR(100) NOT NULL,
              uidvalidity BIGINT UNSIGNED NOT NULL DEFAULT 0,
              last_uid BIGINT UNSIGNED NOT NULL DEFAULT 0,
              last_sync DATETIME NULL,
              UNIQUE KEY uk_acct_folder (account_id, folder_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS mailbox_messages (
              id INT AUTO_INCREMENT PRIMARY KEY,
              account_id INT NOT NULL,
              folder_key VARCHAR(20) NOT NULL,
              uid BIGINT UNSIGNED NOT NULL,
              thread_id VARCHAR(40) NOT NULL DEFAULT '',
              message_id VARCHAR(255) NOT NULL DEFAULT '',
              in_reply_to VARCHAR(255) NOT NULL DEFAULT '',
              from_name VARCHAR(255) NOT NULL DEFAULT '',
              from_email VARCHAR(255) NOT NULL DEFAULT '',
              to_addr TEXT NULL,
              cc_addr TEXT NULL,
              subject VARCHAR(500) NOT NULL DEFAULT '',
              mail_date DATETIME NULL,
              snippet VARCHAR(300) NOT NULL DEFAULT '',
              body_html MEDIUMTEXT NULL,
              body_text MEDIUMTEXT NULL,
              body_fetched TINYINT(1) NOT NULL DEFAULT 0,
              is_read TINYINT(1) NOT NULL DEFAULT 0,
              has_attachment TINYINT(1) NOT NULL DEFAULT 0,
              msg_size INT UNSIGNED NOT NULL DEFAULT 0,
              synced_at DATETIME NULL,
              UNIQUE KEY uk_acct_folder_uid (account_id, folder_key, uid),
              KEY idx_thread (account_id, thread_id),
              KEY idx_list (account_id, folder_key, mail_date),
              KEY idx_read (account_id, folder_key, is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS mailbox_attachments (
              id INT AUTO_INCREMENT PRIMARY KEY,
              msg_id INT NOT NULL,
              part_no VARCHAR(20) NOT NULL,
              filename VARCHAR(255) NOT NULL DEFAULT '',
              mime_type VARCHAR(100) NOT NULL DEFAULT '',
              size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
              content_id VARCHAR(255) NOT NULL DEFAULT '',
              KEY idx_msg (msg_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
        foreach ($sqls as $sql) {
            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
                throw new RuntimeException(mysqli_error($db));
            }
            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                throw new RuntimeException($err);
            }
            mysqli_stmt_close($stmt);
        }

        // 기존 테이블에 소유자/공통 컬럼이 없으면 추가 (idempotent)
        $addCols = array(
            array('mailbox_accounts', 'owner_userid', "ALTER TABLE mailbox_accounts ADD COLUMN owner_userid VARCHAR(100) NOT NULL DEFAULT ''"),
            array('mailbox_accounts', 'is_common', "ALTER TABLE mailbox_accounts ADD COLUMN is_common TINYINT(1) NOT NULL DEFAULT 0"),
            array('mailbox_accounts', 'provider', "ALTER TABLE mailbox_accounts ADD COLUMN provider VARCHAR(20) NOT NULL DEFAULT 'gmail' AFTER app_password"),
            array('mailbox_messages', 'thread_id', "ALTER TABLE mailbox_messages ADD COLUMN thread_id VARCHAR(40) NOT NULL DEFAULT '' AFTER uid"),
        );
        foreach ($addCols as $addCol) {
            $table = $addCol[0];
            $col = $addCol[1];
            $alter = $addCol[2];
            $chk = mysqli_query($db, "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . mysqli_real_escape_string($db, $table) . "' AND COLUMN_NAME='" . mysqli_real_escape_string($db, $col) . "' LIMIT 1");
            if ($chk && mysqli_num_rows($chk) === 0) {
                mysqli_query($db, $alter);
            }
        }
        $idx = mysqli_query($db, "SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='mailbox_messages' AND INDEX_NAME='idx_thread' LIMIT 1");
        if ($idx && mysqli_num_rows($idx) === 0) {
            mysqli_query($db, "ALTER TABLE mailbox_messages ADD KEY idx_thread (account_id, thread_id)");
        }

        // MySQL 8.0 기본 collation(utf8mb4_0900_ai_ci)은 레거시 utf8mb3 연결과 파라미터 변환이 불가능해
        // ("Conversion from collation utf8mb3_general_ci into utf8mb4_0900_ai_ci impossible") INSERT가 모두 실패한다.
        // utf8mb3와 호환되는 utf8mb4_general_ci로 정렬하여 동기화가 정상 동작하도록 한다 (idempotent).
        $mbxTables = array('mailbox_accounts', 'mailbox_admins', 'mailbox_folders', 'mailbox_messages', 'mailbox_attachments');
        foreach ($mbxTables as $table) {
            $chk = mysqli_query($db, "SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . mysqli_real_escape_string($db, $table) . "' LIMIT 1");
            if ($chk && ($r = mysqli_fetch_assoc($chk)) && $r['TABLE_COLLATION'] !== 'utf8mb4_general_ci') {
                mysqli_query($db, "ALTER TABLE " . $table . " CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            }
        }
    }

    public function syncAll()
    {
        $out = array('new' => array(), 'errors' => array());
        foreach ($this->folderMap as $key => $name) {
            try {
                $out['new'][$key] = $this->syncFolder($key);
            } catch (Throwable $e) {
                $out['new'][$key] = 0;
                $out['errors'][$key] = $e->getMessage();
            }
        }
        return $out;
    }

    public function syncFolder($folderKey)
    {
        if (!isset($this->folderMap[$folderKey])) {
            throw new RuntimeException('Unknown folder.');
        }
        @set_time_limit(5000);
        $lockName = 'mbx_sync_' . (int)$this->account['id'];
        if (!$this->acquireLock($lockName)) {
            return 0;
        }

        $client = null;
        try {
            $client = $this->openClient();
            $folderName = $this->resolveFolderName($client, $folderKey);
            $folder = $this->ensureFolder($folderKey, $folderName);
            $status = $client->select($folder['imap_name']);

            if ((int)$folder['uidvalidity'] > 0 && (int)$folder['uidvalidity'] !== (int)$status['uidvalidity']) {
                $this->resetFolder($folderKey);
                $folder['last_uid'] = 0;
            }

            $lastUid = (int)$folder['last_uid'];
            $localMaxUid = $this->localMaxUid($folderKey);
            if ($lastUid > 0 && $localMaxUid < $lastUid) {
                // If a previous sync advanced folder state but did not keep the
                // corresponding local rows, rewind to the local edge and refill.
                $lastUid = $localMaxUid;
            }
            if ($lastUid <= 0) {
                $uids = $client->uidSearch('ALL');
                // 초기 동기화는 모든 폴더 동일하게 최신 MBX_INITIAL_SYNC_LIMIT 통만 가져온다.
                // (inbox 를 MBX_INBOX_MAX_MESSAGES=5000 로 한 번에 받으면 100회 FETCH 로 7~8분이 걸려
                //  set_time_limit/요청 타임아웃에 걸리고, 끝의 updateFolderState 에 도달 못 해 last_uid 가
                //  0 으로 남아 매번 처음부터 다시 도는 무한 미완료 상태가 된다.)
                // MBX_INBOX_MAX_MESSAGES 는 enforceInboxLimit 의 보관 상한으로만 사용한다.
                $initialLimit = (int)MBX_INITIAL_SYNC_LIMIT;
                if ($initialLimit > 0 && count($uids) > $initialLimit) {
                    $uids = array_slice($uids, -$initialLimit);
                }
            } else {
                $uids = $client->uidSearch(($lastUid + 1) . ':*');
            }

            $newCount = 0;
            $maxUid = $lastUid;
            $chunks = array_chunk($uids, 50);
            foreach ($chunks as $chunk) {
                if (!$chunk) {
                    continue;
                }
                $set = implode(',', $chunk);
                $items = '(FLAGS ' . $this->gmailThreadItem() . 'RFC822.SIZE BODYSTRUCTURE BODY.PEEK[HEADER.FIELDS (FROM TO CC SUBJECT DATE MESSAGE-ID IN-REPLY-TO)] BODY.PEEK[TEXT]<0.4096>)';
                $rows = $client->uidFetch($set, $items);
                foreach ($rows as $uid => $fetch) {
                    // IMAP "(lastUid+1):*" can echo back the last existing message when
                    // there is nothing new; only treat strictly higher UIDs as new mail.
                    if ($lastUid > 0 && (int)$uid <= $lastUid) {
                        continue;
                    }
                    $this->saveHeaderRow($folderKey, (int)$uid, $fetch);
                    $newCount++;
                    if ((int)$uid > $maxUid) {
                        $maxUid = (int)$uid;
                    }
                }
            }

            $this->syncRecentFlags($client, $folderKey);
            $this->syncMissingThreadIds($client, $folderKey);
            $this->syncMissingPreviews($client, $folderKey);
            if ($folderKey === 'inbox') {
                $this->enforceInboxLimit();
            }
            $this->updateFolderState($folderKey, $status['uidvalidity'], $maxUid);
            $client->logout();
            $this->releaseLock($lockName);
            return $newCount;
        } catch (Exception $e) {
            if ($client) {
                $client->logout();
            }
            $this->releaseLock($lockName);
            throw $e;
        }
    }

    public function fetchBody($messageRowId)
    {
        $row = $this->getMessage((int)$messageRowId);
        if (!$row) {
            throw new RuntimeException('Message not found.');
        }
        $client = $this->openClient();
        try {
            $folderName = $this->resolveFolderName($client, $row['folder_key']);
            $folder = $this->ensureFolder($row['folder_key'], $folderName);
            $status = $client->select($folder['imap_name']);

            $uid = (int)$row['uid'];
            // A changed UIDVALIDITY means every stored UID for this folder is stale.
            if ((int)$folder['uidvalidity'] > 0 && (int)$folder['uidvalidity'] !== (int)$status['uidvalidity']) {
                $uid = 0;
            }

            $bodyItems = '(' . $this->gmailThreadItem() . 'BODY.PEEK[])';
            $fetched = $uid > 0 ? $client->uidFetch((string)$uid, $bodyItems) : array();

            // Recover a stale/moved message by locating it via its Message-ID.
            if (empty($fetched) && trim((string)$row['message_id']) !== '') {
                $criteria = 'HEADER MESSAGE-ID "' . addcslashes(trim($row['message_id']), '"\\') . '"';
                $found = $client->uidSearch($criteria);
                if ($found) {
                    $uid = (int)end($found);
                    $fetched = $client->uidFetch((string)$uid, $bodyItems);
                    if ($uid !== (int)$row['uid']) {
                        $stmt = $this->stmt("UPDATE mailbox_messages SET uid=? WHERE id=?", 'ii', array($uid, (int)$row['id']));
                        mysqli_stmt_close($stmt);
                    }
                }
            }

            // 여전히 못 찾으면(Gmail 에서 보관되어 원 폴더에서 빠진 경우 등) 전체보관함에서
            // Message-ID 로 다시 찾아 본문을 복구한다. 전체보관함 UID 는 폴더가 달라
            // mailbox_messages.uid(원 폴더 기준)를 덮어쓰지 않는다.
            if (empty($fetched) && $this->isGmail && trim((string)$row['message_id']) !== '') {
                $allMail = $this->findAllMailFolder($client);
                if ($allMail !== '') {
                    $client->select($allMail);
                    $criteria = 'HEADER MESSAGE-ID "' . addcslashes(trim($row['message_id']), '"\\') . '"';
                    $found = $client->uidSearch($criteria);
                    if ($found) {
                        $uid = (int)end($found);
                        $fetched = $client->uidFetch((string)$uid, $bodyItems);
                    }
                }
            }

            if (empty($fetched)) {
                throw new RuntimeException('Message body fetch failed: 서버에서 메일을 찾을 수 없습니다. 메일 동기화를 다시 실행해 주세요.');
            }
            $first = reset($fetched);
            $threadId = isset($first['X-GM-THRID']) ? (string)$first['X-GM-THRID'] : (isset($row['thread_id']) ? (string)$row['thread_id'] : '');
            $raw = isset($first['BODY']) ? $first['BODY'] : '';
            if ($raw === '') {
                throw new RuntimeException('Message body is empty or unavailable.');
            }
            $parsed = MimeParser::parseMessage($raw);
            $bodyHtml = $parsed['body_html'];
            $bodyText = $parsed['body_text'];
            $snippet = MimeParser::makeSnippet($bodyHtml !== '' ? $bodyHtml : $bodyText);
            $hasAttach = count($parsed['attachments']) > 0 ? 1 : 0;

            $stmt = $this->stmt("UPDATE mailbox_messages SET thread_id=?, body_html=?, body_text=?, body_fetched=1, is_read=1, has_attachment=?, snippet=?, synced_at=NOW() WHERE id=?", 'sssisi', array($threadId, $bodyHtml, $bodyText, $hasAttach, $snippet, $row['id']));
            mysqli_stmt_close($stmt);

            $stmt = $this->stmt("DELETE FROM mailbox_attachments WHERE msg_id=?", 'i', array($row['id']));
            mysqli_stmt_close($stmt);
            foreach ($parsed['attachments'] as $att) {
                $this->insertAttachment($row['id'], $att);
            }
            try {
                $client->uidStore((string)$uid, '+FLAGS.SILENT', '\\Seen');
            } catch (Exception $e) {
                // Marking as read is best-effort; never fail body display over it.
            }
            $client->logout();
            return $this->getMessage((int)$messageRowId);
        } catch (Exception $e) {
            $client->logout();
            throw $e;
        }
    }

    public function refreshThreadId($messageRowId)
    {
        $row = $this->getMessage((int)$messageRowId);
        if (!$row) {
            return '';
        }
        if (isset($row['thread_id']) && trim((string)$row['thread_id']) !== '') {
            return (string)$row['thread_id'];
        }
        // X-GM-THRID 는 Gmail 전용. 다른 서버에서는 대화 ID 가 없으므로 그냥 빈 값으로 둔다.
        if (!$this->isGmail) {
            return '';
        }
        $client = $this->openClient();
        try {
            $folderName = $this->resolveFolderName($client, $row['folder_key']);
            $client->select($folderName);
            $uid = (int)$row['uid'];
            $fetched = $uid > 0 ? $client->uidFetch((string)$uid, '(X-GM-THRID)') : array();
            if (empty($fetched) && trim((string)$row['message_id']) !== '') {
                $criteria = 'HEADER MESSAGE-ID "' . addcslashes(trim($row['message_id']), '"\\') . '"';
                $found = $client->uidSearch($criteria);
                if ($found) {
                    $uid = (int)end($found);
                    $fetched = $client->uidFetch((string)$uid, '(X-GM-THRID)');
                }
            }
            $first = reset($fetched);
            $threadId = isset($first['X-GM-THRID']) ? (string)$first['X-GM-THRID'] : '';
            if ($threadId !== '') {
                $stmt = $this->stmt("UPDATE mailbox_messages SET thread_id=? WHERE id=? AND account_id=?", 'sii', array($threadId, (int)$row['id'], (int)$this->account['id']));
                mysqli_stmt_close($stmt);
            }
            $client->logout();
            return $threadId;
        } catch (Exception $e) {
            $client->logout();
            return '';
        }
    }

    public function markRead(array $rowIds, $read)
    {
        $ids = $this->cleanIds($rowIds);
        if (!$ids) {
            return;
        }
        $rows = $this->getMessagesByIds($ids);
        $groups = $this->groupRows($rows);
        foreach ($groups as $folderKey => $uids) {
            $client = $this->openClient();
            try {
                $folderName = $this->resolveFolderName($client, $folderKey);
                $folder = $this->ensureFolder($folderKey, $folderName);
                $client->select($folder['imap_name']);
                $client->uidStore(implode(',', $uids), $read ? '+FLAGS.SILENT' : '-FLAGS.SILENT', '\\Seen');
                $client->logout();
            } catch (Exception $e) {
                $client->logout();
                throw $e;
            }
        }
        $in = implode(',', $ids);
        $readVal = $read ? 1 : 0;
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge(array($readVal), $ids);
        $stmt = $this->stmt("UPDATE mailbox_messages SET is_read=? WHERE id IN (" . $ph . ")", 'i' . str_repeat('i', count($ids)), $params);
        mysqli_stmt_close($stmt);
    }

    public function moveToTrash(array $rowIds)
    {
        $ids = $this->cleanIds($rowIds);
        if (!$ids) {
            return;
        }
        $rows = $this->getMessagesByIds($ids);
        $groups = $this->groupFullRows($rows);
        $movedIds = array();
        $errors = array();
        foreach ($groups as $folderKey => $folderRows) {
            if ($folderKey === 'trash') {
                foreach ($folderRows as $row) {
                    $movedIds[] = (int)$row['id'];
                }
                continue;
            }
            $client = $this->openClient();
            try {
                $folderName = $this->resolveFolderName($client, $folderKey);
                $folder = $this->ensureFolder($folderKey, $folderName);
                $client->select($folder['imap_name']);
                $trashName = $this->resolveTrashFolder($client);
                foreach ($folderRows as $row) {
                    try {
                        $uid = $this->resolveCurrentUid($client, $row);
                        if ($uid <= 0) {
                            throw new RuntimeException('Message UID not found.');
                        }
                        $client->uidMove((string)$uid, $trashName);
                        $movedIds[] = (int)$row['id'];
                    } catch (Exception $moveError) {
                        $errors[] = '#' . (int)$row['id'] . ' ' . $moveError->getMessage();
                    }
                }
                $client->logout();
            } catch (Exception $e) {
                $client->logout();
                foreach ($folderRows as $row) {
                    $errors[] = '#' . (int)$row['id'] . ' ' . $e->getMessage();
                }
            }
        }
        $movedIds = $this->cleanIds($movedIds);
        if ($movedIds) {
            $ph = implode(',', array_fill(0, count($movedIds), '?'));
            $stmt = $this->stmt("UPDATE mailbox_messages SET folder_key='trash' WHERE id IN (" . $ph . ")", str_repeat('i', count($movedIds)), $movedIds);
            mysqli_stmt_close($stmt);
        }
        if (!$movedIds && $errors) {
            throw new RuntimeException('Delete failed: ' . implode(' / ', array_slice($errors, 0, 3)));
        }
    }

    public function deleteForever(array $rowIds)
    {
        $ids = $this->cleanIds($rowIds);
        if (!$ids) {
            return;
        }
        $rows = $this->getMessagesByIds($ids);
        $groups = $this->groupRows($rows);
        foreach ($groups as $folderKey => $uids) {
            if (!isset($this->folderMap[$folderKey])) {
                continue;
            }
            $client = $this->openClient();
            try {
                $folderName = $this->resolveFolderName($client, $folderKey);
                $folder = $this->ensureFolder($folderKey, $folderName);
                $client->select($folder['imap_name']);
                $client->uidStore(implode(',', $uids), '+FLAGS.SILENT', '\\Deleted');
                $client->expunge();
                $client->logout();
            } catch (Exception $e) {
                $client->logout();
                throw $e;
            }
        }
        $this->deleteLocalMessages($ids);
    }

    private function resolveTrashFolder(ImapClient $client)
    {
        return $this->resolveFolderName($client, 'trash');
    }

    public function resolveFolderName(ImapClient $client, $folderKey)
    {
        $configured = isset($this->folderMap[$folderKey]) ? $this->folderMap[$folderKey] : 'INBOX';
        if ($folderKey === 'inbox') {
            return 'INBOX';
        }
        $folders = $client->listFolderDetails();
        $wantedAttr = '';
        if ($folderKey === 'sent') {
            $wantedAttr = '\\Sent';
        } elseif ($folderKey === 'trash') {
            $wantedAttr = '\\Trash';
        }
        if ($wantedAttr !== '') {
            foreach ($folders as $folder) {
                foreach ($folder['attrs'] as $attr) {
                    if (strcasecmp($attr, $wantedAttr) === 0) {
                        $this->ensureFolder($folderKey, $folder['name']);
                        return $folder['name'];
                    }
                }
            }
        }

        $candidates = $this->folderCandidates($folderKey, $configured);
        foreach ($folders as $folder) {
            $name = strtolower($folder['name']);
            if (in_array($name, $candidates, true)) {
                $this->ensureFolder($folderKey, $folder['name']);
                return $folder['name'];
            }
            foreach ($candidates as $candidate) {
                if ($candidate !== '' && substr($name, -strlen($candidate)) === $candidate) {
                    $this->ensureFolder($folderKey, $folder['name']);
                    return $folder['name'];
                }
            }
        }
        return $configured;
    }

    private function folderCandidates($folderKey, $configured)
    {
        if ($folderKey === 'sent') {
            return array(
                strtolower($configured),
                '[gmail]/sent mail',
                '[google mail]/sent mail',
                '[gmail]/sent',
                '[google mail]/sent',
                'sent mail',
                'sent',
                'sent messages',
                'sent items'
            );
        }
        if ($folderKey === 'trash') {
            return array(
                strtolower($configured),
                '[gmail]/trash',
                '[google mail]/trash',
                '[gmail]/bin',
                '[google mail]/bin',
                'trash',
                'bin',
                'deleted messages',
                'deleted items'
            );
        }
        return array(strtolower($configured));
    }

    // Gmail "전체보관함"(All Mail) 폴더 이름을 찾는다. 보관(archive)된 메일은 INBOX 등
    // 개별 폴더에서 빠지고 전체보관함에만 남으므로, Message-ID 로 본문을 복구할 때 사용한다.
    private function findAllMailFolder(ImapClient $client)
    {
        $folders = $client->listFolderDetails();
        foreach ($folders as $folder) {
            if (empty($folder['attrs'])) {
                continue;
            }
            foreach ($folder['attrs'] as $attr) {
                if (strcasecmp($attr, '\\All') === 0) {
                    return $folder['name'];
                }
            }
        }
        $candidates = array(
            '[gmail]/all mail', '[google mail]/all mail',
            '[gmail]/all', '[google mail]/all',
            'all mail', 'all',
            '[gmail]/전체보관함', '[google mail]/전체보관함', '전체보관함',
        );
        foreach ($folders as $folder) {
            $name = strtolower((string)$folder['name']);
            if (in_array($name, $candidates, true)) {
                return $folder['name'];
            }
            foreach ($candidates as $candidate) {
                if ($candidate !== '' && substr($name, -strlen($candidate)) === $candidate) {
                    return $folder['name'];
                }
            }
        }
        return '';
    }

    private function openClient()
    {
        $client = new ImapClient($this->account['imap_host'], (int)$this->account['imap_port']);
        $client->connect();
        $client->login($this->account['email'], $this->account['app_password']);
        return $client;
    }

    private function saveHeaderRow($folderKey, $uid, array $fetch)
    {
        $headers = MimeParser::parseHeaders(isset($fetch['HEADER']) ? $fetch['HEADER'] : '');
        $from = MimeParser::parseAddressList(isset($headers['from']) ? $headers['from'] : '');
        $fromFirst = isset($from[0]) ? $from[0] : array('name' => '', 'email' => '');
        $to = MimeParser::parseAddressList(isset($headers['to']) ? $headers['to'] : '');
        $cc = MimeParser::parseAddressList(isset($headers['cc']) ? $headers['cc'] : '');
        $subject = MimeParser::decodeHeader(isset($headers['subject']) ? $headers['subject'] : '');
        $date = isset($headers['date']) ? strtotime($headers['date']) : false;
        $mailDate = $date ? date('Y-m-d H:i:s', $date) : null;
        $messageId = isset($headers['message-id']) ? trim($headers['message-id']) : '';
        $inReplyTo = isset($headers['in-reply-to']) ? trim($headers['in-reply-to']) : '';
        $threadId = isset($fetch['X-GM-THRID']) ? (string)$fetch['X-GM-THRID'] : '';
        $isRead = (isset($fetch['FLAGS']) && stripos($fetch['FLAGS'], '\\Seen') !== false) ? 1 : 0;
        $hasAttach = MimeParser::hasAttachmentFromBodyStructure(isset($fetch['BODYSTRUCTURE']) ? $fetch['BODYSTRUCTURE'] : '') ? 1 : 0;
        $size = isset($fetch['RFC822.SIZE']) ? (int)$fetch['RFC822.SIZE'] : 0;

        $toJson = json_encode($to);
        $ccJson = json_encode($cc);
        $snippet = isset($fetch['BODY']) ? MimeParser::makePreviewSnippet($fetch['BODY']) : '';
        $sql = "INSERT INTO mailbox_messages
            (account_id, folder_key, uid, thread_id, message_id, in_reply_to, from_name, from_email, to_addr, cc_addr, subject, mail_date, snippet, body_fetched, is_read, has_attachment, msg_size, synced_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
              thread_id=VALUES(thread_id), message_id=VALUES(message_id), in_reply_to=VALUES(in_reply_to), from_name=VALUES(from_name), from_email=VALUES(from_email),
              to_addr=VALUES(to_addr), cc_addr=VALUES(cc_addr), subject=VALUES(subject), mail_date=VALUES(mail_date),
              is_read=VALUES(is_read), has_attachment=VALUES(has_attachment), msg_size=VALUES(msg_size), synced_at=NOW()";
        $stmt = $this->stmt($sql, 'isissssssssssiii', array(
            (int)$this->account['id'], $folderKey, $uid, $threadId, $messageId, $inReplyTo,
            $fromFirst['name'], $fromFirst['email'], $toJson, $ccJson, $subject,
            $mailDate, $snippet, $isRead, $hasAttach, $size
        ));
        mysqli_stmt_close($stmt);
    }

    private function syncRecentFlags(ImapClient $client, $folderKey)
    {
        $row = $this->fetchOne($this->stmt("SELECT MIN(uid) AS min_uid FROM (SELECT uid FROM mailbox_messages WHERE account_id=? AND folder_key=? ORDER BY uid DESC LIMIT 500) x", 'is', array((int)$this->account['id'], $folderKey)));
        $minUid = isset($row['min_uid']) ? (int)$row['min_uid'] : 0;
        if ($minUid <= 0) {
            return;
        }
        $flags = $client->uidFetch($minUid . ':*', '(FLAGS)');
        $seen = array();
        $serverUids = array();
        foreach ($flags as $uid => $fetch) {
            $serverUids[(int)$uid] = true;
            if (isset($fetch['FLAGS']) && stripos($fetch['FLAGS'], '\\Seen') !== false) {
                $seen[] = (int)$uid;
            }
        }

        // Reconcile: drop local messages no longer present in this server folder
        // (e.g. Gmail moved them to Spam, archived, or they were deleted elsewhere),
        // so the received list only shows mail that is still in the folder.
        $localRows = $this->fetchAll($this->stmt("SELECT id, uid FROM mailbox_messages WHERE account_id=? AND folder_key=? AND uid>=?", 'isi', array((int)$this->account['id'], $folderKey, $minUid)));
        $goneIds = array();
        foreach ($localRows as $localRow) {
            if (!isset($serverUids[(int)$localRow['uid']])) {
                $goneIds[] = (int)$localRow['id'];
            }
        }
        if ($goneIds) {
            $this->deleteLocalMessages($goneIds);
        }

        $stmt = $this->stmt("UPDATE mailbox_messages SET is_read=0 WHERE account_id=? AND folder_key=? AND uid>=?", 'isi', array((int)$this->account['id'], $folderKey, $minUid));
        mysqli_stmt_close($stmt);
        if ($seen) {
            $ph = implode(',', array_fill(0, count($seen), '?'));
            $params = array_merge(array((int)$this->account['id'], $folderKey), $seen);
            $stmt = $this->stmt("UPDATE mailbox_messages SET is_read=1 WHERE account_id=? AND folder_key=? AND uid IN (" . $ph . ")", 'is' . str_repeat('i', count($seen)), $params);
            mysqli_stmt_close($stmt);
        }
    }

    private function syncMissingThreadIds(ImapClient $client, $folderKey)
    {
        // X-GM-THRID 기반 대화 보강은 Gmail 에서만. 다른 서버에 보내면 FETCH 가 실패한다.
        if (!$this->isGmail) {
            return;
        }
        $rows = $this->fetchAll($this->stmt("SELECT id, uid FROM mailbox_messages WHERE account_id=? AND folder_key=? AND thread_id='' ORDER BY uid DESC LIMIT 200", 'is', array((int)$this->account['id'], $folderKey)));
        if (!$rows) {
            return;
        }
        $uidMap = array();
        foreach ($rows as $row) {
            $uid = (int)$row['uid'];
            if ($uid > 0) {
                $uidMap[$uid] = (int)$row['id'];
            }
        }
        foreach (array_chunk(array_keys($uidMap), 50) as $chunk) {
            try {
                $items = $client->uidFetch(implode(',', $chunk), '(X-GM-THRID)');
            } catch (Exception $e) {
                continue;
            }
            foreach ($items as $uid => $fetch) {
                $threadId = isset($fetch['X-GM-THRID']) ? (string)$fetch['X-GM-THRID'] : '';
                if ($threadId === '' || !isset($uidMap[(int)$uid])) {
                    continue;
                }
                $stmt = $this->stmt("UPDATE mailbox_messages SET thread_id=? WHERE id=?", 'si', array($threadId, $uidMap[(int)$uid]));
                mysqli_stmt_close($stmt);
            }
        }
    }

    private function enforceInboxLimit()
    {
        $limit = defined('MBX_INBOX_MAX_MESSAGES') ? (int)MBX_INBOX_MAX_MESSAGES : 5000;
        if ($limit <= 0) {
            return;
        }
        $row = $this->fetchOne($this->stmt("SELECT COUNT(*) AS c FROM mailbox_messages WHERE account_id=? AND folder_key='inbox'", 'i', array((int)$this->account['id'])));
        if (!$row || (int)$row['c'] <= $limit) {
            return;
        }
        $sql = "SELECT id FROM mailbox_messages WHERE account_id=? AND folder_key='inbox' ORDER BY uid DESC LIMIT " . $limit . ", 18446744073709551615";
        $rows = $this->fetchAll($this->stmt($sql, 'i', array((int)$this->account['id'])));
        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (int)$r['id'];
        }
        if ($ids) {
            $this->deleteLocalMessages($ids);
        }
    }

    private function syncMissingPreviews(ImapClient $client, $folderKey)
    {
        $rows = $this->fetchAll($this->stmt("SELECT id, uid FROM mailbox_messages WHERE account_id=? AND folder_key=? AND body_fetched=0 AND snippet='' ORDER BY uid DESC LIMIT 50", 'is', array((int)$this->account['id'], $folderKey)));
        if (!$rows) {
            return;
        }
        $uidMap = array();
        foreach ($rows as $row) {
            $uidMap[(int)$row['uid']] = (int)$row['id'];
        }
        $chunks = array_chunk(array_keys($uidMap), 50);
        foreach ($chunks as $chunk) {
            $fetched = $client->uidFetch(implode(',', $chunk), '(BODY.PEEK[TEXT]<0.4096>)');
            foreach ($fetched as $uid => $fetch) {
                if (!isset($uidMap[(int)$uid]) || !isset($fetch['BODY'])) {
                    continue;
                }
                $snippet = MimeParser::makePreviewSnippet($fetch['BODY']);
                if ($snippet === '') {
                    continue;
                }
                $stmt = $this->stmt("UPDATE mailbox_messages SET snippet=? WHERE id=?", 'si', array($snippet, $uidMap[(int)$uid]));
                mysqli_stmt_close($stmt);
            }
        }
    }

    private function localMaxUid($folderKey)
    {
        $row = $this->fetchOne($this->stmt("SELECT MAX(uid) AS max_uid FROM mailbox_messages WHERE account_id=? AND folder_key=?", 'is', array((int)$this->account['id'], $folderKey)));
        return isset($row['max_uid']) ? (int)$row['max_uid'] : 0;
    }

    private function ensureFolder($folderKey, $imapName)
    {
        $row = $this->fetchOne($this->stmt("SELECT * FROM mailbox_folders WHERE account_id=? AND folder_key=?", 'is', array((int)$this->account['id'], $folderKey)));
        if ($row) {
            if ($row['imap_name'] !== $imapName) {
                $stmt = $this->stmt("UPDATE mailbox_folders SET imap_name=? WHERE id=?", 'si', array($imapName, (int)$row['id']));
                mysqli_stmt_close($stmt);
                $row['imap_name'] = $imapName;
            }
            return $row;
        }
        $stmt = $this->stmt("INSERT INTO mailbox_folders (account_id, folder_key, imap_name) VALUES (?, ?, ?)", 'iss', array((int)$this->account['id'], $folderKey, $imapName));
        mysqli_stmt_close($stmt);
        return $this->fetchOne($this->stmt("SELECT * FROM mailbox_folders WHERE account_id=? AND folder_key=?", 'is', array((int)$this->account['id'], $folderKey)));
    }

    private function resetFolder($folderKey)
    {
        $rows = $this->fetchAll($this->stmt("SELECT id FROM mailbox_messages WHERE account_id=? AND folder_key=?", 'is', array((int)$this->account['id'], $folderKey)));
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
        }
        if ($ids) {
            $this->deleteLocalMessages($ids);
        }
        $stmt = $this->stmt("UPDATE mailbox_folders SET uidvalidity=0,last_uid=0,last_sync=NULL WHERE account_id=? AND folder_key=?", 'is', array((int)$this->account['id'], $folderKey));
        mysqli_stmt_close($stmt);
    }

    private function updateFolderState($folderKey, $uidvalidity, $lastUid)
    {
        $stmt = $this->stmt("UPDATE mailbox_folders SET uidvalidity=?, last_uid=?, last_sync=NOW() WHERE account_id=? AND folder_key=?", 'iiis', array((int)$uidvalidity, (int)$lastUid, (int)$this->account['id'], $folderKey));
        mysqli_stmt_close($stmt);
    }

    private function insertAttachment($msgId, array $att)
    {
        $partNo = isset($att['part_no']) ? $att['part_no'] : '';
        $filename = isset($att['filename']) ? $att['filename'] : '';
        $mime = isset($att['mime_type']) ? $att['mime_type'] : '';
        $size = isset($att['size']) ? (int)$att['size'] : 0;
        $cid = isset($att['content_id']) ? $att['content_id'] : '';
        $stmt = $this->stmt("INSERT INTO mailbox_attachments (msg_id, part_no, filename, mime_type, size_bytes, content_id) VALUES (?, ?, ?, ?, ?, ?)", 'isssis', array((int)$msgId, $partNo, $filename, $mime, $size, $cid));
        mysqli_stmt_close($stmt);
    }

    private function getMessage($id)
    {
        return $this->fetchOne($this->stmt("SELECT * FROM mailbox_messages WHERE id=? AND account_id=?", 'ii', array((int)$id, (int)$this->account['id'])));
    }

    private function getMessagesByIds(array $ids)
    {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge(array((int)$this->account['id']), $ids);
        return $this->fetchAll($this->stmt("SELECT * FROM mailbox_messages WHERE account_id=? AND id IN (" . $ph . ")", 'i' . str_repeat('i', count($ids)), $params));
    }

    private function deleteLocalMessages(array $ids)
    {
        $ids = $this->cleanIds($ids);
        if (!$ids) {
            return;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->stmt("DELETE FROM mailbox_attachments WHERE msg_id IN (" . $ph . ")", str_repeat('i', count($ids)), $ids);
        mysqli_stmt_close($stmt);
        $stmt = $this->stmt("DELETE FROM mailbox_messages WHERE id IN (" . $ph . ")", str_repeat('i', count($ids)), $ids);
        mysqli_stmt_close($stmt);
    }

    private function groupRows(array $rows)
    {
        $groups = array();
        foreach ($rows as $row) {
            $key = $row['folder_key'];
            if (!isset($groups[$key])) {
                $groups[$key] = array();
            }
            $groups[$key][] = (int)$row['uid'];
        }
        return $groups;
    }

    private function groupFullRows(array $rows)
    {
        $groups = array();
        foreach ($rows as $row) {
            $key = $row['folder_key'];
            if (!isset($groups[$key])) {
                $groups[$key] = array();
            }
            $groups[$key][] = $row;
        }
        return $groups;
    }

    private function resolveCurrentUid(ImapClient $client, array $row)
    {
        $uid = isset($row['uid']) ? (int)$row['uid'] : 0;
        if ($uid > 0) {
            try {
                $fetched = $client->uidFetch((string)$uid, '(FLAGS)');
                if (!empty($fetched)) {
                    return $uid;
                }
            } catch (Exception $e) {
            }
        }
        $messageId = isset($row['message_id']) ? trim((string)$row['message_id']) : '';
        if ($messageId !== '') {
            $criteria = 'HEADER MESSAGE-ID "' . addcslashes($messageId, '"\\') . '"';
            $found = $client->uidSearch($criteria);
            if ($found) {
                $uid = (int)end($found);
                if ($uid > 0 && isset($row['id'])) {
                    $stmt = $this->stmt("UPDATE mailbox_messages SET uid=? WHERE id=? AND account_id=?", 'iii', array($uid, (int)$row['id'], (int)$this->account['id']));
                    mysqli_stmt_close($stmt);
                }
                return $uid;
            }
        }
        return 0;
    }

    private function cleanIds(array $rowIds)
    {
        $ids = array();
        foreach ($rowIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    private function acquireLock($name)
    {
        $row = $this->fetchOne($this->stmt("SELECT GET_LOCK(?, 0) AS got_lock", 's', array($name)));
        return $row && (int)$row['got_lock'] === 1;
    }

    private function releaseLock($name)
    {
        try {
            $stmt = $this->stmt("SELECT RELEASE_LOCK(?)", 's', array($name));
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
        }
    }

    private function stmt($sql, $types = '', array $params = array())
    {
        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            throw new RuntimeException(mysqli_error($this->db));
        }
        if ($types !== '') {
            $refs = array($types);
            foreach ($params as $k => $v) {
                $refs[] = &$params[$k];
            }
            call_user_func_array(array($stmt, 'bind_param'), $refs);
        }
        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException($err);
        }
        return $stmt;
    }

    private function fetchOne(mysqli_stmt $stmt)
    {
        $rows = $this->fetchAll($stmt);
        return isset($rows[0]) ? $rows[0] : null;
    }

    private function fetchAll(mysqli_stmt $stmt)
    {
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }
}
?>
