package com.pasabayan.navigation

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import com.pasabayan.ui.screens.delivery.DeliveryRequestScreen
import com.pasabayan.ui.screens.home.HomeScreen
import com.pasabayan.ui.screens.login.LoginScreen
import com.pasabayan.ui.screens.onboarding.OnboardingScreen
import com.pasabayan.ui.screens.profile.ProfileScreen
import com.pasabayan.ui.screens.registration.RegistrationScreen
import com.pasabayan.ui.screens.splash.SplashScreen
import com.pasabayan.ui.screens.trips.TripDetailsScreen
import com.pasabayan.ui.screens.trips.TripsScreen

/**
 * Routes for the application navigation
 */
object AppDestinations {
    const val SPLASH_ROUTE = "splash"
    const val ONBOARDING_ROUTE = "onboarding"
    const val LOGIN_ROUTE = "login"
    const val REGISTRATION_ROUTE = "registration"
    const val HOME_ROUTE = "home"
    const val PROFILE_ROUTE = "profile"
    const val TRIPS_ROUTE = "trips"
    const val TRIP_DETAILS_ROUTE = "trip_details"
    const val DELIVERY_REQUEST_ROUTE = "delivery_request"
}

/**
 * Navigation actions for the app
 */
class AppNavigationActions(private val navController: NavHostController) {
    
    fun navigateToRoute(route: String) {
        navController.navigate(route) {
            // Pop up to the start destination of the graph to
            // avoid building up a large stack of destinations
            popUpTo(navController.graph.findStartDestination().id) {
                saveState = true
            }
            // Avoid duplicate destinations
            launchSingleTop = true
            // Restore state when navigating back
            restoreState = true
        }
    }
    
    fun navigateToHome() = navigateToRoute(AppDestinations.HOME_ROUTE)
    fun navigateToLogin() = navigateToRoute(AppDestinations.LOGIN_ROUTE)
    fun navigateToRegistration() = navigateToRoute(AppDestinations.REGISTRATION_ROUTE)
    fun navigateToProfile() = navigateToRoute(AppDestinations.PROFILE_ROUTE)
    fun navigateToTrips() = navigateToRoute(AppDestinations.TRIPS_ROUTE)
    
    fun navigateToTripDetails(tripId: String) {
        navController.navigate("${AppDestinations.TRIP_DETAILS_ROUTE}/$tripId")
    }
    
    fun navigateToDeliveryRequest() = navigateToRoute(AppDestinations.DELIVERY_REQUEST_ROUTE)
    
    fun navigateBack() {
        navController.popBackStack()
    }
}

/**
 * Main navigation component for the application
 */
@Composable
fun AppNavHost(
    navController: NavHostController,
    modifier: Modifier = Modifier,
    startDestination: String = AppDestinations.SPLASH_ROUTE
) {
    val navigationActions = AppNavigationActions(navController)
    
    NavHost(
        navController = navController,
        startDestination = startDestination,
        modifier = modifier
    ) {
        composable(AppDestinations.SPLASH_ROUTE) {
            SplashScreen(onNavigateToOnboarding = { 
                navController.navigate(AppDestinations.ONBOARDING_ROUTE) {
                    popUpTo(AppDestinations.SPLASH_ROUTE) { inclusive = true }
                }
            })
        }
        
        composable(AppDestinations.ONBOARDING_ROUTE) {
            OnboardingScreen(
                onNavigateToLogin = navigationActions::navigateToLogin,
                onNavigateToRegistration = navigationActions::navigateToRegistration
            )
        }
        
        composable(AppDestinations.LOGIN_ROUTE) {
            LoginScreen(
                onLoginSuccess = navigationActions::navigateToHome,
                onNavigateToRegistration = navigationActions::navigateToRegistration
            )
        }
        
        composable(AppDestinations.REGISTRATION_ROUTE) {
            RegistrationScreen(
                onRegistrationSuccess = navigationActions::navigateToHome,
                onNavigateToLogin = navigationActions::navigateToLogin
            )
        }
        
        composable(AppDestinations.HOME_ROUTE) {
            HomeScreen(
                onNavigateToProfile = navigationActions::navigateToProfile,
                onNavigateToTrips = navigationActions::navigateToTrips,
                onNavigateToDeliveryRequest = navigationActions::navigateToDeliveryRequest
            )
        }
        
        composable(AppDestinations.PROFILE_ROUTE) {
            ProfileScreen(
                onNavigateBack = navigationActions::navigateBack
            )
        }
        
        composable(AppDestinations.TRIPS_ROUTE) {
            TripsScreen(
                onNavigateToTripDetails = navigationActions::navigateToTripDetails,
                onNavigateBack = navigationActions::navigateBack
            )
        }
        
        composable("${AppDestinations.TRIP_DETAILS_ROUTE}/{tripId}") { backStackEntry ->
            val tripId = backStackEntry.arguments?.getString("tripId") ?: ""
            TripDetailsScreen(
                tripId = tripId,
                onNavigateBack = navigationActions::navigateBack
            )
        }
        
        composable(AppDestinations.DELIVERY_REQUEST_ROUTE) {
            DeliveryRequestScreen(
                onRequestSubmitted = navigationActions::navigateToHome,
                onNavigateBack = navigationActions::navigateBack
            )
        }
    }
} 