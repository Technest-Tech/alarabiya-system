import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

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
