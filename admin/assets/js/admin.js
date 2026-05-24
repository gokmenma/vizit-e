/**
 * Core Application Logic for SGK Vizite Admin
 * Includes SPA Router, Content Loader, and Global Event Listeners
 */

window.App = window.App || {};

// Global Fetch Interceptor for Session Management
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    const response = await originalFetch(...args);
    if (response.status === 401) {
        // If we get a 401, it means session is dropped
        window.location.href = 'login.php';
        // Return a never-resolving promise or just let the redirect happen
        return new Promise(() => {}); 
    }
    return response;
};

// Asset Registry for CDN-based resources to recover when offline connection is restored
App.assets = {
    styles: [
        { id: 'basecoat-css', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css' },
        { id: 'geist-css', url: 'https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css' },
        { id: 'flatpickr-css', url: 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css' },
        { id: 'summernote-css', url: 'https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css' }
    ],
    scripts: [
        { id: 'tailwind', url: 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4', check: () => window.tailwind },
        { id: 'jquery', url: 'https://code.jquery.com/jquery-3.4.1.min.js', check: () => window.jQuery },
        { id: 'flatpickr', url: 'https://cdn.jsdelivr.net/npm/flatpickr', check: () => window.flatpickr },
        { id: 'flatpickr-tr', url: 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js', check: () => window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.tr, depends: ['flatpickr'] },
        { id: 'summernote', url: 'https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js', check: () => window.jQuery && window.jQuery.fn.summernote, depends: ['jquery'] },
        { id: 'lucide', url: 'https://unpkg.com/lucide@latest', check: () => window.lucide },
        { id: 'basecoat-js', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/basecoat.min.js', check: () => window.basecoat || (document.querySelector('aside.sidebar') && document.querySelector('aside.sidebar').toggleAttribute) },
        { id: 'basecoat-all', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js', check: () => true },
        { id: 'basecoat-toast', url: 'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/toast.min.js', check: () => typeof App.toast === 'function' && window.basecoat }
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

// Recovery Function
App.recoverMissingAssets = async () => {
    if (!navigator.onLine) return false;

    // Check key globals
    const isMissingLucide = typeof window.lucide === 'undefined';
    const isMissingJquery = typeof window.jQuery === 'undefined';
    const isMissingFlatpickr = typeof window.flatpickr === 'undefined';

    if (!isMissingLucide && !isMissingJquery && !isMissingFlatpickr) {
        return false;
    }

    console.log('Online connection detected but some critical assets are missing. Starting asset recovery...');

    // Elegant Toast Notification
    const showToastNotification = (message, isSuccess = false) => {
        let recoveryToast = document.getElementById('recovery-toast');
        if (!recoveryToast) {
            recoveryToast = document.createElement('div');
            recoveryToast.id = 'recovery-toast';
            recoveryToast.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: #18181b;
                color: #fafafa;
                border: 1px solid #27272a;
                padding: 12px 18px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
                z-index: 999999;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.8125rem;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(recoveryToast);
        }
        
        if (isSuccess) {
            recoveryToast.innerHTML = `<span style="color: #22c55e; font-weight: bold;">✓</span> <span>${message}</span>`;
            setTimeout(() => {
                recoveryToast.style.opacity = '0';
                setTimeout(() => recoveryToast.remove(), 300);
            }, 3000);
        } else {
            recoveryToast.innerHTML = `<span class="spinner" style="width: 14px; height: 14px; border-width: 2px; border-color: #fafafa transparent transparent transparent; border-style: solid; border-radius: 50%; display: inline-block; animation: spin 1s linear infinite;"></span> <span>${message}</span>`;
        }
    };

    if (!document.getElementById('recovery-spinner-style')) {
        const style = document.createElement('style');
        style.id = 'recovery-spinner-style';
        style.innerHTML = `@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
        document.head.appendChild(style);
    }

    showToastNotification('İnternet bağlantısı algılandı. Eksik ikon ve bileşenler otomatik olarak yükleniyor...');

    // 1. Recover Styles
    for (const style of App.assets.styles) {
        const link = document.querySelector(`link[href="${style.url}"]`);
        let isLoaded = false;
        try {
            if (link && link.sheet) {
                // If we can access cssRules, or if it throws a SecurityError (CORS),
                // it means the stylesheet was successfully loaded and parsed!
                const rules = link.sheet.cssRules;
                isLoaded = true;
            }
        } catch (e) {
            // A SecurityError confirms the stylesheet is loaded but rules are CORS-protected
            if (e.name === 'SecurityError' || e.code === 18) {
                isLoaded = true;
            }
        }

        if (!isLoaded) {
            await App.loadStyle(style.url);
        }
    }

    // 2. Recover Scripts
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

    // 3. Re-render Lucide Icons and widgets
    if (window.lucide) {
        window.lucide.createIcons();
    }
    
    const appContent = document.getElementById('app-content');
    if (appContent) {
        if (App.initCustomSelects) App.initCustomSelects(appContent);
        if (App.initDatePickers) App.initDatePickers(appContent);
        if (App.TableFilter) App.TableFilter.init(appContent);
    }

    showToastNotification('Tüm ikonlar ve bileşenler başarıyla yüklendi, sayfa güncellendi.', true);
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
    const absoluteBase = baseElement ? new URL(baseElement.href).pathname : '/admin/';
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

    // Dynamically recover any missing assets if we are online before transitioning
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
        const title = doc.querySelector('title')?.innerText || 'Admin Panel';
        
        appContent.innerHTML = html;
        
        // Execute scripts within loaded content
        const scripts = appContent.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        appContent.style.opacity = '1';
        appContent.classList.add('animate-fade-in');
        
        // Initialize components
        if (window.lucide) window.lucide.createIcons();
        if (App.TableFilter) App.TableFilter.init(appContent);
        if (App.initCustomSelects) App.initCustomSelects(); // Global to catch popovers in body
        if (App.initDatePickers) App.initDatePickers(appContent);

        // Update Breadcrumb
        const breadcrumb = document.getElementById('breadcrumb');
        if (breadcrumb) {
            const formattedRoute = route.split('/').pop().split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
            breadcrumb.innerHTML = `
                <a href="dashboard" class="breadcrumb-item">Ana Sayfa</a>
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
        appContent.innerHTML = `<div class="card"><h2>Error</h2><p>${error.message}</p></div>`;
        appContent.style.opacity = '1';
    }
};

// Global Initialization
document.addEventListener('DOMContentLoaded', () => {
    // Auto-recover assets if online
    if (navigator.onLine) {
        App.recoverMissingAssets();
    }

    // Network status listeners
    window.addEventListener('online', () => {
        App.recoverMissingAssets();
    });

    const appContent = document.getElementById('app-content');

    // Handle SPA Link Clicks
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.nav-link[data-route]');
        if (link) {
            e.preventDefault();
            const route = link.getAttribute('data-route');
            App.loadPage(route);
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

    // Detect Initial Route and State
    const baseElement = document.querySelector('base');
    const absoluteBase = baseElement ? new URL(baseElement.href).pathname : '/admin/';
    const pathName = window.location.pathname;
    let initialRoute = pathName.replace(absoluteBase, '').replace(/^\/+|\/+$/g, '');
    
    const isPreRendered = appContent.children.length > 0 && !appContent.querySelector('.spinner');

    if (isPreRendered) {
        // Just sync sidebar and init components
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
        
        if (App.initCustomSelects) App.initCustomSelects(appContent);
        if (App.initDatePickers) App.initDatePickers(appContent);
        if (App.TableFilter) App.TableFilter.init(appContent);
    } else {
        App.loadPage(initialRoute, false);
    }

    // Always run initial global inits
    const toasterElement = document.getElementById('toaster');
    if (toasterElement && toasterElement.showPopover) {
        try { toasterElement.showPopover(); } catch(e) {}
    }
    if (App.initCustomSelects) App.initCustomSelects();
    if (App.initDatePickers) App.initDatePickers();
    // TableFilter is already initialized above via appContent or loadPage

    // Close user dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown && userDropdown.open && !userDropdown.contains(e.target)) {
            userDropdown.open = false;
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
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        };

        // Initial icon sync
        updateIcons();

        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateIcons();
        });
    }
});
