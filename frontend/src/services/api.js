export const API_BASE = process.env.BUN_PUBLIC_API_BASE || '/v1';

/**
 * Generic API helper to handle requests and errors.
 * Automatically adds 'Content-Type: application/json' for JSON bodies.
 * @param {string} endpoint - The API endpoint (e.g., '/users/me')
 * @param {Object} [options={}] - Fetch options (method, headers, body, etc.)
 * @returns {Promise<any>} The parsed JSON or text response
 * @throws {Error} If response status is not OK (200-299)
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
        credentials: 'include',
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
            // Handle 401 Unauthorized (Token Expiry)
            if (response.status === 401 && !options._retry && !endpoint.includes('/signin') && !endpoint.includes('/signup') && !endpoint.includes('/refresh')) {
                try {
                    // Attempt to refresh token
                    await api.refreshToken();

                    // Retry original request
                    return request(endpoint, { ...options, _retry: true });
                } catch (refreshError) {
                    console.error('Token refresh failed:', refreshError);
                    // Dispatch event to trigger global logout
                    window.dispatchEvent(new CustomEvent('auth:unauthorized'));
                }
            } else if (response.status === 401 && !endpoint.includes('/signin')) {
                // If 401 but not eligible for refresh (e.g. signout or unexpected), verify if we should logout
                 window.dispatchEvent(new CustomEvent('auth:unauthorized'));
            }
            
            if (response.status === 429) {
                 const error = new Error('Too many requests. Please try again later.');
                 error.status = 429;
                 throw error;
            }

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
    refreshToken: () => request('/users/refresh', { method: 'POST' }), // Helper for refresh
    me: () => request('/users/me', { method: 'GET' }),
};
