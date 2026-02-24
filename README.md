# Logto PHP Sample

This is a minimal PHP application demonstrating the Logto SDK.

## Setup

1. Copy `.env.example` to `.env` and populate with your Logto application credentials:
   ```
   LOGTO_ENDPOINT=https://id.your-domain.com/
   LOGTO_APP_ID=...
   LOGTO_APP_SECRET=...
   ```

2. Install dependencies via Composer:
   ```sh
   composer install
   ```

3. Start a PHP built-in server:
   ```sh
   php -S localhost:8080
   ```

4. Browse to `http://localhost:8080` and follow the sign-in links.

## Files

- `index.php` – main entrypoint demonstrating sign-in/out and userinfo display
- `.env` – environment variables (not committed)
- `composer.json` – project dependencies
- `vendor/` – dependencies installed by Composer

## Notes

- Make sure the redirect URI `http://localhost:8080/sign-in-callback` is registered
  in your Logto application settings; otherwise you'll see an `invalid_redirect_uri` error.
- SSL verification is disabled in this sample for convenience; enable it in production.
