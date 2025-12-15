import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
/**
 * @file DeleteTaskModal.test.jsx
 * @description Unit tests for the DeleteTaskModal component.
 * Verifies rendering of warning message and interaction with confirm/cancel buttons.
 */
import DeleteTaskModal from './DeleteTaskModal';

describe('DeleteTaskModal Component', () => {
    const mockConfirm = vi.fn();
    const mockClose = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should render correct warning message', () => {
        render(
            <DeleteTaskModal 
                isOpen={true} 
                onClose={mockClose} 
                onConfirm={mockConfirm} 
                taskTitle="My Task" 
            />
        );

        expect(screen.getByText('Delete Task')).toBeInTheDocument();
        expect(screen.getByText(/Are you sure you want to delete/)).toBeInTheDocument();
        expect(screen.getByText(/"My Task"/)).toBeInTheDocument();
    });

    it('should call onConfirm when Delete is clicked', () => {
        render(
            <DeleteTaskModal 
                isOpen={true} 
                onClose={mockClose} 
                onConfirm={mockConfirm} 
                taskTitle="My Task" 
            />
        );

        fireEvent.click(screen.getByText('Delete'));
        expect(mockConfirm).toHaveBeenCalled();
    });

    it('should call onClose when Cancel is clicked', () => {
        render(
            <DeleteTaskModal 
                isOpen={true} 
                onClose={mockClose} 
                onConfirm={mockConfirm} 
                taskTitle="My Task" 
            />
        );

        fireEvent.click(screen.getByText('Cancel'));
        expect(mockClose).toHaveBeenCalled();
    });
});
