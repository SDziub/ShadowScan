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
        <h1 class="terminal-title">ShadowScan</h1>
        <p class="terminal-subtitle"> digital footprint audit</p>

        <form id="scanForm">
            <label>Email</label>
            <input
                type="email"
                name="email"
                placeholder="jan@example.com"
                required
            >

            <label>Nick</label>
            <input
                type="text"
                name="username"
                placeholder="Silvernax"
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
</body>
</html>