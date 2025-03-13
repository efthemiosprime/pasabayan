# Pasabay - Peer-to-Peer Delivery Platform API Documentation

Introduction
This documentation provides information about the Pasabay API, a platform that connects senders with travelers who are already heading to a desired destination, enabling cost-effective and convenient item deliveries.
Base URL: http://localhost:8000/api

Authentication
The API uses Laravel Sanctum for token-based authentication.

Register a New User
```POST /register```
Request Body:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password",
  "phone": "1234567890",
  "role": "sender" // "sender" or "traveler"
}
```
Response: (201 Created)
```json
{
  "status": "success",
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "role": "sender",
    "rating": 0,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:00:00.000000Z"
  },
  "token": "ACCESS_TOKEN"
}
```

Login

```POST /login```
Request Body:
```json
{
  "email": "john@example.com",
  "password": "password"
}
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "User logged in successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "role": "sender",
    "rating": 0,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:00:00.000000Z"
  },
  "token": "ACCESS_TOKEN"
}
```

Logout

```POST /logout```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "User logged out successfully"
}
```

Get User Profile

```GET /user```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "address": "123 Main St",
    "city": "Anytown",
    "state": "State",
    "country": "Country",
    "postal_code": "12345",
    "profile_photo": null,
    "id_verification": null,
    "is_verified": false,
    "role": "sender",
    "rating": 0,
    "bio": null,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:00:00.000000Z"
  }
}
```

Update User Profile

```PUT /user/profile```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "name": "John Smith",
  "phone": "0987654321",
  "address": "456 Second St",
  "city": "Newtown",
  "state": "New State",
  "country": "New Country",
  "postal_code": "54321",
  "bio": "I'm a sender looking for travelers to deliver my packages"
}
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "name": "John Smith",
    "email": "john@example.com",
    "phone": "0987654321",
    "address": "456 Second St",
    "city": "Newtown",
    "state": "New State",
    "country": "New Country",
    "postal_code": "54321",
    "bio": "I'm a sender looking for travelers to deliver my packages",
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:15:00.000000Z"
  }
}
```

Trips

List All Trips

```GET /trips```
Query Parameters:
```
origin - Filter by origin location
destination - Filter by destination location
travel_date - Filter by travel date (YYYY-MM-DD)
transport_mode - Filter by mode of transport
min_capacity - Filter by minimum available capacity
```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "traveler_id": 2,
        "origin": "Manila",
        "destination": "Cebu",
        "travel_date": "2025-04-01T00:00:00.000000Z",
        "return_date": "2025-04-10T00:00:00.000000Z",
        "available_capacity": 5,
        "transport_mode": "Airplane",
        "notes": "I can carry small to medium packages",
        "status": "active",
        "created_at": "2025-03-11T00:00:00.000000Z",
        "updated_at": "2025-03-11T00:00:00.000000Z",
        "traveler": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com",
          "rating": 4.5
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/trips?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/trips?page=1",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/trips?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "next_page_url": null,
    "path": "http://localhost:8000/api/trips",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Create a Trip

```POST /trips```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "origin": "Manila",
  "destination": "Cebu",
  "travel_date": "2025-04-01",
  "return_date": "2025-04-10",
  "available_capacity": 5,
  "transport_mode": "Airplane",
  "notes": "I can carry small to medium packages"
}
```
Response: (201 Created)
```json
{
  "status": "success",
  "message": "Trip created successfully",
  "data": {
    "id": 1,
    "traveler_id": 2,
    "origin": "Manila",
    "destination": "Cebu",
    "travel_date": "2025-04-01T00:00:00.000000Z",
    "return_date": "2025-04-10T00:00:00.000000Z",
    "available_capacity": 5,
    "transport_mode": "Airplane",
    "notes": "I can carry small to medium packages",
    "status": "active",
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:00:00.000000Z"
  }
}
```

View Trip Details

```GET /trips/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "traveler_id": 2,
    "origin": "Manila",
    "destination": "Cebu",
    "travel_date": "2025-04-01T00:00:00.000000Z",
    "return_date": "2025-04-10T00:00:00.000000Z",
    "available_capacity": 5,
    "transport_mode": "Airplane",
    "notes": "I can carry small to medium packages",
    "status": "active",
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:00:00.000000Z",
    "traveler": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "rating": 4.5
    },
    "delivery_requests": []
  }
}
```

Update a Trip

```PUT /trips/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "origin": "Makati",
  "destination": "Davao",
  "travel_date": "2025-04-05",
  "available_capacity": 3,
  "transport_mode": "Bus",
  "notes": "Updated trip details"
}
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Trip updated successfully",
  "data": {
    "id": 1,
    "traveler_id": 2,
    "origin": "Makati",
    "destination": "Davao",
    "travel_date": "2025-04-05T00:00:00.000000Z",
    "return_date": "2025-04-10T00:00:00.000000Z",
    "available_capacity": 3,
    "transport_mode": "Bus",
    "notes": "Updated trip details",
    "status": "active",
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:30:00.000000Z"
  }
}
```

Delete a Trip

```DELETE /trips/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Trip deleted successfully"
}
```

List My Trips

```GET /my-trips```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "traveler_id": 2,
        "origin": "Manila",
        "destination": "Cebu",
        "travel_date": "2025-04-01T00:00:00.000000Z",
        "return_date": "2025-04-10T00:00:00.000000Z",
        "available_capacity": 5,
        "transport_mode": "Airplane",
        "notes": "I can carry small to medium packages",
        "status": "active",
        "created_at": "2025-03-11T00:00:00.000000Z",
        "updated_at": "2025-03-11T00:00:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/my-trips?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/my-trips?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/my-trips",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Delivery Requests

List All Delivery Requests

```GET /requests```
Query Parameters:
```
pickup_location - Filter by pickup location
dropoff_location - Filter by dropoff location
delivery_date - Filter by delivery date (YYYY-MM-DD)
package_size - Filter by package size
urgency - Filter by urgency
```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "sender_id": 1,
        "trip_id": null,
        "pickup_location": "Makati",
        "dropoff_location": "Cebu City",
        "package_size": "small",
        "package_weight": 2,
        "package_description": "Small gift box",
        "urgency": "medium",
        "delivery_date": "2025-04-05T00:00:00.000000Z",
        "status": "pending",
        "special_instructions": "Handle with care",
        "estimated_cost": null,
        "created_at": "2025-03-11T00:00:00.000000Z",
        "updated_at": "2025-03-11T00:00:00.000000Z",
        "sender": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "rating": 4.0
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/requests?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/requests?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/requests",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Create a Delivery Request

```POST /requests```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "trip_id": null,
  "pickup_location": "Makati",
  "dropoff_location": "Cebu City",
  "package_size": "small",
  "package_weight": 2,
  "package_description": "Small gift box",
  "urgency": "medium",
  "delivery_date": "2025-04-05",
  "special_instructions": "Handle with care"
}
```
Response: (201 Created)
```json
{
  "status": "success",
  "message": "Delivery request created successfully",
  "data": {
    "id": 1,
    "sender_id": 1,
    "trip_id": null,
    "pickup_location": "Makati",
    "dropoff_location": "Cebu City",
    "package_size": "small",
    "package_weight": 2,
    "package_description": "Small gift box",
    "urgency": "medium",
    "delivery_date": "2025-04-05T00:00:00.000000Z",
    "status": "pending",
    "special_instructions": "Handle with care",
    "estimated_cost": null,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:00:00.000000Z"
  }
}
```

View Delivery Request Details

```GET /requests/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "sender_id": 1,
    "trip_id": null,
    "pickup_location": "Makati",
    "dropoff_location": "Cebu City",
    "package_size": "small",
    "package_weight": 2,
    "package_description": "Small gift box",
    "urgency": "medium",
    "delivery_date": "2025-04-05T00:00:00.000000Z",
    "status": "pending",
    "special_instructions": "Handle with care",
    "estimated_cost": null,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:00:00.000000Z",
    "sender": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "rating": 4.0
    },
    "trip": null
  }
}
```

Update a Delivery Request

```PUT /requests/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "pickup_location": "BGC",
  "dropoff_location": "Cebu City",
  "package_size": "medium",
  "package_weight": 3,
  "package_description": "Updated description",
  "urgency": "high",
  "special_instructions": "Please call before delivery"
}
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Delivery request updated successfully",
  "data": {
    "id": 1,
    "sender_id": 1,
    "trip_id": null,
    "pickup_location": "BGC",
    "dropoff_location": "Cebu City",
    "package_size": "medium",
    "package_weight": 3,
    "package_description": "Updated description",
    "urgency": "high",
    "delivery_date": "2025-04-05T00:00:00.000000Z",
    "status": "pending",
    "special_instructions": "Please call before delivery",
    "estimated_cost": null,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T00:45:00.000000Z"
  }
}
```

Delete a Delivery Request

```DELETE /requests/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Delivery request deleted successfully"
}
```

Accept a Delivery Request (for Travelers)

```POST /requests/{id}/accept```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Delivery request accepted successfully",
  "data": {
    "id": 1,
    "sender_id": 1,
    "trip_id": 1,
    "pickup_location": "BGC",
    "dropoff_location": "Cebu City",
    "package_size": "medium",
    "package_weight": 3,
    "package_description": "Updated description",
    "urgency": "high",
    "delivery_date": "2025-04-05T00:00:00.000000Z",
    "status": "accepted",
    "special_instructions": "Please call before delivery",
    "estimated_cost": 14,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T01:00:00.000000Z"
  }
}
```

List My Delivery Requests

```GET /my-requests```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "sender_id": 1,
        "trip_id": null,
        "pickup_location": "BGC",
        "dropoff_location": "Cebu City",
        "package_size": "medium",
        "package_weight": 3,
        "package_description": "Updated description",
        "urgency": "high",
        "delivery_date": "2025-04-05T00:00:00.000000Z",
        "status": "pending",
        "special_instructions": "Please call before delivery",
        "estimated_cost": null,
        "created_at": "2025-03-11T00:00:00.000000Z",
        "updated_at": "2025-03-11T00:45:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/my-requests?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/my-requests?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/my-requests",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Matching

Get Delivery Requests Matching a Trip

```GET /matches/trip/{trip_id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "sender_id": 1,
        "trip_id": null,
        "pickup_location": "BGC",
        "dropoff_location": "Cebu City",
        "package_size": "medium",
        "package_weight": 3,
        "package_description": "Updated description",
        "urgency": "high",
        "delivery_date": "2025-04-05T00:00:00.000000Z",
        "status": "pending",
        "special_instructions": "Please call before delivery",
        "estimated_cost": null,
        "created_at": "2025-03-11T00:00:00.000000Z",
        "updated_at": "2025-03-11T00:45:00.000000Z",
        "sender": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "rating": 4.0
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/matches/trip/1?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/matches/trip/1?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/matches/trip/1",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Get Trips Matching a Delivery Request

```GET /matches/request/{request_id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "traveler_id": 2,
        "origin": "Manila",
        "destination": "Cebu",
        "travel_date": "2025-04-01T00:00:00.000000Z",
        "return_date": "2025-04-10T00:00:00.000000Z",
        "available_capacity": 5,
        "transport_mode": "Airplane",
        "notes": "I can carry small to medium packages",
        "status": "active",
        "created_at": "2025-03-11T00:00:00.000000Z",
        "updated_at": "2025-03-11T00:00:00.000000Z",
        "traveler": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com",
          "rating": 4.5
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/matches/request/1?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/matches/request/1?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/matches/request/1",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Assign a Delivery Request to a Trip

```POST /matches/assign```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "trip_id": 1,
  "request_id": 1
}
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Delivery request assigned to trip successfully",
  "data": {
    "id": 1,
    "sender_id": 1,
    "trip_id": 1,
    "pickup_location": "BGC",
    "dropoff_location": "Cebu City",
    "package_size": "medium",
    "package_weight": 3,
    "package_description": "Updated description",
    "urgency": "high",
    "delivery_date": "2025-04-05T00:00:00.000000Z",
    "status": "pending",
    "special_instructions": "Please call before delivery",
    "estimated_cost": 14,
    "created_at": "2025-03-11T00:00:00.000000Z",
    "updated_at": "2025-03-11T01:15:00.000000Z"
  }
}
```

Reviews

List All Reviews

```GET /reviews```
Query Parameters:
```
reviewer_id - Filter by reviewer
reviewee_id - Filter by reviewee
trip_id - Filter by trip
delivery_request_id - Filter by delivery request
min_rating - Filter by minimum rating
```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "reviewer_id": 1,
        "reviewee_id": 2,
        "delivery_request_id": 1,
        "trip_id": 1,
        "rating": 4,
        "comment": "Great traveler, highly recommended!",
        "created_at": "2025-03-11T01:30:00.000000Z",
        "updated_at": "2025-03-11T01:30:00.000000Z",
        "reviewer": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "rating": 4.0
        },
        "reviewee": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com",
          "rating": 4.0
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/reviews?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/reviews?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/reviews",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Create a Review

```POST /reviews```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "reviewee_id": 2,
  "delivery_request_id": 1,
  "trip_id": 1,
  "rating": 4,
  "comment": "Great traveler, highly recommended!"
}
```
Response: (201 Created)
```json
{
  "status": "success",
  "message": "Review created successfully",
  "data": {
    "id": 1,
    "reviewer_id": 1,
    "reviewee_id": 2,
    "delivery_request_id": 1,
    "trip_id": 1,
    "rating": 4,
    "comment": "Great traveler, highly recommended!",
    "created_at": "2025-03-11T01:30:00.000000Z",
    "updated_at": "2025-03-11T01:30:00.000000Z"
  }
}
```

View Review Details

```GET /reviews/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "reviewer_id": 1,
    "reviewee_id": 2,
    "delivery_request_id": 1,
    "trip_id": 1,
    "rating": 4,
    "comment": "Great traveler, highly recommended!",
    "created_at": "2025-03-11T01:30:00.000000Z",
    "updated_at": "2025-03-11T01:30:00.000000Z",
    "reviewer": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "rating": 4.0
    },
    "reviewee": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "rating": 4.0
    },
    "deliveryRequest": {
      "id": 1,
      "sender_id": 1,
      "trip_id": 1,
      "pickup_location": "BGC",
      "dropoff_location": "Cebu City",
      "status": "delivered"
    },
    "trip": {
      "id": 1,
      "traveler_id": 2,
      "origin": "Manila",
      "destination": "Cebu",
      "status": "completed"
    }
  }
}
```

Update a Review

```PUT /reviews/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Request Body:
```json
{
  "rating": 5,
  "comment": "Actually, this traveler was excellent!"
}
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Review updated successfully",
  "data": {
    "id": 1,
    "reviewer_id": 1,
    "reviewee_id": 2,
    "delivery_request_id": 1,
    "trip_id": 1,
    "rating": 5,
    "comment": "Actually, this traveler was excellent!",
    "created_at": "2025-03-11T01:30:00.000000Z",
    "updated_at": "2025-03-11T01:45:00.000000Z"
  }
}
```

Delete a Review

```DELETE /reviews/{id}```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "message": "Review deleted successfully"
}
```

List Reviews Given by the Authenticated User

```GET /my-given-reviews```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "reviewer_id": 1,
        "reviewee_id": 2,
        "delivery_request_id": 1,
        "trip_id": 1,
        "rating": 5,
        "comment": "Actually, this traveler was excellent!",
        "created_at": "2025-03-11T01:30:00.000000Z",
        "updated_at": "2025-03-11T01:45:00.000000Z",
        "reviewee": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com",
          "rating": 5.0
        },
        "deliveryRequest": {
          "id": 1,
          "sender_id": 1,
          "trip_id": 1,
          "pickup_location": "BGC",
          "dropoff_location": "Cebu City",
          "status": "delivered"
        },
        "trip": {
          "id": 1,
          "traveler_id": 2,
          "origin": "Manila",
          "destination": "Cebu",
          "status": "completed"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/my-given-reviews?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/my-given-reviews?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/my-given-reviews",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

List Reviews Received by the Authenticated User

```GET /my-received-reviews```
Headers:
```
Authorization: Bearer ACCESS_TOKEN
```
Response: (200 OK)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 2,
        "reviewer_id": 2,
        "reviewee_id": 1,
        "delivery_request_id": 1,
        "trip_id": 1,
        "rating": 4,
        "comment": "Good sender, packages were well prepared",
        "created_at": "2025-03-11T02:00:00.000000Z",
        "updated_at": "2025-03-11T02:00:00.000000Z",
        "reviewer": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com",
          "rating": 5.0
        },
        "deliveryRequest": {
          "id": 1,
          "sender_id": 1,
          "trip_id": 1,
          "pickup_location": "BGC",
          "dropoff_location": "Cebu City",
          "status": "delivered"
        },
        "trip": {
          "id": 1,
          "traveler_id": 2,
          "origin": "Manila",
          "destination": "Cebu",
          "status": "completed"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/my-received-reviews?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/my-received-reviews?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/my-received-reviews",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

Error Responses

Validation Error (422 Unprocessable Entity)
```json
{
  "status": "error",
  "message": "Validation error",
  "errors": {
    "field_name": [
      "The field name is required."
    ]
  }
}
```

Authentication Error (401 Unauthorized)
```json
{
  "status": "error",
  "message": "Invalid login credentials"
}
```

Authorization Error (403 Forbidden)
```json
{
  "status": "error",
  "message": "You are not authorized to perform this action"
}
```

Resource Not Found (404 Not Found)
```json
{
  "status": "error",
  "message": "Resource not found"
}
```

Bad Request (400 Bad Request)
```json
{
  "status": "error",
  "message": "The delivery request is not associated with the specified trip"
}
```