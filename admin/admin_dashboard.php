<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 2));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PawConnect — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Poppins', system-ui, sans-serif;
  background: #F4F1E6;
  color: #012224;
  min-height: 100vh;
  display: flex;
}

/* ── Layout ── */
.app { display: flex; width: 100%; min-height: 100vh; }
.sidebar { width: 220px; min-width: 220px; background: #F4F1E6; border-right: 1px solid rgba(1,34,36,0.12); display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
.main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

/* ── Sidebar ── */
.sidebar-logo {
  padding: 18px 16px 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid rgba(1,34,36,0.1);
  margin-bottom: 4px;
}
.sidebar-logo img {
  width: 60px;
  height: 60px;
  object-fit: contain;
  border-radius: 50%;
}
.logo-fallback {
  width: 52px; height: 52px; background: #4E8DC0; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 22px;
}

.nav-section { padding: 18px 16px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: .1em; color: rgba(1,34,36,0.35); font-weight: 600; }
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 14px; font-size: 13px; color: rgba(1,34,36,0.55);
  cursor: pointer; border-radius: 8px; margin: 2px 8px;
  transition: background .15s, color .15s; font-weight: 400;
  text-decoration: none;
}
.nav-item:hover { background: rgba(78,141,192,0.12); color: #012224; }
.nav-item.active { background: rgba(78,141,192,0.15); color: #4E8DC0; font-weight: 600; }
.nav-item i { font-size: 17px; flex-shrink: 0; }
.nav-badge {
  margin-left: auto; background: #DA8063; color: #fff;
  font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 10px;
}
.sidebar-bottom { margin-top: auto; padding: 12px 8px; border-top: 1px solid rgba(1,34,36,0.1); }

/* ── Topbar ── */
.topbar {
  background: #F4F1E6;
  border-bottom: 1px solid rgba(1,34,36,0.1);
  padding: 13px 24px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 10; flex-shrink: 0;
}

.topbar-left { display: flex; align-items: center; gap: 12px; }
.topbar-brand { display: flex; align-items: center; gap: 9px; }
.topbar-mark { 
  width: 30px; 
  height: 30px; 
  border-radius: 50%; 
  background: #4E8DC0; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: #fff; 
  font-size: 15px; 
  flex-shrink: 0; 
  overflow: hidden; 
}
.topbar-mark img { 
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
  border-radius: 50%; 
}

.topbar-brand-name { font-size: 14.5px; font-weight: 700; color: #012224; letter-spacing: -.01em; }
.topbar-divider { width: 1px; height: 20px; background: rgba(1,34,36,0.14); }
.topbar-admin-badge { display: flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #2b5f8a; background: rgba(78,141,192,0.14); padding: 4px 10px; border-radius: 20px; }
.topbar-crumb { font-size: 12.5px; color: rgba(1,34,36,0.45); font-weight: 500; }
.topbar-right { display: flex; align-items: center; gap: 14px; position: relative; }
.topbar-icon { width: 36px; height: 36px; border-radius: 50%; border: 1.5px solid rgba(1,34,36,0.15); display: flex; align-items: center; justify-content: center; cursor: pointer; background: transparent; transition: background .15s; position: relative; }
.topbar-icon:hover { background: rgba(78,141,192,0.1); }
.topbar-icon i { font-size: 17px; color: #012224; }
.avatar { width: 36px; height: 36px; border-radius: 50%; background: #4E8DC0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #fff; cursor: pointer; }
.notif-btn { position: relative; }
.notif-dot { width: 8px; height: 8px; background: #DA8063; border-radius: 50%; position: absolute; top: 0; right: 0; border: 2px solid #F4F1E6; }

/* ── Notification Dropdown ── */
.notif-dropdown {
  display: none;
  position: absolute;
  top: calc(100% + 10px);
  right: 50px;
  width: 320px;
  background: #fff;
  border: 1px solid rgba(1,34,36,0.1);
  border-radius: 14px;
  box-shadow: 0 8px 24px rgba(1,34,36,0.12);
  z-index: 100;
  overflow: hidden;
}
.notif-dropdown.open { display: block; }
.notif-header { padding: 14px 16px; border-bottom: 1px solid rgba(1,34,36,0.08); display: flex; align-items: center; justify-content: space-between; }
.notif-header span { font-size: 13px; font-weight: 600; color: #012224; }
.notif-mark-read { font-size: 11px; color: #4E8DC0; cursor: pointer; background: none; border: none; font-family: inherit; }
.notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 12px 16px; border-bottom: 1px solid rgba(1,34,36,0.05); cursor: pointer; transition: background .15s; }
.notif-item:hover { background: #faf9f4; }
.notif-item.unread { background: rgba(78,141,192,0.04); }
.notif-item:last-child { border-bottom: none; }
.notif-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px; }
.notif-text { font-size: 12px; color: #012224; line-height: 1.5; flex: 1; }
.notif-time { font-size: 10px; color: rgba(1,34,36,0.4); margin-top: 2px; }
.unread-dot { width: 6px; height: 6px; background: #4E8DC0; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.notif-empty { padding: 24px; text-align: center; font-size: 13px; color: rgba(1,34,36,0.4); }

/* ── Page content ── */
.page { display: none; padding: 24px 26px; overflow-y: auto; flex: 1; background: #F4F1E6; }
.page.active { display: block; }
.page-header { margin-bottom: 22px; }
.page-header h2 { font-size: 19px; font-weight: 600; color: #012224; margin-bottom: 4px; }
.page-header p { font-size: 13px; color: rgba(1,34,36,0.5); font-weight: 400; }

/* ── Stat cards ── */
.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
.stat-card { background: #fff; border: 1px solid rgba(1,34,36,0.08); border-radius: 14px; padding: 18px; }
.stat-card .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; font-size: 20px; }
.ic-blue   { background: rgba(78,141,192,0.15); color: #4E8DC0; }
.ic-coral  { background: rgba(218,128,99,0.15); color: #DA8063; }
.ic-green  { background: rgba(180,177,86,0.18); color: #8a862e; }
.ic-pink   { background: rgba(246,197,180,0.5); color: #b85a3a; }
.stat-card .label { font-size: 11px; color: rgba(1,34,36,0.45); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; font-weight: 500; }
.stat-card .value { font-size: 28px; font-weight: 600; color: #012224; line-height: 1; }
.stat-card .sub { font-size: 11px; color: rgba(1,34,36,0.45); margin-top: 5px; }
.stat-card .sub.up   { color: #8a862e; }
.stat-card .sub.warn { color: #c0692e; }
.stat-card .sub.down { color: #b03030; }

/* ── Layout grids ── */
.two-col   { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* ── Panels ── */
.panel { background: #fff; border: 1px solid rgba(1,34,36,0.08); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.panel-head { padding: 14px 18px; border-bottom: 1px solid rgba(1,34,36,0.07); display: flex; align-items: center; justify-content: space-between; }
.panel-head .title { font-size: 13px; font-weight: 600; color: #012224; }
.panel-body { padding: 16px 18px; }

/* ── Tables ── */
.tbl { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.tbl th { padding: 10px 16px; text-align: left; font-size: 10px; font-weight: 600; color: rgba(1,34,36,0.4); background: #F4F1E6; border-bottom: 1px solid rgba(1,34,36,0.08); text-transform: uppercase; letter-spacing: .06em; }
.tbl td { padding: 11px 16px; border-bottom: 1px solid rgba(1,34,36,0.06); color: #012224; vertical-align: middle; }
.tbl tr:last-child td { border-bottom: none; }
.tbl tr.clickable { cursor: pointer; }
.tbl tr.clickable:hover td { background: #faf9f4; }

/* ── Badges ── */
.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.badge.pending  { background: rgba(218,128,99,0.15); color: #a04820; }
.badge.approved { background: rgba(180,177,86,0.2);  color: #6b6812; }
.badge.rejected { background: rgba(218,128,99,0.25); color: #8f2800; }
.badge.active   { background: rgba(180,177,86,0.2);  color: #6b6812; }
.badge.inactive { background: rgba(1,34,36,0.07);    color: rgba(1,34,36,0.5); }
.badge.owner    { background: rgba(78,141,192,0.15);  color: #2b5f8a; }
.badge.adopter  { background: rgba(246,197,180,0.5);  color: #7a3520; }
.badge.suspended { background: rgba(218,128,99,0.2); color: #8f2800; }

/* ── Buttons ── */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid; font-family: 'Poppins', inherit; transition: all .15s; }
.btn-primary { background: #4E8DC0; border-color: #4E8DC0; color: #fff; }
.btn-primary:hover { background: #3a78ab; border-color: #3a78ab; }
.btn-approve { background: rgba(180,177,86,0.18); border-color: #B4B156; color: #6b6812; }
.btn-approve:hover { background: rgba(180,177,86,0.32); }
.btn-reject  { background: rgba(218,128,99,0.18); border-color: #DA8063; color: #8f2800; }
.btn-reject:hover  { background: rgba(218,128,99,0.32); }
.btn-ghost   { background: #fff; border-color: rgba(1,34,36,0.15); color: #012224; }
.btn-ghost:hover   { background: #F4F1E6; }
.btn-danger  { background: rgba(218,128,99,0.18); border-color: #DA8063; color: #8f2800; }
.btn-danger:hover  { background: rgba(218,128,99,0.32); }
.btn-sm { padding: 5px 12px; font-size: 12px; }
.btn-warning { background: rgba(180,177,86,0.18); border-color: #B4B156; color: #6b6812; }
.btn-warning:hover { background: rgba(180,177,86,0.32); }

/* ── Toolbar ── */
.toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 7px; background: #fff; border: 1px solid rgba(1,34,36,0.14); border-radius: 9px; padding: 8px 13px; flex: 1; max-width: 320px; }
.search-box i { color: rgba(1,34,36,0.35); font-size: 15px; }
.search-box input { border: none; outline: none; font-size: 13px; color: #012224; background: transparent; width: 100%; font-family: 'Poppins', inherit; }
.search-box input::placeholder { color: rgba(1,34,36,0.35); }
.filter-select { background: #fff; border: 1px solid rgba(1,34,36,0.14); border-radius: 9px; padding: 8px 13px; font-size: 13px; color: #012224; cursor: pointer; font-family: 'Poppins', inherit; outline: none; }

/* ── Activity feed ── */
.activity-item { display: flex; align-items: flex-start; gap: 12px; padding: 11px 18px; border-bottom: 1px solid rgba(1,34,36,0.06); }
.activity-item:last-child { border-bottom: none; }
.act-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 15px; }
.act-blue  { background: rgba(78,141,192,0.15);  color: #4E8DC0; }
.act-coral { background: rgba(218,128,99,0.15);  color: #DA8063; }
.act-green { background: rgba(180,177,86,0.2);   color: #8a862e; }
.act-pink  { background: rgba(246,197,180,0.5);  color: #b85a3a; }
.act-text  { font-size: 12.5px; color: #012224; line-height: 1.5; }
.act-time  { font-size: 11px; color: rgba(1,34,36,0.4); margin-top: 2px; }

/* ── Chart bars ── */
.bar-chart { display: flex; align-items: flex-end; gap: 6px; height: 80px; padding: 0 4px; }
.bar-wrap { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; }
.bar { width: 100%; background: #4E8DC0; border-radius: 4px 4px 0 0; }
.bar.coral { background: #DA8063; }
.bar-label { font-size: 10px; color: rgba(1,34,36,0.4); }

/* ── Note banner ── */
.note-banner { background: rgba(246,197,180,0.45); border: 1px solid #F6C5B4; border-radius: 9px; padding: 11px 14px; font-size: 12px; color: #7a3520; display: flex; align-items: flex-start; gap: 8px; margin-bottom: 18px; }
.note-banner i { font-size: 15px; margin-top: 1px; flex-shrink: 0; }

/* ── Modal ── */
.overlay { display: none; position: fixed; inset: 0; background: rgba(1,34,36,0.45); align-items: flex-start; justify-content: center; padding: 40px 20px; z-index: 200; overflow-y: auto; }
.overlay.open { display: flex; }
.modal { background: #fff; border: 1px solid rgba(1,34,36,0.1); border-radius: 16px; width: 100%; max-width: 560px; overflow: hidden; margin: auto; }
.modal-head { padding: 18px 20px; border-bottom: 1px solid rgba(1,34,36,0.08); display: flex; align-items: center; justify-content: space-between; }
.modal-head h3 { font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 9px; color: #012224; }
.modal-close { background: none; border: none; cursor: pointer; font-size: 18px; color: rgba(1,34,36,0.45); transition: color .15s; }
.modal-close:hover { color: #012224; }
.modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
.modal-foot { padding: 14px 20px; border-top: 1px solid rgba(1,34,36,0.08); display: flex; gap: 8px; justify-content: flex-end; }
.modal-section { margin-bottom: 20px; }
.modal-section:last-child { margin-bottom: 0; }
.modal-section-title { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: rgba(1,34,36,0.4); font-weight: 600; margin-bottom: 11px; padding-bottom: 8px; border-bottom: 1px solid rgba(1,34,36,0.07); }
.info-row { display: flex; align-items: flex-start; gap: 10px; padding: 5px 0; }
.info-label { width: 145px; min-width: 145px; font-size: 12px; color: rgba(1,34,36,0.5); display: flex; align-items: center; gap: 5px; }
.info-label i { font-size: 13px; }
.info-val { font-size: 13px; color: #012224; flex: 1; line-height: 1.5; }

/* ── Form fields ── */
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 12px; font-weight: 500; color: rgba(1,34,36,0.6); margin-bottom: 5px; }
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%; padding: 10px 13px; border: 1px solid rgba(1,34,36,0.15); border-radius: 9px;
  font-size: 13px; font-family: 'Poppins', inherit; color: #012224; background: #fff; outline: none;
  transition: border-color .15s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: #4E8DC0; }
.form-group textarea { resize: vertical; min-height: 80px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

/* ── Feedback cards ── */
.feedback-card { background: #fff; border: 1px solid rgba(1,34,36,0.08); border-radius: 12px; padding: 16px 18px; margin-bottom: 12px; }
.feedback-card .fc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.feedback-card .fc-user { font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; color: #012224; }
.feedback-card .fc-date { font-size: 11px; color: rgba(1,34,36,0.4); }
.feedback-card .fc-body { font-size: 13px; color: rgba(1,34,36,0.7); line-height: 1.65; }
.feedback-card .fc-footer { margin-top: 10px; display: flex; align-items: center; gap: 8px; }
.stars { color: #B4B156; font-size: 14px; }

/* ── Tabs ── */
.tabs { display: flex; gap: 2px; background: rgba(1,34,36,0.07); border-radius: 10px; padding: 3px; margin-bottom: 18px; width: fit-content; }
.tab { padding: 7px 18px; border-radius: 8px; font-size: 13px; cursor: pointer; color: rgba(1,34,36,0.5); transition: background .15s, color .15s; font-weight: 400; }
.tab.active { background: #fff; color: #012224; font-weight: 500; box-shadow: 0 1px 4px rgba(1,34,36,0.1); }

/* ── User row avatar ── */
.user-ava { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #fff; flex-shrink: 0; }

/* ── Category tiles ── */
.cat-tile { text-align: center; padding: 12px; background: #F4F1E6; border-radius: 10px; }
.cat-tile .cat-val { font-size: 24px; font-weight: 600; margin-bottom: 3px; }
.cat-tile .cat-lbl { font-size: 11px; color: rgba(1,34,36,0.5); }

/* ── Toast ── */
.toast {
  position: fixed; bottom: 24px; right: 24px;
  background: #012224; color: #fff;
  padding: 12px 20px; border-radius: 10px;
  font-size: 13px; font-weight: 500;
  display: flex; align-items: center; gap: 9px;
  box-shadow: 0 4px 16px rgba(1,34,36,0.2);
  z-index: 9999;
  transform: translateY(80px); opacity: 0;
  transition: all .3s ease;
  pointer-events: none;
}
.toast.show { transform: translateY(0); opacity: 1; }
.toast.success i { color: #B4B156; }
.toast.error i { color: #DA8063; }

/* ── Confirm modal ── */
.confirm-modal { max-width: 400px; }
.confirm-body { padding: 24px 20px; text-align: center; }
.confirm-body i { font-size: 40px; margin-bottom: 12px; display: block; }
.confirm-body h4 { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
.confirm-body p { font-size: 13px; color: rgba(1,34,36,0.55); }

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(1,34,36,0.15); border-radius: 3px; }
</style>
</head>
<body>
<div class="app">

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<nav class="sidebar" role="navigation" aria-label="Main navigation">
  <div class="sidebar-logo">
    <img src="admin-img/logo.png" alt="PawConnect Logo"
         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div class="logo-fallback" style="display:none"><i class="ti ti-paw"></i></div>
  </div>

  <div style="padding:6px 0;flex:1">
    <div class="nav-section">Main</div>
    <div class="nav-item active" onclick="navigate(this,'dashboard')">
      <i class="ti ti-layout-dashboard"></i> Dashboard
    </div>
    <div class="nav-section">Management</div>
    <div class="nav-item" onclick="navigate(this,'facilities')">
      <i class="ti ti-building"></i> Facilities
    </div>
    <div class="nav-item" onclick="navigate(this,'users')">
      <i class="ti ti-users"></i> Users
    </div>
    <div class="nav-item" onclick="navigate(this,'verification')">
      <i class="ti ti-shield-check"></i> Verification
      <span class="nav-badge" id="badge-verification">6</span>
    </div>
    <div class="nav-section">Insights</div>
    <div class="nav-item" onclick="navigate(this,'reports')">
      <i class="ti ti-flag"></i> Reports &amp; Feedback
      <span class="nav-badge" id="badge-reports">3</span>
    </div>
  </div>
  <div class="sidebar-bottom">
    <a href="admin_logout.php" class="nav-item" onclick="return confirmLogout(event)">
      <i class="ti ti-logout"></i> Logout
    </a>
  </div>
</nav>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="main">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-brand">
        <div class="topbar-mark">
          <img src="admin-img/logo.png" alt="PawConnect logo"
              onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <i class="ti ti-paw" style="display:none"></i>
        </div>
        <span class="topbar-brand-name">PawConnect</span>
      </div>
      <div class="topbar-divider"></div>
      <span class="topbar-crumb" id="topbar-title">Dashboard</span>
    </div>
    <div class="topbar-right">
      <!-- Notification Bell -->
      <div class="topbar-icon notif-btn" onclick="toggleNotif(event)" id="notif-btn">
        <i class="ti ti-bell"></i>
        <div class="notif-dot" id="notif-dot"></div>
      </div>

      <!-- Notification Dropdown -->
      <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-header">
          <span>Notifications <span id="notif-count-badge" style="background:rgba(218,128,99,0.2);color:#8f2800;font-size:10px;padding:1px 7px;border-radius:10px;margin-left:4px">5</span></span>
          <button class="notif-mark-read" onclick="markAllRead()">Mark all as read</button>
        </div>
        <div id="notif-list">
          <div class="notif-item unread" onclick="handleNotifClick('verification')">
            <div class="notif-icon act-coral"><i class="ti ti-building"></i></div>
            <div style="flex:1">
              <div class="notif-text"><strong>Fur Ever Home</strong> submitted a new facility for review.</div>
              <div class="notif-time">15 minutes ago</div>
            </div>
            <div class="unread-dot"></div>
          </div>
          <div class="notif-item unread" onclick="handleNotifClick('reports')">
            <div class="notif-icon act-pink"><i class="ti ti-flag"></i></div>
            <div style="flex:1">
              <div class="notif-text">New report filed against <strong>PetGroomPH</strong>.</div>
              <div class="notif-time">3 hours ago</div>
            </div>
            <div class="unread-dot"></div>
          </div>
          <div class="notif-item unread" onclick="handleNotifClick('users')">
            <div class="notif-icon act-blue"><i class="ti ti-user-plus"></i></div>
            <div style="flex:1">
              <div class="notif-text"><strong>Maria Santos</strong> registered as a new pet owner.</div>
              <div class="notif-time">2 hours ago</div>
            </div>
            <div class="unread-dot"></div>
          </div>
          <div class="notif-item unread" onclick="handleNotifClick('verification')">
            <div class="notif-icon act-coral"><i class="ti ti-building"></i></div>
            <div style="flex:1">
              <div class="notif-text"><strong>Happy Paws Shelter</strong> submitted for verification.</div>
              <div class="notif-time">Yesterday, 4:12 PM</div>
            </div>
            <div class="unread-dot"></div>
          </div>
          <div class="notif-item unread" onclick="handleNotifClick('reports')">
            <div class="notif-icon act-pink"><i class="ti ti-flag"></i></div>
            <div style="flex:1">
              <div class="notif-text">Report filed against <strong>Carlo Tan</strong> for inappropriate behavior.</div>
              <div class="notif-time">Yesterday, 2:30 PM</div>
            </div>
            <div class="unread-dot"></div>
          </div>
        </div>
      </div>

      <div class="avatar"><?= htmlspecialchars($initials) ?></div>
      <span style="font-size:13px;color:rgba(1,34,36,0.55);font-weight:500"><?= htmlspecialchars($adminName) ?></span>
    </div>
  </div>

  <!-- ─── PAGE: DASHBOARD ─── -->
  <div class="page active" id="page-dashboard">
    <div class="page-header">
      <h2>Dashboard Analytics</h2>
      <p>System-wide overview of users, facilities, and platform activity.</p>
    </div>
    <div class="stat-grid">
      <div class="stat-card">
        <div class="icon ic-blue"><i class="ti ti-users"></i></div>
        <div class="label">Total users</div>
        <div class="value" id="stat-users">1,284</div>
        <div class="sub up"><i class="ti ti-trending-up" style="font-size:11px"></i> +42 this month</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-green"><i class="ti ti-building"></i></div>
        <div class="label">Listed facilities</div>
        <div class="value" id="stat-facilities">34</div>
        <div class="sub up"><i class="ti ti-circle-check" style="font-size:11px"></i> Active on map</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-coral"><i class="ti ti-shield-check"></i></div>
        <div class="label">Pending verifications</div>
        <div class="value" id="stat-verifications">6</div>
        <div class="sub warn"><i class="ti ti-clock" style="font-size:11px"></i> Awaiting review</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-pink"><i class="ti ti-flag"></i></div>
        <div class="label">Open reports</div>
        <div class="value" id="stat-reports">3</div>
        <div class="sub down"><i class="ti ti-alert-circle" style="font-size:11px"></i> Needs attention</div>
      </div>
    </div>
    <div class="two-col">
      <div>
        <div class="panel">
          <div class="panel-head"><span class="title">New user registrations</span><span style="font-size:11px;color:rgba(1,34,36,0.4)">This week</span></div>
          <div class="panel-body">
            <div class="bar-chart">
              <div class="bar-wrap"><div class="bar" style="height:40px"></div><div class="bar-label">Mon</div></div>
              <div class="bar-wrap"><div class="bar" style="height:55px"></div><div class="bar-label">Tue</div></div>
              <div class="bar-wrap"><div class="bar coral" style="height:35px"></div><div class="bar-label">Wed</div></div>
              <div class="bar-wrap"><div class="bar" style="height:70px"></div><div class="bar-label">Thu</div></div>
              <div class="bar-wrap"><div class="bar coral" style="height:50px"></div><div class="bar-label">Fri</div></div>
              <div class="bar-wrap"><div class="bar" style="height:30px"></div><div class="bar-label">Sat</div></div>
              <div class="bar-wrap"><div class="bar coral" style="height:65px"></div><div class="bar-label">Sun</div></div>
            </div>
            <div style="font-size:11px;color:rgba(1,34,36,0.4);margin-top:10px">Blue = pet owners &nbsp;·&nbsp; Coral = business owners</div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head"><span class="title">Facility categories</span></div>
          <div class="panel-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="cat-tile"><div class="cat-val" style="color:#4E8DC0">12</div><div class="cat-lbl">Veterinary clinics</div></div>
            <div class="cat-tile"><div class="cat-val" style="color:#DA8063">9</div><div class="cat-lbl">Grooming centers</div></div>
            <div class="cat-tile"><div class="cat-val" style="color:#B4B156">8</div><div class="cat-lbl">Pet shops</div></div>
            <div class="cat-tile"><div class="cat-val" style="color:#8a862e">5</div><div class="cat-lbl">Shelters / rescues</div></div>
          </div>
        </div>
      </div>
      <div>
        <div class="panel">
          <div class="panel-head"><span class="title">Recent activity</span></div>
          <div class="activity-item">
            <div class="act-icon act-blue"><i class="ti ti-user-plus"></i></div>
            <div><div class="act-text">New user registered: <strong>Maria Santos</strong> (Pet owner)</div><div class="act-time">2 minutes ago</div></div>
          </div>
          <div class="activity-item">
            <div class="act-icon act-coral"><i class="ti ti-building"></i></div>
            <div><div class="act-text">Facility submitted for review: <strong>Fur Ever Home</strong></div><div class="act-time">15 minutes ago</div></div>
          </div>
          <div class="activity-item">
            <div class="act-icon act-green"><i class="ti ti-circle-check"></i></div>
            <div><div class="act-text"><strong>Metro Vet Clinic</strong> approved and published to map</div><div class="act-time">1 hour ago</div></div>
          </div>
          <div class="activity-item">
            <div class="act-icon act-pink"><i class="ti ti-flag"></i></div>
            <div><div class="act-text">New report submitted against <strong>PetGroomPH</strong></div><div class="act-time">3 hours ago</div></div>
          </div>
          <div class="activity-item">
            <div class="act-icon act-blue"><i class="ti ti-user-plus"></i></div>
            <div><div class="act-text">New business owner registered: <strong>Juan Reyes</strong></div><div class="act-time">4 hours ago</div></div>
          </div>
          <div class="activity-item">
            <div class="act-icon act-coral"><i class="ti ti-building"></i></div>
            <div><div class="act-text">Facility submitted: <strong>Happy Paws Shelter</strong></div><div class="act-time">Yesterday, 5:10 PM</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── PAGE: FACILITY MANAGEMENT ─── -->
  <div class="page" id="page-facilities">
    <div class="page-header">
      <h2>Facility Management</h2>
      <p>Add, edit, update, or remove pet-related facilities on the platform.</p>
    </div>
    <div class="toolbar">
      <div class="search-box"><i class="ti ti-search"></i><input type="text" id="facility-search" placeholder="Search facilities…" oninput="filterFacilities()"></div>
      <select class="filter-select" id="facility-type-filter" onchange="filterFacilities()">
        <option value="">All types</option>
        <option>Veterinary</option><option>Grooming</option><option>Pet shop</option><option>Shelter</option>
      </select>
      <select class="filter-select" id="facility-status-filter" onchange="filterFacilities()">
        <option value="">All statuses</option><option>Active</option><option>Inactive</option>
      </select>
      <button class="btn btn-primary btn-sm" onclick="openAddFacility()"><i class="ti ti-plus"></i> Add facility</button>
    </div>
    <div class="panel">
      <div class="panel-head"><span class="title">All facilities <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="facility-count">(5)</span></span></div>
      <table class="tbl">
        <thead><tr><th>Facility name</th><th>Type</th><th>Address</th><th>Contact</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="facilities-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- ─── PAGE: USER MANAGEMENT ─── -->
  <div class="page" id="page-users">
    <div class="page-header">
      <h2>User Management</h2>
      <p>Monitor and manage all registered pet owners and business owners.</p>
    </div>
    <div class="toolbar">
      <div class="search-box"><i class="ti ti-search"></i><input type="text" id="user-search" placeholder="Search users…" oninput="filterUsers()"></div>
      <select class="filter-select" id="user-role-filter" onchange="filterUsers()">
        <option value="">All roles</option><option>Pet owner</option><option>Business owner</option>
      </select>
      <select class="filter-select" id="user-status-filter" onchange="filterUsers()">
        <option value="">All statuses</option><option>Active</option><option>Suspended</option>
      </select>
    </div>
    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-allusers')">All users</div>
      <div class="tab" onclick="switchTab(this,'tab-petowners')">Pet owners</div>
      <div class="tab" onclick="switchTab(this,'tab-bizowners')">Business owners</div>
    </div>
    <div id="tab-allusers">
      <div class="panel">
        <div class="panel-head"><span class="title">Registered users <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="user-count">(5)</span></span></div>
        <table class="tbl">
          <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="users-tbody"></tbody>
        </table>
      </div>
    </div>
    <div id="tab-petowners" style="display:none">
      <div class="panel">
        <table class="tbl">
          <thead><tr><th>User</th><th>Email</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="petowners-tbody"></tbody>
        </table>
      </div>
    </div>
    <div id="tab-bizowners" style="display:none">
      <div class="panel">
        <table class="tbl">
          <thead><tr><th>User</th><th>Email</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="bizowners-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ─── PAGE: VERIFICATION ─── -->
  <div class="page" id="page-verification">
    <div class="page-header">
      <h2>Verification &amp; Approval</h2>
      <p>Review and verify submitted pet-related businesses before they go live on the platform.</p>
    </div>
    <div class="panel">
      <div class="panel-head"><span class="title">Pending submissions <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="verif-count">(5)</span></span></div>
      <table class="tbl">
        <thead><tr><th>Facility name</th><th>Type</th><th>Submitted by</th><th>Date submitted</th><th>Status</th><th>Review</th></tr></thead>
        <tbody id="verif-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- ─── PAGE: REPORTS & FEEDBACK ─── -->
  <div class="page" id="page-reports">
    <div class="page-header">
      <h2>Reports &amp; Feedback</h2>
      <p>User-submitted feedback and reports for platform monitoring and improvement.</p>
    </div>
    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat-card">
        <div class="icon ic-pink"><i class="ti ti-flag"></i></div>
        <div class="label">Open reports</div>
        <div class="value" id="open-reports-count">3</div>
        <div class="sub down">Needs action</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-coral"><i class="ti ti-message-circle"></i></div>
        <div class="label">Total feedback</div>
        <div class="value">87</div>
        <div class="sub">All time</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-green"><i class="ti ti-star"></i></div>
        <div class="label">Avg. satisfaction</div>
        <div class="value">4.2</div>
        <div class="sub up">Out of 5</div>
      </div>
    </div>
    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-reports')">Reports <span id="reports-tab-badge" style="background:rgba(218,128,99,0.2);color:#8f2800;font-size:10px;padding:1px 7px;border-radius:10px;margin-left:4px">3</span></div>
      <div class="tab" onclick="switchTab(this,'tab-feedback')">User feedback</div>
    </div>
    <div id="tab-reports">
      <div class="panel">
        <div class="panel-head"><span class="title">Active reports</span></div>
        <table class="tbl">
          <thead><tr><th>Reported entity</th><th>Reported by</th><th>Reason</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="reports-tbody"></tbody>
        </table>
      </div>
    </div>
    <div id="tab-feedback" style="display:none">
      <div class="feedback-card">
        <div class="fc-header"><div class="fc-user"><div class="user-ava" style="background:#4E8DC0;width:28px;height:28px;font-size:10px">MS</div>Maria Santos</div><div class="fc-date">Jun 20, 2026</div></div>
        <div class="stars">★★★★★</div>
        <div class="fc-body" style="margin-top:6px">The facility map is incredibly helpful! Found a vet clinic near me in seconds. Would love to see more filter options for specializations.</div>
        <div class="fc-footer"><span class="badge active">Positive</span></div>
      </div>
      <div class="feedback-card">
        <div class="fc-header"><div class="fc-user"><div class="user-ava" style="background:#B4B156;width:28px;height:28px;font-size:10px">AC</div>Ana Cruz</div><div class="fc-date">Jun 18, 2026</div></div>
        <div class="stars">★★★★<span style="color:rgba(1,34,36,0.15)">★</span></div>
        <div class="fc-body" style="margin-top:6px">App works great overall. Some facility pages have outdated phone numbers. Please add a way for users to flag stale info directly from the listing.</div>
        <div class="fc-footer"><span class="badge pending">Suggestion</span></div>
      </div>
      <div class="feedback-card">
        <div class="fc-header"><div class="fc-user"><div class="user-ava" style="background:#DA8063;width:28px;height:28px;font-size:10px">JR</div>Juan Reyes</div><div class="fc-date">Jun 15, 2026</div></div>
        <div class="stars">★★★<span style="color:rgba(1,34,36,0.15)">★★</span></div>
        <div class="fc-body" style="margin-top:6px">As a business owner, the listing submission process was a bit slow. It took 3 days to get approved. Faster turnaround would really help.</div>
        <div class="fc-footer"><span class="badge rejected">Concern</span></div>
      </div>
    </div>
  </div>

</div><!-- end .main -->
</div><!-- end .app -->

<!-- ═══════════════ MODALS ═══════════════ -->

<!-- Verification Modal -->
<div class="overlay" id="verificationModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-shield-check" style="color:#4E8DC0"></i> <span id="vm-title">Facility Review</span></h3>
      <button class="modal-close" onclick="closeModal('verificationModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="note-banner"><i class="ti ti-info-circle"></i> Review the listing to verify accuracy before approving.</div>
      <div class="modal-section">
        <div class="modal-section-title">Facility information</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-building"></i> Name</div><div class="info-val" id="vm-name"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-map-pin"></i> Address</div><div class="info-val" id="vm-address"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-clock"></i> Operating hours</div><div class="info-val" id="vm-hours"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-phone"></i> Contact</div><div class="info-val" id="vm-contact"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-file-description"></i> Description</div><div class="info-val" id="vm-desc"></div></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Submitted by</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-user"></i> Account</div><div class="info-val" id="vm-submitter"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-calendar"></i> Date</div><div class="info-val" id="vm-date"></div></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('verificationModal')">Cancel</button>
      <button class="btn btn-reject" id="vm-reject-btn"><i class="ti ti-x"></i> Reject</button>
      <button class="btn btn-approve" id="vm-approve-btn"><i class="ti ti-check"></i> Approve &amp; publish</button>
    </div>
  </div>
</div>

<!-- Facility Add/Edit Modal -->
<div class="overlay" id="facilityFormModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-building" style="color:#4E8DC0"></i> <span id="ffm-title">Add facility</span></h3>
      <button class="modal-close" onclick="closeModal('facilityFormModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ffm-index" value="-1">
      <div class="modal-section">
        <div class="modal-section-title">Facility details</div>
        <div class="form-group"><label>Facility name</label><input type="text" id="ffm-name" placeholder="e.g. Metro Vet Clinic"></div>
        <div class="form-row">
          <div class="form-group"><label>Type</label>
            <select id="ffm-type"><option>Veterinary</option><option>Grooming</option><option>Pet shop</option><option>Shelter</option><option>Rescue</option></select>
          </div>
          <div class="form-group"><label>Contact number</label><input type="text" id="ffm-contact" placeholder="+63 9XX XXX XXXX"></div>
        </div>
        <div class="form-group"><label>Address</label><input type="text" id="ffm-address" placeholder="Street, City, Province"></div>
        <div class="form-group"><label>Operating hours</label><input type="text" id="ffm-hours" placeholder="e.g. Mon–Sat, 8:00 AM – 5:00 PM"></div>
        <div class="form-group"><label>Status</label>
          <select id="ffm-status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('facilityFormModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveFacility()"><i class="ti ti-device-floppy"></i> Save facility</button>
    </div>
  </div>
</div>

<!-- User Detail Modal -->
<div class="overlay" id="userDetailModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-user" style="color:#4E8DC0"></i> <span id="udm-title">User details</span></h3>
      <button class="modal-close" onclick="closeModal('userDetailModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="modal-section-title">Account information</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-user"></i> Full name</div><div class="info-val" id="udm-name"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-mail"></i> Email</div><div class="info-val" id="udm-email"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-phone"></i> Contact</div><div class="info-val" id="udm-contact"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-map-pin"></i> Address</div><div class="info-val" id="udm-address"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-id-badge"></i> Role</div><div class="info-val" id="udm-role"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-calendar"></i> Joined</div><div class="info-val" id="udm-joined"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-activity"></i> Status</div><div class="info-val" id="udm-status"></div></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('userDetailModal')">Close</button>
      <button class="btn btn-danger btn-sm" id="suspend-btn" onclick="toggleSuspend()"></button>
    </div>
  </div>
</div>

<!-- Report Detail Modal -->
<div class="overlay" id="reportDetailModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-flag" style="color:#DA8063"></i> Report detail</h3>
      <button class="modal-close" onclick="closeModal('reportDetailModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="modal-section-title">Report information</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-alert-circle"></i> Reported entity</div><div class="info-val" id="rdm-entity"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-user"></i> Reported by</div><div class="info-val" id="rdm-reporter"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-category"></i> Reason</div><div class="info-val" id="rdm-reason"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-calendar"></i> Date filed</div><div class="info-val" id="rdm-date"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-info-circle"></i> Status</div><div class="info-val" id="rdm-status"></div></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Description</div>
        <div style="font-size:13px;color:rgba(1,34,36,0.7);line-height:1.65" id="rdm-desc"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" id="rdm-dismiss-btn" onclick="dismissReport()">Dismiss report</button>
      <button class="btn btn-danger" id="rdm-action-btn" onclick="resolveReport()"><i class="ti ti-ban"></i> Resolve &amp; take action</button>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="overlay" id="deleteConfirmModal">
  <div class="modal confirm-modal">
    <div class="modal-head">
      <h3><i class="ti ti-trash" style="color:#DA8063"></i> Confirm delete</h3>
      <button class="modal-close" onclick="closeModal('deleteConfirmModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="confirm-body">
      <i class="ti ti-alert-triangle" style="color:#DA8063"></i>
      <h4>Delete this facility?</h4>
      <p id="delete-confirm-text">This action cannot be undone.</p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('deleteConfirmModal')">Cancel</button>
      <button class="btn btn-danger" onclick="confirmDelete()"><i class="ti ti-trash"></i> Delete</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <i class="ti ti-circle-check"></i>
  <span id="toast-msg">Action completed.</span>
</div>

<script>
// ══════════════════════════════════════════
//  DATA STORE
// ══════════════════════════════════════════
let facilities = [
  { name:'Metro Vet Clinic',        type:'Veterinary', address:'88 Bonifacio St, Pasig',       contact:'+63 998 765 4321', hours:'Mon–Sun, 7AM–9PM',  status:'Active'   },
  { name:'Happy Paws Shelter',      type:'Shelter',    address:'12 Mabini St, QC',              contact:'+63 912 345 6789', hours:'Mon–Sat, 8AM–5PM',  status:'Active'   },
  { name:'Waggy Grooming Co.',      type:'Grooming',   address:'5 Katipunan Ave, Marikina',     contact:'+63 917 222 3333', hours:'Mon–Sat, 9AM–7PM',  status:'Active'   },
  { name:'PetGroomPH',              type:'Grooming',   address:'22 Taft Ave, Manila',           contact:'+63 920 444 5555', hours:'Mon–Fri, 10AM–6PM', status:'Inactive' },
  { name:'Fur & Feathers Pet Shop', type:'Pet shop',   address:'67 Ortigas Ave, Pasig',         contact:'+63 933 666 7777', hours:'Daily, 9AM–8PM',    status:'Active'   },
];

let users = [
  { name:'Maria Santos', email:'maria.santos@email.com', contact:'+63 915 111 2222', address:'23 Sampaguita St, Caloocan', role:'Pet owner',      joined:'Jun 20, 2026', status:'Active',    ava:'#4E8DC0', initials:'MS' },
  { name:'Juan Reyes',   email:'juan.reyes@email.com',   contact:'+63 920 333 4444', address:'5 Bayanihan Rd, Mandaluyong', role:'Business owner', joined:'Jun 18, 2026', status:'Active',    ava:'#DA8063', initials:'JR' },
  { name:'Ana Cruz',     email:'ana.cruz@email.com',     contact:'+63 928 555 6666', address:'78 Luna St, Las Piñas',       role:'Pet owner',      joined:'Jun 15, 2026', status:'Active',    ava:'#B4B156', initials:'AC' },
  { name:'Carlo Tan',    email:'carlo.tan@email.com',    contact:'+63 935 777 8888', address:'11 Pag-asa Ave, Makati',      role:'Pet owner',      joined:'Jun 10, 2026', status:'Suspended', ava:'#012224', initials:'CT' },
  { name:'Rosa Lim',     email:'rosa.lim@email.com',     contact:'+63 941 999 0000', address:'34 Quezon Blvd, Manila',      role:'Business owner', joined:'May 28, 2026', status:'Active',    ava:'#F6C5B4', initials:'RL', initialsColor:'#7a3520' },
];

let verifications = [
  { name:'Happy Paws Shelter', type:'Shelter',    address:'12 Mabini St, QC',            hours:'Mon–Sat, 8AM–5PM',  contact:'+63 912 345 6789', desc:'Non-profit shelter for abandoned dogs and cats.',                 submitter:'admin@happypaws.ph',    date:'Jun 21, 2026', status:'Pending'  },
  { name:'Fur Ever Home',      type:'Rescue',     address:'45 Rizal Ave, Marikina',       hours:'Tue–Sun, 9AM–6PM',  contact:'+63 917 890 1234', desc:'Community-run rescue org matching strays with adopters.',         submitter:'info@furever.ph',       date:'Jun 21, 2026', status:'Pending'  },
  { name:'Waggy Grooming Co.', type:'Grooming',   address:'5 Katipunan Ave, Marikina',    hours:'Mon–Sat, 9AM–7PM',  contact:'+63 917 222 3333', desc:'Professional pet grooming salon for dogs and cats.',              submitter:'waggy@grooming.ph',     date:'Jun 20, 2026', status:'Pending'  },
  { name:'Metro Vet Clinic',   type:'Veterinary', address:'88 Bonifacio St, Pasig',       hours:'Mon–Sun, 7AM–9PM',  contact:'+63 998 765 4321', desc:'Full-service vet clinic — consultations, vaccines, surgery.',     submitter:'metro@vet.ph',          date:'Jun 18, 2026', status:'Approved' },
  { name:'PetGroomPH',         type:'Grooming',   address:'22 Taft Ave, Manila',          hours:'Mon–Fri, 10AM–6PM', contact:'+63 920 444 5555', desc:'Budget-friendly grooming for small to medium dogs.',              submitter:'contact@petgroomph.com',date:'Jun 15, 2026', status:'Rejected' },
];

let reports = [
  { entity:'PetGroomPH',     reporter:'Maria Santos', reason:'Misleading information',   date:'Jun 21, 2026', status:'Open',     desc:'The facility listed operating hours as Mon–Sun 8AM–8PM but was found closed on Sundays. Contact number is also unreachable.' },
  { entity:'Carlo Tan',      reporter:'Ana Cruz',     reason:'Inappropriate behavior',   date:'Jun 19, 2026', status:'Open',     desc:'User left inappropriate comments on a listing and sent unsolicited messages to other users.' },
  { entity:'Unnamed Pet Shop',reporter:'Juan Reyes',  reason:'Inaccurate operating hours',date:'Jun 17, 2026', status:'Open',    desc:'Operating hours listed are incorrect. The shop closes at 5PM not 8PM as stated on the platform.' },
];

let currentVerifIndex = -1;
let currentReportIndex = -1;
let currentUserIndex   = -1;
let deleteTargetIndex  = -1;

// ══════════════════════════════════════════
//  NAVIGATION
// ══════════════════════════════════════════
const pageTitles = {
  dashboard:'Dashboard Analytics', facilities:'Facility Management',
  users:'User Management', verification:'Verification & Approval', reports:'Reports & Feedback'
};

function navigate(el, pageId) {
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-' + pageId).classList.add('active');
  document.getElementById('topbar-title').textContent = pageTitles[pageId];
  closeNotif();
}

function switchTab(el, tabId) {
  const page = el.closest('.page') || document.body;
  page.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  page.querySelectorAll('[id^="tab-"]').forEach(t => { if(t.closest('.page') === page || document.getElementById(tabId)) t.style.display = 'none'; });
  // Only hide tabs within same parent
  const parent = el.closest('.tabs').parentElement;
  parent.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
  document.getElementById(tabId).style.display = 'block';
}

// ══════════════════════════════════════════
//  MODALS
// ══════════════════════════════════════════
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.overlay').forEach(ov => {
  ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});

//  TOAST
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  const icon = t.querySelector('i');
  document.getElementById('toast-msg').textContent = msg;
  t.className = 'toast ' + type;
  icon.className = type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

//  NOTIFICATIONS
let unreadCount = 5;

function toggleNotif(e) {
  e.stopPropagation();
  document.getElementById('notif-dropdown').classList.toggle('open');
}
function closeNotif() {
  document.getElementById('notif-dropdown').classList.remove('open');
}
document.addEventListener('click', e => {
  if (!document.getElementById('notif-btn').contains(e.target)) closeNotif();
});

function markAllRead() {
  document.querySelectorAll('.notif-item.unread').forEach(item => {
    item.classList.remove('unread');
    const dot = item.querySelector('.unread-dot');
    if (dot) dot.remove();
  });
  unreadCount = 0;
  document.getElementById('notif-dot').style.display = 'none';
  document.getElementById('notif-count-badge').style.display = 'none';
  showToast('All notifications marked as read.');
}

function handleNotifClick(page) {
  closeNotif();
  const navItem = document.querySelector(`.nav-item[onclick*="${page}"]`);
  if (navItem) navigate(navItem, page);
}

//  FACILITIES
function renderFacilities(list) {
  const tbody = document.getElementById('facilities-tbody');
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No facilities found.</td></tr>';
    return;
  }
  list.forEach((f, i) => {
    const realIdx = facilities.indexOf(f);
    tbody.innerHTML += `
      <tr>
        <td><strong>${esc(f.name)}</strong></td>
        <td>${esc(f.type)}</td>
        <td>${esc(f.address)}</td>
        <td>${esc(f.contact)}</td>
        <td><span class="badge ${f.status === 'Active' ? 'active' : 'inactive'}">${esc(f.status)}</span></td>
        <td>
          <button class="btn btn-ghost btn-sm" onclick="openEditFacility(${realIdx})"><i class="ti ti-edit"></i> Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteFacility(${realIdx})" style="margin-left:4px"><i class="ti ti-trash"></i></button>
        </td>
      </tr>`;
  });
  document.getElementById('facility-count').textContent = `(${list.length})`;
}

function filterFacilities() {
  const q      = document.getElementById('facility-search').value.toLowerCase();
  const type   = document.getElementById('facility-type-filter').value;
  const status = document.getElementById('facility-status-filter').value;
  const filtered = facilities.filter(f =>
    (!q || f.name.toLowerCase().includes(q) || f.address.toLowerCase().includes(q)) &&
    (!type   || f.type === type) &&
    (!status || f.status === status)
  );
  renderFacilities(filtered);
}

function openAddFacility() {
  document.getElementById('ffm-title').textContent = 'Add facility';
  document.getElementById('ffm-index').value = -1;
  document.getElementById('ffm-name').value    = '';
  document.getElementById('ffm-type').value    = 'Veterinary';
  document.getElementById('ffm-contact').value = '';
  document.getElementById('ffm-address').value = '';
  document.getElementById('ffm-hours').value   = '';
  document.getElementById('ffm-status').value  = 'Active';
  openModal('facilityFormModal');
}

function openEditFacility(idx) {
  const f = facilities[idx];
  document.getElementById('ffm-title').textContent = 'Edit facility — ' + f.name;
  document.getElementById('ffm-index').value   = idx;
  document.getElementById('ffm-name').value    = f.name;
  document.getElementById('ffm-type').value    = f.type;
  document.getElementById('ffm-contact').value = f.contact;
  document.getElementById('ffm-address').value = f.address;
  document.getElementById('ffm-hours').value   = f.hours;
  document.getElementById('ffm-status').value  = f.status;
  openModal('facilityFormModal');
}

function saveFacility() {
  const idx  = parseInt(document.getElementById('ffm-index').value);
  const name = document.getElementById('ffm-name').value.trim();
  if (!name) { showToast('Please enter a facility name.', 'error'); return; }
  const data = {
    name,
    type:    document.getElementById('ffm-type').value,
    contact: document.getElementById('ffm-contact').value.trim(),
    address: document.getElementById('ffm-address').value.trim(),
    hours:   document.getElementById('ffm-hours').value.trim(),
    status:  document.getElementById('ffm-status').value,
  };
  if (idx === -1) {
    facilities.push(data);
    showToast(`"${name}" added successfully.`);
  } else {
    facilities[idx] = data;
    showToast(`"${name}" updated successfully.`);
  }
  closeModal('facilityFormModal');
  renderFacilities(facilities);
}

function deleteFacility(idx) {
  deleteTargetIndex = idx;
  document.getElementById('delete-confirm-text').textContent = `"${facilities[idx].name}" will be permanently removed.`;
  openModal('deleteConfirmModal');
}

function confirmDelete() {
  const name = facilities[deleteTargetIndex].name;
  facilities.splice(deleteTargetIndex, 1);
  closeModal('deleteConfirmModal');
  renderFacilities(facilities);
  showToast(`"${name}" has been deleted.`);
}

// ══════════════════════════════════════════
//  USERS
// ══════════════════════════════════════════
function renderUsers(list) {
  const tbody = document.getElementById('users-tbody');
  tbody.innerHTML = '';
  list.forEach((u, i) => {
    const realIdx = users.indexOf(u);
    tbody.innerHTML += `
      <tr>
        <td><div style="display:flex;align-items:center;gap:9px">
          <div class="user-ava" style="background:${u.ava};color:${u.initialsColor||'#fff'}">${u.initials}</div>
          <strong>${esc(u.name)}</strong>
        </div></td>
        <td>${esc(u.email)}</td>
        <td><span class="badge ${u.role==='Pet owner'?'adopter':'owner'}">${esc(u.role)}</span></td>
        <td>${esc(u.joined)}</td>
        <td><span class="badge ${u.status==='Active'?'active':'suspended'}">${esc(u.status)}</span></td>
        <td><button class="btn btn-ghost btn-sm" onclick="openUserDetail(${realIdx})"><i class="ti ti-eye"></i> View</button></td>
      </tr>`;
  });
  document.getElementById('user-count').textContent = `(${list.length})`;

  // Pet owners tab
  const po = users.filter(u => u.role === 'Pet owner');
  document.getElementById('petowners-tbody').innerHTML = po.map(u => {
    const ri = users.indexOf(u);
    return `<tr>
      <td><div style="display:flex;align-items:center;gap:9px">
        <div class="user-ava" style="background:${u.ava};color:${u.initialsColor||'#fff'}">${u.initials}</div>
        <strong>${esc(u.name)}</strong></div></td>
      <td>${esc(u.email)}</td><td>${esc(u.joined)}</td>
      <td><span class="badge ${u.status==='Active'?'active':'suspended'}">${esc(u.status)}</span></td>
      <td><button class="btn btn-ghost btn-sm" onclick="openUserDetail(${ri})"><i class="ti ti-eye"></i> View</button></td>
    </tr>`;
  }).join('');

  // Biz owners tab
  const bo = users.filter(u => u.role === 'Business owner');
  document.getElementById('bizowners-tbody').innerHTML = bo.map(u => {
    const ri = users.indexOf(u);
    return `<tr>
      <td><div style="display:flex;align-items:center;gap:9px">
        <div class="user-ava" style="background:${u.ava};color:${u.initialsColor||'#fff'}">${u.initials}</div>
        <strong>${esc(u.name)}</strong></div></td>
      <td>${esc(u.email)}</td><td>${esc(u.joined)}</td>
      <td><span class="badge ${u.status==='Active'?'active':'suspended'}">${esc(u.status)}</span></td>
      <td><button class="btn btn-ghost btn-sm" onclick="openUserDetail(${ri})"><i class="ti ti-eye"></i> View</button></td>
    </tr>`;
  }).join('');
}

function filterUsers() {
  const q      = document.getElementById('user-search').value.toLowerCase();
  const role   = document.getElementById('user-role-filter').value;
  const status = document.getElementById('user-status-filter').value;
  const filtered = users.filter(u =>
    (!q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)) &&
    (!role   || u.role === role) &&
    (!status || u.status === status)
  );
  renderUsers(filtered);
}

function openUserDetail(idx) {
  currentUserIndex = idx;
  const u = users[idx];
  document.getElementById('udm-title').textContent   = u.name;
  document.getElementById('udm-name').textContent    = u.name;
  document.getElementById('udm-email').textContent   = u.email;
  document.getElementById('udm-contact').textContent = u.contact;
  document.getElementById('udm-address').textContent = u.address;
  document.getElementById('udm-role').textContent    = u.role;
  document.getElementById('udm-joined').textContent  = u.joined;
  document.getElementById('udm-status').textContent  = u.status;
  const btn = document.getElementById('suspend-btn');
  if (u.status === 'Active') {
    btn.innerHTML = '<i class="ti ti-ban"></i> Suspend user';
    btn.className = 'btn btn-danger btn-sm';
  } else {
    btn.innerHTML = '<i class="ti ti-circle-check"></i> Restore user';
    btn.className = 'btn btn-warning btn-sm';
  }
  openModal('userDetailModal');
}

function toggleSuspend() {
  const u = users[currentUserIndex];
  u.status = u.status === 'Active' ? 'Suspended' : 'Active';
  document.getElementById('udm-status').textContent = u.status;
  const btn = document.getElementById('suspend-btn');
  if (u.status === 'Active') {
    btn.innerHTML = '<i class="ti ti-ban"></i> Suspend user';
    btn.className = 'btn btn-danger btn-sm';
    showToast(`${u.name} has been restored.`);
  } else {
    btn.innerHTML = '<i class="ti ti-circle-check"></i> Restore user';
    btn.className = 'btn btn-warning btn-sm';
    showToast(`${u.name} has been suspended.`, 'error');
  }
  renderUsers(users);
  closeModal('userDetailModal');
}

// ══════════════════════════════════════════
//  VERIFICATION
// ══════════════════════════════════════════
function renderVerifications() {
  const tbody = document.getElementById('verif-tbody');
  tbody.innerHTML = '';
  verifications.forEach((v, i) => {
    const badgeClass = v.status === 'Approved' ? 'approved' : v.status === 'Rejected' ? 'rejected' : 'pending';
    tbody.innerHTML += `
      <tr class="clickable" onclick="openVerification(${i})">
        <td><strong>${esc(v.name)}</strong></td>
        <td>${esc(v.type)}</td>
        <td>${esc(v.submitter)}</td>
        <td>${esc(v.date)}</td>
        <td><span class="badge ${badgeClass}">${esc(v.status)}</span></td>
        <td><span style="font-size:12px;color:#4E8DC0;font-weight:500">${v.status === 'Pending' ? 'Review →' : 'View →'}</span></td>
      </tr>`;
  });
  const pending = verifications.filter(v => v.status === 'Pending').length;
  document.getElementById('verif-count').textContent = `(${verifications.length})`;
  document.getElementById('badge-verification').textContent = pending;
  document.getElementById('stat-verifications').textContent = pending;
}

function openVerification(i) {
  currentVerifIndex = i;
  const v = verifications[i];
  document.getElementById('vm-title').textContent     = v.name + ' — Review';
  document.getElementById('vm-name').textContent      = v.name;
  document.getElementById('vm-address').textContent   = v.address;
  document.getElementById('vm-hours').textContent     = v.hours;
  document.getElementById('vm-contact').textContent   = v.contact;
  document.getElementById('vm-desc').textContent      = v.desc;
  document.getElementById('vm-submitter').textContent = v.submitter;
  document.getElementById('vm-date').textContent      = v.date;
  const approveBtn = document.getElementById('vm-approve-btn');
  const rejectBtn  = document.getElementById('vm-reject-btn');
  if (v.status !== 'Pending') {
    approveBtn.style.display = 'none';
    rejectBtn.style.display  = 'none';
  } else {
    approveBtn.style.display = '';
    rejectBtn.style.display  = '';
    approveBtn.onclick = () => setVerifStatus(i, 'Approved');
    rejectBtn.onclick  = () => setVerifStatus(i, 'Rejected');
  }
  openModal('verificationModal');
}

function setVerifStatus(i, status) {
  verifications[i].status = status;
  closeModal('verificationModal');
  renderVerifications();
  showToast(`"${verifications[i].name}" has been ${status.toLowerCase()}.`);
}

// ══════════════════════════════════════════
//  REPORTS
// ══════════════════════════════════════════
function renderReports() {
  const tbody = document.getElementById('reports-tbody');
  tbody.innerHTML = '';
  reports.forEach((r, i) => {
    const badgeClass = r.status === 'Open' ? 'pending' : r.status === 'Resolved' ? 'approved' : 'inactive';
    tbody.innerHTML += `
      <tr>
        <td><strong>${esc(r.entity)}</strong></td>
        <td>${esc(r.reporter)}</td>
        <td>${esc(r.reason)}</td>
        <td>${esc(r.date)}</td>
        <td><span class="badge ${badgeClass}">${esc(r.status)}</span></td>
        <td><button class="btn btn-ghost btn-sm" onclick="openReportDetail(${i})"><i class="ti ti-eye"></i> View</button></td>
      </tr>`;
  });
  const open = reports.filter(r => r.status === 'Open').length;
  document.getElementById('open-reports-count').textContent = open;
  document.getElementById('badge-reports').textContent = open;
  document.getElementById('stat-reports').textContent  = open;
  document.getElementById('reports-tab-badge').textContent = open;
}

function openReportDetail(i) {
  currentReportIndex = i;
  const r = reports[i];
  document.getElementById('rdm-entity').textContent   = r.entity;
  document.getElementById('rdm-reporter').textContent = r.reporter;
  document.getElementById('rdm-reason').textContent   = r.reason;
  document.getElementById('rdm-date').textContent     = r.date;
  document.getElementById('rdm-status').textContent   = r.status;
  document.getElementById('rdm-desc').textContent     = r.desc;
  const dismissBtn = document.getElementById('rdm-dismiss-btn');
  const actionBtn  = document.getElementById('rdm-action-btn');
  if (r.status !== 'Open') {
    dismissBtn.disabled = true;
    actionBtn.disabled  = true;
    dismissBtn.style.opacity = '0.5';
    actionBtn.style.opacity  = '0.5';
  } else {
    dismissBtn.disabled = false;
    actionBtn.disabled  = false;
    dismissBtn.style.opacity = '';
    actionBtn.style.opacity  = '';
  }
  openModal('reportDetailModal');
}

function dismissReport() {
  reports[currentReportIndex].status = 'Dismissed';
  closeModal('reportDetailModal');
  renderReports();
  showToast('Report has been dismissed.');
}

function resolveReport() {
  reports[currentReportIndex].status = 'Resolved';
  closeModal('reportDetailModal');
  renderReports();
  showToast('Report marked as resolved.');
}

// ══════════════════════════════════════════
//  UTILITY
// ══════════════════════════════════════════
function esc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ══════════════════════════════════════════
//  INIT
// ══════════════════════════════════════════
renderFacilities(facilities);
renderUsers(users);
renderVerifications();
renderReports();
</script>
</body>
</html>