<?php
/**
 * Anthropic API Integration Class
 * Handles communication with Anthropic Claude API for dialog generation
 */
class AnthropicAPI {
    private $apiKey;
    private $baseUrl = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-5-sonnet-20241022';
    
    public function __construct($apiKey = null) {
        // Try to get API key from environment or config
        $this->apiKey = $apiKey ?? ($_ENV['ANTHROPIC_API_KEY'] ?? null);
        
        if (!$this->apiKey) {
            throw new Exception("Anthropic API key not provided");
        }
        
        // Use configured model if available
        if (defined('ANTHROPIC_MODEL')) {
            $this->model = ANTHROPIC_MODEL;
        }
    }
    
    /**
     * Generate a response from Claude
     * @param string $systemPrompt
     * @param array $messages
     * @param int $maxTokens
     * @return array|false
     */
    public function generateResponse($systemPrompt, $messages, $maxTokens = 1000) {
        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => $messages
        ];
        
        try {
            $response = $this->makeRequest($payload);
            
            if (isset($response['content'][0]['text'])) {
                return [
                    'success' => true,
                    'message' => $response['content'][0]['text'],
                    'usage' => $response['usage'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid response format from API'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate dialog turn with conversation history
     * @param string $characterSystemPrompt
     * @param string $topic
     * @param array $conversationHistory
     * @param string $characterType
     * @param string $characterName
     * @param string $partnerName
     * @param string $partnerType
     * @param array $currentEmotions - Current emotional state for AEI characters
     * @return array
     */
    public function generateDialogTurn($characterSystemPrompt, $topic, $conversationHistory, $characterType, $characterName = null, $partnerName = null, $partnerType = null, $currentEmotions = null) {
        // Build system prompt with context
        $systemPrompt = $characterSystemPrompt . "\n\n";
        $systemPrompt .= "You are participating in a dialog about: " . $topic . "\n";
        
        // Add character identity information
        if ($characterName) {
            $systemPrompt .= "You are " . $characterName . " (" . $characterType . " character).\n";
        } else {
            $systemPrompt .= "You are a " . $characterType . " character.\n";
        }
        
        // Add chat partner information
        if ($partnerName && $partnerType) {
            $systemPrompt .= "You are talking with " . $partnerName . " (" . $partnerType . " character).\n";
        }
        
        // Add current emotional state for AEI characters
        if ($characterType === 'AEI' && $currentEmotions && is_array($currentEmotions)) {
            $systemPrompt .= "\nYour current emotional state:\n";
            $activeEmotions = [];
            $neutralEmotions = [];
            $lowEmotions = [];
            
            foreach ($currentEmotions as $emotion => $value) {
                if ($value >= 0.7) {
                    $activeEmotions[] = "$emotion: " . number_format($value, 1);
                } elseif ($value >= 0.4) {
                    $neutralEmotions[] = "$emotion: " . number_format($value, 1);
                } elseif ($value > 0) {
                    $lowEmotions[] = "$emotion: " . number_format($value, 1);
                }
            }
            
            if (!empty($activeEmotions)) {
                $systemPrompt .= "Strong emotions: " . implode(", ", $activeEmotions) . "\n";
            }
            if (!empty($neutralEmotions)) {
                $systemPrompt .= "Moderate emotions: " . implode(", ", $neutralEmotions) . "\n";
            }
            if (!empty($lowEmotions)) {
                $systemPrompt .= "Mild emotions: " . implode(", ", $lowEmotions) . "\n";
            }
            
            $systemPrompt .= "Respond in a way that reflects your current emotional state naturally.\n";
        }
        
        $systemPrompt .= "Respond naturally and stay in character. Keep responses conversational and engaging.\n";
        $systemPrompt .= "This is part of a training dialog, so make it realistic and helpful.";
        
        // Convert conversation history to Anthropic format
        $messages = [];
        
        if (empty($conversationHistory)) {
            // First message - start the conversation
            $messages[] = [
                'role' => 'user',
                'content' => "Start a conversation about: " . $topic
            ];
        } else {
            // Process conversation history to ensure proper alternating pattern
            // and that the last message is always from 'user' role
            
            // First, create the message array from history
            $historyMessages = [];
            foreach ($conversationHistory as $historyItem) {
                $historyMessages[] = $historyItem['message'];
            }
            
            // Determine if we need to flip roles to end with 'user'
            $messageCount = count($historyMessages);
            
            // If we have an even number of messages, the pattern should be:
            // user, assistant, user, assistant (last is assistant)
            // If we have an odd number of messages, the pattern should be:
            // user, assistant, user (last is user)
            
            // We want to ALWAYS end with user, so:
            // - If messageCount is odd: start with user (normal pattern)
            // - If messageCount is even: start with assistant (flipped pattern)
            
            $startWithUser = ($messageCount % 2 === 1);
            
            // Build the alternating message pattern
            foreach ($historyMessages as $index => $message) {
                if ($startWithUser) {
                    $role = ($index % 2 === 0) ? 'user' : 'assistant';
                } else {
                    $role = ($index % 2 === 0) ? 'assistant' : 'user';
                }
                
                $messages[] = [
                    'role' => $role,
                    'content' => $message
                ];
            }
            
            // Verify the last message is from 'user' (safety check)
            if (count($messages) > 0 && $messages[count($messages) - 1]['role'] !== 'user') {
                error_log("Warning: Dialog history doesn't end with user message, this may cause issues");
            }
        }
        
        $response = $this->generateResponse($systemPrompt, $messages);
        
        // Add the actual messages sent to Anthropic for debugging
        if ($response['success']) {
            $response['anthropic_messages'] = $messages;
        }
        
        return $response;
    }
    
    /**
     * Analyze emotional state from conversation history
     * @param array $conversationHistory
     * @param string $characterName
     * @param string $topic
     * @return array|false
     */
    public function analyzeEmotionalState($conversationHistory, $characterName, $topic) {
        // Build system prompt for emotion analysis
        $systemPrompt = "You are an emotion analysis expert. Analyze the emotional state of the AEI character '$characterName' based on the conversation history about '$topic'.\n\n";
        $systemPrompt .= "IMPORTANT: Return ONLY a valid JSON object with emotion values between 0.0 and 1.0 (in 0.1 increments).\n";
        $systemPrompt .= "Use EXACTLY these 18 emotions (no others): joy, sadness, fear, anger, surprise, disgust, trust, anticipation, shame, love, contempt, loneliness, pride, envy, nostalgia, gratitude, frustration, boredom\n\n";
        $systemPrompt .= "Required format: {\"joy\": 0.3, \"sadness\": 0.7, \"fear\": 0.2, \"anger\": 0.1, \"surprise\": 0.4, \"disgust\": 0.0, \"trust\": 0.8, \"anticipation\": 0.6, \"shame\": 0.1, \"love\": 0.9, \"contempt\": 0.0, \"loneliness\": 0.2, \"pride\": 0.5, \"envy\": 0.0, \"nostalgia\": 0.3, \"gratitude\": 0.7, \"frustration\": 0.2, \"boredom\": 0.0}\n\n";
        $systemPrompt .= "DO NOT include any text before or after the JSON. DO NOT add explanations or additional emotions.";
        
        // Build conversation history for analysis
        $conversationText = "Conversation history:\n";
        foreach ($conversationHistory as $message) {
            $conversationText .= $message['character_name'] . " (" . $message['character_type'] . "): " . $message['message'] . "\n";
        }
        
        $messages = [
            [
                'role' => 'user',
                'content' => $conversationText . "\n\nAnalyze " . $characterName . "'s current emotional state and return the JSON response."
            ]
        ];
        
        try {
            $response = $this->generateResponse($systemPrompt, $messages, 500);
            
            if ($response['success']) {
                // Try to extract JSON from the response - find JSON block in the text
                $jsonPattern = '/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/';
                $matches = [];
                
                if (preg_match($jsonPattern, $response['message'], $matches)) {
                    $jsonString = $matches[0];
                    $emotionData = json_decode($jsonString, true);
                } else {
                    // If no JSON found, try to decode the whole response
                    $emotionData = json_decode($response['message'], true);
                }
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($emotionData)) {
                    // Validate that all required emotions are present and values are valid
                    $requiredEmotions = ['joy', 'sadness', 'fear', 'anger', 'surprise', 'disgust',
                                       'trust', 'anticipation', 'shame', 'love', 'contempt', 
                                       'loneliness', 'pride', 'envy', 'nostalgia', 'gratitude',
                                       'frustration', 'boredom'];
                    
                    $validEmotions = [];
                    foreach ($requiredEmotions as $emotion) {
                        if (isset($emotionData[$emotion])) {
                            $value = floatval($emotionData[$emotion]);
                            $validEmotions[$emotion] = max(0, min(1, round($value, 1)));
                        } else {
                            $validEmotions[$emotion] = 0.5; // Default neutral value
                        }
                    }
                    
                    return [
                        'success' => true,
                        'emotions' => $validEmotions,
                        'raw_response' => $response['message']
                    ];
                }
            }
            
            return [
                'success' => false,
                'error' => 'Failed to parse emotion analysis response',
                'raw_response' => $response['message'] ?? 'No response'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Make HTTP request to Anthropic API
     * @param array $payload
     * @return array
     * @throws Exception
     */
    private function makeRequest($payload) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: " . $error);
        }
        
        if ($httpCode !== 200) {
            $errorResponse = json_decode($response, true);
            $errorMessage = isset($errorResponse['error']['message']) 
                ? $errorResponse['error']['message'] 
                : "HTTP error: " . $httpCode;
            
            // Add specific handling for rate limits and overload errors
            if ($httpCode === 429 || $httpCode === 529 || strpos($errorMessage, 'overload') !== false) {
                throw new Exception("RATE_LIMIT: " . $errorMessage);
            }
            
            throw new Exception($errorMessage);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }
        
        return $decodedResponse;
    }
    
    /**
     * Validate API key
     * @return bool
     */
    public function validateApiKey() {
        try {
            $testResponse = $this->generateResponse(
                "You are a helpful assistant.",
                [['role' => 'user', 'content' => 'Hello']],
                10
            );
            
            return $testResponse['success'] ?? false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get available models
     * @return array
     */
    public function getAvailableModels() {
        return [
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
            'claude-3-5-sonnet-20240620' => 'Claude 3.5 Sonnet (June)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-3-opus-20240229' => 'Claude 3 Opus'
        ];
    }
    
    /**
     * Set model
     * @param string $model
     */
    public function setModel($model) {
        $this->model = $model;
    }
    
    /**
     * Get current model
     * @return string
     */
    public function getModel() {
        return $this->model;
    }
    
    /**
     * Format conversation history for API
     * @param array $messages
     * @return array
     */
    public function formatConversationHistory($messages) {
        $formatted = [];
        
        foreach ($messages as $message) {
            $formatted[] = [
                'character' => $message['character_name'],
                'type' => $message['character_type'],
                'message' => $message['message'],
                'turn' => $message['turn_number']
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Log API usage for monitoring
     * @param array $usage
     * @param string $dialogId
     */
    public function logUsage($usage, $dialogId) {
        if (!$usage) return;
        
        $logData = [
            'dialog_id' => $dialogId,
            'input_tokens' => $usage['input_tokens'] ?? 0,
            'output_tokens' => $usage['output_tokens'] ?? 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log("Anthropic API Usage: " . json_encode($logData));
    }
}
?> 