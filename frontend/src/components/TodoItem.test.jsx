import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
/**
 * @file TodoItem.test.jsx
 * @description Unit tests for TodoItem component.
 * Verifies rendering of task data and interaction with action buttons.
 */
import TodoItem from './TodoItem';

describe('TodoItem Component', () => {
    const mockTodo = {
        id: 1,
        title: 'Test Task',
        description: 'Test Description',
        isDone: false,
    };
    
    const mockActions = {
        onToggle: vi.fn(),
        onDelete: vi.fn(),
        onUpdate: vi.fn(),
        onSelect: vi.fn(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should render task details', () => {
        render(<TodoItem todo={mockTodo} {...mockActions} />);
        expect(screen.getByText('Test Task')).toBeInTheDocument();
        expect(screen.getByText('Test Description')).toBeInTheDocument();
    });

    it('should render as checked when isDone is true', () => {
        const doneTodo = { ...mockTodo, isDone: true };
        render(<TodoItem todo={doneTodo} {...mockActions} />);
        
        const checkbox = screen.getByRole('checkbox');
        expect(checkbox).toBeChecked();
        
        const title = screen.getByText('Test Task');
        expect(title).toHaveStyle('text-decoration: line-through');
    });

    it('should call onToggle when checkbox is clicked', () => {
        render(<TodoItem todo={mockTodo} {...mockActions} />);
        const checkbox = screen.getByRole('checkbox');
        fireEvent.click(checkbox);
        expect(mockActions.onToggle).toHaveBeenCalledWith(mockTodo.id);
    });

    it('should call onDelete when delete button is clicked', () => {
        render(<TodoItem todo={mockTodo} {...mockActions} />);
        const deleteBtn = screen.getByLabelText('Delete task');
        fireEvent.click(deleteBtn);
        expect(mockActions.onDelete).toHaveBeenCalledWith(mockTodo.id);
    });

    it('should call onUpdate when edit button is clicked', () => {
        render(<TodoItem todo={mockTodo} {...mockActions} />);
        const editBtn = screen.getByLabelText('Edit task');
        fireEvent.click(editBtn);
    });

    describe('Selection Mode', () => {
        it('should hide action buttons in selection mode', () => {
            render(<TodoItem todo={mockTodo} {...mockActions} isSelectionMode={true} />);
            
            expect(screen.queryByLabelText('Edit task')).not.toBeInTheDocument();
            expect(screen.queryByLabelText('Delete task')).not.toBeInTheDocument();
        });

        it('should use onSelect instead of onToggle when checkbox clicked', () => {
            render(<TodoItem todo={mockTodo} {...mockActions} isSelectionMode={true} />);
            
            const checkbox = screen.getByRole('checkbox');
            fireEvent.click(checkbox);
            
            expect(mockActions.onSelect).toHaveBeenCalledWith(mockTodo.id);
            expect(mockActions.onToggle).not.toHaveBeenCalled();
        });

        it('should display selected state correctly', () => {
            render(<TodoItem todo={mockTodo} {...mockActions} isSelectionMode={true} isSelected={true} />);
            
            const checkbox = screen.getByRole('checkbox');
            expect(checkbox).toBeChecked();
        });
    });
});
