# WebSocket Real-Time Notifications
## Instant Notification Delivery via WebSockets

**Status**: ✅ **COMPLETED**
**Implementation Date**: 2024
**Type**: Node.js + Socket.io WebSocket Server

---

## Overview

A real-time WebSocket notification system has been implemented using Node.js and Socket.io. This provides instant notification delivery without the latency of AJAX polling, while maintaining fallback support for environments where WebSockets are not available.

---

## Architecture

### Components
1. **WebSocket Server** (`websocket-server.js`) - Node.js server with Socket.io
2. **WebSocket Client** (`public/assets/js/websocket.js`) - Browser client with Socket.io
3. **Database Integration** - MySQL polling for new notifications
4. **Connection Management** - User authentication and session management
5. **Fallback Support** - Automatic fallback to AJAX polling

### Technology Stack
- **Server**: Node.js
- **Library**: Socket.io 4.x
- **Database**: MySQL (via mysql2)
- **Transport**: WebSocket + HTTP polling (fallback)
- **Authentication**: Token-based with database verification

---

## WebSocket Server

### File: `websocket-server.js`

**Features**:
- Socket.io server with WebSocket and HTTP polling support
- MySQL database integration for notification polling
- User authentication with token verification
- Role-based notification broadcasting
- Room-based channel support
- Automatic reconnection handling
- Health check endpoint
- Broadcast API for external systems

**Configuration**:
```javascript
const PORT = process.env.WEBSOCKET_PORT || 3001;
const USE_HTTPS = process.env.WEBSOCKET_HTTPS === 'true';
```

**Database Polling**:
- Polls database every 5 seconds for new notifications
- Broadcasts notifications to connected users
- Supports user-specific, role-based, and global notifications

---

## WebSocket Client

### File: `public/assets/js/websocket.js`

**Class: `WebSocketNotificationManager`**

**Methods**:
- `connect()` - Connect to WebSocket server
- `disconnect()` - Disconnect from server
- `authenticate()` - Authenticate with user ID and token
- `markAsRead(notificationId)` - Mark notification as read
- `joinRoom(room)` - Join a specific channel
- `leaveRoom(room)` - Leave a channel
- `getConnectionStatus()` - Get connection status

**Events**:
- `connect` - Connection established
- `disconnect` - Connection lost
- `authenticated` - Authentication response
- `notification` - New notification received
- `initial_notifications` - Initial notification batch
- `reconnect_attempt` - Reconnection attempt
- `reconnect` - Reconnection successful
- `reconnect_failed` - Reconnection failed

---

## Environment Configuration

### .env Variables
```env
# WebSocket Configuration
WEBSOCKET_SERVER=localhost
WEBSOCKET_PORT=3001
WEBSOCKET_HTTPS=false
WEBSOCKET_RECONNECT_DELAY=5000
WEBSOCKET_MAX_RECONNECT_ATTEMPTS=10
```

### Production Configuration
```env
WEBSOCKET_SERVER=yourdomain.com
WEBSOCKET_PORT=3001
WEBSOCKET_HTTPS=true
```

---

## Installation

### 1. Install Node.js Dependencies
```bash
npm init -y
npm install express socket.io mysql2 dotenv
```

### 2. Create package.json
```json
{
  "name": "abuu-websocket-server",
  "version": "1.0.0",
  "main": "websocket-server.js",
  "scripts": {
    "start": "node websocket-server.js",
    "dev": "node websocket-server.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "socket.io": "^4.5.4",
    "mysql2": "^3.6.0",
    "dotenv": "^16.0.3"
  }
}
```

### 3. Start WebSocket Server
```bash
# Development
node websocket-server.js

# Production with PM2
pm2 start websocket-server.js --name "websocket-server"

# Production with forever
forever start websocket-server.js
```

---

## Usage Examples

### Server-Side Broadcasting

#### Broadcast to All Users
```javascript
// Via HTTP endpoint
POST /broadcast
{
    "notification": {
        "title": "System Maintenance",
        "message": "Server will be down for maintenance",
        "type": "warning"
    },
    "target": "all"
}
```

#### Broadcast to Role
```javascript
POST /broadcast
{
    "notification": {
        "title": "New Assignment",
        "message": "Assignment posted for CS101",
        "type": "assignment",
        "target_role": "student"
    },
    "target": "role"
}
```

#### Broadcast to Specific User
```javascript
POST /broadcast
{
    "notification": {
        "title": "Payment Confirmed",
        "message": "Your payment has been confirmed",
        "type": "success"
    },
    "target": "user",
    "user_id": 123
}
```

### Client-Side Usage

#### Manual Connection
```javascript
const manager = new WebSocketNotificationManager({
    serverUrl: 'localhost',
    port: 3001,
    useSSL: false,
    onNotification: function(notification) {
        console.log('New notification:', notification);
    }
});

manager.connect();
```

#### Join Room
```javascript
window.websocketManager.joinRoom('course-101');
```

#### Mark as Read
```javascript
window.websocketManager.markAsRead(456);
```

#### Check Connection Status
```javascript
const status = window.websocketManager.getConnectionStatus();
console.log(status);
// { connected: true, authenticated: true, reconnectAttempts: 0 }
```

---

## Integration with Existing System

### Automatic Integration
The WebSocket client automatically integrates with:
- Existing notification polling system
- Notification badge updates
- Sound notifications
- UI rendering

### Event Dispatching
WebSocket notifications dispatch custom events:
```javascript
document.addEventListener('websocketNotification', function(e) {
    const notification = e.detail;
    // Handle notification
});
```

### Connection Status Indicators
Body classes indicate connection status:
```css
.websocket-connected {
    /* Connected styles */
}

.websocket-disconnected {
    /* Disconnected styles */
}
```

---

## Database Polling

### Polling Mechanism
The WebSocket server polls the database every 5 seconds for new notifications:
```sql
SELECT * FROM notifications 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
ORDER BY created_at ASC
```

### Notification Routing
- **User-specific**: Sent to user's connected sockets
- **Role-based**: Sent to all users with matching role
- **Global**: Sent to all connected users

---

## Security Considerations

### Authentication
- User ID and token verification
- Database-based user validation
- Session validation on connection

### CORS Configuration
```javascript
cors: {
    origin: process.env.APP_URL || 'http://localhost',
    methods: ['GET', 'POST'],
    credentials: true
}
```

### Rate Limiting
- Connection rate limiting (via Socket.io)
- Database query optimization
- Connection pool management

### HTTPS Support
- SSL/TLS encryption support
- Certificate configuration
- Secure WebSocket (wss://) support

---

## Performance Optimization

### Connection Pooling
```javascript
const pool = mysql.createPool({
    connectionLimit: 10,
    queueLimit: 0
});
```

### Polling Optimization
- 5-second polling interval
- Only fetches recent notifications (30 seconds)
- Efficient query indexing

### Transport Optimization
```javascript
transports: ['websocket', 'polling']
```
- WebSocket preferred for performance
- HTTP polling as fallback

---

## Troubleshooting

### Server Won't Start
1. Check Node.js is installed: `node --version`
2. Check dependencies are installed: `npm list`
3. Check port is available: `netstat -an | grep 3001`
4. Check database connection: Verify `.env` credentials

### Client Won't Connect
1. Check Socket.io library is loaded
2. Check WebSocket server is running
3. Check firewall settings
4. Check browser console for errors
5. Verify CORS configuration

### Notifications Not Received
1. Check user is authenticated
2. Check database polling is working
3. Check notification routing logic
4. Verify user role matches target
5. Check browser console for errors

### Connection Drops Frequently
1. Check network stability
2. Increase ping timeout
3. Disable HTTP polling fallback
4. Check server resources
5. Review server logs

---

## Monitoring

### Health Check Endpoint
```bash
curl http://localhost:3001/health
```

**Response**:
```json
{
    "status": "ok",
    "connectedUsers": 15,
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Server Logs
```bash
# View logs
tail -f logs/websocket.log

# PM2 logs
pm2 logs websocket-server

# Forever logs
forever logs websocket-server
```

### Metrics to Monitor
- Connected users count
- Message delivery rate
- Connection duration
- Reconnection attempts
- Database query performance

---

## Production Deployment

### Using PM2 (Recommended)
```bash
# Install PM2
npm install -g pm2

# Start server
pm2 start websocket-server.js --name "websocket-server"

# Configure auto-restart
pm2 startup
pm2 save

# Monitor
pm2 monit

# Logs
pm2 logs websocket-server

# Restart
pm2 restart websocket-server
```

### Using systemd
```ini
[Unit]
Description=WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/abuu-nufaysah-university
ExecStart=/usr/bin/node websocket-server.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### Using Docker
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm install --production
COPY . .
EXPOSE 3001
CMD ["node", "websocket-server.js"]
```

### Nginx Reverse Proxy
```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;

    location /socket.io/ {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

---

## Scaling Considerations

### Horizontal Scaling
- Use Redis adapter for Socket.io
- Multiple WebSocket servers behind load balancer
- Sticky sessions for WebSocket connections

### Redis Adapter Setup
```javascript
const redis = require('redis');
const { createClient } = redis;
const { Server } = require('socket.io');
const { RedisAdapter } = require('@socket.io/redis-adapter');

const io = new Server(3001);
const pubClient = createClient({ url: 'redis://localhost:6379' });
const subClient = pubClient.duplicate();

Promise.all([pubClient.connect(), subClient.connect()]).then(() => {
    io.adapter(createRedisAdapter(pubClient, subClient));
});
```

### Load Balancer Configuration
- Enable sticky sessions
- WebSocket-aware load balancer
- Health checks for WebSocket servers

---

## Fallback to AJAX Polling

The system automatically falls back to AJAX polling when:
- WebSocket is not supported
- WebSocket connection fails
- WebSocket server is unavailable

### Fallback Detection
```javascript
if (!window.websocketManager || !window.websocketManager.isConnected) {
    // Use AJAX polling
    window.notificationPoller.start();
}
```

### Graceful Degradation
- WebSocket preferred when available
- AJAX polling as fallback
- Seamless switching between modes
- No user impact

---

## Files Created/Modified

### New Files (2)
```
websocket-server.js
public/assets/js/websocket.js
```

### Modified Files (3)
```
views/layouts/main.php
.env
.env.example
```

---

## Comparison: WebSocket vs AJAX Polling

| Feature | WebSocket | AJAX Polling |
|---------|-----------|--------------|
| Latency | Instant | 30-second interval |
| Server Load | Low | Higher (repeated requests) |
| Bandwidth | Low | Higher |
| Real-time | Yes | No |
| Browser Support | Modern browsers | All browsers |
| Complexity | Higher | Lower |
| Reliability | High | Very High |

---

## Testing

### Manual Testing
1. Start WebSocket server: `node websocket-server.js`
2. Open application in browser
3. Check browser console for connection status
4. Create a notification via NotificationHelper
5. Verify notification appears instantly

### Automated Testing
```bash
# Test WebSocket connection
wscat -c ws://localhost:3001

# Test health endpoint
curl http://localhost:3001/health

# Test broadcast endpoint
curl -X POST http://localhost:3001/broadcast \
  -H "Content-Type: application/json" \
  -d '{"notification":{"title":"Test"},"target":"all"}'
```

---

## Documentation References

- **Socket.io Documentation**: https://socket.io/docs/
- **Node.js Documentation**: https://nodejs.org/docs/
- **Real-Time Notifications**: `docs/REALTIME_NOTIFICATIONS.md`
- **Role-Based Notifications**: `docs/ROLE_BASED_NOTIFICATIONS.md`

---

## Next Steps

### Immediate Actions
1. Install Node.js dependencies
2. Start WebSocket server in development
3. Test connection and notification delivery
4. Configure for production environment
5. Set up process manager (PM2)

### Production Deployment
1. Configure HTTPS for WebSocket server
2. Set up Redis adapter for scaling
3. Configure Nginx reverse proxy
4. Set up monitoring and logging
5. Configure auto-restart on failure

---

## Support

For issues or questions:
1. Check server logs for errors
2. Verify database connection
3. Check browser console for client errors
4. Verify WebSocket server is running
5. Review troubleshooting section above

---

**Implementation Completed**: 2024
**Status**: ✅ Infrastructure Ready
**Version**: 1.0
