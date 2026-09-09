const BASE_DOMAIN = "https://zerolms.aminmadani.xyz"; // در صورت تغییر آدرس این بخش را تغییر دهید
let lastTimestamp = 0;

chrome.runtime.onInstalled.addListener(() => {
  chrome.alarms.create("checkLMS", { periodInMinutes: 0.5 });
});

chrome.alarms.onAlarm.addListener(async (alarm) => {
  if (alarm.name === "checkLMS") {
    await checkNotifications();
  }
});

chrome.runtime.onMessage.addListener((request) => {
  if (request.action === "setToken") {
    checkNotifications();
  }
});

async function checkNotifications() {
  chrome.storage.local.get(["token"], async (result) => {
    const userToken = result.token;
    if (!userToken) return;

    try {
      const res = await fetch(`${BASE_DOMAIN}/dashboard/api/extension/notifications.php?token=${userToken}`);
      if (!res.ok) return;

      const data = await res.json();

      if (data.count > 0 && data.timestamp > lastTimestamp) {
        lastTimestamp = data.timestamp;

        data.notifications.forEach(notif => {
          chrome.notifications.create({
            type: "basic",
            iconUrl: "icon.png",
            title: "هوشیار: " + (notif.title || "اعلان جدید"),
            message: notif.message,
            priority: 2
          });

          chrome.action.setBadgeText({ text: "!" });
          chrome.action.setBadgeBackgroundColor({ color: "#6366f1" });

          setTimeout(() => {
            chrome.action.setBadgeText({ text: "" });
          }, 8000);
        });
      }
    } catch (e) {
      console.log("خطا در بررسی اعلان‌ها");
    }
  });
}