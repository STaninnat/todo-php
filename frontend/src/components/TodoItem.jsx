import React from 'react';
import PropTypes from 'prop-types';
import './TodoItem.css';

export default function TodoItem({ todo }) {
    return (
        <div className="todo-item">
            <input type="checkbox" checked={todo.isDone} readOnly />

            <div className="todo-content">
                <h3>{todo.title}</h3>
                <p>{todo.description}</p>
            </div>

            <button>Update</button>
            <button>Delete</button>
        </div>
    );
}

TodoItem.propTypes = {
    todo: PropTypes.shape({
        isDone: PropTypes.bool.isRequired,
        title: PropTypes.string.isRequired,
        description: PropTypes.string,
    }).isRequired,
};
