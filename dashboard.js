document.addEventListener('DOMContentLoaded', () => {
    // Modal controls
    const modal = document.getElementById("taskModal");
    const openBtn = document.getElementById("openModalBtn");
    const closeBtn = document.getElementById("closeModalBtn");

    if (openBtn) openBtn.addEventListener("click", () => modal.style.display = "flex");
    if (closeBtn) closeBtn.addEventListener("click", () => modal.style.display = "none");
    window.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });

    // Load tasks
    const taskList = document.getElementById("taskList");
    const todaysTasks = [
        { title: "Review Calculus Chapter 3", time: "10:00 AM - 11:30 AM" },
        { title: "Complete Physics Lab Report", time: "1:00 PM - 3:00 PM" },
        { title: "Read Literature Essay", time: "4:00 PM - 5:00 PM" }
    ];

    if (taskList) {
        taskList.innerHTML = "";
        todaysTasks.forEach(task => {
            const taskDiv = document.createElement("div");
            taskDiv.className = "task-item"; 
            taskDiv.innerHTML = `
                <strong style="color: #1e293b;">${task.title}</strong>
                <span style="color: #15803d; font-weight: 600; font-size: 0.9rem;">⏰ ${task.time}</span>
            `;
            taskList.appendChild(taskDiv);
        });
    }
});
