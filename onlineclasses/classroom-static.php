<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Live Classroom (Local SDK)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- ✅ Local Zego UIKit SDK -->
  <script src="zego-uikit-prebuilt.js"></script>
</head>
<body>
  <!-- ✅ This is where the video room will render -->
  <div id="container" style="width: 100%; height: 90vh;"></div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // ✅ Check if SDK loaded correctly
      if (typeof ZegoUIKitPrebuilt === "undefined") {
        document.body.innerHTML += "<p style='color:red;'>❌ Zego SDK failed to load.</p>";
        return;
      }

      // ✅ Your Zego details
      const appID = 2096951377;
      const serverSecret = "c186b809ae926b7d55b0921297ebda88";

      // ✅ Room info
      const roomID = "demo_room";
      const userID = "user_" + Math.floor(Math.random() * 10000);
      const userName = "Static User";

      // ✅ Generate Token
      const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(
        appID,
        serverSecret,
        roomID,
        userID,
        userName
      );

      // ✅ Join Room
      const zp = ZegoUIKitPrebuilt.create(kitToken);
      zp.joinRoom({
        container: document.getElementById("container"),
        scenario: {
          mode: "education",
          config: {
            role: "student"
          }
        }
      });
    });
  </script>
</body>
</html>
