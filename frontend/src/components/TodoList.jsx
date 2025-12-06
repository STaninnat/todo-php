import React from 'react';
import PropTypes from 'prop-types';
import TodoItem from './TodoItem';
import './TodoList.css';

export default function TodoList({ todos, onToggle, onDelete, onUpdate }) {
    if (todos.length === 0) {
        return (
            <div className="todo-list-empty">
                <p>No tasks yet. Add one above!</p>
            </div>
        );
    }

    return (
        <div className="todo-list">
            {todos.map((todo) => (
                <TodoItem
                    key={todo.id}
                    todo={todo}
                    onToggle={onToggle}
                    onDelete={onDelete}
                    onUpdate={onUpdate}
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
};
