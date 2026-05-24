/**
 * Progressive Web Application Core Logic for SGK Vizite Mobile
 * Handles Hash SPA Router, Touch Gestures, Bottom Sheets, and Workplace Switching
 */

window.App = window.App || {};

// SPA Page Loader for Mobile Shell
App.loadMobilePage = async (route) => {
    const container = document.getElementById('mobile-app-content');
    if (!container) return;

    // Show Mobile Loading Spinner
    container.innerHTML = `
        <div class="mobile-loader">
            <div class="spinner"></div>
            <span>Yükleniyor...</span>
        </div>
    `;

    // Synchronize bottom active tab
    App.updateBottomNav(route);

    try {
        // Fetch raw sub-page contents via AJAX route relative to root basepath
        const response = await fetch(route, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) throw new Error('Sayfa yüklenirken hata oluştu');

        const html = await response.text();

        // Clear existing page-level script elements to avoid memory leaks
        document.querySelectorAll('script[data-spa-page-script]').forEach(s => s.remove());

        container.innerHTML = html;

        // Re-inject and execute scripts in fetched contents
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.setAttribute('data-spa-page-script', 'true');
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        // Initialize interactive elements in mobile context
        if (window.lucide) window.lucide.createIcons();
        if (window.App && App.initGlobalSelect2) App.initGlobalSelect2(container);
        if (window.App && App.initGlobalFlatpickr) App.initGlobalFlatpickr(container);

        // Scroll mobile viewport to top on route change
        const viewport = document.getElementById('mobile-viewport');
        if (viewport) viewport.scrollTop = 0;

    } catch (err) {
        console.error('Mobile SPA Loader error:', err);
        container.innerHTML = `
            <div class="card p-4 m-4 text-center" style="background: var(--card); border: 1px solid var(--border); border-radius: 12px;">
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap: 0.5rem; padding: 1.5rem 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-rose-500" style="margin-bottom: 0.25rem;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3 style="font-weight: 700; font-size: 0.9rem;">Yükleme Hatası</h3>
                    <p style="font-size: 0.75rem; color: var(--muted-foreground); margin: 0.25rem 0;">${err.message || 'Lütfen bağlantınızı kontrol edin.'}</p>
                    <button onclick="App.refreshMobilePage()" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; height: auto; margin-top: 0.5rem;">Yeniden Dene</button>
                </div>
            </div>
        `;
        if (window.lucide) window.lucide.createIcons();
    }
};

// Update active states of Bottom Navigation Tabs
App.updateBottomNav = (route) => {
    const sgkRoutes = [
        'onay-bekleyen-raporlar', 
        'onayli-raporlar', 
        'manuel-rapor-bildirimi', 
        'arsivlenmis-raporlar', 
        'is-kazasi-bildirimi',
        'mahsuplastirilacak-raporlar',
        'mahsuplastirilan-raporlar',
        'prim-borcuna-mahsup-edilen-odemeler'
    ];
    const isyeriRoutes = ['isyerlerim'];
    const digerRoutes = ['kullanicilar', 'profile', 'iletisim-bilgileri', 'abonelik-paketleri'];

    let activeTabId = 'tab-home';
    if (sgkRoutes.includes(route)) {
        activeTabId = 'tab-sgk';
    } else if (isyeriRoutes.includes(route)) {
        activeTabId = 'tab-isyerleri';
    } else if (digerRoutes.includes(route)) {
        activeTabId = 'tab-diger';
    }

    document.querySelectorAll('.mobile-bottom-nav__item').forEach(item => {
        if (item.id === activeTabId) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
};

// Reload current page route
App.refreshMobilePage = () => {
    const rawRoute = window.location.hash.substring(1) || 'dashboard';
    App.loadMobilePage(rawRoute);
};

// Bottom Sheet Open helper
App.openBottomSheet = (sheetId) => {
    const overlay = document.getElementById('bottom-sheet-overlay');
    const sheet = document.getElementById(sheetId);
    
    if (overlay && sheet) {
        overlay.classList.add('active');
        sheet.classList.add('active');
        document.body.style.overflow = 'hidden'; // Lock background scrolling
    }
};

// Bottom Sheet Close helper
App.closeBottomSheet = (sheetId) => {
    const overlay = document.getElementById('bottom-sheet-overlay');
    const sheet = document.getElementById(sheetId);
    
    if (sheet) sheet.classList.remove('active');
    
    const openSheets = document.querySelectorAll('.bottom-sheet.active');
    if (openSheets.length <= 1 && overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
};

// Close all active sheets
App.closeAllBottomSheets = () => {
    const overlay = document.getElementById('bottom-sheet-overlay');
    const sheets = document.querySelectorAll('.bottom-sheet');
    
    sheets.forEach(sheet => sheet.classList.remove('active'));
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
};

// Async Workplace switching logic
App.selectWorkplace = async (encryptedId) => {
    try {
        const response = await fetch(`isyeri-sec?isyeri_id=${encryptedId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error('İşyeri seçimi gerçekleştirilemedi');

        if (window.showToast) {
            window.showToast('İşyeri başarıyla değiştirildi', 'success');
        }

        App.closeBottomSheet('workplace-sheet');

        // Complete window reload to synchronize session workplace tokens and names
        setTimeout(() => {
            window.location.reload();
        }, 600);

    } catch (err) {
        if (window.showToast) {
            window.showToast(err.message, 'error');
        } else {
            console.error('Workplace selection failed:', err);
        }
    }
};

// Client Hashchange routing handler
window.addEventListener('hashchange', () => {
    const rawRoute = window.location.hash.substring(1) || 'dashboard';
    App.loadMobilePage(rawRoute);
    App.closeAllBottomSheets();
});

// Event Binding and Touch Gesture Integrations
document.addEventListener('DOMContentLoaded', () => {
    const rawRoute = window.location.hash.substring(1) || 'dashboard';
    App.loadMobilePage(rawRoute);

    // Bind backdrop click to close active sheets
    const overlay = document.getElementById('bottom-sheet-overlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            App.closeAllBottomSheets();
        });
    }

    // Bind close buttons inside sheets
    document.querySelectorAll('.bottom-sheet-close').forEach(btn => {
        btn.addEventListener('click', () => {
            const sheet = btn.closest('.bottom-sheet');
            if (sheet) {
                App.closeBottomSheet(sheet.id);
            }
        });
    });

    // PWA Offline status banner updates
    const offlineBanner = document.getElementById('offline-banner');
    const updateOnlineStatus = () => {
        if (navigator.onLine) {
            if (offlineBanner) offlineBanner.classList.remove('visible');
        } else {
            if (offlineBanner) offlineBanner.classList.add('visible');
        }
    };
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    // High fidelity Touch gesture: Swipe-down to close sheets
    document.querySelectorAll('.bottom-sheet').forEach(sheet => {
        let touchStartY = 0;
        let touchMoveY = 0;
        
        sheet.addEventListener('touchstart', (e) => {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        sheet.addEventListener('touchmove', (e) => {
            touchMoveY = e.touches[0].clientY;
            const diff = touchMoveY - touchStartY;
            if (diff > 0) {
                sheet.style.transform = `translateY(${diff}px)`;
                sheet.style.transition = 'none'; // Lock transition during drag
            }
        }, { passive: true });

        sheet.addEventListener('touchend', (e) => {
            const diff = touchMoveY - touchStartY;
            sheet.style.transform = '';
            sheet.style.transition = ''; // Restore default sheet transitions
            
            if (diff > 120) {
                App.closeBottomSheet(sheet.id);
            }
            touchStartY = 0;
            touchMoveY = 0;
        });
    });
});
