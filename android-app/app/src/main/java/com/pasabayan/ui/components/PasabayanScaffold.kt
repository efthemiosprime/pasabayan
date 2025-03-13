package com.pasabayan.ui.components

import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.RowScope
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.navigation.NavHostController

/**
 * Main scaffold for the app
 */
@Composable
fun PasabayanScaffold(
    navController: NavHostController,
    showBottomBar: Boolean = true,
    showTopBar: Boolean = true,
    topBarTitle: String = "Pasabayan",
    canNavigateBack: Boolean = false,
    onNavigateBack: () -> Unit = {},
    topBarActions: @Composable (RowScope.() -> Unit) = {},
    modifier: Modifier = Modifier,
    content: @Composable (PaddingValues) -> Unit
) {
    Scaffold(
        topBar = {
            if (showTopBar) {
                PasabayanTopAppBar(
                    title = topBarTitle,
                    canNavigateBack = canNavigateBack,
                    onNavigateBack = onNavigateBack,
                    actions = topBarActions
                )
            }
        },
        bottomBar = {
            if (showBottomBar) {
                PasabayanBottomNavBar(navController = navController)
            }
        },
        modifier = modifier
    ) { innerPadding ->
        content(innerPadding)
    }
} 