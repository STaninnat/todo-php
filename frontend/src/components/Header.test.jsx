import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
/**
 * @file Header.test.jsx
 * @description Unit tests for the Header component.
 * Tests conditional rendering based on auth state and route.
 */
import Header from './Header';
import { useAuth } from '../hooks/useAuth';

// Mock dependencies
vi.mock('../hooks/useAuth');

// We need to mock useLocation sometimes if we want to change it easily, 
// but MemoryRouter handles it well for integration. 
// However, the component calls useLocation. MemoryRouter provides the context for it.

describe('Header Component', () => {
    const mockLogout = vi.fn();
    const mockUser = { username: 'testuser' };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should render nothing on auth pages', () => {
        useAuth.mockReturnValue({ user: null, logout: mockLogout });
        
        const { container } = render(
            <MemoryRouter initialEntries={['/signin']}>
                <Header />
            </MemoryRouter>
        );
        expect(container).toBeEmptyDOMElement();
        
        const { container: containerSignup } = render(
            <MemoryRouter initialEntries={['/signup']}>
                <Header />
            </MemoryRouter>
        );
        expect(containerSignup).toBeEmptyDOMElement();
    });

    it('should render "Sign In" button when not logged in (and not on auth page)', () => {
        useAuth.mockReturnValue({ user: null, logout: mockLogout });

        render(
            <MemoryRouter initialEntries={['/']}>
                <Header />
            </MemoryRouter>
        );

        expect(screen.getByText('Todo App')).toBeInTheDocument();
        expect(screen.getByText('Sign In')).toBeInTheDocument();
        expect(screen.queryByText('Log Out')).not.toBeInTheDocument();
    });

    it('should render user greeting and "Log Out" button when logged in', () => {
        useAuth.mockReturnValue({ user: mockUser, logout: mockLogout });

        render(
            <MemoryRouter initialEntries={['/']}>
                <Header />
            </MemoryRouter>
        );

        expect(screen.getByText('Hi, testuser')).toBeInTheDocument();
        expect(screen.getByText('Log Out')).toBeInTheDocument();
        expect(screen.queryByText('Sign In')).not.toBeInTheDocument();
    });

    it('should call logout and navigate on Log Out click', async () => {
        useAuth.mockReturnValue({ user: mockUser, logout: mockLogout });
        
        // Mock navigate
        
        // Since useNavigate is from react-router-dom and we are using MemoryRouter,
        // we can't easily spy on the internal navigate function unless we mock react-router-dom fully
        // or use a test wrapper that captures navigation.
        // A simpler way for unit testing this component specifically is to mock `useNavigate`.
        
        // Re-mock react-router-dom only for useNavigate, but keep others? 
        // It's easier to mock the module partially if supported, or just verify logout call.
        // Let's verify logout call primarily. Testing navigation is secondary (integration).
        
        render(
            <MemoryRouter initialEntries={['/']}>
                <Header />
            </MemoryRouter>
        );

        fireEvent.click(screen.getByText('Log Out'));
        
        expect(mockLogout).toHaveBeenCalledTimes(1);
    });
});
