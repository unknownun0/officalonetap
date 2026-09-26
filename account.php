<?php
/**
 * Page 3 - Customer Account Page
 * Handles: activation (if not active), login, public profile view, editable dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qr_generator.php';

$db = getDb();

// Get activation code from URL
$activationCode = $_GET['code'] ?? '';

// Fetch customer by activation code
$customer = null;
if ($activationCode) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE activation_code = ?");
    $stmt->execute([$activationCode]);
    $customer = $stmt->fetch();
}

// If no customer found, show error
if (!$customer) {
    http_response_code(404);
    $pageTitle = 'Invalid Link';
    $errorMessage = 'This activation link is invalid or has expired.';
    include __DIR__ . '/templates/error.php';
    exit;
}

// Fetch customer details
$details = null;
if ($customer) {
    $stmt = $db->prepare("SELECT * FROM customer_details WHERE customer_id = ?");
    $stmt->execute([$customer['id']]);
    $details = $stmt->fetch();
}

// Parse socials JSON
$socials = [];
if ($details && $details['socials']) {
    $socials = json_decode($details['socials'], true) ?? [];
}

// Handle activation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate']) && !$customer['is_active']) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    if ($email !== $customer['email']) $errors[] = 'Email does not match';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
    
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE customers SET is_active = 1, password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $customer['id']]);
        
        // Log them in
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['customer_name'] = $customer['name'];
        
        // Create empty customer_details if not exists
        $stmt = $db->prepare("INSERT IGNORE INTO customer_details (customer_id) VALUES (?)");
        $stmt->execute([$customer['id']]);
        
        redirect('account.php?code=' . urlencode($activationCode));
    } else {
        setFlash('error', implode('<br>', $errors));
    }
}

// Handle customer login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_login']) && $customer['is_active']) {
    $email = trim($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';
    
    if ($email === $customer['email'] && password_verify($password, $customer['password_hash'])) {
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['customer_name'] = $customer['name'];
        redirect('account.php?code=' . urlencode($activationCode));
    } else {
        setFlash('error', 'Invalid email or password');
    }
}

// Handle customer logout
if (isset($_GET['logout']) && isCustomerLoggedIn()) {
    session_destroy();
    redirect('account.php?code=' . urlencode($activationCode));
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && isCustomerLoggedIn() && getCustomerId() === $customer['id']) {
    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Socials
    $socialsData = [];
    $socialFields = ['instagram', 'linkedin', 'facebook', 'twitter', 'github', 'website'];
    foreach ($socialFields as $field) {
        $value = trim($_POST[$field] ?? '');
        if ($value) $socialsData[$field] = $value;
    }
    
    // Update customers table (name)
    $stmt = $db->prepare("UPDATE customers SET name = ? WHERE id = ?");
    $stmt->execute([$name, $customer['id']]);
    $_SESSION['customer_name'] = $name;
    
    // Update or insert customer_details
    $stmt = $db->prepare("SELECT id FROM customer_details WHERE customer_id = ?");
    $stmt->execute([$customer['id']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $db->prepare("UPDATE customer_details SET title = ?, company = ?, bio = ?, phone = ?, socials = ? WHERE customer_id = ?");
        $stmt->execute([$title, $company, $bio, $phone, json_encode($socialsData), $customer['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO customer_details (customer_id, title, company, bio, phone, socials) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customer['id'], $title, $company, $bio, $phone, json_encode($socialsData)]);
    }
    
    setFlash('success', 'Profile updated successfully');
    redirect('account.php?code=' . urlencode($activationCode));
}

$flashes = getFlashes();
$isOwnSession = isCustomerLoggedIn() && getCustomerId() === $customer['id'];
$isActive = $customer['is_active'];

// Determine view mode
$viewMode = 'activation'; // default
if ($isActive && !$isOwnSession) $viewMode = 'public_profile';
if ($isActive && $isOwnSession) $viewMode = 'dashboard';

$socialFields = [
    'instagram' => ['label' => 'Instagram', 'icon' => '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-.127-1.28-.057-1.689.072-4.947zm0 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.053.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.354 2.618 6.78 6.98 6.98.059 1.28.073 1.689.073 4.948 0 3.259-.014 3.667-.072 4.947-.2 4.354-2.618 6.78-6.98 6.98-1.281.058-1.689.072-4.948.072-3.259 0-3.667-.014-4.947-.072-4.354-.2-6.78-2.618-6.98-6.98-.059-1.281-.073-1.689-.073-4.948 0-3.259.014-3.667.072-4.947.196-4.354 2.617-6.78 6.979-6.98.127-1.28.057-1.689-.072-4.947C8.333.014 8.741 0 12 0z"/></svg>', 'placeholder' => 'username'],
    'linkedin' => ['label' => 'LinkedIn', 'icon' => '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>', 'placeholder' => 'username'],
    'facebook' => ['label' => 'Facebook', 'icon' => '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>', 'placeholder' => 'username'],
    'twitter' => ['label' => 'Twitter/X', 'icon' => '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>', 'placeholder' => 'username'],
    'github' => ['label' => 'GitHub', 'icon' => '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>', 'placeholder' => 'username'],
    'website' => ['label' => 'Website', 'icon' => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>', 'placeholder' => 'https://example.com'],
];

function renderSocialInputs($socials, $editable = false) {
    global $socialFields;
    $html = '<div class="social-links" style="gap:var(--space-2);">';
    foreach ($socialFields as $key => $field) {
        $value = $socials[$key] ?? '';
        if ($editable) {
            $html .= '
                <div class="form-group" style="margin-bottom:var(--space-3);">
                    <label class="form-label" style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-2);">
                        ' . $field['icon'] . '
                        <span>' . $field['label'] . '</span>
                    </label>
                    <input type="url" class="form-input" name="' . $key . '" placeholder="' . $field['placeholder'] . '" value="' . e($value) . '">
                </div>
            ';
        } elseif ($value) {
            $displayValue = $value;
            if (strpos($value, '://') === 0) {
                $displayValue = parse_url($value, PHP_URL_HOST) ?? $value;
            }
            $html .= '<a href="' . e($value) . '" target="_blank" rel="noopener" class="social-link" title="' . e($field['label']) . '">' . $field['icon'] . '</a>';
        }
    }
    $html .= '</div>';
    return $html;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $customer['name'] ? e($customer['name']) . ' - NFC Solutions' : 'Customer Account' ?>">
    <title><?= $customer['name'] ? e($customer['name']) : 'Account' ?> - NFC Solutions</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👤</text></svg>">
</head>
<body>
    <main class="main">
        <div class="container">
            <?php if (!empty($flashes)): ?>
                <?php foreach ($flashes as $type => $message): ?>
                    <div class="alert alert-<?= e($type) ?>" style="max-width:600px;margin:0 auto var(--space-5);">
                        <svg class="alert-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <?= $message ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($viewMode === 'activation'): ?>
                <!-- Activation Form -->
                <div class="login-container">
                    <div class="card login-card">
                        <div class="login-header">
                            <div class="login-logo">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <h1 class="login-title">Activate Your Account</h1>
                            <p class="login-subtitle">Set up your password to access your profile</p>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="activate" value="1">
                            
                            <div class="form-group">
                                <label class="form-label" for="actEmail">Email</label>
                                <input type="email" class="form-input" id="actEmail" name="email" value="<?= e($customer['email']) ?>" readonly style="background:var(--bg-secondary);">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="actPassword">Password *</label>
                                <input type="password" class="form-input" id="actPassword" name="password" required autocomplete="new-password" minlength="8">
                                <p class="form-help">Minimum 8 characters</p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="actConfirm">Confirm Password *</label>
                                <input type="password" class="form-input" id="actConfirm" name="confirm_password" required autocomplete="new-password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-lg">Activate Account</button>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($viewMode === 'public_profile'): ?>
                <!-- Public Profile View (Read-only) -->
                <div style="max-width:700px;margin:0 auto;">
                    <header class="profile-header">
                        <div class="profile-avatar">
                            <?= e(strtoupper(substr($customer['name'] ?? $customer['email'], 0, 1))) ?>
                        </div>
                        <h1 class="profile-name"><?= e($customer['name'] ?? 'No Name') ?></h1>
                        <?php if ($details && $details['title']): ?>
                            <p class="profile-title"><?= e($details['title']) ?></p>
                        <?php endif; ?>
                    </header>
                    
                    <div class="card" style="margin-top:calc(-1 * var(--space-6));border-radius:0 0 var(--radius-xl) var(--radius-xl);">
                        <div class="card-body">
                            <?php if ($details && ($details['company'] || $details['bio'])): ?>
                                <div class="profile-section">
                                    <h4 class="profile-section-title">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        About
                                    </h4>
                                    <?php if ($details['company']): ?>
                                        <div class="profile-field">
                                            <span class="profile-field-label">Company</span>
                                            <span class="profile-field-value"><?= e($details['company']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($details['bio']): ?>
                                        <div class="profile-field">
                                            <span class="profile-field-label">Bio</span>
                                            <p class="profile-field-value" style="white-space:pre-wrap;"><?= e($details['bio']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($details && ($details['phone'] || !empty($socials))): ?>
                                <div class="profile-section">
                                    <h4 class="profile-section-title">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        Contact
                                    </h4>
                                    <?php if ($details['phone']): ?>
                                        <div class="profile-field">
                                            <span class="profile-field-label">Phone</span>
                                            <a href="tel:<?= e($details['phone']) ?>" class="profile-field-value"><?= e($details['phone']) ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($socials)): ?>
                                        <div class="profile-field">
                                            <span class="profile-field-label">Social</span>
                                            <?= renderSocialInputs($socials, false) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="profile-field">
                                        <span class="profile-field-label">Email</span>
                                        <a href="mailto:<?= e($customer['email']) ?>" class="profile-field-value"><?= e($customer['email']) ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div style="text-align:center;margin-top:var(--space-8);padding-top:var(--space-6);border-top:1px solid var(--border-color);color:var(--text-secondary);font-size:0.875rem;">
                                This is a public profile. <a href="account.php?code=<?= e($activationCode) ?>" style="font-weight:500;">Log in</a> to edit.
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($viewMode === 'dashboard'): ?>
                <!-- Editable Dashboard -->
                <div style="max-width:800px;margin:0 auto;">
                    <header class="profile-header">
                        <div class="profile-avatar">
                            <?= e(strtoupper(substr($_SESSION['customer_name'] ?? $customer['email'], 0, 1))) ?>
                        </div>
                        <h1 class="profile-name"><?= e($_SESSION['customer_name'] ?? 'Your Profile') ?></h1>
                        <p class="profile-title">Dashboard</p>
                    </header>
                    
                    <div class="card" style="margin-top:calc(-1 * var(--space-6));border-radius:0 0 var(--radius-xl) var(--radius-xl);">
                        <div class="card-body">
                            <div class="dashboard-tabs" role="tablist">
                                <button class="dashboard-tab active" role="tab" aria-selected="true" data-tab="profile">Profile</button>
                                <button class="dashboard-tab" role="tab" aria-selected="false" data-tab="contact">Contact</button>
                                <button class="dashboard-tab" role="tab" aria-selected="false" data-tab="social">Social</button>
                                <button class="dashboard-tab" role="tab" aria-selected="false" data-tab="security">Security</button>
                            </div>
                            
                            <form method="POST" id="profileForm">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <!-- Profile Tab -->
                                <div class="dashboard-tab-panel active" id="tab-profile" role="tabpanel">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Full Name *</label>
                                        <input type="text" class="form-input" id="name" name="name" value="<?= e($_SESSION['customer_name'] ?? $customer['name'] ?? '') ?>" required autocomplete="name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="title">Job Title</label>
                                        <input type="text" class="form-input" id="title" name="title" value="<?= e($details['title'] ?? '') ?>" placeholder="e.g. Senior Developer" autocomplete="organization-title">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="company">Company</label>
                                        <input type="text" class="form-input" id="company" name="company" value="<?= e($details['company'] ?? '') ?>" placeholder="Company name" autocomplete="organization">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="bio">Bio</label>
                                        <textarea class="form-textarea" id="bio" name="bio" rows="4" placeholder="Tell us about yourself..."><?= e($details['bio'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Contact Tab -->
                                <div class="dashboard-tab-panel" id="tab-contact" role="tabpanel">
                                    <div class="form-group">
                                        <label class="form-label" for="phone">Phone Number</label>
                                        <input type="tel" class="form-input" id="phone" name="phone" value="<?= e($details['phone'] ?? '') ?>" placeholder="+1 (555) 123-4567" autocomplete="tel">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" class="form-input" id="email" name="email" value="<?= e($customer['email']) ?>" readonly style="background:var(--bg-secondary);">
                                        <p class="form-help">Email cannot be changed. Contact admin if you need to update it.</p>
                                    </div>
                                </div>
                                
                                <!-- Social Tab -->
                                <div class="dashboard-tab-panel" id="tab-social" role="tabpanel">
                                    <p style="color:var(--text-secondary);margin-bottom:var(--space-4);">Add your social media profiles. Leave blank to hide.</p>
                                    <div class="form-row form-row-2">
                                        <?= renderSocialInputs($socials, true) ?>
                                    </div>
                                </div>
                                
                                <!-- Security Tab -->
                                <div class="dashboard-tab-panel" id="tab-security" role="tabpanel">
                                    <div class="card" style="background:var(--bg-secondary);">
                                        <div class="card-body">
                                            <h4 style="margin-bottom:var(--space-4);">Change Password</h4>
                                            <div class="form-group">
                                                <label class="form-label" for="currentPassword">Current Password</label>
                                                <input type="password" class="form-input" id="currentPassword" name="current_password" autocomplete="current-password">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="newPassword">New Password</label>
                                                <input type="password" class="form-input" id="newPassword" name="new_password" autocomplete="new-password" minlength="8">
                                                <p class="form-help">Minimum 8 characters</p>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="confirmNewPassword">Confirm New Password</label>
                                                <input type="password" class="form-input" id="confirmNewPassword" name="confirm_new_password" autocomplete="new-password">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card" style="background:#fffbeb;border-color:#fde68a;margin-top:var(--space-6);">
                                        <div class="card-body">
                                            <h4 style="margin-bottom:var(--space-3);color:#92400e;display:flex;align-items:center;gap:var(--space-2);">
                                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                Danger Zone
                                            </h4>
                                            <p style="color:#92400e;margin-bottom:var(--space-3);font-size:0.875rem;">Once deleted, your account and all data cannot be recovered.</p>
                                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Account</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="modal-footer" style="margin-top:var(--space-8);border-top:none;background:transparent;padding:0;justify-content:flex-end;">
                                    <a href="?logout=1" class="btn btn-secondary">Logout</a>
                                    <button type="submit" class="btn btn-primary">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Login Form (Active but not logged in) -->
                <div class="login-container">
                    <div class="card login-card">
                        <div class="login-header">
                            <div class="login-logo">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h1 class="login-title">Welcome Back</h1>
                            <p class="login-subtitle">Sign in to access your dashboard</p>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="customer_login" value="1">
                            
                            <div class="form-group">
                                <label class="form-label" for="loginEmail">Email</label>
                                <input type="email" class="form-input" id="loginEmail" name="login_email" value="<?= e($customer['email']) ?>" required autocomplete="email" autofocus>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="loginPassword">Password</label>
                                <input type="password" class="form-input" id="loginPassword" name="login_password" required autocomplete="current-password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
                        </form>
                        
                        <p style="text-align:center;margin-top:var(--space-4);">
                            <a href="account.php?code=<?= e($activationCode) ?>" style="font-size:0.875rem;">This isn't you?</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-content">
            <p>&copy; <?= date('Y') ?> NFC Solutions</p>
        </div>
    </footer>

    <script>
        // Tab switching
        document.querySelectorAll('.dashboard-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                document.querySelectorAll('.dashboard-tab').forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('.dashboard-tab-panel').forEach(p => p.classList.remove('active'));
                
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                document.getElementById('tab-' + tabName).classList.add('active');
            });
        });

        // Confirm delete account
        function confirmDelete() {
            if (confirm('Are you sure you want to permanently delete your account? This cannot be undone.')) {
                if (confirm('This will delete ALL your data. Type "DELETE" to confirm.')) {
                    const input = prompt('Type DELETE to confirm:');
                    if (input === 'DELETE') {
                        // Create a form to submit delete request
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = '<input type="hidden" name="delete_account" value="1">';
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            }
        }

        // Password change handling (add to form submit)
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            const currentPass = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmNewPassword').value;
            
            if (currentPass || newPass || confirmPass) {
                if (!currentPass) {
                    e.preventDefault();
                    alert('Please enter your current password to change it.');
                    return;
                }
                if (newPass.length < 8) {
                    e.preventDefault();
                    alert('New password must be at least 8 characters.');
                    return;
                }
                if (newPass !== confirmPass) {
                    e.preventDefault();
                    alert('New passwords do not match.');
                    return;
                }
                // Add password fields to form
                const fields = ['current_password', 'new_password', 'confirm_new_password'];
                fields.forEach(name => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = document.getElementById(name.replace('_', '')).value;
                    this.appendChild(input);
                });
            }
        });

        // Auto-focus first input
        document.querySelector('.form-input:not([readonly])')?.focus();
    </script>
</body>
</html>