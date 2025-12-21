import React from 'react';
import { render } from '@testing-library/react';
import { describe, it, expect } from 'vitest';

/**
 * @file TaskSkeleton.test.jsx
 * @description Unit tests for TaskSkeletonList component.
 * Verifies rendering without crashing, correct default item count, and prop overrides.
 */
import TaskSkeletonList from './TaskSkeleton';

describe('TaskSkeletonList', () => {
    it('renders without crashing', () => {
        const { container } = render(<TaskSkeletonList />);
        expect(container.firstChild).toHaveClass('task-skeleton-list');
    });

    it('renders correct number of skeleton items (default 3)', () => {
        const { container } = render(<TaskSkeletonList />);
        const items = container.querySelectorAll('.task-skeleton');
        expect(items).toHaveLength(3);
    });

    it('renders correct number of skeleton items when count prop is provided', () => {
        const { container } = render(<TaskSkeletonList count={5} />);
        const items = container.querySelectorAll('.task-skeleton');
        expect(items).toHaveLength(5);
    });
});
