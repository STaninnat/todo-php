import { useState, useEffect } from 'react';
import { api } from '../services/api';

export function useTodos() {
    const [todos, setTodos] = useState([]);
    const [isGuest, setIsGuest] = useState(true);

    // Load tasks on mount
    useEffect(() => {
        const loadTasks = async () => {
            try {
                const data = await api.get('/tasks');
                // Creating a standard structure: Array of tasks
                if (data && data.task && Array.isArray(data.task)) {
                    const normalized = data.task.map(t => ({
                        id: t.id, 
                        title: t.title, 
                        description: t.description, 
                        isDone: !!t.is_done // Convert 0/1 to bool
                    }));
                    setTodos(normalized);
                    setIsGuest(false);
                }
            } catch (err) {
                // If 401 Unauthorized, we are a guest.
                // Load from Local Storage
                if (err.status === 401) {
                    const saved = localStorage.getItem('guest_todos');
                    if (saved) {
                        try {
                            setTodos(JSON.parse(saved));
                        } catch (e) {
                            if (process.env.NODE_ENV !== 'production') {
                                console.error('Failed to parse (guest) todos', e);
                            }
                            localStorage.removeItem('guest_todos');
                        }
                    }
                    setIsGuest(true);
                } else {
                    if (process.env.NODE_ENV !== 'production') {
                        console.error('Failed to fetch tasks', err);
                    }
                }
            }
        };
        loadTasks();
    }, []);

    // Helper to save to LS
    const saveToLS = (newTodos) => {
        localStorage.setItem('guest_todos', JSON.stringify(newTodos));
    };

    // Add Task
    const addTodo = async (newTask) => {
        const tempId = Date.now();
        const optimisticTask = {
            id: tempId,
            title: newTask.title,
            description: newTask.description,
            isDone: false,
        };

        if (isGuest) {
            const updated = [optimisticTask, ...todos];
            setTodos(updated);
            saveToLS(updated);
        } else {
            // Cloud Mode
            try {
                // Optimistic UI update
                setTodos((prev) => [optimisticTask, ...prev]);
                
                // Actual API Call
                const response = await api.post('/tasks/add', {
                     title: newTask.title,
                     description: newTask.description
                });
                
                // Replace temp ID with real ID from server to allow subsequent actions
                if (response && response.task && response.task.id) {
                     setTodos(prev => prev.map(t => t.id === tempId ? { ...t, id: response.task.id } : t));
                }
            } catch (err) {
                // Revert if failed
                setTodos((prev) => prev.filter((t) => t.id !== tempId));
                if (process.env.NODE_ENV !== 'production') {
                    console.error("Failed to add task", err);
                }
                throw err;
            }
        }
    };

    const toggleTodo = async (id) => {
         // Optimistic Update
         const updatedTodos = todos.map((todo) => (todo.id === id ? { ...todo, isDone: !todo.isDone } : todo));
         const previousTodos = todos;
         setTodos(updatedTodos);

         if (isGuest) {
             saveToLS(updatedTodos);
         } else {
             try {
                 await api.put('/tasks/mark_done', { id });
             } catch (err) {
                // Revert
                setTodos(previousTodos);
                if (process.env.NODE_ENV !== 'production') {
                    console.error("Failed to toggle status", err);
                }
                throw err;
            }
         }
    };

    const deleteTodo = async (id) => {
        // Optimistic
        const previousTodos = todos;
        const updated = todos.filter((todo) => todo.id !== id);
        setTodos(updated);

        if (isGuest) {
             saveToLS(updated);
        } else {
             try {
                 await api.delete('/tasks/delete', { id });
             } catch (err) {
                 setTodos(previousTodos);
                 if (process.env.NODE_ENV !== 'production') {
                     console.error("Failed to delete", err);
                 }
                 throw err;
             }
        }
    };

   const updateTodo = async (updatedTask) => {
         // Optimistic
         const previousTodos = todos;
         const updatedList = todos.map(t => t.id === updatedTask.id ? updatedTask : t);
         setTodos(updatedList);

         if (isGuest) {
             saveToLS(updatedList);
         } else {
             try {
                 await api.put('/tasks/update', {
                     id: updatedTask.id,
                     title: updatedTask.title,
                     description: updatedTask.description
                 });
             } catch (err) {
                 setTodos(previousTodos);
                 if (process.env.NODE_ENV !== 'production') {
                     console.error("Failed to update", err);
                 }
                 throw err;
             }
         }
    };

    return {
        todos,
        isGuest,
        addTodo,
        toggleTodo,
        deleteTodo,
        updateTodo
    };
}
