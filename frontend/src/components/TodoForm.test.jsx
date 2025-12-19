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
        const submitBtn = screen.getByText('Add Task').closest('button');

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
        const submitBtn = screen.getByText('Add Task').closest('button');

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

    it('should disable inputs and button when isLoading is true', () => {
        render(<TodoForm onAdd={mockAdd} isLoading={true} />);
        
        // Expand to see all elements
        const input = screen.getByPlaceholderText('What needs to be done?');
        fireEvent.focus(input);
        
        const textarea = screen.getByPlaceholderText('Description (optional)');
        const submitBtn = screen.getByText('Add Task').closest('button'); // Get the button element

        expect(input).toBeDisabled();
        
        expect(textarea).toBeDisabled();
        expect(submitBtn).toBeDisabled();
        expect(submitBtn).toHaveClass('btn-loading'); // Check for loading class
        expect(screen.getByTestId('loading-spinner')).toBeInTheDocument(); // Check for spinner
    });
});
