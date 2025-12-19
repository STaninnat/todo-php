import React from 'react';
import PropTypes from 'prop-types';
import LoadingSpinner from './LoadingSpinner';
import './Button.css';

/**
 * Reusable Button component with built-in loading state.
 * @param {Object} props - Component props
 * @param {boolean} props.isLoading - Whether the button is in a loading state
 * @param {string} props.variant - Visual style: 'primary', 'secondary', 'danger'
 * @param {React.ReactNode} props.children - Button content
 */
export default function Button({ 
    isLoading = false, 
    variant = 'primary', 
    className = '', 
    children, 
    disabled, 
    ...props 
}) {
    return (
        <button
            className={`btn btn-${variant} ${className} ${isLoading ? 'btn-loading' : ''}`}
            disabled={disabled || isLoading}
            {...props}
        >
            {isLoading && (
                <span className="btn-spinner-wrapper">
                    <LoadingSpinner size="small" />
                </span>
            )}
            <span className={`btn-content ${isLoading ? 'invisible' : ''}`}>
                {children}
            </span>
        </button>
    );
}

Button.propTypes = {
    isLoading: PropTypes.bool,
    variant: PropTypes.oneOf(['primary', 'secondary', 'danger', 'text', 'icon']),
    children: PropTypes.node.isRequired,
    className: PropTypes.string,
    disabled: PropTypes.bool,
};
