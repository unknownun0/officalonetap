<?php
/**
 * Page 1 - Public Product Page
 * Displays all products in a responsive grid
 * Clicking a product opens modal with details + contact form
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qr_generator.php';

$db = getDb();

// Fetch all products
$stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Handle contact form submission
$contactSuccess = false;
$contactError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate
    $errors = [];
    if ($name === '') $errors[] = 'Name is required';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if ($message === '') $errors[] = 'Message is required';
    
    if (empty($errors)) {
        // Save to database
        $stmt = $db->prepare("INSERT INTO messages (product_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$productId ?: null, $name, $email, $subject, $message]);
        
        // Send email to admin
        $emailBody = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Product:</strong> " . e($products[array_search($productId, array_column($products, 'id'))]['name'] ?? 'N/A') . "</p>
            <p><strong>Name:</strong> " . e($name) . "</p>
            <p><strong>Email:</strong> " . e($email) . "</p>
            <p><strong>Subject:</strong> " . e($subject) . "</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(e($message)) . "</p>
        ";
        
        sendEmail(ADMIN_EMAIL, 'New Contact: ' . e($subject), $emailBody);
        
        $contactSuccess = true;
    } else {
        $contactError = implode('<br>', $errors);
    }
}

$flashes = getFlashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse our NFC-enabled products">
    <title>Products - NFC Solutions</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📱</text></svg>">
</head>
<body>
    <main class="main">
        <div class="container">
            <header class="page-header">
                <h1 class="page-title">Our Products</h1>
                <p class="page-subtitle">Discover premium NFC-enabled solutions for your business</p>
            </header>

            <?php if (!empty($flashes)): ?>
                <?php foreach ($flashes as $type => $message): ?>
                    <div class="alert alert-<?= e($type) ?>">
                        <svg class="alert-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <?= $message ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="card" style="text-align: center; padding: var(--space-12);">
                    <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted); margin-bottom: var(--space-4);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <h2 style="margin-bottom: var(--space-2);">No Products Found</h2>
                    <p style="color: var(--text-secondary);">Products will appear here once added by the administrator.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-1 grid-2 grid-3" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                    <?php foreach ($products as $product): ?>
                        <article class="card product-card">
                            <a href="#" class="product-link" data-product-id="<?= $product['id'] ?>">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?= e($product['image_url']) ?>" alt="" class="product-image" loading="lazy">
                                <?php else: ?>
                                    <div class="product-image" style="display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
                                        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                <?php endif; ?>
                                <div class="product-content">
                                    <h3 class="product-name"><?= e($product['name']) ?></h3>
                                    <p class="product-description"><?= e($product['description']) ?></p>
                                    <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-content">
            <p>&copy; <?= date('Y') ?> NFC Solutions. All rights reserved.</p>
        </div>
    </footer>

    <!-- Product Detail Modal -->
    <div class="modal-overlay" id="productModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Product Details</h2>
                <button class="modal-close" id="modalClose" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content loaded via JS -->
            </div>
        </div>
    </div>

    <script>
        // Product data for modal
        const products = <?= json_encode($products) ?>;
        
        const modal = document.getElementById('productModal');
        const modalBody = document.getElementById('modalBody');
        const modalClose = document.getElementById('modalClose');
        let lastFocusedElement = null;

        // Open modal
        document.querySelectorAll('.product-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = parseInt(this.dataset.productId);
                const product = products.find(p => p.id === productId);
                if (product) openModal(product);
            });
        });

        // Close modal
        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            if (lastFocusedElement) lastFocusedElement.focus();
        }

        modalClose.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
        });

        function openModal(product) {
            lastFocusedElement = document.activeElement;
            document.body.style.overflow = 'hidden';
            
            const specs = product.specs ? product.specs.split('\n').map(s => s.trim()).filter(s => s) : [];
            const contactEmail = '<?= e(ADMIN_EMAIL) ?>';
            
            modalBody.innerHTML = `
                <div style="text-align: center; margin-bottom: var(--space-6);">
                    ${product.image_url ? `<img src="${product.image_url}" alt="" style="width:100%;max-height:200px;object-fit:cover;border-radius:var(--radius-md);margin-bottom:var(--space-4);">` : ''}
                    <h3 style="margin-bottom:var(--space-2);">${escapeHtml(product.name)}</h3>
                    <p style="color:var(--text-secondary);">${escapeHtml(product.description)}</p>
                    <div style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-top:var(--space-3);">$${parseFloat(product.price).toFixed(2)}</div>
                </div>
                
                ${specs.length ? `
                <div class="profile-section">
                    <h4 class="profile-section-title">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Specifications
                    </h4>
                    <ul style="list-style:none;display:grid;gap:var(--space-2);">
                        ${specs.map(spec => `<li style="display:flex;align-items:center;gap:var(--space-2);padding:var(--space-2);background:var(--bg-secondary);border-radius:var(--radius-sm);"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--success);flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>${escapeHtml(spec)}</span></li>`).join('')}
                    </ul>
                </div>
                ` : ''}
                
                <div class="profile-section">
                    <h4 class="profile-section-title">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Contact Us
                    </h4>
                    <p style="color:var(--text-secondary);margin-bottom:var(--space-4);">Interested in this product? Send us a message and we'll get back to you.</p>
                    
                    <form id="contactForm" method="POST">
                        <input type="hidden" name="product_id" value="${product.id}">
                        <input type="hidden" name="contact_submit" value="1">
                        
                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="contactName">Name *</label>
                                <input type="text" class="form-input" id="contactName" name="name" required autocomplete="name">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="contactEmail">Email *</label>
                                <input type="email" class="form-input" id="contactEmail" name="email" required autocomplete="email">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="contactSubject">Subject</label>
                            <input type="text" class="form-input" id="contactSubject" name="subject" placeholder="Inquiry about ${escapeHtml(product.name)}" autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="contactMessage">Message *</label>
                            <textarea class="form-textarea" id="contactMessage" name="message" rows="4" required placeholder="Tell us about your requirements..."></textarea>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                            <button type="submit" class="btn btn-primary">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Send Message
                            </button>
                        </div>
                    </form>
                    
                    <div id="contactSuccess" class="alert alert-success hidden" style="margin-top:var(--space-4);">
                        <svg class="alert-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <div>Thank you! Your message has been sent successfully.</div>
                    </div>
                    
                    <div id="contactError" class="alert alert-error hidden" style="margin-top:var(--space-4);">
                        <svg class="alert-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <div id="contactErrorMessage"></div>
                    </div>
                </div>
            `;
            
            modal.classList.add('active');
            modalClose.focus();
            
            // Handle contact form submission
            const form = document.getElementById('contactForm');
            form.addEventListener('submit', handleContactSubmit);
        }

        async function handleContactSubmit(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const successDiv = document.getElementById('contactSuccess');
            const errorDiv = document.getElementById('contactError');
            const errorMessage = document.getElementById('contactErrorMessage');
            
            // Reset states
            successDiv.classList.add('hidden');
            errorDiv.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Sending...';
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const text = await response.text();
                
                // Check if success (page returns with success message)
                if (text.includes('contactSuccess') || response.ok) {
                    form.reset();
                    successDiv.classList.remove('hidden');
                    submitBtn.style.display = 'none';
                } else {
                    throw new Error('Submission failed');
                }
            } catch (err) {
                errorMessage.textContent = 'Failed to send message. Please try again.';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Send Message';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>