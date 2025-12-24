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
    let store = {};

    beforeEach(() => {
        vi.clearAllMocks();
        store = {};

        vi.spyOn(Storage.prototype, 'getItem').mockImplementation((key) => {
            return store[key] || null;
        });

        vi.spyOn(Storage.prototype, 'setItem').mockImplementation((key, value) => {
            store[key] = value.toString();
        });

        vi.spyOn(Storage.prototype, 'clear').mockImplementation(() => {
            store = {};
        });
    });

    afterEach(() => {
        localStorage.clear();
    });

    describe('Guest Mode (LocalStorage)', () => {
        it('should default to guest mode immediately if not logged in', async () => {
            // store['auth_status'] is undefined (null) by default
            
            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });

            await waitFor(() => expect(result.current.isGuest).toBe(true));
            expect(api.get).not.toHaveBeenCalled(); // New optimization check
            expect(result.current.todos).toEqual([]);
        });

        it('should add task to localStorage in guest mode', async () => {
            // Auth status is null by default
            
            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.isGuest).toBe(true));

            await act(async () => {
                await result.current.addTodo({ title: 'Guest Task', description: 'Desc' });
            });

            const saved = JSON.parse(store['guest_todos']);
            expect(saved).toHaveLength(1);
            expect(saved[0].title).toBe('Guest Task');
            // Check that the hook state updated (via query invalidation)
            await waitFor(() => expect(result.current.todos).toHaveLength(1));
        });

        it('should toggle task in guest mode', async () => {
            const task = { id: 123, title: 'Task', isDone: false };
            store['guest_todos'] = JSON.stringify([task]);

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.todos).toHaveLength(1));

            await act(async () => {
                await result.current.toggleTodo(123);
            });

            const saved = JSON.parse(store['guest_todos']);
            expect(saved[0].isDone).toBe(true);
        });

        it('should filter todos by search query in guest mode', async () => {
            const task1 = { id: 1, title: 'Apple', isDone: false };
            const task2 = { id: 2, title: 'Banana', isDone: false };
            store['guest_todos'] = JSON.stringify([task1, task2]);

            // Render hook with search query 'App'
            const { result } = renderHook(() => useTodos('App'), { wrapper: createWrapper() });
            
            await waitFor(() => {
                expect(result.current.isGuest).toBe(true);
                expect(result.current.todos).toHaveLength(1);
            });
            expect(result.current.todos[0].title).toBe('Apple');
        });

        it('should filter todos by status in guest mode', async () => {
             const task1 = { id: 1, title: 'Active Task', isDone: false };
             const task2 = { id: 2, title: 'Done Task', isDone: true };
             store['guest_todos'] = JSON.stringify([task1, task2]);
 
             // Render hook with filter 'completed'
             const { result } = renderHook(() => useTodos('', 'completed'), { wrapper: createWrapper() });
             
             await waitFor(() => {
                expect(result.current.isGuest).toBe(true);
                expect(result.current.todos).toHaveLength(1);
             });
             expect(result.current.todos[0].title).toBe('Done Task');
        });

        it('should bulk delete todos in guest mode', async () => {
            const task1 = { id: 1, title: 'One', isDone: false };
            const task2 = { id: 2, title: 'Two', isDone: false };
            const task3 = { id: 3, title: 'Three', isDone: false };
            store['guest_todos'] = JSON.stringify([task1, task2, task3]);
            
            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.isGuest).toBe(true));

            await act(async () => {
                await result.current.bulkDeleteTodos([1, 3]);
            });

            const saved = JSON.parse(store['guest_todos']);
            expect(saved).toHaveLength(1);
            expect(saved[0].id).toBe(2);
        });

        it('should bulk mark todos as done in guest mode', async () => {
            const task1 = { id: 1, title: 'One', isDone: false };
            const task2 = { id: 2, title: 'Two', isDone: false };
            store['guest_todos'] = JSON.stringify([task1, task2]);
            
            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.isGuest).toBe(true));

            await act(async () => {
                await result.current.bulkMarkDoneTodos([1, 2], true);
            });

            const saved = JSON.parse(store['guest_todos']);
            expect(saved[0].isDone).toBe(true);
            expect(saved[1].isDone).toBe(true);
        });
    });

    describe('Cloud Mode (API)', () => {
        it('should fetch todos from API', async () => {
            store['auth_status'] = 'logged_in';

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
            store['auth_status'] = 'logged_in';
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

        it('should include search and filter params in API call', async () => {
             store['auth_status'] = 'logged_in';
            api.get.mockResolvedValue({
                data: { task: [], pagination: { total_pages: 1 } }
            });

            renderHook(() => useTodos('searchterm', 'active'), { wrapper: createWrapper() });

            await waitFor(() => {
                expect(api.get).toHaveBeenCalledWith(expect.stringContaining('search=searchterm'));
                expect(api.get).toHaveBeenCalledWith(expect.stringContaining('status=active'));
            });
        });

        it('should call API to bulk delete tasks', async () => {
            store['auth_status'] = 'logged_in';
            api.get.mockResolvedValue({
                 data: { task: [], pagination: { total_pages: 1 } }
            });
            api.delete.mockResolvedValue({ count: 2 });

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.isGuest).toBe(false));

            await act(async () => {
                await result.current.bulkDeleteTodos([1, 2]);
            });

            expect(api.delete).toHaveBeenCalledWith('/tasks/delete_bulk', { ids: [1, 2] });
        });

        it('should call API to bulk mark tasks as done', async () => {
            store['auth_status'] = 'logged_in';
            api.get.mockResolvedValue({
                 data: { task: [], pagination: { total_pages: 1 } }
            });
            api.put.mockResolvedValue({ count: 2 });

            const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });
            await waitFor(() => expect(result.current.isGuest).toBe(false));

            await act(async () => {
                await result.current.bulkMarkDoneTodos([1, 2], true);
            });

            expect(api.put).toHaveBeenCalledWith('/tasks/mark_done_bulk', { ids: [1, 2], is_done: true });
        });
    });

    describe('Pagination', () => {
        it('should auto-navigate to previous page if current page becomes empty', async () => {
            store['auth_status'] = 'logged_in';

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
