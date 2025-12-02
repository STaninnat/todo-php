# todo-php

**NOTICE: This project is currently under active development.**

This repository contains the source code for a PHP-based to-do list application. The primary objective of this project is to provide a robust personal task management system. Please be advised that many features are still in the implementation phase, and significant changes to the codebase are to be expected.

## Project Status

The project is currently divided into two main components with varying levels of completion:

### Backend Development

The backend infrastructure is in an advanced state of development. Key achievements include:

- **Authentication & Security:**

  - Secure user authentication using JWT (JSON Web Tokens).
  - HttpOnly cookie management for token storage.
  - Middleware-based route protection.

- **API Endpoints:**

  - **User Management:**
    - `POST /users/signup`: Register a new user account.
    - `POST /users/signin`: Authenticate and retrieve session tokens.
    - `POST /users/signout`: Terminate the current session.
    - `GET /users/me`: Retrieve current user profile.
    - `PUT /users/update`: Update user profile information.
    - `DELETE /users/delete`: Permanently remove a user account.
  - **Task Management:**
    - `GET /tasks`: Retrieve a list of tasks.
    - `POST /tasks/add`: Create a new task.
    - `PUT /tasks/update`: Modify an existing task.
    - `PUT /tasks/mark_done`: Update task completion status.
    - `DELETE /tasks/delete`: Remove a task.

- **Database Integration:** Seamless connectivity with the MySQL database has been established and verified.

### Frontend Development

The frontend user interface has **not yet been started**.

- There is currently no graphical user interface (GUI) available for this application.
- All interactions with the application must be performed directly through the API or via command-line tools.

## Technology Stack

This project utilizes a modern PHP stack to ensure reliability and maintainability:

- **Language:** PHP (Latest Stable Version)
- **Dependency Manager:** Composer
- **Database:** MySQL (Containerized via Docker)
- **Containerization:** Docker & Docker Compose
- **Testing Framework:** PHPUnit for unit and integration testing
- **Static Analysis:** PHPStan for code quality assurance

## Usage Instructions

Due to the absence of a frontend, current usage is limited to backend operations, testing, and development tasks.

### Prerequisites

Ensure that **Docker** and **Docker Compose** are installed and running on your system before proceeding.

### 1. Environment Initialization

To set up the development environment and start the necessary Docker containers, execute the following command:

```bash
composer up
```

> **Note:** This script orchestrates the creation and configuration of the application's containerized environment.

### 2. Database Migrations

After starting the environment, you must run the database migrations to set up the schema:

```bash
composer phinx:migrate
```

To stop the Docker containers when you're done:

```bash
composer down
```

### 3. Running the Test Suite

To verify the integrity of the codebase and ensure that all implemented features are functioning correctly, run the test suite from the `backend` directory:

```bash
cd backend
composer test
```

> **Note:** This command performs the following operations:
>
> - Executes **unit tests** against the application logic.
> - Executes **integration tests** by automatically starting the test Docker containers, running the tests, and then stopping the containers.
>
> The integration tests handle the Docker environment lifecycle automatically, so you don't need to manually run `composer up:test` beforehand.

You can also run specific test types:

```bash
cd backend
composer test                   # Run all tests (unit + integration + e2e)
composer test:unit              # Run only unit tests
composer test:integration       # Run only integration tests
composer test:integration:fast  # Run integration tests using existing containers
composer test:e2e               # Run end-to-end tests (full stack with real DB)
```

### 4. Code Quality Verification

To maintain high code quality standards, run the static analysis and linting tools from the `backend` directory:

```bash
cd backend
composer check:hard
```

This will perform a rigorous check of the codebase using PHPStan and standard PHP linters, reporting any potential issues or deviations from coding standards.

## Project Structure

The codebase is organized as follows:

- `backend/`: This directory contains all backend-related code and configuration:
  - `backend/src/`: Core application source code, including API controllers, business logic, and database interaction layers.
  - `backend/tests/`: Comprehensive test suite, ensuring coverage for unit, integration, and E2E flows.
  - `backend/db/`: Database migrations (Phinx) and seeds.
  - `backend/vendor/`: Composer dependencies.
  - Backend configuration files: `composer.json`, `phpstan.neon`, `phpunit.*.xml*`, `phinx.php`.
- `frontend/`: This directory is prepared for future frontend development:
  - `frontend/public/`: Frontend assets (currently empty, ready for future use).
- `scripts/`: Utility shell scripts used to automate Docker management, testing procedures, and maintenance tasks.
- Root directory: Docker configuration, environment files, and other infrastructure files.
