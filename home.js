document.addEventListener("DOMContentLoaded", () => {
    // ----------------------------
    // Chart.js Initialization
    // ----------------------------
    const ctx = document.getElementById('activityChart');
    if (ctx) {
        // Use real data from PHP if available, otherwise fallback (or empty)
        const labels = window.ACTIVITY_DATA?.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const data = window.ACTIVITY_DATA?.data || [0, 0, 0, 0, 0, 0, 0];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'XP Gained',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' XP';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    }

    // ----------------------------
    // Modal Logic
    // ----------------------------
    const modal = document.getElementById('taskModal');
    const openBtn = document.getElementById('addTaskBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const saveBtn = document.getElementById('saveTaskBtn');
    const overlay = document.getElementById('modalOverlay');

    const toggleModal = (show) => {
        if (show) {
            modal.classList.remove('hidden');
            document.getElementById('taskTitle').focus();
        } else {
            modal.classList.add('hidden');
        }
    };

    if (openBtn) openBtn.onclick = () => toggleModal(true);
    if (closeBtn) closeBtn.onclick = () => toggleModal(false);
    if (overlay) overlay.onclick = () => toggleModal(false);

    // ----------------------------
    // Task Logic
    // ----------------------------
    loadTasks();

    if (saveBtn) {
        saveBtn.onclick = async () => {
            const title = document.getElementById('taskTitle').value;
            const xp = document.getElementById('taskXP').value;
            const categoryId = document.getElementById('taskCategory').value;

            if (!title) {
                alert('Please enter a task title');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const form = new FormData();
            form.append("action", "create");
            form.append("title", title);
            form.append("xp", xp);
            if (categoryId) form.append("category_id", categoryId);

            try {
                const res = await fetch("task.php", { method: "POST", body: form });
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('taskTitle').value = '';
                    toggleModal(false);
                    loadTasks();
                } else {
                    alert('Failed to create task');
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Create Task';
            }
        };
    }
});

async function loadTasks() {
    const container = document.getElementById('taskList');
    if (!container) return;

    try {
        const res = await fetch("task.php?api=1");
        const data = await res.json();
        
        if (data.success) {
            const pending = data.tasks.filter(t => t.status !== "completed").slice(0, 5);
            
            if (pending.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-slate-500 bg-slate-800/30 rounded-lg border border-dashed border-slate-700">
                        <i class="fas fa-clipboard-check text-4xl mb-3 opacity-20"></i>
                        <p>All caught up! No pending tasks.</p>
                    </div>`;
                return;
            }

            container.innerHTML = pending.map(t => `
                <div class="task-item group flex items-center justify-between p-4 bg-slate-800/50 hover:bg-slate-800 rounded-xl border border-transparent hover:border-slate-700 transition-all" data-id="${t.id}">
                    <div class="flex items-center gap-4">
                        <button onclick="completeTask(${t.id}, this)" class="w-6 h-6 rounded-full border-2 border-slate-500 hover:border-primary hover:bg-primary/20 transition-colors flex items-center justify-center group-hover:scale-110">
                            <i class="fas fa-check text-xs text-transparent hover:text-primary"></i>
                        </button>
                        <div>
                            <h4 class="text-slate-200 font-medium group-hover:text-white transition-colors">${escapeHtml(t.title)}</h4>
                            <span class="text-xs text-slate-500">${t.category ? escapeHtml(t.category) : 'General'}</span>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-1 rounded-full">+${t.xp} XP</span>
                </div>
            `).join("");
        }
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p class="text-red-400">Failed to load tasks</p>';
    }
}

async function completeTask(id, btn) {
    // Optimistic UI
    const card = btn.closest('.task-item');
    card.style.opacity = '0.5';
    card.style.transform = 'scale(0.98)';
    
    const form = new FormData();
    form.append("action", "toggle_complete");
    form.append("task_id", id);

    try {
        await fetch("task.php", { method: "POST", body: form });
        // Reload to sync stats
        setTimeout(() => {
            // Optional: could just animate removal instead of full reload if we wanted to be fancy
            // But reloading ensures XP stats update in the header without complex state management
             window.location.reload();
        }, 300);
    } catch (err) {
        console.error(err);
        alert('Error completing task');
    }
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[s]));
}
