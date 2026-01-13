<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRUEBA</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Tesseract.js -->
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js'></script>
</head>

<body>
    <div class="container">
        <header class="main-header">
            <h1 class="logo">DocDigest</h1>
            <div class="language-notice" style="margin-top: 0.5rem; font-size: 1rem; color: var(--text-muted); font-style: italic; min-height: 24px;">
                <span id="typewriter"></span>
            </div>
        </header>

        <main class="app-interface">
            <div class="input-section card">
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('file')">
                        <span class="icon">📄</span>
                        <span>Upload File</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('image')">
                        <span class="icon">📷</span>
                        <span>Upload Image</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('text')">
                        <span class="icon">✏️</span>
                        <span>Paste Text</span>
                    </button>
                </div>

                <!-- File Upload Form -->
                <div id="tab-file" class="tab-content active">
                    <form id="fileForm" action="process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="type" value="file">
                        <input type="hidden" name="language" id="fileLanguage" value="en">
                        <div class="drop-zone" id="fileDropZone">
                            <p>Drag & Drop your file here</p>
                            <span>or</span>
                            <input type="file" name="document" id="fileInput" accept=".txt,.pdf,.docx" required>
                            <label for="fileInput" class="btn secondary">Browse Files</label>
                            <p class="small-text">PDF, DOCX, TXT (Max 10MB)</p>
                        </div>
                        <button type="submit" class="btn primary full-width">Simplify Document</button>
                    </form>
                </div>

                <!-- Image Upload Form (OCR) -->
                <div id="tab-image" class="tab-content">
                    <div class="drop-zone" id="imageDropZone">
                        <p>Upload an image of printed text</p>
                        <input type="file" id="imageInput" accept="image/*">
                        <label for="imageInput" class="btn secondary">Select Image</label>
                    </div>
                    <div id="ocr-status" class="status-message hidden">
                        <div class="spinner"></div>
                        <p id="ocr-progress">Initializing OCR...</p>
                    </div>
                    <!-- Hidden form to submit extracted text -->
                    <form id="imageForm" action="process.php" method="POST">
                        <input type="hidden" name="type" value="text"> <!-- Treat as text after OCR -->
                        <input type="hidden" name="language" id="imageLanguage" value="en">
                        <textarea name="content" id="imageExtractedText" style="display:none;"></textarea>
                        <button type="button" id="startOcrBtn" class="btn primary full-width">Extract &
                            Simplify</button>
                    </form>
                </div>

                <!-- Text Paste Form -->
                <div id="tab-text" class="tab-content">
                    <form id="textForm" action="process.php" method="POST">
                        <input type="hidden" name="type" value="text">
                        <input type="hidden" name="language" id="textLanguage" value="en">
                        <textarea name="content" placeholder="Paste the official text here..." rows="10"
                            required></textarea>
                        <button type="submit" class="btn primary full-width">Simplify Text</button>
                    </form>
                </div>
            </div>

            <!-- Features / Info -->
            <div class="features-grid">
                <div class="feature-item">
                    <span class="icon">🔒</span>
                    <h3>Secure & Private</h3>
                    <p>Files are auto-deleted after processing.</p>
                </div>
                <div class="feature-item">
                    <span class="icon">💬</span>
                    <h3>Interactive AI</h3>
                    <p>Ask questions to your document.</p>
                </div>
                <div class="feature-item">
                    <span class="icon">🌍</span>
                    <h3>Multilingual</h3>
                    <p>Works in any major language.</p>
                </div>
            </div>
        </main>

        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> DocDigest. <a href="#">Privacy Policy</a></p>
        </footer>
    </div>

    <script src="assets/i18n.js?v=2"></script>
    <script src="assets/processing-timer.js?v=2"></script>
    <script src="assets/script.js?v=2"></script>
    <script>
    // Typewriter effect for language notice
    (function() {
        const messages = [
            "DocDigest recognizes most languages and responds in the same language",
            "DocDigest reconoce la mayoría de idiomas y responde en el mismo idioma",
            "DocDigest reconnaît la plupart des langues et répond dans la même langue",
            "DocDigest riconosce la maggior parte delle lingue e risponde nella stessa lingua",
            "DocDigest reconhece a maioria dos idiomas e responde no mesmo idioma"
        ];
        
        let messageIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const typewriterEl = document.getElementById('typewriter');
        const typingSpeed = 50;
        const deletingSpeed = 30;
        const pauseBetweenMessages = 3000;
        const pauseBeforeDelete = 2000;
        
        function type() {
            const currentMessage = messages[messageIndex];
            
            if (!isDeleting && charIndex <= currentMessage.length) {
                typewriterEl.textContent = currentMessage.substring(0, charIndex);
                charIndex++;
                setTimeout(type, typingSpeed);
            } else if (!isDeleting && charIndex > currentMessage.length) {
                setTimeout(() => {
                    isDeleting = true;
                    type();
                }, pauseBeforeDelete);
            } else if (isDeleting && charIndex > 0) {
                typewriterEl.textContent = currentMessage.substring(0, charIndex - 1);
                charIndex--;
                setTimeout(type, deletingSpeed);
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                messageIndex = (messageIndex + 1) % messages.length;
                setTimeout(type, 500);
            }
        }
        
        type();
    })();
    </script>
</body>

</html>
