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
    let dispatchEventSpy;
    let store = {};

    beforeEach(() => {
        global.fetch = vi.fn();
        dispatchEventSpy = vi.spyOn(window, 'dispatchEvent');
        
        store = { auth_status: 'logged_in' };
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation((key) => store[key] || null);
    });

    afterEach(() => {
        global.fetch = originalFetch;
        vi.restoreAllMocks();
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

    it('should sanitize 404 "Route not found" error', async () => {
        global.fetch.mockResolvedValue({
            ok: false,
            status: 404,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Route not found: POST /users/signup' }),
        });

        await expect(api.post('/users/signup', {}))
            .rejects
            .toThrow('Service endpoint not found or unavailable.');
    });

    it('should sanitize 500 Server Error', async () => {
        global.fetch.mockResolvedValue({
            ok: false,
            status: 500,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'SQLSTATE[HY000]: General error' }),
        });

        await expect(api.get('/users/me'))
            .rejects
            .toThrow('Something went wrong on the server. Please try again later.');
    });

    it('should sanitize Network Error', async () => {
        global.fetch.mockRejectedValue(new Error('Failed to fetch'));

        await expect(api.get('/users/me'))
            .rejects
            .toThrow('Unable to connect to the server. Please check your internet connection.');
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
    });

    it('should throw error and dispatch event if refresh fails on 401', async () => {
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
        
        expect(dispatchEventSpy).toHaveBeenCalledWith(expect.any(CustomEvent));
        const event = dispatchEventSpy.mock.calls[0][0];
        expect(event.type).toBe('auth:unauthorized');
    });

    it('should NOT attempt refresh if auth_status is missing (Guest Mode)', async () => {
        // Mock as guest
        store['auth_status'] = null;

        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Token expired' }),
        });

        // Should throw, but NOT call refresh
        await expect(api.get('/protected-resource')).rejects.toThrow('Token expired');

        expect(fetch).toHaveBeenCalledTimes(1); 
        // Only 1 call (Original). No /users/refresh call.

        expect(dispatchEventSpy).toHaveBeenCalledWith(expect.any(CustomEvent));
        const event = dispatchEventSpy.mock.calls[0][0];
        expect(event.type).toBe('auth:unauthorized');
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

    it('should not retry if endpoint is refresh', async () => {
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            headers: { get: () => 'application/json' },
            json: async () => ({ message: 'Unauthorized' }),
        });

        await expect(api.refreshToken()).rejects.toThrow('Unauthorized');
        expect(fetch).toHaveBeenCalledTimes(1);
        
        // Also verify it dispatches unauthorized event
        expect(dispatchEventSpy).toHaveBeenCalledWith(expect.any(CustomEvent));
        const event = dispatchEventSpy.mock.calls[0][0];
        expect(event.type).toBe('auth:unauthorized');
    });
});
