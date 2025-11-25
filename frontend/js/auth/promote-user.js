isSuperAdmin().then(isSuperAdmin => {
        if (isSuperAdmin) {
            return true;
        } else {
            alert('You do not have permission to access this page.');
            window.location.href = 'profile.html';
            return false;
        }
    });

const adminList = document.getElementById('admin-list');
const promoteButton = document.getElementById('promote-button');

async function fetchAdmins() {
    const authToken = localStorage.getItem('auth_token');
    const response = await fetch('../../api/auth/getAllAdmin.php', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + authToken
        }
    });
    const result = await response.json();
    if (response.ok) {
        adminList.innerHTML = '';
        result.data.forEach(admin => {
            const listItem = `
                <li class="list-group-item">
                    <span>${admin.email}</span>
                    <button class="btn btn-sm btn-danger rounded-pill float-end" id="demote-button" onclick="demoteAdmin('${admin.email}')">Demote</button>
                </li>
                `;
            adminList.insertAdjacentHTML('beforeend', listItem);
        }
        );
    } else {
        alert('Failed to fetch admins: ' + result.message);
    }
}

async function promote() {
    const emailInput = document.getElementById('email').value;
    const authToken = localStorage.getItem('auth_token');
    const response = await fetch('../../api/auth/promote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + authToken
        },
        body: JSON.stringify({ email: emailInput })
    });
    const result = await response.json();
    if (response.ok) {
        alert('User promoted to admin successfully.');
        await fetchAdmins();
    } else {
        alert('Failed to promote user: ' + result.message);
    }
}

async function performDemotion(email) {
    const authToken = localStorage.getItem('auth_token');
    const response = await fetch('../../api/auth/demote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + authToken
        },
        body: JSON.stringify({ email: email })
    });
    const result = await response.json();
    if (response.ok) {
        alert('Admin demoted successfully.');
        await fetchAdmins();
    } else {
        alert('Failed to demote admin: ' + result.message);
    }
}

function demoteAdmin(email) {
    if (confirm(`Are you sure you want to demote ${email} from admin?`)) {
        performDemotion(email);
    }
}

promoteButton.addEventListener('click', async (e) => {
    e.preventDefault();
    await promote();
});

window.addEventListener('load', async () => {
    await fetchAdmins();
});