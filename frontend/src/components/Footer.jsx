import React from 'react';
import { useLocation } from 'react-router-dom';
import { Github, Twitter, Linkedin, Instagram } from 'lucide-react';
import './Footer.css';

/**
 * Application Footer Component.
 * Displays footer navigation links, social media icons, and copyright information.
 * Automatically hides itself on authentication pages (Sign In/Sign Up).
 */
export default function Footer() {
    const location = useLocation();
    const currentYear = new Date().getFullYear();

    // Hide footer on auth pages to match Header behavior
    const isAuthPage = ['/signin', '/signup'].includes(location.pathname);
    if (isAuthPage) return null;

    return (
        <footer className="site-footer">
            <div className="footer-top">
                <div className="footer-links">
                    <a href="#" className="footer-link">Privacy Policy</a>
                    <a href="#" className="footer-link">Terms of Service</a>
                </div>

                <div className="footer-socials">
                    <a 
                        href="https://github.com/STaninnat" 
                        aria-label="Github"
                        target="_blank" 
                        rel="noopener noreferrer"
                    >
                        <Github size={20} />
                    </a>
                    <a 
                        href="#" 
                        aria-label="Twitter"
                        target="_blank" 
                        rel="noopener noreferrer"
                    >
                        <Twitter size={20} />
                    </a>
                    <a 
                        href="#" 
                        aria-label="Instagram"
                        target="_blank" 
                        rel="noopener noreferrer"
                    >
                        <Instagram size={20} />
                    </a>
                    <a 
                        href="#" 
                        aria-label="LinkedIn"
                        target="_blank" 
                        rel="noopener noreferrer"
                    >
                        <Linkedin size={20} />
                    </a>
                </div>
            </div>
            
            <div className="footer-bottom">
                 <p className="footer-copyright">
                    &copy; {currentYear} Todo App. All rights reserved.
                </p>
            </div>
        </footer>
    );
}
