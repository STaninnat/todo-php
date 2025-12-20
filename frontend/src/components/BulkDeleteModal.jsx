import React from 'react';
import PropTypes from 'prop-types';
import Modal from './Modal';
import Button from './Button';
import './DeleteTaskModal.css';

/**
 * Modal component to confirm bulk task deletion.
 * @param {Object} props - Component props
 * @param {boolean} props.isOpen - Whether the modal is visible
 * @param {function} props.onClose - Function to close the modal
 * @param {function} props.onConfirm - Function to confirm deletion
 * @param {number} props.count - Number of tasks being deleted
 */
export default function BulkDeleteModal({ isOpen, onClose, onConfirm, count }) {
    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Delete Multiple Tasks">
            <div className="delete-modal-content">
                <p>
                    Are you sure you want to delete <strong>{count}</strong> tasks?
                </p>
                <p className="delete-warning">This action cannot be undone.</p>
                
                <div className="modal-actions">
                    <Button variant="secondary" onClick={onClose} className="btn-cancel">
                        Cancel
                    </Button>
                    <Button variant="danger" onClick={onConfirm} className="btn-delete-confirm">
                        Delete
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

BulkDeleteModal.propTypes = {
    isOpen: PropTypes.bool.isRequired,
    onClose: PropTypes.func.isRequired,
    onConfirm: PropTypes.func.isRequired,
    count: PropTypes.number.isRequired,
};
