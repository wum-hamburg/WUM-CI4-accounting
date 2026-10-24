/**
 * Shared dynamic position rows for document wizard and quote conversion.
 */
(function () {
    const PREFIX_PATTERN = /^\s*(Eventual|Alternativ)\s*:\s*/iu;

    const unitSelectHtml = (fieldName) =>
        '<select name="' + fieldName + '" class="form-select form-select-sm">'
        + '<option value="">—</option>'
        + '<option value="psch.">psch.</option>'
        + '<option value="Std.">Std.</option>'
        + '<option value="AW">AW</option>'
        + '<option value="Stck." selected>Stck.</option>'
        + '</select>';

    function titleInputSelector(titleName) {
        return 'input[name="' + titleName + '"]';
    }

    function stripPrefix(value) {
        return String(value || '').replace(PREFIX_PATTERN, '').trimStart();
    }

    function buildPrefixButton(kind, label, title) {
        return '<button type="button" class="btn btn-sm btn-outline-secondary prefix-' + kind + '" title="' + title + '">' + label + '</button>';
    }

    function buildActionCell(config) {
        if (!config.optionalPrefixes) {
            return '<td><button type="button" class="btn btn-sm btn-outline-danger ' + config.removeClass + '" title="Zeile entfernen">&times;</button></td>';
        }
        return '<td class="text-nowrap">'
            + buildPrefixButton('alternativ', 'A', 'Alternativ') + ' '
            + buildPrefixButton('eventual', 'E', 'Eventual') + ' '
            + '<button type="button" class="btn btn-sm btn-outline-danger ' + config.removeClass + '" title="Zeile entfernen">&times;</button>'
            + '</td>';
    }

    function buildRowHtml(config) {
        return '<td><input type="text" name="' + config.titleName + '" class="form-control form-control-sm"></td>'
            + '<td><input type="number" name="' + config.qtyName + '" class="form-control form-control-sm" min="0"></td>'
            + '<td>' + unitSelectHtml(config.unitName) + '</td>'
            + '<td><input type="text" name="' + config.priceName + '" class="form-control form-control-sm" placeholder="0,00"></td>'
            + buildActionCell(config);
    }

    function setPrefixActive(row, kind) {
        row.querySelectorAll('.prefix-alternativ, .prefix-eventual').forEach(function (btn) {
            btn.classList.remove('btn-secondary', 'active');
            btn.classList.add('btn-outline-secondary');
        });
        if (!kind) {
            delete row.dataset.optionalPrefix;
            return;
        }
        const activeBtn = row.querySelector('.prefix-' + kind);
        if (activeBtn) {
            activeBtn.classList.remove('btn-outline-secondary');
            activeBtn.classList.add('btn-secondary', 'active');
        }
        row.dataset.optionalPrefix = kind;
    }

    function applyPrefix(row, config, kind) {
        const input = row.querySelector(titleInputSelector(config.titleName));
        if (!input) {
            return;
        }
        const detail = stripPrefix(input.value);
        if (row.dataset.optionalPrefix === kind) {
            input.value = detail;
            setPrefixActive(row, null);
            return;
        }
        input.value = detail;
        setPrefixActive(row, kind);
    }

    function mergePrefixesBeforeSubmit(table, config) {
        if (!config.optionalPrefixes) {
            return;
        }
        table.querySelectorAll('.' + config.rowClass).forEach(function (row) {
            const input = row.querySelector(titleInputSelector(config.titleName));
            const kind = row.dataset.optionalPrefix;
            if (!input || !kind) {
                return;
            }
            const detail = input.value.trim();
            const prefix = kind === 'alternativ' ? 'Alternativ:' : 'Eventual:';
            input.value = detail === '' ? prefix : prefix + ' ' + detail;
        });
    }

    function initExistingRow(row, config) {
        if (!config.optionalPrefixes) {
            return;
        }
        const kind = row.dataset.optionalPrefix;
        if (kind === 'alternativ' || kind === 'eventual') {
            setPrefixActive(row, kind);
        }
    }

    window.wumInitPositionRows = function (config) {
        const table = document.querySelector(config.tableSelector);
        const addBtn = document.getElementById(config.addButtonId);
        const form = document.querySelector(config.formSelector || 'form');

        if (!table || !addBtn) {
            return;
        }

        table.querySelectorAll('.' + config.rowClass).forEach(function (row) {
            initExistingRow(row, config);
        });

        addBtn.addEventListener('click', function () {
            const tbody = table.querySelector('tbody');
            const tr = document.createElement('tr');
            tr.className = config.rowClass;
            tr.innerHTML = buildRowHtml(config);
            tbody.appendChild(tr);
        });

        (form || table).addEventListener('click', function (e) {
            const target = e.target;
            if (target.classList.contains('prefix-alternativ')) {
                applyPrefix(target.closest('tr'), config, 'alternativ');
                return;
            }
            if (target.classList.contains('prefix-eventual')) {
                applyPrefix(target.closest('tr'), config, 'eventual');
                return;
            }
            if (!target.classList.contains(config.removeClass)) {
                return;
            }
            const rows = table.querySelectorAll('.' + config.rowClass);
            if (rows.length > 1) {
                target.closest('tr').remove();
            }
        });

        if (form && config.optionalPrefixes) {
            form.addEventListener('submit', function () {
                mergePrefixesBeforeSubmit(table, config);
            });
        }
    };
})();
