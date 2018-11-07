// TODO: Remove 'board' from each message
document.addEventListener('DOMContentLoaded', () => {
	// Ensure that a session ID has been set
	if (!cookie.get('username') || !cookie.get('password')) window.location = 'login.html';
	if (!cookie.get('sessid')) cookie.set('sessid', 'TEMPORARY', 100);

	let messages, firstRun = true, filterType = {unread: 1, flagged: 1}, filterBoard = ['1048', '1050', '1039', '1049', '1053'];
	const loadingDesc = document.getElementById('loading-text'), loadingOverlay = document.getElementById('loading'), loadingIcon = document.getElementById('loading-icon'), listElement = document.getElementById('messages'), viewElement = document.getElementById('viewer');

	function updateLocalStorage() {
		localStorage.messages = JSON.stringify(messages);
	}

	function isNumber(number) {
		return !isNaN(parseFloat(number));
	}

	function init() {
		loadingIcon.innerHTML = 'cloud_download';
		Promise.all([getMessages(1048), getMessages(1050), getMessages(1039), getMessages(1049), getMessages(1053)]).then(data => {
			messages = {
				1048: data[0],
				1050: data[1],
				1039: data[2],
				1049: data[3],
				1053: data[4]
			};
			renderMessages();
			updateLocalStorage();
			loadingOverlay.classList.add('hidden');
			loadingIcon.innerHTML = 'cloud_done';
		}).catch(err => {
			switch (err) {
				case 'expired':
					renewSession(init);
				default:
					alert('An error occured');
					console.error(err);
			}
		});
	}

	function getMessages(board) {
		return new Promise((res, rej) => {
			if (!isNumber(board)) return false;
			fetch(`api/messages.php?&board=${encodeURIComponent(board)}`, {
				credentials: 'include'
			}).then(res => res.json()).then(data => {
				console.info(`Loaded Board ${board}`);
				if (data.success) res(data.content);
				else throw data.error;
			}).catch(err => rej(err));
		});
	}

	function renderMessages(query = '') {
		console.info('Rendering messages...');

		// Clears the div of messages
		while (listElement.hasChildNodes()) listElement.removeChild(listElement.lastChild);

		list = [];
		filterBoard.forEach(board => list = list.concat(messages[board]));
		list = list.sort((a, b) => new Date(b.date) - new Date(a.date));
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
	}

	function renewSession(callback) {
		loadingDesc.innerHTML = 'Renewing Session';
		fetch('api/login.php', {
			method: 'post',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: `username=${encodeURIComponent(cookie.get('username'))}&password=${encodeURIComponent(cookie.get('password'))}`
		}).then(res => res.json()).then(data => {
			if (data.success) {
				cookie.set('sessid', data.content, 1800);
				callback();
			}
			else throw data.error;
		}).catch(err => {
			switch (err) {
				case 'credentials':
					logout();
					break;
				default:
					alert('An error occured');
					console.error(err);
			}
		});
	}

	function logout() {
		fetch('api/logout.php', {
			method: 'post',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: `sessid=${encodeURIComponent(cookie.get('sessid'))}`
		}).then(res => res.json()).then(data => {
			if (!data.success) throw data.error;
			localStorage.clear();
			cookie.delete('username');
			cookie.delete('password');
			cookie.delete('sessid');
			// TODO: Clear service worker here
			window.location = 'login.html';
		}).catch(err => {
			switch (err) {
				case 'expired':
					break;
				default:
				alert('An error occured');
				console.error(err);
			}
		});
	}

	// Event listener to open dropdown
	document.getElementById('filter-button').addEventListener('click', () => document.getElementById('filter-container').classList.toggle('open-dropdown'));

	document.getElementById('clear-all').addEventListener('click', () => {
		let unreads = [];
		for (board in messages) {
			board.forEach(message => {
				if (message.unread) {
					unreads += message.id;
					message.unread = false;
				}
			})
		}
		fetch('api/readall.php', {
			method: 'post',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: `sessid=${encodeURIComponent(cookie.get('sessid'))}&${encodeURIComponent(JSON.stringify(unreads))}`
		}).then(res => res.json()).then(data => {
			if (!data.success) throw data.error
		}).catch(err => {
			alert('An error occured');
			console.error(err);
		})
	});

	// Event listener to log out
	document.getElementById('logout-button').addEventListener('click', logout);

	// Event listener to load messages
	listElement.addEventListener('click', e => {
		if (e.target !== e.currentTarget) {
			const target = e.target.parentNode === listElement ? e.target : e.target.parentNode;
			console.log('Running Message Load Event Listener');
			if (document.getElementById('selected')) document.getElementById('selected').id = '';
			target.id = 'selected';

			while (viewElement.hasChildNodes()) viewElement.removeChild(viewElement.lastChild);

			loadingOverlay.classList.remove('hidden');
			loadingDesc.innerHTML = 'Loading Message';

			viewElement.scrollTop = 0;

			fetch(`api/message.php?message=${encodeURIComponent(target.dataset.id)}`, {
				credentials: 'include'
			}).then(res => res.json()).then(data => {
				if (!data.success) throw data.error;

				// TODO: Improve this function
				for (let message of messages[target.dataset.board]) {
					if (message.id == target.dataset.id) {
						if (message.unread) message.unread = false;
						else break;
						updateLocalStorage();
					}
				}

				const metadata = document.createElement('section');

				const title = document.createElement('div');
				title.appendChild(document.createTextNode(data.content.metadata.title));

				const sender = document.createElement('div');
				sender.appendChild(document.createTextNode(data.content.metadata.sender.toLowerCase()));

				const datetime = document.createElement('div');
				datetime.appendChild(document.createTextNode(data.content.metadata.datetime));

				metadata.appendChild(title);
				metadata.appendChild(sender);
				metadata.appendChild(datetime);

				viewElement.appendChild(metadata);

				viewElement.innerHTML += data.content.message;

				if (data.content.attachments.length > 0) {
					console.info('Loading attachments');
					const details = document.createElement('details');

					const summary = document.createElement('summary');
					summary.appendChild(document.createTextNode('Attachments'));
					details.appendChild(summary);

					data.content.attachments.forEach(attachment => {
						const link = document.createElement('a');
						link.href = `api/attachment.php?board=${encodeURIComponent(target.dataset.board)}&message=${encodeURIComponent(target.dataset.id)}&name=${encodeURIComponent(attachment)}`
						link.appendChild(document.createTextNode(attachment));
						details.appendChild(link);
					});

					viewElement.appendChild(details);
				}

				if (data.content.form.present) {
					console.info('Loading form');
					const details = document.createElement('details');

					const summary = document.createElement('summary');
					summary.appendChild(document.createTextNode('Response'));
					details.appendChild(summary);

					const form = document.createElement('form');

					const textbox = document.createElement('textarea');
					textbox.appendChild(document.createTextNode(data.content.form.comment));
					textbox.placeholder = 'Your Response';

					const submit = document.createElement('button');
					submit.appendChild(document.createTextNode('Respond'));

					const letters = ['A', 'B', 'C', 'D', 'E'];
					for (let i = 0; i < 5; i++) {
						const radio = document.createElement('input');
						radio.type = 'radio';
						radio.value = letters[i];
						radio.name = 'response';
						radio.id = `radio-${letters[i]}`;
						if (data.content.form.selected == letters[i]) radio.checked = true;

						const label = document.createElement('label');

						const radioIndicator = document.createElement('span');
						radioIndicator.classList.add('radio-indicator');
						label.appendChild(radioIndicator);

						label.appendChild(document.createTextNode(letters[i]));
						label.setAttribute('for', `radio-${letters[i]}`);

						form.appendChild(radio);
						form.appendChild(label);
					}
					form.appendChild(submit);
					form.appendChild(textbox);

					form.addEventListener('submit', e => {
						e.preventDefault();
						fetch('api/reply.php', {
							method: 'post',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded'
							},
							body: `sessid=${encodeURIComponent(cookie.get('sessid'))}&message=${encodeURIComponent(target.dataset.id)}&board=${encodeURIComponent(target.dataset.board)}&comment=${encodeURIComponent(textbox.value)}&selection=${encodeURIComponent(form.response.value)}`
						}).then(res => res.json()).then(data => {
							if (!data.success) throw data.error;
						}).catch(err => {
							switch (err) {
								case 'expired':
									submit.click();
									break;
								default:
								alert('An error occured');
								console.error(err);
							}
						});
					});

					details.appendChild(form);
					viewElement.appendChild(details);
				}
				loadingOverlay.classList.add('hidden');
			}).catch(err => {
				switch (err) {
					case 'expired':
						renewSession(() => {document.getElementById('selected').click();});
						break;
					default:
						alert('An error occured');
						console.error(err);
						loadingOverlay.classList.add('hidden');
				}
			});
		}
		e.stopPropagation();
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

	loadingDesc.innerHTML = 'Checking Session';

	// Check if session ID has expired
	fetch('api/checkValidity.php', {
		method: 'post',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: `sessid=${encodeURIComponent(cookie.get('sessid'))}`
	}).then(res => res.json()).then(data => {
		if (data.success) {
			if (data.content) {
				if (!firstRun) loadingOverlay.classList.add('hidden');
				else loadingDesc.innerHTML = 'Loading Messages';
				init();
			}
			else {
				renewSession(() => {
					if (!firstRun) loadingOverlay.classList.add('hidden');
					else loadingDesc.innerHTML = 'Loading Messages';
					init();
				});
			}
		}
		else throw data.error;
	}).catch(err => {
		alert('An error occured');
		console.error(err);
	});

	// Refresh messages every 15 minutes
	setInterval(init, 900000);
});