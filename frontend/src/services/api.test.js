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
});
