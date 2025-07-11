<?php
// Pastikan user sudah login
if (!isLoggedIn()) return;

// Periksa apakah kolom active ada di tabel users
$checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
$activeColumnExists = $checkColumn->rowCount() > 0;

// Ambil daftar pengguna untuk chat
$sql = "
    SELECT u.id, u.name, u.profile_photo, u.role, 
           (SELECT MAX(cm.created_at) FROM chat_messages cm 
            WHERE (cm.sender_id = u.id AND cm.receiver_id = :user_id) 
               OR (cm.sender_id = :user_id AND cm.receiver_id = u.id)) as last_message_time,
           (SELECT COUNT(*) FROM chat_messages cm 
            WHERE cm.sender_id = u.id AND cm.receiver_id = :user_id AND cm.is_read = 0) as unread_count
    FROM users u 
    WHERE u.id != :user_id
";

// Tambahkan kondisi active jika kolom tersebut ada
if ($activeColumnExists) {
    $sql .= " AND u.active = 1";
}

$sql .= " ORDER BY last_message_time IS NULL, last_message_time DESC, u.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$chatUsers = $stmt->fetchAll();

// Hitung total pesan yang belum dibaca
$totalUnread = 0;
foreach ($chatUsers as $user) {
    $totalUnread += $user['unread_count'];
}

// Fungsi untuk memeriksa apakah user online
// REMOVE THIS FUNCTION DEFINITION - it's already defined in user_functions.php
// Instead, make sure user_functions.php is included before this file
/* 
function isUserOnline($userId) {
    global $pdo;
    
    // Cek apakah tabel user_sessions ada
    $checkTable = $pdo->query("SHOW TABLES LIKE 'user_sessions'");
    if ($checkTable->rowCount() == 0) {
        // Jika tidak ada, anggap semua user online
        return true;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT last_activity 
            FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY last_activity DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $session = $stmt->fetch();
        
        if ($session) {
            // User dianggap online jika aktivitas terakhir < 5 menit
            $lastActivity = strtotime($session['last_activity']);
            return (time() - $lastActivity) < 300; // 5 menit
        }
        return false;
    } catch (Exception $e) {
        // Jika error, anggap user online
        return true;
    }
}
*/
?>
<!-- Chat Widget -->
<div class="chat-widget minimized">
    <div class="chat-header">
        <div class="chat-title">
            <i class="fas fa-comments"></i> Chat
            <?php if ($totalUnread > 0): ?>
                <span id="chat-unread-badge" class="badge bg-danger show"><?= $totalUnread ?></span>
            <?php else: ?>
                <span id="chat-unread-badge" class="badge bg-danger" style="display: none;">0</span>
            <?php endif; ?>
        </div>
        <button class="toggle-chat"><i class="fas fa-chevron-up"></i></button>
    </div>
    <div class="chat-body">
        <div class="user-list">
            <?php 
            // Kelompokkan pengguna berdasarkan role
            $usersByRole = [
                'online' => ['title' => 'Online', 'users' => []],
                'content_team' => ['title' => 'Tim Konten', 'users' => []],
                'production_team' => ['title' => 'Tim Produksi', 'users' => []],
                'marketing_team' => ['title' => 'Tim Marketing', 'users' => []],
                'creative_director' => ['title' => 'Management', 'users' => []],
                'other' => ['title' => 'Lainnya', 'users' => []]
            ];

            // Simpan ID pengguna yang online untuk mencegah duplikasi
            $onlineUserIds = [];

            // Kelompokkan pengguna ke dalam kategori role dan online
            foreach ($chatUsers as $user) {
                $isOnline = isUserOnline($user['id']);
                
                // Tambahkan ke grup online jika user sedang online
                if ($isOnline) {
                    $usersByRole['online']['users'][] = $user;
                    $onlineUserIds[] = $user['id']; // Simpan ID pengguna online
                } else {
                    // Hanya tambahkan ke grup role jika tidak online
                    $role = $user['role'] ?? 'other';
                    if (!isset($usersByRole[$role])) {
                        $role = 'other';
                    }
                    $usersByRole[$role]['users'][] = $user;
                }
            }

            // Tampilkan pengguna berdasarkan kelompok role
            foreach ($usersByRole as $role => $group):
                if (empty($group['users'])) continue;
            ?>
                <div class="chat-role-group">
                    <div class="role-title"><?= $group['title'] ?></div>
                    <?php foreach ($group['users'] as $user): 
                        $isOnline = isUserOnline($user['id']);
                    ?>
                        <div class="chat-user" data-user-id="<?= $user['id'] ?>">
                            <?php if (!empty($user['profile_photo'])): ?>
                                <img src="../uploads/profiles/<?= $user['profile_photo'] ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="user-avatar" loading="lazy">
                            <?php else: ?>
                                <div class="user-avatar-placeholder"><?= substr($user['name'], 0, 1) ?></div>
                            <?php endif; ?>
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                                <div class="user-status">
                                    <span class="status-indicator <?= $isOnline ? 'online' : 'offline' ?>"></span>
                                    <span class="status-text"><?= $isOnline ? 'Online' : 'Offline' ?></span>
                                </div>
                            </div>
                            <?php if ($user['unread_count'] > 0): ?>
                                <span class="unread-badge badge bg-danger"><?= $user['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Chat Windows Container -->
<div class="chat-windows-container"></div>

<!-- Notification Sound -->
<audio id="notificationSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

<script>
$(document).ready(function() {
    const currentUserId = <?= $_SESSION['user_id'] ?>;
    let activeChats = {};
    let lastCheck = new Date().getTime();
    let processedMessageIds = new Set(); // Tambahkan ini untuk melacak pesan yang sudah diproses
    let typingTimer = {}; // Timer untuk deteksi ketikan
    
    // Toggle chat widget
    $('.toggle-chat').on('click', function(e) {
        e.stopPropagation();
        $('.chat-widget').toggleClass('minimized');
        
        // Save state to localStorage
        localStorage.setItem('chatWidgetMinimized', $('.chat-widget').hasClass('minimized'));
    });
    
    // Open chat widget when clicking on header
    $('.chat-header').on('click', function() {
        $('.chat-widget').toggleClass('minimized');
        
        // Save state to localStorage
        localStorage.setItem('chatWidgetMinimized', $('.chat-widget').hasClass('minimized'));
    });
    
    // Restore chat widget state from localStorage
    if (localStorage.getItem('chatWidgetMinimized') === 'false') {
        $('.chat-widget').removeClass('minimized');
    }
    
    // Open chat window when clicking on user
    $(document).on('click', '.chat-user', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).find('.user-name').text();
        let avatarUrl = '';
        
        if ($(this).find('.user-avatar').length) {
            avatarUrl = $(this).find('.user-avatar').attr('src');
        }
        
        // Check if chat window already exists
        if ($(`#chat-window-${userId}`).length) {
            $(`#chat-window-${userId}`).show();
            return;
        }
        
        // Create chat window
        const chatWindow = $(`
            <div class="chat-window" id="chat-window-${userId}" data-user-id="${userId}">
                <div class="chat-window-header">
                    <div class="chat-window-title">
                        <img src="${avatarUrl}" alt="${userName}" class="user-avatar" loading="lazy">
                        <span>${userName}</span>
                    </div>
                    <div class="chat-window-actions">
                        <button class="minimize-chat"><i class="fas fa-minus"></i></button>
                        <button class="close-chat"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="messages-container">
                    <div class="loading-messages">
                        <i class="fas fa-spinner fa-spin"></i> Loading messages...
                    </div>
                </div>
                <div class="typing-indicator" style="display: none;">
                    <span>Typing</span>
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
                <form class="message-form">
                    <textarea class="message-input" placeholder="Type a message..." rows="1"></textarea>
                    <button type="submit" class="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        `);
        
        // Add to container
        if (!$('.chat-windows-container').length) {
            $('body').append('<div class="chat-windows-container"></div>');
        }
        
        $('.chat-windows-container').append(chatWindow);
        
        // Load messages
        loadMessages(userId);
        
        // Mark messages as read
        markMessagesAsRead(userId);
        
        // Add to active chats
        activeChats[userId] = true;
        
        // Auto-resize textarea
        const textarea = chatWindow.find('.message-input');
        textarea.on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Send typing indicator
            if (!typingTimer[userId]) {
                sendTypingIndicator(userId, true);
                typingTimer[userId] = true;
            }
            
            // Clear previous timeout
            if (typingTimer[userId]) {
                clearTimeout(typingTimer[userId]);
            }
            
            // Set new timeout
            typingTimer[userId] = setTimeout(function() {
                sendTypingIndicator(userId, false);
                typingTimer[userId] = false;
            }, 2000);
        });
        
        // Remove unread badge
        $(this).find('.unread-badge').remove();
        updateTotalUnreadBadge();
    });
    
    // Close chat window
    $(document).on('click', '.close-chat', function() {
        const chatWindow = $(this).closest('.chat-window');
        const userId = chatWindow.data('user-id');
        
        // Remove from active chats
        delete activeChats[userId];
        
        // Remove with animation
        chatWindow.css('transform', 'translateY(100%)');
        chatWindow.css('opacity', '0');
        
        setTimeout(function() {
            chatWindow.remove();
        }, 300);
    });
    
    // Minimize chat window
    $(document).on('click', '.minimize-chat', function() {
        const chatWindow = $(this).closest('.chat-window');
        chatWindow.hide();
    });
    
    // Send message
    $(document).on('submit', '.message-form', function(e) {
        e.preventDefault();
        
        const chatWindow = $(this).closest('.chat-window');
        const userId = chatWindow.data('user-id');
        const input = chatWindow.find('.message-input');
        const message = input.val().trim();
        
        if (!message) return;
        
        // Clear input and reset height
        input.val('');
        input.css('height', 'auto');
        
        // Send message to server
        $.ajax({
            url: '../api/send_message.php',
            type: 'POST',
            data: {
                receiver_id: userId,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    // Add message to chat
                    appendNewMessages(userId, [response.message]);
                    
                    // Stop typing indicator
                    if (typingTimer[userId]) {
                        clearTimeout(typingTimer[userId]);
                    }
                    sendTypingIndicator(userId, false);
                    typingTimer[userId] = false;
                }
            }
        });
    });
    
    // Function to load messages
    function loadMessages(userId) {
        const messagesContainer = $(`#chat-window-${userId} .messages-container`);
        
        $.ajax({
            url: '../api/get_messages.php',
            type: 'GET',
            data: {
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    messagesContainer.empty();
                    
                    if (response.messages.length === 0) {
                        messagesContainer.html(`
                            <div class="empty-chat">
                                <i class="far fa-comments"></i>
                                <p>No messages yet.<br>Start a conversation!</p>
                            </div>
                        `);
                        return;
                    }
                    
                    let currentDate = '';
                    
                    response.messages.forEach(function(message) {
                        const msgDate = new Date(message.created_at);
                        const formattedDate = formatDate(msgDate);
                        
                        // Add date separator if needed
                        if (formattedDate !== currentDate) {
                            currentDate = formattedDate;
                            messagesContainer.append(`
                                <div class="date-separator">${currentDate}</div>
                            `);
                        }
                        
                        // Format time
                        const messageTime = formatTime(msgDate);
                        
                        // Determine message class
                        const messageClass = message.sender_id == currentUserId ? 'sent' : 'received';
                        
                        // Add message
                        messagesContainer.append(`
                            <div class="message ${messageClass}" data-id="${message.id}">
                                <div class="message-content">${message.message}</div>
                                <div class="message-time">${messageTime}</div>
                            </div>
                        `);
                    });
                    
                    // Scroll to bottom
                    scrollToBottom(messagesContainer);
                }
            }
        });
    }
    
    // Function to append new messages
    function appendNewMessages(userId, messages) {
        const messagesContainer = $(`#chat-window-${userId} .messages-container`);
        
        // Remove empty chat message if present
        messagesContainer.find('.empty-chat').remove();
        
        let currentDate = getLastMessageDate(messagesContainer);
        
        messages.forEach(function(message) {
            const msgDate = new Date(message.created_at);
            const formattedDate = formatDate(msgDate);
            
            // Add date separator if needed
            if (formattedDate !== currentDate) {
                currentDate = formattedDate;
                messagesContainer.append(`
                    <div class="date-separator">${currentDate}</div>
                `);
            }
            
            // Format time
            const messageTime = formatTime(msgDate);
            
            // Determine message class
            const messageClass = message.sender_id == currentUserId ? 'sent' : 'received';
            
            // Add message with animation
            const messageElement = $(`
                <div class="message ${messageClass}" data-id="${message.id}" style="opacity: 0; transform: translateY(10px);">
                    <div class="message-content">${message.message}</div>
                    <div class="message-time">${messageTime}</div>
                </div>
            `);
            
            messagesContainer.append(messageElement);
            
            // Trigger animation
            setTimeout(function() {
                messageElement.css({
                    'opacity': '1',
                    'transform': 'translateY(0)'
                });
            }, 10);
        });
        
        // Scroll to bottom
        scrollToBottom(messagesContainer);
    }
    
    // Function to get the last message date
    function getLastMessageDate(container) {
        const lastDateSeparator = container.find('.date-separator').last();
        return lastDateSeparator.length ? lastDateSeparator.text() : '';
    }
    
    // Function to format date
    function formatDate(date) {
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (date.toDateString() === today.toDateString()) {
            return 'Today';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        } else {
            return date.toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric' 
            });
        }
    }
    
    // Function to format time
    function formatTime(date) {
        return date.toLocaleTimeString('id-ID', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
    
    // Function to scroll to bottom
    function scrollToBottom(container) {
        container.scrollTop(container[0].scrollHeight);
    }
    
    // Function to mark messages as read
    function markMessagesAsRead(userId) {
        $.ajax({
            url: '../api/mark_messages_read.php',
            type: 'POST',
            data: {
                sender_id: userId
            },
            success: function(response) {
                if (response.success) {
                    // Update unread badge
                    $(`.chat-user[data-user-id="${userId}"]`).find('.unread-badge').remove();
                    updateTotalUnreadBadge();
                }
            }
        });
    }
    
    // Function to update total unread badge
    function updateTotalUnreadBadge() {
        let totalUnread = 0;
        
        $('.chat-user .unread-badge').each(function() {
            totalUnread += parseInt($(this).text());
        });
        
        const unreadBadge = $('#chat-unread-badge');
        
        if (totalUnread > 0) {
            unreadBadge.text(totalUnread).show().addClass('show');
        } else {
            unreadBadge.hide().removeClass('show');
        }
    }
    
    // Function to send typing indicator
    function sendTypingIndicator(userId, isTyping) {
        $.ajax({
            url: '../api/typing_indicator.php',
            type: 'POST',
            data: {
                receiver_id: userId,
                is_typing: isTyping ? 1 : 0
            }
        });
    }
    
    // Function to check for new messages
    function checkNewMessages() {
        const now = new Date().getTime();
        
        $.ajax({
            url: '../api/check_new_messages.php',
            type: 'GET',
            data: {
                last_check: lastCheck
            },
            success: function(response) {
                if (response.success) {
                    lastCheck = now;
                    
                    // Process new messages
                    if (response.messages && response.messages.length > 0) {
                        const messagesByUser = {};
                        
                        // Group messages by user and filter already processed messages
                        response.messages.forEach(function(message) {
                            // Skip if we've already processed this message
                            if (processedMessageIds.has(message.id)) {
                                return;
                            }
                            
                            // Add to processed set
                            processedMessageIds.add(message.id);
                            
                            if (message.sender_id != currentUserId) {
                                if (!messagesByUser[message.sender_id]) {
                                    messagesByUser[message.sender_id] = [];
                                }
                                messagesByUser[message.sender_id].push(message);
                            }
                        });
                        
                        // Process each user's messages
                        for (const userId in messagesByUser) {
                            // If chat window is open, append messages and mark as read
                            if (activeChats[userId]) {
                                appendNewMessages(userId, messagesByUser[userId]);
                                markMessagesAsRead(userId);
                            } else {
                                // Otherwise, update unread count
                                const userItem = $(`.chat-user[data-user-id="${userId}"]`);
                                let unreadBadge = userItem.find('.unread-badge');
                                
                                if (unreadBadge.length) {
                                    const count = parseInt(unreadBadge.text()) + messagesByUser[userId].length;
                                    unreadBadge.text(count);
                                } else {
                                    userItem.append(`<span class="unread-badge badge bg-danger">${messagesByUser[userId].length}</span>`);
                                }
                                
                                // Play notification sound
                                $('#notificationSound')[0].play();
                                
                                // Move user to top of list
                                userItem.prependTo('.user-list');
                            }
                        }
                        
                        // Update total unread badge
                        updateTotalUnreadBadge();
                    }
                    
                    // Process typing indicators
                    if (response.typing_users) {
                        for (const userId in response.typing_users) {
                            const isTyping = response.typing_users[userId];
                            const typingIndicator = $(`#chat-window-${userId} .typing-indicator`);
                            
                            if (isTyping) {
                                typingIndicator.show();
                            } else {
                                typingIndicator.hide();
                            }
                        }
                    }
                    
                    // Process online status updates
                    if (response.online_users) {
                        $('.chat-user').each(function() {
                            const userId = $(this).data('user-id');
                            const statusIndicator = $(this).find('.status-indicator');
                            const statusText = $(this).find('.status-text');
                            
                            if (response.online_users.includes(parseInt(userId))) {
                                statusIndicator.addClass('online').removeClass('offline');
                                statusText.text('Online');
                            } else {
                                statusIndicator.removeClass('online').addClass('offline');
                                statusText.text('Offline');
                            }
                        });
                    }
                }
            }
        });
    }
    
    // Check for new messages periodically
    setInterval(checkNewMessages, 3000);
    
    // Initial check
    checkNewMessages();
    
    // Update online status on page load
    $('.chat-user').each(function() {
        const userId = $(this).data('user-id');
        const isOnline = $(this).find('.user-status').hasClass('online');
        
        $(this).find('.status-indicator').addClass(isOnline ? 'online' : 'offline');
        $(this).find('.status-text').text(isOnline ? 'Online' : 'Offline');
    });
    
    // Limit the size of processedMessageIds set to prevent memory issues
    setInterval(function() {
        if (processedMessageIds.size > 1000) {
            // Convert to array, slice to keep only the last 500, then convert back to Set
            const messageArray = Array.from(processedMessageIds);
            processedMessageIds = new Set(messageArray.slice(messageArray.length - 500));
        }
    }, 60000); // Check every minute
    
    // Tambahkan CSS untuk animasi typing indicator
    $('head').append(`
        <style>
            .typing-indicator {
                padding: 5px 10px;
                background-color: #f1f1f1;
                border-radius: 10px;
                margin: 5px 0;
                display: flex;
                align-items: center;
                font-size: 0.85rem;
                color: #666;
                max-width: 60%;
                align-self: flex-start;
            }
            
            .typing-dots {
                display: flex;
                margin-left: 8px;
            }
            
            .typing-dot {
                width: 6px;
                height: 6px;
                background-color: #999;
                border-radius: 50%;
                margin: 0 2px;
                animation: typingAnimation 1.5s infinite ease-in-out;
            }
            
            .typing-dot:nth-child(2) {
                animation-delay: 0.2s;
            }
            
            .typing-dot:nth-child(3) {
                animation-delay: 0.4s;
            }
            
            @keyframes typingAnimation {
                0% { transform: translateY(0); }
                50% { transform: translateY(-5px); }
                100% { transform: translateY(0); }
            }
        </style>
    `);

    // Tambahkan event listener untuk tombol Enter pada input pesan
    $(document).on('keydown', '.message-input', function(e) {
        // Jika tombol yang ditekan adalah Enter dan tidak bersamaan dengan Shift
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); // Mencegah default action (newline)
            
            // Temukan tombol kirim yang sesuai dan klik
            $(this).closest('.message-form').find('.send-button').click();
            
            return false;
        }
    });
});
</script>

<style>
/* Chat Widget Styles - Modern & Animated */
.chat-widget {
    position: fixed;
    bottom: 0;
    right: 20px;
    width: 320px;
    background-color: #fff;
    border-radius: 15px 15px 0 0;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    z-index: 1000;
    transition: none;
    overflow: hidden;
}

.chat-widget.minimized {
    transform: translateY(calc(100% - 50px));
}

.chat-widget.minimized .toggle-chat i {
    transform: rotate(180deg);
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: linear-gradient(135deg, #5e72e4, #825ee4);
    color: white;
    border-radius: 15px 15px 0 0;
    cursor: pointer;
    position: relative;
    z-index: 2;
}

.chat-title {
    font-weight: 600;
    display: flex;
    align-items: center;
    font-size: 1.1rem;
}

.chat-title i {
    margin-right: 10px;
    font-size: 1.2rem;
}

#chat-unread-badge {
    margin-left: 8px;
    transform: scale(0);
    transition: transform 0.3s ease;
}

#chat-unread-badge.show {
    transform: scale(1);
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); }
    50% { transform: scale(1.05); }
    100% { transform: scale(0.95); }
}

.toggle-chat {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    transition: transform 0.3s ease;
}

.toggle-chat:hover {
    transform: scale(1.2);
}

.chat-body {
    max-height: 400px;
    overflow-y: auto;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.user-list {
    padding: 0;
}

.chat-user {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    cursor: pointer;
    position: relative;
    border-bottom: 1px solid #eaeaea;
    transition: all 0.2s ease;
    background-color: #fff;
}

.chat-user:hover {
    background-color: #f0f7ff;
    transform: translateX(5px);
}

.chat-user:active {
    transform: scale(0.98);
}

.user-avatar, .user-avatar-placeholder {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-right: 12px;
    object-fit: cover;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.user-avatar-placeholder {
    background: linear-gradient(135deg, #5e72e4, #825ee4);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: 600;
    margin-bottom: 4px;
    color: #333;
}

.user-status {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
    color: #6c757d;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 5px;
    transition: all 0.3s ease;
}

.status-indicator.online {
    background-color: #28a745;
    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
}

.status-indicator.offline {
    background-color: #dc3545;
}

.status-text {
    transition: color 0.3s ease;
}

.online .status-text {
    color: #28a745;
}

.unread-badge {
    position: absolute;
    top: 12px;
    right: 15px;
    font-size: 0.7rem;
    padding: 3px 6px;
    border-radius: 10px;
    animation: bounce 1s infinite alternate;
}

@keyframes bounce {
    from { transform: translateY(-3px); }
    to { transform: translateY(3px); }
}

/* Chat Window Styles */
.chat-windows-container {
    position: fixed;
    bottom: 0;
    right: 340px;
    display: flex;
    gap: 15px;
    z-index: 999;
}

.chat-window {
    width: 320px;
    background-color: #fff;
    border-radius: 15px 15px 0 0;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    max-height: 450px;
    animation: slideUp 0.3s forwards;
}

@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.chat-window-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: linear-gradient(135deg, #5e72e4, #825ee4);
    color: white;
    border-radius: 15px 15px 0 0;
}

.chat-window-title {
    font-weight: 600;
    display: flex;
    align-items: center;
}

.chat-window-title .user-avatar, .chat-window-title .user-avatar-placeholder {
    width: 35px;
    height: 35px;
    margin-right: 10px;
}

.chat-window-actions button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    margin-left: 5px;
    transition: transform 0.2s ease;
}

.chat-window-actions button:hover {
    transform: scale(1.2);
}

.messages-container {
    flex-grow: 1;
    overflow-y: auto;
    padding: 15px;
    display: flex;
    flex-direction: column;
    max-height: 300px;
    background-color: #f8f9fa;
}

.loading-messages {
    text-align: center;
    padding: 20px;
    color: #6c757d;
    font-style: italic;
}

.date-separator {
    text-align: center;
    margin: 15px 0;
    font-size: 0.8rem;
    color: #6c757d;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.date-separator:before, .date-separator:after {
    content: "";
    flex-grow: 1;
    height: 1px;
    background-color: #dee2e6;
    margin: 0 10px;
}

.message {
    max-width: 75%;
    margin-bottom: 12px;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
    animation: fadeIn 0.3s forwards;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message.sent {
    align-self: flex-end;
    background: linear-gradient(135deg, #5e72e4, #825ee4);
    color: white;
    border-bottom-right-radius: 5px;
}

.message.received {
    align-self: flex-start;
    background-color: #f1f1f1;
    color: #333;
    border-bottom-left-radius: 5px;
}

.message-content {
    word-break: break-word;
    line-height: 1.4;
}

.message-time {
    font-size: 0.7rem;
    margin-top: 5px;
    text-align: right;
    opacity: 0.8;
}

.message-form {
    display: flex;
    padding: 12px;
    border-top: 1px solid #eaeaea;
    background-color: #fff;
}

.message-input {
    flex-grow: 1;
    border: 1px solid #ced4da;
    border-radius: 20px;
    padding: 10px 15px;
    margin-right: 10px;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    resize: none;
    max-height: 100px;
    min-height: 40px;
}

.message-input:focus {
    border-color: #5e72e4;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.send-button {
    background: linear-gradient(135deg, #5e72e4, #825ee4);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease;
}

.send-button:hover {
    transform: scale(1.1);
    background: linear-gradient(135deg, #0069d9, #004494);
}

.send-button:active {
    transform: scale(0.95);
}

.typing-indicator {
    display: flex;
    align-items: center;
    padding: 5px 10px;
    font-size: 0.8rem;
    color: #6c757d;
    font-style: italic;
}

.typing-dots {
    display: flex;
    margin-left: 5px;
}

.typing-dot {
    width: 6px;
    height: 6px;
    background-color: #6c757d;
    border-radius: 50%;
    margin: 0 2px;
    animation: typingAnimation 1.5s infinite ease-in-out;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typingAnimation {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-5px); }
}

/* Empty state */
.empty-chat {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    color: #6c757d;
    text-align: center;
}

.empty-chat i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #dee2e6;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .chat-widget {
        width: 280px;
        right: 10px;
    }
    
    .chat-windows-container {
        right: 300px;
    }
    
    .chat-window {
        width: 280px;
    }
}

@media (max-width: 576px) {
    .chat-widget {
        width: 100%;
        right: 0;
        border-radius: 0;
    }
    
    .chat-windows-container {
        width: 100%;
        right: 0;
    }
    
    .chat-window {
        width: 100%;
        border-radius: 0;
    }
    
    .chat-header, .chat-window-header {
        border-radius: 0;
    }
}

/* Tambahkan CSS untuk styling role groups */
.chat-role-group {
    margin-bottom: 10px;
}

.role-title {
    font-weight: bold;
    padding: 5px 10px;
    background-color: #f0f0f0;
    border-radius: 4px;
    margin-bottom: 5px;
    font-size: 0.85rem;
    color: #555;
}
</style>