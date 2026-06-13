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