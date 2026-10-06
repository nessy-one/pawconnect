<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'vet') {
  header("Location: ../index.html");
  exit();
}

const FACILITY_NAME = 'Angeles City Vet Office';

$vetName      = $_SESSION['vet_name'] ?? $_SESSION['name'] ?? 'Vet';
$facilityName = FACILITY_NAME;
$initials     = strtoupper(substr($vetName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PawConnect — Vet</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
:root{
  --blue:#4E8DC0;
  --blue-soft:rgba(78,141,192,0.15);
  --coral:#DA8063;
  --coral-soft:rgba(218,128,99,0.15);
  --olive:#B4B156;
  --olive-soft:rgba(180,177,86,0.2);
  --pink-soft:rgba(246,197,180,0.5);
  --bg:#F4F1E6;
  --surface:#fff;
  --text:#012224;
  --muted:rgba(1,34,36,0.5);
  --border:rgba(1,34,36,0.12);
}

*, *::before, *::after{box-sizing:border-box;margin:0;padding:0;}
body{
  font-family:'Poppins', system-ui, sans-serif;
  background:var(--bg);
  color:var(--text);
  min-height:100vh;
  display:flex;
}

/* ── Layout ── */
.app{display:flex;width:100%;min-height:100vh;}
.sidebar{
  width:220px;min-width:220px;background:var(--bg);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  position:sticky;top:0;height:100vh;overflow-y:auto;
}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}

/* ── Sidebar ── */
.sidebar-logo{
  padding:16px 14px 12px;
  display:flex;align-items:center;justify-content:center;
  border-bottom:1px solid rgba(1,34,36,0.1);
  margin-bottom:4px;
}
.sidebar-logo img{width:60px;height:60px;object-fit:contain;border-radius:50%;}
.logo-fallback{
  width:52px;height:52px;background:var(--blue);border-radius:50%;
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;
}
.nav-section{padding:18px 16px 6px;font-size:9px;text-transform:uppercase;letter-spacing:.1em;color:rgba(1,34,36,0.35);font-weight:600;}
.nav-item{
  display:flex;align-items:center;gap:10px;
  padding:8px 13px;font-size:12px;color:var(--text);
  cursor:pointer;border-radius:8px;margin:2px 8px;
  transition:background .15s,color .15s;font-weight:400;
  border:none;background:none;width:calc(100% - 16px);
  font-family:inherit;text-align:left;
}
.nav-item:hover{background:rgba(78,141,192,0.12);}
.nav-item.active{background:var(--blue-soft);color:var(--blue);font-weight:600;}
.nav-item i{font-size:17px;flex-shrink:0;}
.nav-item .left{display:flex;align-items:center;gap:10px;}
.nav-badge{
  margin-left:auto;background:var(--coral);color:#fff;
  font-size:10px;font-weight:600;padding:1px 7px;border-radius:10px;
}
.nav-badge.zero{background:rgba(1,34,36,0.08);color:var(--muted);}
.sidebar-footer{margin-top:20px;padding:16px 8px 18px;border-top:1px solid var(--border);}
.logout{
  display:flex;align-items:center;gap:10px;width:100%;
  padding:8px 13px;font-size:13px;color:var(--text);
  cursor:pointer;border-radius:8px;background:none;border:none;
  font-family:inherit;transition:background .15s,color .15s;
}
.logout:hover{background:rgba(218,128,99,0.12);color:#8f2800;}
.logout i{width:18px;text-align:center;font-size:14px;}

/* ── Topbar ── */
.topbar{
  background:var(--bg);border-bottom:1px solid rgba(1,34,36,0.1);
  padding:13px 24px;display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:10;flex-shrink:0;
}
.topbar-left{display:flex;align-items:center;gap:12px;}
.topbar-brand{display:flex;align-items:center;gap:9px;}
.topbar-mark{
  width:30px;height:30px;border-radius:50%;background:var(--blue);
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;
  flex-shrink:0;overflow:hidden;
}
.topbar-mark img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.topbar-brand-name{font-size:14.5px;font-weight:700;letter-spacing:-.01em;}
.topbar-divider{width:1px;height:20px;background:rgba(1,34,36,0.14);}
.topbar-crumb{font-size:12.5px;color:rgba(1,34,36,0.45);font-weight:500;}
.topbar-right{display:flex;align-items:center;gap:14px;}
.topbar-icon{
  width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(1,34,36,0.15);
  display:flex;align-items:center;justify-content:center;cursor:pointer;
  background:transparent;transition:background .15s;position:relative;
}
.topbar-icon:hover{background:rgba(78,141,192,0.1);}
.topbar-icon i{font-size:17px;color:var(--text);}
.notif-dot{
  width:8px;height:8px;background:var(--coral);border-radius:50%;
  position:absolute;top:0;right:0;border:2px solid var(--bg);
}
.notif-dot.hidden{display:none;}
.avatar{
  width:36px;height:36px;border-radius:50%;background:var(--blue);
  display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:600;color:#fff;
  overflow:hidden;flex-shrink:0;cursor:pointer;transition:box-shadow .15s;
}
.avatar:hover{box-shadow:0 0 0 3px rgba(78,141,192,0.25);}
.avatar img{width:100%;height:100%;object-fit:cover;display:block;}
.who{font-size:13px;color:rgba(1,34,36,0.55);font-weight:500;line-height:1.3;}
.who .role{display:block;font-size:11px;color:rgba(1,34,36,0.4);font-weight:400;}

/* ── Pages ── */
.content{padding:24px 26px 40px;overflow-y:auto;flex:1;background:var(--bg);}
.view{display:none;}
.view.active{display:block;}
.page-title{font-size:19px;font-weight:600;margin-bottom:4px;}
.page-sub{font-size:13px;color:var(--muted);font-weight:400;margin-bottom:22px;}

/* ── Stat cards ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;}
.stat-card{
  background:var(--surface);border:1px solid rgba(1,34,36,0.08);
  border-radius:14px;padding:18px;cursor:pointer;transition:box-shadow .15s ease;
}
.stat-card:hover{box-shadow:0 2px 10px rgba(1,34,36,.07);}
.stat-icon{
  width:40px;height:40px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  margin-bottom:12px;font-size:20px;
}
.ic-blue{background:var(--blue-soft);color:var(--blue);}
.ic-coral{background:var(--coral-soft);color:var(--coral);}
.ic-green{background:rgba(180,177,86,0.18);color:#8a862e;}
.ic-pink{background:var(--pink-soft);color:#b85a3a;}
.stat-label{font-size:11px;color:rgba(1,34,36,0.45);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;font-weight:500;}
.stat-value{font-size:28px;font-weight:600;line-height:1;}
.stat-meta{font-size:11px;color:rgba(1,34,36,0.45);margin-top:5px;}
.stat-meta.alert{color:#c0692e;}
.stat-meta.ok{color:#8a862e;}

/* ── Panels ── */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;}
.panel{
  background:var(--surface);border:1px solid rgba(1,34,36,0.08);
  border-radius:14px;overflow:hidden;margin-bottom:16px;
}
.panel-head{
  padding:14px 18px;border-bottom:1px solid rgba(1,34,36,0.07);
  display:flex;align-items:center;justify-content:space-between;gap:10px;
}
.panel-head .title{font-size:13px;font-weight:600;}
.panel-body{padding:16px 18px;}
.panel-action{
  font-size:11.5px;font-weight:500;color:var(--blue);cursor:pointer;
  background:none;border:none;font-family:inherit;
}
.panel-action:hover{text-decoration:underline;}
.empty-state{padding:24px;text-align:center;font-size:13px;color:rgba(1,34,36,0.4);}

/* ── Tables ── */
.rec-table{width:100%;border-collapse:collapse;font-size:12.5px;}
.rec-table th{
  padding:10px 16px;text-align:left;font-size:10px;font-weight:600;
  color:rgba(1,34,36,0.4);background:var(--bg);
  border-bottom:1px solid rgba(1,34,36,0.08);
  text-transform:uppercase;letter-spacing:.06em;
}
.rec-table td{padding:11px 16px;border-bottom:1px solid rgba(1,34,36,0.06);vertical-align:middle;}
.rec-table tr:last-child td{border-bottom:none;}

/* ── Badges / chips ── */
.badge, .status-chip, .pill{
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500;
  border:none;font-family:inherit;white-space:nowrap;
}
.status-chip.updated{background:var(--olive-soft);color:#6b6812;}
.status-chip.due{background:var(--coral-soft);color:#a04820;}
.status-chip.inactive{background:rgba(1,34,36,0.07);color:var(--muted);}
.pill.new{background:var(--coral-soft);color:#8f2800;}
.pill.ack{background:var(--olive-soft);color:#6b6812;}
.pill.pending{background:var(--coral-soft);color:#a04820;}
.pill.approved{background:var(--olive-soft);color:#6b6812;}
.pill.rejected{background:rgba(218,128,99,0.25);color:#8f2800;}
.pill-btn{cursor:pointer;font-weight:600;}
.pill-btn:hover{filter:brightness(0.95);}
.tag-chip{
  display:inline-flex;align-items:center;gap:6px;
  font-family:'Courier New', monospace;font-size:12px;
  background:rgba(1,34,36,0.06);padding:4px 10px;border-radius:7px;
  color:var(--text);letter-spacing:.02em;
}
.tag-chip::before{content:'\ea9f';font-family:'tabler-icons';color:var(--blue);font-size:13px;}
.tag-chip.inactive{color:var(--muted);}
.tag-chip.inactive::before{color:var(--muted);}
.pet-ava{
  width:34px;height:34px;border-radius:10px;background:var(--blue-soft);color:var(--blue);
  display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;
}

/* ── Buttons ── */
.btn-primary{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--blue);border:1px solid var(--blue);color:#fff;
  padding:8px 18px;border-radius:8px;font-size:13px;font-weight:500;
  cursor:pointer;font-family:inherit;transition:background .15s;
}
.btn-primary:hover{background:#3a78ab;}
.btn-primary:disabled{opacity:.6;cursor:default;}
.btn-mini{
  display:inline-flex;align-items:center;gap:5px;
  padding:5px 12px;border-radius:8px;font-size:12px;font-weight:500;
  cursor:pointer;font-family:inherit;border:1px solid transparent;
}
.btn-mini.cancel{background:var(--surface);border-color:rgba(1,34,36,0.15);color:var(--text);}
.btn-mini.cancel:hover{background:var(--bg);}
.row-actions{display:flex;gap:6px;align-items:center;}
.icon-btn{
  width:30px;height:30px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  background:var(--surface);border:1px solid rgba(1,34,36,0.15);color:var(--text);
  cursor:pointer;flex-shrink:0;transition:background .15s,border-color .15s;
}
.icon-btn:hover{background:var(--bg);}
.icon-btn i{font-size:15px;pointer-events:none;}
.icon-btn.danger{background:var(--coral-soft);border-color:var(--coral);color:#8f2800;}
.icon-btn.danger:hover{background:rgba(218,128,99,0.32);}
.icon-btn.positive{background:rgba(180,177,86,0.18);border-color:var(--olive);color:#6b6812;}
.icon-btn.positive:hover{background:rgba(180,177,86,0.32);}

/* ── Notifications list ── */
.notif-item{
  display:flex;gap:12px;align-items:flex-start;
  padding:12px 0;border-bottom:1px solid rgba(1,34,36,0.06);
}
.notif-item:first-child{padding-top:0;}
.notif-item:last-child{border-bottom:none;padding-bottom:0;}
.notif-icon{
  width:32px;height:32px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  background:var(--blue-soft);color:var(--blue);font-size:15px;
}
.notif-body{flex:1;}
.notif-title{font-size:12.5px;line-height:1.5;}
.notif-title b{font-weight:600;}
.notif-time{font-size:11px;color:rgba(1,34,36,0.4);margin-top:2px;}
.notif-actions{display:flex;flex-direction:column;align-items:flex-end;gap:6px;}

/* ── Due-soon list (dashboard) ── */
.due-item{padding:12px 0;border-bottom:1px solid rgba(1,34,36,0.06);}
.due-item:first-child{padding-top:0;}
.due-item:last-child{border-bottom:none;padding-bottom:0;}
.due-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;}
.due-name{font-size:13px;font-weight:600;}
.due-name span{font-size:11px;font-weight:400;color:rgba(1,34,36,0.45);}
.due-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.due-note{font-size:12px;color:rgba(1,34,36,0.6);}

/* ── Request items (announcements / transactions) ── */
.req-item{padding:13px 0;border-bottom:1px solid rgba(1,34,36,0.06);}
.req-item:first-child{padding-top:0;}
.req-item:last-child{border-bottom:none;padding-bottom:0;}
.req-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:5px;}
.req-title{font-size:13px;font-weight:600;}
.req-desc{font-size:12.5px;color:rgba(1,34,36,0.6);line-height:1.6;margin-bottom:6px;}
.req-meta{font-size:11px;color:rgba(1,34,36,0.4);}

/* ── Forms ── */
.form-group{margin-bottom:14px;}
.form-label{display:block;font-size:12px;font-weight:500;color:rgba(1,34,36,0.6);margin-bottom:5px;}
.form-input, .form-textarea, .form-select{
  width:100%;padding:10px 13px;border:1px solid rgba(1,34,36,0.15);border-radius:9px;
  font-size:13px;font-family:inherit;color:var(--text);background:var(--surface);
  outline:none;transition:border-color .15s;
}
.form-input:focus, .form-textarea:focus, .form-select:focus{border-color:var(--blue);}
.form-textarea{resize:vertical;min-height:80px;}
.btn-note{font-size:11.5px;color:var(--muted);margin-top:8px;}
.form-error{font-size:11.5px;color:#8f2800;margin-top:8px;margin-bottom:14px;display:none;}
.note-banner{
  background:var(--pink-soft);border:1px solid #F6C5B4;border-radius:9px;
  padding:11px 14px;font-size:12px;color:#7a3520;margin-bottom:16px;
}
.announce-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;}

/* ── Toast ── */
.toast{
  position:fixed;bottom:24px;right:24px;
  background:var(--text);color:#fff;
  padding:12px 20px;border-radius:10px;font-size:13px;font-weight:500;
  box-shadow:0 4px 16px rgba(1,34,36,0.2);
  z-index:9999;transform:translateY(80px);opacity:0;
  transition:all .3s ease;pointer-events:none;
}
.toast.show{transform:translateY(0);opacity:1;}

/* ── Overlays & modals ── */
.logout-overlay{
  position:fixed;inset:0;background:rgba(1,34,36,0.45);
  display:none;align-items:center;justify-content:center;z-index:200;
  padding:40px 20px;overflow-y:auto;
}
.logout-overlay.show{display:flex;}
.logout-card{
  background:var(--surface);border:1px solid rgba(1,34,36,0.1);border-radius:16px;
  padding:32px 36px;text-align:center;max-width:320px;
  box-shadow:0 20px 60px rgba(1,34,36,0.25);
}
.logout-card .mark{
  width:46px;height:46px;border-radius:50%;background:var(--blue-soft);color:var(--blue);
  display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:20px;
}
.logout-card h3{font-size:16px;font-weight:600;margin-bottom:6px;}
.logout-card p{font-size:12.5px;color:var(--muted);margin-bottom:18px;}
.modal-card{
  background:var(--surface);border:1px solid rgba(1,34,36,0.1);border-radius:16px;
  width:100%;max-width:460px;padding:22px 24px;text-align:left;
  max-height:86vh;overflow-y:auto;margin:auto;
  box-shadow:0 20px 60px rgba(1,34,36,0.25);
}
.modal-card h3{font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px;}
.modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:14px;}
.modal-head p{font-size:12px;color:var(--muted);margin-top:4px;}
.modal-close{background:none;border:none;cursor:pointer;font-size:18px;color:rgba(1,34,36,0.45);}
.modal-close:hover{color:var(--text);}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:4px;}
.current-record{
  font-size:12.5px;color:rgba(1,34,36,0.6);margin-top:10px;
  padding:9px 12px;background:var(--bg);border-radius:9px;
}
.history-list{margin-top:18px;border-top:1px solid rgba(1,34,36,0.08);padding-top:14px;}
.history-item{padding:9px 0;border-bottom:1px solid rgba(1,34,36,0.06);}
.history-item:last-child{border-bottom:none;padding-bottom:0;}
.history-note{font-size:12.5px;line-height:1.5;}
.history-meta{font-size:11px;color:rgba(1,34,36,0.4);margin-top:3px;display:flex;gap:8px;align-items:center;}
.section-title{
  font-size:10px;text-transform:uppercase;letter-spacing:.08em;
  color:rgba(1,34,36,0.4);font-weight:600;margin-bottom:10px;
}

/* ── Scrollbar ── */
::-webkit-scrollbar{width:6px;height:6px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:rgba(1,34,36,0.15);border-radius:3px;}

@media (max-width:1100px){
  .stats-grid{grid-template-columns:repeat(2,1fr);}
  .two-col, .announce-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="app">

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<nav class="sidebar" role="navigation" aria-label="Main navigation">
  <div class="sidebar-logo">
    <img src="logo.png" alt="PawConnect Logo"
      onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div class="logo-fallback" style="display:none"><i class="ti ti-paw"></i></div>
  </div>

  <div style="padding:6px 0;flex:1">
    <div class="nav-section">Main</div>
    <button class="nav-item active" data-view="dashboard" type="button">
      <i class="ti ti-layout-dashboard"></i> Dashboard
    </button>

    <div class="nav-section">Adoptions</div>
    <button class="nav-item" data-view="adoptions" type="button">
      <i class="ti ti-bell"></i> Adoption Notifications
      <span class="nav-badge" id="nav-badge-adoptions">0</span>
    </button>
    <button class="nav-item" data-view="transactions" type="button">
      <i class="ti ti-circle-check"></i> Successful Transactions
      <span class="nav-badge" id="nav-badge-transactions">0</span>
    </button>

    <div class="nav-section">Medical</div>
    <button class="nav-item" data-view="rfidmanagement" type="button">
      <i class="ti ti-id"></i> RFID Management
    </button>
    <button class="nav-item" data-view="animals" type="button">
      <i class="ti ti-paw"></i> Animals Under Care
    </button>

    <div class="nav-section">Outreach</div>
    <button class="nav-item" data-view="announcements" type="button">
      <i class="ti ti-speakerphone"></i> Announcement Requests
      <span class="nav-badge" id="nav-badge-announce">0</span>
    </button>
  </div>

  <div class="sidebar-footer">
    <button class="logout" id="logout-btn" type="button"><i class="ti ti-logout"></i> Logout</button>
  </div>
</nav>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="main">

  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-brand">
        <div class="topbar-mark">
          <img src="logo.png" alt="PawConnect logo"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <i class="ti ti-paw" style="display:none"></i>
        </div>
        <span class="topbar-brand-name">PawConnect</span>
      </div>
      <div class="topbar-divider"></div>
      <span class="topbar-crumb" id="breadcrumb-current">Vet Dashboard</span>
    </div>
    <div class="topbar-right">
      <div class="topbar-icon" id="bell-btn" title="Notifications">
        <i class="ti ti-bell"></i>
        <span class="notif-dot" id="bell-dot"></span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="avatar" id="vet-avatar" title="Change profile photo"><?= htmlspecialchars($initials) ?></div>
        <div class="who"><?= htmlspecialchars($vetName) ?><span class="role">Vet Office</span></div>
      </div>
    </div>
  </div>

  <div class="content">

    <!-- ===================== DASHBOARD VIEW ===================== -->
    <div class="view active" id="view-dashboard">
      <div class="page-title">Vet Dashboard</div>
      <div class="page-sub">Adoption notifications, medical records, and announcement requests for <?= htmlspecialchars($facilityName) ?>.</div>

      <div class="stats-grid">
        <div class="stat-card" data-goto="adoptions">
          <div class="stat-icon ic-blue"><i class="ti ti-bell"></i></div>
          <div class="stat-label">New adoption alerts</div>
          <div class="stat-value" id="stat-new-adoptions">0</div>
          <div class="stat-meta" id="stat-new-adoptions-meta">No new requests</div>
        </div>
        <div class="stat-card" data-goto="animals">
          <div class="stat-icon ic-green"><i class="ti ti-paw"></i></div>
          <div class="stat-label">Animals under care</div>
          <div class="stat-value" id="stat-animals-under-care">0</div>
          <div class="stat-meta ok" id="stat-animals-meta">All tagged</div>
        </div>
        <div class="stat-card" data-goto="rfidmanagement">
          <div class="stat-icon ic-coral"><i class="ti ti-vaccine"></i></div>
          <div class="stat-label">Records due for update</div>
          <div class="stat-value" id="stat-due-records">0</div>
          <div class="stat-meta alert">Needs medical entry</div>
        </div>
        <div class="stat-card" data-goto="announcements">
          <div class="stat-icon ic-pink"><i class="ti ti-speakerphone"></i></div>
          <div class="stat-label">Announcement requests</div>
          <div class="stat-value" id="stat-announce-requests">0</div>
          <div class="stat-meta alert">Awaiting admin approval</div>
        </div>
      </div>

      <div class="two-col">
        <div class="panel">
          <div class="panel-head">
            <span class="title">Adoption request notifications</span>
            <button class="panel-action" data-goto="adoptions" type="button">View all</button>
          </div>
          <div class="panel-body" id="dashboard-notif-preview"></div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <span class="title">RFID medical records — due soon</span>
            <button class="panel-action" data-goto="rfidmanagement" type="button">Open full list</button>
          </div>
          <div class="panel-body" id="dashboard-rfid-preview"></div>
        </div>
      </div>
    </div>

    <!-- ===================== ADOPTIONS VIEW ===================== -->
    <div class="view" id="view-adoptions">
      <div class="page-title">Adoption Notifications</div>
      <div class="page-sub">Review adoption requests routed to your clinic and confirm health clearances.</div>
      <div class="panel">
        <div class="panel-head"><span class="title">All notifications</span></div>
        <div class="panel-body" id="adoptions-full-list"></div>
      </div>
    </div>

    <!-- ===================== TRANSACTIONS VIEW ===================== -->
    <div class="view" id="view-transactions">
      <div class="page-title">Successful Transactions</div>
      <div class="page-sub">Report completed adoption hand-offs to the admin, with a photo for the record.</div>
      <div class="panel">
        <div class="panel-head">
          <span class="title">Reported transactions</span>
          <button class="panel-action" id="report-transaction-toggle" type="button">+ Report successful transaction</button>
        </div>
        <div class="panel-body" id="transactions-list"></div>
      </div>
    </div>

    <!-- ===================== RFID MANAGEMENT VIEW ===================== -->
    <div class="view" id="view-rfidmanagement">
      <div class="page-title">RFID Management</div>
      <div class="page-sub">Registered tags and medical records for animals tagged at your clinic, in one place.</div>
      <div class="panel">
        <div class="panel-head">
          <span class="title">Tagged animals</span>
          <button class="panel-action" id="rfidmgmt-register-btn" type="button">+ Register a new RFID tag</button>
        </div>
        <table class="rec-table">
          <thead>
            <tr>
              <th>Animal</th>
              <th>RFID tag</th>
              <th>Last update</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="rfidmgmt-table-body"></tbody>
        </table>
      </div>
    </div>

    <!-- ===================== ANIMALS VIEW ===================== -->
    <div class="view" id="view-animals">
      <div class="page-title">Animals Under Care</div>
      <div class="page-sub">Every animal still at your facility — tagged or not.</div>
      <div class="panel">
        <div class="panel-head"><span class="title">Animals in your care</span></div>
        <table class="rec-table">
          <thead>
            <tr><th>Animal</th><th>RFID tag</th><th>Adoption status</th><th>Tag status</th></tr>
          </thead>
          <tbody id="animals-table-body"></tbody>
        </table>
      </div>
    </div>

    <!-- ===================== ANNOUNCEMENTS VIEW ===================== -->
    <div class="view" id="view-announcements">
      <div class="page-title">Announcement Requests</div>
      <div class="page-sub">Propose community events. An admin reviews each request before it's published to pet owners.</div>
      <div class="panel">
        <div class="panel-head"><span class="title">New request</span></div>
        <div class="panel-body">
          <div class="announce-grid">
            <div>
              <div class="form-group">
                <label class="form-label">Event title</label>
                <input class="form-input" type="text" id="announce-title" placeholder="e.g. Free Spay &amp; Neuter Program — September">
              </div>
              <div class="form-group">
                <label class="form-label">Event type</label>
                <select class="form-select" id="announce-type">
                  <option>Vaccination Drive</option>
                  <option>Free Spay/Neuter</option>
                  <option>Pet Adoption Fair</option>
                  <option>Pet Blessing</option>
                  <option>Rabies Awareness Seminar</option>
                  <option>Free Veterinary Check-up</option>
                  <option>Animal Rescue Event</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Details</label>
                <textarea class="form-textarea" id="announce-details" placeholder="Describe the program, dates, and who it's for..."></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Proposed date</label>
                <input class="form-input" type="date" id="announce-date">
              </div>
              <div class="form-group">
                <label class="form-label">Location</label>
                <input class="form-input" type="text" id="announce-location" placeholder="e.g. Angeles City Vet Office">
              </div>
              <button class="btn-primary" id="announce-submit" type="button"><i class="ti ti-send"></i> Send request to admin</button>
              <div class="btn-note">An admin reviews your request before it's published to pet owners.</div>
              <div class="form-error" id="announce-error">Add a title and event date before sending.</div>
            </div>

            <div>
              <div class="section-title">Your requests</div>
              <div id="announce-list"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div><!-- end .main -->
</div><!-- end .app -->

<div class="toast" id="toast"></div>

<!-- Vet profile photo modal -->
<div class="logout-overlay" id="vet-photo-overlay">
  <div class="modal-card" style="max-width:360px;">
    <div class="modal-head">
      <h3><i class="ti ti-camera" style="color:var(--blue)"></i> My profile photo</h3>
      <button class="modal-close" id="vet-photo-close-x" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div style="text-align:center;">
      <div id="vet-photo-preview" style="display:flex;justify-content:center;margin-bottom:14px;"></div>
      <input type="file" id="vet-photo-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
      <button class="btn-primary" id="vet-photo-select" type="button" style="width:auto;padding:9px 16px;"><i class="ti ti-upload"></i> Select image</button>
      <button class="btn-mini cancel" id="vet-photo-remove" type="button" style="padding:9px 16px;color:#a04820;">Remove</button>
      <div class="btn-note" style="margin-top:12px;">JPEG, PNG, GIF or WEBP · Max 5 MB</div>
    </div>
  </div>
</div>

<!-- Report transaction modal -->
<div class="logout-overlay" id="transaction-overlay">
  <div class="modal-card" style="max-width:420px;">
    <div class="modal-head">
      <h3><i class="ti ti-circle-check" style="color:var(--blue)"></i> Report successful transaction</h3>
      <button class="modal-close" id="transaction-close-x" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-group">
      <label class="form-label">Adoption request</label>
      <select class="form-select" id="txn-reservation-select"></select>
      <div class="btn-note" id="txn-reservation-hint"></div>
    </div>
    <div class="form-group">
      <label class="form-label">Photo proof</label>
      <input class="form-input" type="file" accept="image/*" id="txn-photo-input" style="padding:8px 10px;">
      <div id="txn-photo-preview" style="display:none;margin-top:10px;">
        <img id="txn-photo-img" style="width:100%;max-height:180px;object-fit:cover;border-radius:10px;border:1px solid rgba(1,34,36,0.1);" alt="Transaction photo preview">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Notes</label>
      <textarea class="form-textarea" id="txn-notes-input" placeholder="Any details for the admin about the hand-off..." style="min-height:60px;"></textarea>
    </div>
    <div class="form-error" id="txn-error">Select an animal and attach a photo before sending.</div>
    <div class="modal-actions">
      <button class="btn-mini cancel" id="txn-cancel-btn" style="padding:9px 16px;" type="button">Cancel</button>
      <button class="btn-primary" id="txn-submit-btn" type="button">Submit report</button>
    </div>
  </div>
</div>

<!-- RFID Management: edit medical record + history modal -->
<div class="logout-overlay" id="rfidmgmt-edit-overlay">
  <div class="modal-card">
    <div class="modal-head">
      <div>
        <h3 id="rfidmgmt-edit-title">Update record</h3>
        <p id="rfidmgmt-edit-sub"></p>
        <div class="current-record" id="rfidmgmt-edit-current"></div>
      </div>
      <button class="modal-close" id="rfidmgmt-edit-close-x" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div id="rfidmgmt-edit-form" style="margin-top:14px;">
      <div class="form-group">
        <label class="form-label">Update note</label>
        <input class="form-input" type="text" id="rfidmgmt-edit-note" placeholder="e.g. Rabies vaccine — Aug 18, 2026">
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-select" id="rfidmgmt-edit-status">
          <option value="updated">Up to date</option>
          <option value="due">Due for update</option>
        </select>
      </div>
      <div class="form-error" id="rfidmgmt-edit-error">Add an update note before saving.</div>
      <div class="modal-actions">
        <button class="btn-mini cancel" id="rfidmgmt-edit-cancel" style="padding:9px 16px;" type="button">Cancel</button>
        <button class="btn-primary" id="rfidmgmt-edit-save" type="button"><i class="ti ti-device-floppy"></i> Save record</button>
      </div>
    </div>

    <div class="history-list">
      <div class="section-title">Record history</div>
      <div id="rfidmgmt-edit-history"></div>
    </div>
  </div>
</div>

<div class="logout-overlay" id="logout-overlay">
  <div class="logout-card">
    <div class="mark"><i class="ti ti-logout"></i></div>
    <h3>Log out of PawConnect?</h3>
    <p>You'll need to sign back in to access the vet portal.</p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button class="btn-mini cancel" id="logout-cancel" style="padding:9px 16px;" type="button">Cancel</button>
      <button class="btn-primary" id="logout-confirm" style="padding:9px 16px;" type="button">Log out</button>
    </div>
  </div>
</div>

<script>
(function(){

  // PawConnect currently runs a single vet clinic. Keep this in sync with
  // the FACILITY_NAME constant in vet_reservations.php.
  const FACILITY_NAME = <?= json_encode(FACILITY_NAME) ?>;

  const viewLabels = {
    dashboard: 'Vet Dashboard',
    adoptions: 'Adoption Notifications',
    transactions: 'Successful Transactions',
    rfidmanagement: 'RFID Management',
    animals: 'Animals Under Care',
    announcements: 'Announcement Requests'
  };

  const RESV_API = 'vet_reservations.php';
  const TXN_API  = 'vet_transactions.php';
  const ANNOUNCE_API = '/pawconnect/admin/admin_announcement.php';

  async function loadNotifications(){
    try{
      const res = await fetch(`${RESV_API}?action=list_forwarded`);
      const data = await res.json();
      if(data.success){
        state.notifications = data.reservations.map(r => ({
          id: r.id,
          pet: r.pet_name || 'Unknown pet',
          petInfo: `${r.pet_type || ''}${r.pet_breed ? ', ' + r.pet_breed : ''}`,
          person: r.applicant_name,
          time: timeAgoLabel((r.forwarded_at || '').replace(' ', 'T') || new Date().toISOString())
        }));
      } else {
        console.error('list_forwarded failed:', res.status, data.message);
        showToast(data.message ? `Couldn't load requests: ${data.message}` : 'Could not load adoption requests.');
      }
    }catch(err){
      console.error('Could not load adoption notifications.', err);
      showToast('Could not reach the server to load adoption requests.');
    }
    renderNotifications();
  }

  let state = {
    notifications: [], // loaded from the server via loadNotifications() — connected
    announcements: [], // loaded from admin_announcement.php via loadAnnouncements() — connected
    transactions: []   // loaded from the server via loadTransactions() — connected
  };

  // Medical records, keyed by rfid_tag_id (the same "id" admin_rfid.php
  // already returns per chip). Loaded from vet_records.php — see
  // loadMedicalRecords() further down.
  let medicalRecords = {};

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function showToast(msg){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(()=> t.classList.remove('show'), 2200);
  }

  /* ---------- NAVIGATION ---------- */
  function switchView(name){
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    const target = document.getElementById('view-' + name);
    if(target) target.classList.add('active');
    document.querySelectorAll('.nav-item').forEach(n => n.classList.toggle('active', n.dataset.view === name));
    document.getElementById('breadcrumb-current').textContent = viewLabels[name] || name;
    window.scrollTo({top:0, behavior:'smooth'});
  }

  document.querySelectorAll('.nav-item[data-view]').forEach(btn => {
    btn.addEventListener('click', () => switchView(btn.dataset.view));
  });
  document.querySelectorAll('[data-goto]').forEach(el => {
    el.addEventListener('click', () => switchView(el.dataset.goto));
  });

  /* ---------- RENDER: NOTIFICATIONS ---------- */
  function renderNotifItem(n){
    return `
      <div class="notif-item">
        <div class="notif-icon"><i class="ti ti-paw"></i></div>
        <div class="notif-body">
          <div class="notif-title"><b>${escapeHtml(n.person)}</b> requested to adopt <b>${escapeHtml(n.pet)}</b> (${escapeHtml(n.petInfo)}) — forwarded by admin.</div>
          <div class="notif-time">${escapeHtml(n.time)}</div>
        </div>
        <div class="notif-actions">
          <button class="pill ack pill-btn" data-decide="${n.id}" data-decision="approve" type="button">Approve</button>
          <button class="pill new pill-btn" data-decide="${n.id}" data-decision="deny" type="button">Deny</button>
        </div>
      </div>`;
  }

  function renderNotifications(){
    const full = document.getElementById('adoptions-full-list');
    const preview = document.getElementById('dashboard-notif-preview');
    if(state.notifications.length === 0){
      full.innerHTML = `<div class="empty-state">No adoption notifications right now.</div>`;
    } else {
      full.innerHTML = state.notifications.map(n => renderNotifItem(n)).join('');
    }
    const previewItems = state.notifications.slice(0, 3);
    preview.innerHTML = previewItems.length
      ? previewItems.map(n => renderNotifItem(n)).join('')
      : `<div class="empty-state">No adoption notifications right now.</div>`;

    document.querySelectorAll('[data-decide]').forEach(btn => {
      btn.addEventListener('click', () => decideNotif(parseInt(btn.dataset.decide, 10), btn.dataset.decision));
    });

    const newCount = state.notifications.length;
    document.getElementById('nav-badge-adoptions').textContent = newCount;
    document.getElementById('nav-badge-adoptions').classList.toggle('zero', newCount === 0);
    document.getElementById('stat-new-adoptions').textContent = newCount;
    document.getElementById('bell-dot').classList.toggle('hidden', newCount === 0);

    const newAdoptionsMeta = document.getElementById('stat-new-adoptions-meta');
    if(newAdoptionsMeta){
      newAdoptionsMeta.textContent = newCount > 0 ? 'Sent by admin' : 'No new requests';
      newAdoptionsMeta.classList.toggle('alert', newCount > 0);
      newAdoptionsMeta.classList.toggle('ok', newCount === 0);
    }
  }

  async function decideNotif(id, decision){
    try{
      const res = await fetch(`${RESV_API}?action=decide`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, decision })
      });
      const data = await res.json();
      if(!data.success){ showToast(data.message || 'Could not submit decision.'); return; }
      showToast(decision === 'approve' ? 'Approved — ready to report hand-off once completed.' : 'Adoption request denied.');
      loadNotifications();
    }catch(err){
      showToast('Could not reach the server.');
    }
  }

  /* ---------- RENDER: RFID MANAGEMENT ---------- */
  function statusChipHtml(status){
    return status === 'updated'
      ? `<span class="status-chip updated">Up to date</span>`
      : `<span class="status-chip due">Due for update</span>`;
  }

  // Adoption status of the *animal*, as tracked on the pet listing itself
  // (pet.status, joined in by admin_rfid.php as pet_status). This is
  // independent of the RFID chip's own active/deactivated/replaced state.
  function adoptionStatusChipHtml(petStatus){
    if(petStatus === 'adopted') return `<span class="status-chip inactive">Adopted</span>`;
    if(!petStatus) return `<span class="status-chip inactive">Unassigned</span>`;
    return `<span class="status-chip updated">Available</span>`;
  }

  const RECORDS_API = 'vet_records.php';

  async function loadMedicalRecords(){
    try{
      const res = await fetch(`${RECORDS_API}?action=list`);
      if(!res.ok){
        const bodyText = await res.text();
        console.error('vet_records.php list failed:', res.status, bodyText);
        showToast(`Could not load medical records (HTTP ${res.status}) — see console.`);
        return renderRfidManagement();
      }
      const data = await res.json();
      if(data.success){
        medicalRecords = {};
        (data.records || []).forEach(r => {
          const key = r.rfid_tag_id;
          if(!medicalRecords[key]) medicalRecords[key] = { note: null, status: 'due', history: [] };
          medicalRecords[key].history.push(r);
        });
        Object.values(medicalRecords).forEach(rec => {
          if(rec.history.length){
            rec.note = rec.history[0].note;
            rec.status = rec.history[0].status;
          }
        });
      } else {
        console.error('vet_records list failed:', data.message);
        showToast(data.message ? `Couldn't load medical records: ${data.message}` : 'Could not load medical records.');
      }
    }catch(err){
      console.warn('Could not load medical records from server.', err);
      showToast('Could not reach the server to load medical records.');
    }
    renderRfidManagement();
  }

  function formatRecordDate(iso){
    if(!iso) return '';
    const d = new Date(String(iso).replace(' ', 'T'));
    if(isNaN(d.getTime())) return String(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function getRecordInfo(chip){
    return medicalRecords[chip.id] || { note: 'No medical records yet', status: 'due', history: [] };
  }

  function petMetaLabel(type, breed){
    const parts = [type, breed].filter(Boolean).join(', ');
    return parts ? ' · ' + escapeHtml(parts) : '';
  }

  function renderRfidManagement(){
    const body = document.getElementById('rfidmgmt-table-body');
    if(!body) return;

    if(!rfidChips.length){
      body.innerHTML = `<tr><td colspan="5" class="empty-state">No chips registered yet. Use "+ Register a new RFID tag" to add one.</td></tr>`;
      updateRfidStats();
      return;
    }

    body.innerHTML = rfidChips.map(chip => {
      const rec = getRecordInfo(chip);
      const isActive = chip.status === 'active';
      const tagClass = isActive ? 'tag-chip' : 'tag-chip inactive';
      const statusHtml = isActive
        ? statusChipHtml(rec.status)
        : `<span class="status-chip inactive">${chip.status === 'replaced' ? 'Replaced' : 'Deactivated'}</span>`;
      // A tag that's still "active" on an animal whose listing already
      // says "adopted" is stale — the vet needs to know to deactivate it.
      const needsDeactivation = isActive && chip.pet_status === 'adopted';
      const adoptedWarningHtml = needsDeactivation
        ? `<div style="margin-top:6px;"><span class="status-chip due" title="This animal has been adopted — deactivate this tag.">Adopted, tag still active</span></div>`
        : '';
      return `
        <tr data-chip="${chip.id}">
          <td>
            <div style="display:flex;align-items:center;gap:9px">
              <div class="pet-ava"><i class="ti ti-paw"></i></div>
              <div>
                <strong>${escapeHtml(chip.pet_name || 'Unassigned')}</strong>
                <div style="font-size:11px;color:rgba(1,34,36,0.45)">${escapeHtml([chip.pet_type, chip.pet_breed].filter(Boolean).join(' · ')) || '—'}</div>
              </div>
            </div>
          </td>
          <td><span class="${tagClass}">${escapeHtml(chip.chip_uid)}</span></td>
          <td>${escapeHtml(rec.note)}</td>
          <td>${statusHtml}${adoptedWarningHtml}</td>
          <td>
            <div class="row-actions">
              <button class="icon-btn" data-history-chip="${chip.id}" title="View record history" type="button"><i class="ti ti-history"></i></button>
              <button class="icon-btn" data-edit-chip="${chip.id}" title="Edit medical record" type="button"><i class="ti ti-edit"></i></button>
              ${isActive
                ? `<button class="icon-btn danger" data-toggle-chip="${chip.id}" data-to="deactivated" title="Deactivate tag" type="button"><i class="ti ti-power"></i></button>`
                : chip.status === 'deactivated'
                  ? `<button class="icon-btn positive" data-toggle-chip="${chip.id}" data-to="active" title="Reactivate tag" type="button"><i class="ti ti-power"></i></button>`
                  : ''}
            </div>
          </td>
        </tr>`;
    }).join('');

    updateRfidStats();
  }

  // Event delegation for row actions: bound once, works no matter how many
  // times the table body's innerHTML is replaced by renderRfidManagement().
  document.getElementById('rfidmgmt-table-body').addEventListener('click', (e) => {
    const historyBtn = e.target.closest('[data-history-chip]');
    if(historyBtn){ openRfidEditModal(Number(historyBtn.dataset.historyChip), 'history'); return; }

    const editBtn = e.target.closest('[data-edit-chip]');
    if(editBtn){ openRfidEditModal(Number(editBtn.dataset.editChip), 'edit'); return; }

    const toggleBtn = e.target.closest('[data-toggle-chip]');
    if(toggleBtn){ toggleRfidChip(Number(toggleBtn.dataset.toggleChip), toggleBtn.dataset.to); return; }
  });

  function updateRfidStats(){
    const dueChips = rfidChips.filter(c => {
      if(c.status !== 'active') return false;
      const rec = getRecordInfo(c);
      return rec.status === 'due';
    });

    // Dashboard panel: records due for update. Rendered as a compact
    // stacked list (not a table) so it reads cleanly in the half-width
    // column it now shares with the adoption notifications panel.
    const preview = document.getElementById('dashboard-rfid-preview');
    if(preview){
      if(dueChips.length === 0){
        preview.innerHTML = `<div class="empty-state">No records are due right now — nice work.</div>`;
      } else {
        preview.innerHTML = dueChips.map(c => {
          const rec = getRecordInfo(c);
          return `
            <div class="due-item">
              <div class="due-top">
                <div class="due-name">${escapeHtml(c.pet_name || 'Unassigned')}<span>${petMetaLabel(c.pet_type, c.pet_breed)}</span></div>
                ${statusChipHtml(rec.status)}
              </div>
              <div class="due-meta">
                <span class="tag-chip">${escapeHtml(c.chip_uid)}</span>
                <span class="due-note">${escapeHtml(rec.note)}</span>
              </div>
            </div>`;
        }).join('');
      }
    }
    const dueStatEl = document.getElementById('stat-due-records');
    if(dueStatEl) dueStatEl.textContent = dueChips.length;
  }

  // "Animals Under Care" is sourced from the full `pet` list (allPets,
  // loaded via admin_rfid.php?action=list_pets — every animal, tagged or
  // not). "Under care" = still at the facility, i.e. its adoption listing
  // hasn't been marked "adopted" yet.
  function currentlyUnderCare(){
    return allPets.filter(p => p.status !== 'adopted');
  }

  function findActiveTagForPet(petId){
    return rfidChips.find(c => c.status === 'active' && Number(c.pet_id) === Number(petId));
  }

  function renderAnimals(){
    const body = document.getElementById('animals-table-body');
    if(!body) return;

    const underCare = currentlyUnderCare();

    body.innerHTML = underCare.length
      ? underCare.map(pet => {
          const tag = findActiveTagForPet(pet.id);
          return `
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:9px">
            <div class="pet-ava"><i class="ti ti-paw"></i></div>
            <div>
              <strong>${escapeHtml(pet.name || 'Unnamed')}</strong>
              <div style="font-size:11px;color:rgba(1,34,36,0.45)">${escapeHtml([pet.type, pet.breed].filter(Boolean).join(' · ')) || '—'}</div>
            </div>
          </div>
        </td>
        <td>${tag ? `<span class="tag-chip">${escapeHtml(tag.chip_uid)}</span>` : `<span style="color:rgba(1,34,36,0.4);font-size:12px;">No tag registered</span>`}</td>
        <td>${adoptionStatusChipHtml(pet.status)}</td>
        <td>${tag ? '<span class="status-chip updated">Active</span>' : '<span class="status-chip inactive">Not tagged</span>'}</td>
      </tr>`;
        }).join('')
      : `<tr><td colspan="4" class="empty-state">No animals currently under care.</td></tr>`;

    const statEl = document.getElementById('stat-animals-under-care');
    if(statEl) statEl.textContent = underCare.length;

    const untaggedCount = underCare.filter(pet => !findActiveTagForPet(pet.id)).length;
    const adoptedButActive = rfidChips.filter(c => c.status === 'active' && c.pet_status === 'adopted').length;
    const metaEl = document.getElementById('stat-animals-meta');
    if(metaEl){
      if(adoptedButActive > 0){
        metaEl.textContent = `${adoptedButActive} adopted animal${adoptedButActive === 1 ? '' : 's'} still tagged active`;
      } else if(untaggedCount > 0){
        metaEl.textContent = `${untaggedCount} of ${underCare.length} not yet tagged`;
      } else {
        metaEl.textContent = 'All tagged';
      }
      metaEl.classList.toggle('alert', adoptedButActive > 0);
      metaEl.classList.toggle('ok', adoptedButActive === 0);
    }
  }

  /* ---------- EDIT MODAL: medical record + history ---------- */
  let editingChipId = null;

  function openRfidEditModal(chipId, focus){
    const chip = rfidChips.find(c => Number(c.id) === Number(chipId));
    if(!chip) return;
    editingChipId = chipId;
    const rec = getRecordInfo(chip);
    const isHistoryMode = focus === 'history';

    document.getElementById('rfidmgmt-edit-title').textContent = isHistoryMode
      ? `Record history — ${chip.pet_name || 'Unassigned'}`
      : `Update record — ${chip.pet_name || 'Unassigned'}`;
    document.getElementById('rfidmgmt-edit-sub').textContent = `${chip.chip_uid}${chip.pet_type || chip.pet_breed ? ' · ' + [chip.pet_type, chip.pet_breed].filter(Boolean).join(', ') : ''}`;
    document.getElementById('rfidmgmt-edit-note').value = '';
    document.getElementById('rfidmgmt-edit-status').value = rec.status;
    document.getElementById('rfidmgmt-edit-error').style.display = 'none';
    document.getElementById('rfidmgmt-edit-current').innerHTML =
      rec.history.length
        ? `<b>Current:</b> ${escapeHtml(rec.note)} <span style="opacity:.7;">(${escapeHtml(rec.status === 'updated' ? 'Up to date' : 'Due for update')})</span>`
        : `No entries yet — this will be the first record.`;

    document.getElementById('rfidmgmt-edit-form').style.display = isHistoryMode ? 'none' : 'block';

    renderRecordHistory(rec);
    document.getElementById('rfidmgmt-edit-overlay').classList.add('show');

    if(!isHistoryMode){
      setTimeout(() => document.getElementById('rfidmgmt-edit-note').focus(), 50);
    }
  }

  function closeRfidEditModal(){
    document.getElementById('rfidmgmt-edit-overlay').classList.remove('show');
    editingChipId = null;
  }

  function renderRecordHistory(rec){
    const list = document.getElementById('rfidmgmt-edit-history');
    if(!rec.history || rec.history.length === 0){
      list.innerHTML = `<div class="empty-state" style="padding:14px 0;">No past entries yet.</div>`;
      return;
    }
    list.innerHTML = rec.history.map(h => `
      <div class="history-item">
        <div class="history-note">${escapeHtml(h.note)}</div>
        <div class="history-meta">
          <span>${escapeHtml(formatRecordDate(h.created_at))}${h.vet_name ? ' · ' + escapeHtml(h.vet_name) : ''}</span>
          ${statusChipHtml(h.status)}
        </div>
      </div>`).join('');
  }

  document.getElementById('rfidmgmt-edit-close-x').addEventListener('click', closeRfidEditModal);
  document.getElementById('rfidmgmt-edit-cancel').addEventListener('click', closeRfidEditModal);

  document.getElementById('rfidmgmt-edit-save').addEventListener('click', async () => {
    const chip = rfidChips.find(c => Number(c.id) === Number(editingChipId));
    if(!chip) return;
    const note = document.getElementById('rfidmgmt-edit-note').value.trim();
    const status = document.getElementById('rfidmgmt-edit-status').value;
    const errorEl = document.getElementById('rfidmgmt-edit-error');

    if(!note){
      errorEl.textContent = 'Add an update note before saving.';
      errorEl.style.display = 'block';
      return;
    }
    errorEl.style.display = 'none';

    const saveBtn = document.getElementById('rfidmgmt-edit-save');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    try{
      const res = await fetch(`${RECORDS_API}?action=create`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rfid_tag_id: chip.id, note, status })
      });
      const data = await res.json();
      if(!data.success){
        errorEl.textContent = data.message || 'Could not save the record.';
        errorEl.style.display = 'block';
        return;
      }
      await loadMedicalRecords();
      renderAnimals();
      document.getElementById('rfidmgmt-edit-note').value = '';
      renderRecordHistory(getRecordInfo(chip));
      showToast(`Record updated for ${chip.pet_name || 'this animal'}.`);
    }catch(err){
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.style.display = 'block';
    }finally{
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="ti ti-device-floppy"></i> Save record';
    }
  });

  /* ---------- ANNOUNCEMENTS (wired to admin_announcement.php) ---------- */
  function renderAnnouncements(){
    const list = document.getElementById('announce-list');
    if(state.announcements.length === 0){
      list.innerHTML = `<div class="empty-state">You haven't sent any requests yet.</div>`;
    } else {
      list.innerHTML = state.announcements.map(a => {
        const status = (a.status || 'pending');
        const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
        const statusClass = status === 'approved' ? 'approved' : status === 'rejected' ? 'rejected' : 'pending';
        return `
        <div class="req-item">
          <div class="req-top">
            <div class="req-title">${escapeHtml(a.title)}</div>
            <span class="pill ${statusClass}">${escapeHtml(statusLabel)}</span>
          </div>
          <div class="req-desc">${escapeHtml(a.description || '')}</div>
          <div class="req-meta">${escapeHtml(a.event_date || '')}${a.location ? ' · ' + escapeHtml(a.location) : ''}</div>
        </div>`;
      }).join('');
    }
    document.getElementById('nav-badge-announce').textContent = state.announcements.length;
    document.getElementById('nav-badge-announce').classList.toggle('zero', state.announcements.length === 0);
    document.getElementById('stat-announce-requests').textContent = state.announcements.length;
  }

  async function loadAnnouncements(){
    try{
      const res = await fetch(`${ANNOUNCE_API}?action=list&mine=1`);
      const data = await res.json();
      if(data.success){
        // Only show this clinic's own submissions.
        state.announcements = (data.announcements || []).filter(a => a.facility_name === FACILITY_NAME);
      } else {
        console.error('Announcement list failed:', data.message);
      }
    }catch(err){
      console.warn('Could not load announcements from server.', err);
    }
    renderAnnouncements();
  }

  document.getElementById('announce-submit').addEventListener('click', async () => {
    const title = document.getElementById('announce-title').value.trim();
    const type = document.getElementById('announce-type').value;
    const details = document.getElementById('announce-details').value.trim();
    const date = document.getElementById('announce-date').value;
    const location = document.getElementById('announce-location').value.trim() || FACILITY_NAME;
    const errorEl = document.getElementById('announce-error');

    if(!title || !date){
      errorEl.style.display = 'block';
      return;
    }
    errorEl.style.display = 'none';

    const submitBtn = document.getElementById('announce-submit');
    submitBtn.disabled = true;

    const body = new URLSearchParams({
      action: 'create',
      id: '',
      title,
      type,
      facility_name: FACILITY_NAME,
      event_date: date,
      location,
      description: details,
      status: 'pending',
    });

    try{
      const res = await fetch(ANNOUNCE_API, { method: 'POST', body });
      const data = await res.json();
      if(!data.success){
        showToast(data.message || 'Could not send the request.');
        return;
      }
      document.getElementById('announce-title').value = '';
      document.getElementById('announce-details').value = '';
      document.getElementById('announce-date').value = '';
      document.getElementById('announce-location').value = '';
      showToast('Request sent to admin for review.');
      loadAnnouncements();
    }catch(err){
      showToast('Could not reach the server.');
    }finally{
      submitBtn.disabled = false;
    }
  });

  /* ---------- SUCCESSFUL TRANSACTIONS ---------- */
  let selectedPhotoFile = null;
  let pendingReservations = [];

  function renderTransactions(){
    const list = document.getElementById('transactions-list');
    if(state.transactions.length === 0){
      list.innerHTML = `<div class="empty-state">No transactions reported yet. Use "+ Report successful transaction" once a hand-off is complete.</div>`;
    } else {
      list.innerHTML = state.transactions.map(t => {
        // adopter_name comes straight from the backend (the adoption
        // request's applicant_name). If it's missing or clearly truncated,
        // fall back to a safe label instead of a broken-looking line.
        const adopterLabel = (t.adopter && t.adopter.trim().length > 1) ? t.adopter.trim() : null;
        return `
        <div class="req-item" style="display:flex;gap:14px;align-items:flex-start;">
          ${t.photo ? `<img src="${escapeHtml(t.photo)}" alt="Proof for ${escapeHtml(t.animal)}" style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid rgba(1,34,36,0.1);flex-shrink:0;">` : ''}
          <div style="flex:1;">
            <div class="req-top">
              <div class="req-title">${escapeHtml(t.animal)}${adopterLabel ? ' → ' + escapeHtml(adopterLabel) : ''}</div>
              <span class="pill ${t.status === 'acknowledged' ? 'approved' : 'pending'}">${t.status === 'acknowledged' ? 'Acknowledged by admin' : 'Awaiting admin review'}</span>
            </div>
            ${t.notes ? `<div class="req-desc">${escapeHtml(t.notes)}</div>` : ''}
            <div class="req-meta">${escapeHtml(t.meta)}</div>
          </div>
        </div>`;
      }).join('');
    }
    document.getElementById('nav-badge-transactions').textContent = state.transactions.length;
    document.getElementById('nav-badge-transactions').classList.toggle('zero', state.transactions.length === 0);
  }

  function timeAgoLabel(dateStr){
    const then = new Date(dateStr.replace(' ', 'T'));
    const mins = Math.floor((Date.now() - then.getTime()) / 60000);
    if(mins < 1) return 'just now';
    if(mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if(hrs < 24) return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    return `${days}d ago`;
  }

  async function loadTransactions(){
    try{
      const res = await fetch(`${TXN_API}?action=list`);
      const data = await res.json();
      if(data.success){
        state.transactions = data.transactions.map(t => ({
          id: t.id,
          animal: t.animal_name,
          adopter: t.adopter_name,
          notes: t.notes,
          photo: t.photo_url,
          status: t.status,
          meta: `${t.status === 'acknowledged' ? 'Acknowledged' : 'Reported'} ${timeAgoLabel(t.created_at)}`
        }));
      } else {
        console.error('Transaction list failed:', res.status, data.message);
      }
    }catch(err){
      console.warn('Could not load transactions from server.', err);
    }
    renderTransactions();
  }

  async function loadPendingReservations(){
    const select = document.getElementById('txn-reservation-select');
    select.innerHTML = `<option value="">Loading…</option>`;
    try{
      const res = await fetch(`${RESV_API}?action=list_pending`);
      const data = await res.json();
      if(data.success){
        pendingReservations = data.reservations;
        if(!pendingReservations.length){
          select.innerHTML = `<option value="">No pending adoption requests right now</option>`;
          return;
        }
        select.innerHTML = pendingReservations.map(r => `
          <option value="${r.id}">${escapeHtml(r.pet_name || 'Unknown pet')} — ${escapeHtml(r.applicant_name)} (${escapeHtml(r.visit_date)})${r.has_proof ? ' [proof already attached]' : ''}</option>
        `).join('');
        updateReservationHint();
      } else {
        select.innerHTML = `<option value="">Could not load requests</option>`;
      }
    }catch(err){
      select.innerHTML = `<option value="">Could not reach the server</option>`;
    }
  }

  function updateReservationHint(){
    const id = document.getElementById('txn-reservation-select').value;
    const r = pendingReservations.find(x => String(x.id) === String(id));
    const hint = document.getElementById('txn-reservation-hint');
    if(!r){ hint.textContent = ''; return; }
    hint.textContent = r.has_proof
      ? 'This request already has proof attached — submitting will add another photo.'
      : `${r.pet_type || ''} ${r.pet_breed ? '· ' + r.pet_breed : ''} · applicant contact: ${r.applicant_contact || '—'}`;
  }

  const txnOverlay = document.getElementById('transaction-overlay');
  function openTransactionModal(){
    document.getElementById('txn-notes-input').value = '';
    document.getElementById('txn-photo-input').value = '';
    document.getElementById('txn-photo-preview').style.display = 'none';
    document.getElementById('txn-error').style.display = 'none';
    selectedPhotoFile = null;
    loadPendingReservations();
    txnOverlay.classList.add('show');
  }
  function closeTransactionModal(){ txnOverlay.classList.remove('show'); }

  document.getElementById('report-transaction-toggle').addEventListener('click', openTransactionModal);
  document.getElementById('transaction-close-x').addEventListener('click', closeTransactionModal);
  document.getElementById('txn-cancel-btn').addEventListener('click', closeTransactionModal);
  document.getElementById('txn-reservation-select').addEventListener('change', updateReservationHint);

  document.getElementById('txn-photo-input').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if(!file) { selectedPhotoFile = null; return; }
    selectedPhotoFile = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
      document.getElementById('txn-photo-img').src = ev.target.result;
      document.getElementById('txn-photo-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('txn-submit-btn').addEventListener('click', async () => {
    const reservationId = document.getElementById('txn-reservation-select').value;
    const notes = document.getElementById('txn-notes-input').value.trim();
    const errorEl = document.getElementById('txn-error');
    if(!reservationId || !selectedPhotoFile){
      errorEl.textContent = 'Select an adoption request and attach a photo before sending.';
      errorEl.style.display = 'block';
      return;
    }
    errorEl.style.display = 'none';

    const submitBtn = document.getElementById('txn-submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('reservation_id', reservationId);
    formData.append('notes', notes);
    formData.append('photo', selectedPhotoFile);

    try{
      const res = await fetch(TXN_API, { method: 'POST', body: formData });
      const data = await res.json();
      if(data.success){
        state.transactions.unshift({
          id: data.transaction.id,
          animal: data.transaction.animal_name,
          adopter: data.transaction.adopter_name,
          notes: data.transaction.notes,
          photo: data.transaction.photo_path,
          status: data.transaction.status,
          meta: 'Reported just now'
        });
        closeTransactionModal();
        renderTransactions();
        switchView('transactions');
        showToast('Transaction report sent to admin.');
      } else {
        errorEl.textContent = data.message || 'Could not send the report.';
        errorEl.style.display = 'block';
      }
    }catch(err){
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.style.display = 'block';
    }finally{
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit report';
    }
  });

  /* ---------- BELL ---------- */
  document.getElementById('bell-btn').addEventListener('click', () => {
    switchView('adoptions');
  });

  /* ---------- RFID CHIP REGISTRY (real chips, shared with admin) ---------- */
  const RFID_API = '/pawconnect/admin/admin_rfid.php';
  let rfidChips = [];
  // Every animal at the facility, tagged or not — powers "Animals Under
  // Care" and doubles as the pick-list source for "+ Register a new RFID tag".
  let allPets = [];

  async function loadRfidRegistry(){
    try{
      const res = await fetch(`${RFID_API}?action=list`);
      const data = await res.json();
      if(data.success) rfidChips = data.tags;
      else showToast(data.message || 'Could not load RFID chips.');
    }catch(err){
      showToast('Could not reach the server for RFID chips.');
    }
    renderRfidManagement();
    renderAnimals();
  }

  async function loadAllPets(){
    try{
      const res = await fetch(`${RFID_API}?action=list_pets`);
      const data = await res.json();
      if(data.success) allPets = data.pets;
      else showToast(data.message || 'Could not load animal records.');
    }catch(err){
      showToast('Could not reach the server for animal records.');
    }
    renderAnimals();
  }

  async function toggleRfidChip(id, status){
    try{
      const res = await fetch(`${RFID_API}?action=set_status`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
      });
      const data = await res.json();
      if(!data.success){ showToast(data.message || 'Could not update chip.'); return; }
      showToast(status === 'active' ? 'Chip reactivated.' : 'Chip deactivated.');
      loadRfidRegistry();
    }catch(err){
      showToast('Could not reach the server.');
    }
  }

  document.getElementById('rfidmgmt-register-btn').addEventListener('click', async () => {
    await loadAllPets();
    const chip = prompt('New chip ID (from the reader):');
    if(!chip) return;
    const petList = allPets.map(p => `${p.id}: ${p.name} (${p.type}, ${p.breed})`).join('\n');
    const petIdRaw = prompt(`Enter the animal's ID:\n${petList}`);
    const petId = parseInt(petIdRaw, 10);
    if(!petId) return;
    try{
      const res = await fetch(`${RFID_API}?action=register`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chip_uid: chip.trim(), pet_id: petId })
      });
      const data = await res.json();
      if(!data.success){ showToast(data.message || 'Could not register chip.'); return; }
      showToast('Chip registered.');
      loadRfidRegistry();
    }catch(err){
      showToast('Could not reach the server.');
    }
  });

  /* ---------- LOGOUT ---------- */
  const overlay = document.getElementById('logout-overlay');
  document.getElementById('logout-btn').addEventListener('click', () => overlay.classList.add('show'));
  document.getElementById('logout-cancel').addEventListener('click', () => overlay.classList.remove('show'));
  document.getElementById('logout-confirm').addEventListener('click', () => {
    window.location.href = 'vet_logout.php';
  });

  /* ---------- MY PROFILE PHOTO (same php/userprof.php as the user pages) ---------- */
  const PROFILE_API = '../php/userprof.php';
  const VET_INITIALS = <?= json_encode($initials) ?>;
  let vetPhoto = null;            // server path, or a data: URL right after an upload
  let vetPhotoBroken = false;

  function vetPhotoUrl(path){
    if(!path) return null;
    if(/^(https?:|data:|\/\/)/.test(path)) return path;
    // Saved paths are root-based, e.g. "/pawconnect/php/uploads/profile_photos/6.jpg?v=1".
    // Older rows were saved without the project folder ("/php/uploads/..."). This page sits
    // one folder below the project root, so work out that root from the page's own URL
    // ("/pawconnect/vet/x.php" -> "/pawconnect") and add it only if it's missing.
    const root = location.pathname.replace(/\/[^\/]*\/[^\/]*$/, '');
    let p = path.charAt(0) === '/' ? path : '/' + path.replace(/^(\.\.\/)+/, '');
    if(root && p.indexOf(root + '/') !== 0) p = root + p;
    return p;
  }

  function renderVetAvatar(){
    const src = vetPhotoBroken ? null : vetPhotoUrl(vetPhoto);
    document.getElementById('vet-avatar').innerHTML = src
      ? `<img src="${escapeHtml(src)}" alt="Profile" id="vet-avatar-img">`
      : escapeHtml(VET_INITIALS);
    document.getElementById('vet-photo-preview').innerHTML = src
      ? `<img src="${escapeHtml(src)}" alt="Profile" id="vet-preview-img" style="width:84px;height:84px;border-radius:50%;object-fit:cover;">`
      : `<div class="avatar" style="width:84px;height:84px;font-size:26px;cursor:default;">${escapeHtml(VET_INITIALS)}</div>`;
    document.getElementById('vet-photo-remove').style.display = vetPhoto ? '' : 'none';
    ['vet-avatar-img','vet-preview-img'].forEach(id => {
      const im = document.getElementById(id);
      if(im) im.addEventListener('error', () => {
        console.warn('Vet profile photo failed to load:', im.getAttribute('src'));
        showToast('Photo could not be displayed: ' + im.getAttribute('src'));
        vetPhotoBroken = true; renderVetAvatar();
      }, {once:true});
    });
  }

  function canLoadImage(url){
    return new Promise(resolve => {
      const im = new Image();
      im.onload = () => resolve(true);
      im.onerror = () => resolve(false);
      im.src = url;
    });
  }

  // Ask the server what photo this account currently has (same call the user page makes).
  async function fetchVetPhoto(){
    try{
      const res  = await fetch(PROFILE_API + '?action=get&_=' + Date.now());
      const data = await res.json();
      return (data.success && data.user && data.user.photo) || null;
    }catch(err){ console.warn('Could not load profile photo.', err); return null; }
  }

  async function loadVetPhoto(){
    vetPhoto = await fetchVetPhoto();
    vetPhotoBroken = false;
    renderVetAvatar();
  }

  const vetPhotoOverlay = document.getElementById('vet-photo-overlay');
  document.getElementById('vet-avatar').addEventListener('click', () => { renderVetAvatar(); vetPhotoOverlay.classList.add('show'); });
  document.getElementById('vet-photo-close-x').addEventListener('click', () => vetPhotoOverlay.classList.remove('show'));
  vetPhotoOverlay.addEventListener('click', e => { if(e.target === vetPhotoOverlay) vetPhotoOverlay.classList.remove('show'); });
  document.getElementById('vet-photo-select').addEventListener('click', () => document.getElementById('vet-photo-input').click());

  document.getElementById('vet-photo-input').addEventListener('change', async function(){
    const file = this.files[0];
    if(!file) return;
    if(!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)){ showToast('Only JPEG, PNG, GIF or WEBP images are allowed.'); this.value=''; return; }
    if(file.size > 5*1024*1024){ showToast('Image must be smaller than 5 MB.'); this.value=''; return; }

    // Show the picked image immediately (like the user page does)
    const localUrl = await new Promise(r => { const fr = new FileReader(); fr.onload = e => r(e.target.result); fr.readAsDataURL(file); });
    vetPhoto = localUrl; vetPhotoBroken = false; renderVetAvatar();

    const fd = new FormData();
    fd.append('action','upload_photo');
    fd.append('photo', file);
    try{
      const res  = await fetch(PROFILE_API, {method:'POST', body:fd});
      const data = await res.json();
      if(!data.success){ showToast(data.message || 'Photo upload failed.'); await loadVetPhoto(); return; }

      // Don't trust the upload response's field names: re-read the saved photo from the server.
      const fresh = await fetchVetPhoto();
      if(!fresh){
        showToast('Uploaded, but the server did not return a photo path for this account.');
      }else if(await canLoadImage(vetPhotoUrl(fresh))){
        vetPhoto = fresh; vetPhotoBroken = false; renderVetAvatar();
        showToast('Profile photo updated.');
      }else{
        showToast('Saved, but the image is not reachable at: ' + vetPhotoUrl(fresh));
      }
    }catch(err){ showToast('Could not upload photo.'); }
    finally{ this.value=''; }
  });

  document.getElementById('vet-photo-remove').addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action','remove_photo');
    try{
      const res  = await fetch(PROFILE_API, {method:'POST', body:fd});
      const data = await res.json();
      if(data.success){ vetPhoto = null; vetPhotoBroken = false; renderVetAvatar(); showToast('Profile photo removed.'); }
      else showToast(data.message || 'Could not remove photo.');
    }catch(err){ showToast('Could not reach the server.'); }
  });

  /* ---------- INIT ---------- */
  loadVetPhoto();
  loadNotifications();
  loadAnnouncements();
  loadTransactions();
  loadRfidRegistry();
  loadAllPets();
  loadMedicalRecords();
})();
</script>

</body>
</html>