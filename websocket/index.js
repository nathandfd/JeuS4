const express = require('express')
const app = express()
const fs = require('fs')
var cors = require('cors');
var users = []
var bodyParser = require('body-parser');
app.use(bodyParser.json()); // support json encoded bodies
app.use(bodyParser.urlencoded({ extended: true })); // support encoded bodies

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

app.post('/action/:action',(req,res)=>{
    if (req.body.userId){
        let userId = req.body.userId
        let action = req.params.action
        let userIndex = users.findIndex((el)=>{
            return el.server_id === userId
        })
        let socketId = users[userIndex].client_id
        let data = {
            action:action,
        }

        switch (action){
            case 'secret':
                io.to(socketId).emit("action",data)
                break
            case 'depot':
                io.to(socketId).emit("action",data)
                break
            case 'echange':
                if(!req.body.cards){
                    res.status(400).end("Il manque des données")
                }
                data.cards = req.body.cards
                io.to(socketId).emit("action",data)
                break
            case 'offre':
                if(!req.body.cards){
                    res.status(400).end("Il manque des données")
                }
                data.cards = req.body.cards
                io.to(socketId).emit("action",data)
                break
            default:
                res.status(400).end("L'action demandée n'existe pas")
                break
        }
        res.status(200).end()
    }
    else {
        res.status(400).end("Données incomplètes")
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

app.get('/reload',(req,res)=>{
    if (req.query.userId){
        let userIndex = users.findIndex((el)=>{
            return el.server_id === req.query.userId
        })
        let socketId = users[userIndex].client_id
        io.to(socketId).emit("reload",true)
        res.status(200).end()
    }
    else{
        res.status(400).end()
    }
})

io.on('connection',socket=>{
    socket.on('disconnect',()=>{
         //TODO: Récupérer list de ses amis et leur envoyer un ping pour dire qu'il est hors-ligne
        let delIndex = users.findIndex((el)=>{
            return el.server_id === socket.id
        })

        if (delIndex !== -1){
            users.splice(delIndex,1)
        }
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
        //TODO: Récupérer list de ses amis et leur envoyer un ping pour dire qu'il est ligne
        
    })
})

server.listen(8080)

