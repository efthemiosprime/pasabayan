# Pasabay Android Messaging & Notifications Integration Guide

This guide provides step-by-step instructions for implementing real-time features and notifications in the Pasabay Android app. We'll cover:

1. [Pusher Integration](#pusher-integration)
2. [Firebase Cloud Messaging (FCM)](#firebase-cloud-messaging)
3. [Deep Links](#deep-links)
4. [SMS Verification](#sms-verification)

## Pusher Integration

Pusher allows your Android app to receive real-time updates from the Pasabay backend.

### Prerequisites

- Android Studio 4.0+
- Android SDK Level 23+ (Android 6.0 Marshmallow or higher)
- Kotlin or Java

### Installation Steps

1. **Add Pusher to your app-level build.gradle**:

```gradle
dependencies {
    implementation 'com.pusher:pusher-java-client:2.4.0'
    implementation 'com.google.code.gson:gson:2.9.0'
}
```

2. **Sync your project** with the Gradle files.

### Implementation

1. **Create a PusherManager class**:

```kotlin
// PusherManager.kt
package com.yourcompany.pasabay.utils

import android.util.Log
import com.google.gson.Gson
import com.pusher.client.Pusher
import com.pusher.client.PusherOptions
import com.pusher.client.channel.PrivateChannelEventListener
import com.pusher.client.connection.ConnectionEventListener
import com.pusher.client.connection.ConnectionState
import com.pusher.client.connection.ConnectionStateChange
import com.pusher.client.util.HttpAuthorizer
import com.yourcompany.pasabay.models.DeliveryRequestPayload
import org.json.JSONObject
import java.util.concurrent.ConcurrentHashMap

class PusherManager private constructor() {
    companion object {
        private const val TAG = "PusherManager"
        val instance = PusherManager()
    }
    
    private var pusher: Pusher? = null
    private val subscribedChannels = ConcurrentHashMap<String, Boolean>()
    private val listeners = ArrayList<PusherEventListener>()
    
    fun initialize(authToken: String) {
        try {
            // Set up the authorizer for private channels
            val authorizer = HttpAuthorizer("https://your-api.com/broadcasting/auth").apply {
                setHeaders(hashMapOf("Authorization" to "Bearer $authToken"))
            }
            
            // Configure Pusher options
            val options = PusherOptions().apply {
                setAuthorizer(authorizer)
                
                // Use your app's websocket server
                setHost("your-app-websocket-server.com")
                setWsPort(6001)
                setUseTLS(false)
                
                // Or if using Pusher.com service
                // setCluster("mt1")
            }
            
            // Create Pusher instance
            pusher = Pusher("pasabay-key", options)
            
            // Set up connection event listener
            pusher?.connection?.bind(object : ConnectionEventListener {
                override fun onConnectionStateChange(change: ConnectionStateChange) {
                    Log.d(TAG, "Connection state changed from ${change.previousState} to ${change.currentState}")
                    
                    if (change.currentState == ConnectionState.CONNECTED) {
                        // Reconnect to previously subscribed channels
                        resubscribeToChannels()
                    }
                }
                
                override fun onError(message: String, code: String?, e: Exception?) {
                    Log.e(TAG, "Connection error: $message, code: $code", e)
                }
            })
            
            // Connect
            pusher?.connect()
        } catch (e: Exception) {
            Log.e(TAG, "Error initializing Pusher", e)
        }
    }
    
    fun subscribeToPrivateChannel(channelName: String, userId: String) {
        try {
            val formattedChannelName = "private-trip.$userId"
            
            if (subscribedChannels.containsKey(formattedChannelName)) {
                return // Already subscribed
            }
            
            val channel = pusher?.subscribePrivate(formattedChannelName)
            
            channel?.bind("App\\Events\\NewDeliveryRequest", object : PrivateChannelEventListener {
                override fun onEvent(channelName: String, eventName: String, data: String) {
                    try {
                        Log.d(TAG, "Received event: $eventName on channel: $channelName with data: $data")
                        
                        val jsonObject = JSONObject(data)
                        val gson = Gson()
                        val payload = gson.fromJson(jsonObject.toString(), DeliveryRequestPayload::class.java)
                        
                        // Notify all registered listeners
                        notifyListeners(eventName, payload)
                    } catch (e: Exception) {
                        Log.e(TAG, "Error parsing event data", e)
                    }
                }
                
                override fun onAuthenticationFailure(message: String, e: Exception) {
                    Log.e(TAG, "Authentication failure: $message", e)
                }
                
                override fun onSubscriptionSucceeded(channelName: String) {
                    Log.d(TAG, "Successfully subscribed to channel: $channelName")
                    subscribedChannels[channelName] = true
                }
            })
        } catch (e: Exception) {
            Log.e(TAG, "Error subscribing to channel", e)
        }
    }
    
    fun unsubscribeFromChannel(channelName: String) {
        try {
            val formattedChannelName = "private-$channelName"
            pusher?.unsubscribe(formattedChannelName)
            subscribedChannels.remove(formattedChannelName)
        } catch (e: Exception) {
            Log.e(TAG, "Error unsubscribing from channel", e)
        }
    }
    
    fun registerListener(listener: PusherEventListener) {
        if (!listeners.contains(listener)) {
            listeners.add(listener)
        }
    }
    
    fun unregisterListener(listener: PusherEventListener) {
        listeners.remove(listener)
    }
    
    fun disconnect() {
        try {
            pusher?.disconnect()
            subscribedChannels.clear()
        } catch (e: Exception) {
            Log.e(TAG, "Error disconnecting Pusher", e)
        }
    }
    
    private fun resubscribeToChannels() {
        subscribedChannels.keys().toList().forEach { channelName ->
            val userId = channelName.split(".")[1]
            subscribeToPrivateChannel(channelName, userId)
        }
    }
    
    private fun notifyListeners(eventName: String, payload: DeliveryRequestPayload) {
        for (listener in listeners) {
            when (eventName) {
                "App\\Events\\NewDeliveryRequest" -> listener.onNewDeliveryRequest(payload)
                // Add more event types as needed
            }
        }
    }
    
    interface PusherEventListener {
        fun onNewDeliveryRequest(payload: DeliveryRequestPayload)
        // Add more callback methods for other events
    }
}
```

2. **Create the model class for delivery request payload**:

```kotlin
// DeliveryRequestPayload.kt
package com.yourcompany.pasabay.models

import com.google.gson.annotations.SerializedName

data class DeliveryRequestPayload(
    val id: Int,
    @SerializedName("item_description")
    val itemDescription: String,
    val sender: SenderInfo
)

data class SenderInfo(
    val id: Int,
    val name: String
)
```

3. **Initialize Pusher in your Application class**:

```kotlin
// PasabayApplication.kt
package com.yourcompany.pasabay

import android.app.Application
import com.yourcompany.pasabay.utils.PusherManager
import com.yourcompany.pasabay.utils.SessionManager

class PasabayApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        
        // Get auth token from your session manager
        val authToken = SessionManager(this).getAuthToken()
        
        // Initialize Pusher if user is logged in
        if (authToken.isNotEmpty()) {
            PusherManager.instance.initialize(authToken)
        }
    }
}
```

4. **Implement the event listener in your Activity or Fragment**:

```kotlin
// TripDetailActivity.kt
package com.yourcompany.pasabay.ui.activities

import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import com.yourcompany.pasabay.R
import com.yourcompany.pasabay.models.DeliveryRequestPayload
import com.yourcompany.pasabay.utils.PusherManager

class TripDetailActivity : AppCompatActivity(), PusherManager.PusherEventListener {
    private var tripId: Int = 0
    private var userId: String = ""
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_trip_detail)
        
        // Get trip ID from intent
        tripId = intent.getIntExtra("trip_id", 0)
        userId = intent.getStringExtra("user_id") ?: ""
        
        if (tripId > 0 && userId.isNotEmpty()) {
            // Register as listener
            PusherManager.instance.registerListener(this)
            
            // Subscribe to the channel
            PusherManager.instance.subscribeToPrivateChannel("trip.$tripId", userId)
        }
    }
    
    override fun onDestroy() {
        super.onDestroy()
        
        // Unregister listener and unsubscribe
        PusherManager.instance.unregisterListener(this)
        PusherManager.instance.unsubscribeFromChannel("trip.$tripId")
    }
    
    override fun onNewDeliveryRequest(payload: DeliveryRequestPayload) {
        runOnUiThread {
            // Show alert dialog
            AlertDialog.Builder(this)
                .setTitle("New Delivery Request")
                .setMessage("You received a new delivery request for ${payload.itemDescription} from ${payload.sender.name}")
                .setPositiveButton("View") { _, _ ->
                    // Navigate to request details
                    navigateToRequestDetails(payload.id)
                }
                .setNegativeButton("Dismiss", null)
                .show()
        }
    }
    
    private fun navigateToRequestDetails(requestId: Int) {
        // Implementation for navigation
    }
}
```

## Firebase Cloud Messaging

Implement push notifications in your Android app using Firebase Cloud Messaging (FCM).

### Prerequisites

- Firebase account
- Firebase project set up for your app

### Setup

1. **Add Firebase to your Android project**:
   - Go to the [Firebase Console](https://console.firebase.google.com/)
   - Create a new project or select an existing one
   - Register your app with your package name
   - Download the `google-services.json` file and place it in your app module directory

2. **Add Firebase dependencies**:

```gradle
// Project-level build.gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.3.15'
    }
}

// App-level build.gradle
dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.2.0')
    implementation 'com.google.firebase:firebase-messaging-ktx'
}

// Apply plugin
apply plugin: 'com.google.gms.google-services'
```

3. **Create a FirebaseMessagingService**:

```kotlin
// PasabayFirebaseMessagingService.kt
package com.yourcompany.pasabay.services

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.media.RingtoneManager
import android.os.Build
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.yourcompany.pasabay.R
import com.yourcompany.pasabay.ui.activities.MainActivity
import com.yourcompany.pasabay.ui.activities.RequestDetailActivity
import com.yourcompany.pasabay.utils.ApiClient
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class PasabayFirebaseMessagingService : FirebaseMessagingService() {
    companion object {
        private const val TAG = "FCMService"
        private const val CHANNEL_ID = "pasabay_notifications"
    }
    
    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        Log.d(TAG, "From: ${remoteMessage.from}")
        
        // Check if message contains data payload
        remoteMessage.data.isNotEmpty().let {
            Log.d(TAG, "Message data payload: ${remoteMessage.data}")
            
            val title = remoteMessage.data["title"] ?: "Pasabay"
            val message = remoteMessage.data["message"] ?: "You have a new notification"
            val requestId = remoteMessage.data["delivery_request_id"]?.toIntOrNull()
            
            sendNotification(title, message, requestId)
        }
        
        // Check if message contains notification payload
        remoteMessage.notification?.let {
            Log.d(TAG, "Message Notification Body: ${it.body}")
            sendNotification(it.title ?: "Pasabay", it.body ?: "You have a new notification")
        }
    }
    
    override fun onNewToken(token: String) {
        Log.d(TAG, "Refreshed token: $token")
        
        // Send the new token to your backend
        CoroutineScope(Dispatchers.IO).launch {
            try {
                ApiClient.instance.updateDeviceToken(token, "android")
            } catch (e: Exception) {
                Log.e(TAG, "Failed to update device token", e)
            }
        }
    }
    
    private fun sendNotification(title: String, messageBody: String, requestId: Int? = null) {
        // Create intent based on whether we have a request ID
        val intent = if (requestId != null) {
            Intent(this, RequestDetailActivity::class.java).apply {
                putExtra("request_id", requestId)
                addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP)
            }
        } else {
            Intent(this, MainActivity::class.java).apply {
                addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP)
            }
        }
        
        val pendingIntent = PendingIntent.getActivity(
            this, 0, intent,
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_ONE_SHOT
        )
        
        val defaultSoundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
        val notificationBuilder = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(messageBody)
            .setAutoCancel(true)
            .setSound(defaultSoundUri)
            .setContentIntent(pendingIntent)
        
        val notificationManager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        
        // Create notification channel for Android O and above
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Pasabay Notifications",
                NotificationManager.IMPORTANCE_DEFAULT
            ).apply {
                description = "Delivery request notifications"
            }
            notificationManager.createNotificationChannel(channel)
        }
        
        // Show notification
        val notificationId = System.currentTimeMillis().toInt()
        notificationManager.notify(notificationId, notificationBuilder.build())
    }
}
```

4. **Update your AndroidManifest.xml**:

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.yourcompany.pasabay">
    
    <!-- Permissions -->
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
    
    <application
        android:name=".PasabayApplication"
        ... >
        
        <!-- Firebase Messaging Service -->
        <service
            android:name=".services.PasabayFirebaseMessagingService"
            android:exported="false">
            <intent-filter>
                <action android:name="com.google.firebase.MESSAGING_EVENT" />
            </intent-filter>
        </service>
        
        <!-- Default notification icon -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_icon"
            android:resource="@drawable/ic_notification" />
            
        <!-- Default notification color -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_color"
            android:resource="@color/colorAccent" />
            
        <!-- Default notification channel ID -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_channel_id"
            android:value="@string/default_notification_channel_id" />
        
        <!-- Activities ... -->
        
    </application>
</manifest>
```

5. **Request notification permission on newer Android versions**:

```kotlin
// MainActivity.kt
import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat

class MainActivity : AppCompatActivity() {
    companion object {
        private const val NOTIFICATION_PERMISSION_REQUEST_CODE = 123
    }
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        
        // Request notification permission for Android 13+
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != 
                PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(
                    this,
                    arrayOf(Manifest.permission.POST_NOTIFICATIONS),
                    NOTIFICATION_PERMISSION_REQUEST_CODE
                )
            }
        }
    }
    
    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        
        if (requestCode == NOTIFICATION_PERMISSION_REQUEST_CODE) {
            if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                // Permission granted, we can send notifications
            } else {
                // Permission denied, show a message to the user explaining why notifications are important
            }
        }
    }
}
```

6. **Create an API client method to update device token**:

```kotlin
// ApiClient.kt (add this method)
suspend fun updateDeviceToken(token: String, deviceType: String) {
    val requestBody = mapOf(
        "device_token" to token,
        "device_type" to deviceType
    )
    
    apiService.updateDeviceToken(requestBody)
}

// ApiService.kt (add this interface method)
@POST("update-device-token")
suspend fun updateDeviceToken(@Body requestBody: Map<String, String>): Response<Unit>
```

## Deep Links

Implement deep links to handle email verification links and notifications.

### Setup

1. **Update your AndroidManifest.xml**:

```xml
<manifest ... >
    <application ... >
        <!-- Main Activity with deep link handling -->
        <activity
            android:name=".ui.activities.MainActivity"
            android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
            
            <!-- Custom URL scheme -->
            <intent-filter>
                <action android:name="android.intent.action.VIEW" />
                <category android:name="android.intent.category.DEFAULT" />
                <category android:name="android.intent.category.BROWSABLE" />
                <data android:scheme="pasabay" />
            </intent-filter>
            
            <!-- App Links (HTTP/HTTPS URLs) -->
            <intent-filter android:autoVerify="true">
                <action android:name="android.intent.action.VIEW" />
                <category android:name="android.intent.category.DEFAULT" />
                <category android:name="android.intent.category.BROWSABLE" />
                <data android:scheme="http" android:host="your-domain.com" />
                <data android:scheme="https" android:host="your-domain.com" />
            </intent-filter>
        </activity>
        <!-- ... -->
    </application>
</manifest>
```

2. **Create Digital Asset Links file**:
   - Create a file named `assetlinks.json` in your website's `.well-known` directory
   - Add the following content:

```json
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.yourcompany.pasabay",
    "sha256_cert_fingerprints": [
      "SHA256 fingerprint of your app's signing certificate"
    ]
  }
}]
```

3. **Handle deep links in your MainActivity**:

```kotlin
// MainActivity.kt
override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)
    setContentView(R.layout.activity_main)
    
    // Handle any deep links that launched the app
    intent?.data?.let { handleDeepLink(it) }
}

override fun onNewIntent(intent: Intent) {
    super.onNewIntent(intent)
    
    // Handle deep links that arrive when the app is already running
    intent.data?.let { handleDeepLink(it) }
}

private fun handleDeepLink(uri: Uri) {
    when {
        // Handle request details
        uri.path?.contains("/requests/") == true -> {
            val pathSegments = uri.pathSegments
            if (pathSegments.size >= 2 && pathSegments[0] == "requests") {
                try {
                    val requestId = pathSegments[1].toInt()
                    navigateToRequestDetail(requestId)
                } catch (e: NumberFormatException) {
                    Log.e("DeepLink", "Invalid request ID format: ${pathSegments[1]}")
                }
            }
        }
        
        // Handle email verification
        uri.path?.contains("/verify-email") == true -> {
            val token = uri.getQueryParameter("token")
            if (!token.isNullOrEmpty()) {
                verifyEmail(token)
            }
        }
        
        // Handle other deep links as needed
    }
}

private fun navigateToRequestDetail(requestId: Int) {
    val intent = Intent(this, RequestDetailActivity::class.java).apply {
        putExtra("request_id", requestId)
    }
    startActivity(intent)
}

private fun verifyEmail(token: String) {
    // Show a loading indicator
    val progressDialog = ProgressDialog(this).apply {
        setMessage("Verifying your email...")
        setCancelable(false)
        show()
    }
    
    // Call API to verify email
    CoroutineScope(Dispatchers.IO).launch {
        try {
            val response = ApiClient.instance.verifyEmail(token)
            
            withContext(Dispatchers.Main) {
                progressDialog.dismiss()
                
                if (response.isSuccessful) {
                    // Show success message
                    Toast.makeText(this@MainActivity, "Email verified successfully!", Toast.LENGTH_LONG).show()
                } else {
                    // Show error message
                    Toast.makeText(this@MainActivity, "Failed to verify email. Please try again.", Toast.LENGTH_LONG).show()
                }
            }
        } catch (e: Exception) {
            withContext(Dispatchers.Main) {
                progressDialog.dismiss()
                Toast.makeText(this@MainActivity, "Error: ${e.message}", Toast.LENGTH_LONG).show()
            }
        }
    }
}
```

## SMS Verification

Implement phone number verification using Twilio.

### Setup

1. **Add dependencies**:

```gradle
dependencies {
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
}
```

2. **Create a verification UI**:

```xml
<!-- activity_phone_verification.xml -->
<?xml version="1.0" encoding="utf-8"?>
<LinearLayout xmlns:android="http://schemas.android.com/apk/res/android"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    android:orientation="vertical"
    android:padding="16dp">

    <TextView
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Phone Verification"
        android:textSize="24sp"
        android:textStyle="bold"
        android:layout_marginBottom="24dp"/>

    <TextView
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Enter your phone number:"
        android:layout_marginBottom="8dp"/>

    <EditText
        android:id="@+id/phoneEditText"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:hint="+1234567890"
        android:inputType="phone"
        android:layout_marginBottom="16dp"/>

    <Button
        android:id="@+id/sendCodeButton"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Send Verification Code"
        android:layout_marginBottom="32dp"/>

    <TextView
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Enter verification code:"
        android:layout_marginBottom="8dp"/>

    <EditText
        android:id="@+id/codeEditText"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:hint="123456"
        android:inputType="number"
        android:enabled="false"
        android:layout_marginBottom="16dp"/>

    <Button
        android:id="@+id/verifyButton"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Verify"
        android:enabled="false"
        android:layout_marginBottom="16dp"/>

    <TextView
        android:id="@+id/statusTextView"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text=""
        android:textAlignment="center"
        android:layout_marginTop="16dp"/>

</LinearLayout>
```

3. **Create the verification activity**:

```kotlin
// PhoneVerificationActivity.kt
package com.yourcompany.pasabay.ui.activities

import android.app.ProgressDialog
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.yourcompany.pasabay.R
import com.yourcompany.pasabay.utils.ApiClient
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class PhoneVerificationActivity : AppCompatActivity() {
    
    private lateinit var phoneEditText: EditText
    private lateinit var codeEditText: EditText
    private lateinit var sendCodeButton: Button
    private lateinit var verifyButton: Button
    private lateinit var statusTextView: TextView
    
    private var verificationId: String? = null
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_phone_verification)
        
        // Initialize views
        phoneEditText = findViewById(R.id.phoneEditText)
        codeEditText = findViewById(R.id.codeEditText)
        sendCodeButton = findViewById(R.id.sendCodeButton)
        verifyButton = findViewById(R.id.verifyButton)
        statusTextView = findViewById(R.id.statusTextView)
        
        // Set click listeners
        sendCodeButton.setOnClickListener {
            val phoneNumber = phoneEditText.text.toString().trim()
            
            if (phoneNumber.isEmpty()) {
                Toast.makeText(this, "Please enter a valid phone number", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            
            requestVerificationCode(phoneNumber)
        }
        
        verifyButton.setOnClickListener {
            val code = codeEditText.text.toString().trim()
            
            if (code.isEmpty()) {
                Toast.makeText(this, "Please enter the verification code", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            
            verifyCode(code)
        }
    }
    
    private fun requestVerificationCode(phoneNumber: String) {
        val progressDialog = ProgressDialog(this).apply {
            setMessage("Sending verification code...")
            setCancelable(false)
            show()
        }
        
        sendCodeButton.isEnabled = false
        
        CoroutineScope(Dispatchers.IO).launch {
            try {
                val response = ApiClient.instance.requestVerificationCode(phoneNumber)
                
                withContext(Dispatchers.Main) {
                    progressDialog.dismiss()
                    sendCodeButton.isEnabled = true
                    
                    if (response.isSuccessful && response.body() != null) {
                        val result = response.body()!!
                        verificationId = result.verificationId
                        
                        // Enable verification fields
                        codeEditText.isEnabled = true
                        verifyButton.isEnabled = true
                        
                        statusTextView.text = "Verification code sent!"
                        Toast.makeText(this@PhoneVerificationActivity, "Code sent successfully", Toast.LENGTH_SHORT).show()
                    } else {
                        statusTextView.text = "Failed to send code: ${response.message()}"
                        Toast.makeText(this@PhoneVerificationActivity, "Failed to send code", Toast.LENGTH_SHORT).show()
                    }
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    progressDialog.dismiss()
                    sendCodeButton.isEnabled = true
                    statusTextView.text = "Error: ${e.message}"
                    Toast.makeText(this@PhoneVerificationActivity, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
                }
            }
        }
    }
    
    private fun verifyCode(code: String) {
        if (verificationId == null) {
            Toast.makeText(this, "Please request a verification code first", Toast.LENGTH_SHORT).show()
            return
        }
        
        val progressDialog = ProgressDialog(this).apply {
            setMessage("Verifying code...")
            setCancelable(false)
            show()
        }
        
        verifyButton.isEnabled = false
        
        CoroutineScope(Dispatchers.IO).launch {
            try {
                val response = ApiClient.instance.verifyCode(
                    verificationId = verificationId!!,
                    code = code
                )
                
                withContext(Dispatchers.Main) {
                    progressDialog.dismiss()
                    verifyButton.isEnabled = true
                    
                    if (response.isSuccessful) {
                        statusTextView.text = "Phone verified successfully!"
                        Toast.makeText(this@PhoneVerificationActivity, "Phone verified successfully!", Toast.LENGTH_LONG).show()
                        
                        // Update user profile or navigate back
                        setResult(RESULT_OK)
                        finish()
                    } else {
                        statusTextView.text = "Verification failed: ${response.message()}"
                        Toast.makeText(this@PhoneVerificationActivity, "Verification failed", Toast.LENGTH_SHORT).show()
                    }
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    progressDialog.dismiss()
                    verifyButton.isEnabled = true
                    statusTextView.text = "Error: ${e.message}"
                    Toast.makeText(this@PhoneVerificationActivity, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
                }
            }
        }
    }
}
```

4. **Add API methods**:

```kotlin
// ApiService.kt
@POST("request-verification-code")
suspend fun requestVerificationCode(@Body requestBody: Map<String, String>): Response<VerificationResponse>

@POST("verify-code")
suspend fun verifyCode(@Body requestBody: Map<String, String>): Response<Unit>

// ApiClient.kt
suspend fun requestVerificationCode(phoneNumber: String): Response<VerificationResponse> {
    val requestBody = mapOf("phone_number" to phoneNumber)
    return apiService.requestVerificationCode(requestBody)
}

suspend fun verifyCode(verificationId: String, code: String): Response<Unit> {
    val requestBody = mapOf(
        "verification_id" to verificationId,
        "code" to code
    )
    return apiService.verifyCode(requestBody)
}

// VerificationResponse.kt
data class VerificationResponse(
    @SerializedName("verification_id")
    val verificationId: String
)
```

## Testing

### Testing Pusher

Create a utility function to test Pusher connectivity:

```kotlin
// Testing in your activity or fragment
private fun testPusherConnection() {
    PusherManager.instance.registerListener(object : PusherManager.PusherEventListener {
        override fun onNewDeliveryRequest(payload: DeliveryRequestPayload) {
            Log.d("PusherTest", "Received delivery request: $payload")
            Toast.makeText(this@YourActivity, "Received push event!", Toast.LENGTH_SHORT).show()
        }
    })
    
    // Test by hitting your test endpoint
    CoroutineScope(Dispatchers.IO).launch {
        try {
            val response = ApiClient.instance.testPusherEvent()
            withContext(Dispatchers.Main) {
                Toast.makeText(this@YourActivity, "Test event sent!", Toast.LENGTH_SHORT).show()
            }
        } catch (e: Exception) {
            withContext(Dispatchers.Main) {
                Toast.makeText(this@YourActivity, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }
}
```

### Testing FCM

Create a test FCM message:

1. **In your backend Laravel application**:

```php
Route::get('/test-fcm/{token}', function ($token) {
    $notification = new \App\Notifications\DeliveryRequestStatusChanged(
        \App\Models\DeliveryRequest::first(),
        'accepted'
    );
    
    return $notification->toFcm($token);
});
```

2. **In your Android app**:

```kotlin
private fun logFCMToken() {
    FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
        if (task.isSuccessful) {
            val token = task.result
            Log.d("FCM", "Token: $token")
            
            // Copy to clipboard for testing
            val clipboard = getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
            val clip = ClipData.newPlainText("FCM Token", token)
            clipboard.setPrimaryClip(clip)
            
            Toast.makeText(this, "FCM Token copied to clipboard", Toast.LENGTH_SHORT).show()
        } else {
            Log.e("FCM", "Failed to get FCM token", task.exception)
        }
    }
}
```

### Debugging Guidelines

1. **Pusher issues**:
   - Check internet connectivity
   - Verify that authentication is properly set up
   - Check for correct channel names
   - Use Pusher's debug console for real-time monitoring

2. **FCM issues**:
   - Verify the correct setup of google-services.json
   - Check that the device has Google Play Services
   - Test with the Firebase console to send test messages
   - Inspect Logcat for Firebase-related logs

3. **Deep link issues**:
   - Test with adb: `adb shell am start -a android.intent.action.VIEW -d "https://your-domain.com/requests/123"`
   - Check the Digital Asset Links validation with the App Links Assistant in Android Studio
   - Verify your manifest intent filters 