import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useEffect } from 'react';
import toast from 'react-hot-toast';
import { api } from '../services/api';

/**
 * Custom hook for managing authentication state.
 * uses React Query to persist user session data.
 * @returns {Object} Auth object
 * @returns {Object|null} return.user - The current user object or null
 * @returns {boolean} return.isLoading - Loading state of auth check
 * @returns {Error|null} return.error - Error object if any
 * @returns {function} return.login - Function to login (email, password)
 * @returns {function} return.register - Function to register (username, email, password)
 * @returns {function} return.logout - Function to logout
 */
export function useAuth() {
    const queryClient = useQueryClient();

    // Query: Get Current User
    const { data: user, isLoading, error } = useQuery({
        queryKey: ['auth', 'user'],
        queryFn: async () => {
             try {
                 const data = await api.me();

                 return data.data || data.user || null;
             } catch (err) {
                 // 401/403 just means not logged in, return null
                 if (err.status === 401 || err.status === 403) {
                     return null;
                 }
                 
                 // For other errors (500, Network Error), log and fall back to guest mode
                 toast.error("Backend unavailable. Switching to Guest Mode.", { id: 'backend-error' });
                 return null;
             }
        },
        retry: false, // Don't retry on any error to prevent loops
        staleTime: 1000 * 60 * 5, // Consider user data fresh for 5 minutes
    });

    // Mutation: Login
    const loginMutation = useMutation({
        mutationFn: api.login,
        onSuccess: (data) => {
            toast.success("Welcome back!");
            
            // Extract user from response. Backend returns { data: { user: ... } }
            // Or if flattened: { user: ... }
            const userData = data?.user || data?.data?.user;

            if (userData) {
                queryClient.setQueryData(['auth', 'user'], userData);
            } else {
                queryClient.invalidateQueries({ queryKey: ['auth', 'user'] });
            }
        },
        onError: (err) => {
            toast.error(err.message || "Sign in failed");
        }
    });

    // Mutation: Register
    const registerMutation = useMutation({
        mutationFn: api.register,
        onSuccess: (data) => {
             toast.success("Registration successful!");
             
             // Check if backend returns user (auto-login)
             const userData = data?.user || data?.data?.user;

             if (userData) {
                queryClient.setQueryData(['auth', 'user'], userData);
             } else {
                queryClient.invalidateQueries({ queryKey: ['auth', 'user'] });
             }
        },
        onError: (err) => {
            toast.error(err.message || "Registration failed");
        }
    });

    // Mutation: Logout
    const logoutMutation = useMutation({
        mutationFn: api.logout,
        onSuccess: () => {
            toast.success("Logged out");
            queryClient.setQueryData(['auth', 'user'], null);
            queryClient.clear(); // Clear all data (todos, etc)
        },
        onError: (err) => {
            toast.error(err.message || "Sign out failed");
        }
    });

    // Listen for global auth errors (e.g. from api interceptor)
    useEffect(() => {
        const handleAuthError = () => {
             // Prevent redundant handling if multiple requests fail at once
             const currentUser = queryClient.getQueryData(['auth', 'user']);
             if (!currentUser) return;

             queryClient.setQueryData(['auth', 'user'], null);
             
             // clear() is appropriate here to ensure no sensitive task data remains
             queryClient.clear(); 
             
             // Use a unique ID to prevent toast spam from concurrent failures
             toast.error("Session expired. Please log in again.", { id: 'session-expired' });
        };

        window.addEventListener('auth:unauthorized', handleAuthError);
        return () => window.removeEventListener('auth:unauthorized', handleAuthError);
    }, [queryClient]);

    return {
        user,
        isLoading,
        error,
        login: loginMutation.mutateAsync,
        register: registerMutation.mutateAsync,
        logout: logoutMutation.mutateAsync,
    };
}
