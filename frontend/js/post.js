const token = localStorage.getItem('token');
const isAuthed = !!token;
let currentUser = null;

const postContainer = document.getElementById('postContainer');
const commentsContainer = document.getElementById('commentsContainer');
const commentsList = document.getElementById('commentsList');
const commentFormContainer = document.getElementById('commentFormContainer');
const commentForm = document.getElementById('commentForm');
const commentInput = document.getElementById('commentInput');

function normalizeImage(url) {
        if (!url) return '';
        const isAbsolute = url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:');
        if (isAbsolute) return url;
        return `../api/storage/images/${url.replace(/^\//, '')}`;
}

function getPostId() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
}

async function fetchCurrentUser() {
        if (!isAuthed) return null;
        try {
                const res = await fetch('../api/auth/getUser.php', {
                        headers: { Authorization: `Bearer ${token}` }
                });
                const data = await res.json();
                if (res.ok && data?.data) {
                        currentUser = data.data;
                }
        } catch (err) {
                console.error('Failed to fetch current user', err);
        }
        return currentUser;
}

async function loadPost() {
        const postId = getPostId();
        if (!postId) {
                postContainer.innerHTML = `<div style="text-align:center; color:#dc2626; padding:20px;">Invalid Post ID.</div>`;
                return;
        }

        try {
                const res = await fetch(`../api/posts/show.php?id=${postId}`);
                const data = await res.json();

                if (!res.ok || !data.success) {
                        postContainer.innerHTML = `<div style="text-align:center; color:#dc2626; padding:20px;">${data.message || 'Post not found.'}</div>`;
                        return;
                }

                renderPost(data.data);
                loadComments(postId);
        } catch (e) {
                postContainer.innerHTML = `<div style="text-align:center; color:#dc2626; padding:20px;">Error loading post.</div>`;
        }
}

function renderPost(p) {
        const created = p.created_at ? new Date(p.created_at).toLocaleString() : '';
        const imageUrl = normalizeImage(p.image_url);
        const avatar = p.profile_picture ? normalizeImage(p.profile_picture) : '../api/storage/images/default.jpg';
        const cityCountry = [p.city, p.country].filter(Boolean).join(', ');

        postContainer.innerHTML = `
    <article class="post">
      <div class="post-header">
        <div class="avatar">
          <img src="${avatar}" alt="${p.username || 'User'}">
        </div>
        <div class="user-info">
          <span class="username">${p.username || 'User'}</span>
          <span class="handle">${p.email ? p.email : ''} • ${created}</span>
          ${cityCountry ? `<span class="location">📍 ${cityCountry}</span>` : ''}
        </div>
      </div>
      ${imageUrl ? `<img class="post-image" src="${imageUrl}" alt="${p.title || ''}">` : ''}
      <div class="post-text"><strong>${p.title || ''}</strong>${p.content ? '<br>' + p.content : ''}</div>
    </article>
  `;

        commentsContainer.style.display = 'block';

        if (isAuthed) {
                commentFormContainer.style.display = 'block';
        }
}

async function submitComment(e) {
        e.preventDefault();
        if (!isAuthed) return;

        const content = commentInput.value.trim();
        if (!content) return;

        const postId = getPostId();
        if (!postId) return;

        try {
                const res = await fetch('../api/coment/create.php', {
                        method: 'POST',
                        headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify({
                                post_id: postId,
                                content: content,
                                token: token
                        })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                        commentInput.value = '';
                        loadComments(postId);
                } else {
                        alert(data.message || 'Failed to post comment.');
                }
        } catch (e) {
                console.error(e);
                alert('Error posting comment.');
        }
}

async function loadComments(postId) {
        try {
                const res = await fetch(`../api/coment/index.php?post_id=${postId}`);
                const data = await res.json();

                if (!res.ok || !data.success) {
                        commentsList.innerHTML = `<div style="color:var(--muted);">Failed to load comments.</div>`;
                        return;
                }

                renderComments(data.data || []);
        } catch (e) {
                commentsList.innerHTML = `<div style="color:var(--muted);">Error loading comments.</div>`;
        }
}

function renderComments(comments) {
        if (!comments.length) {
                commentsList.innerHTML = `<div style="color:var(--muted); font-style:italic;">No comments yet.</div>`;
                return;
        }

        // Show newest comments first
        const sorted = [...comments].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));

        commentsList.innerHTML = sorted.map(c => {
                const created = c.created_at ? new Date(c.created_at).toLocaleString() : '';
                const avatar = c.profile_picture ? normalizeImage(c.profile_picture) : '../api/storage/images/default.jpg';
                const isOwner = currentUser && Number(c.user_id) === Number(currentUser.user_id);

                return `
      <div class="comment" data-comment-id="${c.comment_id}">
        <div class="comment-avatar">
          <img src="${avatar}" alt="${c.username}">
        </div>
        <div class="comment-body">
          <div class="comment-header">
            <span class="comment-user">${c.username}</span>
            <span class="comment-date">${created}</span>
          </div>
          <div class="comment-text" data-comment-text="${c.comment_id}">${c.content}</div>
          ${isOwner ? `
          <div class="comment-actions">
            <button class="action-btn update-comment-btn" data-comment-id="${c.comment_id}">Update</button>
            <button class="action-btn danger delete-comment-btn" data-comment-id="${c.comment_id}">Delete</button>
          </div>
          ` : ''}
        </div>
      </div>
    `;
        }).join('');

        attachCommentActions();
}

function attachCommentActions() {
        document.querySelectorAll('.delete-comment-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        const commentId = btn.dataset.commentId;
                        await deleteComment(commentId);
                });
        });

        document.querySelectorAll('.update-comment-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const commentId = btn.dataset.commentId;
                        openCommentEditor(commentId);
                });
        });
}

function openCommentEditor(commentId) {
        const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
        if (!commentEl) return;
        const textEl = commentEl.querySelector(`[data-comment-text="${commentId}"]`);
        if (!textEl) return;

        // Prevent duplicate editors
        const existing = commentEl.querySelector('.comment-edit-block');
        if (existing) {
                existing.remove();
        }

        const originalText = textEl.textContent.trim();
        const editWrap = document.createElement('div');
        editWrap.className = 'comment-edit-block';

        const textarea = document.createElement('textarea');
        textarea.className = 'edit-input';
        textarea.value = originalText;

        const actions = document.createElement('div');
        actions.className = 'comment-actions';

        const saveBtn = document.createElement('button');
        saveBtn.className = 'action-btn';
        saveBtn.textContent = 'Save';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'action-btn danger';
        cancelBtn.textContent = 'Cancel';

        actions.appendChild(saveBtn);
        actions.appendChild(cancelBtn);
        editWrap.appendChild(textarea);
        editWrap.appendChild(actions);
        textEl.after(editWrap);

        cancelBtn.addEventListener('click', () => {
                editWrap.remove();
        });

        saveBtn.addEventListener('click', async () => {
                const newContent = textarea.value.trim();
                if (!newContent) {
                        alert('Komentar tidak boleh kosong.');
                        return;
                }
                const ok = await updateComment(commentId, newContent);
                if (ok) {
                        textEl.textContent = newContent;
                        editWrap.remove();
                }
        });
}

async function updateComment(commentId, content) {
        try {
                const res = await fetch('../api/coment/update.php', {
                        method: 'PUT',
                        headers: {
                                'Content-Type': 'application/json',
                                ...(token ? { Authorization: `Bearer ${token}` } : {})
                        },
                        body: JSON.stringify({ comment_id: Number(commentId), content, token })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                        alert(data.message || 'Gagal memperbarui komentar.');
                        return false;
                }
                alert(data.message || 'Komentar diperbarui.');
                loadComments(getPostId());
                return true;
        } catch (err) {
                console.error(err);
                alert('Error memperbarui komentar.');
                return false;
        }
}

async function deleteComment(commentId) {
        if (!confirm('Hapus komentar ini?')) return;
        try {
                const res = await fetch('../api/coment/delete.php', {
                        method: 'DELETE',
                        headers: {
                                'Content-Type': 'application/json',
                                ...(token ? { Authorization: `Bearer ${token}` } : {})
                        },
                        body: JSON.stringify({ comment_id: Number(commentId), token })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                        alert(data.message || 'Gagal menghapus komentar.');
                        return;
                }
                alert(data.message || 'Komentar dihapus.');
                loadComments(getPostId());
        } catch (err) {
                console.error(err);
                alert('Error menghapus komentar.');
        }
}

document.addEventListener('DOMContentLoaded', async () => {
        if (isAuthed) {
                await fetchCurrentUser();
        }
        loadPost();
        if (commentForm) {
                commentForm.addEventListener('submit', submitComment);
        }
});
