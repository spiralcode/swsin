<!doctype html>
<html lang="en">

<head>
    <title>vdo : caller</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/app.css">
</head>

<body>
    <div class="appBox">
        <div class="titleSlot">
            <h2>Sender</h2>
        </div>
        <div class="optionSlot">
            <div class="form-group">
        <input type="number" id="regID" placeholder="Choose a number" />
            <button class="btn btn-primary" id="registerButton" onclick="register()">Register</button>
        </div>
        <div class="form-group">

            <input type="number" id="recID" placeholder="Recepient Number" />
            <button class="btn btn-primary" onclick="makeCall()">Make</button>
        </div>
            </div>
            <div id="offer" style="display: none;">There's a call.</div>
        <video class="videoSlot" autoplay id="tv"></video>

    </div>
        <video id="viewfinder" autoplay muted></video>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script>
        function register() {
            socket.emit("register", document.getElementById('regID').value);
            mediaSanction();
            document.getElementById('registerButton').addAttribute('disabled','true');

        }
        const configuration = {
            'iceServers': [{
                'urls': 'turn:65.0.215.67:3478',
                'username': 'raj',
                'credential': '123456'
            }]
        };
        const peerConnection = new RTCPeerConnection(configuration);

        const constraints = {
            'video': true,
            'audio': true
        }

        async function mediaSanction() {
            navigator.mediaDevices.enumerateDevices().then(async(devices)=>{
                let audio = false;
                let video=false;
                devices.forEach((device,i)=>{
                    if(device.kind=="audioinput")
                    {
                        audio=true;
                    }
                    else if(device.kind=="videoinput")
                    {
                        video=true;
                    }
                });
                try{
            const localStream = await navigator.mediaDevices.getUserMedia({'audio':audio,'video':video});
            //Setting localStream to viewFinder
            document.getElementById('viewfinder').srcObject=localStream;
            //Setting localStream to remote river
            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });
            }
            catch(error){

                console.log(error);
            }
            });
            
            
            //console.log('Got Media Stream',stream);
            const remoteStream = new MediaStream();
                    const remoteVideo = document.getElementById('tv');
                    remoteVideo.srcObject = remoteStream;
                    peerConnection.addEventListener('track', async (event) => {
                    remoteStream.addTrack(event.track, remoteStream);
          
        });
        }

        async function makeCall() {

            socket.on('answer', async (message) => {
                if (message.answer) {
                    console.log('Remote Description set');
                    const remoteDesc = new RTCSessionDescription(message.answer);
                    await peerConnection.setRemoteDescription(remoteDesc);
                }
            });
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            let recipient = new Object();
            recipient.id = document.getElementById('recID').value;
            recipient.offer = offer;
            socket.emit('request', recipient);
            peerConnection.addEventListener('icecandidate', event => {
                if (event.candidate) {
                    let package = new Object();
                    package.recID = document.getElementById('recID').value;
                    package.candidate = event.candidate;
                    socket.emit('ice-candidate', package);
                }
            });
            socket.on('ice-candidate', async (package) => {
                console.log('Candidate');

                try {
                    await peerConnection.addIceCandidate(package.candidate);
                } catch(e) {
                    console.error('Error adding ice candidate', e);
                }
            });
            socket.on('ice-candidate', async (package) => {
                console.log('Candidate');
                try {
                    await peerConnection.addIceCandidate(package.candidate);
                    
        }
        catch (e) {
                    console.error('Error adding ice candidate', e);
                }
        
                }); 
             
            
            peerConnection.addEventListener('connectionstatechange', (event) => {
                if (peerConnection.connectionState === 'connected') {
                    alert('Connected');
                }
            });

       
        }

    </script>
     <script src="https://wesignal.herokuapp.com/socket.io/socket.io.js"></script>
     <script>
         const socket = io('https://wesignal.herokuapp.com/');
     </script>
</body>

</html>