import React from 'react';
import PropTypes from 'prop-types';
import { Trash2, Edit } from 'lucide-react';
import './TodoItem.css';

export default function TodoItem({ todo, onToggle, onDelete, onUpdate }) {
    return (
        <div className={`todo-item ${todo.isDone ? 'done' : ''}`}>
            <input type="checkbox" checked={todo.isDone} onChange={() => onToggle(todo.id)} />

            <div className="todo-content">
                <h3 style={{ textDecoration: todo.isDone ? 'line-through' : 'none' }}>
                    {todo.title}
                </h3>
                <p>{todo.description}</p>
            </div>

            <div className="todo-actions">
                <button 
                    onClick={() => onUpdate(todo)} 
                    className="btn-icon btn-edit"
                    aria-label="Edit task"
                >
                    <Edit size={18} />
                </button>
                <button 
                    onClick={() => onDelete(todo.id)} 
                    className="btn-icon btn-delete"
                    aria-label="Delete task"
                >
                    <Trash2 size={18} />
                </button>
            </div>
        </div>
    );
}

TodoItem.propTypes = {
    todo: PropTypes.shape({
        id: PropTypes.number.isRequired,
        isDone: PropTypes.bool.isRequired,
        title: PropTypes.string.isRequired,
        description: PropTypes.string,
    }).isRequired,
    onToggle: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    onUpdate: PropTypes.func.isRequired,
};
