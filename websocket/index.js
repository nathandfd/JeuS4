const express = require('express')
const app = express()
const fs = require('fs')
var cors = require('cors');

const server = require('https').Server({
    key: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr-0002/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr-0002/fullchain.pem'),
},app)
const io = require('socket.io')(server)

app.use(cors());

app.get('/',(req,res)=>{
    res.end('nik tes morts')
    io.emit('foo','bar')
})

server.listen(8080)
