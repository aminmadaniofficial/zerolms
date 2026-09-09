const BASE_DOMAIN = "https://zerolms.aminmadani.xyz"; // در صورت تغییر دامنه یا لوکال‌هاست این آدرس را تغییر دهید
const VALIDATE_URL = `${BASE_DOMAIN}/dashboard/api/extension/validate_token.php?token=`;
const NOTIF_URL    = `${BASE_DOMAIN}/dashboard/api/extension/notifications.php?token=`;

let currentToken = null;

document.addEventListener("DOMContentLoaded", () => {
    const connectBtn = document.getElementById("connectBtn");
    const settingsBtn = document.getElementById("settingsBtn");

    if (connectBtn) connectBtn.addEventListener("click", connect);
    if (settingsBtn) {
        settingsBtn.addEventListener("click", () => {
            showLoginModal();
            document.getElementById("tokenInput").focus();
        });
    }

    loadTokenAndCheck();
});

async function connect() {
    const tokenInput = document.getElementById("tokenInput");
    const token = tokenInput ? tokenInput.value.trim() : "";

    if (!token || token.length !== 32) {
        showLoginMessage("توکن باید ۳۲ کاراکتر باشد.", "danger");
        return;
    }

    showLoginMessage("در حال اعتبار سنجی...", "info");

    try {
        const res = await fetch(VALIDATE_URL + token);
        if (!res.ok) throw new Error("خطای شبکه");

        const data = await res.json();

        if (data.success) {
            chrome.storage.local.set({ token: token }, () => {
                currentToken = token;
                hideLoginModal();
                updateStatus(`متصل: ${data.user}`, true);
                chrome.runtime.sendMessage({ action: "setToken", token: token });
                checkConnection();
            });
        } else {
            showLoginMessage("توکن نامعتبر یا منقضی شده است.", "danger");
        }
    } catch (e) {
        showLoginMessage("ارتباط با سرور برقرار نشد.", "danger");
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
        if (!res.ok) throw new Error("Error");

        const data = await res.json();
        updateNotifications(data.notifications || []);
        updateStatus("متصل", true);
    } catch (e) {
        updateStatus("قطع ارتباط", false);
        updateNotifications([]);
    }
}

function updateStatus(text, isOnline) {
    const badge = document.getElementById("statusBadge");
    const statusText = document.getElementById("statusText");
    if (statusText) statusText.textContent = text;
    if (badge) {
        badge.className = isOnline ? "status-badge online" : "status-badge offline";
    }
}

function updateNotifications(notifs) {
    const list = document.getElementById("notificationsList");
    if (!list) return;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    if (notifs.length === 0) {
        list.innerHTML = `<div class="empty"><i class="bi bi-check2-circle fs-3 d-block mb-2 text-success"></i>اعلان جدیدی وجود ندارد.</div>`;
    } else {
        list.innerHTML = notifs.map(n => {
            let icon = "bi-bell";
            let color = "#38bdf8";

            if (n.type === "homework") { icon = "bi-journal-check"; color = "#fbbf24"; }
            else if (n.type === "exam") { icon = "bi-pen"; color = "#f43f5e"; }
            else if (n.type === "grade") { icon = "bi-trophy"; color = "#34d399"; }
            else if (n.type === "badge") { icon = "bi-award"; color = "#a855f7"; }
            else if (n.type === "absence") { icon = "bi-exclamation-octagon"; color = "#f43f5e"; }

            return `
                <div class="notif-item">
                    <div class="notif-title" style="color: ${color};">
                        <i class="bi ${icon}"></i> ${escapeHtml(n.title || "اعلان")}
                    </div>
                    <div class="notif-msg">${escapeHtml(n.message || "")}</div>
                </div>
            `;
        }).join("");
    }
}

function showLoginModal() {
    const modal = document.getElementById("loginModal");
    if (modal) modal.classList.add("active");
}

function hideLoginModal() {
    const modal = document.getElementById("loginModal");
    if (modal) modal.classList.remove("active");
}

function showLoginMessage(text, type) {
    const el = document.getElementById("loginMessage");
    if (!el) return;
    const color = type === "danger" ? "#f43f5e" : type === "info" ? "#38bdf8" : "#34d399";
    el.innerHTML = `<span style="color:${color}">${text}</span>`;
}