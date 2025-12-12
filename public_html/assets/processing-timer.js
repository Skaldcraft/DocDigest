
// Processing Timer with Mechanical Gear Animation
document.addEventListener('DOMContentLoaded', () => {
    const fileForm = document.getElementById('fileForm');
    const textForm = document.getElementById('textForm');
    const imageForm = document.getElementById('imageForm');

    function addProcessingTimer(form) {
        if (!form) return;

        form.addEventListener('submit', (e) => {
            // Check if form is valid (for required fields)
            if (!form.checkValidity()) return;

            // Create processing indicator overlay
            const processingDiv = document.createElement('div');
            processingDiv.id = 'processing-indicator';
            processingDiv.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(5px);
                z-index: 9999;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            `;

            // Mechanical Gear Icon (SVG)
            const gearSvg = `
            <svg width="120" height="120" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="var(--primary-color)">
                <style>
                    .gear-spin { transform-origin: center; animation: spin 4s linear infinite; }
                    @keyframes spin { 100% { transform: rotate(360deg); } }
                </style>
                <path class="gear-spin" d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5ZM19.433 12.993L21.237 14.488C21.402 14.624 21.448 14.863 21.334 15.05L19.432 18.336C19.324 18.523 19.096 18.595 18.905 18.521L16.634 17.618C16.148 17.989 15.625 18.293 15.068 18.523L14.717 20.932C14.688 21.143 14.507 21.298 14.294 21.298H10.499C10.284 21.298 10.103 21.141 10.075 20.932L9.724 18.524C9.166 18.295 8.643 17.99 8.156 17.619L5.887 18.523C5.696 18.598 5.468 18.524 5.36 18.337L3.456 15.051C3.342 14.864 3.388 14.625 3.553 14.489L5.357 12.994C5.344 12.83 5.333 12.666 5.333 12.5C5.333 12.334 5.344 12.169 5.357 12.006L3.553 10.511C3.388 10.375 3.342 10.136 3.456 9.949L5.36 6.664C5.468 6.477 5.697 6.402 5.887 6.478L8.157 7.382C8.644 7.011 9.166 6.706 9.724 6.477L10.075 4.069C10.103 3.859 10.284 3.702 10.499 3.702H14.294C14.507 3.702 14.688 3.858 14.717 4.069L15.068 6.477C15.626 6.705 16.149 7.01 16.635 7.382L18.905 6.478C19.096 6.402 19.324 6.477 19.432 6.664L21.334 9.95C21.448 10.137 21.402 10.376 21.237 10.512L19.432 12.007C19.446 12.171 19.457 12.335 19.457 12.501C19.457 12.666 19.446 12.829 19.433 12.993Z"/>
            </svg>`;

            const messageP = document.createElement('h2');
            messageP.textContent = 'Processing Document...';
            messageP.style.cssText = `
                margin-top: 1.5rem;
                color: var(--primary-color);
                font-size: 1.5rem;
                font-weight: 700;
            `;

            const subMessage = document.createElement('p');
            subMessage.textContent = 'Analyzing and simplifying. This may take a few seconds.';
            subMessage.style.cssText = 'color: var(--text-muted); margin-top: 0.5rem;';

            processingDiv.innerHTML = gearSvg;
            processingDiv.appendChild(messageP);
            processingDiv.appendChild(subMessage);

            document.body.appendChild(processingDiv);
        });
    }

    addProcessingTimer(fileForm);
    addProcessingTimer(textForm);
    addProcessingTimer(imageForm);
});
