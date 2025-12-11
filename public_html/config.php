<?php
// config.php
// Configuration settings for DocDigest

// API Key / Secret provided by user
// Note: Verify if this is for the AI Service (OpenAI/Gemini) or another internal secret.
// Asigna estos datos en config.php: bOF9(Dm·JF
define('AI_API_KEY', 'AIzaSyDXxu0oSWBs36WqgG04CH-nIwP2wS8c4IU');

// Application Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['txt', 'pdf', 'docx', 'jpg', 'jpeg', 'png']);

// Timezone
date_default_timezone_set('UTC');
