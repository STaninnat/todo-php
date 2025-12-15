import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
/**
 * @file TodoForm.test.jsx
 * @description Unit tests for TodoForm component.
 * Tests expand/collapse behavior on focus/blur and form submission logic.
 */
import { TodoForm } from './TodoForm';

describe('TodoForm Component', () => {
    const mockAdd = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should render collapsed initially', () => {
        render(<TodoForm onAdd={mockAdd} />);
        expect(screen.getByPlaceholderText('What needs to be done?')).toBeInTheDocument();
        // Description should not be visible or container should not have active class
        // In the code: <div className={`form-expanded ${isExpanded ? 'active' : ''}`}>
        // We can check for the class on that div.
        // Or check if textarea is visible? It's always rendered but maybe hidden via CSS?
        // Code: className="form-expanded" (and active/inactive). CSS likely handles visibility.
        // We can check if the container has class 'active' or not.
        
        // Let's find the container. It contains the textarea.
        const textarea = screen.getByPlaceholderText('Description (optional)');
        const container = textarea.parentElement; // div.form-expanded
        expect(container).not.toHaveClass('active');
    });

    it('should expand when input is focused', () => {
        render(<TodoForm onAdd={mockAdd} />);
        const input = screen.getByPlaceholderText('What needs to be done?');
        fireEvent.focus(input);
        
        const textarea = screen.getByPlaceholderText('Description (optional)');
        const container = textarea.parentElement;
        expect(container).toHaveClass('active');
    });

    it('should submit form with title and description', () => {
        render(<TodoForm onAdd={mockAdd} />);
        const input = screen.getByPlaceholderText('What needs to be done?');
        const textarea = screen.getByPlaceholderText('Description (optional)');
        const submitBtn = screen.getByText('Add Task');

        // Expand first
        fireEvent.focus(input);

        // Fill form
        fireEvent.change(input, { target: { value: 'New Task' } });
        fireEvent.change(textarea, { target: { value: 'New Desc' } });

        fireEvent.click(submitBtn);

        expect(mockAdd).toHaveBeenCalledWith({ title: 'New Task', description: 'New Desc' });
        // Should clear inputs and collapse?
        expect(input.value).toBe('');
        expect(textarea.value).toBe('');
    });

    it('should not submit if title is empty', () => {
        render(<TodoForm onAdd={mockAdd} />);
        const input = screen.getByPlaceholderText('What needs to be done?');
        const submitBtn = screen.getByText('Add Task');

        fireEvent.focus(input);
        fireEvent.click(submitBtn);

        expect(mockAdd).not.toHaveBeenCalled();
    });

    it('should collapse when Cancel is clicked', () => {
        render(<TodoForm onAdd={mockAdd} />);
        const input = screen.getByPlaceholderText('What needs to be done?');
        fireEvent.focus(input);
        
        const cancelBtn = screen.getByText('Cancel');
        fireEvent.click(cancelBtn);

        const textarea = screen.getByPlaceholderText('Description (optional)');
        const container = textarea.parentElement;
        expect(container).not.toHaveClass('active');
    });
});
