const registerForm = document.getElementById('register-form');
const username = document.getElementById('username');
const email = document.getElementById('email');
const password = document.getElementById('password');

async function registerUser(event) {
    event.preventDefault();

    const payload = {
        username: username.value,
        email: email.value,
        password: password.value
    };

    const response = await fetch('../../api/auth/register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const result = await response.json();

    if (response.ok) {
        alert('Registration successful! Please check your email to verify your account.');
        window.location.href = './verify.html';
    } else {
        alert('Registration failed: ' + result.message);
    }
}

registerForm.addEventListener('submit', registerUser);