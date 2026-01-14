const express = require('express');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const multer = require('multer');
const path = require('path');

const envPath = path.join(__dirname, '../config/.env');
console.log(`Loading .env from: ${envPath}`);
const result = require('dotenv').config({ path: envPath });
if (result.error) {
    console.error("Error loading .env file:", result.error);
} else {
    console.log(".env loaded successfully");
}

const { analyzeDocumentStream } = require('./modules/ia');
const { extractText } = require('./services/extract');


const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.static(path.join(__dirname, '../frontend')));

// Rate Limiting
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // Limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP, please try again later.'
});
app.use('/api/', limiter);

// File Upload (Memory Storage)
const upload = multer({
    storage: multer.memoryStorage(),
    limits: { fileSize: 5 * 1024 * 1024 } // 5MB limit
});

// Routes
app.post('/api/analyze', upload.single('document'), async (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({ error: 'No file uploaded' });
        }

        // 1. Extract Text
        console.log(`Processing file: ${req.file.originalname} (${req.file.mimetype})`);
        const text = await extractText(req.file.buffer, req.file.mimetype);

        if (!text || text.trim().length === 0) {
            return res.status(400).json({ error: 'Could not extract text from document' });
        }

        // 2. Stream AI Analysis
        res.setHeader('Content-Type', 'text/plain; charset=utf-8');
        res.setHeader('Transfer-Encoding', 'chunked');

        await analyzeDocumentStream(text, res);

    } catch (error) {
        console.error('Server Error:', error);
        if (!res.headersSent) {
            res.status(500).json({ error: error.message });
        } else {
            res.end();
        }
    }
});

// Start Server
app.listen(port, () => {
    console.log(`Server running at http://localhost:${port}`);
});
