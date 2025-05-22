export class PreviewHandler {
    private static instance: PreviewHandler;

    public static getInstance(): PreviewHandler {
        if (!PreviewHandler.instance) {
            PreviewHandler.instance = new PreviewHandler();
        }
        return PreviewHandler.instance;
    }

    public init(): void {
        console.log('Initializing PreviewHandler');
        this.initCommandCopy();
    }

    private initCommandCopy(): void {
        const container = document.querySelector('.ts-module-Preview');
        if (!container) {
            console.warn('Preview container not found');
            return;
        }

        const copyButton = container.querySelector('.preview-command-copy');
        const copiedMessage = container.querySelector('.preview-command-copied');
        const commandCode = container.querySelector('.preview-command-content code');

        if (!copyButton || !copiedMessage || !commandCode) {
            console.warn('Copy elements not found:', { copyButton, copiedMessage, commandCode });
            return;
        }

        console.log('Setting up copy button handler');
        copyButton.addEventListener('click', async () => {
            console.log('Copy button clicked');
            try {
                const textToCopy = commandCode.textContent || '';
                console.log('Copying text:', textToCopy);
                await navigator.clipboard.writeText(textToCopy);

                // Show copied message
                copiedMessage.classList.add('show');
                console.log('Showing copied message');

                // Hide message after 2 seconds
                setTimeout(() => {
                    copiedMessage.classList.remove('show');
                    console.log('Hiding copied message');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy command:', err);
            }
        });
    }
}