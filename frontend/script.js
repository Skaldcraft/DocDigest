console.log("DocDigest Script Starting (Restored)...");

// Initialize immediately
safeRun(setupSubtitleAnimation, "Subtitle Animation");
safeRun(setupDragAndDrop, "DragAndDrop File");
safeRun(setupDragAndDropImage, "DragAndDrop Image");
safeRun(setupButtons, "Buttons");
safeRun(setupTabs, "Tabs");

console.log("DocDigest Initialization Sequence Finished");

function safeRun(fn, name) {
    try {
        console.log(`Starting ${name}...`);
        fn();
        console.log(`Finished ${name}`);
    } catch (e) {
        console.error(`ERROR in ${name}:`, e);
    }
}

function setupSubtitleAnimation() {
    const subtitle = document.getElementById('dynamic-subtitle');
    if (!subtitle) return;
    const texts = [
        "DocDigest recognizes most languages and responds in the same language", // English
        "DocDigest reconoce la mayoría de los idiomas y responde en el mismo idioma", // Spanish
        "DocDigest reconnaît la plupart des langues et répond dans la même langue", // French
        "DocDigest riconosce la maggior parte delle lingue e risponde nella stessa lingua", // Italian
        "DocDigest reconhece a maioria dos idiomas e responde no mesmo idioma" // Portuguese
    ];
    let index = 0;

    window.subtitleInterval = setInterval(() => {
        subtitle.style.opacity = '0';
        setTimeout(() => {
            index = (index + 1) % texts.length;
            subtitle.textContent = texts[index];
            subtitle.style.opacity = '1';
        }, 1000);
    }, 4000);
}

function setupTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            tab.classList.add('active');
            document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
        });
    });
}

function setupDragAndDrop() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const browseBtn = document.getElementById('browse-btn');

    setupGenericDragAndDrop(dropZone, fileInput, browseBtn, handleFile);
}

function setupDragAndDropImage() {
    const dropZone = document.getElementById('drop-zone-img');
    const fileInput = document.getElementById('file-input-img');
    const browseBtn = document.getElementById('browse-btn-img');

    setupGenericDragAndDrop(dropZone, fileInput, browseBtn, handleFile);
}

function setupGenericDragAndDrop(dropZone, fileInput, browseBtn, handler) {
    if (!dropZone || !fileInput || !browseBtn) {
        console.warn("Missing elements for DragAndDrop setup");
        return;
    }
    browseBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) handler(e.target.files[0]);
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handler(e.dataTransfer.files[0]);
    });
}

function setupButtons() {
    const resetBtn = document.getElementById('reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            document.getElementById('result-container').classList.add('hidden');
            document.querySelector('.input-tabs').style.display = 'flex';
            // Reset active tab view
            const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
            document.getElementById(`tab-${activeTab}`).classList.remove('hidden');
            document.getElementById('result-text').textContent = '';
            document.getElementById('text-input').value = '';
        });
    }

    const copyBtn = document.getElementById('copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const text = document.getElementById('result-text').dataset.plainText || document.getElementById('result-text').textContent;
            navigator.clipboard.writeText(text).then(() => alert('Copied to clipboard!'));
        });
    }

    const downloadBtn = document.getElementById('download-btn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            const text = document.getElementById('result-text').dataset.plainText || document.getElementById('result-text').textContent;
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'DocDigest-Analysis.txt';
            a.click();
        });
    }

    const analyzeTextBtn = document.getElementById('analyze-text-btn');
    if (analyzeTextBtn) {
        analyzeTextBtn.addEventListener('click', () => {
            const text = document.getElementById('text-input').value;
            if (text.trim()) {
                const blob = new Blob([text], { type: 'text/plain' });
                const file = new File([blob], "paste.txt", { type: "text/plain" });
                handleFile(file);
            } else {
                alert("Please paste some text first.");
            }
        });
    }
}

async function handleFile(file) {
    const loading = document.getElementById('loading-indicator');
    const resultContainer = document.getElementById('result-container');
    const resultText = document.getElementById('result-text');
    const tabs = document.querySelector('.input-tabs');
    const tabContents = document.querySelectorAll('.tab-content');

    // UI Updates
    // tabs.style.display = 'none'; // REMOVED: Keep tabs visible
    // tabContents.forEach(c => c.classList.add('hidden')); // REMOVED: Keep inputs visible

    // Hide subtitle after processing starts
    const subtitle = document.getElementById('dynamic-subtitle');
    if (subtitle) {
        subtitle.style.display = 'none';
        if (window.subtitleInterval) clearInterval(window.subtitleInterval);
    }

    loading.classList.remove('hidden');
    resultText.innerHTML = "";
    document.getElementById('result-disclaimer').classList.add('hidden');
    let cumulativeText = "";

    const formData = new FormData();
    formData.append('document', file);

    try {
        const response = await fetch('/api/analyze', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error('Analysis failed');

        loading.classList.add('hidden');
        resultContainer.classList.remove('hidden');

        // Handle Streaming Response
        const reader = response.body.getReader();
        const decoder = new TextDecoder();

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            const chunk = decoder.decode(value, { stream: true });
            cumulativeText += chunk;

            // Basic markdown bold converter
            resultText.innerHTML = cumulativeText
                .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
                .replace(/\n/g, '<br>'); // Keep line breaks with innerHTML

            // Store plain text for copy/download
            resultText.dataset.plainText = cumulativeText;
        }

        // Add Disclaimer
        showDisclaimer(cumulativeText);

    } catch (error) {
        console.error(error);
        loading.classList.add('hidden');
        tabs.style.display = 'flex';
        // Restore active tab
        const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
        document.getElementById(`tab-${activeTab}`).classList.remove('hidden');
        alert('Error processing document: ' + error.message);
    }
}
function showDisclaimer(text) {
    const disclaimerDiv = document.getElementById('result-disclaimer');
    if (!disclaimerDiv) return;

    const disclaimers = {
        en: "DocDigest should be used only as a time‑saving aid, not as a substitute for reading the official communication. The esencial information generated may not be complete or fully accurate. All details, deadlines, and requirements must be verified directly in the original document.",
        es: "DocDigest debe utilizarse únicamente como una ayuda para ahorrar tiempo, no como un sustituto de la lectura de la comunicación oficial. La información esencial generada puede no ser completa o totalmente precisa. Todos los detalles, plazos y requisitos deben verificarse directamente en el documento original.",
        fr: "DocDigest doit être utilisé uniquement comme une aide pour gagner du temps, et non comme un substitut à la lecture de la communication officielle. Les informations essentielles générées peuvent ne pas être complètes ou totalement exactes. Tous les détails, délais et exigences doivent être vérifiés directement dans le document original.",
        it: "DocDigest deve essere utilizzato solo come aiuto per risparmiare tempo, non come sostituto della lettura della comunicazione ufficiale. Le informazioni essenziali generate potrebbero non essere complete o del tutto accurate. Tutti i dettagli, le scadenze e i requisiti devono essere verificati direttamente nel documento originale.",
        pt: "O DocDigest deve ser utilizado apenas como uma ajuda para poupar tempo, não como um substituto para a leitura da comunicação oficial. As informações essenciais geradas podem não estar completas ou totalmente corretas. Todos os detalhes, prazos e requisitos devem ser verificados diretamente no documento original."
    };

    // Simple language detection
    let lang = 'en';
    const lower = text.toLowerCase();
    if (lower.includes(' de ') || lower.includes(' el ') || lower.includes(' la ')) lang = 'es';
    if (lower.includes(' le ') || lower.includes(' les ') || lower.includes(' et ')) lang = 'fr';
    if (lower.includes(' il ') || lower.includes(' della ') || lower.includes(' che ')) lang = 'it';
    if (lower.includes(' do ') || lower.includes(' da ') || lower.includes(' uma ')) lang = 'pt';

    // Fine-tune ES vs PT/FR overlap if needed, but this is a decent heuristic for now
    if (lang === 'es' && (lower.includes(' o ') || lower.includes(' os '))) lang = 'pt';

    disclaimerDiv.textContent = disclaimers[lang] || disclaimers['en'];
    disclaimerDiv.classList.remove('hidden');
}
