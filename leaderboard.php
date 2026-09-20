<?php
require __DIR__ . '/session.php';
require __DIR__ . '/db.php';

// Fetch Top 10 Users by Total XP
$stmt = $pdo->prepare('
    SELECT u.username, SUM(uc.xp) as total_xp
    FROM users u
    JOIN user_categories uc ON u.id = uc.user_id
    GROUP BY u.id
    ORDER BY total_xp DESC
    LIMIT 10
');
$stmt->execute();
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/layout/header.php';
?>

<main class="p-6 overflow-y-auto h-full">
    <div class="max-w-4xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="text-center py-8 fade-in">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-yellow-400 to-orange-500 mb-6 shadow-lg shadow-orange-500/30">
                <i class="fas fa-trophy text-4xl text-white"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">Leaderboard</h1>
            <p class="text-xl text-slate-400 max-w-2xl mx-auto">
                See who's leading the pack. Compete for the top spot!
            </p>
        </div>

        <!-- Leaderboard List -->
        <div class="glass-card rounded-2xl overflow-hidden fade-in" style="animation-delay: 0.1s;">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-700/50 bg-slate-800/30 text-slate-400 text-sm uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Rank</th>
                            <th class="px-6 py-4 font-semibold">User</th>
                            <th class="px-6 py-4 font-semibold text-right">Total XP</th>
                            <th class="px-6 py-4 font-semibold text-right">Level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php if (empty($leaders)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                    No data available yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leaders as $index => $user): 
                                $rank = $index + 1;
                                $xp = (int)$user['total_xp'];
                                $level = 1 + intdiv($xp, 300);
                                
                                // Medal Colors
                                $rankClass = "text-slate-400";
                                $medalIcon = "";
                                if ($rank === 1) {
                                    $rankClass = "text-yellow-400 font-bold text-xl";
                                    $medalIcon = '<i class="fas fa-crown mr-2 text-yellow-400"></i>';
                                } elseif ($rank === 2) {
                                    $rankClass = "text-slate-300 font-bold text-lg";
                                    $medalIcon = '<i class="fas fa-medal mr-2 text-slate-300"></i>';
                                } elseif ($rank === 3) {
                                    $rankClass = "text-amber-600 font-bold text-lg";
                                    $medalIcon = '<i class="fas fa-medal mr-2 text-amber-600"></i>';
                                }
                            ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="<?php echo $rankClass; ?> w-8 inline-block text-center"><?php echo $rank; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold mr-4">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="text-sm font-medium text-white">
                                            <?php echo $medalIcon; ?>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['username'] === ($_SESSION['username'] ?? '')): ?>
                                                <span class="ml-2 px-2 py-0.5 rounded-full bg-primary/20 text-primary text-xs border border-primary/30">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-bold text-primary"><?php echo number_format($xp); ?> XP</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                                        Lvl <?php echo $level; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php require __DIR__ . '/layout/footer.php'; ?>
