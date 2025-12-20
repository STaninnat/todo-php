/**
 * @file FilterSidebar.test.jsx
 * @description Unit tests for FilterSidebar component.
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import FilterSidebar from './FilterSidebar';
import React from 'react';

describe('FilterSidebar Component', () => {
    const defaultProps = {
        currentFilter: 'all',
        onFilterChange: vi.fn(),
    };

    it('renders all filter options', () => {
        render(<FilterSidebar {...defaultProps} />);
        expect(screen.getByText('All Tasks')).toBeInTheDocument();
        expect(screen.getByText('Active')).toBeInTheDocument();
        expect(screen.getByText('Completed')).toBeInTheDocument();
    });

    it('highlights the current filter', () => {
        render(<FilterSidebar {...defaultProps} currentFilter="active" />);
        // The active button should have the 'active' class.
        // We can find by role 'button' with name 'Active'
        const activeButton = screen.getByRole('button', { name: /active/i });
        expect(activeButton).toHaveClass('active');
        
        const allButton = screen.getByRole('button', { name: /all tasks/i });
        expect(allButton).not.toHaveClass('active');
    });

    it('calls onFilterChange when a filter is clicked', () => {
        const onFilterChange = vi.fn();
        render(<FilterSidebar {...defaultProps} onFilterChange={onFilterChange} />);
        
        fireEvent.click(screen.getByText('Completed'));
        expect(onFilterChange).toHaveBeenCalledWith('completed');
    });

    it('toggles sidebar expansion', () => {
        render(<FilterSidebar {...defaultProps} />);
        const sidebar = screen.getByRole('complementary'); // aside element
        const toggleBtn = screen.getByLabelText(/expand sidebar/i);

        // Initial state (collapsed)
        expect(sidebar).toHaveClass('collapsed');
        expect(sidebar).not.toHaveClass('expanded');
        
        // Expand
        fireEvent.click(toggleBtn);
        expect(sidebar).toHaveClass('expanded');
        expect(sidebar).not.toHaveClass('collapsed');
        expect(screen.getByLabelText(/collapse sidebar/i)).toBeInTheDocument();

        // Collapse
        fireEvent.click(toggleBtn);
        expect(sidebar).toHaveClass('collapsed');
        expect(sidebar).not.toHaveClass('expanded');
    });
});
