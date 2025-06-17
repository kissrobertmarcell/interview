# Secret Server

REST API for sharing secrets securely with expiration rules.

## Features
- Create and retrieve secrets via API
- Automatic expiration after specified views
- Time-based expiration
- JSON and XML response support

## API Endpoints

### Create Secret
```bash
curl -X POST http://localhost:8000/api/secret \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: application/json" \
  -d "secret=my secret&expireAfterViews=2&expireAfter=5"
```

### Retrieve Secret
```bash
curl -H "Accept: application/json" http://localhost:8000/api/secret/{hash}
```

## Setup
1. Clone repository
2. Run `composer install`
3. Start server: `symfony server:start`

## Testing
```bash
php bin/phpunit --testdox
```