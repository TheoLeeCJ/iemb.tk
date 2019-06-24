// Service Worker checker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js')
			.then((reg) => {
				console.log('Service worker registered.', reg);
			});
  });
}

// Somewhere here, we need to clear the service worker before allowing a **new** user to log in
document.addEventListener('DOMContentLoaded', () => {
	const form = document.getElementsByTagName('form')[0];
	form.addEventListener('submit', e => {
		e.preventDefault();
		fetch('api/login.php', {
			method: 'post',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: `username=${encodeURIComponent(form.username.value)}&password=${encodeURIComponent(form.password.value)}`
		}).then(res => res.json()).then(data => {
			if (document.getElementById('error')) 
				form.removeChild(document.getElementById('error'));
			if (data.success) {
				cookie.set('sessid', data.content, 1800);
				cookie.set('username', form.username.value, 15552000);
				cookie.set('password', form.password.value, 15552000);

				window.location = 'index.html';

				// Go to tutorial if there's no prior data in localStorage, else it's the app
				//if (localStorage.getItem('messages') === null) window.location = 'tutorial.html';
				//else window.location = 'index.html';
			}
			else throw data.error;
		}).catch(err => {
			const errorParent = document.createElement('div');
			let error;
			errorParent.id = 'error';
			console.log(err);
			switch (err) {
				case 'credentials':
					error = document.createTextNode('Wrong username/password.');
					break;
				default:
					error = document.createTextNode('An unknown error occured; it\'s likely that the iEMB server is down. Please try again later.');
			}
			errorParent.appendChild(error);
			form.insertBefore(errorParent, form.childNodes[5]);
		});
	});
});