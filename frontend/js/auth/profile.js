const username = document.getElementById('username');
const email = document.getElementById('email');
const date = document.getElementById('date');
const role = document.getElementById('role');
const profilePhoto = document.getElementById('profile-photo');
const logoutButton = document.getElementById('logout-button');
const navItems = document.getElementById('nav-items');

async function fetchProfile() {
    const authToken = localStorage.getItem('auth_token');
    const response = await fetch('../../api/auth/get.php', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + authToken
        }
    });
    const result = await response.json();
    if (response.ok) {
        username.textContent = result.data.username;
        role.textContent = result.data.role;

        if (result.data.role === 'superadmin') {
            const link = `<a class="nav-link" aria-current="page" href="/carrental/frontend/auth/promote-user.html">Create Admin</a>`
            navItems.insertAdjacentHTML('afterbegin', link);
        }

        email.textContent = 'Email: ' + result.data.email;
        date.textContent = 'Date Join: ' + new Date(result.data.created_at).toLocaleDateString();
        profilePhoto.src = result.data.profile_photo;
    } else {
        alert('Failed to fetch profile: ' + result.message);
        if (response.status === 401) {
            window.location.href = 'login.html';
        }
    }
}

async function logout() {
    localStorage.removeItem('auth_token');

    await fetch('../../api/auth/logout.php', {
        method: 'POST'
    });

    window.location.href = 'login.html';
}

window.addEventListener('load', async () => {
    await fetchProfile();
});

logoutButton.addEventListener('click', async (e) => {
    e.preventDefault();
    await logout();
});
