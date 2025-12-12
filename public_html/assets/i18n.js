// Multi-language support with Privacy Translation & Result Page
const translations = {
    en: {
        tagline: "Understand official documents, fast and easy.",
        uploadFile: "Upload File",
        uploadImage: "Upload Image",
        pasteText: "Paste Text",
        dragDrop: "Drag & Drop your file here",
        or: "or",
        browseFiles: "Browse Files",
        maxSize: "PDF, DOCX, TXT (Max 10MB)",
        simplifyDocument: "Simplify Document",
        uploadImageText: "Upload an image of printed text",
        selectImage: "Select Image",
        extractSimplify: "Extract & Simplify",
        pasteHere: "Paste the official text here...",
        simplifyText: "Simplify Text",
        securePrivate: "Secure & Private",
        secureDesc: "Files are auto-deleted after processing.",
        interactiveAI: "Interactive AI",
        interactiveDesc: "Ask questions to your document.",
        multilingual: "Multilingual",
        multilingualDesc: "Works in any major language.",
        privacyPolicy: "Privacy Policy",
        uploadedSuccess: "✓ Uploaded successfully",
        redactedSensitive: "Sensitive information (emails, phones, IDs) has been redacted for your privacy.",
        resultTitle: "Simplified Document",
        chatTitle: "💬 Ask Questions about this Document",
        chatSubtitle: "Need to clarify something? Ask DocDigest below.",
        sendChatBtn: "Send"
    },
    es: {
        tagline: "Entiende documentos oficiales, rápido y fácil.",
        uploadFile: "Subir Archivo",
        uploadImage: "Subir Imagen",
        pasteText: "Pegar Texto",
        dragDrop: "Arrastra y suelta tu archivo aquí",
        or: "o",
        browseFiles: "Buscar Archivos",
        maxSize: "PDF, DOCX, TXT (Máx 10MB)",
        simplifyDocument: "Simplificar Documento",
        uploadImageText: "Sube una imagen de texto impreso",
        selectImage: "Seleccionar Imagen",
        extractSimplify: "Extraer y Simplificar",
        pasteHere: "Pega el texto oficial aquí...",
        simplifyText: "Simplificar Texto",
        securePrivate: "Seguro y Privado",
        secureDesc: "Los archivos se eliminan automáticamente después del procesamiento.",
        interactiveAI: "IA Interactiva",
        interactiveDesc: "Haz preguntas sobre tu documento.",
        multilingual: "Multiidioma",
        multilingualDesc: "Funciona en cualquier idioma principal.",
        privacyPolicy: "Política de Privacidad",
        uploadedSuccess: "✓ Subido exitosamente",
        redactedSensitive: "Se ha ocultado información sensible (emails, teléfonos, DNI) por tu privacidad.",
        resultTitle: "Documento Simplificado",
        chatTitle: "💬 Haz preguntas sobre este documento",
        chatSubtitle: "¿Necesitas aclarar algo? Pregúntale a DocDigest abajo.",
        sendChatBtn: "Enviar"
    },
    fr: {
        tagline: "Comprenez les documents officiels, rapidement et facilement.",
        uploadFile: "Télécharger un Fichier",
        uploadImage: "Télécharger une Image",
        pasteText: "Coller du Texte",
        dragDrop: "Glissez-déposez votre fichier ici",
        or: "ou",
        browseFiles: "Parcourir les Fichiers",
        maxSize: "PDF, DOCX, TXT (Max 10Mo)",
        simplifyDocument: "Simplifier le Document",
        uploadImageText: "Téléchargez une image de texte imprimé",
        selectImage: "Sélectionner une Image",
        extractSimplify: "Extraire et Simplifier",
        pasteHere: "Collez le texte officiel ici...",
        simplifyText: "Simplifier le Texte",
        securePrivate: "Sécurisé et Privé",
        secureDesc: "Les fichiers sont automatiquement supprimés après traitement.",
        interactiveAI: "IA Interactive",
        interactiveDesc: "Posez des questions sur votre document.",
        multilingual: "Multilingue",
        multilingualDesc: "Fonctionne dans toutes les langues principales.",
        privacyPolicy: "Politique de Confidentialité",
        uploadedSuccess: "✓ Téléchargé avec succès",
        redactedSensitive: "Les informations sensibles (emails, téléphones, identifiants) ont été masquées pour votre confidentialité.",
        resultTitle: "Document Simplifié",
        chatTitle: "💬 Posez des questions sur ce document",
        chatSubtitle: "Besoin de clarifier quelque chose ? Demandez à DocDigest ci-dessous.",
        sendChatBtn: "Envoyer"
    },
    de: {
        tagline: "Verstehen Sie offizielle Dokumente, schnell und einfach.",
        uploadFile: "Datei Hochladen",
        uploadImage: "Bild Hochladen",
        pasteText: "Text Einfügen",
        dragDrop: "Ziehen Sie Ihre Datei hierher",
        or: "oder",
        browseFiles: "Dateien Durchsuchen",
        maxSize: "PDF, DOCX, TXT (Max 10MB)",
        simplifyDocument: "Dokument Vereinfachen",
        uploadImageText: "Laden Sie ein Bild mit gedrucktem Text hoch",
        selectImage: "Bild Auswählen",
        extractSimplify: "Extrahieren & Vereinfachen",
        pasteHere: "Fügen Sie den offiziellen Text hier ein...",
        simplifyText: "Text Vereinfachen",
        securePrivate: "Sicher & Privat",
        secureDesc: "Dateien werden nach der Verarbeitung automatisch gelöscht.",
        interactiveAI: "Interaktive KI",
        interactiveDesc: "Stellen Sie Fragen zu Ihrem Dokument.",
        multilingual: "Mehrsprachig",
        multilingualDesc: "Funktioniert in jeder Hauptsprache.",
        privacyPolicy: "Datenschutzrichtlinie",
        uploadedSuccess: "✓ Erfolgreich hochgeladen",
        redactedSensitive: "Sensible Informationen (E-Mails, Telefonnummern, Ausweise) wurden zu Ihrer Privatsphäre geschwärzt.",
        resultTitle: "Vereinfachtes Dokument",
        chatTitle: "💬 Fragen zum Dokument stellen",
        chatSubtitle: "Müssen Sie etwas klären? Fragen Sie DocDigest unten.",
        sendChatBtn: "Senden"
    },
    it: {
        tagline: "Comprendi i documenti ufficiali, velocemente e facilmente.",
        uploadFile: "Carica File",
        uploadImage: "Carica Immagine",
        pasteText: "Incolla Testo",
        dragDrop: "Trascina e rilascia il tuo file qui",
        or: "o",
        browseFiles: "Sfoglia File",
        maxSize: "PDF, DOCX, TXT (Max 10MB)",
        simplifyDocument: "Semplifica Documento",
        uploadImageText: "Carica un'immagine di testo stampato",
        selectImage: "Seleziona Immagine",
        extractSimplify: "Estrai e Semplifica",
        pasteHere: "Incolla il testo ufficiale qui...",
        simplifyText: "Semplifica Testo",
        securePrivate: "Sicuro e Privato",
        secureDesc: "I file vengono eliminati automaticamente dopo l'elaborazione.",
        interactiveAI: "IA Interattiva",
        interactiveDesc: "Fai domande sul tuo documento.",
        multilingual: "Multilingue",
        multilingualDesc: "Funziona in qualsiasi lingua principale.",
        privacyPolicy: "Informativa sulla Privacy",
        uploadedSuccess: "✓ Caricato con successo",
        redactedSensitive: "Le informazioni sensibili (email, telefoni, documenti) sono state oscurate per la tua privacy.",
        resultTitle: "Documento Semplificato",
        chatTitle: "💬 Fai domande su questo documento",
        chatSubtitle: "Hai bisogno di chiarimenti? Chiedi a DocDigest qui sotto.",
        sendChatBtn: "Invia"
    },
    cn: {
        tagline: "快速轻松地理解官方文件。",
        uploadFile: "上传文件",
        uploadImage: "上传图片",
        pasteText: "粘贴文本",
        dragDrop: "将文件拖放到此处",
        or: "或",
        browseFiles: "浏览文件",
        maxSize: "PDF, DOCX, TXT (最大10MB)",
        simplifyDocument: "简化文档",
        uploadImageText: "上传打印文本的图片",
        selectImage: "选择图片",
        extractSimplify: "提取并简化",
        pasteHere: "在此粘贴官方文本...",
        simplifyText: "简化文本",
        securePrivate: "安全私密",
        secureDesc: "文件在处理后自动删除。",
        interactiveAI: "互动AI",
        interactiveDesc: "向您的文档提问。",
        multilingual: "多语言",
        multilingualDesc: "适用于任何主要语言。",
        privacyPolicy: "隐私政策",
        uploadedSuccess: "✓ 上传成功",
        redactedSensitive: "敏感信息（电子邮件、电话、身份证）已根据您的隐私进行了遮盖。",
        resultTitle: "简化文档",
        chatTitle: "💬 关于此文档提问",
        chatSubtitle: "需要澄清某些内容吗？在下方询问 DocDigest。",
        sendChatBtn: "发送"
    }
};

// Language switcher logic
let currentLang = 'en';

function switchLanguage(lang) {
    currentLang = lang;
    const t = translations[lang];

    // Update all translatable elements via selectors
    const selectors = {
        '.tagline': t.tagline,
        'label[for="fileInput"]': t.browseFiles,
        '#fileDropZone .small-text': t.maxSize,
        'label[for="imageInput"]': t.selectImage,
        '#fileForm button[type="submit"]': t.simplifyDocument,
        '#startOcrBtn': t.extractSimplify,
        '#textForm button[type="submit"]': t.simplifyText,
        '.main-footer a': t.privacyPolicy,
        '#imageDropZone p': t.uploadImageText,

        // Result Page
        '#result-title': t.resultTitle,
        '#chat-title': t.chatTitle,
        '#chat-subtitle': t.chatSubtitle,
        '#sendChatBtn': t.sendChatBtn
    };

    for (const [selector, text] of Object.entries(selectors)) {
        const el = document.querySelector(selector);
        if (el) el.textContent = text;
    }

    // Tabs have specific indices
    const tabs = document.querySelectorAll('.tab-btn span:last-child');
    if (tabs.length >= 3) {
        tabs[0].textContent = t.uploadFile;
        tabs[1].textContent = t.uploadImage;
        tabs[2].textContent = t.pasteText;
    }

    // Dynamic content (Upload Drop Zone)
    const dragDropText = document.querySelector('#fileDropZone p');
    if (dragDropText && !dragDropText.innerHTML.includes('✓') && !dragDropText.querySelector('div')) {
        dragDropText.textContent = t.dragDrop;
    }
    const orSpan = document.querySelector('#fileDropZone span');
    if (orSpan) orSpan.textContent = t.or;

    // Placeholders
    const textarea = document.querySelector('#tab-text textarea');
    if (textarea) textarea.placeholder = t.pasteHere;

    // Feature items
    const features = document.querySelectorAll('.feature-item');
    if (features[0]) {
        features[0].querySelector('h3').textContent = t.securePrivate;
        features[0].querySelector('p').textContent = t.secureDesc;
    }
    if (features[1]) {
        features[1].querySelector('h3').textContent = t.interactiveAI;
        features[1].querySelector('p').textContent = t.interactiveDesc;
    }
    if (features[2]) {
        features[2].querySelector('h3').textContent = t.multilingual;
        features[2].querySelector('p').textContent = t.multilingualDesc;
    }

    // Privacy Sensitive Notice Redaction
    const statusMsg = document.querySelector('.status-message');
    if (statusMsg && statusMsg.textContent.includes('edacted') && !statusMsg.dataset.translated) {
        statusMsg.innerHTML = `<strong>${lang === 'en' ? 'Notice:' : (lang === 'es' ? 'Aviso:' : 'Note:')}</strong> ${t.redactedSensitive}`;
        statusMsg.dataset.translated = "true";
    }

    // Store preference
    localStorage.setItem('docdigest_lang', lang);
}

document.addEventListener('DOMContentLoaded', () => {
    // Load saved language
    const savedLang = localStorage.getItem('docdigest_lang') || 'en';
    switchLanguage(savedLang);

    // Flags (Using class selector now for both index and process pages)
    const flags = document.querySelectorAll('.lang-flag');

    // Fallback for index.php flags if they don't have the class yet (or old cached html)
    const compatFlags = document.querySelectorAll('.flags-container img');

    const allFlags = flags.length > 0 ? flags : compatFlags;

    const langMap = { 'us': 'en', 'es': 'es', 'fr': 'fr', 'de': 'de', 'it': 'it', 'cn': 'cn' };

    allFlags.forEach(flag => {
        flag.style.cursor = 'pointer';
        flag.style.transition = 'transform 0.2s ease';

        flag.addEventListener('click', () => {
            // Get lang from data attribute OR src regex fallback
            let lang = flag.dataset.lang;
            if (!lang) {
                const match = flag.src.match(/\/([a-z]{2})\.png/);
                if (match) lang = langMap[match[1]];
            }
            if (lang) switchLanguage(lang);
        });

        flag.addEventListener('mouseenter', () => flag.style.transform = 'scale(1.15)');
        flag.addEventListener('mouseleave', () => flag.style.transform = 'scale(1)');
    });
});
