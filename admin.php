<?php
/**
 * Page 2 - Admin Panel
 * Session-based login, customer management, QR code generation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qr_generator.php';

$db = getDb();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        redirect('admin.php');
    } else {
        setFlash('error', 'Invalid email or password');
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('admin.php');
}

// Handle generate customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_customer']) && isAdminLoggedIn()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $errors = [];
    if ($name === '') $errors[] = 'Name is required';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'A customer with this email already exists';
        }
    }
    
    if (empty($errors)) {
        $activationCode = generateActivationCode();
        $stmt = $db->prepare("INSERT INTO customers (name, email, activation_code) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $activationCode]);
        setFlash('success', 'Customer created successfully. QR code generated.');
    } else {
        setFlash('error', implode('<br>', $errors));
    }
    redirect('admin.php');
}

// Handle revoke customer
if (isset($_GET['revoke']) && isAdminLoggedIn()) {
    $customerId = (int)$_GET['revoke'];
    $stmt = $db->prepare("UPDATE customers SET activation_code = CONCAT(activation_code, '_revoked'), is_active = 0 WHERE id = ?");
    $stmt->execute([$customerId]);
    setFlash('success', 'Customer access revoked');
    redirect('admin.php');
}

// Handle delete customer
if (isset($_GET['delete']) && isAdminLoggedIn()) {
    $customerId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    setFlash('success', 'Customer deleted');
    redirect('admin.php');
}

// Fetch all customers with details
$stmt = $db->query("
    SELECT c.*, cd.title, cd.company, cd.bio, cd.phone, cd.socials
    FROM customers c
    LEFT JOIN customer_details cd ON c.id = cd.customer_id
    ORDER BY c.created_at DESC
");
$customers = $stmt->fetchAll();

// Stats
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM customers WHERE is_active = 1")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM customers WHERE is_active = 0")->fetchColumn(),
];

$flashes = getFlashes();
$accountUrl = appUrl('account.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - one tap las pinas </title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
</head>
<body>
    <main class="main">
        <div class="container">
            <?php if (!isAdminLoggedIn()): ?>
                <!-- Login Form -->
                <div class="login-container">
                    <div class="card login-card">
                        <div class="login-header">
                            <div class="login-logo">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h1 class="login-title">Admin Panel</h1>
                            <p class="login-subtitle">Sign in to manage customers</p>
                        </div>
                        
                        <?php if (!empty($flashes)): ?>
                            <?php foreach ($flashes as $type => $message): ?>
                                <div class="alert alert-<?= e($type) ?>">
                                    <svg class="alert-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    <?= $message ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="login" value="1">
                            
                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-input" id="email" name="email" required autocomplete="email" autofocus>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" class="form-input" id="password" name="password" required autocomplete="current-password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
                        </form>
                        
                        <p style="text-align:center;margin-top:var(--space-6);color:var(--text-secondary);font-size:0.875rem;">
                            Demo: admin@example.com / admin123
                        </p>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Admin Dashboard -->
                <header class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:var(--space-4);text-align:left;">
                    <div>
                        <h1 class="page-title">Admin Dashboard</h1>
                        <p class="page-subtitle">Manage customer accounts and QR codes</p>
                    </div>
                    <a href="?logout=1" class="btn btn-secondary">Logout</a>
                </header>
                
                <?php if (!empty($flashes)): ?>
                    <?php foreach ($flashes as $type => $message): ?>
                        <div class="alert alert-<?= e($type) ?>">
                            <svg class="alert-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <?= $message ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-primary">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $stats['total'] ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-success">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $stats['active'] ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-warning">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $stats['pending'] ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                </div>
                
                <!-- Generate New Customer -->
                <div class="card" style="margin-bottom:var(--space-8);">
                    <div class="card-header">
                        <h3 style="margin:0;">Generate New Customer</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-row form-row-2">
                            <input type="hidden" name="generate_customer" value="1">
                            
                            <div class="form-group">
                                <label class="form-label" for="custName">Name</label>
                                <input type="text" class="form-input" id="custName" name="name" placeholder="John Doe" autocomplete="name">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="custEmail">Email *</label>
                                <input type="email" class="form-input" id="custEmail" name="email" required placeholder="john@example.com" autocomplete="email">
                            </div>
                            
                            <div style="grid-column:1/-1;display:flex;justify-content:flex-end;">
                                <button type="submit" class="btn btn-primary">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Generate QR Code
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Customers List -->
                <div class="card">
                    <div class="card-header">
                        <h3 style="margin:0;">Customers</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($customers)): ?>
                            <div style="padding:var(--space-12);text-align:center;color:var(--text-secondary);">
                                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom:var(--space-4);opacity:0.5;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <p>No customers yet. Generate your first customer above.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Activation Code</th>
                                            <th>QR Code</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><?= e($customer['name'] ?? '—') ?></td>
                                                <td><?= e($customer['email']) ?></td>
                                                <td>
                                                    <span class="badge <?= $customer['is_active'] ? 'badge-active' : 'badge-pending' ?>">
                                                        <?= $customer['is_active'] ? 'Active' : 'Pending' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <code style="font-size:0.75rem;background:var(--bg-tertiary);padding:var(--space-1) var(--space-2);border-radius:var(--radius-sm);font-family:var(--font-mono);">
                                                        <?= e(substr($customer['activation_code'], 0, 16)) ?>...
                                                    </code>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-secondary qr-toggle" data-customer-id="<?= $customer['id'] ?>" data-code="<?= e($customer['activation_code']) ?>" data-name="<?= e($customer['name'] ?? 'Customer') ?>" data-email="<?= e($customer['email']) ?>" data-active="<?= $customer['is_active'] ?>">
                                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7z"/></svg>
                                                        View QR
                                                    </button>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td>
                                                <td>
                                                    <div style="display:flex;gap:var(--space-2);">
                                                        <?php if (!$customer['is_active']): ?>
                                                            <a href="account.php?code=<?= e($customer['activation_code']) ?>" class="btn btn-sm btn-outline" target="_blank" title="Test activation link">
                                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?revoke=<?= $customer['id'] ?>" class="btn btn-sm btn-outline btn-danger" onclick="return confirm('Revoke access for this customer?')" title="Revoke access">
                                                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                        </a>
                                                        <a href="?delete=<?= $customer['id'] ?>" class="btn btn-sm btn-outline btn-danger" onclick="return confirm('Permanently delete this customer? This cannot be undone.')" title="Delete customer">
                                                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-content">
            <p>&copy; <?= date('Y') ?> NFC Solutions Admin</p>
        </div>
    </footer>

    <!-- QR Code Modal -->
    <div class="modal-overlay" id="qrModal" role="dialog" aria-modal="true" aria-labelledby="qrModalTitle">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="qrModalTitle">QR Code</h2>
                <button class="modal-close" id="qrModalClose" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body" style="text-align:center;">
                <div class="qr-container" id="qrContainer">
                    <!-- QR code inserted here -->
                </div>
                <div style="display:flex;gap:var(--space-3);justify-content:center;flex-wrap:wrap;margin-top:var(--space-4);">
                    <button class="btn btn-primary" id="downloadQr">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download
                    </button>
                    <button class="btn btn-secondary" id="copyLink">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // QR Code Modal
        const qrModal = document.getElementById('qrModal');
        const qrContainer = document.getElementById('qrContainer');
        const qrModalClose = document.getElementById('qrModalClose');
        const downloadQr = document.getElementById('downloadQr');
        const copyLink = document.getElementById('copyLink');
        let currentQrData = null;

        document.querySelectorAll('.qr-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const customerId = this.dataset.customerId;
                const code = this.dataset.code;
                const name = this.dataset.name;
                const email = this.dataset.email;
                const isActive = this.dataset.active === '1';
                
                const link = '<?= e($accountUrl) ?>?code=' + encodeURIComponent(code);
                currentQrData = { link, code, name, email, isActive };
                
                // Generate QR code URL
                const qrUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' + encodeURIComponent(link) + '&choe=UTF-8';
                
                qrContainer.innerHTML = `
                    <img src="${qrUrl}" alt="QR Code for ${escapeHtml(name)}" class="qr-image" id="qrImage">
                    <div class="qr-link">${escapeHtml(link)}</div>
                    <p style="margin-top:var(--space-3);font-size:0.875rem;color:var(--text-secondary);">
                        ${isActive ? 'This QR code links to the customer\'s public profile.' : 'This QR code links to the activation form.'}
                    </p>
                `;
                
                qrModal.classList.add('active');
                qrModalClose.focus();
            });
        });

        qrModalClose.addEventListener('click', () => qrModal.classList.remove('active'));
        qrModal.addEventListener('click', e => { if (e.target === qrModal) qrModal.classList.remove('active'); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && qrModal.classList.contains('active')) qrModal.classList.remove('active'); });

        downloadQr.addEventListener('click', async () => {
            if (!currentQrData) return;
            const qrUrl = 'https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl=' + encodeURIComponent(currentQrData.link) + '&choe=UTF-8';
            const a = document.createElement('a');
            a.href = qrUrl;
            a.download = `qr-${currentQrData.code}.png`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });

        copyLink.addEventListener('click', async () => {
            if (!currentQrData) return;
            await navigator.clipboard.writeText(currentQrData.link);
            copyLink.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!';
            setTimeout(() => {
                copyLink.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy Link';
            }, 2000);
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>