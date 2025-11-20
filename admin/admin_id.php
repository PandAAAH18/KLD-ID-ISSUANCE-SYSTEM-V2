<?php
session_start();
require_once __DIR__.'/classes/IdManager.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type']!=='admin'){
    header('Location: ../index.php'); exit();
}

$adm = new IdManager();

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

/* ---------- BULK ACTIONS ---------- */
if (isset($_POST['bulk_approve']) && isset($_POST['request_ids'])){
    foreach ($_POST['request_ids'] as $requestId) {
        $adm->setRequestStatus((int)$requestId, 'approved');
    }
    header('Location: admin_id.php?filter=pending');
    exit();
}

if (isset($_POST['bulk_generate_ids']) && isset($_POST['request_ids'])){
    $result = $adm->bulkGenerateIds($_POST['request_ids']);
    
    // Store result in session for display
    $_SESSION['bulk_operation_result'] = $result;
    header('Location: admin_id.php?filter=approved');
    exit();
}

if (isset($_POST['bulk_print_ids']) && isset($_POST['student_ids'])) {
    $layout = $_POST['print_layout'] ?? '2x2';
    $result = $adm->generateBulkIdPrint($_POST['student_ids'], $layout);
    
    if ($result['success']) {
        $_SESSION['bulk_print_result'] = $result;
        header('Location: admin_id.php?filter=generated&bulk_print=' . $result['filename']);
    } else {
        $_SESSION['bulk_operation_result'] = [
            'success_count' => 0,
            'errors' => [$result['message']]
        ];
    }
    exit();
}

/* ---------- 2.  filter ---------- */
$filter = $_GET['filter'] ?? 'pending';
$filter = in_array($filter,['pending','approved','rejected','generated'],true) ? $filter : 'pending';

/* requests list only for pending/approved/rejected */
$requests = in_array($filter,['pending','approved','rejected']) ? $adm->getRequestsByStatus($filter) : [];

/* issued list for generated/completed */
$issued   = in_array($filter,['generated']) ? $adm->getIssuedByStatus($filter) : [];

/* Get approved requests for bulk operations */
$approvedRequests = ($filter === 'approved') ? $adm->getApprovedIdRequests() : [];

/* Display bulk operation results if available */
$bulkResult = null;
if (isset($_SESSION['bulk_operation_result'])) {
    $bulkResult = $_SESSION['bulk_operation_result'];
    unset($_SESSION['bulk_operation_result']);
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>ID Management</title>
    <style>
        .bulk-section {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .bulk-actions {
            margin: 10px 0;
        }
        .result-success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        .result-error {
            color: #721c24;
            background: #f8d7da;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        .select-all-cell {
            width: 30px;
        }
    </style>
</head>

<body>

    <h2>ID Requests / Issued Cards</h2>

    <!-- Display bulk operation results -->
    <?php if ($bulkResult): ?>
        <?php if ($bulkResult['success_count'] > 0): ?>
            <div class="result-success">
                ✅ Successfully generated <?= $bulkResult['success_count'] ?> ID(s) out of <?= $bulkResult['total_processed'] ?> processed.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($bulkResult['errors'])): ?>
            <div class="result-error">
                ❌ Errors encountered:
                <ul>
                    <?php foreach ($bulkResult['errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- filter links -->
    <p>
        <b>Filter:</b>
        <a href="?filter=pending" <?=($filter==='pending'   ?'style="font-weight:bold;"':'')?>>Pending</a> |
        <a href="?filter=approved" <?=($filter==='approved'  ?'style="font-weight:bold;"':'')?>>Approved</a> |
        <a href="?filter=rejected" <?=($filter==='rejected'  ?'style="font-weight:bold;"':'')?>>Rejected</a> |
        <a href="?filter=generated" <?=($filter==='generated' ?'style="font-weight:bold;"':'')?>>Generated</a> |
    </p>

    <!-- BULK ACTIONS SECTION FOR APPROVED REQUESTS -->
    <?php if ($filter === 'approved' && !empty($approvedRequests)): ?>
    <div class="bulk-section">
        <h3>Bulk Operations</h3>
        <form method="post" id="bulkForm">
            <div class="bulk-actions">
                <button type="submit" name="bulk_generate_ids" onclick="return confirm('Generate IDs for all selected requests?')">
                    🚀 Generate Selected IDs
                </button>
                <span id="selectedCount">0 selected</span>
            </div>
            
            <table border="1" cellpadding="6">
                <tr>
                    <th class="select-all-cell">
                        <input type="checkbox" id="selectAllApproved">
                    </th>
                    <th>Req-ID</th>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Requested</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($approvedRequests as $r): 
                    $name = htmlspecialchars($r['first_name'].' '.$r['last_name']);
                ?>
                <tr>
                    <td>
                        <input type="checkbox" name="request_ids[]" value="<?= $r['id'] ?>" class="request-checkbox">
                    </td>
                    <td><?= $r['id'] ?></td>
                    <td><?= $name ?></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td><?= htmlspecialchars($r['course'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($r['year_level'] ?? 'N/A') ?></td>
                    <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <button name="generate_id">Generate Single ID</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </form>
    </div>
    <?php endif; ?>

    <!-- BULK APPROVE FOR PENDING REQUESTS -->
    <?php if ($filter === 'pending' && !empty($requests)): ?>
    <div class="bulk-section">
        <h3>Bulk Approve</h3>
        <form method="post">
            <button type="submit" name="bulk_approve" onclick="return confirm('Approve all selected pending requests?')">
                ✅ Approve Selected
            </button>
            
            <table border="1" cellpadding="6">
                <tr>
                    <th class="select-all-cell">
                        <input type="checkbox" id="selectAllPending">
                    </th>
                    <th>Req-ID</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Requested</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($requests as $r):
                    $name = htmlspecialchars($r['first_name'].' '.$r['last_name']);
                ?>
                <tr>
                    <td>
                        <input type="checkbox" name="request_ids[]" value="<?= $r['id'] ?>" class="pending-checkbox">
                    </td>
                    <td><?= $r['id'] ?></td>
                    <td><?= $name ?></td>
                    <td><?= htmlspecialchars($r['request_type']) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['reason'])) ?></td>
                    <td><?= $r['created_at'] ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <button name="approve">Approve</button>
                            <button name="reject" onclick="return confirm('Reject request?');">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </form>
    </div>
    <?php endif; ?>

    <!-- ----------- REQUESTS (approved / rejected) ----------- -->
    <?php if (in_array($filter,['pending','approved','rejected']) && empty($requests)): ?>
        <p>No <?= $filter ?> requests found.</p>
    <?php endif; ?>

    <!-- ----------- ISSUED (generated / completed) ----------- -->
<?php if (in_array($filter,['generated'])): ?>
<h3>Issued Cards</h3>
<?php if (empty($issued)): ?>
    <p>No generated IDs found.</p>
<?php else: ?>
<table border="1" cellpadding="6">
    <tr>
        <th>ID Number</th>
        <th>Student</th>
        <th>Issue Date</th>
        <th>Expiry</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php foreach ($issued as $row):
        $name = htmlspecialchars($row['first_name'].' '.$row['last_name']);
        // Use the actual ID field that exists - either 'id' or 'id_number'
        $issuedId = $row['id'] ?? $row['id_number'] ?? 'N/A';
    ?>
    <tr>
        <td><?= htmlspecialchars($row['id_number']) ?></td>
        <td><?= $name ?></td>
        <td><?= $row['issue_date'] ?></td>
        <td><?= $row['expiry_date'] ?></td>
        <td><?= $row['status'] ?></td>
        <td>
            <?php
            if ($row['status']==='generated'):
                $file = $row['digital_id_file'] ?? null;
                if ($file && file_exists(__DIR__.'/../uploads/digital_id/'.$file)):
                    $safe   = htmlspecialchars($file);
                    $jsSafe = addslashes($safe);

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
<?php endif; ?>

    <script>
        // Select All functionality for approved requests
        document.addEventListener('DOMContentLoaded', function() {
            // Approved requests select all
            const selectAllApproved = document.getElementById('selectAllApproved');
            if (selectAllApproved) {
                selectAllApproved.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.request-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateSelectedCount();
                });
            }

            // Pending requests select all
            const selectAllPending = document.getElementById('selectAllPending');
            if (selectAllPending) {
                selectAllPending.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.pending-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Update selected count for approved requests
            function updateSelectedCount() {
                const checkboxes = document.querySelectorAll('.request-checkbox');
                const checked = document.querySelectorAll('.request-checkbox:checked');
                const countElement = document.getElementById('selectedCount');
                if (countElement) {
                    countElement.textContent = `${checked.length} selected`;
                }
            }

            // Add event listeners to all checkboxes
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            // Initial count update
            updateSelectedCount();
        });

        // Confirm bulk generation
        function confirmBulkGeneration() {
            const checked = document.querySelectorAll('.request-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one request to generate IDs.');
                return false;
            }
            return confirm(`Generate IDs for ${checked.length} selected students?`);
        }

        // Add confirmation to bulk generate button
        const bulkGenerateBtn = document.querySelector('button[name="bulk_generate_ids"]');
        if (bulkGenerateBtn) {
            bulkGenerateBtn.addEventListener('click', function(e) {
                if (!confirmBulkGeneration()) {
                    e.preventDefault();
                }
            });
        }
    </script>

</body>

</html>