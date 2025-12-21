import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
/**
 * @file TodoList.test.jsx
 * @description Unit tests for TodoList component.
 * Verifies rendering of lists and empty states.
 */
import TodoList from './TodoList';

vi.mock('./TodoItem', () => ({
    default: ({ todo, isSelectionMode, isSelected, onSelect }) => (
        <div data-testid="todo-item">
            <span>{todo.title}</span>
            {isSelectionMode && <span data-testid="selection-mode">Selection Mode</span>}
            {isSelected && <span data-testid="selected">Selected</span>}
            {/* Bind onSelect to click for testing */}
             <button onClick={() => onSelect(todo.id)}>Select</button>
        </div>
    ),
}));

describe('TodoList Component', () => {
    const mockActions = {
        onToggle: vi.fn(),
        onDelete: vi.fn(),
        onUpdate: vi.fn(),
        onSelect: vi.fn(),
    };

    const sampleTodos = [
        { id: 1, title: 'Task 1', description: 'Desc 1', isDone: false },
        { id: 2, title: 'Task 2', description: 'Desc 2', isDone: true },
    ];

    it('should render empty state message when no todos', () => {
        render(<TodoList todos={[]} {...mockActions} />);
        expect(screen.getByText('No tasks yet')).toBeInTheDocument();
        expect(screen.getByText(/Enjoy your free time/i)).toBeInTheDocument();
    });

    it('should render list of todos', () => {
        render(<TodoList todos={sampleTodos} {...mockActions} />);
        
        expect(screen.getByText('Task 1')).toBeInTheDocument();
        expect(screen.getByText('Task 2')).toBeInTheDocument();
        expect(screen.getAllByTestId('todo-item')).toHaveLength(2);
    });

    it('should pass selection props to TodoItem', () => {
        const selectedIds = new Set([1]);
        render(
            <TodoList 
                todos={sampleTodos} 
                {...mockActions} 
                isSelectionMode={true} 
                selectedIds={selectedIds}
            />
        );

        const items = screen.getAllByTestId('todo-item');
        
        // Check Item 1 (Selected)
        expect(items[0]).toHaveTextContent('Task 1');
        expect(items[0]).toHaveTextContent('Selection Mode');
        expect(items[0]).toHaveTextContent('Selected');

        // Check Item 2 (Not Selected)
        expect(items[1]).toHaveTextContent('Task 2');
        expect(items[1]).toHaveTextContent('Selection Mode');
        expect(items[1]).not.toHaveTextContent('Selected');
    });

    it('should call onSelect callback from TodoItem', () => {
         render(
            <TodoList 
                todos={sampleTodos} 
                {...mockActions} 
                isSelectionMode={true} 
                selectedIds={new Set()}
            />
        );
        
        // Click select button on first item (mocked above)
        screen.getAllByText('Select')[0].click();
        expect(mockActions.onSelect).toHaveBeenCalledWith(1);
    });
});
