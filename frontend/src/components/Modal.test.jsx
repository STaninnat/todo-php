import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
/**
 * @file Modal.test.jsx
 * @description Unit tests for the generic Modal component.
 * Covers visibility, closing mechanisms (backdrop, escape, close button), and content rendering.
 */
import Modal from './Modal';

describe('Modal Component', () => {
    const defaultProps = {
        isOpen: true,
        onClose: vi.fn(),
        title: 'Test Modal',
        children: <div>Modal Content</div>,
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should result null when isOpen is false', () => {
        const { container } = render(<Modal {...defaultProps} isOpen={false} />);
        expect(container).toBeEmptyDOMElement();
    });

    it('should render correctly when isOpen is true', () => {
        render(<Modal {...defaultProps} />);
        expect(screen.getByText('Test Modal')).toBeInTheDocument();
        expect(screen.getByText('Modal Content')).toBeInTheDocument();
        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('should call onClose when close button is clicked', () => {
        render(<Modal {...defaultProps} />);
        const closeButton = screen.getByLabelText('Close modal');
        fireEvent.click(closeButton);
        expect(defaultProps.onClose).toHaveBeenCalledTimes(1);
    });

    it('should call onClose when clicking on the backdrop', () => {
        render(<Modal {...defaultProps} />);
        // The backdrop is the outer div with class 'modal-backdrop'
        // In the component: <div className="modal-backdrop" onClick={handleBackdropClick}>
        // finding specifically by class is a bit tricky with testing-lib queries best practices, 
        // but since we don't have a specific role or text on the backdrop, we can rely on DOM structure or add a test id if needed.
        // However, looking at the DOM: role="dialog" is inner. The backdrop is its parent. 
        // Actually, the structure is: backdrop -> container(dialog) -> content.
        // Clicking container shouldn't close. Clicking backdrop (outside container) should.
        
        // This is tricky to target precisely without a testId on the backdrop, 
        // but we can try targeting the container via role and getting its parent.
        const dialog = screen.getByRole('dialog');
        const backdrop = dialog.parentElement;
        
        fireEvent.click(backdrop);
        expect(defaultProps.onClose).toHaveBeenCalled();
    });

    it('should not call onClose when clicking inside the modal content', () => {
        render(<Modal {...defaultProps} />);
        const dialog = screen.getByRole('dialog');
        fireEvent.click(dialog);
        expect(defaultProps.onClose).not.toHaveBeenCalled();
    });

    it('should call onClose when Escape key is pressed', () => {
        render(<Modal {...defaultProps} />);
        fireEvent.keyDown(document, { key: 'Escape' });
        expect(defaultProps.onClose).toHaveBeenCalled();
    });
});
