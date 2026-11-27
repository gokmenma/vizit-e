(function () {
const excelUploadUrl = "App/Api/APIisyerlerim.php";

function showExcelUploadInfo(message) {
  const excelUploadAlert = document.getElementById("excel-upload-alert");
  if (excelUploadAlert) {
    excelUploadAlert.innerHTML = message;
    excelUploadAlert.className =
      "rounded-lg border border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-300 px-4 py-3 text-xs leading-relaxed";
    return;
  }

  const legacyAlert = document.querySelector(".alert-description");
  if (legacyAlert) {
    $(legacyAlert).html(message).removeClass("d-none alert-danger").addClass("alert-info");
  }
}

function clearExcelUploadInfo() {
  const excelUploadAlert = document.getElementById("excel-upload-alert");
  if (excelUploadAlert) {
    excelUploadAlert.innerHTML = "";
    excelUploadAlert.classList.add("d-none");
  }
}

$(document).off("change.excelUpload").on("change.excelUpload", "#excel_dosya", function (event) {
  const fileInput = event.target;
  const file = fileInput.files[0];
  const allowedExtensions = /(\.xls|\.xlsx)$/i;

  clearExcelUploadInfo();
  if (!file) return;

  if (!allowedExtensions.test(file.name)) {
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
        showExcelUploadInfo(
          `Dosya hazır. Yüklenecek veri satırı: <strong>${satir_sayisi}</strong>`
        );
      }
    };
    // Dosyayı oku
    reader.readAsBinaryString(file);
  }
});

/*Excelden Yüklemek için*/
$(document).off("click.excelUpload").on("click.excelUpload", "#excel_yukle_buton", function (e) {
  e.preventDefault();
  const fileInput = document.getElementById("excel_dosya");
  const loadingOverlay = document.getElementById("loading-overlay");

  if (!fileInput || !fileInput.files.length) {
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

  if (loadingOverlay) {
    loadingOverlay.classList.remove("hidden");
    loadingOverlay.style.display = "flex";
  }

  fetch(excelUploadUrl, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      // console.log(data); // Konsola yanıtı yazdır
      const title = data.status === "success" ? "Başarılı" : "Hata";
      if (loadingOverlay) loadingOverlay.style.display = "none";
      swal.fire({
        title: title,
        html: data.message,
        icon: data.status,
        confirmButtonText: "Tamam",
      }).then(() => {
        if (data.status === "success") {
          window.location.reload(); // Sayfayı yeniden yükle
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
      if (loadingOverlay) loadingOverlay.style.display = "none";

      swal.fire({
        title: "Hata",
        text: "Dosya yüklenirken bir hata oluştu.!!",
        icon: "error",
        confirmButtonText: "Tamam",
      }).then(() => {
        window.location.reload(); // Sayfayı yeniden yükle
      });
    });
});

$(document).off("click.excelUploadClear").on("click.excelUploadClear", "#form_temizle_buton", function () {
  const fileInput = document.getElementById("excel_dosya");
  if (!fileInput) return;
  fileInput.value = "";

  showExcelUploadInfo("Dosya seçimi temizlendi. Yeni bir dosya seçebilirsiniz.");
});

$(document).off("hidden.bs.modal.excelUpload").on("hidden.bs.modal.excelUpload", "#excelUploadModal", function () {
  const excelFileInput = document.getElementById("excel_dosya");
  if (excelFileInput) excelFileInput.value = "";
  clearExcelUploadInfo();
  const loadingOverlay = document.getElementById("loading-overlay");
  if (loadingOverlay) loadingOverlay.style.display = "none";
});
})();
