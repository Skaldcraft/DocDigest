
// Add processing timer functionality
document.addEventListener('DOMContentLoaded', () => {
    const fileForm = document.getElementById('fileForm');
    const textForm = document.getElementById('textForm');

    function addProcessingTimer(form) {
        if (!form) return;

        form.addEventListener('submit', (e) => {
            // Create processing indicator
            const processingDiv = document.createElement('div');
            processingDiv.id = 'processing-indicator';
            processingDiv.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2rem 3rem;
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                z-index: 9999;
                text-align: center;
            `;

            let seconds = 0;
            const timerSpan = document.createElement('span');
            timerSpan.style.cssText = 'font-size: 2rem; font-weight: 700; color: var(--primary-color);';

            const messageP = document.createElement('p');
            messageP.textContent = 'Processing your document...';
            messageP.style.cssText = 'margin-top: 1rem; color: var(--text-muted); font-size: 1.1rem;';

            processingDiv.appendChild(timerSpan);
            processingDiv.appendChild(messageP);
            document.body.appendChild(processingDiv);

            // Update timer every second
            const interval = setInterval(() => {
                seconds++;
                timerSpan.textContent = `${seconds}s`;
            }, 1000);

            // Store interval ID to clear it if needed
            window.processingInterval = interval;
        });
    }

    addProcessingTimer(fileForm);
    addProcessingTimer(textForm);
});
