/**
 * UI Components for SGK Vizite Admin
 * Includes CustomSelect, DatePicker, Toast, and Form Validation
 */

window.App = window.App || {};

// Toast Notifications
App.toast = (type, title, message) => {
    const config = {
        category: type || 'success', // success, error, warning, info
        title: title,
        description: message,
        cancel: { label: 'Kapat' }
    };

    document.dispatchEvent(new CustomEvent('basecoat:toast', {
        detail: { config }
    }));
};

// Form Validation
App.validateForm = (formId) => {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;
    const inputs = form.querySelectorAll('[required], .validate');

    inputs.forEach(input => {
        input.classList.remove('is-invalid');
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else if (input.type === 'email' && !input.value.includes('@')) {
            input.classList.add('is-invalid');
            isValid = false;
        }
    });

    if (!isValid) {
        App.toast('error', 'Hata', 'Lütfen tüm alanları doğru şekilde doldurun.');
    }

    return isValid;
};

// Custom Searchable/Popover Select
App.initCustomSelects = (container = document) => {
    const selector = '.custom-select:not([data-initialized])';
    container.querySelectorAll(selector).forEach(select => {
        select.dataset.initialized = 'true';

        const trigger = select.querySelector('.select-trigger');
        const popover = select.querySelector('.select-popover');
        const searchInput = select.querySelector('.select-search');
        const options = select.querySelectorAll('.select-option');
        const hiddenInput = select.querySelector('input[type="hidden"]');
        const label = select.querySelector('.select-label');
        const isNoSearch = select.dataset.search === 'false' || select.classList.contains('no-search');

        if (!trigger || !popover) return;

        if (popover.showPopover) {
            popover.setAttribute('popover', 'manual');
        }

        const closeAll = () => {
            const parentPopover = select.closest('.tf-popover');
            document.querySelectorAll('.select-popover, .tf-popover').forEach(p => {
                if (p === parentPopover) return;
                if (p.hasAttribute('popover') && p.matches(':popover-open')) {
                    try { p.hidePopover(); } catch(e) {}
                }
                p.classList.remove('show');
            });
        };

        const openPopover = () => {
            closeAll();
            const rect = trigger.getBoundingClientRect();
            const placement = select.dataset.placement || 'bottom';
            
            popover.style.position = 'fixed';
            popover.style.margin = '0';
            popover.style.width = rect.width + 'px';
            popover.style.left = rect.left + 'px';
            
            if (placement === 'top') {
                popover.style.top = 'auto';
                popover.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                popover.style.bottom = 'auto';
                popover.style.top = (rect.bottom + 4) + 'px';
            }
            
            if (popover.hasAttribute('popover')) {
                popover.showPopover();
            } else {
                popover.classList.add('show');
            }

            if (searchInput && !isNoSearch) {
                searchInput.value = '';
                setTimeout(() => searchInput.focus(), 100);
                options.forEach(opt => opt.style.display = 'flex');
            } else if (searchInput && isNoSearch) {
                searchInput.parentElement.style.display = 'none';
            }
        };

        const closePopover = () => {
            if (popover.hasAttribute('popover') && popover.matches(':popover-open')) {
                popover.hidePopover();
            }
            popover.classList.remove('show');
        };

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            const isOpen = popover.classList.contains('show') || (popover.hasAttribute('popover') && popover.matches(':popover-open'));
            
            if (isOpen) {
                closePopover();
            } else {
                closeAll();
                openPopover();
            }
        });

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                options.forEach(opt => {
                    const text = opt.textContent.toLowerCase();
                    opt.style.display = text.includes(term) ? 'flex' : 'none';
                });
            });
        }

        options.forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                const val = opt.dataset.value;
                const text = opt.querySelector('span')?.textContent || opt.textContent;
                
                hiddenInput.value = val;
                label.textContent = text;
                closePopover();
                
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                
                options.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
            });
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select') && !e.target.closest('.select-popover')) {
            document.querySelectorAll('.select-popover').forEach(p => {
                if (p.hasAttribute('popover') && p.matches(':popover-open')) {
                    try { p.hidePopover(); } catch(e) {}
                }
                p.classList.remove('show');
            });
        }
    });
};

// Flatpickr DatePickers
App.initDatePickers = (container = document) => {
    if (typeof flatpickr === 'undefined') return;
    
    container.querySelectorAll('input[type="date"], .datepicker').forEach(el => {
        flatpickr(el, {
            locale: 'tr',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd.m.Y',
            allowInput: true,
            static: true,
            onOpen: (selectedDates, dateStr, instance) => {
                instance.calendarContainer.style.zIndex = "10001";
            }
        });
    });
};

// Global Dialog Close on Outside Click
let dialogMouseDown = null;
document.addEventListener('mousedown', (e) => {
    dialogMouseDown = e.target;
});

document.addEventListener('click', (e) => {
    if (e.target.tagName === 'DIALOG' && e.target === dialogMouseDown) {
        const rect = e.target.getBoundingClientRect();
        if (
            e.clientX < rect.left ||
            e.clientX > rect.right ||
            e.clientY < rect.top ||
            e.clientY > rect.bottom
        ) {
            e.target.close();
        }
    }
    dialogMouseDown = null;
});
