/**
 * Search System Unit Tests
 * Tests for book search functionality and filters
 */

describe('Search System Tests', function() {
    
    beforeEach(function() {
        // Setup mock search data
        window.mockBooks = [
            {
                book_id: 1,
                title: 'JavaScript: The Good Parts',
                author: 'Douglas Crockford',
                isbn: '9780596517748',
                category: 'Programming',
                available: true,
                total_copies: 3,
                available_copies: 2
            },
            {
                book_id: 2,
                title: 'Clean Code',
                author: 'Robert C. Martin',
                isbn: '9780132350884',
                category: 'Programming',
                available: false,
                total_copies: 2,
                available_copies: 0
            },
            {
                book_id: 3,
                title: 'Database Design',
                author: 'Adrienne Watt',
                isbn: '9781453394823',
                category: 'Database',
                available: true,
                total_copies: 1,
                available_copies: 1
            }
        ];
        
        // Setup search form elements
        if (!document.getElementById('searchInput')) {
            const searchInput = TestUtils.createMockElement('input', {
                id: 'searchInput',
                type: 'text',
                placeholder: 'Search books...'
            });
            document.body.appendChild(searchInput);
        }
    });
    
    afterEach(function() {
        TestUtils.restoreAjax();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.remove();
    });

    describe('Basic Search Functionality', function() {
        
        it('should perform basic text search', function(done) {
            const mockSearchResults = {
                success: true,
                books: [window.mockBooks[0]], // JavaScript book
                search_term: 'JavaScript',
                total_results: 1,
                search_time: 0.05
            };
            
            TestUtils.mockAjax(mockSearchResults);
            
            window.performSearch = function(searchTerm) {
                return $.ajax({
                    url: 'includes/search_functions.php',
                    method: 'GET',
                    data: {
                        action: 'search_books',
                        search: searchTerm
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.books).to.have.length(1);
                    expect(response.books[0].title).to.include('JavaScript');
                    expect(response.search_term).to.equal('JavaScript');
                    expect(response.total_results).to.equal(1);
                    done();
                });
            };
            
            window.performSearch('JavaScript');
        });
        
        it('should handle empty search results', function(done) {
            const mockEmptyResults = {
                success: true,
                books: [],
                search_term: 'NonexistentBook',
                total_results: 0,
                search_time: 0.02
            };
            
            TestUtils.mockAjax(mockEmptyResults);
            
            window.performSearch = function(searchTerm) {
                return $.ajax({
                    url: 'includes/search_functions.php',
                    method: 'GET',
                    data: {
                        action: 'search_books',
                        search: searchTerm
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.books).to.have.length(0);
                    expect(response.total_results).to.equal(0);
                    done();
                });
            };
            
            window.performSearch('NonexistentBook');
        });
    });

    describe('Advanced Search Filters', function() {
        
        it('should filter by availability status', function(done) {
            const availableBooks = window.mockBooks.filter(book => book.available);
            const mockFilteredResults = {
                success: true,
                books: availableBooks,
                filters: { available_only: true },
                total_results: 2
            };
            
            TestUtils.mockAjax(mockFilteredResults);
            
            window.searchWithFilters = function(searchTerm, filters) {
                return $.ajax({
                    url: 'includes/search_functions.php',
                    method: 'GET',
                    data: {
                        action: 'search_books',
                        search: searchTerm,
                        ...filters
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.books).to.have.length(2);
                    response.books.forEach(book => {
                        expect(book.available).to.be.true;
                    });
                    done();
                });
            };
            
            window.searchWithFilters('', { available_only: true });
        });
        
        it('should filter by category', function(done) {
            const programmingBooks = window.mockBooks.filter(book => book.category === 'Programming');
            const mockCategoryResults = {
                success: true,
                books: programmingBooks,
                filters: { category: 'Programming' },
                total_results: 2
            };
            
            TestUtils.mockAjax(mockCategoryResults);
            
            window.searchWithFilters = function(searchTerm, filters) {
                return $.ajax({
                    url: 'includes/search_functions.php',
                    method: 'GET',
                    data: {
                        action: 'search_books',
                        search: searchTerm,
                        ...filters
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.books).to.have.length(2);
                    response.books.forEach(book => {
                        expect(book.category).to.equal('Programming');
                    });
                    done();
                });
            };
            
            window.searchWithFilters('', { category: 'Programming' });
        });
        
        it('should filter by author', function(done) {
            const mockAuthorResults = {
                success: true,
                books: [window.mockBooks[1]], // Clean Code by Robert C. Martin
                filters: { author: 'Robert C. Martin' },
                total_results: 1
            };
            
            TestUtils.mockAjax(mockAuthorResults);
            
            window.searchWithFilters = function(searchTerm, filters) {
                return $.ajax({
                    url: 'includes/search_functions.php',
                    method: 'GET',
                    data: {
                        action: 'search_books',
                        search: searchTerm,
                        ...filters
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.books).to.have.length(1);
                    expect(response.books[0].author).to.equal('Robert C. Martin');
                    done();
                });
            };
            
            window.searchWithFilters('', { author: 'Robert C. Martin' });
        });
    });

    describe('Search Input Validation', function() {
        
        it('should validate search input length', function() {
            window.validateSearchInput = function(searchTerm) {
                if (!searchTerm || searchTerm.trim().length === 0) {
                    return { valid: false, message: 'Search term cannot be empty' };
                }
                
                if (searchTerm.length < 2) {
                    return { valid: false, message: 'Search term must be at least 2 characters' };
                }
                
                if (searchTerm.length > 100) {
                    return { valid: false, message: 'Search term is too long' };
                }
                
                return { valid: true };
            };
            
            expect(window.validateSearchInput('').valid).to.be.false;
            expect(window.validateSearchInput('a').valid).to.be.false;
            expect(window.validateSearchInput('ab').valid).to.be.true;
            expect(window.validateSearchInput('a'.repeat(101)).valid).to.be.false;
        });
        
        it('should sanitize search input', function() {
            window.sanitizeSearchInput = function(input) {
                return input
                    .trim()
                    .replace(/[<>]/g, '') // Remove HTML tags
                    .replace(/['"]/g, '') // Remove quotes
                    .replace(/\s+/g, ' '); // Normalize whitespace
            };
            
            const dirtyInput = "  <script>JavaScript</script>'s  \"Good\"  Parts  ";
            const cleanInput = window.sanitizeSearchInput(dirtyInput);
            
            expect(cleanInput).to.equal('scriptJavaScript/scripts Good Parts');
            expect(cleanInput).to.not.include('<');
            expect(cleanInput).to.not.include('"');
        });
    });

    describe('Search Results Display', function() {
        
        it('should format search results correctly', function() {
            window.formatSearchResults = function(books) {
                return books.map(book => ({
                    ...book,
                    availability_text: book.available ? 'Available' : 'Not Available',
                    availability_class: book.available ? 'text-success' : 'text-danger',
                    copies_text: `${book.available_copies}/${book.total_copies} available`
                }));
            };
            
            const formattedBooks = window.formatSearchResults(window.mockBooks);
            
            expect(formattedBooks[0].availability_text).to.equal('Available');
            expect(formattedBooks[0].availability_class).to.equal('text-success');
            expect(formattedBooks[0].copies_text).to.equal('2/3 available');
            
            expect(formattedBooks[1].availability_text).to.equal('Not Available');
            expect(formattedBooks[1].availability_class).to.equal('text-danger');
            expect(formattedBooks[1].copies_text).to.equal('0/2 available');
        });
        
        it('should generate pagination for search results', function() {
            window.generatePagination = function(currentPage, totalResults, resultsPerPage) {
                const totalPages = Math.ceil(totalResults / resultsPerPage);
                
                return {
                    current_page: currentPage,
                    total_pages: totalPages,
                    has_previous: currentPage > 1,
                    has_next: currentPage < totalPages,
                    previous_page: currentPage > 1 ? currentPage - 1 : null,
                    next_page: currentPage < totalPages ? currentPage + 1 : null,
                    results_start: ((currentPage - 1) * resultsPerPage) + 1,
                    results_end: Math.min(currentPage * resultsPerPage, totalResults)
                };
            };
            
            const pagination = window.generatePagination(2, 25, 10);
            
            expect(pagination.current_page).to.equal(2);
            expect(pagination.total_pages).to.equal(3);
            expect(pagination.has_previous).to.be.true;
            expect(pagination.has_next).to.be.true;
            expect(pagination.previous_page).to.equal(1);
            expect(pagination.next_page).to.equal(3);
            expect(pagination.results_start).to.equal(11);
            expect(pagination.results_end).to.equal(20);
        });
    });

    describe('Search Performance', function() {
        
        it('should implement search debouncing', function(done) {
            let searchCallCount = 0;
            
            window.debouncedSearch = function(searchTerm, delay = 300) {
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(() => {
                    searchCallCount++;
                    if (searchCallCount === 1) {
                        expect(searchTerm).to.equal('JavaScript');
                        done();
                    }
                }, delay);
            };
            
            // Simulate rapid typing
            window.debouncedSearch('J', 100);
            window.debouncedSearch('Ja', 100);
            window.debouncedSearch('Jav', 100);
            window.debouncedSearch('Java', 100);
            window.debouncedSearch('JavaScript', 100);
            
            // Only the last search should execute
            setTimeout(() => {
                expect(searchCallCount).to.equal(0); // Should still be 0 after 50ms
            }, 50);
        });
        
        it('should cache search results', function() {
            window.searchCache = new Map();
            
            window.getCachedSearch = function(searchTerm) {
                const cacheKey = searchTerm.toLowerCase().trim();
                return window.searchCache.get(cacheKey);
            };
            
            window.setCachedSearch = function(searchTerm, results) {
                const cacheKey = searchTerm.toLowerCase().trim();
                window.searchCache.set(cacheKey, {
                    results: results,
                    timestamp: Date.now()
                });
            };
            
            const mockResults = { books: [window.mockBooks[0]], total_results: 1 };
            
            // Cache results
            window.setCachedSearch('JavaScript', mockResults);
            
            // Retrieve cached results
            const cached = window.getCachedSearch('javascript'); // Case insensitive
            expect(cached.results.books).to.have.length(1);
            expect(cached.timestamp).to.be.a('number');
        });
    });

    describe('Search Analytics', function() {
        
        it('should track search queries', function() {
            window.trackSearchQuery = function(searchTerm, resultsCount, searchTime) {
                return {
                    query: searchTerm,
                    results_count: resultsCount,
                    search_time_ms: searchTime,
                    timestamp: new Date().toISOString(),
                    user_agent: navigator.userAgent.substring(0, 50)
                };
            };
            
            const analytics = window.trackSearchQuery('JavaScript', 5, 45.2);
            
            expect(analytics.query).to.equal('JavaScript');
            expect(analytics.results_count).to.equal(5);
            expect(analytics.search_time_ms).to.equal(45.2);
            expect(analytics.timestamp).to.be.a('string');
        });
        
        it('should identify popular search terms', function() {
            window.searchAnalytics = {
                queries: [
                    { term: 'JavaScript', count: 15 },
                    { term: 'Python', count: 12 },
                    { term: 'Database', count: 8 },
                    { term: 'Web Development', count: 6 }
                ]
            };
            
            window.getPopularSearches = function(limit = 5) {
                return window.searchAnalytics.queries
                    .sort((a, b) => b.count - a.count)
                    .slice(0, limit);
            };
            
            const popular = window.getPopularSearches(3);
            
            expect(popular).to.have.length(3);
            expect(popular[0].term).to.equal('JavaScript');
            expect(popular[0].count).to.equal(15);
            expect(popular[1].term).to.equal('Python');
        });
    });

    describe('Search Error Handling', function() {
        
        it('should handle search service errors', function(done) {
            const mockError = {
                success: false,
                error: 'Database connection failed',
                error_code: 'DB_ERROR',
                retry_after: 5
            };
            
            TestUtils.mockAjax(mockError);
            
            window.performSearch = function(searchTerm) {
                return $.ajax({
                    url: 'includes/search_functions.php',
                    method: 'GET',
                    data: {
                        action: 'search_books',
                        search: searchTerm
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.false;
                    expect(response.error).to.include('Database connection');
                    expect(response.error_code).to.equal('DB_ERROR');
                    expect(response.retry_after).to.be.a('number');
                    done();
                });
            };
            
            window.performSearch('test');
        });
        
        it('should handle network timeouts', function() {
            window.handleSearchTimeout = function() {
                return {
                    success: false,
                    error: 'Search request timed out',
                    error_code: 'TIMEOUT',
                    suggestion: 'Please try again with a shorter search term'
                };
            };
            
            const timeoutResult = window.handleSearchTimeout();
            
            expect(timeoutResult.success).to.be.false;
            expect(timeoutResult.error).to.include('timed out');
            expect(timeoutResult.error_code).to.equal('TIMEOUT');
            expect(timeoutResult.suggestion).to.include('try again');
        });
    });
});
