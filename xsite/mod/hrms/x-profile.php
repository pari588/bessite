<?php
/**
 * HRMS Employee Profile Page
 * Displays employee's profile information and settings
 */

requireHRMSLogin();
$employeeID = $_SESSION['HRMS_USER_ID'];
$employeeName = $_SESSION['HRMS_USER_NAME'];
$employeeEmail = $_SESSION['HRMS_USER_EMAIL'];
$isViewingAs = !empty($_SESSION['HRMS_VIEWING_AS']);
$originalUserName = $_SESSION['HRMS_ORIGINAL_USER_NAME'] ?? '';

// Check if HR Admin - use the original flag when viewing as another user
if ($isViewingAs) {
    $isHRAdmin = !empty($_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN']);
} else {
    $isHRAdmin = !empty($_SESSION['HRMS_IS_HR_ADMIN']);
}
?>

<div class="hrms-page profile-page">
    <?php if ($isViewingAs): ?>
    <!-- Viewing As Banner -->
    <div class="viewing-as-banner">
        <div class="viewing-as-info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>Viewing as <strong><?php echo htmlspecialchars($employeeName); ?></strong></span>
        </div>
        <button class="switch-back-btn" onclick="switchBackToAdmin()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Switch Back
        </button>
    </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar" id="profileAvatar">
            <?php echo strtoupper(substr($employeeName, 0, 2)); ?>
        </div>
        <div class="profile-info">
            <h1 id="profileName"><?php echo htmlspecialchars($employeeName); ?></h1>
            <p id="profileDesignation" class="designation">Loading...</p>
            <p id="profileEmail" class="email"><?php echo htmlspecialchars($employeeEmail); ?></p>
        </div>
        <button class="edit-profile-btn" onclick="openEditModal()" title="Edit Profile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        </button>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal-overlay">
        <div class="modal">
            <form id="editProfileForm" onsubmit="saveProfile(event)">
                <div class="modal-header">
                    <h3>Edit Profile</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="edit-info-text">You can update your personal, bank, and emergency details below. Name, email, and work information can only be changed by HR.</p>

                    <div class="form-section">
                        <h4 class="form-section-title">Personal Information</h4>
                        <div class="form-row">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="editPhone" placeholder="Enter phone number">
                        </div>
                        <div class="form-row">
                            <label>Date of Birth</label>
                            <input type="date" name="dateOfBirth" id="editDOB">
                        </div>
                        <div class="form-row">
                            <label>Address</label>
                            <textarea name="address" id="editAddress" placeholder="Enter your address" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">Bank Details</h4>
                        <div class="form-row">
                            <label>IFSC Code</label>
                            <div class="ifsc-input-group">
                                <input type="text" name="ifscCode" id="editIFSC" placeholder="e.g., HDFC0001234" maxlength="11" style="text-transform: uppercase;" oninput="this.value=this.value.toUpperCase()">
                                <button type="button" class="ifsc-lookup-btn" id="ifscLookupBtn" onclick="lookupIFSC()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                        <circle cx="11" cy="11" r="8"/>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    Lookup
                                </button>
                            </div>
                            <div id="ifscStatus" class="ifsc-status"></div>
                        </div>
                        <div class="form-row">
                            <label>Bank Name</label>
                            <input type="text" name="bankName" id="editBankName" placeholder="Auto-filled from IFSC lookup" readonly style="background: var(--gray-50);">
                        </div>
                        <div class="form-row">
                            <label>Branch</label>
                            <input type="text" name="bankBranch" id="editBankBranch" placeholder="Auto-filled from IFSC lookup" readonly style="background: var(--gray-50);">
                        </div>
                        <div class="form-row">
                            <label>Account Number</label>
                            <input type="text" name="accountNumber" id="editAccountNumber" placeholder="Enter account number">
                        </div>
                        <div class="form-row">
                            <label>PAN Number</label>
                            <input type="text" name="panNumber" id="editPAN" placeholder="e.g., ABCDE1234F" maxlength="10" style="text-transform: uppercase;">
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">Emergency Contact</h4>
                        <div class="form-row">
                            <label>Contact Name</label>
                            <input type="text" name="emergencyContactName" id="editEmergencyName" placeholder="Emergency contact name">
                        </div>
                        <div class="form-row">
                            <label>Relationship</label>
                            <select name="emergencyContactRelation" id="editEmergencyRelation">
                                <option value="">Select relationship</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Friend">Friend</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label>Contact Phone</label>
                            <input type="tel" name="emergencyContactPhone" id="editEmergencyPhone" placeholder="Emergency contact phone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isHRAdmin && !$isViewingAs): ?>
    <!-- Leave Requests Section (HR Admin Only) -->
    <div class="leave-requests-section">
        <div class="section-header">
            <div class="section-icon leave">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <h3 class="section-title">Leave Requests</h3>
            <span class="pending-badge" id="pendingCount">0</span>
        </div>
        <div class="section-content">
            <div id="leaveRequestsList" class="leave-requests-list">
                <div class="loading-state" style="padding: 1rem;">
                    <div class="spinner" style="width:24px;height:24px;"></div>
                    <p style="font-size:0.8125rem;">Loading leave requests...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Switch User Section (HR Admin Only) -->
    <div class="switch-user-section">
        <div class="section-header">
            <div class="section-icon admin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <h3 class="section-title">Switch User</h3>
            <span class="admin-badge">HR Admin</span>
        </div>
        <div class="section-content">
            <p class="switch-user-desc">View the portal as any employee to check their attendance, salary, and documents.</p>
            <div id="teamMembersList" class="team-members-list">
                <div class="loading-state" style="padding: 1rem;">
                    <div class="spinner" style="width:24px;height:24px;"></div>
                    <p style="font-size:0.8125rem;">Loading team members...</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Profile Details -->
    <div id="profileContainer" class="profile-sections">
        <div class="loading-state">
            <div class="spinner"></div>
            <p>Loading profile...</p>
        </div>
    </div>
</div>

<style>
.profile-page {
    padding: 1rem;
    padding-bottom: 100px;
}

/* Edit Profile Button */
.edit-profile-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--surface-bg);
    border: 1px solid var(--border-color);
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.edit-profile-btn:hover {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
}

.edit-profile-btn svg {
    width: 18px;
    height: 18px;
}

.profile-header {
    position: relative;
}

/* Modal Styles - Consistent with salary-processing */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
    padding: 20px;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    transform: scale(0.95);
    transition: transform 0.2s;
    overflow: hidden;
}

.modal-overlay.active .modal {
    transform: scale(1);
}

.modal form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
    flex-shrink: 0;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: transparent;
    border: none;
    color: var(--gray-400);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.modal-close:hover {
    background: var(--gray-100);
    color: var(--gray-600);
}

.modal-close svg {
    width: 20px;
    height: 20px;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

.edit-info-text {
    font-size: 13px;
    color: var(--gray-500);
    margin: 0 0 20px 0;
    padding: 12px 16px;
    background: var(--gray-50);
    border-radius: 8px;
    line-height: 1.5;
}

.form-section {
    margin-bottom: 24px;
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gray-100);
}

.form-row {
    margin-bottom: 16px;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-row label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 6px;
}

.form-row input,
.form-row select,
.form-row textarea {
    width: 100%;
    padding: 10px 14px;
    font-size: 15px;
    color: var(--gray-900);
    background: #fff;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    outline: none;
    transition: all 0.15s;
    font-family: inherit;
}

.form-row input:focus,
.form-row select:focus,
.form-row textarea:focus {
    border-color: var(--blue-500);
    box-shadow: 0 0 0 3px var(--blue-100);
}

.form-row input::placeholder,
.form-row textarea::placeholder {
    color: var(--gray-400);
}

.form-row textarea {
    resize: vertical;
    min-height: 80px;
}

/* IFSC Lookup Styles */
.ifsc-input-group {
    display: flex;
    gap: 8px;
}

.ifsc-input-group input {
    flex: 1;
}

.ifsc-lookup-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    background: var(--blue-600);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}

.ifsc-lookup-btn:hover {
    background: var(--blue-700);
}

.ifsc-lookup-btn:disabled {
    background: var(--gray-400);
    cursor: not-allowed;
}

.ifsc-lookup-btn svg {
    flex-shrink: 0;
}

.ifsc-status {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.5;
}

.ifsc-status .success {
    color: #16a34a;
}

.ifsc-status .error {
    color: #dc2626;
}

.ifsc-status .info {
    color: #3b82f6;
}

.ifsc-status .muted {
    color: var(--gray-500);
    font-size: 11px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--gray-200);
    flex-shrink: 0;
}

.modal-footer .btn {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
}

.modal-footer .btn-secondary {
    background: #fff;
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
}

.modal-footer .btn-secondary:hover {
    background: var(--gray-50);
}

.modal-footer .btn-primary {
    background: var(--blue-600);
    border: none;
    color: #fff;
}

.modal-footer .btn-primary:hover {
    background: var(--blue-700);
}

.modal-footer .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Toast Notification */
.toast {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--text-primary);
    color: white;
    padding: 0.875rem 1.5rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    z-index: 1100;
    animation: toastIn 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toast.success {
    background: #10b981;
}

.toast.error {
    background: #ef4444;
}

.toast svg {
    width: 18px;
    height: 18px;
}

@keyframes toastIn {
    from {
        transform: translateX(-50%) translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}

/* Viewing As Banner */
.viewing-as-banner {
    background: transparent;
    border: 1px solid #7c3aed;
    border-radius: 12px;
    padding: 0.875rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    gap: 0.75rem;
}

.viewing-as-info {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    color: #7c3aed;
    font-size: 0.875rem;
}

.viewing-as-info svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.switch-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    background: transparent;
    border: 1px solid #7c3aed;
    border-radius: 8px;
    color: #7c3aed;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.switch-back-btn:hover {
    background: rgba(124, 58, 237, 0.1);
}

.switch-back-btn svg {
    width: 14px;
    height: 14px;
}

/* Leave Requests Section */
.leave-requests-section {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.leave-requests-section .section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.section-icon.leave {
    background: transparent;
    border: 1px solid #f59e0b;
    color: #f59e0b;
}

.pending-badge {
    margin-left: auto;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pending-badge.zero {
    background: var(--border-color);
    color: var(--text-muted);
}

.leave-requests-list {
    padding: 0.5rem;
    max-height: 400px;
    overflow-y: auto;
}

.leave-request-item {
    background: var(--surface-bg);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 0.5rem;
}

.leave-request-item:last-child {
    margin-bottom: 0;
}

.leave-request-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.leave-request-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: transparent;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    flex-shrink: 0;
}

.leave-request-info {
    flex: 1;
    min-width: 0;
}

.leave-request-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.leave-request-type {
    font-size: 0.75rem;
    color: var(--accent);
    margin: 0;
    font-weight: 500;
}

.leave-request-details {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    margin-bottom: 0.75rem;
    font-size: 0.8125rem;
}

.leave-detail {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: var(--text-muted);
}

.leave-detail svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

.leave-detail strong {
    color: var(--text-primary);
    font-weight: 500;
}

.leave-request-reason {
    background: rgba(245, 158, 11, 0.1);
    border-left: 3px solid #f59e0b;
    padding: 0.625rem 0.75rem;
    border-radius: 0 8px 8px 0;
    margin-bottom: 0.875rem;
}

.leave-request-reason p {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--text-primary);
    line-height: 1.5;
}

.leave-request-actions {
    display: flex;
    gap: 0.5rem;
}

.leave-action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.625rem 1rem;
    border: none;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.leave-action-btn svg {
    width: 16px;
    height: 16px;
}

.leave-action-btn.approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.leave-action-btn.approve:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.leave-action-btn.reject {
    background: transparent;
    border: 1px solid #ef4444;
    color: #ef4444;
}

.leave-action-btn.reject:hover {
    background: rgba(239, 68, 68, 0.1);
}

.leave-action-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

.no-leave-requests {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--text-muted);
}

.no-leave-requests svg {
    width: 48px;
    height: 48px;
    opacity: 0.3;
    margin-bottom: 0.75rem;
}

.no-leave-requests p {
    margin: 0;
    font-size: 0.875rem;
}

/* Switch User Section */
.switch-user-section {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.switch-user-section .section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.section-icon.admin {
    background: transparent;
    border: 1px solid #7c3aed;
    color: #7c3aed;
}

.admin-badge {
    margin-left: auto;
    padding: 0.25rem 0.625rem;
    background: transparent;
    color: #7c3aed;
    border: 1px solid #7c3aed;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 100px;
}

.switch-user-desc {
    padding: 0.75rem 1rem 0;
    margin: 0;
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.team-members-list {
    padding: 0.75rem 1rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.team-member-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--surface-bg);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.team-member-item:hover {
    background: var(--hover-bg);
    transform: translateX(4px);
}

.team-member-avatar {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: transparent;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-primary);
    flex-shrink: 0;
}

.team-member-info {
    flex: 1;
    min-width: 0;
}

.team-member-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.team-member-role {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
}

.team-member-arrow {
    color: var(--text-muted);
    flex-shrink: 0;
}

.team-member-arrow svg {
    width: 16px;
    height: 16px;
}

/* Profile Header */
.profile-header {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 1.5rem;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: transparent;
    border: 2px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.profile-info h1 {
    font-size: 1.375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.profile-info .designation {
    font-size: 0.9375rem;
    color: var(--accent);
    margin: 0 0 0.375rem 0;
    font-weight: 500;
}

.profile-info .email {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin: 0;
}

/* Profile Sections */
.profile-sections {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.profile-section {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.section-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-icon svg {
    width: 18px;
    height: 18px;
}

.section-icon.personal {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.section-icon.work {
    background: rgba(245, 158, 11, 0.15);
    color: var(--accent);
}

.section-icon.bank {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.section-icon.emergency {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.section-content {
    padding: 0.5rem 0;
}

.profile-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
}

.profile-row:not(:last-child) {
    border-bottom: 1px solid var(--border-color);
}

.profile-label {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.profile-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    text-align: right;
    max-width: 60%;
    word-break: break-word;
}

.profile-value.masked {
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 1px;
}

.profile-value .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    border-radius: 100px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
}

.profile-value .badge.active {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.profile-value .badge.manager {
    background: rgba(245, 158, 11, 0.15);
    color: var(--accent);
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-top: 1rem;
}

.action-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.action-card:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
}

.action-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--surface-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.action-icon svg {
    width: 22px;
    height: 22px;
    color: var(--accent);
}

.action-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.action-desc {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
}

/* Loading State */
.loading-state {
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

/* Company Info Section */
.company-info {
    background: var(--surface-bg);
    border-radius: 12px;
    padding: 1.25rem;
    margin-top: 1.5rem;
    text-align: center;
}

.company-logo {
    max-width: 150px;
    height: auto;
    margin-bottom: 0.75rem;
}

.company-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.company-address {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 380px) {
    .quick-actions {
        grid-template-columns: 1fr;
    }

    .profile-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.375rem;
    }

    .profile-value {
        text-align: left;
        max-width: 100%;
    }
}
</style>

<script>
const isHRAdmin = <?php echo $isHRAdmin ? 'true' : 'false'; ?>;
let currentProfile = null; // Store profile data for editing

document.addEventListener('DOMContentLoaded', function() {
    loadProfile();
    if (isHRAdmin) {
        loadLeaveRequests();
        loadTeamMembers();
    }

    // Close modal on overlay click
    document.getElementById('editProfileModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
});

// Modal functions
function openEditModal() {
    if (!currentProfile) {
        showToast('Profile not loaded yet', 'error');
        return;
    }

    // Populate form with current values - Personal
    document.getElementById('editPhone').value = currentProfile.phone || '';
    document.getElementById('editDOB').value = currentProfile.dateOfBirth || '';
    document.getElementById('editAddress').value = currentProfile.address || '';

    // Bank Details
    document.getElementById('editIFSC').value = currentProfile.ifscCode || '';
    document.getElementById('editBankName').value = currentProfile.bankName || '';
    document.getElementById('editBankBranch').value = currentProfile.bankBranch || '';
    document.getElementById('editAccountNumber').value = currentProfile.accountNumber || '';
    document.getElementById('editPAN').value = currentProfile.panNumber || '';

    // Clear IFSC status
    document.getElementById('ifscStatus').innerHTML = '';

    // Emergency Contact
    document.getElementById('editEmergencyName').value = currentProfile.emergencyContactName || '';
    document.getElementById('editEmergencyRelation').value = currentProfile.emergencyContactRelation || '';
    document.getElementById('editEmergencyPhone').value = currentProfile.emergencyContactPhone || '';

    document.getElementById('editProfileModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editProfileModal').classList.remove('active');
    document.body.style.overflow = '';
}

function saveProfile(event) {
    event.preventDefault();

    const btn = document.getElementById('saveProfileBtn');
    const originalText = btn.textContent;

    // Show loading
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const formData = new FormData(document.getElementById('editProfileForm'));
    formData.append('xAction', 'updateProfile');

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.err === 0) {
            showToast('Profile updated successfully', 'success');
            closeEditModal();
            // Reload profile to show updated data
            loadProfile();
        } else {
            showToast(data.msg || 'Failed to update profile', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to update profile', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

function showToast(message, type = 'success') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            ${type === 'success'
                ? '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'}
        </svg>
        ${escapeHtml(message)}
    `;
    document.body.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'toastIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ============ IFSC LOOKUP USING RAZORPAY API ============
function lookupIFSC() {
    const ifscInput = document.getElementById('editIFSC');
    const statusDiv = document.getElementById('ifscStatus');
    const btn = document.getElementById('ifscLookupBtn');
    const ifsc = ifscInput.value.trim().toUpperCase();

    // Basic validation
    if (!ifsc) {
        statusDiv.innerHTML = '<span class="error">Please enter an IFSC code</span>';
        return;
    }

    // IFSC format validation: 4 letters + 0 + 6 alphanumeric
    const ifscPattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;
    if (!ifscPattern.test(ifsc)) {
        statusDiv.innerHTML = '<span class="error">Invalid IFSC format. Expected: 4 letters + 0 + 6 alphanumeric (e.g., HDFC0001234)</span>';
        return;
    }

    // Show loading
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="animation: spin 1s linear infinite;">
            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
            <path d="M12 2a10 10 0 0110 10" stroke-linecap="round"/>
        </svg>
        Looking up...
    `;
    statusDiv.innerHTML = '<span class="info">Fetching bank details...</span>';

    // Call Razorpay IFSC API
    fetch('https://ifsc.razorpay.com/' + ifsc)
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('IFSC code not found');
                }
                throw new Error('API error');
            }
            return response.json();
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Lookup
            `;

            if (data && data.BANK) {
                // Populate bank details
                document.getElementById('editBankName').value = data.BANK;
                document.getElementById('editBankBranch').value = data.BRANCH || '';

                // Show success message with details
                let html = '<span class="success"><strong>&#10003; ' + escapeHtml(data.BANK) + '</strong></span>';
                html += '<br><span class="muted">';
                html += escapeHtml(data.BRANCH) + ', ' + escapeHtml(data.CITY) + ', ' + escapeHtml(data.STATE);
                html += '</span>';

                // Payment modes supported
                const modes = [];
                if (data.NEFT) modes.push('NEFT');
                if (data.RTGS) modes.push('RTGS');
                if (data.IMPS) modes.push('IMPS');
                if (data.UPI) modes.push('UPI');
                if (modes.length > 0) {
                    html += '<br><span class="info" style="font-size:11px;">Supports: ' + modes.join(', ') + '</span>';
                }

                statusDiv.innerHTML = html;
            } else {
                statusDiv.innerHTML = '<span class="error">Invalid response from API</span>';
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Lookup
            `;

            if (error.message === 'IFSC code not found') {
                statusDiv.innerHTML = '<span class="error">IFSC code not found. Please verify the code.</span>';
            } else {
                statusDiv.innerHTML = '<span class="error">Error looking up IFSC. Please try again.</span>';
            }
        });
}

// Auto-lookup on paste or after typing 11 characters
document.addEventListener('DOMContentLoaded', function() {
    const ifscInput = document.getElementById('editIFSC');
    if (ifscInput) {
        ifscInput.addEventListener('input', function() {
            const ifsc = this.value.trim().toUpperCase();
            if (ifsc.length === 11) {
                // Auto-trigger lookup after a short delay
                clearTimeout(window.ifscLookupTimer);
                window.ifscLookupTimer = setTimeout(function() {
                    lookupIFSC();
                }, 500);
            } else {
                document.getElementById('ifscStatus').innerHTML = '';
            }
        });
    }
});

function loadProfile() {
    fetch('<?php echo SITEURL; ?>/hrms/home/?ajax=getEmployeeProfile')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderProfile(data.profile);
            } else {
                showError(data.message || 'Failed to load profile');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load profile');
        });
}

function renderProfile(profile) {
    // Store profile data for editing
    currentProfile = profile;

    // Update header
    document.getElementById('profileDesignation').textContent = profile.designation || 'Employee';

    const container = document.getElementById('profileContainer');
    container.innerHTML = `
        <!-- Personal Information -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-icon personal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h3 class="section-title">Personal Information</h3>
            </div>
            <div class="section-content">
                <div class="profile-row">
                    <span class="profile-label">Full Name</span>
                    <span class="profile-value">${escapeHtml(profile.userName)}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Email</span>
                    <span class="profile-value">${escapeHtml(profile.email)}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Phone</span>
                    <span class="profile-value">${escapeHtml(profile.phone || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Date of Birth</span>
                    <span class="profile-value">${profile.dateOfBirth ? formatDate(profile.dateOfBirth) : '-'}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Gender</span>
                    <span class="profile-value">${escapeHtml(profile.gender || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Address</span>
                    <span class="profile-value">${escapeHtml(profile.address || '-')}</span>
                </div>
            </div>
        </div>

        <!-- Work Information -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-icon work">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                    </svg>
                </div>
                <h3 class="section-title">Work Information</h3>
            </div>
            <div class="section-content">
                <div class="profile-row">
                    <span class="profile-label">Employee ID</span>
                    <span class="profile-value masked">${escapeHtml(profile.employeeCode || profile.userID)}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Designation</span>
                    <span class="profile-value">${escapeHtml(profile.designation || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Department</span>
                    <span class="profile-value">${escapeHtml(profile.department || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Joining Date</span>
                    <span class="profile-value">${profile.joiningDate ? formatDate(profile.joiningDate) : '-'}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Reporting To</span>
                    <span class="profile-value">${escapeHtml(profile.managerName || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Status</span>
                    <span class="profile-value">
                        <span class="badge active">Active</span>
                        ${profile.isManager ? '<span class="badge manager">Manager</span>' : ''}
                    </span>
                </div>
            </div>
        </div>

        <!-- Bank Details -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-icon bank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                <h3 class="section-title">Bank Details</h3>
            </div>
            <div class="section-content">
                <div class="profile-row">
                    <span class="profile-label">Bank Name</span>
                    <span class="profile-value">${escapeHtml(profile.bankName || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Branch</span>
                    <span class="profile-value">${escapeHtml(profile.bankBranch || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Account Number</span>
                    <span class="profile-value masked">${profile.accountNumber ? maskAccountNumber(profile.accountNumber) : '-'}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">IFSC Code</span>
                    <span class="profile-value masked">${escapeHtml(profile.ifscCode || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">PAN</span>
                    <span class="profile-value masked">${profile.panNumber ? maskPAN(profile.panNumber) : '-'}</span>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="profile-section">
            <div class="section-header">
                <div class="section-icon emergency">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                    </svg>
                </div>
                <h3 class="section-title">Emergency Contact</h3>
            </div>
            <div class="section-content">
                <div class="profile-row">
                    <span class="profile-label">Contact Name</span>
                    <span class="profile-value">${escapeHtml(profile.emergencyContactName || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Relationship</span>
                    <span class="profile-value">${escapeHtml(profile.emergencyContactRelation || '-')}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Phone</span>
                    <span class="profile-value">${escapeHtml(profile.emergencyContactPhone || '-')}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="<?php echo SITEURL; ?>/hrms/documents/" class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14,2 14,8 20,8"/>
                    </svg>
                </div>
                <h4 class="action-title">Documents</h4>
                <p class="action-desc">View uploaded docs</p>
            </a>
            <a href="<?php echo SITEURL; ?>/hrms/salary/" class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path d="M4 3.06h2.726c1.22 0 2.12.575 2.325 1.724H4v1.051h5.051C8.855 7.001 8 7.558 6.788 7.558H4v1.317L8.437 14h2.11L6.095 8.884h.855c2.316-.018 3.465-1.476 3.688-3.049H12V4.784h-1.345c-.08-.778-.357-1.335-.793-1.732H12V2H4z"/>
                    </svg>
                </div>
                <h4 class="action-title">Salary Slips</h4>
                <p class="action-desc">View & download</p>
            </a>
            <a href="<?php echo SITEURL; ?>/hrms/attendance/" class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h4 class="action-title">Attendance</h4>
                <p class="action-desc">View history</p>
            </a>
            <a href="javascript:hrmsLogout()" class="action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </div>
                <h4 class="action-title">Logout</h4>
                <p class="action-desc">Sign out</p>
            </a>
        </div>

        <!-- Company Info -->
        <div class="company-info">
            <img src="${SITEURL}/xsite/images/logo.png" alt="Company Logo" class="company-logo" onerror="this.style.display='none'">
            <h4 class="company-name">Bombay Engineering Syndicate</h4>
            <p class="company-address">
                Authorized dealers of Crompton, CG & Kirloskar<br>
                Mumbai, Maharashtra, India
            </p>
        </div>
    `;
}

function maskAccountNumber(accountNum) {
    if (!accountNum || accountNum.length < 4) return accountNum;
    return '****' + accountNum.slice(-4);
}

function maskPAN(pan) {
    if (!pan || pan.length < 4) return pan;
    return pan.slice(0, 2) + '****' + pan.slice(-2);
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
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    const container = document.getElementById('profileContainer');
    container.innerHTML = `
        <div class="loading-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.5;margin-bottom:1rem;">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4M12 16h.01"/>
            </svg>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

// Load pending leave requests for HR Admin
function loadLeaveRequests() {
    const container = document.getElementById('leaveRequestsList');
    const badge = document.getElementById('pendingCount');
    if (!container) return;

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php?ajax=getPendingLeaves')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.leaves && data.leaves.length > 0) {
                renderLeaveRequests(data.leaves);
                badge.textContent = data.leaves.length;
                badge.classList.remove('zero');
            } else {
                container.innerHTML = `
                    <div class="no-leave-requests">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p>No pending leave requests</p>
                    </div>
                `;
                badge.textContent = '0';
                badge.classList.add('zero');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<p style="padding:1rem;color:var(--text-muted);font-size:0.8125rem;">Failed to load leave requests.</p>';
        });
}

function renderLeaveRequests(leaves) {
    const container = document.getElementById('leaveRequestsList');

    container.innerHTML = leaves.map(leave => `
        <div class="leave-request-item" id="leave-${leave.leaveID}">
            <div class="leave-request-header">
                <div class="leave-request-avatar">${getInitials(leave.employeeName)}</div>
                <div class="leave-request-info">
                    <p class="leave-request-name">${escapeHtml(leave.employeeName)}</p>
                    <p class="leave-request-type">${escapeHtml(leave.leaveTypeName || 'Leave')}</p>
                </div>
            </div>
            <div class="leave-request-details">
                <div class="leave-detail">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                    </svg>
                    <strong>${formatLeaveDate(leave.fromDate)}</strong>
                    ${leave.fromDate !== leave.toDate ? ` - <strong>${formatLeaveDate(leave.toDate)}</strong>` : ''}
                </div>
                <div class="leave-detail">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Applied ${formatLeaveDate(leave.dateAdded)}
                </div>
            </div>
            <div class="leave-request-reason">
                <p>${escapeHtml(leave.reason)}</p>
            </div>
            <div class="leave-request-actions">
                <button class="leave-action-btn approve" onclick="handleLeaveAction(${leave.leaveID}, 'Approved', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Approve
                </button>
                <button class="leave-action-btn reject" onclick="handleLeaveAction(${leave.leaveID}, 'Disapproved', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Reject
                </button>
            </div>
        </div>
    `).join('');
}

function formatLeaveDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short'
    });
}

function handleLeaveAction(leaveID, status, btn) {
    const item = document.getElementById(`leave-${leaveID}`);
    const buttons = item.querySelectorAll('.leave-action-btn');

    // Disable buttons during processing
    buttons.forEach(b => b.disabled = true);
    btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;margin:0;border-width:2px;"></div>';

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `xAction=updateLeaveStatus&leaveID=${leaveID}&leaveStatus=${encodeURIComponent(status)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.err === 0) {
            // Animate removal
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            item.style.transition = 'all 0.3s ease';
            setTimeout(() => {
                item.remove();
                // Update badge count
                const badge = document.getElementById('pendingCount');
                const remaining = document.querySelectorAll('.leave-request-item').length;
                badge.textContent = remaining;
                if (remaining === 0) {
                    badge.classList.add('zero');
                    document.getElementById('leaveRequestsList').innerHTML = `
                        <div class="no-leave-requests">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>No pending leave requests</p>
                        </div>
                    `;
                }
            }, 300);
        } else {
            alert(data.msg || 'Failed to update leave status');
            buttons.forEach(b => b.disabled = false);
            loadLeaveRequests(); // Reload
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update leave status');
        buttons.forEach(b => b.disabled = false);
    });
}

// Load team members for HR Admin switch user feature
function loadTeamMembers() {
    const container = document.getElementById('teamMembersList');
    if (!container) return;

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php?ajax=getAllEmployees')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.employees && data.employees.length > 0) {
                renderTeamMembers(data.employees);
            } else {
                container.innerHTML = '<p style="padding:1rem;color:var(--text-muted);font-size:0.8125rem;">No employees found.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<p style="padding:1rem;color:var(--text-muted);font-size:0.8125rem;">Failed to load employees.</p>';
        });
}

function renderTeamMembers(employees) {
    const container = document.getElementById('teamMembersList');
    const currentUserID = <?php echo $employeeID; ?>;

    // Filter out current user
    const otherEmployees = employees.filter(emp => emp.userID !== currentUserID);

    if (otherEmployees.length === 0) {
        container.innerHTML = '<p style="padding:0.5rem 0;color:var(--text-muted);font-size:0.8125rem;">No other employees to switch to.</p>';
        return;
    }

    container.innerHTML = otherEmployees.map(emp => `
        <div class="team-member-item" onclick="switchToUser(${emp.userID}, '${escapeHtml(emp.userName)}')">
            <div class="team-member-avatar">${getInitials(emp.userName)}</div>
            <div class="team-member-info">
                <p class="team-member-name">${escapeHtml(emp.userName)}</p>
                <p class="team-member-role">${escapeHtml(emp.designation || 'Employee')}</p>
            </div>
            <div class="team-member-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </div>
        </div>
    `).join('');
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

function switchToUser(userID, userName) {
    if (!confirm(`Switch to view portal as "${userName}"?`)) return;

    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `xAction=switchUser&targetUserID=${userID}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.err === 0) {
            // Redirect to home page as the switched user
            window.location.href = '<?php echo SITEURL; ?>/hrms/home/';
        } else {
            alert(data.msg || 'Failed to switch user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to switch user');
    });
}

function switchBackToAdmin() {
    fetch('<?php echo SITEURL; ?>/xsite/mod/hrms/x-hrms.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'xAction=switchBack'
    })
    .then(response => response.json())
    .then(data => {
        if (data.err === 0) {
            window.location.href = '<?php echo SITEURL; ?>/hrms/profile/';
        } else {
            alert(data.msg || 'Failed to switch back');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to switch back');
    });
}
</script>
