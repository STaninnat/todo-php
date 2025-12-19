import React from 'react';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { vi, describe, it, expect, beforeEach } from 'vitest';

/**
 * Integration tests for AppRoutes component.
 * 
 * Covers routing scenarios for:
 * - Loading state
 * - Guest users (accessing public routes, redirections)
 * - Authenticated users (accessing private routes, redirections, URL username mismatches)
 */
import AppRoutes from './AppRoutes';
import { useAuth } from '../hooks/useAuth';

// Mock dependencies
vi.mock('../hooks/useAuth');
vi.mock('../pages/SignUp', () => ({ default: () => <div>SignUp Page</div> }));
vi.mock('../pages/SignIn', () => ({ default: () => <div>SignIn Page</div> }));
vi.mock('../pages/TodoPage', () => ({ default: () => <div>Todo Page</div> }));
vi.mock('./Header', () => ({ default: () => <div>Header</div> }));

describe('AppRoutes Component', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should show loading screen when loading', () => {
        useAuth.mockReturnValue({ user: null, isLoading: true });
        
        render(
            <MemoryRouter>
                <AppRoutes />
            </MemoryRouter>
        );

        expect(screen.getByText('Loading...')).toBeInTheDocument();
    });

    describe('Guest User', () => {
        beforeEach(() => {
            useAuth.mockReturnValue({ user: null, isLoading: false });
        });

        it('should render SignIn page on /signin', () => {
            render(
                <MemoryRouter initialEntries={['/signin']}>
                    <AppRoutes />
                </MemoryRouter>
            );
            expect(screen.getByText('SignIn Page')).toBeInTheDocument();
        });

        it('should render SignUp page on /signup', () => {
             render(
                <MemoryRouter initialEntries={['/signup']}>
                    <AppRoutes />
                </MemoryRouter>
            );
            expect(screen.getByText('SignUp Page')).toBeInTheDocument();
        });

        it('should redirect /:username to / (home -> TodoPage default view for guest)', () => {
             // Logic in AppRoutes:
             // Route /:username -> element={user ? ... : <Navigate to="/" />}
             // Route / -> element={user ? ... : <TodoPage />}
             // So guest on /user1 -> redirect to / -> render TodoPage (Guest Mode)

             render(
                <MemoryRouter initialEntries={['/someuser']}>
                    <AppRoutes />
                </MemoryRouter>
            );
            expect(screen.getByText('Todo Page')).toBeInTheDocument();
        });
    });

    describe('Authenticated User', () => {
        const mockUser = { username: 'testuser' };

        beforeEach(() => {
            useAuth.mockReturnValue({ user: mockUser, isLoading: false });
        });

        it('should render TodoPage on correct /:username', () => {
             render(
                <MemoryRouter initialEntries={['/testuser']}>
                    <AppRoutes />
                </MemoryRouter>
            );
            expect(screen.getByText('Todo Page')).toBeInTheDocument();
        });

        it('should redirect /signin to /:username', () => {
            render(
                <MemoryRouter initialEntries={['/signin']}>
                    <Routes>
                         <Route path="*" element={<AppRoutes />} />
                    </Routes>
                </MemoryRouter>
            );
            // Should redirect to /testuser which renders Todo Page
            expect(screen.getByText('Todo Page')).toBeInTheDocument();
        });

        it('should redirect / to /:username', () => {
             render(
                <MemoryRouter initialEntries={['/']}>
                    <AppRoutes />
                </MemoryRouter>
            );
            expect(screen.getByText('Todo Page')).toBeInTheDocument();
        });

        it('should redirect to correct username if visiting wrong username route', () => {
            // Visiting /otheruser should redirect to /testuser
             render(
                <MemoryRouter initialEntries={['/otheruser']}>
                    <AppRoutes />
                </MemoryRouter>
            );
            expect(screen.getByText('Todo Page')).toBeInTheDocument();
        });
    });
});
