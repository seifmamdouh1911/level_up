<?php 
require __DIR__ . '/session.php'; 
require __DIR__ . '/db.php';

// ---------------------------------------------------------
// API CONTROLLER LOGIC (Combined from tasks.php)
// ---------------------------------------------------------
$isApi = isset($_GET['api']) || $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isApi) {
    header('Content-Type: application/json');
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = (int)$_SESSION['user_id'];

    // GET: Fetch Tasks
    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT t.id, t.title, t.xp, t.status, c.name as category
                               FROM tasks t LEFT JOIN categories c ON t.category_id = c.id
                               WHERE t.user_id = :uid ORDER BY t.created_at DESC');
        $stmt->execute([':uid' => $userId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'tasks' => $tasks]);
        exit;
    }

    // POST: Create / Update / Delete
    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $xp = (int)($_POST['xp'] ?? 0);
            $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'title_required']);
                exit;
            }
            $stmt = $pdo->prepare('INSERT INTO tasks (user_id, category_id, title, xp, status, created_at) VALUES (:uid, :cid, :title, :xp, \'pending\', :c)');
            $stmt->execute([':uid' => $userId, ':cid' => $categoryId, ':title' => $title, ':xp' => $xp, ':c' => date('Y-m-d H:i:s')]);
            $taskId = (int)$pdo->lastInsertId();
            
            // History
            $h = $pdo->prepare('INSERT INTO task_history (task_id, action, created_at) VALUES (:tid, \'created\', :c)');
            $h->execute([':tid' => $taskId, ':c' => date('Y-m-d H:i:s')]);
            
            echo json_encode(['success' => true, 'id' => $taskId]);
            exit;
        }

        if ($action === 'toggle_complete') {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT xp, category_id, status FROM tasks WHERE id = :id AND user_id = :uid');
            $stmt->execute([':id' => $taskId, ':uid' => $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                echo json_encode(['success' => false, 'message' => 'not_found']);
                exit;
            }
            
            $newStatus = $task['status'] === 'completed' ? 'pending' : 'completed';
            $pdo->prepare('UPDATE tasks SET status = :s, completed_at = :ca WHERE id = :id')
                ->execute([':s' => $newStatus, ':ca' => $newStatus === 'completed' ? date('Y-m-d H:i:s') : null, ':id' => $taskId]);
            
            if ($newStatus === 'completed' && $task['category_id']) {
                $pdo->prepare('UPDATE user_categories SET xp = xp + :xp WHERE user_id = :uid AND category_id = :cid')
                    ->execute([':xp' => (int)$task['xp'], ':uid' => $userId, ':cid' => (int)$task['category_id']]);
            }
            
            // History
            $pdo->prepare('INSERT INTO task_history (task_id, action, created_at) VALUES (:tid, :act, :c)')
                ->execute([':tid' => $taskId, ':act' => $newStatus === 'completed' ? 'completed' : 'reopened', ':c' => date('Y-m-d H:i:s')]);
            
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete') {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :uid')->execute([':id' => $taskId, ':uid' => $userId]);
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'invalid_request']);
    exit;
}

// ---------------------------------------------------------
// HTML VIEW LOGIC (Render Page)
// ---------------------------------------------------------

$userId = (int)$_SESSION['user_id'];

// Fetch Categories for the modal
$catStmt = $pdo->prepare('SELECT c.id AS cid, c.name AS name FROM user_categories uc JOIN categories c ON c.id = uc.category_id WHERE uc.user_id = :uid ORDER BY c.name');
$catStmt->execute([':uid' => $userId]);
$cats = $catStmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/layout/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 fade-in">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Task Board</h1>
        <p class="text-slate-400">Manage your daily quests and achievements.</p>
    </div>
    <button id="addTaskBtn" class="bg-primary hover:bg-indigo-600 text-white px-6 py-3 rounded-xl transition-all shadow-lg shadow-primary/25 flex items-center justify-center gap-2">
        <i class="fas fa-plus"></i> New Task
    </button>
</div>

<!-- Task Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 fade-in" style="animation-delay: 0.1s;">
    <div class="glass-card p-6 rounded-2xl flex items-center justify-between">
        <div>
            <p class="text-slate-400 text-sm font-medium mb-1">Total Tasks</p>
            <h3 class="text-3xl font-bold text-white" id="statTotal">-</h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-slate-700/50 flex items-center justify-center text-slate-300">
            <i class="fas fa-tasks text-xl"></i>
        </div>
    </div>
    <div class="glass-card p-6 rounded-2xl flex items-center justify-between">
        <div>
            <p class="text-slate-400 text-sm font-medium mb-1">Pending</p>
            <h3 class="text-3xl font-bold text-white" id="statPending">-</h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-orange-500/10 flex items-center justify-center text-orange-400">
            <i class="fas fa-clock text-xl"></i>
        </div>
    </div>
    <div class="glass-card p-6 rounded-2xl flex items-center justify-between">
        <div>
            <p class="text-slate-400 text-sm font-medium mb-1">Completed</p>
            <h3 class="text-3xl font-bold text-white" id="statCompleted">-</h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-green-500/10 flex items-center justify-center text-green-400">
            <i class="fas fa-check-circle text-xl"></i>
        </div>
    </div>
</div>

<!-- Kanban Board -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 fade-in" style="animation-delay: 0.2s;">
    
    <!-- Pending Column -->
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-between mb-4 px-2">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-orange-400"></span>
                In Progress
                <span class="bg-slate-800 text-slate-400 text-xs px-2 py-0.5 rounded-full" id="countPending">0</span>
            </h2>
        </div>
        <div class="bg-slate-800/30 rounded-2xl p-4 min-h-[500px] border border-slate-700/50 space-y-4" id="pendingList">
            <!-- Loading State -->
            <div class="flex flex-col items-center justify-center h-40 text-slate-500">
                <i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i>
                <p>Loading tasks...</p>
            </div>
        </div>
    </div>

    <!-- Completed Column -->
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-between mb-4 px-2">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-400"></span>
                Completed
                <span class="bg-slate-800 text-slate-400 text-xs px-2 py-0.5 rounded-full" id="countCompleted">0</span>
            </h2>
        </div>
        <div class="bg-slate-800/30 rounded-2xl p-4 min-h-[500px] border border-slate-700/50 space-y-4" id="completedList">
            <!-- Content loaded via JS -->
        </div>
    </div>

</div>

<?php require __DIR__ . '/layout/modal_add_task.php'; ?>

<script>
    // Pass PHP data to JS
    window.USER_CATEGORIES = <?php echo json_encode(array_map(function($c){ return ['id'=>(int)$c['cid'],'name'=>$c['name']]; }, $cats)); ?>;
</script>
<script src="task.js?v=<?php echo time(); ?>"></script>

<?php require __DIR__ . '/layout/footer.php'; ?>