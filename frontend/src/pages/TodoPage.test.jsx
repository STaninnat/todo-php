import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import TodoPage from './TodoPage';
import { useTodos } from '../hooks/useTodos';

/**
 * @file TodoPage.test.jsx
 * @description Integration tests for TodoPage.
 * Verifies interaction between TodoList, TodoForm, Modals and the useTodos hook.
 */

// Mock hook
vi.mock('../hooks/useTodos');

// Mock child components to simplify integration testing
vi.mock('../components/ManagementBar', () => ({
    default: ({ toggleSelectionMode, onBulkDelete, onBulkMarkDone, isSelectionMode, selectedCount }) => (
        <div data-testid="management-bar">
            <button onClick={toggleSelectionMode}>
                {isSelectionMode ? 'Cancel Selection' : 'Select Mode'}
            </button>
            <button onClick={onBulkDelete}>Bulk Delete</button>
            <button onClick={() => onBulkMarkDone(true)}>Bulk Mark Done</button>
            <span data-testid="selected-count">{selectedCount}</span>
        </div>
    ),
}));

vi.mock('../components/BulkDeleteModal', () => ({
    default: ({ isOpen, onConfirm }) => (
        isOpen ? (
            <div data-testid="bulk-delete-modal">
                <button onClick={onConfirm}>Confirm Bulk Delete</button>
            </div>
        ) : null
    ),
}));

vi.mock('../components/TodoList', () => ({
    default: ({ todos, onSelect }) => (
        <div data-testid="todo-list">
            {todos.map(t => (
                <div key={t.id} data-testid="todo-item-mock">
                    <span>{t.title}</span>
                    <button onClick={() => onSelect(t.id)}>Select Item</button>
                </div>
            ))}
        </div>
    ),
}));

vi.mock('../components/FilterSidebar', () => ({
    default: ({ currentFilter, onFilterChange }) => (
        <div data-testid="filter-sidebar">
            <span>Current: {currentFilter}</span>
            <button onClick={() => onFilterChange('active')}>Filter: active</button>
            <button onClick={() => onFilterChange('completed')}>Filter: completed</button>
        </div>
    ),
}));


describe('TodoPage', () => {
    const mockUseTodos = {
        todos: [
            { id: 1, title: 'Task 1', description: 'Desc 1', isDone: false },
            { id: 2, title: 'Task 2', description: 'Desc 2', isDone: true },
        ],
        addTodo: vi.fn(),
        toggleTodo: vi.fn(),
        deleteTodo: vi.fn(),
        updateTodo: vi.fn(),
        bulkDeleteTodos: vi.fn(),
        bulkMarkDoneTodos: vi.fn(),
        isBulkOperating: false,
        isFetching: false,
        page: 1,
        setPage: vi.fn(),
        totalPages: 1,
    };

    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(useTodos).mockReturnValue(mockUseTodos);
    });

    it('should render todos and form', () => {
        render(<TodoPage />);
        expect(screen.getByText('My Tasks')).toBeInTheDocument();
        expect(screen.getByText('Task 1')).toBeInTheDocument();
        // Form input
        expect(screen.getByPlaceholderText('What needs to be done?')).toBeInTheDocument();
    });

    it('should call addTodo from form', () => {
        render(<TodoPage />);
        const input = screen.getByPlaceholderText('What needs to be done?');
        const submitBtn = screen.getByText('Add Task').closest('button');

        fireEvent.focus(input); // Expand form
        fireEvent.change(input, { target: { value: 'New Task' } });
        fireEvent.click(submitBtn);

        expect(mockUseTodos.addTodo).toHaveBeenCalledWith({ title: 'New Task', description: '' });
    });

    it('should toggle selection mode and select items', () => {
        render(<TodoPage />);
        const toggleBtn = screen.getByText('Select Mode');
        
        // Enter selection mode
        fireEvent.click(toggleBtn);
        expect(screen.getByText('Cancel Selection')).toBeInTheDocument();

        // Select item 1 (using mocked TodoList button)
        const selectItemBtns = screen.getAllByText('Select Item');
        fireEvent.click(selectItemBtns[0]); // Select Task 1

        // Check count update in ManagementBar
        expect(screen.getByTestId('selected-count')).toHaveTextContent('1');
    });

    it('should trigger bulk delete', async () => {
        render(<TodoPage />);
        
        // Enter selection mode
        fireEvent.click(screen.getByText('Select Mode'));
        
        // Select Task 1
        const selectItemBtns = screen.getAllByText('Select Item');
        fireEvent.click(selectItemBtns[0]);

        // Click Bulk Delete in ManagementBar
        fireEvent.click(screen.getByText('Bulk Delete'));

        // Confirm in Modal
        const confirmBtn = screen.getByText('Confirm Bulk Delete');
        fireEvent.click(confirmBtn);

        expect(mockUseTodos.bulkDeleteTodos).toHaveBeenCalledWith([1]);
    });

    it('should trigger bulk mark done', () => {
        render(<TodoPage />);
        
        // Enter selection mode
        fireEvent.click(screen.getByText('Select Mode'));
        
        // Select Task 1
        const selectItemBtns = screen.getAllByText('Select Item');
        fireEvent.click(selectItemBtns[0]);

        // Click Bulk Mark Done
        fireEvent.click(screen.getByText('Bulk Mark Done'));

        expect(mockUseTodos.bulkMarkDoneTodos).toHaveBeenCalledWith([1], true);
    });

    it('should update filter state and call useTodos with new filter', () => {
        render(<TodoPage />);
        
        // Find FilterSidebar mock buttons (assuming we mock it shortly)
        // or if using real component, use text.
        // Let's add the mock for FilterSidebar first
        const activeFilterBtn = screen.getByText('Filter: active');
        fireEvent.click(activeFilterBtn);

        // useTodos is called on render with default 'all'
        // After click, it should be called with 'active'
        // Since useTodos is a hook, checking its calls is tricky directly in this setup 
        // without a specialized test helper or checking the hook mock calls.
        
        expect(useTodos).toHaveBeenLastCalledWith('', 'active');
    });
});
