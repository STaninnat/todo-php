import React from 'react';
import PropTypes from 'prop-types';
import './TaskSkeleton.css';

/**
 * Single task skeleton item.
 * Renders a placeholder structure mimicking a todo item with checkbox, content lines, and action buttons.
 */
const TaskSkeleton = () => {
    return (
        <div className="task-skeleton">
            <div className="skeleton-checkbox"></div>
            <div className="skeleton-content">
                <div className="skeleton-line title"></div>
                <div className="skeleton-line desc"></div>
            </div>
            <div className="skeleton-actions">
                <div className="skeleton-btn"></div>
                <div className="skeleton-btn"></div>
            </div>
        </div>
    );
};

/**
 * List of task skeletons.
 * Used to display a loading state for the todo list.
 * @param {Object} props - Component props
 * @param {number} props.count - Number of skeleton items to render (default: 3)
 */
export const TaskSkeletonList = ({ count = 3 }) => {
    return (
        <div className="task-skeleton-list">
            {Array.from({ length: count }).map((_, index) => (
                <TaskSkeleton key={index} />
            ))}
        </div>
    );
};

export default TaskSkeletonList;

TaskSkeletonList.propTypes = {
    count: PropTypes.number,
};
