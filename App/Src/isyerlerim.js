(function() {
let url = "App/Api/APIisyerlerim.php";

// Kaydet Butonuna Tıklama (Namespaced off/on ile event çoğalmasını engelledik)
$(document).off("click.isyeriKaydet").on("click.isyeriKaydet", ".isyeri-kaydet", function () {
  var form = $(this).closest("form");
  var $btn = $(this);
  const isyeriId = $("#isyeri_id").val();

  var formData = new FormData(form[0]);
  formData.append("action", "isyeri_kaydet");

  form.validate({
    rules: {
      firma_adi: {
        required: true
      },
      kullanici_adi: {
        required: true
      },
      isyeri_kodu: {
        required: true
      },
      ws_sifre: {
        required: function () {
          return isyeriId == 0 ? true : false;
        }
      }
    },
    messages: {
      firma_adi: {
        required: "Lütfen firma adını girin"
      },
      kullanici_adi: {
        required: "Lütfen kullanıcı adını girin"
      },
      isyeri_kodu: {
        required: "Lütfen işyeri kodunu girin"
      },
      ws_sifre: {
        required: "Lütfen webservis şifresini girin"
      }
    }
  });
  if (!form.valid()) {
    return;
  }
  $btn.startLoading();

  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => {
      if (response.ok) {
        return response.json();
      } else {
        throw new Error("Network response was not ok");
      }
    })
    .then((data) => {
      $btn.stopLoading();
      showToast(data.message, data.status);
      if (data.status === "success") {
        $("#defaultModal").modal("hide");
        if (window.App && App.refreshContent) {
          App.refreshContent();
        } else {
          location.reload();
        }
      }
    })
    .catch((error) => {
      $btn.stopLoading();
      console.error("Fetch error:", error);
      showToast("Bir hata oluştu", "error");
    });
});

// İşyeri Düzenleme (Namespaced off/on ile event çoğalmasını engelledik)
$(document).off("click.isyeriDuzenle").on("click.isyeriDuzenle", ".isyeri-duzenle", function () {
  var id = $(this).data("id");
  var modal = $("#defaultModal");
  $("#isyeri_id").val(id); // Düzenleme için isyeri_id'yi ayarla

  var formData = new FormData();
  formData.append("action", "isyeri_getir");
  formData.append("isyeri_id", id);

  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === true || data.status === "success") {
        var isyeri = data.isyeri;

        // Modal içeriğini doldur
        $("#firma_adi").val(isyeri.firma_adi);
        $("#kullanici_adi").val(isyeri.kullanici_kodu);
        $("#isyeri_kodu").val(isyeri.isyeri_kodu);
        $("#otomatik_rapor_onay").prop(
          "checked",
          isyeri.otomatik_rapor_onay == 1
        );
        if (isyeri.otomatik_rapor_onay == 1) {
          $(".otomatik-onay-eposta").removeClass("d-none");
        } else {
          $(".otomatik-onay-eposta").addClass("d-none");
        }
        $("#otomatik_onay_eposta").val(isyeri.otomatik_onay_eposta);
        $("#ws_sifre").val("");

        //Modali göster
        modal.modal("show");
      } else {
        showToast(data.message, "error");
      }
    })
    .catch((error) => console.error("Fetch error:", error));
});

// İşyerini Silme Dialog Tetikleyici
$(document).off("click.isyeriSil").on("click.isyeriSil", ".isyeri-sil", function (e) {
  e.preventDefault();
  var isyeriId = $(this).data("isyeri-id");
  var satir = $(this).closest("tr, .mobile-isyeri-card");

  //console.log("isyeri-sil clicked. ID:", isyeriId);

  var dialog = document.getElementById("alert-dialog");
  if (dialog) {
    // console.log("Dialog element found:", dialog);
    dialog.dataset.isyeriId = isyeriId;
    dialog._satir = satir;
    try {
      if (typeof dialog.showModal === "function") {
        dialog.showModal();
        console.log("dialog.showModal() called successfully.");
      } else {
        console.warn("showModal is not a function, falling back to confirmDialog.");
        fallbackConfirm(isyeriId, satir);
      }
    } catch (err) {
      console.error("Error opening dialog:", err);
      fallbackConfirm(isyeriId, satir);
    }
  } else {
    console.error("Dialog element #alert-dialog not found in DOM!");
    fallbackConfirm(isyeriId, satir);
  }
});

// Silme İptal Butonu
$(document).off("click.dialogCancel").on("click.dialogCancel", ".alert-dialog-cancel", function () {
  var dialog = document.getElementById("alert-dialog");
  if (dialog) {
    dialog.close();
  }
});

// Silme Onay Butonu
$(document).off("click.dialogConfirm").on("click.dialogConfirm", ".alert-dialog-confirm", function () {
  var dialog = document.getElementById("alert-dialog");
  if (!dialog) return;

  var isyeriId = dialog.dataset.isyeriId;
  var satir = dialog._satir;

  executeDelete(isyeriId, satir);
  dialog.close();
});

// İşyeri Seçme İşlemi (Yenilenmeden / SPA - Namespaced)
$(document).off("submit.isyeriSec").on("submit.isyeriSec", ".isyeri-sec-form", function (e) {
  e.preventDefault();
  var form = $(this);
  var formData = new FormData(form[0]);
  var isyeriId = form.find("input[name='isyeri_id']").val();
  var submitBtn = form.find("button[type='submit']");
  
  // Bulunduğu satırdan seçilen firmanın adını alalım
  var row = form.closest("tr");
  var firmaAdi = row.find("span.font-semibold").first().text().trim();

  submitBtn.startLoading();

  fetch("isyeri-sec", {
    method: "POST",
    body: formData,
    headers: {
      "X-Requested-With": "XMLHttpRequest"
    }
  })
    .then((response) => response.json())
    .then((data) => {
      submitBtn.stopLoading();
      if (data.status === "success") {
        showToast(data.message || "İşyeri başarıyla değiştirildi.", "success");
        
        // 1. Üst bardaki seçili firma adını yenilemeden güncelle
        $(".isyeri-dropdown summary span").text(firmaAdi);
        
        // 2. Üst bardaki dropdown listesinde aktif işareti taşı
        $(".isyeri-dropdown .isyeri-item").each(function() {
            var item = $(this);
            // Doğrudan isim eşleşmesi kontrolü ile eşleştiriyoruz (şifrelenmiş çakışmaları engellemek için)
            if (item.find("span").first().text().trim() === firmaAdi) {
                item.css("background", "rgba(37, 99, 235, 0.08)");
                item.css("font-weight", "600");
                if (item.find("[data-lucide='check']").length === 0) {
                    item.append('<i data-lucide="check" style="width: 14px; height: 14px; color: hsl(var(--primary)); flex-shrink: 0; margin-left: 0.5rem;"></i>');
                }
            } else {
                item.css("background", "");
                item.css("font-weight", "");
                item.find("[data-lucide='check']").remove();
            }
        });
        
        // 3. Lucide ikonlarını yeniden oluştur
        if (window.lucide) {
            window.lucide.createIcons();
        }

        // 4. Ana sayfaya (dashboard) yönlendir
        if (window.App && App.loadPage) {
            App.loadPage('dashboard', true);
        } else {
            window.location.href = "dashboard";
        }
      } else {
        showToast(data.message || "İşyeri değiştirilemedi.", "error");
      }
    })
    .catch((error) => {
      submitBtn.stopLoading();
      console.error("Fetch error:", error);
      showToast("Bir hata oluştu.", "error");
    });
});

// Fallback Confirmation Engine
function fallbackConfirm(isyeriId, satir) {
  if (window.confirmDialog) {
    window.confirmDialog({
      title: "Emin misiniz?",
      text: "Seçili firma kalıcı olarak silinecektir.",
      confirmButtonText: "Evet, Sil",
      cancelButtonText: "İptal"
    }).then((confirmed) => {
      if (confirmed) {
        executeDelete(isyeriId, satir);
      }
    });
  } else {
    if (confirm("Seçili firmayı silmek istediğinize emin misiniz?")) {
      executeDelete(isyeriId, satir);
    }
  }
}

// Global Delete Executor
function executeDelete(isyeriId, satir) {
  var formData = new FormData();
  formData.append("action", "isyeri_sil");
  formData.append("isyeri_id", isyeriId);

  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === true || data.status === "success") {
        if (satir) {
          satir.fadeOut(400, function () {
            $(this).remove();
          });
        }
        showToast(data.message, "success");
      } else {
        showToast(data.message || "İşyeri silinirken bir hata oluştu.", "error");
      }
    })
    .catch((error) => {
      console.error("Fetch error:", error);
      showToast("Bir ağ hatası oluştu.", "error");
    });
}

// Otomatik Rapor Onaya basınca email adresi textarea gizle-göster (Namespaced)
$(document).off("change.raporOnay", "#otomatik_rapor_onay").on("change.raporOnay", "#otomatik_rapor_onay", function () {
  if ($(this).is(":checked")) {
    $(".otomatik-onay-eposta").removeClass("d-none");
  } else {
    $(".otomatik-onay-eposta").addClass("d-none");
  }
});
})();
