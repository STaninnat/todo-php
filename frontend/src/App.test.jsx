import React from 'react';
import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { vi, test, expect } from 'vitest';
import { App } from './App';

vi.mock('./hooks/useAuth', () => ({
    useAuth: () => ({
        user: null,
        isLoading: false,
    }),
}));

test('renders Todo App header', () => {
  const queryClient = new QueryClient();
  render(
    <QueryClientProvider client={queryClient}>
      <App />
    </QueryClientProvider>
  );
  const headerElement = screen.getByText(/Focus/i);
  expect(headerElement).toBeInTheDocument();
});
