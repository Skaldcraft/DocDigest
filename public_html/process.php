<?php
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
        }
    }

    if (strlen(trim($text)) < 50) {
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $streams)) {
            foreach ($streams[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $stream = $decompressed;
                }

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

// OpenAI API Function
function askOpenAI($instruction, $contextSource)
{
    $apiKey = OPENAI_API_KEY;
    $url = "https://api.openai.com/v1/chat/completions";

    $combinedPrompt = $instruction . "\n\nSource Text:\n" . $contextSource;

    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => $instruction],
            ["role" => "user", "content" => $contextSource]
        ],
        "temperature" => 0.7,
        "max_tokens" => 2048
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $ch_response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("OpenAI API Error: " . $curlError);
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

    if (isset($json['choices'][0]['message']['content'])) {
        return $json['choices'][0]['message']['content'];
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

CRITICAL: Always respond in the SAME LANGUAGE as the source document. Detect the language automatically:
- If the document is in Spanish, respond entirely in Spanish
- If the document is in English, respond entirely in English
- If the document is in French, respond entirely in French
- If the document is in German, respond entirely in German
- If the document is in Italian, respond entirely in Italian
- And so on for any language

OBJECTIVE:
Extract essential information in a format that allows understanding it in seconds, prioritizing what is urgent and what requires action from the recipient.

OUTPUT FORMAT - IMPORTANT:
Generate clean HTML (no Markdown). Use these HTML elements:

- <h2> for the main header: [AGENCY] - [Document type]
- <div class="status-approved"> ✅ Approved</div> (or status-denied, status-alert)
- <div class="urgent-action"> ⚠️ [Urgent action/Deadline]</div>
- <div class="key-info">
    <strong>Label:</strong> value<br>
    <strong>Label:</strong> value
  </div>
- <p> for additional information
- <div class="note">Final notes if applicable</div>

CSS classes available:
- .status-approved (green background)
- .status-denied (red background)
- .status-alert (yellow background)
- .urgent-action (bold, red border, large)
- .key-info (highlighted box)
- .note (gray, italic, small)

STYLE RULES:
- Use <strong> for ALL important data: amounts, dates, deadlines, names of places
- Keep it clean and scannable
- No Markdown syntax (**text**, - lists, etc.)
- Pure HTML only

FORMAT RULES:

1. HEADER:
   - Line 1: <h2>[AGENCY] - [Document type in simple language]</h2>
   - Example: <h2>SEPE - Unemployment benefit</h2>

2. STATUS/OUTCOME (if applicable):
   - <div class="status-approved">✅ Approved</div>
   - <div class="status-denied">❌ Denied</div>
   - <div class="status-alert">⚠️ Required</div>

3. KEY INFORMATION:
   - Wrap in <div class="key-info">...</div>
   - Use <strong> for labels and important values
   - Prioritize: amounts, dates, deadlines, obligations
   - Translate technical terms or clarify in parentheses

4. DEADLINES AND URGENT ACTIONS:
   - <div class="urgent-action">⚠️ DEADLINE: Until [date]</div>
   - Always at the beginning, after status
   - Convert relative deadlines into absolute dates
   - If expired: <div class="urgent-action">⚠️ WARNING: This deadline ALREADY EXPIRED on [date]. Contact [agency] urgently.</div>

5. SUMMONS/APPOINTMENTS:
   - The summoned person must appear on <strong>[day]</strong> at <strong>[time]</strong> at <strong>[complete address]</strong>
   - List what to bring with <br> tags

6. ANONYMIZATION:
   - DO NOT include personal names (replace with "the recipient")
   - DO NOT include ID numbers, personal addresses, phone numbers
   - DO NOT include names of officials unless necessary
   - DO include agency addresses if physical attendance required

7. TONE:
   - Neutral and informative
   - Direct and concise
   - Conversational but professional

8. FINAL NOTES:
   - <div class="note">Additional info here</div>

EXAMPLE OUTPUT:
<h2>Tax Agency - Appointment for tax review</h2>
<div class="urgent-action">⚠️ MANDATORY APPOINTMENT: January 20, 2026 at 9:30 AM</div>
<div class="key-info">
<strong>When:</strong> January 20, 2026 at 9:30 AM<br>
<strong>Where:</strong> Tax Agency - Gijón Office<br>
<strong>Reason:</strong> Review of 2024 income tax return
</div>
<p><strong>What to bring:</strong><br>
- Income and withholding certificates<br>
- Proof of deductions applied<br>
- Bank statements for 2024</p>
<p>If you cannot attend, you can send an authorized legal representative with written authorization.</p>
EOD;

    return askOpenAI($instruction, $text);
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
    <style>
        /* Styles for AI-generated HTML output */
        .output-content h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
        }
        
        .status-approved {
            background: #D1FAE5;
            color: #065F46;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-denied {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-alert {
            background: #FEF3C7;
            color: #92400E;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-weight: 600;
            display: inline-block;
        }
        
        .urgent-action {
            background: #FEE2E2;
            border: 2px solid #DC2626;
            color: #991B1B;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .key-info {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), rgba(59, 130, 246, 0.08));
            border-left: 4px solid var(--primary);
            padding: 1.25rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            line-height: 1.8;
        }
        
        .key-info strong {
            color: var(--primary);
            font-weight: 600;
        }
        
        .note {
            background: #F3F4F6;
            color: #6B7280;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .output-content p {
            line-height: 1.7;
            margin: 1rem 0;
        }
        
        .output-content strong {
            color: var(--text-primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="main-header">
            <h1 class="logo">DocDigest</h1>
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
                            echo $finalOutput;
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