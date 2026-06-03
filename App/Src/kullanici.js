(function() {
let url = "App/Api/APIuser.php";

$(document).on("click", '[data-bs-target="#defaultModal"]', function() {
  if (!$(this).hasClass('kullanici-duzenle')) {
    $("#kullanici_id").val(0);
    $("#altKullaniciForm")[0].reset();
    $("#isyerleri_ids").val([]).trigger("change");
    $('input[name="yetkiler[]"]').prop('checked', false);
    $("#defaultModalLabel").text("Yeni Alt Kullanıcı Ekle");
  } else {
    $("#defaultModalLabel").text("Alt Kullanıcı Düzenle");
  }
});

$(document).on("change", "#isyerleri_ids", function () {
  if ($.fn.valid && $("#altKullaniciForm").data("validator")) {
    $(this).valid();
  }
});

$(document).on("click", "#kaydetButton", function (e) {
  var form = $("#profileForm");

  $(form).validate({
    rules: {
      adi_soyadi: {
        required: true
      },
      telefon: {
        required: true
      },
      mevcut_sifre: {
        required: true
      },

    },

    messages: {
      adi_soyadi: {
        required: "Lütfen adınızı ve soyadınızı giriniz."
      },
      telefon: {
        required: "Lütfen telefon numaranızı giriniz."
      },
      mevcut_sifre: {
        required: "Lütfen mevcut şifrenizi giriniz."
      },
      isyerleri_ids: {
        required: "Lütfen en az bir işyeri seçiniz."
      }
    }
  });

  if (!$(form).valid()) {
    return false;
  }

  var formData = new FormData(form[0]);

  formData.append("action", "profil-guncelle");
  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      showToast(data.message, data.status);
    });
});

$(document).on("click", "#bildirimKaydetButton", function () {
  var form = $("#bildirimForm");

  var formData = new FormData(form[0]);

  formData.append("action", "bildirim-guncelle");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }

  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      showToast(data.message, data.status);
    });
});

//Hesabımı Sil Butonu
$(document).on("click", ".delete-account", function () {
  var form = $("#deleteAccountForm");
  var formData = new FormData(form[0]);

  form.validate({
    rules: {
      sifre: {
        required: true
      }
    },

    messages: {
      sifre: {
        required: "Lütfen şifrenizi giriniz."
      }
    }
  });

  if (!$(form).valid()) {
    return false;
  }

  formData.append("action", "hesabimi-sil");
  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      showToast(data.message, data.status);
      if (data.status === "success") {
        setTimeout(function() {
          window.location.href = "logout";
        }, 1500);
      }
    });
});

//Alt Kullanıcı kaydet butonu
$(document).on("click", ".alt-kullanici-kaydet", function () {
  var form = $("#altKullaniciForm");
  var $btn = $(this);

  form.validate({
    ignore: ".ignore, :hidden:not(#isyerleri_ids)",
    errorElement: "span",
    errorClass: "text-red-600 dark:text-red-400 text-xs mt-1 block font-medium",
    rules: {
      kullanici_adi: {
        required: true
      },
      email: {
        required: true,
        email: true
      },
      sifre: {
        required: function () {
          // Kullanıcı ID'si yoksa (yeni kayıt) şifre zorunlu
          return $("#kullanici_id").val() == 0;
        },
        minlength: 6
      },
      "isyerleri_ids[]": {
        required: true
      }
    },

    messages: {
      kullanici_adi: {
        required: "Lütfen alt kullanıcı için kullanıcı adını giriniz."
      },
      email: {
        required: "Lütfen alt kullanıcı için emailini giriniz.",
        email: "Lütfen geçerli bir email adresi giriniz."
      },
      sifre: {
        required: "Lütfen alt kullanıcı için kullanıcı şifresini giriniz.",
        minlength: "Şifre en az 6 karakter olmalıdır."
      },
      "isyerleri_ids[]": {
        required: "Lütfen en az bir işyeri seçiniz."
      }
    },
    errorPlacement: function (error, element) {
      if (element.attr('id') === 'isyerleri_ids') {
        error.insertAfter(element.next('.select2-container'));
      } else if (element.parent('.relative').length) {
        error.insertAfter(element.parent('.relative'));
      } else {
        error.insertAfter(element);
      }
    }
  });

  if (!$(form).valid()) {
    return false;
  }



  var formData = new FormData(form[0]);

  formData.append("action", "alt-kullanici-olustur");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }


  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
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
    });
});

//ALt Kullanıcı Durumunu Aktif/Pasif Yapma
$(document).on("click", ".kullanici-durum", function () {
  var durum = $(this).data("durum");
  var kullanici_id = $(this).data("kullanici-id");

  let text =
    durum == 1
      ? "Kullanıcıyı aktif yapmak istediğinizden emin misiniz?"
      : "Kullanıcıyı pasif yapmak istediğinizden emin misiniz?";

  var formData = new FormData();
  formData.append("action", "kullanici-durum-guncelle");
  formData.append("durum", durum);
  formData.append("kullanici_id", kullanici_id);

  swal
    .fire({
      title: "Emin misiniz?",
      text: text,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Evet, devam et!",
      cancelButtonText: "Vazgeç"
    })
    .then((result) => {
      if (result.isConfirmed) {
        // Kullanıcı onayladı, işlemi gerçekleştir
        guncelleDurum(formData);
      }
    });

  function guncelleDurum(data) {
    fetch(url, {
      method: "POST",
      body: data
    })
      .then((response) => response.json())
      .then((data) => {
        console.log(data);
        showToast(data.message, data.status);
        if (data.status === "success") {
          if (window.App && App.refreshContent) {
            App.refreshContent();
          } else {
            location.reload();
          }
        }
      });
  }
});

//Alt Kullanıcı düzenle butonu
$(document).on("click", ".kullanici-duzenle", function () {
  var kullanici_id = $(this).data("kullanici-id");

  var formData = new FormData();
  formData.append("action", "kullanici-bilgilerini-getir");
  formData.append("kullanici_id", kullanici_id);

  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        let kullanici = data.user;
        // Başarılı ise modal içindeki alanları doldur
        $("#kullanici_id").val(kullanici.id);
        $("#kullanici_adi").val(kullanici.kullanici_adi);
        $("#adi_soyadi").val(kullanici.adi_soyadi);
        $("#email").val(kullanici.email);


        let isyeriIds = kullanici.yetkili_oldugu_isyeri_ids;

        // Diziye çevir
        if (typeof isyeriIds === "string") {
          isyeriIds = isyeriIds.split(",");
        }

        $("#isyerleri_ids").val(isyeriIds).trigger("change");

        // Yetkileri işaretle
        const yetkiler = kullanici.yetkiler ? kullanici.yetkiler.split(',') : [];
        $('input[name="yetkiler[]"]').prop('checked', false); // Önce temizle
        yetkiler.forEach(val => {
          $('input[name="yetkiler[]"][value="' + val + '"]').prop('checked', true);
        });

        // Modal'ı göster
        $("#defaultModal").modal("show");
      } else {
        // Hata durumunda kullanıcıya mesaj göster
        showToast(data.message, "error");
      }
    });
});

//Alt kullanıcı silme
$(document).on("click", ".alt-kullanici-sil", function () {
  var kullanici_id = $(this).data("kullanici-id");

  var formData = new FormData();
  formData.append("action", "alt-kullanici-sil");
  formData.append("kullanici_id", kullanici_id);

  swal
    .fire({
      title: "Emin misiniz?",
      text: "Alt kullanıcıyı silmek istediğinizden emin misiniz?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Evet, sil!",
      cancelButtonText: "Vazgeç"
    })
    .then((result) => {
      if (result.isConfirmed) {
        // Kullanıcı onayladı, işlemi gerçekleştir
        silAltKullanici(formData);
      }
    });

  function silAltKullanici(data) {
    fetch(url, {
      method: "POST",
      body: data
    })
      .then((response) => response.json())
      .then((data) => {
        console.log(data);
        showToast(data.message, data.status);
        if (data.status === "success") {
          if (window.App && App.refreshContent) {
            App.refreshContent();
          } else {
            location.reload();
          }
        }
      });
  }
});
})();
