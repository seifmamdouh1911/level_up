const body = document.body;
const btn = document.getElementById("themeToggle");

// Auto apply system theme
const systemLight = window.matchMedia("(prefers-color-scheme: light)");

function applySystemTheme() {
    if (systemLight.matches) {
        body.classList.add("light");
        btn.textContent = "🌙";
    } else {
        body.classList.remove("light");
        btn.textContent = "☀️";
    }
}

applySystemTheme();
systemLight.addEventListener("change", applySystemTheme);

// Manual theme toggle
btn.addEventListener("click", () => {
    body.classList.toggle("light");
    btn.textContent = body.classList.contains("light") ? "🌙" : "☀️";
});

// Animate XP bars on load
window.addEventListener("load", () => {
    document.querySelectorAll(".animated-fill").forEach((bar, i) => {
        setTimeout(() => {
            bar.style.width = bar.style.getPropertyValue("--target-width");
        }, i * 200);
    });
});
