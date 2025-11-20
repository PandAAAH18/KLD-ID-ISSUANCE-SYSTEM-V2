<?php
require_once '../includes/User.php';
require_once __DIR__.'/../vendor/autoload.php';
use Dompdf\Dompdf;          // ← correct
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Config\QrCodeConfig; // CRITICAL CLASS: MUST LOAD
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
class Admin extends User
{
    public function getDb(): PDO {
    return $this->db;
}



// ----------------------------- ID FUNCTIONS ==================== */
/* ---------- 1.  all pending requests with student names ---------- */
    public function getAllIdRequests(): array
    {
        $db = $this->getDb();
        $sql = "SELECT r.*, s.first_name, s.last_name, s.student_id
                FROM id_requests r
                JOIN student s ON s.id = r.student_id
                ORDER BY r.created_at DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ---------- 2.  all issued ids with names ---------- */
    public function getAllIssuedIds(): array
    {
        $db = $this->getDb();
        $sql = "SELECT i.*, s.first_name, s.last_name
                FROM issued_ids i
                JOIN student s ON s.id = i.user_id
                ORDER BY i.issue_date DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ---------- 3.  generate unique id & move request to issued ---------- */
    public function generateStudentId(int $requestId): void
    {
        $db = $this->getDb();

        /* lock request row */
        $req = $db->prepare("SELECT * FROM id_requests WHERE id = ? FOR UPDATE");
        $req->execute([$requestId]);
        $row = $req->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['status'] !== 'pending') return;

        /* build next id number (simple incremental) */
        $last = $db->query("SELECT id_number FROM issued_ids ORDER BY id DESC LIMIT 1")->fetchColumn();
        $next = $last ? (intval(substr($last, -6)) + 1) : 100000;   // 6-digit suffix
        $idNumber = date('Y').str_pad($next, 6, '0', STR_PAD_LEFT); // e.g. 2025001001

        $expiry = date('Y-m-d', strtotime('+4 years'));

        /* create issued row */
        $ins = $db->prepare("INSERT INTO issued_ids
                            (user_id, id_number, issue_date, expiry_date, status)
                            VALUES (?, ?, NOW(), ?, 'pending')");
        $ins->execute([$row['student_id'], $idNumber, $expiry]);

        /* mark request approved */
        $up = $db->prepare("UPDATE id_requests
                            SET status = 'approved', updated_at = NOW()
                            WHERE id = ?");
        $up->execute([$requestId]);
    }

    /* ---------- 4.  status helpers ---------- */
    public function markIdPrinted(int $issuedId): void
    {
        $this->getDb()->prepare("UPDATE issued_ids SET status='printed' WHERE id=?")
                      ->execute([$issuedId]);
    }
    public function markIdDelivered(int $issuedId): void
    {
        $this->getDb()->prepare("UPDATE issued_ids SET status='delivered' WHERE id=?")
                      ->execute([$issuedId]);
    }

    /* filter requests */
public function getRequestsByStatus(string $status): array
{
    $db = $this->getDb();
    $sql = "SELECT r.*, s.first_name, s.last_name
            FROM id_requests r
            JOIN student s ON s.id = r.student_id
            WHERE r.status = ?
            ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* filter issued cards */
public function getIssuedByStatus(string $filter): array
{
    $db = $this->getDb();
    $map = ['generated'=>'generated','completed'=>'delivered'];
    $sql = "SELECT i.*, s.first_name, s.last_name
            FROM issued_ids i
            JOIN student s ON s.id = i.user_id
            WHERE i.status = ?
            ORDER BY i.issue_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$map[$filter]]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* approve / reject */
public function setRequestStatus(int $requestId, string $newStatus): void
{
    $db = $this->getDb();
    $sql = "UPDATE id_requests
            SET status = ?, updated_at = NOW()
            WHERE id = ? AND status = 'pending'";
    $db->prepare($sql)->execute([$newStatus, $requestId]);
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
$qrPath = __DIR__.'/../uploads/qr/'.$qrName;

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
    path: __DIR__.'/../assets/images/kldlogo.png',
    resizeToWidth: 50,
    punchoutBackground: true
);

$result = $writer->write($qrCode, $logo);   // label omitted – add if desired
$result->saveToFile($qrPath);               // physical file for local storage
/* -------------------------------- */


    /* 4.  PDF (front + back with QR) */
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $front = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:0 auto;text-align:center;">
        <h3>SCHOOL NAME</h3>
        <img src="'.APP_URL.'/uploads/student_photos/'.$row['photo'].'" style="width:90px;height:90px;object-fit:cover;border:1px solid #ccc;"><br>
        <b>'.$row['first_name'].' '.$row['last_name'].'</b><br>
        '.$row['course'].' - '.$row['year_level'].'<br>
        ID: '.$idNumber.'<br>
        Emergency: '.$row['emergency_contact'].'<br>
        Blood: '.$row['blood_type'].'
    </div>';

    $back = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:30px auto;text-align:center;">
        <p style="margin-top:10px;">If found please return to school registrar.</p>
        <img src="'.APP_URL.'/uploads/qr/'.$qrName.'" style="width:80px;"><br>
        <small>Signature</small><br>
        <img src="'.APP_URL.'/uploads/student_signatures/'.$row['signature'].'" style="width:120px;">
    </div>';

    $dompdf->loadHtml($front.$back);
    $dompdf->setPaper('CR80', 'landscape');
    $dompdf->render();

    /* 5.  save PDF */
    $fileName = $row['email'].'_'.date('YmdHis').'.pdf';
    $filePath = __DIR__.'/../uploads/digital_id/'.$fileName;
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

public function regenerateId(int $issuedId): void
{
    $db = $this->getDb();

    /* 1.  pull row (correct PK) */
    $stmt = $db->prepare("SELECT i.id_number, i.digital_id_file,
                                 s.email, s.first_name, s.last_name,
                                 s.course, s.year_level, s.photo, s.signature,
                                 s.emergency_contact, s.blood_type
                          FROM issued_ids i
                          JOIN student s ON s.id = i.user_id
                          WHERE i.id_number = ? AND i.status = 'generated'");
    $stmt->execute([$issuedId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return;

    $idNumber = $row['id_number'];

    /* 2.  delete old files */
    if ($row['digital_id_file'] && file_exists(__DIR__.'/../uploads/digital_id/'.$row['digital_id_file'])) {
        unlink(__DIR__.'/../uploads/digital_id/'.$row['digital_id_file']);
    }
    if (file_exists(__DIR__.'/../uploads/qr/qr_'.$idNumber.'.png')) {
        unlink(__DIR__.'/../uploads/qr/qr_'.$idNumber.'.png');
    }

  /* 3. QR code */
/* 3. QR code */
$verifyUrl = APP_URL.'/verify_id.php?n='.$idNumber;
$qrName = 'qr_'.$idNumber.'.png';
$qrPath = __DIR__.'/../uploads/qr/'.$qrName;

/* -------  Endroid QrCode  ------- */
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

// optional logo – comment out if you don't want it
$logoPath = __DIR__.'/../assets/images/kldlogo.png';
if (file_exists($logoPath)) {
    $logo = new \Endroid\QrCode\Logo\Logo(
        path: $logoPath,
        resizeToWidth: 50,
        punchoutBackground: true
    );
    $result = $writer->write($qrCode, $logo);
} else {
    $result = $writer->write($qrCode);
}

// Create directory if it doesn't exist
if (!is_dir(dirname($qrPath))) {
    mkdir(dirname($qrPath), 0755, true);
}

$result->saveToFile($qrPath);


    /* 4.  rebuild PDF (identical HTML) */
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $front = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:0 auto;text-align:center;">
        <h3>SCHOOL NAME</h3>
        <img src="'.APP_URL.'/uploads/student_photos/'.$row['photo'].'" style="width:90px;height:90px;object-fit:cover;border:1px solid #ccc;"><br>
        <b>'.$row['first_name'].' '.$row['last_name'].'</b><br>
        '.$row['course'].' - '.$row['year_level'].'<br>
        ID: '.$idNumber.'<br>
        Emergency: '.$row['emergency_contact'].'<br>
        Blood: '.$row['blood_type'].'
    </div>';

    $back = '
    <div style="width:340px;height:214px;border:1px solid #000;margin:30px auto;text-align:center;">
        <p style="margin-top:10px;">If found please return to school registrar.</p>
        <img src="'.APP_URL.'/uploads/qr/'.$qrName.'" style="width:80px;"><br>
        <small>Signature</small><br>
        <img src="'.APP_URL.'/uploads/student_signatures/'.$row['signature'].'" style="width:120px;">
    </div>';

    $dompdf->loadHtml($front.$back);
    $dompdf->setPaper('CR80', 'landscape');
    $dompdf->render();

    /* 5.  save new PDF */
    $fileName = $row['email'].'_'.date('YmdHis').'.pdf';
    $filePath = __DIR__.'/../uploads/digital_id/'.$fileName;
    file_put_contents($filePath, $dompdf->output());

    /* 6.  update row */
    $db->prepare("UPDATE issued_ids
                  SET digital_id_file = ?, issue_date = NOW()
                  WHERE id_number = ?")
       ->execute([$fileName, $issuedId]);
}

/* ==================== BULK ID GENERATION ==================== */

/**
 * Bulk generate IDs for multiple approved requests
 * @param array $requestIds Array of ID request IDs to process
 * @return array Results with success count and errors
 */
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

                // Update request status
                $updateStmt = $this->db->prepare("
                    UPDATE id_requests 
                    SET status = 'generated', updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$requestId]);

                // Log the action
                $this->logAction(
                    'bulk_generate_id',
                    $studentId,
                    'student',
                    [],
                    [
                        'id_number' => $idNumber,
                        'request_id' => $requestId,
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

        // Generate QR code using your existing method
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
        $logoPath = __DIR__ . '/../assets/images/kldlogo.png';
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
        $filePath = __DIR__ . '/../uploads/digital_id/' . $fileName;
        
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



}