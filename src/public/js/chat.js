const messageList = document.getElementById('message-list');
const messageForm = document.getElementById('message-form');
const messageInput = document.getElementById('message-input');
const attachmentInput = document.getElementById('attachment-input');
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

messageForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    const content = messageInput.value.trim();
    const attachment = attachmentInput.files[0];

    if (content == '' && !attachment) {
        return;
    }

    if (socket.readyState != WebSocket.OPEN) {
        alert('채팅 서버에 연결되지 않았습니다.');
        return;
    }

    let attachmentId = null;

    if (attachment) {
        const formData = new FormData();

        formData.append('chat_id', chatId);
        formData.append('attachment', attachment);

        const response = await fetch('upload.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success == false) {
            alert(result.message);
            return;
        }

        attachmentId = result.attachment_id;
    }

    socket.send(JSON.stringify({
        type: 'send',
        content: content,
        attachment_id: attachmentId
    }));

    messageInput.value = '';
    attachmentInput.value = '';
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

    if (data.attachment_id != 0) {
        if (data.attachment_type == 'image') {
            const image = document.createElement('img');

            image.src = `download.php?id=${data.attachment_id}`;
            image.alt = data.original_name;
            image.style.maxWidth = '200px';
            image.style.maxHeight = '200px';

            messageBox.appendChild(image);
        } else {
            const fileLink = document.createElement('a');

            fileLink.href = `download.php?id=${data.attachment_id}`;
            fileLink.textContent = `${data.original_name} 다운로드`;

            messageBox.appendChild(fileLink);
        }
    }

    messageList.appendChild(messageBox);
    messageList.appendChild(line);
    messageList.scrollTop = messageList.scrollHeight;
}
