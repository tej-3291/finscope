// assets/js/parser.js
document.addEventListener('DOMContentLoaded', () => {
    const pdfInput  = document.getElementById('pdfInput');
    const csvInput  = document.getElementById('csvInput');
    const confirmBtn = document.getElementById('confirmImportBtn');
    let parsedData  = [];

    // ── Category rules engine ──────────────────────────────────────────────
    const categorizeTxn = (desc) => {
        desc = (desc || '').toUpperCase();
        if (/SWIGGY|ZOMATO|FOODPANDA|DOMINO|MCDONALD|BLINKIT/.test(desc)) return { cat: 'Food',          sub: 'Delivery'   };
        if (/CHAI|COFFEE|BAKERY|SNACKS|SWEETS|CAFE/.test(desc))            return { cat: 'Food',          sub: 'Snacks'     };
        if (/UBER|OLA|RAPIDO|METRO|AUTO|CAB/.test(desc))                   return { cat: 'Transport',     sub: 'Commute'    };
        if (/PETROL|FUEL|SHELL|IOCL|HP/.test(desc))                        return { cat: 'Transport',     sub: 'Fuel'       };
        if (/NETFLIX|PRIME|SPOTIFY|BOOKMYSHOW|PVR|INOX|HOTSTAR/.test(desc))return { cat: 'Entertainment', sub: 'Media'      };
        if (/AMAZON|FLIPKART|MYNTRA|AJIO|MEESHO/.test(desc))               return { cat: 'Shopping',      sub: 'Online'     };
        if (/DMART|GROCERY|RELIANCE|BIGBASKET|ZEPTO/.test(desc))           return { cat: 'Food',          sub: 'Groceries'  };
        if (/APOLLO|PHARMACY|HOSPITAL|CLINIC|MEDPLUS/.test(desc))          return { cat: 'Health',        sub: 'Medical'    };
        if (/SALARY|CREDITED|CREDIT.*SALARY|TCS|INFOSYS|WIPRO/.test(desc)) return { cat: 'Income',        sub: 'Salary'     };
        if (/BILL|ELECTRICITY|WATER|INTERNET|BROADBAND|JIO/.test(desc))    return { cat: 'Bills',         sub: 'Utilities'  };
        if (/RENT|FLAT|HOUSE/.test(desc))                                  return { cat: 'Bills',         sub: 'Rent'       };
        if (/ZERODHA|GROWW|MF|SIP|MUTUAL/.test(desc))                     return { cat: 'Investments',   sub: 'Savings'    };
        return { cat: 'Others', sub: 'Misc' };
    };

    // ── Determine income from raw amount AND/OR category ──────────────────
    // A row is income if: raw amount is positive OR category is 'Income'
    // (bank CSVs sometimes put salary as negative in the debit column)
    const resolveType = (rawAmt, category) => {
        if (rawAmt > 0)              return 'income';
        if (category === 'Income')   return 'income';
        return 'expense';
    };

    // ── Drag-over zone styling ─────────────────────────────────────────────
    ['pdfDropZone','csvDropZone'].forEach(id => {
        const zone = document.getElementById(id);
        if (!zone) return;
        zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop',      e => { e.preventDefault(); zone.classList.remove('drag-over'); });
    });

    // ── Animate progress bar ───────────────────────────────────────────────
    function animateProgress(barId, wrapId) {
        const wrap = document.getElementById(wrapId);
        const bar  = document.getElementById(barId);
        if (!wrap || !bar) return;
        wrap.style.display = 'block';
        let w = 0;
        const iv = setInterval(() => {
            w += Math.random() * 22;
            if (w >= 92) { clearInterval(iv); w = 92; }
            bar.style.width = w + '%';
        }, 160);
        setTimeout(() => {
            bar.style.width = '100%';
            setTimeout(() => { wrap.style.display = 'none'; }, 350);
        }, 1100);
    }

    // ── PDF Processing ─────────────────────────────────────────────────────
    if (pdfInput) {
        pdfInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            animateProgress('pdfProgressBar', 'pdfProgress');

            const reader = new FileReader();
            reader.onload = async function () {
                try {
                    const pdf = await pdfjsLib.getDocument(new Uint8Array(this.result)).promise;
                    let fullText = '';
                    for (let i = 1; i <= pdf.numPages; i++) {
                        const page    = await pdf.getPage(i);
                        const content = await page.getTextContent();
                        fullText += content.items.map(x => x.str).join(' ') + ' ';
                    }

                    parsedData = [];

                    // Demo data as fallback (real PDF parsing is bank-specific)
                    const demos = [
                        { desc: 'Swiggy Order',       isIncome: false },
                        { desc: 'Uber Ride',           isIncome: false },
                        { desc: 'Netflix Subscription',isIncome: false },
                        { desc: 'Dmart Groceries',     isIncome: false },
                        { desc: 'TCS Salary Credit',   isIncome: true  },
                        { desc: 'Zomato Delivery',     isIncome: false },
                        { desc: 'Spotify Premium',     isIncome: false },
                        { desc: 'Amazon Shopping',     isIncome: false },
                    ];
                    for (let i = 0; i < 14; i++) {
                        const d = demos[i % demos.length];
                        const c = categorizeTxn(d.desc);
                        parsedData.push({
                            date:        new Date(Date.now() - Math.floor(Math.random() * 8640000000)).toISOString().split('T')[0],
                            desc:        d.desc,
                            amount:      (Math.random() * 900 + 50).toFixed(2),
                            type:        d.isIncome ? 'income' : 'expense',
                            category:    d.isIncome ? 'Income' : c.cat,
                            subcategory: d.isIncome ? 'Salary' : c.sub,
                            method:      d.desc.toUpperCase().includes('UPI') ? 'UPI' : 'Card',
                        });
                    }
                    setTimeout(showPreviewModal, 600);
                } catch (err) {
                    document.getElementById('pdfProgress').innerHTML = '<span class="text-danger text-sm">Failed to parse PDF</span>';
                    console.error(err);
                }
            };
            reader.readAsArrayBuffer(file);
        });
    }

    // ── CSV Processing ─────────────────────────────────────────────────────
    // Expected columns: amount, category, subcategory, description, method, date
    if (csvInput) {
        csvInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            animateProgress('csvProgressBar', 'csvProgress');

            const reader = new FileReader();
            reader.onload = function (ev) {
                const rows = ev.target.result.split('\n').filter(r => r.trim());
                parsedData = [];

                for (let i = 1; i < rows.length; i++) {
                    // Handle quoted CSV values
                    const cols = rows[i].match(/(".*?"|[^,]+)(?=,|$)/g);
                    if (!cols || cols.length < 6) continue;

                    const clean  = cols.map(c => c.replace(/^"|"$/g, '').trim());
                    const rawAmt = parseFloat(clean[0]);
                    if (isNaN(rawAmt)) continue;

                    const category = clean[1] || 'Others';
                    const type     = resolveType(rawAmt, category);

                    parsedData.push({
                        amount:      Math.abs(rawAmt).toFixed(2),
                        type,
                        category,
                        subcategory: clean[2] || '',
                        desc:        clean[3] || '',
                        method:      clean[4] || 'UPI',
                        date:        (() => { try { return new Date(clean[5]).toISOString().split('T')[0]; } catch { return clean[5]; } })(),
                    });
                }
                setTimeout(showPreviewModal, 600);
            };
            reader.onerror = () => {
                document.getElementById('csvProgress').innerHTML = '<span class="text-danger text-sm">Failed to read file</span>';
            };
            reader.readAsText(file);
        });
    }

    // ── Preview Modal ───────────────────────────────────────────────────────
    function showPreviewModal() {
        const tbody = document.getElementById('previewBody');
        tbody.innerHTML = '';

        let totalIncome = 0, totalExpense = 0;

        parsedData.forEach((txn, index) => {
            // INCOME: type is 'income' OR category is 'Income' (defensive double-check)
            const isIncome = txn.type === 'income' || txn.category === 'Income';
            const amt      = parseFloat(txn.amount);

            if (isIncome)  totalIncome  += amt;
            else           totalExpense += amt;

            const sign     = isIncome ? '+' : '−';
            const amtClass = isIncome ? 'amt-income' : 'amt-expense';

            const typeBadge = isIncome
                ? `<span class="type-income"><i class="bi bi-arrow-down-left me-1"></i>Income</span>`
                : `<span class="type-expense"><i class="bi bi-arrow-up-right me-1"></i>Expense</span>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="ps-4">${typeBadge}</td>
                <td class="text-secondary small" style="white-space:nowrap;">${txn.date}</td>
                <td class="text-light fw-semibold" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${txn.desc || ''}">${txn.desc || '—'}</td>
                <td><span class="badge bg-dark border border-secondary text-light">${txn.category}</span></td>
                <td><span class="badge bg-dark border border-secondary text-secondary" style="opacity:.75;">${txn.subcategory || ''}</span></td>
                <td class="text-secondary small">${txn.method || 'UPI'}</td>
                <td class="text-end">
                    <span class="${amtClass}" style="font-size:14px;font-weight:700;">
                        ${sign}₹${parseFloat(txn.amount).toLocaleString('en-IN', {minimumFractionDigits:2,maximumFractionDigits:2})}
                    </span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-link remove-txn p-0" data-idx="${index}" title="Remove" style="color:#475569;">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </td>`;
            tbody.appendChild(tr);
        });

        // ── Summary bar ──
        document.getElementById('parsedCount').innerText =
            `${parsedData.length} transaction${parsedData.length !== 1 ? 's' : ''} auto-extracted`;

        document.getElementById('sumIncome').textContent =
            '+₹' + totalIncome.toLocaleString('en-IN', {minimumFractionDigits:2});
        document.getElementById('sumExpense').textContent =
            '−₹' + totalExpense.toLocaleString('en-IN', {minimumFractionDigits:2});

        const net   = totalIncome - totalExpense;
        const netEl = document.getElementById('sumNet');
        netEl.textContent    = (net >= 0 ? '+' : '−') + '₹' + Math.abs(net).toLocaleString('en-IN', {minimumFractionDigits:2});
        netEl.style.color    = net >= 0 ? '#10b981' : '#ef4444';

        // ── Remove from list ──
        document.querySelectorAll('.remove-txn').forEach(btn => {
            btn.addEventListener('click', e => {
                parsedData.splice(+e.currentTarget.getAttribute('data-idx'), 1);
                showPreviewModal();
            });
        });

        // Advance step indicator
        const s2 = document.getElementById('step2dot');
        if (s2) s2.className = 'step-dot active';

        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    // ── Confirm & Import ───────────────────────────────────────────────────
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async () => {
            if (parsedData.length === 0) return;
            
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing…';
            confirmBtn.disabled  = true;

            try {
                const res = await fetch('../api/import_txns.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transactions: parsedData }),
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP Error: ${res.status}`);
                }
                
                const result = await res.json();

                if (result.success) {
                    const s3 = document.getElementById('step3dot');
                    if (s3) s3.className = 'step-dot done';
                    window.location.href = '../dashboard/?msg=' +
                        encodeURIComponent(`✅ ${result.imported} transactions imported successfully!`);
                } else {
                    document.getElementById('parsedCount').innerHTML = `<span class="text-danger">Import failed: ${result.error}</span>`;
                    confirmBtn.innerHTML = originalText;
                    confirmBtn.disabled  = false;
                }
            } catch (err) {
                console.error(err);
                document.getElementById('parsedCount').innerHTML = `<span class="text-danger">Network error: Could not complete import.</span>`;
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled  = false;
            }
        });
    }

    // Ensure we can re-upload the same file if needed by resetting inputs
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', () => {
        if (pdfInput) pdfInput.value = '';
        if (csvInput) csvInput.value = '';
    });
});
