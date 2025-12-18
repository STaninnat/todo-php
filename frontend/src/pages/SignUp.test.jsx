import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import SignUp from './SignUp';
import { api } from '../services/api';
import { MemoryRouter, useNavigate } from 'react-router-dom';

/**
 * @file SignUp.test.jsx
 * @description Integration tests for SignUp page.
 * Verifies registration form, password matching validation, and successful registration flow.
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

describe('SignUp Page', () => {
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

    it('should render registration form', () => {
        render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <SignUp />
                </MemoryRouter>
            </QueryClientProvider>
        );

        expect(screen.getByLabelText(/Username/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Email/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^Password:/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Confirm Password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Sign Up/i })).toBeInTheDocument();
    });

    it('should validate password mismatch', async () => {
        const { container } = render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <SignUp />
                </MemoryRouter>
            </QueryClientProvider>
        );

        fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'test@example.com' } });
        fireEvent.change(screen.getByLabelText(/^Password:/i), { target: { value: 'Pass123!' } });
        fireEvent.change(screen.getByLabelText(/Confirm Password/i), { target: { value: 'Mismatch!' } });
        
        const form = container.querySelector('form');
        fireEvent.submit(form);

        expect(await screen.findByText('Passwords do not match.')).toBeInTheDocument();
        expect(api.register).not.toHaveBeenCalled();
    });

    it('should call api.register and redirect on success', async () => {
        api.register.mockResolvedValue({ user: { id: 1 } });
        
        const { container } = render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <SignUp />
                </MemoryRouter>
            </QueryClientProvider>
        );

        fireEvent.change(screen.getByLabelText(/Username/i), { target: { value: 'newuser' } });
        fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'new@example.com' } });
        fireEvent.change(screen.getByLabelText(/^Password:/i), { target: { value: 'Password123!' } });
        fireEvent.change(screen.getByLabelText(/Confirm Password/i), { target: { value: 'Password123!' } });
        
        const form = container.querySelector('form');
        fireEvent.submit(form);

        await waitFor(() => expect(api.register).toHaveBeenCalledWith({
            username: 'newuser',
            email: 'new@example.com',
            password: 'Password123!',
        }, expect.anything()));
        
        expect(mockNavigate).toHaveBeenCalledWith('/');
    });
});
