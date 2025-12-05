import React, { useState } from 'react';
import TodoItem from '../components/TodoItem';
import './TodoPage.css';

export default function TodoPage() {
    const [todos] = useState([
        { id: 1, title: 'Learn React', description: 'Understand components', isDone: false },
        { id: 2, title: 'Build Todo App', description: 'Create proper UI', isDone: true },
    ]);

    return (
        <div className="todo-container">
            <h1>My Tasks</h1>
            <div className="todo-list">
                {todos.map((todo) => (
                    <TodoItem key={todo.id} todo={todo} />
                ))}
            </div>
        </div>
    );
}
