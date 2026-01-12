
// assets/script.js
let currentLang = 'en';

// Typewriter for language message
const messages = [
    "DocuDigest recognizes most languages and responds in the same language",
    "DocuDigest reconoce la mayoría de los idiomas y responde en el mismo idioma",
    "DocuDigest reconnaît la plupart des langues et répond dans la même langue",
    "DocuDigest riconosce la maggior parte delle lingue e risponde nella stessa lingua",
    "DocuDigest reconhece a maioria dos idiomas e responde no mesmo idioma"
];

const typewriterElement = document.getElementById("typewriter-text");
let msgIndex = 0;
let charIndex = 0;

function typeMessage() {
    if (!typewriterElement) return;

    const currentMessage = messages[msgIndex];
    if (charIndex <= currentMessage.length) {
        typewriterElement.textContent = currentMessage.substring(0, charIndex);
        charIndex++;
        setTimeout(typeMessage, 60);
    } else {
        setTimeout(() => {
            eraseMessage();
        }, 1500);
    }
}

function eraseMessage() {
    const currentMessage = messages[msgIndex];
    if (charIndex >= 0) {
        typewriterElement.textContent = currentMessage.substring(0, charIndex);
        charIndex--;
        setTimeout(eraseMessage, 30);
    } else {
        msgIndex = (msgIndex + 1) % messages.length;
        setTimeout(typeMessage, 300);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    typeMessage();
});

document.addEventListener('DOMContentLoaded', () => {
    // Tab Switching
    window.switchTab = function (tabName) {
        // Update Buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const activeBtn = document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`);
        if (activeBtn) activeBtn.classList.add('active');

        // Update Content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabName}`).classList.add('active');
    };

    // File Drag & Drop
    const fileDropZone = document.getElementById('fileDropZone');
    const fileInput = document.getElementById('fileInput');

    if (fileDropZone && fileInput) {
        fileDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileDropZone.style.borderColor = 'var(--primary-color)';
            fileDropZone.style.backgroundColor = 'rgba(204, 123, 103, 0.05)';
        });

        fileDropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            fileDropZone.style.borderColor = '#E5D4C1';
            fileDropZone.style.backgroundColor = 'rgba(247, 244, 234, 0.5)';
        });

        fileDropZone.addEventListener('drop', (e) => {
            e.preventDefault();

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                const fileName = e.dataTransfer.files[0].name;
                showUploadSuccess(fileName);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                const fileName = fileInput.files[0].name;
                showUploadSuccess(fileName);
            }
        });

        function showUploadSuccess(fileName) {
            console.log('File uploaded:', fileName); // Debug
            const dropZoneText = fileDropZone.querySelector('p');
            // Get translated success message from global translations or default
            const successMsg = (typeof translations !== 'undefined' && translations[currentLang])
                ? translations[currentLang].uploadedSuccess
                : "✓ Uploaded successfully";

            dropZoneText.innerHTML = `
                <div style="
                    background-color: #DEF7EC; 
                    border: 2px solid #03543F; 
                    border-radius: 12px; 
                    padding: 1rem; 
                    margin-top: 0.5rem;
                    display: inline-block;
                    width: 90%;
                ">
                    <span style="color: #03543F; font-weight: 800; font-size: 1.3rem; display: block; margin-bottom: 0.25rem;">
                        ${successMsg}
                    </span>
                    <span style="font-size: 1rem; color: #03543F; word-break: break-all;">
                        ${fileName}
                    </span>
                </div>
            `;
            fileDropZone.style.borderColor = '#03543F';
            fileDropZone.style.backgroundColor = '#F3FAF7';
            fileDropZone.style.borderWidth = '3px';
        }
    }

    // Privacy Notice Translation Hook
    const privacyNotice = document.querySelector('.status-message strong');
    if (privacyNotice && typeof translations !== 'undefined') {
        const sensitiveText = document.querySelector('.status-message');
        if (sensitiveText && sensitiveText.innerHTML.includes('Sensitive information')) {
            const t = translations[currentLang] || translations['en'];
            // This is a simple replacement, ideally done on server-side or via full text replacement
            // For now, we will handle it in i18n.js update to be cleaner.
        }
    }

    // ... (rest of Tesseract and Chat code remains similar, ensuring chat UI classes are used)

    const imageInput = document.getElementById('imageInput');
    const imageForm = document.getElementById('imageForm');
    const ocrStatus = document.getElementById('ocr-status');
    const ocrProgress = document.getElementById('ocr-progress');
    const extractedTextarea = document.getElementById('imageExtractedText');

    if (startOcrBtn && imageInput) {
        startOcrBtn.addEventListener('click', async () => {
            if (!imageInput.files || !imageInput.files[0]) {
                alert('Please select an image first.');
                return;
            }

            const imageFile = imageInput.files[0];

            ocrStatus.classList.remove('hidden');
            ocrProgress.textContent = "Initializing OCR Engine...";
            startOcrBtn.disabled = true;

            try {
                const worker = await Tesseract.createWorker('eng');
                // Load multiple languages for better detection
                await worker.loadLanguage('eng+spa+fra+deu');
                await worker.initialize('eng+spa+fra+deu');

                ocrProgress.textContent = "Recognizing text... (This may take a moment)";

                const { data: { text } } = await worker.recognize(imageFile);

                await worker.terminate();

                if (!text || text.trim().length === 0) {
                    throw new Error("No text found in image.");
                }

                ocrProgress.textContent = "Text extracted! Sending for simplification...";
                extractedTextarea.value = text;
                imageForm.submit();

            } catch (error) {
                console.error(error);
                ocrProgress.textContent = "Error: " + error.message;
                startOcrBtn.disabled = false;
            }
        });
    }

    // Chat Functionality
    const chatInput = document.getElementById('chatInput');
    const sendChatBtn = document.getElementById('sendChatBtn');
    const chatBox = document.getElementById('chatBox');

    if (chatInput && sendChatBtn && chatBox) {
        // Scroll to bottom initially
        chatBox.scrollTop = chatBox.scrollHeight;

        function addMessage(text, sender) {
            const div = document.createElement('div');
            div.classList.add('chat-message', sender);
            div.textContent = text;
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
        }


        async function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            // UI Update
            addMessage(message, 'user');
            chatInput.value = '';

            // Show loading state (could be improved)
            const loadingDiv = document.createElement('div');
            loadingDiv.classList.add('chat-message', 'ai');
            loadingDiv.textContent = '...';
            loadingDiv.id = 'chat-loading';
            chatBox.appendChild(loadingDiv);
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                // Send to Backend
                const formData = new FormData();
                formData.append('type', 'chat_question');
                formData.append('question', message);
                formData.append('language', currentLang); // NUEVO

                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                // Remove loading
                const loader = document.getElementById('chat-loading');
                if (loader) loader.remove();

                if (data.status === 'success') {
                    addMessage(data.answer, 'ai');
                } else {
                    addMessage("Error: " + data.message, 'ai');
                }

            } catch (error) {
                const loader = document.getElementById('chat-loading');
                if (loader) loader.remove();
                addMessage("Connection error. Please try again.", 'ai');
                console.error(error);
            }
        }
// Sincronizar campos hidden de idioma en formularios
function updateHiddenLanguageFields(lang) {
    const fileLang = document.getElementById('fileLanguage');
    const imageLang = document.getElementById('imageLanguage');
    const textLang = document.getElementById('textLanguage');
    if (fileLang) fileLang.value = lang;
    if (imageLang) imageLang.value = lang;
    if (textLang) textLang.value = lang;
}

// Hook para integración con i18n.js
if (typeof switchLanguage === 'function') {
    const originalSwitchLanguage = switchLanguage;
    window.switchLanguage = function(lang) {
        originalSwitchLanguage(lang);
        updateHiddenLanguageFields(lang);
    };
}

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar campos hidden con el idioma guardado
    const savedLang = localStorage.getItem('docdigest_lang') || 'en';
    updateHiddenLanguageFields(savedLang);
});

        sendChatBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }

    // Function to switch between tabs
    
function switchTab(tabName) {
    // Remove 'active' class from all tab buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Hide all tab contents
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Add 'active' class to clicked button
    // Add 'active' class to the button that matches tabName
    document.querySelector(`button[onclick="switchTab('${tabName}')"]`).classList.add('active');    
    // Show the corresponding tab content
    const targetTab = document.getElementById('tab-' + tabName);
    if (targetTab) {
        targetTab.classList.add('active');
    }
}});
