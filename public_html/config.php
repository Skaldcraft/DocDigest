<?php
// Configuration settings for DocDigest

define('OPENAI_API_KEY', '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');

// Application Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['txt', 'pdf', 'docx', 'jpg', 'jpeg', 'png']);

date_default_timezone_set('UTC');
