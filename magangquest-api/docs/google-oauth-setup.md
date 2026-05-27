# Google OAuth Setup Guide

This guide walks you through obtaining Google OAuth credentials for the MagangQuest application.

## Prerequisites

- A Google account
- Access to the [Google Cloud Console](https://console.cloud.google.com/)

## Step-by-Step Instructions

### 1. Create a New Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **"Select a project"** at the top navigation bar
3. Click **"New Project"**
4. Enter a project name (e.g., "MagangQuest")
5. Click **"Create"**

### 2. Enable the Google+ API

1. In the sidebar, go to **"APIs & Services"** > **"Library"**
2. Search for **"Google+ API"** or **"Identity"**
3. Click on the API and click **"Enable"**

### 3. Configure OAuth Consent Screen

1. Go to **"APIs & Services"** > **"OAuth consent screen"**
2. Select **"External"** and click **"Create"**
3. Fill in the required fields:
   - App name: `MagangQuest`
   - User support email: Your email
   - Developer contact information: Your email
4. Click **"Save and Continue"**
5. On Scopes page, click **"Add or Remove Scopes"**
6. Select these scopes:
   - `../auth/userinfo.email`
   - `../auth/userinfo.profile`
   - `../auth/openid`
7. Click **"Save and Continue"**
8. Add test users (your Google email for testing)
9. Click **"Save and Continue"**

### 4. Create OAuth Credentials

1. Go to **"APIs & Services"** > **"Credentials"**
2. Click **"Create Credentials"** > **"OAuth client ID"**
3. Application type: **"Web application"**
4. Name: `MagangQuest Web Client`
5. **Authorized JavaScript origins**:
   - `http://localhost` (for local development)
   - `http://localhost:8000`
6. **Authorized redirect URIs**:
   - `http://localhost:8000/auth/google/callback` (for local development)
   - `http://localhost:3000/auth/google/callback` (if using Vite dev server)
   - Add your production URL when deploying
7. Click **"Create"**
8. Copy the **Client ID** and **Client Secret**

### 5. Add Credentials to .env

Add these lines to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-client-id-here
GOOGLE_CLIENT_SECRET=your-client-secret-here
```

### 6. (Optional) Verify Your Domain

If deploying to production, you need to verify your domain in **"APIs & Services"** > **"OAuth consent screen"** by adding your domain to authorized domains.

## Testing the Integration

1. Start your Laravel development server
2. Navigate to the login page
3. Click "Login with Google"
4. You should be redirected to Google's consent screen
5. After approving, you should be redirected back to the application

## Troubleshooting

### "This app isn't verified" warning
- Add your email as a test user in the OAuth consent screen settings
- For production, submit your app for Google verification

### Redirect URI mismatch
- Ensure the redirect URI in Google Cloud Console matches exactly with your application
- Check for trailing slashes and protocol (http vs https)

### Scope errors
- Ensure all required scopes are added to your OAuth consent screen
