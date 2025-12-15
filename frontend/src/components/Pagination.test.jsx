import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import Pagination from './Pagination';

/**
 * @file Pagination.test.jsx
 * @description Unit tests for Pagination component.
 * Verifies navigation button states (disabled/enabled) and page change callbacks.
 */

describe('Pagination Component', () => {
    test('renders pagination buttons', () => {
        render(<Pagination currentPage={1} totalPages={2} onPageChange={() => {}} />);
        expect(screen.getByText(/Prev/)).toBeInTheDocument();
        expect(screen.getByText(/Next/)).toBeInTheDocument();
        expect(screen.getByText('Page 1 of 2')).toBeInTheDocument();
    });

    test('disables Prev button on first page', () => {
        render(<Pagination currentPage={1} totalPages={2} onPageChange={() => {}} />);
        expect(screen.getByText(/Prev/)).toBeDisabled();
    });

    test('disables Next button on last page', () => {
        render(<Pagination currentPage={2} totalPages={2} onPageChange={() => {}} />);
        expect(screen.getByText(/Next/)).toBeDisabled();
    });

    test('calls onPageChange when buttons clicked', () => {
        const handleChange = vi.fn();
        render(<Pagination currentPage={2} totalPages={3} onPageChange={handleChange} />);
        
        fireEvent.click(screen.getByText(/Prev/));
        expect(handleChange).toHaveBeenCalledWith(1);
        
        fireEvent.click(screen.getByText(/Next/));
        expect(handleChange).toHaveBeenCalledWith(3);
    });

    test('does not render if totalPages is 1', () => {
        const { container } = render(<Pagination currentPage={1} totalPages={1} onPageChange={() => {}} />);
        expect(container).toBeEmptyDOMElement();
    });
});
