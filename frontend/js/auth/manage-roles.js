const token = localStorage.getItem('token');
const userRows = document.getElementById('user-rows');
const refreshBtn = document.getElementById('refresh-btn');

function requireAuth() {
    if (!token) {
        window.location.href = './login.html';
    }
}

function renderPlaceholder(message) {
    userRows.innerHTML = `<tr><td colspan="5" class="text-center text-muted">${message}</td></tr>`;
}

function buildRoleBadge(role) {
    const className = role === 'superadmin' ? 'bg-warning text-dark' : role === 'admin' ? 'bg-primary' : 'bg-secondary';
    return `<span class="badge ${className} text-uppercase">${role}</span>`;
}

function renderUsers(users) {
    if (!users.length) {
        renderPlaceholder('Tidak ada user ditemukan.');
        return;
    }

    userRows.innerHTML = users.map(user => {
        const created = new Date(user.created_at).toLocaleDateString();
        const role = user.role || 'user';
        const disablePromote = role !== 'user';
        const disableDemote = role === 'user' || role === 'superadmin';

        return `
            <tr data-user-id="${user.user_id}">
                <td class="fw-semibold text-dark">${user.username}</td>
                <td class="text-muted">${user.email}</td>
                <td>${buildRoleBadge(role)}</td>
                <td class="text-muted">${created}</td>
                <td class="text-end">
                    <button class="btn btn-outline-light btn-sm me-1 promote-btn" ${disablePromote ? 'disabled' : ''}>Promote</button>
                    <button class="btn btn-danger btn-sm demote-btn" ${disableDemote ? 'disabled' : ''}>Demote</button>
                </td>
            </tr>
        `;
    }).join('');

    attachActionHandlers();
}

function attachActionHandlers() {
    const promoteButtons = document.querySelectorAll('.promote-btn');
    const demoteButtons = document.querySelectorAll('.demote-btn');

    promoteButtons.forEach(btn => {
        btn.addEventListener('click', async event => {
            const userId = event.target.closest('tr').dataset.userId;
            await updateRole(userId, 'promote');
        });
    });

    demoteButtons.forEach(btn => {
        btn.addEventListener('click', async event => {
            const userId = event.target.closest('tr').dataset.userId;
            await updateRole(userId, 'demote');
        });
    });
}

async function fetchUsers() {
    requireAuth();
    renderPlaceholder('Loading users...');

    const response = await fetch('../../api/auth/getAllUsers.php', {
        headers: {
            Authorization: 'Bearer ' + token
        }
    });

    const result = await response.json();

    if (!response.ok) {
        renderPlaceholder('Gagal memuat data pengguna.');
        alert('Tidak bisa mengambil user: ' + result.message);
        if (response.status === 401) {
            window.location.href = './login.html';
        }
        return;
    }

    renderUsers(result.data || []);
}

async function updateRole(userId, action) {
    const endpoint = action === 'promote' ? '../../api/auth/promoteUser.php' : '../../api/auth/demoteAdmin.php';
    const confirmMsg = action === 'promote' ? 'Promote user ini menjadi admin?' : 'Demote admin ini ke user?';
    if (!confirm(confirmMsg)) return;

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token
        },
        body: JSON.stringify({ userId: Number(userId) })
    });

    const result = await response.json();

    if (response.ok) {
        alert(result.message || 'Berhasil memperbarui role.');
        fetchUsers();
    } else {
        alert('Gagal mengubah role: ' + result.message);
    }
}

async function guardSuperadmin() {
    requireAuth();
    const response = await fetch('../../api/auth/getUser.php', {
        headers: {
            Authorization: 'Bearer ' + token
        }
    });

    const result = await response.json();

    if (!response.ok || result.data?.role !== 'superadmin') {
        alert('Halaman ini hanya untuk superadmin.');
        window.location.href = './profile.html';
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', async () => {
    const allowed = await guardSuperadmin();
    if (allowed) {
        fetchUsers();
        refreshBtn.addEventListener('click', fetchUsers);
    }
});
