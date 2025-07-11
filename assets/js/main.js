// File: assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
    
    // Sidebar toggle for mobile
    $('#sidebarToggle').click(function() {
        $('.sidebar').toggleClass('active');
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove(); 
        });
    }, 5000);
    
    // Deadline countdown
    $('.deadline-countdown').each(function() {
        const deadline = new Date($(this).data('deadline'));
        const now = new Date();
        const diff = deadline - now;
        
        if (diff <= 0) {
            $(this).html('<span class="text-danger">Terlambat</span>');
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        
        let text = '';
        if (days > 0) {
            text += `${days} hari `;
        }
        text += `${hours} jam`;
        
        if (days <= 1) {
            $(this).addClass('text-warning');
        }
        
        $(this).text(text);
    });
    
    // Task status change handler
    $('.task-status-change').change(function() {
        const taskId = $(this).data('task-id');
        const newStatus = $(this).val();
        
        if (confirm('Anda yakin ingin mengubah status task ini?')) {
            $.ajax({
                url: '../api/update_task_status.php',
                method: 'POST',
                data: {
                    task_id: taskId,
                    status: newStatus
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Gagal mengubah status task');
                }
            });
        } else {
            $(this).val($(this).data('current-status'));
        }
    });
    
    // Priority color coding
    $('.task-row').each(function() {
        const priority = $(this).data('priority');
        const deadline = new Date($(this).data('deadline'));
        const now = new Date();
        
        if (priority === 'high') {
            $(this).addClass('priority-high');
        } else if (priority === 'medium') {
            $(this).addClass('priority-medium');
        } else {
            $(this).addClass('priority-low');
        }
        
        // Highlight overdue tasks
        if (deadline < now && $(this).data('status') !== 'completed') {
            $(this).addClass('bg-light-danger');
        }
    });
});