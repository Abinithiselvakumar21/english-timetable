/**
 * ========================================================
 * Node.js AI Timetable Generation & Real-time Alert Server
 * File: ai_service/server.js
 * Port: 5000
 * ========================================================
 */

const express = require('express');
const cors = require('cors');
const http = require('http');
const { Server } = require('socket.io');
const cron = require('node-cron');
const TimetableScheduler = require('./scheduler');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const PORT = process.env.PORT || 5000;

app.use(cors());
app.use(express.json({ limit: '10mb' }));

// Health Check
app.get('/api/status', (req, res) => {
    res.json({
        status: "ONLINE",
        service: "English Dept AI Timetable Microservice",
        timestamp: new Date().toISOString()
    });
});

// AI Timetable Generation Endpoint
app.post('/api/generate', (req, res) => {
    try {
        const inputData = req.body;
        console.log(`[AI Engine] Received generation request with ${inputData.classes?.length || 0} classes and ${inputData.allocations?.length || 0} allocations.`);

        const scheduler = new TimetableScheduler(inputData);
        const result = scheduler.generate();

        if (result.success) {
            console.log(`[AI Engine] Scheduling successful! Generated ${result.slots.length} clash-free slots.`);
            // Emit real-time socket alert
            io.emit('timetable_generated', {
                message: "New AI Timetable has been generated and ready for review!",
                total_slots: result.slots.length,
                time: new Date()
            });
            return res.json(result);
        } else {
            console.warn(`[AI Engine] Conflict detected: ${result.message}`);
            return res.status(422).json(result);
        }

    } catch (err) {
        console.error(`[AI Engine] Internal Execution Error:`, err);
        return res.status(500).json({
            success: false,
            message: "Fatal Error in AI Scheduling Engine: " + err.message,
            slots: []
        });
    }
});

// Socket.IO Connection Handler
io.on('connection', (socket) => {
    console.log(`[Socket.io] Client connected: ${socket.id}`);
    
    socket.on('disconnect', () => {
        console.log(`[Socket.io] Client disconnected: ${socket.id}`);
    });
});

// Background Automated Cron (Runs every minute to check and notify faculty)
cron.schedule('* * * * *', () => {
    // Heartbeat log
    // In full production, this compares current system time with period start times
});

server.listen(PORT, () => {
    console.log(`========================================================`);
    console.log(`🚀 English Dept AI Scheduling Engine Running on Port ${PORT}`);
    console.log(`📡 Endpoint: http://localhost:${PORT}/api/generate`);
    console.log(`========================================================`);
});
