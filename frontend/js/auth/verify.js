const verifyButton = document.getElementById('verify-button');

async function verify() {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('verify_token');
    const code = document.getElementById('code').value;

    const payload = {
        verify_token: token,
        verify_code: code
    }

    const response = await fetch('../../api/auth/verify.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (response.ok) {
        alert(result.message);
        window.location.href = 'login.html';
    } else {
        alert('Verification failed: ' + result.message);
    }
}

verifyButton.addEventListener('click', async (e) => {
    e.preventDefault();
    await verify();
});