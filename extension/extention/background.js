let userToken = null;
let lastTimestamp = 0;

chrome.runtime.onInstalled.addListener(() => {
  chrome.alarms.create("checkLMS", { periodInMinutes: 0.5 }); 
});

chrome.alarms.onAlarm.addListener(async (alarm) => {
  if (alarm.name === "checkLMS" && userToken) {
    await checkNotifications();
  }
});

chrome.runtime.onMessage.addListener((request) => {
  if (request.action === "setToken") {
    userToken = request.token;
  }
});

async function checkNotifications() {
  if (!userToken) return;

  try {
    const res = await fetch(`https://bahonarkaraj.ir/dashboard/api/extension/notifications.php?token=${userToken}`);
    if (!res.ok) return;

    const data = await res.json();

    if (data.count > 0 && data.timestamp > lastTimestamp) {
      lastTimestamp = data.timestamp;

      data.notifications.forEach(notif => {
        chrome.notifications.create({
          type: "basic",
          iconUrl: "icon.png",
          title: "هوشیار : " + (notif.title || "جدید!"),
          message: notif.message,
          priority: 2,
          requireInteraction: true
        });

        chrome.action.setBadgeText({ text: "!" });
        chrome.action.setBadgeBackgroundColor({ color: "#FF0000" });

        setTimeout(() => {
          chrome.action.setBadgeText({ text: "" });
        }, 10000);
      });
    }
  } catch (e) {
    console.log("خطا در اتصال به LMS");
  }
}
