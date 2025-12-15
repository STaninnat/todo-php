import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
/**
 * @file TodoList.test.jsx
 * @description Unit tests for TodoList component.
 * Verifies rendering of lists and empty states.
 */
import TodoList from './TodoList';

describe('TodoList Component', () => {
    const mockActions = {
        onToggle: vi.fn(),
        onDelete: vi.fn(),
        onUpdate: vi.fn(),
    };

    it('should render empty state message when no todos', () => {
        render(<TodoList todos={[]} {...mockActions} />);
        expect(screen.getByText(/No tasks yet/i)).toBeInTheDocument();
    });

    it('should render list of todos', () => {
        const todos = [
            { id: 1, title: 'Task 1', description: 'Desc 1', isDone: false },
            { id: 2, title: 'Task 2', description: 'Desc 2', isDone: true },
        ];
        render(<TodoList todos={todos} {...mockActions} />);
        
        expect(screen.getByText('Task 1')).toBeInTheDocument();
        expect(screen.getByText('Task 2')).toBeInTheDocument();
        expect(screen.getAllByRole('checkbox')).toHaveLength(2);
    });
});
