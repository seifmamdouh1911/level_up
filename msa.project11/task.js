document.addEventListener("DOMContentLoaded", () => {
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
            setTimeout(() => document.getElementById('taskTitle').focus(), 50);
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
    const pendingContainer = document.getElementById('pendingList');
    const completedContainer = document.getElementById('completedList');
    
    if (!pendingContainer || !completedContainer) return;

    try {
        const res = await fetch("task.php?api=1");
        const data = await res.json();
        
        if (data.success) {
            const pending = data.tasks.filter(t => t.status !== "completed");
            const completed = data.tasks.filter(t => t.status === "completed");

            // Update Stats
            document.getElementById('statTotal').innerText = data.tasks.length;
            document.getElementById('statPending').innerText = pending.length;
            document.getElementById('statCompleted').innerText = completed.length;
            document.getElementById('countPending').innerText = pending.length;
            document.getElementById('countCompleted').innerText = completed.length;
            
            // Render Pending
            if (pending.length === 0) {
                pendingContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-slate-500 py-12">
                        <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mb-4">
                            <i class="fas fa-check text-2xl text-slate-600"></i>
                        </div>
                        <p>No pending tasks</p>
                    </div>`;
            } else {
                pendingContainer.innerHTML = pending.map(t => renderTaskCard(t, false)).join("");
            }

            // Render Completed
            if (completed.length === 0) {
                completedContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-slate-500 py-12">
                        <p>No completed tasks yet</p>
                    </div>`;
            } else {
                completedContainer.innerHTML = completed.map(t => renderTaskCard(t, true)).join("");
            }
        }
    } catch (err) {
        console.error(err);
        pendingContainer.innerHTML = '<p class="text-red-400 text-center">Failed to load tasks</p>';
    }
}

function renderTaskCard(t, isCompleted) {
    const statusColor = isCompleted ? 'text-green-400' : 'text-slate-400';
    const bgClass = isCompleted ? 'bg-slate-800/30 border-transparent opacity-75' : 'glass-card border-slate-700/50';
    
    return `
    <div class="${bgClass} p-4 rounded-xl mb-3 group transition-all duration-300 hover:-translate-y-1 hover:shadow-lg relative overflow-hidden" data-id="${t.id}">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1">
                <button onclick="toggleTask(${t.id}, this)" class="mt-1 w-5 h-5 rounded border-2 ${isCompleted ? 'bg-green-500 border-green-500' : 'border-slate-500 hover:border-primary'} transition-colors flex items-center justify-center flex-shrink-0">
                    ${isCompleted ? '<i class="fas fa-check text-xs text-white"></i>' : ''}
                </button>
                <div>
                    <h4 class="${isCompleted ? 'text-slate-500 line-through' : 'text-slate-200'} font-medium text-sm leading-snug mb-1 transition-all">${escapeHtml(t.title)}</h4>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">
                            ${t.category ? escapeHtml(t.category) : 'General'}
                        </span>
                        <span class="text-xs font-bold text-primary">+${t.xp} XP</span>
                    </div>
                </div>
            </div>
            <button onclick="deleteTask(${t.id}, this)" class="text-slate-600 hover:text-red-400 transition-colors p-1 opacity-0 group-hover:opacity-100">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>`;
}

async function toggleTask(id, btn) {
    const card = btn.closest('div[data-id]');
    card.style.opacity = '0.5';
    
    const form = new FormData();
    form.append("action", "toggle_complete");
    form.append("task_id", id);

    try {
        await fetch("task.php", { method: "POST", body: form });
        loadTasks(); // Reload to move between columns
    } catch (err) {
        console.error(err);
        alert('Error updating task');
        card.style.opacity = '1';
    }
}

async function deleteTask(id, btn) {
    if(!confirm('Are you sure you want to delete this task?')) return;
    
    const card = btn.closest('div[data-id]');
    card.style.transform = 'scale(0.9) opacity(0)';
    
    const form = new FormData();
    form.append("action", "delete");
    form.append("task_id", id);

    try {
        await fetch("task.php", { method: "POST", body: form });
        setTimeout(() => loadTasks(), 300);
    } catch (err) {
        console.error(err);
        alert('Error deleting task');
        card.style.transform = 'none';
    }
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[s]));
}