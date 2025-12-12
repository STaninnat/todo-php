/**
 * Validates an email address using a regular expression.
 * @param {string} email - The email address to validate.
 * @returns {boolean} True if the email is valid, false otherwise.
 */
export const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

/**
 * Validates a password against complexity requirements:
 * - At least 8 characters
 * - At least one lowercase letter
 * - At least one uppercase letter
 * - At least one number
 * - At least one special character (@$!%*?&)
 * @param {string} password - The password to validate.
 * @returns {boolean} True if the password meets all criteria.
 */
export const validatePassword = (password) => {
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return passwordRegex.test(password);
};

/**
 * Validates that the confirm password matches the password and meets length requirement.
 * @param {string} password - The original password.
 * @param {string} confirmPassword - The password confirmation.
 * @returns {boolean} True if passwords match and confirmPassword is at least 8 chars.
 */
export const validateConfirmPassword = (password, confirmPassword) => {
    return password === confirmPassword && confirmPassword.length >= 8;
};