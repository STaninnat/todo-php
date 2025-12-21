import React, { useState, useEffect } from 'react';
import { TodoForm } from '../components/TodoForm';
import TodoList from '../components/TodoList';
import DeleteTaskModal from '../components/DeleteTaskModal';
import BulkDeleteModal from '../components/BulkDeleteModal';
import UpdateTaskModal from '../components/UpdateTaskModal';
import Pagination from '../components/Pagination';
import ManagementBar from '../components/ManagementBar';
import FilterSidebar from '../components/FilterSidebar';
import TaskSkeletonList from '../components/TaskSkeleton';
import { useTodos } from '../hooks/useTodos';
import './TodoPage.css';

/**
 * Main Todo Page Component.
 * Orchestrates task management using `useTodos` hook.
 * Renders TodoList, TodoForm, and Modals for updating/deleting tasks.
 */

export default function TodoPage() {
    const [searchQuery, setSearchQuery] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [filter, setFilter] = useState('all');
    
    // Debounce search query to prevent excessive API calls
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchQuery);
        }, 300); // 300ms delay

        return () => clearTimeout(timer);
    }, [searchQuery]);

    const [isSelectionMode, setIsSelectionMode] = useState(false);
    const [selectedIds, setSelectedIds] = useState(new Set());

    const { 
        todos, 
        addTodo, 
        isAdding, 
        toggleTodo, 
        deleteTodo, 
        updateTodo, 
        bulkDeleteTodos,
        bulkMarkDoneTodos,
        isBulkOperating,
        page, 
        setPage, 
        totalPages,
        isFetching 
    } = useTodos(debouncedSearch, filter);

    // Modal State
    const [deleteModal, setDeleteModal] = useState({ isOpen: false, taskId: null, taskTitle: '' });
    const [updateModal, setUpdateModal] = useState({ isOpen: false, task: null });
    const [bulkDeleteModalOpen, setBulkDeleteModalOpen] = useState(false);

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

    // Selection Handlers
    const toggleSelectionMode = () => {
        setIsSelectionMode(!isSelectionMode);
        setSelectedIds(new Set()); // Clear selection on toggle
    };

    const handleSelect = (id) => {
        const newSelected = new Set(selectedIds);
        if (newSelected.has(id)) {
            newSelected.delete(id);
        } else {
            newSelected.add(id);
        }
        setSelectedIds(newSelected);
    };

    const handleBulkDeleteClick = () => {
        if (selectedIds.size > 0) {
            setBulkDeleteModalOpen(true);
        }
    };

    const confirmBulkDelete = async () => {
        await bulkDeleteTodos(Array.from(selectedIds));
        setSelectedIds(new Set());
        setIsSelectionMode(false);
        setBulkDeleteModalOpen(false);
    };

    const handleBulkMarkDone = async (isDone) => {
        await bulkMarkDoneTodos(Array.from(selectedIds), isDone);
        setSelectedIds(new Set());
        setIsSelectionMode(false);
    };

    return (
        <div className="todo-page-wrapper">
            <FilterSidebar currentFilter={filter} onFilterChange={setFilter} />
            
            <main className="todo-container">
                <h1>Focus</h1>

                <TodoForm onAdd={addTodo} isLoading={isAdding} />
                
                <ManagementBar 
                    searchQuery={searchQuery}
                    setSearchQuery={setSearchQuery}
                    isSelectionMode={isSelectionMode}
                    toggleSelectionMode={toggleSelectionMode}
                    selectedCount={selectedIds.size}
                    onBulkDelete={handleBulkDeleteClick}
                    onBulkMarkDone={handleBulkMarkDone}
                    isLoading={isBulkOperating}
                    isSearching={isFetching && !!debouncedSearch}
                />

                {isFetching && todos.length === 0 ? (
                    <TaskSkeletonList count={4} />
                ) : (
                    <TodoList
                        todos={todos}
                        onToggle={toggleTodo}
                        onDelete={handleDeleteClick}
                        onUpdate={handleUpdateClick}
                        isSelectionMode={isSelectionMode}
                        selectedIds={selectedIds}
                        onSelect={handleSelect}
                    />
                )}

                <Pagination 
                    currentPage={page} 
                    totalPages={totalPages} 
                    onPageChange={setPage} 
                />
            </main>

            {/* Modals */}
            <DeleteTaskModal 
                isOpen={deleteModal.isOpen} 
                onClose={() => setDeleteModal({ ...deleteModal, isOpen: false })}
                onConfirm={confirmDelete}
                taskTitle={deleteModal.taskTitle}
            />

            <BulkDeleteModal
                isOpen={bulkDeleteModalOpen}
                onClose={() => setBulkDeleteModalOpen(false)}
                onConfirm={confirmBulkDelete}
                count={selectedIds.size}
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
