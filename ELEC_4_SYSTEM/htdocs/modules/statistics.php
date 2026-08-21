<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - UMak Registrar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fc; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #8b1e2b; padding-bottom: 15px; margin-bottom: 25px; }
        h1 { color: #003366; }
        h1 i { color: #8b1e2b; }
        .back-btn { display: inline-block; padding: 10px 25px; background: #003366; color: white; text-decoration: none; border-radius: 6px; }
        .back-btn:hover { background: #1a4d7a; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0; }
        .stat-card { background: #f8f7f4; padding: 25px; border-radius: 10px; text-align: center; border-left: 4px solid #8b1e2b; }
        .stat-card .number { font-size: 2.5rem; font-weight: 700; color: #003366; }
        .stat-card .label { color: #666; }
        .umak-badge { background: #8b1e2b; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> View Statistics</h1>
            <span class="umak-badge"><i class="fas fa-calendar-check"></i> A.Y. 2026-2027</span>
        </div>
        <p style="color: #666;">Overall summary of enrollment statistics.</p>
        <div class="stats-grid">
            <div class="stat-card"><div class="number">1,234</div><div class="label">Total Students</div></div>
            <div class="stat-card"><div class="number">856</div><div class="label">Currently Enrolled</div></div>
            <div class="stat-card"><div class="number">98%</div><div class="label">Retention Rate</div></div>
            <div class="stat-card"><div class="number">12%</div><div class="label">Growth Rate</div></div>
        </div>
        <a href="../User_Dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>