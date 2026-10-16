<?php
// uploads/index.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
require '../config/database.php';
$user_id = $_SESSION['user_id'];
include '../includes/header.php';
?>

<style>
.drop-zone {
    border: 2px dashed rgba(255,255,255,0.1);
    border-radius: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--accent-neon);
    background: rgba(56,189,248,0.05);
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(56,189,248,0.1);
}
.drop-zone input[type="file"] {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0; cursor: pointer; z-index: 2;
}
.drop-icon {
    width: 72px; height: 72px; border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; margin: 0 auto 16px;
}
.format-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--bg-card); border: 1px solid var(--border-card);
    border-radius: 999px; padding: 4px 12px; font-size: 12px; color: var(--text-muted);
}

/* Preview table row colors */
.txn-income td .amt-cell { color: #10b981 !important; }
.txn-expense td .amt-cell { color: #ef4444 !important; }
.amt-income { color: #10b981 !important; font-weight: 700; }
.amt-expense { color: #ef4444 !important; font-weight: 700; }

/* Type badge */
.type-income { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.25); border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; }
.type-expense { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; }

/* Progress animation */
.upload-progress { height: 3px; background: rgba(255,255,255,0.05); border-radius: 999px; overflow: hidden; margin-top: 12px; display: none; }
.upload-progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, var(--accent-neon), var(--accent-positive)); border-radius: 999px; transition: width 0.4s ease; animation: progressPulse 1.5s ease-in-out infinite; }
@keyframes progressPulse { 0%,100%{opacity:1;} 50%{opacity:0.6;} }

.step-indicator { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }
.step-dot { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }
.step-dot.active { background: var(--accent); color: #000; }
.step-dot.done { background: var(--s-400); color: #000; }
.step-dot.pending { background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border-card); }
.step-line { flex: 1; height: 1px; background: var(--border-card); max-width: 40px; }
</style>

<div class="row fade-in-up">
    <div class="col-12 mb-2">
        <h2 class="fw-bold mb-1">Import Transactions</h2>
        <p class="text-secondary mb-4">Upload bank statements to auto-extract and categorize your transactions.</p>

        <!-- Step indicator -->
        <div class="step-indicator">
            <span class="step-dot active" id="step1dot">1</span>
            <span>Upload File</span>
            <span class="step-line"></span>
            <span class="step-dot pending" id="step2dot">2</span>
            <span>Review & Edit</span>
            <span class="step-line"></span>
            <span class="step-dot pending" id="step3dot">3</span>
            <span>Import to Dashboard</span>
        </div>
    </div>
</div>

<!-- CSV Format Info -->
<div class="alert d-flex gap-3 align-items-start mb-4 fade-in-up" style="background:rgba(56,189,248,0.07);border:1px solid rgba(56,189,248,0.2);border-radius:14px;padding:16px 20px;">
    <i class="bi bi-info-circle-fill text-neon fs-5 flex-shrink-0 mt-1"></i>
    <div>
        <div class="fw-bold text-light mb-1">CSV Format Guide</div>
        <div class="text-secondary small">Your CSV must have columns in this order: <code style="color:#38bdf8;">amount, category, subcategory, description, method, date</code><br>
        Positive amounts = <span class="text-positive fw-bold">Income</span>, Negative amounts = <span class="text-negative fw-bold">Expenses</span></div>
    </div>
</div>

<div class="row fade-in-up delay-1">
    <!-- PDF Drop Zone -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-5 text-center drop-zone" id="pdfDropZone">
            <input type="file" id="pdfInput" accept=".pdf">
            <div class="drop-icon" style="background:rgba(239,68,68,0.12);">
                <i class="bi bi-file-earmark-pdf-fill" style="color:#ef4444;"></i>
            </div>
            <h4 class="fw-bold text-light mb-2">PDF Bank Statement</h4>
            <p class="text-secondary small mb-4">Auto-detects Swiggy, Zomato, Uber, and UPI patterns from your PDF.</p>
            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="format-pill"><i class="bi bi-filetype-pdf" style="color:#ef4444;"></i> .PDF</span>
            </div>
            <button class="btn btn-outline-danger rounded-pill px-4" style="pointer-events:none;">
                <i class="bi bi-cloud-upload me-2"></i>Drop or Click to Upload
            </button>
            <div class="upload-progress" id="pdfProgress">
                <div class="upload-progress-bar" id="pdfProgressBar"></div>
            </div>
        </div>
    </div>
    
    <!-- CSV Drop Zone -->
    <div class="col-md-6 mb-4">
        <div class="glass-card p-5 text-center drop-zone" id="csvDropZone">
            <input type="file" id="csvInput" accept=".csv">
            <div class="drop-icon" style="background:rgba(16,185,129,0.12);">
                <i class="bi bi-filetype-csv" style="color:#10b981;"></i>
            </div>
            <h4 class="fw-bold text-light mb-2">CSV Data File</h4>
            <p class="text-secondary small mb-4">Import from any bank export or custom spreadsheet. Income shown in green, expenses in red.</p>
            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="format-pill"><i class="bi bi-filetype-csv" style="color:#10b981;"></i> .CSV</span>
            </div>
            <button class="btn btn-outline-success rounded-pill px-4" style="pointer-events:none;">
                <i class="bi bi-cloud-upload me-2"></i>Drop or Click to Upload
            </button>
            <div class="upload-progress" id="csvProgress">
                <div class="upload-progress-bar" id="csvProgressBar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" style="z-index:9999;">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-secondary border-opacity-25">
        <div>
            <h5 class="modal-title fw-bold mb-1"><i class="bi bi-table me-2 text-neon"></i>Parsed Transactions — Review & Edit</h5>
            <div class="text-secondary small" id="parsedCount">0 transactions detected</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <!-- Summary bar in modal -->
      <div class="d-flex gap-3 px-4 py-3 border-bottom border-secondary border-opacity-25" id="previewSummary" style="background:rgba(0,0,0,0.2);">
        <div style="font-size:13px;"><span class="text-secondary">Income:</span> <strong class="text-positive" id="sumIncome">₹0</strong></div>
        <div style="font-size:13px;"><span class="text-secondary">Expenses:</span> <strong class="text-negative" id="sumExpense">₹0</strong></div>
        <div style="font-size:13px;"><span class="text-secondary">Net:</span> <strong class="text-neon" id="sumNet">₹0</strong></div>
      </div>
      <div class="modal-body p-0">
          <div class="table-responsive" style="max-height:55vh;">
              <table class="table table-dark table-hover mb-0 align-middle" id="previewTable">
                  <thead class="position-sticky top-0 z-1" style="background:#0a1223;">
                      <tr class="border-secondary">
                          <th class="text-secondary small text-uppercase px-4">Type</th>
                          <th class="text-secondary small text-uppercase">Date</th>
                          <th class="text-secondary small text-uppercase">Description</th>
                          <th class="text-secondary small text-uppercase">Category</th>
                          <th class="text-secondary small text-uppercase">Subcategory</th>
                          <th class="text-secondary small text-uppercase">Method</th>
                          <th class="text-secondary small text-uppercase text-end">Amount</th>
                          <th class="text-secondary small text-uppercase text-center">Remove</th>
                      </tr>
                  </thead>
                  <tbody id="previewBody"></tbody>
              </table>
          </div>
      </div>
      <div class="modal-footer border-secondary border-opacity-25 bg-transparent justify-content-between">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">← Re-upload</button>
          <button type="button" class="btn btn-primary fw-bold px-5" id="confirmImportBtn">
              <i class="bi bi-cloud-check me-2"></i>Confirm & Import
          </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>
<script src="<?= $baseUrl ?>/assets/js/parser.js"></script>

<?php include '../includes/footer.php'; ?>
