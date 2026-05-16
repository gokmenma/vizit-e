/**
 * Core Application Logic for SGK Vizite Admin
 * Includes SPA Router, Content Loader, and Global Event Listeners
 */

window.App = window.App || {};

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

// SPA Page Loader
App.loadPage = async (rawRoute, pushState = true) => {
    const appContent = document.getElementById('app-content');
    if (!appContent) return;

    const route = normalizeRoute(rawRoute);
    
    // Show Loader
    const loader = document.querySelector('.page-loader');
    const overlay = document.querySelector('.page-loader-overlay');
    if (loader) loader.classList.add('active');
    if (overlay) overlay.classList.add('active');
    
    appContent.style.opacity = '0.5';
    
    try {
        const response = await fetch(route, {
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
                <span style="font-size: 0.875rem; color: #71717a;">SGK Vizite</span>
                <span style="font-size: 0.875rem; color: #d4d4d8;">/</span>
                <span style="font-size: 0.875rem; font-weight: 500; color: #18181b;">${formattedRoute}</span>
            `;
        }
        
        setTimeout(() => appContent.classList.remove('animate-fade-in'), 400);

        // Hide Loader
        if (loader) loader.classList.remove('active');
        if (overlay) overlay.classList.remove('active');

        if (pushState) {
            window.history.pushState({ route }, title, route);
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
        // Hide Loader on error
        const loader = document.querySelector('.page-loader');
        const overlay = document.querySelector('.page-loader-overlay');
        if (loader) loader.classList.remove('active');
        if (overlay) overlay.classList.remove('active');

        console.error('Error loading page:', error);
        appContent.innerHTML = `<div class="card"><h2>Error</h2><p>${error.message}</p></div>`;
        appContent.style.opacity = '1';
    }
};

// Global Initialization
document.addEventListener('DOMContentLoaded', () => {
    // Inject Loader HTML if missing
    if (!document.querySelector('.page-loader')) {
        const appMain = document.querySelector('.app-main');
        if (appMain) {
            const loaderHtml = `
                <div class="page-loader-overlay"></div>
                <div class="page-loader">
                    <div class="spinner"></div>
                    <span style="font-size: 0.8125rem; font-weight: 500; color: #71717a;">Yükleniyor...</span>
                </div>
            `;
            appMain.insertAdjacentHTML('beforeend', loaderHtml);
        }
    }

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
});
