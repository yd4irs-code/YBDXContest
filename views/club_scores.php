<?php
// views/club_scores.php
require_once __DIR__ . '/../config.php';
global $pdo;

// Fetch aggregated club scores
$stmt = $pdo->prepare("
    SELECT p.club_name, SUM(c.raw_score) as total_score, COUNT(p.id) as total_members
    FROM participants p
    JOIN cabrillo_logs c ON p.id = c.participant_id
    WHERE p.year = ? AND p.disqualified = 0 AND p.club_name IS NOT NULL AND TRIM(p.club_name) != ''
    GROUP BY p.club_name
    ORDER BY total_score DESC
");
$stmt->execute([$current_contest_year]);
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2 style="margin-bottom: 2rem;"><?php echo t('club_scores'); ?> (<?php echo $current_contest_year; ?>)</h2>
        
        <?php if (count($clubs) == 0): ?>
            <p style="text-align:center; color: var(--text-secondary);">No club data available yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Rank</th>
                            <th>Club Name</th>
                            <th style="text-align: right;">Total Members</th>
                            <th style="text-align: right;">Total Aggregate Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($clubs as $club): 
                        ?>
                        <tr>
                            <td>
                                <?php if($rank == 1) echo '<span style="color: gold;"><i class="fas fa-trophy"></i> 1</span>';
                                      elseif($rank == 2) echo '<span style="color: silver;">2</span>';
                                      elseif($rank == 3) echo '<span style="color: #cd7f32;">3</span>';
                                      else echo $rank; 
                                ?>
                            </td>
                            <td style="font-weight: bold; color: var(--text-primary);"><?php echo htmlspecialchars($club['club_name']); ?></td>
                            <td style="text-align: right;"><?php echo $club['total_members']; ?></td>
                            <td style="text-align: right; font-weight: bold; color: var(--accent-hover);"><?php echo number_format($club['total_score']); ?></td>
                        </tr>
                        <?php 
                        $rank++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
