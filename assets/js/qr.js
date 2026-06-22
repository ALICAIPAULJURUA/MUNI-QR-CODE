/**
 * Muni University QR Verification System
 * QR Code Generation JavaScript
 */

// QR Code Generator using QRCode.js
$(document).ready(function() {
    'use strict';
    
    // Initialize QR Code generation on load
    if ($('#qr-code-display').length) {
        generateQRCode();
    }
    
    // Generate QR Code with customization
    function generateQRCode() {
        const url = $('#qr-url').val() || 'https://verify.muni.ac.ug/profile/vc';
        const size = parseInt($('#qr-size').val()) || 300;
        const colorDark = $('#qr-color-dark').val() || '#8B0000';
        const colorLight = $('#qr-color-light').val() || '#FFFFFF';
        const container = $('#qr-code-display');
        
        // Clear container
        container.empty();
        
        // Generate QR Code
        try {
            // Use QRCode.js library
            const qr = new QRCode(container[0], {
                text: url,
                width: size,
                height: size,
                colorDark: colorDark,
                colorLight: colorLight,
                correctLevel: QRCode.CorrectLevel.H // High error correction for logo
            });
            
            // Add logo overlay if exists
            const logoUrl = $('#logo-url').val();
            if (logoUrl) {
                addLogoToQR(container, logoUrl);
            }
            
            // Store generated QR data
            const canvas = container.find('canvas');
            if (canvas.length) {
                $('#qr-data-url').val(canvas[0].toDataURL('image/png'));
            }
            
        } catch (error) {
            console.error('QR Code generation failed:', error);
            container.html('<div class="alert alert-danger">Failed to generate QR Code. Please try again.</div>');
        }
    }
    
    // Add logo overlay to QR code
    function addLogoToQR(container, logoUrl) {
        const canvas = container.find('canvas');
        if (!canvas.length) return;
        
        const ctx = canvas[0].getContext('2d');
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            const size = canvas[0].width;
            const logoSize = size * 0.25; // Logo takes 25% of QR size
            const x = (size - logoSize) / 2;
            const y = (size - logoSize) / 2;
            
            // Draw white background for logo
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(x - 5, y - 5, logoSize + 10, logoSize + 10);
            
            // Draw circular mask
            ctx.beginPath();
            ctx.arc(size/2, size/2, logoSize/2 + 5, 0, Math.PI * 2);
            ctx.fillStyle = '#FFFFFF';
            ctx.fill();
            
            // Draw logo
            ctx.drawImage(img, x, y, logoSize, logoSize);
        };
        img.src = logoUrl;
    }
    
    // Download QR Code
    window.downloadQR = function(format) {
        const canvas = $('#qr-code-display canvas');
        if (!canvas.length) {
            Swal.fire({
                icon: 'warning',
                title: 'No QR Code',
                text: 'Please generate a QR Code first.'
            });
            return;
        }
        
        const dataUrl = canvas[0].toDataURL('image/png');
        
        if (format === 'png') {
            const link = document.createElement('a');
            link.download = 'muni-vc-qr.png';
            link.href = dataUrl;
            link.click();
        } else if (format === 'svg') {
            // For SVG, convert canvas to SVG
            const svg = canvasToSVG(canvas[0]);
            const blob = new Blob([svg], {type: 'image/svg+xml'});
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.download = 'muni-vc-qr.svg';
            link.href = url;
            link.click();
            URL.revokeObjectURL(url);
        }
    };
    
    // Convert canvas to SVG
    function canvasToSVG(canvas) {
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const imageData = ctx.getImageData(0, 0, width, height);
        const data = imageData.data;
        
        let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">`;
        
        // Calculate pixel size
        const pixelSize = 1;
        
        for (let y = 0; y < height; y += pixelSize) {
            for (let x = 0; x < width; x += pixelSize) {
                const index = (y * width + x) * 4;
                const r = data[index];
                const g = data[index + 1];
                const b = data[index + 2];
                const a = data[index + 3];
                
                if (a > 128) {
                    const hex = `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
                    svg += `<rect x="${x}" y="${y}" width="${pixelSize}" height="${pixelSize}" fill="${hex}"/>`;
                }
            }
        }
        
        svg += '</svg>';
        return svg;
    }
    
    // Regenerate QR Code
    window.regenerateQR = function() {
        const token = $('#qr-token').val();
        const url = $('#qr-url').val();
        
        if (!url) {
            Swal.fire({
                icon: 'warning',
                title: 'No URL',
                text: 'Please provide a verification URL.'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'Generating QR Code...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Simulate generation
        setTimeout(() => {
            generateQRCode();
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: 'QR Code Regenerated!',
                timer: 2000,
                showConfirmButton: false
            });
        }, 800);
    };
    
    // Update QR Code on customization change
    $(document).on('change', '#qr-size, #qr-color-dark, #qr-color-light, #logo-url', function() {
        generateQRCode();
    });
    
    // QR download buttons
    $(document).on('click', '.download-qr-btn', function(e) {
        e.preventDefault();
        const format = $(this).data('format');
        downloadQR(format);
    });
});