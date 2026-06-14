# Agent Visit Tracking API

## Overview

Visit tracking API tracks user visits via agent referral codes. Monitors traffic/engagement from marketing channels. Supports REST endpoints and cross-domain tracking pixels.

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
| `referral_code` | string | Yes | Agent's referral code |
| `visit_url` | string | Yes | Visited URL (must be valid URL) |
| `visit_time` | string | Yes | ISO 8601 timestamp of visit |
| `referral_page` | string | No | Referring page URL |
| `session_id` | string | No | Unique session identifier |
| `page_title` | string | No | Title of visited page |
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

Tracking pixel enables cross-domain tracking without CORS issues. Returns 1x1 transparent GIF.

#### Query Parameters

| Parameter | Short | Required | Description |
|-----------|-------|----------|-------------|
| `referral_code` | `rc` | Yes | Agent's referral code |
| `visit_url` | `url` | Yes | Visited URL |
| `visit_time` | `t` | No | ISO 8601 timestamp (defaults to current time) |
| `referral_page` | `ref` | No | Referring page URL |
| `session_id` | `sid` | No | Unique session identifier |
| `page_title` | `title` | No | Title of visited page |
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
1. **IP Address**: API auto-captures visitor IP
2. **User Agent**: Falls back to request user agent if not provided
3. **Activity Logging**: All visits logged for audit
4. **Validation**: Referral code must be active, linked to active agent
5. **Database**: Visits stored in `agent_visits` table with performance indexing

### Tracking Pixel
1. **Cross-Domain**: Works across domains without CORS issues
2. **Fault Tolerant**: Always returns pixel even if tracking fails
3. **CORS Headers**: Includes proper CORS headers for cross-domain requests
4. **Short Parameters**: Abbreviated param names minimize URL length
5. **No-Cache**: Pixel responses uncached for accurate tracking

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

## Track Sale

```
POST /api/agents/track/sale
```

Track sales conversions linked to agent referral code. Creates sale record, auto-calculates commission from agent's commission rate.

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `referral_code` | string | Yes | Agent's referral code (max 50 chars) |
| `customer_name` | string | Yes | Customer name (max 255 chars) |
| `customer_email` | string | Yes | Customer email (valid email, max 255 chars) |
| `customer_phone` | string | No | Customer phone (max 20 chars) |
| `sale_amount` | number | Yes | Sale amount (min 0.01) |
| `product_name` | string | Yes | Product/service name (max 255 chars) |
| `sale_date` | string | Yes | Sale date YYYY-MM-DD (today or earlier) |
| `notes` | string | No | Additional notes (max 1000 chars) |
| `source` | string | No | Sale source e.g. "website", "phone", "email" (max 100 chars) |

#### Example Request

```javascript
fetch('/api/agents/track/sale', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        referral_code: 'AGENT123',
        customer_name: 'John Doe',
        customer_email: 'john.doe@example.com',
        customer_phone: '+60123456789',
        sale_amount: 1500.00,
        product_name: 'Premium Package',
        sale_date: '2025-07-20',
        notes: 'Customer purchased premium package with annual subscription',
        source: 'website'
    })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

#### Example Response (201 Created)

```json
{
    "success": true,
    "message": "Sale tracked successfully",
    "data": {
        "sale_id": 1,
        "commission_id": 1,
        "agent_name": "John Doe",
        "customer_name": "John Doe",
        "sale_amount": 1500.00,
        "commission_amount": 150.00,
        "commission_percentage": 10.0,
        "status": "pending",
        "tracked_at": "2025-07-20T10:30:00.000000Z"
    }
}
```

#### Error Responses

**Invalid Referral Code (404)**
```json
{
    "success": false,
    "message": "Invalid or inactive referral code"
}
```

**Inactive Agent (404)**
```json
{
    "success": false,
    "message": "Agent not found or inactive"
}
```

**Validation Error (422)**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "referral_code": ["The referral code field is required."],
        "customer_email": ["The customer email must be a valid email address."],
        "sale_amount": ["The sale amount must be at least 0.01."],
        "sale_date": ["The sale date must be a date before or equal to today."]
    }
}
```

#### Implementation Notes

- **Commission Calculation**: Auto-calculated from agent's custom rate, defaults to 10% if none set
- **Sale Record**: Created in `sales` table
- **Commission Record**: Auto-created with status "pending"
- **Activity Logging**: Sale and commission both logged
- **IP Address**: API auto-captures visitor IP
- **User Agent**: API auto-captures request user agent

---

## Get Referral Code Information

```
GET /api/agents/track/code/{code}
```

Retrieve referral code info including agent details and activation status.

#### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `code` | string | Yes | Referral code to look up |

#### Example Request

```javascript
// Using fetch
fetch('/api/agents/track/code/AGENT123')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Agent:', data.data.agent_name);
            console.log('Type:', data.data.agent_type);
            console.log('Active:', data.data.is_active);
        }
    })
    .catch(error => console.error('Error:', error));

// Using axios
import axios from 'axios';

const response = await axios.get('/api/agents/track/code/AGENT123');
console.log(response.data);
```

#### Example Response (200 OK)

```json
{
    "success": true,
    "data": {
        "referral_code": "AGENT123",
        "agent_name": "John Doe",
        "agent_type": "individual",
        "is_active": true,
        "created_at": "2025-01-15T08:00:00.000000Z"
    }
}
```

#### Error Responses

**Referral Code Not Found (404)**
```json
{
    "success": false,
    "message": "Referral code not found or inactive"
}
```

**Inactive Agent (404)**
```json
{
    "success": false,
    "message": "Agent not found or inactive"
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `referral_code` | string | Referral code |
| `agent_name` | string | Agent name linked to code |
| `agent_type` | string | Agent type: "individual" or "company" |
| `is_active` | boolean | Whether referral code is active |
| `created_at` | string | ISO 8601 creation timestamp |

#### Implementation Notes

- **Validation**: Only active codes linked to active agents returned
- **Use Case**: Validate codes before tracking visits or sales
- **Public Access**: No authentication required

---

## Usage Examples

### Track Sale - JavaScript (Browser)

```javascript
async function trackSale(referralCode, saleData) {
    try {
        const response = await fetch('/api/agents/track/sale', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                referral_code: referralCode,
                customer_name: saleData.customerName,
                customer_email: saleData.customerEmail,
                customer_phone: saleData.customerPhone,
                sale_amount: saleData.amount,
                product_name: saleData.productName,
                sale_date: saleData.saleDate,
                notes: saleData.notes,
                source: saleData.source || 'website'
            })
        });

        const result = await response.json();
        
        if (result.success) {
            console.log('Sale tracked:', result.data);
            return result.data;
        } else {
            console.error('Error tracking sale:', result.message);
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Network error:', error);
        throw error;
    }
}

// Usage example
trackSale('AGENT123', {
    customerName: 'Jane Smith',
    customerEmail: 'jane@example.com',
    customerPhone: '+60123456789',
    amount: 2500.00,
    productName: 'Enterprise Package',
    saleDate: '2025-07-20',
    notes: 'Annual subscription',
    source: 'website'
});
```

### Get Referral Code Info - JavaScript (Browser)

```javascript
async function validateReferralCode(code) {
    try {
        const response = await fetch(`/api/agents/track/code/${code}`);
        const result = await response.json();
        
        if (result.success && result.data.is_active) {
            return {
                valid: true,
                agentName: result.data.agent_name,
                agentType: result.data.agent_type
            };
        } else {
            return {
                valid: false,
                message: result.message || 'Invalid referral code'
            };
        }
    } catch (error) {
        return {
            valid: false,
            message: 'Error validating referral code'
        };
    }
}

// Usage example
const validation = await validateReferralCode('AGENT123');
if (validation.valid) {
    console.log(`Valid code for agent: ${validation.agentName}`);
} else {
    console.error('Invalid code:', validation.message);
}
```

### Track Sale - PHP (Laravel)

```php
use Illuminate\Support\Facades\Http;

// Track a sale
$response = Http::post('/api/agents/track/sale', [
    'referral_code' => 'AGENT123',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'customer_phone' => '+60123456789',
    'sale_amount' => 1500.00,
    'product_name' => 'Premium Package',
    'sale_date' => now()->format('Y-m-d'),
    'notes' => 'Customer purchased premium package',
    'source' => 'website'
]);

if ($response->successful()) {
    $data = $response->json();
    $saleId = $data['data']['sale_id'];
    $commissionAmount = $data['data']['commission_amount'];
    // Process success
} else {
    // Handle error
    $error = $response->json();
    Log::error('Sale tracking failed', $error);
}
```

### Get Referral Code Info - PHP (Laravel)

```php
use Illuminate\Support\Facades\Http;

// Get referral code information
$code = 'AGENT123';
$response = Http::get("/api/agents/track/code/{$code}");

if ($response->successful()) {
    $data = $response->json();
    $agentName = $data['data']['agent_name'];
    $isActive = $data['data']['is_active'];
    
    if ($isActive) {
        // Proceed with tracking
    }
} else {
    // Invalid or inactive code
    $error = $response->json();
    Log::warning('Invalid referral code', ['code' => $code, 'error' => $error]);
}
```

### Track Sale - cURL

```bash
curl -X POST https://api.penurwill.com/api/agents/track/sale \
  -H "Content-Type: application/json" \
  -d '{
    "referral_code": "AGENT123",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "+60123456789",
    "sale_amount": 1500.00,
    "product_name": "Premium Package",
    "sale_date": "2025-07-20",
    "notes": "Customer purchased premium package",
    "source": "website"
  }'
```

### Get Referral Code Info - cURL

```bash
curl -X GET https://api.penurwill.com/api/agents/track/code/AGENT123
```

---

## Related Endpoints

- `POST /api/agents/track/referral` - Track customer referrals
- `POST /api/agents/track/sale` - Track sales conversions
- `GET /api/agents/track/code/{code}` - Get referral code info
- `GET /api/agents/track/version` - Get API version info