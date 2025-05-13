# ACME Corp CSR Donation Platform

## 1. Architecture Choices

*   **Framework**: Laravel (latest version) was chosen as per the requirements. Laravel provides a robust, scalable, and well-structured foundation for building APIs, with built-in support for features like ORM (Eloquent), routing, authentication (Sanctum), and testing.
*   **API Type**: A RESTful API design is implemented. This is a widely adopted standard, making the API understandable and easy to consume by various clients (web, mobile, etc.).
*   **Database**: SQLite is used for local development and testing, as specified as an option. It's lightweight and easy to set up. For production, a more robust database like PostgreSQL or MySQL would be recommended and can be easily configured in Laravel.
*   **Authentication**: Laravel Sanctum is used for API token-based authentication. It's a lightweight system suitable for SPAs, mobile applications, and simple token-based APIs.
*   **Static Analysis**: PHPStan (level 8) is integrated for static analysis to help maintain code quality and catch potential bugs early, as per requirements.
*   **Testing**: PestPHP is set up for writing feature and unit tests, adhering to the project requirements. While tests haven't been written in this initial setup, the framework is in place.

## 2. Assumed Hypotheses

*   **Employee Data**: It's assumed that employee authentication (registration, login) is handled, and basic user data (ID, name, email) is available through the `User` model.
*   **Payment System Agnostic**: The payment system is not yet chosen. The current implementation simulates a successful donation. When a payment gateway is selected, its integration will involve:
    *   Creating payment intents with the chosen provider.
    *   Handling payment callbacks/webhooks to update donation statuses.
    *   Storing relevant payment identifiers (e.g., `payment_intent_id` in the `donations` table).
*   **Single Currency**: The platform is assumed to operate with a single currency for donations and goal amounts. Multi-currency support would require additional complexity.
*   **Confirmation Mechanism**: The requirement for donation confirmation is acknowledged. This would typically involve sending an email or in-app notification. An event listener for a `DonationReceived` event (commented out in `DonationController`) would be the standard Laravel way to handle this.
*   **No UI**: The focus is solely on the API, as stated. No front-end/UI development is included.

## 4. Running the Application with Docker Compose

These instructions assume you have Docker and Docker Compose installed on your system.

1.  **Clone the repository.**
    ```bash
    git clone <repository-url>
    cd <repository-name>
    ```

2.  **Environment Setup**:
    *   Copy `.env.example` to `.env`:
        ```bash
        cp .env.example .env
        ```
    *   Review the `.env` file. Key settings for Docker Compose are already configured (e.g., `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/database/database.sqlite`). You might want to adjust `APP_NAME` or `APP_PORT` if needed.

3.  **Build and Start Docker Containers**:
    ```bash
    docker-compose up -d --build
    ```
    This command will build the Docker images (if they don't exist or if `Dockerfile` changed) and start the `app` (PHP-FPM) and `webserver` (Nginx) services in detached mode.

4.  **Generate Application Key** (if not already set in your `.env` or if it's a fresh setup):
    Run the following command to execute `php artisan key:generate` inside the `app` container:
    ```bash
    docker-compose exec app php artisan key:generate
    ```

5.  **Run Database Migrations**:
    Execute the migrations inside the `app` container:
    ```bash
    docker-compose exec app php artisan migrate
    ```

6.  **(Optional) Seed the Database** (Seeders would need to be created first):
    ```bash
    docker-compose exec app php artisan db:seed
    ```

7.  **Access the Application**:
    The API should now be accessible at `http://localhost:${APP_PORT:-8000}/api/` (defaulting to `http://localhost:8000/api/` if `APP_PORT` is not set in your `.env` file).

### Other Useful Docker Compose Commands

*   **Stop containers**:
    ```bash
    docker-compose down
    ```
*   **View logs**:
    ```bash
    docker-compose logs -f app
    docker-compose logs -f webserver
    ```
*   **Access shell in the app container**:
    ```bash
    docker-compose exec app sh
    ```

### Running Tests (Pest)

To run the Pest tests, execute the following command. This will run the tests inside the `app` container:

```bash
docker-compose exec app php artisan test
```

Alternatively, you can run Pest directly:

```bash
docker-compose exec app ./vendor/bin/pest
```

You can also run specific test files or add Pest options as needed:

```bash
docker-compose exec app ./vendor/bin/pest tests/Feature/ExampleTest.php
docker-compose exec app ./vendor/bin/pest --coverage
```

## 5. API Endpoints

(Refer to `routes/api.php` for the full list and controller methods for request/response details)

### Authentication

*   `POST /register`
*   `POST /login`
*   `POST /logout` (Authenticated)

### Campaigns

*   `GET /api/campaigns` - List all campaigns
*   `POST /api/campaigns` - Create a new campaign (Authenticated)
*   `GET /api/campaigns/{campaign}` - Show a specific campaign
*   `PUT /api/campaigns/{campaign}` - Update a campaign (Authenticated, Owner)
*   `DELETE /api/campaigns/{campaign}` - Delete a campaign (Authenticated, Owner)

### Donations

*   `GET /api/donations` - List user's donations (Authenticated)
*   `POST /api/donations` - Create a new donation (Authenticated)
*   `GET /api/donations/{donation}` - Show a specific donation (Authenticated, Owner)

## 6. API Usage: Authentication and Making Calls

### 6.1. Registration

To register a new employee (user), send a `POST` request to `/register`. Note: Laravel Breeze API scaffolding typically creates `/register` and `/login` routes that are not prefixed with `/api`. If you are using a different setup, or if these routes are not working, please check your `routes/auth.php` or `routes/api.php` for the exact paths.

**Endpoint:** `POST /register`

**Headers:**
```
Accept: application/json
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

A successful registration will typically return the user details and a 201 status code.

### 6.2. Login

To log in an existing user, send a `POST` request to `/login`.

**Endpoint:** `POST /login`

**Headers:**
```
Accept: application/json
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "email": "john.doe@example.com",
    "password": "password123"
}
```

A successful login will return a plain text Sanctum API token.
**Example Response (200 OK):**
```
1|abcdefghijklmnopqrstuvwxyz1234567890
```
(The actual token will be a long string). Store this token securely on the client-side.

### 6.3. Making Authenticated API Calls

Once logged in, you must include the API token in the `Authorization` header for all subsequent requests to protected API endpoints (those under `auth:sanctum` middleware).

**Header Format:**
```
Authorization: Bearer <YOUR_API_TOKEN>
Accept: application/json
Content-Type: application/json (if sending a body)
```

**Example: Creating a Campaign**

**Endpoint:** `POST /api/campaigns`

**Headers:**
```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890
Accept: application/json
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "title": "My New Charity Campaign",
    "description": "Helping those in need.",
    "goal_amount": 5000.00,
    "start_date": "2024-06-01",
    "end_date": "2024-12-31"
}
```

### 6.4. Logout

To log out and invalidate the current API token, send a `POST` request to `/logout`.

**Endpoint:** `POST /logout`

**Headers:**
```
Authorization: Bearer <YOUR_API_TOKEN>
Accept: application/json
```
This will invalidate the token used for the request. You should then remove the token from client-side storage.