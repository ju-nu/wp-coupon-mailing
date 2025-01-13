document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("newsletter-form");
    const resultDiv = document.getElementById("newsletter-result");
  
    if (!form) {
      return;
    }
  
    form.addEventListener("submit", function (e) {
      e.preventDefault();
  
      // "YOUR_RECAPTCHA_SITE_KEY" must match what's used in form.html
      grecaptcha.ready(function () {
        grecaptcha
          .execute("YOUR_RECAPTCHA_SITE_KEY", { action: "subscribe" })
          .then(function (token) {
            // Insert token into hidden field
            document.getElementById("g-recaptcha-response").value = token;
  
            // Now do an AJAX POST to /subscribe.php
            const formData = new FormData(form);
            fetch("/subscribe.php", {
              method: "POST",
              body: formData,
            })
              .then((resp) => resp.json())
              .then((data) => {
                if (data.success) {
                  // Subscription OK
                  resultDiv.innerHTML = `<p style="color:green;">${data.message}</p>`;
                  form.style.display = "none";
                } else {
                  // Some error
                  resultDiv.innerHTML = `<p style="color:red;">${data.message}</p>`;
                }
              })
              .catch((err) => {
                console.error("Subscription error:", err);
                resultDiv.innerHTML =
                  "<p style='color:red;'>Es gab einen Fehler bei der Anmeldung.</p>";
              });
          });
      });
    });
  });
  