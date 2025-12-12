
// Add processing timer functionality with spinner
document.addEventListener('DOMContentLoaded', () => {
    const fileForm = document.getElementById('fileForm');
    const textForm = document.getElementById('textForm');

    function addProcessingTimer(form) {
        if (!form) return;

        form.addEventListener('submit', (e) => {
            // Create processing indicator with spinner
            const processingDiv = document.createElement('div');
            processingDiv.id = 'processing-indicator';
            processingDiv.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2.5rem 3rem;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                z-index: 9999;
                text-align: center;
                min-width: 300px;
            `;

            // Create spinner
            const spinner = document.createElement('div');
            spinner.style.cssText = `
                width: 60px;
                height: 60px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid var(--accent-color, #FF6B6B);
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 1.5rem auto;
            `;

            const messageP = document.createElement('p');
            messageP.textContent = 'Processing your document...';
            messageP.style.cssText = `
                margin: 0;
                color: var(--text-muted);
                font-size: 1.1rem;
                font-weight: 500;
            `;

            processingDiv.appendChild(spinner);
            processingDiv.appendChild(messageP);

            // Add spinner animation if not exists
            if (!document.getElementById('spinner-animation')) {
                const style = document.createElement('style');
                style.id = 'spinner-animation';
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(processingDiv);
        });
    }

    addProcessingTimer(fileForm);
    addProcessingTimer(textForm);
});
