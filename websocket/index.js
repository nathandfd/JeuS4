const express = require('express')
const app = express()
const fs = require('fs')
var cors = require('cors');
var users = []

const server = require('https').Server({
    key: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr-0002/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr-0002/fullchain.pem'),
},app)
const io = require('socket.io')(server,{
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
})

app.use(function(req, res, next) {
    res.header("Access-Control-Allow-Origin", "*"); // update to match the domain you will make the request from
    res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
    next();
});
app.get('/opponent',(req,res)=>{
    if (req.query.userId && req.query.opponentName){
        io.emit('opponent_'+req.query.userId,req.query.opponentName)
        res.status(200).end()
    }
    else{
        res.status(400).end()
    }
})

app.get('/game',(req,res)=>{
    if (req.query.userId && req.query.gameId){
        io.emit('game_'+req.query.userId,req.query.gameId)
        res.status(200).end()
    }
    else{
        res.status(400).end()
    }
})

app.get('/sendFriendRequest',(req,res)=>{
    if (req.query.userId && req.query.friendUsername){
        io.emit('friendRequest_'+req.query.userId,req.query.friendUsername)
        res.status(200).end()
    }
    else{
        res.status(400).end()
    }
})

io.on('connection',socket=>{
    socket.on('disconnect',()=>{
        console.log('on a perdu un djo')
    })

    socket.on('attachId',(data)=>{
        let user = {
            client_id: socket.id,
            server_id: data
        }

        let delIndex = users.findIndex((el)=>{
            return el.server_id === data
        })

        if (delIndex !== -1){
            users.splice(delIndex,1)
        }

        users.push(user)

        console.log(users)
    })
})

server.listen(8080)

