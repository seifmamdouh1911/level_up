            </div>
        </main>
    </div>
</div>

<!-- Mobile Sidebar Overlay (optional implementation) -->
<div id="mobileMenu" class="fixed inset-0 bg-black/50 z-50 hidden md:hidden">
    <div class="w-64 h-full bg-card p-4">
        <!-- Mobile Menu Content -->
        <div class="flex justify-between items-center mb-6">
            <span class="text-xl font-bold">Menu</span>
            <button id="closeMobileMenu" class="text-slate-400"><i class="fas fa-times"></i></button>
        </div>
        <nav class="space-y-2">
            <a href="home.php" class="block px-4 py-2 rounded text-slate-300 hover:bg-slate-800">Dashboard</a>
            <a href="task.php" class="block px-4 py-2 rounded text-slate-300 hover:bg-slate-800">Tasks</a>
            <a href="categories.php" class="block px-4 py-2 rounded text-slate-300 hover:bg-slate-800">Categories</a>
            <a href="profile.php" class="block px-4 py-2 rounded text-slate-300 hover:bg-slate-800">Profile</a>
            <a href="summary.php" class="block px-4 py-2 rounded text-slate-300 hover:bg-slate-800">Summary</a>
            <a href="about.php" class="block px-4 py-2 rounded text-slate-300 hover:bg-slate-800">About</a>
            <a href="logout.php" class="block px-4 py-2 rounded text-red-400 hover:bg-red-900/20 mt-4">Logout</a>
        </nav>
    </div>
</div>

<script>
    // Mobile Menu Toggle
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeBtn = document.getElementById('closeMobileMenu');

    if(mobileBtn) {
        mobileBtn.addEventListener('click', () => {
            mobileMenu.classList.remove('hidden');
        });
    }

    if(closeBtn) {
        closeBtn.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
        });
    }
    
    // Close when clicking outside
    if(mobileMenu) {
        mobileMenu.addEventListener('click', (e) => {
            if(e.target === mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });
    }
</script>
</body>
</html>
