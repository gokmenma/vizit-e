<?php

use App\Helper\Security;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$title = "İptal Edilen Raporlar";

$isyeri_id = $_SESSION['isyeri_id'] ?? 0;

try {
    $db = \Core\Database::getInstance()->getConnection();
    // İptal edilen raporları veritabanından çekelim
    $stmt = $db->prepare("SELECT * FROM onaylanan_raporlar WHERE isyeri_id = ? AND onay_durumu = 'iptal' ORDER BY updated_at DESC");
    $stmt->execute([$isyeri_id]);
    $iptalRaporlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $iptalRaporlar = [];
    $hataMesaji = $e->getMessage();
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
<section class="content">
    <div class="container">
        <div class="flex flex-col gap-6 w-full py-2 px-1">
            <!-- Header Bölümü -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">İptal Edilen Raporlar</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        Onayını iptal ettiğiniz ve SGK sisteminde tekrar işlem yapılmayı bekleyen raporları yönetin.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-zinc-100 dark:bg-zinc-800/80 text-zinc-800 dark:text-zinc-200">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        Toplam: <?php echo count($iptalRaporlar); ?> Adet Rapor
                    </span>
                </div>
            </div>

            <!-- Bilgilendirme Kutusu -->
            <div class="border rounded-xl p-4 flex gap-3 border-blue-200 bg-blue-50/50 text-blue-800 dark:border-blue-900/30 dark:bg-blue-950/20 dark:text-blue-300">
                <i data-lucide="help-circle" class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600 dark:text-blue-400"></i>
                <div>
                    <h4 class="font-bold text-sm">İptal Edilen Rapor Süreci Hakkında</h4>
                    <p class="text-xs mt-1 leading-relaxed opacity-90">
                        Bu sayfadaki raporlar, daha önce onaylanmış olup <strong>"Onay İptal"</strong> işlemi gerçekleştirdiğiniz vizite kayıtlarıdır. 
                        Bu işlemler SGK web servislerine anlık bildirilmiş ve ilgili raporların onay durumu kaldırılmıştır. 
                        Raporları tekrar SGK'ya bildirmek için sağdaki <strong>"Tekrar Onayla"</strong> butonunu kullanabilirsiniz.
                    </p>
                </div>
            </div>

            <!-- Filtreler ve Arama -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
                <div class="relative flex-1 max-w-sm">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                    <input type="text" id="rapor-search" placeholder="İsim, TCKN veya Rapor No ara..." 
                        class="h-9 w-full pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
            </div>

            <!-- Tablo Alanı -->
            <div class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
                <table class="w-full border-collapse text-left" id="iptal-rapor-table">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[60px] text-center">Sıra</th>
                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Sigortalı Bilgileri</th>
                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">Vaka Türü</th>
                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">Rapor Takip No</th>
                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">Poliklinik Tarihi</th>
                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">İptal Tarihi</th>
                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center pr-6 w-[160px]">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        <?php if (!empty($iptalRaporlar)): ?>
                            <?php
                            $i = 0;
                            foreach ($iptalRaporlar as $rapor):
                                $i++;
                                $enc_id = Security::encrypt($rapor['id']);
                                $medulaId = $rapor['MEDULARAPORID'];
                            ?>
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/10 transition-colors" data-search-text="<?php echo strtolower($rapor['SIGORTALIADSOYAD'] . ' ' . $rapor['TCKIMLIKNO'] . ' ' . $rapor['RAPORTAKIPNO']); ?>">
                                    <td class="py-3.5 px-4 text-xs font-medium text-zinc-500 dark:text-zinc-400 text-center"><?php echo $i; ?></td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex flex-col text-left">
                                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100"><?php echo htmlspecialchars($rapor['SIGORTALIADSOYAD']); ?></span>
                                            <span class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-500 mt-0.5">T.C.: <?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                            <?php echo htmlspecialchars($rapor['VAKAADI']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-xs font-semibold text-zinc-650 dark:text-zinc-400">
                                        <?php echo htmlspecialchars($rapor['RAPORTAKIPNO'] ?? '-'); ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                        <?php echo !empty($rapor['POLIKLINIKTAR']) ? date('d.m.Y', strtotime($rapor['POLIKLINIKTAR'])) : '-'; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-500">
                                        <?php echo !empty($rapor['updated_at']) ? date('d.m.Y H:i', strtotime($rapor['updated_at'])) : '-'; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center pr-6">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" class="tekrar-onayla h-8 px-3 border border-emerald-200 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-950/10 text-emerald-650 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 hover:text-emerald-700 dark:hover:text-emerald-400 rounded-md text-xs font-bold transition-all flex items-center gap-1 shadow-sm cursor-pointer"
                                                data-id="<?php echo $enc_id; ?>"
                                                data-name="<?php echo htmlspecialchars($rapor['SIGORTALIADSOYAD']); ?>">
                                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                                <span>Tekrar Onayla</span>
                                            </button>
                                            <button type="button" class="rapor-detay-btn h-8 w-8 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 rounded-md flex items-center justify-center transition-all shadow-sm cursor-pointer"
                                                data-id="<?php echo $enc_id; ?>"
                                                data-name="<?php echo htmlspecialchars($rapor['SIGORTALIADSOYAD']); ?>"
                                                data-tckn="<?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?>"
                                                data-medula="<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>"
                                                data-takip="<?php echo htmlspecialchars($rapor['RAPORTAKIPNO']); ?>"
                                                data-vaka="<?php echo htmlspecialchars($rapor['VAKAADI']); ?>"
                                                data-baslangic="<?php echo !empty($rapor['POLIKLINIKTAR']) ? date('d.m.Y', strtotime($rapor['POLIKLINIKTAR'])) : '-'; ?>"
                                                data-isebasi="<?php echo !empty($rapor['ISBASKONTTAR']) ? date('d.m.Y', strtotime($rapor['ISBASKONTTAR'])) : '-'; ?>"
                                                data-gun="<?php echo $rapor['rapor_gun_sayisi']; ?>"
                                                data-tesis="<?php echo htmlspecialchars($rapor['TESISKODU'] ?? '-'); ?>">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-xs font-semibold text-zinc-400 dark:text-zinc-650 bg-zinc-50/20 dark:bg-zinc-900/50">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="folder-open" class="w-8 h-8 opacity-45"></i>
                                        <span>Herhangi bir iptal edilmiş onay kaydı bulunmamaktadır.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Detail Modal -->
<dialog id="detail-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--foreground);">
            <i data-lucide="file-text" style="width: 20px; color: var(--primary);"></i> Rapor Detay Bilgisi
        </h2>
        <button onclick="this.closest('dialog').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
    </div>
    <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; text-align: left;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem; font-size: 0.825rem;">
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">Sigortalı Adı Soyadı:</span>
                <span id="detail-name" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">T.C. Kimlik Numarası:</span>
                <span id="detail-tckn" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">Medula Rapor ID:</span>
                <span id="detail-medula" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">Rapor Takip Numarası:</span>
                <span id="detail-takip" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">Vaka Türü:</span>
                <span id="detail-vaka" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">Poliklinik Tarihi:</span>
                <span id="detail-baslangic" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">İşbaşı / Kontrol Tarihi:</span>
                <span id="detail-isebasi" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">Rapor Gün Sayısı:</span>
                <span id="detail-gun" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem;">
                <span style="color: var(--muted-foreground); font-weight: 500;">Tesis Kodu:</span>
                <span id="detail-tesis" style="font-weight: 700; color: var(--foreground);"></span>
            </div>
        </div>
        <div style="margin-top: 1rem; width: 100%;">
            <button onclick="this.closest('dialog').close()" class="btn btn-outline w-full" style="height: 2.5rem; border-radius: 8px;">Kapat</button>
        </div>
    </div>
</dialog>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- Script Alanı -->
<script>
    // Lucide Icons'ları oluştur
    if (window.lucide) {
        lucide.createIcons();
    }

    // Client-side Arama Filtresi
    document.getElementById('rapor-search').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#iptal-rapor-table tbody tr');

        rows.forEach(row => {
            const searchText = row.getAttribute('data-search-text');
            if (searchText) {
                if (searchText.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });

    // Detay Butonu Tetikleyicisi
    document.querySelectorAll('.rapor-detay-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('detail-name').innerText = this.dataset.name;
            document.getElementById('detail-tckn').innerText = this.dataset.tckn;
            document.getElementById('detail-medula').innerText = this.dataset.medula;
            document.getElementById('detail-takip').innerText = this.dataset.takip;
            document.getElementById('detail-vaka').innerText = this.dataset.vaka;
            document.getElementById('detail-baslangic').innerText = this.dataset.baslangic;
            document.getElementById('detail-isebasi').innerText = this.dataset.isebasi;
            document.getElementById('detail-gun').innerText = this.dataset.gun + " Gün";
            document.getElementById('detail-tesis').innerText = this.dataset.tesis;

            const modal = document.getElementById('detail-modal');
            if (modal && typeof modal.showModal === 'function') {
                modal.showModal();
            }
        });
    });

    // Tekrar Onayla Butonu Tetikleyicisi
    document.querySelectorAll('.tekrar-onayla').forEach(btn => {
        btn.addEventListener('click', function() {
            const enc_id = this.dataset.id;
            const name = this.dataset.name;

            Swal.fire({
                title: 'Emin misiniz?',
                text: `${name} isimli çalışanın rapor onayını SGK web servislerine tekrar göndermek istediğinize emin misiniz?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'hsl(var(--primary))',
                cancelButtonColor: '#71717a',
                confirmButtonText: 'Evet, Onayla',
                cancelButtonText: 'Vazgeç',
                background: document.documentElement.classList.contains('dark') ? '#09090b' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#09090b'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'SGK Bildirimi Gönderiliyor',
                        text: 'Lütfen bekleyin, işlem yapılıyor...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX call to re-approve
                    fetch('App/Api/APIiptal_raporlar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type: 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'rapor_tekrar_onayla',
                            id: enc_id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Refresh the page to reload active and pending cancelled reports
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'SGK Bildirim Hatası',
                                text: data.message || 'Onaylama işlemi sırasında bir hata oluştu.',
                                confirmButtonColor: 'hsl(var(--primary))'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Sistem Hatası',
                            text: 'Bir sunucu bağlantı hatası meydana geldi.',
                            confirmButtonColor: 'hsl(var(--primary))'
                        });
                    });
                }
            });
        });
    });
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>
