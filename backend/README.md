# Cabal Online Backend

PHP backend for Cabal Online game with user registration, login, and dashboard.

## Features
- User registration with validation
- Secure login with password hashing
- Protected dashboard routes
- MySQL database integration
- Prepared statements for SQL injection prevention

## Setup Instructions

### 1. Requirements
- PHP 7.4+
- MySQL 5.7+
- Composer (optional, for dependencies)

### 2. Database Setup
Create the database and user:
```sql
CREATE DATABASE cabal_online;
CREATE USER 'cabal_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON cabal_online.* TO 'cabal_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Import Schema
Run the SQL schema:
```bash
mysql -u cabal_user -p cabal_online < database/schema.sql
```

### 4. Configuration
Copy `.env.example` to `.env` and update:
```
DB_HOST=localhost
DB_NAME=cabal_online
DB_USER=cabal_user
DB_PASS=your_secure_password
```

### 5. Run the Server
```bash
php -S localhost:8000 -t public
```

## API Endpoints

### POST /api/register
Register a new user
```json
{
  "username": "string",
  "password": "string",
  "email": "string",
  "full_name": "string",
  "validation_key": "string"
}
```

### POST /api/login
Login user
```json
{
  "username": "string",
  "password": "string"
}
```
Returns JWT token

### GET /api/dashboard
Get dashboard data (requires auth)
Returns user info and game data

## Security Features
- Password hashing with bcrypt
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- JWT-based authentication
- CORS protection