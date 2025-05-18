<?php
// Ensure we only return JSON and no PHP errors/warnings are included in the output
header('Content-Type: application/json');

// Configure error handling to prevent PHP from outputting errors directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Enable debugging for development if needed (comment this out in production)
// define('DEBUG_MODE', true);

// Function to handle errors consistently
function return_error($message, $details = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($details !== null && defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['details'] = $details;
    }
    
    echo json_encode($response);
    exit();
}

// Function to return success
function return_success($data) {
    // Make sure we're returning a proper array
    if (!is_array($data)) {
        $data = ['message' => $data];
    }
    
    $data['success'] = true;
    
    // Ensure JSON is valid with proper encoding
    $json = json_encode($data);
    
    // Check for JSON encoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        return_error('JSON encoding error: ' . json_last_error_msg(), $data);
    }
    
    echo $json;
    exit();
}

// Set up error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    return_error("PHP Error: $errstr", [
        'file' => $errfile,
        'line' => $errline
    ]);
    return true;
});

session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    return_error('دەبێت خۆت تۆمار بکەیت بۆ ئەنجامدانی ئەم کردارە.');
}

// Check permission to add or edit transactions (since receipt upload is part of adding/editing)
if (!hasPermission('add_transaction') && !hasPermission('edit_transaction')) {
    return_error('ڕێگەپێدانی ناتەواو. تۆ ناتوانیت پسووڵەی مامەڵە ئەپڵۆد بکەیت.');
}

// Check if uploads directory exists and is writable
$upload_dir = '../../uploads/receipts/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        return_error('نەتوانرا دایرێکتۆری ئەپلۆد دروست بکرێت. تکایە دڵنیا بەوە کە ڕێگەپێدانی نووسین هەیە.');
    }
} elseif (!is_writable($upload_dir)) {
    return_error('دایرێکتۆری ئەپلۆد نووسینی بۆ ناکرێت. تکایە ڕێگەپێدانەکان بپشکنە.');
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'هەڵەیەک ڕوویدا لە کاتی ئەپلۆدکردنی فایل.';
    
    // Provide more detailed error based on the error code
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'قەبارەی فایل زۆر گەورەیە.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'فایلەکە بە تەواوی ئەپلۆد نەکرا.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'هیچ فایلێک ئەپلۆد نەکرا.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'بوونی کێشەیەک لە سێرڤەر - فۆڵدەری کاتی نییە.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'نەتوانرا فایلەکە بنووسرێت لەسەر دیسک.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'ئەپلۆدکردنی فایل ڕاگیرا بەهۆی درێژکراوەیەکەوە.';
                break;
        }
    }
    
    return_error($error_message);
}

// Define allowed file types
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

// Check file type
if (!in_array($_FILES['file']['type'], $allowed_types)) {
    return_error('جۆری فایل پشتگیری ناکرێت. تەنها وێنەکان ڕێگەپێدراون.');
}

// Check file size (10 MB max)
$max_size = 10 * 1024 * 1024; // 10 MB in bytes
if ($_FILES['file']['size'] > $max_size) {
    return_error('قەبارەی فایل زۆر گەورەیە. زۆرترین قەبارە 10 مێگابایتە.');
}

// Get the next sequential number for this file
try {
    $conn = Database::getInstance();
    
    // Get the current largest file number from the database or create a sequence table if needed
    $stmt = $conn->prepare("
        CREATE TABLE IF NOT EXISTS file_sequences (
            sequence_name VARCHAR(50) PRIMARY KEY,
            current_value INT NOT NULL DEFAULT 0
        )
    ");
    $stmt->execute();
    
    // Try to get the current receipt sequence value
    $stmt = $conn->prepare("
        SELECT current_value FROM file_sequences WHERE sequence_name = 'receipt_sequence'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $nextSequence = $result['current_value'] + 1;
        
        // Update the sequence
        $stmt = $conn->prepare("
            UPDATE file_sequences SET current_value = :next_val WHERE sequence_name = 'receipt_sequence'
        ");
        $stmt->bindParam(':next_val', $nextSequence);
        $stmt->execute();
    } else {
        // Initialize the sequence if it doesn't exist
        $nextSequence = 1;
        $stmt = $conn->prepare("
            INSERT INTO file_sequences (sequence_name, current_value) VALUES ('receipt_sequence', :current_val)
        ");
        $stmt->bindParam(':current_val', $nextSequence);
        $stmt->execute();
    }
    
    // Generate a unique filename with sequence
    $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $unique_filename = 'receipt_' . uniqid() . '_' . time() . '_' . $nextSequence . '.' . $file_extension;
    $temp_path = $_FILES['file']['tmp_name'];
    $upload_path = $upload_dir . $unique_filename;
    
    // Check if the file is already compressed (client-side)
    $is_client_compressed = isset($_POST['client_compressed']) && $_POST['client_compressed'] === 'true';
    
    if ($is_client_compressed || $_FILES['file']['size'] < 500 * 1024) { // Already compressed or small file
        // Move the file directly without compression
        if (!move_uploaded_file($temp_path, $upload_path)) {
            return_error('نەتوانرا فایلەکە جووڵێندرێت بۆ شوێنی مەبەست.');
        }
        
        $relative_path = 'uploads/receipts/' . $unique_filename;
        
        $response = [
            'message' => 'فایل بە سەرکەوتوویی ئەپلۆد کرا (ژمارە: ' . $nextSequence . ').',
            'file_path' => $relative_path,
            'compressed_size' => filesize($upload_path),
            'client_compressed' => $is_client_compressed
        ];
        
        return_success($response);
    } else {
        // Compress and resize the image before saving
        $original_size = filesize($temp_path);
        $max_width = 1200;
        $max_height = 1200;
        $quality = 50;
        
        // Get image info
        $image_info = @getimagesize($temp_path);
        if ($image_info === false) {
            return_error('نەتوانرا زانیاری وێنەکە بخوێندرێتەوە. وێنەکە دەشێت تێکچووبێت.');
        }
        
        list($width, $height, $type) = $image_info;
        
        // Calculate new dimensions while maintaining aspect ratio
        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);
        } else {
            $new_width = $width;
            $new_height = $height;
        }
        
        // Create new image with new dimensions
        $new_image = @imagecreatetruecolor($new_width, $new_height);
        if (!$new_image) {
            return_error('نەتوانرا وێنەی نوێ دروست بکرێت.');
        }
        
        // Preserve transparency for PNG images
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // Load the source image based on its type
        $source = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = @imagecreatefromjpeg($temp_path);
                break;
            case IMAGETYPE_PNG:
                $source = @imagecreatefrompng($temp_path);
                break;
            case IMAGETYPE_GIF:
                $source = @imagecreatefromgif($temp_path);
                break;
        }
        
        if (!$source) {
            // If we can't process the image, try to move it without compression
            if (move_uploaded_file($temp_path, $upload_path)) {
                $relative_path = 'uploads/receipts/' . $unique_filename;
                $compressed_size = filesize($upload_path);
                
                $response = [
                    'message' => 'فایل بە سەرکەوتوویی ئەپلۆد کرا (ژمارە: ' . $nextSequence . '). نەتوانرا کۆمپرێس بکرێت.',
                    'file_path' => $relative_path,
                    'original_size' => $original_size,
                    'compressed_size' => $compressed_size
                ];
                
                return_success($response);
            } else {
                return_error('نەتوانرا وێنەکە بخوێندرێتەوە یان فایلەکە جووڵێندرێت بۆ شوێنی مەبەست.');
            }
        } else {
            // Resize the image
            imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // Save the resized image with appropriate quality settings
            $saved = false;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $saved = imagejpeg($new_image, $upload_path, $quality);
                    break;
                case IMAGETYPE_PNG:
                    // For PNG, we use compression level 9 (maximum) since PNG is already lossless
                    $saved = imagepng($new_image, $upload_path, 9);
                    break;
                case IMAGETYPE_GIF:
                    $saved = imagegif($new_image, $upload_path);
                    break;
            }
            
            // Free up memory
            imagedestroy($source);
            imagedestroy($new_image);
            
            if (!$saved) {
                return_error('نەتوانرا وێنەی کۆمپرێسکراو خەزن بکرێت.');
            }
            
            $relative_path = 'uploads/receipts/' . $unique_filename;
            $compressed_size = filesize($upload_path);
            $savings = $original_size - $compressed_size;
            $percent_saved = round(($savings / $original_size) * 100);
            
            $response = [
                'message' => 'فایل بە سەرکەوتوویی ئەپلۆد کرا (ژمارە: ' . $nextSequence . '). ' . 
                            'قەبارە کەمکرایەوە بە ڕێژەی: ' . $percent_saved . '%.',
                'file_path' => $relative_path,
                'original_size' => $original_size,
                'compressed_size' => $compressed_size
            ];
            
            return_success($response);
        }
    }
} catch (Exception $e) {
    return_error('هەڵەیەک ڕوویدا: ' . $e->getMessage());
}

return_success($response); 