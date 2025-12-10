import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import { api } from '../services/api';

const GUEST_KEY = 'guest_todos';

export function useTodos() {
    const queryClient = useQueryClient();
    const [isGuest, setIsGuest] = useState(true);

    // --- Query: Fetch Todos ---
    const { data: todos = [] } = useQuery({
        queryKey: ['todos'],
        queryFn: async () => {
             try {
                const data = await api.get('/tasks');
                if (data && data.task && Array.isArray(data.task)) {
                    setIsGuest(false);
                    return data.task.map(t => ({
                        id: t.id, 
                        title: t.title, 
                        description: t.description, 
                        isDone: !!t.is_done 
                    }));
                }
                return [];
             } catch (err) {
                 if (err.status === 401) {
                     setIsGuest(true);
                     const saved = localStorage.getItem(GUEST_KEY);
                     return saved ? JSON.parse(saved) : [];
                 }
                 throw err;
             }
        },
        initialData: [],
    });

    // Helper to save to LS
    const saveToLS = (newTodos) => {
        localStorage.setItem(GUEST_KEY, JSON.stringify(newTodos));
    };

    // --- Mutation: Add Todo ---
    const addMutation = useMutation({
        mutationFn: async (newTask) => {
            if (isGuest) {
                // Return mock response for guest
                return { 
                    ...newTask, 
                    id: Date.now(), 
                    isDone: false 
                };
            } else {
                 const response = await api.post('/tasks/add', {
                     title: newTask.title,
                     description: newTask.description
                 });
                 // Map backend response to frontend model
                 if (response && response.task) {
                     return {
                         id: response.task.id,
                         title: response.task.title,
                         description: response.task.description,
                         isDone: !!response.task.is_done
                     };
                 }
                 throw new Error("Invalid response from server");
            }
        },
        onMutate: async (newTask) => {
            // Cancel outgoing refetches
            await queryClient.cancelQueries({ queryKey: ['todos'] });
            
            // Snapshot previous value
            const previousTodos = queryClient.getQueryData(['todos']);

            // Optimistic Update
            const optimisticTodo = { 
                id: Date.now(), 
                title: newTask.title, 
                description: newTask.description, 
                isDone: false 
            };

            queryClient.setQueryData(['todos'], old => [optimisticTodo, ...old]);

            return { previousTodos };
        },
        onError: (err, variables, context) => {
            toast.error(err.message || "Failed to add task");
            if (context?.previousTodos) {
                queryClient.setQueryData(['todos'], context.previousTodos);
            }
        },
        onSuccess: () => {
             toast.success('Task added successfully');
             // In Guest mode, we must manually update the cache with the "saved" (mocked) todo 
             // because there is no server to refetch from that would hold this new data permanently 
             // if we rely solely on invalidation (since invalidation re-runs queryFn which reads LS).
             // However, for Guest mode, we also need to persist to LS.
             
             if (isGuest) {
                 queryClient.setQueryData(['todos'], old => {
                     saveToLS(old);
                     return old;
                 });
             } else {
                 // Cloud mode: Invalidate to get fresh data
                 queryClient.invalidateQueries({ queryKey: ['todos'] }); 
             }
        }
    });
    
    // REFACTORING MUTATIONS TO BE SIMPLER FOR GUEST/CLOUD HYBRID
    // The previous implementation had complex optimistic logic in `onMutate`.
    // For this specific hybrid app, it might be cleaner to separate the logic inside the mutationFn 
    // or handle the side-effects (LS vs Server) differently.
    
    // Let's try a cleaner approach for `addTodo` that handles both:
    
    const addTodo = async (newTask) => {
        addMutation.mutate(newTask);
    };

    // --- Mutation: Toggle Todo ---
    const toggleMutation = useMutation({
        mutationFn: async (id) => {
            if (isGuest) {
                 return { id };
            }
            await api.put('/tasks/mark_done', { id });
            return { id };
        },
        onMutate: async (id) => {
            await queryClient.cancelQueries({ queryKey: ['todos'] });
            const previousTodos = queryClient.getQueryData(['todos']);
            
            queryClient.setQueryData(['todos'], old => 
                old.map(t => t.id === id ? { ...t, isDone: !t.isDone } : t)
            );
            
            return { previousTodos };
        },
        onError: (err, vars, context) => {
            queryClient.setQueryData(['todos'], context.previousTodos);
        },
        onSuccess: () => {
            if (isGuest) {
                saveToLS(queryClient.getQueryData(['todos']));
            } else {
               // queryClient.invalidateQueries({ queryKey: ['todos'] });
               // For toggle, invalidation might cause a flash if the list order changes or is slow.
               // Since we optimistically updated, we strictly don't *need* to invalidate immediately if we trust the server.
               // But usually good practice.
            }
        }
    });

    const toggleTodo = (id) => toggleMutation.mutate(id);

    // --- Mutation: Delete Todo ---
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            if (isGuest) return id;
            await api.delete('/tasks/delete', { id });
            return id;
        },
        onMutate: async (id) => {
            await queryClient.cancelQueries({ queryKey: ['todos'] });
            const previousTodos = queryClient.getQueryData(['todos']);
            
            queryClient.setQueryData(['todos'], old => old.filter(t => t.id !== id));
            
            return { previousTodos };
        },
        onError: (err, vars, context) => {
            toast.error(err.message || "Failed to delete task");
            queryClient.setQueryData(['todos'], context.previousTodos);
        },
        onSuccess: () => {
             toast.success('Task deleted');
             if (isGuest) {
                 saveToLS(queryClient.getQueryData(['todos']));
             } else {
                 queryClient.invalidateQueries({ queryKey: ['todos'] });
             }
        }
    });

    const deleteTodo = (id) => deleteMutation.mutate(id);

    // --- Mutation: Update Todo ---
    const updateMutation = useMutation({
        mutationFn: async (updatedTask) => {
            if (isGuest) return updatedTask;
            await api.put('/tasks/update', {
                id: updatedTask.id,
                title: updatedTask.title,
                description: updatedTask.description
            });
            return updatedTask;
        },
        onMutate: async (updatedTask) => {
             await queryClient.cancelQueries({ queryKey: ['todos'] });
             const previousTodos = queryClient.getQueryData(['todos']);
             
             queryClient.setQueryData(['todos'], old => 
                 old.map(t => t.id === updatedTask.id ? updatedTask : t)
             );
             
             return { previousTodos };
        },
        onError: (err, vars, context) => {
             toast.error(err.message || "Failed to update task");
             queryClient.setQueryData(['todos'], context.previousTodos);
        },
        onSuccess: () => {
             toast.success('Task updated');
             if (isGuest) {
                 saveToLS(queryClient.getQueryData(['todos']));
             } else {
                 queryClient.invalidateQueries({ queryKey: ['todos'] });
             }
        }
    });

    const updateTodo = (task) => updateMutation.mutate(task);


    return {
        todos,
        isGuest,
        addTodo,
        toggleTodo,
        deleteTodo,
        updateTodo
    };
}
