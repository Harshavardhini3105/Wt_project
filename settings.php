<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn, "SELECT fullname, email, study_level, goal, study_hours, pref_time FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($result);

$settings_result = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id='$user_id'");
$settings = mysqli_fetch_assoc($settings_result);

$name_parts = explode(" ", $user['fullname']);
$first_name = $name_parts[0];
$avatar_letter = strtoupper(substr($first_name, 0, 1));

// Check Dark Mode
$dark_mode = false;
$dm_query = mysqli_query($conn, "SELECT dark_mode FROM user_settings WHERE user_id='$user_id'");
if ($dm_query && mysqli_num_rows($dm_query) > 0) {
    $dm_row = mysqli_fetch_assoc($dm_query);
    if ($dm_row['dark_mode'] == 1) $dark_mode = true;
}
$body_class = $dark_mode ? 'class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | AI Study Planner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="dashboard.css"> 
    <link rel="stylesheet" href="settings.css"> 
</head>
<body <?php echo $body_class; ?>>

    <div class="dashboard-layout">
        
        <aside class="sidebar">
            <div class="sidebar-logo"> StudyPlaner</div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><span>📊</span> Dashboard</a>
                <a href="schedule.php"><span>📅</span> My Schedule</a>
                <a href="subjects.php"><span>📚</span> Subjects</a>
                <a href="analytics.php"><span>📈</span> Analytics</a>
                <a href="settings.php" class="active"><span>⚙️</span> Settings</a>
            </nav>
            <div class="sidebar-bottom">
                <a href="logout.php" class="logout-btn"><span>🚪</span> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            
            <header class="topbar">
                <div class="greeting">
                    <h1>Settings ⚙️</h1>
                    <p>Manage your account, preferences, and notifications.</p>
                </div>
                <div class="user-profile">
                    <button class="btn-primary" id="saveSettingsBtn">Save Changes</button>
                    <div class="avatar"><?php echo htmlspecialchars($avatar_letter); ?></div>
                </div>
            </header>

            <div class="settings-grid">
                
                <section class="card">
                    <h2 class="settings-section-title">👤 Profile Details</h2>
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" id="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>">
                    </div>
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                    <div class="input-group">
                        <label>School / University</label>
                        <input type="text" id="school" placeholder="Enter your institution" value="<?php echo htmlspecialchars($settings['school'] ?? ''); ?>">
                    </div>
                    <div class="input-group">
                        <label>Study Level</label>
                        <select id="study_level">
                            <option value="High School" <?php echo ($user['study_level'] == 'High School') ? 'selected' : ''; ?>>High School</option>
                            <option value="Undergraduate" <?php echo ($user['study_level'] == 'Undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                            <option value="Postgraduate" <?php echo ($user['study_level'] == 'Postgraduate') ? 'selected' : ''; ?>>Postgraduate</option>
                            <option value="Professional" <?php echo ($user['study_level'] == 'Professional') ? 'selected' : ''; ?>>Professional</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Primary Goal</label>
                        <input type="text" id="goal" placeholder="e.g. Ace finals, learn Python" value="<?php echo htmlspecialchars($user['goal'] ?? ''); ?>">
                    </div>
                </section>

                <section class="card">
                    <h2 class="settings-section-title">🎯 AI Study Preferences</h2>
                    <div class="input-group">
                        <label>Default Focus Session (Minutes)</label>
                        <input type="number" id="focus_session_mins" value="<?php echo $settings['focus_session_mins'] ?? 50; ?>">
                    </div>
                    <div class="input-group">
                        <label>Short Break Duration (Minutes)</label>
                        <input type="number" id="short_break_mins" value="<?php echo $settings['short_break_mins'] ?? 10; ?>">
                    </div>
                    <div class="input-group">
                        <label>Preferred Daily Study Limit (Hours)</label>
                        <input type="number" id="daily_limit_hours" value="<?php echo $settings['daily_limit_hours'] ?? 6; ?>">
                    </div>
                    <div class="input-group">
                        <label>Weekly Study Goal (Hours)</label>
                        <input type="number" id="study_hours" value="<?php echo htmlspecialchars($user['study_hours'] ?? 10); ?>">
                    </div>
                    <div class="input-group">
                        <label>Preferred Study Time</label>
                        <select id="pref_time">
                            <option value="Morning" <?php echo (strtolower($user['pref_time'] ?? '') == 'morning') ? 'selected' : ''; ?>>Morning</option>
                            <option value="Afternoon" <?php echo (strtolower($user['pref_time'] ?? '') == 'afternoon') ? 'selected' : ''; ?>>Afternoon</option>
                            <option value="Evening" <?php echo (strtolower($user['pref_time'] ?? '') == 'evening') ? 'selected' : ''; ?>>Evening</option>
                            <option value="Night" <?php echo (strtolower($user['pref_time'] ?? '') == 'night') ? 'selected' : ''; ?>>Night</option>
                        </select>
                    </div>
                </section>

                <section class="card" style="grid-column: 1 / -1;">
                    <h2 class="settings-section-title">🔔 Notifications & Appearance</h2>
                    
                    <div class="setting-row">
                        <div class="setting-info">
                            <strong>Notification via Email</strong>
                            <span>Get session reminders and schedule updates directly to your inbox.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="email_notify" <?php echo !empty($settings['email_notify']) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="setting-row">
                        <div class="setting-info">
                            <strong>Dark Mode</strong>
                            <span>Toggle deep dark mode aesthetic for the entire application.</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="dark_mode" <?php echo !empty($settings['dark_mode']) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <script src="settings.js"></script>
</body>
</html>
