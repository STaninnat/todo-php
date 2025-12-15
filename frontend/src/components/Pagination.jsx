import React from 'react';
import PropTypes from 'prop-types';
import './Pagination.css';

/**
 * Pagination control component.
 * Renders Previous/Next buttons and current page info.
 * @param {Object} props - Component props
 * @param {number} props.currentPage - The current active page (1-based)
 * @param {number} props.totalPages - Total number of pages
 * @param {function} props.onPageChange - Callback when a page is selected
 */
export default function Pagination({ currentPage, totalPages, onPageChange }) {
    if (totalPages <= 1) return null;

    const handlePrev = () => {
        if (currentPage > 1) {
            onPageChange(currentPage - 1);
        }
    };

    const handleNext = () => {
        if (currentPage < totalPages) {
            onPageChange(currentPage + 1);
        }
    };

    return (
        <div className="pagination">
            <button 
                className="pagination-btn" 
                onClick={handlePrev} 
                disabled={currentPage === 1}
                aria-label="Previous Page"
            >
                &larr; Prev
            </button>
            <span className="pagination-info">
                Page {currentPage} of {totalPages}
            </span>
            <button 
                className="pagination-btn" 
                onClick={handleNext} 
                disabled={currentPage === totalPages}
                aria-label="Next Page"
            >
                Next &rarr;
            </button>
        </div>
    );
}

Pagination.propTypes = {
    currentPage: PropTypes.number.isRequired,
    totalPages: PropTypes.number.isRequired,
    onPageChange: PropTypes.func.isRequired,
};
