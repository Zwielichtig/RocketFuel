import { AjaxHelper } from '../helper/AjaxHelper';

export class UserContent {
    private modal: HTMLElement | null;
    private deleteUrl: string | null = null;
    private deleteItemName: string | null = null;
    private ajaxHelper: AjaxHelper;

    constructor() {
        this.modal = document.getElementById('deleteModal');
        this.ajaxHelper = new AjaxHelper({
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        });
        this.initializeDeleteButtons();
        this.initializeModalClose();
    }

    private initializeDeleteButtons(): void {
        const deleteButtons = document.querySelectorAll('[data-delete-url]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const target = e.currentTarget as HTMLElement;
                this.deleteUrl = target.dataset.deleteUrl || null;
                this.deleteItemName = target.dataset.itemName || null;
                this.showModal();
            });
        });
    }

    private initializeModalClose(): void {
        // Close button
        const closeButton = this.modal?.querySelector('[data-modal-close]');
        closeButton?.addEventListener('click', () => this.hideModal());

        // Click outside modal
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.hideModal();
            }
        });

        // Confirm delete button
        const confirmButton = this.modal?.querySelector('#confirmDelete');
        confirmButton?.addEventListener('click', () => this.handleDelete());
    }

    private showModal(): void {
        if (!this.modal) return;

        // Update modal content
        const itemNameSpan = this.modal.querySelector('.delete-item-name');
        if (itemNameSpan && this.deleteItemName) {
            itemNameSpan.textContent = this.deleteItemName;
        }

        // Show modal
        this.modal.style.display = 'flex';
        requestAnimationFrame(() => {
            this.modal?.classList.add('active');
        });
    }

    private hideModal(): void {
        if (!this.modal) return;

        this.modal.classList.remove('active');
        setTimeout(() => {
            this.modal!.style.display = 'none';
            this.deleteUrl = null;
            this.deleteItemName = null;
        }, 200);
    }

    private async handleDelete(): Promise<void> {
        if (!this.deleteUrl) return;

        try {
            await this.ajaxHelper.request(this.deleteUrl, {
                method: 'DELETE'
            });

            // Remove the item from the DOM
            const item = document.querySelector(`[data-delete-url="${this.deleteUrl}"]`)?.closest('.user-content-item');
            item?.remove();

            // Check if there are any items left
            const remainingItems = document.querySelectorAll('.user-content-item');
            if (remainingItems.length === 0) {
                // Reload the page to show empty state
                window.location.reload();
            }
        } catch (error) {
            console.error('Error deleting item:', error);
            alert('Fehler beim Löschen. Bitte versuche es später erneut.');
        }

        this.hideModal();
    }
}