<?php
// public_html/download.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'], $_POST['format'])) {
    $content = $_POST['content'];
    $format = $_POST['format'];
    $filename = 'DocDigest_Simplified_' . date('Y-m-d_H-i') . '.' . $format;

    if ($format === 'txt') {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;
    } elseif ($format === 'docx') {
        // Simple HTML-based DOCX (Works in most Word processors)
        header('Content-Type: application/vnd.ms-word');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $htmlContent = "<html><body>";
        $htmlContent .= "<h1>DocDigest Simplified Document</h1>";
        $htmlContent .= "<p>Generated on: " . date('Y-m-d H:i') . "</p>";
        $htmlContent .= "<hr>";
        $htmlContent .= nl2br(htmlspecialchars($content));
        $htmlContent .= "</body></html>";

        echo $htmlContent;
        exit;
    }
}

// Redirect if invalid
header('Location: index.php');
exit;
