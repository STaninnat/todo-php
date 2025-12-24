
import React from 'react';
/**
 * @file useTodosProxy.test.jsx
 * @description Specific tests for useTodos hook handling proxy errors (500/503).
 * Verifies graceful fallback to Guest Mode when backend is down.
 */
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useTodos } from './useTodos';
import { api } from '../services/api';
import { vi, describe, test, expect, beforeEach } from 'vitest';

// Mock the API module
vi.mock('../services/api', () => ({
    api: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}));

import PropTypes from 'prop-types';

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });

  const TestWrapper = ({ children }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );

  TestWrapper.propTypes = {
    children: PropTypes.node.isRequired,
  };

  return TestWrapper;
};

describe('useTodos Proxy Error Handling', () => {
    let store = {};

    beforeEach(() => {
        vi.clearAllMocks();
        store = {};

        vi.spyOn(Storage.prototype, 'getItem').mockImplementation((key) => store[key] || null);
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation((key, value) => {
            store[key] = value.toString();
        });
        vi.spyOn(Storage.prototype, 'clear').mockImplementation(() => {
            store = {};
        });
    });

    test('falls back to Guest Mode on 500 Proxy Error', async () => {
        store['auth_status'] = 'logged_in';
        // Mock a 500 error effectively
        const proxyError = new Error("Proxy Error");
        proxyError.status = 500;
        api.get.mockRejectedValue(proxyError);

        const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });

        // Should settle in guest mode (isGuest derived from queryData)
        // With isGuest derived from queryData which defaults to true on error catch return
        await waitFor(() => {
             // In my new logic, queryFn catches the error and returns { isGuest: true, ... }
             // So result.current.isGuest should be true.
             expect(result.current.isGuest).toBe(true);
        });
        
        expect(result.current.todos).toEqual([]);
    });

     test('falls back to Guest Mode on 503 Service Unavailable', async () => {
        store['auth_status'] = 'logged_in';
        const proxyError = new Error("Service Unavailable");
        proxyError.status = 503;
        api.get.mockRejectedValue(proxyError);

        const { result } = renderHook(() => useTodos(), { wrapper: createWrapper() });

        await waitFor(() => expect(result.current.isGuest).toBe(true));
    });
});
