import React, { useState } from 'react';
import TodoItem from '../components/TodoItem';
import { TodoForm } from '../components/TodoForm';
import './TodoPage.css';

export default function TodoPage() {
    const [todos, setTodos] = useState([
        { id: 1, title: 'Learn React', description: 'Understand components', isDone: false },
        { id: 2, title: 'Build Todo App', description: 'Create proper UI', isDone: true },
    ]);

    // This is the "Dummy Function"
    const handleAdd = (newTask) => {
        // Create a new task object
        const task = {
            id: Date.now(), // Generate a unique ID (fake)
            title: newTask.title,
            description: newTask.description,
            isDone: false,
        };

        // Update the state (add to the list)
        setTodos((prevTodos) => [task, ...prevTodos]);
    };

    return (
        <div className="todo-container">
            <h1>My Tasks</h1>

            <TodoForm onAdd={handleAdd} />

            <div className="todo-list">
                {todos.map((todo) => (
                    <TodoItem key={todo.id} todo={todo} />
                ))}
            </div>
        </div>
    );
}
