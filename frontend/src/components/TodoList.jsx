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
                <p>No tasks yet!...enjoy this rare moment.</p>
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
