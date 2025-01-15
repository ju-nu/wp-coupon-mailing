// subscribe.js
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("newsletter-form");
  const resultDiv = document.getElementById("newsletter-result");
  const cfTokenField = document.getElementById("cf-turnstile-response");

  if (!form) {
    return;
  }

  // 1) Intercept the normal form submission
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    // Manually trigger Turnstile. The widget is invisible, so the user doesn’t see anything.
    turnstile.execute();
  });

  // 2) This function will be called automatically by Turnstile after a successful token is generated
  window.onTurnstileSubmit = function (token) {
    // Place the Turnstile token into our hidden field
    cfTokenField.value = token;

    // Now do an AJAX POST to /subscribe.php with the entire form data
    const formData = new FormData(form);

    fetch("/subscribe.php", {
      method: "POST",
      body: formData,
    })
      .then((resp) => resp.json())
      .then((data) => {
        if (data.success) {
          resultDiv.innerHTML = `<p style="color:green;">${data.message}</p>`;
          form.style.display = "none";
        } else {
          resultDiv.innerHTML = `<p style="color:red;">${data.message}</p>`;
        }
      })
      .catch((err) => {
        console.error("Subscription error:", err);
        resultDiv.innerHTML =
          "<p style='color:red;'>Es gab einen Fehler bei der Anmeldung.</p>";
      });
  };
});
