/**
 * @file validation.test.js
 * @description Unit tests for validation utility functions.
 * Covers email format, password complexity, and password confirmation logic.
 */
import { describe, it, expect } from 'vitest';
import { validateEmail, validatePassword, validateConfirmPassword } from './validation';

describe('Validation Utils', () => {
    describe('validateEmail', () => {
        it('should return true for valid emails', () => {
            expect(validateEmail('test@example.com')).toBe(true);
            expect(validateEmail('user.name@domain.co.uk')).toBe(true);
            expect(validateEmail('user+tag@example.org')).toBe(true);
        });

        it('should return false for invalid emails', () => {
            expect(validateEmail('invalid-email')).toBe(false);
            expect(validateEmail('user@')).toBe(false);
            expect(validateEmail('@domain.com')).toBe(false);
            expect(validateEmail('user@domain')).toBe(false);
            expect(validateEmail('')).toBe(false);
        });
    });

    describe('validatePassword', () => {
        it('should return true for valid passwords', () => {
            // Min 8 chars, at least 1 uppercase, 1 lowercase, 1 number, 1 special char
            expect(validatePassword('Password123!')).toBe(true);
            expect(validatePassword('StrongP@ssw0rd')).toBe(true);
        });

        it('should return false for passwords that are too short', () => {
            expect(validatePassword('Pass1!')).toBe(false);
        });

        it('should return false for passwords missing required characters', () => {
            expect(validatePassword('password123!')).toBe(false); // Missing uppercase
            expect(validatePassword('PASSWORD123!')).toBe(false); // Missing lowercase
            expect(validatePassword('Password!')).toBe(false);    // Missing number
            expect(validatePassword('Password123')).toBe(false);  // Missing special char
        });
    });

    describe('validateConfirmPassword', () => {
        it('should return true when passwords match and are long enough', () => {
            expect(validateConfirmPassword('Password123!', 'Password123!')).toBe(true);
        });

        it('should return false when passwords do not match', () => {
            expect(validateConfirmPassword('Password123!', 'Password124!')).toBe(false);
        });

        it('should return false when confirm password is too short', () => {
            // Even if they match, if it's too short commonly existing logic might flag it, 
            // though the function explicitly checks confirmPassword.length >= 8
            expect(validateConfirmPassword('Short1!', 'Short1!')).toBe(false);
        });
    });
});
