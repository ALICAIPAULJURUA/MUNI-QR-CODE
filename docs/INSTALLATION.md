# Muni University QR Verification System - Installation Guide

## System Requirements

### Server Requirements
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache 2.4+ or Nginx
- PHP Extensions:
  - PDO MySQL
  - GD Library (for image processing)
  - JSON
  - Session
  - FileInfo
  - OpenSSL

### Minimum Specifications
- 256MB RAM
- 50MB Disk Space
- Shared hosting or VPS

---

## Installation Steps

### 1. Download and Extract
```bash
# Download the package
wget https://example.com/muni-vc-qr.zip

# Extract to web root
unzip muni-vc-qr.zip -d /var/www/html/