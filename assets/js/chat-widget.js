class ChatWidget {
    constructor() {
        this.isInitialized = false;
        this.isLoading = false;
        this.currentUserId = null;
        this.updateInterval = null;
        this.retryCount = 0;
        this.maxRetries = 3;
        
        // Bind methods
        this.init = this.init.bind(this);
        this.toggle = this.toggle.bind(this);
        this.loadUsers = this.loadUsers.bind(this);
        this.updateUnreadCount = this.updateUnreadCount.bind(this);
        
        // Initialize immediately if DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', this.init);
        } else {
            // Small delay to ensure all elements are rendered
            setTimeout(this.init, 50);
        }
    }
    
    init() {
        if (this.isInitialized) return;
        
        try {
            this.chatWidget = document.querySelector('.chat-widget');
            this.chatHeader = document.querySelector('.chat-header');
            this.toggleBtn = document.querySelector('.toggle-chat');
            this.userList = document.querySelector('.user-list');
            this.unreadBadge = document.getElementById('chat-unread-badge');
            
            if (!this.chatWidget) {
                console.warn('Chat widget not found');
                return;
            }
            
            // Set initial state without transitions
            this.chatWidget.style.transition = 'none';
            this.chatWidget.classList.add('minimized');
            
            // Force reflow
            this.chatWidget.offsetHeight;
            
            // Re-enable transitions after initial state is set
            setTimeout(() => {
                this.chatWidget.style.transition = '';
            }, 100);
            
            this.setupEventListeners();
            this.loadUsers();
            this.startPeriodicUpdate();
            
            this.isInitialized = true;
            console.log('Chat widget initialized successfully');
            
        } catch (error) {
            console.error('Error initializing chat widget:', error);
        }
    }
    
    setupEventListeners() {
        if (this.chatHeader) {
            this.chatHeader.addEventListener('click', this.toggle);
        }
        
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });
        }
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isInitialized) {
                this.loadUsers();
            }
        });
        
        // Handle window focus
        window.addEventListener('focus', () => {
            if (this.isInitialized) {
                this.loadUsers();
            }
        });
    }
    
    toggle() {
        if (!this.chatWidget || this.isLoading) return;
        
        const isMinimized = this.chatWidget.classList.contains('minimized');
        
        if (isMinimized) {
            this.chatWidget.classList.remove('minimized');
            this.chatWidget.classList.add('expanded');
            if (this.toggleBtn) {
                this.toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i>';
            }
            this.loadUsers(); // Refresh data when expanding
        } else {
            this.chatWidget.classList.remove('expanded');
            this.chatWidget.classList.add('minimized');
            if (this.toggleBtn) {
                this.toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
            }
        }
    }
    
    async loadUsers() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.chatWidget?.classList.add('loading');
        
        try {
            const response = await fetch('../api/get_chat_users.php', {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                if (this.userList) {
                    // Use requestAnimationFrame for smooth updates
                    requestAnimationFrame(() => {
                        this.userList.innerHTML = data.html;
                        this.updateUnreadCount(data.users);
                    });
                }
                this.retryCount = 0; // Reset retry count on success
            } else {
                throw new Error(data.message || 'Failed to load users');
            }
            
        } catch (error) {
            console.error('Error loading chat users:', error);
            this.handleLoadError();
        } finally {
            this.isLoading = false;
            this.chatWidget?.classList.remove('loading');
        }
    }
    
    handleLoadError() {
        this.retryCount++;
        
        if (this.retryCount <= this.maxRetries) {
            console.log(`Retrying to load users (${this.retryCount}/${this.maxRetries})...`);
            setTimeout(() => this.loadUsers(), 2000 * this.retryCount);
        } else {
            console.error('Max retries reached. Stopping automatic retry.');
            if (this.userList) {
                this.userList.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Gagal memuat daftar pengguna</div>';
            }
        }
    }
    
    updateUnreadCount(users) {
        if (!this.unreadBadge || !Array.isArray(users)) return;
        
        const totalUnread = users.reduce((total, user) => {
            return total + (parseInt(user.unread_count) || 0);
        }, 0);
        
        requestAnimationFrame(() => {
            if (totalUnread > 0) {
                this.unreadBadge.textContent = totalUnread;
                this.unreadBadge.classList.add('show');
                this.unreadBadge.style.display = 'inline-flex';
            } else {
                this.unreadBadge.classList.remove('show');
                this.unreadBadge.style.display = 'none';
            }
        });
    }
    
    startPeriodicUpdate() {
        // Clear existing interval
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        
        // Update every 30 seconds
        this.updateInterval = setInterval(() => {
            if (this.isInitialized && !document.hidden) {
                this.loadUsers();
            }
        }, 30000);
    }
    
    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
        
        this.isInitialized = false;
        this.isLoading = false;
        this.retryCount = 0;
        
        // Remove event listeners
        if (this.chatHeader) {
            this.chatHeader.removeEventListener('click', this.toggle);
        }
        
        if (this.toggleBtn) {
            this.toggleBtn.removeEventListener('click', this.toggle);
        }
    }
}

// Initialize chat widget
let chatWidgetInstance = null;

// Ensure clean initialization
function initializeChatWidget() {
    // Destroy existing instance
    if (chatWidgetInstance) {
        chatWidgetInstance.destroy();
    }
    
    // Create new instance
    chatWidgetInstance = new ChatWidget();
}

// Initialize on DOM ready or immediately if already ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeChatWidget);
} else {
    initializeChatWidget();
}

// Handle page unload
window.addEventListener('beforeunload', () => {
    if (chatWidgetInstance) {
        chatWidgetInstance.destroy();
    }
});

// Export for global access
window.ChatWidget = ChatWidget;
window.chatWidgetInstance = chatWidgetInstance;