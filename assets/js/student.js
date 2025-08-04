/**
 * Student Portal JavaScript - Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

// Student Portal JavaScript Functions

// Auto-refresh dashboard data
function refreshDashboardData() {
    fetch('../student/ajax/check_overdue.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.overdue_count);
                updateDashboardStats(data);
            }
        })
        .catch(error => {
            console.error('Error refreshing dashboard data:', error);
        });
}

// Update notification badge
function updateNotificationBadge(overdueCount) {
    const badge = document.querySelector('#notificationDropdown .badge');
    if (badge) {
        if (overdueCount > 0) {
            badge.textContent = overdueCount;
            badge.style.display = 'inline';
            badge.classList.add('pulse');
        } else {
            badge.style.display = 'none';
            badge.classList.remove('pulse');
        }
    }
}

// Update dashboard statistics
function updateDashboardStats(data) {
    // Update overdue books count
    const overdueElement = document.querySelector('.dashboard-card.danger .h5');
    if (overdueElement) {
        overdueElement.textContent = data.overdue_count;
    }
    
    // Update pending fines
    const finesElement = document.querySelector('.dashboard-card.warning .h5');
    if (finesElement) {
        finesElement.textContent = '₹' + parseFloat(data.pending_fines).toFixed(2);
    }
}

// Search books with AJAX
function searchBooks(searchTerm, category = '', author = '', page = 1) {
    const searchContainer = document.getElementById('searchResults');
    if (searchContainer) {
        searchContainer.innerHTML = '<div class="text-center"><div class="loading-spinner"></div><p>Searching...</p></div>';
    }
    
    const params = new URLSearchParams({
        search: searchTerm,
        category: category,
        author: author,
        page: page
    });
    
    fetch('../student/ajax/search_books.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data);
            } else {
                showError('Search failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error searching books:', error);
            showError('Search failed. Please try again.');
        });
}

// Display search results
function displaySearchResults(data) {
    const container = document.getElementById('searchResults');
    if (!container) return;
    
    if (data.books.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No books found</h6>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="row g-3">';
    data.books.forEach(book => {
        // Generate thumbnail URL
        const thumbnailUrl = `../uploads/thumbnails/book_${md5(book.title + book.author + (book.category_name || '') + 'medium')}.png`;
        
        html += `
            <div class="col-md-6 col-lg-4">
                <div class="book-card card h-100">
                    <div class="card-img-top book-cover">
                        <img src="${thumbnailUrl}" 
                             class="img-fluid" alt="Book Cover" 
                             style="width: 100%; height: 200px; object-fit: cover;">
                    </div>
                    <div class="card-body">
                        <h6 class="book-title">${escapeHtml(book.title)}</h6>
                        <p class="book-author">by ${escapeHtml(book.author)}</p>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-tag me-1"></i>${escapeHtml(book.category_name || 'Uncategorized')}
                        </p>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-barcode me-1"></i>${escapeHtml(book.isbn)}
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="book-status ${book.availability_status === 'Available' ? 'status-available' : 'status-unavailable'}">
                                ${book.availability_status}
                            </span>
                            <small class="text-muted">
                                ${book.available_copies} copies available
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    // Add pagination if needed
    if (data.total_pages > 1) {
        html += generatePagination(data.page, data.total_pages, data.total);
    }
    
    container.innerHTML = html;
}

// Generate pagination
function generatePagination(currentPage, totalPages, totalItems) {
    let html = `
        <div class="d-flex justify-content-between align-items-center mt-4">
            <small class="text-muted">Showing ${((currentPage - 1) * 10) + 1} to ${Math.min(currentPage * 10, totalItems)} of ${totalItems} results</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
    `;
    
    // Previous button
    if (currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="searchBooks(currentSearchTerm, currentCategory, currentAuthor, ${currentPage - 1})">Previous</a></li>`;
    }
    
    // Page numbers
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="searchBooks(currentSearchTerm, currentCategory, currentAuthor, ${i})">${i}</a>
            </li>
        `;
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="searchBooks(currentSearchTerm, currentCategory, currentAuthor, ${currentPage + 1})">Next</a></li>`;
    }
    
    html += '</ul></nav></div>';
    return html;
}

// Get my books with AJAX
function getMyBooks(status = '', page = 1) {
    const container = document.getElementById('myBooksContainer');
    if (container) {
        container.innerHTML = '<div class="text-center"><div class="loading-spinner"></div><p>Loading...</p></div>';
    }
    
    const params = new URLSearchParams({
        status: status,
        page: page
    });
    
    fetch('../student/ajax/get_my_books.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMyBooks(data);
            } else {
                showError('Failed to load books: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error loading my books:', error);
            showError('Failed to load books. Please try again.');
        });
}

// Display my books
function displayMyBooks(data) {
    const container = document.getElementById('myBooksContainer');
    if (!container) return;
    
    if (data.books.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No books found</h6>
                <p class="text-muted">You haven't borrowed any books yet.</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += `
        <thead>
            <tr>
                <th>Book Details</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Fine</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    data.books.forEach(book => {
        const statusClass = book.status === 'overdue' ? 'text-danger' : 
                           book.status === 'issued' && book.days_remaining <= 3 ? 'text-warning' : 'text-success';
        
        // Generate thumbnail URL
        const thumbnailUrl = `../uploads/thumbnails/book_${md5(book.title + book.author + (book.category_name || '') + 'small')}.png`;
        
        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${thumbnailUrl}" 
                             class="me-3 rounded" 
                             style="width: 50px; height: 60px; object-fit: cover;" 
                             alt="Book Cover">
                        <div>
                            <strong>${escapeHtml(book.title)}</strong><br>
                            <small class="text-muted">by ${escapeHtml(book.author)}</small>
                        </div>
                    </div>
                </td>
                <td>${formatDate(book.issue_date)}</td>
                <td class="${statusClass}">
                    ${formatDate(book.due_date)}
                    ${book.days_remaining < 0 ? `<br><small class="text-danger">${Math.abs(book.days_remaining)} days overdue</small>` : ''}
                    ${book.days_remaining >= 0 && book.days_remaining <= 3 ? `<br><small class="text-warning">${book.days_remaining} days remaining</small>` : ''}
                </td>
                <td>
                    <span class="badge ${book.status === 'overdue' ? 'bg-danger' : book.status === 'issued' ? 'bg-success' : 'bg-secondary'}">
                        ${book.display_status}
                    </span>
                </td>
                <td>
                    ${book.fine_amount > 0 ? `<span class="text-danger">₹${book.fine_amount.toFixed(2)}</span>` : '-'}
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    
    // Add pagination if needed
    if (data.total_pages > 1) {
        html += generateMyBooksPagination(data.page, data.total_pages, data.total);
    }
    
    container.innerHTML = html;
}

// Generate pagination for my books
function generateMyBooksPagination(currentPage, totalPages, totalItems) {
    let html = `
        <div class="d-flex justify-content-between align-items-center mt-4">
            <small class="text-muted">Showing ${((currentPage - 1) * 10) + 1} to ${Math.min(currentPage * 10, totalItems)} of ${totalItems} books</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
    `;
    
    if (currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="getMyBooks(currentStatus, ${currentPage - 1})">Previous</a></li>`;
    }
    
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="getMyBooks(currentStatus, ${i})">${i}</a>
            </li>
        `;
    }
    
    if (currentPage < totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="getMyBooks(currentStatus, ${currentPage + 1})">Next</a></li>`;
    }
    
    html += '</ul></nav></div>';
    return html;
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Simple MD5-like hash function for thumbnail generation
function md5(str) {
    let hash = 0;
    if (str.length === 0) return hash.toString();
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash).toString();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN');
}

function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid') || document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
}

// Global variables for search state
let currentSearchTerm = '';
let currentCategory = '';
let currentAuthor = '';
let currentStatus = '';

// Initialize student portal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard data every 5 minutes
    setInterval(refreshDashboardData, 5 * 60 * 1000);
    
    // Initialize search functionality
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            const categorySelect = document.getElementById('categorySelect');
            const authorInput = document.getElementById('authorInput');
            
            currentSearchTerm = searchInput.value;
            currentCategory = categorySelect ? categorySelect.value : '';
            currentAuthor = authorInput ? authorInput.value : '';
            
            searchBooks(currentSearchTerm, currentCategory, currentAuthor, 1);
        });
    }
    
    // Initialize filter buttons
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const status = this.dataset.status || '';
            currentStatus = status;
            getMyBooks(status, 1);
        });
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    

}); 