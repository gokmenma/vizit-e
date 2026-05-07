let url = "App/Api/APIonayli_raporlar.php";

$(document).on("click", ".onay-iptal", function () {
  let MEDULARAPORID = $(this).data("id");
  let row = $(this).closest("tr");
  var formData = new FormData();

  swal
    .fire({
      title: "Onay İptal",
      text: "Bu raporun onayını iptal etmek istediğinize emin misiniz?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Evet, İptal Et",
      cancelButtonText: "Hayır, İptal Etme",
    })
    .then((result) => {
      if (result.isConfirmed) {
        formData.append("MEDULARAPORID", MEDULARAPORID);
        formData.append("action", "rapor_onay_iptal");

        fetch(url, {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            console.log(data);
            let title = data.status == "success" ? "Başarılı" : "Hata";
            swal
              .fire({
                title: title,
                text: data.message,
                icon: data.status == "success" ? "success" : "error",
                confirmButtonText: "Tamam",
              })
              .then((result) => {
                if (result.isConfirmed) {
                  // Onay iptali başarılıysa, raporu kuyruktan da düşelim.
                  row.remove();
                }
              });
          });
      }
    });
});
