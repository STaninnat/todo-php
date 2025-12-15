import React, { useState } from 'react';
import { TodoForm } from '../components/TodoForm';
import TodoList from '../components/TodoList';
import DeleteTaskModal from '../components/DeleteTaskModal';
import UpdateTaskModal from '../components/UpdateTaskModal';
import Pagination from '../components/Pagination';
import { useTodos } from '../hooks/useTodos';
import './TodoPage.css';

/**
 * Main Todo Page Component.
 * Orchestrates task management using `useTodos` hook.
 * Renders TodoList, TodoForm, and Modals for updating/deleting tasks.
 */
export default function TodoPage() {
    const { todos, addTodo, toggleTodo, deleteTodo, updateTodo, page, setPage, totalPages } = useTodos();

    // Modal State
    const [deleteModal, setDeleteModal] = useState({ isOpen: false, taskId: null, taskTitle: '' });
    const [updateModal, setUpdateModal] = useState({ isOpen: false, task: null });

    // Open Delete Modal
    const handleDeleteClick = (id) => {
        const task = todos.find(t => t.id === id);
        setDeleteModal({ isOpen: true, taskId: id, taskTitle: task ? task.title : '' });
    };

    // Confirm Delete
    const confirmDelete = async () => {
        const id = deleteModal.taskId;
        if (!id) return;
        await deleteTodo(id);
        setDeleteModal({ isOpen: false, taskId: null, taskTitle: '' });
    };

    // Open Update Modal
    const handleUpdateClick = (todo) => {
        setUpdateModal({ isOpen: true, task: todo });
    };

    // Confirm Update
    const confirmUpdate = async (updatedTask) => {
         await updateTodo(updatedTask);
         setUpdateModal({ isOpen: false, task: null });
    };

    return (
        <div className="todo-container">
            <h1>My Tasks</h1>

            <TodoForm onAdd={addTodo} />

            <TodoList
                todos={todos}
                onToggle={toggleTodo}
                onDelete={handleDeleteClick}
                onUpdate={handleUpdateClick}
            />

            <Pagination 
                currentPage={page} 
                totalPages={totalPages} 
                onPageChange={setPage} 
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
