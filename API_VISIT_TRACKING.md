# Agent Visit Tracking API

## Overview

The visit tracking API allows you to track user visits to your website or application using agent referral codes. This helps monitor traffic and engagement from different marketing channels. The API now supports both REST endpoints and cross-domain tracking pixels.

## API Version

```
GET /api/agents/track/version
```

### Version Response

```json
{
    "success": true,
    "data": {
        "version": "1.0.0",
        "name": "Penurwill Agent Tracking API",
        "description": "API for tracking agent referrals, visits, and sales",
        "endpoints": {
            "track_referral": "POST /api/agents/track/referral",
            "track_visit": "POST /api/agents/track/visit",
            "track_sale": "POST /api/agents/track/sale",
            "get_referral_info": "GET /api/agents/track/code/{code}",
            "get_version": "GET /api/agents/track/version"
        },
        "features": {
            "cross_domain_tracking": true,
            "pixel_tracking": true,
            "session_tracking": true,
            "activity_logging": true
        },
        "timestamp": "2025-07-20T10:30:00.000000Z"
    }
}
```

## REST API Endpoints

### Track Visit (REST)

```
POST /api/agents/track/visit
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `referral_code` | string | Yes | The agent's referral code |
| `visit_url` | string | Yes | The URL that was visited (must be a valid URL) |
| `visit_time` | string | Yes | ISO 8601 timestamp of when the visit occurred |
| `referral_page` | string | No | The referring page URL |
| `session_id` | string | No | Unique session identifier |
| `page_title` | string | No | Title of the visited page |
| `user_agent` | string | No | Browser user agent string |
| `screen_resolution` | string | No | Screen resolution (e.g., "1920x1080") |
| `language` | string | No | Browser language (e.g., "en-US") |
| `timezone` | string | No | User's timezone (e.g., "America/New_York") |

#### Example Request

```javascript
fetch('/api/agents/track/visit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        referral_code: 'AGENT123',
        visit_url: 'https://example.com/product/123',
        visit_time: '2025-07-20T10:30:00Z',
        referral_page: 'https://example.com/landing',
        session_id: 'session_abc123',
        page_title: 'Product Details',
        screen_resolution: '1920x1080',
        language: 'en-US',
        timezone: 'America/New_York'
    })
});
```

## Cross-Domain Tracking Pixel

### Track Visit (Pixel)

```
GET /api/pixel/track
```

The tracking pixel allows cross-domain tracking without CORS issues. It returns a 1x1 transparent GIF image.

#### Query Parameters

| Parameter | Short | Required | Description |
|-----------|-------|----------|-------------|
| `referral_code` | `rc` | Yes | The agent's referral code |
| `visit_url` | `url` | Yes | The URL that was visited |
| `visit_time` | `t` | No | ISO 8601 timestamp (defaults to current time) |
| `referral_page` | `ref` | No | The referring page URL |
| `session_id` | `sid` | No | Unique session identifier |
| `page_title` | `title` | No | Title of the visited page |
| `user_agent` | `ua` | No | Browser user agent string |
| `screen_resolution` | `sr` | No | Screen resolution |
| `language` | `lang` | No | Browser language |
| `timezone` | `tz` | No | User's timezone |

#### Example Usage

```html
<!-- Basic tracking pixel -->
<img src="https://api.penurwill.com/api/pixel/track?rc=AGENT123&url=https://example.com/product/123" 
     alt="" width="1" height="1" style="display:none;">

<!-- Advanced tracking pixel with more data -->
<img src="https://api.penurwill.com/api/pixel/track?rc=AGENT123&url=https://example.com/product/123&ref=https://example.com/landing&title=Product%20Details" 
     alt="" width="1" height="1" style="display:none;">
```

#### JavaScript Implementation

```javascript
function trackVisitPixel(referralCode, pageUrl, pageTitle) {
    const params = new URLSearchParams({
        rc: referralCode,
        url: pageUrl,
        t: new Date().toISOString(),
        title: pageTitle,
        ref: document.referrer,
        sid: getSessionId(), // Your session management
        sr: `${screen.width}x${screen.height}`,
        lang: navigator.language,
        tz: Intl.DateTimeFormat().resolvedOptions().timeZone
    });

    const pixel = new Image();
    pixel.src = `https://api.penurwill.com/api/pixel/track?${params}`;
    pixel.style.display = 'none';
    document.body.appendChild(pixel);
}

// Usage
trackVisitPixel('AGENT123', window.location.href, document.title);
```

## Response Formats

### Success Response (201 for REST, 200 for Pixel)

```json
{
    "success": true,
    "message": "Visit tracked successfully",
    "data": {
        "visit_id": 1,
        "agent_name": "John Doe",
        "referral_code": "AGENT123",
        "visit_url": "https://example.com/product/123",
        "visit_time": "2025-07-20T10:30:00Z",
        "referral_page": "https://example.com/landing",
        "tracked_at": "2025-07-20T10:30:00.000000Z"
    }
}
```

### Error Responses

#### Invalid Referral Code (404)
```json
{
    "success": false,
    "message": "Invalid or inactive referral code"
}
```

#### Inactive Agent (404)
```json
{
    "success": false,
    "message": "Agent not found or inactive"
}
```

#### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "referral_code": ["The referral code field is required."],
        "visit_url": ["The visit url field is required."],
        "visit_time": ["The visit time field is required."]
    }
}
```

## Implementation Notes

### REST API
1. **IP Address**: The API automatically captures the visitor's IP address
2. **User Agent**: If not provided, the API will use the request's user agent
3. **Activity Logging**: All visits are logged in the activity log for audit purposes
4. **Validation**: The referral code must be active and associated with an active agent
5. **Database**: Visits are stored in the `agent_visits` table with proper indexing for performance

### Tracking Pixel
1. **Cross-Domain**: Works across different domains without CORS issues
2. **Fault Tolerant**: Always returns a pixel image, even if tracking fails
3. **CORS Headers**: Includes proper CORS headers for cross-domain requests
4. **Short Parameters**: Uses abbreviated parameter names to minimize URL length
5. **No-Cache**: Pixel responses are not cached to ensure accurate tracking

## Usage Examples

### JavaScript (Browser) - REST API
```javascript
// Track a page visit via REST API
function trackVisitRest(referralCode, pageUrl, pageTitle) {
    const visitData = {
        referral_code: referralCode,
        visit_url: pageUrl,
        visit_time: new Date().toISOString(),
        page_title: pageTitle,
        session_id: getSessionId(), // Your session management
        screen_resolution: `${screen.width}x${screen.height}`,
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
    };

    fetch('/api/agents/track/visit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(visitData)
    });
}
```

### JavaScript (Browser) - Pixel Tracking
```javascript
// Track a page visit via pixel
function trackVisitPixel(referralCode, pageUrl, pageTitle) {
    const params = new URLSearchParams({
        rc: referralCode,
        url: pageUrl,
        t: new Date().toISOString(),
        title: pageTitle,
        ref: document.referrer,
        sid: getSessionId(),
        sr: `${screen.width}x${screen.height}`,
        lang: navigator.language,
        tz: Intl.DateTimeFormat().resolvedOptions().timeZone
    });

    const pixel = new Image();
    pixel.src = `https://api.penurwill.com/api/pixel/track?${params}`;
    pixel.style.display = 'none';
    document.body.appendChild(pixel);
}
```

### PHP
```php
// REST API
$visitData = [
    'referral_code' => 'AGENT123',
    'visit_url' => 'https://example.com/product/123',
    'visit_time' => now()->toISOString(),
    'page_title' => 'Product Details'
];

$response = Http::post('/api/agents/track/visit', $visitData);

// Pixel tracking
$pixelUrl = 'https://api.penurwill.com/api/pixel/track?' . http_build_query([
    'rc' => 'AGENT123',
    'url' => 'https://example.com/product/123',
    't' => now()->toISOString(),
    'title' => 'Product Details'
]);
```

### HTML Integration
```html
<!-- Add this to your HTML pages for automatic tracking -->
<script>
(function() {
    const referralCode = 'AGENT123'; // Get from URL params or config
    const pageUrl = window.location.href;
    const pageTitle = document.title;
    
    // Use pixel tracking for cross-domain compatibility
    const params = new URLSearchParams({
        rc: referralCode,
        url: pageUrl,
        t: new Date().toISOString(),
        title: pageTitle,
        ref: document.referrer,
        sr: screen.width + 'x' + screen.height,
        lang: navigator.language,
        tz: Intl.DateTimeFormat().resolvedOptions().timeZone
    });
    
    const pixel = new Image();
    pixel.src = 'https://api.penurwill.com/api/pixel/track?' + params;
    pixel.style.display = 'none';
    document.body.appendChild(pixel);
})();
</script>
```

## Related Endpoints

- `POST /api/agents/track/referral` - Track customer referrals
- `POST /api/agents/track/sale` - Track sales conversions
- `GET /api/agents/track/code/{code}` - Get referral code information
- `GET /api/agents/track/version` - Get API version information 