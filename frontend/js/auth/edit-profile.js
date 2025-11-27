const previewPhoto = document.getElementById('preview-photo');
const profilePhotoInput = document.getElementById('profile-photo');
const usernameInput = document.getElementById('username');
const oldPasswordInput = document.getElementById('old-password');
const newPasswordInput = document.getElementById('new-password');
const editProfileForm = document.getElementById('edit-profile-form');
const deleteAccountBtn = document.getElementById('delete-account-btn');
const userEmail = document.getElementById('user-email');
const token = localStorage.getItem('token');

function ensureAuth() {
    if (!token) {
        window.location.href = './login.html';
    }
}

async function loadProfile() {
    ensureAuth();

    const response = await fetch('../../api/auth/getUser.php', {
        headers: {
            Authorization: 'Bearer ' + token
        }
    });

    const result = await response.json();

    if (!response.ok) {
        alert('Gagal memuat profil: ' + result.message);
        window.location.href = './login.html';
        return;
    }

    const user = result.data;
    previewPhoto.src = user.profile_picture;
    usernameInput.value = user.username || '';
    userEmail.textContent = user.email || '';
}

async function submitProfile(event) {
    event.preventDefault();
    ensureAuth();

    const oldPassword = oldPasswordInput.value.trim();
    const newPassword = newPasswordInput.value.trim();

    const wantsPasswordChange = oldPassword || newPassword;
    if (wantsPasswordChange && (!oldPassword || !newPassword)) {
        alert('Isi kedua field password untuk mengganti kata sandi.');
        return;
    }

    const formData = new FormData();
    if (profilePhotoInput.files[0]) {
        formData.append('profile_picture', profilePhotoInput.files[0]);
    }
    formData.append('username', usernameInput.value.trim());
    if (wantsPasswordChange) {
        formData.append('old_password', oldPassword);
        formData.append('new_password', newPassword);
    }

    const response = await fetch('../../api/auth/updateProfile.php', {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });

    const result = await response.json();

    if (response.ok) {
        alert('Profil berhasil diperbarui.');
        window.location.href = './profile.html';
    } else {
        alert('Gagal memperbarui profil: ' + result.message);
    }
}

function handlePreview() {
    const file = profilePhotoInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        previewPhoto.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

async function deleteAccount() {
    ensureAuth();
    if (!confirm('Akun akan dihapus permanen. Lanjutkan?')) return;

    const response = await fetch('../../api/auth/deleteAccount.php', {
        method: 'DELETE',
        headers: {
            Authorization: 'Bearer ' + token
        }
    });

    const result = await response.json().catch(() => ({}));

    if (response.ok) {
        alert(result.message || 'Akun berhasil dihapus.');
        localStorage.removeItem('token');
        window.location.href = './register.html';
    } else {
        alert('Gagal menghapus akun: ' + (result.message || 'Terjadi kesalahan.'));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadProfile();
    profilePhotoInput.addEventListener('change', handlePreview);
    editProfileForm.addEventListener('submit', submitProfile);
    deleteAccountBtn.addEventListener('click', deleteAccount);
});
