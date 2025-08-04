<?php
/**
 * CSS-based Placeholder Generator (No GD Required)
 * Creates HTML/CSS placeholder when GD extension is not available
 */

// Get parameters
$title = isset($_GET['title']) ? urldecode($_GET['title']) : 'No Title';
$author = isset($_GET['author']) ? urldecode($_GET['author']) : 'Unknown Author';
$size = isset($_GET['size']) ? $_GET['size'] : 'medium';

// Set dimensions based on size
switch ($size) {
    case 'small':
        $width = 120;
        $height = 160;
        $fontSize = '12px';
        $authorSize = '10px';
        $title = substr($title, 0, 12);
        $author = substr($author, 0, 10);
        break;
    case 'large':
        $width = 300;
        $height = 400;
        $fontSize = '18px';
        $authorSize = '14px';
        $title = substr($title, 0, 25);
        $author = substr($author, 0, 20);
        break;
    default: // medium
        $width = 200;
        $height = 280;
        $fontSize = '14px';
        $authorSize = '12px';
        $title = substr($title, 0, 18);
        $author = substr($author, 0, 15);
        break;
}

// Set content type to SVG
header('Content-Type: image/svg+xml');

// Generate SVG placeholder
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg width="<?php echo $width; ?>" height="<?php echo $height; ?>" xmlns="http://www.w3.org/2000/svg">
    <!-- Background -->
    <rect width="100%" height="100%" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
    
    <!-- Top accent bar -->
    <rect width="100%" height="8" fill="#007bff"/>
    
    <!-- Book icon -->
    <rect x="<?php echo $width * 0.3; ?>" y="<?php echo $height * 0.25; ?>" 
          width="<?php echo $width * 0.4; ?>" height="<?php echo $height * 0.3; ?>" 
          fill="#dee2e6" stroke="#6c757d" stroke-width="1"/>
    
    <!-- Book pages lines -->
    <?php for ($i = 0; $i < 4; $i++): ?>
        <line x1="<?php echo $width * 0.35; ?>" y1="<?php echo ($height * 0.25) + 15 + ($i * 8); ?>" 
              x2="<?php echo $width * 0.65; ?>" y2="<?php echo ($height * 0.25) + 15 + ($i * 8); ?>" 
              stroke="#6c757d" stroke-width="2"/>
    <?php endfor; ?>
    
    <!-- Book title -->
    <text x="50%" y="<?php echo $height * 0.65; ?>" 
          text-anchor="middle" 
          font-family="Arial, sans-serif" 
          font-size="<?php echo $fontSize; ?>" 
          font-weight="bold"
          fill="#495057">
        <?php echo htmlspecialchars($title); ?>
    </text>
    
    <!-- Author -->
    <text x="50%" y="<?php echo $height * 0.72; ?>" 
          text-anchor="middle" 
          font-family="Arial, sans-serif" 
          font-size="<?php echo $authorSize; ?>" 
          fill="#6c757d">
        by <?php echo htmlspecialchars($author); ?>
    </text>
    
    <!-- Library name -->
    <text x="50%" y="<?php echo $height - 15; ?>" 
          text-anchor="middle" 
          font-family="Arial, sans-serif" 
          font-size="10px" 
          fill="#007bff">
        Digital Library
    </text>
</svg>
