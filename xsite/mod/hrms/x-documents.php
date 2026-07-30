<?php
/**
 * HRMS Employee Documents View
 * Shows employee's uploaded documents (ID proof, address proof, etc.)
 */

requireHRMSLogin();
$employeeID = $_SESSION['HRMS_USER_ID'];
$employeeName = $_SESSION['HRMS_USER_NAME'];
?>

<div class="hrms-page documents-page">
    <!-- Page Header -->
    <div class="page-header">
        <h1>My Documents</h1>
        <p class="subtitle">View and download your uploaded documents</p>
    </div>

    <!-- Documents Grid -->
    <div id="documentsContainer" class="documents-grid">
        <div class="loading-state">
            <div class="spinner"></div>
            <p>Loading documents...</p>
        </div>
    </div>

    <!-- Empty State Template -->
    <template id="emptyStateTemplate">
        <div class="empty-state">
            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3>No Documents Found</h3>
            <p>Your documents haven't been uploaded yet. Please contact HR.</p>
        </div>
    </template>
</div>

<!-- Document Preview Modal -->
<div id="documentModal" class="modal-overlay" style="display: none;">
    <div class="modal-container document-modal">
        <div class="modal-header">
            <h2 id="modalDocTitle">Document</h2>
            <button class="modal-close" onclick="closeDocumentModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="documentPreview" class="document-preview">
                <!-- Image or PDF preview will be inserted here -->
            </div>
            <div class="document-info">
                <div class="info-row">
                    <span class="info-label">Document Type</span>
                    <span id="modalDocType" class="info-value">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Uploaded On</span>
                    <span id="modalDocDate" class="info-value">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">File Size</span>
                    <span id="modalDocSize" class="info-value">-</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a id="modalDownloadBtn" href="#" class="btn btn-primary" download>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Download
            </a>
        </div>
    </div>
</div>

<style>
.documents-page {
    padding: 1rem;
    padding-bottom: 100px;
}

.page-header {
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.page-header .subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

/* Documents Grid */
.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

/* Document Card */
.document-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.document-card:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.document-card .doc-icon {
    width: 48px;
    height: 48px;
    background: rgba(245, 158, 11, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.document-card .doc-icon svg {
    width: 24px;
    height: 24px;
    color: var(--accent);
}

.document-card .doc-icon.pdf-icon {
    background: rgba(239, 68, 68, 0.1);
}

.document-card .doc-icon.pdf-icon svg {
    color: #ef4444;
}

.document-card .doc-icon.image-icon {
    background: rgba(59, 130, 246, 0.1);
}

.document-card .doc-icon.image-icon svg {
    color: #3b82f6;
}

.document-card .doc-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.document-card .doc-type {
    font-size: 0.75rem;
    color: var(--accent);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
}

.document-card .doc-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.document-card .doc-size {
    font-family: 'JetBrains Mono', monospace;
}

.document-card .doc-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.document-card .doc-actions .btn {
    flex: 1;
    padding: 0.5rem;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.document-card .doc-actions .btn svg {
    width: 14px;
    height: 14px;
}

/* Document Types Badge */
.doc-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 100px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.doc-type-badge.id-proof {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
}

.doc-type-badge.address-proof {
    background: rgba(34, 197, 94, 0.15);
    color: #4ade80;
}

.doc-type-badge.education {
    background: rgba(168, 85, 247, 0.15);
    color: #c084fc;
}

.doc-type-badge.experience {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
}

.doc-type-badge.other {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
}

/* Loading State */
.loading-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--text-muted);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--border-color);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
}

.empty-state .empty-icon {
    width: 64px;
    height: 64px;
    color: var(--text-muted);
    opacity: 0.5;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-container {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.modal-close {
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}

.modal-close svg {
    width: 18px;
    height: 18px;
}

.modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    flex: 1;
}

.document-preview {
    background: var(--surface-bg);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.document-preview img {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
}

.document-preview iframe {
    width: 100%;
    height: 400px;
    border: none;
}

.document-preview .no-preview {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.document-preview .no-preview svg {
    width: 48px;
    height: 48px;
    opacity: 0.5;
    margin-bottom: 0.5rem;
}

.document-info {
    background: var(--surface-bg);
    border-radius: 12px;
    padding: 1rem;
}

.document-info .info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.document-info .info-row:not(:last-child) {
    border-bottom: 1px solid var(--border-color);
}

.document-info .info-label {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.document-info .info-value {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-primary);
}

.modal-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
}

.modal-footer .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-footer .btn svg {
    width: 18px;
    height: 18px;
}

/* Button Styles */
.btn {
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    text-decoration: none;
}

.btn-primary {
    background: var(--accent);
    color: #000;
}

.btn-primary:hover {
    background: var(--accent-hover);
}

.btn-secondary {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: var(--hover-bg);
}

/* Responsive */
@media (max-width: 480px) {
    .documents-grid {
        grid-template-columns: 1fr;
    }

    .modal-container {
        max-height: 95vh;
    }

    .document-preview img {
        max-height: 250px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDocuments();
});

function loadDocuments() {
    $.ajax({
        url: SITEURL + '/xsite/mod/hrms/x-hrms.inc.php',
        type: 'POST',
        data: { xAction: 'getDocuments' },
        dataType: 'json',
        success: function(res) {
            if (res.err == 0) {
                renderDocuments(res.data || []);
            } else {
                showError(res.msg || 'Failed to load documents');
            }
        },
        error: function() {
            showError('Failed to load documents');
        }
    });
}

function renderDocuments(documents) {
    const container = document.getElementById('documentsContainer');

    if (!documents || documents.length === 0) {
        const template = document.getElementById('emptyStateTemplate');
        container.innerHTML = template.innerHTML;
        return;
    }

    container.innerHTML = documents.map(doc => createDocumentCard(doc)).join('');
}

function createDocumentCard(doc) {
    // Handle both old and new data structures
    var fileType = doc.fileType || (doc.documentPath ? doc.documentPath.split('.').pop() : 'file');
    var docName = doc.documentName || doc.docType || 'Document';
    var docType = doc.documentType || doc.docType || 'Document';
    var fileUrl = doc.fileUrl || (doc.documentPath ? '<?php echo SITEURL; ?>/' + doc.documentPath : '#');
    var uploadDate = doc.uploadedAt || doc.uploadDate || '';
    var fileSize = doc.fileSize || 0;

    const iconClass = getIconClass(fileType);
    const typeClass = getTypeClass(docType);
    const formattedSize = formatFileSize(fileSize);
    const formattedDate = formatDate(uploadDate);

    var docData = {
        documentName: docName,
        documentType: docType,
        fileType: fileType,
        fileUrl: fileUrl,
        uploadedAt: uploadDate,
        fileSize: fileSize
    };

    return `
        <div class="document-card" onclick='viewDocument(${JSON.stringify(docData)})'>
            <div class="doc-icon ${iconClass}">
                ${getFileIcon(fileType)}
            </div>
            <h3 class="doc-title">${escapeHtml(docName)}</h3>
            <span class="doc-type-badge ${typeClass}">${escapeHtml(docType)}</span>
            <div class="doc-meta">
                <span class="doc-date">${formattedDate}</span>
                <span class="doc-size">${formattedSize}</span>
            </div>
            <div class="doc-actions">
                <button class="btn btn-secondary" onclick="event.stopPropagation(); viewDocument(${JSON.stringify(docData).replace(/"/g, '&quot;')})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View
                </button>
                <a href="${fileUrl}" class="btn btn-primary" download onclick="event.stopPropagation()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    Download
                </a>
            </div>
        </div>
    `;
}

function getIconClass(fileType) {
    if (fileType === 'pdf') return 'pdf-icon';
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileType.toLowerCase())) return 'image-icon';
    return '';
}

function getTypeClass(documentType) {
    const type = documentType.toLowerCase();
    if (type.includes('id') || type.includes('aadhar') || type.includes('pan')) return 'id-proof';
    if (type.includes('address')) return 'address-proof';
    if (type.includes('education') || type.includes('degree') || type.includes('certificate')) return 'education';
    if (type.includes('experience') || type.includes('offer') || type.includes('relieving')) return 'experience';
    return 'other';
}

function getFileIcon(fileType) {
    if (fileType === 'pdf') {
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
            <path d="M9 15h6M9 11h6"/>
        </svg>`;
    }
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileType.toLowerCase())) {
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21,15 16,10 5,21"/>
        </svg>`;
    }
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14,2 14,8 20,8"/>
    </svg>`;
}

function viewDocument(doc) {
    const modal = document.getElementById('documentModal');
    const preview = document.getElementById('documentPreview');

    document.getElementById('modalDocTitle').textContent = doc.documentName;
    document.getElementById('modalDocType').textContent = doc.documentType;
    document.getElementById('modalDocDate').textContent = formatDate(doc.uploadedAt);
    document.getElementById('modalDocSize').textContent = formatFileSize(doc.fileSize);
    document.getElementById('modalDownloadBtn').href = doc.fileUrl;

    // Render preview based on file type
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(doc.fileType.toLowerCase())) {
        preview.innerHTML = `<img src="${doc.fileUrl}" alt="${escapeHtml(doc.documentName)}">`;
    } else if (doc.fileType === 'pdf') {
        preview.innerHTML = `<iframe src="${doc.fileUrl}#toolbar=0" title="${escapeHtml(doc.documentName)}"></iframe>`;
    } else {
        preview.innerHTML = `
            <div class="no-preview">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                </svg>
                <p>Preview not available</p>
                <p>Click download to view this file</p>
            </div>
        `;
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeDocumentModal() {
    const modal = document.getElementById('documentModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('documentPreview').innerHTML = '';
}

function formatFileSize(bytes) {
    if (!bytes) return '-';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    const container = document.getElementById('documentsContainer');
    container.innerHTML = `
        <div class="empty-state">
            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4M12 16h.01"/>
            </svg>
            <h3>Error</h3>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

// Close modal on overlay click
document.getElementById('documentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDocumentModal();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDocumentModal();
    }
});
</script>
