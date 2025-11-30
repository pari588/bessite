<?php
// Directories to exclude from conversion
$excludeDirs = ['xadmin', 'Old rubbish'];

// Recursive function to scan and convert images
function convertToWebP($dir, $excludeDirs) {
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $path = "$dir/$file";

        // Skip excluded directories
        if (is_dir($path)) {
            if (in_array($file, $excludeDirs)) continue;
            convertToWebP($path, $excludeDirs); // Recursive call
        } else {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $webpFile = "$path.webp";
                
                if (file_exists($webpFile)) continue; // Skip if already converted
                
                if ($ext === 'png') {
                    $img = imagecreatefrompng($path);
                } else {
                    $img = imagecreatefromjpeg($path);
                }

                if ($img) {
                    imagewebp($img, $webpFile, 80);
                    imagedestroy($img);
                    echo "Converted: $path → $webpFile<br>";
                } else {
                    echo "Failed to convert: $path<br>";
                }
            }
        }
    }
}

// Set your base directory
$baseDirectory = __DIR__; // root directory

// Start conversion
convertToWebP($baseDirectory, $excludeDirs);
?>
