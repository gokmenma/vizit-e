let url = "App/Api/APIodeme_sayfasi.php";
$(document).on("click", ".odeme-yap", function () {
  var form = $("#odemeForm");
  var button = $(this);
  var formData = new FormData(form[0]);

  formData.append("action", "odeme_yap");

  // Show premium loading state
  button.html('<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> <span class="button-text">Ödeme Bildiriliyor...</span>');
  button.prop("disabled", true);
  
  if (window.lucide) {
    lucide.createIcons();
  }
  
  fetch(url, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      return response.json();
    })
    .then((data) => {
      // Re-enable and restore original styling
      button.prop("disabled", false);
      button.html('<i data-lucide="check-circle" class="w-5 h-5"></i> <span class="button-text">Ödemeyi Yaptım, Onay Bekliyorum</span>');
      if (window.lucide) {
        lucide.createIcons();
      }
      
      let title = data.status == "success" ? "Başarılı" : "Hata";

      swal.fire({
        title: title,
        text: data.message,
        icon: data.status == "success" ? "success" : "error",
        confirmButtonText: "Tamam",
        customClass: {
          confirmButton: 'bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-950 px-4 py-2 rounded-md font-semibold text-sm transition-colors'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.isConfirmed && data.status == "success") {
          window.location.href = "dashboard";
        }
      });
     
    }).catch((error) => {
      console.error("Error:", error);

      swal.fire({
        title: "Hata",
        text: "Ödeme işlemi sırasında bir hata oluştu.",
        icon: "error",
        confirmButtonText: "Tamam",
        customClass: {
          confirmButton: 'bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-950 px-4 py-2 rounded-md font-semibold text-sm transition-colors'
        },
        buttonsStyling: false
      });

      // Restore button state
      button.prop("disabled", false);
      button.html('<i data-lucide="check-circle" class="w-5 h-5"></i> <span class="button-text">Ödemeyi Yaptım, Onay Bekliyorum</span>');
      if (window.lucide) {
        lucide.createIcons();
      }
    });
});
