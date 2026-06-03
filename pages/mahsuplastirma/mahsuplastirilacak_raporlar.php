<?php
use App\Helper\Security;
Security::checkUserRole();

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$hataMesaji = '';
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';
$title = 'Mahsuplaştırılacak Raporlar';
$mahsuplasmaRaporlari = []; // Yeni değişken adı
$hataMesaji = '';
$formGonderildi = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sorgula_buton'])) {
    $formGonderildi = true;
    if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
        $hataMesaji = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {
        try {
            $sgkClient = new SgkViziteService();

            $tarih1 = new DateTime($_POST['tarih1']);
            $tarih2 = new DateTime($_POST['tarih2']);



            // Yeni metodumuzu çağırıyoruz
            $mahsuplasmaRaporlari = $sgkClient->mahsuplastirilacakRaporlariGetir($tarih1, $tarih2);
        } catch (Exception $e) {
            $hataMesaji = $e->getMessage();
        }
    }
}
?>
<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>

<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>

<!-- ANA İÇERİK BÖLÜMÜ -->
<div class="animate-in flex flex-col gap-6 w-full py-2 px-1">
    <!-- Sayfa Başlığı -->
    <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Mahsuplaştırılacak Raporlar
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                SGK sistemi üzerinden gelen, mahsuplaştırma bekleyen vizite raporlarının takibi ve sorgulaması.
            </p>
        </div>
    </div>


    <!-- Sorgulama Formu -->
    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm">
        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <i data-lucide="search" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                Mahsuplaştırılacak Rapor Sorgula
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Sorgulamak istediğiniz tarih aralığını belirleyin.
            </p>
        </div>

        <form method="post" class="flex flex-col md:flex-row md:items-end gap-4 w-full">
            <div class="form-group flex-1">
                <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="tarih1">Başlangıç
                    Tarihi</label>
                <input type="date" id="tarih1" name="tarih1" class="form-input"
                    value="<?php echo $_POST['tarih1'] ?? date('Y-m-01'); ?>">
            </div>
            <div class="form-group flex-1">
                <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="tarih2">Bitiş
                    Tarihi</label>
                <input type="date" id="tarih2" name="tarih2" class="form-input"
                    value="<?php echo $_POST['tarih2'] ?? date('Y-m-d'); ?>">
            </div>
            <button type="submit" name="sorgula_buton"
                class="btn btn-primary h-9 px-4 flex items-center justify-center gap-1.5 shadow cursor-pointer self-start md:self-auto">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>Sorgula</span>
            </button>
        </form>
    </div>

    <!-- Sonuçlar Bölümü -->
    <?php if ($formGonderildi): ?>
    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm">
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
            <div>
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                    <i data-lucide="list" style="width: 18px; height: 18px;"
                        class="text-zinc-700 dark:text-zinc-300"></i>
                    Mahsuplaştırılacak Ödeme Listesi
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Sorgu sonucunda bulunan ödeme kayıtları.</p>
            </div>

            <?php if (!empty($mahsuplasmaRaporlari)): ?>
            <div class="flex items-center gap-2 self-end md:self-auto">
                <button type="button"
                    class="btn btn-outline h-9 px-3 flex items-center gap-1.5 text-xs font-semibold shadow-sm cursor-pointer excel-aktar">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                    <span>Excel'e Aktar</span>
                </button>
                <button type="button"
                    class="btn btn-primary h-9 px-3 flex items-center gap-1.5 text-xs font-semibold shadow cursor-pointer secilenleri-mahsuplastir"
                    disabled>
                    <i data-lucide="check-check" class="w-4 h-4"></i>
                    <span>Seçilenleri Mahsuplaştır</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($hataMesaji): ?>
        <div
            class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Hata!</h4>
                <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
        <?php else: ?>
        <div
            class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm mt-2">
            <table class="w-full border-collapse text-left dataTable js-exportable">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[50px] text-center">
                            <label class="label justify-center cursor-pointer m-0 p-0"
                                style="display: inline-flex; min-height: 0;">
                                <input id="select_all" type="checkbox" class="input select-all cursor-pointer"
                                    style="margin: 0;">
                            </label>
                        </th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[60px] text-center">
                            Sıra</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            TC Kimlik No</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Ad Soyad</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Vaka</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Başlangıç</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Bitiş</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Ödenen Tutar</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-right pr-6 w-[80px]">
                            İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php if (!empty($mahsuplasmaRaporlari)): $i = 0; ?>
                    <?php foreach ($mahsuplasmaRaporlari as $rapor): $i++; ?>
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors"
                        data-rapor='<?php echo htmlspecialchars(json_encode($rapor)); ?>'>
                        <td class="py-3 px-4 text-center">
                            <label class="label justify-center cursor-pointer m-0 p-0"
                                style="display: inline-flex; min-height: 0;">
                                <input id="checkbox_<?php echo $rapor['id']; ?>"
                                    class="input rapor-checkbox cursor-pointer" type="checkbox"
                                    value="<?php echo $rapor['id']; ?>" style="margin: 0;">
                            </label>
                        </td>
                        <td class="py-3 px-4 text-xs font-medium text-zinc-500 dark:text-zinc-400 text-center">
                            <?php echo $i; ?></td>
                        <td class="py-3 px-4 text-xs font-mono text-zinc-700 dark:text-zinc-300">
                            <?php echo htmlspecialchars($rapor['tcKimlikNo'] ?? ''); ?></td>
                        <td class="py-3 px-4 text-xs font-bold text-zinc-900 dark:text-zinc-50">
                            <?php echo htmlspecialchars($rapor['adiSoyadi'] ?? ''); ?></td>
                        <td class="py-3 px-4 text-xs">
                            <?php
                                            $vakaBadgeClass = 'border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300';
                                            if (stripos($rapor['vakaAdi'], 'ANALIK') !== false) {
                                                $vakaBadgeClass = 'border-pink-200 dark:border-pink-900/30 bg-pink-50 dark:bg-pink-950/20 text-pink-700 dark:text-pink-300';
                                            } elseif (stripos($rapor['vakaAdi'], 'HASTALIK') !== false) {
                                                $vakaBadgeClass = 'border-blue-200 dark:border-blue-900/30 bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300';
                                            } elseif (stripos($rapor['vakaAdi'], 'KAZASI') !== false) {
                                                $vakaBadgeClass = 'border-amber-200 dark:border-amber-900/30 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300';
                                            }
                                            ?>
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold transition-all <?php echo $vakaBadgeClass; ?>">
                                <?php echo htmlspecialchars($rapor['vakaAdi']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-center text-zinc-600 dark:text-zinc-400 font-medium">
                            <?php echo htmlspecialchars($rapor['odemeBasTar'] ?? ''); ?></td>
                        <td class="py-3 px-4 text-xs text-center text-zinc-600 dark:text-zinc-400 font-medium">
                            <?php echo htmlspecialchars($rapor['odemeBitTar'] ?? ''); ?></td>
                        <td class="py-3 px-4 text-xs text-center font-bold text-zinc-900 dark:text-zinc-50">
                            <?php echo htmlspecialchars($rapor['odenenTutar'] ?? ''); ?> TL</td>
                        <td class="py-3 px-4 text-right pr-6">
                            <button type="button" class="btn btn-icon-sm mahsuplastir" title="Mahsuplaştır"
                                style="display: inline-flex; margin-left: auto;">
                                <i data-lucide="check-check" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr id="rapor-yok-mesaji">
                        <td colspan="9" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="inbox" class="w-8 h-8 opacity-45"></i>
                                <span>Belirtilen kriterlere uygun mahsuplaştırılacak rapor bulunamadı.</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- HTML5 Custom Confirm Dialog Component -->
<dialog id="alert-dialog" class="dialog w-full sm:max-w-[425px] max-h-[612px]" aria-labelledby="alert-dialog-title"
    aria-describedby="alert-dialog-description" onclick="if(event.target === this) this.close()">
    <div>
        <header>
            <h2 id="alert-dialog-title">Emin misiniz?</h2>
            <p id="alert-dialog-description"></p>
        </header>
        <footer>
            <button type="button" class="btn-outline" onclick="document.getElementById('alert-dialog').close()">Vazgeç</button>
            <button type="button" id="dialog-confirm-btn" class="btn-primary">Devam Et</button>
        </footer>
    </div>
</dialog>




<!-- Script'leri dahil ediyoruz -->
<?php include 'layouts/vendor-scripts.php'; ?>

<style>
.form-label {
    font-size: 0.8125rem !important;
    font-weight: 500 !important;
    margin-bottom: 0.375rem !important;
    color: var(--foreground) !important;
}

.form-input {
    font-size: 0.8125rem !important;
    height: 36px !important;
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
    width: 100% !important;
    border-radius: 6px !important;
    border: 1px solid var(--border) !important;
    background: var(--background) !important;
    color: var(--foreground) !important;
    box-sizing: border-box !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

.form-input:focus {
    outline: none !important;
    border-color: hsl(var(--primary)) !important;
}

/* Premium btn-icon-sm Styling */
.btn-icon-sm {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 28px !important;
    height: 28px !important;
    padding: 0 !important;
    background: transparent !important;
    border: 1px solid var(--border) !important;
    border-radius: 6px !important;
    color: var(--muted-foreground) !important;
    transition: all 0.2s !important;
    cursor: pointer !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}


.btn-icon-sm:hover {
    background: var(--muted) !important;
    border-color: var(--border) !important;
    color: var(--foreground) !important;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite !important;
    display: inline-block !important;
}
</style>

<script>
(function() {
    function init() {
        if (window.lucide) {
            lucide.createIcons();
        }

        //Tarih1 alanında seçim yapınca tarih2 alanını o ayın son günü yap
        const tarih1Input = $('#tarih1');
        const tarih2Input = $('#tarih2');

        tarih1Input.on('change', function() {
            let tarih1 = new Date(this.value);
            if (isNaN(tarih1)) return;

            // Ayın son günü hesapla
            let yil = tarih1.getFullYear();
            let ay = tarih1.getMonth() + 1; // Aylar 0’dan başlar
            let sonGun = new Date(yil, ay, 0).getDate();

            // YYYY-MM-DD formatına çevir
            let ayStr = String(ay).padStart(2, '0');
            let gunStr = String(sonGun).padStart(2, '0');
            let tarih2 = `${yil}-${ayStr}-${gunStr}`;

            tarih2Input.val(tarih2);
        });

        // Excel Aktar Butonu
        const excelBtn = document.querySelector('.excel-aktar');
        if (excelBtn) {
            excelBtn.addEventListener('click', function() {
                // DataTables'ın kendi excel butonunu tetikle
                const dtButton = document.querySelector('.buttons-excel');
                if (dtButton) {
                    dtButton.click();
                } else {
                    // Eğer buton yoksa manuel export (DataTables exportable zaten açık olmalı)
                    $('.js-exportable').DataTable().button('.buttons-excel').trigger();
                }
            });
        }

        // Custom alert-dialog components reference
        const alertDialog = document.getElementById('alert-dialog');
        const dialogTitle = document.getElementById('alert-dialog-title');
        const dialogDescription = document.getElementById('alert-dialog-description');
        const dialogConfirmBtn = document.getElementById('dialog-confirm-btn');
        let pendingConfirmAction = null;

        if (dialogConfirmBtn) {
            // Unbind any previous listener just in case
            const newConfirmBtn = dialogConfirmBtn.cloneNode(true);
            dialogConfirmBtn.parentNode.replaceChild(newConfirmBtn, dialogConfirmBtn);
            newConfirmBtn.addEventListener('click', function() {
                if (pendingConfirmAction) {
                    const cancelBtn = alertDialog.querySelector('.btn-outline');
                    if (cancelBtn) cancelBtn.disabled = true;
                    
                    newConfirmBtn.disabled = true;
                    const originalHTML = newConfirmBtn.innerHTML;
                    newConfirmBtn.innerHTML = '<span class="flex items-center gap-1.5"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> İşlem yapılıyor...</span>';
                    if (window.lucide) lucide.createIcons();

                    const promise = pendingConfirmAction();
                    if (promise && typeof promise.finally === 'function') {
                        promise.finally(() => {
                            newConfirmBtn.disabled = false;
                            newConfirmBtn.innerHTML = originalHTML;
                            if (cancelBtn) cancelBtn.disabled = false;
                            alertDialog.close();
                        });
                    } else {
                        newConfirmBtn.disabled = false;
                        newConfirmBtn.innerHTML = originalHTML;
                        if (cancelBtn) cancelBtn.disabled = false;
                        alertDialog.close();
                    }
                } else {
                    alertDialog.close();
                }
            });
        }

        // Seçilenleri Mahsuplaştır Butonu
        const secilenleriMahsuplastirBtn = document.querySelector('.secilenleri-mahsuplastir');
        const selectAllCheckbox = document.getElementById('select_all');
        const raporCheckboxes = document.querySelectorAll('.rapor-checkbox');

        if (secilenleriMahsuplastirBtn) {
            // Checkbox değişimlerini izle
            function updateBulkButtonState() {
                const checkedCount = document.querySelectorAll('.rapor-checkbox:checked').length;
                secilenleriMahsuplastirBtn.disabled = checkedCount === 0;
                if (checkedCount === 0) {
                    secilenleriMahsuplastirBtn.innerHTML =
                        '<i data-lucide="check-check" class="w-4 h-4"></i> Seçilenleri Mahsuplaştır';
                } else {
                    secilenleriMahsuplastirBtn.innerHTML =
                        `<i data-lucide="check-check" class="w-4 h-4"></i> Seçilenleri Mahsuplaştır (${checkedCount})`;
                }
                if (window.lucide) {
                    lucide.createIcons();
                }
            }

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    raporCheckboxes.forEach(cb => {
                        cb.checked = this.checked;
                    });
                    updateBulkButtonState();
                });
            }

            raporCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkButtonState);
            });

            secilenleriMahsuplastirBtn.addEventListener('click', function() {
                const checkedBoxes = document.querySelectorAll('.rapor-checkbox:checked');
                const secilenRaporlar = [];

                checkedBoxes.forEach(cb => {
                    const satir = cb.closest('tr');
                    const raporData = JSON.parse(satir.dataset.rapor);
                    secilenRaporlar.push(raporData);
                });

                if (secilenRaporlar.length === 0) return;

                dialogTitle.textContent = "Toplu Mahsuplaştırma Onayı";
                dialogDescription.textContent =
                    `${secilenRaporlar.length} adet raporu toplu olarak mahsuplaştırmak istediğinize emin misiniz?`;

                pendingConfirmAction = function() {
                    var formData = new FormData();
                    formData.append('action', 'mahsuplastiOnaylaToplu');
                    formData.append('raporData', JSON.stringify(secilenRaporlar));

                    return fetch('App/Api/APImahsuplastirma.php', {
                            method: 'POST',
                            body: formData,
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showToast(data.message, "success");
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                showToast("İşlem başarısız oldu: " + data.message, "error");
                            }
                        })
                        .catch(error => {
                            showToast("Sunucuya ulaşılamadı: " + error, "error");
                        });
                };

                alertDialog.showModal();
            });
        }

        // Tekli mahsuplaştırma butonları
        const butonlar = document.querySelectorAll('.mahsuplastir');

        butonlar.forEach(buton => {
            buton.addEventListener('click', function(event) {
                const satir = this.closest('tr'); // Butonun bulunduğu satırı bul
                const raporData = JSON.parse(satir.dataset
                    .rapor); // Satırın data-rapor özelliğindeki JSON verisini al

                dialogTitle.textContent = "Mahsuplaştırma Onayı";
                dialogDescription.textContent =
                    `${raporData.adiSoyadi} kişisine ait raporu mahsuplaştırmak istediğinize emin misiniz?`;

                pendingConfirmAction = function() {
                    var formData = new FormData();
                    formData.append('action', 'mahsuplastiOnayla');
                    formData.append('raporData', JSON.stringify(raporData));

                    return fetch('App/Api/APImahsuplastirma.php', {
                            method: 'POST',
                            body: formData,
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showToast(data.message, "success");
                                satir.style.transition = "opacity 0.5s ease";
                                satir.style.opacity = "0";
                                setTimeout(() => {
                                    satir.remove();
                                }, 500); // 0.5 saniye bekle
                            } else {
                                showToast("İşlem başarısız oldu: " + data.message, "error");
                            }
                        })
                        .catch(error => {
                            showToast("Sunucuya ulaşılamadı: " + error, "error");
                        });
                };

                alertDialog.showModal();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php include 'layouts/foot.php'; ?>