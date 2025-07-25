<?php
/**
 * Background Dialog Processor
 * Processes dialog jobs and generates turns using Anthropic API
 * Run this script every 30 seconds via cron
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Include bootstrap
require_once __DIR__ . '/../includes/bootstrap.php';

// Log start of processing
error_log("Dialog Processor: Starting processing cycle at " . date('Y-m-d H:i:s'));

try {
    // Initialize Anthropic API
    $anthropicAPI = new AnthropicAPI(defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : null);
    
    // Get pending jobs
    $pendingJobs = $dialogJob->getPendingJobs();
    
    if (empty($pendingJobs)) {
        error_log("Dialog Processor: No pending jobs found");
        exit(0);
    }
    
    error_log("Dialog Processor: Found " . count($pendingJobs) . " pending jobs");
    
    // Process each job
    foreach ($pendingJobs as $job) {
        processDialogJob($job, $dialogJob, $dialog, $character, $anthropicAPI);
    }
    
    // Clean up old jobs
    $cleanedJobs = $dialogJob->cleanupOldJobs();
    if ($cleanedJobs > 0) {
        error_log("Dialog Processor: Cleaned up $cleanedJobs old jobs");
    }
    
    // Reset stuck jobs
    $resetJobs = $dialogJob->resetStuckJobs();
    if ($resetJobs > 0) {
        error_log("Dialog Processor: Reset $resetJobs stuck jobs");
    }
    
    // Reset failed jobs for retry
    $retryJobs = $dialogJob->resetFailedJobs();
    if ($retryJobs > 0) {
        error_log("Dialog Processor: Reset $retryJobs failed jobs for retry");
    }
    
    error_log("Dialog Processor: Processing cycle completed successfully");
    
} catch (Exception $e) {
    error_log("Dialog Processor Error: " . $e->getMessage());
    exit(1);
}

/**
 * Process a single dialog job
 */
function processDialogJob($job, $dialogJob, $dialog, $character, $anthropicAPI) {
    $jobId = $job['id'];
    $dialogId = $job['dialog_id'];
    
    try {
        error_log("Dialog Processor: Processing job $jobId for dialog $dialogId");
        
        // Mark job as in progress
        $dialogJob->updateStatus($jobId, DialogJob::STATUS_IN_PROGRESS);
        
        // Check if job is complete
        if ($job['current_turn'] >= $job['max_turns']) {
            $dialogJob->complete($jobId);
            error_log("Dialog Processor: Job $jobId completed (max turns reached)");
            return;
        }
        
        // Get dialog information
        $dialogData = $dialog->getById($dialogId);
        if (!$dialogData) {
            throw new Exception("Dialog not found: $dialogId");
        }
        
        // Determine which character should speak next
        $nextCharacterType = $job['next_character_type'];
        $characterId = ($nextCharacterType === 'AEI') ? 
            $dialogData['aei_character_id'] : 
            $dialogData['user_character_id'];
        
        // Get character information
        $characterData = $character->getById($characterId);
        if (!$characterData) {
            throw new Exception("Character not found: $characterId");
        }
        
        // Get conversation history
        $conversationHistory = $dialog->getMessages($dialogId);
        
        // Get chat partner information
        $partnerCharacterType = ($nextCharacterType === 'AEI') ? 'User' : 'AEI';
        $partnerCharacterId = ($nextCharacterType === 'AEI') ? $dialogData['user_character_id'] : $dialogData['aei_character_id'];
        $partnerCharacterData = $character->getById($partnerCharacterId);
        
        // Get current emotional state for AEI characters
        $currentEmotions = null;
        if ($nextCharacterType === 'AEI') {
            $currentEmotionalState = $dialog->getEmotionalState($dialogId);
            if ($currentEmotionalState) {
                // Extract emotions without aei_ prefix for API
                $currentEmotions = [];
                foreach (Dialog::EMOTIONS as $emotion) {
                    $currentEmotions[$emotion] = $currentEmotionalState["aei_$emotion"];
                }
                error_log("Dialog Processor: Passing current emotional state to AEI for dialog $dialogId");
            }
        }
        
        // Generate response using Anthropic API
        $formattedHistory = $anthropicAPI->formatConversationHistory($conversationHistory);
        $response = $anthropicAPI->generateDialogTurn(
            $characterData['system_prompt'],
            $dialogData['topic'],
            $formattedHistory,
            $nextCharacterType,
            $characterData['name'],
            $partnerCharacterData ? $partnerCharacterData['name'] : null,
            $partnerCharacterType,
            $currentEmotions
        );
        
        if (!$response['success']) {
            throw new Exception("API Error: " . $response['error']);
        }
        
        // For AEI messages, get current emotional state to store with the message
        $messageEmotions = null;
        $enableEmotionAnalysis = defined('ENABLE_EMOTION_ANALYSIS') ? ENABLE_EMOTION_ANALYSIS : false;
        
        if ($nextCharacterType === 'AEI') {
            // Get current emotional state from dialog (this represents the state BEFORE this message)
            $currentEmotionalState = $dialog->getEmotionalState($dialogId);
            if ($currentEmotionalState) {
                // Extract emotions for message storage (remove aei_ prefix)
                $messageEmotions = [];
                foreach (Dialog::EMOTIONS as $emotion) {
                    $messageEmotions[$emotion] = $currentEmotionalState["aei_$emotion"];
                }
                error_log("Dialog Processor: Using current emotional state for AEI message in dialog $dialogId");
            }
        }
        
        // Build the actual system prompt that was used
        $actualSystemPrompt = $characterData['system_prompt'] . "\n\n";
        $actualSystemPrompt .= "You are participating in a dialog about: " . $dialogData['topic'] . "\n";
        
        if ($characterData['name']) {
            $actualSystemPrompt .= "You are " . $characterData['name'] . " (" . $nextCharacterType . " character).\n";
        } else {
            $actualSystemPrompt .= "You are a " . $nextCharacterType . " character.\n";
        }
        
        if ($partnerCharacterData && $partnerCharacterData['name']) {
            $actualSystemPrompt .= "You are talking with " . $partnerCharacterData['name'] . " (" . $partnerCharacterType . " character).\n";
        }
        
        $actualSystemPrompt .= "Respond naturally and stay in character. Keep responses conversational and engaging.\n";
        $actualSystemPrompt .= "This is part of a training dialog, so make it realistic and helpful.";
        
        // Prepare the full request data for storage
        $fullRequestData = [
            'dialog_id' => $dialogId,
            'character_id' => $characterId,
            'character_name' => $characterData['name'],
            'character_type' => $nextCharacterType,
            'partner_character_name' => $partnerCharacterData ? $partnerCharacterData['name'] : null,
            'partner_character_type' => $partnerCharacterType,
            'system_prompt' => $characterData['system_prompt'],
            'topic' => $dialogData['topic'],
            'conversation_history' => $formattedHistory,
            'turn_number' => $job['current_turn'] + 1,
            'anthropic_request' => [
                'model' => $anthropicAPI->getModel(),
                'system' => $actualSystemPrompt,
                'messages' => $response['anthropic_messages'] ?? [],
                'max_tokens' => defined('ANTHROPIC_MAX_TOKENS') ? ANTHROPIC_MAX_TOKENS : 1000
            ],
            'anthropic_response' => $response,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Save the generated message with request data and emotions
        $turnNumber = $job['current_turn'] + 1;
        $messageAdded = $dialog->addMessage(
            $dialogId,
            $characterId,
            $response['message'],
            $turnNumber,
            json_encode($fullRequestData, JSON_PRETTY_PRINT),
            $messageEmotions // Pass emotions for AEI messages
        );
        
        if (!$messageAdded) {
            throw new Exception("Failed to save message to database");
        }
        
        // Log API usage
        if (isset($response['usage'])) {
            $anthropicAPI->logUsage($response['usage'], $dialogId);
        }
        
        // Continue with next turn processing FIRST, then do emotion analysis
        // Remember who just spoke before updating nextCharacterType
        $currentSpeaker = $nextCharacterType;
        
        // Update job progress
        $nextCharacterType = $dialogJob->getNextCharacterType($nextCharacterType);
        $dialogJob->updateProgress($jobId, $turnNumber, $nextCharacterType);
        
        // Update dialog status to in_progress
        $dialog->update($dialogId, array_merge($dialogData, ['status' => 'in_progress']));
        
        // Check if we've reached max turns
        if ($turnNumber >= $job['max_turns']) {
            $dialogJob->complete($jobId);
            error_log("Dialog Processor: Job $jobId completed after $turnNumber turns");
        } else {
            // Mark as pending for next turn
            $dialogJob->updateStatus($jobId, DialogJob::STATUS_PENDING);
            error_log("Dialog Processor: Job $jobId turn $turnNumber completed, next: $nextCharacterType");
        }
        
        // AFTER saving the message, analyze emotions if AEI just spoke
        if ($enableEmotionAnalysis && $currentSpeaker === 'AEI') {
            try {
                error_log("Dialog Processor: Analyzing emotions after AEI turn $turnNumber in dialog $dialogId for next turn");
                
                // Get updated conversation history including the new message
                $updatedHistory = $dialog->getMessages($dialogId);
                
                // Analyze emotional state based on the complete conversation
                $emotionAnalysis = $anthropicAPI->analyzeEmotionalState(
                    $updatedHistory,
                    $characterData['name'],
                    $dialogData['topic']
                );
                
                if ($emotionAnalysis['success']) {
                    error_log("Dialog Processor: Emotion analysis successful for dialog $dialogId");
                    
                    // Get current emotional state
                    $currentEmotionalState = $dialog->getEmotionalState($dialogId);
                    if ($currentEmotionalState) {
                        // Calculate new emotional state: 70% old + 30% new
                        $newEmotionalState = [];
                        foreach (Dialog::EMOTIONS as $emotion) {
                            $oldValue = $currentEmotionalState["aei_$emotion"];
                            $newValue = $emotionAnalysis['emotions'][$emotion] ?? 0.5;
                            $blendedValue = ($oldValue * 0.7) + ($newValue * 0.3);
                            $newEmotionalState[$emotion] = max(0, min(1, round($blendedValue, 1)));
                        }
                        
                        // Update dialog's emotional state for next turn
                        $dialog->updateEmotionalState($dialogId, $newEmotionalState);
                        error_log("Dialog Processor: Updated dialog emotional state (70% old + 30% new) for dialog $dialogId");
                        
                        // Log the emotional changes for debugging
                        $changes = [];
                        foreach (Dialog::EMOTIONS as $emotion) {
                            $oldVal = $currentEmotionalState["aei_$emotion"];
                            $newVal = $newEmotionalState[$emotion];
                            if (abs($oldVal - $newVal) >= 0.1) {
                                $changes[] = "$emotion: $oldVal -> $newVal";
                            }
                        }
                        if (!empty($changes)) {
                            error_log("Dialog Processor: Significant emotional changes in dialog $dialogId: " . implode(', ', $changes));
                        }
                    }
                } else {
                    error_log("Dialog Processor: Failed to analyze emotions for dialog $dialogId: " . ($emotionAnalysis['error'] ?? 'Unknown error'));
                }
                
            } catch (Exception $e) {
                error_log("Dialog Processor: Emotion analysis error for dialog $dialogId: " . $e->getMessage());
                // Continue processing even if emotion analysis fails
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        error_log("Dialog Processor Error for job $jobId: " . $errorMessage);
        
        // Add delay for rate limit errors before failing
        if (strpos($errorMessage, 'RATE_LIMIT') !== false) {
            error_log("Rate limit detected for job $jobId, will retry later");
            // Set status back to pending with delay
            $dialogJob->updateStatus($jobId, DialogJob::STATUS_PENDING, $errorMessage);
        } else {
            $dialogJob->fail($jobId, $errorMessage);
        }
    }
}

/**
 * Check if script is running in CLI mode
 */
function isCommandLine() {
    return php_sapi_name() === 'cli';
}

/**
 * Validate environment
 */
function validateEnvironment() {
    if (!defined('ANTHROPIC_API_KEY') || ANTHROPIC_API_KEY === 'your_anthropic_api_key_here') {
        throw new Exception("Anthropic API key not configured");
    }
    
    if (!function_exists('curl_init')) {
        throw new Exception("cURL extension not available");
    }
    
    return true;
}

// Validate environment before processing
validateEnvironment();

// Only run in CLI mode for security
if (!isCommandLine()) {
    http_response_code(403);
    echo "This script can only be run from command line";
    exit(1);
}
?> 