import React from 'react';
import PropTypes from 'prop-types';
import { useAutoAnimate } from '@formkit/auto-animate/react';
import TodoItem from './TodoItem';
import './TodoList.css';

/**
 * Component to render a list of todo items.
 * Displays an empty state message if the list is empty.
 * @param {Object} props - Component props
 * @param {Array} props.todos - List of todo objects
 * @param {function} props.onToggle - Callback to toggle task status
 * @param {function} props.onDelete - Callback to delete task
 * @param {function} props.onUpdate - Callback to update task
 */
export default function TodoList({ 
    todos, 
    onToggle, 
    onDelete, 
    onUpdate, 
    isSelectionMode, 
    selectedIds, 
    onSelect 
}) {
    const [listRef] = useAutoAnimate();

    if (todos.length === 0) {
        return (
            <div className="todo-list-empty">
                <div className="empty-icon-container">
                    <svg 
                        width="64" 
                        height="64" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        strokeWidth="1.5" 
                        strokeLinecap="round" 
                        strokeLinejoin="round" 
                        className="empty-icon"
                    >
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
                        <path d="M9 14h6" />
                        <path d="M9 10h6" />
                        <path d="M9 18h6" />
                    </svg>
                </div>
                <h3>No tasks yet</h3>
                <p>Enjoy your free time, or create a new task to get started!</p>
            </div>
        );
    }

    return (
        <div className="todo-list" ref={listRef}>
            {todos.map((todo) => (
                <TodoItem
                    key={todo.id}
                    todo={todo}
                    onToggle={onToggle}
                    onDelete={onDelete}
                    onUpdate={onUpdate}
                    isSelectionMode={isSelectionMode}
                    isSelected={selectedIds ? selectedIds.has(todo.id) : false}
                    onSelect={onSelect}
                />
            ))}
        </div>
    );
}

TodoList.propTypes = {
    todos: PropTypes.arrayOf(
        PropTypes.shape({
            id: PropTypes.number.isRequired,
            title: PropTypes.string.isRequired,
            description: PropTypes.string,
            isDone: PropTypes.bool.isRequired,
        }),
    ).isRequired,
    onToggle: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    onUpdate: PropTypes.func.isRequired,
    isSelectionMode: PropTypes.bool,
    selectedIds: PropTypes.instanceOf(Set),
    onSelect: PropTypes.func,
};
