import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { MemoryRouter } from 'react-router-dom';

/**
 * @file Footer.test.jsx
 * @description Unit tests for Footer component.
 * Verifies rendering of copyright, links, setup of social icons, and conditional visibility.
 */
import Footer from './Footer';

describe('Footer Component', () => {
    it('should render copyright text', () => {
        render(
            <MemoryRouter>
                <Footer />
            </MemoryRouter>
        );
        const currentYear = new Date().getFullYear();
        expect(screen.getByText(new RegExp(`© ${currentYear} Todo App. All rights reserved.`, 'i'))).toBeInTheDocument();
    });

    it('should render privacy and terms links', () => {
        render(
            <MemoryRouter>
                <Footer />
            </MemoryRouter>
        );
        expect(screen.getByText('Privacy Policy')).toBeInTheDocument();
        expect(screen.getByText('Terms of Service')).toBeInTheDocument();
    });

    it('should render social icons with correct attributes', () => {
        render(
            <MemoryRouter>
                <Footer />
            </MemoryRouter>
        );
        
        const githubLink = screen.getByLabelText('Github').closest('a');
        expect(githubLink).toHaveAttribute('href', 'https://github.com/STaninnat');
        expect(githubLink).toHaveAttribute('target', '_blank');
        expect(githubLink).toHaveAttribute('rel', 'noopener noreferrer');

        const twitterLink = screen.getByLabelText('Twitter').closest('a');
        expect(twitterLink).toHaveAttribute('target', '_blank');
        expect(twitterLink).toHaveAttribute('rel', 'noopener noreferrer');
    });

    it('should NOT render on /signin page', () => {
        const { container } = render(
            <MemoryRouter initialEntries={['/signin']}>
                <Footer />
            </MemoryRouter>
        );
        expect(container.firstChild).toBeNull();
    });

    it('should NOT render on /signup page', () => {
        const { container } = render(
            <MemoryRouter initialEntries={['/signup']}>
                <Footer />
            </MemoryRouter>
        );
        expect(container.firstChild).toBeNull();
    });

    it('should render on other pages (e.g. /)', () => {
        const { container } = render(
            <MemoryRouter initialEntries={['/']}>
                <Footer />
            </MemoryRouter>
        );
        expect(container.firstChild).not.toBeNull();
        expect(container.querySelector('.site-footer')).toBeInTheDocument();
    });
});
