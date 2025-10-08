<?php
// ---------------------------------------------------------------
//  default.php — Live Classroom launcher (PHP + HTML + JS)
// ---------------------------------------------------------------
//  Expect URL like:  default.php?subject=maths
//  • $subject   – passed blindly to the JS which then fetches the
//                 session token from resolve-session.php.
//  • $userRole  – pull from your own auth/session; demo defaults
//                 to "student".
// ---------------------------------------------------------------

$subject  = isset($_GET['subject']) ? trim($_GET['subject']) : '';
// In a real app you’d determine the role from your user/session:
$userRole = $_SESSION['role'] ?? 'student';
?><!DOCTYPE html><html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Live Classroom – <?= htmlspecialchars($subject ?: 'Unknown Subject') ?></title>
  <style>
    :root {
      --border-radius: 10px;
      --accent: #0070f3;
    }
    * { box-sizing: border-box; }
    html,body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }
    #container {
      width: 90%;
      max-width: 900px;
      min-height: 500px;
      margin: 40px auto;
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: var(--border-radius);
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
    }
    #container.error { color: #c00; }
    code { background: #f0f0f0; padding: 2px 4px; border-radius: 4px; }
  </style>
</head>
<body>
  <div id="container">
    <h2>🎓 Loading your live classroom…</h2>
  </div>  <script>
    // ----- Values injected safely from PHP (no braces!) ----------------------
    const SUBJECT   = <?= json_encode($subject) ?>;
    const USER_ROLE = <?= json_encode($userRole) ?>;

    // ----- Helper: load Zego UIKit Prebuilt only once ------------------------
    function loadZego() {
      const src = "https://unpkg.com/@zegocloud/zego-uikit-prebuilt@2.15.0/zego-uikit-prebuilt.js";
      return new Promise((resolve, reject) => {
        if (window.ZegoUIKitPrebuilt) return resolve();
        if (document.querySelector(script[src=\"${src}\"])) {
          document.addEventListener("ZegoLoaded", () => resolve(), { once: true });
          return;
        }
        const s = document.createElement("script");
        s.src = src;
        s.async = true;
        s.defer = true;
        s.onload = () => { document.dispatchEvent(new Event("ZegoLoaded")); resolve(); };
        s.onerror = () => reject(new Error("Failed to load Zego SDK"));
        document.head.appendChild(s);
      });
    }

    async function initClassroom() {
      const $c = document.getElementById("container");

      if (!SUBJECT) {
        $c.classList.add("error");
        $c.innerHTML = "<p>❌ Missing <code>subject</code> in URL — try <code>?subject=maths</code></p>";
        return;
      }

      try {
        await loadZego();

        const res = await fetch(resolve-session.php?subject=${encodeURIComponent(SUBJECT)});
        if (!res.ok) throw new Error(Server error ${res.status});
        const data = await res.json();
        if (!data.success) throw new Error(data.message || "Invalid server response");

        const zp = ZegoUIKitPrebuilt.create(data.token);
        zp.joinRoom({
          container: $c,
          sharedLinks: [{ name: "Invite", url: location.href }],
          scenario: {
            mode: ZegoUIKitPrebuilt.VideoConference,
            config: {
              role: USER_ROLE === "teacher" ?