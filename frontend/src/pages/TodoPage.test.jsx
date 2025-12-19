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

describe('TodoPage', () => {
    const mockUseTodos = {
        todos: [
            { id: 1, title: 'Task 1', description: 'Desc 1', isDone: false },
        ],
        addTodo: vi.fn(),
        toggleTodo: vi.fn(),
        deleteTodo: vi.fn(),
        updateTodo: vi.fn(),
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

    it('should open delete modal and confirm deletion', async () => {
        render(<TodoPage />);
        const deleteBtn = screen.getByLabelText('Delete task'); // Assuming aria-label from TodoItem
        
        fireEvent.click(deleteBtn);

        // Modal should appear
        const modalDeleteBtn = screen.getByText('Delete').closest('button');
        expect(screen.getByText(/Are you sure you want to delete/)).toBeInTheDocument();

        fireEvent.click(modalDeleteBtn);

        expect(mockUseTodos.deleteTodo).toHaveBeenCalledWith(1);
    });

    it('should open update modal and save changes', () => {
        render(<TodoPage />);
        const editBtn = screen.getByLabelText('Edit task');

        fireEvent.click(editBtn);

        // Modal should appear with populated data
        const titleInput = screen.getByDisplayValue('Task 1');
        const saveBtn = screen.getByText('Save Changes').closest('button');

        fireEvent.change(titleInput, { target: { value: 'Updated Task' } });
        fireEvent.click(saveBtn);

        expect(mockUseTodos.updateTodo).toHaveBeenCalledWith(expect.objectContaining({
            id: 1,
            title: 'Updated Task',
        }));
    });
});
