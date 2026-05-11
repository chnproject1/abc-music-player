<?php
// ──────────────────────────────────────────
//  abcMusic — Página de entrega da música
//  musicas.abcmusic.tech/{uuid}
// ──────────────────────────────────────────
 
// 1. Pegar UUID da URL
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uuid = preg_replace('/[^a-f0-9\-]/i', '', $path);
 
if (strlen($uuid) !== 36) {
    http_response_code(404);
    die('Música não encontrada.');
}
 
// 2. Configuração Supabase
define('SUPABASE_URL', 'https://baltzukuszagxcgkfrpi.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJhbHR6dWt1c3phZ3hjZ2tmcnBpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzczMTg4MjMsImV4cCI6MjA5Mjg5NDgyM30.gcRHTzssV3OsbObvnpnbROrrpA8Dn6zZz9j_qDJdw0s');
 
// 3. Buscar dados no Supabase
$api = SUPABASE_URL . '/rest/v1/presentes?uuid=eq.' . urlencode($uuid) . '&limit=1';
$ch  = curl_init($api);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'apikey: '        . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
    ],
]);
$resp = curl_exec($ch);
curl_close($ch);
 
$rows = json_decode($resp, true);
if (empty($rows)) {
    http_response_code(404);
    die('Música não encontrada.');
}
 
$m         = $rows[0];
$titulo    = htmlspecialchars($m['titulo']    ?? 'Sua música especial');
$nome      = htmlspecialchars($m['nome']      ?? '');
$audio_url = htmlspecialchars($m['audio_url'] ?? '');
$cover_url = htmlspecialchars($m['cover_url'] ?? '');
$letra_raw = $m['letra'] ?? '';
$letra     = nl2br(htmlspecialchars($letra_raw));
$tem_letra = trim($letra_raw) !== '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta property="og:title"       content="<?= $titulo ?> 🎵">
  <meta property="og:description" content="Uma música feita especialmente para você pela abcMusic.">
  <meta property="og:image"       content="<?= $cover_url ?>">
  <meta name="theme-color"        content="#0d1a12">
  <title><?= $titulo ?> — abcMusic</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
    body {
      min-height: 100dvh;
      background: #0d1a12;
      color: #f0faf4;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 0 0 40px;
      overflow-x: hidden;
    }
 
    /* ── Capa com blur de fundo ── */
    .bg-blur {
      position: fixed;
      inset: 0;
      z-index: 0;
      background-image: url('<?= $cover_url ?>');
      background-size: cover;
      background-position: center;
      filter: blur(60px) brightness(0.18) saturate(1.4);
      transform: scale(1.1);
    }
 
    .content {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 420px;
      padding: 0 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
 
    /* ── Header ── */
    .brand {
      padding: 20px 0 28px;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.12em;
      color: #34d399;
      text-transform: uppercase;
      text-decoration: none;
    }
 
    /* ── Capa ── */
    .cover-wrap {
      width: 100%;
      aspect-ratio: 1;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(0,0,0,0.7);
      margin-bottom: 28px;
      background: #1a2e20;
    }
    .cover-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .cover-placeholder {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 64px;
    }
 
    /* ── Título ── */
    .song-title {
      font-size: 22px;
      font-weight: 700;
      text-align: center;
      line-height: 1.3;
      margin-bottom: 6px;
      color: #ffffff;
    }
    .song-sub {
      font-size: 14px;
      color: #5ecea0;
      text-align: center;
      margin-bottom: 32px;
    }
 
    /* ── Player ── */
    .player {
      width: 100%;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(52,211,153,0.15);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 16px;
    }
 
    .progress-area {
      margin-bottom: 16px;
      cursor: pointer;
    }
    .progress-bar {
      width: 100%;
      height: 4px;
      background: rgba(255,255,255,0.12);
      border-radius: 2px;
      overflow: hidden;
      margin-bottom: 8px;
    }
    .progress-fill {
      height: 100%;
      width: 0%;
      background: #34d399;
      border-radius: 2px;
      transition: width 0.3s linear;
    }
    .time-row {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      color: rgba(255,255,255,0.4);
      font-variant-numeric: tabular-nums;
    }
 
    .controls {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 24px;
    }
    .btn-skip {
      background: none;
      border: none;
      cursor: pointer;
      padding: 8px;
      color: rgba(255,255,255,0.5);
      transition: color 0.15s;
      display: flex;
      align-items: center;
    }
    .btn-skip:hover { color: #34d399; }
    .btn-play {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #34d399;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.1s, background 0.15s;
      flex-shrink: 0;
    }
    .btn-play:hover  { background: #2ebd87; }
    .btn-play:active { transform: scale(0.95); }
    .btn-play svg    { fill: #0d1a12; }
 
    /* ── Botão baixar ── */
    .btn-download {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 14px;
      border-radius: 12px;
      background: rgba(52,211,153,0.1);
      border: 1px solid rgba(52,211,153,0.3);
      color: #34d399;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      margin-bottom: 24px;
      transition: background 0.15s;
    }
    .btn-download:hover { background: rgba(52,211,153,0.18); }
 
    /* ── Letra ── */
    .letra-section {
      width: 100%;
      margin-bottom: 32px;
    }
    .btn-letra {
      width: 100%;
      background: none;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 16px;
      border-radius: 12px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.7);
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 0;
      transition: background 0.15s;
    }
    .btn-letra:hover { background: rgba(255,255,255,0.07); }
    .btn-letra .arrow {
      transition: transform 0.25s;
      opacity: 0.5;
    }
    .btn-letra.open .arrow { transform: rotate(180deg); }
 
    .letra-body {
      display: none;
      padding: 20px 16px;
      font-size: 14px;
      line-height: 2;
      color: rgba(255,255,255,0.65);
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.06);
      border-top: none;
      border-radius: 0 0 12px 12px;
      white-space: pre-wrap;
    }
    .letra-body.open { display: block; }
 
    /* ── Footer ── */
    footer {
      font-size: 12px;
      color: rgba(255,255,255,0.2);
      text-align: center;
    }
    footer a {
      color: #34d399;
      text-decoration: none;
      opacity: 0.7;
    }
 
    /* ── Audio nativo escondido ── */
    audio { display: none; }
  </style>
</head>
<body>
 
<div class="bg-blur"></div>
 
<div class="content">
  <a class="brand" href="https://abcmusic.tech" target="_blank">abcMusic</a>
 
  <!-- Capa -->
  <div class="cover-wrap">
    <?php if ($cover_url): ?>
      <img src="<?= $cover_url ?>" alt="Capa da música" loading="eager">
    <?php else: ?>
      <div class="cover-placeholder">🎵</div>
    <?php endif; ?>
  </div>
 
  <!-- Título -->
  <h1 class="song-title"><?= $titulo ?></h1>
  <?php if ($nome): ?>
    <p class="song-sub">Uma música feita para <?= $nome ?></p>
  <?php endif; ?>
 
  <!-- Player -->
  <div class="player">
    <audio id="audio" src="<?= $audio_url ?>" preload="metadata"></audio>
 
    <div class="progress-area" id="progressArea">
      <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
      </div>
      <div class="time-row">
        <span id="timeNow">0:00</span>
        <span id="timeDur">0:00</span>
      </div>
    </div>
 
    <div class="controls">
      <!-- Voltar 10s -->
      <button class="btn-skip" onclick="seek(-10)" aria-label="Voltar 10 segundos">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M11 17l-5-5 5-5"/>
          <path d="M18 17l-5-5 5-5"/>
        </svg>
      </button>
 
      <!-- Play / Pause -->
      <button class="btn-play" id="playBtn" onclick="togglePlay()" aria-label="Play/Pause">
        <svg id="iconPlay" width="26" height="26" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        <svg id="iconPause" width="26" height="26" viewBox="0 0 24 24" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
      </button>
 
      <!-- Avançar 10s -->
      <button class="btn-skip" onclick="seek(10)" aria-label="Avançar 10 segundos">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M13 17l5-5-5-5"/>
          <path d="M6 17l5-5-5-5"/>
        </svg>
      </button>
    </div>
  </div>
 
  <!-- Baixar -->
  <a class="btn-download" href="<?= $audio_url ?>" download>
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M12 3v13M7 12l5 5 5-5M3 21h18"/>
    </svg>
    Baixar música (MP3)
  </a>
 
  <!-- Letra -->
  <?php if ($tem_letra): ?>
  <div class="letra-section">
    <button class="btn-letra" id="btnLetra" onclick="toggleLetra()">
      <span>Ver letra completa</span>
      <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M6 9l6 6 6-6"/>
      </svg>
    </button>
    <div class="letra-body" id="letraBody"><?= $letra ?></div>
  </div>
  <?php endif; ?>
 
  <footer>
    Feito com 💚 pela <a href="https://abcmusic.tech" target="_blank">abcMusic</a>
  </footer>
</div>
 
<script>
  const audio    = document.getElementById('audio');
  const fill     = document.getElementById('progressFill');
  const timeNow  = document.getElementById('timeNow');
  const timeDur  = document.getElementById('timeDur');
  const playBtn  = document.getElementById('playBtn');
  const iconPlay = document.getElementById('iconPlay');
  const iconPause= document.getElementById('iconPause');
 
  function fmt(s) {
    s = Math.floor(s || 0);
    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  }
 
  audio.addEventListener('loadedmetadata', () => {
    timeDur.textContent = fmt(audio.duration);
  });
 
  audio.addEventListener('timeupdate', () => {
    if (!audio.duration) return;
    const pct = (audio.currentTime / audio.duration) * 100;
    fill.style.width = pct + '%';
    timeNow.textContent = fmt(audio.currentTime);
  });
 
  audio.addEventListener('ended', () => {
    iconPlay.style.display  = '';
    iconPause.style.display = 'none';
  });
 
  function togglePlay() {
    if (audio.paused) {
      audio.play();
      iconPlay.style.display  = 'none';
      iconPause.style.display = '';
    } else {
      audio.pause();
      iconPlay.style.display  = '';
      iconPause.style.display = 'none';
    }
  }
 
  function seek(delta) {
    audio.currentTime = Math.max(0, Math.min(audio.duration || 0, audio.currentTime + delta));
  }
 
  document.getElementById('progressArea').addEventListener('click', function(e) {
    if (!audio.duration) return;
    const rect = this.getBoundingClientRect();
    audio.currentTime = ((e.clientX - rect.left) / rect.width) * audio.duration;
  });
 
  function toggleLetra() {
    const btn  = document.getElementById('btnLetra');
    const body = document.getElementById('letraBody');
    btn.classList.toggle('open');
    body.classList.toggle('open');
    btn.querySelector('span').textContent =
      btn.classList.contains('open') ? 'Esconder letra' : 'Ver letra completa';
  }
</script>
</body>
</html>
