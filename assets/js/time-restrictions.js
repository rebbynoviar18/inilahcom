document.addEventListener('DOMContentLoaded', function() {
    // Find all datetime-local inputs
    const datetimeInputs = document.querySelectorAll('input[type="datetime-local"]');
    
    datetimeInputs.forEach(input => {
        // Add note about time restrictions
        const note = document.createElement('div');
        note.className = 'time-restriction-note';
        note.textContent = 'Waktu hanya tersedia antara 09:00-22:00 dengan interval 30 menit';
        input.parentNode.insertBefore(note, input.nextSibling);
        
        // Add event listener for input change
        input.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            
            // Get hours and minutes
            let hours = selectedDate.getHours();
            let minutes = selectedDate.getMinutes();
            
            // Check if time is within allowed range (09:00-22:00)
            if (hours < 9) hours = 9;
            if (hours > 22 || (hours === 22 && minutes > 0)) hours = 22;
            
            // Round minutes to nearest 30
            minutes = Math.round(minutes / 30) * 30;
            if (minutes === 60) {
                minutes = 0;
                hours++;
            }
            
            // Update the input value
            selectedDate.setHours(hours);
            selectedDate.setMinutes(minutes);
            
            // Format the date back to datetime-local format
            const year = selectedDate.getFullYear();
            const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
            const day = String(selectedDate.getDate()).padStart(2, '0');
            const hoursStr = String(hours).padStart(2, '0');
            const minutesStr = String(minutes).padStart(2, '0');
            
            this.value = `${year}-${month}-${day}T${hoursStr}:${minutesStr}`;
        });
    });
});
