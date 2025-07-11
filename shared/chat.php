<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
$userId = $_SESSION['user_id'];

// Ambil daftar pengguna untuk chat - tanpa filter status
$stmt = $pdo->prepare("
    SELECT id, name, profile_photo, 
    (SELECT MAX(created_at) FROM chat_messages WHERE (sender_id = users.id AND receiver_id = ?) 
     OR (sender_id = ? AND receiver_id = users.id)) as last_message_time
    FROM users 
    WHERE id != ?
    ORDER BY CASE WHEN last_message_time IS NULL THEN 1 ELSE 0 END, 
             last_message_time DESC, 
             name ASC
");
$stmt->execute([$userId, $userId, $userId]);
$users = $stmt->fetchAll();

$pageTitle = "Live Chat";
include '../includes/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5>Kontak</h5>
                    <div class="input-group mt-2">
                        <input type="text" class="form-control" id="searchUsers" placeholder="Cari pengguna...">
                        <button class="btn btn-outline-secondary" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group user-list">
                        <?php foreach ($users as $user): ?>
                            <a href="#" class="list-group-item list-group-item-action user-item" data-user-id="<?= $user['id'] ?>">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($user['profile_photo'])): ?>
                                        <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>" 
                                             class="rounded-circle me-2" width="40" height="40" alt="">
                                    <?php else: ?>
                                        <div class="avatar-circle me-2" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; background-color: #007bff; color: white; border-radius: 50%;">
                                            <span class="avatar-text"><?= substr($user['name'], 0, 1) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($user['name']) ?></h6>
                                        <small class="text-muted last-seen">Online</small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header chat-header">
                    <h5>Pilih kontak untuk memulai chat</h5>
                </div>
                <div class="card-body chat-body">
                    <div class="chat-messages" id="chatMessages">
                        <div class="text-center empty-chat-message">
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            <p>Pilih kontak untuk memulai percakapan</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer chat-footer d-none">
                    <form id="chatForm" class="d-flex">
                        <input type="hidden" id="receiverId" value="">
                        <input type="text" class="form-control me-2" id="messageInput" placeholder="Ketik pesan...">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = <?= $userId ?>;
    let selectedUserId = null;
    let chatPollingInterval = null;
    
    // Fungsi untuk memuat pesan
    function loadMessages(userId) {
        fetch(`../api/get_messages.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const chatMessages = document.getElementById('chatMessages');
                    chatMessages.innerHTML = '';
                    
                    data.messages.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = message.sender_id == currentUserId ? 
                            'message-bubble sent' : 'message-bubble received';
                        
                        messageDiv.innerHTML = `
                            <div class="message-content">${message.message}</div>
                            <div class="message-time">${message.formatted_time}</div>
                        `;
                        
                        chatMessages.appendChild(messageDiv);
                    });
                    
                    // Scroll to bottom
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(error => console.error('Error loading messages:', error));
    }
    
    // Pilih user untuk chat
    document.querySelectorAll('.user-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Hapus kelas aktif dari semua item
            document.querySelectorAll('.user-item').forEach(el => {
                el.classList.remove('active');
            });
            
            // Tambahkan kelas aktif ke item yang dipilih
            this.classList.add('active');
            
            // Ambil ID dan nama pengguna
            selectedUserId = this.dataset.userId;
            const userName = this.querySelector('h6').textContent;
            
            // Update header chat
            document.querySelector('.chat-header h5').textContent = userName;
            
            // Tampilkan footer chat
            document.querySelector('.chat-footer').classList.remove('d-none');
            
            // Set receiver ID
            document.getElementById('receiverId').value = selectedUserId;
            
            // Muat pesan
            loadMessages(selectedUserId);
            
            // Mulai polling pesan baru
            if (chatPollingInterval) {
                clearInterval(chatPollingInterval);
            }
            
            chatPollingInterval = setInterval(() => {
                if (selectedUserId) {
                    loadMessages(selectedUserId);
                }
            }, 5000); // Poll setiap 5 detik
        });
    });
    
    // Kirim pesan
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        const receiverId = document.getElementById('receiverId').value;
        
        if (message && receiverId) {
            fetch('../api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${receiverId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadMessages(receiverId);
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }
    });
    
    // Pencarian pengguna
    document.getElementById('searchUsers').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        document.querySelectorAll('.user-item').forEach(item => {
            const userName = item.querySelector('h6').textContent.toLowerCase();
            
            if (userName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>

<style>
.user-list {
    max-height: 500px;
    overflow-y: auto;
}

.chat-body {
    height: 400px;
    overflow-y: auto;
}

.chat-messages {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.empty-chat-message {
    margin-top: auto;
    margin-bottom: auto;
}

.message-bubble {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    margin-bottom: 10px;
    position: relative;
}

.message-bubble.sent {
    align-self: flex-end;
    background-color: #dcf8c6;
    border-bottom-right-radius: 5px;
}

.message-bubble.received {
    align-self: flex-start;
    background-color: #f1f0f0;
    border-bottom-left-radius: 5px;
}

.message-time {
    font-size: 0.7rem;
    color: #999;
    text-align: right;
    margin-top: 3px;
}

.user-item.active {
    background-color: #e9ecef;
}
</style>

<?php include '../includes/footer.php'; ?>