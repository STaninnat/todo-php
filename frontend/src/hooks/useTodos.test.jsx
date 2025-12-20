/**
 * @file useTodos.test.jsx
 * @description Unit tests for useTodos hook.
 * Covers both Cloud Mode (API) and Guest Mode (LocalStorage) logic, including fallback mechanisms.
 */
import { renderHook, waitFor, act } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useTodos } from './useTodos';
import { api } from '../services/api';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';

// Mock dependencies
vi.mock('../services/api');
vi.mock('react-hot-toast', () => ({
    default: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

import PropTypes from 'prop-types';

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

describe('useTodos Hook', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();
    });

    afterEach(() => {
        localStorage.clear();
    });

    describe('Guest Mode (LocalStorage)', () => {
        it('should default to guest mode on 401/Network Error', async () => {
            const error = new Error('Network Error');
            // Trigger guest mode logic
            api.get.mockRejectedValue(error);

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });

            await waitFor(() => expect(result.current.isGuest).toBe(true));
            expect(result.current.todos).toEqual([]);
        });

        it('should add task to localStorage in guest mode', async () => {
            api.get.mockRejectedValue(new Error('Network Error'));
            
            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.isGuest).toBe(true));

            await act(async () => {
                await result.current.addTodo({ title: 'Guest Task', description: 'Desc' });
            });

            const saved = JSON.parse(localStorage.getItem('guest_todos'));
            expect(saved).toHaveLength(1);
            expect(saved[0].title).toBe('Guest Task');
            // Check that the hook state updated (via query invalidation)
            await waitFor(() => expect(result.current.todos).toHaveLength(1));
        });

        it('should toggle task in guest mode', async () => {
            // Setup initial LS data
            const task = { id: 123, title: 'Task', isDone: false };
            localStorage.setItem('guest_todos', JSON.stringify([task]));
            
            api.get.mockRejectedValue(new Error('Network Error'));

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.todos).toHaveLength(1));

            await act(async () => {
                await result.current.toggleTodo(123);
            });

            const saved = JSON.parse(localStorage.getItem('guest_todos'));
            expect(saved[0].isDone).toBe(true);
        });
    });

    describe('Cloud Mode (API)', () => {
        it('should fetch todos from API', async () => {
            const mockTodos = [{ id: 1, title: 'API Task', is_done: 0 }];
            api.get.mockResolvedValue({
                data: {
                    task: mockTodos,
                    pagination: { total_pages: 1 }
                }
            });

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });

            await waitFor(() => expect(result.current.isGuest).toBe(false));
            expect(result.current.todos).toHaveLength(1);
            expect(result.current.todos[0].title).toBe('API Task');
            expect(result.current.isAdding).toBeDefined();
            expect(result.current.isAdding).toBe(false); // Default state
        });

        it('should call API to add task', async () => {
            api.get.mockResolvedValue({
                 data: { task: [], pagination: { total_pages: 1 } }
            });
            api.post.mockResolvedValue({
                 data: { task: { id: 1, title: 'New', is_done: 0 } }
            });

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.isGuest).toBe(false));

            await act(async () => {
                await result.current.addTodo({ title: 'New' });
            });

            expect(api.post).toHaveBeenCalledWith('/tasks/add', expect.objectContaining({ title: 'New' }));
        });
    });

    describe('Pagination', () => {
        it('should auto-navigate to previous page if current page becomes empty', async () => {
            // 1. Setup: Start on Page 2 with data
            // Call 1: Initial render (Page 1)
            api.get.mockResolvedValueOnce({
                data: {
                    task: [{ id: 10, title: 'Task on Page 1', is_done: 0 }],
                    pagination: { total_pages: 2 }
                }
            });

            // Call 2: Transition to Page 2
            api.get.mockResolvedValueOnce({
                data: {
                    task: [{ id: 1, title: 'Task on Page 2', is_done: 0 }],
                    pagination: { total_pages: 2 }
                }
            });

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });

            // Wait for initial load (Page 1 data) to settle
            await waitFor(() => expect(result.current.totalPages).toBe(2));

            // Move to Page 2
            await act(async () => {
                result.current.setPage(2);
            });

            // Expect to be on Page 2
            await waitFor(() => expect(result.current.page).toBe(2));

            // 2. Action: Simulate re-fetch where Page 2 is now empty (e.g. item deleted)
            
            // Call 3: Re-fetch triggered by delete/invalidation
            api.get.mockResolvedValueOnce({
                data: {
                    task: [], // Empty now
                    pagination: { total_pages: 1 } // Total pages drops to 1
                }
            });

            // Force a refetch or re-render that updates the query data
            await act(async () => {
                await result.current.deleteTodo(1); // Triggers invalidation
            });

            // 3. Assertion: Should auto-revert to Page 1
            await waitFor(() => {
                expect(result.current.page).toBe(1);
            });
        });
    });
});
