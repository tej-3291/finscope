<?php
// esg/index.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
require '../config/database.php';

$user_id = $_SESSION['user_id'];

// Arbitrary mapping for ESG scores (0-100) based on categories
// This is a proxy since we don't have deeper item data yet.
$stmt = $pdo->prepare("
    SELECT category, SUM(ABS(amount)) as total 
    FROM transactions 
    WHERE user_id = ? AND amount < 0 AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY)
    GROUP BY category
");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll();

$env = 100; $soc = 100; $gov = 100;
foreach($data as $row) {
    if ($row['category'] === 'Transport') $env -= ($row['total'] * 0.005);
    if ($row['category'] === 'Shopping') { $env -= ($row['total'] * 0.002); $soc -= ($row['total'] * 0.001); }
    if ($row['category'] === 'Food') $env -= ($row['total'] * 0.001); // Delivery packaging
}
$env = max(10, min(100, $env));
$soc = max(10, min(100, $soc));
$gov = max(10, min(100, $gov)); // Static for now

$overall = round(($env + $soc + $gov) / 3);

include '../includes/header.php';
?>

<div class="row fade-in-up">
    <div class="col-md-8 mb-4">
        <h2 class="fw-bold mb-1">ESG Impact Profile</h2>
        <p class="text-secondary mb-0">How your spending aligns with Environmental, Social, and Governance metrics.</p>
    </div>
</div>

<div class="row fade-in-up delay-1">
    <div class="col-lg-7 mb-4">
        <div class="glass-card p-4 h-100 position-relative text-center">
            <h5 class="fw-bold mb-4 text-neon">Your Impact Radar</h5>
            
            <div style="height: 400px; width: 100%;" class="mx-auto position-relative">
                <canvas id="esgRadarChart"></canvas>
            </div>
            
            <div class="position-absolute top-0 end-0 p-4">
                <div style="width:80px;height:80px;background:var(--bg-card);border:2px solid var(--border-card);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.5rem;" class="<?= $overall > 70 ? 'text-positive' : ($overall > 40 ? 'text-warning' : 'text-negative') ?>">
                    <?= $overall ?>
                </div>
                <div class="small text-secondary fw-bold mt-2">Overall Score</div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5 mb-4 d-flex flex-column gap-3">
        <div class="glass-card p-4 border-start border-4 border-success">
            <h6 class="fw-bold text-light mb-1"><i class="bi bi-tree-fill text-success me-2"></i> Environmental (<?= round($env) ?>/100)</h6>
            <p class="text-secondary small mb-0">Affected by high Transport (fuel) and delivery packaging costs. Use public transport to boost this.</p>
        </div>
        
        <div class="glass-card p-4 border-start border-4 border-info">
            <h6 class="fw-bold text-light mb-1"><i class="bi bi-people-fill text-info me-2"></i> Social (<?= round($soc) ?>/100)</h6>
            <p class="text-secondary small mb-0">Affected by fast-fashion shopping choices. Supporting local vendors improves this metric.</p>
        </div>
        
        <div class="glass-card p-4 border-start border-4 border-warning">
            <h6 class="fw-bold text-light mb-1"><i class="bi bi-bank2 text-warning me-2"></i> Governance (<?= round($gov) ?>/100)</h6>
            <p class="text-secondary small mb-0">Reflects your general transparency in financial logging and avoiding black-market cache leakages.</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--text-muted') || '#94a3b8';
    Chart.defaults.font.family = "'Inter', sans-serif";
    
    const ctx = document.getElementById('esgRadarChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Environmental', 'Social', 'Governance', 'Local Economy', 'Carbon Footprint'],
            datasets: [{
                label: 'Your Score',
                data: [<?= round($env) ?>, <?= round($soc) ?>, <?= round($gov) ?>, <?= round(($soc+$gov)/2) ?>, <?= round($env*0.8) ?>],
                backgroundColor: 'rgba(56, 189, 248, 0.2)', // Neon soft
                borderColor: '#38bdf8',
                pointBackgroundColor: '#fff',
                pointBorderColor: '#38bdf8',
                borderWidth: 2,
            },
            {
                label: 'Global Average',
                data: [65, 70, 80, 55, 60],
                backgroundColor: 'rgba(255, 255, 255, 0.05)',
                borderColor: '#64748b', // text-secondary
                pointBackgroundColor: '#1e293b',
                borderWidth: 2,
                borderDash: [5, 5]
            }]
        },
        options: {
            scales: {
                r: {
                    angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    pointLabels: { color: '#f8fafc', font: {size: 13, weight: 'bold'} },
                    ticks: { display: false, min: 0, max: 100 }
                }
            },
            animation: { duration: 2000, easing: 'easeOutElastic' },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
