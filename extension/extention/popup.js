
const VALIDATE_URL = "https://bahonarkaraj.ir/dashboard/api/extension/validate_token.php?token=";
const NOTIF_URL    = "https://bahonarkaraj.ir/dashboard/api/extension/notifications.php?token=";
let currentToken = null;

document.addEventListener("DOMContentLoaded", () => {
    
    const connectBtn = document.getElementById("connectBtn");
    const settingsBtn = document.getElementById("settingsBtn");

    if (!connectBtn || !settingsBtn) {
        console.error("دکمه‌ها پیدا نشدند! HTML رو چک کن");
        return;
    }

    connectBtn.addEventListener("click", connect);
    settingsBtn.addEventListener("click", () => {
        document.getElementById("loginModal").style.display = "flex";
        document.getElementById("tokenInput").focus();
    });

    loadTokenAndCheck();
});

async function connect() {
    const token = document.getElementById("tokenInput").value.trim();
    if (!token || token.length < 32) {
        showLoginMessage("توکن باید ۳۲ کاراکتر باشه!", "danger");
        return;
    }

    showLoginMessage("در حال اتصال...", "info");

    try {
        const res = await fetch(VALIDATE_URL + token);
        if (!res.ok) throw new Error("خطای شبکه");

        const data = await res.json();

        if (data.success && data.user) {
            chrome.storage.local.set({ token: token }, () => {
                currentToken = token;
                hideLoginModal();
                updateStatus(`متصل به ${data.user}`, true);
                showLoginMessage("با موفقیت وصل شدی!", "success");
                chrome.runtime.sendMessage({ action: "setToken", token: token });
                setTimeout(() => document.getElementById("loginMessage").innerHTML = "", 3000);
                checkConnection(); 
            });
        } else {
            showLoginMessage("توکن اشتباه یا منقضی شده!", "danger");
        }
    } catch (e) {
        console.error(e);
        showLoginMessage("اتصال به سرور ممکن نیست!", "danger");
    }
}

function loadTokenAndCheck() {
    chrome.storage.local.get(["token"], async (result) => {
        if (result.token && result.token.length === 32) {
            currentToken = result.token;
            hideLoginModal();
            await checkConnection();
        } else {
            showLoginModal();
        }
    });
}

async function checkConnection() {
    if (!currentToken) return;

    try {
        const res = await fetch(NOTIF_URL + currentToken);
        if (!res.ok) throw new Error("خطا");

        const data = await res.json();
        updateNotifications(data.notifications || []);
        updateStatus("متصل", true);

    } catch (e) {
        updateStatus("اتصال قطع است", false);
        updateNotifications([]);
    }
}


function updateStatus(text, isOnline) {
    const statusText = document.getElementById("statusText");
    const statusIcon = document.getElementById("statusIcon");
    if (statusText) statusText.textContent = text;
    if (statusIcon) {
        statusIcon.className = isOnline ? "bi bi-wifi online" : "bi bi-wifi-off offline";
    }
}


function updateNotifications(notifs) {
    const list = document.getElementById("notificationsList");
    if (!list) return;

    if (notifs.length === 0) {
        list.innerHTML = `<div class="empty">هیچ هشداری وجود ندارد</div>`;
    } else {
        list.innerHTML = notifs.map(n => `
            <div class="notif-item">
                <div class="notif-title">${n.title || "هشدار"}</div>
                <div>${n.message}</div>
                <div class="notif-time">چند لحظه پیش</div>
            </div>
        `).join("");
    }
}

function showLoginModal() {
    const modal = document.getElementById("loginModal");
    if (modal) modal.style.display = "flex";
}

function hideLoginModal() {
    const modal = document.getElementById("loginModal");
    if (modal) modal.style.display = "none";
}

function showLoginMessage(text, type) {
    const el = document.getElementById("loginMessage");
    if (!el) return;
    const color = type === "danger" ? "#ff6b6b" : type === "info" ? "#4ecdc4" : "#00ff88";
    el.innerHTML = `<small style="color:${color}">${text}</small>`;
}
