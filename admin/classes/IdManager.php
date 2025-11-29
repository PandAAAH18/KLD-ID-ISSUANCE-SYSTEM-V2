<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/User.php';
require_once __DIR__ . '/AuditLogger.php';
require_once __DIR__.'/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\src\Writer\PngWriter;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Logo\Logo;

class IdManager extends User
{
    private AuditLogger $auditLogger;

    public function __construct($db = null)
    {
        // If no DB connection provided, try to use parent's connection
        if ($db === null) {
            parent::__construct();
            $this->db = $this->getDb(); // Assuming parent has this method
        } else {
            parent::__construct($db);
        }
        
        $this->auditLogger = new AuditLogger($this->db);
    }

    public function getAllIdRequests(): array
    {
        $sql = "SELECT r.*, s.first_name, s.last_name, s.email, s.student_id
                FROM id_requests r
                JOIN student s ON s.id = r.student_id
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllIssuedIds(): array
    {
        $sql = "SELECT i.*, s.first_name, s.last_name, s.email
                FROM issued_ids i
                JOIN student s ON s.id = i.user_id
                ORDER BY i.issue_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateStudentId(int $requestId): bool
    {
        try {
            $this->db->beginTransaction();

            // Lock request row
            $req = $this->db->prepare("SELECT * FROM id_requests WHERE id = ? FOR UPDATE");
            $req->execute([$requestId]);
            $row = $req->fetch(PDO::FETCH_ASSOC);
            
            if (!$row || $row['status'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            // Build next ID number
            $last = $this->db->query("SELECT id_number FROM issued_ids ORDER BY id DESC LIMIT 1")->fetchColumn();
            $next = $last ? (intval(substr($last, -6)) + 1) : 100000;
            $idNumber = date('Y').str_pad($next, 6, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d', strtotime('+4 years'));

            // Create issued row
            $ins = $this->db->prepare("INSERT INTO issued_ids
                                (user_id, id_number, issue_date, expiry_date, status)
                                VALUES (?, ?, NOW(), ?, 'pending')");
            $ins->execute([$row['student_id'], $idNumber, $expiry]);

            $issuedId = $this->db->lastInsertId();

            // Mark request approved
            $up = $this->db->prepare("UPDATE id_requests
                                SET status = 'approved', updated_at = NOW()
                                WHERE id = ?");
            $up->execute([$requestId]);

            // Log the action
            $this->auditLogger->logAction(
                'generate_student_id',
                $requestId,
                'id_requests',
                ['status' => 'pending'],
                [
                    'status' => 'approved',
                    'issued_id' => $issuedId,
                    'id_number' => $idNumber,
                    'expiry_date' => $expiry
                ]
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error generating student ID for request {$requestId}: " . $e->getMessage());
            return false;
        }
    }

    public function markIdPrinted(int $issuedId): bool
    {
        try {
            // Get old data for audit log
            $oldData = $this->getIssuedIdData($issuedId);
            
            $sql = "UPDATE issued_ids SET status = 'printed' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$issuedId]);
            
            if ($success && $stmt->rowCount() > 0) {
                $this->auditLogger->logAction(
                    'mark_id_printed',
                    $issuedId,
                    'issued_ids',
                    $oldData ? ['status' => $oldData['status']] : [],
                    ['status' => 'printed']
                );
            }
            
            return $success && $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error marking ID as printed: " . $e->getMessage());
            return false;
        }
    }

    public function markIdDelivered(int $issuedId): bool
    {
        try {
            // Get old data for audit log
            $oldData = $this->getIssuedIdData($issuedId);
            
            $sql = "UPDATE issued_ids SET status = 'delivered' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$issuedId]);
            
            if ($success && $stmt->rowCount() > 0) {
                $this->auditLogger->logAction(
                    'mark_id_delivered',
                    $issuedId,
                    'issued_ids',
                    $oldData ? ['status' => $oldData['status']] : [],
                    ['status' => 'delivered']
                );
            }
            
            return $success && $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error marking ID as delivered: " . $e->getMessage());
            return false;
        }
    }

    public function getRequestsByStatus(string $status): array
    {
        $sql = "SELECT r.*, s.first_name, s.last_name, s.email
                FROM id_requests r
                JOIN student s ON s.id = r.student_id
                WHERE r.status = ?
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIssuedByStatus(string $filter): array
    {
        $map = ['generated' => 'generated', 'completed' => 'delivered'];
        $targetStatus = $map[$filter] ?? $filter;
        
        $sql = "SELECT i.*, s.first_name, s.last_name, s.email
                FROM issued_ids i
                JOIN student s ON s.id = i.user_id
                WHERE i.status = ?
                ORDER BY i.issue_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$targetStatus]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setRequestStatus(int $requestId, string $newStatus): bool
    {
        try {
            // Get old data for audit log
            $oldData = $this->getRequestData($requestId);
            
            $sql = "UPDATE id_requests
                    SET status = ?, updated_at = NOW()
                    WHERE id = ? AND status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$newStatus, $requestId]);
            
            if ($success && $stmt->rowCount() > 0) {
                $this->auditLogger->logAction(
                    'set_request_status',
                    $requestId,
                    'id_requests',
                    $oldData ? ['status' => $oldData['status']] : [],
                    ['status' => $newStatus]
                );
            }
            
            return $success && $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error setting request status: " . $e->getMessage());
            return false;
        }
    }

    public function generateId(int $requestId): void
{
    $db = $this->getDb();

    /* 1.  pull request + student */
    $stmt = $db->prepare("SELECT r.student_id, s.email, s.first_name, s.last_name,
                                 s.course, s.year_level, s.photo, s.signature,
                                 s.emergency_contact, s.blood_type
                          FROM   id_requests r
                          JOIN   student      s ON s.id = r.student_id
                          WHERE  r.id = ? AND r.status = 'approved'");
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return;

    $studentId = $row['student_id'];

    /* 2.  next id number */
    $last = $db->query("SELECT id_number FROM issued_ids ORDER BY id_number DESC LIMIT 1")->fetchColumn();
    $next = $last ? (intval(substr($last, -6)) + 1) : 100000;
    $idNumber = date('Y').str_pad($next, 6, '0', STR_PAD_LEFT);
    $expiry   = date('Y-m-d', strtotime('+4 years'));

    /* 3. QR code ------------------------------------------------------------- */
$verifyUrl = APP_URL.'/verify_id.php?n='.$idNumber;

$qrName = $idNumber.'.png';          // file name we will store & reference
$qrPath = __DIR__.'/../../uploads/qr/'.$qrName;

/* -------  Endroid QrCode  ------- */
$writer = new \Endroid\QrCode\Writer\PngWriter();

$qrCode = new \Endroid\QrCode\QrCode(
    data: $verifyUrl,                                        // verification link
    encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
    errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Low,
    size: 300,
    margin: 10,
    foregroundColor: new \Endroid\QrCode\Color\Color(0, 0, 0),
    backgroundColor: new \Endroid\QrCode\Color\Color(255, 255, 255)
);

// optional logo – comment out if you don’t want it
$logo = new \Endroid\QrCode\Logo\Logo(
    path: __DIR__.'/../../assets/images/kldlogo.png',
    resizeToWidth: 50,
    punchoutBackground: true
);

$result = $writer->write($qrCode, $logo);   // label omitted – add if desired
$result->saveToFile($qrPath);               // physical file for local storage
/* -------------------------------- */


    $options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$dompdf = new \Dompdf\Dompdf($options);

// Increase the height of the ID cards
$cardHeight = '300px'; // Increased from 214px

$front = '
<div style="width:340px;height:'.$cardHeight.';background:url(\''.APP_URL.'/assets/images/id_front.png\') no-repeat center/contain;padding:20px 15px;box-sizing:border-box;position:relative;font-family:Arial,sans-serif;display:inline-block;vertical-align:top;text-align:center;margin-top:20px;">
    <img src="'.APP_URL.'/uploads/student_photos/'.$row['photo'].'" style="width:75px;height:75px;object-fit:cover;border:1px solid #ccc;margin-top:70px;"><br>
    <b style="font-size:12px;">'.$row['first_name'].' '.$row['last_name'].'</b><br>
    <span style="font-size:10px;">'.$row['course'].' - '.$row['year_level'].'</span><br>
    <span style="font-size:10px;">ID: '.$row['student_id'].'</span><br>
    <img src="'.APP_URL.'/uploads/student_signatures/'.$row['signature'].'" style="width:100px;margin-top:50px;">
</div>';

$back = '
<div style="width:340px;height:'.$cardHeight.';background:url(\''.APP_URL.'/assets/images/id_back.png\') no-repeat center/contain;padding:20px 15px;box-sizing:border-box;position:relative;display:inline-block;vertical-align:top;margin-left:20px;text-align:center;margin-top:20px;">
    <span style="font-size:13px;margin-top:95px;display:inline-block;">'.$row['emergency_contact_name'].'</span><br>
    <span style="font-size:13px;display:inline-block;">'.$row['emergency_contact'].'</span><br>
    <img src="'.APP_URL.'/uploads/qr/'.$qrName.'" style="width:70px;margin-top:40px;margin-left:95px; "><br>
</div>';

// Wrap both divs in a container
$html = '<div style="width:100%;text-align:center;">' . $front . $back . '</div>';

$dompdf->loadHtml($html);
$dompdf->setPaper('CR80', 'landscape'); // Keep CR80 paper size
$dompdf->render();

    /* 5.  save PDF */
    $fileName = $row['email'].'_'.date('YmdHis').'.pdf';
    $filePath = __DIR__.'/../../uploads/digital_id/'.$fileName;
    if (!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);
    file_put_contents($filePath, $dompdf->output());

    /* 6.  insert row */
    $ins = $db->prepare("INSERT INTO issued_ids
                        (user_id, id_number, issue_date, expiry_date, status, digital_id_file)
                        VALUES (?, ?, NOW(), ?, 'generated', ?)");
    $ins->execute([$studentId, $idNumber, $expiry, $fileName]);

    /* 7.  close request */
    $db->prepare("UPDATE id_requests SET status='generated', updated_at=NOW() WHERE id=?")
       ->execute([$requestId]);
}

   public function regenerateId(string $idNumber): bool
{
    error_log("DEBUG regenerateId: called with idNumber='{$idNumber}' (type: " . gettype($idNumber) . ")");

    try {
        $db = $this->db; // Use class $db directly

        // 1. Get existing issued ID + student data
        $stmt = $db->prepare("SELECT i.id_number, i.digital_id_file,
                                     s.email, s.first_name, s.last_name,
                                     s.course, s.year_level, s.photo, s.signature,
                                     s.emergency_contact, s.blood_type, s.student_id, s.emergency_contact_name
                              FROM issued_ids i
                              JOIN student s ON s.id = i.user_id
                              WHERE i.id_number = ?");
        $stmt->execute([$idNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            error_log("DEBUG regenerateId: No row found for id_number '{$idNumber}'");
            return false;
        }

        $oldDigitalFile = $row['digital_id_file'];

        // 2. Delete old files (consistent naming: no "qr_" prefix)
        $qrName = $idNumber . '.png';
        $qrPath = __DIR__ . '/../../uploads/qr/' . $qrName;

        if ($oldDigitalFile) {
            $oldPdfPath = __DIR__ . '/../../uploads/digital_id/' . $oldDigitalFile;
            if (file_exists($oldPdfPath)) {
                unlink($oldPdfPath);
            }
        }
        if (file_exists($qrPath)) {
            unlink($qrPath);
        }

        // 3. Regenerate QR code
        $verifyUrl = APP_URL . '/verify_id.php?n=' . $idNumber;

        if (!is_dir(dirname($qrPath))) {
            mkdir(dirname($qrPath), 0755, true);
        }

        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $qrCode = new \Endroid\QrCode\QrCode(
            data: $verifyUrl,
            encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
            errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            foregroundColor: new \Endroid\QrCode\Color\Color(0, 0, 0),
            backgroundColor: new \Endroid\QrCode\Color\Color(255, 255, 255)
        );

        $logoPath = __DIR__ . '/../../assets/images/kldlogo.png';
        $logo     = file_exists($logoPath)
            ? new \Endroid\QrCode\Logo\Logo(path: $logoPath, resizeToWidth: 50, punchoutBackground: true)
            : null;

        $result = $writer->write($qrCode, $logo);
        $result->saveToFile($qrPath);

      $options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$dompdf = new \Dompdf\Dompdf($options);

// Increase the height of the ID cards
$cardHeight = '300px'; // Increased from 214px

$front = '
<div style="width:340px;height:'.$cardHeight.';background:url(\''.APP_URL.'/assets/images/id_front.png\') no-repeat center/contain;padding:20px 15px;box-sizing:border-box;position:relative;font-family:Arial,sans-serif;display:inline-block;vertical-align:top;text-align:center;margin-top:20px;">
    <img src="'.APP_URL.'/uploads/student_photos/'.$row['photo'].'" style="width:75px;height:75px;object-fit:cover;border:1px solid #ccc;margin-top:70px;"><br>
    <b style="font-size:12px;">'.$row['first_name'].' '.$row['last_name'].'</b><br>
    <span style="font-size:10px;">'.$row['course'].' - '.$row['year_level'].'</span><br>
    <span style="font-size:10px;">ID: '.$row['student_id'].'</span><br>
    <img src="'.APP_URL.'/uploads/student_signatures/'.$row['signature'].'" style="width:100px;margin-top:50px;">
</div>';

$back = '
<div style="width:340px;height:'.$cardHeight.';background:url(\''.APP_URL.'/assets/images/id_back.png\') no-repeat center/contain;padding:20px 15px;box-sizing:border-box;position:relative;display:inline-block;vertical-align:top;margin-left:20px;text-align:center;margin-top:20px;">
    <span style="font-size:13px;margin-top:95px;display:inline-block;">'.$row['emergency_contact_name'].'</span><br>
    <span style="font-size:13px;display:inline-block;">'.$row['emergency_contact'].'</span><br>
    <img src="'.APP_URL.'/uploads/qr/'.$qrName.'" style="width:70px;margin-top:40px;margin-left:95px; "><br>
</div>';

// Wrap both divs in a container
$html = '<div style="width:100%;text-align:center;">' . $front . $back . '</div>';

$dompdf->loadHtml($html);
$dompdf->setPaper('CR80', 'landscape'); // Keep CR80 paper size
$dompdf->render();

        // 5. Save new PDF
        $newFileName = $row['email'] . '_' . date('YmdHis') . '.pdf';
        $newFilePath = __DIR__ . '/../../uploads/digital_id/' . $newFileName;
        if (!is_dir(dirname($newFilePath))) {
            mkdir(dirname($newFilePath), 0755, true);
        }
        file_put_contents($newFilePath, $dompdf->output());

        // 6. Update database record
        $update = $db->prepare("UPDATE issued_ids
                                SET digital_id_file = ?, issue_date = NOW()
                                WHERE id_number = ?");
        $update->execute([$newFileName, $idNumber]);

        // 7. Audit log
        if (method_exists($this, 'auditLogger') && $this->auditLogger) {
            $this->auditLogger->logAction(
                'regenerate_id',
                $idNumber,
                'issued_ids',
                ['digital_id_file' => $oldDigitalFile],
                ['digital_id_file' => $newFileName, 'qr_code' => $qrName]
            );
        }

        return true;

    } catch (Exception $e) {
        error_log("Regenerate ID failed (ID {$issuedId}): " . $e->getMessage());
        return false;
    }
}
    /* ==================== BULK ID GENERATION ==================== */

    public function bulkGenerateIds(array $requestIds): array
    {
        if (empty($requestIds)) {
            return [
                'success_count' => 0,
                'errors' => ['No request IDs provided']
            ];
        }

        $successCount = 0;
        $errors = [];
        
        try {
            // Begin transaction for data consistency
            $this->db->beginTransaction();

            foreach ($requestIds as $requestId) {
                try {
                    // Validate request exists and is approved
                    $stmt = $this->db->prepare("
                        SELECT r.student_id, s.email, s.first_name, s.last_name,
                                     s.course, s.year_level, s.photo, s.signature,
                                     s.emergency_contact, s.blood_type
                              FROM   id_requests r
                              JOIN   student      s ON s.id = r.student_id
                              WHERE  r.id = ? AND r.status = 'approved'
                    ");
                    $stmt->execute([$requestId]);
                    $request = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$request) {
                        $errors[] = "Request ID {$requestId} not found or not approved";
                        continue;
                    }

                    $studentId = $request['student_id'];

                    // Check if ID already exists for this student
                    $checkStmt = $this->db->prepare("
                        SELECT id_number FROM issued_ids 
                        WHERE user_id = ? AND status != 'revoked'
                    ");
                    $checkStmt->execute([$studentId]);
                    
                    if ($checkStmt->rowCount() > 0) {
                        $errors[] = "Student {$request['first_name']} {$request['last_name']} already has an ID";
                        continue;
                    }

                    // Generate next ID number
                    $lastId = $this->db->query("
                        SELECT id_number FROM issued_ids 
                        ORDER BY id_number DESC LIMIT 1
                    ")->fetchColumn();
                    
                    $next = $lastId ? (intval(substr($lastId, -6)) + 1) : 100000;
                    $idNumber = date('Y') . str_pad($next, 6, '0', STR_PAD_LEFT);
                    $expiry = date('Y-m-d', strtotime('+4 years'));

                    // Generate QR Code
                    $qrResult = $this->generateQRCode($idNumber);
                    if (!$qrResult['success']) {
                        $errors[] = "Failed to generate QR for request {$requestId}: " . $qrResult['message'];
                        continue;
                    }

                    // Generate PDF
                    $pdfResult = $this->generateIdPDF($request, $idNumber, $qrResult['filename']);
                    if (!$pdfResult['success']) {
                        $errors[] = "Failed to generate PDF for request {$requestId}: " . $pdfResult['message'];
                        // Clean up QR code file
                        if (file_exists($qrResult['filepath'])) {
                            unlink($qrResult['filepath']);
                        }
                        continue;
                    }

                    // Insert into issued_ids table
                    $insertStmt = $this->db->prepare("
                        INSERT INTO issued_ids 
                        (user_id, id_number, issue_date, expiry_date, status, digital_id_file)
                        VALUES (?, ?, NOW(), ?, 'generated', ?)
                    ");
                    
                    $insertSuccess = $insertStmt->execute([
                        $studentId,
                        $idNumber,
                        $expiry,
                        $pdfResult['filename']
                    ]);

                    if (!$insertSuccess) {
                        $errors[] = "Database error for request {$requestId}";
                        // Clean up generated files
                        if (file_exists($qrResult['filepath'])) {
                            unlink($qrResult['filepath']);
                        }
                        if (file_exists($pdfResult['filepath'])) {
                            unlink($pdfResult['filepath']);
                        }
                        continue;
                    }

                    $issuedId = $this->db->lastInsertId();

                    // Update request status
                    $updateStmt = $this->db->prepare("
                        UPDATE id_requests 
                        SET status = 'generated', updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$requestId]);

                    // Log the action
                    $this->auditLogger->logAction(
                        'bulk_generate_id',
                        $studentId,
                        'student',
                        [],
                        [
                            'id_number' => $idNumber,
                            'request_id' => $requestId,
                            'issued_id' => $issuedId,
                            'digital_file' => $pdfResult['filename']
                        ]
                    );

                    $successCount++;

                } catch (Exception $e) {
                    $errors[] = "Error processing request {$requestId}: " . $e->getMessage();
                    continue;
                }
            }

            // Commit transaction if all operations were successful
            $this->db->commit();

            return [
                'success_count' => $successCount,
                'errors' => $errors,
                'total_processed' => count($requestIds)
            ];

        } catch (Exception $e) {
            // Rollback transaction on failure
            $this->db->rollBack();
            
            return [
                'success_count' => 0,
                'errors' => ['Transaction failed: ' . $e->getMessage()],
                'total_processed' => 0
            ];
        }
    }

    /**
     * Generate QR Code for ID
     */
    private function generateQRCode(string $idNumber): array
    {
        try {
            $verifyUrl = APP_URL . '/verify_id.php?n=' . $idNumber;
            $qrName = 'qr_' . $idNumber . '.png';
            $qrPath = __DIR__ . '/../uploads/qr/' . $qrName;

            // Create directory if it doesn't exist
            if (!is_dir(dirname($qrPath))) {
                mkdir(dirname($qrPath), 0755, true);
            }

            // Generate QR code
            $writer = new PngWriter();
            
            $qrCode = new QrCode(
                data: $verifyUrl,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 300,
                margin: 10,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );

            // Optional: Add logo if available
            $logoPath = __DIR__ . '/../../assets/images/kldlogo.png';
            if (file_exists($logoPath)) {
                $logo = new Logo(
                    path: $logoPath,
                    resizeToWidth: 50,
                    punchoutBackground: true
                );
                $result = $writer->write($qrCode, $logo);
            } else {
                $result = $writer->write($qrCode);
            }

            $result->saveToFile($qrPath);

            return [
                'success' => true,
                'filename' => $qrName,
                'filepath' => $qrPath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate ID PDF
     */
    private function generateIdPDF(array $studentData, string $idNumber, string $qrFilename): array
    {
        try {
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);

            // Front of ID
            $front = '
            <div style="width:340px;height:214px;border:1px solid #000;margin:0 auto;text-align:center;padding:10px;font-family:Arial;">
                <h3 style="margin:5px 0;">KLD - ID CARD</h3>
                <img src="' . APP_URL . '/uploads/student_photos/' . ($studentData['photo'] ?? 'default.png') . '" 
                     style="width:80px;height:80px;object-fit:cover;border:1px solid #ccc;border-radius:5px;"><br>
                <b style="font-size:14px;">' . htmlspecialchars($studentData['first_name'] . ' ' . $studentData['last_name']) . '</b><br>
                <span style="font-size:12px;">' . htmlspecialchars($studentData['course'] ?? 'N/A') . ' - ' . htmlspecialchars($studentData['year_level'] ?? 'N/A') . '</span><br>
                <span style="font-size:11px;">ID: ' . $idNumber . '</span><br>
                <span style="font-size:10px;">Emergency: ' . htmlspecialchars($studentData['emergency_contact'] ?? 'N/A') . '</span><br>
                <span style="font-size:10px;">Blood Type: ' . htmlspecialchars($studentData['blood_type'] ?? 'N/A') . '</span>
            </div>';

            // Back of ID
            $back = '
            <div style="width:340px;height:214px;border:1px solid #000;margin:30px auto;text-align:center;padding:10px;font-family:Arial;">
                <p style="margin-top:10px;font-size:12px;">If found please return to school registrar.</p>
                <img src="' . APP_URL . '/uploads/qr/' . $qrFilename . '" style="width:70px;"><br>
                <small>Signature</small><br>
                <img src="' . APP_URL . '/uploads/student_signatures/' . ($studentData['signature'] ?? 'default.png') . '" 
                     style="width:100px;height:40px;object-fit:contain;">
                <p style="font-size:10px;margin-top:10px;">Valid until: ' . date('M Y', strtotime('+4 years')) . '</p>
            </div>';

            $dompdf->loadHtml($front . $back);
            $dompdf->setPaper('CR80', 'landscape');
            $dompdf->render();

            // Save PDF
            $fileName = $studentData['email'] . '_' . $idNumber . '.pdf';
            $filePath = __DIR__ . '/../../uploads/digital_id/' . $fileName;
            
            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
            
            file_put_contents($filePath, $dompdf->output());

            return [
                'success' => true,
                'filename' => $fileName,
                'filepath' => $filePath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get approved requests for bulk processing
     */
    public function getApprovedIdRequests(): array
    {
        $sql = "SELECT r.id as request_id, r.*, s.id as student_id, s.first_name, s.last_name, s.email, s.course, s.year_level
                FROM id_requests r
                JOIN student s ON s.id = r.student_id
                WHERE r.status = 'approved'
                ORDER BY r.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==================== PRIVATE HELPER METHODS ==================== */

    /**
     * Get request data for audit logging
     */
    private function getRequestData(int $requestId): ?array
    {
        $sql = "SELECT * FROM id_requests WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get issued ID data for audit logging
     */
    private function getIssuedIdData(int $issuedId): ?array
    {
        $sql = "SELECT * FROM issued_ids WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$issuedId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

  public function bulkPrintIds(array $idNumbers): array
{
    try {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);

        $html = '<div style="width:100%; text-align:center; font-family: Arial, sans-serif;">';
        
        $count = 0;
        $cardsPerRow = 2;
        
        foreach ($idNumbers as $idNumber) {
            $idData = $this->getIssuedIdDataByNumber($idNumber);
            if (!$idData) {
                error_log("ID data not found for: " . $idNumber);
                continue;
            }
            
            if ($count % $cardsPerRow === 0 && $count > 0) {
                $html .= '<div style="clear:both; height:20px;"></div>';
            }
            
            // Front card
            $html .= '
            <div style="width:340px;height:300px;background:url(\''.APP_URL.'/assets/images/id_front.png\') no-repeat center/contain;padding:20px 15px;box-sizing:border-box;display:inline-block;vertical-align:top;text-align:center;margin:10px;">
                <img src="'.APP_URL.'/uploads/student_photos/'.$idData['photo'].'" style="width:75px;height:75px;object-fit:cover;border:1px solid #ccc;margin-top:70px;"><br>
                <b style="font-size:12px;">'.$idData['first_name'].' '.$idData['last_name'].'</b><br>
                <span style="font-size:10px;">'.$idData['course'].' - '.$idData['year_level'].'</span><br>
                <span style="font-size:10px;">ID: '.$idData['student_id'].'</span><br>
                <img src="'.APP_URL.'/uploads/student_signatures/'.$idData['signature'].'" style="width:100px;margin-top:50px;">
            </div>';
            
            // Back card
            $html .= '
            <div style="width:340px;height:300px;background:url(\''.APP_URL.'/assets/images/id_back.png\') no-repeat center/contain;padding:20px 15px;box-sizing:border-box;display:inline-block;vertical-align:top;margin:10px;text-align:center;">
                <span style="font-size:13px;margin-top:95px;display:inline-block;">'.$idData['emergency_contact_name'].'</span><br>
                <span style="font-size:13px;display:inline-block;">'.$idData['emergency_contact'].'</span><br>
                <img src="'.APP_URL.'/uploads/qr/'.$idNumber.'.png" style="width:70px;margin-top:40px;margin-left:95px;"><br>
            </div>';
            
            $count++;
            
            if ($count % 2 === 0 && $count > 0) {
                $html .= '<div style="page-break-after: always;"></div>';
            }
        }
        
        $html .= '</div>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Generate filename
        $filename = 'bulk_ids_' . date('Ymd_His') . '.pdf';
        $filePath = __DIR__ . '/../../uploads/bulk_print/' . $filename;
        
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        file_put_contents($filePath, $dompdf->output());

        // Return PDF info WITHOUT updating status
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filePath,
            'count' => $count,
            'id_numbers' => $idNumbers
        ];

    } catch (Exception $e) {
        error_log("Bulk print failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// New method to mark IDs as physically printed
public function markIdsAsPrinted(array $idNumbers): bool
{
    try {
        $this->db->beginTransaction();
        
        $sql = "UPDATE issued_ids SET status = 'printed' WHERE id_number = ? AND status = 'generated'";
        $stmt = $this->db->prepare($sql);
        
        foreach ($idNumbers as $idNumber) {
            $stmt->execute([$idNumber]);
            
            // Log the action
            $this->auditLogger->logAction(
                'mark_id_printed',
                $idNumber,
                'issued_ids',
                ['status' => 'generated'],
                ['status' => 'printed']
            );
        }
        
        $this->db->commit();
        return true;
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Error marking IDs as printed: " . $e->getMessage());
        return false;
    }
}

private function getIssuedIdDataByNumber(string $idNumber): ?array
{
    $sql = "SELECT i.*, s.first_name, s.last_name, s.email, s.course, s.year_level, 
                   s.photo, s.signature, s.emergency_contact, s.blood_type, 
                   s.student_id, s.emergency_contact_name
            FROM issued_ids i
            JOIN student s ON s.id = i.user_id
            WHERE i.id_number = ?";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

private function markIdAsPrintedInBulk(string $idNumber): bool
{
    try {
        $sql = "UPDATE issued_ids SET status = 'printed' WHERE id_number = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idNumber]);
    } catch (Exception $e) {
        error_log("Error marking ID as printed in bulk: " . $e->getMessage());
        return false;
    }
}
    
}