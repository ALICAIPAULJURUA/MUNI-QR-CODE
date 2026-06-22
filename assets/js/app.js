/**
 * Muni University QR Verification System
 * Main Application JavaScript
 */

$(document).ready(function() {
    'use strict';
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Image preview for file uploads
    $('input[type="file"][data-preview]').on('change', function(e) {
        const previewId = $(this).data('preview');
        const file = this.files[0];
        
        if (file && previewId) {
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#' + previewId).attr('src', event.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const input = $($(this).data('target'));
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });
    
    // QR Code download buttons
    $('.download-qr').on('click', function(e) {
        e.preventDefault();
        const format = $(this).data('format');
        const url = $(this).data('url');
        
        if (url) {
            window.location.href = url + '?format=' + format;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'No QR Code Available',
                text: 'Please generate a QR Code first.'
            });
        }
    });
    
    // Copy to clipboard
    $('.copy-to-clipboard').on('click', function() {
        const text = $(this).data('copy');
        if (text) {
            navigator.clipboard.writeText(text).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Text copied to clipboard.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(function() {
                // Fallback
                const temp = $('<input>');
                $('body').append(temp);
                temp.val(text).select();
                document.execCommand('copy');
                temp.remove();
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        }
    });
    
    // Confirm delete actions
    $('.confirm-delete').on('click', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const message = $(this).data('message') || 'Are you sure you want to delete this?';
        
        Swal.fire({
            title: 'Confirm Delete',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8B0000',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
    
    // QR Status toggle
    $('.toggle-qr-status').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'activate' : 'deactivate';
        
        Swal.fire({
            title: `Are you sure?`,
            text: `You are about to ${action} the QR Code.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: newStatus === 'active' ? '#059669' : '#8B0000',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${action} it!`
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Helper function to generate random string
function generateToken(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

// Helper function to validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Helper function to validate URL
function validateURL(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}