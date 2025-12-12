<?php
// public_html/process.php
session_start(); // Start session to store document context for chat
include 'config.php';

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

// Helper Function: Parse Text from PDF (Native PHP Implementation)
function readPdf($filename)
{
    if (!file_exists($filename)) {
        return "PDF file not found.";
    }

    // Read the PDF file
    $content = file_get_contents($filename);

    if (!$content) {
        return "Error reading PDF file.";
    }

    // Check if it's a valid PDF
    if (strpos($content, '%PDF') !== 0) {
        return "Invalid PDF file format.";
    }

    // Extract text from PDF
    // This is a simplified approach that works for many text-based PDFs
    $text = '';

    // Method 1: Extract text between BT (Begin Text) and ET (End Text) operators
    if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
        foreach ($matches[1] as $textBlock) {
            // Extract text from Tj and TJ operators
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $textBlock, $textMatches)) {
                foreach ($textMatches[1] as $t) {
                    $text .= $t . ' ';
                }
            }
            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $textBlock, $textMatches)) {
                foreach ($textMatches[1] as $t) {
                    // Remove positioning numbers and extract text
                    $cleaned = preg_replace('/\(([^)]+)\)/', '$1 ', $t);
                    $cleaned = preg_replace('/-?\d+/', '', $cleaned);
                    $text .= $cleaned;
                }
            }
        }
    }

    // Method 2: Try to extract from stream objects if Method 1 didn't work well
    if (strlen(trim($text)) < 50) {
        // Look for text in stream objects
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $streams)) {
            foreach ($streams[1] as $stream) {
                // Try to decompress if it's a FlateDecode stream
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $stream = $decompressed;
                }

                // Extract readable text
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

    // Clean up the extracted text
    $text = str_replace(['\\(', '\\)', '\\n', '\\r', '\\t'], ['(', ')', "\n", "\r", "\t"], $text);
    $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
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

// Real Gemini API Simplification
function askGemini($instruction, $contextSource)
{
    $apiKey = AI_API_KEY;
    // Using gemini-2.5-flash - latest fast model (confirmed available via API test)
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    // Prompt Engineering
    $combinedPrompt = $instruction . "\n\nSource Text:\n" . $contextSource;

    $data = [
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
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $ch_response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return "Error connecting to AI service: " . $curlError;
    }

    if ($httpCode !== 200) {
        // Log detailed error for debugging
        $errorLog = "API Error Details:\n";
        $errorLog .= "HTTP Code: $httpCode\n";
        $errorLog .= "URL: $url\n";
        $errorLog .= "Response: " . ($ch_response ? $ch_response : "No response") . "\n";
        error_log($errorLog);

        $errorDetails = $ch_response ? " | Response: " . substr($ch_response, 0, 500) : "";
        return "AI Service Error (HTTP $httpCode).$errorDetails";
    }

    $json = json_decode($ch_response, true);

    // Extract text from Gemini response structure
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return $json['candidates'][0]['content']['parts'][0]['text'];
    } else {
        $debugInfo = $ch_response ? " Debug: " . substr($ch_response, 0, 300) : "";
        return "Could not parse AI response.$debugInfo";
    }
}

function simplifyTextWithAI($text)
{
    $instruction = "You are DocDigest. Your task is to extract the ESSENTIAL information from official documents into valid JSON format.\n\n" .

        "**CRITICAL CONTENT RULES:**\n" .
        "1. **TONE**: Use IMPERSONAL language ('" . 'Se ha decidido...' . "', '" . 'El titular...' . "', '" . 'La solicitud...' . "'). NEVER address the user as '" . 'tÃº' . "' or '" . 'usted' . "'.\n" .
        "2. **STRUCTURE**: You MUST preserve the ORIGINAL SECTION TITLES of the document (e.g., '" . 'ANTECEDENTES' . "', '" . 'ACUERDO' . "', '" . 'RECURSOS' . "'). Do not invent new headers unless the document has absolutely no structure.\n" .
        "3. **PRIVACY**: REMOVE all personal data (Names, NIF/IDs, Phone numbers, Emails, Addresses). Replace with generic terms like '" . '[El titular]' . "', '" . '[Municipio]' . "'.\n" .
        "4. **NO FILLER**: \n" .
        "   - DELETE office addresses, verification codes (CSV), names of officials who signed.\n" .
        "   - DELETE generic legal definitions ('" . 'Se detallan las leyes...' . "').\n" .
        "   - DELETE 'procedural' narration ('" . 'Aparece que se basan en...' . "').\n" .
        "   - KEEP concrete facts: Decisions, Dates, Amounts, Grants, Deadlines.\n\n" .

        "**DRAFTING RULES:**\n" .
        "- **Concise**: '" . 'No se ha aceptado tu solicitud' . "' is better than '" . 'Se ha decidido rechazar...' . "' (Avoid '" . 'Esto significa que...' . "').\n" .
        "- **Direct**: State the outcome immediately.\n" .
        "- **Deadlines**: If a deadline is mentioned (e.g., '" . 'un mes' . "'), EXTRACT IT explicitly. It is crucial.\n" .
        "- **Contextual Help**: Only suggest '" . 'puedes pedirme una lista de documentos' . "' if the text explicitly asks for documentation to be submitted.\n\n" .

        "**EXAMPLES OF EXTRACTION:**\n\n" .

        "ðŸ”´ ORIGINAL: '" . 'TÃº, D. Juan con NIF X, presentaste solicitud el 6/2/21. El 16/5/21 un equipo te valorÃ³...' . "'\n" .
        "ðŸŸ¢ RESULT: '" . 'El titular presentÃ³ una solicitud de reconocimiento de dependencia el 6 de febrero de 2021.\\nSe realizÃ³ la valoraciÃ³n el 16 de mayo de 2021 y el informe tÃ©cnico confirmÃ³ limitaciones para las actividades bÃ¡sicas.' . "'\n\n" .

        "ðŸ”´ ORIGINAL: '" . 'ACUERDO: Desestimar la solicitud. Fundamentos: Ley 44/2000...' . "'\n" .
        "ðŸŸ¢ RESULT: '" . 'La Agencia Tributaria no ha aceptado las alegaciones presentadas.' . "' (Skip the laws)\n\n" .

        "ðŸ”´ ORIGINAL: '" . 'Se puede interponer recurso de reposiciÃ³n en el plazo de un mes...' . "'\n" .
        "ðŸŸ¢ RESULT: '" . 'Esta resoluciÃ³n se puede recurrir.\\nPlazo: Un mes a partir del dÃ­a siguiente a la recepciÃ³n.\\nSi se decide recurrir, es importante revisar bien los requisitos.' . "'\n\n" .

        "**OUTPUT FORMAT (JSON):**\n" .
        "{\n" .
        "  \"document_title\": \"Descriptive Title (e.g., '" . 'Respuesta a solicitud de rectificaciÃ³n' . "')\",\n" .
        "  \"sections\": [\n" .
        "    {\n" .
        "      \"title\": \"EXACT ORIGINAL HEADER\",\n" .
        "      \"type\": \"relevant\",\n" .
        "      \"content\": \"Impersonal, concise extracted info.\"\n" .
        "    }\n" .
        "  ]\n" .
        "}\n\n" .
        "Rules for 'type': Use 'boilerplate' ONLY for sections that are purely legal strings with no specific info (which should properly be skipped or minimized).";

    return askGemini($instruction, $text);
}

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
    // Emails
    $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL REDACTED]', $text);
    // Phone Numbers (Generic)
    $text = preg_replace('/(\+\d{1,3}[- ]?)?\d{10}/', '[PHONE REDACTED]', $text);
    // SSN / ID patterns (Generic XXX-XX-XXXX)
    $text = preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[ID REDACTED]', $text);
    return $text;
}

// Main Logic
$textToSimplify = "";
$error = "";
$isAjaxChat = false;

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
        $textToSimplify = $_POST['content'];
    }
}

// Process Simplification
$finalOutput = "";
$hasSensitiveData = false;

if (!empty($textToSimplify)) {
    // Redact
    $redacted = redactSensitiveData($textToSimplify);
    if ($redacted !== $textToSimplify) {
        $hasSensitiveData = true;
    }

    // Store context for Chat (Store the redacted version for privacy)
    $_SESSION['doc_context'] = $redacted;

    // Simplify
    $finalOutput = simplifyTextWithAI($redacted);
} else if (empty($error)) {
    $error = "No content provided.";
}

// Render Result Page
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
        </header>

        <main class="app-interface">
            <div class="card">
                <h2>Simplified Document</h2>

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
                            <strong>Notice:</strong> Sensitive information (emails, phones, IDs) has been redacted for your
                            privacy.
                        </div>
                    <?php endif; ?>

                    <div class="output-content">
                        <?php
                        // Try to parse as JSON for structured output
                        $jsonData = json_decode($finalOutput, true);

                        if ($jsonData && isset($jsonData['sections'])) {
                            // Structured output
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
                            // Fallback to plain text if JSON parsing fails
                            echo '<div style="white-space: pre-wrap; line-height: 1.7;">';
                            echo htmlspecialchars($finalOutput);
                            echo '</div>';
                        }
                        ?>
                    </div>

                    <div class="actions" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
                        <a href="index.php" class="btn secondary">Simplify Another</a>
                        <!-- Download Form -->
                        <form action="download.php" method="POST" target="_blank" style="display:contents;">
                            <input type="hidden" name="content" value="<?php echo htmlspecialchars($finalOutput); ?>">
                            <button type="submit" name="format" value="txt" class="btn primary">Download TXT</button>
                            <button type="submit" name="format" value="docx" class="btn primary">Download DOCX</button>
                        </form>
                    </div>

                    <!-- Chat Section -->
                    <div class="chat-section">
                        <div class="chat-header">
                            <h3>💬 Ask Questions about this Document</h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                                Need to clarify something? Ask DocDigest below.
                            </p>
                        </div>
                        <div id="chatBox" class="chat-box">
                            <!-- Messages go here -->
                            <div class="chat-message ai">Hello! I've read the document. What would you like to know?</div>
                        </div>
                        <div class="chat-input-area">
                            <input type="text" id="chatInput" placeholder="e.g., Do I need to bring my ID?">
                            <button id="sendChatBtn" class="btn primary">Send</button>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Include script for chat functionality -->
    <script src="assets/script.js"></script>
</body>

</html>