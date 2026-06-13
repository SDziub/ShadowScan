document.getElementById("scanForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);

    const response = await fetch("api/scan.php", {
        method: "POST",
        body: formData
    });

    const data = await response.json();

    console.log(data);
});

window.addEventListener('load', () => {

    fetch('audit_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body:
            'email=<?= urlencode($email) ?>' +
            '&username=<?= urlencode($username) ?>'
    })
    .then(r => r.text())
    .then(html => {

        document.getElementById('auditLoading').remove();

        document.getElementById('auditResults').innerHTML = html;
    });

});
