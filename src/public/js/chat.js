const messageList = document.getElementById('message-list');
const messageForm = document.getElementById('message-form');
const messageInput = document.getElementById('message-input');
const wsProtocol = location.protocol === 'https:' ? 'wss' : 'ws';
const socket = new WebSocket(`${wsProtocol}://${location.hostname}:8282`);

socket.addEventListener('open', function () {
    socket.send(JSON.stringify({
        type: 'join',
        chat_id: chatId,
        user_id: userId
    }));
});

socket.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);

    if (data.type == 'message') {
        addMessage(data);
    } else if (data.type == 'error') {
        alert(data.message);
    }
});

socket.addEventListener('close', function () {
    console.log('채팅 서버와 연결이 끊겼습니다.');
});

messageForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const content = messageInput.value.trim();

    if (content == '') {
        return;
    }

    if (socket.readyState != WebSocket.OPEN) {
        alert('채팅 서버에 연결되지 않았습니다.');
        return;
    }

    socket.send(JSON.stringify({
        type: 'send',
        content: content
    }));

    messageInput.value = '';
});

function addMessage(data) {
    const emptyMessage = messageList.querySelector(':scope > p');

    if (emptyMessage && emptyMessage.textContent.trim() == '아직 대화 내역이 없습니다.') {
        emptyMessage.remove();
    }

    const messageBox = document.createElement('div');
    const sender = document.createElement('strong');
    const createdAt = document.createElement('span');
    const content = document.createElement('p');
    const line = document.createElement('hr');

    sender.textContent = data.username;
    createdAt.textContent = ` ${data.created_at}`;
    content.textContent = data.content;

    messageBox.appendChild(sender);
    messageBox.appendChild(createdAt);
    messageBox.appendChild(content);

    messageList.appendChild(messageBox);
    messageList.appendChild(line);
    messageList.scrollTop = messageList.scrollHeight;
}
