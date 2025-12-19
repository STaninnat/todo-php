import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import { api } from '../services/api';

const GUEST_KEY = 'guest_todos';

/**
 * Custom hook for managing todos with dual-mode support (Cloud vs Guest).
 * - Cloud Mode: Fetches/Persists data to backend API.
 * - Guest Mode: Persists data to localStorage when backend is unreachable or user is unauthorized.
 * Handles pagination and optimistic updates/invalidations.
 * @returns {Object} Todos state and actions
 */
export function useTodos() {
    const queryClient = useQueryClient();

    const [page, setPage] = useState(1);

    // --- Query: Fetch Todos ---
    const { data: queryData } = useQuery({
        queryKey: ['todos', page],
        queryFn: async () => {
            try {
                const response = await api.get(`/tasks?page=${page}&limit=10`);
                // Backend returns { data: { task: [...], pagination: {...} } }
                const data = response.data || response; 

                if (data && data.task && Array.isArray(data.task)) {
                    const mappedTodos = data.task.map((t) => ({
                        id: t.id,
                        title: t.title,
                        description: t.description,
                        isDone: !!t.is_done,
                    }));

                    return {
                        todos: mappedTodos,
                        isGuest: false,
                        pagination: {
                            totalPages: data.pagination ? data.pagination.total_pages : 1,
                        },
                    };
                }

                return { todos: [], isGuest: false, pagination: { totalPages: 1 } };
            } catch (err) {
                // Fallback to guest mode on:
                // - 401 (Unauthorized)
                // - 5xx (Backend Error / Proxy Error / Connection Refused from Proxy)
                // - Network Error (no status)
                const isBackendDown = !err.status || err.status >= 500;
                if (err.status === 401 || isBackendDown) {
                    const saved = localStorage.getItem(GUEST_KEY);
                    let allTodos = saved ? JSON.parse(saved) : [];

                    // Sort to match backend: isDone ASC, then Created/ID DESC
                    allTodos.sort((a, b) => {
                        if (a.isDone === b.isDone) {
                            return b.id - a.id;
                        }
                        return a.isDone ? 1 : -1;
                    });

                    // Client-side pagination
                    const limit = 10;
                    const totalItems = allTodos.length;
                    const totalPages = Math.ceil(totalItems / limit) || 1;

                    const startIndex = (page - 1) * limit;
                    const slicedTodos = allTodos.slice(startIndex, startIndex + limit);

                    return {
                        todos: slicedTodos,
                        isGuest: true,
                        pagination: { totalPages },
                    };
                }
                throw err;
            }
        },
        // Fallback initial data
        initialData: { todos: [], isGuest: true, pagination: { totalPages: 1 } },
        keepPreviousData: true,
    });

    // Derive state from query data
    const todos = queryData?.todos || [];
    const isGuest = queryData?.isGuest ?? true;
    const totalPages = queryData?.pagination?.totalPages || 1;

    // Auto-navigate to previous page if current page becomes empty
    useEffect(() => {
        if (page > totalPages && page > 1) {
            // eslint-disable-next-line
            setPage((prev) => Math.max(prev - 1, 1));
        }
    }, [page, totalPages]);

    // Helper to save to LS
    const saveToLS = (newTodos) => {
        localStorage.setItem(GUEST_KEY, JSON.stringify(newTodos));
    };

    // Helper to get from LS
    const getFromLS = () => {
        const saved = localStorage.getItem(GUEST_KEY);
        return saved ? JSON.parse(saved) : [];
    };

    // --- Mutation: Add Todo ---
    const addMutation = useMutation({
        mutationFn: async (newTask) => {
            if (isGuest) {
                const current = getFromLS();
                const todo = {
                    ...newTask,
                    id: Date.now(),
                    isDone: false,
                };
                saveToLS([todo, ...current]);
                return todo;
            } else {
                const response = await api.post('/tasks/add', {
                    title: newTask.title,
                    description: newTask.description,
                });
                
                const task = response?.data?.task || response?.task;

                if (task) {
                    return {
                        id: task.id,
                        title: task.title,
                        description: task.description,
                        isDone: !!task.is_done,
                    };
                }
                throw new Error('Invalid response from server');
            }
        },
        onSuccess: () => {
            toast.success('Task added successfully');
            queryClient.invalidateQueries({ queryKey: ['todos'] });
        },
        onError: (err) => {
            toast.error(err.message || 'Failed to add task');
        },
    });

    const addTodo = (newTask) => addMutation.mutate(newTask);

    // --- Mutation: Toggle Todo ---
    const toggleMutation = useMutation({
        mutationFn: async (id) => {
            if (isGuest) {
                const current = getFromLS();
                const updated = current.map((t) => (t.id === id ? { ...t, isDone: !t.isDone } : t));
                saveToLS(updated);
                return { id };
            }
            await api.put('/tasks/mark_done', { id });
            return { id };
        },
        onSuccess: () => {
            // For toggle, invalidation is safest for consistency
            queryClient.invalidateQueries({ queryKey: ['todos'] });
        },
        onError: (err) => {
            toast.error(err.message || 'Failed to toggle task');
        },
    });

    const toggleTodo = (id) => toggleMutation.mutate(id);

    // --- Mutation: Delete Todo ---
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            if (isGuest) {
                const current = getFromLS();
                const updated = current.filter((t) => t.id !== id);
                saveToLS(updated);
                return id;
            }
            await api.delete('/tasks/delete', { id });
            return id;
        },
        onSuccess: () => {
            toast.success('Task deleted');
            queryClient.invalidateQueries({ queryKey: ['todos'] });
        },
        onError: (err) => {
            toast.error(err.message || 'Failed to delete task');
        },
    });

    const deleteTodo = (id) => deleteMutation.mutate(id);

    // --- Mutation: Update Todo ---
    const updateMutation = useMutation({
        mutationFn: async (updatedTask) => {
            if (isGuest) {
                const current = getFromLS();
                const updated = current.map((t) =>
                    t.id === updatedTask.id ? { ...t, ...updatedTask } : t,
                );
                saveToLS(updated);
                return updatedTask;
            }
            await api.put('/tasks/update', {
                id: updatedTask.id,
                title: updatedTask.title,
                description: updatedTask.description,
            });
            return updatedTask;
        },
        onSuccess: () => {
            toast.success('Task updated');
            queryClient.invalidateQueries({ queryKey: ['todos'] });
        },
        onError: (err) => {
            toast.error(err.message || 'Failed to update task');
        },
    });

    const updateTodo = (task) => updateMutation.mutate(task);

    return {
        todos,
        isGuest,
        addTodo,
        isAdding: addMutation.isPending,
        toggleTodo,
        deleteTodo,
        updateTodo,
        page,
        setPage,
        totalPages,
    };
}
