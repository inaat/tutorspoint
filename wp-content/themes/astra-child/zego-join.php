<div id="zego-container"></div>
<script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>
<script>
async function joinClass(roomID, userID, userName) {
  const appID = YOUR_APP_ID;
  const serverUrl = "/api/zego_token.php?user_id=" + userID;
  
  const resp = await fetch(serverUrl);
  const data = await resp.json();
  
  const token = data.token;
  
  const zp = ZegoUIKitPrebuilt.create(appID, token, roomID, userID, userName);
  zp.joinRoom({
    container: document.querySelector("#zego-container"),
    scenario: { mode: ZegoUIKitPrebuilt.OneONoneCall }
  });
}
</script>
