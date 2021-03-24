var express = require('express')
var fs = require('fs')
var https = require('https')
var app = express()

app.get('/', function (req, res) {
    res.send('hello world')
})

https.createServer({
    key: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr-0002/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/nathandfd.fr-0002/fullchain.pem'),
}, app)
    .listen(8080, function () {
        console.log('Example app listening on port 3000! Go to https://localhost:3000/')
    })