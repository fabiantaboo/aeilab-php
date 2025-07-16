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
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create a new dialog
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        $sql = "INSERT INTO dialogs (name, description, aei_character_id, user_character_id, topic, turns_per_topic, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $this->db->query($sql, [
                $data['name'],
                $data['description'] ?? null,
                $data['aei_character_id'],
                $data['user_character_id'],
                $data['topic'],
                $data['turns_per_topic'] ?? 5,
                $data['created_by']
            ]);
            
            return $this->db->lastInsertId();
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
                WHERE d.id = ?";
        
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
                WHERE 1=1";
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
     * @return bool
     */
    public function addMessage($dialogId, $characterId, $message, $turnNumber) {
        $sql = "INSERT INTO dialog_messages (dialog_id, character_id, message, turn_number) VALUES (?, ?, ?, ?)";
        
        try {
            $this->db->query($sql, [$dialogId, $characterId, $message, $turnNumber]);
            return true;
        } catch (Exception $e) {
            error_log("Message addition failed: " . $e->getMessage());
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
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
                FROM dialogs";
        
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
        $sql = "SELECT id, name, description FROM characters WHERE type = 'AEI' AND is_active = 1 ORDER BY name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get available User characters
     * @return array
     */
    public function getUserCharacters() {
        $sql = "SELECT id, name, description FROM characters WHERE type = 'User' AND is_active = 1 ORDER BY name";
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
        
        if (!empty($data['turns_per_topic']) && (!is_numeric($data['turns_per_topic']) || $data['turns_per_topic'] < 1 || $data['turns_per_topic'] > 50)) {
            $errors[] = 'Turns per topic must be between 1 and 50';
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
}
?> 