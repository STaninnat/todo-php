import React, { useState, useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import './TodoForm.css';

/**
 * Form component for adding new tasks.
 * Features an expandable interface that reveals description input on focus.
 * Handles click-outside to collapse.
 * @param {Object} props - Component props
 * @param {function} props.onAdd - Callback to add a new task {title, description}
 */
export function TodoForm({ onAdd, isLoading = false }) {
    const [isExpanded, setIsExpanded] = useState(false);
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');

    // Create a reference to the form element
    const formRef = useRef(null);

    // Add the event listener logic
    useEffect(() => {
        const handleClickOutside = (event) => {
            // If the form is open AND the click is NOT inside our form
            if (isExpanded && formRef.current && !formRef.current.contains(event.target)) {
                setIsExpanded(false);
            }
        };

        // Listen for clicks on the whole document
        document.addEventListener('mousedown', handleClickOutside);

        // Cleanup: Remove the listener when the component unmounts
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [isExpanded]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!title.trim()) return;

        onAdd({ title, description });

        setTitle('');
        setDescription('');
        setIsExpanded(false);
    };

    return (
        <form
            ref={formRef}
            className="todo-form"
            onSubmit={handleSubmit}
            onFocus={() => setIsExpanded(true)}
        >
            <input
                type="text"
                className="todo-input"
                placeholder="What needs to be done?"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                disabled={isLoading}
            />

            <div className={`form-expanded ${isExpanded ? 'active' : ''}`}>
                <textarea
                    className="todo-textarea"
                    placeholder="Description (optional)"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    disabled={isLoading}
                />
                <div className="form-actions">
                    <button
                        type="button"
                        className="btn-cancel"
                        onClick={(e) => {
                            e.stopPropagation();
                            setIsExpanded(false);
                        }}
                    >
                        Cancel
                    </button>
                    <button type="submit" className="btn-add" disabled={isLoading}>
                        {isLoading ? 'Adding...' : 'Add Task'}
                    </button>
                </div>
            </div>
        </form>
    );
}

TodoForm.propTypes = {
    onAdd: PropTypes.func.isRequired,
    isLoading: PropTypes.bool,
};
