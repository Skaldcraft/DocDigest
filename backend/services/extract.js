const pdf = require('pdf-parse');
const mammoth = require('mammoth');
const Tesseract = require('tesseract.js');

async function extractText(fileBuffer, mimeType) {
    try {
        if (mimeType === 'application/pdf') {
            const data = await pdf(fileBuffer);
            return data.text;
        } else if (mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            const result = await mammoth.extractRawText({ buffer: fileBuffer });
            return result.value;
        } else if (mimeType === 'text/plain') {
            return fileBuffer.toString('utf-8');
        } else if (mimeType.startsWith('image/')) {
            // OCR for Images
            const { data: { text } } = await Tesseract.recognize(fileBuffer, 'eng+spa+fra+ita+por');
            return text;
        } else {
            throw new Error('Unsupported file type: ' + mimeType);
        }
    } catch (error) {
        console.error('Extraction Error:', error);
        throw new Error('Failed to extract text from document');
    }
}

module.exports = { extractText };
