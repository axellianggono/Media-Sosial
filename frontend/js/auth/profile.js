const token = localStorage.getItem('token');
const userProfilePhoto = document.getElementById('user-profile-photo');
const userName = document.getElementById('user-username');
const userEmail = document.getElementById('user-email');
const userJoined = document.getElementById('user-joined');
const manageRolesBtn = document.getElementById('manage-roles-btn');
const manageContentBtn = document.getElementById('manage-content-btn');
const contentList = document.getElementById('content-list');
const tabPosts = document.getElementById('tab-posts');
const tabComments = document.getElementById('tab-comments');
const postCommentsCache = new Map();
let isAdmin = false;
let currentUser;
let cachedPosts = null;
let cachedComments = null;

async function fetchUserProfile() {
    if (!token) {
        window.location.href = './login.html';
        return;
    }

    const response = await fetch('../../api/auth/getUser.php', {
        method: 'GET',
        headers: {
            Authorization: 'Bearer ' + token
        }
    });
    
    const result = await response.json();

    if (response.ok) {
        const user = result.data;
        currentUser = user;
        isAdmin = user.role === 'admin' || user.role === 'superadmin';
        userProfilePhoto.src = user.profile_picture;
        userName.textContent = user.username;
        userEmail.textContent = user.email;
        const createdDate = user.created_at ? new Date(user.created_at) : null;

        userJoined.textContent = createdDate ? `Joined ${createdDate.toLocaleDateString()}` : 'Joined -';

        if (user.role === 'superadmin') {
            manageRolesBtn?.classList.remove('d-none');
        }
        if (isAdmin) {
            manageContentBtn?.classList.remove('d-none');
        }

        // Load default tab
        loadPosts();
    } else {
        alert('Failed to fetch profile: ' + result.message);
        window.location.href = './login.html';
    }
}

function renderPlaceholder(message) {
    contentList.innerHTML = `<div class="content-card text-center text-muted">${message}</div>`;
}

function renderPosts(posts) {
    if (!posts || posts.length === 0) {
        renderPlaceholder('Belum ada postingan.');
        return;
    }

    contentList.innerHTML = posts.map(post => {
        const created = post.created_at ? new Date(post.created_at).toLocaleString() : '-';
        const title = post.title || `Post #${post.post_id}`;
        const desc = post.content || '';
        let imageUrl = post.image_url || '';
        const isAbsolute = imageUrl.startsWith('http://') || imageUrl.startsWith('https://') || imageUrl.startsWith('data:');
        if (imageUrl && !isAbsolute) {
            imageUrl = `../../api/storage/images/${imageUrl.replace(/^\//, '')}`;
        }
        const showDelete = post.user_id === currentUser?.user_id;

        return `
            <div class="content-card">
                <div class="row g-3 align-items-center">
                    ${imageUrl ? `<div class="col-md-3"><img src="${imageUrl}" alt="${title}"></div>` : ''}
                    <div class="${imageUrl ? 'col-md-9' : 'col-12'}">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1 text-white">${title}</h6>
                                <div class="content-meta">Dibuat ${created}</div>
                            </div>
                        </div>
                        ${desc ? `<p class="mb-2 content-text">${desc}</p>` : ''}
                        <div class="action-links d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-secondary btn-sm see-comments-btn" data-post-id="${post.post_id}">See Comments</button>
                            ${showDelete ? `<button class="btn btn-outline-secondary btn-sm update-post-btn" data-post-id="${post.post_id}">Update</button>` : ''}
                            ${showDelete ? `<button class="btn btn-danger btn-sm delete-post-btn" data-post-id="${post.post_id}">Delete</button>` : ''}
                        </div>
                        <div class="post-comments d-none mt-2" data-comments-for="${post.post_id}"></div>
                        <div class="post-update d-none mt-2" data-update-for="${post.post_id}">
                            <form class="update-post-form" data-post-id="${post.post_id}">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Title</label>
                                        <input type="text" class="form-control form-control-sm" name="title" value="${title}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Picture</label>
                                        <input type="file" class="form-control form-control-sm" name="picture" accept="image/*">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label mb-1">Caption</label>
                                        <textarea class="form-control form-control-sm" rows="2" name="caption">${desc}</textarea>
                                    </div>
                                    <div class="col-12 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm cancel-update-post" data-post-id="${post.post_id}">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    attachPostInteractions();
}

function renderComments(comments) {
    if (!comments || comments.length === 0) {
        renderPlaceholder('Belum ada komentar.');
        return;
    }

    contentList.innerHTML = comments.map(comment => {
        const created = comment.created_at ? new Date(comment.created_at).toLocaleString() : '-';
        const postLink = comment.post_title || (comment.post_id ? `Post #${comment.post_id}` : 'Post tidak ditemukan');
        const showDelete = comment.user_id === currentUser?.user_id;
        return `
            <div class="content-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-1 content-text" data-comment-text="${comment.comment_id}">${comment.content || ''}</p>
                        <div class="content-meta">Pada ${postLink} • ${created}</div>
                    </div>
                    <div class="action-links d-flex gap-2 ms-2">
                        ${showDelete ? `
                            <button class="btn btn-outline-secondary btn-sm update-comment-btn" data-comment-id="${comment.comment_id}">Update</button>
                            <button class="btn btn-danger btn-sm delete-comment-btn" data-comment-id="${comment.comment_id}">Delete</button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
    attachCommentDelete();
    attachCommentUpdate();
}

async function loadPosts() {
    if (!currentUser) return;
    tabPosts.classList.add('active');
    tabComments.classList.remove('active');

    if (cachedPosts) {
        renderPosts(cachedPosts);
        return;
    }

    renderPlaceholder('Memuat postingan...');
    const response = await fetch('../../api/auth/getAllPosts.php', {
        headers: { Authorization: 'Bearer ' + token }
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        renderPlaceholder(result.message || 'Gagal memuat postingan.');
        return;
    }

    const data = result.data || [];
    const mine = data.filter(post => post.user_id === currentUser.user_id);
    cachedPosts = mine;
    renderPosts(mine);
}

async function loadComments() {
    if (!currentUser) return;
    tabComments.classList.add('active');
    tabPosts.classList.remove('active');

    if (cachedComments) {
        renderComments(cachedComments);
        return;
    }

    renderPlaceholder('Memuat komentar...');
    const response = await fetch('../../api/auth/getAllComments.php', {
        headers: { Authorization: 'Bearer ' + token }
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        renderPlaceholder(result.message || 'Gagal memuat komentar.');
        return;
    }
    const data = result.data || [];
    const mine = data.filter(comment => comment.user_id === currentUser.user_id);
    cachedComments = mine;
    renderComments(mine);
}

function setupTabs() {
    tabPosts?.addEventListener('click', loadPosts);
    tabComments?.addEventListener('click', loadComments);
}

function attachCommentDelete() {
    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const commentId = btn.dataset.commentId;
            await deleteComment(commentId);
        });
    });
}

function attachCommentUpdate() {
    document.querySelectorAll('.update-comment-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const commentId = btn.dataset.commentId;
            const textEl = document.querySelector(`[data-comment-text="${commentId}"]`);
            if (!textEl) return;

            const currentText = textEl.textContent;
            const wrapper = document.createElement('div');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = currentText;

            const saveBtn = document.createElement('button');
            saveBtn.className = 'btn btn-primary btn-sm mt-2';
            saveBtn.textContent = 'Save';

            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'btn btn-outline-secondary btn-sm mt-2 ms-2';
            cancelBtn.textContent = 'Cancel';

            wrapper.appendChild(input);
            const btnGroup = document.createElement('div');
            btnGroup.className = 'd-flex gap-2';
            btnGroup.appendChild(saveBtn);
            btnGroup.appendChild(cancelBtn);
            wrapper.appendChild(btnGroup);

            // replace text with form
            textEl.replaceWith(wrapper);

            cancelBtn.addEventListener('click', () => {
                wrapper.replaceWith(textEl);
            });

            saveBtn.addEventListener('click', async () => {
                const newContent = input.value.trim();
                if (!newContent) {
                    alert('Komentar tidak boleh kosong.');
                    return;
                }

                const response = await fetch('../../api/coment/update.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ comment_id: Number(commentId), content: newContent, token })
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    alert(result.message || 'Gagal memperbarui komentar.');
                    return;
                }

                textEl.textContent = newContent;
                wrapper.replaceWith(textEl);
                alert(result.message || 'Komentar diperbarui.');
            });
        });
    });
}

async function deletePost(postId) {
    if (!confirm('Hapus postingan ini?')) return;
    const response = await fetch('../../api/posts/delete.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: Number(postId), token })
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        alert(result.message || 'Gagal menghapus postingan.');
        return;
    }
    alert(result.message || 'Postingan dihapus.');
    cachedPosts = null;
    loadPosts();
}

async function deleteComment(commentId) {
    if (!confirm('Hapus komentar ini?')) return;
    const response = await fetch('../../api/coment/delete.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ comment_id: Number(commentId), token })
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        alert(result.message || 'Gagal menghapus komentar.');
        return;
    }
    alert(result.message || 'Komentar dihapus.');
    cachedComments = null;
    loadComments();
}

function attachPostInteractions() {
    const buttons = document.querySelectorAll('.see-comments-btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const postId = btn.dataset.postId;
            const row = document.querySelector(`[data-comments-row="${postId}"]`);
            const container = document.querySelector(`[data-comments-for="${postId}"]`);
            if (!container) return;
            if (row) row.classList.remove('d-none');
            await togglePostComments(postId, container);
        });
    });

    document.querySelectorAll('.delete-post-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const postId = btn.dataset.postId;
            await deletePost(postId);
        });
    });

    document.querySelectorAll('.update-post-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const postId = btn.dataset.postId;
            const formWrapper = document.querySelector(`[data-update-for="${postId}"]`);
            if (formWrapper) {
                formWrapper.classList.toggle('d-none');
            }
        });
    });

    document.querySelectorAll('.cancel-update-post').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const postId = btn.dataset.postId;
            const formWrapper = document.querySelector(`[data-update-for="${postId}"]`);
            if (formWrapper) {
                formWrapper.classList.add('d-none');
            }
        });
    });

    document.querySelectorAll('.update-post-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const postId = form.dataset.postId;
            const formData = new FormData(form);
            formData.append('post_id', postId);
            formData.append('token', token);

            const response = await fetch('../../api/posts/update.php', {
                method: 'POST',
                headers: {},
                body: formData
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                alert(result.message || 'Gagal memperbarui post.');
                return;
            }

            alert(result.message || 'Post diperbarui.');
            cachedPosts = null;
            await loadPosts();
        });
    });
}

async function togglePostComments(postId, container) {
    if (container.dataset.loaded === 'true') {
        container.classList.toggle('d-none');
        return;
    }

    container.classList.remove('d-none');
    container.innerHTML = '<div class="text-muted">Memuat komentar...</div>';

    if (postCommentsCache.has(postId)) {
        renderPostComments(container, postCommentsCache.get(postId));
        container.dataset.loaded = 'true';
        return;
    }

    const response = await fetch(`../../api/auth/getAllPostComments.php?post_id=${postId}`, {
        headers: { Authorization: 'Bearer ' + token }
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        container.innerHTML = `<div class="text-danger">${result.message || 'Gagal memuat komentar.'}</div>`;
        return;
    }

    postCommentsCache.set(postId, result.data || []);
    container.dataset.loaded = 'true';
    renderPostComments(container, result.data || []);
}

function renderPostComments(container, comments) {
    if (!comments.length) {
        container.innerHTML = '<div class="text-muted">Belum ada komentar.</div>';
        return;
    }

    container.innerHTML = comments.map(c => {
        const created = c.created_at ? new Date(c.created_at).toLocaleString() : '-';
        return `
            <div class="content-meta mb-1">• ${c.content || ''} <span class="text-secondary">(${created})</span></div>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', fetchUserProfile);
document.addEventListener('DOMContentLoaded', setupTabs);
