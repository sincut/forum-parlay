(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const api = window.FORUM_CONFIG.api;
  const state = { rooms: [], roomId: 1, messages: [], pollTimer: null, pollInterval: 3000, loading: false };
  const palette = ['#c17d60', '#6f8e82', '#a57791', '#5786a1', '#7f965d', '#a17b55'];
  const savedProfile = JSON.parse(localStorage.getItem('forum-profile') || 'null');
  const profile = savedProfile || {
    id: `guest_${crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36)}`,
    name: 'Kamu',
    color: palette[Math.floor(Math.random() * palette.length)]
  };
  localStorage.setItem('forum-profile', JSON.stringify(profile));

  const elements = {
    app: $('#app'), rooms: $('#rooms'), messages: $('#messages'), roomSearch: $('#room-search'),
    chatName: $('#chat-room-name'), chatAvatar: $('#chat-avatar'), pinnedText: $('#pinned-text'),
    detailName: $('#detail-name'), detailAvatar: $('#detail-avatar'), detailDescription: $('#detail-description'),
    detailPanel: $('#details-panel'), form: $('#message-form'), input: $('#message-input'),
    toast: $('#toast'), emojiPicker: $('#emoji-picker'), messageSearch: $('#message-search'),
    profileDialog: $('#profile-dialog'), profileName: $('#profile-name')
  };

  async function request(action, options = {}) {
    const response = await fetch(`${api}?action=${encodeURIComponent(action)}${options.query || ''}`, {
      headers: { 'Content-Type': 'application/json' }, cache: 'no-store', ...options
    });
    let data;
    try { data = await response.json(); } catch { throw new Error('Respons server tidak valid.'); }
    if (!response.ok || !data.ok) throw new Error(data.message || 'Permintaan gagal.');
    return data;
  }

  function escapeTime(value) {
    if (!value) return '';
    const date = new Date(value.replace(' ', 'T') + (value.includes('Z') ? '' : 'Z'));
    return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' }).format(date).replace('.', ':');
  }

  function initials(name) {
    return name.trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
  }

  function showToast(message) {
    elements.toast.textContent = message;
    elements.toast.classList.add('show');
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => elements.toast.classList.remove('show'), 2600);
  }

  function renderRooms(filter = '') {
    const needle = filter.trim().toLocaleLowerCase('id');
    const filtered = state.rooms.filter(room => `${room.name} ${room.last_user || ''} ${room.last_message || ''}`.toLocaleLowerCase('id').includes(needle));
    elements.rooms.replaceChildren();
    if (!filtered.length) {
      const empty = document.createElement('div');
      empty.className = 'loading-list'; empty.textContent = 'Forum tidak ditemukan.'; elements.rooms.append(empty); return;
    }
    filtered.forEach((room, index) => {
      const button = document.createElement('button');
      button.className = `room-item${Number(room.id) === state.roomId ? ' active' : ''}`;
      button.dataset.roomId = room.id;
      const avatar = document.createElement('span');
      avatar.className = 'room-avatar list'; avatar.style.setProperty('--avatar', room.accent); avatar.textContent = room.icon;
      const copy = document.createElement('span'); copy.className = 'room-copy';
      const title = document.createElement('strong'); title.textContent = room.name;
      const preview = document.createElement('span'); preview.textContent = room.last_message ? `${room.last_user}: ${room.last_message.replace(/\n/g, ' ')}` : 'Belum ada pesan';
      copy.append(title, preview);
      const meta = document.createElement('span'); meta.className = 'room-meta';
      const time = document.createElement('time'); time.textContent = escapeTime(room.last_at);
      meta.append(time);
      if (index < 6) { const badge = document.createElement('b'); badge.className = 'badge'; badge.textContent = String([4, 12, 3, 6, 9, 2][index]); meta.append(badge); }
      button.append(avatar, copy, meta); elements.rooms.append(button);
    });
  }

  function updateRoomUI(room) {
    const targets = [elements.chatAvatar, elements.detailAvatar];
    targets.forEach(target => { target.textContent = room.icon; target.style.setProperty('--avatar', room.accent); });
    elements.chatName.textContent = room.name;
    elements.detailName.textContent = room.name;
    elements.detailDescription.textContent = room.description;
    elements.pinnedText.textContent = room.pinned;
    elements.input.placeholder = `Ketik pesan di ${room.name}…`;
    document.title = `${room.name} — Forum Chat`;
  }

  function createMessage(message) {
    const mine = message.user_id === profile.id || (message.user_id === 'me' && profile.name === 'Kamu');
    const row = document.createElement('article'); row.className = `message-row${mine ? ' mine' : ''}`; row.dataset.messageId = message.id;
    if (!mine) {
      const avatar = document.createElement('span'); avatar.className = 'message-avatar'; avatar.style.setProperty('--avatar', message.avatar_color); avatar.textContent = initials(message.user_name); row.append(avatar);
    }
    const bubble = document.createElement('div'); bubble.className = 'bubble';
    if (!mine) { const name = document.createElement('strong'); name.textContent = message.user_name; bubble.append(name); }
    const body = document.createElement('p'); body.textContent = message.body;
    const time = document.createElement('time'); time.textContent = escapeTime(message.created_at);
    bubble.append(body, time); row.append(bubble); return row;
  }

  function renderMessages(messages) {
    elements.messages.replaceChildren();
    const day = document.createElement('div'); day.className = 'day-pill'; day.textContent = 'Hari ini'; elements.messages.append(day);
    messages.forEach(message => elements.messages.append(createMessage(message)));
    if (!messages.length) { const empty = document.createElement('div'); empty.className = 'loading-messages'; empty.textContent = 'Belum ada pesan. Mulai percakapan pertama!'; elements.messages.append(empty); }
    elements.messages.setAttribute('aria-busy', 'false');
    elements.messages.scrollTop = elements.messages.scrollHeight;
  }

  async function openRoom(roomId) {
    state.roomId = Number(roomId); state.messages = [];
    renderRooms(elements.roomSearch.value);
    elements.app.classList.add('chat-open');
    const room = state.rooms.find(item => Number(item.id) === state.roomId);
    if (room) updateRoomUI(room);
    elements.messages.setAttribute('aria-busy', 'true');
    try {
      const data = await request('messages', { query: `&room_id=${state.roomId}` });
      if (state.roomId !== Number(data.room.id)) return;
      state.messages = data.messages; updateRoomUI(data.room); renderMessages(state.messages); schedulePoll();
    } catch (error) { showToast(error.message); elements.messages.setAttribute('aria-busy', 'false'); }
  }

  function schedulePoll() {
    clearTimeout(state.pollTimer);
    state.pollTimer = setTimeout(pollMessages, state.pollInterval);
  }

  async function pollMessages() {
    if (document.hidden || state.loading) { schedulePoll(); return; }
    state.loading = true;
    const after = state.messages.length ? Number(state.messages.at(-1).id) : 0;
    try {
      const data = await request('messages', { query: `&room_id=${state.roomId}&after=${after}` });
      if (data.messages.length) {
        data.messages.forEach(message => { state.messages.push(message); elements.messages.append(createMessage(message)); });
        elements.messages.scrollTop = elements.messages.scrollHeight;
      }
    } catch { /* polling will retry silently */ }
    finally { state.loading = false; schedulePoll(); }
  }

  async function bootstrap() {
    try {
      const data = await request('bootstrap');
      state.rooms = data.rooms; state.pollInterval = data.config.pollInterval || 3000; renderRooms();
      const preferred = new URLSearchParams(location.search).get('room');
      await openRoom(preferred || state.rooms[0]?.id || 1);
      if (innerWidth <= 900 && !preferred) elements.app.classList.remove('chat-open');
    } catch (error) { elements.rooms.innerHTML = '<div class="loading-list">Forum gagal dimuat.</div>'; showToast(error.message); }
  }

  elements.rooms.addEventListener('click', event => { const room = event.target.closest('.room-item'); if (room) openRoom(room.dataset.roomId); });
  elements.roomSearch.addEventListener('input', event => renderRooms(event.target.value));
  $('#back-button').addEventListener('click', () => { elements.app.classList.remove('chat-open'); history.replaceState({}, '', location.pathname); });
  $('#detail-toggle').addEventListener('click', () => elements.detailPanel.classList.toggle('open'));
  $('#detail-close').addEventListener('click', () => elements.detailPanel.classList.remove('open'));
  $('#hide-pin').addEventListener('click', () => $('#pinned').hidden = true);
  $('#new-room-button').addEventListener('click', () => showToast('Pembuatan ruang baru khusus admin.'));

  elements.form.addEventListener('submit', async event => {
    event.preventDefault(); const body = elements.input.value.trim(); if (!body) return;
    const submit = $('.send-button', elements.form); submit.disabled = true;
    try {
      const data = await request('send', { method: 'POST', body: JSON.stringify({ room_id: state.roomId, user_id: profile.id, user_name: profile.name, avatar_color: profile.color, body }) });
      elements.input.value = ''; elements.input.style.height = ''; state.messages.push(data.message); elements.messages.append(createMessage(data.message)); elements.messages.scrollTop = elements.messages.scrollHeight;
    } catch (error) { showToast(error.message); }
    finally { submit.disabled = false; elements.input.focus(); }
  });
  elements.input.addEventListener('input', () => { elements.input.style.height = 'auto'; elements.input.style.height = `${Math.min(elements.input.scrollHeight, 100)}px`; });
  elements.input.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); elements.form.requestSubmit(); } });

  const emojis = ['😊','😂','👍','❤️','🔥','🚀','⚽','🎉','🙏','🤝','😎','💬'];
  emojis.forEach(emoji => { const button = document.createElement('button'); button.type = 'button'; button.textContent = emoji; button.addEventListener('click', () => { elements.input.value += emoji; elements.input.focus(); }); elements.emojiPicker.append(button); });
  $('#emoji-button').addEventListener('click', () => elements.emojiPicker.classList.toggle('open'));
  $('#message-search-button').addEventListener('click', () => { elements.messageSearch.classList.add('open'); $('input', elements.messageSearch).focus(); });
  $('button', elements.messageSearch).addEventListener('click', () => { elements.messageSearch.classList.remove('open'); $('input', elements.messageSearch).value = ''; renderMessages(state.messages); });
  $('input', elements.messageSearch).addEventListener('input', event => { const needle = event.target.value.toLocaleLowerCase('id'); renderMessages(state.messages.filter(message => `${message.user_name} ${message.body}`.toLocaleLowerCase('id').includes(needle))); });

  document.querySelectorAll('.profile-button').forEach(button => button.addEventListener('click', () => { elements.profileName.value = profile.name; elements.profileDialog.showModal(); }));
  $('#profile-form').addEventListener('submit', event => { if (event.submitter?.value === 'cancel') return; event.preventDefault(); profile.name = elements.profileName.value.trim() || 'Kamu'; localStorage.setItem('forum-profile', JSON.stringify(profile)); elements.profileDialog.close(); showToast('Profil berhasil diperbarui.'); });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) pollMessages(); });
  window.addEventListener('resize', () => { if (innerWidth > 900) elements.app.classList.add('chat-open'); });

  bootstrap();
})();
