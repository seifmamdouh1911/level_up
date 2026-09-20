const body = document.body;
const btn = document.getElementById("themeToggle");

// Theme toggle
const systemLight = window.matchMedia("(prefers-color-scheme: light)");
function applySystemTheme() {
    if(systemLight.matches){
        body.classList.add("light");
        btn.textContent = "🌙";
    } else {
        body.classList.remove("light");
        btn.textContent = "☀️";
    }
}
applySystemTheme();
systemLight.addEventListener("change", applySystemTheme);
btn.addEventListener("click", () => {
    body.classList.toggle("light");
    btn.textContent = body.classList.contains("light") ? "🌙" : "☀️";
});

// Animate XP bars and pop-in cards
window.addEventListener("load", () => {
    document.querySelectorAll(".xp-fill").forEach((bar, i) => {
        setTimeout(() => {
            bar.style.width = bar.style.getPropertyValue("--target-width");
        }, i*200);
    });

    document.querySelectorAll(".cat-card").forEach((card, i) => {
        setTimeout(() => {
            card.style.opacity = "1";
            card.style.transform = "scale(1)";
        }, i*150);
    });
});

// Add task buttons
document.getElementById("addTaskBtn").onclick = async () => {
    if (window.CURRENT_CATEGORY_ID) {
        const title = prompt("Task title");
        if (!title) return;
        const xp = parseInt(prompt("XP amount (e.g., 10)"), 10);
        const form = new FormData();
        form.append("action", "create");
        form.append("title", title.trim());
        form.append("xp", isNaN(xp) ? 0 : xp);
        form.append("category_id", String(window.CURRENT_CATEGORY_ID));
        const res = await fetch("tasks.php", { method: "POST", body: form });
        const data = await res.json().catch(() => ({ success: false }));
        if (data.success) {
            window.location.reload();
        } else {
            alert("Failed to add task");
        }
        return;
    }
    const name = prompt("New category name");
    if (!name) return;
    const desc = prompt("Description (optional)") || "";
    const form = new FormData();
    form.append("action", "create");
    form.append("name", name.trim());
    form.append("description", desc.trim());
    await fetch("categories.php", { method: "POST", body: form });
    window.location.reload();
};
document.querySelectorAll(".mini-add-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
        if (!window.CURRENT_CATEGORY_ID) return;
        const title = prompt("Task title");
        if (!title) return;
        const xp = parseInt(prompt("XP amount (e.g., 10)"), 10);
        const form = new FormData();
        form.append("action", "create");
        form.append("title", title.trim());
        form.append("xp", isNaN(xp) ? 0 : xp);
        form.append("category_id", String(window.CURRENT_CATEGORY_ID));
        const res = await fetch("tasks.php", { method: "POST", body: form });
        const data = await res.json().catch(() => ({ success: false }));
        if (data.success) {
            window.location.reload();
        } else {
            alert("Failed to add task");
        }
    });
});

document.querySelectorAll(".taskCheck").forEach(cb => {
    cb.addEventListener("change", async (e) => {
        const id = e.target.closest(".task-card")?.dataset.id;
        if (!id) return;
        const form = new FormData();
        form.append("action", "toggle_complete");
        form.append("task_id", id);
        await fetch("tasks.php", { method: "POST", body: form });
        window.location.reload();
    });
});
