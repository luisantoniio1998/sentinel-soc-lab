<?php
require_once 'config.php';

$upload_dir = '/var/www/html/uploads/';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = $file['name'];

    // INTENTIONALLY VULNERABLE - No file type validation
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $destination = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        log_to_syslog("FILE_UPLOAD | IP=" . $_SERVER['REMOTE_ADDR'] . " | File=$filename", LOG_WARNING);
        $message = "File uploaded: <a href='uploads/$filename'>$filename</a>";
    } else {
        $message = "Upload failed.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Upload - Security Lab</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>File Upload (RCE Vulnerable)</h1>

    <div class="warning">
        <strong>Vulnerable:</strong> No file type validation — upload a .php file to achieve RCE.
    </div>

    <?php if ($message): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <input type="submit" value="Upload">
    </form>

    <p style="margin-top: 30px;"><a href="index.php">Back to Home</a></p>
</body>
</html>
