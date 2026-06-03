let url = "App/Api/APIisyerlerim.php";
document
  .getElementById("excel_dosya")
  .addEventListener("change", function (event) {
    const fileInput = event.target;
    const file = fileInput.files[0];
    const allowedExtensions = /(\.xls|\.xlsx)$/i;

    if (!allowedExtensions.exec(file.name)) {
      swal.fire({
        title: "Hata",
        text: "Lütfen sadece Excel dosyası yükleyin.",
        icon: "error",
        confirmButtonText: "Tamam",
      });
      fileInput.value = ""; // Dosya girişini temizle
    } else {
      const reader = new FileReader();
      reader.onload = function (e) {
        const workbook = XLSX.read(e.target.result, { type: "binary" });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

        // Hücre aralığını al
        const range = XLSX.utils.decode_range(firstSheet["!ref"]);

        // Satır sayısını hesapla
        const satir_sayisi = range.e.r - range.s.r;

        //Tutarları Topla
        let toplam_tutar = 0;
        for (let row = range.s.r; row <= range.e.r; row++) {
          const cellAddress = XLSX.utils.encode_cell({ r: row, c: 1 }); // 1. sütun (B sütunu) için
          const cell = firstSheet[cellAddress];
          if (cell && !isNaN(cell.v)) {
            // Check if the cell value is a valid number
            toplam_tutar += parseFloat(cell.v);
          }
        }
        //satır sayısı 1000'den fazla ise uyarı ver
        if (satir_sayisi > 1000) {
          swal.fire({
            title: "Uyarı",
            text: "Yüklenen dosyada bulunan satır sayısı 1000'den fazla. Lütfen daha az satır içeren bir dosya yükleyin.",
            icon: "warning",
            confirmButtonText: "Tamam",
          });
          fileInput.value = ""; // Dosya girişini temizle
          return;
        } else {
          const alertDiv = $(".alert-description");
          $(alertDiv).html(`
                Yüklenen dosyada bulunan satır sayısı: <strong>${satir_sayisi}
              `);
              $(alertDiv).removeClass("d-none").addClass("alert-info");

          $(".upload-info").removeClass("d-none").hide().fadeIn(400);
        }
      };
      // Dosyayı oku
      reader.readAsBinaryString(file);
    }
  });

/*Excelden Yüklemek için*/
$(document).on("click", "#excel_yukle_buton", function (e) {
  e.preventDefault();
  const fileInput = document.getElementById("excel_dosya");
  const loadingOverlay = document.getElementById("loading-overlay");

  if (!fileInput.files.length) {
    swal.fire({
      title: "Hata",
      text: "Lütfen bir dosya seçin.",
      icon: "error",
      confirmButtonText: "Tamam",
    });
    return;
  }

  const formData = new FormData();
  formData.append("action", "excel_upload_isyeri");
  formData.append("excelFile", fileInput.files[0]);

  loadingOverlay.style.display = "flex"; // CSS'te flex kullandığımız için 'flex' yapıyoruz

  fetch(url, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
     
      // console.log(data); // Konsola yanıtı yazdır
      const title = data.status === "success" ? "Başarılı" : "Hata";
      loadingOverlay.style.display = "none"; // Yükleme overlay'ini gizle
      swal.fire({
        title: title,
        html: data.message,
        icon: data.status,
        confirmButtonText: "Tamam",
      }).then(() => {
        if(data.status === "success"){
          window.location.reload(); // Sayfayı yeniden yükle
        }
      });

    })
    .catch((error) => {
      console.error("Error:", error);
      loadingOverlay.style.display = "none"; // Yükleme overlay'ini gizle

      swal.fire({
        title: "Hata",
        text: "Dosya yüklenirken bir hata oluştu.!!",
        icon: "error",
        confirmButtonText: "Tamam",
      })
      .then(()=>{
        window.location.reload(); // Sayfayı yeniden yükle
      })
      ;
    });
});
$(document).on("click", "#form_temizle_buton", function () {
  const fileInput = document.getElementById("excel_dosya");
  fileInput.value = "";

  const alertDiv = document.querySelector(".alert-description");

  $(alertDiv)
    .html(
      "Yüklenen dosya bilgileri temizlendi. Lütfen yeni bir dosya yükleyin."
    )
    .fadeIn(400);
    $(alertDiv).removeClass("d-none").addClass("alert-info");

  setTimeout(() => {
    $(alertDiv).fadeOut(400);
  }, 4000);

});
