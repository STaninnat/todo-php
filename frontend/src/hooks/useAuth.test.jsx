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
        vi.spyOn(Storage.prototype, 'getItem').mockReturnValue('logged_in');
        const mockUser = { id: 1, username: 'testuser' };
        api.me.mockResolvedValue({ user: mockUser });

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        await waitFor(() => expect(result.current.user).toEqual(mockUser));
        expect(result.current.isLoading).toBe(false);
    });

    it('should NOT call api.me when not logged in (Guest)', async () => {
         vi.spyOn(Storage.prototype, 'getItem').mockReturnValue(null); // No auth flag

         const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });
         
         await waitFor(() => expect(result.current.isLoading).toBe(false));
         expect(api.me).not.toHaveBeenCalled();
         expect(result.current.user).toBeNull();
    });

    it('should return null when 401 occurs and clear auth flag', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockReturnValue('logged_in');
        const setItemSpy = vi.spyOn(Storage.prototype, 'removeItem');

        const error = new Error('Unauthorized');
        error.status = 401;
        api.me.mockRejectedValue(error);

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        await waitFor(() => expect(result.current.user).toBeNull());
        expect(setItemSpy).toHaveBeenCalledWith('auth_status');
    });

    it('should handle login successfully', async () => {
        const setItemSpy = vi.spyOn(Storage.prototype, 'setItem');
        const mockUser = { id: 1, username: 'testuser' };
        api.me.mockReturnValue(Promise.resolve({ user: mockUser })); // api.me used in query refetch

        api.login.mockResolvedValue({ user: mockUser });

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        await result.current.login({ username: 'test', password: 'password' });

        expect(api.login).toHaveBeenCalledWith(
            expect.objectContaining({ username: 'test', password: 'password' }),
            expect.anything() // Ignore second arg (variables/options)
        );
        expect(setItemSpy).toHaveBeenCalledWith('auth_status', 'logged_in');
    });

    it('should handle logout successfully', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockReturnValue('logged_in');
        const removeItemSpy = vi.spyOn(Storage.prototype, 'removeItem');
        
        api.me.mockResolvedValue({ user: { id: 1 } }); 
        api.logout.mockResolvedValue({});

        const { result } = renderHook(() => useAuth(), { wrapper: createWrapper() });

        await waitFor(() => expect(result.current.user).toBeTruthy());

        await result.current.logout();

        expect(api.logout).toHaveBeenCalled();
        expect(removeItemSpy).toHaveBeenCalledWith('auth_status');
    });
});
