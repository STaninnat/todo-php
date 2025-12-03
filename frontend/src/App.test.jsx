import React from 'react';
import { render, screen } from '@testing-library/react';
import { App } from './App';

test('renders Todo App header', () => {
  render(<App />);
  const headerElement = screen.getByText(/Todo App/i);
  expect(headerElement).toBeInTheDocument();
});
