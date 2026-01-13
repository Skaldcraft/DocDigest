// assets/script.js
// Function to switch between tabs
function switchTab(tabName) {
    console.log('switchTab llamada con:', tabName);
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    const clickedButton = document.querySelector(`.tab-btn[onclick*="${tabName}"]`);
    if (clickedButton) clickedButton.classList.add('active');
    
    const targetTab = document.getElementById('tab-' + tabName);
    if (targetTab) targetTab.classList.add('active');
}

// Exponer la función al ámbito global
window.switchTab = switchTab;
console.log('switchTab está disponible en window:', typeof window.switchTab);

// Update hidden language fields
function updateHiddenLanguageFields(lang) {
    const fileLang = document.getElementById('fileLanguage');
    const imageLang = document.getElementById('imageLanguage');
    const textLang = document.getElementById('textLanguage');
    if (fileLang) fileLang.value = lang;
    if (imageLang) imageLang.value = lang;
    if (textLang) textLang.value = lang;
}

document.addEventListener('DOMContentLoaded', () => {
    // Initialize language fields
    const savedLang = localStorage.getItem('docdigest_lang') || 'en';
    currentLang = savedLang;
    updateHiddenLanguageFields(savedLang);

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
                showUploadSuccess(e.dataTransfer.files[0].name);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                showUploadSuccess(fileInput.files[0].name);
            }
        });

        function showUploadSuccess(fileName) {
            const dropZoneText = fileDropZone.querySelector('p');
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

    // OCR Functionality
    const imageInput = document.getElementById('imageInput');
    const imageForm = document.getElementById('imageForm');
    const ocrStatus = document.getElementById('ocr-status');
    const ocrProgress = document.getElementById('ocr-progress');
    const extractedTextarea = document.getElementById('imageExtractedText');
    const startOcrBtn = document.getElementById('startOcrBtn');

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
                await worker.loadLanguage('eng+spa+fra+deu');
                await worker.initialize('eng+spa+fra+deu');
                ocrProgress.textContent = "Recognizing text...";

                const { data: { text } } = await worker.recognize(imageFile);
                await worker.terminate();

                if (!text || text.trim().length === 0) {
                    throw new Error("No text found in image.");
                }

                ocrProgress.textContent = "Text extracted! Sending...";
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

            addMessage(message, 'user');
            chatInput.value = '';

            const loadingDiv = document.createElement('div');
            loadingDiv.classList.add('chat-message', 'ai');
            loadingDiv.textContent = '...';
            loadingDiv.id = 'chat-loading';
            chatBox.appendChild(loadingDiv);
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                const formData = new FormData();
                formData.append('type', 'chat_question');
                formData.append('question', message);
                formData.append('language', currentLang);

                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

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

        sendChatBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }
});

// Hook for i18n.js integration
if (typeof switchLanguage === 'function') {
    const originalSwitchLanguage = switchLanguage;
    window.switchLanguage = function(lang) {
        originalSwitchLanguage(lang);
        currentLang = lang;
        updateHiddenLanguageFields(lang);
    };
}