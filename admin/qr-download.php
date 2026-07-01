<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'png';

if (!$id) {
    header('Location: manage-qr.php');
    exit;
}

// Get QR code
$stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE id = ?");
$stmt->execute([$id]);
$qr = $stmt->fetch();

if (!$qr) {
    header('Location: manage-qr.php');
    exit;
}

// Get design settings
$design = json_decode($qr['design_settings'] ?? '{}', true);
$color = $design['color'] ?? '#8B0000';
$bgColor = $design['background'] ?? '#FFFFFF';
$size = $design['size'] ?? 300;
$padding = $design['padding'] ?? 25;
$pattern = $design['pattern'] ?? 'dots';
$corner = $design['corner'] ?? 'square';

$url = APP_URL . '/verify?token=' . $qr['token'];

// Generate QR code using QRCode.js via command line or API
// Since we can't use QRCode.js on server side, we'll use an API

if ($format === 'png') {
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="qr-' . $qr['id'] . '.png"');
    
    // Use Google Charts API as fallback (supports color customization)
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/";
    $params = http_build_query([
        'size' => $size . 'x' . $size,
        'data' => $url,
        'color' => str_replace('#', '', $color),
        'bgcolor' => str_replace('#', '', $bgColor),
        'margin' => ceil($padding / 10)
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $qrUrl . '?' . $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data) {
        echo $data;
    } else {
        // Fallback: Try another API
        $qrUrl = "https://chart.googleapis.com/chart?cht=qr&chs=" . $size . "x" . $size . "&chl=" . urlencode($url) . "&choe=UTF-8";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $qrUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        
        if ($data) {
            echo $data;
        } else {
            // Last resort: Create a simple image with text
            $im = imagecreate($size, $size);
            $bgColorRGB = hexdec(str_replace('#', '', $bgColor));
            $colorRGB = hexdec(str_replace('#', '', $color));
            
            $bg = imagecolorallocate($im, ($bgColorRGB >> 16) & 0xFF, ($bgColorRGB >> 8) & 0xFF, $bgColorRGB & 0xFF);
            $txtColor = imagecolorallocate($im, ($colorRGB >> 16) & 0xFF, ($colorRGB >> 8) & 0xFF, $colorRGB & 0xFF);
            
            imagefilledrectangle($im, 0, 0, $size, $size, $bg);
            
            // Draw a simple QR-like pattern
            $blockSize = 10;
            for ($y = 0; $y < $size; $y += $blockSize) {
                for ($x = 0; $x < $size; $x += $blockSize) {
                    if (($x + $y) % 3 == 0) {
                        imagefilledrectangle($im, $x, $y, $x + $blockSize - 2, $y + $blockSize - 2, $txtColor);
                    }
                }
            }
            
            // Add text
            $text = "QR: " . substr($qr['token'], 0, 8);
            imagestring($im, 5, 10, $size/2 - 10, $text, $txtColor);
            
            imagepng($im);
            imagedestroy($im);
        }
    }
    exit;
}

if ($format === 'svg') {
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: attachment; filename="qr-' . $qr['id'] . '.svg"');
    
    // Use SVG generation via API
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/";
    $params = http_build_query([
        'size' => $size . 'x' . $size,
        'data' => $url,
        'color' => str_replace('#', '', $color),
        'bgcolor' => str_replace('#', '', $bgColor),
        'format' => 'svg',
        'margin' => ceil($padding / 10)
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $qrUrl . '?' . $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data) {
        echo $data;
    } else {
        // Create simple SVG fallback
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        echo '<rect width="100%" height="100%" fill="' . $bgColor . '"/>';
        echo '<text x="50%" y="50%" text-anchor="middle" fill="' . $color . '" font-size="14">QR: ' . substr($qr['token'], 0, 8) . '</text>';
        echo '</svg>';
    }
    exit;
}

// If format not supported, redirect back
header('Location: qr-customize.php?id=' . $id);
exit;
?>