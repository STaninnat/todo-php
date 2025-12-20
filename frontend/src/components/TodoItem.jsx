import React from 'react';
import PropTypes from 'prop-types';
import Button from './Button';
import { Trash2, Edit } from 'lucide-react';
import './TodoItem.css';

/**
 * Component representing a single todo task.
 * Displays task details and action buttons (edit, delete, toggle).
 * @param {Object} props - Component props
 * @param {Object} props.todo - The todo object {id, title, description, isDone}
 * @param {function} props.onToggle - Callback to toggle task status
 * @param {function} props.onDelete - Callback to delete task
 * @param {function} props.onUpdate - Callback to initiate update
 */
export default function TodoItem({ 
    todo, 
    onToggle, 
    onDelete, 
    onUpdate, 
    isSelectionMode = false, 
    isSelected = false, 
    onSelect 
}) {
    const handleCheckboxChange = () => {
        if (isSelectionMode) {
            onSelect(todo.id);
        } else {
            onToggle(todo.id);
        }
    };

    return (
        <div className={`todo-item ${todo.isDone ? 'done' : ''} ${isSelected ? 'selected' : ''}`}>
            <input 
                type="checkbox" 
                checked={isSelectionMode ? isSelected : todo.isDone} 
                onChange={handleCheckboxChange} 
            />

            <div className="todo-content">
                <h3 style={{ textDecoration: todo.isDone ? 'line-through' : 'none' }}>
                    {todo.title}
                </h3>
                <p>{todo.description}</p>
            </div>

            {!isSelectionMode && (
                <div className="todo-actions">
                    <Button 
                        onClick={() => onUpdate(todo)} 
                        variant="icon"
                        className="btn-edit"
                        aria-label="Edit task"
                    >
                        <Edit size={18} />
                    </Button>
                    <Button 
                        onClick={() => onDelete(todo.id)} 
                        variant="icon"
                        className="btn-delete"
                        aria-label="Delete task"
                    >
                        <Trash2 size={18} />
                    </Button>
                </div>
            )}
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
    isSelectionMode: PropTypes.bool,
    isSelected: PropTypes.bool,
    onSelect: PropTypes.func,
};
