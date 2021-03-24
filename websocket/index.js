const express = require('express')
const app = express()
const fs = require('fs')
var cors = require('cors');

const server = require('http').Server({
    key: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr/fullchain.pem'),
},app)
const io = require('socket.io')(server)

app.use(cors());

app.get('/',(req,res)=>{
    res.end('nik tes morts')
    io.emit('foo','bar')
})

server.listen(8080)
