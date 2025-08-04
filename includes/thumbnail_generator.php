<?php
/**
 * Thumbnail Generator - Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

// Unused functions removed - we now use direct PHP/SVG generation

/**
 * Generate thumbnail for book data
 */
function generateBookThumbnailFromData($bookData, $size = 'medium') {
    // 1. If an uploaded book cover exists and is accessible, use it first
    if (!empty($bookData['book_image'])) {
        $coverPath = 'uploads/' . ltrim($bookData['book_image'], '/');
        // Ensure the file actually exists on disk to prevent broken images
        if (file_exists(__DIR__ . '/../' . $coverPath)) {
            // Always return absolute URL so it works from any sub-directory (admin/, student/, etc.)
            return APP_URL . $coverPath;
        }
    }

    // 2. Generate placeholder with book details
    $title = $bookData['title'] ?? 'No Title';
    $author = $bookData['author'] ?? 'Unknown Author';
    
    // URL encode the parameters for the PHP generator
    $titleParam = urlencode($title);
    $authorParam = urlencode($author);
    $sizeParam = urlencode($size);
    
    // Check if we should use CSS fallback instead of PHP generation
    if (!extension_loaded('gd')) {
        // Use CSS-based placeholder
        return APP_URL . "assets/images/css_placeholder.php?title={$titleParam}&author={$authorParam}&size={$sizeParam}";
    }
    
    return APP_URL . "assets/images/generate_placeholder.php?title={$titleParam}&author={$authorParam}&size={$sizeParam}";
}
?> 