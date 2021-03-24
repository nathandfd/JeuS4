const express = require('express')
const app = express()
const fs = require('fs')
var cors = require('cors');

const server = require('http').Server({
    key: fs.readFileSync('server.key'),
    cert: fs.readFileSync('server.cert')
},app)
const io = require('socket.io')(server)

app.use(cors({
    origin:'http://localhost:8000'
}));

app.get('/',(req,res)=>{
    res.end('nik tes morts')
    io.emit('foo','bar')
})

server.listen(8080)
