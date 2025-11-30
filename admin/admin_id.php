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
    $id_number = $_POST['issued_id']; // string id_number like '2025100001'
    error_log("REGEN DEBUG: Handler received issued_id = '{$id_number}' from POST");
    if ($adm->regenerateId($id_number)) {
        $_SESSION['success_msg'] = 'ID regenerated successfully.';
        error_log("REGEN DEBUG: regenerateId succeeded for {$id_number}");
    } else {
        $_SESSION['error_msg'] = 'Failed to regenerate ID. Check logs.';
        error_log("REGEN DEBUG: regenerateId FAILED for {$id_number}");
    }
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

/* ---------- BULK PRINT ---------- */
if (isset($_POST['bulk_print']) && isset($_POST['selected_issued_ids'])) {
    try {
        $result = $adm->bulkPrintIds($_POST['selected_issued_ids']);
        
        if ($result['success']) {
            // Store the IDs that were included in the PDF for later status update
            $_SESSION['pending_print_ids'] = $result['id_numbers'];
            $_SESSION['pending_print_filename'] = $result['filename'];
            $_SESSION['show_print_modal'] = true;
            
            // Store download URL for JavaScript to open in new tab
            $_SESSION['bulk_print_download_url'] = APP_URL . '/uploads/bulk_print/' . $result['filename'];
            
            header('Location: admin_id.php?filter=generated');
            exit();
        } else {
            $_SESSION['error_msg'] = "Bulk printing failed: " . $result['message'];
            header('Location: admin_id.php?filter=generated');
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Bulk printing failed: " . $e->getMessage();
        header('Location: admin_id.php?filter=generated');
        exit();
    }
}

/* ---------- CONFIRM PRINT COMPLETION ---------- */
if (isset($_POST['confirm_print_completion']) && isset($_POST['printed_ids'])) {
    $success = $adm->markIdsAsPrinted($_POST['printed_ids']);
    
    if ($success) {
        $_SESSION['success_msg'] = "Successfully marked " . count($_POST['printed_ids']) . " ID(s) as printed.";
        unset($_SESSION['pending_print_ids']);
        unset($_SESSION['pending_print_filename']);
    } else {
        $_SESSION['error_msg'] = "Failed to update print status. Please try again.";
    }
    
    header('Location: admin_id.php?filter=generated');
    exit();
}

/* ---------- CANCEL PRINT ---------- */
if (isset($_POST['cancel_print'])) {
    unset($_SESSION['pending_print_ids']);
    unset($_SESSION['pending_print_filename']);
    header('Location: admin_id.php?filter=generated');
    exit();
}

/* ---------- 2.  filter & pagination ---------- */
$filter = $_GET['filter'] ?? 'pending';
$filter = in_array($filter,['pending','approved','rejected','generated','printed'],true) ? $filter : 'pending';

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? max(10, min(100, intval($_GET['per_page']))) : 50;

/* requests list only for pending/approved/rejected */
$requests = in_array($filter,['pending','approved','rejected']) ? 
    $adm->getRequestsByStatusPaginated($filter, $page, $perPage) : [];

/* issued list for generated/completed */
$issued   = in_array($filter,['generated','printed']) ? 
    $adm->getIssuedByStatusPaginated($filter, $page, $perPage) : [];

// Get counts for pagination
$totalRequests = in_array($filter,['pending','approved','rejected']) ? 
    $adm->countRequestsByStatus($filter) : 0;
$totalIssued = in_array($filter,['generated','printed']) ? 
    $adm->countIssuedByStatus($filter) : 0;
$totalItems = $totalRequests + $totalIssued;
$totalPages = ceil($totalItems / $perPage);

/* Get approved requests for bulk operations */
$approvedRequests = ($filter === 'approved') ? $adm->getApprovedIdRequestsPaginated($page, $perPage) : [];
$totalApprovedRequests = ($filter === 'approved') ? $adm->countApprovedIdRequests() : 0;
$totalApprovedPages = ($filter === 'approved') ? ceil($totalApprovedRequests / $perPage) : 0;

/* Display bulk operation results if available */
$bulkResult = null;
if (isset($_SESSION['bulk_operation_result'])) {
    $bulkResult = $_SESSION['bulk_operation_result'];
    unset($_SESSION['bulk_operation_result']);
}

$success_msg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);
$error_msg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['error_msg']);
        require_once 'admin_header.php';

        // Get counts for statistics
        $pendingCount = $adm->countRequestsByStatus('pending');
$approvedCount = $adm->countRequestsByStatus('approved');
$rejectedCount = $adm->countRequestsByStatus('rejected');
$generatedCount = $adm->countIssuedByStatus('generated');
$printedCount = $adm->countIssuedByStatus('printed');
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Pagination Styles */
        .pagination {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-right: 15px;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #007bff;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            min-width: 32px;
        }

        .pagination-btn:hover:not(.disabled) {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination-btn.disabled {
            background: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .pagination-info {
            padding: 6px 12px;
            font-size: 0.8rem;
            color: #495057;
            font-weight: 500;
        }

        .results-per-page {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 15px;
        }

        .results-per-page select {
            padding: 4px 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.8rem;
        }
    </style>
</head>

<body class="admin-body">
    <!-- Back to Top Button -->
    <button class="back-to-top" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <div class="admin-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-id-card-alt"></i> ID Card Management</h2>
            <p>Manage student ID requests, approvals, and generated cards</p>
        </div>

        <!-- Display messages -->
<?php if ($success_msg): ?>
    <div class="alert-banner alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($success_msg) ?>
    </div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class="alert-banner alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error_msg) ?>
    </div>
<?php endif; ?>

<!-- Warning for students with existing IDs -->
<?php if ($filter === 'approved'): ?>
    <?php
    // Get all approved requests including those with conflicts
    $allApprovedRequests = $adm->getRequestsByStatus('approved');
    $displayedRequests = $adm->getApprovedIdRequests(); // This already filters out conflicts
    
    // If there's a difference, some requests are hidden due to conflicts
    if (count($allApprovedRequests) > count($displayedRequests)): ?>
    <div class="alert-banner alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Note:</strong> Some approved requests are not shown because the students already have generated IDs.
        <div style="margin-top: 10px;">
            <strong>Affected requests:</strong> <?= count($allApprovedRequests) - count($displayedRequests) ?> request(s) hidden
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

        <!-- Display bulk operation results -->
        <?php if ($bulkResult): ?>
            <div class="alert-banner <?php echo !empty($bulkResult['errors']) ? 'alert-error' : 'alert-success'; ?>">
                <i class="fas <?php echo !empty($bulkResult['errors']) ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                <div>
                    <?php if ($bulkResult['success_count'] > 0): ?>
                        <strong>Successfully generated <?= $bulkResult['success_count'] ?> ID(s)</strong> out of <?= $bulkResult['total_processed'] ?> processed.
                    <?php endif; ?>
                    
                    <?php if (!empty($bulkResult['errors'])): ?>
                        <div style="margin-top: 10px;">
                            <strong>Errors encountered:</strong>
                            <ul style="margin: 8px 0 0 20px;">
                                <?php foreach ($bulkResult['errors'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="stats-dashboard">
            <div class="stat-card">
                <div class="stat-number"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $approvedCount ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $rejectedCount ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $generatedCount ?></div>
                <div class="stat-label">Generated IDs</div>
            </div>
            <div class="stat-card">
    <div class="stat-number"><?= $printedCount ?></div>
    <div class="stat-label">Printed IDs</div>
</div>
        </div>

        <!-- Filter Navigation -->
        <div class="filter-nav">
            <a href="?filter=pending" class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> Pending Requests
                <?php if ($pendingCount > 0): ?>
                    <span class="badge"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?filter=approved" class="filter-btn <?= $filter === 'approved' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i> Approved
                <?php if ($approvedCount > 0): ?>
                    <span class="badge"><?= $approvedCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?filter=rejected" class="filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>">
                <i class="fas fa-times-circle"></i> Rejected
                <?php if ($rejectedCount > 0): ?>
                    <span class="badge"><?= $rejectedCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?filter=generated" class="filter-btn <?= $filter === 'generated' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i> Generated IDs
                <?php if ($generatedCount > 0): ?>
                    <span class="badge"><?= $generatedCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?filter=printed" class="filter-btn <?= $filter === 'printed' ? 'active' : '' ?>">
    <i class="fas fa-print"></i> Printed IDs
    <?php if ($printedCount > 0): ?>
        <span class="badge"><?= $printedCount ?></span>
    <?php endif; ?>
</a>
        </div>

        <!-- ========== PAGINATION CONTROLS ========== -->
        <?php if ($totalItems > 0): ?>
        <div class="bulk-section">
            <div class="bulk-actions">
                <span style="font-weight: 600;">
                    <i class="fas fa-database"></i> 
                    Showing <?= min($perPage, count($requests) + count($issued)) ?> of <?= $totalItems ?> items
                    (Page <?= $page ?> of <?= $totalPages ?>)
                </span>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Results per page selector -->
                    <div class="results-per-page">
                        <span style="font-size: 0.8rem; color: #666;">Show:</span>
                        <select onchange="changePerPage(this.value)">
                            <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pagination-btn" title="First Page">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn" title="Previous Page">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">
                                <i class="fas fa-angle-double-left"></i>
                            </span>
                            <span class="pagination-btn disabled">
                                <i class="fas fa-angle-left"></i>
                            </span>
                        <?php endif; ?>

                        <span class="pagination-info">
                            Page <?= $page ?> of <?= $totalPages ?>
                        </span>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn" title="Next Page">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="pagination-btn" title="Last Page">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">
                                <i class="fas fa-angle-right"></i>
                            </span>
                            <span class="pagination-btn disabled">
                                <i class="fas fa-angle-double-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- BULK ACTIONS SECTION FOR APPROVED REQUESTS -->
        <?php if ($filter === 'approved' && !empty($approvedRequests)): ?>
        <div class="bulk-section">
            <h3><i class="fas fa-bolt"></i> Bulk ID Generation</h3>
            <p>Select multiple approved requests and generate IDs in batch</p>
            <form method="post" id="bulkForm">
                <div class="bulk-actions">
                    <button type="submit" name="bulk_generate_ids" class="btn-admin btn-bulk">
                        <i class="fas fa-rocket"></i> Generate Selected IDs
                    </button>
                    <span id="selectedCount" class="text-muted">0 selected</span>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="select-all-cell">
                                    <input type="checkbox" id="selectAllApproved" class="form-check-input">
                                </th>
                                <th>Request ID</th>
                                <th>Student Information</th>
                                <th>Academic Details</th>
                                <th>Request Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approvedRequests as $r): 
                                $name = htmlspecialchars($r['first_name'].' '.$r['last_name']);
                            ?>
                            <tr>
                                <td class="select-all-cell">
                                    <input type="checkbox" name="request_ids[]" value="<?= $r['id'] ?>" class="form-check-input request-checkbox">
                                </td>
                                <td><strong>#<?= $r['id'] ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;"><?= $name ?></div>
                                    <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($r['email']) ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($r['course'] ?? 'Not specified') ?></div>
                                    <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($r['year_level'] ?? 'Not specified') ?></div>
                                </td>
                                <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="generate_id" class="btn-admin btn-generate">
                                            <i class="fas fa-id-card"></i> Generate
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- BULK APPROVE FOR PENDING REQUESTS -->
        <?php if ($filter === 'pending' && !empty($requests)): ?>
        <div class="bulk-section">
            <h3><i class="fas fa-check-double"></i> Bulk Approval</h3>
            <p>Select multiple pending requests and approve them all at once</p>
            <form method="post" id="pendingBulkForm">
                <div class="bulk-actions">
                    <button type="submit" name="bulk_approve" class="btn-admin btn-bulk">
                        <i class="fas fa-check"></i> Approve Selected
                    </button>
                    <span id="pendingSelectedCount" class="text-muted">0 selected</span>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="select-all-cell">
                                    <input type="checkbox" id="selectAllPending" class="form-check-input">
                                </th>
                                <th>Request ID</th>
                                <th>Student Information</th>
                                <th>Request Details</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $r):
                                $name = htmlspecialchars($r['first_name'].' '.$r['last_name']);
                            ?>
                            <tr>
                                <td class="select-all-cell">
                                    <input type="checkbox" name="request_ids[]" value="<?= $r['id'] ?>" class="form-check-input pending-checkbox">
                                </td>
                                <td><strong>#<?= $r['id'] ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;"><?= $name ?></div>
                                    <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($r['email']) ?></div>
                                </td>
                                <td>
                                    <div>
                                        <span class="status-badge status-pending">
                                            <?= htmlspecialchars($r['request_type']) ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.85rem; margin-top: 5px;">
                                        <?= nl2br(htmlspecialchars($r['reason'])) ?>
                                    </div>
                                </td>
                                <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                            <button type="submit" name="approve" class="btn-admin btn-approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                            <button type="submit" name="reject" class="btn-admin btn-reject" onclick="return confirm('Reject this request?');">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- REQUESTS TABLE (approved / rejected) -->
        <?php if (in_array($filter,['pending','approved','rejected']) && empty($requests)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <span>
                        <i class="fas fa-list"></i>
                        <?= ucfirst($filter) ?> Requests
                    </span>
                    <span class="badge">0</span>
                </div>
                <div class="admin-card-body">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>No <?= $filter ?> requests</h4>
                        <p>There are currently no <?= $filter ?> ID requests in the system.</p>
                    </div>
                </div>
            </div>
        <?php elseif (in_array($filter,['pending','approved','rejected']) && !empty($requests)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <span>
                        <i class="fas fa-list"></i>
                        <?= ucfirst($filter) ?> Requests
                    </span>
                    <span class="badge"><?= count($requests) ?></span>
                </div>
                <div class="admin-card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Student Information</th>
                                    <th>Request Details</th>
                                    <th>Requested</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $r):
                                    $name = htmlspecialchars($r['first_name'].' '.$r['last_name']);
                                    $statusClass = 'status-'.$filter;
                                ?>
                                <tr>
                                    <td><strong>#<?= $r['id'] ?></strong></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= $name ?></div>
                                        <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($r['email']) ?></div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="status-badge status-pending">
                                                <?= htmlspecialchars($r['request_type']) ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 0.85rem; margin-top: 5px;">
                                            <?= nl2br(htmlspecialchars($r['reason'])) ?>
                                        </div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= ucfirst($filter) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($filter === 'pending'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" name="approve" class="btn-admin btn-approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" name="reject" class="btn-admin btn-reject" onclick="return confirm('Reject this request?');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($filter === 'approved'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" name="generate_id" class="btn-admin btn-generate">
                                                        <i class="fas fa-id-card"></i> Generate ID
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ISSUED CARDS TABLE (generated) -->
<?php if (in_array($filter,['generated'])): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <span>
                <i class="fas fa-id-card"></i>
                Generated ID Cards
            </span>
            <span class="badge"><?= count($issued) ?></span>
        </div>
        <div class="admin-card-body">
            <?php if (empty($issued)): ?>
                <div class="empty-state">
                    <i class="fas fa-id-card"></i>
                    <h4>No generated IDs</h4>
                    <p>There are currently no generated ID cards in the system.</p>
                </div>
            <?php else: ?>
                <!-- BULK PRINT SECTION FOR GENERATED IDs -->
                <form method="post" id="bulkPrintForm">
                    <div class="bulk-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4><i class="fas fa-print"></i> Bulk Print IDs</h4>
                        <p>Select multiple generated IDs and print them in batch</p>
                        <div class="bulk-actions">
                            <button type="submit" name="bulk_print" class="btn-admin btn-bulk">
                                <i class="fas fa-print"></i> Print Selected IDs
                            </button>
                            <span id="bulkPrintSelectedCount" class="text-muted">0 selected</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="select-all-cell">
                                        <input type="checkbox" id="selectAllGenerated" class="form-check-input">
                                    </th>
                                    <th>ID Number</th>
                                    <th>Student Information</th>
                                    <th>Issue Date</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($issued as $row): 
                                    $name = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                                ?>
                                <tr>
                                    <td class="select-all-cell">
                                        <input type="checkbox" name="selected_issued_ids[]" value="<?= htmlspecialchars($row['id_number']) ?>" class="form-check-input generated-checkbox">
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['id_number']) ?></strong></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= $name ?></div>
                                        <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars(isset($row['email']) ? $row['email'] : '') ?></div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['issue_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['expiry_date'])) ?></td>
                                    <td>
                                        <span class="status-badge status-generated">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php
                                            if ($row['status']==='generated'):
                                                $file = $row['digital_id_file'] ?? null;
                                                if ($file && file_exists(__DIR__.'/../uploads/digital_id/'.$file)):
                                                    $safe   = htmlspecialchars($file);
                                                    $jsSafe = addslashes($safe);
                                            ?>
                                                    <a href="../uploads/digital_id/<?= $safe ?>" target="_blank" class="btn-admin btn-view" title="View ID">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../uploads/digital_id/<?= $safe ?>" download class="btn-admin btn-generate" title="Download ID">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button" onclick="window.open('../uploads/digital_id/<?= $jsSafe ?>', '_blank').print();" class="btn-admin" title="Print ID">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="issued_id" value="<?= htmlspecialchars($row['id_number']) ?>">
                                                        <button type="submit" name="regenerate" class="btn-admin btn-regenerate" title="Regenerate ID" onclick="return confirm('Regenerate this ID card?');">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 0.85rem;">File missing</span>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="issued_id" value="<?= htmlspecialchars($row['id_number']) ?>">
                                                        <button type="submit" name="regenerate" class="btn-admin btn-regenerate" title="Regenerate ID" onclick="return confirm('Regenerate this ID card?');">
                                                            <i class="fas fa-sync"></i> Regenerate
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($filter === 'printed'): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <span>
                <i class="fas fa-print"></i>
                Printed ID Cards
            </span>
            <span class="badge"><?= count($issued) ?></span>
        </div>
        <div class="admin-card-body">
            <?php if (empty($issued)): ?>
                <div class="empty-state">
                    <i class="fas fa-print"></i>
                    <h4>No printed IDs</h4>
                    <p>There are currently no printed ID cards in the system.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Student Information</th>
                                <th>Academic Details</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>Print Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issued as $row): 
                                $name = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['id_number']) ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;"><?= $name ?></div>
                                    <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($row['email']) ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($row['course'] ?? 'Not specified') ?></div>
                                    <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($row['year_level'] ?? 'Not specified') ?></div>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['issue_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($row['expiry_date'])) ?></td>
                                <td>
                                    <?php 
                                    // Use updated_at as print date, or issue_date if not available
                                    $printDate = !empty($row['updated_at']) && $row['updated_at'] != '0000-00-00 00:00:00' 
                                        ? $row['updated_at'] 
                                        : $row['issue_date'];
                                    echo date('M d, Y', strtotime($printDate));
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-printed">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php
                                        $file = $row['digital_id_file'] ?? null;
                                        if ($file && file_exists(__DIR__.'/../uploads/digital_id/'.$file)):
                                            $safe = htmlspecialchars($file);
                                            $jsSafe = addslashes($safe);
                                        ?>
                                            <a href="../uploads/digital_id/<?= $safe ?>" target="_blank" class="btn-admin btn-view" title="View ID">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../uploads/digital_id/<?= $safe ?>" download class="btn-admin btn-generate" title="Download ID">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" onclick="window.open('../uploads/digital_id/<?= $jsSafe ?>', '_blank').print();" class="btn-admin" title="Print ID">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.85rem;">File missing</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

        <!-- ========== BOTTOM PAGINATION ========== -->
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pagination-btn" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn" title="Previous Page">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-left"></i>
                    </span>
                <?php endif; ?>

                <span class="pagination-info">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn" title="Next Page">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="pagination-btn" title="Last Page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-right"></i>
                    </span>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
<?php if (isset($_SESSION['pending_print_ids']) && isset($_SESSION['show_print_modal'])): ?>
<div id="printConfirmationModal" style="display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">
        <h3><i class="fas fa-print"></i> Print Confirmation</h3>
        <p>Your bulk ID PDF has been generated and opened in a new tab. Did you successfully print the <?= count($_SESSION['pending_print_ids']) ?> selected ID card(s)?</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <strong>Printed IDs:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <?php foreach ($_SESSION['pending_print_ids'] as $id): ?>
                    <li><?= htmlspecialchars($id) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <form method="post" style="display: inline;">
            <?php foreach ($_SESSION['pending_print_ids'] as $id): ?>
                <input type="hidden" name="printed_ids[]" value="<?= htmlspecialchars($id) ?>">
            <?php endforeach; ?>
            <button type="submit" name="confirm_print_completion" class="btn-admin btn-success">
                <i class="fas fa-check"></i> Yes, Printing Successful
            </button>
        </form>
        
        <form method="post" style="display: inline; margin-left: 10px;">
            <button type="submit" name="cancel_print" class="btn-admin btn-reject">
                <i class="fas fa-times"></i> No, Printing Failed
            </button>
        </form>
        
        <div style="margin-top: 15px; font-size: 0.9em; color: #666;">
            <i class="fas fa-info-circle"></i> 
            Status will remain "generated" if printing failed. You can re-print later.
        </div>
    </div>
</div>

<script>
    // Auto-show the modal and open PDF in new tab
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('printConfirmationModal').style.display = 'block';
        
        // Open PDF in new tab
        <?php if (isset($_SESSION['bulk_print_download_url'])): ?>
            window.open('<?= $_SESSION['bulk_print_download_url'] ?>', '_blank');
            <?php unset($_SESSION['bulk_print_download_url']); ?>
        <?php endif; ?>
        
        // Clear the trigger flag
        <?php unset($_SESSION['show_print_modal']); ?>
    });
</script>
<?php endif; ?>
    <script>
        // Select All functionality
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
                    updatePendingSelectedCount();
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

            // Update selected count for pending requests
            function updatePendingSelectedCount() {
                const checkboxes = document.querySelectorAll('.pending-checkbox');
                const checked = document.querySelectorAll('.pending-checkbox:checked');
                const countElement = document.getElementById('pendingSelectedCount');
                if (countElement) {
                    countElement.textContent = `${checked.length} selected`;
                }
            }

            // Add event listeners to all checkboxes
            const requestCheckboxes = document.querySelectorAll('.request-checkbox');
            requestCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            const pendingCheckboxes = document.querySelectorAll('.pending-checkbox');
            pendingCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePendingSelectedCount);
            });

            // Confirm bulk generation
            const bulkGenerateBtn = document.querySelector('button[name="bulk_generate_ids"]');
            if (bulkGenerateBtn) {
                bulkGenerateBtn.addEventListener('click', function(e) {
                    const checked = document.querySelectorAll('.request-checkbox:checked');
                    if (checked.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one request to generate IDs.');
                        return false;
                    }
                    if (!confirm(`Generate IDs for ${checked.length} selected students?`)) {
                        e.preventDefault();
                    }
                });
            }

            // Confirm bulk approval
            const bulkApproveBtn = document.querySelector('button[name="bulk_approve"]');
            if (bulkApproveBtn) {
                bulkApproveBtn.addEventListener('click', function(e) {
                    const checked = document.querySelectorAll('.pending-checkbox:checked');
                    if (checked.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one request to approve.');
                        return false;
                    }
                    if (!confirm(`Approve ${checked.length} selected requests?`)) {
                        e.preventDefault();
                    }
                });
            }

            // Back to top button functionality
            window.onscroll = function() {
                const backToTopBtn = document.querySelector('.back-to-top');
                if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                    backToTopBtn.style.display = 'flex';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            };

            // Initial count updates
            updateSelectedCount();
            updatePendingSelectedCount();
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        const selectAllGenerated = document.getElementById('selectAllGenerated');
if (selectAllGenerated) {
    selectAllGenerated.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.generated-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkPrintSelectedCount();
    });
}

// Update selected count for bulk print
function updateBulkPrintSelectedCount() {
    const checkboxes = document.querySelectorAll('.generated-checkbox');
    const checked = document.querySelectorAll('.generated-checkbox:checked');
    const countElement = document.getElementById('bulkPrintSelectedCount');
    if (countElement) {
        countElement.textContent = `${checked.length} selected`;
    }
}

// Add event listeners to generated checkboxes
const generatedCheckboxes = document.querySelectorAll('.generated-checkbox');
generatedCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkPrintSelectedCount);
});

// Confirm bulk print
const bulkPrintBtn = document.querySelector('button[name="bulk_print"]');
if (bulkPrintBtn) {
    bulkPrintBtn.addEventListener('click', function(e) {
        const checked = document.querySelectorAll('.generated-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Please select at least one ID to print.');
            return false;
        }
        if (!confirm(`Print ${checked.length} selected ID cards? This will generate a PDF with all selected IDs.`)) {
            e.preventDefault();
        }
    });
}

// Change results per page
function changePerPage(perPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', perPage);
    params.delete('page'); // Go back to first page
    window.location.href = '?' + params.toString();
}

// Initial count update for bulk print
updateBulkPrintSelectedCount();
        
    </script>

</body>

</html>