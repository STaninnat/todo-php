import React, { useState } from 'react';
import PropTypes from 'prop-types';
import Modal from './Modal';
import Button from './Button';
import './UpdateTaskModal.css';

/**
 * Modal component for updating an existing task.
 * Pre-fills form with task data on open.
 * @param {Object} props - Component props
 * @param {boolean} props.isOpen - Whether the modal is visible
 * @param {function} props.onClose - Function to close the modal
 * @param {function} props.onUpdate - Callback with updated task data
 * @param {Object} props.task - The task object to update
 */
export default function UpdateTaskModal({ isOpen, onClose, onUpdate, task }) {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');

    // Track the last synced task ID to handle prop changes
    const [lastSyncId, setLastSyncId] = useState(null);

    // Sync state from props when task changes (Better than useEffect)
    if (isOpen && task && task.id !== lastSyncId) {
        setTitle(task.title);
        setDescription(task.description || '');
        setLastSyncId(task.id);
    }

    const handleSubmit = (e) => {
        e.preventDefault();
        onUpdate({
            ...task,
            title,
            description,
        });
        onClose();
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Update Task">
            <form onSubmit={handleSubmit} className="update-form">
                <div className="form-group">
                    <label htmlFor="update-title">Title</label>
                    <input
                        type="text"
                        id="update-title"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        required
                        placeholder="Task title..."
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="update-desc">Description</label>
                    <textarea
                        id="update-desc"
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        placeholder="Add details..."
                        rows="4"
                    />
                </div>
                
                <div className="modal-actions">
                    <Button variant="secondary" onClick={onClose} className="btn-cancel">
                        Cancel
                    </Button>
                    <Button type="submit" className="btn-save">
                        Save Changes
                    </Button>
                </div>
            </form>
        </Modal>
    );
}

UpdateTaskModal.propTypes = {
    isOpen: PropTypes.bool.isRequired,
    onClose: PropTypes.func.isRequired,
    onUpdate: PropTypes.func.isRequired,
    task: PropTypes.shape({
        id: PropTypes.number, // Or string depending on normalization, safe to overlap
        title: PropTypes.string,
        description: PropTypes.string,
    }),
};
