<!-- Add Task Modal -->
<div id="taskModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" id="modalOverlay"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-card border border-slate-700 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-card px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-500/10 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-plus text-primary"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">Add New Task</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="taskTitle" class="block text-sm font-medium text-slate-400">Title</label>
                                <input type="text" id="taskTitle" class="mt-1 block w-full bg-slate-800 border border-slate-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-all" placeholder="What needs to be done?">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="taskXP" class="block text-sm font-medium text-slate-400">XP Reward</label>
                                    <input type="number" id="taskXP" value="10" class="mt-1 block w-full bg-slate-800 border border-slate-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-all">
                                </div>
                                <div>
                                    <label for="taskCategory" class="block text-sm font-medium text-slate-400">Category</label>
                                    <select id="taskCategory" class="mt-1 block w-full bg-slate-800 border border-slate-600 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-all">
                                        <option value="">No Category</option>
                                        <?php if(isset($cats) && is_array($cats)): ?>
                                            <?php foreach ($cats as $cat): ?>
                                                <option value="<?php echo $cat['cid']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-700">
                <button type="button" id="saveTaskBtn" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    Create Task
                </button>
                <button type="button" id="closeModalBtn" class="mt-3 w-full inline-flex justify-center rounded-lg border border-slate-600 shadow-sm px-4 py-2 bg-transparent text-base font-medium text-slate-300 hover:text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>