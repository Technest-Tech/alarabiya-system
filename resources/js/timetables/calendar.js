import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const eventHtml = (arg) => {
    const student = arg.event.extendedProps.student || 'Student';
    const teacher = arg.event.extendedProps.teacher || 'Unassigned';
    const teacherTime = arg.event.extendedProps.displayTime || arg.timeText || '';
    const studentTime = arg.event.extendedProps.student_time_display || '';

    return `
        <div class="fc-event-card">
            <div class="fc-event-time">${teacherTime}</div>
            <div class="fc-event-title">${student}</div>
            <div class="fc-event-meta">${teacher}</div>
            ${
                studentTime
                    ? `<div class="fc-event-meta fc-event-meta--student text-xs text-slate-500">
                        <span class="block">Student:</span>
                        <span class="block">${studentTime.replace(/\((.*?)\)/, '<span class="block">$&</span>')}</span>
                    </div>`
                    : ''
            }
        </div>
    `;
};

export function initTimetableCalendar(element) {
    if (!element) {
        return null;
    }

    const eventsUrl = element.dataset.eventsUrl;

    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        height: 'auto',
        nowIndicator: true,
        eventDisplay: 'block',
        displayEventEnd: true,
        slotMinTime: '05:00:00',
        slotMaxTime: '24:00:00',
        events: (fetchInfo, successCallback, failureCallback) => {
            const params = new URLSearchParams({
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
            });

            if (element.dataset.studentId) {
                params.set('student_id', element.dataset.studentId);
            }
            if (element.dataset.teacherId) {
                params.set('teacher_id', element.dataset.teacherId);
            }

            fetch(`${eventsUrl}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => response.json())
                .then((data) => successCallback(data))
                .catch((error) => {
                    console.error('Failed to load events', error);
                    failureCallback(error);
                });
        },
        eventClick(info) {
            window.dispatchEvent(
                new CustomEvent('timetable:event-click', {
                    detail: {
                        id: info.event.id,
                        title: info.event.title,
                        start: info.event.startStr,
                        end: info.event.endStr,
                        extendedProps: info.event.extendedProps,
                    },
                })
            );
        },
        eventContent(arg) {
            return { html: eventHtml(arg) };
        },
        datesSet(info) {
            element.dataset.viewStart = info.startStr;
            element.dataset.viewEnd = info.endStr;
        },
    });

    calendar.render();

    window.addEventListener('timetable:refresh', () => {
        calendar.refetchEvents();
    });

    window.addEventListener('timetable:event-updated', (event) => {
        const payload = event.detail?.event;
        if (!payload) {
            return;
        }

        const existing = calendar.getEventById(String(payload.id));
        if (existing) {
            existing.remove();
        }

        calendar.addEvent(payload);
    });

    window.addEventListener('timetable:event-removed', (event) => {
        const eventId = event.detail?.id;
        if (!eventId) {
            return;
        }

        const existing = calendar.getEventById(String(eventId));
        if (existing) {
            existing.remove();
        }
    });

    return calendar;
}

