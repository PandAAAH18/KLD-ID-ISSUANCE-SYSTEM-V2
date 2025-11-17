<?php
session_start();
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/admin.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type']!=='admin'){
    header('Location: ../login.php'); exit();
}

$adm = new Admin();

/* ---------- 1.  actions ---------- */
if (isset($_POST['approve']) && isset($_POST['request_id'])){
    $adm->setRequestStatus((int)$_POST['request_id'],'approved');
    header('Location: admin_id.php'); exit();
}
if (isset($_POST['reject']) && isset($_POST['request_id'])){
    $adm->setRequestStatus((int)$_POST['request_id'],'rejected');
    header('Location: admin_id.php'); exit();
}
if (isset($_POST['generate_id']) && isset($_POST['request_id'])){
    $adm->generateId((int)$_POST['request_id']);
    header('Location: admin_id.php'); exit();
}
if (isset($_POST['mark_complete']) && isset($_POST['issued_id'])){
    $adm->markIdComplete((int)$_POST['issued_id']);
    header('Location: admin_id.php'); exit();
}
if (isset($_POST['regenerate']) && isset($_POST['issued_id'])){
    $adm->regenerateId((int)$_POST['issued_id']);
    header('Location: admin_id.php?filter=generated');
    exit();
}

/* ---------- 2.  filter ---------- */
$filter = $_GET['filter'] ?? 'pending';
$filter = in_array($filter,['pending','approved','rejected','generated'],true) ? $filter : 'pending';

/* requests list only for pending/approved/rejected */
$requests = in_array($filter,['pending','approved','rejected']) ? $adm->getRequestsByStatus($filter) : [];

/* issued list for generated/completed */
$issued   = in_array($filter,['generated']) ? $adm->getIssuedByStatus($filter) : [];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>ID Management</title>
</head>

<body>

    <h2>ID Requests / Issued Cards</h2>

    <!-- filter links -->
    <p>
        <b>Filter:</b>
        <a href="?filter=pending" <?=($filter==='pending'   ?'style="font-weight:bold;"':'')?>>Pending</a> |
        <a href="?filter=approved" <?=($filter==='approved'  ?'style="font-weight:bold;"':'')?>>Approved</a> |
        <a href="?filter=rejected" <?=($filter==='rejected'  ?'style="font-weight:bold;"':'')?>>Rejected</a> |
        <a href="?filter=generated" <?=($filter==='generated' ?'style="font-weight:bold;"':'')?>>Generated</a> |
    </p>

    <!-- ----------- REQUESTS (approved / rejected) ----------- -->
    <?php if (in_array($filter,['pending','approved','rejected'])): ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Req-ID</th>
            <th>Student</th>
            <th>Type</th>
            <th>Reason</th>
            <th>Requested</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($requests as $r):
          $name = htmlspecialchars($r['first_name'].' '.$r['last_name']);
    ?>
        <tr>
            <td><?=$r['id']?></td>
            <td><?=$name?></td>
            <td><?=htmlspecialchars($r['request_type'])?></td>
            <td><?=nl2br(htmlspecialchars($r['reason']))?></td>
            <td><?=$r['created_at']?></td>
            <td><?=$r['status']?></td>
            <td>
                <?php if ($r['status']==='pending'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="request_id" value="<?=$r['id']?>">
                    <button name="approve">Approve</button>
                    <button name="reject" onclick="return confirm('Reject request?');">Reject</button>
                </form>
                <?php elseif ($r['status']==='approved'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="request_id" value="<?=$r['id']?>">
                    <button name="generate_id">Generate ID</button>
                </form>
                <?php else: ?>
                <small><?=ucfirst($r['status'])?></small>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- ----------- ISSUED (generated / completed) ----------- -->
    <?php if (in_array($filter,['generated'])): ?>
    <h3>Issued Cards</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>Issued-ID</th>
            <th>ID Number</th>
            <th>Student</th>
            <th>Issue Date</th>
            <th>Expiry</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($issued as $row):
          $name = htmlspecialchars($row['first_name'].' '.$row['last_name']);
    ?>
        <tr>
            <td><?=$row['id_number']?></td>
            <td><?=htmlspecialchars($row['id_number'])?></td>
            <td><?=$name?></td>
            <td><?=$row['issue_date']?></td>
            <td><?=$row['expiry_date']?></td>
            <td><?=$row['status']?></td>
            <td>
<?php
if ($row['status']==='generated'):
    $file = $row['digital_id_file'] ?? null;
    if ($file && file_exists(__DIR__.'/../uploads/digital_id/'.$file)):
        $safe   = htmlspecialchars($file);
        $jsSafe = addslashes($safe);

        // -----  whole line in ONE echo  -----
        echo '<a href="../uploads/digital_id/'.$safe.'" target="_blank">View</a> | ';
        echo '<a href="../uploads/digital_id/'.$safe.'" download>Download</a> | ';
        echo '<button type="button" onclick="window.open(\'../uploads/digital_id/'.$jsSafe.'\', \'_blank\').print();">Print</button> | ';
        echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Regenerate ID card?\');">';
        echo   '<input type="hidden" name="issued_id" value="'.$row['id_number'].'">';
        echo   '<button name="regenerate">Regenerate</button>';
        echo '</form>';
    else:
        echo '<em>No file yet</em> | ';
        echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Regenerate ID card?\');">';
        echo   '<input type="hidden" name="issued_id" value="'.$row['id_number'].'">';
        echo   '<button name="regenerate">Regenerate</button>';
        echo '</form>';
    endif;
endif;
?>
</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

</body>

</html>