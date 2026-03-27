document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Render the Weekly Bar Chart ---
    const weeklyData = [
        { day: 'Mon', hours: 4, percent: 60 },
        { day: 'Tue', hours: 5.5, percent: 85 },
        { day: 'Wed', hours: 3, percent: 45 },
        { day: 'Thu', hours: 6, percent: 90 },
        { day: 'Fri', hours: 4.5, percent: 70 },
        { day: 'Sat', hours: 2, percent: 30, isWeekend: true },
        { day: 'Sun', hours: 1, percent: 15, isWeekend: true }
    ];

    const chartContainer = document.getElementById('weeklyChart');

    if (chartContainer) {
        weeklyData.forEach(data => {
            const col = document.createElement('div');
            col.className = 'bar-column';
            
            const weekendClass = data.isWeekend ? 'weekend' : '';
            
            col.innerHTML = `
                <div class="bar-fill ${weekendClass}" style="height: 0%" data-target="${data.percent}%"></div>
                <div class="bar-label">${data.day}</div>
            `;
            chartContainer.appendChild(col);
        });

        // Small delay to trigger the CSS animation smoothly after the page loads
        setTimeout(() => {
            document.querySelectorAll('.bar-fill').forEach(bar => {
                bar.style.height = bar.getAttribute('data-target');
            });
        }, 100);
    }

    // --- 2. Render the Subject Breakdown Progress Bars ---
    const subjectData = [
        { name: 'Physics', hours: '14h', percent: 80 },
        { name: 'Calculus', hours: '10h', percent: 60 },
        { name: 'Literature', hours: '6h', percent: 35 },
        { name: 'Chemistry', hours: '4.5h', percent: 25 }
    ];

    const subjectContainer = document.getElementById('subjectBreakdown');

    if (subjectContainer) {
        subjectData.forEach(sub => {
            const item = document.createElement('div');
            item.className = 'subject-item';
            item.innerHTML = `
                <div class="subject-header">
                    <span>${sub.name}</span>
                    <span class="subject-hours">${sub.hours}</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: 0%" data-target="${sub.percent}%"></div>
                </div>
            `;
            subjectContainer.appendChild(item);
        });

        // Animate progress bars
        setTimeout(() => {
            document.querySelectorAll('.progress-fill').forEach(bar => {
                bar.style.width = bar.getAttribute('data-target');
            });
        }, 100);
    }
});
