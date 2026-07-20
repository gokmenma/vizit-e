/**
 * Core Application Logic for SGK Vizite User Panel
 * Includes SPA Router, Content Loader, and Global Event Listeners
 */

window.App = window.App || {};

// Global Fetch Interceptor for Session Management
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    const response = await originalFetch(...args);
    if (response.status === 401) {
        // Session expired, redirect to sign-in
        window.location.href = 'sign-in';
        return new Promise(() => {}); 
    }
    return response;
};

// Asset Registry for CDN-based resources
App.assets = {
    styles: [
        { id: 'basecoat-css', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css' },
        { id: 'geist-css', url: 'https://cdn.jsdelivr.net/npm/@fontsource/geist-sans/index.css' }
    ],
    scripts: [
        { id: 'tailwind', url: 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4', check: () => window.tailwind },
        { id: 'jquery', url: 'https://code.jquery.com/jquery-3.4.1.min.js', check: () => window.jQuery },
        { id: 'lucide', url: 'https://unpkg.com/lucide@latest', check: () => window.lucide },
        { id: 'basecoat-js', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/basecoat.min.js', check: () => window.basecoat || (document.querySelector('aside.sidebar') && document.querySelector('aside.sidebar').toggleAttribute) },
        { id: 'basecoat-all', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js', check: () => true },
        { id: 'basecoat-toast', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/toast.min.js', check: () => true }
    ]
};

// Dynamic Asset Loaders
App.loadScript = (url) => {
    return new Promise((resolve, reject) => {
        let script = document.querySelector(`script[src="${url}"]`);
        if (script) script.remove();
        
        script = document.createElement('script');
        script.src = url;
        script.onload = () => resolve(true);
        script.onerror = () => reject(new Error(`Failed to load script: ${url}`));
        document.body.appendChild(script);
    });
};

App.loadStyle = (url) => {
    return new Promise((resolve) => {
        let link = document.querySelector(`link[href="${url}"]`);
        if (link) link.remove();
        
        link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = url;
        link.onload = () => resolve(true);
        link.onerror = () => resolve(false);
        document.head.appendChild(link);
    });
};

// Centralized Select2 components auto-initializer
App.initGlobalSelect2 = (container = document) => {
    if (window.jQuery && window.jQuery.fn.select2) {
        const $ = window.jQuery;
        $(container).find('select.select2, select.custom-select, select.nitelik-durumu').each(function() {
            const $select = $(this);
            if (!$select.data('select2')) {
                const modal = $select.closest('.modal');
                const showSearch = $select.attr('data-search') === 'true';
                $select.select2({
                    placeholder: $select.attr('placeholder') || 'Seçiniz',
                    width: $select.attr('data-width') || '100%',
                    minimumResultsForSearch: showSearch ? undefined : Infinity,
                    dropdownParent: modal.length ? modal : undefined
                });
            }
        });
    }
};

// Centralized Flatpickr components auto-initializer
App.initGlobalFlatpickr = (container = document) => {
    if (window.flatpickr) {
        const dateInputs = container.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            const val = input.value;
            input.setAttribute('type', 'text');
            flatpickr(input, {
                dateFormat: 'Y-m-d',
                locale: 'tr',
                defaultDate: val || undefined,
                allowInput: true
            });
        });
    }
};

// Recovery Function for Offline-to-Online Transitions
App.recoverMissingAssets = async () => {
    if (!navigator.onLine) return false;

    const isMissingLucide = typeof window.lucide === 'undefined';
    const isMissingJquery = typeof window.jQuery === 'undefined';

    if (!isMissingLucide && !isMissingJquery) {
        return false;
    }

    console.log('Asset recovery triggered...');

    for (const style of App.assets.styles) {
        const link = document.querySelector(`link[href="${style.url}"]`);
        let isLoaded = false;
        try {
            if (link && link.sheet) {
                const rules = link.sheet.cssRules;
                isLoaded = true;
            }
        } catch (e) {
            if (e.name === 'SecurityError' || e.code === 18) {
                isLoaded = true;
            }
        }

        if (!isLoaded) {
            await App.loadStyle(style.url);
        }
    }

    const missingScripts = App.assets.scripts.filter(s => {
        try { return !s.check(); } catch (e) { return true; }
    });

    const stage1 = missingScripts.filter(s => !s.depends);
    const stage2 = missingScripts.filter(s => s.depends);

    for (const script of stage1) {
        try {
            await App.loadScript(script.url);
        } catch (e) {
            console.error(`Asset Recovery: Failed to load ${script.id}`, e);
        }
    }

    for (const script of stage2) {
        try {
            const met = script.depends.every(depId => {
                const dep = App.assets.scripts.find(s => s.id === depId);
                try { return dep && dep.check(); } catch (e) { return false; }
            });
            if (met) {
                await App.loadScript(script.url);
            }
        } catch (e) {
            console.error(`Asset Recovery: Failed to load dependent ${script.id}`, e);
        }
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
    return true;
};

// Helper: Normalize route for matching
const normalizeRoute = (r) => {
    if (!r) return 'dashboard';
    let route = r.replace(/^\/+|\/+$/g, '').split('?')[0];
    if (!route || route === 'index.php' || route === 'index') return 'dashboard';
    return route;
};

// SPA Page Refresh
App.refreshContent = async () => {
    const baseElement = document.querySelector('base');
    const absoluteBase = baseElement ? new URL(baseElement.href).pathname : '/';
    const pathName = window.location.pathname;
    let currentRoute = pathName.replace(absoluteBase, '').replace(/^\/+|\/+$/g, '');
    if (!currentRoute) currentRoute = 'dashboard';
    
    await App.loadPage(currentRoute, false);
};

// Dynamic Top Loading Bar Controls (GitHub/Vercel style)
App.startLoadingBar = () => {
    const bar = document.getElementById('top-loading-bar');
    if (bar) {
        bar.style.transition = 'width 0.4s ease, opacity 0.3s ease';
        bar.style.opacity = '1';
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = '35%'; }, 10);
        
        if (App._loadingBarInterval) clearInterval(App._loadingBarInterval);
        App._loadingBarInterval = setInterval(() => {
            const width = parseFloat(bar.style.width);
            if (width < 85) {
                bar.style.width = (width + (90 - width) * 0.15) + '%';
            }
        }, 200);
    }
};

App.stopLoadingBar = () => {
    if (App._loadingBarInterval) {
        clearInterval(App._loadingBarInterval);
        App._loadingBarInterval = null;
    }
    const bar = document.getElementById('top-loading-bar');
    if (bar) {
        bar.style.width = '100%';
        setTimeout(() => {
            bar.style.opacity = '0';
            setTimeout(() => { bar.style.width = '0%'; }, 300);
        }, 250);
    }
};

// SPA Page Loader
App.loadPage = async (rawRoute, pushState = true) => {
    const appContent = document.getElementById('app-content');
    if (!appContent) return;

    if (navigator.onLine) {
        try {
            await App.recoverMissingAssets();
        } catch (e) {
            console.warn('Asset recovery skipped or failed during transition:', e);
        }
    }

    const route = normalizeRoute(rawRoute);
    
    // Show Modern Top Loading Progress Bar
    App.startLoadingBar();
    
    appContent.style.opacity = '0.5';
    
    try {
        const response = await fetch(rawRoute, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error('Page not found');
        
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const title = doc.querySelector('title')?.innerText || 'SGK Vizite';
        
        // Remove previously loaded page-specific scripts to prevent duplicate handlers
        document.querySelectorAll('script[data-spa-page-script]').forEach(s => s.remove());

        appContent.innerHTML = html;
        
        // Execute scripts within loaded content
        const scripts = appContent.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.setAttribute('data-spa-page-script', 'true');
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        appContent.style.opacity = '1';
        appContent.classList.add('animate-fade-in');
        
        // Initialize components
        if (window.lucide) window.lucide.createIcons();
        if (window.App && App.initGlobalSelect2) App.initGlobalSelect2(appContent);
        if (window.App && App.initGlobalFlatpickr) App.initGlobalFlatpickr(appContent);

        // Update Breadcrumb
        const breadcrumb = document.getElementById('breadcrumb');
        if (breadcrumb) {
            const formattedRoute = route.split('/').pop().split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
            breadcrumb.innerHTML = `
                <a href="dashboard" class="breadcrumb-item nav-link" data-route="dashboard">Ana Sayfa</a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-active">${formattedRoute}</span>
            `;
        }
        
        setTimeout(() => appContent.classList.remove('animate-fade-in'), 400);

        // Hide Modern Top Loading Progress Bar
        App.stopLoadingBar();

        if (pushState) {
            window.history.pushState({ route: rawRoute }, title, rawRoute);
        }

        // Update sidebar active link
        const allLinks = document.querySelectorAll('.sidebar .nav-link[data-route]');
        allLinks.forEach(link => {
            const linkRoute = normalizeRoute(link.getAttribute('data-route'));
            if (linkRoute === route) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
                let parent = link.parentElement;
                while (parent && parent !== document.body) {
                    if (parent.tagName === 'DETAILS') parent.open = true;
                    parent = parent.parentElement;
                }
            } else {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });

    } catch (error) {
        // Hide Modern Top Loading Progress Bar on error
        App.stopLoadingBar();

        console.error('Error loading page:', error);
        appContent.innerHTML = `<div class="card"><div class="card-content"><h2>Error</h2><p>${error.message}</p></div></div>`;
        appContent.style.opacity = '1';
    }
};

// Global Initialization
document.addEventListener('DOMContentLoaded', () => {
    // jQuery Bootstrap Modal & Component Polyfill for SPA Mode
    if (window.jQuery) {
        (function($) {
            // Core Modal Polyfill
            if (typeof $.fn.modal === 'undefined') {
                $.fn.modal = function(action) {
                    return this.each(function() {
                        const $modal = $(this);
                        if (action === 'show') {
                            $modal.removeClass('hidden').addClass('flex');
                            $('body').addClass('overflow-hidden');
                            $modal.trigger('shown.bs.modal');
                        } else if (action === 'hide') {
                            $modal.removeClass('flex').addClass('hidden');
                            $('body').removeClass('overflow-hidden');
                            $modal.trigger('hidden.bs.modal');
                        }
                    });
                };

                // Global trigger delegations
                $(document).on('click', '[data-bs-toggle="modal"]', function(e) {
                    e.preventDefault();
                    const target = $(this).attr('data-bs-target');
                    $(target).modal('show');
                });

                $(document).on('click', '[data-bs-dismiss="modal"]', function(e) {
                    e.preventDefault();
                    $(this).closest('.modal').modal('hide');
                });

                // Dismiss modal when clicking outer backdrop
                $(document).on('mousedown', '.modal', function(e) {
                    if ($(e.target).hasClass('modal')) {
                        $(this).modal('hide');
                    }
                });
            }

            // Global Auto-Initialization of Select2 elements inside Modals
            $(document).on('shown.bs.modal', '.modal', function() {
                if (window.App && App.initGlobalSelect2) {
                    App.initGlobalSelect2(this);
                }
            });

            // Initial Select2 Load
            if (window.App && App.initGlobalSelect2) {
                App.initGlobalSelect2(document);
            }
        })(window.jQuery);
    }

    // Ensure toaster container exists before recovering assets
    if (!document.getElementById('toaster')) {
        const toasterDiv = document.createElement('div');
        toasterDiv.id = 'toaster';
        toasterDiv.className = 'toaster';
        toasterDiv.setAttribute('data-align', 'end');
        toasterDiv.setAttribute('popover', 'manual');
        document.body.appendChild(toasterDiv);
    }

    if (navigator.onLine) {
        App.recoverMissingAssets();
    }

    window.addEventListener('online', () => {
        App.recoverMissingAssets();
    });

    // Removed old full-screen loader injection in favor of the premium top progress loading bar

    const appContent = document.getElementById('app-content');

    // Close any native modal <dialog> when its backdrop is clicked (global, tüm sayfalar).
    // Modal dialog'da backdrop alanına tıklanınca event.target dialog'un kendisi olur;
    // kart içeriği bir alt öğede olduğundan kart tıklamaları dialog'u kapatmaz.
    document.addEventListener('click', (e) => {
        if (e.target.tagName === 'DIALOG' && e.target.open) {
            e.target.close();
        }
    });

    // Handle SPA Link Clicks
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.nav-link[data-route]');
        if (link) {
            const route = link.getAttribute('data-route');
            if (route && route !== '#') {
                e.preventDefault();
                App.loadPage(route);
            }
        }
    });

    // Handle Browser History Navigation
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.route) {
            App.loadPage(e.state.route, false);
        } else {
            App.loadPage('dashboard', false);
        }
    });

    // Initial Flatpickr Load
    if (window.App && App.initGlobalFlatpickr) {
        App.initGlobalFlatpickr(document);
    }

    // Detect Initial Route
    const baseElement = document.querySelector('base');
    const absoluteBase = baseElement ? new URL(baseElement.href).pathname : '/';
    const pathName = window.location.pathname;
    let initialRoute = pathName.replace(absoluteBase, '').replace(/^\/+|\/+$/g, '');
    
    const isPreRendered = appContent.children.length > 0 && !appContent.querySelector('.spinner');

    if (isPreRendered) {
        const route = normalizeRoute(initialRoute);
        const allLinks = document.querySelectorAll('.sidebar .nav-link[data-route]');
        allLinks.forEach(link => {
            const linkRoute = normalizeRoute(link.getAttribute('data-route'));
            if (linkRoute === route) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
                let parent = link.parentElement;
                while (parent && !parent.classList.contains('sidebar')) {
                    if (parent.tagName === 'DETAILS') parent.open = true;
                    parent = parent.parentElement;
                }
            }
        });
    } else {
        App.loadPage(initialRoute, false);
    }

    // Close user/workplace dropdowns when clicking outside or clicking any option inside
    document.addEventListener('click', (e) => {
        const userDropdown = document.querySelector('.user-dropdown');
        const isyeriDropdown = document.querySelector('.isyeri-dropdown');

        // Outside clicks
        if (userDropdown && userDropdown.hasAttribute('open') && !userDropdown.contains(e.target)) {
            userDropdown.removeAttribute('open');
        }
        if (isyeriDropdown && isyeriDropdown.hasAttribute('open') && !isyeriDropdown.contains(e.target)) {
            isyeriDropdown.removeAttribute('open');
        }

        // Inside clicks on links or items
        const clickedInsideLink = e.target.closest('.user-dropdown a, .isyeri-dropdown a, .user-dropdown button, .isyeri-dropdown button');
        if (clickedInsideLink) {
            const details = clickedInsideLink.closest('details');
            if (details) {
                details.removeAttribute('open');
            }
        }
    });

    // Theme Toggle Logic
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        const sunIcon = themeToggle.querySelector('.sun-icon');
        const moonIcon = themeToggle.querySelector('.moon-icon');
        
        const updateIcons = () => {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                if (sunIcon) sunIcon.style.display = 'none';
                if (moonIcon) moonIcon.style.display = 'block';
            } else {
                if (sunIcon) sunIcon.style.display = 'block';
                if (moonIcon) moonIcon.style.display = 'none';
            }
        };

        updateIcons();

        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateIcons();
        });
    }

    // Intercept form submissions for SPA routing (e.g. search filters)
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        const action = form.getAttribute('action');
        
        if (form.hasAttribute('data-bypass')) {
            return;
        }

        e.preventDefault();
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        const formData = new FormData(form);
        if (e.submitter && e.submitter.name) {
            formData.append(e.submitter.name, e.submitter.value || '');
        }

        // Show Modern Top Loading Progress Bar
        App.startLoadingBar();

        if (appContent) appContent.style.opacity = '0.5';

        try {
            let fetchUrl = action || (window.location.pathname + window.location.search);
            let options = {
                method: method,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            };

            if (method === 'POST') {
                options.body = formData;
            } else {
                const params = new URLSearchParams(formData).toString();
                const cleanAction = action || (window.location.pathname + window.location.search);
                fetchUrl = cleanAction.split('?')[0] + '?' + params;
            }

            const response = await fetch(fetchUrl, options);
            if (!response.ok) throw new Error('Form submission failed');

            const html = await response.text();
            if (appContent) {
                // Remove previously loaded page-specific scripts
                document.querySelectorAll('script[data-spa-page-script]').forEach(s => s.remove());

                appContent.innerHTML = html;
                appContent.style.opacity = '1';

                const scripts = appContent.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.setAttribute('data-spa-page-script', 'true');
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                if (window.lucide) window.lucide.createIcons();
                if (window.App && App.initGlobalSelect2) App.initGlobalSelect2(appContent);
                if (window.App && App.initGlobalFlatpickr) App.initGlobalFlatpickr(appContent);
            }
        } catch (error) {
            console.error('Error during form submission:', error);
            if (appContent) appContent.style.opacity = '1';
        } finally {
            // Hide Modern Top Loading Progress Bar
            App.stopLoadingBar();
        }
    });
});

// Style injection for Toast notification animations
if (!document.getElementById('toast-animations-style')) {
    const toastStyle = document.createElement('style');
    toastStyle.id = 'toast-animations-style';
    toastStyle.innerHTML = `
    @keyframes toast-slide-in {
        0% {
            opacity: 0;
            transform: translateX(100%) translateY(0) scale(0.9);
        }
        100% {
            opacity: 1;
            transform: translateX(0) translateY(0) scale(1);
        }
    }
    @keyframes toast-slide-out {
        0% {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        100% {
            opacity: 0;
            transform: translateX(120%) scale(0.9);
        }
    }
    .animate-toast-in {
        animation: toast-slide-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    .animate-toast-out {
        animation: toast-slide-out 0.25s cubic-bezier(0.36, 0.07, 0.19, 0.97) forwards;
    }
    `;
    document.head.appendChild(toastStyle);
}

// Global Toast Notification Helper
// Deduplication map to prevent the same message from appearing multiple times
window._toastDedup = window._toastDedup || {};

window.showToast = function(message, type = 'success') {
    // Deduplication: skip if same message+type was shown within the last 500ms
    const dedupKey = type + ':' + message;
    const now = Date.now();
    if (window._toastDedup[dedupKey] && (now - window._toastDedup[dedupKey]) < 500) {
        return; // Skip duplicate toast
    }
    window._toastDedup[dedupKey] = now;

    let toaster = document.getElementById('toaster');
    if (!toaster) {
        toaster = document.createElement('div');
        toaster.id = 'toaster';
        toaster.className = 'toaster';
        toaster.setAttribute('data-align', 'end');
        toaster.setAttribute('popover', 'manual');
        document.body.appendChild(toaster);
    }

    // Ensure the toaster popover container is shown/open in the browser's top-layer.
    if (toaster.showPopover && !toaster.matches(':popover-open')) {
        try {
            toaster.showPopover();
        } catch (e) {
            // Safe fallback
        }
    }

    // Define standard titles and icons based on type
    let category = type;
    if (type === 'danger') category = 'error'; // Map danger to error alias
    
    let titleText = 'Success';
    let iconSvg = '';

    if (category === 'success') {
        titleText = 'Başarılı';
        iconSvg = `
          <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <path d="m9 12 2 2 4-4" />
          </svg>
        `;
    } else if (category === 'error' || category === 'danger') {
        titleText = 'Hata';
        category = 'danger'; // Basecoat uses 'danger' for error toasts
        iconSvg = `
          <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="15" y1="9" x2="9" y2="15" />
            <line x1="9" y1="9" x2="15" y2="15" />
          </svg>
        `;
    } else if (category === 'warning') {
        titleText = 'Uyarı';
        iconSvg = `
          <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
            <line x1="12" y1="9" x2="12" y2="13" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
          </svg>
        `;
    } else {
        titleText = 'Bilgi';
        category = 'info';
        iconSvg = `
          <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="16" x2="12" y2="12" />
            <line x1="12" y1="8" x2="12.01" y2="8" />
          </svg>
        `;
    }

    const toast = document.createElement('div');
    toast.className = 'toast animate-toast-in';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-atomic', 'true');
    toast.setAttribute('aria-hidden', 'false');
    toast.setAttribute('data-category', category);

    toast.innerHTML = `
      <div class="toast-content">
        ${iconSvg}
        <section>
          <h2>${titleText}</h2>
          <p>${message}</p>
        </section>
        <footer>
          <button type="button" class="btn" data-toast-action="dismiss">Kapat</button>
        </footer>
      </div>
    `;

    toaster.appendChild(toast);

    let isDismissed = false;
    const dismissToast = () => {
        if (isDismissed) return;
        isDismissed = true;
        toast.classList.remove('animate-toast-in');
        toast.classList.add('animate-toast-out');
        setTimeout(() => {
            toast.remove();
            // Close popover container if all toasts are gone
            if (toaster.children.length === 0 && toaster.hidePopover) {
                try { toaster.hidePopover(); } catch (e) {}
            }
        }, 250);
    };

    // Auto dismiss after 4 seconds
    const autoDismiss = setTimeout(dismissToast, 4000);

    // Bind close action
    const dismissBtn = toast.querySelector('[data-toast-action]');
    if (dismissBtn) {
        dismissBtn.onclick = (e) => {
            e.preventDefault();
            clearTimeout(autoDismiss);
            dismissToast();
        };
    }
};

// Centralized HTML5 <dialog> Confirmation Dialog Engine & Swal Interceptor
window.confirmDialog = function({ title, text, confirmButtonText = 'Evet, devam et!', cancelButtonText = 'Vazgeç' }) {
    return new Promise((resolve) => {
        let dialog = document.getElementById('custom-confirm-dialog');
        if (!dialog) {
            // Düz <div> kullanıyoruz; <dialog> olursa app.css'teki
            // `dialog:not([open]) { display:none !important }` kuralı görünmez kılıyor.
            dialog = document.createElement('div');
            dialog.id = 'custom-confirm-dialog';
            dialog.className = 'fixed inset-0 z-[999999] hidden items-center justify-center p-4 bg-zinc-950/45 backdrop-blur-sm w-full h-full border-0 outline-none flex';
            dialog.innerHTML = `
                <div class="relative w-full max-w-md bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl p-6 flex flex-col gap-4 animate-scale-in">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50" id="confirm-dialog-title"></h2>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 leading-relaxed" id="confirm-dialog-description"></p>
                    </div>
                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button" class="h-9 px-4 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg text-xs font-bold shadow-sm transition-all cursor-pointer cancel-btn"></button>
                        <button type="button" class="h-9 px-4 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold shadow transition-all cursor-pointer confirm-btn"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(dialog);

            if (!document.getElementById('dialog-scale-in-style')) {
                const dialogStyle = document.createElement('style');
                dialogStyle.id = 'dialog-scale-in-style';
                dialogStyle.innerHTML = `
                @keyframes dialog-scale-in {
                    from { opacity: 0; transform: scale(0.95); }
                    to { opacity: 1; transform: scale(1); }
                }
                .animate-scale-in {
                    animation: dialog-scale-in 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                }
                `;
                document.head.appendChild(dialogStyle);
            }
        }

        dialog.querySelector('#confirm-dialog-title').textContent = title || 'Emin misiniz?';
        dialog.querySelector('#confirm-dialog-description').textContent = text || '';

        const cancelBtn = dialog.querySelector('.cancel-btn');
        const confirmBtn = dialog.querySelector('.confirm-btn');

        cancelBtn.textContent = cancelButtonText;
        confirmBtn.textContent = confirmButtonText;

        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        
        if (window.jQuery) {
            window.jQuery('body').addClass('overflow-hidden');
        } else {
            document.body.classList.add('overflow-hidden');
        }

        let backdropClick;
        const cleanup = (confirmed) => {
            dialog.removeEventListener('mousedown', backdropClick);
            dialog.classList.remove('flex');
            dialog.classList.add('hidden');
            if (window.jQuery) {
                window.jQuery('body').removeClass('overflow-hidden');
            } else {
                document.body.classList.remove('overflow-hidden');
            }
            resolve(confirmed);
        };

        backdropClick = (e) => { if (e.target === dialog) cleanup(false); };
        dialog.addEventListener('mousedown', backdropClick);

        cancelBtn.onclick = () => cleanup(false);
        confirmBtn.onclick = () => cleanup(true);
    });
};

// Global SweetAlert Interceptor to dynamically forward confirms to the native <dialog>
(function() {
    const customSwal = function(firstArg, secondArg, thirdArg) {
        let options = {};
        if (typeof firstArg === 'string') {
            options.title = firstArg;
            options.text = secondArg || '';
            options.icon = thirdArg || 'info';
        } else {
            options = firstArg || {};
        }

        if (options.showCancelButton || options.buttons) {
            return window.confirmDialog({
                title: options.title,
                text: options.text || options.description || '',
                confirmButtonText: options.confirmButtonText || 'Evet, devam et!',
                cancelButtonText: options.cancelButtonText || 'İptal'
            }).then(confirmed => {
                return { isConfirmed: confirmed, value: confirmed };
            });
        } else {
            // Standard notification modal fallback using our sleek custom toast engine
            window.showToast(options.text || options.title || '', options.icon === 'error' ? 'error' : 'success');
            return Promise.resolve({ isConfirmed: true, value: true });
        }
    };

    customSwal.fire = customSwal;
    
    // Bind to all potential global namespaces using dynamic getters/setters to block library overrides
    try {
        Object.defineProperty(window, 'Swal', {
            get: () => customSwal,
            set: () => { /* Suppress SweetAlert2 from overwriting our premium native dialog interceptor */ },
            configurable: true
        });
        Object.defineProperty(window, 'swal', {
            get: () => customSwal,
            set: () => { /* Suppress SweetAlert2 from overwriting our premium native dialog interceptor */ },
            configurable: true
        });
    } catch (e) {
        window.Swal = customSwal;
        window.swal = customSwal;
    }
})();
