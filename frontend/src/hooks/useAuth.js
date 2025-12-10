import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import { api } from '../services/api';

export function useAuth() {
    const queryClient = useQueryClient();

    // Query: Get Current User
    const { data: user, isLoading, error } = useQuery({
        queryKey: ['auth', 'user'],
        queryFn: async () => {
             try {
                 const data = await api.me();
                 return data.user || null;
             } catch (err) {
                 // 401 just means not logged in, return null
                 if (err.status === 401 || err.status === 403) {
                     return null;
                 }
                 throw err;
             }
        },
        retry: false, // Don't retry on 401s
        staleTime: 1000 * 60 * 5, // Consider user data fresh for 5 minutes
    });

    // Mutation: Login
    const loginMutation = useMutation({
        mutationFn: api.login,
        onSuccess: (data) => {
            toast.success("Welcome back!");
            // Update user cache immediately
            if (data && data.user) {
                queryClient.setQueryData(['auth', 'user'], data.user);
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
        onSuccess: () => {
             toast.success("Registration successful! Please sign in.");
             // Usually register autologs in, or requires login. 
             // Safest to invalidate.
             queryClient.invalidateQueries({ queryKey: ['auth', 'user'] });
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

    return {
        user,
        isLoading,
        error,
        login: loginMutation.mutateAsync,
        register: registerMutation.mutateAsync,
        logout: logoutMutation.mutateAsync,
    };
}
