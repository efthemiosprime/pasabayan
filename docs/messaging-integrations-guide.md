# Pasabay Web Messaging & Notifications Integration Guide

This guide provides step-by-step instructions for setting up real-time features and notifications in the Pasabay application. We'll cover:

1. [Laravel WebSockets](#laravel-websockets)
2. [Pusher Integration](#pusher-integration)
3. [Email Configuration](#email-configuration)
4. [SMS Integration](#sms-integration)

## Laravel WebSockets

Laravel WebSockets is a drop-in Pusher replacement that enables you to run a WebSocket server on your own infrastructure.

### Prerequisites

- Laravel 8.0+
- PHP 7.4+
- Composer

### Installation Steps

1. **Install the Laravel WebSockets package**:

```bash
composer require beyondcode/laravel-websockets
```

2. **Publish the configuration and migration files**:

```bash
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

3. **Run the migrations**:

```bash
php artisan migrate
```

4. **Update your `.env` file**:

```
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=pasabay
PUSHER_APP_KEY=pasabay-key
PUSHER_APP_SECRET=pasabay-secret
PUSHER_APP_CLUSTER=mt1

PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

5. **Configure the Pusher connection in `config/broadcasting.php`**:

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'host' => env('PUSHER_HOST', '127.0.0.1'),
        'port' => env('PUSHER_PORT', 6001),
        'scheme' => env('PUSHER_SCHEME', 'http'),
        'encrypted' => true,
        'useTLS' => env('PUSHER_SCHEME') === 'https',
    ],
],
```

6. **Create WebSockets app in the database**:

```bash
php artisan websockets:serve
```

Then visit `http://yourdomain.com/laravel-websockets` to access the dashboard and create a new app with the following details:
- App ID: `pasabay`
- App Key: `pasabay-key`
- App Secret: `pasabay-secret`

### Usage Example

1. **Create an event**:

```bash
php artisan make:event NewDeliveryRequest
```

2. **Update the event class**:

```php
<?php

namespace App\Events;

use App\Models\DeliveryRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDeliveryRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deliveryRequest;

    public function __construct(DeliveryRequest $deliveryRequest)
    {
        $this->deliveryRequest = $deliveryRequest;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('trip.' . $this->deliveryRequest->trip_id);
    }
    
    public function broadcastWith()
    {
        return [
            'id' => $this->deliveryRequest->id,
            'item_description' => $this->deliveryRequest->item_description,
            'sender' => [
                'id' => $this->deliveryRequest->sender->id,
                'name' => $this->deliveryRequest->sender->name,
            ],
        ];
    }
}
```

3. **Set up authentication for private channels in `routes/channels.php`**:

```php
Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    $trip = \App\Models\Trip::find($tripId);
    return $user->id === $trip->traveler_id;
});
```

4. **Dispatch the event from your controller**:

```php
// In DeliveryRequestController.php
public function store(Request $request)
{
    // ... validation and request creation logic
    
    $deliveryRequest = DeliveryRequest::create([
        'trip_id' => $request->trip_id,
        'sender_id' => Auth::id(),
        // ... other fields
    ]);
    
    event(new NewDeliveryRequest($deliveryRequest));
    
    return response()->json([
        'success' => true,
        'message' => 'Delivery request created successfully',
        'request' => $deliveryRequest
    ], 201);
}
```

5. **Frontend JavaScript setup (with Laravel Echo)**:

Add to `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    wsHost: process.env.MIX_PUSHER_HOST || window.location.hostname,
    wsPort: process.env.MIX_PUSHER_PORT || 6001,
    forceTLS: false,
    disableStats: true,
});
```

6. **Listen for events in your React component**:

```jsx
import React, { useEffect, useState } from 'react';

const TripDetails = ({ tripId, user }) => {
    const [notifications, setNotifications] = useState([]);
    
    useEffect(() => {
        // Subscribe to private channel
        const channel = window.Echo.private(`trip.${tripId}`)
            .listen('NewDeliveryRequest', (e) => {
                setNotifications(prev => [...prev, {
                    type: 'new_request',
                    message: `New delivery request for ${e.item_description}`,
                    data: e
                }]);
                
                // Show notification
                toast.info(`New delivery request: ${e.item_description}`);
            });
            
        return () => {
            channel.stopListening('NewDeliveryRequest');
        };
    }, [tripId]);
    
    // Component render...
};
```

### Running WebSockets Server

In production, you should run the WebSockets server using Supervisor:

1. **Install Supervisor**:

```bash
sudo apt-get install supervisor
```

2. **Create configuration file**:

```bash
sudo nano /etc/supervisor/conf.d/websockets.conf
```

3. **Add the following configuration**:

```
[program:websockets]
command=php /path/to/your/project/artisan websockets:serve
numprocs=1
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/websockets.log
```

4. **Update Supervisor**:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start websockets
```

## Pusher Integration

If you prefer using Pusher's hosted service instead of Laravel WebSockets, follow these steps:

### Setup Steps

1. **Sign up for an account** at [pusher.com](https://pusher.com)

2. **Create a new Channels app** in the Pusher dashboard

3. **Install the Pusher PHP SDK**:

```bash
composer require pusher/pusher-php-server
```

4. **Update your `.env` file with Pusher credentials**:

```
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-app-cluster
```

5. **Configure Laravel to use Pusher in `config/broadcasting.php`**:

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'encrypted' => true,
    ],
],
```

6. **Install frontend dependencies**:

```bash
npm install --save laravel-echo pusher-js
```

7. **Update your frontend configuration** (same as in the WebSockets section, but with different options):

```javascript
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    encrypted: true
});
```

### Usage

The usage is identical to the Laravel WebSockets section. The only difference is the configuration.

## Email Configuration

Laravel provides several drivers for sending emails. Here's how to set up the most common ones:

### SMTP Configuration

1. **Update your `.env` file**:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

> Note: For Gmail, you'll need to use an "App Password" if you have 2FA enabled.

### Mailgun Configuration

1. **Create an account** at [mailgun.com](https://www.mailgun.com/)

2. **Install the Mailgun package**:

```bash
composer require symfony/mailgun-mailer symfony/http-client
```

3. **Update your `.env` file**:

```
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-mailgun-secret
MAILGUN_ENDPOINT=api.mailgun.net
```

### Amazon SES Configuration

1. **Create an AWS account** and set up SES

2. **Install the AWS SDK**:

```bash
composer require aws/aws-sdk-php
```

3. **Update your `.env` file**:

```
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-aws-key-id
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
```

### Creating and Sending Emails

1. **Create a Mailable class**:

```bash
php artisan make:mail DeliveryRequestAccepted
```

2. **Update the Mailable class**:

```php
<?php

namespace App\Mail;

use App\Models\DeliveryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeliveryRequestAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public $deliveryRequest;

    public function __construct(DeliveryRequest $deliveryRequest)
    {
        $this->deliveryRequest = $deliveryRequest;
    }

    public function build()
    {
        return $this->subject('Your Delivery Request Has Been Accepted')
            ->markdown('emails.delivery-request-accepted');
    }
}
```

3. **Create an email template** at `resources/views/emails/delivery-request-accepted.blade.php`:

```blade
@component('mail::message')
# Delivery Request Accepted

Dear {{ $deliveryRequest->sender->name }},

Your delivery request for **{{ $deliveryRequest->item_description }}** has been accepted by the traveler **{{ $deliveryRequest->trip->traveler->name }}**.

Trip Details:
- From: {{ $deliveryRequest->trip->origin }}
- To: {{ $deliveryRequest->trip->destination }}
- Departure Date: {{ \Carbon\Carbon::parse($deliveryRequest->trip->travel_date)->format('M d, Y') }}

@component('mail::button', ['url' => config('app.url') . '/requests/' . $deliveryRequest->id])
View Request Details
@endcomponent

Thank you for using Pasabay!

Regards,<br>
{{ config('app.name') }}
@endcomponent
```

4. **Send the email from your controller**:

```php
use App\Mail\DeliveryRequestAccepted;
use Illuminate\Support\Facades\Mail;

// In DeliveryRequestController.php
public function acceptRequest(Request $request, string $id)
{
    $deliveryRequest = DeliveryRequest::with(['sender', 'trip.traveler'])->findOrFail($id);
    
    // ... logic to accept the request
    
    // Send email notification
    Mail::to($deliveryRequest->sender->email)
        ->send(new DeliveryRequestAccepted($deliveryRequest));
    
    return response()->json([
        'success' => true,
        'message' => 'Request accepted successfully'
    ]);
}
```

### Queuing Emails

To prevent email sending from slowing down your application:

1. **Configure a queue driver in `.env`**:

```
QUEUE_CONNECTION=database
```

2. **Create the queue tables**:

```bash
php artisan queue:table
php artisan migrate
```

3. **Implement the `ShouldQueue` interface in your Mailable**:

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class DeliveryRequestAccepted extends Mailable implements ShouldQueue
{
    // ...
}
```

4. **Start the queue worker**:

```bash
php artisan queue:work
```

Or with Supervisor for production:

```
[program:queue-worker]
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3
numprocs=1
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue.log
```

## SMS Integration

For SMS notifications, we'll use Twilio, one of the most reliable SMS providers.

### Twilio Setup

1. **Create an account** at [twilio.com](https://www.twilio.com/)

2. **Get your Account SID, Auth Token, and a Twilio phone number** from the dashboard

3. **Install the Twilio SDK**:

```bash
composer require twilio/sdk
```

4. **Add Twilio configuration** to your `.env` file:

```
TWILIO_SID=your-account-sid
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_FROM=your-twilio-phone-number
```

5. **Create a service provider**:

```bash
php artisan make:provider TwilioServiceProvider
```

6. **Update the service provider**:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client;

class TwilioServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
        });
    }

    public function boot()
    {
        //
    }
}
```

7. **Register the service provider** in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\TwilioServiceProvider::class,
],
```

8. **Add Twilio configuration** to `config/services.php`:

```php
'twilio' => [
    'sid' => env('TWILIO_SID'),
    'token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_FROM'),
],
```

### Creating a Notification

1. **Create a notification class**:

```bash
php artisan make:notification DeliveryRequestStatusChanged
```

2. **Update the notification class**:

```php
<?php

namespace App\Notifications;

use App\Models\DeliveryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;

class DeliveryRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $deliveryRequest;
    protected $status;

    public function __construct(DeliveryRequest $deliveryRequest, string $status)
    {
        $this->deliveryRequest = $deliveryRequest;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        // Check user preferences - for example:
        $channels = ['database'];
        
        if ($notifiable->notifications_email) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->notifications_sms && $notifiable->phone) {
            $channels[] = 'vonage'; // or 'twilio'
        }
        
        return $channels;
    }

    public function toMail($notifiable)
    {
        $statusText = ucfirst($this->status);
        
        return (new MailMessage)
            ->subject("Delivery Request {$statusText}")
            ->line("Your delivery request for {$this->deliveryRequest->item_description} has been {$this->status}.")
            ->action('View Request', url("/requests/{$this->deliveryRequest->id}"))
            ->line('Thank you for using Pasabay!');
    }

    public function toVonage($notifiable)
    {
        $statusText = ucfirst($this->status);
        
        return (new VonageMessage)
            ->content("Pasabay: Your delivery request for {$this->deliveryRequest->item_description} has been {$this->status}.");
    }
    
    public function toTwilio($notifiable)
    {
        $statusText = ucfirst($this->status);
        
        return "Pasabay: Your delivery request for {$this->deliveryRequest->item_description} has been {$this->status}.";
    }

    public function toArray($notifiable)
    {
        return [
            'delivery_request_id' => $this->deliveryRequest->id,
            'status' => $this->status,
            'item_description' => $this->deliveryRequest->item_description,
        ];
    }
}
```

3. **Create a custom Twilio channel**:

```bash
php artisan make:notification-channel Twilio
```

4. **Update the channel**:

```php
<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class TwilioChannel
{
    protected $twilio;

    public function __construct(Client $twilio)
    {
        $this->twilio = $twilio;
    }

    public function send($notifiable, Notification $notification)
    {
        if (!$to = $notifiable->routeNotificationFor('twilio', $notification)) {
            return;
        }

        $message = $notification->toTwilio($notifiable);

        if (is_string($message)) {
            $message = ['content' => $message];
        }

        $this->twilio->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message['content'],
        ]);
    }
}
```

5. **Register the channel** in a service provider:

```php
// In TwilioServiceProvider.php

public function boot()
{
    \Illuminate\Support\Facades\Notification::extend('twilio', function ($app) {
        return new \App\Notifications\Channels\TwilioChannel(
            $app->make(Client::class)
        );
    });
}
```

6. **Add a routing method** to your User model:

```php
public function routeNotificationForTwilio($notification)
{
    return $this->phone;
}
```

7. **Send the notification** from your controller:

```php
use App\Notifications\DeliveryRequestStatusChanged;

// In DeliveryRequestController.php
public function acceptRequest(Request $request, string $id)
{
    $deliveryRequest = DeliveryRequest::with(['sender', 'trip.traveler'])->findOrFail($id);
    
    // ... logic to accept the request
    
    // Send notification
    $deliveryRequest->sender->notify(new DeliveryRequestStatusChanged($deliveryRequest, 'accepted'));
    
    return response()->json([
        'success' => true,
        'message' => 'Request accepted successfully'
    ]);
}
```

## Integration Testing

### Testing WebSockets/Pusher

1. **Create a test event route** in `routes/web.php`:

```php
Route::get('/test-event', function () {
    event(new App\Events\NewDeliveryRequest(
        DeliveryRequest::with(['sender', 'trip'])->first()
    ));
    return 'Event dispatched!';
});
```

2. **Create a testing page** to verify:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Pusher Test</title>
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script>
        // Configure Pusher instance
        const pusher = new Pusher('pasabay-key', {
            wsHost: window.location.hostname,
            wsPort: 6001,
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        });

        // Subscribe to channel
        const channel = pusher.subscribe('private-trip.1');
        
        // Bind to event
        channel.bind('App\\Events\\NewDeliveryRequest', function(data) {
            console.log('Received event!', data);
            alert('New delivery request: ' + data.item_description);
        });
    </script>
</head>
<body>
    <h1>Pusher Test</h1>
    <p>Open your console to see events!</p>
</body>
</html>
```

### Testing Email

Create a route to test email sending:

```php
Route::get('/test-mail', function () {
    $deliveryRequest = \App\Models\DeliveryRequest::with(['sender', 'trip.traveler'])->first();
    \Illuminate\Support\Facades\Mail::to('test@example.com')
        ->send(new \App\Mail\DeliveryRequestAccepted($deliveryRequest));
    return 'Email sent!';
});
```

### Testing SMS

Create a route to test SMS sending:

```php
Route::get('/test-sms', function () {
    $twilio = new Twilio\Rest\Client(
        config('services.twilio.sid'),
        config('services.twilio.token')
    );
    
    $twilio->messages->create(
        '+1234567890', // Replace with a real phone number
        [
            'from' => config('services.twilio.from'),
            'body' => 'This is a test SMS from Pasabay!'
        ]
    );
    
    return 'SMS sent!';
});
```

## Troubleshooting

### WebSockets Issues

1. **Connection refused errors**:
   - Check firewall settings
   - Ensure the correct port is open
   - Verify the correct host is configured

2. **Authentication errors**:
   - Check your app ID, key, and secret
   - Ensure your channel authorization is working correctly

3. **Messages not being received**:
   - Check browser console for errors
   - Verify event names match exactly (including namespaces)
   - Test with the WebSockets dashboard

### Email Issues

1. **Failed to authenticate on SMTP server**:
   - Check credentials in `.env`
   - For Gmail, ensure you're using an app password if 2FA is enabled
   - Verify the correct port and encryption settings

2. **Connection refused**:
   - Check firewall settings
   - Verify SMTP server is reachable

3. **Rate limiting**:
   - Implement queues for bulk email sending
   - Use a dedicated email service provider like Mailgun or SES

### SMS Issues

1. **Invalid phone number**:
   - Ensure phone numbers are in E.164 format (e.g., +12345678900)
   - Validate phone numbers before sending

2. **Authentication errors**:
   - Verify your Twilio credentials
   - Check if your Twilio account has sufficient balance

3. **Content issues**:
   - Keep messages under the character limit
   - Avoid using certain keywords that might trigger spam filters 