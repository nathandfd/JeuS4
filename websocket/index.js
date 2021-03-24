const express = require('express')
const app = express()
var cors = require('cors');

const server = require('http').Server(app)
const io = require('socket.io')(server)

app.use(cors({
    origin:'http://localhost:8000'
}));

app.get('/',(req,res)=>{
    res.end('nik tes morts')
    io.emit('foo','bar')
})

server.listen(8080)
