<?php
/**
 * Simple QR Code Generator using Google Chart API (no library needed)
 * Fallback if phpqrcode is not available
 */

function generateQrCodeDataUri(string $data, int $size = 300): string {
    $url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
    return $url;
}

/**
 * Generate QR code as base64 data URI (for embedding directly in HTML)
 * Uses a simple approach - returns external URL
 */
function getQrCodeUrl(string $data, int $size = 300): string {
    // Primary: Google Chart API (reliable, no key needed)
    return 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
    
    // Alternative: qrserver.com
    // return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
}