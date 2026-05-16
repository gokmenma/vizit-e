/**
 * Table Modules for SGK Vizite Admin
 * Includes DataTable (Search/Sort) and TableFilter (Advanced Filtering)
 */

window.App = window.App || {};

// DataTable Helper (Search and Sort)
App.DataTable = {
    search: (inputId, rowClass, nameClass, emailClass) => {
        const input = document.getElementById(inputId);
        const filter = input.value.toUpperCase();
        const rows = document.querySelectorAll(rowClass);

        rows.forEach(row => {
            const name = row.querySelector(nameClass)?.textContent.toUpperCase() || '';
            const email = row.querySelector(emailClass)?.textContent.toUpperCase() || '';
            row.style.display = (name.includes(filter) || email.includes(filter)) ? "" : "none";
        });
    },

    sort: (tableId, n) => {
        const table = document.getElementById(tableId);
        if (!table) return;
        let switching = true, i, x, y, shouldSwitch, dir = "asc", switchcount = 0;
        
        while (switching) {
            switching = false;
            let rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                if (dir == "asc") {
                    if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                        shouldSwitch = true;
                        break;
                    }
                } else if (dir == "desc") {
                    if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                        shouldSwitch = true;
                        break;
                    }
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchcount++;
            } else {
                if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }
    }
};

// TableFilter Module (Advanced Column Filtering)
App.TableFilter = {
    activeFilters: {}, // tableId -> { columnIndex -> { type, rules: [...] } }

    init: (container = document) => {
        // Target both explicit data-tables and general card tables
        container.querySelectorAll('table.data-table, .card > table, .table-container > table').forEach(table => {
            if (!table.id) table.id = 'table-' + Math.random().toString(36).substr(2, 9);
            
            // Add filtering class if there are active filters for this table in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tf_' + table.id)) {
                table.classList.add('is-filtering');
            }

            App.TableFilter.attachToTable(table);
            
            // Restore from URL
            App.TableFilter.restoreFromUrl(table.id);
        });
    },

    restoreFromUrl: (tableId) => {
        const urlParams = new URLSearchParams(window.location.search);
        const filterStr = urlParams.get('tf_' + tableId);
        if (filterStr) {
            try {
                const filters = JSON.parse(decodeURIComponent(filterStr));
                App.TableFilter.activeFilters[tableId] = filters;
                
                // Update UI state
                const table = document.getElementById(tableId);
                if (table) {
                    const headers = table.querySelectorAll('thead th');
                    for (const index in filters) {
                        const th = headers[index];
                        if (th) {
                            const trigger = th.querySelector('.tf-trigger');
                            if (trigger) trigger.classList.add('active');
                        }
                    }
                    App.TableFilter.run(tableId);
                    App.TableFilter.updateGlobalUI(tableId);
                }
            } catch (e) { console.error('Filter restore failed', e); }
        }
    },

    saveToUrl: (tableId) => {
        const url = new URL(window.location);
        const filters = App.TableFilter.activeFilters[tableId];
        
        if (filters && Object.keys(filters).length > 0) {
            url.searchParams.set('tf_' + tableId, encodeURIComponent(JSON.stringify(filters)));
        } else {
            url.searchParams.delete('tf_' + tableId);
        }
        
        window.history.replaceState({}, '', url);
    },

    updateGlobalUI: (tableId) => {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const container = table.closest('.dt-container');
        if (!container) return;
        
        const actionsArea = container.querySelector('.dt-actions');
        if (!actionsArea) return;
        
        let clearBtn = actionsArea.querySelector('.tf-global-clear');
        const hasFilters = App.TableFilter.activeFilters[tableId] && Object.keys(App.TableFilter.activeFilters[tableId]).length > 0;
        
        if (hasFilters) {
            if (!clearBtn) {
                clearBtn = document.createElement('button');
                clearBtn.className = 'btn btn-ghost tf-global-clear';
                clearBtn.style.height = '2.25rem';
                clearBtn.style.color = '#ef4444';
                clearBtn.style.fontSize = '0.8125rem';
                clearBtn.innerHTML = '<i data-lucide="filter-x" style="width: 14px;"></i> Filtreleri Temizle';
                
                // Insert before search
                const searchWrapper = actionsArea.querySelector('.dt-search-wrapper');
                if (searchWrapper) searchWrapper.before(clearBtn);
                else actionsArea.prepend(clearBtn);
                
                clearBtn.onclick = () => App.TableFilter.clearAll(tableId);
                if (window.lucide) lucide.createIcons();
            }
        } else if (clearBtn) {
            clearBtn.remove();
        }
    },

    clearAll: (tableId) => {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        App.TableFilter.activeFilters[tableId] = {};
        
        // Reset header icons
        table.querySelectorAll('.tf-trigger.active').forEach(t => t.classList.remove('active'));
        
        // Reset all popover inputs
        document.querySelectorAll('.tf-popover').forEach(pop => {
            pop.querySelectorAll('.tf-input').forEach(i => i.value = '');
            pop.querySelectorAll('input:checked').forEach(c => c.checked = false);
            // Keep only first rule row
            const ruleRows = pop.querySelectorAll('.tf-rule-row');
            for (let i = 1; i < ruleRows.length; i++) ruleRows[i].remove();
        });
        
        App.TableFilter.saveToUrl(tableId);
        App.TableFilter.updateGlobalUI(tableId);
        App.TableFilter.run(tableId);
    },

    attachToTable: (table) => {
        const headers = table.querySelectorAll('thead th');
        headers.forEach((th, index) => {
            const text = th.textContent.trim();
            if (th.classList.contains('no-filter') || 
                th.querySelector('input[type="checkbox"]') || 
                text === 'İşlemler' || 
                text === '#' ||
                text === '') return;
            
            if (th.querySelector('.tf-trigger')) return;

            th.style.position = 'relative';
            
            const filterBtn = document.createElement('button');
            filterBtn.className = 'tf-trigger';
            filterBtn.type = 'button';
            filterBtn.title = 'Filtrele';
            filterBtn.innerHTML = '<i data-lucide="filter" style="width: 12px; height: 12px;"></i>';
            th.appendChild(filterBtn);
            
            const popoverId = `tf-popover-${Math.random().toString(36).substr(2, 9)}`;
            const popover = document.createElement('div');
            popover.id = popoverId;
            popover.className = 'tf-popover';
            popover.setAttribute('popover', 'manual');
            
            // Determine filter type
            let type = th.dataset.filterType || 'text';
            const lowerText = text.toLowerCase();
            if (lowerText.includes('tarih') || lowerText.includes('bitiş') || lowerText.includes('başlangıç')) type = 'date';
            if (lowerText.includes('durum') || lowerText.includes('paket')) type = 'select';

            popover.innerHTML = `
                <div class="tf-header">
                    <span>${text}</span>
                    <button class="tf-close" onclick="document.getElementById('${popoverId}').hidePopover()"><i data-lucide="x" style="width: 14px;"></i></button>
                </div>
                <div class="tf-body" data-type="${type}">
                    ${App.TableFilter.renderFilterBody(type, table, index)}
                </div>
                <div class="tf-footer">
                    <button class="btn btn-ghost btn-sm tf-clear" onclick="App.TableFilter.clear('${table.id}', ${index}, '${popoverId}')">Temizle</button>
                    <button class="btn btn-sm tf-apply" style="background: #18181b; color: white;" onclick="App.TableFilter.apply('${table.id}', ${index}, '${popoverId}')">Uygula</button>
                </div>
            `;
            document.body.appendChild(popover);
            
            filterBtn.onclick = (e) => {
                e.stopPropagation();
                
                // Close all other popovers first
                document.querySelectorAll('.tf-popover, .select-popover').forEach(p => {
                    if (p !== popover && p.matches(':popover-open')) p.hidePopover();
                });

                const rect = filterBtn.getBoundingClientRect();
                popover.style.position = 'fixed';
                popover.style.margin = '0';
                popover.style.top = (rect.bottom + 5) + 'px';
                popover.style.left = Math.min(rect.right - 240, window.innerWidth - 260) + 'px';
                popover.showPopover();
                if (window.lucide) lucide.createIcons();
            };
        });
        if (window.lucide) lucide.createIcons();
    },

    renderFilterBody: (type, table, index) => {
        if (type === 'select') {
            const values = new Set();
            table.querySelectorAll(`tbody tr`).forEach(row => {
                const cell = row.cells[index];
                if (cell) {
                    const val = cell.textContent.trim();
                    if (val) values.add(val);
                }
            });
            
            let html = '<div class="tf-checkbox-list">';
            Array.from(values).sort().forEach(val => {
                html += `
                    <label class="tf-checkbox-item">
                        <input type="checkbox" value="${val}">
                        <span>${val}</span>
                    </label>
                `;
            });
            if (values.size === 0) html += '<span style="font-size: 0.75rem; color: #71717a; padding: 0.5rem;">Değer bulunamadı</span>';
            html += '</div>';
            return html;
        } else {
            return `
                <div class="tf-rules-container">
                    ${App.TableFilter.renderRuleRow(type)}
                </div>
                <button class="tf-rule-add" onclick="App.TableFilter.addRule(this, '${type}')"><i data-lucide="plus" style="width: 12px;"></i> Kural Ekle</button>
            `;
        }
    },

    renderRuleRow: (type, isAdditional = false) => {
        const operators = type === 'date' ? [
            { val: 'equals', text: 'Eşittir' },
            { val: 'after', text: 'Sonra' },
            { val: 'before', text: 'Önce' }
        ] : [
            { val: 'contains', text: 'İçerir' },
            { val: 'equals', text: 'Eşittir' },
            { val: 'starts', text: 'İle Başlar' },
            { val: 'ends', text: 'İle Biter' },
            { val: 'not_contains', text: 'İçermez' },
            { val: 'gt', text: 'Büyüktür' },
            { val: 'lt', text: 'Küçüktür' }
        ];

        let optionsHtml = '';
        operators.forEach(op => {
            optionsHtml += `<div class="select-option" data-value="${op.val}"><span>${op.text}</span></div>`;
        });

        return `
            <div class="tf-rule-row" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.5rem; border-bottom: 1px dashed #e4e4e7; padding-bottom: 0.75rem; position: relative;">
                ${isAdditional ? '<button class="tf-rule-remove" onclick="this.parentElement.remove()" style="position: absolute; right: 0; top: 0; background: none; border: none; color: #ef4444; cursor: pointer; padding: 0;"><i data-lucide="trash-2" style="width: 12px;"></i></button>' : ''}
                <div class="custom-select no-search" style="width: 100%;">
                    <input type="hidden" class="tf-operator-select" value="${operators[0].val}">
                    <div class="select-trigger" style="height: 2rem; font-size: 0.8125rem;">
                        <span class="select-label">${operators[0].text}</span>
                        <i data-lucide="chevron-down" style="width: 12px; margin-left: auto;"></i>
                    </div>
                    <div class="select-popover" popover="manual">
                        <header style="display:none;"><input type="text" class="select-search"></header>
                        <div class="select-options">${optionsHtml}</div>
                    </div>
                </div>
                <input type="${type === 'date' ? 'date' : 'text'}" class="tf-input" placeholder="Değer girin..." style="height: 2rem; font-size: 0.8125rem;">
            </div>
        `;
    },

    addRule: (btn, type) => {
        const container = btn.previousElementSibling;
        const temp = document.createElement('div');
        temp.innerHTML = App.TableFilter.renderRuleRow(type, true);
        container.appendChild(temp.firstElementChild);
        if (window.lucide) lucide.createIcons();
        App.initCustomSelects(container);
    },

    apply: (tableId, index, popoverId) => {
        const table = document.getElementById(tableId);
        const popover = document.getElementById(popoverId);
        const body = popover.querySelector('.tf-body');
        const type = body.dataset.type;
        const th = table.querySelectorAll('thead th')[index];
        const trigger = th.querySelector('.tf-trigger');
        
        let filterData = { type, index, rules: [] };

        if (type === 'select') {
            const checked = Array.from(body.querySelectorAll('input:checked')).map(cb => cb.value);
            if (checked.length === 0) {
                App.TableFilter.clear(tableId, index, popoverId);
                return;
            }
            filterData.values = checked;
        } else {
            const ruleRows = body.querySelectorAll('.tf-rule-row');
            ruleRows.forEach(row => {
                const operator = row.querySelector('.tf-operator-select').value;
                const value = row.querySelector('.tf-input').value.trim();
                if (value) {
                    filterData.rules.push({ operator, value: value.toLowerCase() });
                }
            });

            if (filterData.rules.length === 0) {
                App.TableFilter.clear(tableId, index, popoverId);
                return;
            }
        }

        if (!App.TableFilter.activeFilters[tableId]) App.TableFilter.activeFilters[tableId] = {};
        App.TableFilter.activeFilters[tableId][index] = filterData;
        
        trigger.classList.add('active');
        popover.hidePopover();
        
        App.TableFilter.saveToUrl(tableId);
        App.TableFilter.updateGlobalUI(tableId);
        App.TableFilter.run(tableId);
    },

    clear: (tableId, index, popoverId) => {
        const table = document.getElementById(tableId);
        const popover = document.getElementById(popoverId);
        const th = table.querySelectorAll('thead th')[index];
        const trigger = th.querySelector('.tf-trigger');

        if (App.TableFilter.activeFilters[tableId]) {
            delete App.TableFilter.activeFilters[tableId][index];
        }

        const body = popover.querySelector('.tf-body');
        if (body.dataset.type === 'select') {
            body.querySelectorAll('input:checked').forEach(cb => cb.checked = false);
        } else {
            body.querySelectorAll('.tf-input').forEach(inp => inp.value = '');
            // Keep only first rule row
            const ruleRows = body.querySelectorAll('.tf-rule-row');
            for (let i = 1; i < ruleRows.length; i++) ruleRows[i].remove();
        }

        trigger.classList.remove('active');
        popover.hidePopover();
        
        App.TableFilter.saveToUrl(tableId);
        App.TableFilter.updateGlobalUI(tableId);
        App.TableFilter.run(tableId);
    },

    run: (tableId) => {
        const table = document.getElementById(tableId);
        if (!table) return;
        const filters = App.TableFilter.activeFilters[tableId] || {};
        const rows = table.querySelectorAll('tbody tr:not(.no-data)');

        rows.forEach(row => {
            let show = true;
            for (const index in filters) {
                const filter = filters[index];
                const cell = row.cells[index];
                if (!cell) continue;
                
                const cellValue = cell.textContent.trim().toLowerCase();
                const cellValueNum = parseFloat(cellValue.replace(/[^0-9.-]+/g, ""));
                
                if (filter.type === 'select') {
                    if (!filter.values.some(v => v.toLowerCase() === cellValue)) {
                        show = false;
                        break;
                    }
                } else {
                    // All rules must match (AND)
                    for (const rule of filter.rules) {
                        const val = rule.value;
                        const valNum = parseFloat(val);
                        let match = true;

                        if (filter.type === 'date') {
                            let cellDate = cellValue;
                            if (cellValue.includes('.')) {
                                const parts = cellValue.split('.');
                                if (parts.length === 3) cellDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
                            }
                            switch (rule.operator) {
                                case 'equals': if (cellDate !== val) match = false; break;
                                case 'after': if (cellDate <= val) match = false; break;
                                case 'before': if (cellDate >= val) match = false; break;
                            }
                        } else {
                            switch (rule.operator) {
                                case 'contains': if (!cellValue.includes(val)) match = false; break;
                                case 'equals': if (cellValue !== val) match = false; break;
                                case 'starts': if (!cellValue.startsWith(val)) match = false; break;
                                case 'ends': if (!cellValue.endsWith(val)) match = false; break;
                                case 'not_contains': if (cellValue.includes(val)) match = false; break;
                                case 'gt': if (isNaN(cellValueNum) || cellValueNum <= valNum) match = false; break;
                                case 'lt': if (isNaN(cellValueNum) || cellValueNum >= valNum) match = false; break;
                            }
                        }
                        if (!match) {
                            show = false;
                            break;
                        }
                    }
                }
                if (!show) break;
            }
            row.style.display = show ? '' : 'none';
        });

        // Remove filtering class to show filtered table
        table.classList.remove('is-filtering');
    }
};
