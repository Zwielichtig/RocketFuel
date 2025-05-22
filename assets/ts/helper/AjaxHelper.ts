export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE';

export interface AjaxOptions {
    method?: HttpMethod;
    headers?: Record<string, string>;
    body?: any;
    queryParams?: Record<string, string | number | boolean>;
}

export class AjaxHelper {
    private defaultHeaders: Record<string, string>;

    constructor(defaultHeaders: Record<string, string> = { 'Content-Type': 'application/json' }) {
        this.defaultHeaders = defaultHeaders;
    }

    private buildUrl(url: string, queryParams?: Record<string, string | number | boolean>): string {
        if (!queryParams) return url;

        const params = new URLSearchParams();
        for (const key in queryParams) {
            params.append(key, String(queryParams[key]));
        }

        return `${url}?${params.toString()}`;
    }

    public async request<T = any>(url: string, options: AjaxOptions = {}): Promise<T> {
        const { method = 'GET', headers = {}, body, queryParams } = options;
        const fullUrl = this.buildUrl(url, queryParams);

        const fetchOptions: RequestInit = {
            method,
            headers: { ...this.defaultHeaders, ...headers },
        };

        if (body && method !== 'GET') {
            fetchOptions.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(fullUrl, fetchOptions);

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const contentType = response.headers.get('Content-Type');

            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return (await response.text()) as any;
            }
        } catch (error) {
            console.error('AJAX Request failed:', error);
            throw error;
        }
    }
}
