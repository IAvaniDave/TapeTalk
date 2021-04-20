
const express = require('express');
const cors = require('cors');
const http = require('http');

// create new express app and save it as "app"
const app = express();

app.use(express.json())
app.use(cors());

const httpServer = http.createServer(app);

const io = require('socket.io')(httpServer);
// const io = require("socket.io")(httpServer, {
//     handlePreflightRequest: (req, res) => {
//         const headers = {
//             "Access-Control-Allow-Headers": "Content-Type, Authorization",	
//             "Access-Control-Allow-Origin": "*", // req.headers.origin, //or the specific origin you want to give access to,
//             "Access-Control-Allow-Credentials": true
//         };
//         res.writeHead(200, headers);
//         res.end();
//     }
// });
// uncommment below line whenever need to upload the code on server.
// const io = require('socket.io')(httpServer, {path: '/nodeapp/socket.io/'});

app.get('*', (req, res) => res.send('Socket is working. You requested the following url ' + req.originalUrl));

const dotenv = require('dotenv');
dotenv.config();
var port = process.env.NODE_SERVER_PORT;
console.log("portport",port);
console.log("host", process.env.REDIS_HOST);
var Redis = require('ioredis');
var redis = new Redis({
    port: process.env.REDIS_PORT,               // replace with your port
    host: process.env.REDIS_HOST,        // replace with your hostanme or IP address
});

redis.subscribe('chat-channel');

redis.on('message', function (channel, message) {
    message = JSON.parse(message);
    console.log(message, 'message');
    console.log(channel, 'message - channel');
    console.log(message.data, 'message');
    // if (message.data.event == 'chatMessageAdd') {
    //     // console.log("ifffffffff");
    //     io.sockets.to("chat-users-" + message.data.data.group_id).emit(channel + ':' + message.data.event, message.data.data);
    // }
});

io.on('connection', (socket) => {
    console.log("socketttttt conn",socket);
});

httpServer.listen(port, () => {
    console.log('HTTP Server running on port' + port);
});