const verificationForm = document.getElementById('verification-form');
const verificationCode = document.getElementById('verification-code');
const verificationToken = new URLSearchParams(window.location.search).get('token');

async function verifyAccount(event) {
    event.preventDefault();

    const payload = {
        verificationCode: verificationCode.value,
        verificationToken: verificationToken
    };

    const response = await fetch('../../api/auth/verify.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (response.ok) {
        alert('Account verified successfully! You can now log in.');
        window.location.href = './login.html';
    } else {
        alert('Verification failed: ' + result.message);
    }
}

verificationForm.addEventListener('submit', verifyAccount);
