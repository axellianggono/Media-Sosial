const token = localStorage.getItem('token');
const postRows = document.getElementById('admin-post-rows');
const commentRows = document.getElementById('admin-comment-rows');
const refreshActiveBtn = document.getElementById('refresh-active');
const tabPosts = document.getElementById('tab-posts');
const tabComments = document.getElementById('tab-comments');
const postsCard = document.getElementById('posts-card');
const commentsCard = document.getElementById('comments-card');
let activeTab = 'posts';

function requireAuth() {
    if (!token) {
        window.location.href = './login.html';
    }
}

async function guardAdmin() {
    requireAuth();
    const response = await fetch('../../api/auth/getUser.php', {
        headers: { Authorization: 'Bearer ' + token }
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || (result.data?.role !== 'admin' && result.data?.role !== 'superadmin')) {
        alert('Hanya admin/superadmin yang dapat mengakses halaman ini.');
        window.location.href = './profile.html';
        return false;
    }
    return true;
}

function renderPostRows(posts) {
    if (!posts.length) {
        postRows.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada postingan.</td></tr>';
        return;
    }

    postRows.innerHTML = posts.map(post => {
        const created = post.created_at ? new Date(post.created_at).toLocaleString() : '-';
        const title = post.title || `Post #${post.post_id}`;
        const username = post.username || `#${post.user_id}`;
        return `
            <tr data-post-id="${post.post_id}">
                <td class="text-muted">${title}</td>
                <td class="text-muted">${username}</td>
                <td class="text-muted">${created}</td>
                <td class="text-end">
                    <button class="btn btn-danger btn-sm delete-post-btn" data-post-id="${post.post_id}">Delete</button>
                </td>
            </tr>
        `;
    }).join('');

    postRows.querySelectorAll('.delete-post-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            await deletePost(postId);
        });
    });
}

function renderCommentRows(comments) {
    if (!comments.length) {
        commentRows.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada komentar.</td></tr>';
        return;
    }

    commentRows.innerHTML = comments.map(comment => {
        const created = comment.created_at ? new Date(comment.created_at).toLocaleString() : '-';
        const username = comment.username || `#${comment.user_id}`;
        const postTitle = comment.post_title || (comment.post_id ? `#${comment.post_id}` : '-');
        return `
            <tr data-comment-id="${comment.comment_id}">
                <td>${comment.content || ''}</td>
                <td class="text-muted">${username}</td>
                <td class="text-muted">${postTitle}</td>
                <td class="text-muted">${created}</td>
                <td class="text-end">
                    <button class="btn btn-danger btn-sm delete-comment-btn" data-comment-id="${comment.comment_id}">Delete</button>
                </td>
            </tr>
        `;
    }).join('');

    commentRows.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const commentId = btn.dataset.commentId;
            await deleteComment(commentId);
        });
    });
}

async function loadPosts() {
    postRows.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Memuat postingan...</td></tr>';
    const response = await fetch('../../api/auth/getAllPosts.php', {
        headers: { Authorization: 'Bearer ' + token }
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        postRows.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${result.message || 'Gagal memuat postingan.'}</td></tr>`;
        return;
    }
    renderPostRows(result.data || []);
}

async function loadComments() {
    commentRows.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Memuat komentar...</td></tr>';
    const response = await fetch('../../api/auth/getAllComments.php', {
        headers: { Authorization: 'Bearer ' + token }
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        commentRows.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${result.message || 'Gagal memuat komentar.'}</td></tr>`;
        return;
    }
    renderCommentRows(result.data || []);
}

async function deletePost(postId) {
    if (!confirm('Hapus postingan ini?')) return;
    const response = await fetch('../../api/auth/deletePost.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token
        },
        body: JSON.stringify({ postId: Number(postId) })
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        alert(result.message || 'Gagal menghapus postingan.');
        return;
    }
    alert(result.message || 'Postingan dihapus.');
    loadPosts();
}

async function deleteComment(commentId) {
    if (!confirm('Hapus komentar ini?')) return;
    const response = await fetch('../../api/auth/deleteComment.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token
        },
        body: JSON.stringify({ comment_id: Number(commentId) })
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        alert(result.message || 'Gagal menghapus komentar.');
        return;
    }
    alert(result.message || 'Komentar dihapus.');
    loadComments();
}

function setupTabs() {
    tabPosts.addEventListener('click', () => {
        tabPosts.classList.add('active');
        tabComments.classList.remove('active');
        postsCard.classList.remove('d-none');
        commentsCard.classList.add('d-none');
        activeTab = 'posts';
        refreshActiveBtn.textContent = 'Refresh Posts';
    });
    tabComments.addEventListener('click', () => {
        tabComments.classList.add('active');
        tabPosts.classList.remove('active');
        commentsCard.classList.remove('d-none');
        postsCard.classList.add('d-none');
        activeTab = 'comments';
        refreshActiveBtn.textContent = 'Refresh Comments';
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    const allowed = await guardAdmin();
    if (!allowed) return;
    loadPosts();
    loadComments();
    refreshActiveBtn.addEventListener('click', () => {
        if (activeTab === 'posts') {
            loadPosts();
        } else {
            loadComments();
        }
    });
    setupTabs();
});
