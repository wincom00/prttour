<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

mbx_require_api_auth();

function mbx_api_group_list_threads(array $rows)
{
    $groups = array();
    $out = array();
    foreach ($rows as $row) {
        $threadId = isset($row['thread_id']) ? trim((string)$row['thread_id']) : '';
        $key = $threadId !== '' ? 'thread|' . $threadId : 'row|' . (int)$row['id'];
        if (!isset($groups[$key])) {
            $row['_thread_count'] = 1;
            $groups[$key] = count($out);
            $out[] = $row;
        } else {
            $idx = $groups[$key];
            $out[$idx]['_thread_count']++;
            if ((int)$row['is_read'] === 0) {
                $out[$idx]['is_read'] = 0;
            }
            if ((int)$row['has_attachment'] === 1) {
                $out[$idx]['has_attachment'] = 1;
            }
        }
    }
    return $out;
}

function mbx_api_list_row_html(array $row, $folder)
{
    $noSubject = json_decode('"\\uc81c\\ubaa9 \\uc5c6\\uc74c"', true);
    $newLabel = json_decode('"\\uc0c8\\uba54\\uc77c"', true);
    $to = json_decode($row['to_addr'], true);
    $toLabel = isset($to[0]['email']) ? $to[0]['email'] : '';
    $previewSource = ($row['body_html'] !== null && $row['body_html'] !== '') ? $row['body_html'] : (string)$row['body_text'];
    $snippet = $previewSource !== '' ? MimeParser::cleanPreviewText($previewSource) : MimeParser::cleanPreviewText((string)$row['snippet']);
    $senderLabel = $folder === 'sent' ? $toLabel : ($row['from_name'] !== '' ? $row['from_name'] : $row['from_email']);
    $threadCount = isset($row['_thread_count']) ? (int)$row['_thread_count'] : 1;

    ob_start();
    ?>
          <tr class="mbx-row <?php echo (int)$row['is_read'] ? '' : 'mbx-unread'; ?>" data-id="<?php echo (int)$row['id']; ?>" data-url="<?php echo mbx_h(mbx_plugin_url('view.php?id=' . (int)$row['id'])); ?>">
            <td><input type="checkbox" class="chk" value="<?php echo (int)$row['id']; ?>"></td>
            <td><?php echo (int)$row['has_attachment'] ? '<i class="fa fa-paperclip"></i>' : ''; ?></td>
            <td><?php echo mbx_h($senderLabel); ?><?php if ($threadCount > 1): ?><span class="mbx-thread-count">(<?php echo $threadCount; ?>)</span><?php endif; ?></td>
            <td><span class="mbx-subject"><?php echo mbx_h($row['subject'] !== '' ? $row['subject'] : '(' . $noSubject . ')'); ?><?php if (!(int)$row['is_read']): ?><span class="mbx-badge-new"><?php echo mbx_h($newLabel); ?></span><?php endif; ?></span><?php if ($snippet !== ''): ?><span class="mbx-snippet"><?php echo mbx_h($snippet); ?></span><?php endif; ?></td>
            <td><?php echo mbx_h(mbx_date_label($row['mail_date'])); ?></td>
            <td><?php echo mbx_h(mbx_size($row['msg_size'])); ?></td>
          </tr>
    <?php
    return ob_get_clean();
}

function mbx_api_pagination_html($folder, $page, $totalPages, $search)
{
    if ($totalPages <= 1) {
        return '';
    }
    ob_start();
    ?>
        <ul class="pagination mbx-pagination">
          <?php for ($i = max(1, $page - 5); $i <= min($totalPages, $page + 5); $i++): ?><li class="<?php echo $i === $page ? 'active' : ''; ?>"><a href="<?php echo mbx_h(mbx_plugin_url('index.php?folder=' . urlencode($folder) . '&page=' . $i . '&search=' . urlencode($search))); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
        </ul>
    <?php
    return ob_get_clean();
}

try {
    $db = mbx_db();
    MailboxSync::ensureTables($db);
    $account = mbx_current_account($db);
    if (!$account) {
        mbx_json(array('status' => 'error', 'message' => 'No mailbox account.'), 400);
    }

    $folderRows = mbx_account_folders($db, (int)$account['id'], true);
    $defaultFolder = isset($folderRows[0]['folder_key']) ? (string)$folderRows[0]['folder_key'] : 'inbox';
    $folder = isset($_GET['folder']) ? (string)$_GET['folder'] : $defaultFolder;
    if (!mbx_folder_allowed($db, $account, $folder, true)) {
        $folder = $defaultFolder;
    }
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $where = 'account_id=? AND folder_key=?';
    $types = 'is';
    $params = array((int)$account['id'], $folder);
    if ($search !== '') {
        $where .= ' AND (subject LIKE ? OR from_email LIKE ? OR from_name LIKE ? OR snippet LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'ssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $countRow = mbx_fetch_one_stmt(mbx_stmt($db, 'SELECT COUNT(*) AS c FROM mailbox_messages WHERE ' . $where, $types, $params));
    $totalRows = isset($countRow['c']) ? (int)$countRow['c'] : 0;
    $totalPages = max(1, (int)ceil($totalRows / $limit));
    $rows = mbx_fetch_all_stmt(mbx_stmt($db, 'SELECT * FROM mailbox_messages WHERE ' . $where . ' ORDER BY mail_date DESC, uid DESC LIMIT ' . (int)$offset . ', ' . (int)$limit, $types, $params));
    $rows = mbx_api_group_list_threads($rows);

    $html = '';
    $ids = array();
    foreach ($rows as $row) {
        $ids[] = (int)$row['id'];
        $html .= mbx_api_list_row_html($row, $folder);
    }
    if (!$rows) {
        $noMail = json_decode('"\\uba54\\uc77c\\uc774 \\uc5c6\\uc2b5\\ub2c8\\ub2e4."', true);
        $html = '<tr><td colspan="6" class="text-center">' . mbx_h($noMail) . '</td></tr>';
    }

    $unread = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT COUNT(*) AS c FROM mailbox_messages WHERE account_id=? AND folder_key='inbox' AND is_read=0", 'i', array((int)$account['id'])));
    mbx_json(array(
        'status' => 'success',
        'rows_html' => $html,
        'pagination_html' => mbx_api_pagination_html($folder, $page, $totalPages, $search),
        'ids' => $ids,
        'total_rows' => $totalRows,
        'total_pages' => $totalPages,
        'unread_inbox' => isset($unread['c']) ? (int)$unread['c'] : 0,
    ));
} catch (Throwable $e) {
    mbx_json(array('status' => 'error', 'message' => $e->getMessage()), 200);
}
?>
