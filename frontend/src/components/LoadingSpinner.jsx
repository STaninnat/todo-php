import React from 'react';
import PropTypes from 'prop-types';
import './LoadingSpinner.css';

/**
 * A modern, minimal loading spinner component.
 * @param {Object} props - Component props
 * @param {string} props.size - Size of the spinner: 'small', 'medium', 'large'
 * @param {string} props.className - Additional classes
 */
export default function LoadingSpinner({ size = 'medium', className = '' }) {
    return (
        <div className={`loading-spinner-container ${className}`} data-testid="loading-spinner">
            <div className={`loading-spinner spinner-${size}`} />
        </div>
    );
}

LoadingSpinner.propTypes = {
    size: PropTypes.oneOf(['small', 'medium', 'large']),
    className: PropTypes.string,
};
