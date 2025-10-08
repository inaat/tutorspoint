<?php
// 📄 File: teacher-registration.php
// 📂 Location: public_html/wp-content/themes/astra-child/General/

function teacher_registration_form() {
    ob_start();
    ?>

<div class="teacher-registration-form">
    <h2>👨‍🏫 Teacher Registration</h2>

    <!-- Profile Image Upload with Camera Icon -->
    <div class="profile-pic-wrapper">
        <img id="profilePreview" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/default-user.png" alt="Your Picture">
        <div class="upload-options">
            <button type="button" onclick="document.getElementById('uploadLocal').click()">📁 Upload</button>
            <button type="button" onclick="openCamera()">📷 Camera</button>
        </div>
        <input type="file" id="uploadLocal" accept="image/*" style="display:none;" onchange="previewImage(event)">
    </div>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>

        <div id="qualifications-section"><?php
// 📄 File: public_html/wp-content/themes/astra-child/General/teacher-registration.php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['teacher_register'])) {
    global $wpdb;

    // Upload profile picture
    $photo_url = '';
    if (!empty($_FILES['photo']['name'])) {
        $upload = wp_handle_upload($_FILES['photo'], ['test_form' => false]);
        if (isset($upload['url'])) {
            $photo_url = esc_url_raw($upload['url']);
        }
    }

    // Insert into wpC_teachers_main
    $wpdb->insert('wpC_teachers_main', [
        'full_name' => sanitize_text_field($_POST['full_name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'photo' => $photo_url,
        'status' => 0
    ]);
    $teacher_id = $wpdb->insert_id;

    // Insert qualifications
    if (!empty($_POST['qualification'])) {
        foreach ($_POST['qualification'] as $i => $q) {
            $wpdb->insert('wpC_teacher_qualifications', [
                'teacher_id' => $teacher_id,
                'degree' => sanitize_text_field($q),
                'university' => sanitize_text_field($_POST['university'][$i]),
                'country' => sanitize_text_field($_POST['country'][$i]),
                'year' => intval($_POST['year'][$i])
            ]);
        }
    }

    echo "<p style='color:green;'>✅ Registration submitted. Awaiting admin approval.</p>";
}
?>

<div class="teacher-register-wrapper">
  <h2>📚 Teacher Registration</h2>

  <form method="post" enctype="multipart/form-data">
    <div class="photo-section">
      <div class="photo-frame">
        <img id="preview-pic" src="https://via.placeholder.com/100" alt="Preview">
        <label for="photo" class="camera-icon">📷</label>
        <input type="file" name="photo" id="photo" accept="image/*" capture="environment" hidden>
      </div>
    </div>

    <div class="tabbed-section">
      <div class="tabs">
        <button type="button" class="tab active" onclick="switchTab('info')">Personal Info</button>
        <button type="button" class="tab" onclick="switchTab('qual')">Qualifications</button>
      </div>

      <div id="tab-info" class="tab-content active">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
      </div>

      <div id="tab-qual" class="tab-content">
        <div id="qualifications-container">
          <div class="qualification-row">
            <input type="text" name="qualification[]" placeholder="Degree" required>
            <input type="text" name="university[]" placeholder="University" required>
            <input type="text" name="country[]" placeholder="Country" required>
            <input type="number" name="year[]" placeholder="Year" required>
          </div>
        </div>
        <button type="button" class="add-qual" onclick="addQualification()">➕ Add Qualification</button>
      </div>
    </div>

    <button type="submit" name="teacher_register" class="submit-btn">Register</button>
  </form>
</div>

<style>
.teacher-register-wrapper {
  max-width: 600px;
  margin: auto;
  padding: 20px;
  font-family: Arial, sans-serif;
}
.teacher-register-wrapper h2 {
  text-align: center;
  margin-bottom: 20px;
}
.photo-section {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}
.photo-frame {
  position: relative;
}
#preview-pic {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #0ABAB5;
}
.camera-icon {
  position: absolute;
  bottom: 0;
  right: 0;
  background: #0ABAB5;
  color: white;
  padding: 4px 6px;
  border-radius: 50%;
  cursor: pointer;
}
.tabbed-section .tabs {
  display: flex;
  justify-content: center;
  margin-bottom: 10px;
}
.tabbed-section .tab {
  padding: 10px 20px;
  cursor: pointer;
  border: none;
  background: #eee;
  margin-right: 5px;
}
.tabbed-section .tab.active {
  background: #0ABAB5;
  color: white;
}
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
input[type="text"], input[type="email"], input[type="number"] {
  width: 100%;
  padding: 10px;
  margin-bottom: 10px;
}
.qualification-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.qualification-row input {
  flex: 1 1 22%;
}
.add-qual {
  background: #eee;
  padding: 6px 12px;
  border: none;
  cursor: pointer;
  margin-top: 10px;
}
.submit-btn {
  width: 100%;
  padding: 12px;
  background: #0ABAB5;
  color: white;
  border: none;
  font-size: 16px;
  margin-top: 20px;
  border-radius: 5px;
  cursor: pointer;
}
</style>

<script>
function switchTab(tab) {
  document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  document.querySelector('.tab[data-tab="' + tab + '"]').classList.add('active');
  document.getElementById('tab-' + tab).classList.add('active');
}

document.getElementById("photo").addEventListener("change", function(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById("preview-pic").src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
});

function addQualification() {
  const container = document.getElementById("qualifications-container");
  const row = document.createElement("div");
  row.className = "qualification-row";
  row.innerHTML = `
    <input type="text" name="qualification[]" placeholder="Degree" required>
    <input type="text" name="university[]" placeholder="University" required>
    <input type="text" name="country[]" placeholder="Country" required>
    <input type="number" name="year[]" placeholder="Year" required>
  `;
  container.appendChild(row);
}
</script>
<?php
/**
 * Template Name: Teacher Registration
 */
get_header();
?>

<style>
.teacher-registration-wrapper {
  max-width: 800px;
  margin: 40px auto;
  padding: 20px;
  background: #ffffff;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(0,0,0,0.1);
  font-family: 'Segoe UI', sans-serif;
}
.teacher-registration-wrapper h2 {
  text-align: center;
  margin-bottom: 30px;
}
.profile-pic-container {
  text-align: center;
  position: relative;
  margin-bottom: 20px;
}
.profile-pic {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #0ABAB5;
}
.camera-icon {
  position: absolute;
  bottom: 0;
  right: calc(50% - 60px);
  background: #0ABAB5;
  padding: 6px;
  border-radius: 50%;
  cursor: pointer;
}
.tabs {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}
.tab-btn {
  padding: 10px 20px;
  margin: 0 5px;
  cursor: pointer;
  background: #eee;
  border: none;
  border-radius: 5px;
}
.tab-btn.active {
  background: #0ABAB5;
  color: #fff;
}
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
input, select {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 5px;
  border: 1px solid #ccc;
}
.qualifications-group {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}
.add-qualification-btn {
  background: #0ABAB5;
  color: white;
  padding: 5px 12px;
  border: none;
  border-radius: 50%;
  font-size: 20px;
  cursor: pointer;
}
button[type="submit"] {
  background: #0ABAB5;
  color: white;
  padding: 12px 20px;
  border: none;
  border-radius: 5px;
  width: 100%;
  cursor: pointer;
  font-size: 16px;
}
</style>

<div class="teacher-registration-wrapper">
  <h2>👨‍🏫 Teacher Registration</h2>

  <form method="post" enctype="multipart/form-data" action="">
    <div class="profile-pic-container">
      <img id="previewPic" class="profile-pic" src="https://via.placeholder.com/120" alt="Profile Picture">
      <label class="camera-icon">
        📷
        <input type="file" name="profile_pic" accept="image/*" capture="user" style="display:none" onchange="previewImage(this)">
      </label>
    </div>

    <div class="tabs">
      <button type="button" class="tab-btn active" onclick="switchTab('info')">Personal Info</button>
      <button type="button" class="tab-btn" onclick="switchTab('qual')">Qualifications</button>
    </div>

    <div id="tab-info" class="tab-content active">
      <input type="text" name="full_name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="tel" name="phone" placeholder="Phone Number" required>
    </div>

    <div id="tab-qual" class="tab-content">
      <div id="qualifications">
        <div class="qualifications-group">
          <input type="text" name="qualification[]" placeholder="Degree">
          <input type="text" name="university[]" placeholder="University">
          <input type="text" name="country[]" placeholder="Country">
        </div>
      </div>
      <div style="text-align:right;">
        <button type="button" class="add-qualification-btn" onclick="addQualification()">+</button>
      </div>
    </div>

    <button type="submit" name="submit_teacher">Register</button>
  </form>
</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(tabC => tabC.classList.remove('active'));
  document.querySelector(`.tab-btn[onclick="switchTab('${tab}')"]`).classList.add('active');
  document.getElementById(`tab-${tab}`).classList.add('active');
}
function addQualification() {
  const container = document.getElementById('qualifications');
  const div = document.createElement('div');
  div.classList.add('qualifications-group');
  div.innerHTML = `
    <input type="text" name="qualification[]" placeholder="Degree">
    <input type="text" name="university[]" placeholder="University">
    <input type="text" name="country[]" placeholder="Country">
  `;
  container.appendChild(div);
}
function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewPic').src = e.target.result;
    }
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php get_footer(); ?>

            <label>Qualifications</label>
            <div class="qualification-row">
                <input type="text" name="qualification[]" placeholder="Qualification" required>
                <input type="text" name="university[]" placeholder="University" required>
                <input type="text" name="country[]" placeholder="Country" required>
                <button type="button" class="add-row">➕</button>
            </div>
        </div>

        <button type="submit" name="submit_teacher">Register</button>
    </form>
</div>

<style>
.teacher-registration-form {
    max-width: 600px;
    margin: auto;
    padding: 20px;
}
.profile-pic-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 10px;
}
.profile-pic-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #ddd;
}
.upload-options {
    position: absolute;
    bottom: 0;
    width: 100%;
    background: rgba(0,0,0,0.6);
    color: #fff;
    text-align: center;
    display: none;
}
.profile-pic-wrapper:hover .upload-options {
    display: block;
}
.form-group {
    margin-bottom: 15px;
}
.qualification-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}
.qualification-row input {
    flex: 1;
}
</style>

<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('profilePreview').src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}

function openCamera() {
    alert("Camera integration coming soon.");
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.add-row').addEventListener('click', function () {
        const row = this.closest('.qualification-row');
        const newRow = row.cloneNode(true);
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        row.parentNode.appendChild(newRow);
    });
});
</script>

    <?php
    return ob_get_clean();
}
?>

