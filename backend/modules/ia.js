const OpenAI = require('openai');
const path = require('path');

let openai = null; // Lazy initialization

function getOpenAIClient() {
    if (!openai) {
        const apiKey = process.env.DEEPSEEK_API_KEY;
        console.log("Initializing OpenAI client...");
        if (!apiKey) {
            throw new Error("CRITICAL: DEEPSEEK_API_KEY is missing in process.env");
        }
        const lastFour = apiKey.substring(Math.max(0, apiKey.length - 4));
        console.log(`Using OpenRouter API Key ending in: ...${lastFour}`);

        openai = new OpenAI({
            apiKey: apiKey,
            baseURL: 'https://openrouter.ai/api/v1',
            defaultHeaders: {
                "HTTP-Referer": "http://localhost:3000",
                "X-Title": "DocDigest",
            }
        });
    }
    return openai;
}

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
5. IMPORTANT DETAILS: Bullet points.
6. NEXT STEPS: Numbered list.
7. SIMPLIFIED EXPLANATION: One clear paragraph.

### OUTPUT FORMAT:
- Use **bold** for important terms.
- Keep output concise and scannable.
- Avoid filler words, be direct.
    `;

    try {
        const stream = await getOpenAIClient().chat.completions.create({
            model: 'google/gemini-2.0-flash-exp:free',
            messages: [
                { role: "system", content: systemPrompt },
                { role: "user", content: `Current Date: ${(new Date()).toISOString().split('T')[0]} \n\nDocument Text:\n${text}` }
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
        const errMsg = error.status === 401 ? "Invalid API Key" : "Connection Error with AI Provider";
        res.write(`\n[Error: ${errMsg}]`);
        res.end();
    }
}

module.exports = { analyzeDocumentStream };
