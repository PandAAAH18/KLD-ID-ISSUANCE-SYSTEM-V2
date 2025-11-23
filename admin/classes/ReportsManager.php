<?php
require_once __DIR__ . '/../../includes/User.php';

class ReportsManager extends User
{
    /**
     * Get overall statistics for the dashboard
     */
    public function getOverallStats(): array
    {
        try {
            return [
                'total_students' => $this->getTotalStudents(),
                'total_users' => $this->getTotalUsers(),
                'total_admins' => $this->getTotalAdmins(),
                'total_ids_generated' => $this->getTotalIdsGenerated(),
                'pending_id_requests' => $this->getPendingIdRequests(),
                'total_id_requests' => $this->getTotalIdRequests(),
            ];
        } catch (Exception $e) {
            error_log("Error getting overall stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total number of students
     */
    public function getTotalStudents(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM student WHERE deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting total students: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total number of users
     */
    public function getTotalUsers(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting total users: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total number of admin users
     */
    public function getTotalAdmins(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting total admins: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total number of IDs generated
     */
    public function getTotalIdsGenerated(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM issued_ids WHERE status IN ('generated', 'printed', 'delivered')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting total IDs generated: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get pending ID requests count
     */
    public function getPendingIdRequests(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM id_requests WHERE status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting pending ID requests: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total ID requests
     */
    public function getTotalIdRequests(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM id_requests";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting total ID requests: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get students by course
     */
    public function getStudentsByCourse(): array
    {
        try {
            $sql = "SELECT 
                        course, 
                        COUNT(*) as count 
                    FROM student 
                    WHERE deleted_at IS NULL 
                    GROUP BY course 
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting students by course: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get students by year level
     */
    public function getStudentsByYearLevel(): array
    {
        try {
            $sql = "SELECT 
                        year_level, 
                        COUNT(*) as count 
                    FROM student 
                    WHERE deleted_at IS NULL 
                    GROUP BY year_level 
                    ORDER BY year_level ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting students by year level: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get profile completion statistics
     */
    public function getProfileCompletionStats(): array
    {
        try {
            $sql = "SELECT 
                        CASE 
                            WHEN profile_completed = 1 THEN 'Completed'
                            ELSE 'Incomplete'
                        END as status,
                        COUNT(*) as count
                    FROM student 
                    WHERE deleted_at IS NULL 
                    GROUP BY profile_completed";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting profile completion stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get ID generation statistics
     */
    public function getIdGenerationStats(): array
    {
        try {
            $sql = "SELECT 
                        status, 
                        COUNT(*) as count 
                    FROM issued_ids 
                    GROUP BY status 
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting ID generation stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get ID request statistics
     */
    public function getIdRequestStats(): array
    {
        try {
            $sql = "SELECT 
                        status, 
                        COUNT(*) as count 
                    FROM id_requests 
                    GROUP BY status 
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting ID request stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user verification statistics
     */
    public function getUserVerificationStats(): array
    {
        try {
            $sql = "SELECT 
                        CASE 
                            WHEN is_verified = 1 THEN 'Verified'
                            ELSE 'Not Verified'
                        END as status,
                        COUNT(*) as count
                    FROM users 
                    WHERE deleted_at IS NULL 
                    GROUP BY is_verified";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user verification stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user status statistics
     */
    public function getUserStatusStats(): array
    {
        try {
            $sql = "SELECT 
                        status, 
                        COUNT(*) as count 
                    FROM users 
                    WHERE deleted_at IS NULL 
                    GROUP BY status 
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user status stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activities (audit logs)
     */
    public function getRecentActivities(int $limit = 10): array
    {
        try {
            $sql = "SELECT 
                        a.id,
                        a.action, 
                        a.table_name,
                        a.record_id,
                        u.full_name as admin_name,
                        a.created_at
                    FROM audit_logs a
                    LEFT JOIN users u ON u.user_id = a.user_id
                    ORDER BY a.created_at DESC 
                    LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting recent activities: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity log by date range
     */
    public function getActivityByDateRange(string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as count
                    FROM audit_logs
                    WHERE DATE(created_at) BETWEEN :startDate AND :endDate
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':startDate' => $startDate,
                ':endDate' => $endDate
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting activity by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get students with complete profiles and photos
     */
    public function getStudentProfileCompletionDetail(): array
    {
        try {
            $sql = "SELECT 
                        s.id,
                        s.first_name,
                        s.last_name,
                        s.email,
                        s.course,
                        s.year_level,
                        s.profile_completed,
                        CASE 
                            WHEN s.photo_path IS NOT NULL THEN 'Yes'
                            ELSE 'No'
                        END as has_photo,
                        CASE 
                            WHEN s.signature IS NOT NULL THEN 'Yes'
                            ELSE 'No'
                        END as has_signature
                    FROM student s
                    WHERE s.deleted_at IS NULL
                    ORDER BY s.id DESC
                    LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting student profile completion detail: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get students awaiting ID generation
     */
    public function getStudentsAwaitingId(): array
    {
        try {
            $sql = "SELECT 
                        s.id,
                        s.first_name,
                        s.last_name,
                        s.email,
                        s.student_id,
                        r.created_at as request_date,
                        r.status as request_status
                    FROM student s
                    INNER JOIN id_requests r ON r.student_id = s.id
                    WHERE r.status IN ('pending', 'approved')
                    AND s.deleted_at IS NULL
                    ORDER BY r.created_at ASC
                    LIMIT 100";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting students awaiting ID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export statistics to CSV
     */
    public function exportStatsToCSV(): void
    {
        try {
            $stats = $this->getOverallStats();
            $courseStat = $this->getStudentsByCourse();
            $yearStat = $this->getStudentsByYearLevel();

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="statistics_' . date('Y-m-d_His') . '.csv"');

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");

            // Overall Statistics
            fputcsv($output, ['OVERALL STATISTICS']);
            fputcsv($output, ['Metric', 'Count']);
            fputcsv($output, ['Total Students', $stats['total_students']]);
            fputcsv($output, ['Total Users', $stats['total_users']]);
            fputcsv($output, ['Total Admins', $stats['total_admins']]);
            fputcsv($output, ['Total IDs Generated', $stats['total_ids_generated']]);
            fputcsv($output, ['Pending ID Requests', $stats['pending_id_requests']]);
            fputcsv($output, ['Total ID Requests', $stats['total_id_requests']]);

            fputcsv($output, []);
            fputcsv($output, ['STUDENTS BY COURSE']);
            fputcsv($output, ['Course', 'Count']);
            foreach ($courseStat as $row) {
                fputcsv($output, [$row['course'] ?? 'N/A', $row['count']]);
            }

            fputcsv($output, []);
            fputcsv($output, ['STUDENTS BY YEAR LEVEL']);
            fputcsv($output, ['Year Level', 'Count']);
            foreach ($yearStat as $row) {
                fputcsv($output, [$row['year_level'] ?? 'N/A', $row['count']]);
            }

            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log("Error exporting stats to CSV: " . $e->getMessage());
        }
    }
}
?>
