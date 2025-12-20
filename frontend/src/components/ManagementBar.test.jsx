/**
 * @file ManagementBar.test.jsx
 * @description Unit tests for ManagementBar component.
 * Verifies rendering of search vs selection modes and action callbacks.
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ManagementBar from './ManagementBar';
import React from 'react';

describe('ManagementBar Component', () => {
    const defaultProps = {
        searchQuery: '',
        setSearchQuery: vi.fn(),
        isSelectionMode: false,
        toggleSelectionMode: vi.fn(),
        selectedCount: 0,
        onBulkDelete: vi.fn(),
        onBulkMarkDone: vi.fn(),
        isLoading: false,
        isSearching: false,
    };

    it('should render search input and select button in default mode', () => {
        render(<ManagementBar {...defaultProps} />);

        expect(screen.getByPlaceholderText('Search tasks...')).toBeInTheDocument();
        expect(screen.getByText('Select')).toBeInTheDocument();
        expect(screen.queryByText('Cancel')).not.toBeInTheDocument();
    });

    it('should call setSearchQuery when typing in search input', () => {
        render(<ManagementBar {...defaultProps} />);

        const input = screen.getByPlaceholderText('Search tasks...');
        fireEvent.change(input, { target: { value: 'test' } });

        expect(defaultProps.setSearchQuery).toHaveBeenCalledWith('test');
    });

    it('should show loading spinner when searching', () => {
        render(<ManagementBar {...defaultProps} isSearching={true} />);
        
        const inputContainer = screen.getByPlaceholderText('Search tasks...').parentElement;
        expect(inputContainer.querySelector('.search-spinner')).toBeInTheDocument();
        expect(inputContainer.querySelector('.search-icon')).not.toBeInTheDocument();
    });

    it('should render bulk actions in selection mode', () => {
        render(<ManagementBar {...defaultProps} isSelectionMode={true} />);

        expect(screen.getByText('0 selected')).toBeInTheDocument();
        expect(screen.getByText('Cancel')).toBeInTheDocument();
        expect(screen.queryByPlaceholderText('Search tasks...')).not.toBeInTheDocument();
    });

    it('should show action buttons when items are selected', () => {
        render(<ManagementBar {...defaultProps} isSelectionMode={true} selectedCount={2} />);

        expect(screen.getByText('2 selected')).toBeInTheDocument();
        expect(screen.getByText('Mark Done')).toBeInTheDocument();
        expect(screen.getByText('Delete')).toBeInTheDocument();
    });

    it('should call action handlers when buttons clicked', () => {
        render(<ManagementBar {...defaultProps} isSelectionMode={true} selectedCount={2} />);

        fireEvent.click(screen.getByText('Mark Done'));
        expect(defaultProps.onBulkMarkDone).toHaveBeenCalledWith(true);

        fireEvent.click(screen.getByText('Delete'));
        expect(defaultProps.onBulkDelete).toHaveBeenCalled();
    });

    it('should disable buttons when loading', () => {
        render(<ManagementBar {...defaultProps} isSelectionMode={true} selectedCount={2} isLoading={true} />);

        const deleteBtn = screen.getByText('Delete').closest('button');
        const markDoneBtn = screen.getByText('Mark Done').closest('button');

        expect(deleteBtn).toBeDisabled();
        expect(markDoneBtn).toBeDisabled();
    });

    it('should toggle selection mode when Cancel/Select clicked', () => {
        // Test "Select" button (Normal Mode)
        const { rerender } = render(<ManagementBar {...defaultProps} />);
        fireEvent.click(screen.getByText('Select'));
        expect(defaultProps.toggleSelectionMode).toHaveBeenCalledTimes(1);

        // Test "Cancel" button (Selection Mode)
        rerender(<ManagementBar {...defaultProps} isSelectionMode={true} />);
        fireEvent.click(screen.getByText('Cancel'));
        expect(defaultProps.toggleSelectionMode).toHaveBeenCalledTimes(2);
    });
});
