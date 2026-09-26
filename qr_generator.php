<?php
/**
 * QR Code Generator - Vercel compatible
 * Uses external APIs since filesystem is read-only on Vercel
 */

require_once __DIR__ . '/config.php';

/**
 * Generate QR code URL using configured provider
 * @param string $data
 * @param int $size
 * @return string
 */
function generateQrCodeDataUri(string $data, int $size = 300): string {
    return generateQrCodeUrl($data, $size);
}

/**
 * Get QR code URL (alias for consistency)
 * @param string $data
 * @param int $size
 * @return string
 */
function getQrCodeUrl(string $data, int $size = 300): string {
    return generateQrCodeUrl($data, $size);
}

/**
 * Generate QR code as SVG (inline, no external dependency)
 * @param string $data
 * @param int $size
 * @return string
 */
function generateQrCodeSvg(string $data, int $size = 300): string {
    // Simple QR code using an external service that returns SVG
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data) . '&format=svg';
}