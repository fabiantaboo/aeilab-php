<?php
/**
 * Dialog Job Management Class
 * Handles background processing of dialog generation
 */
class DialogJob {
    private $db;
    
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    const NEXT_AEI = 'AEI';
    const NEXT_USER = 'User';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create a new dialog job
     * @param int $dialogId
     * @param int $maxTurns
     * @param string $nextCharacterType
     * @return bool|int
     */
    public function create($dialogId, $maxTurns, $nextCharacterType = self::NEXT_AEI) {
        $sql = "INSERT INTO dialog_jobs (dialog_id, max_turns, next_character_type) VALUES (?, ?, ?)";
        
        try {
            $this->db->query($sql, [$dialogId, $maxTurns, $nextCharacterType]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Dialog job creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get job by ID
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT * FROM dialog_jobs WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get job by dialog ID
     * @param int $dialogId
     * @return array|false
     */
    public function getByDialogId($dialogId) {
        $sql = "SELECT * FROM dialog_jobs WHERE dialog_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->db->fetch($sql, [$dialogId]);
    }
    
    /**
     * Get all pending jobs ready for processing
     * @return array
     */
    public function getPendingJobs() {
        $sql = "SELECT dj.*, d.name as dialog_name, d.topic, d.aei_character_id, d.user_character_id
                FROM dialog_jobs dj
                JOIN dialogs d ON dj.dialog_id = d.id
                WHERE dj.status = ? 
                AND (dj.last_processed_at IS NULL OR dj.last_processed_at < DATE_SUB(NOW(), INTERVAL 30 SECOND))
                AND dj.current_turn < dj.max_turns
                ORDER BY dj.created_at ASC";
        
        return $this->db->fetchAll($sql, [self::STATUS_PENDING]);
    }
    
    /**
     * Get all active jobs (pending or in progress)
     * @return array
     */
    public function getActiveJobs() {
        $sql = "SELECT dj.*, d.name as dialog_name, d.topic
                FROM dialog_jobs dj
                JOIN dialogs d ON dj.dialog_id = d.id
                WHERE dj.status IN (?, ?)
                ORDER BY dj.created_at DESC";
        
        return $this->db->fetchAll($sql, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }
    
    /**
     * Update job status
     * @param int $jobId
     * @param string $status
     * @param string $errorMessage
     * @return bool
     */
    public function updateStatus($jobId, $status, $errorMessage = null) {
        $sql = "UPDATE dialog_jobs SET status = ?, error_message = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$status, $errorMessage, $jobId]);
            return true;
        } catch (Exception $e) {
            error_log("Job status update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update job progress
     * @param int $jobId
     * @param int $currentTurn
     * @param string $nextCharacterType
     * @return bool
     */
    public function updateProgress($jobId, $currentTurn, $nextCharacterType) {
        $sql = "UPDATE dialog_jobs SET current_turn = ?, next_character_type = ?, last_processed_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$currentTurn, $nextCharacterType, $jobId]);
            return true;
        } catch (Exception $e) {
            error_log("Job progress update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark job as completed
     * @param int $jobId
     * @return bool
     */
    public function complete($jobId) {
        $sql = "UPDATE dialog_jobs SET status = ?, last_processed_at = NOW(), updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [self::STATUS_COMPLETED, $jobId]);
            
            // Also update dialog status to completed
            $job = $this->getById($jobId);
            if ($job) {
                $this->db->query("UPDATE dialogs SET status = 'completed' WHERE id = ?", [$job['dialog_id']]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Job completion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark job as failed
     * @param int $jobId
     * @param string $errorMessage
     * @return bool
     */
    public function fail($jobId, $errorMessage) {
        return $this->updateStatus($jobId, self::STATUS_FAILED, $errorMessage);
    }
    
    /**
     * Check if job is ready for processing
     * @param array $job
     * @return bool
     */
    public function isReadyForProcessing($job) {
        if ($job['status'] !== self::STATUS_PENDING) {
            return false;
        }
        
        if ($job['current_turn'] >= $job['max_turns']) {
            return false;
        }
        
        // Check if enough time has passed (30 seconds)
        if ($job['last_processed_at']) {
            $lastProcessed = strtotime($job['last_processed_at']);
            $now = time();
            return ($now - $lastProcessed) >= 30;
        }
        
        return true;
    }
    
    /**
     * Get job statistics
     * @return array
     */
    public function getStats() {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'failed' => 0
        ];
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM dialog_jobs";
        
        $result = $this->db->fetch($sql);
        
        if ($result) {
            $stats = array_merge($stats, $result);
        }
        
        return $stats;
    }
    
    /**
     * Get next character type for alternating turns
     * @param string $currentType
     * @return string
     */
    public function getNextCharacterType($currentType) {
        return $currentType === self::NEXT_AEI ? self::NEXT_USER : self::NEXT_AEI;
    }
    
    /**
     * Clean up old completed jobs (older than 7 days)
     * @return int Number of deleted jobs
     */
    public function cleanupOldJobs() {
        $sql = "DELETE FROM dialog_jobs WHERE status = ? AND updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        try {
            $stmt = $this->db->query($sql, [self::STATUS_COMPLETED]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Job cleanup failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Reset stuck jobs (in progress for more than 5 minutes)
     * @return int Number of reset jobs
     */
    public function resetStuckJobs() {
        $sql = "UPDATE dialog_jobs SET status = ?, error_message = 'Reset due to timeout' 
                WHERE status = ? AND last_processed_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        try {
            $stmt = $this->db->query($sql, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Job reset failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Restart a failed dialog job
     * @param int $jobId
     * @return bool
     */
    public function restart($jobId) {
        try {
            // First check if the job exists and is actually failed
            $job = $this->getById($jobId);
            if (!$job) {
                error_log("Cannot restart job $jobId: Job not found");
                return false;
            }
            
            if ($job['status'] !== self::STATUS_FAILED) {
                error_log("Cannot restart job $jobId: Job status is not 'failed' (current: {$job['status']})");
                return false;
            }
            
            // Reset the job to pending status and clear error message
            $sql = "UPDATE dialog_jobs SET 
                        status = ?, 
                        error_message = NULL, 
                        last_processed_at = NULL,
                        updated_at = NOW() 
                    WHERE id = ?";
            
            $this->db->query($sql, [self::STATUS_PENDING, $jobId]);
            
            // Also update the dialog status back to in_progress if it was completed due to the failure
            if ($job['dialog_id']) {
                $dialogSql = "UPDATE dialogs SET status = 'in_progress' WHERE id = ? AND status != 'completed'";
                $this->db->query($dialogSql, [$job['dialog_id']]);
            }
            
            error_log("Dialog job restarted: $jobId");
            return true;
            
        } catch (Exception $e) {
            error_log("Job restart failed: " . $e->getMessage());
            return false;
        }
    }
}
?> 