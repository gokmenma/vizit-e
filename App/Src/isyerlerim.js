let url = "App/Api/APIisyerlerim.php";

$(document).on("click", ".isyeri-kaydet", function () {
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
      //console.log(data);
      var title = data.status == "success" ? "Başarılı" : "Hata";
      $btn.stopLoading();
      swal
        .fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam",
          allowOutsideClick: false
        })
        .then(() => {
          if (data.status == "success") {
            location.reload();
          }
        });
    })
    .catch((error) => {
      console.error("Fetch error:", error);
    });
});

//İsyeri Düzenleme
$(document).on("click", ".isyeri-duzenle", function () {
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
        swal.fire({
          title: "Hata",
          text: data.message,
          icon: "error",
          confirmButtonText: "Tamam"
        });
      }
    })
    .catch((error) => console.error("Fetch error:", error));
});

//İşyerini silme işlemi
$(document).on("click", ".isyeri-sil", function () {
  var isyeriId = $(this).data("isyeri-id");
  var satir = $(this).closest("tr");

  Swal.fire({
    title: "Emin misiniz?",
    text: "Seçili firma silinecektir!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    cancelButtonText: "Hayır, iptal et!",
    confirmButtonText: "Evet, sil!"
  }).then((result) => {
    if (result.isConfirmed) {
      var formData = new FormData();
      formData.append("action", "isyeri_sil");
      formData.append("isyeri_id", isyeriId);

      fetch(url, {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          console.log(data);

          if (data.status === true || data.status === "success") {
            satir.fadeOut(400, function () {
              $(this).remove();
            });
            // Silme başarılı ise sayfayı yenile
            Swal.fire({
              title: "Başarılı!",
              text: data.message,
              icon: "success"
            });
          }
        })
        .catch((error) => console.error("Fetch error:", error));
    }
  });
});

//Otomatik Rapor Onaya basınca email adresi textarea gizle-göster
$(document).on("change", "#otomatik_rapor_onay", function () {
  if ($(this).is(":checked")) {
    $(".otomatik-onay-eposta").removeClass("d-none");
  } else {
    $(".otomatik-onay-eposta").addClass("d-none");
  }
});
