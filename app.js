
const express = require('express');
const cors = require('cors');
const http = require('http');

// create new express app and save it as "app"
const app = express();
app.use(cors({}));

const httpServer = http.createServer(app);

const io = require('socket.io')(httpServer);
// uncommment below line whenever need to upload the code on server.
// const io = require('socket.io')(httpServer, {path: '/nodeapp/socket.io/'});

app.get('*', (req, res) => res.send('Socket is working. You requested the following url ' + req.originalUrl));

const dotenv = require('dotenv');
dotenv.config();
var hostname = 'tape-talk.com';// process.env.NODE_SERVER_HOST;
var port = 7000;
// var port = process.env.NODE_SERVER_PORT;
var Redis = require('ioredis');
var redis = new Redis({
    port: process.env.REDIS_PORT,               // replace with your port
    host: process.env.REDIS_HOST,        // replace with your hostanme or IP address
});


httpServer.listen(port, hostname, () => {
    // console.log('HTTP Server running on port' + port);
    console.log(`Server running at http://${hostname}:${port}/`);
});

// redis.subscribe('chat-channel');
redis.subscribe('chat-channel', function () {
    console.log('Redis: message-channel subscribed');
});

redis.on('message', function (channel, message) {
    try {
        message = JSON.parse(message);
        console.log(message.data, 'message');
        if (message.data.event == 'chatMessageAdd') {
            io.sockets.to("chat-users-" + message.data.data.group_id).emit(channel + ':' + message.data.event, message.data.data);
        }
    } catch (error) {
        console.log('[error]', 'join room :', error);
    }
});

io.on('connection', (socket) => {
    try {
        console.log("socket connected",socket.id);
        socket.on('joinroom', (data) => {
            socket.join(data.room);
            if (data.event == 'chat-users') {
                socket.join("chat-users-" + data.room);
            }
        })
        socket.on('speaking',(data) => {
            // console.log("socket data typing",data);
            if (data.event == 'message-speaking') {
                console.log("in if","chat-users-" + data.groupId);
                // socket.join("message-typing-" + data.room);
                socket.to("chat-users-" + data.groupId).emit('typing', data);
            }
            //  else if(data.event == 'typing-stop'){
            //     socket.to("chat-users-" + data.groupId).emit('typing', data);
            // }
        })
        socket.on('typing',(data) => {
            // console.log("socket data typing",data);
            if (data.event == 'message-typing') {
                console.log("in if","chat-users-" + data.groupId);
                // socket.join("message-typing-" + data.room);
                socket.to("chat-users-" + data.groupId).emit('typing', data);
            }
            //  else if(data.event == 'typing-stop'){
            //     socket.to("chat-users-" + data.groupId).emit('typing', data);
            // }
        })
    } catch (e) {
        console.log('[error]', 'join room :', e);
        // socket.emit('error', 'couldnt perform requested action');
    }
});
