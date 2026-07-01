<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();

// ********** HANDLE AJAX CREATE QR REQUEST **********
if (isset($_GET['ajax']) && $_GET['ajax'] === 'create_qr') {
    header('Content-Type: application/json');
    
    try {
        // ONLY use wizard data - NO profile fallback
        $content = $_SESSION['qr_wizard']['content'] ?? [];
        $design = $_SESSION['qr_wizard']['design'] ?? [];
        
        // Validate - MUST have wizard data
        if (empty($content) || empty($content['full_name']) || empty($content['title_position']) || empty($content['office'])) {
            echo json_encode(['success' => false, 'message' => 'Please complete all required fields in Step 1.']);
            exit;
        }
        
        // Generate token
        $token = 'vc_' . bin2hex(random_bytes(16));
        $name = $content['title'] ?? 'QR Code - ' . date('Y-m-d H:i');
        
        // Insert - ONLY wizard data
        $insert_stmt = $pdo->prepare("INSERT INTO qr_codes (name, token, status, design_settings, content_data) VALUES (?, ?, 'active', ?, ?)");
        $insert_stmt->execute([
            $name,
            $token,
            json_encode($design),
            json_encode($content)
        ]);
        
        $qr_id = $pdo->lastInsertId();
        
        // Clear wizard session
        unset($_SESSION['qr_wizard']);
        
        echo json_encode(['success' => true, 'qr_id' => $qr_id]);
        exit;
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Initialize session - NO PROFILE DATA
if (!isset($_SESSION['qr_wizard'])) {
    $_SESSION['qr_wizard'] = [
        'content' => [],
        'design' => []
    ];
}

// ********** HANDLE FORM SUBMISSIONS **********
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // STEP 1: Save Content
    if ($action === 'save_content') {
        $_SESSION['qr_wizard']['content'] = [
            'title' => trim($_POST['qr_title'] ?? ''),
            'description' => trim($_POST['qr_description'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'title_position' => trim($_POST['title_position'] ?? ''),
            'office' => trim($_POST['office'] ?? ''),
            'biography' => trim($_POST['biography'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'linkedin' => trim($_POST['linkedin'] ?? ''),
            'facebook' => trim($_POST['facebook'] ?? ''),
            'twitter' => trim($_POST['twitter'] ?? '')
        ];
        
        // Handle photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'profile_' . time() . '.' . $ext;
                $target = '../assets/uploads/profiles/' . $filename;
                if (!is_dir('../assets/uploads/profiles/')) {
                    mkdir('../assets/uploads/profiles/', 0777, true);
                }
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $_SESSION['qr_wizard']['content']['photo'] = $filename;
                }
            }
        }
        
        header('Location: qr-create.php?step=2');
        exit;
    }
    
    // STEP 2: Save Design
    if ($action === 'save_design') {
        $_SESSION['qr_wizard']['design'] = [
            'pattern' => $_POST['pattern'] ?? 'dots',
            'corner' => $_POST['corner'] ?? 'square',
            'color' => $_POST['qr_color'] ?? '#8B0000',
            'background' => $_POST['bg_color'] ?? '#FFFFFF',
            'size' => intval($_POST['qr_size'] ?? 300),
            'padding' => intval($_POST['padding'] ?? 25)
        ];
        
        header('Location: qr-create.php?step=3');
        exit;
    }
}

// Get current wizard data - NO PROFILE
$wizard_content = $_SESSION['qr_wizard']['content'] ?? [];
$wizard_design = $_SESSION['qr_wizard']['design'] ?? [];

require_once '../includes/header.php';
?>

<style>
.step-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 0;
    position: relative;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    transition: all 0.3s ease;
    border: 3px solid #dee2e6;
}

.step-circle.active {
    background: #8B0000;
    border-color: #8B0000;
    box-shadow: 0 0 0 5px rgba(139, 0, 0, 0.2);
}

.step-circle.completed {
    background: #28a745;
    border-color: #28a745;
}

.step-circle .checkmark { display: none; }
.step-circle.completed .checkmark { display: block; }
.step-circle.completed .step-number { display: none; }

.step-label {
    margin-top: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #6c757d;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.step-label.active { color: #8B0000; }
.step-label.completed { color: #28a745; }

.step-arrow {
    flex: 1;
    height: 3px;
    background: #dee2e6;
    position: relative;
    margin: 0 5px;
    z-index: 1;
    min-width: 40px;
}

.step-arrow.active { background: #8B0000; }
.step-arrow.completed { background: #28a745; }

.step-arrow::after {
    content: '';
    position: absolute;
    right: -6px;
    top: -4px;
    border-left: 8px solid #dee2e6;
    border-top: 5px solid transparent;
    border-bottom: 5px solid transparent;
}

.step-arrow.active::after { border-left-color: #8B0000; }
.step-arrow.completed::after { border-left-color: #28a745; }

@media (max-width: 768px) {
    .step-circle { width: 40px; height: 40px; font-size: 14px; }
    .step-label { font-size: 10px; }
    .step-arrow { min-width: 20px; }
}
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-magic text-primary"></i> Create QR Code</h2>
        <span class="badge bg-primary">Step <?php echo $step; ?> of 4</span>
    </div>
    
    <!-- Step Progress -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="step-progress">
                <div class="step-item">
                    <div class="step-circle <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                        <span class="step-number">1</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">Content</div>
                </div>
                <div class="step-arrow <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>"></div>
                <div class="step-item">
                    <div class="step-circle <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                        <span class="step-number">2</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">Design</div>
                </div>
                <div class="step-arrow <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>"></div>
                <div class="step-item">
                    <div class="step-circle <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                        <span class="step-number">3</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">Preview</div>
                </div>
                <div class="step-arrow <?php echo $step >= 4 ? 'active' : ''; ?>"></div>
                <div class="step-item">
                    <div class="step-circle <?php echo $step >= 4 ? 'active' : ''; ?>">
                        <span class="step-number">4</span>
                        <span class="checkmark">✓</span>
                    </div>
                    <div class="step-label <?php echo $step >= 4 ? 'active' : ''; ?>">Create</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Step Content -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if ($step == 1): ?>
                <h4 class="mb-3"><i class="bi bi-pencil-square text-primary"></i> Step 1: Add Content</h4>
                <p class="text-muted mb-4">Enter the information you want to display when the QR code is scanned.</p>
                
                <form method="POST" enctype="multipart/form-data" id="step1Form">
                    <input type="hidden" name="action" value="save_content">
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">QR Code Title/Name *</label>
                            <input type="text" class="form-control" name="qr_title" 
                                   value="<?php echo htmlspecialchars($wizard_content['title'] ?? ''); ?>" placeholder="Enter QR code name" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="qr_description" rows="2" placeholder="Enter description"><?php echo htmlspecialchars($wizard_content['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?php echo htmlspecialchars($wizard_content['full_name'] ?? ''); ?>" placeholder="Enter full name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title/Position *</label>
                            <input type="text" class="form-control" name="title_position" 
                                   value="<?php echo htmlspecialchars($wizard_content['title_position'] ?? ''); ?>" placeholder="Enter title/position" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Office *</label>
                            <input type="text" class="form-control" name="office" 
                                   value="<?php echo htmlspecialchars($wizard_content['office'] ?? ''); ?>" placeholder="Enter office/department" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Biography</label>
                            <textarea class="form-control" name="biography" rows="3" placeholder="Enter biography"><?php echo htmlspecialchars($wizard_content['biography'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($wizard_content['email'] ?? ''); ?>" placeholder="Enter email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($wizard_content['phone'] ?? ''); ?>" placeholder="Enter phone number">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" name="website" 
                                   value="<?php echo htmlspecialchars($wizard_content['website'] ?? ''); ?>" placeholder="Enter website URL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">LinkedIn</label>
                            <input type="url" class="form-control" name="linkedin" 
                                   value="<?php echo htmlspecialchars($wizard_content['linkedin'] ?? ''); ?>" placeholder="LinkedIn URL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Facebook</label>
                            <input type="url" class="form-control" name="facebook" 
                                   value="<?php echo htmlspecialchars($wizard_content['facebook'] ?? ''); ?>" placeholder="Facebook URL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Twitter / X</label>
                            <input type="url" class="form-control" name="twitter" 
                                   value="<?php echo htmlspecialchars($wizard_content['twitter'] ?? ''); ?>" placeholder="Twitter URL">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="profile_photo" accept="image/*">
                            <?php if (!empty($wizard_content['photo'])): ?>
                                <small class="text-success">Current: <?php echo $wizard_content['photo']; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Next Step <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
                
            <?php elseif ($step == 2): ?>
                <h4 class="mb-3"><i class="bi bi-palette text-primary"></i> Step 2: Design QR Code</h4>
                <p class="text-muted mb-4">Customize the appearance of your QR code.</p>
                
                <form method="POST" id="step2Form">
                    <input type="hidden" name="action" value="save_design">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Pattern Style</label>
                            <select class="form-select" name="pattern">
                                <option value="dots" <?php echo ($wizard_design['pattern'] ?? 'dots') == 'dots' ? 'selected' : ''; ?>>Dots</option>
                                <option value="squares" <?php echo ($wizard_design['pattern'] ?? '') == 'squares' ? 'selected' : ''; ?>>Squares</option>
                                <option value="rounded" <?php echo ($wizard_design['pattern'] ?? '') == 'rounded' ? 'selected' : ''; ?>>Rounded</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Corner Style</label>
                            <select class="form-select" name="corner">
                                <option value="square" <?php echo ($wizard_design['corner'] ?? 'square') == 'square' ? 'selected' : ''; ?>>Square</option>
                                <option value="rounded" <?php echo ($wizard_design['corner'] ?? '') == 'rounded' ? 'selected' : ''; ?>>Rounded</option>
                                <option value="circle" <?php echo ($wizard_design['corner'] ?? '') == 'circle' ? 'selected' : ''; ?>>Circle</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">QR Color</label>
                            <input type="color" class="form-control" name="qr_color" 
                                   value="<?php echo $wizard_design['color'] ?? '#8B0000'; ?>" style="height: 50px; cursor: pointer;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Background Color</label>
                            <input type="color" class="form-control" name="bg_color" 
                                   value="<?php echo $wizard_design['background'] ?? '#FFFFFF'; ?>" style="height: 50px; cursor: pointer;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">QR Size</label>
                            <select class="form-select" name="qr_size">
                                <option value="200" <?php echo ($wizard_design['size'] ?? 300) == 200 ? 'selected' : ''; ?>>Small (200px)</option>
                                <option value="300" <?php echo ($wizard_design['size'] ?? 300) == 300 ? 'selected' : ''; ?>>Medium (300px)</option>
                                <option value="400" <?php echo ($wizard_design['size'] ?? 300) == 400 ? 'selected' : ''; ?>>Large (400px)</option>
                                <option value="500" <?php echo ($wizard_design['size'] ?? 300) == 500 ? 'selected' : ''; ?>>Extra Large (500px)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Padding (space around QR)</label>
                            <select class="form-select" name="padding">
                                <option value="15" <?php echo ($wizard_design['padding'] ?? 25) == 15 ? 'selected' : ''; ?>>Small (15px)</option>
                                <option value="25" <?php echo ($wizard_design['padding'] ?? 25) == 25 ? 'selected' : ''; ?>>Medium (25px)</option>
                                <option value="35" <?php echo ($wizard_design['padding'] ?? 25) == 35 ? 'selected' : ''; ?>>Large (35px)</option>
                                <option value="45" <?php echo ($wizard_design['padding'] ?? 25) == 45 ? 'selected' : ''; ?>>Extra Large (45px)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="qr-create.php?step=1" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <button type="submit" class="btn btn-primary">Next Step <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <h4 class="mb-3"><i class="bi bi-eye text-primary"></i> Step 3: Preview</h4>
                <p class="text-muted mb-4">Review your QR code content and design before creation.</p>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-info-circle"></i> Content Summary</h6></div>
                            <div class="card-body">
                                <p><strong>Title:</strong> <?php echo htmlspecialchars($wizard_content['title'] ?? 'N/A'); ?></p>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($wizard_content['full_name'] ?? 'N/A'); ?></p>
                                <p><strong>Title:</strong> <?php echo htmlspecialchars($wizard_content['title_position'] ?? 'N/A'); ?></p>
                                <p><strong>Office:</strong> <?php echo htmlspecialchars($wizard_content['office'] ?? 'N/A'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($wizard_content['email'] ?? 'N/A'); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($wizard_content['phone'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="card border mt-3">
                            <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-palette"></i> Design Settings</h6></div>
                            <div class="card-body">
                                <p><strong>Pattern:</strong> <?php echo ucfirst($wizard_design['pattern'] ?? 'dots'); ?></p>
                                <p><strong>Corner:</strong> <?php echo ucfirst($wizard_design['corner'] ?? 'square'); ?></p>
                                <p><strong>Color:</strong> <span style="display:inline-block; width:20px; height:20px; background:<?php echo $wizard_design['color'] ?? '#8B0000'; ?>; border-radius:3px;"></span></p>
                                <p><strong>Size:</strong> <?php echo $wizard_design['size'] ?? 300; ?>px</p>
                                <p><strong>Padding:</strong> <?php echo $wizard_design['padding'] ?? 25; ?>px</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-qr-code"></i> QR Code Preview</h6></div>
                            <div class="card-body text-center">
                                <div id="preview-qr" style="display: inline-block; background: white; padding: 20px; border-radius: 10px;"></div>
                                <p class="text-muted mt-3 small">This is how your QR code will look</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 d-flex justify-content-between">
                    <a href="qr-create.php?step=2" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <a href="qr-create.php?step=4" class="btn btn-success"><i class="bi bi-check-circle"></i> Create & Download</a>
                </div>
                
                <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const previewUrl = '<?php echo APP_URL . '/verify?token=preview'; ?>';
                        const container = document.getElementById('preview-qr');
                        const color = '<?php echo $wizard_design['color'] ?? '#8B0000'; ?>';
                        const bgColor = '<?php echo $wizard_design['background'] ?? '#FFFFFF'; ?>';
                        const size = parseInt('<?php echo $wizard_design['size'] ?? 300; ?>') || 300;
                        
                        new QRCode(container, {
                            text: previewUrl,
                            width: Math.min(size, 250),
                            height: Math.min(size, 250),
                            colorDark: color,
                            colorLight: bgColor,
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    });
                </script>
                
            <?php elseif ($step == 4): ?>
                <h4 class="mb-3"><i class="bi bi-check-circle text-success"></i> Step 4: Create & Download</h4>
                <p class="text-muted mb-4">Generate your QR code and save it to the database.</p>
                
                <div class="card border">
                    <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-info-circle"></i> Ready to Create</h6></div>
                    <div class="card-body text-center py-4">
                        <div class="mb-4"><i class="bi bi-qr-code" style="font-size: 4rem; color: #8B0000;"></i></div>
                        <h5>Your QR Code is ready to be created!</h5>
                        <p class="text-muted">Click the button below to generate and save your QR code.</p>
                        
                        <div class="row justify-content-center mt-3">
                            <div class="col-md-8">
                                <div class="bg-light p-3 rounded text-start">
                                    <p class="mb-1"><strong>Title:</strong> <?php echo htmlspecialchars($wizard_content['title'] ?? 'N/A'); ?></p>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($wizard_content['full_name'] ?? 'N/A'); ?></p>
                                    <p class="mb-0"><strong>Design:</strong> <?php echo ucfirst($wizard_design['pattern'] ?? 'dots'); ?> | <?php echo ucfirst($wizard_design['corner'] ?? 'square'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" class="btn btn-success btn-lg px-5" id="createBtn">
                                <i class="bi bi-check-circle"></i> Create QR Code
                            </button>
                            <p class="text-muted mt-2 small">You'll be redirected to customize and download your QR code</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 d-flex justify-content-between">
                    <a href="qr-create.php?step=3" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-house"></i> Dashboard</a>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createBtn = document.getElementById('createBtn');
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Creating...';
            this.disabled = true;
            
            fetch('qr-create.php?ajax=create_qr')
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('Server error: ' + text.substring(0, 100));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        window.location.href = 'qr-customize.php?id=' + data.qr_id + '&generated=1';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to create QR code'
                        });
                        this.innerHTML = '<i class="bi bi-check-circle"></i> Create QR Code';
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to create QR code: ' + error.message
                    });
                    this.innerHTML = '<i class="bi bi-check-circle"></i> Create QR Code';
                    this.disabled = false;
                });
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>