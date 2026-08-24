<?php
// index.php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finscope — Master Your Money</title>
    <meta name="description" content="Finscope is your intelligent personal finance companion. Track spending, identify leakage, and grow your wealth.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --neon: #38bdf8;
            --neon-green: #10b981;
            --neon-purple: #a78bfa;
            --bg: #060d1a;
            --glass: rgba(14, 26, 48, 0.75);
            --glass-border: rgba(56, 189, 248, 0.18);
            --text: #f8fafc;
            --muted: #94a3b8;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* === CANVAS BG === */
        #bgCanvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        /* === GLOWING ORB BLOBS === */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.35;
            pointer-events: none;
            z-index: 0;
            animation: orbFloat 12s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, #38bdf840, transparent 70%); top: -100px; left: -100px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #10b98130, transparent 70%); bottom: -80px; right: -80px; animation-delay: -4s; }
        .orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, #a78bfa25, transparent 70%); top: 40%; left: 60%; animation-delay: -8s; }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%  { transform: translate(30px, -20px) scale(1.05); }
            66%  { transform: translate(-20px, 15px) scale(0.95); }
        }

        /* === MAIN LAYOUT === */
        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
        }

        /* === LEFT HERO === */
        .hero-side {
            padding: 60px 50px 60px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--neon);
            letter-spacing: 0.5px;
            margin-bottom: 32px;
            width: fit-content;
            animation: fadeSlideIn 0.6s ease forwards;
        }

        .brand-badge .dot {
            width: 6px; height: 6px;
            background: var(--neon);
            border-radius: 50%;
            animation: pulse-dot 1.5s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 0 rgba(56,189,248,0.6); }
            50% { box-shadow: 0 0 0 5px rgba(56,189,248,0); }
        }

        .hero-title {
            font-size: clamp(2.4rem, 4vw, 3.8rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -2px;
            margin-bottom: 24px;
            animation: fadeSlideIn 0.7s ease 0.1s forwards;
            opacity: 0;
        }

        .hero-title .line-neon {
            background: linear-gradient(90deg, var(--neon), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 1.05rem;
            color: var(--muted);
            line-height: 1.7;
            max-width: 450px;
            margin-bottom: 48px;
            animation: fadeSlideIn 0.7s ease 0.2s forwards;
            opacity: 0;
        }

        /* Feature Pills */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            animation: fadeSlideIn 0.7s ease 0.3s forwards;
            opacity: 0;
        }
        .pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 13px;
            color: #cbd5e1;
            transition: all 0.3s ease;
        }
        .pill:hover {
            background: rgba(56,189,248,0.08);
            border-color: rgba(56,189,248,0.25);
            color: var(--neon);
            transform: translateY(-2px);
        }
        .pill i { font-size: 14px; color: var(--neon); }

        /* Stats Row */
        .hero-stats {
            display: flex;
            gap: 36px;
            margin-top: 48px;
            animation: fadeSlideIn 0.7s ease 0.4s forwards;
            opacity: 0;
        }
        .stat-item { }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
        }
        .stat-label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,0.08);
        }

        /* === RIGHT AUTH CARD === */
        .auth-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 80px 40px 40px;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--glass);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(56,189,248,0.05), inset 0 1px 0 rgba(255,255,255,0.07);
            animation: cardReveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.2s forwards;
            opacity: 0;
            transform: translateY(30px);
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(56,189,248,0.6), rgba(16,185,129,0.4), transparent);
        }

        /* Shimmer line on top */
        .auth-card::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.02), transparent);
            animation: cardShimmer 4s ease-in-out infinite;
        }
        @keyframes cardShimmer {
            0% { left: -100%; }
            100% { left: 200%; }
        }

        @keyframes cardReveal {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Logo in card */
        .card-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }
        .logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--neon), var(--neon-green));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            box-shadow: 0 0 20px rgba(56,189,248,0.4);
        }
        .logo-text {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .logo-text span { color: var(--neon); }

        /* Tab switcher */
        .tab-switcher {
            display: flex;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 30px;
            position: relative;
        }
        .tab-btn {
            flex: 1;
            border: none;
            background: transparent;
            color: var(--muted);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 9px;
            cursor: pointer;
            transition: color 0.3s ease;
            position: relative;
            z-index: 2;
        }
        .tab-btn.active { color: var(--text); }
        .tab-indicator {
            position: absolute;
            top: 4px; bottom: 4px; left: 4px;
            width: calc(50% - 4px);
            background: rgba(56,189,248,0.12);
            border: 1px solid rgba(56,189,248,0.2);
            border-radius: 9px;
            transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .tab-indicator.right { transform: translateX(100%); }

        /* Form */
        .auth-form { display: none; }
        .auth-form.active { display: block; animation: formSlideIn 0.4s ease; }
        @keyframes formSlideIn {
            from { opacity: 0; transform: translateX(12px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .form-heading {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        .form-sub {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 28px;
        }

        .field-group {
            margin-bottom: 18px;
            position: relative;
        }
        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .field-wrap {
            position: relative;
        }
        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 16px;
            transition: color 0.3s ease;
            pointer-events: none;
        }
        .field-input {
            width: 100%;
            background: rgba(0,0,0,0.35);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 13px 14px 13px 44px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--text);
            outline: none;
            transition: all 0.3s ease;
        }
        .field-input::placeholder { color: #577399; }
        .field-input:focus {
            border-color: var(--neon);
            background: rgba(56,189,248,0.05);
            box-shadow: 0 0 0 3px rgba(56,189,248,0.12), 0 0 20px rgba(56,189,248,0.08);
        }
        .field-input:focus ~ .field-icon,
        .field-wrap:focus-within .field-icon { color: var(--neon); }

        /* Eye toggle */
        .eye-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--muted);
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            transition: color 0.3s;
        }
        .eye-btn:hover { color: var(--neon); }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
            margin-top: 8px;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--neon), #0ea5e9);
            color: #fff;
            box-shadow: 0 4px 20px rgba(56,189,248,0.35);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(56,189,248,0.5);
        }
        .btn-login:active { transform: translateY(0); }

        .btn-register {
            background: linear-gradient(135deg, var(--neon-green), #059669);
            color: #fff;
            box-shadow: 0 4px 20px rgba(16,185,129,0.35);
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(16,185,129,0.5);
        }

        /* Shine on button hover */
        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent);
            transform: skewX(-25deg);
            transition: 0.6s;
        }
        .btn-submit:hover::after { left: 200%; }

        /* Alert */
        .alert-msg {
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: formSlideIn 0.4s ease;
        }
        .alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); color: #f87171; }
        .alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #34d399; }

        /* Divider */
        .divider { margin: 22px 0; display: flex; align-items: center; gap: 12px; }
        .divider-line { flex: 1; height: 1px; background: rgba(255,255,255,0.06); }
        .divider-text { font-size: 11px; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }

        /* Trust badges */
        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 24px;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #94a3b8;
        }
        .trust-item i { font-size: 12px; color: #94a3b8; }

        /* Mobile */
        @media (max-width: 900px) {
            .page-wrapper { grid-template-columns: 1fr; }
            .hero-side { padding: 50px 30px 20px; text-align: center; }
            .hero-sub, .feature-pills { margin-left: auto; margin-right: auto; }
            .feature-pills { justify-content: center; }
            .hero-stats { justify-content: center; }
            .brand-badge { margin: 0 auto 24px; }
            .auth-side { padding: 20px 24px 60px; }
            .hero-title { font-size: 2.2rem; }
        }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Floating chart mock in hero */
        .mock-chart {
            margin-top: 40px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 20px;
            animation: fadeSlideIn 0.7s ease 0.5s forwards;
            opacity: 0;
        }
        .mock-bars {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 70px;
        }
        .mock-bar {
            flex: 1;
            border-radius: 6px 6px 0 0;
            transition: height 0.5s ease;
            animation: barGrow 1s ease forwards;
            transform-origin: bottom;
        }
        @keyframes barGrow {
            from { transform: scaleY(0); }
            to { transform: scaleY(1); }
        }
        .mock-bar:nth-child(1) { background: linear-gradient(to top, #38bdf8, #0ea5e9); height: 40%; animation-delay: 0.6s; }
        .mock-bar:nth-child(2) { background: linear-gradient(to top, #10b981, #059669); height: 65%; animation-delay: 0.7s; }
        .mock-bar:nth-child(3) { background: linear-gradient(to top, #a78bfa, #7c3aed); height: 45%; animation-delay: 0.8s; }
        .mock-bar:nth-child(4) { background: linear-gradient(to top, #38bdf8, #0ea5e9); height: 80%; animation-delay: 0.9s; }
        .mock-bar:nth-child(5) { background: linear-gradient(to top, #10b981, #059669); height: 55%; animation-delay: 1s; }
        .mock-bar:nth-child(6) { background: linear-gradient(to top, #f59e0b, #d97706); height: 70%; animation-delay: 1.1s; }
        .mock-label { font-size: 11px; color: #94a3b8; margin-top: 8px; text-align: center; }

        /* Loading overlay on form submit */
        .btn-submit .btn-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- Animated background canvas -->
<canvas id="bgCanvas"></canvas>

<!-- Glowing orbs -->
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="page-wrapper">
    <!-- LEFT: Hero Section -->
    <div class="hero-side">
        <div class="brand-badge">
            <span class="dot"></span>
            FINSCOPE — PERSONAL FINANCE OS
        </div>

        <h1 class="hero-title">
            Spot the leaks,<br>
            <span class="line-neon">seal the wealth.</span>
        </h1>

        <p class="hero-sub">
            Beautifully insightful analytics to dissect your spending leakage. Log via PDF/CSV, visualize patterns, and build a smarter financial future.
        </p>

        <div class="feature-pills">
            <div class="pill"><i class="bi bi-graph-up-arrow"></i> Spending Analytics</div>
            <div class="pill"><i class="bi bi-shield-check"></i> ESG Score</div>
            <div class="pill"><i class="bi bi-robot"></i> Smart Insights</div>
            <div class="pill"><i class="bi bi-file-earmark-pdf"></i> PDF/CSV Import</div>
            <div class="pill"><i class="bi bi-piggy-bank"></i> Savings Simulator</div>
        </div>

        <!-- Mock chart -->
        <div class="mock-chart" style="max-width: 380px;">
            <div style="font-size:12px; color: #94a3b8; margin-bottom:12px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase;">6-Month Behavior Trend</div>
            <div class="mock-bars">
                <div class="mock-bar"></div>
                <div class="mock-bar"></div>
                <div class="mock-bar"></div>
                <div class="mock-bar"></div>
                <div class="mock-bar"></div>
                <div class="mock-bar"></div>
            </div>
            <div class="mock-label">Oct · Nov · Dec · Jan · Feb · Mar</div>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-value">5</div>
                <div class="stat-label">Categories Tracked</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-value">100%</div>
                <div class="stat-label">Privacy First</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-value">∞</div>
                <div class="stat-label">Insights</div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Auth Card -->
    <div class="auth-side">
        <div class="auth-card">
            <!-- Logo -->
            <div class="card-logo">
                <div class="logo-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                <div class="logo-text">Fin<span>scope</span></div>
            </div>

            <!-- Tab Switcher -->
            <div class="tab-switcher" id="tabSwitcher">
                <div class="tab-indicator" id="tabIndicator"></div>
                <button class="tab-btn active" id="tabLogin" onclick="switchTab('login')">Sign In</button>
                <button class="tab-btn" id="tabRegister" onclick="switchTab('register')">Create Account</button>
            </div>

            <!-- LOGIN FORM -->
            <div class="auth-form active" id="formLogin">
                <div class="form-heading">Welcome back 👋</div>
                <div class="form-sub">Sign in to continue to your dashboard.</div>

                <form action="auth/login.php" method="POST" id="loginForm">
                    <div class="field-group">
                        <label class="field-label">Email Address</label>
                        <div class="field-wrap">
                            <span class="field-icon"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" name="email" class="field-input" placeholder="you@example.com" required autocomplete="email">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Password</label>
                        <div class="field-wrap">
                            <span class="field-icon"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="password" id="loginPassword" class="field-input" placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" class="eye-btn" onclick="togglePass('loginPassword', this)">
                                <i class="bi bi-eye-slash-fill"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit btn-login">
                        Access Dashboard &nbsp;<i class="bi bi-arrow-right-short"></i>
                    </button>
                </form>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert-msg alert-error">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert-msg alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <?= htmlspecialchars($_GET['msg']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- REGISTER FORM -->
            <div class="auth-form" id="formRegister">
                <div class="form-heading">Start your journey 🚀</div>
                <div class="form-sub">Create your free account in seconds.</div>

                <form action="auth/register.php" method="POST" id="registerForm">
                    <div class="field-group">
                        <label class="field-label">Email Address</label>
                        <div class="field-wrap">
                            <span class="field-icon"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" name="email" class="field-input" placeholder="you@example.com" required autocomplete="email">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Password</label>
                        <div class="field-wrap">
                            <span class="field-icon"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="password" id="regPassword" class="field-input" placeholder="Create a strong password" required autocomplete="new-password">
                            <button type="button" class="eye-btn" onclick="togglePass('regPassword', this)">
                                <i class="bi bi-eye-slash-fill"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Password strength -->
                    <div style="margin-bottom:16px;">
                        <div style="height:4px; background:rgba(255,255,255,0.05); border-radius:99px; overflow:hidden;">
                            <div id="strengthBar" style="height:100%; width:0%; border-radius:99px; transition: width 0.4s ease, background 0.4s ease;"></div>
                        </div>
                        <div id="strengthLabel" style="font-size:11px; color: var(--muted); margin-top:5px;"></div>
                    </div>

                    <button type="submit" class="btn-submit btn-register">
                        Create Free Account &nbsp;<i class="bi bi-person-plus-fill"></i>
                    </button>
                </form>
            </div>

            <!-- Trust -->
            <div class="trust-badges">
                <div class="trust-item"><i class="bi bi-shield-lock-fill"></i> Secure</div>
                <div class="trust-item"><i class="bi bi-house-lock-fill"></i> Local Only</div>
                <div class="trust-item"><i class="bi bi-eye-slash-fill"></i> Private</div>
            </div>
        </div>
    </div>
</div>

<script>
    /* ===== PARTICLE CANVAS ===== */
    const canvas = document.getElementById('bgCanvas');
    const ctx = canvas.getContext('2d');
    let particles = [];
    let W, H;

    function resize() {
        W = canvas.width = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    class Particle {
        constructor() { this.reset(); }
        reset() {
            this.x = Math.random() * W;
            this.y = Math.random() * H;
            this.size = Math.random() * 1.5 + 0.3;
            this.speedX = (Math.random() - 0.5) * 0.3;
            this.speedY = (Math.random() - 0.5) * 0.3;
            this.alpha = Math.random() * 0.5 + 0.1;
            this.color = Math.random() < 0.5 ? '56,189,248' : Math.random() < 0.5 ? '16,185,129' : '167,139,250';
        }
        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            if (this.x < 0 || this.x > W || this.y < 0 || this.y > H) this.reset();
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${this.color},${this.alpha})`;
            ctx.fill();
        }
    }

    for (let i = 0; i < 120; i++) particles.push(new Particle());

    function drawGrid() {
        ctx.strokeStyle = 'rgba(56,189,248,0.025)';
        ctx.lineWidth = 0.5;
        const gap = 60;
        for (let x = 0; x < W; x += gap) {
            ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke();
        }
        for (let y = 0; y < H; y += gap) {
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke();
        }
    }

    function animate() {
        ctx.clearRect(0, 0, W, H);
        drawGrid();
        particles.forEach(p => { p.update(); p.draw(); });
        requestAnimationFrame(animate);
    }
    animate();

    /* ===== TAB SWITCHER ===== */
    function switchTab(tab) {
        const indicator = document.getElementById('tabIndicator');
        const loginBtn = document.getElementById('tabLogin');
        const regBtn = document.getElementById('tabRegister');
        const formLogin = document.getElementById('formLogin');
        const formReg = document.getElementById('formRegister');

        if (tab === 'login') {
            indicator.classList.remove('right');
            loginBtn.classList.add('active');
            regBtn.classList.remove('active');
            formLogin.classList.add('active');
            formReg.classList.remove('active');
        } else {
            indicator.classList.add('right');
            regBtn.classList.add('active');
            loginBtn.classList.remove('active');
            formReg.classList.add('active');
            formLogin.classList.remove('active');
        }
    }

    // Auto-switch to register if error in register
    <?php if (isset($_GET['tab']) && $_GET['tab'] === 'register'): ?>
    switchTab('register');
    <?php endif; ?>

    /* ===== PASSWORD VISIBILITY ===== */
    function togglePass(inputId, btn) {
        const inp = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'bi bi-eye-fill';
        } else {
            inp.type = 'password';
            icon.className = 'bi bi-eye-slash-fill';
        }
    }

    /* ===== PASSWORD STRENGTH ===== */
    const regPwd = document.getElementById('regPassword');
    const bar = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');

    regPwd.addEventListener('input', () => {
        const val = regPwd.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const map = [
            { w: '0%', color: 'transparent', text: '' },
            { w: '25%', color: '#ef4444', text: 'Weak' },
            { w: '50%', color: '#f59e0b', text: 'Fair' },
            { w: '75%', color: '#38bdf8', text: 'Good' },
            { w: '100%', color: '#10b981', text: 'Strong 💪' },
        ];
        bar.style.width = map[score].w;
        bar.style.background = map[score].color;
        label.textContent = map[score].text;
    });

    /* ===== FORM SUBMIT LOADING ===== */
    document.querySelectorAll('.auth-form form').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('.btn-submit');
            btn.disabled = true;
            btn.innerHTML = '<div class="btn-spinner" style="display:inline-block;"></div>';
        });
    });
</script>

</body>
</html>
