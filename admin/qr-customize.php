<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$generated = isset($_GET['generated']) ? true : false;

if (!$id) {
    $stmt = $pdo->query("SELECT * FROM qr_codes ORDER BY id DESC LIMIT 1");
    $qr = $stmt->fetch();
    if ($qr) {
        $id = $qr['id'];
    } else {
        header('Location: qr-create.php');
        exit;
    }
}

// Get QR code
$stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE id = ?");
$stmt->execute([$id]);
$qr = $stmt->fetch();

if (!$qr) {
    header('Location: manage-qr.php');
    exit;
}

$verification_url = APP_URL . '/verify?token=' . $qr['token'];
$content = json_decode($qr['content_data'] ?? '{}', true);
$design = json_decode($qr['design_settings'] ?? '{}', true);

// Handle design update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_design'])) {
    $design = [
        'pattern' => $_POST['pattern'] ?? 'dots',
        'corner' => $_POST['corner'] ?? 'square',
        'color' => $_POST['qr_color'] ?? '#8B0000',
        'background' => $_POST['bg_color'] ?? '#FFFFFF',
        'size' => intval($_POST['qr_size'] ?? 300),
        'padding' => intval($_POST['padding'] ?? 25)
    ];
    
    $stmt = $pdo->prepare("UPDATE qr_codes SET design_settings = ? WHERE id = ?");
    $stmt->execute([json_encode($design), $id]);
    
    $message = "Design updated successfully!";
    
    // Reload QR
    $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE id = ?");
    $stmt->execute([$id]);
    $qr = $stmt->fetch();
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <?php if ($generated): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> QR Code generated successfully! Customize and download below.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-palette text-primary"></i> Customize QR Code</h2>
        <div>
            <a href="qr-details.php?id=<?php echo $qr['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-eye"></i> Details
            </a>
            <a href="manage-qr.php" class="btn btn-outline-secondary">
                <i class="bi bi-list-ul"></i> Manage
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- QR Display -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-eye text-primary"></i> QR Preview</h5>
                </div>
                <div class="card-body text-center">
                    <div class="qr-container bg-light p-4 rounded">
                        <div id="qr-display" class="mx-auto" style="max-width: 400px; display: inline-block;"></div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="btn-group" role="group">
                            <button onclick="downloadQR('png')" class="btn btn-success">
                                <i class="bi bi-download"></i> PNG
                            </button>
                            <button onclick="downloadQR('svg')" class="btn btn-outline-secondary">
                                <i class="bi bi-download"></i> SVG
                            </button>
                            <button onclick="copyURL()" class="btn btn-outline-primary">
                                <i class="bi bi-clipboard"></i> Copy URL
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <span class="badge <?php echo $qr['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <i class="bi <?php echo $qr['status'] === 'active' ? 'bi-check-circle' : 'bi-x-circle'; ?>"></i>
                            <?php echo ucfirst($qr['status']); ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($qr['name']); ?>
                        </span>
                        <span class="badge bg-info">
                            <i class="bi bi-eye"></i> <?php echo number_format($qr['scan_count']); ?> scans
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customization -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-sliders text-primary"></i> Design Options</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="designForm">
                        <input type="hidden" name="update_design" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pattern Style</label>
                                <select class="form-select" name="pattern" id="pattern" onchange="updateQR()">
                                    <option value="dots" <?php echo ($design['pattern'] ?? 'dots') == 'dots' ? 'selected' : ''; ?>>Dots</option>
                                    <option value="squares" <?php echo ($design['pattern'] ?? '') == 'squares' ? 'selected' : ''; ?>>Squares</option>
                                    <option value="rounded" <?php echo ($design['pattern'] ?? '') == 'rounded' ? 'selected' : ''; ?>>Rounded</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Corner Style</label>
                                <select class="form-select" name="corner" id="corner" onchange="updateQR()">
                                    <option value="square" <?php echo ($design['corner'] ?? 'square') == 'square' ? 'selected' : ''; ?>>Square</option>
                                    <option value="rounded" <?php echo ($design['corner'] ?? '') == 'rounded' ? 'selected' : ''; ?>>Rounded</option>
                                    <option value="circle" <?php echo ($design['corner'] ?? '') == 'circle' ? 'selected' : ''; ?>>Circle</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">QR Color</label>
                                <input type="color" class="form-control" name="qr_color" id="qr_color"
                                       value="<?php echo $design['color'] ?? '#8B0000'; ?>" 
                                       style="height: 50px; cursor: pointer;" onchange="updateQR()">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Background Color</label>
                                <input type="color" class="form-control" name="bg_color" id="bg_color"
                                       value="<?php echo $design['background'] ?? '#FFFFFF'; ?>" 
                                       style="height: 50px; cursor: pointer;" onchange="updateQR()">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">QR Size</label>
                                <select class="form-select" name="qr_size" id="qr_size" onchange="updateQR()">
                                    <option value="200" <?php echo ($design['size'] ?? 300) == 200 ? 'selected' : ''; ?>>Small (200px)</option>
                                    <option value="300" <?php echo ($design['size'] ?? 300) == 300 ? 'selected' : ''; ?>>Medium (300px)</option>
                                    <option value="400" <?php echo ($design['size'] ?? 300) == 400 ? 'selected' : ''; ?>>Large (400px)</option>
                                    <option value="500" <?php echo ($design['size'] ?? 300) == 500 ? 'selected' : ''; ?>>Extra Large (500px)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Padding (space around QR)</label>
                                <select class="form-select" name="padding" id="padding" onchange="updateQR()">
                                    <option value="15" <?php echo ($design['padding'] ?? 25) == 15 ? 'selected' : ''; ?>>Small (15px)</option>
                                    <option value="25" <?php echo ($design['padding'] ?? 25) == 25 ? 'selected' : ''; ?>>Medium (25px)</option>
                                    <option value="35" <?php echo ($design['padding'] ?? 25) == 35 ? 'selected' : ''; ?>>Large (35px)</option>
                                    <option value="45" <?php echo ($design['padding'] ?? 25) == 45 ? 'selected' : ''; ?>>Extra Large (45px)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> Save Design
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="qr-create.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Create New QR
                        </a>
                        <a href="manage-qr.php" class="btn btn-outline-secondary">
                            <i class="bi bi-list-ul"></i> Manage QR Codes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
// QR Code variables
const qrUrl = '<?php echo $verification_url; ?>';
let currentQRCanvas = null;

// Generate QR on load
document.addEventListener('DOMContentLoaded', function() {
    updateQR();
});

function updateQR() {
    const size = parseInt(document.getElementById('qr_size').value) || 300;
    const color = document.getElementById('qr_color').value || '#8B0000';
    const bgColor = document.getElementById('bg_color').value || '#FFFFFF';
    const pattern = document.getElementById('pattern').value || 'dots';
    const corner = document.getElementById('corner').value || 'square';
    const padding = parseInt(document.getElementById('padding').value) || 25;
    
    const container = document.getElementById('qr-display');
    container.innerHTML = '';
    
    // Create wrapper with padding
    const wrapper = document.createElement('div');
    wrapper.style.padding = padding + 'px';
    wrapper.style.background = bgColor;
    wrapper.style.borderRadius = '8px';
    wrapper.style.display = 'inline-block';
    wrapper.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
    container.appendChild(wrapper);
    
    // Generate QR code
    const qr = new QRCode(wrapper, {
        text: qrUrl,
        width: size,
        height: size,
        colorDark: color,
        colorLight: bgColor,
        correctLevel: QRCode.CorrectLevel.H
    });
    
    // Apply custom styles after generation
    setTimeout(function() {
        const canvas = wrapper.querySelector('canvas');
        if (canvas) {
            currentQRCanvas = canvas;
            applyCustomStyles(canvas, pattern, corner, color, bgColor);
        }
    }, 100);
}

function applyCustomStyles(canvas, pattern, corner, color, bgColor) {
    const ctx = canvas.getContext('2d');
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    const width = canvas.width;
    const height = canvas.height;
    
    // Detect QR modules
    const modules = [];
    const moduleSize = Math.ceil(width / 25);
    
    for (let y = 0; y < height; y += moduleSize) {
        const row = [];
        for (let x = 0; x < width; x += moduleSize) {
            let isDark = false;
            const cx = Math.min(x + Math.floor(moduleSize/2), width - 1);
            const cy = Math.min(y + Math.floor(moduleSize/2), height - 1);
            const idx = (cy * width + cx) * 4;
            if (data[idx] < 128) {
                isDark = true;
            }
            row.push(isDark);
        }
        if (row.length > 0) {
            modules.push(row);
        }
    }
    
    // Redraw with custom styles
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = bgColor;
    ctx.fillRect(0, 0, width, height);
    
    const cols = modules[0]?.length || 0;
    const rows = modules.length;
    const moduleW = width / cols;
    const moduleH = height / rows;
    
    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            if (modules[r] && modules[r][c]) {
                const x = c * moduleW;
                const y = r * moduleH;
                const size = Math.min(moduleW, moduleH);
                
                ctx.fillStyle = color;
                
                if (pattern === 'dots') {
                    const radius = size * 0.4;
                    ctx.beginPath();
                    ctx.arc(x + moduleW/2, y + moduleH/2, radius, 0, Math.PI * 2);
                    ctx.fill();
                } else if (pattern === 'rounded') {
                    const radius2 = size * 0.2;
                    const rw = size * 0.8;
                    const rh = size * 0.8;
                    const rx = x + (moduleW - rw) / 2;
                    const ry = y + (moduleH - rh) / 2;
                    ctx.beginPath();
                    ctx.moveTo(rx + radius2, ry);
                    ctx.lineTo(rx + rw - radius2, ry);
                    ctx.quadraticCurveTo(rx + rw, ry, rx + rw, ry + radius2);
                    ctx.lineTo(rx + rw, ry + rh - radius2);
                    ctx.quadraticCurveTo(rx + rw, ry + rh, rx + rw - radius2, ry + rh);
                    ctx.lineTo(rx + radius2, ry + rh);
                    ctx.quadraticCurveTo(rx, ry + rh, rx, ry + rh - radius2);
                    ctx.lineTo(rx, ry + radius2);
                    ctx.quadraticCurveTo(rx, ry, rx + radius2, ry);
                    ctx.closePath();
                    ctx.fill();
                } else {
                    // Squares
                    const sqSize = size * 0.85;
                    const sx = x + (moduleW - sqSize) / 2;
                    const sy = y + (moduleH - sqSize) / 2;
                    
                    const isFinder = (r < 3 && c < 3) || (r < 3 && c > cols - 4) || (r > rows - 4 && c < 3);
                    
                    if (isFinder && corner === 'rounded') {
                        const radius3 = sqSize * 0.3;
                        ctx.beginPath();
                        ctx.moveTo(sx + radius3, sy);
                        ctx.lineTo(sx + sqSize - radius3, sy);
                        ctx.quadraticCurveTo(sx + sqSize, sy, sx + sqSize, sy + radius3);
                        ctx.lineTo(sx + sqSize, sy + sqSize - radius3);
                        ctx.quadraticCurveTo(sx + sqSize, sy + sqSize, sx + sqSize - radius3, sy + sqSize);
                        ctx.lineTo(sx + radius3, sy + sqSize);
                        ctx.quadraticCurveTo(sx, sy + sqSize, sx, sy + sqSize - radius3);
                        ctx.lineTo(sx, sy + radius3);
                        ctx.quadraticCurveTo(sx, sy, sx + radius3, sy);
                        ctx.closePath();
                        ctx.fill();
                    } else if (isFinder && corner === 'circle') {
                        ctx.beginPath();
                        ctx.arc(x + moduleW/2, y + moduleH/2, sqSize/2, 0, Math.PI * 2);
                        ctx.fill();
                    } else {
                        ctx.fillRect(sx, sy, sqSize, sqSize);
                    }
                }
            }
        }
    }
}

function downloadQR(format) {
    if (!currentQRCanvas) {
        Swal.fire({
            icon: 'warning',
            title: 'No QR Code',
            text: 'Please generate a QR code first.'
        });
        return;
    }
    
    // Create a new canvas for the final image
    const finalCanvas = document.createElement('canvas');
    const ctx = finalCanvas.getContext('2d');
    
    // Get settings
    const bgColor = document.getElementById('bg_color').value || '#FFFFFF';
    const padding = parseInt(document.getElementById('padding').value) || 25;
    const size = parseInt(document.getElementById('qr_size').value) || 300;
    const totalSize = size + (padding * 2);
    
    // Set canvas size
    finalCanvas.width = totalSize;
    finalCanvas.height = totalSize;
    
    // Draw background
    ctx.fillStyle = bgColor;
    ctx.fillRect(0, 0, totalSize, totalSize);
    
    // Draw the QR code canvas onto the final canvas with padding
    ctx.drawImage(currentQRCanvas, padding, padding, size, size);
    
    // Create download link
    const link = document.createElement('a');
    if (format === 'png') {
        link.download = 'muni-vc-qr.png';
        link.href = finalCanvas.toDataURL('image/png');
    } else if (format === 'svg') {
        link.download = 'muni-vc-qr.svg';
        link.href = finalCanvas.toDataURL('image/png');
    }
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function copyURL() {
    const url = '<?php echo $verification_url; ?>';
    navigator.clipboard.writeText(url).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Verification URL copied to clipboard.',
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(function() {
        const temp = document.createElement('input');
        temp.value = url;
        document.body.appendChild(temp);
        temp.select();
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
</script>

<style>
#qr-display {
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}
#qr-display > div {
    transition: all 0.3s ease;
}
</style>

<?php require_once '../includes/footer.php'; ?>