import React from 'react';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { describe, it, expect, vi, beforeEach } from 'vitest';

/**
 * Tests for useAuth hook fallback logic.
 * 
 * Verifies that the application gracefully degrades to Guest Mode when:
 * 1. The backend returns a 500 error
 * 2. A network error occurs
 * 
 * In both cases, the user should be null, and an appropriate toast notification should appear.
 */
import { useAuth } from './useAuth';
import { api } from '../services/api';
import toast from 'react-hot-toast';

// Mock dependencies
vi.mock('../services/api');
vi.mock('react-hot-toast');

describe('useAuth Fallback Logic', () => {
    let queryClient;

    beforeEach(() => {
        queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                },
            },
        });
        vi.clearAllMocks();
    });

    const wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>
            {children}
        </QueryClientProvider>
    );

    it('should return null (Guest Mode) and show toast on 500 Backend Error', async () => {
        // Must be logged in to attempt the call that fails
        vi.spyOn(Storage.prototype, 'getItem').mockReturnValue('logged_in');
        
        // Mock api.me to fail with 500
        const error = new Error('Internal Server Error');
        error.status = 500;
        api.me.mockRejectedValue(error);

        const { result } = renderHook(() => useAuth(), { wrapper });

        // Initially loading
        expect(result.current.isLoading).toBe(true);

        // wait for query to finish
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        // Assert: user is null (fallback to guest)
        expect(result.current.user).toBeNull();

        // Assert: error is NOT set in the hook result because we caught it
        // (useQuery returns error if it throws, but we returned null)
        expect(result.current.error).toBeNull();

        // Assert: Toast error was shown
        expect(toast.error).toHaveBeenCalledWith(
            "Offline Mode Active. Changes are saved locally.",
            expect.objectContaining({ id: 'backend-error' })
        );
        
        // Assert: Error logged to console (mocked console.error to avoid noise if needed, but simple verify toast is enough)
    });

    it('should return null (Guest Mode) on Network Error', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockReturnValue('logged_in');
        
        // Mock api.me to fail with generic network error (no status)
        const error = new Error('Network Error');
        api.me.mockRejectedValue(error);

        const { result } = renderHook(() => useAuth(), { wrapper });

        await waitFor(() => expect(result.current.isLoading).toBe(false));

        expect(result.current.user).toBeNull();
        expect(toast.error).toHaveBeenCalledWith(
            "Offline Mode Active. Changes are saved locally.",
            expect.objectContaining({ id: 'backend-error' })
        );
    });
});
