<?php
/**
 * Error Template
 * Used for 404 and other error pages
 */
$pageTitle = $pageTitle ?? 'Error';
$errorMessage = $errorMessage ?? 'An error occurred.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - NFC Solutions</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="main">
        <div class="container">
            <div class="card" style="max-width:500px;margin:var(--space-12) auto;text-align:center;">
                <div class="card-body" style="padding:var(--space-12);">
                    <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--danger);margin-bottom:var(--space-4);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h1 style="margin-bottom:var(--space-3);"><?= e($pageTitle) ?></h1>
                    <p style="color:var(--text-secondary);"><?= e($errorMessage) ?></p>
                    <a href="index.php" class="btn btn-primary mt-6">Back to Products</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>