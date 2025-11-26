const userProfilePhoto = document.getElementById('user-profile-photo');
const userName = document.getElementById('user-username');
const userEmail = document.getElementById('user-email');

async function fetchUserProfile() {
    const token = localStorage.getItem('token');

    const response = await fetch('../../api/auth/getUser.php', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });
    
    const result = await response.json();

    if (response.ok) {
        userProfilePhoto.src = result.data.profile_picture;
        userName.textContent = result.data.username;
        userEmail.textContent = result.data.email;
    } else {
        alert('Failed to fetch profile: ' + result.message);
        window.location.href = './login.html';
    }
}

document.addEventListener('DOMContentLoaded', fetchUserProfile);
