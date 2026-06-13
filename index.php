<?php
session_start();
require_once "path.php";
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<?php require_once ROOT_PATH . "public/includes/head.php"; ?>
<title>ShadowScan</title>
</head>

<body>
<div class="container">

    <section class="left-panel">
        <h1 class="typing-title">ShadowScan</h1>
        <p class="terminal-subtitle"> digital footprint audit</p>

        <form id="scanForm" form action="audit.php" method="POST">
            <label>Email</label>
            <input
                type="email"
                name="email"
                placeholder="twój_mail@example.com"
                required
            >

            <label>Nick</label>
            <input
                type="text"
                name="username"
                placeholder="Twój_Nick"
                required
            >

            <button type="submit">
                Skanuj
            </button>
        </form>
    </section>

    <section class="right-panel">
    <div class="content">
        <?php include(ROOT_PATH ."public/includes/text.php"); ?>
    </div>
    </section>

</div>

<div id="scanOverlay" class="scan-overlay hidden">
    <div class="scan-box">
        <p>Skanowanie śladu cyfrowego...</p>

        <div class="progress-bar">
            <div id="progressFill"></div>
        </div>

        <p id="progressText">0%</p>
    </div>
</div>

<script>
document.getElementById("scanForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const overlay = document.getElementById("scanOverlay");
    const bar = document.getElementById("progressFill");
    const text = document.getElementById("progressText");

    overlay.classList.remove("hidden");

    let progress = 0;
    const duration = 4500;
    const intervalTime = 50;
    const step = 100 / (duration / intervalTime);

    const interval = setInterval(() => {
        progress += step;

        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);

   
            document.getElementById("scanForm").submit();
        }

        bar.style.width = progress + "%";
        text.textContent = Math.floor(progress) + "%";

    }, intervalTime);
});
</script>
</body>
</html>