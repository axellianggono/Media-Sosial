const token = localStorage.getItem('token');
const isAuthed = !!token;

const form = document.getElementById('composer-form');
const postBtn = document.getElementById('postBtn');
const titleInput = document.getElementById('titleInput');
const captionInput = document.getElementById('captionInput');
const imageInput = document.getElementById('imageInput');
const latInput = document.getElementById('latInput');
const lonInput = document.getElementById('lonInput');
const addressInput = document.getElementById('addressInput');
const cityInput = document.getElementById('cityInput');
const countryInput = document.getElementById('countryInput');
const feedStream = document.getElementById('feedStream');
const sortSelect = document.getElementById('sortSelect');
const emailFilter = document.getElementById('emailFilter');
const applyFilterBtn = document.getElementById('applyFilter');
const paginationEl = document.getElementById('pagination');
const composerSection = document.getElementById('composer-section');
const postBtnToggle = document.createElement('button');
if (composerSection) {
  postBtnToggle.textContent = 'Create Post';
  postBtnToggle.className = 'toggle-compose';
  postBtnToggle.addEventListener('click', () => {
    const isHidden = composerSection.style.display === 'none' || composerSection.style.display === '';
    composerSection.style.display = isHidden ? 'flex' : 'none';
  });
}

let currentPage = 1;
let totalPages = 1;
let locationFilled = false;

function normalizeImage(url) {
  if (!url) return '';
  const isAbsolute = url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:');
  if (isAbsolute) return url;
  return `../api/storage/images/${url.replace(/^\//, '')}`;
}

function renderPosts(posts) {
  if (!posts || !posts.length) {
    feedStream.innerHTML = `<div style="text-align:center; color:#6b7280; padding:20px;">Belum ada postingan.</div>`;
    return;
  }

  feedStream.innerHTML = posts.map(p => {
    const created = p.created_at ? new Date(p.created_at).toLocaleString() : '';
    const imageUrl = normalizeImage(p.image_url);
    const avatar = p.profile_picture ? normalizeImage(p.profile_picture) : '../api/storage/images/default.jpg';
    const cityCountry = [p.city, p.country].filter(Boolean).join(', ');
    return `
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
        <div class="post-actions">
          <span class="action-item">💬 ${p.comment_count ?? 0}</span>
        </div>
      </article>
    `;
  }).join('');
}

function renderPagination() {
  if (totalPages <= 1) {
    paginationEl.innerHTML = '';
    return;
  }
  let buttons = '';
  for (let p = 1; p <= totalPages; p++) {
    buttons += `<button data-page="${p}" class="btn" style="padding:6px 10px; ${p === currentPage ? 'background:#dbeafe; border-color:#bcd2fb;' : ''}">${p}</button>`;
  }
  paginationEl.innerHTML = buttons;
  paginationEl.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', () => {
      currentPage = Number(btn.dataset.page);
      loadPosts();
    });
  });
}

async function reverseGeocode(lat, lon) {
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    const address = data.address || {};
    return {
      address: data.display_name || '',
      city: address.city || address.town || address.village || address.county || '',
      country: address.country || ''
    };
  } catch (e) {
    return { address: '', city: '', country: '' };
  }
}

function autofillLocation() {
  if (locationFilled) return;
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(
    async pos => {
      const lat = pos.coords.latitude.toFixed(6);
      const lon = pos.coords.longitude.toFixed(6);
      locationFilled = true;
      if (latInput) latInput.value = lat;
      if (lonInput) lonInput.value = lon;
      form.dataset.lat = lat;
      form.dataset.lon = lon;
      const rev = await reverseGeocode(lat, lon);
      if (addressInput) addressInput.value = rev.address;
      if (cityInput) cityInput.value = rev.city;
      if (countryInput) countryInput.value = rev.country;
      form.dataset.address = rev.address;
      form.dataset.city = rev.city;
      form.dataset.country = rev.country;
    },
    () => {},
    { enableHighAccuracy: true, timeout: 8000 }
  );
}

async function loadPosts() {
  const sort = sortSelect.value;
  const email = emailFilter.value.trim();
  const params = new URLSearchParams();
  params.set('page', currentPage);
  params.set('sort', sort);
  if (email) params.set('email', email);

  feedStream.innerHTML = `<div style="text-align:center; color:#6b7280; padding:20px;">Memuat...</div>`;
  const res = await fetch(`../api/posts/index.php?${params.toString()}`);
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    feedStream.innerHTML = `<div style="text-align:center; color:#dc2626; padding:20px;">Gagal memuat post.</div>`;
    return;
  }
  renderPosts(data.data || []);
  totalPages = data.pagination?.total_pages || 1;
  renderPagination();
}

function handleComposeState() {
  const hasTitle = titleInput.value.trim().length > 0;
  if (hasTitle) {
    postBtn.classList.add('active');
  } else {
    postBtn.classList.remove('active');
  }
}

async function submitPost(event) {
    event.preventDefault();
    if (!isAuthed) {
      alert('Silakan login untuk membuat post.');
      window.location.href = './auth/login.html';
      return;
    }
  const title = titleInput.value.trim();
  const caption = captionInput.value.trim();
  if (!title) {
    alert('Title wajib diisi');
    return;
  }
  const formData = new FormData();
  formData.append('title', title);
  if (caption) formData.append('caption', caption);
  if (imageInput.files[0]) formData.append('picture', imageInput.files[0]);
  if (form.dataset.lat) formData.append('latitude', form.dataset.lat);
  if (form.dataset.lon) formData.append('longitude', form.dataset.lon);
  if (form.dataset.address) formData.append('address', form.dataset.address);
  if (form.dataset.city) formData.append('city', form.dataset.city);
  if (form.dataset.country) formData.append('country', form.dataset.country);
  formData.append('token', token || '');

  postBtn.disabled = true;
  const res = await fetch('../api/posts/create.php', {
    method: 'POST',
    body: formData
  });
  const data = await res.json().catch(() => ({}));
  postBtn.disabled = false;

  if (!res.ok || !data.success) {
    alert(data.message || 'Gagal membuat post.');
    return;
  }

  // reset form
  form.reset();
  handleComposeState();
  currentPage = 1;
  loadPosts();
}

document.addEventListener('DOMContentLoaded', () => {
  if (composerSection) {
    composerSection.style.display = 'none';
    composerSection.parentElement.insertBefore(postBtnToggle, composerSection);
  }
  autofillLocation();
  handleComposeState();
  titleInput.addEventListener('input', handleComposeState);
  captionInput.addEventListener('input', handleComposeState);
  autofillLocation();
  if (imageInput) imageInput.addEventListener('click', autofillLocation);
  form.addEventListener('submit', submitPost);
  applyFilterBtn.addEventListener('click', () => { currentPage = 1; loadPosts(); });
  loadPosts();
});
