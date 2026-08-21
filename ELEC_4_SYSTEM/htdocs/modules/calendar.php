<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Calendar - UMak Registrar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fc; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #8b1e2b; padding-bottom: 15px; margin-bottom: 25px; }
        h1 { color: #003366; }
        h1 i { color: #8b1e2b; }
        .back-btn { display: inline-block; padding: 10px 25px; background: #003366; color: white; text-decoration: none; border-radius: 6px; }
        .back-btn:hover { background: #1a4d7a; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #003366; color: white; }
        tr:hover { background: #f5f5f5; }
        .status-ongoing { color: #16a34a; font-weight: 600; }
        .status-upcoming { color: #f59e0b; font-weight: 600; }
        .umak-badge { background: #8b1e2b; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Academic Calendar</h1>
            <span class="umak-badge"><i class="fas fa-calendar-check"></i> A.Y. 2026-2027</span>
        </div>
        <p style="color: #666;">View schedule of academic events.</p>
        <table>
            <thead><tr><th>Date</th><th>Event</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td>August 1, 2026</td><td>Start of Enrollment</td><td><span class="status-ongoing">● Ongoing</span></td></tr>
                <tr><td>August 20, 2026</td><td>End of Enrollment</td><td><span class="status-upcoming">⏰ Upcoming</span></td></tr>
                <tr><td>September 1, 2026</td><td>Start of Classes</td><td><span class="status-upcoming">⏰ Upcoming</span></td></tr>
                <tr><td>December 15, 2026</td><td>End of First Semester</td><td><span class="status-upcoming">⏰ Upcoming</span></td></tr>
                <tr><td>January 10, 2027</td><td>Start of Second Semester</td><td><span class="status-upcoming">⏰ Upcoming</span></td></tr>
            </tbody>
        </table>
        <a href="../User_Dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>