function getFingerprint() {
    return {
        userAgent: navigator.userAgent,
        language: navigator.language,
        screen: screen.width + "x" + screen.height,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        cookiesEnabled: navigator.cookieEnabled
    };
}

fetch("fingerprint.php", {
    method: "POST",
    body: JSON.stringify(getFingerprint())
});