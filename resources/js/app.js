import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Helper function to check if mobile
function checkIsMobile() {
    return window.innerWidth < 1024;
}

// Create a global store for sidebar state
Alpine.store('sidebar', {
    open: false,
    isMobile: checkIsMobile(),
    
    toggle() {
        // Update isMobile state before toggling
        this.isMobile = checkIsMobile();
        
        // Always allow toggle on mobile (screen width < 1024px)
        // Check both stored value and current window width for reliability
        if (this.isMobile || window.innerWidth < 1024) {
            this.open = !this.open;
        }
    }
});

// Initialize store state after Alpine starts
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const store = Alpine.store('sidebar');
    if (store) {
        // Ensure initial state is correct
        store.isMobile = checkIsMobile();
        store.open = !store.isMobile;
        
        // Handle window resize
        window.addEventListener('resize', () => {
            const wasMobile = store.isMobile;
            store.isMobile = checkIsMobile();
            if (!store.isMobile) {
                store.open = true;
            } else if (!wasMobile && store.isMobile) {
                store.open = false;
            }
        });
        
        // Expose global toggle function as fallback
        window.toggleSidebar = () => {
            if (store) {
                store.toggle();
            }
        };
    }
});

// Handle sidebar toggle events
document.addEventListener('DOMContentLoaded', function() {
    // Listen for window resize to handle sidebar state
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const sidebar = document.querySelector('aside[x-data]');
            if (sidebar && window.innerWidth >= 1024) {
                // Force sidebar open on desktop
                const event = new CustomEvent('toggle-sidebar');
                document.dispatchEvent(event);
            }
        }, 250);
    });
});

function initializeCalendars() {
    const calendarNodes = document.querySelectorAll('[data-calendar]');
    if (!calendarNodes.length) {
        return;
    }

    import('./timetables/calendar')
        .then(({ initTimetableCalendar }) => {
            calendarNodes.forEach((node) => {
                if (!node.dataset.initialized) {
                    initTimetableCalendar(node);
                    node.dataset.initialized = '1';
                }
            });
        })
        .catch((error) => console.error('Failed to load calendar module', error));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCalendars);
} else {
    initializeCalendars();
}
