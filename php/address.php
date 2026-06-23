<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Page</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

body { background: #F4F1E6; }

.container { display: flex; height: 100vh; }

/* ── SIDEBAR ─────────────────────────────────────────────── */
.sidebar {
  width: 220px;
  flex-shrink: 0;
  padding: 24px 16px;
  border-right: 1px solid #ddd;
  background: #F4F1E6;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}

.logo-circle {
  width: 52px; height: 52px;
  border-radius: 50%;
  background-color: #3b8fc4;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  margin-bottom: 20px;
  flex-shrink: 0;
}
.logo-circle img { width: 52px; height: 52px; object-fit: cover; }

.menu-item {
  padding: 11px 14px;
  border-radius: 8px;
  cursor: pointer;
  display: flex; align-items: center; gap: 10px;
  text-decoration: none;
  color: #012224;
  font-size: 13px; font-weight: 500;
  transition: background 0.15s, color 0.15s;
}
.menu-item:hover { background: #e0ddd2; }
.menu-item.active { background: #AFC4D6; color: #012224; }
.menu-item i { width: 18px; text-align: center; font-size: 14px; }

.sidebar-divider { height: 1px; background: #ddd; margin: 8px 0; }

/* ── MAIN ─────────────────────────────────────────────────── */
.main { flex: 1; padding: 20px 28px; overflow-y: auto; }

/* ── TOPBAR ───────────────────────────────────────────────── */
.topbar {
  display: flex; justify-content: flex-end; align-items: center;
  gap: 18px; margin-bottom: 20px;
}
.topbar a { color: #012224; text-decoration: none; font-size: 18px; transition: opacity 0.15s; }
.topbar a:hover { opacity: 0.65; }

.topbar-user { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; color: #012224; }
.topbar-user .avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  background: #ddd;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; color: #555;
  overflow: hidden;
}
.topbar-user .avatar img { width: 100%; height: 100%; object-fit: cover; }

/* ── CARD ─────────────────────────────────────────────────── */
.card { background: #fff; padding: 24px; border-radius: 10px; display: flex; gap: 30px; }

/* ── FORM SECTION ─────────────────────────────────────────── */
.form-section { flex: 2; }
.form-section h2 { font-size: 18px; font-weight: 600; color: #012224; margin-bottom: 20px; }

.form-group { display: flex; align-items: center; margin-bottom: 15px; }
.form-group label { width: 140px; font-size: 13.5px; color: #333; flex-shrink: 0; }
.form-group input[type="text"],
.form-group input[type="email"] {
  flex: 1; padding: 9px 10px;
  border: 1px solid #ccc; border-radius: 6px;
  font-family: 'Poppins', sans-serif; font-size: 13px;
  transition: border-color 0.15s;
}
.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus { outline: none; border-color: #4E8DC0; }

.gender { display: flex; gap: 10px; flex-wrap: wrap; }
.gender label {
  width: auto; border: 1px solid #ccc; padding: 7px 15px;
  border-radius: 20px; cursor: pointer; font-size: 13px;
  display: flex; align-items: center; gap: 6px;
  transition: background 0.15s, border-color 0.15s;
}
.gender label:hover { background: #f0f0f0; }
.gender input[type="radio"] { accent-color: #4E8DC0; }

.btn {
  margin-top: 20px; padding: 9px 20px;
  background: #4E8DC0; color: white; border: none; border-radius: 6px;
  cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 500;
  transition: background 0.15s;
}
.btn:hover { background: #3a7aad; }
.btn:disabled { background: #9ab8d0; cursor: not-allowed; }

/* ── IMAGE SECTION ────────────────────────────────────────── */
.image-section {
  flex: 1; border-left: 1px solid #ccc; padding-left: 30px;
  display: flex; flex-direction: column; align-items: center; padding-top: 10px;
}

.profile-pic {
  width: 120px; height: 120px;
  background: #ddd; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 40px; color: #888; overflow: hidden; margin-bottom: 16px;
  position: relative;
}
.profile-pic img { width: 100%; height: 100%; object-fit: cover; }
.profile-pic .loading-overlay {
  position: absolute; inset: 0;
  background: rgba(255,255,255,0.7);
  display: none; align-items: center; justify-content: center;
  border-radius: 50%;
}
.profile-pic .loading-overlay.active { display: flex; }
.profile-pic .loading-overlay i { font-size: 22px; color: #4E8DC0; animation: spin 1s linear infinite; }

@keyframes spin { to { transform: rotate(360deg); } }

/* Hidden real file input */
#photoFileInput { display: none; }

.upload-btn {
  padding: 8px 16px; border: 1px solid #ccc; border-radius: 6px;
  cursor: pointer; background: #f5f5f5; font-size: 13px;
  font-family: 'Poppins', sans-serif; transition: background 0.15s;
  user-select: none;
}
.upload-btn:hover { background: #e8e8e8; }

.remove {
  margin-top: 10px; font-size: 12px; color: gray;
  cursor: pointer; text-decoration: underline;
}
.remove:hover { color: #c0392b; }

/* ── LOADING SKELETON ─────────────────────────────────────── */
.skeleton {
  background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
  border-radius: 4px; height: 36px;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* ── POPUP ────────────────────────────────────────────────── */
.popup {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.3);
  display: none; align-items: center; justify-content: center; z-index: 200;
}
.popup-box {
  background: white; padding: 25px 40px; border-radius: 10px;
  text-align: center; animation: pop 0.3s ease;
}
.popup-box i { font-size: 40px; color: #4E8DC0; margin-bottom: 10px; display: block; }
.popup-box p { font-size: 14px; color: #333; }

@keyframes pop {
  from { transform: scale(0.7); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}
</style>
</head>

<body>
<div class="container">

  <!-- SIDEBAR -->
  <div class="sidebar">
    <a href="dashboard.html">
      <div class="logo-circle">
        <img src="image/logo.png" alt="PawConnect Logo">
      </div>
    </a>

    <a href="profile.html" class="menu-item active">
      <i class="fa-regular fa-user"></i> My Account
    </a>
    <a href="addres.html" class="menu-item">
      <i class="fa-solid fa-location-dot"></i> My Addresses
    </a>
    <a href="donation.html" class="menu-item">
      <i class="fa-solid fa-hand-holding-heart"></i> My Donations
    </a>
    <a href="report.html" class="menu-item">
      <i class="fa-solid fa-bug"></i> My Reports
    </a>
    <a href="save.html" class="menu-item">
      <i class="fa-regular fa-heart"></i> Saves
    </a>
    <a href="notifications.html" class="menu-item">
      <i class="fa-regular fa-bell"></i> Notifications
    </a>

    <div class="sidebar-divider"></div>

    <a href="login.html" class="menu-item">
      <i class="fa-solid fa-right-from-bracket"></i> Log out
    </a>
  </div>

  <!-- MAIN -->
  <div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
      <a href="notifications.html" title="Notifications"><i class="fa-regular fa-bell"></i></a>
      <a href="save.html" title="Saves"><i class="fa-regular fa-heart"></i></a>
      <div class="topbar-user">
        <div class="avatar" id="topbarAvatar">
          <i class="fa-regular fa-user"></i>
        </div>
        <span id="topbarUsername">User</span>
      </div>
    </div>

    <!-- CARD -->
    <div class="card">
      <div class="form-section">
        <h2>Profile</h2>

        <div class="form-group">
          <label>Username</label>
          <input type="text" id="inputUsername" placeholder="Username">
        </div>

        <div class="form-group">
          <label>Name</label>
          <input type="text" id="inputName" placeholder="Name">
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" id="inputEmail" placeholder="Email">
        </div>

        <div class="form-group">
          <label>Mobile Number</label>
          <input type="text" id="inputMobile" placeholder="+">
        </div>

        <div class="form-group">
          <label>Gender</label>
          <div class="gender">
            <label><input type="radio" name="gender" value="Female"> Female</label>
            <label><input type="radio" name="gender" value="Male"> Male</label>
            <label><input type="radio" name="gender" value="Other"> Other</label>
          </div>
        </div>

        <button class="btn" id="saveBtn" onclick="saveChanges()">SAVE CHANGES</button>
      </div>

      <!-- IMAGE SECTION -->
      <div class="image-section">
        <div class="profile-pic" id="profilePicContainer">
          <i class="fa-regular fa-user" id="profilePlaceholderIcon"></i>
          <img id="profilePicImg" src="" alt="Profile" style="display:none;">
          <div class="loading-overlay" id="photoLoadingOverlay">
            <i class="fa-solid fa-spinner"></i>
          </div>
        </div>

        <!-- Hidden file input -->
        <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/gif,image/webp">

        <div class="upload-btn" onclick="document.getElementById('photoFileInput').click()">
          Select image
        </div>
        <div class="remove" id="removePhotoBtn" onclick="removePhoto()" style="display:none;">
          Remove
        </div>
      </div>
    </div>

  </div>
</div>

<!-- POPUP -->
<div id="popup" class="popup">
  <div class="popup-box">
    <i id="popupIcon" class="fa-solid fa-circle-check"></i>
    <p id="popupText">Changes saved successfully!</p>
  </div>
</div>

<script>
  // ── CONFIG ────────────────────────────────────────────────
  const API = 'profile_api.php'; // Path to PHP file

  // ── HELPERS ───────────────────────────────────────────────
  function showPopup(message, isError = false) {
    const popup     = document.getElementById('popup');
    const popupText = document.getElementById('popupText');
    const popupIcon = document.getElementById('popupIcon');

    popupText.textContent = message;
    if (isError) {
      popupIcon.className   = 'fa-solid fa-circle-exclamation';
      popupIcon.style.color = '#DA8063';
    } else {
      popupIcon.className   = 'fa-solid fa-circle-check';
      popupIcon.style.color = '#4E8DC0';
    }
    popup.style.display = 'flex';
    setTimeout(() => { popup.style.display = 'none'; }, 2000);
  }

  function setProfilePhoto(url) {
    const img         = document.getElementById('profilePicImg');
    const placeholder = document.getElementById('profilePlaceholderIcon');
    const removeBtn   = document.getElementById('removePhotoBtn');
    const topbarAv    = document.getElementById('topbarAvatar');

    if (url) {
      img.src           = url;
      img.style.display = 'block';
      placeholder.style.display = 'none';
      removeBtn.style.display   = 'block';

      // Sync topbar avatar
      topbarAv.innerHTML = `<img src="${url}" alt="avatar">`;
    } else {
      img.style.display         = 'none';
      placeholder.style.display = '';
      removeBtn.style.display   = 'none';
      topbarAv.innerHTML        = '<i class="fa-regular fa-user"></i>';
    }
  }

  function setPhotoLoading(on) {
    document.getElementById('photoLoadingOverlay').classList.toggle('active', on);
  }

  // ── LOAD PROFILE ON PAGE LOAD ─────────────────────────────
  async function loadProfile() {
    try {
      const res  = await fetch(`${API}?action=get`);
      const data = await res.json();

      if (!data.success) {
        showPopup(data.message || 'Failed to load profile.', true);
        return;
      }

      const u = data.user;
      document.getElementById('inputUsername').value = u.username || '';
      document.getElementById('inputName').value     = u.name     || '';
      document.getElementById('inputEmail').value    = u.email    || '';
      document.getElementById('topbarUsername').textContent = u.username || 'User';

      // Gender (if stored)
      if (u.gender) {
        const radios = document.querySelectorAll('input[name="gender"]');
        radios.forEach(r => { r.checked = (r.value === u.gender); });
      }

      // Photo
      setProfilePhoto(u.photo || null);

    } catch (err) {
      console.error(err);
      showPopup('Could not connect to server.', true);
    }
  }

  // ── SAVE CHANGES ─────────────────────────────────────────
  async function saveChanges() {
    const username = document.getElementById('inputUsername').value.trim();
    const name     = document.getElementById('inputName').value.trim();
    const email    = document.getElementById('inputEmail').value.trim();
    const mobile   = document.getElementById('inputMobile').value.trim();
    const genderEl = document.querySelector('input[name="gender"]:checked');
    const gender   = genderEl ? genderEl.value : '';

    if (!username || !name || !email) {
      showPopup('Username, name, and email are required!', true);
      return;
    }

    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled     = true;
    saveBtn.textContent  = 'Saving…';

    try {
      const res  = await fetch(`${API}?action=update`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ username, name, email, mobile, gender }),
      });
      const data = await res.json();

      if (data.success) {
        // Update topbar username live
        document.getElementById('topbarUsername').textContent = username;
        showPopup('Changes saved successfully!');
      } else {
        showPopup(data.message || 'Failed to save changes.', true);
      }
    } catch (err) {
      console.error(err);
      showPopup('Could not connect to server.', true);
    } finally {
      saveBtn.disabled    = false;
      saveBtn.textContent = 'SAVE CHANGES';
    }
  }

  // ── PHOTO UPLOAD ──────────────────────────────────────────
  document.getElementById('photoFileInput').addEventListener('change', async function () {
    const file = this.files[0];
    if (!file) return;

    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
      showPopup('Only JPEG, PNG, GIF, or WEBP images are allowed.', true);
      this.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      showPopup('Image must be smaller than 5 MB.', true);
      this.value = '';
      return;
    }

    setPhotoLoading(true);

    const formData = new FormData();
    formData.append('action', 'upload_photo');
    formData.append('photo', file);

    try {
      const res  = await fetch(API, { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        setProfilePhoto(data.photo);
        showPopup('Profile photo updated!');
      } else {
        showPopup(data.message || 'Photo upload failed.', true);
      }
    } catch (err) {
      console.error(err);
      showPopup('Could not upload photo.', true);
    } finally {
      setPhotoLoading(false);
      this.value = ''; 
    }
  });

  // ── REMOVE PHOTO ──────────────────────────────────────────
  async function removePhoto() {
    if (!confirm('Remove your profile photo?')) return;

    setPhotoLoading(true);

    try {
      const formData = new FormData();
      formData.append('action', 'remove_photo');

      const res  = await fetch(API, { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        setProfilePhoto(null);
        showPopup('Profile photo removed.');
      } else {
        showPopup(data.message || 'Could not remove photo.', true);
      }
    } catch (err) {
      console.error(err);
      showPopup('Could not connect to server.', true);
    } finally {
      setPhotoLoading(false);
    }
  }

  // ── INIT ──────────────────────────────────────────────────
  loadProfile();
</script>

</body>
</html>