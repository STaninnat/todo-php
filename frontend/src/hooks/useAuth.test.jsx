/**
 * @file useAuth.test.jsx
 * @description Unit tests for useAuth hook.
 * Verifies user session retrieval, login, and logout functionalities using mocked API.
 */
import { renderHook, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuth } from './useAuth';
import { api } from '../services/api';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import PropTypes from 'prop-types';
import React from 'react';

// Mock dependencies
vi.mock('../services/api', () => ({
    api: {
        me: vi.fn(),
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
    },
}));

vi.mock('react-hot-toast', () => ({
    default: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

// Setup QueryClient for testing
const createWrapper = () => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    const Wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );

    Wrapper.propTypes = {
        children: PropTypes.node.isRequired,
    };

    return Wrapper;
};

describe('useAuth Hook', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should return user data when logged in', async () => {
        const mockUser = { id: 1, username: 'testuser' };
        api.me.mockResolvedValue({ user: mockUser });

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        await waitFor(() => expect(result.current.user).toEqual(mockUser));
        expect(result.current.isLoading).toBe(false);
    });

    it('should return null when not logged in (401)', async () => {
        const error = new Error('Unauthorized');
        error.status = 401;
        api.me.mockRejectedValue(error);

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        await waitFor(() => expect(result.current.user).toBeNull());
    });

    it('should handle login successfully', async () => {
        const mockUser = { id: 1, username: 'testuser' };
        api.me.mockRejectedValue({ status: 401 }); // Initially not logged in
        api.login.mockResolvedValue({ user: mockUser });

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        await result.current.login({ username: 'test', password: 'password' });

        expect(api.login.mock.calls[0][0]).toEqual({ username: 'test', password: 'password' });
        // After login, we might check if user is updated via cache update in mutation setup
        // But since we are mocking api.me rejected, manual queryClient.setQueryData in hook logic
        // should update it.
    });

    it('should handle logout successfully', async () => {
        api.me.mockResolvedValue({ user: { id: 1 } }); // Initially logged in
        api.logout.mockResolvedValue({});

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        // Wait for initial load
        await waitFor(() => expect(result.current.user).toBeTruthy());

        await result.current.logout();

        expect(api.logout).toHaveBeenCalled();
    });
});
