import React from 'react';
import { render, screen } from '@testing-library/react';
import { App } from './App';

test('renders Bun + React header', () => {
  render(<App />);
  const headerElement = screen.getByText(/Bun \+ React/i);
  expect(headerElement).toBeInTheDocument();
});
