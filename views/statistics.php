<?php
// views/statistics.php
require_once __DIR__ . '/../config.php';
global $pdo;

// Fetch Year-by-Year Statistics
$stmt = $pdo->query("
    SELECT 
        p.year, 
        COUNT(DISTINCT p.id) as total_participants, 
        COUNT(DISTINCT p.country) as total_dxcc,
        COALESCE(SUM(c.total_qso), 0) as total_qso
    FROM participants p
    LEFT JOIN cabrillo_logs c ON p.id = c.participant_id AND c.status = 'adjudicated'
    WHERE p.disqualified = 0
    GROUP BY p.year
    ORDER BY p.year DESC
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$chart_years = [];
$chart_participants = [];
$chart_dxcc = [];
$chart_qsos = [];

// Reverse the array so the chart goes chronologically left to right
$stats_chrono = array_reverse($stats);
foreach ($stats_chrono as $row) {
    $chart_years[] = $row['year'];
    $chart_participants[] = $row['total_participants'];
    $chart_dxcc[] = $row['total_dxcc'];
    $chart_qsos[] = $row['total_qso'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2 style="margin-bottom: 2rem;"><i class="fas fa-chart-bar"></i> <?php echo t('statistics'); ?></h2>
        
        <p style="margin-bottom: 2rem; color: var(--text-secondary);">Contest execution statistics from year to year, showing the growth of participants, unique DXCCs, and total QSOs.</p>
        
        <?php if (count($stats) == 0): ?>
            <p style="text-align:center; color: var(--text-secondary);">No historical data available.</p>
        <?php else: ?>
            
            <!-- Chart Container -->
            <div style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--glass-border); margin-bottom: 2rem;">
                <canvas id="statsChart" height="100"></canvas>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th style="text-align: center;">Total DXCCs</th>
                            <th style="text-align: right;">Total Logs Received</th>
                            <th style="text-align: right;">Total Valid QSOs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats as $row): ?>
                        <tr>
                            <td style="font-weight: bold; color: var(--accent-hover);"><?php echo $row['year']; ?></td>
                            <td style="text-align: center; color: #fbbf24;"><?php echo number_format($row['total_dxcc']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($row['total_participants']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($row['total_qso']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('statsChart').getContext('2d');
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($chart_years); ?>,
                            datasets: [
                                {
                                    label: 'Total Logs Received',
                                    data: <?php echo json_encode($chart_participants); ?>,
                                    borderColor: '#60a5fa',
                                    backgroundColor: 'rgba(96, 165, 250, 0.2)',
                                    tension: 0.3,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Total DXCCs',
                                    data: <?php echo json_encode($chart_dxcc); ?>,
                                    borderColor: '#fbbf24',
                                    backgroundColor: 'rgba(251, 191, 36, 0.2)',
                                    tension: 0.3,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Total Valid QSOs',
                                    data: <?php echo json_encode($chart_qsos); ?>,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                    tension: 0.3,
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            scales: {
                                x: {
                                    grid: { color: 'rgba(255,255,255,0.05)' },
                                    ticks: { color: '#9ca3af' }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    grid: { color: 'rgba(255,255,255,0.05)' },
                                    ticks: { color: '#9ca3af' }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    grid: { drawOnChartArea: false },
                                    ticks: { color: '#10b981' }
                                }
                            },
                            plugins: {
                                legend: {
                                    labels: { color: '#e5e7eb' }
                                }
                            }
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</div>
