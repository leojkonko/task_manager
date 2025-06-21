# Task API Documentation for Postman

## Base URL
```
http://localhost:8080
```

## Authentication

### Important Notes
- All task endpoints require authentication
- Authentication uses session-based cookies
- Include session cookie in all API requests after login
- CSRF tokens may be required for certain operations

## Authentication Endpoints

### 1. User Login
- **URL**: `POST /auth/login`
- **Content-Type**: `application/json` or `application/x-www-form-urlencoded`
- **Headers**: `X-Requested-With: XMLHttpRequest` (for JSON response)

**JSON Body**:
```json
{
    "username": "testuser",
    "password": "password123"
}
```

**Form-data Body** (alternative):
- `username`: testuser
- `password`: password123

**Success Response (JSON)**:
```json
{
    "success": true,
    "message": "Login successful",
    "redirect": "/task-manager"
}
```

**Error Response (JSON)**:
```json
{
    "success": false,
    "message": "Invalid username or password."
}
```

### 2. User Registration
- **URL**: `POST /auth/register`
- **Content-Type**: `application/json` or `application/x-www-form-urlencoded`
- **Headers**: `X-Requested-With: XMLHttpRequest` (for JSON response)

**JSON Body**:
```json
{
    "username": "newuser",
    "email": "user@example.com",
    "password": "securepassword123",
    "confirm_password": "securepassword123",
    "full_name": "John Doe"
}
```

**Form-data Body** (alternative):
- `username`: newuser
- `email`: user@example.com
- `password`: securepassword123
- `confirm_password`: securepassword123
- `full_name`: John Doe

**Success Response (JSON)**:
```json
{
    "success": true,
    "message": "Registration successful",
    "redirect": "/auth/login"
}
```

**Error Response (JSON)**:
```json
{
    "success": false,
    "message": "Username or email already exists. Please try with different details."
}
```

### 3. User Logout
- **URL**: `POST /auth/logout`
- **Requires**: Valid session cookie

**Response**: Redirects to login page or returns success message

### 4. Authentication Status Check
To verify if user is authenticated, make a request to any protected endpoint:
- **URL**: `GET /api/tasks/list`
- **Authenticated**: Returns task data
- **Not Authenticated**: Returns 401 or redirects to login

## Session Management

### Authentication Headers for API Calls
After successful login, include the session cookie in subsequent requests:

```http
Cookie: auth_session=your_session_id_here
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

### Session Expiration
- Sessions expire after 24 hours
- Sessions are automatically renewed on activity
- Failed login attempts (5+) lock account for 30 minutes

## Available Endpoints

### 1. List Tasks
- **URL**: `GET /api/tasks/list`
- **Query Parameters** (optional):
  - `page`: Page number (default: 1)
  - `limit`: Items per page (default: 10)
  - `status`: Filter by status (`pending`, `in_progress`, `completed`, `cancelled`)
  - `priority`: Filter by priority (`low`, `medium`, `high`, `urgent`)
  - `search`: Search in title

**Example**:
```
GET http://localhost:8080/api/tasks/list?page=1&limit=5&status=pending
```

### 2. Get Specific Task
- **URL**: `GET /api/tasks/{id}`

**Example**:
```
GET http://localhost:8080/api/tasks/1
```

### 3. Create New Task
- **URL**: `POST /api/tasks/create`
- **Content-Type**: `application/json` or `application/x-www-form-urlencoded`

**JSON Body (recommended)**:
```json
{
    "title": "My new task",
    "description": "Detailed task description",
    "status": "pending",
    "priority": "medium",
    "due_date": "2025-06-30T14:30:00"
}
```

**Form-data Body** (alternative):
- `title`: My new task
- `description`: Detailed task description
- `status`: pending
- `priority`: medium
- `due_date`: 2025-06-30T14:30:00

### 4. Update Task
- **URL**: `PUT /api/tasks/update/{id}` or `POST /api/tasks/update/{id}`
- **Content-Type**: `application/json` or `application/x-www-form-urlencoded`

**Example**:
```
PUT http://localhost:8080/api/tasks/update/1
```

**JSON Body**:
```json
{
    "title": "Updated title",
    "description": "New description",
    "status": "in_progress",
    "priority": "high"
}
```

### 5. Delete Task
- **URL**: `DELETE /api/tasks/delete/{id}` or `POST /api/tasks/delete/{id}`

**Example**:
```
DELETE http://localhost:8080/api/tasks/delete/1
```

### 6. Mark Task as Completed
- **URL**: `POST /api/tasks/complete/{id}`

**Example**:
```
POST http://localhost:8080/api/tasks/complete/1
```

### 7. Mark Task as In Progress
- **URL**: `POST /api/tasks/start/{id}`

**Example**:
```
POST http://localhost:8080/api/tasks/start/1
```

### 8. Duplicate Task
- **URL**: `POST /api/tasks/duplicate/{id}`

**Example**:
```
POST http://localhost:8080/api/tasks/duplicate/1
```

### 9. Get Statistics
- **URL**: `GET /api/tasks/statistics`

**Example**:
```
GET http://localhost:8080/api/tasks/statistics
```

## Applied Validations

### Business Rules
- ✅ **Creation only on weekdays**: Tasks can only be created Monday to Friday
- ✅ **Update only with "pending" status**: Tasks can only be updated if status is "pending"
- ✅ **Deletion only with "pending" status**: Tasks can only be deleted if status is "pending"
- ✅ **Deletion only after 5 days**: Tasks can only be deleted if created more than 5 days ago
- ✅ **Due date**: Cannot be in the past (only on creation)

### Title
- ✅ **Required** (only on creation)
- ✅ **Minimum**: 3 characters
- ✅ **Maximum**: 200 characters
- ✅ **Allowed characters**: letters, numbers, spaces, basic punctuation (. , ! ? - _)

### Description
- ✅ **Optional**
- ✅ **Maximum**: 1000 characters

### Status
- ✅ **Valid values**: `pending`, `in_progress`, `completed`, `cancelled`

### Priority
- ✅ **Valid values**: `low`, `medium`, `high`, `urgent`

### Due Date
- ✅ **Optional**
- ✅ **Format**: `Y-m-d H:i:s` or `Y-m-d\TH:i`
- ✅ **Cannot be in the past** (only on creation)

## Validation Test Examples

### 1. Empty Title Test
```json
POST /api/tasks/create
{
    "title": "",
    "description": "Test"
}
```
**Expected response**: Validation error

### 2. Too Short Title Test
```json
POST /api/tasks/create
{
    "title": "AB",
    "description": "Test"
}
```
**Expected response**: Validation error

### 3. Too Long Title Test
```json
POST /api/tasks/create
{
    "title": "A".repeat(201),
    "description": "Test"
}
```
**Expected response**: Validation error

### 4. Invalid Status Test
```json
POST /api/tasks/create
{
    "title": "Valid task",
    "status": "invalid_status"
}
```
**Expected response**: Validation error

### 5. Past Date Test
```json
POST /api/tasks/create
{
    "title": "Valid task",
    "due_date": "2020-01-01T10:00:00"
}
```
**Expected response**: Validation error

### 6. Weekend Creation Test
```json
POST /api/tasks/create
{
    "title": "Weekend task",
    "description": "Attempt to create task on Saturday or Sunday"
}
```
**Expected response**: Validation error (only when executed during weekend)

### 7. Update Non-Pending Task Test
```json
PUT /api/tasks/update/1
{
    "title": "Attempt to update completed task",
    "description": "This task is already completed/in_progress/cancelled"
}
```
**Expected response**: Validation error