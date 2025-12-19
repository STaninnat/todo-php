import React, { useEffect, useRef } from 'react';
import Button from './Button';
import { X } from 'lucide-react';
import PropTypes from 'prop-types';
import './Modal.css';

/**
 * Generic Modal component.
 * Handles backdrop click, escape key, and body scroll locking.
 * @param {Object} props - Component props
 * @param {boolean} props.isOpen - Whether the modal is visible
 * @param {function} props.onClose - Function to call on close request
 * @param {string} props.title - Modal title
 * @param {React.ReactNode} props.children - Modal content
 */
export default function Modal({ isOpen, onClose, title, children }) {
    const modalRef = useRef(null);

    // Close on Escape key
    useEffect(() => {
        const handleEscape = (e) => {
            if (e.key === 'Escape') onClose();
        };

        if (isOpen) {
            document.addEventListener('keydown', handleEscape);
            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
        }

        return () => {
            document.removeEventListener('keydown', handleEscape);
            document.body.style.overflow = 'unset';
        };
    }, [isOpen, onClose]);

    // Close on click outside
    const handleBackdropClick = (e) => {
        if (modalRef.current && !modalRef.current.contains(e.target)) {
            onClose();
        }
    };

    if (!isOpen) return null;

    return (
        <div className="modal-backdrop" onClick={handleBackdropClick}>
            <div className="modal-container" ref={modalRef} role="dialog" aria-modal="true">
                <div className="modal-header">
                    <h3>{title}</h3>
                    <Button variant="icon" className="btn-close" onClick={onClose} aria-label="Close modal">
                        <X size={20} />
                    </Button>
                </div>
                <div className="modal-content">
                    {children}
                </div>
            </div>
        </div>
    );
}

Modal.propTypes = {
    isOpen: PropTypes.bool.isRequired,
    onClose: PropTypes.func.isRequired,
    title: PropTypes.string.isRequired,
    children: PropTypes.node.isRequired,
};
