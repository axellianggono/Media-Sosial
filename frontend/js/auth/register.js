const loginButton = document.getElementById('register-button');

async function register() {
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const payload = {
        username: username,
        email: email,
        password: password
    }

    const response = await fetch('../../api/auth/register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const result = await response.json();

    if (response.ok) {
        alert(result.message);
    } else {
        alert('Registration failed: ' + result.message);
    }
}

loginButton.addEventListener('click', async (e) => {
    e.preventDefault();
    await register();
});