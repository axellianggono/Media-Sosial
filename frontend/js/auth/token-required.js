const token = localStorage.getItem('auth_token');

if (!token) {
    alert('You must be logged in to access this page.');

    window.location.href = 'login.html';
}

async function isAdmin() {
    const response = await fetch('../../api/auth/get.php', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });

    const result = await response.json();

    if (response.ok) {
        return result.data.role === 'admin';
    } else {
        return false;
    }
}

async function isSuperAdmin() {
    const response = await fetch('../../api/auth/get.php', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });
    const result = await response.json();
    if (response.ok) {
        return result.data.role === 'superadmin';
    } else {
        return false;
    }
}

function verifyUserRole(allowedRoles) {
    return isAdmin().then(isAdmin => {
        return isSuperAdmin().then(isSuperAdmin => {
            if (allowedRoles.includes('admin') && isAdmin) {
                return true;
            }
            if (allowedRoles.includes('superadmin') && isSuperAdmin) {
                return true;
            }
            if (allowedRoles.includes('user') && !isAdmin && !isSuperAdmin) {
                return true;
            }
            alert('You do not have permission to access this page.');
            window.location.href = 'profile.html';
            return false;
        }
        );
    });
}