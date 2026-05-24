let url = "App/Api/APIrapor_onay.php"; // API URL'si

$(document).ready(function () {
  $(document).on("click", ".btn-onayla", function () {
    let satirElement = $(this).closest("tr, .mobile-rapor-card"); // Butonun bulunduğu satırı/kartı al
    let raporData = satirElement.data("rapor"); // Satırdaki rapor verisini al

    // Nitelik durumunu seçili değere göre ayarla
    let nitelikDurumu = satirElement
      .find(".nitelik-durumu option:selected")
      .val();

    // Eğer nitelik durumu seçilmemişse kullanıcıyı uyar
    if (nitelikDurumu === undefined) {
      swal.fire({
        icon: "warning",
        title: "Uyarı",
        text: "Lütfen nitelik durumunu seçiniz."
      });
      return;
    }

    // API'ye gönderilecek payload
    let payload = {
      MEDULARAPORID: raporData.MEDULARAPORID,
      TCKIMLIKNO: raporData.TCKIMLIKNO,
      RAPORTAKIPNO: raporData.RAPORTAKIPNO,
      ABASTAR: raporData.ABASTAR,
      ABITTAR: raporData.ABITTAR,
      VAKA: raporData.VAKAADI,
      SIGORTALIADSOYAD: raporData.SIGORTALIADSOYAD,
      POLIKLINIKTAR: raporData.POLIKLINIKTAR,
      ISBASKONTTAR: raporData.ISBASKONTTAR,
      VAKAADI: raporData.VAKAADI,
      nitelikDurumu: nitelikDurumu,
      raporBitisTarihi: raporData.ABITTAR, // İşbaşı tarihi
      RAPORDURUMU: raporData.RAPORDURUMU,
      EKRANTARIHI: raporData.EKRANTARIHI,
      TESISKODU: raporData.TESISKODU,
      BRANSKODU: raporData.BRANSKODU,
    };

    callApi("raporOnayla", payload, satirElement);
  });

  // Tekrarlanan fetch kodunu bir fonksiyona alalım
  function callApi(action, payload, satirElement) {
    fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        action,
        ...payload
      })
    })
      .then((response) => response.json())
      .then((data) => {
        console.log(data);

        let title = data.status === "success" ? "Başarılı!" : "Hata!";
        swal
          .fire({
            icon: data.status,
            title: title,
            text: data.message
          })
          .then(() => {
            if (data.status === "success") {
              window.open(data.redirectUrl, "_blank");
            }
          });
        satirElement.remove(); // Başarılı olursa satırı tablodan kaldır
      })
      .catch((error) => {
        swal.fire({
          icon: "error",
          title: "Hata",
          text: "Bir hata oluştu: " + error.message
        });
      });
  }
});

//Okundu Olarak işaretler
$(document).on("click", ".btn-kapat", function () {
  let satirElement = $(this).closest("tr, .mobile-rapor-card"); // Butonun bulunduğu satırı/kartı al
  let raporId = $(this).data("id"); // Buton üzerindeki data-id'yi al
  
  if (!raporId && satirElement.data("rapor")) {
    raporId = satirElement.data("rapor").MEDULARAPORID;
  }

  // API'ye gönderilecek payload
  let payload = {
    MEDULARAPORID: raporId
  };

  swal.fire({
    title: "Emin misiniz?",
    text: "Rapor kapatılacaktır.Ancak eğer rapor onaylı değilse SGK'nın sitesinde görünmeye devam edecektir.Bu raporu okundu olarak işaretlemek istediğinize emin misiniz?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Evet, işaretle",
    cancelButtonText: "Hayır, iptal et",
    reverseButtons: true

  }).then((result) => {
    if (result.isConfirmed) {
      // return
  fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      action: "raporOkunduKapat",
      ...payload
    })
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        swal.fire("Başarılı!", data.message, "success");
        satirElement.remove(); // Başarılı olursa satırı tablodan kaldır
      } else {
        swal.fire({
          icon: "error",
          title: "Hata",
          text:
            data.message || "Rapor okundu olarak işaretleme işlemi başarısız."
        });
      }
    })
    .catch((error) => {
      swal.fire({
        icon: "error",
        title: "Hata",
        text: "Bir hata oluştu: " + error.message
      });
    });
    }
  });

});

// Personelim Değil Bildirimi
$(document).on("click", ".btn-personel-degil", function (e) {
  e.preventDefault();
  let satirElement = $(this).closest("tr, .mobile-rapor-card"); // Butonun bulunduğu satırı/kartı al
  let raporData = satirElement.data("rapor"); // Satırdaki rapor verisini al

  let payload = {
    MEDULARAPORID: raporData.MEDULARAPORID,
    TCKIMLIKNO: raporData.TCKIMLIKNO,
    VAKA: raporData.VAKAADI
  };

  swal.fire({
    title: "Emin misiniz?",
    text: "Bu personelin sizin işyerinizde çalışmadığını bildirmek istediğinize emin misiniz?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Evet, bildir",
    cancelButtonText: "Hayır, iptal et"
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "personelimDegil",
          ...payload
        })
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success || data.status === "success") {
            swal.fire("Başarılı!", data.message || "Personel bildirim işlemi başarılı.", "success");
            satirElement.remove(); // Başarılı olursa satırı/kartı kaldır
          } else {
            swal.fire({
              icon: "error",
              title: "Hata",
              text: data.message || "İşlem başarısız."
            });
          }
        })
        .catch((error) => {
          swal.fire({
            icon: "error",
            title: "Hata",
            text: "Bir hata oluştu: " + error.message
          });
        });
    }
  });
});

$(document).ready(function () {
  // --- YENİ FİLTRELEME KODU ---
  const $checkbox = $("#kisa-rapor-goster-cb");
  const $raporSatirlari = $("tbody tr[data-gun-farki], .mobile-rapor-card[data-gun-farki]");
  const $raporSayiBilgisi = $("#rapor-sayi-bilgisi");
  const toplamRaporSayisi = parseInt($("#toplam-rapor-sayisi").val(), 10) || 0;

  // Filtreleme fonksiyonu
  function filtreleRaporlari() {
    let gosterilenSayisi = 0;

    const kisaRaporlariGoster = $checkbox.is(":checked");

    $raporSatirlari.each(function () {
      const $satir = $(this);
      const gunFarki = parseInt($satir.data("gun-farki"), 10);

      if (kisaRaporlariGoster || gunFarki >= 3) {
        // Eğer checkbox işaretliyse VEYA rapor 3+ günlükse göster
        $satir.show(); // Satırı görünür yap
        if ($satir.is("tr")) {
          gosterilenSayisi++;
        }
      } else {
        // Değilse gizle
        $satir.hide();
      }
    });

    // Eğer gösterilen satır sayısı 0 ise bilgi mesajı göster
    if (gosterilenSayisi === 0) {
      $("#rapor-yok-mesaji").show();
      $("#mobile-rapor-yok-mesaji").show();
    } else {
      $("#rapor-yok-mesaji").hide();
      $("#mobile-rapor-yok-mesaji").hide();
    }

    // Bilgi metnini güncelle
    if (kisaRaporlariGoster) {
      $raporSayiBilgisi.html(`
                ${formatDate(new Date())} tarihine kadar SGK'dan
                bulunan toplam <strong>${toplamRaporSayisi}</strong> raporun tamamı listelenmektedir.
                `);
    } else {
      $raporSayiBilgisi.html(`
                ${formatDate(new Date())} tarihine kadar SGK'dan
                bulunan toplam <strong>${toplamRaporSayisi}</strong> rapor içerisinden,
                rapor süresi 3 gün ve daha uzun olan <strong>${gosterilenSayisi}</strong> rapor
                listelenmektedir.
                `);
    }
  }
  // Tarih formatı için helper fonksiyon
  function formatDate(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}.${month}.${year}`;
  }

  // Checkbox'a her tıklandığında filtreleme fonksiyonunu çalıştır
  $checkbox.on("change", filtreleRaporlari);

  // Sayfa ilk yüklendiğinde de filtrelemeyi çalıştır (başlangıç durumu için)
  filtreleRaporlari();
});
