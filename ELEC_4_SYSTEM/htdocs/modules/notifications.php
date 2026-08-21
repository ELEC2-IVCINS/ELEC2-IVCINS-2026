<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - UMak Registrar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fc; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #8b1e2b; padding-bottom: 15px; margin-bottom: 25px; }
        h1 { color: #003366; }
        h1 i { color: #8b1e2b; }
        .back-btn { display: inline-block; padding: 10px 25px; background: #003366; color: white; text-decoration: none; border-radius: 6px; }
        .back-btn:hover { background: #1a4d7a; }
        .notification { padding: 18px 20px; border-left: 4px solid #FFD700; background: #f8f7f4; margin: 12px 0; border-radius: 6px; }
        .notification .title { font-weight: 600; color: #003366; }
        .notification .date { font-size: 0.8rem; color: #999; }
        .notification .body { margin-top: 8px; color: #444; }
        .notification.unread { border-left-color: #8b1e2b; background: #fef9f9; }
        .umak-badge { background: #8b1e2b; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }
        .badge-unread { background: #8b1e2b; color: white; font-size: 0.7rem; padding: 2px 10px; border-radius: 12px; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <span class="umak-badge">3 New</span>
        </div>
        <p style="color: #666;">View updates and announcements.</p>
        <div class="notification unread">
            <div class="title">📢 Enrollment Period Extended <span class="badge-unread">New</span></div>
            <div class="date">August 14, 2026 · 2:30 PM</div>
            <div class="body">The enrollment period has been extended until August 20, 2026.</div>
        </div>
        <div class="notification unread">
            <div class="title">📋 New TOR Request System <span class="badge-unread">New</span></div>
            <div class="date">August 10, 2026 · 9:00 AM</div>
            <div class="body">The new TOR request system is now available online.</div>
        </div>
        <div class="notification">
            <div class="title">🎓 Graduation Ceremony Schedule</div>
            <div class="date">August 5, 2026 · 4:15 PM</div>
            <div class="body">Graduation ceremony will be held on September 30, 2026.</div>
        </div>
        <a href="../User_Dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>