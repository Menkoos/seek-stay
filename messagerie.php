<?php
require 'session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Authentification.html?error=" . urlencode("Connectez-vous pour accéder à la messagerie."));
    exit;
}

$uid = $_SESSION['user_id'];
$contactParam = $_GET['contact'] ?? '';

// Charger les conversations côté serveur pour le premier rendu
$conversations = [];
try {
    $s = $pdo->prepare("
        SELECT DISTINCT IF(emetteur_id = ?, recepteur_id, emetteur_id) AS contact_id
        FROM messagerie WHERE emetteur_id = ? OR recepteur_id = ?
    ");
    $s->execute([$uid, $uid, $uid]);
    $contactIds = $s->fetchAll(PDO::FETCH_COLUMN);

    foreach ($contactIds as $cid) {
        $s = $pdo->prepare("
            SELECT m.contenu, m.date_emission, m.emetteur_id,
                   u.nom, u.lastname, u.photo_profil,
                   (SELECT COUNT(*) FROM messagerie WHERE emetteur_id = ? AND recepteur_id = ? AND lu = 0) AS non_lus
            FROM messagerie m
            JOIN utilisateur_ u ON u.id_utilisateur = ?
            WHERE (m.emetteur_id = ? AND m.recepteur_id = ?) OR (m.emetteur_id = ? AND m.recepteur_id = ?)
            ORDER BY m.date_emission DESC LIMIT 1
        ");
        $s->execute([$cid, $uid, $cid, $uid, $cid, $cid, $uid]);
        $row = $s->fetch();
        if ($row) { $row['contact_id'] = $cid; $conversations[] = $row; }
    }
    usort($conversations, fn($a,$b) => strtotime($b['date_emission']) - strtotime($a['date_emission']));
} catch (PDOException $e) {}

// Infos du contact sélectionné
$contactInfo = null;
if ($contactParam) {
    try {
        $s = $pdo->prepare("SELECT id_utilisateur, nom, lastname, photo_profil FROM utilisateur_ WHERE id_utilisateur = ?");
        $s->execute([$contactParam]);
        $contactInfo = $s->fetch();
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Messagerie — Seek &amp; Stay</title>
  <link rel="icon" type="image/x-icon" href="img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="styles/styles.css" />
  <style>
    :root {
      --ss-primary: #244676; --ss-primary-hover: #1a3459; --ss-accent: #30bae6;
      --text: #1e293b; --text-muted: #64748b; --border: #e2e8f0;
      --bg: #f1f5f9; --white: #ffffff; --radius: 8px;
    }

    body { background: var(--bg); }

    /* ── Mise en page globale ── */
    .msg-layout {
      display: grid;
      grid-template-columns: 320px 1fr;
      height: calc(100vh - 61px);
      overflow: hidden;
    }

    /* ── SIDEBAR ── */
    .msg-sidebar {
      background: var(--white);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .sidebar-header {
      padding: 16px 16px 12px;
      border-bottom: 1px solid var(--border);
    }
    .sidebar-header h2 {
      font-size: 17px; font-weight: 700; color: var(--text); margin: 0 0 12px;
    }

    .search-wrap { position: relative; }
    .search-wrap input {
      width: 100%; padding: 8px 12px 8px 34px;
      border: 1px solid var(--border); border-radius: 20px;
      font-size: 13px; font-family: inherit; color: var(--text);
      background: var(--bg); box-sizing: border-box;
      transition: border-color .2s, box-shadow .2s;
    }
    .search-wrap input:focus {
      outline: none; border-color: var(--ss-primary);
      box-shadow: 0 0 0 3px rgba(36,70,118,.1);
    }
    .search-wrap svg {
      position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
      width: 15px; height: 15px; color: var(--text-muted); pointer-events: none;
    }
    .search-results {
      background: var(--white); border: 1px solid var(--border);
      border-radius: var(--radius); box-shadow: 0 4px 16px rgba(0,0,0,.1);
      margin-top: 4px; display: none; max-height: 200px; overflow-y: auto;
    }
    .search-results.open { display: block; }
    .search-result-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; cursor: pointer; transition: background .15s;
    }
    .search-result-item:hover { background: var(--bg); }

    .conv-list { flex: 1; overflow-y: auto; }
    .conv-list::-webkit-scrollbar { width: 4px; }
    .conv-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    .conv-item {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 16px; cursor: pointer; transition: background .15s;
      border-bottom: 1px solid #f8fafc;
      position: relative;
    }
    .conv-item:hover   { background: var(--bg); }
    .conv-item.active  { background: #eff6ff; }

    .conv-avatar {
      width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
      background: var(--ss-primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; font-weight: 700; overflow: hidden;
    }
    .conv-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .conv-body { flex: 1; min-width: 0; }
    .conv-name {
      font-size: 14px; font-weight: 600; color: var(--text);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .conv-preview {
      font-size: 12px; color: var(--text-muted); margin-top: 2px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .conv-preview.unread { color: var(--text); font-weight: 500; }

    .conv-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
    .conv-time { font-size: 11px; color: var(--text-muted); }
    .conv-badge {
      background: var(--ss-accent); color: #fff; border-radius: 10px;
      font-size: 11px; font-weight: 700; padding: 1px 7px; min-width: 18px; text-align: center;
    }

    .conv-empty {
      padding: 40px 20px; text-align: center; color: var(--text-muted); font-size: 13px;
    }

    /* ── THREAD ── */
    .msg-thread {
      display: flex; flex-direction: column; overflow: hidden;
      background: var(--bg);
    }

    .thread-header {
      background: var(--white); border-bottom: 1px solid var(--border);
      padding: 12px 20px; display: flex; align-items: center; gap: 12px;
    }
    .thread-header .thread-avatar {
      width: 40px; height: 40px; border-radius: 50%;
      background: var(--ss-primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 15px; font-weight: 700; overflow: hidden; flex-shrink: 0;
    }
    .thread-header .thread-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .thread-header .thread-avatar { cursor: pointer; transition: opacity .15s; }
    .thread-header .thread-avatar:hover { opacity: .85; }
    .thread-contact-name { font-size: 15px; font-weight: 700; color: var(--text); cursor: pointer; }
    #thread-name-link:hover .thread-contact-name { color: var(--ss-primary); }
    .thread-contact-sub  { font-size: 12px; color: var(--text-muted); }
    #thread-view-profile:hover { background: var(--ss-primary-hover) !important; }
    #thread-view-profile-small:hover { text-decoration: underline; }

    .thread-messages {
      flex: 1; overflow-y: auto; padding: 20px 24px;
      display: flex; flex-direction: column; gap: 4px;
    }
    .thread-messages::-webkit-scrollbar { width: 4px; }
    .thread-messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    /* ── Bulles de messages ── */
    .msg-day-sep {
      text-align: center; font-size: 11px; color: var(--text-muted);
      margin: 12px 0 8px; position: relative;
    }
    .msg-day-sep::before, .msg-day-sep::after {
      content: ''; flex: 1; border-top: 1px solid var(--border);
      position: absolute; top: 50%; width: calc(50% - 60px);
    }
    .msg-day-sep::before { left: 0; }
    .msg-day-sep::after  { right: 0; }

    .msg-bubble-wrap {
      display: flex; align-items: flex-end; gap: 6px; margin-bottom: 2px;
    }
    .msg-bubble-wrap.mine   { flex-direction: row-reverse; }

    .bubble-avatar {
      width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
      background: var(--ss-primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; overflow: hidden;
      align-self: flex-end;
    }
    .bubble-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .bubble-avatar.hidden { visibility: hidden; }

    .bubble {
      max-width: 62%; padding: 9px 13px;
      border-radius: 16px; font-size: 14px; line-height: 1.5;
      word-break: break-word; position: relative;
    }
    .msg-bubble-wrap:not(.mine) .bubble {
      background: var(--white); color: var(--text);
      border-bottom-left-radius: 4px;
      box-shadow: 0 1px 2px rgba(0,0,0,.07);
    }
    .msg-bubble-wrap.mine .bubble {
      background: var(--ss-primary); color: #fff;
      border-bottom-right-radius: 4px;
    }
    .bubble-time {
      font-size: 10px; margin-top: 3px; display: block;
      color: rgba(255,255,255,.6);
    }
    .msg-bubble-wrap:not(.mine) .bubble-time { color: var(--text-muted); }

    /* ── Zone de saisie ── */
    .thread-input {
      background: var(--white); border-top: 1px solid var(--border);
      padding: 12px 20px; display: flex; align-items: flex-end; gap: 10px;
    }
    #msg-textarea {
      flex: 1; resize: none; border: 1px solid var(--border);
      border-radius: 20px; padding: 10px 16px; font-size: 14px;
      font-family: inherit; color: var(--text); line-height: 1.5;
      max-height: 120px; min-height: 42px; overflow-y: auto;
      transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
    }
    #msg-textarea:focus {
      outline: none; border-color: var(--ss-primary);
      box-shadow: 0 0 0 3px rgba(36,70,118,.1);
    }
    .btn-send {
      width: 42px; height: 42px; border-radius: 50%;
      background: var(--ss-primary); color: #fff; border: none;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; flex-shrink: 0; transition: background .2s, transform .1s;
    }
    .btn-send:hover   { background: var(--ss-primary-hover); }
    .btn-send:active  { transform: scale(.93); }
    .btn-send svg     { width: 18px; height: 18px; }
    .btn-send:disabled { background: var(--border); cursor: not-allowed; }

    /* ── État vide (aucune conv sélectionnée) ── */
    .thread-empty {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--text-muted); text-align: center; gap: 12px; padding: 40px;
    }
    .thread-empty svg { width: 52px; height: 52px; opacity: .3; }
    .thread-empty p   { font-size: 14px; }

    /* ── Badge navbar ── */
    .nav-badge {
      display: inline-flex; align-items: center; justify-content: center;
      background: #ef4444; color: #fff; border-radius: 10px;
      font-size: 11px; font-weight: 700; padding: 1px 6px;
      min-width: 18px; margin-left: 4px; vertical-align: middle;
    }

    @media (max-width: 700px) {
      .msg-layout { grid-template-columns: 1fr; }
      .msg-sidebar { display: none; }
      .msg-sidebar.show-mobile { display: flex; position: fixed; inset: 61px 0 0 0; z-index: 100; width: 100%; }
      .msg-thread  { display: none; }
      .msg-thread.show-mobile  { display: flex; }
    }
  </style>
</head>
<body>

<header>
  <div class="flex">
    <a href="Accueil.php" class="header-logo">
      <img src="img/iconSite.png" class="header-logo" alt="logo Seek &amp; Stay" />
    </a>
    <ul class="header-menu">
      <li><a href="Accueil.php">Accueil</a></li>
      <li><a href="Annonces.php">Annonces</a></li>
      <li><a href="Favoris.php">Favoris</a></li>
      <?php if (!isset($_SESSION['user_id']) || ($_SESSION['role_type'] ?? '') === 'proprietaire'): ?>
      <li><a href="Publier.php">Publier</a></li>
      <?php endif; ?>
      <li><a href="Contact.html">Contact</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="messagerie.php" class="active">Messages <span class="nav-badge" id="nav-unread" style="display:none">0</span></a></li>
      <li><a href="mon-compte.php" style="font-weight:600;">Mon compte</a></li>
      <li><a href="FAQ.html">FAQ</a></li>
      <?php else: ?>
      <li><a href="FAQ.html">FAQ</a></li>
      <li><a href="Authentification.html">Inscription / Connexion</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>

<div class="msg-layout">

  <!-- ══ SIDEBAR ══ -->
  <aside class="msg-sidebar" id="sidebar">
    <div class="sidebar-header">
      <h2>Messages</h2>
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="search-input" placeholder="Nouvelle conversation..." autocomplete="off">
        <div class="search-results" id="search-results"></div>
      </div>
    </div>

    <div class="conv-list" id="conv-list">
      <?php if (empty($conversations)): ?>
      <div class="conv-empty">
        <p>Aucune conversation.<br>Cherchez un utilisateur pour commencer.</p>
      </div>
      <?php else: ?>
      <?php foreach ($conversations as $conv):
        $cid    = htmlspecialchars($conv['contact_id']);
        $nom    = htmlspecialchars($conv['nom'] . ' ' . $conv['lastname']);
        $init   = strtoupper(mb_substr($conv['nom'],0,1) . mb_substr($conv['lastname'],0,1));
        $isMe   = $conv['emetteur_id'] === $uid;
        $prev   = ($isMe ? 'Vous : ' : '') . htmlspecialchars(mb_substr($conv['contenu'],0,45) . (mb_strlen($conv['contenu'])>45?'…':''));
        $unread = (int)$conv['non_lus'];
        $ts     = strtotime($conv['date_emission']);
        $time   = date('d/m', $ts) === date('d/m') ? date('H:i', $ts) : date('d/m', $ts);
        $active = ($contactParam === $conv['contact_id']) ? 'active' : '';
      ?>
      <div class="conv-item <?php echo $active; ?>" data-cid="<?php echo $cid; ?>" onclick="openConv('<?php echo $cid; ?>', '<?php echo $nom; ?>')">
        <div class="conv-avatar">
          <?php if (!empty($conv['photo_profil']) && file_exists(__DIR__.'/'.$conv['photo_profil'])): ?>
            <img src="<?php echo htmlspecialchars($conv['photo_profil']); ?>" alt="">
          <?php else: ?>
            <?php echo $init; ?>
          <?php endif; ?>
        </div>
        <div class="conv-body">
          <div class="conv-name"><?php echo $nom; ?></div>
          <div class="conv-preview <?php echo $unread>0?'unread':''; ?>"><?php echo $prev; ?></div>
        </div>
        <div class="conv-meta">
          <span class="conv-time"><?php echo $time; ?></span>
          <?php if ($unread > 0): ?>
          <span class="conv-badge"><?php echo $unread; ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </aside>

  <!-- ══ THREAD ══ -->
  <main class="msg-thread" id="thread">

    <!-- État vide -->
    <div class="thread-empty" id="thread-empty" <?php echo $contactInfo ? 'style="display:none"' : ''; ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <p>Sélectionnez une conversation<br>ou cherchez un utilisateur.</p>
    </div>

    <!-- Conversation ouverte -->
    <div id="thread-open" style="display:<?php echo $contactInfo ? 'flex' : 'none'; ?>;flex-direction:column;height:100%;overflow:hidden;">
      <div class="thread-header" id="thread-header">
        <a id="thread-avatar-link" class="thread-avatar" href="#" title="Voir le profil"
           style="text-decoration:none;">
          <?php if ($contactInfo): ?>
            <?php if (!empty($contactInfo['photo_profil']) && file_exists(__DIR__.'/'.$contactInfo['photo_profil'])): ?>
              <img src="<?php echo htmlspecialchars($contactInfo['photo_profil']); ?>" alt="">
            <?php else: ?>
              <?php echo strtoupper(mb_substr($contactInfo['nom'],0,1).mb_substr($contactInfo['lastname'],0,1)); ?>
            <?php endif; ?>
          <?php endif; ?>
        </a>
        <div style="flex:1;min-width:0;">
          <a id="thread-name-link" href="#" style="text-decoration:none;color:inherit;">
            <div class="thread-contact-name" id="thread-contact-name">
              <?php echo $contactInfo ? htmlspecialchars($contactInfo['nom'].' '.$contactInfo['lastname']) : ''; ?>
            </div>
          </a>
          <div class="thread-contact-sub">
            <a id="thread-view-profile-small" href="#"
               style="color:var(--ss-accent);text-decoration:none;font-size:12px;">
              Voir le profil →
            </a>
          </div>
        </div>
        <a id="thread-view-profile" href="#" title="Voir le profil"
           style="background:var(--ss-primary);color:#fff;padding:9px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:background .15s;white-space:nowrap;">
          <i class="fa-solid fa-user"></i> Voir le profil
        </a>
      </div>

      <div class="thread-messages" id="thread-messages"></div>

      <div class="thread-input">
        <textarea id="msg-textarea" rows="1" placeholder="Écrivez votre message…" maxlength="2000"></textarea>
        <button class="btn-send" id="btn-send" onclick="sendMessage()" title="Envoyer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
        </button>
      </div>
    </div>

  </main>
</div>

<script>
const ME = <?php echo json_encode($uid); ?>;
let selectedContactId = <?php echo json_encode($contactParam ?: null); ?>;
let lastMessageDate   = null;
let lastRenderedDay   = null;
let pollTimer         = null;

// ── Charger un thread ────────────────────────────────────────────────────
async function openConv(cid, name, avatarHtml) {
  selectedContactId = cid;
  clearTimeout(pollTimer);

  // Marquer conv active dans sidebar
  document.querySelectorAll('.conv-item').forEach(el => {
    el.classList.toggle('active', el.dataset.cid === cid);
    if (el.dataset.cid === cid) el.querySelector('.conv-badge')?.remove();
  });

  // Afficher le thread
  document.getElementById('thread-empty').style.display = 'none';
  document.getElementById('thread-open').style.display  = 'flex';

  // Header — si avatarHtml n'est pas fourni, le lire depuis la sidebar
  if (!avatarHtml) {
    const sideAvatar = document.querySelector('.conv-item[data-cid="' + cid.replace(/"/g,'\\"') + '"] .conv-avatar');
    if (sideAvatar) avatarHtml = sideAvatar.innerHTML;
  }
  if (avatarHtml) document.getElementById('thread-avatar-link').innerHTML = avatarHtml;
  if (name)       document.getElementById('thread-contact-name').textContent = name;

  // Tous les éléments du header pointent vers le profil du contact
  const profileUrl = 'profil.php?id=' + encodeURIComponent(cid);
  document.getElementById('thread-avatar-link').href       = profileUrl;
  document.getElementById('thread-name-link').href         = profileUrl;
  document.getElementById('thread-view-profile').href      = profileUrl;
  document.getElementById('thread-view-profile-small').href = profileUrl;

  // Charger les messages
  const res  = await fetch(`api/messages.php?action=thread&contact_id=${cid}`);
  const msgs = await res.json();
  renderMessages(msgs, true);

  // URL
  window.history.replaceState({}, '', `messagerie.php?contact=${cid}`);

  schedulePoll();
  refreshUnreadBadge();
}

// ── Rendu des messages ───────────────────────────────────────────────────
function renderMessages(msgs, replace) {
  const container = document.getElementById('thread-messages');
  if (replace) {
    container.innerHTML = '';
    lastRenderedDay = null;   // reset au changement de conversation
  }

  msgs.forEach(m => {
    const d    = m.date_emission.substring(0, 10);
    const mine = m.emetteur_id === ME;

    // Séparateur de jour uniquement si on change de jour
    if (d !== lastRenderedDay) {
      lastRenderedDay = d;
      const sep = document.createElement('div');
      sep.className = 'msg-day-sep';
      sep.textContent = formatDay(d);
      container.appendChild(sep);
    }

    const wrap = document.createElement('div');
    wrap.className = `msg-bubble-wrap${mine ? ' mine' : ''}`;
    wrap.dataset.id = m.id;

    const initiales = ((m.nom||'').charAt(0) + (m.lastname||'').charAt(0)).toUpperCase();
    const avatarHtml = m.photo_profil
      ? `<img src="${m.photo_profil}" alt="" style="width:100%;height:100%;object-fit:cover;">`
      : initiales;

    wrap.innerHTML = `
      <div class="bubble-avatar${mine?' hidden':''}">${avatarHtml}</div>
      <div class="bubble">
        ${escapeHtml(m.contenu).replace(/\n/g,'<br>')}
        <span class="bubble-time">${formatTime(m.date_emission)}</span>
      </div>
    `;
    container.appendChild(wrap);
    lastMessageDate = m.date_emission;
  });

  if (replace || msgs.length > 0) {
    container.scrollTop = container.scrollHeight;
  }
}

// ── Envoyer un message ───────────────────────────────────────────────────
async function sendMessage() {
  const textarea = document.getElementById('msg-textarea');
  const contenu  = textarea.value.trim();
  if (!contenu || !selectedContactId) return;

  const btn = document.getElementById('btn-send');
  btn.disabled = true;
  textarea.value = '';
  textarea.style.height = 'auto';

  try {
    const res = await fetch('api/messages.php?action=send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ contact_id: selectedContactId, contenu })
    });
    const msg = await res.json();
    if (msg && msg.id) renderMessages([msg], false);
    updateConvPreview(selectedContactId, 'Vous : ' + contenu.substring(0, 45) + (contenu.length > 45 ? '…' : ''));
  } catch(e) {
    textarea.value = contenu;
  }
  btn.disabled = false;
  textarea.focus();
}

// ── Polling nouveaux messages ────────────────────────────────────────────
async function schedulePoll() {
  clearTimeout(pollTimer);
  if (!selectedContactId) return;
  pollTimer = setTimeout(async () => {
    try {
      const since = lastMessageDate || '1970-01-01 00:00:00';
      const res   = await fetch(`api/messages.php?action=poll&contact_id=${selectedContactId}&since=${encodeURIComponent(since)}`);
      const msgs  = await res.json();
      if (msgs.length > 0) renderMessages(msgs, false);
    } catch(e) {}
    schedulePoll();
  }, 4000);
}

// ── Badge global non lus ─────────────────────────────────────────────────
async function refreshUnreadBadge() {
  try {
    const res  = await fetch('api/messages.php?action=unread');
    const data = await res.json();
    const badge = document.getElementById('nav-unread');
    if (data.count > 0) {
      badge.textContent = data.count;
      badge.style.display = 'inline-flex';
    } else {
      badge.style.display = 'none';
    }
  } catch(e) {}
}

// ── Recherche utilisateurs ───────────────────────────────────────────────
let searchTimer = null;
document.getElementById('search-input').addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  if (q.length < 2) { closeSearch(); return; }
  searchTimer = setTimeout(() => searchUsers(q), 300);
});

document.addEventListener('click', e => {
  if (!e.target.closest('.search-wrap')) closeSearch();
});

async function searchUsers(q) {
  const res   = await fetch(`api/messages.php?action=search&q=${encodeURIComponent(q)}`);
  const users = await res.json();
  const box   = document.getElementById('search-results');
  box.innerHTML = '';
  if (!users.length) {
    box.innerHTML = '<div style="padding:12px;font-size:13px;color:var(--text-muted);">Aucun résultat</div>';
    box.classList.add('open');
    return;
  }
  users.forEach(u => {
    const init = (u.nom.charAt(0) + u.lastname.charAt(0)).toUpperCase();
    const avatarHtml = u.photo_profil
      ? `<div class="conv-avatar"><img src="${u.photo_profil}" alt=""></div>`
      : `<div class="conv-avatar">${init}</div>`;
    const div = document.createElement('div');
    div.className = 'search-result-item';
    div.innerHTML = `${avatarHtml}<span style="font-size:14px;font-weight:500;">${escapeHtml(u.nom)} ${escapeHtml(u.lastname)}</span>`;
    div.onclick = () => {
      closeSearch();
      document.getElementById('search-input').value = '';
      openConv(u.id_utilisateur, u.nom + ' ' + u.lastname, avatarHtml);
    };
    box.appendChild(div);
  });
  box.classList.add('open');
}

function closeSearch() {
  document.getElementById('search-results').classList.remove('open');
}

// ── Mettre à jour le preview dans la sidebar ─────────────────────────────
function updateConvPreview(cid, preview) {
  const item = document.querySelector(`.conv-item[data-cid="${cid}"]`);
  if (item) {
    item.querySelector('.conv-preview').textContent = preview;
    // Remonter en haut de la liste
    const list = document.getElementById('conv-list');
    list.prepend(item);
  } else {
    // Nouvelle conversation — recharger la sidebar
    location.reload();
  }
}

// ── Auto-resize textarea ─────────────────────────────────────────────────
const textarea = document.getElementById('msg-textarea');
textarea.addEventListener('input', () => {
  textarea.style.height = 'auto';
  textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
});
textarea.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// ── Utilitaires ──────────────────────────────────────────────────────────
function escapeHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatTime(dt) {
  const d = new Date(dt.replace(' ','T'));
  return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}
function formatDay(d) {
  const today = new Date().toISOString().substring(0,10);
  const yest  = new Date(Date.now()-86400000).toISOString().substring(0,10);
  if (d === today) return "Aujourd'hui";
  if (d === yest)  return 'Hier';
  return new Date(d).toLocaleDateString('fr-FR', { day:'numeric', month:'long', year:'numeric' });
}

// ── Init ─────────────────────────────────────────────────────────────────
refreshUnreadBadge();
setInterval(refreshUnreadBadge, 15000);

<?php if ($contactParam && $contactInfo): ?>
(function () {
  const url = 'profil.php?id=' + encodeURIComponent(<?php echo json_encode($contactParam); ?>);
  document.getElementById('thread-avatar-link').href        = url;
  document.getElementById('thread-name-link').href          = url;
  document.getElementById('thread-view-profile').href       = url;
  document.getElementById('thread-view-profile-small').href = url;
})();
openConv(
  <?php echo json_encode($contactParam); ?>,
  <?php echo json_encode($contactInfo['nom'].' '.$contactInfo['lastname']); ?>
);
<?php endif; ?>
</script>
</body>
</html>
