<?php
/**
 * Student Messages - Library Management System
 * In-app messaging interface for students
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

define('LIBRARY_SYSTEM', true);

// Include required files
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/student_functions.php';
require_once '../includes/messaging_functions.php';

// Require student access
requireStudent();

$currentUser = getCurrentUser();

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_message':
                $receiver_id = (int)$_POST['receiver_id'];
                $subject = trim($_POST['subject']);
                $message_body = trim($_POST['message_body']);
                $message_type = $_POST['message_type'] ?? 'general';
                
                if ($receiver_id && $subject && $message_body) {
                    $result = $messagingSystem->sendMessage(
                        $currentUser['user_id'],
                        $receiver_id,
                        $subject,
                        $message_body,
                        $message_type
                    );
                    $_SESSION['flash_message'] = $result['message'];
                    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'mark_read':
                $message_id = (int)$_POST['message_id'];
                $messagingSystem->markAsRead($message_id, $currentUser['user_id']);
                break;
                
            case 'archive':
                $message_id = (int)$_POST['message_id'];
                $messagingSystem->archiveMessage($message_id, $currentUser['user_id']);
                break;
                
            case 'toggle_star':
                $message_id = (int)$_POST['message_id'];
                $messagingSystem->toggleStar($message_id, $currentUser['user_id']);
                break;
        }
    }
    
    header('Location: messages.php');
    exit;
}

// Get messages
$type = isset($_GET['type']) ? $_GET['type'] : 'received';
$messages = $messagingSystem->getUserMessages($currentUser['user_id'], $type);
$unread_count = $messagingSystem->getUnreadCount($currentUser['user_id']);

// Get librarians for compose
$librarians = $messagingSystem->getLibrarians();

// View specific message
$view_message = null;
if (isset($_GET['message_id'])) {
    $message_id = (int)$_GET['message_id'];
    $view_message = $messagingSystem->getMessage($message_id, $currentUser['user_id']);
    
    // Mark as read if it's received message
    if ($view_message && $view_message['receiver_id'] == $currentUser['user_id']) {
        $messagingSystem->markAsRead($message_id, $currentUser['user_id']);
    }
}

$pageTitle = 'Messages';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-envelope me-2"></i>Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                        <i class="fas fa-plus me-1"></i>Compose Message
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <div class="row">
                <!-- Message Navigation -->
                <div class="col-md-3">
                    <div class="list-group">
                        <a href="messages.php?type=received" class="list-group-item list-group-item-action <?php echo $type === 'received' ? 'active' : ''; ?>">
                            <i class="fas fa-inbox me-2"></i>Inbox
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php?type=sent" class="list-group-item list-group-item-action <?php echo $type === 'sent' ? 'active' : ''; ?>">
                            <i class="fas fa-paper-plane me-2"></i>Sent
                        </a>
                    </div>
                </div>

                <!-- Messages List or View -->
                <div class="col-md-9">
                    <?php if ($view_message): ?>
                        <!-- Message View -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($view_message['subject']); ?></h5>
                                    <small class="text-muted">
                                        From: <?php echo htmlspecialchars($view_message['sender_name']); ?> 
                                        (<?php echo ucfirst($view_message['sender_type']); ?>)
                                        | <?php echo date('M j, Y g:i A', strtotime($view_message['sent_date'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <a href="messages.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Back
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($view_message['message_body'])); ?>
                                </div>
                                
                                <?php if ($view_message['message_type'] !== 'general'): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Message Type: <?php echo ucfirst(str_replace('_', ' ', $view_message['message_type'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary" onclick="replyToMessage(<?php echo $view_message['message_id']; ?>, '<?php echo htmlspecialchars($view_message['sender_name']); ?>', '<?php echo htmlspecialchars($view_message['subject']); ?>')">
                                    <i class="fas fa-reply me-1"></i>Reply
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Messages List -->
                        <?php if (!empty($messages)): ?>
                            <div class="list-group">
                                <?php foreach ($messages as $message): ?>
                                    <a href="messages.php?message_id=<?php echo $message['message_id']; ?>" 
                                       class="list-group-item list-group-item-action <?php echo $message['is_read'] === 'no' && $message['receiver_id'] == $currentUser['user_id'] ? 'list-group-item-light border-start border-primary border-3' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 <?php echo $message['is_read'] === 'no' && $message['receiver_id'] == $currentUser['user_id'] ? 'fw-bold' : ''; ?>">
                                                <?php echo htmlspecialchars($message['subject']); ?>
                                                <?php if ($message['is_starred'] === 'yes'): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php endif; ?>
                                            </h6>
                                            <small><?php echo date('M j', strtotime($message['sent_date'])); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <?php 
                                            $contact_name = $type === 'sent' ? $message['receiver_name'] : $message['sender_name'];
                                            $contact_type = $type === 'sent' ? $message['receiver_type'] : $message['sender_type'];
                                            echo htmlspecialchars($contact_name) . ' (' . ucfirst($contact_type) . ')';
                                            ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo substr(htmlspecialchars($message['message_body']), 0, 100) . '...'; ?>
                                        </small>
                                        
                                        <?php if ($message['message_type'] !== 'general'): ?>
                                            <span class="badge bg-info ms-2"><?php echo ucfirst(str_replace('_', ' ', $message['message_type'])); ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- No Messages -->
                            <div class="text-center py-5">
                                <i class="fas fa-envelope fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No messages found</h4>
                                <p class="text-muted">
                                    <?php echo $type === 'sent' ? "You haven't sent any messages yet." : "You don't have any messages in your inbox."; ?>
                                </p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                                    <i class="fas fa-plus me-1"></i>Send Your First Message
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Compose Message Modal -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="composeModalLabel">
                    <i class="fas fa-edit me-2"></i>Compose Message
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="reply_to" id="replyToId">
                    
                    <div class="mb-3">
                        <label for="receiver_id" class="form-label">To</label>
                        <select class="form-select" name="receiver_id" id="receiver_id" required>
                            <option value="">Select recipient...</option>
                            <?php foreach ($librarians as $librarian): ?>
                                <option value="<?php echo $librarian['user_id']; ?>">
                                    <?php echo htmlspecialchars($librarian['full_name']); ?> (Librarian)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message_type" class="form-label">Message Type</label>
                        <select class="form-select" name="message_type" id="message_type">
                            <option value="general">General Inquiry</option>
                            <option value="book_request">Book Request</option>
                            <option value="book_issue">Book Issue</option>
                            <option value="fine">Fine Related</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" name="subject" id="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message_body" class="form-label">Message</label>
                        <textarea class="form-control" name="message_body" id="message_body" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function replyToMessage(messageId, senderName, originalSubject) {
    // Set reply data
    document.getElementById('replyToId').value = messageId;
    
    // Pre-fill subject with "Re: " prefix
    const subject = originalSubject.startsWith('Re: ') ? originalSubject : 'Re: ' + originalSubject;
    document.getElementById('subject').value = subject;
    
    // Show compose modal
    const composeModal = new bootstrap.Modal(document.getElementById('composeModal'));
    composeModal.show();
}
</script>

<?php include '../includes/student_footer.php'; ?>
