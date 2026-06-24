// script.js – all front-end logic for Monet’s Atelier
(function() {
  // ----- data store (simulated SQL / PHP backend) -----
  let paintings = [
    { id: 1, title: 'Water Lilies', artist: 'Claude Monet', medium: 'oil', genre: 'impressionism', price: 1200 },
    { id: 2, title: 'Impression, Sunrise', artist: 'Claude Monet', medium: 'oil', genre: 'impressionism', price: 980 },
    { id: 3, title: 'The Starry Night', artist: 'Vincent van Gogh', medium: 'oil', genre: 'impressionism', price: 1500 },
    { id: 4, title: 'Sunflowers', artist: 'Vincent van Gogh', medium: 'acrylic', genre: 'realism', price: 820 },
    { id: 5, title: 'The Persistence of Memory', artist: 'Salvador Dalí', medium: 'oil', genre: 'surrealism', price: 2100 },
    { id: 6, title: 'Nocturne in Blue', artist: 'James Whistler', medium: 'pastel', genre: 'abstract', price: 670 },
    { id: 7, title: 'The Hay Wain', artist: 'John Constable', medium: 'oil', genre: 'realism', price: 950 },
  ];
  let nextId = 8;

  // ----- Auth state -----
  let currentUser = null; // { email, role, name }

  // DOM refs
  const gallery = document.getElementById('galleryContainer');
  const filterMedium = document.getElementById('filterMedium');
  const filterGenre = document.getElementById('filterGenre');
  const resetBtn = document.getElementById('resetFilters');
  const toggleAddBtn = document.getElementById('toggleAddBtn');
  const addPanel = document.getElementById('addPanel');
  const artForm = document.getElementById('artForm');

  // Auth DOM refs
  const loginBtn = document.getElementById('loginBtn');
  const logoutBtn = document.getElementById('logoutBtn');
  const userDisplay = document.getElementById('userDisplay');
  const loginOverlay = document.getElementById('loginOverlay');
  const loginClose = document.getElementById('loginClose');
  const loginForm = document.getElementById('loginForm');
  const loginEmail = document.getElementById('loginEmail');
  const loginPassword = document.getElementById('loginPassword');
  const loginRole = document.getElementById('loginRole');

  // Chat DOM refs
  const chatOverlay = document.getElementById('chatOverlay');
  const chatClose = document.getElementById('chatClose');
  const chatMessages = document.getElementById('chatMessages');
  const chatInput = document.getElementById('chatInput');
  const chatSend = document.getElementById('chatSend');
  const chatArtistName = document.getElementById('chatArtistName');
  let currentChatArtist = '';

  // ----- Auth functions -----
  function login(email, role) {
    currentUser = { email, role, name: email.split('@')[0] };
    updateUI();
    loginOverlay.classList.remove('active');
  }

  function logout() {
    currentUser = null;
    updateUI();
    // Close add panel if open
    addPanel.classList.remove('open');
    if (toggleAddBtn) toggleAddBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Add your art';
  }

  function updateUI() {
    if (currentUser) {
      loginBtn.style.display = 'none';
      logoutBtn.style.display = 'inline-flex';
      userDisplay.style.display = 'inline-block';
      userDisplay.textContent = `👤 ${currentUser.name} (${currentUser.role})`;
      // Show add panel for artists and admins
      if (currentUser.role === 'artist' || currentUser.role === 'admin') {
        toggleAddBtn.style.display = 'inline-flex';
      } else {
        toggleAddBtn.style.display = 'none';
        addPanel.classList.remove('open');
      }
    } else {
      loginBtn.style.display = 'inline-flex';
      logoutBtn.style.display = 'none';
      userDisplay.style.display = 'none';
      toggleAddBtn.style.display = 'inline-flex'; // visible but login required to use
    }
  }

  // ----- render gallery (filtered) -----
  function renderGallery() {
    const medium = filterMedium.value;
    const genre = filterGenre.value;
    const filtered = paintings.filter(p => {
      const mMatch = medium === 'all' || p.medium === medium;
      const gMatch = genre === 'all' || p.genre === genre;
      return mMatch && gMatch;
    });

    if (filtered.length === 0) {
      gallery.innerHTML = `<div class="empty"><i class="fas fa-paint-brush"></i><br>No paintings match the filters. Add a new one!</div>`;
      return;
    }

    gallery.innerHTML = filtered.map(p => {
      // medium icon
      let icon = 'fa-palette';
      if (p.medium === 'oil') icon = 'fa-oil-can';
      else if (p.medium === 'acrylic') icon = 'fa-fill-drip';
      else if (p.medium === 'watercolor') icon = 'fa-tint';
      else if (p.medium === 'pastel') icon = 'fa-pastel';

      return `
        <div class="art-card" data-id="${p.id}">
          <div class="art-img"><i class="fas ${icon}"></i></div>
          <div class="art-info">
            <div class="art-title">${p.title}</div>
            <div class="art-artist"><i class="fas fa-user-astronaut" style="opacity:0.7;"></i> ${p.artist}</div>
            <div class="art-meta">
              <span class="badge medium"><i class="fas fa-paint-brush"></i> ${p.medium}</span>
              <span class="badge genre"><i class="fas fa-tag"></i> ${p.genre}</span>
            </div>
            <div class="art-price">$${p.price}</div>
            <div class="art-actions">
              <button class="btn-talk talk-btn" data-id="${p.id}" data-artist="${p.artist}"><i class="fas fa-comment-dots"></i> Talk to artist</button>
            </div>
          </div>
        </div>
      `;
    }).join('');

    // attach talk events
    document.querySelectorAll('.talk-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (!currentUser) {
          alert('Please login first to chat with artists.');
          loginOverlay.classList.add('active');
          return;
        }
        const artist = this.dataset.artist;
        openChat(artist);
      });
    });
  }

  // ----- Chat functions -----
  function openChat(artistName) {
    currentChatArtist = artistName;
    chatArtistName.textContent = '💬 ' + artistName;
    chatMessages.innerHTML = `<div class="chat-message system">Welcome! Ask about the artwork or make an offer.</div>`;
    chatOverlay.classList.add('active');
    chatInput.focus();
  }

  function closeChat() {
    chatOverlay.classList.remove('active');
    currentChatArtist = '';
  }

  function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;
    const userMsg = document.createElement('div');
    userMsg.className = 'chat-message own';
    userMsg.textContent = text;
    chatMessages.appendChild(userMsg);
    chatInput.value = '';
    chatMessages.scrollTop = chatMessages.scrollHeight;

    setTimeout(() => {
      const replies = [
        `Thank you for your interest in my work! I'd be happy to discuss.`,
        `That's a wonderful piece. Would you like to know more about the technique?`,
        `I'm open to offers. What price did you have in mind?`,
        `I can ship worldwide. Let me know if you need framing options.`,
        `I appreciate your message! Feel free to ask anything about the artwork.`
      ];
      const reply = replies[Math.floor(Math.random() * replies.length)];
      const artistMsg = document.createElement('div');
      artistMsg.className = 'chat-message';
      artistMsg.textContent = `${currentChatArtist}: ${reply}`;
      chatMessages.appendChild(artistMsg);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 800 + Math.random() * 1200);
  }

  // ----- add painting (simulated POST to PHP) -----
  function addPainting(title, artist, medium, genre, price) {
    if (!currentUser || (currentUser.role !== 'artist' && currentUser.role !== 'admin')) {
      alert('Only artists and admins can add paintings. Please login with the correct role.');
      return;
    }
    const newPainting = {
      id: nextId++,
      title: title.trim(),
      artist: artist.trim(),
      medium: medium,
      genre: genre,
      price: parseFloat(price) || 0
    };
    paintings.push(newPainting);
    renderGallery();
    addPanel.classList.remove('open');
    toggleAddBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Add your art';
    artForm.reset();
  }

  // ----- event listeners -----
  filterMedium.addEventListener('change', renderGallery);
  filterGenre.addEventListener('change', renderGallery);

  resetBtn.addEventListener('click', function() {
    filterMedium.value = 'all';
    filterGenre.value = 'all';
    renderGallery();
  });

  toggleAddBtn.addEventListener('click', function() {
    if (!currentUser) {
      alert('Please login as an artist or admin to add paintings.');
      loginOverlay.classList.add('active');
      return;
    }
    if (currentUser.role !== 'artist' && currentUser.role !== 'admin') {
      alert('Only artists and admins can add paintings.');
      return;
    }
    addPanel.classList.toggle('open');
    this.innerHTML = addPanel.classList.contains('open') ? '<i class="fas fa-times"></i> Close' : '<i class="fas fa-plus-circle"></i> Add your art';
  });

  artForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const title = document.getElementById('artTitle').value.trim();
    const artist = document.getElementById('artArtist').value.trim();
    const medium = document.getElementById('artMedium').value;
    const genre = document.getElementById('artGenre').value;
    const price = document.getElementById('artPrice').value;
    if (!title || !artist) {
      alert('Please fill in Title and Artist.');
      return;
    }
    addPainting(title, artist, medium, genre, price);
  });

  // Auth event listeners
  loginBtn.addEventListener('click', () => loginOverlay.classList.add('active'));
  loginClose.addEventListener('click', () => loginOverlay.classList.remove('active'));
  loginOverlay.addEventListener('click', (e) => {
    if (e.target === loginOverlay) loginOverlay.classList.remove('active');
  });

  loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const email = loginEmail.value.trim();
    const role = loginRole.value;
    if (!email) {
      alert('Please enter an email.');
      return;
    }
    login(email, role);
    loginForm.reset();
  });

  logoutBtn.addEventListener('click', logout);

  // Chat event listeners
  chatClose.addEventListener('click', closeChat);
  chatOverlay.addEventListener('click', (e) => {
    if (e.target === chatOverlay) closeChat();
  });
  chatSend.addEventListener('click', sendMessage);
  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
  });

  // ----- init -----
  updateUI();
  renderGallery();
})();