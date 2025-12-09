export const API_BASE = '/api';

/**
 * Generic API helper to handle requests and errors.
 * Automatically adds 'Content-Type: application/json' for JSON bodies.
 */
async function request(endpoint, options = {}) {
    const url = `${API_BASE}${endpoint}`;

    const headers = {
        'Content-Type': 'application/json',
        ...options.headers,
    };

    const config = {
        ...options,
        headers,
    };

    if (options.body && typeof options.body === 'object') {
        config.body = JSON.stringify(options.body);
    }

    try {
        const response = await fetch(url, config);

        // Parse JSON if possible
        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            data = await response.text();
        }

        if (!response.ok) {
            // Throw an error with the server message if available
            const error = new Error(data.message || data.error || `Request failed with status ${response.status}`);
            error.status = response.status;
            throw error;
        }

        return data;
    } catch (error) {
        if (process.env.NODE_ENV !== 'production') {
            console.error('API Error:', error);
        }
        throw error;
    }
}

// Export specific API methods
export const api = {
    get: (endpoint) => request(endpoint, { method: 'GET' }),
    post: (endpoint, body) => request(endpoint, { method: 'POST', body }),
    put: (endpoint, body) => request(endpoint, { method: 'PUT', body }),
    delete: (endpoint, body) => request(endpoint, { method: 'DELETE', body }),
    
    // Auth specific
    login: (credentials) => request('/users/signin', { method: 'POST', body: credentials }),
    register: (userData) => request('/users/signup', { method: 'POST', body: userData }),
    logout: () => request('/users/signout', { method: 'POST' }),
    me: () => request('/users/me', { method: 'GET' }),
};
