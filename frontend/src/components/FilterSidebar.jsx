import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { Filter, CheckCircle2, Circle, List, ChevronLeft } from 'lucide-react';
import Button from './Button';
import './FilterSidebar.css';

/**
 * FilterSidebar Component
 * 
 * A sticky, collapsible sidebar for filtering tasks.
 * 
 * @param {string} currentFilter - 'all', 'active', 'completed'
 * @param {function} onFilterChange - Callback when filter changes
 */
export default function FilterSidebar({ currentFilter, onFilterChange }) {
    const [isExpanded, setIsExpanded] = useState(false);

    const toggleSidebar = () => {
        setIsExpanded(!isExpanded);
    };

    const filters = [
        { id: 'all', label: 'All Tasks', icon: List },
        { id: 'active', label: 'Active', icon: Circle },
        { id: 'completed', label: 'Completed', icon: CheckCircle2 },
    ];

    return (
        <aside className={`filter-sidebar ${isExpanded ? 'expanded' : 'collapsed'}`}>
            <Button 
                variant="icon"
                className="toggle-btn" 
                onClick={toggleSidebar}
                aria-label={isExpanded ? "Collapse sidebar" : "Expand sidebar"}
            >
                {isExpanded ? <ChevronLeft size={20} /> : <Filter size={20} />}
            </Button>

            <div className="filter-list">
                {filters.map((f) => {
                    const Icon = f.icon;
                    const isActive = currentFilter === f.id;
                    return (
                        <Button
                            key={f.id}
                            variant="text"
                            className={`filter-item ${isActive ? 'active' : ''}`}
                            onClick={() => onFilterChange(f.id)}
                            title={!isExpanded ? f.label : ''}
                        >
                            <Icon size={20} strokeWidth={isActive ? 2.5 : 2} />
                            <span className="filter-label">{f.label}</span>
                        </Button>
                    );
                })}
            </div>
        </aside>
    );
}

FilterSidebar.propTypes = {
    currentFilter: PropTypes.string.isRequired,
    onFilterChange: PropTypes.func.isRequired,
};
