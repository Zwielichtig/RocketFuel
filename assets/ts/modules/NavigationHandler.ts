export class NavigationHandler {
    private static instance: NavigationHandler;
    private container: HTMLElement | null = null;
    private userButton: HTMLElement | null = null;
    private dropdownMenu: HTMLElement | null = null;

    private constructor() {}

    public static getInstance(): NavigationHandler {
        if (!NavigationHandler.instance) {
            NavigationHandler.instance = new NavigationHandler();
        }
        return NavigationHandler.instance;
    }

    public init(): void {
        this.container = document.querySelector('.ts-module-Navigation');
        if (!this.container) {
            console.error('Navigation container not found');
            return;
        }

        this.userButton = this.container.querySelector('.nav-user-btn');
        this.dropdownMenu = this.container.querySelector('.nav-dropdown-menu');

        if (!this.userButton || !this.dropdownMenu) {
            console.error('Required navigation elements not found');
            return;
        }

        this.initializeEventListeners();
    }

    private initializeEventListeners(): void {
        if (!this.userButton || !this.dropdownMenu) return;

        // Toggle dropdown on user button click
        this.userButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            this.closeDropdown();
        });

        // Prevent dropdown from closing when clicking inside it
        this.dropdownMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    private toggleDropdown(): void {
        if (!this.userButton || !this.dropdownMenu) return;

        const isActive = this.dropdownMenu.classList.contains('active');

        if (isActive) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    private openDropdown(): void {
        if (!this.userButton || !this.dropdownMenu) return;

        this.userButton.classList.add('active');
        this.dropdownMenu.classList.remove('hidden');
        this.dropdownMenu.classList.add('active');
    }

    private closeDropdown(): void {
        if (!this.userButton || !this.dropdownMenu) return;

        this.userButton.classList.remove('active');
        this.dropdownMenu.classList.remove('active');
        this.dropdownMenu.classList.add('hidden');
    }
}