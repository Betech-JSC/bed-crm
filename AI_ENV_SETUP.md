# AI & Social Media Environment Variables Setup

## AI Services Configuration

### OpenAI
```env
OPENAI_API_KEY=sk-...
```
Get your API key from: https://platform.openai.com/api-keys

### Anthropic Claude
```env
ANTHROPIC_API_KEY=sk-ant-...
```
Get your API key from: https://console.anthropic.com/settings/keys

### Default AI Service
```env
AI_DEFAULT_SERVICE=openai
```
Options: `openai` or `claude`

## Social Media OAuth Configuration

### LinkedIn
1. Go to: https://www.linkedin.com/developers/apps
2. Create a new app
3. Add redirect URI: `http://localhost:8000/social-accounts/linkedin/callback`
4. Request permissions: `r_liteprofile`, `r_emailaddress`, `w_member_social`
5. Copy Client ID and Client Secret

```env
LINKEDIN_CLIENT_ID=your_client_id
LINKEDIN_CLIENT_SECRET=your_client_secret
LINKEDIN_REDIRECT_URI=http://localhost:8000/social-accounts/linkedin/callback
```

### Twitter (X)
1. Go to: https://developer.twitter.com/en/portal/dashboard
2. Create a new app
3. Enable OAuth 2.0
4. Add callback URL: `http://localhost:8000/social-accounts/twitter/callback`
5. Request scopes: `tweet.read`, `tweet.write`, `users.read`
6. Copy Client ID and Client Secret

```env
TWITTER_CLIENT_ID=your_client_id
TWITTER_CLIENT_SECRET=your_client_secret
TWITTER_REDIRECT_URI=http://localhost:8000/social-accounts/twitter/callback
```

### Facebook
1. Go to: https://developers.facebook.com/apps/
2. Create a new app
3. Add Facebook Login product
4. Add redirect URI: `http://localhost:8000/social-accounts/facebook/callback`
5. Request permissions: `pages_manage_posts`, `pages_read_engagement`
6. Copy App ID and App Secret

```env
FACEBOOK_CLIENT_ID=your_app_id
FACEBOOK_CLIENT_SECRET=your_app_secret
FACEBOOK_REDIRECT_URI=http://localhost:8000/social-accounts/facebook/callback
```

### Instagram
1. Go to: https://developers.facebook.com/apps/
2. Create a new app or use existing Facebook app
3. Add Instagram Basic Display or Instagram Graph API
4. Add redirect URI: `http://localhost:8000/social-accounts/instagram/callback`
5. Request permissions: `user_profile`, `user_media`
6. Copy Client ID and Client Secret

```env
INSTAGRAM_CLIENT_ID=your_client_id
INSTAGRAM_CLIENT_SECRET=your_client_secret
INSTAGRAM_REDIRECT_URI=http://localhost:8000/social-accounts/instagram/callback
```

## Production Setup

For production, update the redirect URIs to your production domain:
- Replace `http://localhost:8000` with your production URL
- Ensure HTTPS is enabled
- Update OAuth apps with production redirect URIs

## Notes

- Keep your API keys and secrets secure
- Never commit `.env` file to version control
- Use different credentials for development and production
- Rotate keys regularly for security



