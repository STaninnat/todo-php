import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import SignIn from './SignIn';
import { api } from '../services/api';
import { MemoryRouter, useNavigate } from 'react-router-dom';

/**
 * @file SignIn.test.jsx
 * @description Integration tests for SignIn page.
 * Verifies form rendering, validation, API login calls, and redirection.
 */

// Mock dependencies
vi.mock('../services/api');
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: vi.fn(),
    };
});

describe('SignIn Page', () => {
    const mockNavigate = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(useNavigate).mockReturnValue(mockNavigate);
    });

    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                 retry: false,
            },
        },
    });

    it('should render sign in form', () => {
        render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <SignIn />
                </MemoryRouter>
            </QueryClientProvider>
        );

        expect(screen.getByLabelText(/Username/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Sign In/i })).toBeInTheDocument();
    });

    it('should validate required username', async () => {
        const { container } = render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <SignIn />
                </MemoryRouter>
            </QueryClientProvider>
        );

        const usernameInput = screen.getByLabelText(/Username/i);
        // We use direct form submission to avoid potential button click issues in test env
        const form = container.querySelector('form');

        fireEvent.change(usernameInput, { target: { value: '   ' } }); // Empty or whitespace
        fireEvent.submit(form);

        expect(await screen.findByText('Please enter your username.')).toBeInTheDocument();
        expect(api.login).not.toHaveBeenCalled();
    });

    it('should call api.login and redirect on success', async () => {
        api.login.mockResolvedValue({ user: { id: 1, username: 'testuser' } });
        
        const { container } = render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <SignIn />
                </MemoryRouter>
            </QueryClientProvider>
        );

        fireEvent.change(screen.getByLabelText(/Username/i), { target: { value: 'testuser' } });
        fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'Password123!' } });
        
        const form = container.querySelector('form');
        fireEvent.submit(form);

        await waitFor(() => expect(api.login).toHaveBeenCalledWith({
            username: 'testuser',
            password: 'Password123!',
        }, expect.anything()));
        
        expect(mockNavigate).toHaveBeenCalledWith('/testuser');
    });

    it('should display error message on API failure', async () => {
        const error = new Error('Invalid credentials');
        error.status = 401;
        api.login.mockRejectedValue(error);

        const { container } = render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <SignIn />
                </MemoryRouter>
            </QueryClientProvider>
        );

        fireEvent.change(screen.getByLabelText(/Username/i), { target: { value: 'testuser' } });
        fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'WrongPass' } });
        
        const form = container.querySelector('form');
        fireEvent.submit(form);

        expect(await screen.findByText('Invalid username or password.')).toBeInTheDocument();
    });
});
