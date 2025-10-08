// Configuration
const APP_ID = 2096951377;
const SERVER_SECRET = "c186b809ae926b7d55b0921297ebda88";
const ROOM_TOKEN_EXPIRY = 3600; // 1 hour in seconds

// DOM Elements
const authScreen = document.getElementById('authScreen');
const classroomScreen = document.getElementById('classroomScreen');
const joinBtn = document.getElementById('joinBtn');
const leaveBtn = document.getElementById('leaveBtn');
const userNameInput = document.getElementById('userName');
const roomTokenInput = document.getElementById('roomToken');
const userRoleSelect = document.getElementById('userRole');
const authLoader = document.getElementById('authLoader');
const videoContainer = document.getElementById('videoContainer');
const classNameDisplay = document.getElementById('className');
const participantCount = document.getElementById('participantCount');
const participantsList = document.getElementById('participantsList');
const chatMessages = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');
const sendMsgBtn = document.getElementById('sendMsgBtn');
const whiteboardContainer = document.getElementById('whiteboardContainer');
const micBtn = document.getElementById('micBtn');
const cameraBtn = document.getElementById('cameraBtn');
const screenShareBtn = document.getElementById('screenShareBtn');
const inviteBtn = document.getElementById('inviteBtn');
const inviteModal = document.getElementById('inviteModal');
const inviteTokenDisplay = document.getElementById('inviteTokenDisplay');
const inviteLink = document.getElementById('inviteLink');
const copyLinkBtn = document.getElementById('copyLinkBtn');
const closeInviteModal = document.getElementById('closeInviteModal');

// Tab controls
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

// State
let zp;
let currentRoomId;
let currentUser;
let currentRole;
let isMicOn = true;
let isCameraOn = true;
let isScreenSharing = false;
let zegoWhiteboard;

// Initialize
initEventListeners();

function initEventListeners() {
    // Join button
    joinBtn.addEventListener('click', handleJoin);
    
    // Leave button
    leaveBtn.addEventListener('click', handleLeave);
    
    // Tab switching
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });
    
    // Chat
    sendMsgBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
    
    // Device controls
    micBtn.addEventListener('click', toggleMic);
    cameraBtn.addEventListener('click', toggleCamera);
    screenShareBtn.addEventListener('click', toggleScreenShare);
    
    // Invite
    inviteBtn.addEventListener('click', showInviteModal);
    copyLinkBtn.addEventListener('click', copyInviteLink);
    closeInviteModal.addEventListener('click', () => {
        inviteModal.classList.add('hidden');
    });
}

async function handleJoin() {
    const username = userNameInput.value.trim();
    const password = prompt("Enter teacher password:"); // In production, use a proper form
    
    try {
        // 1. Request Teacher Token
        const response = await fetch('/api/generate-token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                password: password,
                role: 'teacher',
                room_id: 'advanced_math' // Or generate dynamically
            })
        });
        
        if (!response.ok) throw new Error('Token generation failed');
        
        const { token, room_id } = await response.json();
        
        // 2. Store Token Securely
        sessionStorage.setItem('zego_teacher_token', token);
        sessionStorage.setItem('zego_room_id', room_id);
        
        // 3. Join Classroom
        joinRoomAsTeacher(token);
        
    } catch (error) {
        console.error("Teacher login failed:", error);
        alert("Authentication failed. Please check credentials.");
    }
}

async function handleJoin() {
    const username = userNameInput.value.trim();
    const password = prompt("Enter teacher password:"); // In production, use a proper form
    
    try {
        // 1. Request Teacher Token
        const response = await fetch('/api/generate-token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                password: password,
                role: 'teacher',
                room_id: 'advanced_math' // Or generate dynamically
            })
        });
        
        if (!response.ok) throw new Error('Token generation failed');
        
        const { token, room_id } = await response.json();
        
        // 2. Store Token Securely
        sessionStorage.setItem('zego_teacher_token', token);
        sessionStorage.setItem('zego_room_id', room_id);
        
        // 3. Join Classroom
        joinRoomAsTeacher(token);
        
    } catch (error) {
        console.error("Teacher login failed:", error);
        alert("Authentication failed. Please check credentials.");
    }
}

function joinRoomAsTeacher(token) {
    zp = ZegoUIKitPrebuilt.create(token);
   // In joinRoom() config
zp.joinRoom({
    // ... other config ...
    privileges: {
        // Teacher-exclusive controls
        screenSharingConfig: {
            enable: true,
            role: ['teacher'] // Only teachers can share
        },
        whiteboardConfig: {
            role: ['teacher'] // Only teachers can edit
        },
        userListConfig: {
            showKickButton: ['teacher'] // Only teachers see kick buttons
        }
    }
});
   
   /*
    zp.joinRoom({
        container: videoContainer,
        scenario: {
            mode: 'education',
            config: {
                role: 'teacher',
                // Enable all teacher privileges
                enableScreenSharing: true,
                enableChat: true,
                showUserList: true,
                turnOnMicrophoneWhenJoining: true,
                turnOnCameraWhenJoining: true
            }
        }
    });*/
}

function teacherLogout() {
    // 1. Revoke token
    fetch('/api/revoke-token', {
        method: 'POST',
        body: JSON.stringify({
            token: sessionStorage.getItem('zego_teacher_token')
        })
    });
    
    // 2. Clean up
    sessionStorage.removeItem('zego_teacher_token');
    zp.leaveRoom();
}


/*
function joinRoomAsTeacher(token) {
    zp = ZegoUIKitPrebuilt.create(token);
    zp.joinRoom({
        container: videoContainer,
        scenario: {
            mode: 'education',
            config: {
                role: 'teacher',
                // Enable all teacher privileges
                enableScreenSharing: true,
                enableChat: true,
                showUserList: true,
                turnOnMicrophoneWhenJoining: true,
                turnOnCameraWhenJoining: true
            }
        }
    });
}

*/
/*

async function handleJoin() {
    const userName = userNameInput.value.trim();
    const userRole = userRoleSelect.value;
    
    // Request token from your backend
    const response = await fetch('/api/generate-token', {
        method: 'POST',
        body: JSON.stringify({
            username: userName,
            role: userRole,
            room_id: `class-${Math.random().toString(36).substring(7)}`
        })
    });
    
    const data = await response.json();
    
    // Use these to join
    currentRoomId = data.room_id;
    currentUser = userName;
    currentRole = userRole;
    
    joinRoom(data.token); // Pass the generated token
}


*/

const jwt = require('jsonwebtoken');

app.post('/api/token', (req, res) => {
    const { user, role, room } = req.body;
    
    const token = jwt.sign({
        app_id: 2096951377,
        room_id: room,
        user_id: user,
        role: role,
        exp: Math.floor(Date.now() / 1000) + 3600 // 1 hour
    }, "c186b809ae926b7d55b0921297ebda88");
    
    res.json({ token });
});

// When joining:
const response = await fetch('/api/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        user: "Mr. Smith",
        role: "teacher",
        room: "advanced-math-2023"
    })
});
const { token } = await response.json();

// Use this token with ZegoUIKitPrebuilt



/*
function handleJoin() 
{
    const userName = userNameInput.value.trim();
    const roomToken = roomTokenInput.value.trim();
    const userRole = userRoleSelect.value;
    
    if (!userName || !roomToken) {
        alert('Please enter your name and room token');
        return;
    }
    
    // Show loading
    joinBtn.disabled = true;
    authLoader.classList.remove('hidden');
    
    // In a real app, you would validate the token with your server
    // For this demo, we'll just use it as the room ID
    currentRoomId = roomToken;
    currentUser = userName;
    currentRole = userRole;
    
    // Join the room after a short delay to show loading
    setTimeout(() => {
        joinRoom();
    }, 1000);
}
*/
function joinRoom() {
    // Generate token (in production, get this from your server)
    const token = ZegoUIKitPrebuilt.generateKitTokenForProduction(
        APP_ID,
        SERVER_SECRET,
        currentRoomId,
        Date.now().toString(),
        currentUser
    );
    
    // Create Zego instance
    zp = ZegoUIKitPrebuilt.create(token);
    
    // Join room with config
    zp.joinRoom({
        container: videoContainer,
        scenario: {
            mode: 'education',
            config: {
                role: currentRole === 'teacher' ? 'teacher' : 'student',
                enableScreenSharing: true,
                enableChat: true,
                showUserList: true,
                turnOnMicrophoneWhenJoining: isMicOn,
                turnOnCameraWhenJoining: isCameraOn
            }
        },
        sharedLinks: [
            {
                name: 'Copy invite link',
                url: generateInviteLink()
            }
        ],
        showPreJoinView: false,
        onUserAvatarSetter: (user) => {
            // Return avatar URL or base64 string
            return `https://ui-avatars.com/api/?name=${user.userName}&background=random`;
        }
    });
    
    // Set up event listeners
    zp.on('roomStateChanged', (state) => {
        if (state === 'CONNECTED') {
            // Successfully joined
            authScreen.classList.add('hidden');
            classroomScreen.classList.remove('hidden');
            classNameDisplay.textContent = `Room: ${currentRoomId}`;
            
            // Initialize whiteboard if teacher
            if (currentRole === 'teacher') {
                initWhiteboard();
            }
            
            // Update participant list
            updateParticipantList();
        }
    });
    
    zp.on('roomUserUpdate', (updateType, userList) => {
        updateParticipantList();
    });
    
    zp.on('chatUpdate', (updateType, chatData) => {
        if (updateType === 'ADD') {
            addChatMessage(chatData);
        }
    });
}

function handleLeave() {
    if (zp) {
        zp.leaveRoom();
    }
    if (zegoWhiteboard) {
        zegoWhiteboard.destroy();
    }
    classroomScreen.classList.add('hidden');
    authScreen.classList.remove('hidden');
    joinBtn.disabled = false;
    authLoader.classList.add('hidden');
}

function switchTab(tabName) {
    // Update active tab button
    tabBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });
    
    // Update active tab content
    tabContents.forEach(content => {
        content.classList.toggle('active', content.id === `${tabName}Tab`);
    });
    
    // If switching to whiteboard and teacher, ensure it's initialized
    if (tabName === 'whiteboard' && currentRole === 'teacher' && !zegoWhiteboard) {
        initWhiteboard();
    }
}

function updateParticipantList() {
    if (!zp) return;
    
    const userList = zp.getAllUsers();
    participantCount.textContent = userList.length;
    
    participantsList.innerHTML = '';
    userList.forEach(user => {
        const isTeacher = user.userID === zp.getHostUser()?.userID;
        const participantItem = document.createElement('div');
        participantItem.className = `participant-item ${isTeacher ? 'teacher' : ''}`;
        participantItem.innerHTML = `
            <i class="fas fa-${isTeacher ? 'chalkboard-teacher' : 'user-graduate'}"></i>
            <span>${user.userName}</span>
            ${user.userID === zp.getLocalUser().userID ? '<span>(You)</span>' : ''}
        `;
        participantsList.appendChild(participantItem);
    });
}

function addChatMessage(chatData) {
    const isLocalUser = chatData.fromUser.userID === zp.getLocalUser().userID;
    const messageElement = document.createElement('div');
    messageElement.className = `chat-message ${isLocalUser ? 'sent' : ''}`;
    
    const time = new Date(chatData.sendTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    messageElement.innerHTML = `
        <div class="sender">${chatData.fromUser.userName}</div>
        <div class="text">${chatData.message}</div>
        <div class="time">${time}</div>
    `;
    
    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function sendMessage() {
    const message = chatInput.value.trim();
    if (message && zp) {
        zp.sendChatMessage(message);
        chatInput.value = '';
    }
}

function toggleMic() {
    if (!zp) return;
    
    isMicOn = !isMicOn;
    if (isMicOn) {
        zp.unmuteMicrophone();
        micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        micBtn.classList.remove('active');
    } else {
        zp.muteMicrophone();
        micBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
        micBtn.classList.add('active');
    }
}

function toggleCamera() {
    if (!zp) return;
    
    isCameraOn = !isCameraOn;
    if (isCameraOn) {
        zp.openCamera();
        cameraBtn.innerHTML = '<i class="fas fa-video"></i>';
        cameraBtn.classList.remove('active');
    } else {
        zp.closeCamera();
        cameraBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
        cameraBtn.classList.add('active');
    }
}

function toggleScreenShare() {
    if (!zp) return;
    
    isScreenSharing = !isScreenSharing;
    if (isScreenSharing) {
        zp.startScreenSharing();
        screenShareBtn.classList.add('active');
    } else {
        zp.stopScreenSharing();
        screenShareBtn.classList.remove('active');
    }
}

// Auto-refresh token 5 mins before expiry
function startTokenRefreshTimer(expiryTime) {
    const refreshTime = expiryTime - Date.now() - (5 * 60 * 1000);
    
    if (refreshTime > 0) {
        setTimeout(async () => {
            const newToken = await refreshTeacherToken();
            zp.renewToken(newToken);
        }, refreshTime);
    }
}

async function refreshTeacherToken() {
    const response = await fetch('/api/refresh-token', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${sessionStorage.getItem('zego_teacher_token')}`
        }
    });
    return response.json().token;
}

function initWhiteboard() {
    if (zegoWhiteboard) return;
    
    // Load whiteboard SDK dynamically
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/@zegocloud/zego-whiteboard';
    script.onload = () => {
        zegoWhiteboard = new ZegoWhiteboard({
            container: whiteboardContainer,
            appID: APP_ID,
            userID: zp.getLocalUser().userID,
            userName: zp.getLocalUser().userName,
            roomID: currentRoomId,
            token: ZegoUIKitPrebuilt.generateKitTokenForProduction(
                APP_ID,
                SERVER_SECRET,
                currentRoomId,
                zp.getLocalUser().userID,
                zp.getLocalUser().userName
            ),
            role: currentRole === 'teacher' ? 'admin' : 'reader'
        });
    };
    document.body.appendChild(script);
}

function showInviteModal() {
    inviteTokenDisplay.textContent = currentRoomId;
    inviteLink.value = generateInviteLink();
    inviteModal.classList.remove('hidden');
}

function copyInviteLink() {
    inviteLink.select();
    document.execCommand('copy');
    
    // Visual feedback
    const originalText = copyLinkBtn.innerHTML;
    copyLinkBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    setTimeout(() => {
        copyLinkBtn.innerHTML = originalText;
    }, 2000);
}

function generateInviteLink() {
    return `${window.location.origin}${window.location.pathname}?room=${currentRoomId}`;
}

// Check for room ID in URL
window.addEventListener('load', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const roomId = urlParams.get('room');
    
    if (roomId) {
        roomTokenInput.value = roomId;
        userRoleSelect.value = 'student';
    }
});

async function handleStudentJoin() {
    try {
        const response = await fetch(`${API_BASE}/generate-token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpApiSettings.nonce // For WordPress auth
            },
            body: JSON.stringify({
                username: userNameInput.value.trim(),
                room_id: roomTokenInput.value.trim(),
                role: 'student'
            })
        });

        const data = await response.json();
        
        if (!response.ok) throw new Error(data.message || 'Join failed');

        // Store token with expiration
        localStorage.setItem('zego_data', JSON.stringify({
            token: data.token,
            room_id: data.room_id,
            expires_at: data.expires_at
        }));

        initZegoSDK(data.token);
        
    } catch (error) {
        console.error('Join failed:', error);
        showToast(error.message, 'error');
    }
}

/*
async function handleStudentJoin() {
    try {
        const response = await fetch('/api/generate-student-token', {
            method: 'POST',
            // ... other params ...
        });

        if (response.status === 429) {
            const retryAfter = response.headers.get('Retry-After') || 3600;
            showRateLimitError(retryAfter);
            return;
        }
        // ... rest of flow ...
    } catch (error) {
        // Handle errors
    }
}
*/
function showRateLimitError(seconds) {
    const hours = Math.ceil(seconds / 3600);
    alert(`You've exceeded join attempts. Please try again in ${hours} hour(s).`);
}

/*
async function handleStudentJoin() {
    const captchaToken = grecaptcha.getResponse();
    if (!captchaToken) {
        alert('Please complete the CAPTCHA');
        return;
    }

    const response = await fetch('/api/verify-captcha', {
        method: 'POST',
        body: JSON.stringify({ captcha_token: captchaToken })
    });
    
    if (!response.ok) throw new Error('CAPTCHA failed');
    
    // Proceed with token generation
}

*/


/*
async function handleStudentJoin() {
    const studentName = userNameInput.value.trim();
    const roomId = roomTokenInput.value.trim();

    if (!roomId) {
        alert('Please enter a valid room ID from your teacher');
        return;
    }

    try {
        // 1. Get Student Token
        const response = await fetch('/api/generate-student-token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: studentName,
                room_id: roomId
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Join failed');
        }

        const { token, room_id } = await response.json();

        // 2. Store Token (Less sensitive - no password)
        localStorage.setItem('zego_student_token', token);
        localStorage.setItem('zego_room_id', room_id);

        // 3. Join with Restricted Privileges
        joinRoomAsStudent(token);

    } catch (error) {
        console.error("Student join failed:", error);
        alert(`Error: ${error.message}`);
    }
}

*/








function joinRoomAsStudent(token) {
    zp = ZegoUIKitPrebuilt.create(token);
    zp.joinRoom({
        container: videoContainer,
        scenario: {
            mode: 'education',
            config: {
                role: 'student',
                turnOnMicrophoneWhenJoining: false, // Default mute
                turnOnCameraWhenJoining: false, // Default camera off
                enableScreenSharing: false, // Disabled
                whiteboardConfig: {
                    role: 'reader' // View-only
                }
            }
        },
        showUserList: false // Students see limited info
    });
}

// Disable teacher-only UI elements
function setupStudentUI() {
    document.getElementById('screenShareBtn').style.display = 'none';
    document.getElementById('inviteBtn').style.display = 'none';
    
    // Whiteboard view-only mode
    if (zegoWhiteboard) {
        zegoWhiteboard.setRole('reader');
    }
    
    // Limited chat features
    zp.setChatConfig({
        maxMessageLength: 100,
        disablePrivateChat: true
    });
}


class LiveClassroom {
    constructor() {
        this.apiBase = '/api';
        this.initElements();
        this.bindEvents();
    }

    initElements() {
        this.authSection = document.querySelector('.classroom-auth');
        this.classroomContainer = document.querySelector('.classroom-container');
        this.userNameInput = document.getElementById('userName');
        this.roomCodeInput = document.getElementById('roomCode');
        this.joinBtn = document.getElementById('joinBtn');
    }

    bindEvents() {
        this.joinBtn.addEventListener('click', () => this.handleJoin());
    }

    async handleJoin() {
        try {
            this.showLoader();
            
            // 1. Verify CAPTCHA
            const captchaToken = grecaptcha.getResponse();
            if (!captchaToken) throw new Error('Complete CAPTCHA first');

            // 2. Get Token
            const response = await fetch(`${this.apiBase}/generate-token.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: this.userNameInput.value.trim(),
                    room_id: this.roomCodeInput.value.trim(),
                    role: 'student',
                    captcha_token: captchaToken
                })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Join failed');

            // 3. Initialize Classroom
            this.initZegoCloud(data.token);
            
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.hideLoader();
        }
    }

    initZegoCloud(token) {
        // Load Zego SDK dynamically
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js';
        script.onload = () => {
            const zp = ZegoUIKitPrebuilt.create(token);
            zp.joinRoom({
                container: this.classroomContainer,
                scenario: { mode: 'education' }
            });
            
            // Switch UI
            this.authSection.classList.add('hidden');
            this.classroomContainer.classList.remove('hidden');
        };
        document.head.appendChild(script);
    }

    showLoader() {
        this.joinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
        this.joinBtn.disabled = true;
    }

    hideLoader() {
        this.joinBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Join Class';
        this.joinBtn.disabled = false;
    }

    showError(message) {
        const errorEl = document.createElement('div');
        errorEl.className = 'error-message';
        errorEl.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        this.authSection.appendChild(errorEl);
        setTimeout(() => errorEl.remove(), 5000);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    new LiveClassroom();
});


