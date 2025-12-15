import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
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

    it('should render sign in form', () => {
        render(
            <MemoryRouter>
                <SignIn />
            </MemoryRouter>
        );

        expect(screen.getByLabelText(/Email/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Sign In/i })).toBeInTheDocument();
    });

    it('should validate email format locally', async () => {
        const { container } = render(
            <MemoryRouter>
                <SignIn />
            </MemoryRouter>
        );

        const emailInput = screen.getByLabelText(/Email/i);
        // We use direct form submission to avoid potential button click issues in test env
        const form = container.querySelector('form');

        fireEvent.change(emailInput, { target: { value: 'invalid-email' } });
        fireEvent.submit(form);

        expect(await screen.findByText('Please enter a valid email address.')).toBeInTheDocument();
        expect(api.login).not.toHaveBeenCalled();
    });

    it('should call api.login and redirect on success', async () => {
        api.login.mockResolvedValue({ user: { id: 1 } });
        
        const { container } = render(
            <MemoryRouter>
                <SignIn />
            </MemoryRouter>
        );

        fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'test@example.com' } });
        fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'Password123!' } });
        
        const form = container.querySelector('form');
        fireEvent.submit(form);

        await waitFor(() => expect(api.login).toHaveBeenCalledWith({
            email: 'test@example.com',
            password: 'Password123!',
        }));
        
        expect(mockNavigate).toHaveBeenCalledWith('/');
    });

    it('should display error message on API failure', async () => {
        const error = new Error('Invalid credentials');
        error.status = 401;
        api.login.mockRejectedValue(error);

        const { container } = render(
            <MemoryRouter>
                <SignIn />
            </MemoryRouter>
        );

        fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'test@example.com' } });
        fireEvent.change(screen.getByLabelText(/Password/i), { target: { value: 'WrongPass' } });
        
        const form = container.querySelector('form');
        fireEvent.submit(form);

        expect(await screen.findByText('Invalid email or password.')).toBeInTheDocument();
    });
});
