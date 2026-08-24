<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finscope</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/finscope/assets/css/premium.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Init theme immediately to prevent flashing
        const themes = ['dark', 'light', 'emerald'];
        let currentTheme = localStorage.getItem('finscope_theme') || 'dark';
        if (!themes.includes(currentTheme)) currentTheme = 'dark';
        document.documentElement.setAttribute('data-theme', currentTheme);
        
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            
            const updateIcon = (theme) => {
                themeIcon.className = theme === 'dark' ? 'bi bi-moon-stars-fill fs-5' : 
                                     theme === 'light' ? 'bi bi-sun-fill fs-5' : 
                                     'bi bi-gem fs-5';
            };
            
            updateIcon(currentTheme);
            document.body.setAttribute('data-theme', currentTheme);

            if (themeToggle) {
                themeToggle.onclick = () => {
                    const index = themes.indexOf(currentTheme);
                    currentTheme = themes[(index + 1) % themes.length];
                    document.documentElement.setAttribute('data-theme', currentTheme);
                    document.body.setAttribute('data-theme', currentTheme);
                    localStorage.setItem('finscope_theme', currentTheme);
                    
                    // Tiny spin animation
                    themeIcon.style.transition = 'transform 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
                    themeIcon.style.transform = 'rotate(180deg)';
                    setTimeout(() => {
                        updateIcon(currentTheme);
                        themeIcon.style.transform = 'rotate(0deg)';
                    }, 150);
                    
                    // Trigger chart updates if available
                    if (window.updateAllCharts) window.updateAllCharts();
                };
            }
        });
    </script>
</head>
<body data-theme="dark">

<?php if (isset($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg fin-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand text-neon fw-bold fs-4" href="/finscope/dashboard/">
            <i class="bi bi-droplet-half me-2"></i>Finscope
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="bi bi-list fs-2 text-secondary"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-3" href="/finscope/dashboard/"><i class="bi bi-grid-1x2-fill nav-icon me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/finscope/categories/"><i class="bi bi-pie-chart-fill nav-icon me-1"></i> Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/finscope/simulator/"><i class="bi bi-sliders nav-icon me-1"></i> Simulator</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/finscope/uploads/"><i class="bi bi-file-earmark-arrow-up-fill nav-icon me-1"></i> Import</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <button id="themeToggle" class="btn btn-link p-2 glass-card" style="border-radius: 12px; line-height: 1; border: 1px solid var(--border-card); color: var(--text-muted);">
                    <i class="bi bi-moon-stars-fill fs-5" id="themeIcon"></i>
                </button>
                
                <!-- Profile & Logout -->
                <div class="dropdown">
                    <button class="btn btn-neon dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i> Profile
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 200px; padding: 12px; border-radius: 16px;">
                        <li><a class="dropdown-item py-2" href="/finscope/profile/" style="border-radius: 8px;"><i class="bi bi-gear-fill me-2 text-muted"></i> Settings</a></li>
                        <li><hr class="dropdown-divider opacity-10"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="/finscope/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
<!-- Main container padding to offset fixed navbar -->
<main class="container" style="padding-top: 100px; padding-bottom: 60px; min-height: 100vh;">
<?php endif; ?>
