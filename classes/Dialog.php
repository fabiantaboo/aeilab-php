<?php
/**
 * Dialog Management Class
 * Handles CRUD operations for dialogs between AEI and User characters
 */
class Dialog {
    private $db;
    
    const STATUS_DRAFT = 'draft';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    
    const EMOTIONS = [
        'joy', 'sadness', 'fear', 'anger', 'surprise', 'disgust',
        'trust', 'anticipation', 'shame', 'love', 'contempt', 
        'loneliness', 'pride', 'envy', 'nostalgia', 'gratitude',
        'frustration', 'boredom'
    ];
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create a new dialog
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        // Build SQL with emotional columns
        $emotionalColumns = [];
        $emotionalValues = [];
        $params = [
            $data['name'],
            $data['description'] ?? null,
            $data['aei_character_id'],
            $data['user_character_id'],
            $data['topic'],
            $data['turns_per_topic'] ?? 5,
            $data['created_by']
        ];
        
        // Add random emotional states for AEI character
        foreach (self::EMOTIONS as $emotion) {
            $emotionalColumns[] = "aei_$emotion";
            $emotionalValues[] = "?";
            $params[] = round(mt_rand(0, 100) / 100, 1); // Random value 0.0-1.0 in 0.1 steps
        }
        
        $sql = "INSERT INTO dialogs (name, description, aei_character_id, user_character_id, topic, turns_per_topic, created_by, " . 
               implode(", ", $emotionalColumns) . ") VALUES (?, ?, ?, ?, ?, ?, ?, " . 
               implode(", ", $emotionalValues) . ")";
        
        try {
            $this->db->query($sql, $params);
            
            $dialogId = $this->db->lastInsertId();
            
            // Create background job for dialog generation
            if ($dialogId) {
                $this->createDialogJob($dialogId, $data['turns_per_topic'] ?? 5);
            }
            
            return $dialogId;
        } catch (Exception $e) {
            error_log("Dialog creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get dialog by ID with character information
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT d.*, 
                       aei.name as aei_character_name, aei.type as aei_character_type,
                       user.name as user_character_name, user.type as user_character_type,
                       creator.username as creator_name
                FROM dialogs d
                JOIN characters aei ON d.aei_character_id = aei.id
                JOIN characters user ON d.user_character_id = user.id
                JOIN users creator ON d.created_by = creator.id
                WHERE d.id = ? AND d.is_active = 1";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get all dialogs with optional filters
     * @param array $filters
     * @return array
     */
    public function getAll($filters = []) {
        $sql = "SELECT d.*, 
                       aei.name as aei_character_name, aei.type as aei_character_type,
                       user.name as user_character_name, user.type as user_character_type,
                       creator.username as creator_name
                FROM dialogs d
                JOIN characters aei ON d.aei_character_id = aei.id
                JOIN characters user ON d.user_character_id = user.id
                JOIN users creator ON d.created_by = creator.id
                WHERE d.is_active = 1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['created_by'])) {
            $sql .= " AND d.created_by = ?";
            $params[] = $filters['created_by'];
        }
        
        if (!empty($filters['character_id'])) {
            $sql .= " AND (d.aei_character_id = ? OR d.user_character_id = ?)";
            $params[] = $filters['character_id'];
            $params[] = $filters['character_id'];
        }
        
        if (isset($filters['is_active'])) {
            $sql .= " AND d.is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (d.name LIKE ? OR d.topic LIKE ? OR d.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Update dialog
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE dialogs SET name = ?, description = ?, aei_character_id = ?, user_character_id = ?, 
                topic = ?, turns_per_topic = ?, status = ? WHERE id = ?";
        
        try {
            $this->db->query($sql, [
                $data['name'],
                $data['description'] ?? null,
                $data['aei_character_id'],
                $data['user_character_id'],
                $data['topic'],
                $data['turns_per_topic'] ?? 5,
                $data['status'] ?? self::STATUS_DRAFT,
                $id
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Dialog update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete dialog (soft delete)
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "UPDATE dialogs SET is_active = 0 WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Dialog deletion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add message to dialog
     * @param int $dialogId
     * @param int $characterId
     * @param string $message
     * @param int $turnNumber
     * @param string $anthropicRequestJson
     * @param array $emotions - emotional state at time of message (for AEI characters)
     * @return bool
     */
    public function addMessage($dialogId, $characterId, $message, $turnNumber, $anthropicRequestJson = null, $emotions = null) {
        try {
            // Build SQL with emotional columns if emotions are provided
            $columns = ['dialog_id', 'character_id', 'message', 'turn_number'];
            $placeholders = ['?', '?', '?', '?'];
            $params = [$dialogId, $characterId, $message, $turnNumber];
            
            // Check if anthropic_request_json column exists
            $columnCheck = $this->db->query("SHOW COLUMNS FROM dialog_messages LIKE 'anthropic_request_json'");
            $jsonColumnExists = $columnCheck->rowCount() > 0;
            
            if ($jsonColumnExists && $anthropicRequestJson !== null) {
                $columns[] = 'anthropic_request_json';
                $placeholders[] = '?';
                $params[] = $anthropicRequestJson;
            }
            
            // Add emotional columns if emotions are provided
            if ($emotions && is_array($emotions)) {
                foreach (self::EMOTIONS as $emotion) {
                    if (isset($emotions[$emotion])) {
                        $columns[] = "aei_$emotion";
                        $placeholders[] = '?';
                        $params[] = max(0, min(1, round($emotions[$emotion], 1)));
                    }
                }
            }
            
            $sql = "INSERT INTO dialog_messages (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Message addition failed: " . $e->getMessage());
            error_log("SQL: " . ($sql ?? 'unknown'));
            error_log("Params: " . json_encode($params ?? []));
            return false;
        }
    }
    
    /**
     * Get dialog messages
     * @param int $dialogId
     * @return array
     */
    public function getMessages($dialogId) {
        $sql = "SELECT dm.*, c.name as character_name, c.type as character_type
                FROM dialog_messages dm
                JOIN characters c ON dm.character_id = c.id
                WHERE dm.dialog_id = ?
                ORDER BY dm.turn_number ASC, dm.created_at ASC";
        
        return $this->db->fetchAll($sql, [$dialogId]);
    }
    
    /**
     * Rate a message with thumbs up or down
     * @param int $messageId
     * @param string $ratingType - 'up' or 'down'
     * @return bool
     */
    public function rateMessage($messageId, $ratingType) {
        if (!in_array($ratingType, ['up', 'down'])) {
            return false;
        }
        
        $column = $ratingType === 'up' ? 'rating_thumbs_up' : 'rating_thumbs_down';
        $otherColumn = $ratingType === 'up' ? 'rating_thumbs_down' : 'rating_thumbs_up';
        
        try {
            // Get current rating values
            $sql = "SELECT $column, $otherColumn FROM dialog_messages WHERE id = ?";
            $current = $this->db->fetch($sql, [$messageId]);
            
            if (!$current) {
                return false;
            }
            
            $currentValue = intval($current[$column]);
            $otherValue = intval($current[$otherColumn]);
            
            // Toggle the rating - if already rated, remove it; otherwise add it
            if ($currentValue > 0) {
                // Already rated with this type, remove the rating
                $newValue = 0;
            } else {
                // Not rated with this type, add rating and remove opposite rating
                $newValue = 1;
                $otherValue = 0;
            }
            
            // Update both columns
            $updateSql = "UPDATE dialog_messages SET $column = ?, $otherColumn = ? WHERE id = ?";
            $this->db->query($updateSql, [$newValue, $otherValue, $messageId]);
            
            return true;
        } catch (Exception $e) {
            error_log("Message rating failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure rating columns exist in dialog_messages table
     * @return bool
     */
    public function ensureRatingColumns() {
        try {
            // Check if rating columns exist
            $checkColumns = $this->db->query("SHOW COLUMNS FROM dialog_messages LIKE 'rating_%'");
            $ratingColumns = $checkColumns->fetchAll();
            
            if (count($ratingColumns) == 0) {
                // Add the rating columns
                $this->db->query("ALTER TABLE dialog_messages ADD COLUMN rating_thumbs_up INT DEFAULT 0");
                $this->db->query("ALTER TABLE dialog_messages ADD COLUMN rating_thumbs_down INT DEFAULT 0");
                error_log("Rating columns added automatically to dialog_messages table");
                return true;
            }
            return true;
        } catch (Exception $e) {
            error_log("Could not add rating columns automatically: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if rating columns exist
     * @return bool
     */
    public function hasRatingColumns() {
        try {
            $checkColumns = $this->db->query("SHOW COLUMNS FROM dialog_messages LIKE 'rating_%'");
            return $checkColumns->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get rating statistics for a dialog
     * @param int $dialogId
     * @return array
     */
    public function getRatingStats($dialogId) {
        try {
            // First check if rating columns exist
            $checkColumns = $this->db->query("SHOW COLUMNS FROM dialog_messages LIKE 'rating_%'");
            $ratingColumns = $checkColumns->fetchAll();
            
            if (count($ratingColumns) == 0) {
                // Rating columns don't exist yet, return default values
                $countResult = $this->db->fetch("SELECT COUNT(*) as total_messages FROM dialog_messages WHERE dialog_id = ?", [$dialogId]);
                return [
                    'total_thumbs_up' => 0,
                    'total_thumbs_down' => 0,
                    'total_messages' => intval($countResult['total_messages'] ?? 0),
                    'rated_messages' => 0
                ];
            }
            
            $sql = "SELECT 
                        SUM(rating_thumbs_up) as total_thumbs_up,
                        SUM(rating_thumbs_down) as total_thumbs_down,
                        COUNT(*) as total_messages,
                        SUM(CASE WHEN rating_thumbs_up > 0 OR rating_thumbs_down > 0 THEN 1 ELSE 0 END) as rated_messages
                    FROM dialog_messages 
                    WHERE dialog_id = ?";
            
            $result = $this->db->fetch($sql, [$dialogId]);
            
            return [
                'total_thumbs_up' => intval($result['total_thumbs_up'] ?? 0),
                'total_thumbs_down' => intval($result['total_thumbs_down'] ?? 0),
                'total_messages' => intval($result['total_messages'] ?? 0),
                'rated_messages' => intval($result['rated_messages'] ?? 0)
            ];
        } catch (Exception $e) {
            // Fallback if there's any error
            $countResult = $this->db->fetch("SELECT COUNT(*) as total_messages FROM dialog_messages WHERE dialog_id = ?", [$dialogId]);
            return [
                'total_thumbs_up' => 0,
                'total_thumbs_down' => 0,
                'total_messages' => intval($countResult['total_messages'] ?? 0),
                'rated_messages' => 0
            ];
        }
    }
    
    /**
     * Get dialog statistics
     * @return array
     */
    public function getStats() {
        $stats = [
            'total' => 0,
            'draft' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'active' => 0
        ];
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    COUNT(*) as active
                FROM dialogs
                WHERE is_active = 1";
        
        $result = $this->db->fetch($sql);
        
        if ($result) {
            $stats = array_merge($stats, $result);
        }
        
        return $stats;
    }
    
    /**
     * Get available dialog statuses
     * @return array
     */
    public function getStatuses() {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed'
        ];
    }
    
    /**
     * Get available AEI characters
     * @return array
     */
    public function getAEICharacters() {
        $sql = "SELECT id, name, description FROM characters WHERE type = 'AEI' AND is_active = 1 AND description != 'Auto-generated user character for manual chat session' ORDER BY name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get available User characters
     * @return array
     */
    public function getUserCharacters() {
        $sql = "SELECT id, name, description FROM characters WHERE type = 'User' AND is_active = 1 AND description != 'Auto-generated user character for manual chat session' ORDER BY name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Validate dialog data
     * @param array $data
     * @return array
     */
    public function validate($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Dialog name is required';
        }
        
        if (empty($data['topic'])) {
            $errors[] = 'Topic is required';
        }
        
        if (empty($data['aei_character_id'])) {
            $errors[] = 'AEI character is required';
        }
        
        if (empty($data['user_character_id'])) {
            $errors[] = 'User character is required';
        }
        
        if ($data['aei_character_id'] == $data['user_character_id']) {
            $errors[] = 'AEI and User characters must be different';
        }
        
        if (!empty($data['turns_per_topic']) && (!is_numeric($data['turns_per_topic']) || $data['turns_per_topic'] < 1)) {
            $errors[] = 'Turns per topic must be at least 1';
        }
        
        if (strlen($data['name']) > 100) {
            $errors[] = 'Dialog name must be less than 100 characters';
        }
        
        if (strlen($data['topic']) > 200) {
            $errors[] = 'Topic must be less than 200 characters';
        }
        
        // Validate that AEI character is actually AEI type
        if (!empty($data['aei_character_id'])) {
            $aeiChar = $this->db->fetch("SELECT type FROM characters WHERE id = ?", [$data['aei_character_id']]);
            if ($aeiChar && $aeiChar['type'] !== 'AEI') {
                $errors[] = 'Selected AEI character is not of AEI type';
            }
        }
        
        // Validate that User character is actually User type
        if (!empty($data['user_character_id'])) {
            $userChar = $this->db->fetch("SELECT type FROM characters WHERE id = ?", [$data['user_character_id']]);
            if ($userChar && $userChar['type'] !== 'User') {
                $errors[] = 'Selected User character is not of User type';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get current emotional state for AEI character in dialog
     * @param int $dialogId
     * @return array|false
     */
    public function getEmotionalState($dialogId) {
        $emotionalColumns = [];
        foreach (self::EMOTIONS as $emotion) {
            $emotionalColumns[] = "aei_$emotion";
        }
        
        $sql = "SELECT " . implode(", ", $emotionalColumns) . " FROM dialogs WHERE id = ?";
        return $this->db->fetch($sql, [$dialogId]);
    }
    
    /**
     * Update emotional state for AEI character in dialog
     * @param int $dialogId
     * @param array $emotions - array of emotion => value pairs
     * @return bool
     */
    public function updateEmotionalState($dialogId, $emotions) {
        $updates = [];
        $params = [];
        
        foreach ($emotions as $emotion => $value) {
            if (in_array($emotion, self::EMOTIONS)) {
                $updates[] = "aei_$emotion = ?";
                $params[] = max(0, min(1, round($value, 1))); // Clamp between 0-1, round to 0.1 steps
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $dialogId;
        $sql = "UPDATE dialogs SET " . implode(", ", $updates) . " WHERE id = ?";
        
        try {
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Emotional state update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adjust emotional state by percentage
     * @param int $dialogId
     * @param array $emotionChanges - array of emotion => change_value pairs
     * @param float $adjustmentFactor - factor to multiply changes by (default 0.3 for 30%)
     * @return bool
     */
    public function adjustEmotionalState($dialogId, $emotionChanges, $adjustmentFactor = 0.3) {
        $currentState = $this->getEmotionalState($dialogId);
        if (!$currentState) {
            return false;
        }
        
        $newEmotions = [];
        foreach (self::EMOTIONS as $emotion) {
            $currentValue = $currentState["aei_$emotion"];
            $change = isset($emotionChanges[$emotion]) ? $emotionChanges[$emotion] * $adjustmentFactor : 0;
            $newValue = $currentValue + $change;
            $newEmotions[$emotion] = max(0, min(1, round($newValue, 1))); // Clamp and round
        }
        
        return $this->updateEmotionalState($dialogId, $newEmotions);
    }
    
    /**
     * Check if user can edit dialog
     * @param int $dialogId
     * @param int $userId
     * @return bool
     */
    public function canEdit($dialogId, $userId) {
        global $user;
        
        // Admins can edit everything
        if ($user->isAdmin()) {
            return true;
        }
        
        // Users can only edit their own dialogs
        $dialog = $this->getById($dialogId);
        return $dialog && $dialog['created_by'] == $userId;
    }
    
    /**
     * Create a background job for dialog generation
     * @param int $dialogId
     * @param int $maxTurns
     * @return bool
     */
    private function createDialogJob($dialogId, $maxTurns) {
        try {
            // Get the global DialogJob instance
            global $dialogJob;
            
            if (!$dialogJob) {
                // Create new instance if not available
                $dialogJob = new DialogJob($this->db);
            }
            
            // Create job starting with AEI character
            $jobId = $dialogJob->create($dialogId, $maxTurns, DialogJob::NEXT_AEI);
            
            if ($jobId) {
                error_log("Dialog job created: $jobId for dialog: $dialogId");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Failed to create dialog job: " . $e->getMessage());
            return false;
        }
    }
}
?> 