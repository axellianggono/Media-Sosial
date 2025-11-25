const loginButton = document.getElementById('login-button');

async function login() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const payload = {
        email: email,
        password: password
    }

    const response = await fetch('../../api/auth/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (response.ok) {
        alert(result.message);
        localStorage.setItem('auth_token', result.data.token);
        window.location.href = './profile.html';
    } else {
        alert('Login failed: ' + result.message);
    }
}

loginButton.addEventListener('click', async (e) => {
    e.preventDefault();
    await login();
});
