import React from 'react';
import PropTypes from 'prop-types';
import Modal from './Modal';
import './DeleteTaskModal.css';

export default function DeleteTaskModal({ isOpen, onClose, onConfirm, taskTitle }) {
    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Delete Task">
            <div className="delete-modal-content">
                <p>
                    Are you sure you want to delete <strong>&quot;{taskTitle}&quot;</strong>?
                </p>
                <p className="delete-warning">This action cannot be undone.</p>
                
                <div className="modal-actions">
                    <button className="btn-cancel" onClick={onClose}>
                        Cancel
                    </button>
                    <button className="btn-delete-confirm" onClick={onConfirm}>
                        Delete
                    </button>
                </div>
            </div>
        </Modal>
    );
}

DeleteTaskModal.propTypes = {
    isOpen: PropTypes.bool.isRequired,
    onClose: PropTypes.func.isRequired,
    onConfirm: PropTypes.func.isRequired,
    taskTitle: PropTypes.string,
};
