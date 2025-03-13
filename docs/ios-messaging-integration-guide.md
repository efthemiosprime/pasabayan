# Pasabay iOS Messaging & Notifications Integration Guide

This guide provides step-by-step instructions for implementing real-time features and notifications in the Pasabay iOS app. We'll cover:

1. [Pusher Integration](#pusher-integration)
2. [Push Notifications](#push-notifications)
3. [Email Deep Links](#email-deep-links)
4. [SMS Verification](#sms-verification)

## Pusher Integration

Pusher allows your iOS app to receive real-time updates from the Pasabay backend.

### Prerequisites

- Xcode 14.0+
- iOS 14.0+
- Swift 5.0+
- CocoaPods or Swift Package Manager

### Installation Steps

#### Using CocoaPods

1. **Add Pusher to your Podfile**:

```ruby
target 'PasabayApp' do
  pod 'PusherSwift', '~> 10.1.0'
end
```

2. **Install the dependency**:

```bash
pod install
```

#### Using Swift Package Manager

1. **Add Pusher as a dependency in Xcode**:
   - Select your project in the Project Navigator
   - Select your app target under "Targets"
   - Select "Swift Packages"
   - Click the "+" button
   - Enter package URL: `https://github.com/pusher/pusher-websocket-swift`
   - Select version: "Up to Next Major" with version "10.1.0"

### Configuration

1. **Create a PusherManager class**:

```swift
import Foundation
import PusherSwift

class PusherManager {
    static let shared = PusherManager()
    
    private var pusher: Pusher?
    private var channelSubscriptions: [String: PusherChannel] = [:]
    
    private init() {}
    
    func configure() {
        // Use the same values from your Laravel .env file
        let options = PusherClientOptions(
            host: .host("your-app-websocket-server.com") // Or use cluster if using Pusher.com
        )
        
        pusher = Pusher(
            key: "pasabay-key", // Same as PUSHER_APP_KEY
            options: options
        )
        
        pusher?.connection.delegate = self
        pusher?.connect()
    }
    
    func subscribeToPrivateChannel(channelName: String, userId: String, authToken: String) {
        guard let pusher = pusher else { return }
        
        // Setup auth
        pusher.connection.options.authMethod = .authRequestBuilder(authRequestBuilder: { channelName, socketId in
            var request = URLRequest(url: URL(string: "https://your-api.com/broadcasting/auth")!)
            request.httpMethod = "POST"
            request.addValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
            request.addValue("application/json", forHTTPHeaderField: "Content-Type")
            
            let params = [
                "socket_id": socketId,
                "channel_name": channelName
            ]
            request.httpBody = try? JSONSerialization.data(withJSONObject: params)
            
            return request
        })
        
        // Subscribe to channel
        let channel = pusher.subscribe("private-trip.\(userId)")
        channelSubscriptions[channelName] = channel
        
        // Listen for specific events
        channel.bind(eventName: "App\\Events\\NewDeliveryRequest") { data in
            guard let dataString = data as? String,
                  let jsonData = dataString.data(using: .utf8),
                  let requestData = try? JSONDecoder().decode(DeliveryRequestPayload.self, from: jsonData) else {
                return
            }
            
            // Handle the delivery request data
            NotificationCenter.default.post(
                name: .newDeliveryRequestReceived,
                object: nil,
                userInfo: ["requestData": requestData]
            )
        }
    }
    
    func unsubscribe(channelName: String) {
        guard let pusher = pusher, channelSubscriptions[channelName] != nil else { return }
        
        pusher.unsubscribe(channelName)
        channelSubscriptions.removeValue(forKey: channelName)
    }
}

// Example payload model
struct DeliveryRequestPayload: Codable {
    let id: Int
    let itemDescription: String
    let sender: SenderInfo
    
    enum CodingKeys: String, CodingKey {
        case id
        case itemDescription = "item_description"
        case sender
    }
}

struct SenderInfo: Codable {
    let id: Int
    let name: String
}

// Notification name extension
extension Notification.Name {
    static let newDeliveryRequestReceived = Notification.Name("newDeliveryRequestReceived")
}
```

2. **Initialize PusherManager in your AppDelegate**:

```swift
import UIKit

@main
class AppDelegate: UIResponder, UIApplicationDelegate {
    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {
        
        // Initialize Pusher
        PusherManager.shared.configure()
        
        return true
    }
}
```

3. **Subscribe to channels in your view controller**:

```swift
import UIKit

class TripDetailViewController: UIViewController {
    var tripId: Int = 0
    var authToken: String = ""
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Subscribe to channel when view loads
        PusherManager.shared.subscribeToPrivateChannel(
            channelName: "private-trip.\(tripId)",
            userId: UserManager.shared.currentUserId,
            authToken: authToken
        )
        
        // Listen for notification
        NotificationCenter.default.addObserver(
            self,
            selector: #selector(handleNewDeliveryRequest),
            name: .newDeliveryRequestReceived,
            object: nil
        )
    }
    
    override func viewDidDisappear(_ animated: Bool) {
        super.viewDidDisappear(animated)
        
        // Unsubscribe when view disappears
        PusherManager.shared.unsubscribe(channelName: "private-trip.\(tripId)")
        
        // Remove observer
        NotificationCenter.default.removeObserver(self, name: .newDeliveryRequestReceived, object: nil)
    }
    
    @objc private func handleNewDeliveryRequest(notification: Notification) {
        guard let requestData = notification.userInfo?["requestData"] as? DeliveryRequestPayload else {
            return
        }
        
        // Update UI with new request
        DispatchQueue.main.async {
            // Show alert or update UI component
            let alert = UIAlertController(
                title: "New Delivery Request",
                message: "You received a new delivery request for \(requestData.itemDescription) from \(requestData.sender.name)",
                preferredStyle: .alert
            )
            
            alert.addAction(UIAlertAction(title: "View", style: .default) { _ in
                // Navigate to request details
                self.navigateToRequestDetails(requestId: requestData.id)
            })
            
            alert.addAction(UIAlertAction(title: "Dismiss", style: .cancel))
            
            self.present(alert, animated: true)
        }
    }
    
    private func navigateToRequestDetails(requestId: Int) {
        // Implementation for navigation
    }
}
```

## Push Notifications

### Setup Push Notifications in iOS

1. **Configure APNs in Xcode**:
   - Open your project settings
   - Select your target
   - Go to "Signing & Capabilities"
   - Click "+" and add "Push Notifications"
   - Also add "Background Modes" and enable "Remote notifications"

2. **Generate APNs Certificate**:
   - Go to [Apple Developer Portal](https://developer.apple.com)
   - Go to "Certificates, Identifiers & Profiles"
   - Create a new certificate for APNs
   - Download and install the certificate

3. **Update your backend with the APNs certificate**:
   - Add the certificate to your Laravel project
   - Configure in `config/services.php`

4. **Register for push notifications in AppDelegate**:

```swift
import UIKit
import UserNotifications

@main
class AppDelegate: UIResponder, UIApplicationDelegate {
    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {
        
        // Initialize Pusher
        PusherManager.shared.configure()
        
        // Request notification permissions
        UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .sound, .badge]) { granted, error in
            if granted {
                DispatchQueue.main.async {
                    application.registerForRemoteNotifications()
                }
            }
        }
        
        return true
    }
    
    func application(_ application: UIApplication, didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
        let tokenParts = deviceToken.map { data in String(format: "%02.2hhx", data) }
        let token = tokenParts.joined()
        
        // Send token to your backend
        ApiClient.shared.updateDeviceToken(token: token) { result in
            switch result {
            case .success:
                print("Device token updated successfully")
            case .failure(let error):
                print("Failed to update device token: \(error)")
            }
        }
    }
    
    func application(_ application: UIApplication, didFailToRegisterForRemoteNotificationsWithError error: Error) {
        print("Failed to register for remote notifications: \(error)")
    }
    
    func application(_ application: UIApplication, didReceiveRemoteNotification userInfo: [AnyHashable : Any], fetchCompletionHandler completionHandler: @escaping (UIBackgroundFetchResult) -> Void) {
        // Handle notification when app is in background
        
        if let aps = userInfo["aps"] as? [String: Any],
           let alert = aps["alert"] as? [String: Any],
           let body = alert["body"] as? String {
            print("Received notification: \(body)")
            
            // Process notification data
            if let requestId = userInfo["delivery_request_id"] as? Int {
                // Store for later use
                NotificationDataStore.shared.lastReceivedRequestId = requestId
            }
        }
        
        completionHandler(.newData)
    }
}

// API Client for updating device token
class ApiClient {
    static let shared = ApiClient()
    
    func updateDeviceToken(token: String, completion: @escaping (Result<Void, Error>) -> Void) {
        guard let url = URL(string: "https://your-api.com/api/update-device-token") else {
            completion(.failure(NSError(domain: "InvalidURL", code: 0)))
            return
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.addValue("application/json", forHTTPHeaderField: "Content-Type")
        request.addValue("Bearer \(UserManager.shared.authToken)", forHTTPHeaderField: "Authorization")
        
        let parameters = ["device_token": token, "device_type": "ios"]
        
        do {
            request.httpBody = try JSONSerialization.data(withJSONObject: parameters)
        } catch {
            completion(.failure(error))
            return
        }
        
        URLSession.shared.dataTask(with: request) { _, response, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            
            guard let httpResponse = response as? HTTPURLResponse,
                  (200...299).contains(httpResponse.statusCode) else {
                completion(.failure(NSError(domain: "HTTPError", code: 0)))
                return
            }
            
            completion(.success(()))
        }.resume()
    }
}

// Store for notification data
class NotificationDataStore {
    static let shared = NotificationDataStore()
    
    var lastReceivedRequestId: Int?
}
```

5. **Handle notification tap in SceneDelegate**:

```swift
func scene(_ scene: UIScene, willConnectTo session: UISceneSession, options connectionOptions: UIScene.ConnectionOptions) {
    // Handle notification if app was launched from notification
    if let userInfo = connectionOptions.notificationResponse?.notification.request.content.userInfo,
       let requestId = userInfo["delivery_request_id"] as? Int {
        // Navigate to the specific request detail page
        navigateToRequestDetail(requestId: requestId)
    }
}

func navigateToRequestDetail(requestId: Int) {
    guard let windowScene = UIApplication.shared.connectedScenes.first as? UIWindowScene,
          let window = windowScene.windows.first,
          let rootViewController = window.rootViewController as? UINavigationController else {
        return
    }
    
    // Reset to home if needed
    if !(rootViewController.viewControllers.first is HomeViewController) {
        let homeVC = HomeViewController()
        rootViewController.setViewControllers([homeVC], animated: false)
    }
    
    // Push request detail
    let requestDetailVC = RequestDetailViewController()
    requestDetailVC.requestId = requestId
    rootViewController.pushViewController(requestDetailVC, animated: true)
}
```

## Email Deep Links

Implement deep links to handle email verification and notifications.

1. **Configure URL Scheme**:
   - Open your project settings
   - Select your target
   - Go to "Info"
   - Expand "URL Types"
   - Add a new URL Type with:
     - Identifier: com.yourdomain.pasabay
     - URL Schemes: pasabay

2. **Create Associated Domains file** (for universal links):
   - Go to "Signing & Capabilities"
   - Add "Associated Domains"
   - Add: `applinks:your-domain.com`

3. **Handle deep links in SceneDelegate**:

```swift
func scene(_ scene: UIScene, openURLContexts URLContexts: Set<UIOpenURLContext>) {
    guard let url = URLContexts.first?.url else { return }
    
    handleDeepLink(url: url)
}

func scene(_ scene: UIScene, continue userActivity: NSUserActivity) {
    if userActivity.activityType == NSUserActivityTypeBrowsingWeb,
       let url = userActivity.webpageURL {
        handleDeepLink(url: url)
    }
}

private func handleDeepLink(url: URL) {
    guard let components = URLComponents(url: url, resolvingAgainstBaseURL: true) else { return }
    
    // Handle different paths
    if components.path.contains("/requests/") {
        let pathComponents = components.path.components(separatedBy: "/")
        if pathComponents.count >= 3, let requestIdString = pathComponents[2], let requestId = Int(requestIdString) {
            navigateToRequestDetail(requestId: requestId)
        }
    } else if components.path.contains("/verify-email") {
        // Handle email verification
        if let queryItems = components.queryItems,
           let token = queryItems.first(where: { $0.name == "token" })?.value {
            verifyEmail(token: token)
        }
    }
}

private func verifyEmail(token: String) {
    // Implement email verification
    ApiClient.shared.verifyEmail(token: token) { result in
        switch result {
        case .success:
            // Show success alert
            break
        case .failure:
            // Show error alert
            break
        }
    }
}
```

## SMS Verification

Implement phone number verification using Twilio.

1. **Create a phone verification screen**:

```swift
import UIKit

class PhoneVerificationViewController: UIViewController {
    // UI Elements
    private let phoneTextField = UITextField()
    private let sendCodeButton = UIButton()
    private let codeTextField = UITextField()
    private let verifyButton = UIButton()
    private let statusLabel = UILabel()
    
    // Properties
    private var verificationId: String?
    
    override func viewDidLoad() {
        super.viewDidLoad()
        setupUI()
    }
    
    private func setupUI() {
        title = "Phone Verification"
        view.backgroundColor = .systemBackground
        
        // Setup text fields, buttons, and constraints
        // ...
        
        sendCodeButton.addTarget(self, action: #selector(sendCodeTapped), for: .touchUpInside)
        verifyButton.addTarget(self, action: #selector(verifyCodeTapped), for: .touchUpInside)
    }
    
    @objc private func sendCodeTapped() {
        guard let phoneNumber = phoneTextField.text, !phoneNumber.isEmpty else {
            statusLabel.text = "Please enter a valid phone number"
            return
        }
        
        // Show loading
        let activityIndicator = UIActivityIndicatorView(style: .medium)
        activityIndicator.startAnimating()
        sendCodeButton.isEnabled = false
        
        // Request verification code
        ApiClient.shared.requestVerificationCode(phoneNumber: phoneNumber) { [weak self] result in
            DispatchQueue.main.async {
                activityIndicator.stopAnimating()
                self?.sendCodeButton.isEnabled = true
                
                switch result {
                case .success(let verificationId):
                    self?.verificationId = verificationId
                    self?.statusLabel.text = "Verification code sent!"
                    self?.codeTextField.isEnabled = true
                    self?.verifyButton.isEnabled = true
                case .failure(let error):
                    self?.statusLabel.text = "Failed to send code: \(error.localizedDescription)"
                }
            }
        }
    }
    
    @objc private func verifyCodeTapped() {
        guard let code = codeTextField.text, !code.isEmpty,
              let verificationId = verificationId else {
            statusLabel.text = "Please enter the verification code"
            return
        }
        
        // Show loading
        let activityIndicator = UIActivityIndicatorView(style: .medium)
        activityIndicator.startAnimating()
        verifyButton.isEnabled = false
        
        // Verify code
        ApiClient.shared.verifyCode(verificationId: verificationId, code: code) { [weak self] result in
            DispatchQueue.main.async {
                activityIndicator.stopAnimating()
                self?.verifyButton.isEnabled = true
                
                switch result {
                case .success:
                    self?.statusLabel.text = "Phone number verified successfully!"
                    // Navigate back or to next screen
                    self?.navigationController?.popViewController(animated: true)
                case .failure(let error):
                    self?.statusLabel.text = "Failed to verify code: \(error.localizedDescription)"
                }
            }
        }
    }
}

// API Client extension for SMS verification
extension ApiClient {
    func requestVerificationCode(phoneNumber: String, completion: @escaping (Result<String, Error>) -> Void) {
        guard let url = URL(string: "https://your-api.com/api/request-verification-code") else {
            completion(.failure(NSError(domain: "InvalidURL", code: 0)))
            return
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.addValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let parameters = ["phone_number": phoneNumber]
        
        do {
            request.httpBody = try JSONSerialization.data(withJSONObject: parameters)
        } catch {
            completion(.failure(error))
            return
        }
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            
            guard let data = data else {
                completion(.failure(NSError(domain: "NoData", code: 0)))
                return
            }
            
            do {
                if let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
                   let verificationId = json["verification_id"] as? String {
                    completion(.success(verificationId))
                } else {
                    completion(.failure(NSError(domain: "InvalidResponse", code: 0)))
                }
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
    
    func verifyCode(verificationId: String, code: String, completion: @escaping (Result<Void, Error>) -> Void) {
        guard let url = URL(string: "https://your-api.com/api/verify-code") else {
            completion(.failure(NSError(domain: "InvalidURL", code: 0)))
            return
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.addValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let parameters = [
            "verification_id": verificationId,
            "code": code
        ]
        
        do {
            request.httpBody = try JSONSerialization.data(withJSONObject: parameters)
        } catch {
            completion(.failure(error))
            return
        }
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            
            guard let httpResponse = response as? HTTPURLResponse,
                  (200...299).contains(httpResponse.statusCode) else {
                completion(.failure(NSError(domain: "HTTPError", code: 0)))
                return
            }
            
            completion(.success(()))
        }.resume()
    }
}
```

## Testing

### Testing Pusher Integration

1. **Create a test environment**:
   - Create a development scheme in Xcode
   - Use development Pusher credentials

2. **Debug with Pusher console**:
   - Add debug logging:

```swift
pusher?.connection.options.logger = PusherLogger(debugLevel: .debug)
```

### Testing Push Notifications

1. **Test locally**:
   - Use the "Push Notification" tool in Xcode
   - Devices > Your Device > Push Notification

2. **Test with your backend**:
   - Create a test endpoint in your Laravel app:

```php
Route::get('/test-push/{deviceToken}', function ($deviceToken) {
    $notification = new \App\Notifications\DeliveryRequestStatusChanged(
        \App\Models\DeliveryRequest::first(),
        'accepted'
    );
    
    $notification->toApns($deviceToken);
    
    return 'Push notification sent!';
});
```

3. **Debug notification handling**:
   - Add breakpoints in notification handling methods
   - Check console logs 