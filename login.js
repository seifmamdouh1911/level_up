// login.js

console.log("Login script loaded");

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    if (errorDiv && errorText) {
        errorText.textContent = message;
        errorDiv.classList.remove('hidden');
        // Auto hide after 5 seconds
        setTimeout(() => {
            errorDiv.classList.add('hidden');
        }, 5000);
    } else {
        alert(message);
    }
}

function showRegister() {
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");
    const errorDiv = document.getElementById('errorMessage');
    
    if (errorDiv) errorDiv.classList.add('hidden');
    
    if (loginForm) loginForm.classList.add("hidden-form");
    if (registerForm) registerForm.classList.remove("hidden-form");
}

function showLogin() {
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");
    const errorDiv = document.getElementById('errorMessage');
    
    if (errorDiv) errorDiv.classList.add('hidden');
    
    if (registerForm) registerForm.classList.add("hidden-form");
    if (loginForm) loginForm.classList.remove("hidden-form");
}

// Register Submit
const registerForm = document.getElementById("registerForm");
if (registerForm) {
    registerForm.addEventListener("submit", async function(e) {
        e.preventDefault(); // Just in case
        console.log("Register submitting...");
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';
        submitBtn.disabled = true;

        const username = document.getElementById("regUser").value.trim();
        const email = document.getElementById("regEmail").value.trim();
        const password = document.getElementById("regPassword").value;

        const form = new FormData();
        form.append("action", "register");
        form.append("username", username);
        form.append("email", email);
        form.append("password", password);

        try {
            const res = await fetch("auth.php", { method: "POST", body: form });
            const text = await res.text(); // Get raw text first to debug
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error("JSON Parse Error:", text);
                throw new Error("Server returned invalid response");
            }
            
            if (data.success) {
                // Success
                alert("Registered Successfully! Please login.");
                showLogin();
                document.getElementById("regUser").value = "";
                document.getElementById("regEmail").value = "";
                document.getElementById("regPassword").value = "";
            } else {
                showError(data.message || "Registration failed. Username or Email may be taken.");
            }
        } catch (err) {
            console.error(err);
            showError("Network error occurred. Check console.");
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Login Submit
const loginForm = document.getElementById("loginForm");
if (loginForm) {
    loginForm.addEventListener("submit", async function(e) {
        e.preventDefault(); // Just in case
        console.log("Login submitting...");
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Signing in...';
        submitBtn.disabled = true;

        const email = document.getElementById("loginEmail").value.trim();
        const password = document.getElementById("loginPassword").value;

        const form = new FormData();
        form.append("action", "login");
        form.append("email", email);
        form.append("password", password);

        try {
            const res = await fetch("auth.php", { method: "POST", body: form });
            const text = await res.text(); // Get raw text first
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error("JSON Parse Error:", text);
                throw new Error("Server returned invalid response: " + text.substring(0, 50));
            }
            
            if (data.success) {
                if (data.role === 'admin') {
                    window.location.href = "admin.php";
                } else {
                    window.location.href = "home.php";
                }
            } else {
                showError(data.message || "Invalid email or password.");
            }
        } catch (err) {
            console.error(err);
            showError("Login failed: " + err.message);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}
