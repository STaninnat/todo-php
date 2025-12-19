import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
/**
 * @file UpdateTaskModal.test.jsx
 * @description Unit tests for UpdateTaskModal component.
 * Verifies form population with task data and submission of updates.
 */
import UpdateTaskModal from './UpdateTaskModal';

describe('UpdateTaskModal Component', () => {
    const mockUpdate = vi.fn();
    const mockClose = vi.fn();
    const mockTask = {
        id: 123,
        title: 'Old Title',
        description: 'Old Description',
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should render with task data populated', () => {
        render(
            <UpdateTaskModal 
                isOpen={true} 
                onClose={mockClose} 
                onUpdate={mockUpdate} 
                task={mockTask} 
            />
        );

        expect(screen.getByDisplayValue('Old Title')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Old Description')).toBeInTheDocument();
    });

    it('should call onUpdate with new data on submit', () => {
        render(
            <UpdateTaskModal 
                isOpen={true} 
                onClose={mockClose} 
                onUpdate={mockUpdate} 
                task={mockTask} 
            />
        );

        const titleInput = screen.getByLabelText('Title');
        const descInput = screen.getByLabelText('Description');
        const saveBtn = screen.getByText('Save Changes').closest('button');

        fireEvent.change(titleInput, { target: { value: 'Updated Title' } });
        fireEvent.change(descInput, { target: { value: 'Updated Description' } });

        fireEvent.click(saveBtn);

        expect(mockUpdate).toHaveBeenCalledWith({
            ...mockTask,
            title: 'Updated Title',
            description: 'Updated Description',
        });
        expect(mockClose).toHaveBeenCalled();
    });

    it('should call onClose when Cancel is clicked', () => {
        render(
            <UpdateTaskModal 
                isOpen={true} 
                onClose={mockClose} 
                onUpdate={mockUpdate} 
                task={mockTask} 
            />
        );

        fireEvent.click(screen.getByText('Cancel'));
        expect(mockClose).toHaveBeenCalled();
    });
});
