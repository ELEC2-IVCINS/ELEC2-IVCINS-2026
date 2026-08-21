<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Tasks - UMak Registrar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fc; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #8b1e2b; padding-bottom: 15px; margin-bottom: 25px; }
        h1 { color: #003366; }
        h1 i { color: #8b1e2b; }
        .back-btn { display: inline-block; padding: 10px 25px; background: #003366; color: white; text-decoration: none; border-radius: 6px; }
        .back-btn:hover { background: #1a4d7a; }
        .task { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border-bottom: 1px solid #eee; }
        .task:hover { background: #f8f7f4; }
        .task .title { font-weight: 600; color: #003366; }
        .task .desc { color: #666; font-size: 0.9rem; }
        .task .status { padding: 4px 16px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef9e7; color: #b8860b; }
        .status-processing { background: #e8f0fe; color: #1a4d7a; }
        .umak-badge { background: #8b1e2b; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }
        .badge-count { background: #8b1e2b; color: white; padding: 2px 10px; border-radius: 12px; font-size: 0.75rem; margin-left: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Quick Tasks <span class="badge-count">4</span></h1>
            <span class="umak-badge">Pending Actions</span>
        </div>
        <p style="color: #666;">Pending actions that require your attention.</p>
        <div style="margin: 20px 0;">
            <div class="task">
                <div><div class="title">📄 Process TOR Request #2026-001</div><div class="desc">John Dela Cruz · BS Information Technology</div></div>
                <span class="status status-pending">Pending</span>
            </div>
            <div class="task">
                <div><div class="title">✅ Review Enrollment Form #2026-045</div><div class="desc">Maria Santos · BS Accountancy</div></div>
                <span class="status status-processing">Processing</span>
            </div>
            <div class="task">
                <div><div class="title">📋 Clearance Verification</div><div class="desc">Ramon Garcia · BS Engineering</div></div>
                <span class="status status-pending">Pending</span>
            </div>
            <div class="task">
                <div><div class="title">🎓 Graduation Application Review</div><div class="desc">Ana Reyes · BS Education</div></div>
                <span class="status status-processing">Processing</span>
            </div>
        </div>
        <a href="../User_Dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>