document.addEventListener("DOMContentLoaded", function() {
  const form = document.getElementById("newsletter-form");
  const resultDiv = document.getElementById("newsletter-result");

  if (form) {
    form.addEventListener("submit", function(e) {
      e.preventDefault();

      grecaptcha.ready(function() {
        grecaptcha.execute("YOUR_RECAPTCHA_SITE_KEY", {action: "subscribe"})
          .then(function(token) {
            document.getElementById("g-recaptcha-response").value = token;
            // AJAX POST
            const formData = new FormData(form);
            fetch("/subscribe.php", {
              method: "POST",
              body: formData
            })
            .then(resp => resp.json())
            .then(data => {
              if (data.success) {
                resultDiv.innerHTML = "<p style='color:green;'>" + data.message + "</p>";
                form.style.display = "none";
              } else {
                resultDiv.innerHTML = "<p style='color:red;'>" + data.message + "</p>";
              }
            })
            .catch(err => {
              console.error(err);
              resultDiv.innerHTML = "<p style='color:red;'>Fehler bei der Anmeldung.</p>";
            });
          });
      });
    });
  }
});
