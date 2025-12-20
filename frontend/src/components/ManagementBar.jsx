import React from 'react';
import PropTypes from 'prop-types';
import LoadingSpinner from './LoadingSpinner';
import Button from './Button';
import './ManagementBar.css';

/**
 * Management Bar Component
 * Handles search and bulk action controls (selection mode).
 * 
 * @param {Object} props
 * @param {string} props.searchQuery - Current search text
 * @param {Function} props.setSearchQuery - Setter for search text
 * @param {boolean} props.isSelectionMode - Whether bulk selection mode is active
 * @param {Function} props.toggleSelectionMode - Toggles selection mode
 * @param {number} props.selectedCount - Number of currently selected items
 * @param {Function} props.onBulkDelete - Handler for bulk delete action
 * @param {Function} props.onBulkMarkDone - Handler for bulk mark done action
 * @param {boolean} [props.isLoading=false] - Loading state for buttons
 * @param {boolean} [props.isSearching=false] - Whether a search is currently in progress (shows spinner)
 */
export default function ManagementBar({
    searchQuery,
    setSearchQuery,
    isSelectionMode,
    toggleSelectionMode,
    selectedCount,
    onBulkDelete,
    onBulkMarkDone,
    isLoading,
    isSearching = false
}) {
    if (isSelectionMode) {
        return (
            <div className="management-bar">
                <div className="bulk-actions">
                    <span className="selected-count">{selectedCount} selected</span>
                    
                    {selectedCount > 0 && (
                        <>
                            <Button
                                variant="primary"
                                onClick={() => onBulkMarkDone(true)}
                                isLoading={isLoading}
                                disabled={isLoading}
                            >
                                Mark Done
                            </Button>
                            <Button
                                variant="danger"
                                onClick={onBulkDelete}
                                isLoading={isLoading}
                                disabled={isLoading}
                            >
                                Delete
                            </Button>
                        </>
                    )}
                    
                    <Button variant="secondary" onClick={toggleSelectionMode}>
                        Cancel
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="management-bar">
            <div className="search-container">
                {isSearching ? (
                    <div className="search-spinner">
                        <LoadingSpinner size="small" />
                    </div>
                ) : (
                    <svg 
                        className="search-icon" 
                        width="20" 
                        height="20" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        strokeWidth="2" 
                        strokeLinecap="round" 
                        strokeLinejoin="round"
                    >
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                )}
                <input
                    type="text"
                    className="search-input"
                    placeholder="Search tasks..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                />
            </div>
            <div className="management-actions">
                <Button variant="secondary" onClick={toggleSelectionMode}>
                    Select
                </Button>
            </div>
        </div>
    );
}

ManagementBar.propTypes = {
    searchQuery: PropTypes.string.isRequired,
    setSearchQuery: PropTypes.func.isRequired,
    isSelectionMode: PropTypes.bool.isRequired,
    toggleSelectionMode: PropTypes.func.isRequired,
    selectedCount: PropTypes.number.isRequired,
    onBulkDelete: PropTypes.func.isRequired,
    onBulkMarkDone: PropTypes.func.isRequired,
    isLoading: PropTypes.bool,
    isSearching: PropTypes.bool
};
