<?php
/**
 * Character Management Class
 * Handles CRUD operations for AEI and User characters
 */
class Character {
    private $db;
    
    const TYPE_AEI = 'AEI';
    const TYPE_USER = 'User';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create a new character
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        $sql = "INSERT INTO characters (name, type, system_prompt, description, created_by) VALUES (?, ?, ?, ?, ?)";
        
        try {
            $this->db->query($sql, [
                $data['name'],
                $data['type'],
                $data['system_prompt'],
                $data['description'] ?? null,
                $data['created_by']
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Character creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get character by ID
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT c.*, u.username as creator_name 
                FROM characters c 
                JOIN users u ON c.created_by = u.id 
                WHERE c.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get all characters with optional filters
     * @param array $filters
     * @return array
     */
    public function getAll($filters = []) {
        $sql = "SELECT c.*, u.username as creator_name 
                FROM characters c 
                JOIN users u ON c.created_by = u.id 
                WHERE 1=1 AND c.description != 'Auto-generated user character for manual chat session'";
        $params = [];
        
        if (!empty($filters['type'])) {
            $sql .= " AND c.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['created_by'])) {
            $sql .= " AND c.created_by = ?";
            $params[] = $filters['created_by'];
        }
        
        if (isset($filters['is_active'])) {
            $sql .= " AND c.is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Update character
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE characters SET name = ?, type = ?, system_prompt = ?, description = ? WHERE id = ?";
        
        try {
            $this->db->query($sql, [
                $data['name'],
                $data['type'],
                $data['system_prompt'],
                $data['description'] ?? null,
                $id
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Character update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete character (soft delete)
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "UPDATE characters SET is_active = 0 WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Character deletion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Permanently delete character
     * @param int $id
     * @return bool
     */
    public function permanentDelete($id) {
        $sql = "DELETE FROM characters WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Character permanent deletion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get character statistics
     * @return array
     */
    public function getStats() {
        $stats = [
            'total' => 0,
            'aei' => 0,
            'user' => 0,
            'active' => 0,
            'inactive' => 0
        ];
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN type = 'AEI' THEN 1 ELSE 0 END) as aei,
                    SUM(CASE WHEN type = 'User' THEN 1 ELSE 0 END) as user,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
                FROM characters
                WHERE description != 'Auto-generated user character for manual chat session'";
        
        $result = $this->db->fetch($sql);
        
        if ($result) {
            $stats = array_merge($stats, $result);
        }
        
        return $stats;
    }
    
    /**
     * Get available character types
     * @return array
     */
    public function getTypes() {
        return [
            self::TYPE_AEI => 'AEI Character',
            self::TYPE_USER => 'User Character'
        ];
    }
    
    /**
     * Validate character data
     * @param array $data
     * @return array
     */
    public function validate($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }
        
        if (empty($data['type']) || !in_array($data['type'], [self::TYPE_AEI, self::TYPE_USER])) {
            $errors[] = 'Valid character type is required';
        }
        
        if (empty($data['system_prompt'])) {
            $errors[] = 'System prompt is required';
        }
        
        if (strlen($data['name']) > 100) {
            $errors[] = 'Name must be less than 100 characters';
        }
        
        if (strlen($data['system_prompt']) > 10000) {
            $errors[] = 'System prompt must be less than 10,000 characters';
        }
        
        return $errors;
    }
    
    /**
     * Check if user can edit character
     * @param int $characterId
     * @param int $userId
     * @return bool
     */
    public function canEdit($characterId, $userId) {
        global $user;
        
        // Admins can edit everything
        if ($user->isAdmin()) {
            return true;
        }
        
        // Users can only edit their own characters
        $character = $this->getById($characterId);
        return $character && $character['created_by'] == $userId;
    }
    
    /**
     * Create a character pairing
     * @param int $aeiCharacterId
     * @param int $userCharacterId
     * @param int $createdBy
     * @return bool
     */
    public function createPairing($aeiCharacterId, $userCharacterId, $createdBy) {
        // Validate characters exist and are correct types
        $aeiChar = $this->getById($aeiCharacterId);
        $userChar = $this->getById($userCharacterId);
        
        if (!$aeiChar || $aeiChar['type'] !== 'AEI') {
            return false;
        }
        
        if (!$userChar || $userChar['type'] !== 'User') {
            return false;
        }
        
        $sql = "INSERT IGNORE INTO character_pairings (aei_character_id, user_character_id, created_by) VALUES (?, ?, ?)";
        
        try {
            $this->db->query($sql, [$aeiCharacterId, $userCharacterId, $createdBy]);
            return true;
        } catch (Exception $e) {
            error_log("Character pairing creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a character pairing
     * @param int $aeiCharacterId
     * @param int $userCharacterId
     * @return bool
     */
    public function removePairing($aeiCharacterId, $userCharacterId) {
        $sql = "DELETE FROM character_pairings WHERE aei_character_id = ? AND user_character_id = ?";
        
        try {
            $this->db->query($sql, [$aeiCharacterId, $userCharacterId]);
            return true;
        } catch (Exception $e) {
            error_log("Character pairing removal failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get suggested user characters for an AEI character
     * @param int $aeiCharacterId
     * @return array
     */
    public function getSuggestedUserCharacters($aeiCharacterId) {
        $sql = "SELECT c.*, cp.created_at as paired_at
                FROM character_pairings cp
                JOIN characters c ON cp.user_character_id = c.id
                WHERE cp.aei_character_id = ? AND c.is_active = 1 
                AND c.description != 'Auto-generated user character for manual chat session'
                ORDER BY cp.created_at ASC";
        
        return $this->db->fetchAll($sql, [$aeiCharacterId]);
    }
    
    /**
     * Get suggested AEI characters for a user character
     * @param int $userCharacterId
     * @return array
     */
    public function getSuggestedAEICharacters($userCharacterId) {
        $sql = "SELECT c.*, cp.created_at as paired_at
                FROM character_pairings cp
                JOIN characters c ON cp.aei_character_id = c.id
                WHERE cp.user_character_id = ? AND c.is_active = 1
                AND c.description != 'Auto-generated user character for manual chat session'
                ORDER BY cp.created_at ASC";
        
        return $this->db->fetchAll($sql, [$userCharacterId]);
    }
    
    /**
     * Get all pairings for a character
     * @param int $characterId
     * @return array
     */
    public function getPairingsForCharacter($characterId) {
        $character = $this->getById($characterId);
        if (!$character) {
            return [];
        }
        
        if ($character['type'] === 'AEI') {
            $sql = "SELECT cp.*, c.name as partner_name, c.type as partner_type, u.username as creator_name
                    FROM character_pairings cp
                    JOIN characters c ON cp.user_character_id = c.id
                    JOIN users u ON cp.created_by = u.id
                    WHERE cp.aei_character_id = ? 
                    AND c.description != 'Auto-generated user character for manual chat session'
                    ORDER BY cp.created_at ASC";
        } else {
            $sql = "SELECT cp.*, c.name as partner_name, c.type as partner_type, u.username as creator_name
                    FROM character_pairings cp
                    JOIN characters c ON cp.aei_character_id = c.id
                    JOIN users u ON cp.created_by = u.id
                    WHERE cp.user_character_id = ? 
                    AND c.description != 'Auto-generated user character for manual chat session'
                    ORDER BY cp.created_at ASC";
        }
        
        return $this->db->fetchAll($sql, [$characterId]);
    }
    
    /**
     * Check if two characters are paired
     * @param int $aeiCharacterId
     * @param int $userCharacterId
     * @return bool
     */
    public function arePaired($aeiCharacterId, $userCharacterId) {
        $sql = "SELECT COUNT(*) FROM character_pairings WHERE aei_character_id = ? AND user_character_id = ?";
        $result = $this->db->fetch($sql, [$aeiCharacterId, $userCharacterId]);
        return $result && $result['COUNT(*)'] > 0;
    }
    
    /**
     * Get all AEI characters with their suggested user characters
     * @return array
     */
    public function getAEICharactersWithSuggestions() {
        $aeiCharacters = $this->getAll(['type' => 'AEI', 'is_active' => 1]);
        
        foreach ($aeiCharacters as &$aeiChar) {
            $aeiChar['suggested_users'] = $this->getSuggestedUserCharacters($aeiChar['id']);
        }
        
        return $aeiCharacters;
    }
    
    /**
     * Get all user characters with their suggested AEI characters
     * @return array
     */
    public function getUserCharactersWithSuggestions() {
        $userCharacters = $this->getAll(['type' => 'User', 'is_active' => 1]);
        
        foreach ($userCharacters as &$userChar) {
            $userChar['suggested_aei'] = $this->getSuggestedAEICharacters($userChar['id']);
        }
        
        return $userCharacters;
    }
}
?> 