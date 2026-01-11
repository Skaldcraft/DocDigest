
<?php
// Limpieza de caché OPCache temporal (quitar después de testing)
opcache_reset(); // Solo para testing, quítalo después
// public_html/process.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';
define('MAX_FILE_SIZE_MB', 10);

// Helper Function: Parse DOCX
function readDocx($filename)
{
    if (!file_exists($filename))
        return false;

    $zip = new ZipArchive;
    if ($zip->open($filename) === TRUE) {
        if (($index = $zip->locateName('word/document.xml')) !== false) {
            $data = $zip->getFromIndex($index);
            $zip->close();

            $xml = new DOMDocument();
            $xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

            $text = '';
            foreach ($xml->getElementsByTagName('p') as $p) {
                $text .= $p->textContent . "\n";
            }
            return $text;
        }
        $zip->close();
    }
    return "Error reading .docx file. Ensure it is a valid format.";
}

// Helper Function: Parse Text from PDF
function readPdf($filename)
{
    if (!file_exists($filename)) {
        return "PDF file not found.";
    }

    $content = file_get_contents($filename);

    if (!$content) {
        return "Error reading PDF file.";
    }

    if (strpos($content, '%PDF') !== 0) {
        return "Invalid PDF file format.";
    }

    $text = '';

    if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
        foreach ($matches[1] as $textBlock) {
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $textBlock, $textMatches)) {
                foreach ($textMatches[1] as $t) {
                    $text .= $t . ' ';
                }
            }
            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $textBlock, $textMatches)) {
                foreach ($textMatches[1] as $t) {
                    $cleaned = preg_replace('/\(([^)]+)\)/', '$1 ', $t);
                    $cleaned = preg_replace('/-?\d+/', '', $cleaned);
                    $text .= $cleaned;
                }
            }

                    // Leer idioma del POST
                    $lang = $_POST['language'] ?? 'en';
                    $langNames = [
                      'en' => 'English',
                      'es' => 'Spanish',
                      'fr' => 'French',
                      'de' => 'German',
                      'it' => 'Italian',
                      'cn' => 'Chinese'
                    ];
                    $langName = $langNames[$lang] ?? 'English';

        }
    }

    if (strlen(trim($text)) < 50) {
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $streams)) {
            foreach ($streams[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $stream = $decompressed;
                }

                            // Incluir idioma en el prompt
                            $instruction = "You are a helpful assistant for a document. The user asks a question about the document provided below. ".
                                "Always respond in $langName. Answer briefly, clearly, and friendly.\n\nUser Question: $question";

                if (preg_match_all('/\((.*?)\)/s', $stream, $textMatches)) {
                    foreach ($textMatches[1] as $t) {
                        if (strlen($t) > 2 && ctype_print(str_replace(["\n", "\r", "\t"], '', $t))) {
                            $text .= $t . ' ';
                        }
                    }
                }
            }
        }
    }

    $text = str_replace(['\\(', '\\)', '\\n', '\\r', '\\t'], ['(', ')', "\n", "\r", "\t"], $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (strlen($text) < 10) {
        return "Could not extract text from PDF. The PDF might be:\n" .
            "- Scanned images (use the Image Upload tab with OCR instead)\n" .
            "- Encrypted or protected\n" .
            "- Using complex formatting\n\n" .
            "Try copying the text manually and using the 'Paste Text' option.";
    }

    return $text;
}

// Gemini API Function
function askGemini($instruction, $contextSource)
{
    $apiKey = AI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    $combinedPrompt = $instruction . "\n\nSource Text:\n" . $contextSource;

                        $finalOutput = simplifyTextWithAI($redacted, $langName);
        "contents" => [
            [
                "parts" => [
                    ["text" => $combinedPrompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $ch_response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Gemini API Error: " . $curlError);
        return "Error connecting to AI service: " . $curlError;
    }

    if ($httpCode !== 200) {
        $errorLog = "API Error Details:\n";
        $errorLog .= "HTTP Code: $httpCode\n";
        $errorLog .= "URL: $url\n";
        $errorLog .= "Response: " . ($ch_response ? $ch_response : "No response") . "\n";
        error_log($errorLog);

        $errorDetails = $ch_response ? " | Response: " . substr($ch_response, 0, 500) : "";
        return "AI Service Error (HTTP $httpCode).$errorDetails";
    }

    $json = json_decode($ch_response, true);

    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return $json['candidates'][0]['content']['parts'][0]['text'];
    } else {
        $debugInfo = $ch_response ? " Debug: " . substr($ch_response, 0, 300) : "";
        return "Could not parse AI response.$debugInfo";
    }
}

// Simplify Document with AI
function simplifyTextWithAI($text)
{
     $instruction = <<<'EOD'
You are DocDigest. You are an assistant that translates official and bureaucratic documents into clear and accessible language.

IMPORTANT: Always respond in the SAME LANGUAGE as the source document. If the document is in Spanish, respond in Spanish. If in English, respond in English.

OBJECTIVE:
Extract essential information in a format that allows understanding it in seconds, prioritizing what is urgent and what requires action from the recipient.

FORMAT RULES:

1. HEADER:
    - Line 1: [AGENCY] - [Document type in simple language]
    - Example: "SEPE - Unemployment benefit" / "Tax Agency - Payment claim" / "Court - Summons"

2. STATUS/OUTCOME (if applicable):
    - Use ✅ for approvals/favorable outcomes
    - Use ❌ for denials/unfavorable outcomes
    - Use ⚠️ for alerts or situations requiring attention
    - One keyword: Approved/Denied/Pending/Required

3. KEY INFORMATION:
    - Format: **Label:** Value
    - Prioritize: amounts, dates, deadlines, obligations
    - Translate technical terms: "prestación contributiva" → "unemployment benefit", "recurso de reposición" → "appeal"
    - If you mention a technical term for the first time, clarify it in parentheses

4. DEADLINES AND URGENT ACTIONS:
    - Always at the beginning, after the status
    - Convert relative deadlines into absolute dates
    - Example: "one month from receipt" → "Until February 3, 2026"
    - If a deadline has already passed: "⚠️ WARNING: This deadline ALREADY EXPIRED on [date]. Contact [agency] urgently to know your options."

5. SUMMONS/APPOINTMENTS:
    - Format: The summoned person must appear on [day] at [time] at [complete address]
    - Indicate what to bring if specified in the document

6. ANONYMIZATION:
    - DO NOT include personal names (replace with "the recipient" or "the person receiving this document")
    - DO NOT include ID numbers, personal addresses, phone numbers
    - DO NOT include names of officials/signatories unless necessary for action (e.g., "must meet with Dr. Pérez")
    - DO NOT include case numbers unless their importance is explicitly mentioned
    - DO include agency addresses if physical attendance is required

7. TONE:
    - Neutral and informative
    - Cordial but without emotional involvement
    - Direct and concise
    - Conversational but professional

8. FINAL NOTES (if applicable):
    - Non-urgent complementary information
    - Format: brief note at the very end
    - Example: "Providing the case number will speed up the process"

GENERAL STRUCTURE:
[AGENCY] - [Document type]

[Status with icon if applicable]

⚠️ [Urgent action/Deadline] (if exists)

**Key data 1:** value
**Key data 2:** value
**Key data 3:** value

[Complementary information if relevant]

[Final note if applicable]
EOD;

    return askGemini($instruction, $text);
}

// Answer Chat Questions
function answerChatQuestion($question, $context)
{
    $instruction = "You are a helpful assistant for a document. The user asks a question about the document provided below. " .
        "Answer briefly, clearly, and friendly. Answer in the same language as the User's Question." .
        "\n\nUser Question: " . $question;

    return askGemini($instruction, $context);
}

// Sensitive Info Redaction
function redactSensitiveData($text)
{
    $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL REDACTED]', $text);
    $text = preg_replace('/(\+\d{1,3}[- ]?)?\d{10}/', '[PHONE REDACTED]', $text);
    $text = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[ID REDACTED]', $text);
    return $text;
}

// Main Logic
$textToSimplify = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'text';

    // Chat Request Handling
    if ($type === 'chat_question') {
        header('Content-Type: application/json');
        $question = $_POST['question'] ?? '';
        $context = $_SESSION['doc_context'] ?? '';

        if (empty($context)) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Please re-upload document.']);
            exit;
        }

        $answer = answerChatQuestion($question, $context);
        echo json_encode(['status' => 'success', 'answer' => $answer]);
        exit;
    }

    if ($type === 'file' && isset($_FILES['document'])) {
        $file = $_FILES['document'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                $error = "Invalid file type. Allowed: " . implode(", ", ALLOWED_EXTENSIONS);
            } else {
                $tmpName = $file['tmp_name'];

                if ($ext === 'txt') {
                    $textToSimplify = file_get_contents($tmpName);
                } elseif ($ext === 'docx') {
                    $textToSimplify = readDocx($tmpName);
                } elseif ($ext === 'pdf') {
                    $textToSimplify = readPdf($tmpName);
                } else {
                    $error = "Unsupported file format for server processing.";
                }
            }
        } else {
            $error = "File upload error code: " . $file['error'];
        }
    } elseif ($type === 'text' && isset($_POST['content'])) {
        $textToSimplify = trim($_POST['content']);
        if (strlen($textToSimplify) > 500000) {
            $error = "Text is too long. Please upload as a file instead.";
        }
    }
}

// Process Simplification
$finalOutput = "";
$hasSensitiveData = false;

if (!empty($textToSimplify)) {
    $redacted = redactSensitiveData($textToSimplify);
    if ($redacted !== $textToSimplify) {
        $hasSensitiveData = true;
    }

    $_SESSION['doc_context'] = $redacted;
    $finalOutput = simplifyTextWithAI($redacted);
} else if (empty($error)) {
    $error = "No content provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocDigest - Result</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="main-header">
            <h1 class="logo">DocDigest</h1>
            <div class="flags-container" style="display:flex; gap:10px; margin-left: 20px; align-items:center;">
                <img src="https://flagcdn.com/w40/us.png" alt="US" title="English" class="lang-flag" data-lang="en" style="cursor:pointer; width:24px; transition:transform 0.2s;">
                <img src="https://flagcdn.com/w40/es.png" alt="ES" title="Español" class="lang-flag" data-lang="es" style="cursor:pointer; width:24px; transition:transform 0.2s;">
                <img src="https://flagcdn.com/w40/fr.png" alt="FR" title="Français" class="lang-flag" data-lang="fr" style="cursor:pointer; width:24px; transition:transform 0.2s;">
                <img src="https://flagcdn.com/w40/de.png" alt="DE" title="Deutsch" class="lang-flag" data-lang="de" style="cursor:pointer; width:24px; transition:transform 0.2s;">
                <img src="https://flagcdn.com/w40/it.png" alt="IT" title="Italiano" class="lang-flag" data-lang="it" style="cursor:pointer; width:24px; transition:transform 0.2s;">
                <img src="https://flagcdn.com/w40/cn.png" alt="CN" title="中文" class="lang-flag" data-lang="cn" style="cursor:pointer; width:24px; transition:transform 0.2s;">
            </div>
        </header>

        <main class="app-interface">
            <div class="card">
                <h2 id="result-title">Simplified Document</h2>

                <?php if ($error): ?>
                    <div class="status-message" style="background: #FECACA; color: #7F1D1D;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="index.php" class="btn secondary">Try Again</a>
                    </div>
                <?php else: ?>

                    <?php if ($hasSensitiveData): ?>
                        <div class="status-message">
                            <strong>Notice:</strong> Sensitive information (emails, phones, IDs) has been redacted for your privacy.
                        </div>
                    <?php endif; ?>

                    <div class="output-content">
                        <?php
                        $cleanOutput = preg_replace('/^```json\s*|\s*```$/s', '', trim($finalOutput));
                        $jsonData = json_decode($cleanOutput, true);

                        if (!$jsonData) {
                            $jsonData = json_decode($finalOutput, true);
                        }

                        if ($jsonData && isset($jsonData['sections'])) {
                            if (isset($jsonData['document_title'])) {
                                echo '<div class="document-title" style="font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; color: var(--primary);">';
                                echo htmlspecialchars($jsonData['document_title']);
                                echo '</div>';
                            }

                            foreach ($jsonData['sections'] as $section) {
                                $isBoilerplate = ($section['type'] ?? 'relevant') === 'boilerplate';
                                $sectionClass = $isBoilerplate ? 'section-boilerplate' : 'section-relevant';

                                echo '<div class="document-section ' . $sectionClass . '" style="margin-bottom: 1.5rem; padding: 1.5rem; border-radius: 12px; ' .
                                    ($isBoilerplate ? 'background: rgba(156, 163, 175, 0.1); border-left: 4px solid #9CA3AF;' : 'background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(59, 130, 246, 0.05)); border-left: 4px solid var(--primary);') . '">';

                                echo '<h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.75rem; color: ' .
                                    ($isBoilerplate ? '#6B7280' : 'var(--primary)') . ';">';
                                echo htmlspecialchars($section['title']);
                                echo '</h3>';

                                echo '<div style="line-height: 1.7; white-space: pre-wrap; color: ' .
                                    ($isBoilerplate ? '#6B7280' : 'var(--text-primary)') . ';">';
                                echo htmlspecialchars($section['content']);
                                echo '</div>';

                                echo '</div>';
                            }
                        } else {
                            echo '<div style="white-space: pre-wrap; line-height: 1.7;">';
                            echo htmlspecialchars($finalOutput);
                            echo '</div>';
                        }
                        ?>
                    </div>

                    <div class="actions" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
                        <a href="index.php" class="btn secondary">Simplify Another</a>
                        <form action="download.php" method="POST" target="_blank" style="display:contents;">
                            <input type="hidden" name="content" value="<?php echo htmlspecialchars($finalOutput); ?>">
                            <button type="submit" name="format" value="txt" class="btn primary">Download TXT</button>
                            <button type="submit" name="format" value="docx" class="btn primary">Download DOCX</button>
                        </form>
                    </div>

                    <div class="chat-section">
                        <div class="chat-header">
                            <h3 id="chat-title">💬 Ask Questions about this Document</h3>
                            <p id="chat-subtitle" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                                Need to clarify something? Ask DocDigest below.
                            </p>
                        </div>
                        <div id="chatBox" class="chat-box"></div>
                        <div class="chat-input-area">
                            <input type="text" id="chatInput" placeholder="">
                            <button id="sendChatBtn" class="btn primary">Send</button>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/i18n.js?v=2"></script>
    <script src="assets/script.js?v=2"></script>
</body>
</html>