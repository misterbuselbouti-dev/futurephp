// FUTURE AUTOMOTIVE - Navbar Time Display
// Real-time clock for the top navigation bar

function updateDateTime() {
    const now = new Date();
    
    // Format options
    const options = {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };
    
    // Get formatted date/time
    const dateTimeString = now.toLocaleDateString('fr-FR', options);
    
    // Update the display
    const element = document.getElementById('currentDateTime');
    if (element) {
        element.textContent = dateTimeString;
    }
}

// Update immediately when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    
    // Update every second
    setInterval(updateDateTime, 1000);
});

// Also update when the element exists (for dynamic content)
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length) {
            const element = document.getElementById('currentDateTime');
            if (element && !element.textContent) {
                updateDateTime();
            }
        }
    });
});

observer.observe(document.body, {
    childList: true,
    subtree: true
});
