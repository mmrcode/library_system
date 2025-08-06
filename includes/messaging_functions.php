<?php
/**
 * Messaging Functions for Library Management System
 * Handles in-app messaging between students and librarians
 * Created by: Mohammad Muqsit Raja (BCA22739)
 */

require_once 'config.php';
require_once 'database.php';

class MessagingSystem {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Check if a table exists
     */
    private function tableExists($tableName) {
        try {
            $result = $this->db->query("SHOW TABLES LIKE '{$tableName}'");
            return ($result && $result->num_rows > 0);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Send a message
     */
    public function sendMessage($sender_id, $receiver_id, $subject, $message_body, $message_type = 'general', $related_request_id = null, $related_issue_id = null, $reply_to = null) {
        // Check if messages table exists
        if (!$this->tableExists('messages')) {
            return [
                'success' => false,
                'message' => 'Messaging system is not yet installed. Please contact the administrator.'
            ];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, message_body, message_type, related_request_id, related_issue_id, reply_to)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissiiii", $sender_id, $receiver_id, $subject, $message_body, $message_type, $related_request_id, $related_issue_id, $reply_to);
            $stmt->execute();
            
            $message_id = $this->db->insert_id;
            
            // Create notification for receiver
            $this->createMessageNotification($receiver_id, $sender_id, $subject, $message_id);
            
            return [
                'success' => true,
                'message' => 'Message sent successfully!',
                'message_id' => $message_id
            ];
            
        } catch (Exception $e) {
            error_log("Error sending message: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send message. Please try again.'
            ];
        }
    }
    
    /**
     * Get user's messages (inbox)
     */
    public function getUserMessages($user_id, $type = 'received', $limit = 50) {
        try {
            $field = $type === 'sent' ? 'sender_id' : 'receiver_id';
            
            $stmt = $this->db->prepare("
                SELECT m.*, 
                       sender.full_name as sender_name,
                       receiver.full_name as receiver_name,
                       sender.user_type as sender_type,
                       receiver.user_type as receiver_type
                FROM messages m
                JOIN users sender ON m.sender_id = sender.user_id
                JOIN users receiver ON m.receiver_id = receiver.user_id
                WHERE m.{$field} = ? AND m.is_archived = 'no'
                ORDER BY m.sent_date DESC
                LIMIT ?
            ");
            $stmt->bind_param("ii", $user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user messages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get message details
     */
    public function getMessage($message_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, 
                       sender.full_name as sender_name,
                       receiver.full_name as receiver_name,
                       sender.user_type as sender_type,
                       receiver.user_type as receiver_type
                FROM messages m
                JOIN users sender ON m.sender_id = sender.user_id
                JOIN users receiver ON m.receiver_id = receiver.user_id
                WHERE m.message_id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
            ");
            $stmt->execute([$message_id, $user_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Mark message as read
     */
    public function markAsRead($message_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE messages 
                SET is_read = 'yes', read_date = NOW() 
                WHERE message_id = ? AND receiver_id = ?
            ");
            $stmt->execute([$message_id, $user_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get conversation thread
     */
    public function getConversationThread($message_id, $user_id) {
        try {
            // First, get the root message
            $root_message = $this->getMessage($message_id, $user_id);
            if (!$root_message) return [];
            
            // Find the root of the conversation
            $root_id = $root_message['reply_to'] ? $this->findRootMessage($root_message['reply_to']) : $message_id;
            
            // Get all messages in the thread
            $stmt = $this->db->prepare("
                SELECT m.*, 
                       sender.full_name as sender_name,
                       receiver.full_name as receiver_name,
                       sender.user_type as sender_type,
                       receiver.user_type as receiver_type
                FROM messages m
                JOIN users sender ON m.sender_id = sender.user_id
                JOIN users receiver ON m.receiver_id = receiver.user_id
                WHERE (m.message_id = ? OR m.reply_to = ? OR m.message_id IN (
                    SELECT message_id FROM messages WHERE reply_to = ?
                )) AND (m.sender_id = ? OR m.receiver_id = ?)
                ORDER BY m.sent_date ASC
            ");
            $stmt->execute([$root_id, $root_id, $root_id, $user_id, $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Find root message of a thread
     */
    private function findRootMessage($message_id) {
        try {
            $stmt = $this->db->prepare("SELECT reply_to FROM messages WHERE message_id = ?");
            $stmt->execute([$message_id]);
            $reply_to = $stmt->fetchColumn();
            
            if ($reply_to) {
                return $this->findRootMessage($reply_to);
            }
            return $message_id;
        } catch (PDOException $e) {
            return $message_id;
        }
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount($user_id) {
        if (!$this->tableExists('messages')) {
            return 0;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM messages 
                WHERE receiver_id = ? AND is_read = 'no' AND is_archived = 'no'
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return $row[0];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Archive message
     */
    public function archiveMessage($message_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE messages 
                SET is_archived = 'yes' 
                WHERE message_id = ? AND (sender_id = ? OR receiver_id = ?)
            ");
            $stmt->execute([$message_id, $user_id, $user_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Star/unstar message
     */
    public function toggleStar($message_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE messages 
                SET is_starred = CASE WHEN is_starred = 'yes' THEN 'no' ELSE 'yes' END
                WHERE message_id = ? AND (sender_id = ? OR receiver_id = ?)
            ");
            $stmt->execute([$message_id, $user_id, $user_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all librarians/admins for messaging
     */
    public function getLibrarians() {
        if (!$this->tableExists('users')) {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, full_name, email 
                FROM users 
                WHERE user_type = 'admin' AND status = 'active'
                ORDER BY full_name
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $librarians = [];
            while ($row = $result->fetch_assoc()) {
                $librarians[] = $row;
            }
            return $librarians;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get all students for messaging (for librarians)
     */
    public function getStudents($search = '') {
        try {
            $sql = "
                SELECT user_id, full_name, email, registration_number 
                FROM users 
                WHERE user_type = 'student' AND status = 'active'
            ";
            $params = [];
            
            if ($search) {
                $sql .= " AND (full_name LIKE ? OR registration_number LIKE ? OR email LIKE ?)";
                $search_term = "%{$search}%";
                $params = [$search_term, $search_term, $search_term];
            }
            
            $sql .= " ORDER BY full_name LIMIT 50";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Create message notification
     */
    private function createMessageNotification($receiver_id, $sender_id, $subject, $message_id) {
        try {
            // Get sender name
            $stmt = $this->db->prepare("SELECT full_name FROM users WHERE user_id = ?");
            $stmt->execute([$sender_id]);
            $sender_name = $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type, action_url, action_text)
                VALUES (?, ?, ?, 'info', ?, 'View Message')
            ");
            $stmt->execute([
                $receiver_id,
                'New Message from ' . $sender_name,
                'Subject: ' . $subject,
                "student/messages.php?message_id={$message_id}"
            ]);
        } catch (PDOException $e) {
            error_log("Error creating message notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send message to all librarians
     */
    public function sendMessageToLibrarians($sender_id, $subject, $message_body, $message_type = 'general', $related_request_id = null) {
        $librarians = $this->getLibrarians();
        $sent_count = 0;
        
        foreach ($librarians as $librarian) {
            $result = $this->sendMessage(
                $sender_id,
                $librarian['user_id'],
                $subject,
                $message_body,
                $message_type,
                $related_request_id
            );
            if ($result['success']) {
                $sent_count++;
            }
        }
        
        return [
            'success' => $sent_count > 0,
            'message' => "Message sent to {$sent_count} librarian(s).",
            'sent_count' => $sent_count
        ];
    }
    
    /**
     * Get messaging statistics
     */
    public function getMessagingStatistics($user_id = null) {
        try {
            $stats = [];
            
            if ($user_id) {
                // User-specific stats
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ?");
                $stmt->execute([$user_id]);
                $stats['total_received'] = $stmt->fetchColumn();
                
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ?");
                $stmt->execute([$user_id]);
                $stats['total_sent'] = $stmt->fetchColumn();
                
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 'no'");
                $stmt->execute([$user_id]);
                $stats['unread'] = $stmt->fetchColumn();
            } else {
                // System-wide stats
                $stmt = $this->db->query("SELECT COUNT(*) FROM messages");
                $stats['total_messages'] = $stmt->fetchColumn();
                
                $stmt = $this->db->query("SELECT COUNT(*) FROM messages WHERE DATE(sent_date) = CURDATE()");
                $stats['today_messages'] = $stmt->fetchColumn();
                
                $stmt = $this->db->query("SELECT COUNT(*) FROM messages WHERE is_read = 'no'");
                $stats['unread_messages'] = $stmt->fetchColumn();
            }
            
            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }
}

// Initialize messaging system
$messagingSystem = new MessagingSystem();
?>
