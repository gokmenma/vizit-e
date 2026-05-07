let url = "App/Api/APIodeme_sayfasi.php";
$(document).on("click", ".odeme-yap", function () {
  var form = $("#odemeForm");
var button = $(this);
  var formData = new FormData(form[0]);

  formData.append("action", "odeme_yap");

  button.text("Ödeme Yapılıyor..."); // Change button text
  button.disabled = true; // Disable the button to prevent multiple clicks
  
  fetch(url, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      return response.json();
    })
    .then((data) => {
     
      button.prop("disabled", false); // Re-enable the button
      button.text("Paketi Satın Al"); // Reset button text
      let title = data.status == "success" ? "Başarılı" : "Hata";

      console.log(data);
      swal.fire({
        title: title,
        text: data.message,
        icon: data.status == "success" ? "success" : "error",
        confirmButtonText: "Tamam",
      }).then((result) => {
        if (result.isConfirmed) {
          //window.location.href = "isyerlerim";
        }
      });
     
      // console.log(data);
    }).catch((error) => {
      console.error("Error:", error);

      swal.fire({
        title: "Hata",
        text: "Ödeme işlemi sırasında bir hata oluştu.",
        icon: "error",
        confirmButtonText: "Tamam",
      });

      button.prop("disabled", false); // Re-enable the button
      button.text("Paketi Satın Al"); // Reset button text
    });

    

});
