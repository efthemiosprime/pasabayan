# Pasabay - Peer-to-Peer Delivery Platform

Pasabay is a web application that connects senders with travelers who are already heading to a desired destination, enabling cost-effective and convenient item deliveries. The platform facilitates matching, secure communication, and transactions.

## Technology Stack

- **Backend:** Laravel (PHP Framework)
- **Database:** PostgreSQL
- **Frontend:** Blade Templates, React (for interactive features)
- **Authentication:** Laravel Sanctum (API authentication)
- **Messaging & Notifications:** Laravel WebSockets, Pusher, Email & SMS Integration
- **Geolocation Services:** Google Maps API for distance calculations and trip mapping

## User Roles

1. **Traveler**: Registers trips and earns money by delivering items.
2. **Sender**: Posts delivery requests for packages.
3. **Admin**: Manages platform operations and resolves disputes.

## Key Features

### 1. User Management
- User registration & authentication
- Profile setup with identity verification (optional)
- User dashboard for trip/package tracking

### 2. Trip Management (Traveler)
- Create and manage trips (origin, destination, travel date, available capacity, mode of transport)
- View matching delivery requests
- Accept/reject delivery requests

### 3. Delivery Requests (Sender)
- Create a delivery request (pickup & drop-off locations, package details, weight, dimensions, urgency)
- View matching trips
- Request delivery from a traveler

### 4. Matching System
- Algorithm to match trips and delivery requests based on:
  - Destination proximity
  - Available capacity
  - Travel date alignment
- Prioritization based on rating and past successful deliveries

### 5. Secure Messaging & Notifications
- Messaging enabled only when a trip and delivery request match
- Real-time chat and notification system (Email, SMS, Push notifications)
- Automated updates on delivery status

### 6. Transaction & Payment System
- Cost estimation based on distance and package size
- Secure online payments (Stripe/PayPal)
- Payment escrow system: Funds held until successful delivery confirmation

### 7. Package Tracking
- Real-time status updates (picked up, in transit, delivered)
- Optional GPS tracking for long-distance deliveries

### 8. Review & Rating System
- Travelers and senders rate each other after transaction completion
- Reviews visible on profiles to ensure trustworthiness

### 9. Admin Dashboard
- User and transaction management
- Dispute resolution center
- Analytics on trips and deliveries

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/pasabay.git
   ```

2. Install dependencies:
   ```
   composer install
   npm install
   ```

3. Set up environment variables:
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database in the `.env` file:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=pasabay2
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

5. Run migrations:
   ```
   php artisan migrate
   ```

6. Seed the database (optional):
   ```
   php artisan db:seed
   ```

7. Start the development server:
   ```
   php artisan serve
   ```

## API Endpoints

### Authentication
- `POST /api/register` – Register user
- `POST /api/login` – Authenticate user
- `POST /api/logout` – Logout user

### Trips
- `POST /api/trips` – Create a new trip
- `GET /api/trips` – Get all available trips
- `GET /api/trips/{id}` – Get trip details
- `DELETE /api/trips/{id}` – Delete a trip

### Delivery Requests
- `POST /api/requests` – Create a delivery request
- `GET /api/requests` – Get available delivery requests
- `GET /api/requests/{id}` – Get request details
- `DELETE /api/requests/{id}` – Delete a request

### Matching System
- `GET /api/matches/trip/{trip_id}` – Get delivery requests matching a trip
- `GET /api/matches/request/{request_id}` – Get trips matching a request

### Messaging
- `POST /api/messages` – Send a message
- `GET /api/messages/{conversation_id}` – Retrieve messages

### Payment
- `POST /api/payments` – Process payment
- `GET /api/payments/{id}` – Get payment details

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributors

- Your Name - Initial work
