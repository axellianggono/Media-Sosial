const loginForm = document.getElementById('login-form');
const email = document.getElementById('email');
const password = document.getElementById('password');

async function loginUser(event) {
    event.preventDefault();

    const payload = {
        email: email.value,
        password: password.value
    };

    const response = await fetch('../../api/auth/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (response.ok) {
        alert('Login successful!');
        localStorage.setItem('token', result.data.token);
        window.location.href = './profile.html';
    } else {
        alert('Login failed: ' + result.message);
    }
}

loginForm.addEventListener('submit', loginUser);
