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
			console.log(data);
			if (document.getElementById('error')) 
				form.removeChild(document.getElementById('error'));
			if (data.success) {
				cookie.set('sessid', data.content.sessid, 1800);
				cookie.set('authtoken', data.content.authtoken, 1800);
				cookie.set('username', form.username.value, 15552000);
				cookie.set('password', form.password.value, 15552000);
				window.location = 'index.html';
			}
			else throw data.error;
		}).catch(err => {
			const errorParent = document.createElement('div');
			let error;
			errorParent.id = 'error';
			console.log(err);
			switch (err) {
				case 'credentials':
					error = document.createTextNode('Wrong username/password');
					break;
				default:
					error = document.createTextNode('An unknown error occured');
			}
			errorParent.appendChild(error);
			form.insertBefore(errorParent, form.childNodes[5]);
		});
	});
});