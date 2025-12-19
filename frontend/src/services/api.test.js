import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

/**
 * @file api.test.js
 * @description Unit tests for the API service wrapper.
 * mocks global fetch to verify request construction (headers, body) and response handling (parsing, errors).
 */
import { api, API_BASE } from './api';

describe('API Service', () => {
    // Save original fetch
    const originalFetch = global.fetch;

    beforeEach(() => {
        global.fetch = vi.fn();
    });

    afterEach(() => {
        global.fetch = originalFetch;
        vi.clearAllMocks();
    });

    it('should perform GET request with correct headers', async () => {
        const mockResponse = { data: 'test' };
        global.fetch.mockResolvedValue({
            ok: true,
            headers: { get: () => 'application/json' },
            json: async () => mockResponse,
        });

        const result = await api.get('/test');

        expect(global.fetch).toHaveBeenCalledWith(
            `${API_BASE}/test`,
            expect.objectContaining({
                method: 'GET',
                headers: expect.objectContaining({
                    'Content-Type': 'application/json',
                }),
            })
        );
        expect(result).toEqual(mockResponse);
    });

    it('should perform POST request with body', async () => {
        const mockResponse = { success: true };
        const body = { foo: 'bar' };
        
        global.fetch.mockResolvedValue({
            ok: true,
            headers: { get: () => 'application/json' },
            json: async () => mockResponse,
        });

        const result = await api.post('/submit', body);

        expect(global.fetch).toHaveBeenCalledWith(
            `${API_BASE}/submit`,
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify(body),
            })
        );
        expect(result).toEqual(mockResponse);
    });

    it('should parse non-JSON response as text', async () => {
        const textResponse = 'Server Error or Plain Text';
        global.fetch.mockResolvedValue({
            ok: true,
            headers: { get: () => 'text/plain' },
            text: async () => textResponse,
        });

        const result = await api.get('/text');

        expect(result).toBe(textResponse);
    });

    it('should throw error on failed request', async () => {
        const errorData = { message: 'Invalid credentials' };
        global.fetch.mockResolvedValue({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => errorData,
        });

        await expect(api.login({ username: 'bad', password: 'bad' }))
            .rejects
            .toThrow('Invalid credentials');
    });

    it('should throw "Too many requests" error on 429 status', async () => {
        global.fetch.mockResolvedValue({
            ok: false,
            status: 429,
            headers: { get: () => 'application/json' },
            json: async () => ({}),
        });

        await expect(api.get('/rate-limited'))
            .rejects
            .toThrow('Too many requests. Please try again later.');
    });

    it('should methods (put, delete) call request with correct method', async () => {
        global.fetch.mockResolvedValue({
            ok: true,
            headers: { get: () => 'application/json' },
            json: async () => ({}),
        });

        await api.put('/update', { id: 1 });
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('/update'),
            expect.objectContaining({ method: 'PUT' })
        );

        await api.delete('/delete', { id: 1 });
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('/delete'),
            expect.objectContaining({ method: 'DELETE' })
        );
    });
    
    it('should retry request on 401 if refresh succeeds', async () => {
        // First call returns 401
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Token expired' }),
        });

        // Second call (refresh) returns 200
        fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            headers: { get: () => 'application/json' },
            json: async () => ({ success: true }),
        });

        // Third call (retry original) returns 200 success
        fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            headers: { get: () => 'application/json' },
            json: async () => ({ success: true, data: 'retried' }),
        });

        const result = await api.get('/protected-resource');
        
        expect(result).toEqual({ success: true, data: 'retried' });
        expect(fetch).toHaveBeenCalledTimes(3); 
        // 1. /protected-resource (401)
        // 2. /users/refresh (200)
        // 3. /protected-resource (200)
    });

    it('should throw error if refresh fails on 401', async () => {
        // First call returns 401
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Token expired' }),
        });

        // Second call (refresh) returns 401 or error
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Refresh expired' }),
        });

        await expect(api.get('/protected-resource')).rejects.toThrow('Token expired');
        
        expect(fetch).toHaveBeenCalledTimes(2);
        // 1. /protected-resource (401)
        // 2. /users/refresh (401) -> Catch block logs error, then outer catch throws original 401 error
    });
    
    it('should not retry if endpoint is signin', async () => {
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Unauthorized' }),
        });

        await expect(api.login({ username: 'u', password: 'p' })).rejects.toThrow('Unauthorized');
        expect(fetch).toHaveBeenCalledTimes(1);
    });

    it('should not retry if endpoint is signup', async () => {
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Unauthorized' }),
        });

        await expect(api.register({ username: 'u', password: 'p' })).rejects.toThrow('Unauthorized');
        expect(fetch).toHaveBeenCalledTimes(1);
    });
});
