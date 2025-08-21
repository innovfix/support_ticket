// Global variables for the ticketing system
let tickets = [];
let ticketCounter = 1;
let currentFilter = 'all';

// Simple online status tracking
let onlineStaff = new Set();

// --- Backend API helpers (MySQL) ---
// Detect environment and set API base so the app can run from Live Server (127.0.0.1:5500)
// or via Apache (http://localhost/hima-support/)
const API_BASE = (() => {
    const origin = window.location.origin;
    const pathname = window.location.pathname;
    
    // When running from VS Code Live Server or similar
    if (origin.includes('127.0.0.1:5500') || origin.includes('localhost:5500')) {
        // Check if we're in the hosting-files directory
        if (pathname.includes('query-desk/hosting-files')) {
            return 'http://localhost/query-desk/hosting-files/api/';
        } else {
            return 'http://localhost/hima-support/api/';
        }
    }
    
    // Default: served by Apache under the same site; use relative api/
    return 'api/';
})();

async function apiFetchJson(url, options = {}) {
    try {
        // Normalize URL: if it's not absolute, build from API_BASE and strip any leading "api/"
        const isAbsolute = /^https?:\/\//i.test(url);
        const normalizedPath = isAbsolute ? url : (API_BASE + url.replace(/^\/?api\/?/, ''));
        const response = await fetch(normalizedPath, options);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        return data;
    } catch (err) {
        console.warn('API request failed, will fallback to localStorage if possible:', url, err);
        throw err;
    }
}

async function createTicketViaApi(formData) {
    // formData is either FormData (multipart) or a plain object (convert to JSON)
    const isFormData = typeof FormData !== 'undefined' && formData instanceof FormData;
    if (isFormData) {
        const res = await apiFetchJson('api/tickets-create.php', {
            method: 'POST',
            body: formData
        });
        if (!res.ok) throw new Error('Create failed');
        return res.ticket;
    }
    const res = await apiFetchJson('api/tickets-create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    });
    if (!res.ok) throw new Error('Create failed');
    return res.ticket;
}

async function fetchTicketsFromApi(status = null) {
    const q = status && status !== 'all' ? `?status=${encodeURIComponent(status)}` : '';
    const res = await apiFetchJson(`api/tickets-list.php${q}`);
    if (!res.ok) throw new Error('List failed');
    return res.tickets || [];
}

async function updateTicketStatusViaApi(ticketCode, newStatus, description = '', changedBy = 'Manager') {
    const assignedSelect = document.getElementById('assignToStaff');
    const assignedNameInput = document.getElementById('assignToStaffName');
    const assignedTo = assignedSelect ? assignedSelect.value : '';
    const assignedToName = assignedNameInput ? assignedNameInput.value : '';
    const res = await apiFetchJson('api/tickets-update-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ticketCode, status: newStatus, description, changedBy, assignedTo, assignedToName })
    });
    if (!res.ok) throw new Error('Update failed');
    return true;
}

// --- Issue Types API helpers ---
async function fetchIssueTypes() {
    const res = await apiFetchJson('api/issue-types-list.php');
    if (!res.ok) throw new Error('Issue types load failed');
    return res.types || [];
}

async function addIssueType(name) {
    const res = await apiFetchJson('api/issue-types-add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name })
    });
    if (!res.ok) throw new Error('Add type failed');
    return res.types || [];
}

async function updateIssueType(id, fields) {
    const res = await apiFetchJson('api/issue-types-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, ...fields })
    });
    if (!res.ok) throw new Error('Update type failed');
    return res.types || [];
}

async function deleteIssueType(id) {
    const res = await apiFetchJson('api/issue-types-delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if (!res.ok) throw new Error('Delete type failed');
    return res.types || [];
}

// Function to update staff online status
function updateStaffStatus(staffId, isOnline) {
    if (isOnline) {
        onlineStaff.add(staffId);
        localStorage.setItem('onlineStaff', JSON.stringify(Array.from(onlineStaff)));
    } else {
        onlineStaff.delete(staffId);
        localStorage.setItem('onlineStaff', JSON.stringify(Array.from(onlineStaff)));
    }
    
    // Update display if on manager dashboard
    if (window.location.href.includes('dashboard.html')) {
        updateManagerOnlineStatus();
    }
}

// Function to check if staff is online
function isStaffOnline(staffId) {
    return onlineStaff.has(staffId);
}

// Function to get current staff ID
function getCurrentStaffId() {
    return localStorage.getItem('currentStaffId') || sessionStorage.getItem('currentStaffId');
}

// Function to update manager dashboard online status display
function updateManagerOnlineStatus() {
    const statusContainer = document.getElementById('staffOnlineStatus');
    if (!statusContainer) return;
    
    const currentStaffId = getCurrentStaffId();
    
    // Check if there's actually a logged-in staff member
    if (currentStaffId && isStaffOnline(currentStaffId)) {
        // Verify the staff is still actively logged in
        const isActive = localStorage.getItem('currentStaffId') === currentStaffId || 
                        sessionStorage.getItem('currentStaffId') === currentStaffId;
        
        if (isActive) {
            // Staff is online - show green badge
            statusContainer.innerHTML = `
                <div class="online-status-badge online">
                    <i class="fas fa-circle"></i>
                    <span>Staff is online</span>
                </div>
            `;
            statusContainer.style.display = 'block';
        } else {
            // Staff is not actively logged in, show offline status
            statusContainer.innerHTML = `
                <div class="online-status-badge offline">
                    <i class="fas fa-circle"></i>
                    <span>Staff is offline</span>
                </div>
            `;
            statusContainer.style.display = 'block';
            // Also remove from online staff list
            updateStaffStatus(currentStaffId, false);
        }
    } else {
        // Staff is offline - show offline badge
        statusContainer.innerHTML = `
            <div class="online-status-badge offline">
                <i class="fas fa-circle"></i>
                <span>Staff is offline</span>
            </div>
        `;
        statusContainer.style.display = 'block';
    }
}

// Initialize online staff from localStorage
function initializeOnlineStatus() {
    const storedOnlineStaff = localStorage.getItem('onlineStaff');
    if (storedOnlineStaff) {
        const staffArray = JSON.parse(storedOnlineStaff);
        staffArray.forEach(staffId => onlineStaff.add(staffId));
    }
    
    // Clear stale online statuses (staff who are not actively logged in)
    clearStaleOnlineStatuses();
}

// Function to clear stale online statuses
function clearStaleOnlineStatuses() {
    const currentStaffId = localStorage.getItem('currentStaffId') || sessionStorage.getItem('currentStaffId');
    
    // If no one is currently logged in, clear all online statuses
    if (!currentStaffId) {
        onlineStaff.clear();
        localStorage.removeItem('onlineStaff');
        console.log('No active staff, cleared all online statuses');
        return;
    }
    
    // Check each online staff member
    const onlineStaffArray = Array.from(onlineStaff);
    onlineStaffArray.forEach(staffId => {
        // If this staff is not the current logged-in user, remove them
        if (staffId !== currentStaffId) {
            onlineStaff.delete(staffId);
            console.log('Removed stale online status for:', staffId);
        }
    });
    
    // Update localStorage
    localStorage.setItem('onlineStaff', JSON.stringify(Array.from(onlineStaff)));
}

// Create Ticket functionality
function openCreateTicket() {
    showInfoMessage('Redirecting to staff dashboard...');
    setTimeout(() => {
        // Redirect to staff dashboard where ticket creation is available
        window.location.href = 'staff-dashboard.html';
    }, 1000);
}

// Staff Dashboard Create Ticket Form
function openCreateTicketForm() {
    console.log('Opening create ticket form...');
    const modal = document.getElementById('createTicketModal');
    if (modal) {
        console.log('Modal found, displaying...');
        modal.style.display = 'block';
        // Ensure issue types are loaded when opening the form
        if (typeof loadIssueTypesIntoStaff === 'function') {
            loadIssueTypesIntoStaff();
        }
        // Reset form when opening
        const form = document.getElementById('createTicketForm');
        if (form) {
            form.reset();
            // Clear screenshot name display
            const screenshotName = document.getElementById('screenshotName');
            if (screenshotName) {
                screenshotName.style.display = 'none';
            }
        }
    } else {
        console.error('Modal not found!');
        showErrorMessage('Ticket creation form not found');
    }
}

function closeCreateTicketForm() {
    console.log('Closing create ticket form...');
    const modal = document.getElementById('createTicketModal');
    if (modal) {
        console.log('Modal found, hiding...');
        modal.style.display = 'none';
        // Reset form
        const form = document.getElementById('createTicketForm');
        if (form) {
            form.reset();
            console.log('Form reset');
        }
        // Clear screenshot name display
        const screenshotName = document.getElementById('screenshotName');
        if (screenshotName) {
            screenshotName.style.display = 'none';
            console.log('Screenshot name cleared');
        }
    } else {
        console.error('Modal not found when trying to close!');
    }
}

function submitTicket(event) {
    event.preventDefault();
    console.log('Submitting ticket...');
    
    const formElem = event.target;
    const formData = new FormData(formElem);
    
    // Try API first (multipart handles screenshot upload)
    // Ensure default assignee name
    if (!formData.get('assignedToName')) {
        formData.append('assignedToName', 'Ops Head');
    }
    createTicketViaApi(formData).then((serverTicket) => {
        // Sync local cache for UI continuity
        const mapped = {
            id: serverTicket.ticketCode || serverTicket.id,
            mobileOrUserId: serverTicket.mobileOrUserId,
            issueType: serverTicket.issueType,
            issueDescription: serverTicket.issueDescription,
            status: serverTicket.status || 'new',
            createdAt: serverTicket.createdAt || new Date().toISOString(),
            createdBy: serverTicket.createdBy || 'Staff',
            assignedTo: serverTicket.assignedToName || serverTicket.assignedTo || 'Ops Head',
            screenshot: serverTicket.screenshot || null,
        };
        tickets.push(mapped);
        localStorage.setItem('tickets', JSON.stringify(tickets));

        closeCreateTicketForm();
        showSuccessMessage('Ticket created and saved to database!');
        updateStaffDashboardCounts();
        if (window.location.pathname.includes('dashboard.html')) {
            displayManagerTickets();
            updateManagerDashboardCounts();
        }
    }).catch((e) => {
        console.error('Create ticket API failed:', e);
        // Fallback to localStorage (offline/no backend)
        const fallbackTicket = {
            id: 'TKT-' + String(ticketCounter).padStart(4, '0'),
            mobileOrUserId: formData.get('mobileOrUserId'),
            issueType: formData.get('issueType'),
            issueDescription: formData.get('issueDescription'),
            status: 'new',
            createdAt: new Date().toISOString(),
            createdBy: 'Staff',
            assignedTo: 'Unassigned'
        };
        const screenshotFile = formData.get('screenshot');
        if (screenshotFile && screenshotFile.size > 0) {
            fallbackTicket.screenshot = URL.createObjectURL(screenshotFile);
        }

        if (!tickets) tickets = [];
        if (!ticketCounter) ticketCounter = 1;
        tickets.push(fallbackTicket);
        ticketCounter++;
        localStorage.setItem('tickets', JSON.stringify(tickets));
        localStorage.setItem('ticketCounter', ticketCounter.toString());

        closeCreateTicketForm();
        showInfoMessage('Backend not reachable. Ticket saved locally.');
        updateStaffDashboardCounts();
        if (window.location.pathname.includes('dashboard.html')) {
            displayManagerTickets();
            updateManagerDashboardCounts();
        }
    });
}

// Helper function to add new query to the list
function addQueryToList(query) {
    const queriesList = document.getElementById('queriesList');
    if (queriesList) {
        const queryElement = createQueryElement(query);
        queriesList.insertBefore(queryElement, queriesList.firstChild);
        
        // Update stats
        const assignedQueries = document.getElementById('assignedQueries');
        if (assignedQueries) {
            assignedQueries.textContent = parseInt(assignedQueries.textContent) + 1;
        }
    }
}

// Logout functionality
function initializeLogoutButtons() {
    const logoutButtons = document.querySelectorAll('.logout-btn');
    console.log('Found logout buttons:', logoutButtons.length);
    
    logoutButtons.forEach((button, index) => {
        console.log(`Setting up logout button ${index + 1}:`, button);
        button.addEventListener('click', function() {
            console.log('Logout button clicked!');
            // Show confirmation message
            showInfoMessage('Logging out...');
            
            // Simulate logout delay
            setTimeout(() => {
                // Clear any stored session data (if any)
                localStorage.removeItem('userSession');
                sessionStorage.clear();
                
                // Redirect to main page
                console.log('Executing redirect to index.html from initializeLogoutButtons...');
                window.location.href = 'index.html';
            }, 1000);
        });
    });
}

// Dashboard functionality
if (window.location.pathname.includes('dashboard.html')) {
    // Only initialize logout buttons - the ticket system will be initialized in DOMContentLoaded
    initializeLogoutButtons();
}

function initializeDashboard() {
    // Initialize dashboard stats
    updateStats();
    
    // Animate counters
    animateCounters();
    
    // Set up quick actions
    setupQuickActions();
}

function refreshDashboard() {
    showInfoMessage('Refreshing dashboard...');
    setTimeout(() => {
        updateStats();
        animateCounters();
        showSuccessMessage('Dashboard refreshed!');
    }, 1000);
}

function setupQuickActions() {
    console.log('Setting up quick actions...');
    const quickActionCards = document.querySelectorAll('.quick-action-card');
    
    quickActionCards.forEach(card => {
        card.addEventListener('click', function() {
            const action = this.querySelector('h4').textContent;
            handleQuickAction(action);
        });
    });
}

function updateStats(data = null) {
    // If no data provided, use default values
    if (!data) {
        data = {
            newTickets: Math.floor(Math.random() * 10) + 1,
            pendingTickets: Math.floor(Math.random() * 8) + 1,
            resolvedTickets: Math.floor(Math.random() * 20) + 5
        };
    }
    
    const elements = {
        newTickets: document.getElementById('newTickets'),
        pendingTickets: document.getElementById('pendingTickets'),
        resolvedTickets: document.getElementById('resolvedTickets')
    };

    // Animate counting up to the target numbers
    Object.keys(data).forEach(key => {
        if (elements[key]) {
            animateCounter(elements[key], 0, data[key], 1000);
        }
    });
}

function viewCompletedTickets() {
    showInfoMessage('Loading completed tickets...');
    setTimeout(() => {
        // In a real application, this would filter and show completed tickets
        showSuccessMessage('Completed tickets loaded!');
    }, 1000);
}

// Staff Dashboard functionality
if (window.location.pathname.includes('staff-dashboard.html') || window.location.href.includes('staff-dashboard.html')) {
    console.log('Initializing Staff Dashboard...');
    // Initialize staff dashboard
    initializeStaffDashboard();
    // Initialize logout buttons
    initializeLogoutButtons();
    console.log('Staff Dashboard initialized successfully');
    // Load issue types once dashboard is ready
    if (typeof loadIssueTypesIntoStaff === 'function') {
        loadIssueTypesIntoStaff();
    }
}

// Ticket details page functionality
if (window.location.pathname.includes('ticket-details.html')) {
    // Initialize logout buttons
    initializeLogoutButtons();
}

function initializeStaffDashboard() {
    // Update staff info
    updateStaffInfo();
    
    // Update the dashboard counts with real data
    updateStaffDashboardCounts();
    
    // Load assigned queries
    loadAssignedQueries();
    
    // Initialize online status
    initializeOnlineStatus();
    
    // Set current staff ID from session if logged in
    const currentStaffId = localStorage.getItem('currentStaffId') || sessionStorage.getItem('currentStaffId');
    
    // Setup online status toggle in header
    setupHeaderOnlineStatusToggle();
}

function updateStaffInfo() {
    // Set staff information
    document.getElementById('staffName').textContent = 'John Doe';
    document.getElementById('staffId').textContent = 'ID: STAFF-001';
    document.getElementById('staffRole').textContent = 'Role: Support Staff';
}

function updateStaffStats(data) {
    const elements = {
        assignedQueries: document.getElementById('assignedQueries'),
        inProgressQueries: document.getElementById('inProgressQueries'),
        completedQueries: document.getElementById('completedQueries')
    };

    // Animate counting up to the target numbers
    Object.keys(data).forEach(key => {
        if (elements[key]) {
            animateCounter(elements[key], 0, data[key], 1000);
        }
    });
}

function loadAssignedQueries() {
    const queriesList = document.getElementById('queriesList');
    
    // Example assigned queries data
    const assignedQueries = [
        {
            id: 'QRY-2024-001',
            title: 'System Login Issue',
            description: 'User unable to access the system with valid credentials',
            status: 'pending',
            priority: 'High',
            assignedDate: '2024-01-15'
        },
        {
            id: 'QRY-2024-002',
            title: 'Database Connection Error',
            description: 'Application showing database connection timeout errors',
            status: 'in-progress',
            priority: 'Medium',
            assignedDate: '2024-01-14'
        },
        {
            id: 'QRY-2024-003',
            title: 'UI Responsiveness Issue',
            description: 'Dashboard not loading properly on mobile devices',
            status: 'completed',
            priority: 'Low',
            assignedDate: '2024-01-13'
        }
    ];

    // Clear existing list
    queriesList.innerHTML = '';

    // Populate queries
    assignedQueries.forEach(query => {
        const queryElement = createQueryElement(query);
        queriesList.appendChild(queryElement);
    });
}

function createQueryElement(query) {
    const div = document.createElement('div');
    div.className = 'query-item';
    
    const statusClass = query.status === 'pending' ? 'pending' : 
                       query.status === 'in-progress' ? 'in-progress' : 'completed';
    
    div.innerHTML = `
        <div class="query-header">
            <div class="query-id">${query.id}</div>
            <span class="query-status ${statusClass}">${query.status.charAt(0).toUpperCase() + query.status.slice(1)}</span>
        </div>
        <div class="query-details">
            <h4>${query.title}</h4>
            <p>${query.description}</p>
            <p><strong>Priority:</strong> ${query.priority} | <strong>Assigned:</strong> ${query.assignedDate}</p>
        </div>
        <div class="query-actions">
            ${query.status === 'pending' ? 
                `<button class="query-action-btn primary" onclick="startQuery('${query.id}')">
                    <i class="fas fa-play"></i> Start Work
                </button>` : ''
            }
            ${query.status === 'in-progress' ? 
                `<button class="query-action-btn success" onclick="completeQuery('${query.id}')">
                    <i class="fas fa-check"></i> Mark Complete
                </button>` : ''
            }
            <button class="query-action-btn secondary" onclick="viewQueryDetails('${query.id}')">
                <i class="fas fa-eye"></i> View Details
            </button>
        </div>
    `;
    
    return div;
}

function startQuery(queryId) {
    showInfoMessage(`Starting work on ${queryId}...`);
    // Here you would typically update the query status in the backend
    setTimeout(() => {
        showSuccessMessage(`Query ${queryId} status updated to In Progress`);
        // Refresh the queries list
        loadAssignedQueries();
    }, 1000);
}

function completeQuery(queryId) {
    showInfoMessage(`Completing ${queryId}...`);
    // Here you would typically update the query status in the backend
    setTimeout(() => {
        showSuccessMessage(`Query ${queryId} marked as completed!`);
        // Refresh the queries list
        loadAssignedQueries();
    }, 1000);
}

function viewQueryDetails(queryId) {
    showInfoMessage(`Opening details for ${queryId}...`);
    // Here you would typically navigate to a detailed view
    setTimeout(() => {
        showInfoMessage('Query details feature coming soon!');
    }, 1000);
}

function refreshQueries() {
    const refreshBtn = document.querySelector('.action-btn[onclick="refreshQueries()"]');
    const originalText = refreshBtn.innerHTML;
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Simulate refresh delay
    setTimeout(() => {
        // Reload queries
        loadAssignedQueries();
        
        // Update dashboard counts
        if (typeof updateStaffDashboardCounts === 'function') {
            updateStaffDashboardCounts();
        }
        
        // Reset button
        refreshBtn.innerHTML = originalText;
        refreshBtn.disabled = false;
        
        showSuccessMessage('Queries refreshed successfully!');
    }, 1500);
}

function viewCompletedQueries() {
    showInfoMessage('Completed queries view coming soon!');
}

function animateCounter(element, start, end, duration) {
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const current = Math.floor(start + (end - start) * easeOutQuart);
        
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    requestAnimationFrame(updateCounter);
}

function addQuickActionHandlers() {
    const quickActionCards = document.querySelectorAll('.quick-action-card');
    
    quickActionCards.forEach(card => {
        card.addEventListener('click', function() {
            const action = this.querySelector('h4').textContent;
            handleQuickAction(action);
        });
    });
}

function handleQuickAction(action) {
    // Add visual feedback
    const card = event.currentTarget;
    card.style.transform = 'scale(0.95)';
    
    setTimeout(() => {
        card.style.transform = '';
        
        // Handle different actions
        switch(action) {
            case 'Create Ticket':
                showInfoMessage('Create Ticket feature coming soon!');
                break;
            case 'Search Tickets':
                showInfoMessage('Search functionality will be available soon!');
                break;
            case 'Export Report':
                showInfoMessage('Report export feature coming soon!');
                break;
        }
    }, 150);
}

function refreshStats() {
    const refreshBtn = document.querySelector('.action-btn[onclick="refreshStats()"]');
    const originalText = refreshBtn.innerHTML;
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Simulate refresh delay
    setTimeout(() => {
        // Generate new random data
        const newData = {
            newTickets: Math.floor(Math.random() * 10) + 1,
            pendingTickets: Math.floor(Math.random() * 8) + 1,
            resolvedTickets: Math.floor(Math.random() * 20) + 5
        };
        
        updateStats(newData);
        
        // Reset button
        refreshBtn.innerHTML = originalText;
        refreshBtn.disabled = false;
        
        showSuccessMessage('Stats refreshed successfully!');
    }, 2000);
}

// Utility functions for showing messages
function showSuccessMessage(message) {
    showMessage(message, 'success');
}

function showErrorMessage(message) {
    showMessage(message, 'error');
}

function showInfoMessage(message) {
    showMessage(message, 'info');
}

// Add missing showToast function
function showToast(message, type = 'info') {
    showMessage(message, type);
}

function showMessage(message, type) {
    // Remove existing message if any
    const existingMessage = document.querySelector('.message-toast');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message-toast message-${type}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#48bb78' : type === 'error' ? '#f56565' : '#4299e1'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        max-width: 300px;
    `;
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .message-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message-content i {
            font-size: 18px;
        }
    `;
    document.head.appendChild(style);
    
    // Add to page
    document.body.appendChild(messageDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        messageDiv.style.animation = 'slideInRight 0.3s ease-out reverse';
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}

// Add some interactive hover effects for stat boxes
document.addEventListener('DOMContentLoaded', function() {
    const statBoxes = document.querySelectorAll('.stat-box');
    
    statBoxes.forEach(box => {
        box.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        box.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});

// Simple logout function that can be called directly
function logoutUser() {
    console.log('Logout function called directly');
    console.log('Will redirect to: index.html');
    
    // Mark current staff as offline
    const currentStaffId = getCurrentStaffId();
    if (currentStaffId) {
        updateStaffStatus(currentStaffId, false);
        localStorage.removeItem('currentStaffId');
        sessionStorage.removeItem('currentStaffId');
        console.log('Staff marked as offline:', currentStaffId);
    }
    
    showInfoMessage('Logging out...');
    
    setTimeout(() => {
        // Clear any stored session data
        localStorage.removeItem('userSession');
        sessionStorage.clear();
        
        // Redirect to main page
        console.log('Executing redirect to index.html...');
        window.location.href = 'index.html';
    }, 1000);
}

// Initialize logout buttons when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for logout buttons...');
    
    // Initialize ticket management system
    initializeTicketSystem();
    
    // Initialize staff login form handler
    initializeStaffLoginForm();
    initializeStaffRegisterForm();
    
    // Initialize manager login form handler
    initializeManagerLoginForm();
    
    // Check if we're on staff dashboard
    if (window.location.href.includes('staff-dashboard.html')) {
        console.log('Staff dashboard detected on DOM load');
        initializeStaffDashboard();
        initializeLogoutButtons();
        updateStaffDashboardCounts();
        // Load issue types dynamically
        (async () => {
            try {
                const types = await fetchIssueTypes();
                const select = document.getElementById('issueType');
                if (select) {
                    // Clear existing
                    select.innerHTML = '<option value="">Select an issue</option>';
                    types.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t;
                        opt.textContent = t;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {
                console.warn('Failed to load issue types, keep defaults if any');
            }
        })();
        
        // Ensure modal is properly initialized
        const modal = document.getElementById('createTicketModal');
        if (modal) {
            console.log('Create ticket modal found and initialized');
            // Set initial display to none
            modal.style.display = 'none';
        } else {
            console.error('Create ticket modal not found!');
        }
    }
    
    // Check if we're on manager dashboard
    if (window.location.href.includes('dashboard.html')) {
        console.log('Manager dashboard detected on DOM load');
        initializeLogoutButtons();
        
        // Initialize manager dashboard after a short delay
        setTimeout(() => {
            console.log('Initializing manager dashboard after delay');
            initializeManagerDashboard();
        }, 500);

        // Wire up change status form submit handler
        const changeStatusForm = document.getElementById('changeStatusForm');
        if (changeStatusForm) {
            changeStatusForm.addEventListener('submit', function(e) {
                e.preventDefault();
                updateTicketStatus();
            });
        }

        // Issue types modal handlers
        window.openIssueTypesModal = function() {
            const modal = document.getElementById('issueTypesModal');
            if (!modal) return;
            modal.style.display = 'block';
            loadIssueTypesIntoManager();
        };
        window.closeIssueTypesModal = function() {
            const modal = document.getElementById('issueTypesModal');
            if (!modal) return;
            modal.style.display = 'none';
        };
        async function loadIssueTypesIntoManager() {
            try {
                const list = document.getElementById('issueTypesList');
                if (!list) return;
                list.innerHTML = '<div style="color:#999">Loading...</div>';
                const types = await fetchIssueTypes();
                list.innerHTML = types.map(t => `
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:8px;">
                        <input type="text" value="${t.name}" data-id="${t.id}" style="flex:1; background:transparent; border:none; color:#fff; outline:none; padding:6px 8px; border-bottom:1px dashed rgba(255,255,255,0.2);">
                        <div style="display:flex; gap:8px;">
                            <button class="action-btn" data-action="save" data-id="${t.id}" style="padding:6px 10px; background:#10b981;">Save</button>
                            <button class="action-btn" data-action="delete" data-id="${t.id}" style="padding:6px 10px; background:#ef4444;">Delete</button>
                        </div>
                    </div>
                `).join('');

                // Attach handlers
                list.querySelectorAll('button[data-action="save"]').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const id = parseInt(this.getAttribute('data-id'));
                        const input = list.querySelector(`input[data-id="${id}"]`);
                        const name = (input?.value || '').trim();
                        if (!name) { showErrorMessage('Name cannot be empty'); return; }
                        try {
                            await updateIssueType(id, { name });
                            showSuccessMessage('Updated');
                            await loadIssueTypesIntoManager();
                        } catch (e) {
                            showErrorMessage('Update failed');
                        }
                    });
                });
                list.querySelectorAll('button[data-action="delete"]').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const id = parseInt(this.getAttribute('data-id'));
                        if (!confirm('Delete this issue type?')) return;
                        try {
                            await deleteIssueType(id);
                            showSuccessMessage('Deleted');
                            await loadIssueTypesIntoManager();
                        } catch (e) {
                            showErrorMessage('Delete failed');
                        }
                    });
                });
            } catch (e) {
                showErrorMessage('Failed to load issue types');
            }
        }
        const addIssueTypeForm = document.getElementById('addIssueTypeForm');
        if (addIssueTypeForm) {
            addIssueTypeForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const input = document.getElementById('newIssueTypeName');
                const name = (input?.value || '').trim();
                if (!name) { showErrorMessage('Enter an issue type'); return; }
                try {
                    await addIssueType(name);
                    input.value = '';
                    showSuccessMessage('Issue type added');
                    // Reload both manager list and staff select (if staff tab open elsewhere, they can refresh)
                    const isManager = window.location.href.includes('dashboard.html');
                    if (isManager) {
                        // refresh within modal
                        const list = document.getElementById('issueTypesList');
                        if (list) {
                            list.innerHTML = '<div style="color:#999">Updating...</div>';
                        }
                        await loadIssueTypesIntoManager();
                    }
                } catch (err) {
                    showErrorMessage('Failed to add type');
                }
            });
        }

        // Auto-open modal via URL flag
        try {
            const params = new URLSearchParams(window.location.search);
            if (window.location.hash === '#issue-types' || params.get('open') === 'issue-types') {
                setTimeout(() => window.openIssueTypesModal && window.openIssueTypesModal(), 300);
            }
        } catch (e) {}
    }
    
    // Check if we're on ticket details page
    if (window.location.href.includes('ticket-details.html')) {
        console.log('Ticket details page detected on DOM load');
        initializeLogoutButtons();
    }
    
    // Initialize modal functionality
    initializeModalHandlers();
});

// Staff: ensure issue types populate reliably
async function loadIssueTypesIntoStaff() {
    try {
        const rows = await fetchIssueTypes();
        const select = document.getElementById('issueType');
        if (!select) return;
        const current = select.value;
        select.innerHTML = '<option value="">Select an issue</option>';
        rows.forEach(obj => {
            const t = obj.name || obj;
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            select.appendChild(opt);
        });
        // Try restore previous selection if still present
        if (current && rows.some(obj => (obj.name || obj) === current)) {
            select.value = current;
        }
    } catch (e) {
        console.warn('Failed to load issue types for staff');
    }
}

// Initialize ticket management system
function initializeTicketSystem() {
    tickets = JSON.parse(localStorage.getItem('tickets')) || [];
    ticketCounter = parseInt(localStorage.getItem('ticketCounter')) || 1;
}

// Initialize modal handlers
function initializeModalHandlers() {
    console.log('Initializing modal handlers...');
    
    // Check if modal exists
    const modal = document.getElementById('createTicketModal');
    if (modal) {
        console.log('Modal found during initialization');
        modal.style.display = 'none';
    } else {
        console.error('Modal not found during initialization!');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('createTicketModal');
        if (event.target === modal) {
            console.log('Clicked outside modal, closing...');
            closeCreateTicketForm();
        }
    };
    
    // Add escape key handler
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('createTicketModal');
            if (modal && modal.style.display === 'block') {
                console.log('Escape key pressed, closing modal...');
                closeCreateTicketForm();
            }
        }
    });
}

// Staff Dashboard Functions - These are now defined above

async function updateStaffDashboardCounts() {
    if (window.location.pathname.includes('staff-dashboard.html')) {
        try {
            // Fetch tickets from API first
            const apiTickets = await fetchTicketsFromApi('all');
            
            // Get today's date
            const today = new Date();
            const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            
            // Count tickets created today by staff
            const todayCount = apiTickets.filter(t => {
                const ticketDate = new Date(t.createdAt);
                return t.createdBy === 'Staff' && ticketDate >= todayStart;
            }).length;
            
            // Count other stats
            const assignedCount = apiTickets.filter(t => t.status === 'new' || t.status === 'in-progress').length;
            const inProgressCount = apiTickets.filter(t => t.status === 'in-progress').length;
            const completedCount = apiTickets.filter(t => t.status === 'resolved' || t.status === 'closed').length;
            
            // Update all dashboard counts
            const todayElement = document.getElementById('todayTotalQueries');
            const assignedElement = document.getElementById('assignedQueries');
            const inProgressElement = document.getElementById('inProgressQueries');
            const completedElement = document.getElementById('completedQueries');
            
            if (todayElement) todayElement.textContent = todayCount;
            if (assignedElement) assignedElement.textContent = assignedCount;
            if (inProgressElement) inProgressElement.textContent = inProgressCount;
            if (completedElement) completedElement.textContent = completedCount;
        } catch (error) {
            console.error('Error updating staff dashboard counts:', error);
            // Set counts to 0 if API fails
            const elements = ['todayTotalQueries', 'assignedQueries', 'inProgressQueries', 'completedQueries'];
            elements.forEach(id => {
                const element = document.getElementById(id);
                if (element) element.textContent = '0';
            });
        }
    }
}

// Manager Dashboard Functions
function displayTickets() {
    const ticketsList = document.getElementById('ticketsList');
    if (!ticketsList) return;
    
    if (tickets.length === 0) {
        ticketsList.innerHTML = `
            <div class="no-tickets">
                <i class="fas fa-inbox fa-3x" style="color: rgba(255,255,255,0.3); margin-bottom: 20px;"></i>
                <p style="color: rgba(255,255,255,0.5); text-align: center;">No tickets created yet</p>
            </div>
        `;
        return;
    }
    
    const filteredTickets = filterTicketsByStatus();
    ticketsList.innerHTML = filteredTickets.map(ticket => createTicketHTML(ticket)).join('');
}

function createTicketHTML(ticket) {
    const statusClass = `status-${ticket.status.replace(' ', '-')}`;
    const statusText = ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1);
    
    return `
        <div class="ticket-card" data-ticket-id="${ticket.id}">
            <div class="ticket-header">
                <div class="ticket-info">
                    <h4>${ticket.id} - ${ticket.issueType}</h4>
                    <div class="ticket-meta">
                        <span><i class="fas fa-user"></i> ${ticket.mobileOrUserId}</span>
                        <span><i class="fas fa-calendar"></i> ${new Date(ticket.createdAt).toLocaleDateString()}</span>
                        <span><i class="fas fa-user-tie"></i> ${ticket.createdBy}</span>
                    </div>
                </div>
                <span class="status-badge ${statusClass}">${statusText}</span>
            </div>
            
            <div class="ticket-description">
                <strong>Issue:</strong> ${ticket.issueDescription}
            </div>
            
            ${ticket.status === 'closed' ? `
                <div class="ticket-actions ticket-actions-closed">
                    <div class="closed-notice-content">
                        <i class="fas fa-lock"></i>
                        <span>This ticket is CLOSED and cannot be modified</span>
                    </div>
                </div>
                ` : `
                <div class="ticket-actions">
                    <button class="btn-edit btn-status-small" onclick="changeTicketStatus('${ticket.id}')">
                        <i class="fas fa-edit"></i> Status
                    </button>
                </div>
                `}
                
                ${ticket.status === 'closed' ? `
                <div class="ticket-closed-notice">
                    <div class="closed-notice-content">
                        <i class="fas fa-lock"></i>
                        <span>This ticket is CLOSED and cannot be modified</span>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
}

function filterTicketsByStatus() {
    const statusFilter = document.getElementById('statusFilter')?.value || 'all';
    if (statusFilter === 'all') return tickets;
    return tickets.filter(ticket => ticket.status === statusFilter);
}

function filterTickets() {
    displayTickets();
}

function editTicket(ticketId) {
    const ticket = tickets.find(t => t.id === ticketId);
    if (!ticket) return;
    
    const newStatus = prompt(`Current status: ${ticket.status}\n\nEnter new status (new, in-progress, resolved, closed):`, ticket.status);
    if (!newStatus || !['new', 'in-progress', 'resolved', 'closed'].includes(newStatus.toLowerCase())) return;
    
    const remark = prompt('Add a remark (optional):');
    
    ticket.status = newStatus.toLowerCase();

    
    // Save to localStorage
    localStorage.setItem('tickets', JSON.stringify(tickets));
    
    // Refresh display
    displayTickets();
    updateTicketCounts();
    showToast('Ticket updated successfully!', 'success');
}

function closeTicket(ticketId) {
    const ticket = tickets.find(t => t.id === ticketId);
    if (!ticket) return;
    
    if (confirm(`Are you sure you want to close ticket ${ticketId}?`)) {
        ticket.status = 'closed';
        localStorage.setItem('tickets', JSON.stringify(tickets));
        displayTickets();
        updateTicketCounts();
        showToast('Ticket closed successfully!', 'success');
    }
}

function updateTicketCounts() {
    const newCount = tickets.filter(t => t.status === 'new').length;
    const pendingCount = tickets.filter(t => t.status === 'in-progress').length;
    const resolvedCount = tickets.filter(t => t.status === 'resolved' || t.status === 'closed').length;
    
    document.getElementById('newTickets').textContent = newCount;
    document.getElementById('pendingTickets').textContent = pendingCount;
    document.getElementById('resolvedTickets').textContent = resolvedCount;
}

// Initialize dashboards
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize manager dashboard on the correct page
    if (window.location.href.includes('dashboard.html')) {
        initializeManagerDashboard();
    }
    
    // Only update staff dashboard counts on the correct page
    if (window.location.href.includes('staff-dashboard.html')) {
        updateStaffDashboardCounts();
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('createTicketModal');
        if (event.target === modal) {
            closeCreateTicketForm();
        }
    };
    
    // Listen for storage changes to auto-update manager dashboard
    window.addEventListener('storage', function(e) {
        if (e.key === 'tickets' && window.location.pathname.includes('dashboard.html')) {
            console.log('Tickets updated, refreshing manager dashboard...');
            setTimeout(() => {
                displayManagerTickets();
                updateManagerDashboardCounts();
            }, 100);
        }
    });
});

function updateScreenshotName() {
    console.log('Updating screenshot name...');
    const fileInput = document.getElementById('screenshot');
    const fileNameDiv = document.getElementById('screenshotName');
    
    if (fileInput && fileNameDiv) {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
            console.log('File selected:', file.name);
        fileNameDiv.textContent = `Selected: ${file.name}`;
        fileNameDiv.style.display = 'block';
    } else {
            console.log('No file selected');
        fileNameDiv.style.display = 'none';
        }
    } else {
        console.error('File input or name display element not found!');
    }
}

// Make sure these functions are globally accessible for HTML onclick handlers
window.openCreateTicketForm = openCreateTicketForm;
window.closeCreateTicketForm = closeCreateTicketForm;
window.submitTicket = submitTicket;
window.updateScreenshotName = updateScreenshotName;
window.displayManagerTickets = displayManagerTickets;
window.updateManagerDashboardCounts = updateManagerDashboardCounts;
window.filterManagerTickets = filterManagerTickets;
window.refreshManagerDashboard = refreshManagerDashboard;

window.changeTicketStatus = changeTicketStatus;
window.closeStatusModal = closeStatusModal;
window.updateTicketStatus = updateTicketStatus;
window.viewScreenshot = viewScreenshot;
window.clearAllTickets = clearAllTickets;
window.debugFilterState = debugFilterState;


// Initialize tickets display on manager dashboard
function initializeManagerDashboard() {
    console.log('=== INITIALIZE MANAGER DASHBOARD FUNCTION CALLED ===');
    
    // Check if we're on the right page
    if (!window.location.href.includes('dashboard.html')) {
        console.log('Not on dashboard.html, skipping initialization');
        return;
    }
    
    console.log('On dashboard.html, proceeding with initialization...');
    
    try {
        // Initialize ticket management system
        initializeTicketSystem();
        
        // Initialize online status system
        initializeOnlineStatus();
        
        // Setup online status display
        setupManagerOnlineStatusDisplay();
        
        // Restore filter selection from localStorage
        const savedFilter = localStorage.getItem('currentFilter');
        if (savedFilter) {
            currentFilter = savedFilter;
            // Update filter button states
            updateFilterButtonStates();
        }
        
        // Display tickets
        displayManagerTickets();
        updateManagerDashboardCounts();
        
        // Set up periodic refresh
        setInterval(() => {
            displayManagerTickets();
            updateManagerDashboardCounts();
            updateManagerOnlineStatus(); // Update online status
        }, 5000); // Refresh every 5 seconds
        
    } catch (error) {
        console.error('Error initializing manager dashboard:', error);
    }
}

// --- Issue Types modal global helpers ---
async function loadIssueTypesIntoManager() {
    try {
        const list = document.getElementById('issueTypesList');
        if (!list) return;
        list.innerHTML = '<div style="color:#999">Loading...</div>';
        const types = await fetchIssueTypes();
        
        if (types.length === 0) {
            list.innerHTML = '<div style="color:#999; text-align:center; padding:20px;">No issue types found</div>';
            return;
        }
        
        list.innerHTML = types.map(type => `
            <div class="issue-type-card">
                <div class="issue-type-content">
                    <h4>${type.name}</h4>
                    <div class="issue-type-actions">
                        <button onclick="openEditIssueTypeModal(${type.id}, '${type.name}')" class="action-btn small">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="openDeleteIssueTypeModal(${type.id}, '${type.name}')" class="action-btn small danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (e) {
        console.error('Error loading issue types:', e);
        if (list) {
            list.innerHTML = '<div style="color:#ef4444; text-align:center; padding:20px;">Failed to load issue types</div>';
        }
    }
}

function openIssueTypesModal() {
    const modal = document.getElementById('issueTypesModal');
    if (!modal) return;
    modal.style.display = 'block';
    loadIssueTypesIntoManager();
}

function closeIssueTypesModal() {
    const modal = document.getElementById('issueTypesModal');
    if (!modal) return;
    modal.style.display = 'none';
}

// expose to global
window.openIssueTypesModal = openIssueTypesModal;
window.closeIssueTypesModal = closeIssueTypesModal;

function displayManagerTickets() {
    console.log('=== DISPLAY MANAGER TICKETS FUNCTION CALLED ===');
    
    const ticketsList = document.getElementById('managerTicketsList');
    console.log('Tickets list element found:', !!ticketsList);
    console.log('Tickets list element:', ticketsList);
    
    if (!ticketsList) {
        console.warn('Manager tickets list element not found (not on manager dashboard).');
        return;
    }
    
    // Prefer server data; fallback to localStorage
    (async () => {
        let list = [];
        try {
            const statusFilter = document.getElementById('statusFilter');
            const status = statusFilter && statusFilter.value ? statusFilter.value : 'all';
            list = await fetchTicketsFromApi(status);
        } catch (e) {
            const storedTickets = localStorage.getItem('tickets');
            list = storedTickets ? JSON.parse(storedTickets) : [];
        }
    
        console.log('Tickets for display:', list);
        console.log('Number of tickets:', list.length);
    
        if (list.length === 0) {
        console.log('No tickets found, showing empty message');
            ticketsList.innerHTML = '<div class="no-tickets">No tickets found</div>';
            return;
        }
    
        // If we fetched with filter at server, list is already filtered
        const filteredTickets = list;
    
        if (filteredTickets.length === 0) {
            console.log('No tickets found with current filter');
            ticketsList.innerHTML = '<div class="no-tickets">No tickets found with selected status</div>';
            return;
        }
    
        let html = '';
        filteredTickets.forEach(ticket => {
            const statusClass = ticket.status === 'new' ? 'status-new' : 
                           ticket.status === 'in-progress' ? 'status-in-progress' : 
                           ticket.status === 'resolved' ? 'status-resolved' : 'status-closed';
            const code = ticket.ticketCode || ticket.id;
            const assignedStaffLabel = ticket.assignedStaffName || ticket.assignedToName || ticket.assigned_to_name || ticket.assignedTo || '';
            const assignedByLabel = ticket.assignedByName || ticket.assigned_by_name || ticket.assignedBy || '';
            const latestNote = (ticket.statusDescription && ticket.statusDescription.trim() !== '')
                ? ticket.statusDescription
                : (ticket.statusHistory && ticket.statusHistory.length > 0 && ticket.statusHistory[ticket.statusHistory.length - 1].description
                    ? ticket.statusHistory[ticket.statusHistory.length - 1].description
                    : '');
            html += `
            <div class="ticket-card ${ticket.status}">
                <div class="ticket-header">
                    <div class="ticket-info">
                        <h4>${ticket.issueType}</h4>
                        <div class="ticket-meta">
                            <span><i class="fas fa-hashtag"></i> ${code}</span>
                            <span><i class="fas fa-user"></i> ${ticket.mobileOrUserId}</span>
                            <span><i class="fas fa-calendar"></i> ${new Date(ticket.createdAt).toLocaleDateString()}</span>
                            <span><i class="fas fa-user-plus"></i> ${ticket.createdBy || 'Staff'}</span>
                            ${assignedByLabel ? `<span><i class=\"fas fa-user-tie\"></i> Assigned By: ${assignedByLabel}</span>` : ''}
                            ${assignedStaffLabel ? `<span><i class=\"fas fa-user-check\"></i> Assigned Staff: ${assignedStaffLabel}</span>` : ''}
                        </div>
                    </div>
                    <span class="status-badge ${statusClass}">${ticket.status}</span>
                </div>
                <div class="ticket-description">
                    <p><strong>Issue Type:</strong> ${ticket.issueType}</p>
                    <p><strong>Description:</strong> ${ticket.issueDescription}</p>
                    ${ticket.screenshot ? `<p><strong>Screenshot:</strong> <a href="#" onclick="viewScreenshot('${ticket.screenshot}')">View Image</a></p>` : ''}
                </div>
                
                         <!-- Simplified Status Section -->
         <div class="ticket-status-section">
             <div class="status-main">
                 <div class="status-details">
                     <div class="status-top-row">
                         <span class="status-badge-large ${statusClass}">${ticket.status.toUpperCase()}</span>
                         <div class="status-option">
                             <span class="status-label">Status</span>
                             <div class="status-bar"></div>
                         </div>
                     </div>
                     ${ticket.statusHistory && ticket.statusHistory.length > 0 ? `
                         <span class="status-updated">Last updated: ${new Date(ticket.statusHistory[ticket.statusHistory.length - 1].changedAt).toLocaleDateString()}</span>
                         ${ticket.statusHistory[ticket.statusHistory.length - 1].description ? `
                         <p class="status-note">${ticket.statusHistory[ticket.statusHistory.length - 1].description}</p>
                         ` : ''}
                     ` : `
                         <span class="status-updated">Status set when created</span>
                     `}
                 </div>

             </div>
                    
                    ${ticket.statusHistory && ticket.statusHistory.length > 1 ? `
                    <!-- Show only if there are multiple status changes -->
                    <div class="status-history-mini">
                        <details>
                            <summary><i class="fas fa-history"></i> View History (${ticket.statusHistory.length} changes)</summary>
                            <div class="status-history-list">
                                ${ticket.statusHistory.map(history => `
                                    <div class="status-history-item">
                                        <span class="status-badge-small status-${history.status}">${history.status}</span>
                                        <span class="status-date">${new Date(history.changedAt).toLocaleDateString()}</span>
                                        ${history.description ? `<span class="status-desc">${history.description}</span>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        </details>
                    </div>
                    ` : ''}
                </div>
                
                ${ticket.status === 'closed' ? `
                <div class="ticket-actions ticket-actions-closed">
                    <button class="btn-view-details" onclick="viewClosedTicketDetails('${ticket.id}')">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </div>
                ` : `
                <div class="ticket-actions" style="display:flex; justify-content:flex-end; gap:10px;">
                    ${latestNote ? `<button class="btn-edit btn-status-small" onclick='openStatusDescriptionModal(${JSON.stringify(latestNote)})'>
                        <i class="fas fa-eye"></i> View Status
                    </button>` : ''}
                    <button class="btn-edit btn-status-small" onclick="changeTicketStatus('${code}')">
                        <i class="fas fa-edit"></i> Status
                    </button>
                </div>
                `}
            </div>
        `;
        });
        
        ticketsList.innerHTML = html;
        console.log('Displayed tickets count:', filteredTickets.length);
    })();
}

// Simple modal for manager to view full status description
function openStatusDescriptionModal(note) {
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.right = '0';
    overlay.style.bottom = '0';
    overlay.style.background = 'rgba(0,0,0,0.6)';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '3000';

    const box = document.createElement('div');
    box.style.background = '#fff';
    box.style.color = '#111827';
    box.style.borderRadius = '12px';
    box.style.maxWidth = '560px';
    box.style.width = '90%';
    box.style.padding = '20px';
    box.style.boxShadow = '0 10px 25px rgba(0,0,0,0.2)';
    box.innerHTML = `
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="margin:0; font-size: 18px;">Status Description</h3>
            <button id="mgrCloseStatusNote" style="background:none; border:none; font-size:24px; line-height:1; cursor:pointer; color:#6b7280">&times;</button>
        </div>
        <div style="background:#f9fafb; border:1px solid #e5e7eb; padding:14px; border-radius:8px; white-space:pre-wrap; word-break: break-word; max-height:300px; overflow:auto;">${note}</div>
        <div style="text-align:right; margin-top:14px;">
            <button id="mgrOkStatusNote" class="action-btn" style="background:#2563eb; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">OK</button>
        </div>
    `;
    overlay.appendChild(box);
    document.body.appendChild(overlay);

    function close() { document.body.removeChild(overlay); }
    box.querySelector('#mgrCloseStatusNote').addEventListener('click', close);
    box.querySelector('#mgrOkStatusNote').addEventListener('click', close);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
}

function updateManagerDashboardCounts() {
    console.log('Updating manager dashboard counts...');
    (async () => {
        let list = [];
        try {
            list = await fetchTicketsFromApi('all');
        } catch (e) {
            const storedTickets = localStorage.getItem('tickets');
            list = storedTickets ? JSON.parse(storedTickets) : [];
        }

        const totalTickets = list.length;
        const pendingTickets = list.filter(t => t.status === 'new' || t.status === 'in-progress').length;
        const resolvedTickets = list.filter(t => t.status === 'resolved' || t.status === 'closed').length;

        const totalElement = document.getElementById('totalTickets');
        const pendingElement = document.getElementById('pendingTickets');
        const resolvedElement = document.getElementById('resolvedTickets');

        if (totalElement) totalElement.textContent = totalTickets;
        if (pendingElement) pendingElement.textContent = pendingTickets;
        if (resolvedElement) resolvedElement.textContent = resolvedTickets;
    })();
}

function filterManagerTickets() {
    console.log('=== FILTER MANAGER TICKETS FUNCTION CALLED ===');
    const statusFilter = document.getElementById('statusFilter');
    
    if (!statusFilter) {
        console.error('Status filter element not found');
        return;
    }
    
    const selectedValue = statusFilter.value;
    console.log('Selected filter value:', selectedValue);
    
    // Store the filter selection in localStorage for persistence
    localStorage.setItem('managerStatusFilter', selectedValue);
    
    // Call the main display function which now handles filtering
    displayManagerTickets();
    
    // Show feedback to user
    if (selectedValue === 'all') {
        showInfoMessage('Showing all tickets');
    } else {
        showInfoMessage(`Filtered to show ${selectedValue} tickets`);
    }
}

function resetManagerFilter() {
    console.log('=== RESET MANAGER FILTER FUNCTION CALLED ===');
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.value = 'all';
        console.log('Filter reset to "all"');
        
        // Clear the stored filter preference
        localStorage.removeItem('managerStatusFilter');
        
        // Refresh the display to show all tickets
        displayManagerTickets();
        
        // Show feedback to user
        showInfoMessage('Filter reset - showing all tickets');
    }
}

function restoreFilterSelection() {
    console.log('=== RESTORE FILTER SELECTION FUNCTION CALLED ===');
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        const savedFilter = localStorage.getItem('managerStatusFilter');
        if (savedFilter) {
            statusFilter.value = savedFilter;
            console.log('Restored filter selection:', savedFilter);
        } else {
            console.log('No saved filter found, using default "all"');
        }

        // Staff suggestions (assign modal)
        (function initStaffSuggestions(){
            const emailInput = document.getElementById('assignToStaff');
            const nameInput = document.getElementById('assignToStaffName');
            const box = document.getElementById('assignStaffSuggestions');
            if (!emailInput || !box) return;

            let staffCache = [];
            async function ensureStaff() {
                if (staffCache.length) return staffCache;
                try {
                    const res = await fetch('api/staff-list.php');
                    const data = await res.json();
                    if (data && data.ok) staffCache = data.staff || [];
                } catch (e) {}
                return staffCache;
            }

            function render(items) {
                if (!items || !items.length) { box.style.display='none'; box.innerHTML=''; return; }
                box.innerHTML = items.map(s => `<div data-email="${s.email}" data-name="${s.name}" style="padding:6px 10px; cursor:pointer;">${s.name} <span style=\"opacity:.7\">(${s.email})</span></div>`).join('');
                box.style.display = 'block';
                Array.from(box.children).forEach(el => {
                    el.addEventListener('click', function(){
                        emailInput.value = this.getAttribute('data-email');
                        if (nameInput) nameInput.value = this.getAttribute('data-name');
                        box.style.display='none';
                    });
                });
            }

            emailInput.addEventListener('input', async function(){
                const q = this.value.toLowerCase();
                const list = await ensureStaff();
                const matched = list.filter(s => s.email.toLowerCase().includes(q) || s.name.toLowerCase().includes(q)).slice(0,10);
                render(matched);
            });

            emailInput.addEventListener('focus', async function(){
                const list = await ensureStaff();
                render(list.slice(0,10));
            });

            document.addEventListener('click', function(e){
                if (e.target !== emailInput && e.target.parentElement !== box) {
                    box.style.display='none';
                }
            });
        })();
    }
}

function debugFilterState() {
    console.log('=== DEBUG FILTER STATE ===');
    
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        console.log('Filter element found:', statusFilter);
        console.log('Current filter value:', statusFilter.value);
        console.log('Available options:', Array.from(statusFilter.options).map(opt => opt.value));
    } else {
        console.error('Status filter element not found!');
    }
    
    const storedTickets = localStorage.getItem('tickets');
    const tickets = storedTickets ? JSON.parse(storedTickets) : [];
    console.log('Total tickets in localStorage:', tickets.length);
    
    if (tickets.length > 0) {
        const statusCounts = {};
        tickets.forEach(ticket => {
            statusCounts[ticket.status] = (statusCounts[ticket.status] || 0) + 1;
        });
        console.log('Ticket status distribution:', statusCounts);
        
        // Show first few tickets for debugging
        tickets.slice(0, 3).forEach((ticket, index) => {
            console.log(`Ticket ${index + 1}:`, {
                id: ticket.id,
                status: ticket.status,
                issueType: ticket.issueType
            });
        });
    }
    
    const savedFilter = localStorage.getItem('managerStatusFilter');
    console.log('Saved filter preference:', savedFilter);
}

function refreshManagerDashboard() {
    console.log('Refreshing manager dashboard...');
    displayManagerTickets();
    updateManagerDashboardCounts();
    showSuccessMessage('Dashboard refreshed successfully!');
}







function changeTicketStatus(ticketId) {
    console.log('=== CHANGE TICKET STATUS FUNCTION CALLED ===');
    console.log('Ticket ID:', ticketId);
    
    currentTicketId = ticketId;
    
    // Get current ticket to show current status
    const storedTickets = localStorage.getItem('tickets');
    const tickets = storedTickets ? JSON.parse(storedTickets) : [];
    const ticket = tickets.find(t => t.id === ticketId);
    console.log('Found ticket:', ticket);
    
    if (ticket) {
        // Check if ticket is closed
        if (ticket.status === 'closed') {
            showErrorMessage('Cannot modify a closed ticket. This ticket is locked and cannot be changed.');
            return;
        }
        
        // Set current status in dropdown
        const statusSelect = document.getElementById('newStatus');
        console.log('Status dropdown found:', !!statusSelect);
        if (statusSelect) {
            statusSelect.value = ticket.status;
            console.log('Status dropdown updated to:', ticket.status);
        } else {
            console.error('Status dropdown not found');
            alert('Status dropdown not found!');
        }
        
        // Hide warning and enable form for non-closed tickets
        const warning = document.getElementById('closedTicketWarning');
        const form = document.getElementById('changeStatusForm');
        if (warning) warning.style.display = 'none';
        if (form) form.style.display = 'block';
        

    }
    
    // Show the modal
    const modal = document.getElementById('changeStatusModal');
    console.log('Status modal found:', !!modal);
    if (modal) {
        modal.style.display = 'block';
        console.log('Status modal display set to block');
    } else {
        console.error('Change status modal not found');
        alert('Change status modal not found!');
    }
}

function closeStatusModal() {
    const modal = document.getElementById('changeStatusModal');
    if (modal) {
        // Clear the form fields
        document.getElementById('newStatus').value = '';
        document.getElementById('statusDescription').value = '';
        
        modal.style.display = 'none';
        currentTicketId = null;
    }
}

function updateTicketStatus() {
    const newStatus = document.getElementById('newStatus').value;
    const statusDescription = document.getElementById('statusDescription').value.trim();
    
    if (!newStatus) {
        showErrorMessage('Please select a status');
        return;
    }
    
    if (!currentTicketId) {
        showErrorMessage('No ticket selected');
        return;
    }
    
    // Try server update first using ticket code if available
    const ticketCode = currentTicketId; // our UI stores code in currentTicketId
    updateTicketStatusViaApi(ticketCode, newStatus, statusDescription, 'Manager').then(() => {
        closeStatusModal();
        displayManagerTickets();
        updateManagerDashboardCounts();
        showSuccessMessage('Ticket status updated in database!');
        currentTicketId = null;
    }).catch((err) => {
        // If server explicitly says not found or other error, do not silently fallback
        const message = (err && err.message) ? err.message : 'Update failed';
        console.warn('Server update failed:', message);
        
        // Only fallback for network failures; not for 4xx from server
        if (/HTTP\s(4\d\d|5\d\d)/.test(message)) {
            showErrorMessage('Failed to update in database. ' + message);
            return;
        }
        // Fallback to local update
        const storedTickets = localStorage.getItem('tickets');
        let ticketsLocal = storedTickets ? JSON.parse(storedTickets) : [];
        const ticketIndex = ticketsLocal.findIndex(t => t.id === currentTicketId);
        if (ticketIndex === -1) {
            showErrorMessage('Ticket not found');
            return;
        }
        ticketsLocal[ticketIndex].status = newStatus;
        if (statusDescription) {
            if (!ticketsLocal[ticketIndex].statusHistory) {
                ticketsLocal[ticketIndex].statusHistory = [];
            }
            ticketsLocal[ticketIndex].statusHistory.push({
                status: newStatus,
                description: statusDescription,
                changedAt: new Date().toISOString(),
                changedBy: 'Manager'
            });
        }
        localStorage.setItem('tickets', JSON.stringify(ticketsLocal));
        closeStatusModal();
        displayManagerTickets();
        updateManagerDashboardCounts();
        showInfoMessage('Backend not reachable. Status updated locally.');
        currentTicketId = null;
    });
}

function viewScreenshot(screenshotData) {
    // Create a modal to display the screenshot
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 80%; max-height: 80%;">
            <div class="modal-header">
                <h2>Screenshot</h2>
                <span class="close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</span>
            </div>
            <div style="text-align: center; padding: 20px;">
                <img src="${screenshotData}" alt="Screenshot" style="max-width: 100%; max-height: 400px; border-radius: 8px;">
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Global variable to track current ticket for status changes
let currentTicketId = null;

function clearAllTickets() {
    if (!confirm('Are you sure you want to clear ALL tickets? This action cannot be undone.')) return;
    // Try server clear first
    (async () => {
        let cleared = false;
        try {
            const res = await apiFetchJson('api/tickets-clear.php', { method: 'POST' });
            if (res && res.ok) { cleared = true; }
        } catch (e) {
            console.warn('Server clear failed, will fallback to local');
        }

        // Always clear local cache as well
        localStorage.removeItem('tickets');
        localStorage.removeItem('ticketCount');
        localStorage.removeItem('todayCount');

        if (window.location.href.includes('dashboard.html')) {
            displayManagerTickets();
            updateManagerDashboardCounts();
        } else if (window.location.href.includes('staff-dashboard.html')) {
            displayTickets();
            updateStaffDashboardCounts();
        }
        showSuccessMessage(cleared ? 'All tickets cleared in database!' : 'Backend unreachable. Local tickets cleared.');
        console.log('All tickets cleared (server:', cleared, ')');
    })();
}

function viewClosedTicketDetails(ticketId) {
    console.log('=== VIEW CLOSED TICKET DETAILS FUNCTION CALLED ===');
    console.log('Ticket ID:', ticketId);
    
    currentTicketId = ticketId;
    
    // Get current ticket to show current status
    const storedTickets = localStorage.getItem('tickets');
    const tickets = storedTickets ? JSON.parse(storedTickets) : [];
    const ticket = tickets.find(t => t.id === ticketId);
    console.log('Found ticket:', ticket);
    
    if (ticket && ticket.status === 'closed') {
        // Show warning and hide form for closed tickets
        const warning = document.getElementById('closedTicketWarning');
        const form = document.getElementById('changeStatusForm');
        if (warning) warning.style.display = 'block';
        if (form) form.style.display = 'none';
        
        // Show the modal
        const modal = document.getElementById('changeStatusModal');
        console.log('Status modal found:', !!modal);
        if (modal) {
            modal.style.display = 'block';
            console.log('Status modal display set to block');
        } else {
            console.error('Change status modal not found');
            alert('Change status modal not found!');
        }
    }
}

// Function to setup online status toggle in staff dashboard header
function setupHeaderOnlineStatusToggle() {
    const headerToggle = document.getElementById('onlineStatusToggleHeader');
    if (!headerToggle) return;
    
    // Add event listener
    headerToggle.addEventListener('change', function() {
        const currentStaffId = getCurrentStaffId();
        if (this.checked) {
            // Turn staff online
            updateStaffStatus(currentStaffId, true);
            showSuccessMessage('You are now online! Managers can see your status.');
            updateHeaderToggleText(true);
            
            // Update manager dashboard if it's open in another tab
            updateManagerDashboardStatus();
        } else {
            // Turn staff offline
            updateStaffStatus(currentStaffId, false);
            showInfoMessage('You are now offline');
            updateHeaderToggleText(false);
            
            // Update manager dashboard if it's open in another tab
            updateManagerDashboardStatus();
        }
    });
    
    // Set initial state based on current online status
    const currentStaffId = getCurrentStaffId();
    if (currentStaffId && isStaffOnline(currentStaffId)) {
        headerToggle.checked = true;
        updateHeaderToggleText(true);
    } else {
        headerToggle.checked = false;
        updateHeaderToggleText(false);
    }
    
    console.log('Header online status toggle initialized');
}

// Function to update header toggle text
function updateHeaderToggleText(isOnline) {
    const toggleText = document.querySelector('.toggle-text-header');
    if (toggleText) {
        toggleText.textContent = isOnline ? 'Online' : 'Offline';
        toggleText.style.color = isOnline ? '#10b981' : '#ffffff';
    }
}

// Function to update manager dashboard status (for cross-tab communication)
function updateManagerDashboardStatus() { /* removed */ }



// Function to setup online status display in manager dashboard
function setupManagerOnlineStatusDisplay() { /* removed */ }

// Function to setup real-time status monitoring
function setupRealTimeStatusMonitoring() { /* removed */ }

// Function to initialize staff login form
function initializeStaffLoginForm() {
    console.log('=== INITIALIZING STAFF LOGIN FORM ===');
    const staffLoginForm = document.getElementById('staffLoginForm');
    console.log('Staff login form found:', !!staffLoginForm);
    
    if (staffLoginForm) {
        staffLoginForm.addEventListener('submit', function(e) {
            console.log('=== STAFF LOGIN FORM SUBMITTED ===');
            e.preventDefault();
            
            const staffId = document.getElementById('staffUserId');
            const password = document.getElementById('staffPassword');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const loginBtn = document.querySelector('.login-btn');
            
            console.log('Staff form elements found:');
            console.log('- Staff ID input:', !!staffId);
            console.log('- Password input:', !!password);
            console.log('- Button text:', !!btnText);
            console.log('- Button loading:', !!btnLoading);
            console.log('- Login button:', !!loginBtn);
            
            if (!staffId || !password || !btnText || !btnLoading || !loginBtn) {
                console.error('Some staff form elements are missing!');
                return;
            }
            
            const staffIdValue = staffId.value.trim();
            const passwordValue = password.value;
            
            console.log('Staff form values:');
            console.log('- Staff ID:', staffIdValue);
            console.log('- Password:', passwordValue ? '***' : 'empty');
            
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            loginBtn.disabled = true;
            
            // Simplified: authenticate against backend
            (async () => {
                try {
                    const response = await fetch(API_BASE + 'api/staff-login.php'.replace(/^\/?api\/?/, ''), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: staffIdValue, password: passwordValue })
                    });
                    const data = await response.json();
                    if (!response.ok || !data.ok) throw new Error(data.error || 'Login failed');
                    // Mark online and persist session
                    updateStaffStatus(staffIdValue, true);
                    localStorage.setItem('currentStaffId', staffIdValue);
                    sessionStorage.setItem('currentStaffId', staffIdValue);
                    sessionStorage.setItem('staffLoggedIn', 'true');
                    showSuccessMessage('Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'staff-dashboard.html';
                    }, 600);
                } catch (err) {
                    showErrorMessage(err.message || 'Invalid credentials');
                    loginBtn.disabled = false;
                    password.value = '';
                } finally {
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                }
            })();
        });
        console.log('Staff login form handler initialized successfully');
    } else {
        console.error('Staff login form not found!');
    }
}

// Simplified staff registration: register, then auto-login
function initializeStaffRegisterForm() {
    const form = document.getElementById('staffRegisterForm');
    if (!form) return;
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = document.getElementById('regName').value.trim();
        const email = document.getElementById('regEmail').value.trim().toLowerCase();
        const password = document.getElementById('regPassword').value;
        const btnText = document.getElementById('regBtnText');
        const btnLoading = document.getElementById('regBtnLoading');
        if (btnText && btnLoading) {
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
        }
        try {
            const res = await fetch(API_BASE + 'api/staff-register.php'.replace(/^\/?api\/?/, ''), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password })
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Registration failed');
            showSuccessMessage('Registered! Logging you in...');
            // Auto-login
            const loginRes = await fetch(API_BASE + 'api/staff-login.php'.replace(/^\/?api\/?/, ''), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            const loginData = await loginRes.json();
            if (!loginRes.ok || !loginData.ok) throw new Error(loginData.error || 'Auto-login failed');
            updateStaffStatus(email, true);
            localStorage.setItem('currentStaffId', email);
            sessionStorage.setItem('currentStaffId', email);
            window.location.href = 'staff-dashboard.html';
        } catch (err) {
            showErrorMessage(err.message || 'Registration failed');
        } finally {
            if (btnText && btnLoading) {
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
        }
    });
}

// Function to initialize manager login form
function initializeManagerLoginForm() {
    console.log('=== INITIALIZING MANAGER LOGIN FORM ===');
    const managerLoginForm = document.getElementById('managerLoginForm');
    console.log('Manager login form found:', !!managerLoginForm);
    
    if (managerLoginForm) {
        managerLoginForm.addEventListener('submit', function(e) {
            console.log('=== MANAGER LOGIN FORM SUBMITTED ===');
            e.preventDefault();
            
            const managerId = document.getElementById('managerUserId');
            const password = document.getElementById('managerPassword');
            const btnText = document.getElementById('managerBtnText');
            const btnLoading = document.getElementById('managerBtnLoading');
            const loginBtn = document.querySelector('.manager-login-btn');
            
            console.log('Form elements found:');
            console.log('- Manager ID input:', !!managerId);
            console.log('- Password input:', !!password);
            console.log('- Button text:', !!btnText);
            console.log('- Button loading:', !!btnLoading);
            console.log('- Login button:', !!loginBtn);
            
            if (!managerId || !password || !btnText || !btnLoading || !loginBtn) {
                console.error('Some form elements are missing!');
                return;
            }
            
            const managerIdValue = managerId.value;
            const passwordValue = password.value;
            
            console.log('Form values:');
            console.log('- Manager ID:', managerIdValue);
            console.log('- Password:', passwordValue ? '***' : 'empty');
            
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            loginBtn.disabled = true;
            
            setTimeout(() => {
                console.log('Checking credentials...');
                if (managerIdValue === 'himaapp@2024' && passwordValue === 'Bala') {
                    console.log('Credentials valid, logging in...');
                    // Set manager as online
                    updateStaffStatus(managerIdValue, true);
                    localStorage.setItem('currentStaffId', managerIdValue);
                    sessionStorage.setItem('currentStaffId', managerIdValue);
                    sessionStorage.setItem('managerLoggedIn', 'true');
                    
                    showSuccessMessage('Login successful! Redirecting...');
                    setTimeout(() => {
                        console.log('Redirecting to dashboard...');
                        window.location.href = 'dashboard.html';
                    }, 1000);
                } else {
                    console.log('Invalid credentials');
                    showErrorMessage('Invalid credentials. Please try again.');
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                    loginBtn.disabled = false;
                    password.value = '';
                }
            }, 1500);
        });
        console.log('Manager login form handler initialized successfully');
    } else {
        console.error('Manager login form not found!');
    }
}






