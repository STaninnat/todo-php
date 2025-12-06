import React, { useState } from 'react';
import { TodoForm } from '../components/TodoForm';
import TodoList from '../components/TodoList';
import './TodoPage.css';

export default function TodoPage() {
    const [todos, setTodos] = useState([]);

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

    const handleToggle = (id) => {
        setTodos((prev) =>
            prev.map((todo) => (todo.id === id ? { ...todo, isDone: !todo.isDone } : todo)),
        );
    };

    const handleDelete = (id) => {
        if (window.confirm('Are you sure you want to delete this task?')) {
            setTodos((prev) => prev.filter((todo) => todo.id !== id));
        }
    };

    const handleUpdate = (todo) => {
        console.log('Opening update popup for:', todo);
        alert(`Coming soon: Update popup for "${todo.title}"`);
    };

    return (
        <div className="todo-container">
            <h1>My Tasks</h1>

            <TodoForm onAdd={handleAdd} />

            <TodoList
                todos={todos}
                onToggle={handleToggle}
                onDelete={handleDelete}
                onUpdate={handleUpdate}
            />
        </div>
    );
}
