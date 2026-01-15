const OpenAI = require('openai');
const path = require('path');

const apiKey = process.env.DEEPSEEK_API_KEY;
console.log("IA Module Loaded.");
if (!apiKey) {
    console.error("CRITICAL: DEEPSEEK_API_KEY is missing in process.env");
} else {
    // Mask key for safety log
    const lastFour = apiKey.substring(Math.max(0, apiKey.length - 4));
    console.log(`Using OpenRouter API Key ending in: ...${lastFour}`);
}

// Initialize OpenAI client pointing to OpenRouter
const openai = new OpenAI({
    apiKey: apiKey,
    baseURL: 'https://openrouter.ai/api/v1',
    defaultHeaders: {
        "HTTP-Referer": "http://localhost:3000", // Optional, for OpenRouter rankings
        "X-Title": "DocDigest", // Optional
    }
});

async function analyzeDocumentStream(text, res) {
    const systemPrompt = `
You are DocDigest, an expert in simplifying bureaucratic documents for citizens.
Detect the language of the input document and respond in that SAME language.

### RESPONSE STRUCTURE (Translate these labels to the output language):
1. HEADER: 
   **[DOCUMENT TYPE IN SIMPLE LANGUAGE]**
   ENTITY: [Agency Name]

2. STATUS: Use exactly one (Translated): ✅ Approved, ❌ Denied, ⚠️ Alert, 📅 Appointment, 🧾 Required Docs.
3. DEADLINES: "**[Deadlines Label]**: [Date]" (Must be highlighted. Convert relative dates to absolute).
4. KEY DATA: **[Label]**: [Value]
5. SUMMONS: "**[Summons Label]**: The holder must appear on [day] at [time] (see address in document)."
6. FINAL NOTES: "**[Notes Label]**: Concise non-urgent info + 'Check original document'."

### RULES:
- LANGUAGE: Use the same language as the document for EVERYTHING (content and labels).
- STYLE: Extremely simple, direct, and concise (for everyone to understand).
- EXAMPLES OF STYLE:
  * Bad: "La persona citada debe presentarse el 20 de enero..." 
  * Good: "El titular debe presentarse el 20 de enero (ver dirección en documento)."
  * Bad: "Se permite designar un representante legal con autorización escrita..."
  * Good: "Si no puede asistir, puede asignar un representante (necesaria autorización por escrito, ver detalles en documento)."
- HIGHLIGHTING: Always include a clear alert for deadlines or due dates.
- ANONYMIZE: Never include personal names, IDs, or specific house addresses. Use "the holder" or "the recipient".
`;

    try {
        const stream = await openai.chat.completions.create({
            model: "deepseek/deepseek-r1:free", // Standard free ID is usually enough
            messages: [
                { role: "system", content: systemPrompt },
                { role: "user", content: `Current Date: ${new Date().toISOString().split('T')[0]}\n\nDocument Content:\n${text}` }
            ],
            stream: true,
            temperature: 0.3
        });

        for await (const chunk of stream) {
            const content = chunk.choices[0]?.delta?.content || "";
            if (content) {
                res.write(content);
            }
        }
        res.end();

    } catch (error) {
        console.error("AI Error Details:", JSON.stringify(error, null, 2));
        const errMsg = error.status === 401 ? "Invalid API Key" : "Connection Error with AI Provider (Try again)";
        res.write(`\n\n[Error: ${errMsg}]`);
        res.end();
    }
}

module.exports = { analyzeDocumentStream };
