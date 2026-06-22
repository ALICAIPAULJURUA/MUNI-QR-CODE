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

$design = json_decode($qr['design_settings'] ?? '{}', true);
$color = $design['color'] ?? '#8B0000';
$bgColor = $design['background'] ?? '#FFFFFF';
$size = $design['size'] ?? 300;
$url = APP_URL . '/verify?token=' . $qr['token'];

// Generate QR code using QRCode library
// For simplicity, we'll use a fallback method
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="qr-' . $qr['id'] . '.png"');

// Simple QR generation using Google Charts API (fallback)
// In production, use a proper QR library
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url) . "&color=" . str_replace('#', '', $color) . "&bgcolor=" . str_replace('#', '', $bgColor);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $qrUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
curl_close($ch);

echo $data;
exit;