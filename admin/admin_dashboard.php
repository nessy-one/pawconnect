<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
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
:root {
  --blue: #4E8DC0;
  --blue-soft: rgba(78,141,192,0.15);
  --surface: #fff;
  --muted: rgba(1,34,36,0.5);
  --border: rgba(1,34,36,0.12);
}

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
  padding: 16px 14px 12px;
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
  padding: 8px 13px; font-size: 12px; color: #012224;
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

.sidebar-footer {
  margin-top: 0;
  padding: 20px 8px;
  border-top: 1px solid var(--border);
}

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
.avatar { width: 36px; height: 36px; border-radius: 50%; background: #4E8DC0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #fff; cursor: pointer; overflow: hidden; flex-shrink: 0; transition: box-shadow .15s; }
.avatar:hover { box-shadow: 0 0 0 3px rgba(78,141,192,0.25); }
.avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
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
.badge.vet-role { background: rgba(78,141,192,0.22); color: #1f4a6b; }
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

/* ── Logout overlay  ── */
.logout i { width: 18px; text-align: center; font-size: 14px; }

.btn-prim{
  background:var(--blue);color:#fff;font-weight:700;font-size:13.5px;
  padding:10px 18px;border-radius:9px;border:none;cursor:pointer;
  display:inline-flex;align-items:center;gap:8px;
}
.btn-prim:hover{filter:brightness(0.95);}
.logout {
  display: flex; align-items: center; gap: 10px; width: 100%;
  padding: 8px 13px; font-size: 13px; color: #012224;
  cursor: pointer; border-radius: 8px;
  background: none; border: none; font-family: 'Poppins', inherit;
  transition: background .15s, color .15s;
}
.logout:hover { background: rgba(218,128,99,0.12); color: #8f2800; }

.btn-mini.cancel {
  background: #fff; border: 1px solid rgba(1,34,36,0.15); color: #012224;
  font-size: 13.5px; font-weight: 500; border-radius: 9px; cursor: pointer;
  font-family: 'Poppins', inherit;
}
.btn-mini.cancel:hover { background: #F4F1E6; }

.logout-overlay{
  position:fixed;inset:0;background:rgba(32,36,31,.55);
  display:none;align-items:center;justify-content:center;z-index:100;
}
.logout-overlay.show{display:flex;}
.logout-card {
  background:var(--surface);border-radius:16px;padding:32px 36px;
  text-align:center;max-width:320px;
  border: 1px solid rgba(1,34,36,0.08);
  box-shadow: 0 20px 60px rgba(1,34,36,0.25);
}
.logout-card .mark{
  width:46px;height:46px;border-radius:50%;background:var(--blue-soft);color:var(--blue);
  display:flex;align-items:center;justify-content:center;margin:0 auto 14px auto;font-size:20px;
}
.logout-card h3{font-size:16px;margin-bottom:6px;}
.logout-card p{font-size:12.5px;color:var(--muted);margin-bottom:18px;}

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(1,34,36,0.15); border-radius: 3px; }

/* ── Clickable profile links ── */
.profile-link { display: inline-flex; align-items: center; gap: 9px; cursor: pointer; border-radius: 8px; padding: 2px 4px; margin: -2px -4px; transition: background .15s; }
.profile-link:hover { background: rgba(78,141,192,0.1); }
.profile-link:hover strong { color: #4E8DC0; }
.name-link { color: #4E8DC0; cursor: pointer; font-weight: 500; text-decoration: none; }
.name-link:hover { text-decoration: underline; }

/* ── Report / feedback images ── */
.report-thumbs { display: flex; gap: 6px; margin-top: 2px; }
.report-thumb { width: 34px; height: 34px; border-radius: 8px; object-fit: cover; cursor: pointer; border: 1px solid rgba(1,34,36,0.1); transition: transform .15s; }
.report-thumb:hover { transform: scale(1.08); }
.report-thumb-more { width: 34px; height: 34px; border-radius: 8px; background: rgba(1,34,36,0.08); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600; color: rgba(1,34,36,0.5); }
.evidence-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.evidence-gallery img { width: 100%; height: 90px; object-fit: cover; border-radius: 10px; cursor: pointer; border: 1px solid rgba(1,34,36,0.1); transition: opacity .15s; }
.evidence-gallery img:hover { opacity: .85; }
.lightbox-overlay { display: none; position: fixed; inset: 0; background: rgba(1,34,36,0.85); z-index: 500; align-items: center; justify-content: center; padding: 40px; }
.lightbox-overlay.open { display: flex; }
.lightbox-overlay img { max-width: 90vw; max-height: 85vh; border-radius: 10px; }
.lightbox-close { position: absolute; top: 20px; right: 28px; color: #fff; font-size: 26px; cursor: pointer; background: none; border: none; }

/* ── RFID ── */
.rfid-chip { display: inline-flex; align-items: center; gap: 6px; font-family: 'Courier New', monospace; font-size: 12px; background: rgba(1,34,36,0.06); padding: 4px 10px; border-radius: 7px; color: #012224; letter-spacing: .02em; }
.rfid-chip i { color: #4E8DC0; font-size: 14px; }
.pet-ava { width: 34px; height: 34px; border-radius: 10px; background: rgba(78,141,192,0.15); color: #4E8DC0; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
.scan-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(1,34,36,0.07); }
.scan-item:last-child { border-bottom: none; }
.scan-dot { width: 9px; height: 9px; border-radius: 50%; background: #4E8DC0; margin-top: 5px; flex-shrink: 0; }
.scan-text { font-size: 12.5px; color: #012224; }
.scan-meta { font-size: 11px; color: rgba(1,34,36,0.45); margin-top: 2px; }
.badge.deactivated { background: rgba(1,34,36,0.08); color: rgba(1,34,36,0.5); }
.badge.replaced { background: rgba(180,177,86,0.15); color: #8a862e; }

/* ── DB status card ── */
.db-status-card { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid rgba(1,34,36,0.08); border-radius: 12px; padding: 12px 16px; font-size: 12px; color: rgba(1,34,36,0.6); margin-bottom: 16px; }
.db-status-dot { width: 8px; height: 8px; border-radius: 50%; background: #DA8063; flex-shrink: 0; }
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
    <div class="nav-item" onclick="navigate(this,'adoption')">
      <i class="ti ti-heart-handshake"></i> Adoption Requests
      <span class="nav-badge" id="badge-adoption">0</span>
    </div>
    <div class="nav-item" onclick="navigate(this,'donations')">
      <i class="ti ti-gift"></i> Donations
    </div>
    <div class="nav-item" onclick="navigate(this,'announcements')">
      <i class="ti ti-speakerphone"></i> Announcements
      <span class="nav-badge" id="badge-announcements">0</span>
    </div>
    <div class="nav-section">Operations</div>
    <div class="nav-item" onclick="navigate(this,'rfid')">
      <i class="ti ti-id"></i> RFID Management
    </div>
    <div class="nav-section">Insights</div>
    <div class="nav-item" onclick="navigate(this,'reports')">
      <i class="ti ti-flag"></i> Reports &amp; Feedback
      <span class="nav-badge" id="badge-reports">0</span>
    </div>
  </div>
  <div class="sidebar-footer">
    <button class="logout" id="logout-btn"><i class="ti ti-logout"></i> Logout</button>
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
        <div class="notif-dot" id="notif-dot" style="display:none"></div>
      </div>

      <!-- Notification Dropdown -->
      <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-header">
          <span>Notifications <span id="notif-count-badge" style="background:rgba(218,128,99,0.2);color:#8f2800;font-size:10px;padding:1px 7px;border-radius:10px;margin-left:4px;display:none">0</span></span>
          <button class="notif-mark-read" onclick="markAllRead()">Mark all as read</button>
        </div>
        <div id="notif-list">
          <div class="notif-empty" id="notif-empty">No notifications yet.</div>
        </div>
      </div>

      <div class="avatar" id="admin-avatar" onclick="openAdminPhotoModal()" title="Change profile photo"><?= htmlspecialchars($initials) ?></div>
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
      <div class="stat-card" onclick="navigate(document.querySelector('.nav-item[onclick*=users]'),'users')" style="cursor:pointer">
        <div class="icon ic-blue"><i class="ti ti-users"></i></div>
        <div class="label">Total users</div>
        <div class="value" id="stat-users">0</div>
        <div class="sub up" id="stat-users-sub"><i class="ti ti-trending-up" style="font-size:11px"></i></div>
      </div>
      <div class="stat-card" onclick="navigate(document.querySelector('.nav-item[onclick*=facilities]'),'facilities')" style="cursor:pointer">
        <div class="icon ic-green"><i class="ti ti-building"></i></div>
        <div class="label">Listed facilities</div>
        <div class="value" id="stat-facilities">0</div>
        <div class="sub up"><i class="ti ti-circle-check" style="font-size:11px"></i> Active on map</div>
      </div>
      <div class="stat-card" onclick="navigate(document.querySelector('.nav-item[onclick*=facilities]'),'facilities')" style="cursor:pointer">
        <div class="icon ic-coral"><i class="ti ti-shield-check"></i></div>
        <div class="label">Pending verifications</div>
        <div class="value" id="stat-verifications">0</div>
        <div class="sub warn"><i class="ti ti-clock" style="font-size:11px"></i> Awaiting review</div>
      </div>
      <div class="stat-card" onclick="navigate(document.querySelector('.nav-item[onclick*=reports]'),'reports')" style="cursor:pointer">
        <div class="icon ic-pink"><i class="ti ti-flag"></i></div>
        <div class="label">Open reports</div>
        <div class="value" id="stat-reports">0</div>
        <div class="sub down"><i class="ti ti-alert-circle" style="font-size:11px"></i> Needs attention</div>
      </div>
    </div>
    <div class="stat-grid" style="grid-template-columns:repeat(2,1fr)">
      <div class="stat-card" onclick="navigate(document.querySelector('.nav-item[onclick*=adoption]'),'adoption')" style="cursor:pointer">
        <div class="icon ic-coral"><i class="ti ti-heart-handshake"></i></div>
        <div class="label">Pending adoption requests</div>
        <div class="value" id="stat-adoption">0</div>
        <div class="sub warn"><i class="ti ti-clock" style="font-size:11px"></i> Awaiting admin decision</div>
      </div>
      <div class="stat-card" onclick="navigate(document.querySelector('.nav-item[onclick*=rfid]'),'rfid')" style="cursor:pointer">
        <div class="icon ic-blue"><i class="ti ti-id"></i></div>
        <div class="label">Active RFID tags</div>
        <div class="value" id="stat-rfid-active">0</div>
        <div class="sub up"><i class="ti ti-circle-check" style="font-size:11px"></i> Registered &amp; scannable</div>
      </div>
    </div>
    <div class="two-col">
      <div>
        <div class="panel">
          <div class="panel-head"><span class="title">New user registrations</span><span style="font-size:11px;color:rgba(1,34,36,0.4)">This week</span></div>
          <div class="panel-body">
            <div class="bar-chart" id="signup-bar-chart">
              <div class="bar-wrap"><div class="bar" style="height:8px"></div><div class="bar-label">Mon</div></div>
              <div class="bar-wrap"><div class="bar" style="height:8px"></div><div class="bar-label">Tue</div></div>
              <div class="bar-wrap"><div class="bar" style="height:8px"></div><div class="bar-label">Wed</div></div>
              <div class="bar-wrap"><div class="bar" style="height:8px"></div><div class="bar-label">Thu</div></div>
              <div class="bar-wrap"><div class="bar" style="height:8px"></div><div class="bar-label">Fri</div></div>
              <div class="bar-wrap"><div class="bar" style="height:8px"></div><div class="bar-label">Sat</div></div>
              <div class="bar-wrap"><div class="bar" style="height:8px"></div><div class="bar-label">Sun</div></div>
            </div>
            <div style="font-size:11px;color:rgba(1,34,36,0.4);margin-top:10px">Blue = pet owners &nbsp;·&nbsp; Coral = business owners</div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head"><span class="title">Facility categories</span></div>
          <div class="panel-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
            <div class="cat-tile"><div class="cat-val" id="cat-clinic" style="color:#4E8DC0">0</div><div class="cat-lbl">Clinics</div></div>
            <div class="cat-tile"><div class="cat-val" id="cat-grooming" style="color:#DA8063">0</div><div class="cat-lbl">Grooming</div></div>
            <div class="cat-tile"><div class="cat-val" id="cat-shop" style="color:#B4B156">0</div><div class="cat-lbl">Shops</div></div>
            <div class="cat-tile"><div class="cat-val" id="cat-shelter" style="color:#8a862e">0</div><div class="cat-lbl">Shelters</div></div>
            <div class="cat-tile"><div class="cat-val" id="cat-boarding" style="color:#4E8DC0">0</div><div class="cat-lbl">Boarding</div></div>
            <div class="cat-tile"><div class="cat-val" id="cat-other" style="color:#DA8063">0</div><div class="cat-lbl">Other</div></div>
          </div>
        </div>
      </div>
      <div>
        <div class="panel">
          <div class="panel-head"><span class="title">Recent activity</span></div>
          <div id="recent-activity-list">
            <div class="activity-item"><div class="act-text" style="color:rgba(1,34,36,0.4)">Loading…</div></div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-head"><span class="title">Recent user feedback</span><span class="name-link" onclick="navigate(document.querySelector('.nav-item[onclick*=reports]'),'reports');switchTab(document.querySelectorAll('#page-reports .tab')[1],'tab-feedback')" style="font-size:11px">View all →</span></div>
          <div id="dashboard-feedback"></div>
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
        <option value="clinic">Clinic</option>
        <option value="grooming">Grooming</option>
        <option value="shop">Shop</option>
        <option value="shelter">Shelter</option>
        <option value="boarding">Boarding</option>
        <option value="other">Other</option>
      </select>
      <select class="filter-select" id="facility-status-filter" onchange="filterFacilities()">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <button class="btn btn-primary btn-sm" onclick="openAddFacility()"><i class="ti ti-plus"></i> Add facility</button>
    </div>
    <div class="panel">
      <div class="panel-head"><span class="title">All facilities <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="facility-count">(0)</span></span></div>
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
      <p>Monitor and manage all registered pet owners and vets.</p>
    </div>
    <div class="toolbar">
      <div class="search-box"><i class="ti ti-search"></i><input type="text" id="user-search" placeholder="Search users…" oninput="filterUsers()"></div>
      <select class="filter-select" id="user-role-filter" onchange="filterUsers()">
        <option value="">All roles</option><option>Pet owner</option><option>Vet</option>
      </select>
      <select class="filter-select" id="user-status-filter" onchange="filterUsers()">
        <option value="">All statuses</option><option>Active</option><option>Suspended</option>
      </select>
    </div>
    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-allusers')">All users</div>
      <div class="tab" onclick="switchTab(this,'tab-petowners')">Pet owners</div>
      <div class="tab" onclick="switchTab(this,'tab-bizowners')">Vets</div>
    </div>
    <div id="tab-allusers">
      <div class="panel">
        <div class="panel-head"><span class="title">Registered users <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="user-count">(0)</span></span></div>
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
        <div class="value" id="open-reports-count">0</div>
        <div class="sub down">Needs action</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-coral"><i class="ti ti-message-circle"></i></div>
        <div class="label">Total feedback</div>
        <div class="value" id="stat-feedback-total">0</div>
        <div class="sub">All time</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-green"><i class="ti ti-star"></i></div>
        <div class="label">Avg. satisfaction</div>
        <div class="value" id="stat-feedback-avg">—</div>
        <div class="sub up">Out of 5</div>
      </div>
    </div>
    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-reports')">Reports <span id="reports-tab-badge" style="background:rgba(218,128,99,0.2);color:#8f2800;font-size:10px;padding:1px 7px;border-radius:10px;margin-left:4px">0</span></div>
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
      <div id="reports-feedback-list"></div>
    </div>
  </div>

  <!-- ─── PAGE: ADOPTION REQUESTS ─── -->
  <div class="page" id="page-adoption">
    <div class="page-header">
      <h2>Adoption Requests</h2>
      <p>Review adoption applications submitted by pet owners and approve or deny them on behalf of shelters and rescues.</p>
    </div>
    <div class="toolbar">
      <div class="search-box"><i class="ti ti-search"></i><input type="text" id="adoption-search" placeholder="Search by pet or applicant…" oninput="filterAdoption()"></div>
      <select class="filter-select" id="adoption-status-filter" onchange="filterAdoption()">
        <option value="">All statuses</option><option>Pending</option><option>Approved</option><option>Denied</option>
      </select>
    </div>
  
    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-adoption-requests')">Requests</div>
      <div class="tab" onclick="switchTab(this,'tab-vet-reports')">Vet hand-off reports <span id="vet-reports-badge" class="nav-badge" style="margin-left:4px">0</span></div>
    </div>
    <div id="tab-adoption-requests">
      <div class="panel">
        <div class="panel-head"><span class="title">Adoption applications <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="adoption-count">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Pet</th><th>Shelter / facility</th><th>Applicant</th><th>Date applied</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="adoption-tbody"></tbody>
        </table>
      </div>
    </div>
    <div id="tab-vet-reports" style="display:none">
      <div class="panel">
        <div class="panel-head"><span class="title">Awaiting acknowledgment <span id="vet-reports-count" style="font-weight:400;color:rgba(1,34,36,0.4)">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Animal</th><th>Adopter</th><th>Vet notes</th><th>Reported</th><th>Proof</th><th>Action</th></tr></thead>
          <tbody id="vet-transactions-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ─── PAGE: PRIVATE VET / CLINIC VERIFICATION ─── -->
  <!-- <div class="page" id="page-privatevets">
    <div class="page-header">
      <h2>Private Vet / Clinic Verification</h2>
      <p>Review clinics that self-registered on the Private Vet side before they can browse or request medical cases.</p>
    </div>
    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-pv-pending')">Pending review</div>
      <div class="tab" onclick="switchTab(this,'tab-pv-all')">All clinics</div>
    </div>
    <div id="tab-pv-pending">
      <div class="panel">
        <div class="panel-head"><span class="title">Awaiting verification <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="pv-pending-count">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Clinic</th><th>Vet</th><th>License #</th><th>Contact</th><th>Submitted</th><th>Action</th></tr></thead>
          <tbody id="pv-pending-tbody"></tbody>
        </table>
      </div>
    </div>
    <div id="tab-pv-all" style="display:none">
      <div class="panel">
        <div class="panel-head"><span class="title">All private vets / clinics <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="pv-all-count">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Clinic</th><th>Vet</th><th>Email</th><th>Status</th><th>Reviewed</th></tr></thead>
          <tbody id="pv-all-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

   ─── PAGE: MEDICAL CASE REQUESTS ─── -->
  <!-- <div class="page" id="page-medicalcases">
    <div class="page-header">
      <h2>Medical Case Requests</h2>
      <p>Flag animals under Angeles City Vet Office custody as needing treatment/rehab, then approve or deny requests from verified private vets.</p>
    </div>
    <div class="toolbar">
      <button class="btn btn-primary btn-sm" onclick="openFlagPetModal()"><i class="ti ti-flag"></i> Flag animal as needing treatment</button>
    </div>
    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-mc-pending')">Pending requests</div>
      <div class="tab" onclick="switchTab(this,'tab-mc-all')">All cases</div>
    </div>
    <div id="tab-mc-pending">
      <div class="panel">
        <div class="panel-head"><span class="title">Awaiting decision <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="mc-pending-count">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Animal</th><th>Requesting clinic</th><th>Reason</th><th>Requested</th><th>Action</th></tr></thead>
          <tbody id="mc-pending-tbody"></tbody>
        </table>
      </div>
    </div>
    <div id="tab-mc-all" style="display:none">
      <div class="panel">
        <div class="panel-head"><span class="title">All medical cases <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="mc-all-count">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Animal</th><th>Clinic</th><th>Status</th><th>Reviewed</th></tr></thead>
          <tbody id="mc-all-tbody"></tbody>
        </table>
      </div>
    </div>
  </div> -->

  <!-- ─── PAGE: RFID MANAGEMENT ─── -->
  <div class="page" id="page-rfid">
    <div class="page-header">
      <h2>RFID Management</h2>
      <p>Register RFID microchips, assign them to animals, and track scan history and ownership transfers.</p>
    </div>
    <div class="toolbar">
      <div class="search-box"><i class="ti ti-search"></i><input type="text" id="rfid-search" placeholder="Search by chip ID, pet, or owner…" oninput="filterRFID()"></div>
      <select class="filter-select" id="rfid-status-filter" onchange="filterRFID()">
        <option value="">All statuses</option><option>Active</option><option>Deactivated</option><option>Replaced</option>
      </select>
      <button class="btn btn-primary btn-sm" onclick="openAddRFID()"><i class="ti ti-plus"></i> Register RFID</button>
    </div>
    <div class="panel">
      <div class="panel-head"><span class="title">Registered chips <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="rfid-count">(0)</span></span></div>
      <table class="tbl">
        <thead><tr><th>Chip ID</th><th>Animal</th><th>Owner</th><th>Facility</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>
        <tbody id="rfid-tbody"></tbody>
      </table>
    </div>

    <div class="panel" id="live-scan-panel">
      <div class="panel-head" style="cursor:pointer;user-select:none" onclick="toggleLiveScanPanel()">
        <span class="title" style="color:rgba(1,34,36,0.5);font-weight:500;display:flex;align-items:center;gap:7px">
          <i class="ti ti-broadcast" style="color:rgba(1,34,36,0.35)"></i> Live scanner
        </span>
        <span style="display:flex;align-items:center;gap:10px">
          <span id="live-scan-status" style="font-size:11px;color:rgba(1,34,36,0.4)">Waiting for a scan…</span>
          <span style="font-size:11px;color:rgba(1,34,36,0.35);display:flex;align-items:center;gap:3px">
            <span id="live-scan-toggle-label">Show</span>
            <i class="ti ti-chevron-down" id="live-scan-chevron" style="font-size:14px;transition:transform .15s"></i>
          </span>
        </span>
      </div>
      <div class="panel-body" id="live-scan-body" style="display:none">
        <div style="font-size:12.5px;color:rgba(1,34,36,0.45)">No scans yet. Tap a registered tag on the reader.</div>
      </div>
    </div>
  </div>

  <!-- ─── PAGE: DONATION MANAGEMENT ─── -->
  <div class="page" id="page-donations">
    <div class="page-header">
      <h2>Donation Management</h2>
      <p>Track donations received from users and log how funds were used with proof of purchase.</p>
    </div>

    <div class="stat-grid" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat-card">
        <div class="icon ic-blue"><i class="ti ti-cash"></i></div>
        <div class="label">Total received</div>
        <div class="value" id="stat-donation-received-total">₱0</div>
        <div class="sub up" id="stat-donation-received-sub">From donors</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-green"><i class="ti ti-gift"></i></div>
        <div class="label">Total amount used</div>
        <div class="value" id="stat-donation-total">₱0</div>
        <div class="sub">Logged with proof</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-coral"><i class="ti ti-users"></i></div>
        <div class="label">Donor transactions</div>
        <div class="value" id="stat-donation-received-count">0</div>
        <div class="sub">Completed donations</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-pink"><i class="ti ti-receipt"></i></div>
        <div class="label">Usage entries with proof</div>
        <div class="value" id="stat-donation-proof">0</div>
        <div class="sub">Photos or receipts</div>
      </div>
    </div>

    <div class="tabs">
      <div class="tab active" onclick="switchTab(this,'tab-donations-received')">Donations received</div>
      <div class="tab" onclick="switchTab(this,'tab-donation-usage')">Usage log</div>
    </div>

    <div id="tab-donations-received">
      <div class="toolbar">
        <div class="search-box"><i class="ti ti-search"></i><input type="text" id="received-search" placeholder="Search by donor name or email…" oninput="filterReceivedDonations()"></div>
        <select class="filter-select" id="received-purpose-filter" onchange="filterReceivedDonations()">
          <option value="">All purposes</option>
          <option>Shelter Animals</option>
          <option>Medical Care</option>
          <option>Food &amp; Supplies</option>
          <option>Rescue Operations</option>
          <option>General Fund</option>
        </select>
      </div>
      <div class="panel">
        <div class="panel-head"><span class="title">Donations from users <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="received-count">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Date</th><th>Donor</th><th>Contact</th><th>Purpose</th><th>Amount</th><th>Recurring</th><th>Status</th></tr></thead>
          <tbody id="received-donations-tbody"></tbody>
        </table>
      </div>
    </div>

    <div id="tab-donation-usage" style="display:none">
      <div class="toolbar">
        <div class="search-box"><i class="ti ti-search"></i><input type="text" id="donation-search" placeholder="Search by description…" oninput="filterDonations()"></div>
        <button class="btn btn-primary btn-sm" onclick="openAddDonation()"><i class="ti ti-plus"></i> Log donation usage</button>
      </div>
      <div class="panel">
        <div class="panel-head"><span class="title">Donation usage log <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="donation-count">(0)</span></span></div>
        <table class="tbl">
          <thead><tr><th>Date</th><th>Description</th><th>Amount used</th><th>Proof</th><th>Actions</th></tr></thead>
          <tbody id="donations-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ─── PAGE: COMMUNITY ANNOUNCEMENTS ─── -->
  <div class="page" id="page-announcements">
    <div class="page-header">
      <h2>Community Announcements</h2>
      <p>Review and approve announcements submitted by clinics and facilities — vaccination drives, adoption fairs, and more.</p>
    </div>
    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat-card">
        <div class="icon ic-coral"><i class="ti ti-speakerphone"></i></div>
        <div class="label">Pending review</div>
        <div class="value" id="stat-announce-pending">0</div>
        <div class="sub warn">Awaiting decision</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-green"><i class="ti ti-circle-check"></i></div>
        <div class="label">Approved &amp; live</div>
        <div class="value" id="stat-announce-approved">0</div>
        <div class="sub up">Visible to the community</div>
      </div>
      <div class="stat-card">
        <div class="icon ic-blue"><i class="ti ti-calendar-event"></i></div>
        <div class="label">Total submitted</div>
        <div class="value" id="stat-announce-total">0</div>
        <div class="sub">All time</div>
      </div>
    </div>
    <div class="toolbar">
      <div class="search-box"><i class="ti ti-search"></i><input type="text" id="announcement-search" placeholder="Search by title or facility…" oninput="filterAnnouncements()"></div>
      <select class="filter-select" id="announcement-type-filter" onchange="filterAnnouncements()">
        <option value="">All event types</option>
        <option>Vaccination Drive</option>
        <option>Free Spay/Neuter</option>
        <option>Pet Adoption Fair</option>
        <option>Pet Blessing</option>
        <option>Rabies Awareness Seminar</option>
        <option>Free Veterinary Check-up</option>
        <option>Animal Rescue Event</option>
      </select>
      <select class="filter-select" id="announcement-status-filter" onchange="filterAnnouncements()">
        <!-- <option value="">All statuses</option>
        <option value="pending">Pending review</option>
        <option value="forwarded">Forwarded to vet</option>
        <option value="vet_approved">Vet approved</option>
        <option value="vet_denied">Denied by vet</option>
        <option value="denied">Denied</option>
        <option value="completed">Completed</option> -->
        <option value="">All statuses</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
      <button class="btn btn-primary btn-sm" onclick="openAddAnnouncement()"><i class="ti ti-plus"></i> New announcement</button>
    </div>
    <div class="panel">
      <div class="panel-head"><span class="title">Submitted announcements <span style="font-weight:400;color:rgba(1,34,36,0.4)" id="announcement-count">(0)</span></span></div>
      <table class="tbl">
        <thead><tr><th>Event</th><th>Type</th><th>Facility / clinic</th><th>Event date</th><th>Status</th><th>Action</th></tr></thead>
        <tbody id="announcements-tbody"></tbody>
      </table>
    </div>
  </div>
</div><!-- end .main -->
</div><!-- end .app -->

<!-- ═══════════════ MODALS ═══════════════ -->

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
            <select id="ffm-type">
              <option value="clinic">Clinic</option>
              <option value="grooming">Grooming</option>
              <option value="shop">Shop</option>
              <option value="shelter">Shelter</option>
              <option value="boarding">Boarding</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group"><label>Contact number</label><input type="text" id="ffm-contact" placeholder="+63 9XX XXX XXXX"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Latitude</label><input type="text" id="ffm-lat" placeholder="15.1527"></div>
          <div class="form-group"><label>Longitude</label><input type="text" id="ffm-lng" placeholder="120.5926"></div>
        </div>
        <div class="form-group"><label>Description</label><textarea id="ffm-desc" placeholder="Short description"></textarea></div>
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

<!-- Flag Animal as Needing Treatment Modal -->
<!-- <div class="overlay" id="flagPetModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-flag" style="color:#D97757"></i> Flag animal as needing treatment</h3>
      <button class="modal-close" onclick="closeModal('flagPetModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="form-group">
          <label>Animal</label>
          <select id="fpm-pet-select"><option value="">Loading available animals…</option></select>
        </div>
        <p style="font-size:12px;color:rgba(1,34,36,0.55);margin-top:4px">
          Only animals currently marked <strong>available</strong> can be flagged. Flagging removes the animal
          from the normal adoption pool and makes it visible to verified private vets on the Medical Cases list.
        </p>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('flagPetModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitFlagPet()"><i class="ti ti-flag"></i> Flag animal</button>
    </div>
  </div>
</div> -->

<!-- Private Vet / Clinic Review Modal -->
<!-- <div class="overlay" id="pvReviewModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-stethoscope" style="color:#4E8DC0"></i> <span id="pvm-title">Review clinic</span></h3>
      <button class="modal-close" onclick="closeModal('pvReviewModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="modal-section-title">Clinic details</div>
        <div class="form-group"><label>Clinic / practice name</label><div id="pvm-clinic"></div></div>
        <div class="form-row">
          <div class="form-group"><label>Veterinarian</label><div id="pvm-vet"></div></div>
          <div class="form-group"><label>License #</label><div id="pvm-license"></div></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Email</label><div id="pvm-email"></div></div>
          <div class="form-group"><label>Contact</label><div id="pvm-contact"></div></div>
        </div>
        <div class="form-group"><label>Address</label><div id="pvm-address"></div></div>
        <div class="form-group"><label>Specialization</label><div id="pvm-specialization"></div></div>
        <div class="form-group"><label>License document</label><div id="pvm-license-doc">—</div></div>
      </div>
      <div class="modal-section" id="pvm-reject-section" style="display:none">
        <div class="modal-section-title">Rejection reason</div>
        <div class="form-group"><textarea id="pvm-reject-reason" placeholder="Let the clinic know what needs fixing before they can be verified…"></textarea></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('pvReviewModal')">Close</button>
      <button class="btn btn-ghost" id="pvm-reject-btn" style="color:#a04820" onclick="handlePvRejectClick()">Reject</button>
      <button class="btn btn-primary" id="pvm-verify-btn">Verify clinic</button>
    </div>
  </div>
</div> -->

<!-- Medical Case Review Modal -->
<!-- <div class="overlay" id="mcReviewModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-kit-medical" style="color:#7C8A5C"></i> <span id="mcm-title">Review medical case request</span></h3>
      <button class="modal-close" onclick="closeModal('mcReviewModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="form-row">
          <div class="form-group"><label>Animal</label><div id="mcm-animal"></div></div>
          <div class="form-group"><label>Requesting clinic</label><div id="mcm-clinic"></div></div>
        </div>
        <div class="form-group"><label>Reason for request</label><div id="mcm-reason"></div></div>
        <div class="form-group"><label>Decision notes (optional)</label><textarea id="mcm-notes" placeholder="Any notes for the clinic or your own records…"></textarea></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('mcReviewModal')">Cancel</button>
      <button class="btn btn-ghost" id="mcm-deny-btn" style="color:#a04820">Deny</button>
      <button class="btn btn-primary" id="mcm-approve-btn">Approve</button>
    </div>
  </div>
</div> -->

<!-- Admin profile photo modal -->
<div class="overlay" id="adminSelfPhotoModal">
  <div class="modal" style="max-width:380px">
    <div class="modal-head">
      <h3><i class="ti ti-camera" style="color:#4E8DC0"></i> My profile photo</h3>
      <button class="modal-close" onclick="closeModal('adminSelfPhotoModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section" style="text-align:center">
        <div id="asp-preview" style="display:flex;justify-content:center;margin-bottom:12px"></div>
        <input type="file" id="asp-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('asp-input').click()"><i class="ti ti-upload"></i> Select image</button>
        <button class="btn btn-ghost btn-sm" id="asp-remove" onclick="removeAdminPhoto()" style="color:#a04820"><i class="ti ti-trash"></i> Remove</button>
        <p style="font-size:11px;color:rgba(1,34,36,0.45);margin-top:10px">JPEG, PNG, GIF or WEBP · Max 5 MB</p>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('adminSelfPhotoModal')">Close</button>
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
      <div class="modal-section" style="text-align:center">
        <div id="udm-photo-preview" style="display:flex;justify-content:center;margin-bottom:10px"></div>
      </div>
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
      <div class="modal-section" id="rdm-evidence-section" style="display:none">
        <div class="modal-section-title">Evidence photos</div>
        <div class="evidence-gallery" id="rdm-evidence"></div>
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

<div class="logout-overlay" id="logout-overlay">
  <div class="logout-card">
    <div class="mark">⏻</div>
    <h3>Log out of PawConnect?</h3>
    <p>You'll need to sign back in to access the admin portal.</p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button class="btn-mini cancel" id="logout-cancel" style="padding:9px 16px;">Cancel</button>
      <button class="btn-prim" id="logout-confirm" style="padding:9px 16px;">Log out</button>
    </div>
  </div>
</div>

<!-- Adoption Detail Modal -->
<div class="overlay" id="adoptionDetailModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-heart-handshake" style="color:#DA8063"></i> Adoption application</h3>
      <button class="modal-close" onclick="closeModal('adoptionDetailModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="modal-section-title">Pet information</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-paw"></i> Name</div><div class="info-val" id="adm-pet"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-dog"></i> Species / breed</div><div class="info-val" id="adm-breed"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-building"></i> Shelter / facility</div><div class="info-val" id="adm-shelter"></div></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Applicant</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-user"></i> Name</div><div class="info-val" id="adm-applicant"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-mail"></i> Email</div><div class="info-val" id="adm-email"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-calendar"></i> Date applied</div><div class="info-val" id="adm-date"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-info-circle"></i> Status</div><div class="info-val" id="adm-status"></div></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Applicant notes</div>
        <div style="font-size:13px;color:rgba(1,34,36,0.7);line-height:1.65" id="adm-notes"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('adoptionDetailModal')">Close</button>
      <button class="btn btn-reject" id="adm-deny-btn"><i class="ti ti-x"></i> Deny</button>
      <button class="btn btn-approve" id="adm-approve-btn"><i class="ti ti-check"></i> Approve adoption</button>
    </div>
  </div>
</div>

<!-- Adoption Verification / Transaction Completion Modal -->
<div class="overlay" id="adoptionVerifyModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-shield-check" style="color:#4E8DC0"></i> Verify &amp; complete transaction</h3>
      <button class="modal-close" onclick="closeModal('adoptionVerifyModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="avm-index" value="-1">
      <div class="note-banner"><i class="ti ti-info-circle"></i> This proof was submitted by the vet after they approved and completed the hand-off. Review it, optionally attach your own confirmation, then mark it complete. The applicant is notified automatically.</div>
      <div class="modal-section">
        <div class="modal-section-title">Transaction summary</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-paw"></i> Pet</div><div class="info-val" id="avm-pet"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-user"></i> Applicant</div><div class="info-val" id="avm-applicant"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-building"></i> Facility</div><div class="info-val" id="avm-facility"></div></div>
      </div>
      <div class="modal-section" id="avm-evidence-section" style="display:none">
        <div class="modal-section-title">Proof submitted by facility</div>
        <div class="evidence-gallery" id="avm-evidence"></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Attach handover proof (optional)</div>
        <div class="form-group"><label>Photo of adopter with pet, signed release form, etc.</label><input type="file" id="avm-files" accept="image/*" multiple></div>
        <div id="avm-preview" class="evidence-gallery" style="margin-top:10px"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('adoptionVerifyModal')">Cancel</button>
      <button class="btn btn-approve" onclick="confirmAdoptionVerify()"><i class="ti ti-shield-check"></i> Verify, complete &amp; notify applicant</button>
    </div>
  </div>
</div>

<!-- RFID Register/Edit Modal -->
<div class="overlay" id="rfidFormModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-id" style="color:#4E8DC0"></i> <span id="rfm-title">Register RFID</span></h3>
      <button class="modal-close" onclick="closeModal('rfidFormModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rfm-index" value="-1">
      <div class="modal-section">
        <div class="modal-section-title">Chip details</div>
        <div class="form-group"><label>RFID chip ID</label><input type="text" id="rfm-chip" placeholder="e.g. PH-985112000123456"></div>
        <div class="form-group"><label>Animal</label>
          <select id="rfm-pet"><option value="">Select an animal…</option></select>
        </div>
        <div class="form-group"><label>Facility on record</label>
          <select id="rfm-facility"></select>
        </div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Medical information</div>
        <div class="form-row">
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:6px;font-weight:500">
              <input type="checkbox" id="rfm-vaccinated" style="width:auto"> Vaccinated
            </label>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:6px;font-weight:500">
              <input type="checkbox" id="rfm-dewormed" style="width:auto"> Dewormed
            </label>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:6px;font-weight:500">
            <input type="checkbox" id="rfm-neutered" style="width:auto"> Neutered / Spayed
          </label>
        </div>
        <div class="form-group"><label>Health status</label><input type="text" id="rfm-health-status" placeholder="e.g. Healthy, Under treatment"></div>
        <div class="form-group"><label>Medical notes</label><textarea id="rfm-medical-notes" placeholder="Allergies, medications, conditions, etc."></textarea></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('rfidFormModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveRFID()"><i class="ti ti-device-floppy"></i> Save chip record</button>
    </div>
  </div>
</div>

<!-- RFID Scan History Modal -->
<div class="overlay" id="rfidHistoryModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-history" style="color:#4E8DC0"></i> <span id="rhm-title">Scan history</span></h3>
      <button class="modal-close" onclick="closeModal('rfidHistoryModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section" style="margin-bottom:14px">
        <div class="rfid-chip" id="rhm-chip"><i class="ti ti-id"></i> <span></span></div>
      </div>
      <div class="modal-section-title">Scan log</div>
      <div id="rhm-list"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('rfidHistoryModal')">Close</button>
    </div>
  </div>
</div>

<!-- RFID Transfer Ownership Modal -->
<div class="overlay" id="rfidTransferModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-transfer" style="color:#4E8DC0"></i> Transfer ownership</h3>
      <button class="modal-close" onclick="closeModal('rfidTransferModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rtm-index" value="-1">
      <div class="note-banner"><i class="ti ti-info-circle"></i> This will update the registered owner for this RFID tag and log the change in the scan history.</div>
      <div class="form-group"><label>Current owner</label><input type="text" id="rtm-current-owner" disabled></div>
      <div class="form-group"><label>New owner</label><input type="text" id="rtm-new-owner"></input></div>
      <div class="form-group"><label>Reason for transfer</label><textarea id="rtm-reason" placeholder="e.g. Adoption finalized, resale, surrender to new guardian…"></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('rfidTransferModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitRFIDTransfer()"><i class="ti ti-transfer"></i> Confirm transfer</button>
    </div>
  </div>
</div>

<!-- RFID Replace Modal -->
<div class="overlay" id="rfidReplaceModal">
  <div class="modal confirm-modal">
    <div class="modal-head">
      <h3><i class="ti ti-refresh" style="color:#4E8DC0"></i> Replace RFID chip</h3>
      <button class="modal-close" onclick="closeModal('rfidReplaceModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rrm-index" value="-1">
      <div class="form-group"><label>New chip ID</label><input type="text" id="rrm-new-chip" placeholder="e.g. PH-985112000999888"></div>
      <p style="font-size:12px;color:rgba(1,34,36,0.5)">The old chip will be marked <strong>Replaced</strong> and a new record will be created carrying over the animal and owner details.</p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('rfidReplaceModal')">Cancel</button>
      <button class="btn btn-primary" onclick="confirmRFIDReplace()"><i class="ti ti-refresh"></i> Replace chip</button>
    </div>
  </div>
</div>

<!-- Deactivate RFID Confirm Modal -->
<div class="overlay" id="rfidDeactivateModal">
  <div class="modal confirm-modal">
    <div class="modal-head">
      <h3><i class="ti ti-power" style="color:#DA8063"></i> <span id="rdc-title">Deactivate chip</span></h3>
      <button class="modal-close" onclick="closeModal('rfidDeactivateModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="confirm-body">
      <i class="ti ti-alert-triangle" style="color:#DA8063"></i>
      <h4 id="rdc-heading">Deactivate this RFID tag?</h4>
      <p id="rdc-text">Scans against this chip will no longer resolve to an animal profile.</p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('rfidDeactivateModal')">Cancel</button>
      <button class="btn btn-danger" onclick="confirmToggleRFID()"><i class="ti ti-power"></i> Confirm</button>
    </div>
  </div>
</div>

<!-- Donation Add/Edit Modal -->
<div class="overlay" id="donationFormModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-gift" style="color:#4E8DC0"></i> <span id="dfm-title">Log donation usage</span></h3>
      <button class="modal-close" onclick="closeModal('donationFormModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="dfm-index" value="-1">
      <div class="modal-section">
        <div class="modal-section-title">Usage details</div>
        <div class="form-row">
          <div class="form-group"><label>Date used</label><input type="date" id="dfm-date"></div>
          <div class="form-group"><label>Amount used (₱)</label><input type="number" id="dfm-amount" placeholder="e.g. 5000" min="0" step="0.01"></div>
        </div>
        <div class="form-group"><label>Description</label><textarea id="dfm-desc" placeholder="What was this donation used for? e.g. Purchased dog food and dewormer for Happy Paws Shelter"></textarea></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Proof of purchase</div>
        <div class="note-banner"><i class="ti ti-info-circle"></i> Upload photos of receipts or proof of purchase. Files stay attached to this entry as transparency evidence.</div>
        <div class="form-group"><label>Receipt / photo files</label><input type="file" id="dfm-files" accept="image/*" multiple></div>
        <div id="dfm-preview" class="evidence-gallery" style="margin-top:10px"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('donationFormModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveDonation()"><i class="ti ti-device-floppy"></i> Save entry</button>
    </div>
  </div>
</div>

<!-- Donation Detail Modal -->
<div class="overlay" id="donationDetailModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-gift" style="color:#4E8DC0"></i> Donation usage detail</h3>
      <button class="modal-close" onclick="closeModal('donationDetailModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="info-row"><div class="info-label"><i class="ti ti-calendar"></i> Date used</div><div class="info-val" id="ddm-date"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-cash"></i> Amount used</div><div class="info-val" id="ddm-amount"></div></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Description</div>
        <div style="font-size:13px;color:rgba(1,34,36,0.7);line-height:1.65" id="ddm-desc"></div>
      </div>
      <div class="modal-section" id="ddm-evidence-section" style="display:none">
        <div class="modal-section-title">Proof of purchase</div>
        <div class="evidence-gallery" id="ddm-evidence"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('donationDetailModal')">Close</button>
      <button class="btn btn-danger btn-sm" onclick="deleteDonation()"><i class="ti ti-trash"></i> Delete entry</button>
    </div>
  </div>
</div>

<!-- Announcement Add/Edit Modal -->
<div class="overlay" id="announcementFormModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-speakerphone" style="color:#4E8DC0"></i> <span id="afm-title">New announcement</span></h3>
      <button class="modal-close" onclick="closeModal('announcementFormModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="afm-index" value="-1">
      <div class="modal-section">
        <div class="modal-section-title">Event details</div>
        <div class="form-group"><label>Event title</label><input type="text" id="afm-title-input" placeholder="e.g. Free Rabies Vaccination Day"></div>
        <div class="form-row">
          <div class="form-group"><label>Event type</label>
            <select id="afm-type">
              <option>Vaccination Drive</option>
              <option>Free Spay/Neuter</option>
              <option>Pet Adoption Fair</option>
              <option>Pet Blessing</option>
              <option>Rabies Awareness Seminar</option>
              <option>Free Veterinary Check-up</option>
              <option>Animal Rescue Event</option>
            </select>
          </div>
          <div class="form-group"><label>Event date</label><input type="date" id="afm-date"></div>
        </div>
        <div class="form-group"><label>Facility / clinic</label>
          <select id="afm-facility"></select>
        </div>
        <div class="form-group"><label>Location</label><input type="text" id="afm-location" placeholder="e.g. Barangay Plaza, Marikina"></div>
        <div class="form-group"><label>Description</label><textarea id="afm-desc" placeholder="Details about the event"></textarea></div>
        <div class="form-group"><label>Status</label>
          <select id="afm-status"><option value="Pending">Pending</option><option value="Approved">Approved</option><option value="Rejected">Rejected</option></select>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('announcementFormModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveAnnouncement()"><i class="ti ti-device-floppy"></i> Save announcement</button>
    </div>
  </div>
</div>

<!-- Announcement Detail / Review Modal -->
<div class="overlay" id="announcementDetailModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-speakerphone" style="color:#DA8063"></i> Announcement review</h3>
      <button class="modal-close" onclick="closeModal('announcementDetailModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-section">
        <div class="modal-section-title">Event information</div>
        <div class="info-row"><div class="info-label"><i class="ti ti-calendar-event"></i> Title</div><div class="info-val" id="adnm-title"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-category"></i> Type</div><div class="info-val" id="adnm-type"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-building"></i> Facility / clinic</div><div class="info-val" id="adnm-facility"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-calendar"></i> Event date</div><div class="info-val" id="adnm-date"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-map-pin"></i> Location</div><div class="info-val" id="adnm-location"></div></div>
        <div class="info-row"><div class="info-label"><i class="ti ti-info-circle"></i> Status</div><div class="info-val" id="adnm-status"></div></div>
      </div>
      <div class="modal-section">
        <div class="modal-section-title">Description</div>
        <div style="font-size:13px;color:rgba(1,34,36,0.7);line-height:1.65" id="adnm-desc"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" id="adnm-edit-btn" onclick="editAnnouncementFromDetail()"><i class="ti ti-edit"></i> Edit</button>
      <button class="btn btn-reject" id="adnm-reject-btn" onclick="setAnnouncementStatus('Rejected')"><i class="ti ti-x"></i> Reject</button>
      <button class="btn btn-approve" id="adnm-approve-btn" onclick="setAnnouncementStatus('Approved')"><i class="ti ti-check"></i> Approve</button>
    </div>
  </div>
</div>

<!-- Live RFID Scan Modal -->
<div class="overlay" id="rfidScanModal">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="ti ti-id" style="color:#4E8DC0"></i> Tag scanned</h3>
      <button class="modal-close" onclick="closeModal('rfidScanModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body" id="rfid-scan-modal-body"></div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('rfidScanModal')">Close</button>
      <button class="btn btn-primary" id="rsm-edit-btn"><i class="ti ti-edit"></i> Open record</button>
    </div>
  </div>
</div>

<!-- Image Lightbox -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox(event)">
  <button class="lightbox-close" onclick="closeLightbox(event)"><i class="ti ti-x"></i></button>
  <img id="lightbox-img" src="" alt="Evidence photo">
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <i class="ti ti-circle-check"></i>
  <span id="toast-msg">Action completed.</span>
</div>

<script>
//  DATA STORE
let facilities = [];

function loadFacilities() {
    fetch('admin_facility.php?action=list')
        .then(r => r.json())
        .then(data => {
          if (!data.success) { showToast('Failed to load facilities.', 'error'); return; }
          facilities = data.facilities;
          renderFacilities(facilities.filter(f => f.status !== 'pending'));
          renderVerifications();
          updateFacilityStats();
          buildRecentActivity();
        })
        .catch(() => showToast('Could not reach the server to load facilities.', 'error'));
}

function renderVerifications() {
    const pending = facilities.filter(f => f.status === 'pending');
    const tbody = document.getElementById('verif-tbody');
    if (!tbody) return;
    tbody.innerHTML = pending.map(f => `
        <tr class="clickable" onclick="openVerification(${f.id})">
            <td><strong>${esc(f.name)}</strong></td>
            <td>${esc(f.type)}</td>
            <td>${esc(f.submitted_by || '—')}</td>
            <td>${esc(f.submitted_at)}</td>
            <td><span class="badge pending">Pending</span></td>
            <td><span style="font-size:12px;color:#4E8DC0;font-weight:500">Review →</span></td>
        </tr>`).join('');
    document.getElementById('verif-count').textContent = `(${pending.length})`;
    document.getElementById('badge-verification').textContent = pending.length;
    document.getElementById('stat-verifications').textContent = pending.length;
}

function openVerification(id) {
    const f = facilities.find(x => x.id === id);
    currentVerifIndex = id;
    document.getElementById('vm-title').textContent     = f.name + ' — Review';
    document.getElementById('vm-name').textContent      = f.name;
    document.getElementById('vm-address').textContent   = f.address;
    document.getElementById('vm-hours').textContent     = f.opening_hours;
    document.getElementById('vm-contact').textContent   = f.contact;
    document.getElementById('vm-desc').textContent      = f.description;
    document.getElementById('vm-submitter').textContent = f.submitted_by || '—';
    document.getElementById('vm-date').textContent      = f.submitted_at;
    document.getElementById('vm-approve-btn').onclick = () => setFacilityStatus(id, 'active');
    document.getElementById('vm-reject-btn').onclick  = () => setFacilityStatus(id, 'rejected');
    openModal('verificationModal');
}

function setFacilityStatus(id, status) {
    fetch('admin_facility.php?action=set_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status }),
    })
    .then(r => r.json())
    .then(() => {
        closeModal('verificationModal');
        showToast(`Facility ${status === 'active' ? 'approved' : 'rejected'}.`);
        loadFacilities();
    });
}

function saveFacility() {
    const idx  = parseInt(document.getElementById('ffm-index').value);
    const name = document.getElementById('ffm-name').value.trim();
    if (!name) { showToast('Please enter a facility name.', 'error'); return; }

    const payload = {
        id: idx === -1 ? undefined : facilities[idx].id,
        name,
        type:          document.getElementById('ffm-type').value,
        contact:       document.getElementById('ffm-contact').value.trim(),
        address:       document.getElementById('ffm-address').value.trim(),
        opening_hours: document.getElementById('ffm-hours').value.trim(),
        description:   document.getElementById('ffm-desc').value.trim(),
        latitude:      parseFloat(document.getElementById('ffm-lat').value) || 0,
        longitude:     parseFloat(document.getElementById('ffm-lng').value) || 0,
        status:        document.getElementById('ffm-status').value.toLowerCase(),
    };

    fetch('admin_facility.php?action=' + (idx === -1 ? 'create' : 'update'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { showToast('Could not save facility.', 'error'); return; }
        closeModal('facilityFormModal');
        showToast(`"${name}" saved successfully.`);
        loadFacilities();
    });
}

function updateFacilityStats() {
  const listed  = facilities.filter(f => f.status !== 'pending');
  const pending = facilities.filter(f => f.status === 'pending');
  const statFac = document.getElementById('stat-facilities');
  if (statFac) statFac.textContent = listed.length;
  const statVerif = document.getElementById('stat-verifications');
  if (statVerif) statVerif.textContent = pending.length;

  const counts = { clinic: 0, grooming: 0, shop: 0, shelter: 0, boarding: 0, other: 0 };
  facilities.forEach(f => {
    const t = (f.type || '').toLowerCase();
    if (counts.hasOwnProperty(t)) counts[t]++;
    else counts.other++;
  });
  const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  setTxt('cat-clinic', counts.clinic);
  setTxt('cat-grooming', counts.grooming);
  setTxt('cat-shop', counts.shop);
  setTxt('cat-shelter', counts.shelter);
  setTxt('cat-boarding', counts.boarding);
  setTxt('cat-other', counts.other);
}

function updateUserChart() {
  const now = new Date();
  const dow = now.getDay(); // 0=Sun..6=Sat
  const monday = new Date(now);
  monday.setDate(now.getDate() + (dow === 0 ? -6 : 1 - dow));
  monday.setHours(0, 0, 0, 0);

  const counts = Array.from({ length: 7 }, () => ({ owner: 0, biz: 0 }));
  users.forEach(u => {
    if (!u.createdAtRaw) return;
    const d = new Date(u.createdAtRaw.replace(' ', 'T'));
    if (isNaN(d)) return;
    const diffDays = Math.floor((d - monday) / 86400000);
    if (diffDays < 0 || diffDays > 6) return;
    if (u.role === 'Vet') counts[diffDays].biz++;
    else counts[diffDays].owner++;
  });

  const max = Math.max(1, ...counts.map(c => c.owner + c.biz));
  const wraps = document.querySelectorAll('#signup-bar-chart .bar-wrap');
  wraps.forEach((wrap, i) => {
    const total = counts[i].owner + counts[i].biz;
    const bar = wrap.querySelector('.bar');
    bar.style.height = (total ? Math.max(8, Math.round((total / max) * 70)) : 4) + 'px';
    bar.className = 'bar' + (counts[i].biz >= counts[i].owner && counts[i].biz > 0 ? ' coral' : '');
    bar.title = `${total} registration${total === 1 ? '' : 's'}`;
  });
}

function timeAgo(date) {
  const s = Math.floor((Date.now() - date.getTime()) / 1000);
  if (s < 60) return 'Just now';
  const m = Math.floor(s / 60); if (m < 60) return `${m} minute${m === 1 ? '' : 's'} ago`;
  const h = Math.floor(m / 60); if (h < 24) return `${h} hour${h === 1 ? '' : 's'} ago`;
  const d = Math.floor(h / 24); if (d < 7) return `${d} day${d === 1 ? '' : 's'} ago`;
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function buildRecentActivity() {
  const container = document.getElementById('recent-activity-list');
  if (!container) return;
  const events = [];
  facilities.forEach(f => {
    if (f.submitted_at) {
      const d = new Date(f.submitted_at.replace(' ', 'T'));
      if (!isNaN(d)) events.push({ date: d, icon: 'act-coral', i: 'ti-building', text: `Facility submitted for review: <strong>${esc(f.name)}</strong>` });
    }
  });
  users.forEach(u => {
    if (u.createdAtRaw) {
      const d = new Date(u.createdAtRaw.replace(' ', 'T'));
      if (!isNaN(d)) events.push({ date: d, icon: 'act-blue', i: 'ti-user-plus', text: `New user registered: <strong>${esc(u.name)}</strong> (${esc(u.role)})` });
    }
  });
  // Completed adoptions (hand-off verified)
  if (typeof adoptionRequests !== 'undefined') {
    adoptionRequests.forEach(a => {
      if (a.status !== 'completed') return;
      const raw = a.verified_at || a.vet_decided_at || a.created_at;
      const d = raw ? new Date(String(raw).replace(' ', 'T')) : null;
      if (d && !isNaN(d)) events.push({ date: d, icon: 'act-pink', i: 'ti-heart-handshake', text: `<strong>${esc(a.pet_name || 'A pet')}</strong> was successfully adopted by <strong>${esc(a.applicant_name || 'an adopter')}</strong>` });
    });
  }
  // RFID tags: registered, and edited (needs rfid_tags.updated_at, see rfid_updated_at.sql)
  if (typeof rfidRecords !== 'undefined') {
    rfidRecords.forEach(r => {
      const label = `<strong>${esc(r.chip_uid)}</strong>${r.pet_name ? ` (${esc(r.pet_name)})` : ''}`;
      const reg = r.registered_at ? new Date(String(r.registered_at).replace(' ', 'T')) : null;
      const upd = r.updated_at ? new Date(String(r.updated_at).replace(' ', 'T')) : null;
      if (reg && !isNaN(reg)) events.push({ date: reg, icon: 'act-blue', i: 'ti-id', text: `RFID tag registered: ${label}` });
      if (upd && !isNaN(upd) && (!reg || Math.abs(upd - reg) > 2000)) {
        events.push({ date: upd, icon: 'act-blue', i: 'ti-edit', text: `RFID tag updated: ${label}` });
      }
    });
  }
  events.sort((a, b) => b.date - a.date);
  const top = events.slice(0, 8);
  container.innerHTML = top.length
    ? top.map(e => `
      <div class="activity-item">
        <div class="act-icon ${e.icon}"><i class="ti ${e.i}"></i></div>
        <div><div class="act-text">${e.text}</div><div class="act-time">${timeAgo(e.date)}</div></div>
      </div>`).join('')
    : '<div class="activity-item"><div class="act-text" style="color:rgba(1,34,36,0.4)">No recent activity yet.</div></div>';
}

function deleteFacility(idx) {
    deleteTargetIndex = idx;
    document.getElementById('delete-confirm-text').textContent = `"${facilities[idx].name}" will be permanently removed.`;
    openModal('deleteConfirmModal');
}

function confirmDelete() {
    const f = facilities[deleteTargetIndex];
    fetch('admin_facility.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: f.id }),
    })
    .then(r => r.json())
    .then(() => {
        closeModal('deleteConfirmModal');
        showToast(`"${f.name}" has been deleted.`);
        loadFacilities();
    });
}

let users = [];

const AVATAR_PALETTE = ['#4E8DC0', '#DA8063', '#B4B156', '#8a862e', '#F6C5B4', '#012224'];
function avatarColorFor(id) { return AVATAR_PALETTE[Math.abs(id) % AVATAR_PALETTE.length]; }
function initialsFor(name) { return (name || '?').trim().split(/\s+/).map(p => p[0]).join('').slice(0,2).toUpperCase(); }
function mapRole(role) {
  const map = { pet_owner: 'Pet owner', business_owner: 'Vet', vet: 'Vet', admin: 'Admin' };
  return map[role] || (role || 'Unassigned');
}

function photoUrl(path) {
  if (!path) return null;
  if (/^(https?:|data:|\/\/)/.test(path)) return path;
  // Saved paths are root-based, e.g. "/pawconnect/php/uploads/profile_photos/5.jpg?v=1".
  // Older rows were saved without the project folder ("/php/uploads/..."). This page sits
  // one folder below the project root, so work out that root from the page's own URL
  // ("/pawconnect/admin/x.php" -> "/pawconnect") and add it only if it's missing.
  const root = location.pathname.replace(/\/[^\/]*\/[^\/]*$/, '');
  let p = path.charAt(0) === '/' ? path : '/' + path.replace(/^(\.\.\/)+/, '');
  if (root && p.indexOf(root + '/') !== 0) p = root + p;
  return p;
}

function avatarFallback(img, color, size, initials) {
  const d = document.createElement('div');
  d.className = 'user-ava';
  d.style.background = color;
  d.style.width = size + 'px';
  d.style.height = size + 'px';
  d.textContent = initials;
  img.replaceWith(d);
}

function avatarHtml(u, size = 30) {
  const src = photoUrl(u.profile_image);
  if (src) {
    return `<img src="${esc(src)}" alt="${esc(u.name)}"
      style="width:${size}px;height:${size}px;border-radius:50%;object-fit:cover;flex-shrink:0"
      onerror="avatarFallback(this,'${u.ava}',${size},'${esc(u.initials)}')">`;
  }
  return `<div class="user-ava" style="background:${u.ava};width:${size}px;height:${size}px">${u.initials}</div>`;
}

// Resolve a display name to a real account row (null if there's no match).
function findUserByName(name) {
  return users.find(u => u.name === name) || null;
}

// Avatar + clickable name that opens the account modal.
function userChipHtml(name, size = 28, userId = null) {
  const u = (userId != null && users.find(x => x.id === userId)) || findUserByName(name);
  if (!u) return `<span style="color:rgba(1,34,36,0.45)">${esc(name)}</span>`;
  const idx = users.indexOf(u);
  return `<div class="profile-link" onclick="openUserDetail(${idx})">
    ${avatarHtml(u, size)}<strong>${esc(u.name)}</strong></div>`;
}

function loadUsers() {
  fetch('admin_users.php?action=list')
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast('Failed to load users.', 'error'); return; }
      users = data.users.map(u => ({
        id: parseInt(u.id, 10),
        name: u.name || u.username,
        username: u.username,
        email: u.email,
        contact: u.mobile || '—',
        address: '—', // not tracked in the users table
        role: mapRole(u.role),
        joined: u.created_at ? formatDateDisplay(u.created_at.slice(0, 10)) : '—',
        status: u.status === 'suspended' ? 'Suspended' : 'Active',
        profile_image: u.profile_image || null,
        initials: initialsFor(u.name || u.username),
        ava: avatarColorFor(parseInt(u.id, 10)),
        createdAtRaw: u.created_at,
      }));
      updateUserChart();
      buildRecentActivity();
      const now = new Date();
      const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
      const loggedInThisMonth = data.users.filter(u => u.last_login && new Date(u.last_login) >= monthStart).length;

      const subEl = document.getElementById('stat-users-sub');

if (subEl) {
  subEl.className = loggedInThisMonth > 0 ? 'sub up' : 'sub';
  subEl.innerHTML = `<i class="ti ti-trending-up" style="font-size:11px"></i> ${loggedInThisMonth} logged in this month`;
}
      renderUsers(users);
      renderFeedback();
      populateRFIDDropdowns();
      const statEl = document.getElementById('stat-users');
      if (statEl) statEl.textContent = users.length.toLocaleString();
    })
    .catch(() => showToast('Could not reach the server for users.', 'error'));
}

let verifications = [];
let reports = [];

function loadReports() {
    fetch('admin_reports.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast('Failed to load reports.', 'error'); return; }
            reports = data.reports.map(r => ({
                id: r.id,
                entity: r.entity_name,
                reporter: r.reporter_name || r.reporter_username,
                reason: r.reason,
                date: r.created_at,
                status: r.status.charAt(0).toUpperCase() + r.status.slice(1), // Open/Resolved/Dismissed
                desc: r.description,
                images: r.images || [],
            }));
            renderReports();
        })
        .catch(() => showToast('Could not reach the server for reports.', 'error'));
}

let feedbackList = [];

function loadFeedback() {
    fetch('admin_feedback.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast('Failed to load feedback.', 'error'); return; }
            feedbackList = data.feedback.map(f => ({
                id: f.id,
                userId: parseInt(f.user_id, 10),
                name: f.user_name || f.username,
                date: f.created_at,
                rating: parseInt(f.rating, 10),
                text: f.comment || '(no comment)',
                petName: f.pet_name,
                tag: f.status.charAt(0).toUpperCase() + f.status.slice(1),
                tagClass: f.status === 'new' ? 'pending' : f.status === 'reviewed' ? 'active' : 'inactive',
                statusRaw: f.status,
                targetType: f.target_type || 'app',
                facilityName: f.facility_name,
            }));
            renderFeedback();
        })
        .catch(() => showToast('Could not reach the server for feedback.', 'error'));
}

function setFeedbackStatus(id, status) {
    fetch('admin_feedback.php?action=set_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showToast('Updated.'); loadFeedback(); }
        else showToast(data.message || 'Could not update.', 'error');
    })
    .catch(() => showToast('Could not reach the server.', 'error'));
}

let adoptionRequests = [];

function loadAdoption() {
    fetch('admin_adoption.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast('Failed to load adoption requests.', 'error'); return; }
            adoptionRequests = data.requests.map(a => ({ ...a, id: parseInt(a.id, 10) }));
            filterAdoption();
            buildRecentActivity();
        })
        .catch(err => { console.error(err); showToast('Could not reach the server.', 'error'); });
}

function filterAdoption() {
  const q      = (document.getElementById('adoption-search')?.value || '').toLowerCase();
  const status = document.getElementById('adoption-status-filter')?.value || '';
  const list = adoptionRequests.filter(a =>
    (!q || (a.pet_name||'').toLowerCase().includes(q) || (a.applicant_name||'').toLowerCase().includes(q)) &&
    (!status || a.status.toLowerCase() === status.toLowerCase())
  );
  const tbody = document.getElementById('adoption-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No adoption requests found.</td></tr>';
  }
  const statusMeta = {
    pending:      { cls: 'pending',  label: 'Pending review' },
    forwarded:    { cls: 'pending',  label: 'Forwarded to vet' },
    vet_approved: { cls: 'approved', label: 'Vet approved — awaiting hand-off' },
    vet_denied:   { cls: 'rejected', label: 'Denied by vet' },
    denied:       { cls: 'rejected', label: 'Denied' },
    completed:    { cls: 'approved', label: 'Completed' },
  };
  list.forEach(a => {
    const meta = statusMeta[a.status] || { cls: 'pending', label: a.status };
    tbody.innerHTML += `
      <tr class="clickable" onclick="openAdoptionDetail(${a.id})">
        <td><div style="display:flex;align-items:center;gap:9px"><div class="pet-ava"><i class="ti ti-paw"></i></div><div><strong>${esc(a.pet_name || 'Unknown')}</strong><div style="font-size:11px;color:rgba(1,34,36,0.45)">${esc(a.pet_type || '')} · ${esc(a.pet_breed || '')}</div></div></div></td>
        <td>${esc(a.pet_shelter || '—')}</td>
        <td><span class="name-link" onclick="event.stopPropagation();openUserByName('${esc(a.applicant_name).replace(/'/g,"\\'")}')">${esc(a.applicant_name)}</span></td>
        <td>${esc(a.visit_date)}</td>
        <td><span class="badge ${meta.cls}">${esc(meta.label)}</span></td>
        <td><span style="font-size:12px;color:#4E8DC0;font-weight:500">${a.status === 'pending' ? 'Review →' : 'View →'}</span></td>
      </tr>`;
  });
  document.getElementById('adoption-count').textContent = `(${list.length})`;
  const pending = adoptionRequests.filter(a => a.status === 'pending').length;
  const awaitingHandoff = adoptionRequests.filter(a => a.status === 'vet_approved').length;
  document.getElementById('badge-adoption').textContent = pending + awaitingHandoff + (vetTransactions ? vetTransactions.length : 0);
  const statEl = document.getElementById('stat-adoption');
  if (statEl) statEl.textContent = pending;
}

function openAdoptionDetail(id) {
  const a = adoptionRequests.find(x => x.id === id);
  if (!a) return;
  currentAdoptionIndex = id;
  document.getElementById('adm-pet').textContent = a.pet_name || 'Unknown';
  document.getElementById('adm-breed').textContent = `${a.pet_type || ''} — ${a.pet_breed || ''}`;
  document.getElementById('adm-shelter').textContent = a.pet_shelter || '—';
  document.getElementById('adm-applicant').innerHTML = `<span class="name-link" onclick="closeModal('adoptionDetailModal');openUserByName('${esc(a.applicant_name).replace(/'/g,"\\'")}')">${esc(a.applicant_name)}</span>`;
  document.getElementById('adm-email').textContent = a.applicant_email || a.applicant_contact || '—';
  document.getElementById('adm-date').textContent = a.visit_date;

  const statusLabels = {
    pending: 'Pending review', forwarded: 'Forwarded to vet — awaiting their decision',
    vet_approved: 'Vet approved — awaiting hand-off', vet_denied: 'Denied by vet',
    denied: 'Denied', completed: 'Completed'
  };
  document.getElementById('adm-status').textContent = statusLabels[a.status] || a.status;
  document.getElementById('adm-notes').textContent = a.notes || '—';

  const approveBtn = document.getElementById('adm-approve-btn');
  const denyBtn    = document.getElementById('adm-deny-btn');

  if (a.status === 'pending') {
    approveBtn.style.display = '';
    denyBtn.style.display = '';
    approveBtn.innerHTML = '<i class="ti ti-send"></i> Forward to vet';
    approveBtn.className = 'btn btn-primary';
    approveBtn.onclick = () => forwardToVet(id);
    denyBtn.onclick = () => setAdoptionStatus(id, 'deny');
  } else {
    approveBtn.style.display = 'none';
    denyBtn.style.display = 'none';
  }
  openModal('adoptionDetailModal');
}

function forwardToVet(id) {
  fetch('admin_adoption.php?action=forward', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { showToast(data.message || 'Could not forward request.', 'error'); return; }
    closeModal('adoptionDetailModal');
    showToast('Request forwarded to the vet for review.');
    loadAdoption();
  });
}






function setAdoptionStatus(id, action) {
  const a = adoptionRequests.find(x => x.id === id);
  fetch('admin_adoption.php?action=' + action, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { showToast(data.message || 'Could not update request.', 'error'); return; }
    closeModal('adoptionDetailModal');
    showToast(`Adoption request has been ${action === 'approve' ? 'approved' : 'denied'}.`);
    // if (action === 'deny' && a) {
    //   sendUserNotification(`<strong>${esc(a.applicant_name)}</strong> was notified that their application for <strong>${esc(a.pet_name || 'the pet')}</strong> was not approved.`, 'act-coral', 'ti-x');
    // }
    loadAdoption();
  });
}

// ══════════════════════════════════════════
//  ADOPTION TRANSACTION VERIFICATION
//  The admin reviews any proof submitted with the request (e.g. a facility/
//  vet-uploaded handover photo), optionally attaches their own confirmation
//  photo, then marks the transaction verified & complete. Confirming pushes
//  a notification to the applicant via the notification bell.
// ══════════════════════════════════════════
// ══════════════════════════════════════════
//  VET HAND-OFF REPORT ACKNOWLEDGMENT
//  The vet approves a forwarded request, then reports the completed
//  hand-off (with their own proof photo) via vet_transactions.php.
//  The admin reviews that report here and acknowledges it, which marks
//  the reservation 'completed', the pet 'adopted', and notifies the applicant.
// ══════════════════════════════════════════
let vetTransactions = [];
let avmPendingFiles = [];
let currentVetTxnIndex = -1;

//let seenVetTransactionIds = null; // tinanggal q muna hehe

function loadVetTransactions() {
  fetch('admin_adoption.php?action=list_transactions')
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        console.error('list_transactions failed:', data.message);
        showToast(data.message ? `Couldn't load hand-off reports: ${data.message}` : 'Could not load vet hand-off reports.', 'error');
        return;
      }
      vetTransactions = data.transactions.map(t => ({ ...t, id: parseInt(t.id, 10) }));
      renderVetTransactions();

      // const currentIds = new Set(vetTransactions.map(t => t.id));
      // if (seenVetTransactionIds !== null) {
      //   vetTransactions.forEach(t => {
      //     if (!seenVetTransactionIds.has(t.id)) {
      //       sendUserNotification(
      //         `Vet reported a completed hand-off for <strong>${esc(t.animal_name || 'a pet')}</strong> — awaiting your review.`,
      //         'act-green', 'ti-shield-check'
      //       );
      //     }
      //   });
      // }
      // seenVetTransactionIds = currentIds;
    })
    .catch(err => {
      console.error(err);
      showToast('Could not reach the server to load vet hand-off reports.', 'error');
    });
}

function renderVetTransactions() {
  const tbody = document.getElementById('vet-transactions-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';
  if (!vetTransactions.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No hand-off reports awaiting review.</td></tr>';
  }
  vetTransactions.forEach(t => {
    tbody.innerHTML += `
      <tr>
        <td><strong>${esc(t.animal_name || 'Unknown')}</strong></td>
        <td>${esc(t.adopter_name || '—')}</td>
        <td style="max-width:220px">${esc(t.notes || '—')}</td>
        <td>${esc(t.created_at)}</td>
        <td>${t.photo_url ? `<img class="report-thumb" src="${esc(t.photo_url)}" onclick="openLightbox('${esc(t.photo_url)}')">` : '—'}</td>
        <td><button class="btn btn-approve btn-sm" onclick="openAcknowledgeTransaction(${t.id})"><i class="ti ti-shield-check"></i> Review</button></td>
      </tr>`;
  });
  const countEl = document.getElementById('vet-reports-count');
  if (countEl) countEl.textContent = `(${vetTransactions.length})`;
  const badgeEl = document.getElementById('vet-reports-badge');
  if (badgeEl) badgeEl.textContent = vetTransactions.length;
  const adoptionBadge = document.getElementById('badge-adoption');
  if (adoptionBadge) {
    const pending = adoptionRequests.filter(a => a.status === 'pending').length;
    const awaitingHandoff = adoptionRequests.filter(a => a.status === 'vet_approved').length;
    adoptionBadge.textContent = pending + awaitingHandoff + vetTransactions.length;
  }
}

function openAcknowledgeTransaction(id) {
  const t = vetTransactions.find(x => x.id === id);
  if (!t) return;
  currentVetTxnIndex = id;
  document.getElementById('avm-pet').textContent = t.animal_name || 'Unknown';
  document.getElementById('avm-applicant').textContent = t.adopter_name || '—';
  document.getElementById('avm-facility').textContent = t.vet_facility || '—';

  const evSection = document.getElementById('avm-evidence-section');
  if (t.photo_url) {
    document.getElementById('avm-evidence').innerHTML = `<img src="${esc(t.photo_url)}" onclick="openLightbox('${esc(t.photo_url)}')" alt="Vet hand-off proof">`;
    evSection.style.display = '';
  } else {
    evSection.style.display = 'none';
  }

  avmPendingFiles = [];
  renderAvmPreview();
  const fileInput = document.getElementById('avm-files');
  if (fileInput) fileInput.value = '';

  openModal('adoptionVerifyModal');
}

document.addEventListener('change', e => {
  if (e.target && e.target.id === 'avm-files') {
    Array.from(e.target.files || []).forEach(file => {
      const reader = new FileReader();
      reader.onload = ev => { avmPendingFiles.push(ev.target.result); renderAvmPreview(); };
      reader.readAsDataURL(file);
    });
  }
});

function fillMedicalFields(p) {
  document.getElementById('rfm-vaccinated').checked = !!(p && Number(p.vaccinated) === 1);
  document.getElementById('rfm-dewormed').checked   = !!(p && Number(p.dewormed) === 1);
  document.getElementById('rfm-neutered').checked   = !!(p && Number(p.neutered) === 1);
  document.getElementById('rfm-health-status').value = (p && p.health_status) || '';
  document.getElementById('rfm-medical-notes').value = (p && p.medical_notes) || '';
}

document.addEventListener('change', e => {
  if (e.target && e.target.id === 'rfm-pet') {
    const petId = parseInt(e.target.value);
    const p = rfidPetOptions.find(x => x.id == petId);
    fillMedicalFields(p);
  }
});

function renderAvmPreview() {
  const wrap = document.getElementById('avm-preview');
  if (!wrap) return;
  wrap.innerHTML = avmPendingFiles.map((src, i) => `
    <div style="position:relative">
      <img src="${src}" style="width:100%;height:90px;object-fit:cover;border-radius:10px;border:1px solid rgba(1,34,36,0.1)">
      <button type="button" onclick="removeAvmFile(${i})" style="position:absolute;top:4px;right:4px;background:rgba(1,34,36,0.7);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:11px;cursor:pointer;line-height:1">✕</button>
    </div>`).join('');
}

function removeAvmFile(i) {
  avmPendingFiles.splice(i, 1);
  renderAvmPreview();
}

function confirmAdoptionVerify() {
  const id = currentVetTxnIndex;
  const t = vetTransactions.find(x => x.id === id);
  if (!t) return;

  fetch('admin_adoption.php?action=acknowledge_transaction', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ transaction_id: id, admin_proof: avmPendingFiles }),
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { showToast(data.message || 'Could not acknowledge transaction.', 'error'); return; }
    closeModal('adoptionVerifyModal');
    showToast(`Transaction verified — "${t.animal_name}" adoption marked complete.`);
    loadVetTransactions();
    loadAdoption();
  })
  .catch(() => showToast('Could not reach the server.', 'error'));
}

// Prepends a new item into the notification bell, simulating the system
// message the applicant/user would receive once a transaction is verified.
function sendUserNotification(html, iconClass, icon) {
  const list = document.getElementById('notif-list');
  if (!list) return;
  const empty = document.getElementById('notif-empty');
  if (empty) empty.remove();
  const item = document.createElement('div');
  item.className = 'notif-item unread';
  item.innerHTML = `
    <div class="notif-icon ${iconClass}"><i class="ti ${icon}"></i></div>
    <div style="flex:1">
      <div class="notif-text">${html}</div>
      <div class="notif-time">Just now</div>
    </div>
    <div class="unread-dot"></div>`;
  list.prepend(item);
  unreadCount++;
  const dot = document.getElementById('notif-dot');
  if (dot) dot.style.display = '';
  const badge = document.getElementById('notif-count-badge');
  if (badge) { badge.style.display = ''; badge.textContent = unreadCount; }
}

let currentVerifIndex = -1;
let currentReportIndex = -1;
let currentUserIndex   = -1;
let deleteTargetIndex  = -1;
let currentAdoptionIndex = -1;
let currentRFIDIndex = -1;
let rfidActionType = ''; // 'deactivate' | 'reactivate'

//  NAVIGATION
const pageTitles = {
  dashboard:'Dashboard Analytics', facilities:'Facility Management',
  users:'User Management', verification:'Verification & Approval', reports:'Reports & Feedback',
  adoption:'Adoption Requests', rfid:'RFID Management',
  donations:'Donation Management', announcements:'Community Announcements'
  // privatevets:'Private Vet / Clinic Verification', medicalcases:'Medical Case Requests'
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

//  MODALS
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
let unreadCount = 0;

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
  fetch('admin_adoption.php?action=mark_notifications_read')
    .then(r => r.json())
    .then(() => { loadAdminNotifications(); showToast('All notifications marked as read.'); })
    .catch(() => showToast('Could not reach the server.', 'error'));
}

function handleNotifClick(page) {
  closeNotif();
  const navItem = document.querySelector(`.nav-item[onclick*="${page}"]`);
  if (navItem) navigate(navItem, page);
}

const NOTIF_STYLE = {
  vet_approved:     { cls: 'act-green', icon: 'ti-check' },
  vet_denied:       { cls: 'act-coral', icon: 'ti-x' },
  handoff_reported: { cls: 'act-blue',  icon: 'ti-shield-check' },
};

function loadAdminNotifications() {
  fetch('admin_adoption.php?action=list_notifications')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const list = document.getElementById('notif-list');
      const unread = data.notifications.filter(n => Number(n.is_read) === 0).length;

      list.innerHTML = data.notifications.length
        ? data.notifications.map(n => {
            const s = NOTIF_STYLE[n.type] || NOTIF_STYLE.handoff_reported;
            const isUnread = Number(n.is_read) === 0;
            return `
            <div class="notif-item ${isUnread ? 'unread' : ''}" onclick="handleNotifClick('adoption')">
              <div class="notif-icon ${s.cls}"><i class="ti ${s.icon}"></i></div>
              <div style="flex:1">
                <div class="notif-text">${esc(n.message)}</div>
                <div class="notif-time">${esc(timeAgo(new Date(n.created_at.replace(' ', 'T'))))}</div>
              </div>
              ${isUnread ? '<div class="unread-dot"></div>' : ''}
            </div>`;
          }).join('')
        : '<div class="notif-empty" id="notif-empty">No notifications yet.</div>';

      unreadCount = unread;
      document.getElementById('notif-dot').style.display = unread ? '' : 'none';
      const badge = document.getElementById('notif-count-badge');
      badge.style.display = unread ? '' : 'none';
      badge.textContent = unread;
    })
    .catch(() => {});
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
      <td>${esc(f.name)}</td>
      <td>${esc(f.type)}</td>
      <td>${esc(f.address)}</td>
      <td>${esc(f.contact)}</td>
      <td><span class="badge ${f.status === 'Active' ? 'active' : 'inactive'}">${esc(f.status)}</span></td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-sm" onclick="openEditFacility(${realIdx})"><i class="ti ti-edit"></i> Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteFacility(${realIdx})" style="margin-left:8px"><i class="ti ti-trash"></i></button>
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
  document.getElementById('ffm-type').value    = 'clinic';
  document.getElementById('ffm-contact').value = '';
  document.getElementById('ffm-address').value = '';
  document.getElementById('ffm-hours').value   = '';
  document.getElementById('ffm-desc').value    = '';
  document.getElementById('ffm-lat').value     = '';
  document.getElementById('ffm-lng').value     = '';
  document.getElementById('ffm-status').value  = 'active';
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
  document.getElementById('ffm-hours').value   = f.opening_hours;
  document.getElementById('ffm-status').value  = f.status;
  document.getElementById('ffm-desc').value = f.description || '';
  document.getElementById('ffm-lat').value  = f.latitude || '';
  document.getElementById('ffm-lng').value  = f.longitude || '';
  openModal('facilityFormModal');
}

//  USERS
function renderUsers(list) {
  const tbody = document.getElementById('users-tbody');
  tbody.innerHTML = '';
  list.forEach((u, i) => {
    const realIdx = users.indexOf(u);
    tbody.innerHTML += `
      <tr>
        <td><div class="profile-link" onclick="openUserDetail(${realIdx})">
          ${avatarHtml(u)}
          <strong>${esc(u.name)}</strong>
        </div></td>
        <td>${esc(u.email)}</td>
        <td><span class="badge ${u.role==='Pet owner'?'adopter':u.role==='Vet'?'vet-role':'owner'}">${esc(u.role)}</span></td>
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
      <td><div class="profile-link" onclick="openUserDetail(${ri})">
        ${avatarHtml(u)}
        <strong>${esc(u.name)}</strong></div></td>
      <td>${esc(u.email)}</td><td>${esc(u.joined)}</td>
      <td><span class="badge ${u.status==='Active'?'active':'suspended'}">${esc(u.status)}</span></td>
      <td><button class="btn btn-ghost btn-sm" onclick="openUserDetail(${ri})"><i class="ti ti-eye"></i> View</button></td>
    </tr>`;
  }).join('');

  // Biz owners tab
  const bo = users.filter(u => u.role === 'Vet');
  document.getElementById('bizowners-tbody').innerHTML = bo.map(u => {
    const ri = users.indexOf(u);
    return `<tr>
      <td><div class="profile-link" onclick="openUserDetail(${ri})">
        ${avatarHtml(u)}
        <strong>${esc(u.name)}</strong></div></td>
      <td>${esc(u.email)}</td><td>${esc(u.joined)}</td>
      <td><span class="badge ${u.status==='Active'?'active':'suspended'}">${esc(u.status)}</span></td>
      <td><button class="btn btn-ghost btn-sm" onclick="openUserDetail(${ri})"><i class="ti ti-eye"></i> View</button></td>
    </tr>`;
  }).join('');
}

// Opens a user's profile modal by matching their name — used to make names
// clickable inside reports, adoption requests, RFID records, and feedback.
function openUserByName(name) {
  const idx = users.findIndex(u => u.name === name);
  if (idx === -1) { showToast('No matching user profile on file.', 'error'); return; }
  openUserDetail(idx);
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
  document.getElementById('udm-photo-preview').innerHTML = avatarHtml(u, 64);
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
  const newStatus = u.status === 'Active' ? 'suspended' : 'active';
  fetch('admin_users.php?action=set_status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: u.id, status: newStatus }),
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { showToast(data.message || 'Could not update user.', 'error'); return; }
    u.status = newStatus === 'active' ? 'Active' : 'Suspended';
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
  })
  .catch(() => showToast('Could not reach the server.', 'error'));
}

//  REPORTS
function renderReports() {
  const tbody = document.getElementById('reports-tbody');
  tbody.innerHTML = '';
  if (!reports.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No reports filed yet.</td></tr>';
  }
  reports.forEach((r, i) => {
    const badgeClass = r.status === 'Open' ? 'pending' : r.status === 'Resolved' ? 'approved' : 'inactive';
    const imgs = r.images || [];
    let thumbHtml = '';
    if (imgs.length) {
      thumbHtml = '<div class="report-thumbs">' +
        imgs.slice(0,3).map(src => `<img class="report-thumb" src="${esc(src)}" onclick="event.stopPropagation();openLightbox('${esc(src)}')">`).join('') +
        (imgs.length > 3 ? `<div class="report-thumb-more">+${imgs.length-3}</div>` : '') +
        '</div>';
    }
    tbody.innerHTML += `
      <tr>
        <td>${userChipHtml(r.entity)}${thumbHtml}</td>
        <td>${userChipHtml(r.reporter)}</td>
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

function renderFeedback() {
  const listEl = document.getElementById('reports-feedback-list');
  const dashEl = document.getElementById('dashboard-feedback');
  if (!feedbackList.length) {
    listEl.innerHTML = '<div class="notif-empty">No user feedback yet.</div>';
    dashEl.innerHTML = '<div class="activity-item"><div class="act-text" style="color:rgba(1,34,36,0.4)">No feedback yet.</div></div>';
  } else {
    listEl.innerHTML = feedbackList.map(feedbackCardHtml).join('');
    dashEl.innerHTML = feedbackList.slice(0,3).map(f => `
      <div class="activity-item">
        <div class="act-icon act-coral"><i class="ti ti-message-circle"></i></div>
        <div>
          <div class="act-text">${userChipHtml(f.name, 22, f.userId)} — <span class="stars" style="font-size:11px">${'★'.repeat(f.rating)}</span></div>
          <div class="act-time">${esc(f.text.slice(0,70))}${f.text.length>70?'…':''}</div>
        </div>
      </div>`).join('');
  }
  const total = feedbackList.length;
  const avg = total ? (feedbackList.reduce((s,f) => s + f.rating, 0) / total).toFixed(1) : '—';
  document.getElementById('stat-feedback-total').textContent = total;
  document.getElementById('stat-feedback-avg').textContent = avg;
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
  const evSection = document.getElementById('rdm-evidence-section');
  const imgs = r.images || [];
  if (imgs.length) {
    document.getElementById('rdm-evidence').innerHTML = imgs.map(src => `<img src="${esc(src)}" onclick="openLightbox('${esc(src)}')" alt="Evidence photo">`).join('');
    evSection.style.display = '';
  } else {
    evSection.style.display = 'none';
  }
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
    const r = reports[currentReportIndex];
    fetch('admin_reports.php?action=set_status', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: r.id, status: 'dismissed' })
    })
    .then(r2 => r2.json())
    .then(d => {
        if (!d.success) { showToast('Could not dismiss report.', 'error'); return; }
        closeModal('reportDetailModal');
        showToast('Report has been dismissed.');
        loadReports();
    });
}

function resolveReport() {
    const r = reports[currentReportIndex];
    fetch('admin_reports.php?action=set_status', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: r.id, status: 'resolved' })
    })
    .then(r2 => r2.json())
    .then(d => {
        if (!d.success) { showToast('Could not resolve report.', 'error'); return; }
        closeModal('reportDetailModal');
        showToast('Report marked as resolved.');
        loadReports();
    });
}

//  FEEDBACK (integrated across Dashboard + Reports page)
function feedbackCardHtml(f) {
  const stars = '★'.repeat(f.rating) + `<span style="color:rgba(1,34,36,0.15)">${'★'.repeat(5-f.rating)}</span>`;
  return `
    <div class="feedback-card">
      <div class="fc-header">
          <div class="fc-user">${userChipHtml(f.name, 28, f.userId)}${f.petName ? ` <span style="color:rgba(1,34,36,0.4);font-weight:400">— re: ${esc(f.petName)}</span>` : ''}</div>
          <div class="fc-date">${esc(f.date)}</div>
        </div>
      <div class="stars">${stars}</div>
      <div style="font-size:12px;color:rgba(1,34,36,0.55);margin-top:2px">${f.targetType === 'facility' ? `<i class="ti ti-building"></i> Facility: <strong>${esc((f.facilityName || 'Unknown').trim())}</strong>` : '<i class="ti ti-paw"></i> About the PawConnect app'}</div>
      <div class="fc-body" style="margin-top:6px">${esc(f.text)}</div>
      <div class="fc-footer">
        <span class="badge ${f.tagClass}">${esc(f.tag)}</span>
      </div>
    </div>`;
}

//  RFID (wired to admin_rfid.php)
let rfidRecords = [];
let rfidPetOptions = [];

function populateRFIDDropdowns() {
  const facSel = document.getElementById('rfm-facility');
  if (facSel) {
    const allowed = facilities.filter(f => f.name.trim().toLowerCase() === 'angeles city veterinary office');
    facSel.innerHTML = '<option value="">— None —</option>' + allowed.map(f => `<option value="${f.id}">${esc(f.name)}</option>`).join('');
  }

  const petSel = document.getElementById('rfm-pet');
  if (!petSel) return;
  fetch('admin_rfid.php?action=list_pets')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      rfidPetOptions = data.pets;
      petSel.innerHTML = '<option value="">Select an animal…</option>' +
        rfidPetOptions.map(p => `<option value="${p.id}">${esc(p.name)} — ${esc(p.type || '')}, ${esc(p.breed || '')}</option>`).join('');
    });
}

function loadRFID() {
  fetch('admin_rfid.php?action=list')
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast('Failed to load RFID tags.', 'error'); return; }
      rfidRecords = data.tags.map(r => ({
        ...r,
        id: parseInt(r.id, 10),
        pet_id: r.pet_id !== null ? parseInt(r.pet_id, 10) : null,
        facility_id: r.facility_id !== null ? parseInt(r.facility_id, 10) : null,
      }));
      filterRFID();
      buildRecentActivity();
    })
    .catch(() => showToast('Could not reach the server for RFID tags.', 'error'));
}

function filterRFID() {
  const q      = (document.getElementById('rfid-search')?.value || '').toLowerCase();
  const status = document.getElementById('rfid-status-filter')?.value || '';
  const list = rfidRecords.filter(r =>
    (!q || r.chip_uid.toLowerCase().includes(q) || (r.pet_name||'').toLowerCase().includes(q) || (r.owner_name||'').toLowerCase().includes(q)) &&
    (!status || r.status.toLowerCase() === status.toLowerCase())
  );
  const tbody = document.getElementById('rfid-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No RFID records found.</td></tr>';
  }
  list.forEach(r => {
    const badgeClass = r.status === 'active' ? 'active' : r.status === 'replaced' ? 'replaced' : 'deactivated';
    const statusLabel = r.status.charAt(0).toUpperCase() + r.status.slice(1);
    const registeredLabel = r.registered_at ? new Date(r.registered_at.replace(' ', 'T')).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '—';
    tbody.innerHTML += `
      <tr>
        <td><span class="rfid-chip"><i class="ti ti-id"></i>${esc(r.chip_uid)}</span></td>
        <td><div style="display:flex;align-items:center;gap:9px"><div class="pet-ava"><i class="ti ti-paw"></i></div><div><strong>${esc(r.pet_name || 'Unassigned')}</strong><div style="font-size:11px;color:rgba(1,34,36,0.45)">${esc(r.pet_type || '')} · ${esc(r.pet_breed || '')}</div></div></div></td>
        <td>${r.owner_name ? `<span class="name-link" onclick="openUserByName('${esc(r.owner_name).replace(/'/g,"\\'")}')">${esc(r.owner_name)}</span>` : '<span style="color:rgba(1,34,36,0.4)">Not adopted yet</span>'}</td>
        <td>${esc(r.facility_name || '—')}</td>
        <td><span class="badge ${badgeClass}">${esc(statusLabel)}</span></td>
        <td>${registeredLabel}</td>
        <td style="white-space:nowrap">
          <button class="btn btn-ghost btn-sm" title="Scan history" onclick="openRFIDHistory(${r.id})"><i class="ti ti-history"></i></button>
          <button class="btn btn-ghost btn-sm" title="Edit" onclick="openEditRFID(${r.id})"><i class="ti ti-edit"></i></button>
          <button class="btn btn-ghost btn-sm" title="Transfer ownership" onclick="openRFIDTransfer(${r.id})"><i class="ti ti-transfer"></i></button>
          ${r.status === 'active'
            ? `<button class="btn btn-danger btn-sm" title="Deactivate" onclick="openRFIDDeactivate(${r.id})"><i class="ti ti-power"></i></button>`
            : r.status === 'deactivated'
              ? `<button class="btn btn-warning btn-sm" title="Reactivate" onclick="openRFIDReactivate(${r.id})"><i class="ti ti-power"></i></button>`
              : ''}
          ${r.status !== 'replaced' ? `<button class="btn btn-ghost btn-sm" title="Replace chip" onclick="openRFIDReplace(${r.id})"><i class="ti ti-refresh"></i></button>` : ''}
        </td>
      </tr>`;
  });
  document.getElementById('rfid-count').textContent = `(${list.length})`;
  const activeCount = rfidRecords.filter(r => r.status === 'active').length;
  const statEl = document.getElementById('stat-rfid-active');
  if (statEl) statEl.textContent = activeCount;
}

function openAddRFID() {
  populateRFIDDropdowns();
  document.getElementById('rfm-title').textContent = 'Register RFID';
  document.getElementById('rfm-index').value = -1;
  document.getElementById('rfm-chip').value = '';
  document.getElementById('rfm-facility').value = '';
  fillMedicalFields(null);
  openModal('rfidFormModal');
}

function openEditRFID(id) {
  populateRFIDDropdowns();
  const r = rfidRecords.find(x => x.id === id);
  if (!r) return;
  document.getElementById('rfm-title').textContent = 'Edit RFID — ' + r.chip_uid;
  document.getElementById('rfm-index').value = id;
  document.getElementById('rfm-chip').value = r.chip_uid;
  const trySetPet = () => {
    const petSel = document.getElementById('rfm-pet');
    if (petSel.options.length > 1) {
      petSel.value = r.pet_id;
      fillMedicalFields(rfidPetOptions.find(x => x.id == r.pet_id));
    } else {
      setTimeout(trySetPet, 100);
    }
  };
  trySetPet();
  document.getElementById('rfm-facility').value = r.facility_id || '';
  openModal('rfidFormModal');
}

function saveRFID() {
  const idx  = parseInt(document.getElementById('rfm-index').value);
  const chip = document.getElementById('rfm-chip').value.trim();
  const petId = parseInt(document.getElementById('rfm-pet').value);
  if (!chip || !petId) { showToast('Please enter a chip ID and select an animal.', 'error'); return; }

  const body = { chip_uid: chip, pet_id: petId, facility_id: document.getElementById('rfm-facility').value || null };
  if (idx !== -1) body.id = idx;

  const medicalBody = {
    pet_id: petId,
    vaccinated: document.getElementById('rfm-vaccinated').checked,
    dewormed: document.getElementById('rfm-dewormed').checked,
    neutered: document.getElementById('rfm-neutered').checked,
    health_status: document.getElementById('rfm-health-status').value.trim(),
    medical_notes: document.getElementById('rfm-medical-notes').value.trim(),
  };

  Promise.all([
    fetch('admin_rfid.php?action=' + (idx === -1 ? 'register' : 'update'), {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
    }).then(r => r.json()),
    fetch('admin_rfid.php?action=update_pet_medical', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(medicalBody)
    }).then(r => r.json())
  ])
  .then(([chipResult, medResult]) => {
    if (!chipResult.success) { showToast(chipResult.message, 'error'); return; }
    if (!medResult.success) { showToast('Chip saved, but medical info failed to save.', 'error'); return; }
    closeModal('rfidFormModal');
    showToast(idx === -1 ? 'RFID chip registered.' : 'RFID record updated.');
    loadRFID();
  })
  .catch(() => showToast('Could not reach the server.', 'error'));
}

function openRFIDHistory(id) {
  const r = rfidRecords.find(x => x.id === id);
  if (!r) return;
  document.getElementById('rhm-title').textContent = `${r.pet_name || 'Unassigned chip'} — Scan history`;
  document.getElementById('rhm-chip').querySelector('span').textContent = r.chip_uid;
  document.getElementById('rhm-list').innerHTML = '<div class="notif-empty">Loading…</div>';
  openModal('rfidHistoryModal');
  fetch('admin_rfid.php?action=history&id=' + id)
    .then(res => res.json())
    .then(data => {
      if (!data.success) { document.getElementById('rhm-list').innerHTML = '<div class="notif-empty">Could not load scan history.</div>'; return; }
      document.getElementById('rhm-list').innerHTML = data.scans.map(s => `
        <div class="scan-item">
          <div class="scan-dot"></div>
          <div>
            <div class="scan-text">${esc(s.event_type)}${s.note ? ' — ' + esc(s.note) : ''}</div>
            <div class="scan-meta">${esc(s.scanned_at)}</div>
          </div>
        </div>`).join('') || '<div class="notif-empty">No scans logged yet.</div>';
    });
}

function openRFIDTransfer(id) {
  const r = rfidRecords.find(x => x.id === id);
  if (!r) return;
  document.getElementById('rtm-index').value = id;
  document.getElementById('rtm-current-owner').value = r.owner_name || 'Not adopted yet';
  document.getElementById('rtm-new-owner').innerHTML = users
    .filter(u => u.name !== r.owner_name)
    .map(u => `<option value="${u.id}">${esc(u.name)}</option>`).join('');
  document.getElementById('rtm-reason').value = '';
  openModal('rfidTransferModal');
}

function submitRFIDTransfer() {
  const id = parseInt(document.getElementById('rtm-index').value);
  const newOwnerId = parseInt(document.getElementById('rtm-new-owner').value);
  const reason = document.getElementById('rtm-reason').value.trim();
  fetch('admin_rfid.php?action=transfer_owner', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, new_owner_id: newOwnerId, reason })
  })
  .then(r => r.json())
  .then(d => {
    if (!d.success) { showToast(d.message, 'error'); return; }
    closeModal('rfidTransferModal');
    showToast('Ownership updated.');
    loadRFID();
  })
  .catch(() => showToast('Could not reach the server.', 'error'));
}

function openRFIDDeactivate(id) {
  currentRFIDIndex = id;
  rfidActionType = 'deactivate';
  const r = rfidRecords.find(x => x.id === id);
  document.getElementById('rdc-title').textContent = 'Deactivate chip';
  document.getElementById('rdc-heading').textContent = `Deactivate RFID for ${r ? r.pet_name : 'this animal'}?`;
  document.getElementById('rdc-text').textContent = 'Scans against this chip will no longer resolve to an animal profile until reactivated.';
  openModal('rfidDeactivateModal');
}

function openRFIDReactivate(id) {
  currentRFIDIndex = id;
  rfidActionType = 'reactivate';
  const r = rfidRecords.find(x => x.id === id);
  document.getElementById('rdc-title').textContent = 'Reactivate chip';
  document.getElementById('rdc-heading').textContent = `Reactivate RFID for ${r ? r.pet_name : 'this animal'}?`;
  document.getElementById('rdc-text').textContent = 'This chip will resolve to the animal profile again on the next scan.';
  openModal('rfidDeactivateModal');
}

function confirmToggleRFID() {
  const id = currentRFIDIndex;
  const status = rfidActionType === 'deactivate' ? 'deactivated' : 'active';
  fetch('admin_rfid.php?action=set_status', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, status })
  })
  .then(r => r.json())
  .then(d => {
    if (!d.success) { showToast(d.message, 'error'); return; }
    closeModal('rfidDeactivateModal');
    showToast(status === 'active' ? 'Chip reactivated.' : 'Chip deactivated.', status === 'active' ? 'success' : 'error');
    loadRFID();
  })
  .catch(() => showToast('Could not reach the server.', 'error'));
}

function openRFIDReplace(id) {
  currentRFIDIndex = id;
  document.getElementById('rrm-index').value = id;
  document.getElementById('rrm-new-chip').value = '';
  openModal('rfidReplaceModal');
}

function confirmRFIDReplace() {
  const id = parseInt(document.getElementById('rrm-index').value);
  const newChip = document.getElementById('rrm-new-chip').value.trim();
  if (!newChip) { showToast('Please enter a new chip ID.', 'error'); return; }
  fetch('admin_rfid.php?action=replace', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, new_chip_uid: newChip })
  })
  .then(r => r.json())
  .then(d => {
    if (!d.success) { showToast(d.message, 'error'); return; }
    closeModal('rfidReplaceModal');
    showToast('Chip replaced.');
    loadRFID();
  })
  .catch(() => showToast('Could not reach the server.', 'error'));
}

let liveScanExpanded = false;

function toggleLiveScanPanel() {
  liveScanExpanded = !liveScanExpanded;
  const body = document.getElementById('live-scan-body');
  const chevron = document.getElementById('live-scan-chevron');
  const label = document.getElementById('live-scan-toggle-label');
  body.style.display = liveScanExpanded ? '' : 'none';
  chevron.style.transform = liveScanExpanded ? 'rotate(180deg)' : '';
  label.textContent = liveScanExpanded ? 'Hide' : 'Show';
}

//  DONATION MANAGEMENT
let donations = [];
let currentDonationIndex = -1;
let dfmPendingFiles = []; // data URLs staged for the add/edit form

function peso(n) {
  return '₱' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderDonations() {
  filterDonations();
}

function filterDonations() {
  const q = (document.getElementById('donation-search')?.value || '').toLowerCase();
  const list = donations.filter(d => !q || d.desc.toLowerCase().includes(q));
  const tbody = document.getElementById('donations-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No donation usage logged yet.</td></tr>';
  }
  list.forEach(d => {
    const idx = donations.indexOf(d);
    const proof = (d.files || []).length
      ? `<div class="report-thumbs">${d.files.slice(0,3).map(src => `<img class="report-thumb" src="${esc(src)}" onclick="event.stopPropagation();openLightbox('${esc(src)}')">`).join('')}${d.files.length > 3 ? `<div class="report-thumb-more">+${d.files.length-3}</div>` : ''}</div>`
      : '<span style="color:rgba(1,34,36,0.4)">None</span>';
    tbody.innerHTML += `
      <tr class="clickable" onclick="openDonationDetail(${idx})">
        <td>${esc(formatDateDisplay(d.date))}</td>
        <td>${esc(d.desc)}</td>
        <td><strong>${peso(d.amount)}</strong></td>
        <td>${proof}</td>
        <td style="white-space:nowrap">
          <button class="btn btn-ghost btn-sm" onclick="event.stopPropagation();openEditDonation(${idx})"><i class="ti ti-edit"></i></button>
          <button class="btn btn-danger btn-sm" onclick="event.stopPropagation();deleteDonation(${idx})" style="margin-left:8px"><i class="ti ti-trash"></i></button>
        </td>
      </tr>`;
  });
  document.getElementById('donation-count').textContent = `(${list.length})`;
  document.getElementById('stat-donation-total').textContent = peso(donations.reduce((s,d) => s + Number(d.amount||0), 0));
  document.getElementById('stat-donation-proof').textContent = donations.filter(d => (d.files||[]).length).length;
}

function formatDateDisplay(iso) {
  if (!iso) return '—';
  const d = new Date(iso + 'T00:00:00');
  if (isNaN(d)) return iso;
  return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
}

function openAddDonation() {
  document.getElementById('dfm-title').textContent = 'Log donation usage';
  document.getElementById('dfm-index').value = -1;
  document.getElementById('dfm-date').value = new Date().toISOString().slice(0,10);
  document.getElementById('dfm-amount').value = '';
  document.getElementById('dfm-desc').value = '';
  document.getElementById('dfm-files').value = '';
  dfmPendingFiles = [];
  renderDfmPreview();
  openModal('donationFormModal');
}

function openEditDonation(idx) {
  const d = donations[idx];
  document.getElementById('dfm-title').textContent = 'Edit donation usage entry';
  document.getElementById('dfm-index').value = idx;
  document.getElementById('dfm-date').value = d.date;
  document.getElementById('dfm-amount').value = d.amount;
  document.getElementById('dfm-desc').value = d.desc;
  document.getElementById('dfm-files').value = '';
  dfmPendingFiles = (d.files || []).slice();
  renderDfmPreview();
  openModal('donationFormModal');
}

document.addEventListener('change', e => {
  if (e.target && e.target.id === 'dfm-files') {
    const files = Array.from(e.target.files || []);
    let remaining = files.length;
    if (!remaining) return;
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = ev => {
        dfmPendingFiles.push(ev.target.result);
        renderDfmPreview();
      };
      reader.readAsDataURL(file);
    });
  }
});

function renderDfmPreview() {
  const wrap = document.getElementById('dfm-preview');
  if (!wrap) return;
  wrap.innerHTML = dfmPendingFiles.map((src, i) => `
    <div style="position:relative">
      <img src="${src}" style="width:100%;height:90px;object-fit:cover;border-radius:10px;border:1px solid rgba(1,34,36,0.1)">
      <button type="button" onclick="removeDfmFile(${i})" style="position:absolute;top:4px;right:4px;background:rgba(1,34,36,0.7);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:11px;cursor:pointer;line-height:1">✕</button>
    </div>`).join('');
}

function removeDfmFile(i) {
  dfmPendingFiles.splice(i, 1);
  renderDfmPreview();
}

function saveDonation() {
  const idx = parseInt(document.getElementById('dfm-index').value);
  const date = document.getElementById('dfm-date').value;
  const amount = parseFloat(document.getElementById('dfm-amount').value);
  const desc = document.getElementById('dfm-desc').value.trim();
  if (!date) { showToast('Please select a date.', 'error'); return; }
  if (!amount || amount <= 0) { showToast('Please enter a valid amount used.', 'error'); return; }
  if (!desc) { showToast('Please describe what the donation was used for.', 'error'); return; }
  const data = { date, amount, desc, files: dfmPendingFiles.slice() };
  if (idx === -1) {
    donations.unshift(data);
    showToast('Donation usage entry logged.');
  } else {
    donations[idx] = data;
    showToast('Donation usage entry updated.');
  }
  closeModal('donationFormModal');
  renderDonations();
}

function openDonationDetail(idx) {
  currentDonationIndex = idx;
  const d = donations[idx];
  document.getElementById('ddm-date').textContent = formatDateDisplay(d.date);
  document.getElementById('ddm-amount').textContent = peso(d.amount);
  document.getElementById('ddm-desc').textContent = d.desc;
  const evSection = document.getElementById('ddm-evidence-section');
  const imgs = d.files || [];
  if (imgs.length) {
    document.getElementById('ddm-evidence').innerHTML = imgs.map(src => `<img src="${esc(src)}" onclick="openLightbox('${esc(src)}')" alt="Proof of purchase">`).join('');
    evSection.style.display = '';
  } else {
    evSection.style.display = 'none';
  }
  openModal('donationDetailModal');
}

function deleteDonation(idx) {
  const i = typeof idx === 'number' ? idx : currentDonationIndex;
  const d = donations[i];
  donations.splice(i, 1);
  closeModal('donationDetailModal');
  renderDonations();
  showToast(`Donation entry${d ? ' — "' + d.desc.slice(0,40) + (d.desc.length>40?'…':'') + '"' : ''} deleted.`, 'error');
}

let receivedDonations = [];

function loadReceivedDonations() {
  fetch('admin_donations.php?action=list_received')
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast('Failed to load donations received.', 'error'); return; }
      receivedDonations = data.donations;
      filterReceivedDonations();
    })
    .catch(() => showToast('Could not reach the server for received donations.', 'error'));
}

function filterReceivedDonations() {
  const q = (document.getElementById('received-search')?.value || '').toLowerCase();
  const purpose = document.getElementById('received-purpose-filter')?.value || '';
  const list = receivedDonations.filter(d => {
    const full = `${d.first_name} ${d.last_name} ${d.email}`.toLowerCase();
    return (!q || full.includes(q)) && (!purpose || d.purpose === purpose);
  });
  const tbody = document.getElementById('received-donations-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No donations received yet.</td></tr>';
  }
  list.forEach(d => {
    const statusClass = d.status === 'completed' ? 'active' : d.status === 'failed' ? 'rejected' : 'pending';
    tbody.innerHTML += `
      <tr>
        <td>${esc(formatDateDisplay((d.created_at || '').slice(0, 10)))}</td>
        <td><strong>${esc(d.first_name)} ${esc(d.last_name)}</strong></td>
        <td>${esc(d.email)}${d.mobile ? '<br><span style="font-size:11px;color:rgba(1,34,36,0.45)">' + esc(d.mobile) + '</span>' : ''}</td>
        <td>${esc(d.purpose)}</td>
        <td><strong>${peso(d.amount)}</strong></td>
        <td>${d.recurring == 1 ? 'Yes' : 'No'}</td>
        <td><span class="badge ${statusClass}">${esc(d.status)}</span></td>
      </tr>`;
  });
  document.getElementById('received-count').textContent = `(${list.length})`;
}

function loadReceivedStats() {
  fetch('admin_donations.php?action=received_stats')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const totalEl = document.getElementById('stat-donation-received-total');
      const countEl = document.getElementById('stat-donation-received-count');
      if (totalEl) totalEl.textContent = peso(data.total_received);
      if (countEl) countEl.textContent = data.donation_count;
    })
    .catch(() => {});
}

//  COMMUNITY ANNOUNCEMENTS (backed by admin_announcement.php)
let announcements = [];
let currentAnnouncementIndex = -1; // holds the announcement `id` (not an array index)
 
function loadAnnouncements() {
  fetch('admin_announcement.php?action=list')
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast('Failed to load announcements.', 'error'); return; }
      // mysqli returns numeric columns as strings once JSON-encoded — normalize
      // id to a real number so `.find(x => x.id === id)` lookups work reliably.
      announcements = data.announcements.map(a => ({ ...a, id: parseInt(a.id, 10) }));
      filterAnnouncements();
    })
    .catch(() => showToast('Could not reach the server.', 'error'));
}
 
function filterAnnouncements() {
  const q      = (document.getElementById('announcement-search')?.value || '').toLowerCase();
  const type   = document.getElementById('announcement-type-filter')?.value || '';
  const status = document.getElementById('announcement-status-filter')?.value || '';
  const list = announcements.filter(a =>
    (!q || a.title.toLowerCase().includes(q) || a.facility_name.toLowerCase().includes(q)) &&
    (!type || a.type === type) &&
    (!status || a.status.toLowerCase() === status.toLowerCase())
  );
  const tbody = document.getElementById('announcements-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No announcements found.</td></tr>';
  }
  list.forEach(a => {
    const statusLabel = a.status.charAt(0).toUpperCase() + a.status.slice(1);
    const badgeClass = a.status === 'approved' ? 'approved' : a.status === 'rejected' ? 'rejected' : 'pending';
    tbody.innerHTML += `
      <tr class="clickable" onclick="openAnnouncementDetail(${a.id})">
        <td><strong>${esc(a.title)}</strong></td>
        <td>${esc(a.type)}</td>
        <td>${esc(a.facility_name)}</td>
        <td>${esc(formatDateDisplay(a.event_date))}</td>
        <td><span class="badge ${badgeClass}">${esc(statusLabel)}</span></td>
        <td><span style="font-size:12px;color:#4E8DC0;font-weight:500">${a.status === 'pending' ? 'Review →' : 'View →'}</span></td>
      </tr>`;
  });
  document.getElementById('announcement-count').textContent = `(${list.length})`;
  const pending  = announcements.filter(a => a.status === 'pending').length;
  const approved = announcements.filter(a => a.status === 'approved').length;
  document.getElementById('badge-announcements').textContent = pending;
  document.getElementById('stat-announce-pending').textContent = pending;
  document.getElementById('stat-announce-approved').textContent = approved;
  document.getElementById('stat-announce-total').textContent = announcements.length;
}
 
function populateAnnouncementFacilities() {
  const sel = document.getElementById('afm-facility');
  if (!sel) return;
  const names = facilities.length ? facilities.map(f => f.name) : ['Metro Vet Clinic','Happy Paws Shelter','Fur Ever Home','Waggy Grooming Co.','PetGroomPH'];
  sel.innerHTML = names.map(n => `<option value="${esc(n)}">${esc(n)}</option>`).join('');
}
 
function openAddAnnouncement() {
  populateAnnouncementFacilities();
  document.getElementById('afm-title').textContent = 'New announcement';
  document.getElementById('afm-index').value = -1;
  document.getElementById('afm-title-input').value = '';
  document.getElementById('afm-type').value = 'Vaccination Drive';
  document.getElementById('afm-date').value = '';
  document.getElementById('afm-location').value = '';
  document.getElementById('afm-desc').value = '';
  document.getElementById('afm-status').value = 'Pending';
  openModal('announcementFormModal');
}
 
function openEditAnnouncement(id) {
  populateAnnouncementFacilities();
  const a = announcements.find(x => x.id === id);
  if (!a) return;
  document.getElementById('afm-title').textContent = 'Edit announcement';
  document.getElementById('afm-index').value = id;
  document.getElementById('afm-title-input').value = a.title;
  document.getElementById('afm-type').value = a.type;
  document.getElementById('afm-date').value = a.event_date;
  document.getElementById('afm-facility').value = a.facility_name;
  document.getElementById('afm-location').value = a.location;
  document.getElementById('afm-desc').value = a.description;
  document.getElementById('afm-status').value = a.status.charAt(0).toUpperCase() + a.status.slice(1);
  openModal('announcementFormModal');
}
 
function editAnnouncementFromDetail() {
  const id = currentAnnouncementIndex;
  closeModal('announcementDetailModal');
  openEditAnnouncement(id);
}
 
function saveAnnouncement() {
  const id    = parseInt(document.getElementById('afm-index').value);
  const title = document.getElementById('afm-title-input').value.trim();
  const date  = document.getElementById('afm-date').value;
  if (!title) { showToast('Please enter an event title.', 'error'); return; }
  if (!date)  { showToast('Please select an event date.', 'error'); return; }
 
  const body = new URLSearchParams({
    action: id === -1 ? 'create' : 'update',
    id: id === -1 ? '' : id,
    title,
    type: document.getElementById('afm-type').value,
    facility_name: document.getElementById('afm-facility').value,
    event_date: date,
    location: document.getElementById('afm-location').value.trim(),
    description: document.getElementById('afm-desc').value.trim(),
    status: document.getElementById('afm-status').value.toLowerCase(),
  });
 
  fetch('admin_announcement.php', { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message || 'Could not save announcement.', 'error'); return; }
      closeModal('announcementFormModal');
      showToast(`"${title}" saved.`);
      loadAnnouncements();
    })
    .catch(() => showToast('Could not reach the server.', 'error'));
}
 
function openAnnouncementDetail(id) {
  currentAnnouncementIndex = id;
  const a = announcements.find(x => x.id === id);
  if (!a) return;
  document.getElementById('adnm-title').textContent = a.title;
  document.getElementById('adnm-type').textContent = a.type;
  document.getElementById('adnm-facility').textContent = a.facility_name;
  document.getElementById('adnm-date').textContent = formatDateDisplay(a.event_date);
  document.getElementById('adnm-location').textContent = a.location || '—';
  document.getElementById('adnm-status').textContent = a.status.charAt(0).toUpperCase() + a.status.slice(1);
  document.getElementById('adnm-desc').textContent = a.description || '—';
  const approveBtn = document.getElementById('adnm-approve-btn');
  const rejectBtn  = document.getElementById('adnm-reject-btn');
  if (a.status !== 'pending') {
    approveBtn.style.display = 'none';
    rejectBtn.style.display  = 'none';
  } else {
    approveBtn.style.display = '';
    rejectBtn.style.display  = '';
  }
  openModal('announcementDetailModal');
}
 
function setAnnouncementStatus(status) {
  const id = currentAnnouncementIndex;
  const body = new URLSearchParams({ action: 'set_status', id, status: status.toLowerCase() });
  fetch('admin_announcement.php', { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message || 'Could not update announcement.', 'error'); return; }
      closeModal('announcementDetailModal');
      showToast(`Announcement has been ${status.toLowerCase()}.`, status === 'Rejected' ? 'error' : 'success');
      loadAnnouncements();
    })
    .catch(() => showToast('Could not reach the server.', 'error'));
}

//  IMAGE LIGHTBOX
// ══════════════════════════════════════════
function openLightbox(src) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox(e) {
  if (e) e.stopPropagation();
  document.getElementById('lightbox').classList.remove('open');
}

//  UTILITY
function esc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

//  PRIVATE VET / CLINIC VERIFICATION
// let privateVets = [];

// function loadPrivateVets() {
//   fetch('admin_private_vets.php?action=list')
//     .then(r => r.json())
//     .then(data => {
//       if (!data.success) { showToast('Failed to load private vets.', 'error'); return; }
//       privateVets = data.vets;
//       renderPrivateVets();
//     })
//     .catch(() => showToast('Could not reach the server to load private vets.', 'error'));
// }

// function pvBadgeClass(status) {
//   return status === 'verified' ? 'active' : status === 'rejected' ? 'rejected' : 'pending';
// }

// function renderPrivateVets() {
//   const pending = privateVets.filter(v => v.verification_status === 'pending');

//   const pendingTbody = document.getElementById('pv-pending-tbody');
//   pendingTbody.innerHTML = pending.length ? pending.map(v => `
//     <tr>
//       <td><strong>${esc(v.clinic_name)}</strong></td>
//       <td>${esc(v.name || v.username)}</td>
//       <td>${esc(v.license_number)}</td>
//       <td>${esc(v.contact || v.mobile || '—')}</td>
//       <td>${esc(v.created_at)}</td>
//       <td><button class="btn btn-primary btn-sm" onclick="openPvReview(${v.id})">Review</button></td>
//     </tr>`).join('') : '<tr><td colspan="6" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No clinics awaiting verification.</td></tr>';
//   document.getElementById('pv-pending-count').textContent = `(${pending.length})`;
//   document.getElementById('badge-privatevets').textContent = pending.length;

//   const allTbody = document.getElementById('pv-all-tbody');
//   allTbody.innerHTML = privateVets.length ? privateVets.map(v => `
//     <tr class="clickable" onclick="openPvReview(${v.id})">
//       <td><strong>${esc(v.clinic_name)}</strong></td>
//       <td>${esc(v.name || v.username)}</td>
//       <td>${esc(v.email)}</td>
//       <td><span class="badge ${pvBadgeClass(v.verification_status)}">${esc(v.verification_status)}</span></td>
//       <td>${esc(v.verified_at || '—')}</td>
//     </tr>`).join('') : '<tr><td colspan="5" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No private vets registered yet.</td></tr>';
//   document.getElementById('pv-all-count').textContent = `(${privateVets.length})`;
// }

// function openPvReview(id) {
//   const v = privateVets.find(x => x.id === id);
//   if (!v) return;

//   document.getElementById('pvm-title').textContent = v.clinic_name;
//   document.getElementById('pvm-clinic').textContent = v.clinic_name;
//   document.getElementById('pvm-vet').textContent = v.name || v.username;
//   document.getElementById('pvm-license').textContent = v.license_number;
//   document.getElementById('pvm-email').textContent = v.email;
//   document.getElementById('pvm-contact').textContent = v.contact || v.mobile || '—';
//   document.getElementById('pvm-address').textContent = v.address;
//   document.getElementById('pvm-specialization').textContent = v.specialization || '—';

//   const docEl = document.getElementById('pvm-license-doc');
//   docEl.innerHTML = v.license_document
//     ? `<a href="../${esc(v.license_document)}" target="_blank" style="color:#4E8DC0">View uploaded document →</a>`
//     : '—';

//   document.getElementById('pvm-reject-section').style.display = 'none';
//   document.getElementById('pvm-reject-reason').value = '';

//   const verifyBtn = document.getElementById('pvm-verify-btn');
//   const rejectBtn = document.getElementById('pvm-reject-btn');
//   const alreadyDecided = v.verification_status !== 'pending';

//   verifyBtn.style.display = alreadyDecided && v.verification_status === 'verified' ? 'none' : 'inline-flex';
//   verifyBtn.textContent = v.verification_status === 'rejected' ? 'Verify clinic' : 'Verify clinic';
//   verifyBtn.onclick = () => setPvStatus(v.id, 'verify');

//   rejectBtn.style.display = v.verification_status === 'rejected' ? 'none' : 'inline-flex';

//   openModal('pvReviewModal');
// }

// function handlePvRejectClick() {
//   const section = document.getElementById('pvm-reject-section');
//   if (section.style.display === 'none') {
//     section.style.display = 'block';
//     return;
//   }
//   const reason = document.getElementById('pvm-reject-reason').value.trim();
//   if (!reason) { showToast('Please provide a reason for rejecting.', 'error'); return; }
//   const title = document.getElementById('pvm-title').textContent;
//   const v = privateVets.find(x => x.clinic_name === title);
//   if (v) setPvStatus(v.id, 'reject', reason);
// }

// function setPvStatus(id, action, reason) {
//   fetch('admin_private_vets.php?action=' + action, {
//     method: 'POST',
//     headers: { 'Content-Type': 'application/json' },
//     body: JSON.stringify(action === 'reject' ? { id, reason } : { id }),
//   })
//   .then(r => r.json())
//   .then(data => {
//     if (!data.success) { showToast(data.message || 'Could not update clinic status.', 'error'); return; }
//     closeModal('pvReviewModal');
//     showToast(action === 'verify' ? 'Clinic verified.' : 'Clinic rejected.');
//     loadPrivateVets();
//   })
//   .catch(() => showToast('Could not reach the server.', 'error'));
// }

//  MEDICAL CASE REQUESTS
// let medicalCases = [];
// let flaggablePets = [];

// function loadMedicalCases() {
//   fetch('admin_medical_cases.php?action=list')
//     .then(r => r.json())
//     .then(data => {
//       if (!data.success) { showToast('Failed to load medical cases.', 'error'); return; }
//       medicalCases = data.cases;
//       renderMedicalCases();
//     })
//     .catch(() => showToast('Could not reach the server to load medical cases.', 'error'));
// }

// function mcBadgeClass(status) {
//   return status === 'approved' || status === 'completed' ? 'active'
//        : status === 'denied' ? 'rejected' : 'pending';
// }

// function renderMedicalCases() {
//   const pending = medicalCases.filter(c => c.status === 'pending');

//   const pendingTbody = document.getElementById('mc-pending-tbody');
//   pendingTbody.innerHTML = pending.length ? pending.map(c => `
//     <tr>
//       <td><strong>${esc(c.pet_name)}</strong> <span style="color:rgba(1,34,36,0.4);font-size:11px">(${esc(c.pet_type)})</span></td>
//       <td>${esc(c.clinic_name || c.vet_name)}</td>
//       <td style="max-width:260px">${esc((c.reason || '').slice(0, 90))}${(c.reason || '').length > 90 ? '…' : ''}</td>
//       <td>${esc(c.created_at)}</td>
//       <td><button class="btn btn-primary btn-sm" onclick="openMcReview(${c.id})">Review</button></td>
//     </tr>`).join('') : '<tr><td colspan="5" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No medical case requests awaiting a decision.</td></tr>';
//   document.getElementById('mc-pending-count').textContent = `(${pending.length})`;
//   document.getElementById('badge-medicalcases').textContent = pending.length;

//   const allTbody = document.getElementById('mc-all-tbody');
//   allTbody.innerHTML = medicalCases.length ? medicalCases.map(c => `
//     <tr class="${c.status === 'pending' ? 'clickable' : ''}" ${c.status === 'pending' ? `onclick="openMcReview(${c.id})"` : ''}>
//       <td><strong>${esc(c.pet_name)}</strong></td>
//       <td>${esc(c.clinic_name || c.vet_name)}</td>
//       <td><span class="badge ${mcBadgeClass(c.status)}">${esc(c.status)}</span></td>
//       <td>${esc(c.reviewed_at || '—')}</td>
//     </tr>`).join('') : '<tr><td colspan="4" style="text-align:center;padding:24px;color:rgba(1,34,36,0.4)">No medical cases yet.</td></tr>';
//   document.getElementById('mc-all-count').textContent = `(${medicalCases.length})`;
// }

// function openMcReview(id) {
//   const c = medicalCases.find(x => x.id === id);
//   if (!c) return;

//   document.getElementById('mcm-animal').textContent = `${c.pet_name} (${c.pet_type}, ${c.pet_breed})`;
//   document.getElementById('mcm-clinic').textContent = c.clinic_name || c.vet_name;
//   document.getElementById('mcm-reason').textContent = c.reason;
//   document.getElementById('mcm-notes').value = '';

//   const approveBtn = document.getElementById('mcm-approve-btn');
//   const denyBtn = document.getElementById('mcm-deny-btn');
//   const isPending = c.status === 'pending';

//   approveBtn.style.display = isPending ? 'inline-flex' : 'none';
//   denyBtn.style.display = isPending ? 'inline-flex' : 'none';
//   approveBtn.onclick = () => decideMedicalCase(c.id, 'approve');
//   denyBtn.onclick = () => decideMedicalCase(c.id, 'deny');

//   openModal('mcReviewModal');
// }

// function decideMedicalCase(id, action) {
//   const notes = document.getElementById('mcm-notes').value.trim();
//   fetch('admin_medical_cases.php?action=' + action, {
//     method: 'POST',
//     headers: { 'Content-Type': 'application/json' },
//     body: JSON.stringify({ id, notes }),
//   })
//   .then(r => r.json())
//   .then(data => {
//     if (!data.success) { showToast(data.message || 'Could not update the case.', 'error'); return; }
//     closeModal('mcReviewModal');
//     showToast(action === 'approve' ? 'Case approved — animal is now under vet care.' : 'Case denied.');
//     loadMedicalCases();
//   })
//   .catch(() => showToast('Could not reach the server.', 'error'));
// }

// function openFlagPetModal() {
//   const select = document.getElementById('fpm-pet-select');
//   select.innerHTML = '<option value="">Loading available animals…</option>';
//   openModal('flagPetModal');

//   fetch('admin_medical_cases.php?action=list_flaggable_pets')
//     .then(r => r.json())
//     .then(data => {
//       if (!data.success) { showToast('Failed to load animals.', 'error'); return; }
//       flaggablePets = data.pets;
//       select.innerHTML = flaggablePets.length
//         ? flaggablePets.map(p => `<option value="${p.id}">${esc(p.name)} — ${esc(p.type)}, ${esc(p.breed)}</option>`).join('')
//         : '<option value="">No available animals to flag right now</option>';
//     })
//     .catch(() => showToast('Could not reach the server.', 'error'));
// }

// function submitFlagPet() {
//   const petId = document.getElementById('fpm-pet-select').value;
//   if (!petId) { showToast('Please select an animal.', 'error'); return; }

//   fetch('admin_medical_cases.php?action=flag_pet', {
//     method: 'POST',
//     headers: { 'Content-Type': 'application/json' },
//     body: JSON.stringify({ pet_id: petId }),
//   })
//   .then(r => r.json())
//   .then(data => {
//     if (!data.success) { showToast(data.message || 'Could not flag this animal.', 'error'); return; }
//     closeModal('flagPetModal');
//     showToast('Animal flagged as needing treatment.');
//     loadMedicalCases();
//   })
//   .catch(() => showToast('Could not reach the server.', 'error'));
// }

//  LIVE RFID SCANS
let lastScanId = 0;
let liveScanReady = false;   

function scanCardHtml(s) {
  const on = v => Number(v) === 1;
  const med = (v, label) =>
    `<span class="badge ${on(v) ? 'active' : 'inactive'}">${label}${on(v) ? '' : ' — no'}</span>`;

  const history = (s.history || []).map(h => `
    <div class="scan-item">
      <div class="scan-dot"></div>
      <div>
        <div class="scan-text">${esc(h.event_type)}${h.note ? ' — ' + esc(h.note) : ''}</div>
        <div class="scan-meta">${esc(h.scanned_at)}</div>
      </div>
    </div>`).join('') || '<div class="scan-meta">No earlier scans.</div>';

  return `
    <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:16px">
      <div class="pet-ava" style="width:54px;height:54px;font-size:26px"><i class="ti ti-paw"></i></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:17px;font-weight:600">${esc(s.pet_name || 'Unassigned chip')}</div>
        <div style="font-size:12px;color:rgba(1,34,36,0.5);margin-bottom:8px">
          ${esc(s.pet_type || '')}${s.pet_breed ? ' · ' + esc(s.pet_breed) : ''}
        </div>
        <span class="rfid-chip"><i class="ti ti-id"></i>${esc(s.chip_uid)}</span>
        <span class="badge ${s.tag_status === 'active' ? 'active' : 'inactive'}" style="margin-left:6px">${esc(s.tag_status)}</span>
      </div>
      <div style="text-align:right;font-size:11px;color:rgba(1,34,36,0.45);white-space:nowrap">
        ${esc(s.scanned_at)}<br>${esc(s.facility_name || '—')}
      </div>
    </div>

    <div class="modal-section-title">Medical history</div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
      ${med(s.vaccinated, 'Vaccinated')}${med(s.dewormed, 'Dewormed')}${med(s.neutered, 'Neutered/spayed')}
    </div>
    <div class="info-row"><div class="info-label"><i class="ti ti-heartbeat"></i> Health status</div>
      <div class="info-val">${esc(s.health_status || '—')}</div></div>
    <div class="info-row"><div class="info-label"><i class="ti ti-notes"></i> Notes</div>
      <div class="info-val">${esc(s.medical_notes || '—')}</div></div>

    <div class="modal-section-title" style="margin-top:16px">Recent scans on this tag</div>
    ${history}`;
}

function showLiveScan(s, popup) {
  const body = document.getElementById('live-scan-body');
  if (body) body.innerHTML = scanCardHtml(s);

  const status = document.getElementById('live-scan-status');
  if (status) {
    status.textContent = 'Last scan: ' + s.scanned_at;
    status.style.color = '#4E8DC0';
    setTimeout(() => { status.style.color = 'rgba(1,34,36,0.4)'; }, 2000);
  }

  if (!popup) return;

  document.getElementById('rfid-scan-modal-body').innerHTML = scanCardHtml(s);
  const editBtn = document.getElementById('rsm-edit-btn');
  editBtn.onclick = () => { closeModal('rfidScanModal'); openEditRFID(s.tag_id); };
  openModal('rfidScanModal');
  showToast(`Scanned: ${s.pet_name || s.chip_uid}`);
  loadRFID();
}

function pollLiveScans() {
  fetch('rfid_live.php?since=' + lastScanId)
    .then(r => r.json())
    .then(d => {
      if (!d.success || !d.scan) return;
      lastScanId = parseInt(d.scan.scan_id, 10);
      showLiveScan(d.scan, liveScanReady);
      liveScanReady = true;
    })
    .catch(() => {});
}

/* ---------- LOGOUT ---------- */
const overlay = document.getElementById('logout-overlay');
document.getElementById('logout-btn').addEventListener('click', () => overlay.classList.add('show'));
document.getElementById('logout-cancel').addEventListener('click', () => overlay.classList.remove('show'));
document.getElementById('logout-confirm').addEventListener('click', () => {
  window.location.href = 'admin_logout.php';
});

//  ADMIN'S OWN PROFILE PHOTO (uses the same php/userprof.php as the user pages)
const ADMIN_PROFILE_API = '../php/userprof.php';
const ADMIN_INITIALS = <?= json_encode($initials) ?>;
let adminPhoto = null;        // server path, or a data: URL right after an upload
let adminPhotoBroken = false;

function renderAdminAvatar() {
  const src = adminPhotoBroken ? null : photoUrl(adminPhoto);
  const topbar = document.getElementById('admin-avatar');
  const preview = document.getElementById('asp-preview');
  topbar.innerHTML = src ? `<img src="${esc(src)}" alt="Admin" onerror="adminPhotoFailed(this)">` : esc(ADMIN_INITIALS);
  preview.innerHTML = src
    ? `<img src="${esc(src)}" alt="Admin" style="width:84px;height:84px;border-radius:50%;object-fit:cover" onerror="adminPhotoFailed(this)">`
    : `<div class="user-ava" style="background:#4E8DC0;width:84px;height:84px;font-size:26px">${esc(ADMIN_INITIALS)}</div>`;
  document.getElementById('asp-remove').style.display = adminPhoto ? '' : 'none';
}

function adminPhotoFailed(img) {
  console.warn('Admin profile photo failed to load:', img.getAttribute('src'));
  showToast('Photo could not be displayed: ' + img.getAttribute('src'), 'error');
  adminPhotoBroken = true;
  renderAdminAvatar();
}

function canLoadImage(url) {
  return new Promise(resolve => {
    const im = new Image();
    im.onload = () => resolve(true);
    im.onerror = () => resolve(false);
    im.src = url;
  });
}

// Ask the server what photo this account currently has (same call the user page makes).
async function fetchAdminPhoto() {
  try {
    const res  = await fetch(ADMIN_PROFILE_API + '?action=get&_=' + Date.now());
    const data = await res.json();
    return (data.success && data.user && data.user.photo) || null;
  } catch (err) { console.warn('Could not load admin photo.', err); return null; }
}

async function loadAdminPhoto() {
  adminPhoto = await fetchAdminPhoto();
  adminPhotoBroken = false;
  renderAdminAvatar();
}

function openAdminPhotoModal() { renderAdminAvatar(); openModal('adminSelfPhotoModal'); }

document.getElementById('asp-input').addEventListener('change', async function () {
  const file = this.files[0];
  if (!file) return;
  if (!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)) { showToast('Only JPEG, PNG, GIF or WEBP images are allowed.', 'error'); this.value = ''; return; }
  if (file.size > 5 * 1024 * 1024) { showToast('Image must be smaller than 5 MB.', 'error'); this.value = ''; return; }

  // Show the picked image immediately (like the user page does)
  const localUrl = await new Promise(r => { const fr = new FileReader(); fr.onload = e => r(e.target.result); fr.readAsDataURL(file); });
  adminPhoto = localUrl; adminPhotoBroken = false; renderAdminAvatar();

  const fd = new FormData();
  fd.append('action', 'upload_photo');
  fd.append('photo', file);
  try {
    const res  = await fetch(ADMIN_PROFILE_API, { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) { showToast(data.message || 'Photo upload failed.', 'error'); await loadAdminPhoto(); return; }

    // Don't trust the upload response's field names: re-read the saved photo from the server.
    const fresh = await fetchAdminPhoto();
    if (!fresh) {
      showToast('Uploaded, but the server did not return a photo path for this account.', 'error');
    } else if (await canLoadImage(photoUrl(fresh))) {
      adminPhoto = fresh; adminPhotoBroken = false; renderAdminAvatar();
      showToast('Profile photo updated.');
      loadUsers();
    } else {
      showToast('Saved, but the image is not reachable at: ' + photoUrl(fresh), 'error');
    }
  } catch (err) {
    showToast('Could not upload photo.', 'error');
  } finally {
    this.value = '';
  }
});

async function removeAdminPhoto() {
  const fd = new FormData();
  fd.append('action', 'remove_photo');
  try {
    const res  = await fetch(ADMIN_PROFILE_API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { adminPhoto = null; adminPhotoBroken = false; renderAdminAvatar(); showToast('Profile photo removed.'); loadUsers(); }
    else showToast(data.message || 'Could not remove photo.', 'error');
  } catch (err) { showToast('Could not reach the server.', 'error'); }
}

//  INIT
loadAdminPhoto();
loadFacilities();
loadUsers();
renderVerifications();
loadReports();
loadFeedback();
loadAdoption();
loadVetTransactions();
loadAdminNotifications();
setInterval(() => {
  loadAdminNotifications();
  loadAdoption();
  loadVetTransactions();
}, 20000);
loadRFID();
loadAnnouncements();
loadReceivedDonations();
loadReceivedStats();
renderDonations();
pollLiveScans();
setInterval(pollLiveScans, 2000);
</script>
</body>
</html>