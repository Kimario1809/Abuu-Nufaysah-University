/**
 * WebSocket Server for Real-Time Notifications
 * Node.js + Socket.io implementation
 * Abuu Nufaysah University System
 */

const express = require('express');
const http = require('http');
const https = require('https');
const fs = require('fs');
const socketIO = require('socket.io');
const mysql = require('mysql2/promise');

// Load environment variables
require('dotenv').config({ path: '.env' });

const app = express();
const PORT = process.env.WEBSOCKET_PORT || 3001;
const USE_HTTPS = process.env.WEBSOCKET_HTTPS === 'true';

// Database configuration
const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    port: process.env.DB_PORT || 3306,
    user: process.env.DB_USERNAME || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_DATABASE || 'abuu_university',
    charset: 'utf8mb4'
};

// Create HTTP or HTTPS server
let server;
if (USE_HTTPS) {
    const sslOptions = {
        key: fs.readFileSync(process.env.SSL_KEY_PATH),
        cert: fs.readFileSync(process.env.SSL_CERT_PATH)
    };
    server = https.createServer(sslOptions, app);
} else {
    server = http.createServer(app);
}

// Create Socket.io server
const io = socketIO(server, {
    cors: {
        origin: process.env.APP_URL || 'http://localhost',
        methods: ['GET', 'POST'],
        credentials: true
    },
    transports: ['websocket', 'polling'],
    pingTimeout: 60000,
    pingInterval: 25000
});

// Database connection pool
const pool = mysql.createPool({
    ...dbConfig,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Store connected users with their socket IDs and user IDs
const connectedUsers = new Map();
const userSockets = new Map(); // userId -> Set of socketIds

/**
 * Broadcast notification to specific user
 */
async function sendNotificationToUser(userId, notification) {
    const socketIds = userSockets.get(userId);
    
    if (socketIds && socketIds.size > 0) {
        socketIds.forEach(socketId => {
            const socket = io.sockets.sockets.get(socketId);
            if (socket) {
                socket.emit('notification', notification);
            }
        });
        
        console.log(`Notification sent to user ${userId}: ${notification.title}`);
        return true;
    }
    
    return false;
}

/**
 * Broadcast notification to role
 */
async function sendNotificationToRole(role, notification) {
    const [rows] = await pool.query(
        'SELECT id FROM users WHERE role = ? AND is_active = 1',
        [role]
    );
    
    for (const row of rows) {
        await sendNotificationToUser(row.id, notification);
    }
    
    console.log(`Notification sent to role ${role}: ${notification.title}`);
}

/**
 * Broadcast notification to all
 */
async function sendNotificationToAll(notification) {
    io.emit('notification', notification);
    console.log(`Notification sent to all: ${notification.title}`);
}

/**
 * Handle new notification from database
 */
async function handleNewNotification(notification) {
    if (notification.user_id) {
        // Send to specific user
        await sendNotificationToUser(notification.user_id, notification);
    } else if (notification.target_role === 'all') {
        // Send to all connected users
        await sendNotificationToAll(notification);
    } else {
        // Send to specific role
        await sendNotificationToRole(notification.target_role, notification);
    }
}

/**
 * Database polling for new notifications
 */
async function pollDatabase() {
    try {
        // Get notifications created in the last 30 seconds
        const [rows] = await pool.query(
            `SELECT * FROM notifications 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
             ORDER BY created_at ASC`
        );
        
        for (const notification of rows) {
            await handleNewNotification(notification);
        }
    } catch (error) {
        console.error('Database polling error:', error);
    }
}

// Start database polling every 5 seconds
setInterval(pollDatabase, 5000);

// Socket.io connection handling
io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);
    
    // Handle user authentication
    socket.on('authenticate', async (data) => {
        try {
            const { userId, token } = data;
            
            // Verify token with database
            const [rows] = await pool.query(
                'SELECT id, full_name, role FROM users WHERE id = ? AND is_active = 1',
                [userId]
            );
            
            if (rows.length > 0) {
                const user = rows[0];
                
                // Store user mapping
                connectedUsers.set(socket.id, {
                    userId: user.id,
                    fullName: user.full_name,
                    role: user.role,
                    connectedAt: new Date()
                });
                
                // Add socket to user's socket set
                if (!userSockets.has(user.id)) {
                    userSockets.set(user.id, new Set());
                }
                userSockets.get(user.id).add(socket.id);
                
                // Send success response
                socket.emit('authenticated', {
                    success: true,
                    user: user
                });
                
                // Send unread notifications
                const [notifications] = await pool.query(
                    `SELECT * FROM notifications 
                     WHERE (user_id = ? OR user_id IS NULL) 
                     AND (target_role = ? OR target_role = 'all')
                     AND is_read = 0
                     ORDER BY created_at DESC LIMIT 10`,
                    [user.id, user.role]
                );
                
                socket.emit('initial_notifications', notifications);
                
                console.log(`User ${user.id} authenticated: ${user.fullName}`);
            } else {
                socket.emit('authenticated', {
                    success: false,
                    message: 'Invalid user'
                });
            }
        } catch (error) {
            console.error('Authentication error:', error);
            socket.emit('authenticated', {
                success: false,
                message: 'Authentication failed'
            });
        }
    });
    
    // Handle notification read
    socket.on('notification_read', async (data) => {
        try {
            const { notificationId } = data;
            const user = connectedUsers.get(socket.id);
            
            if (user) {
                await pool.query(
                    'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
                    [notificationId, user.userId]
                );
                
                console.log(`Notification ${notificationId} marked as read by user ${user.userId}`);
            }
        } catch (error) {
            console.error('Notification read error:', error);
        }
    });
    
    // Handle join room (for specific channels)
    socket.on('join_room', (room) => {
        socket.join(room);
        console.log(`Socket ${socket.id} joined room: ${room}`);
    });
    
    // Handle leave room
    socket.on('leave_room', (room) => {
        socket.leave(room);
        console.log(`Socket ${socket.id} left room: ${room}`);
    });
    
    // Handle disconnect
    socket.on('disconnect', () => {
        const user = connectedUsers.get(socket.id);
        
        if (user) {
            // Remove socket from user's socket set
            const socketIds = userSockets.get(user.userId);
            if (socketIds) {
                socketIds.delete(socket.id);
                
                // Clean up empty sets
                if (socketIds.size === 0) {
                    userSockets.delete(user.userId);
                }
            }
            
            connectedUsers.delete(socket.id);
            console.log(`User ${user.userId} disconnected: ${user.fullName}`);
        } else {
            console.log('Client disconnected:', socket.id);
        }
    });
    
    // Handle errors
    socket.on('error', (error) => {
        console.error('Socket error:', error);
    });
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        connectedUsers: connectedUsers.size,
        timestamp: new Date().toISOString()
    });
});

// Stats endpoint
app.get('/stats', (req, res) => {
    const userRoles = {};
    connectedUsers.forEach((data, socketId) => {
        const role = data.role || 'unknown';
        userRoles[role] = (userRoles[role] || 0) + 1;
    });
    
    res.json({
        status: 'ok',
        connectedUsers: connectedUsers.size,
        userRoles: userRoles,
        timestamp: new Date().toISOString()
    });
});

// Broadcast endpoint (for external systems)
app.post('/broadcast', express.json(), async (req, res) => {
    try {
        const { notification, target } = req.body;
        
        if (!notification) {
            return res.status(400).json({ error: 'Notification required' });
        }
        
        if (target === 'all') {
            await sendNotificationToAll(notification);
        } else if (target === 'role') {
            await sendNotificationToRole(notification.target_role, notification);
        } else if (target === 'user') {
            await sendNotificationToUser(notification.user_id, notification);
        }
        
        res.json({ success: true });
    } catch (error) {
        console.error('Broadcast error:', error);
        res.status(500).json({ error: 'Broadcast failed' });
    }
});

// Start server
server.listen(PORT, () => {
    console.log(`WebSocket server running on port ${PORT}`);
    console.log(`Environment: ${process.env.NODE_ENV || 'development'}`);
    console.log(`HTTPS: ${USE_HTTPS ? 'enabled' : 'disabled'}`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM received, shutting down gracefully');
    server.close(() => {
        pool.end();
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('SIGINT received, shutting down gracefully');
    server.close(() => {
        pool.end();
        process.exit(0);
    });
});
