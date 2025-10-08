<?php
function teacher_registration_form_shortcode() {
    ob_start(); ?>

    <div class="teacher-reg-wrapper">
      <div class="teacher-reg-card">
        <form method="post" enctype="multipart/form-data" id="teacherRegForm">

          <!-- Profile + Tabs Row -->
          <div class="profile-section">
            <div class="profile-pic-container">
              <img id="previewImage" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/default-avatar.png" alt="Profile Picture">
              <div class="upload-icons">
                <span class="icon-btn" id="uploadFromFile" title="Upload from Device">📁</span>
                <span class="icon-btn" id="captureFromCamera" title="Capture from Camera">📸</span>
              </div>
              <input type="file" id="profilePicInput" accept="image/*" hidden>
            </div>
            <div class="tab-buttons-inline">
              <button type="button" class="tab-btn active" data-tab="personal">Personal Info</button>
              <button type="button" class="tab-btn" data-tab="qualifications">Qualifications</button>
            </div>
          </div>

          <!-- Personal Info Tab -->
          <div class="tab-content active" id="tab-personal">
            <div class="multi-input-row">
              <input type="text" name="full_name" placeholder="Full Name" required>
              <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="multi-input-row">
              <input type="tel" name="phone" placeholder="Phone Number" required>
              <input type="text" name="whatsapp" placeholder="WhatsApp Number">
              <input type="text" name="country" placeholder="Country">
            </div>
            <div class="multi-input-row">
              <input type="text" name="bank_name" placeholder="Bank Name">
              <input type="text" name="iban" placeholder="Bank Account Number / IBAN">
            </div>
            <div class="note-submit-row">
              <textarea name="objective_note" maxlength="2000" placeholder="Brief Objective" required></textarea>
              <button type="submit" class="submit-btn-inline">Register</button>
            </div>
          </div>

          <!-- Qualifications Tab -->
          <div class="tab-content" id="tab-qualifications">
            <div id="qualification-wrapper">
              <div class="qualification-row">
                <input type="text" name="qualification[]" placeholder="Degree">
                <input type="text" name="university[]" placeholder="University">
                <input type="text" name="country[]" placeholder="Country">
                <input type="text" name="year[]" placeholder="Year">
                <input type="text" name="grade_or_cgpa[]" placeholder="Grade/CGPA">
              </div>
            </div>
            <button type="button" class="add-row" id="addQualification">➕</button>
          </div>

        </form>
      </div>
    </div>

    <style>
      .teacher-reg-wrapper { display: flex; justify-content: center; margin: 2em auto; padding: 1em; }
      .teacher-reg-card { width: 100%; max-width: 650px; background: #8A0000; color: #fff; border-radius: 8px; padding: 16px 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
      .teacher-reg-card input, .teacher-reg-card textarea { background: #fff; color: #000; padding: 6px; border: none; border-radius: 4px; margin-bottom: 8px; }
      .multi-input-row { display: flex; gap: 10px; margin-bottom: 10px; }
      .multi-input-row input { flex: 1; }
      .note-submit-row { display: flex; align-items: stretch; gap: 10px; margin-top: 10px; }
      .note-submit-row textarea { width: 85%; resize: vertical; }
      .submit-btn-inline { width: 15%; background: #0abab5; color: #fff; border: none; font-size: 14px; border-radius: 4px; cursor: pointer; }
      .profile-section { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 1em; }
      .profile-pic-container { position: relative; width: 90px; height: 90px; margin-right: 10px; }
      .profile-pic-container img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
      .upload-icons { position: absolute; bottom: 0; right: -10px; display: flex; flex-direction: column; gap: 5px; }
      .icon-btn { font-size: 16px; background: #FFD700; color: #fff; padding: 4px 6px; border-radius: 50%; cursor: pointer; }
      .tab-buttons-inline { display: flex; gap: 10px; margin-left: auto; }
      .tab-btn { padding: 6px 10px; background: #fff; color: #8A0000; border: none; border-radius: 4px; cursor: pointer; }
      .tab-btn.active { background: #0abab5; color: #fff; }
      .tab-content { display: none; }
      .tab-content.active { display: block; }
      .qualification-row { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
      .qualification-row input { flex: 1 1 30%; }
      .add-row { background: #0abab5; color: #fff; border: none; padding: 6px 10px; cursor: pointer; border-radius: 4px; }
      .remove-row { background: #c0392b; color: #fff; border: none; padding: 6px 10px; cursor: pointer; border-radius: 4px; align-self: center; }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => tab.addEventListener('click', function() {
          const target = this.dataset.tab;
          tabs.forEach(t => t.classList.remove('active'));
          contents.forEach(c => c.classList.remove('active'));
          this.classList.add('active');
          document.getElementById('tab-' + target).classList.add('active');
        }));

        document.getElementById('addQualification').addEventListener('click', function() {
          const wrapper = document.getElementById('qualification-wrapper');
          const row = document.createElement('div');
          row.className = 'qualification-row';
          row.innerHTML = `
            <input type="text" name="qualification[]" placeholder="Degree">
            <input type="text" name="university[]" placeholder="University">
            <input type="text" name="country[]" placeholder="Country">
            <input type="text" name="year[]" placeholder="Year">
            <input type="text" name="grade_or_cgpa[]" placeholder="Grade/CGPA">
            <button type="button" class="remove-row">&minus;</button>
          `;
          wrapper.appendChild(row);
        });
        document.getElementById('qualification-wrapper').addEventListener('click', function(e) {
          if (e.target.classList.contains('remove-row')) {
            const rows = document.querySelectorAll('.qualification-row');
            if (rows.length > 1) e.target.closest('.qualification-row').remove();
            else alert('At least one qualification is required.');
          }
        });

        document.getElementById('uploadFromFile').addEventListener('click', function() {
          document.getElementById('profilePicInput').click();
        });
        document.getElementById('profilePicInput').addEventListener('change', function(e) {
          const file = e.target.files[0]; if (!file) return;
          const reader = new FileReader();
          reader.onload = function(ev) { document.getElementById('previewImage').src = ev.target.result; };
          reader.readAsDataURL(file);
        });

        document.getElementById('captureFromCamera').addEventListener('click', function() {
          navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(stream => {
              const video = document.createElement('video');
              video.autoplay = true;
              video.srcObject = stream;
              document.body.appendChild(video);
              video.style.display = 'none';
              video.addEventListener('loadedmetadata', function() {
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);
                document.getElementById('previewImage').src = canvas.toDataURL('image/png');
                stream.getTracks().forEach(track => track.stop());
                video.remove();
              });
            })
            .catch(err => alert('Camera not accessible: ' + err.message));
        });
      });
    </script>

    <?php return ob_get_clean();
}
