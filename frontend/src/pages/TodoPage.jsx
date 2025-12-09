import React, { useState } from 'react';
import { TodoForm } from '../components/TodoForm';
import TodoList from '../components/TodoList';
import DeleteTaskModal from '../components/DeleteTaskModal';
import UpdateTaskModal from '../components/UpdateTaskModal';
import { api } from '../services/api';
import './TodoPage.css';

export default function TodoPage() {
    const [todos, setTodos] = useState([]);
    const [isGuest, setIsGuest] = useState(true);

    // Modal State
    const [deleteModal, setDeleteModal] = useState({ isOpen: false, taskId: null, taskTitle: '' });
    const [updateModal, setUpdateModal] = useState({ isOpen: false, task: null });

    // Load tasks on mount
    React.useEffect(() => {
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
    const handleAdd = async (newTask) => {
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
                // Optionally show a flash error
            }
        }
    };

    const handleToggle = async (id) => {
         // Optimistic Update
         const updatedTodos = todos.map((todo) => (todo.id === id ? { ...todo, isDone: !todo.isDone } : todo));
         setTodos(updatedTodos);

         if (isGuest) {
             saveToLS(updatedTodos);
         } else {
             try {
                 await api.put('/tasks/mark_done', { id });
             } catch (err) {
                // Revert
                setTodos(todos);
                if (process.env.NODE_ENV !== 'production') {
                    console.error("Failed to toggle status", err);
                }
            }
         }
    };

    // Open Delete Modal
    const handleDeleteClick = (id) => {
        const task = todos.find(t => t.id === id);
        setDeleteModal({ isOpen: true, taskId: id, taskTitle: task ? task.title : '' });
    };

    // Confirm Delete
    const confirmDelete = async () => {
        const id = deleteModal.taskId;
        if (!id) return;

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
             }
        }
        setDeleteModal({ isOpen: false, taskId: null, taskTitle: '' });
    };

    // Open Update Modal
    const handleUpdateClick = (todo) => {
        setUpdateModal({ isOpen: true, task: todo });
    };

    // Confirm Update
    const confirmUpdate = async (updatedTask) => {
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
             }
         }
         setUpdateModal({ isOpen: false, task: null });
    };

    return (
        <div className="todo-container">
            <h1>My Tasks</h1>

            <TodoForm onAdd={handleAdd} />

            <TodoList
                todos={todos}
                onToggle={handleToggle}
                onDelete={handleDeleteClick}
                onUpdate={handleUpdateClick}
            />

            {/* Modals */}
            <DeleteTaskModal 
                isOpen={deleteModal.isOpen} 
                onClose={() => setDeleteModal({ ...deleteModal, isOpen: false })}
                onConfirm={confirmDelete}
                taskTitle={deleteModal.taskTitle}
            />

            <UpdateTaskModal
                isOpen={updateModal.isOpen}
                onClose={() => setUpdateModal({ ...updateModal, isOpen: false })}
                onUpdate={confirmUpdate}
                task={updateModal.task}
            />
        </div>
    );
}
