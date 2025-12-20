/**
 * @file BulkDeleteModal.test.jsx
 * @description Unit tests for BulkDeleteModal component.
 * Verifies rendering of deletion confirmation and count display.
 */
import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import BulkDeleteModal from './BulkDeleteModal';

// Mock the Modal component to avoid portal/interaction complexity and isolate unit test
vi.mock('./Modal', () => ({
    default: ({ isOpen, title, children, onClose }) => {
        if (!isOpen) return null;
        return (
            <div role="dialog" aria-label={title}>
                <h1>{title}</h1>
                <button onClick={onClose} aria-label="Close">X</button>
                {children}
            </div>
        );
    },
}));

describe('BulkDeleteModal Component', () => {
    const defaultProps = {
        isOpen: true,
        onClose: vi.fn(),
        onConfirm: vi.fn(),
        count: 5,
    };

    it('should render correct message with count when open', () => {
        render(<BulkDeleteModal {...defaultProps} />);
        
        // Check for title passed to Modal
        expect(screen.getByRole('dialog', { name: 'Delete Multiple Tasks' })).toBeInTheDocument();
        
        // Check for count in message
        // The text is split "Are you sure... <strong>5</strong> tasks?"
        // We can check for existence of the number and context
        expect(screen.getByText('5')).toBeInTheDocument();
        expect(screen.getByText(/Are you sure you want to delete/)).toBeInTheDocument();
        expect(screen.getByText((content, element) => {
            return element.tagName.toLowerCase() === 'p' && content.includes('Are you sure');
        })).toBeInTheDocument();
    });

    it('should not render content when isOpen is false', () => {
        render(<BulkDeleteModal {...defaultProps} isOpen={false} />);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('should call onClose when Cancel is clicked', () => {
        render(<BulkDeleteModal {...defaultProps} />);
        
        const cancelButton = screen.getByText('Cancel');
        fireEvent.click(cancelButton);
        
        expect(defaultProps.onClose).toHaveBeenCalled();
    });

    it('should call onConfirm when Delete is clicked', () => {
        render(<BulkDeleteModal {...defaultProps} />);
        
        // There might be two deletions (button and modal title?), explicitly get the button
        // The mock modal X button is not the "Delete" action button.
        // The action button text is "Delete"
        const deleteButton = screen.getByRole('button', { name: 'Delete' });
        fireEvent.click(deleteButton);
        
        expect(defaultProps.onConfirm).toHaveBeenCalled();
    });
});
