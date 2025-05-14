export class LoginHandler {
    private static instance: LoginHandler;
    private container: HTMLElement | null = null;
    private forms: NodeListOf<HTMLFormElement> | null = null;
    private tabs: NodeListOf<HTMLButtonElement> | null = null;

    private constructor() {}

    public static getInstance(): LoginHandler {
        if (!LoginHandler.instance) {
            LoginHandler.instance = new LoginHandler();
        }
        return LoginHandler.instance;
    }

    public init(): void {
        this.container = document.querySelector('.ts-module-Login');
        if (!this.container) {
            console.error('Login container not found');
            return;
        }

        this.forms = this.container.querySelectorAll('.auth-form');
        this.tabs = this.container.querySelectorAll('.auth-tab');

        if (!this.forms || !this.tabs) {
            console.error('Required form elements not found');
            return;
        }

        this.initializeEventListeners();
    }

    private initializeEventListeners(): void {
        if (!this.tabs || !this.forms) return;

        // Tab switching
        this.tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetForm = tab.dataset.tab;
                this.switchForm(targetForm);
            });
        });

        // Add password confirmation validation for register form
        const registerForm = this.container?.querySelector('form[data-form="register"]');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                const form = e.target as HTMLFormElement;
                const password = form.querySelector('input[name="password"]') as HTMLInputElement;
                const passwordConfirm = form.querySelector('input[name="passwordConfirm"]') as HTMLInputElement;

                if (password.value !== passwordConfirm.value) {
                    e.preventDefault();
                    alert('Passwords do not match');
                }
            });
        }
    }

    private switchForm(targetForm: string | undefined): void {
        if (!this.forms || !this.tabs || !targetForm) return;

        // Update tabs
        this.tabs.forEach(tab => {
            if (tab.dataset.tab === targetForm) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        // Update forms
        this.forms.forEach(form => {
            if (form.dataset.form === targetForm) {
                form.classList.remove('hidden');
            } else {
                form.classList.add('hidden');
            }
        });
    }
}