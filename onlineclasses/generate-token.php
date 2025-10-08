<?php
$app_id = 2096951377;
$server_secret = 'c186b809ae926b7d55b0921297ebda88';
$payload = [
  'app_id' => $app_id,
  'room_id' => 'maths_classroom_1',
  'user_id' => 'teacher_123',
  'user_name' => 'Teacher Ali',
  'privilege' => ['login_room' => 1, 'publish_stream' => 1],
  'expire_time' => time() + 3600,
  'create_time' => time()
];
$json = json_encode($payload);
$hash = hash_hmac('sha256', $json, $server_secret, true);
$token = base64_encode($hash . $json);
?>
<!DOCTYPE html>
<html>
<!-- ... HEAD ... -->
<body>
  <div id="class-container"></div>
  <script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt@2.14.1/zego-uikit-prebuilt.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const token = "<?= $token ?>";  // ✅ injected from PHP

      const zp = ZegoUIKitPrebuilt.create(token);
      zp.joinRoom({
        container: document.getElementById('class-container'),
        scenario: {
          mode: 'education',
          config: { role: 'teacher' }
        }
      });
    });
  </script>
</body>
</html>
