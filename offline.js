// Service Worker checker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js')
			.then((reg) => {
				console.log('Service worker registered.', reg);
			});
  });
}

document.addEventListener('DOMContentLoaded', () => {
	let messages, headerHidden = false, firstRun = true, filterType = {unread: 1, flagged: 1}, filterBoard = ['1048', '1050', '1039', '1049'];
	const header = document.getElementsByTagName('header')[0], loadingDesc = document.getElementById('loading-text'), loadingOverlay = document.getElementById('loading'), loadingIcon = document.getElementById('loading-icon'), listElement = document.getElementById('messages'), viewElement = document.getElementById('viewer');

	function isNumber(number) {
		return !isNaN(parseFloat(number));
	}

	function renderMessages(query = '') {
		// Clears the div of messages
		while (listElement.hasChildNodes()) listElement.removeChild(listElement.lastChild);

		list = [];
		filterBoard.forEach(board => list = list.concat(messages[board]));
		list = list.sort((a, b) => b.id - a.id).sort((a, b) => new Date(b.date) - new Date(a.date)); 
		if (list.length == 0) {
			listElement.innerHTML = '<h1>No messages matching your filter</h1>';
			return;
		}

		list.forEach(message => {
			if (query) 
				if (!message.title.toLowerCase().includes(query) && !message.sender.toLowerCase().includes(query) && !message.date.toLowerCase().includes(query)) 
					return;

			const parent = document.createElement('div');

			const title = document.createElement('div');
			title.appendChild(document.createTextNode(message.title));

			const date = document.createElement('div');
			date.appendChild(document.createTextNode(message.date));

			const sender = document.createElement('div');
			sender.appendChild(document.createTextNode(message.sender));

			parent.appendChild(title);
			parent.appendChild(date);
			parent.appendChild(sender);

			if (message.unread) parent.classList.add('unread');
			if (message.flaged) parent.classList.add('flaged');
			if (message.attachments) parent.classList.add('attachments');

			parent.dataset.id = message.id;
			parent.dataset.board = message.board;

			listElement.appendChild(parent);
		});

    if (listElement.childElementCount == 0) listElement.innerHTML = '<h1>No messages matching your filter</h1>';
    
    document.getElementById('loading').style.display = 'none';
	}

	// Hide header on scroll
	if (Math.max(document.documentElement.clientWidth, window.innerWidth || 0) <= 650) {
		let scrolled, lastPos = 0;
		listElement.addEventListener('scroll', () => scrolled = true);
		setInterval(() => {
			if (scrolled) {
				pos = listElement.scrollTop;
				if (Math.abs(pos - lastPos) < 10) return;
				if (pos > lastPos) header.classList.add('hide');
				else header.classList.remove('hide');
				scrolled = false;
				lastPos = pos;
			}
		}, 250);
	}

	// Event listener to open dropdown
	document.getElementById('filter-button').addEventListener('click', () => document.getElementById('filter-container').classList.toggle('open-dropdown'));

	document.getElementById('close').addEventListener('click', () => {
		if (!headerHidden) header.classList.remove('hide');
		viewElement.style.opacity = 0;
		document.getElementById('close').style.opacity = 0;
		document.getElementById('selected').id = '';
		setTimeout(() => viewElement.classList.remove('open-message'), 200);
	});

	// Event listener to load messages
	listElement.addEventListener('click', e => {
		alert("Can't get the message as you are offline.");
	});

	// Load cached messages into memory
	if (typeof localStorage.messages != 'undefined' && typeof localStorage.messages != 'undefined') {
		messages = JSON.parse(localStorage.messages);
		firstRun = false;
		renderMessages();
	}

	document.getElementById('filter').addEventListener('click', e => {
		if (e.target.tagName == 'LI') {
			const target = e.target;
			if (isNumber(target.dataset.code)) {
				// Change board
				if (target.type == 'circle') {
					target.removeAttribute('type');
					filterBoard.push(target.dataset.code);
				}
				else {
					target.type = 'circle';
					filterBoard.splice(filterBoard.indexOf(target.dataset.code), 1);
				}
				renderMessages();
			}
			else {
				// Change type
				if (target.type == 'circle') {
					target.removeAttribute('type');
					filterType[target.dataset.code] = 1;
				}
				else {
					target.type = 'circle';
					filterType[target.dataset.code] = 0;
				}
			}
		}
		e.stopPropagation();
	});

	document.getElementById('search').addEventListener('keyup', () => {
		renderMessages(document.getElementById('search').value.toLowerCase());
  });
  
  renderMessages();
});

window.onload = () => document.getElementById('filter-container').style.left = `${document.getElementById('filter-button').getBoundingClientRect().left}px`;