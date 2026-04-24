<?php
// À inclure après _auth.php. Définit $adminPage (slug de la page active) avant include.
$adminPage = $adminPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — Seek &amp; Stay</title>
  <link rel="icon" type="image/x-icon" href="../img/flavicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <style>
    :root {
      --primary: #244676; --primary-h: #1a3459; --accent: #30bae6;
      --text: #1e293b; --muted: #64748b; --border: #e2e8f0;
      --bg: #f1f5f9; --white: #fff; --radius: 10px;
      --danger: #dc2626; --success: #059669; --warning: #d97706;
    }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); }

    .admin-layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
    .admin-sidebar {
      background: var(--primary); color: #fff;
      padding: 20px 0; display: flex; flex-direction: column;
    }
    .admin-sidebar-head {
      padding: 0 24px 20px; border-bottom: 1px solid rgba(255,255,255,.15);
      display: flex; align-items: center; gap: 10px;
    }
    .admin-sidebar-head img { width: 32px; height: 32px; border-radius: 6px; }
    .admin-sidebar-head strong { font-size: 14px; font-weight: 700; }
    .admin-nav { display: flex; flex-direction: column; padding: 16px 12px; gap: 2px; }
    .admin-nav a {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; color: rgba(255,255,255,.75);
      text-decoration: none; border-radius: 8px; font-size: 14px;
      transition: background .15s, color .15s;
    }
    .admin-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
    .admin-nav a.active { background: rgba(255,255,255,.18); color: #fff; font-weight: 600; }
    .admin-nav a i { width: 16px; text-align: center; }

    .admin-sidebar-foot {
      margin-top: auto; padding: 16px 24px; border-top: 1px solid rgba(255,255,255,.15);
      font-size: 12px; color: rgba(255,255,255,.55);
    }
    .admin-sidebar-foot a { color: rgba(255,255,255,.8); text-decoration: none; }
    .admin-sidebar-foot a:hover { text-decoration: underline; }

    .admin-main { padding: 30px 40px; overflow: auto; }
    .admin-title { font-size: 1.6rem; font-weight: 800; margin: 0 0 6px; color: var(--text); }
    .admin-subtitle { font-size: 14px; color: var(--muted); margin: 0 0 28px; }

    .card {
      background: var(--white); border-radius: var(--radius);
      box-shadow: 0 1px 3px rgba(0,0,0,.05); padding: 20px 24px;
      margin-bottom: 20px;
    }

    table.admin-table {
      width: 100%; border-collapse: collapse; font-size: 13px;
    }
    .admin-table th, .admin-table td {
      padding: 10px 12px; text-align: left;
      border-bottom: 1px solid var(--border); vertical-align: middle;
    }
    .admin-table th {
      background: #f8fafc; font-size: 11px; font-weight: 700;
      color: var(--muted); text-transform: uppercase; letter-spacing: .4px;
    }
    .admin-table tr:hover td { background: #f8fafc; }

    .btn-adm {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 6px 11px; font-size: 12px; font-weight: 600;
      border-radius: 6px; border: 1px solid var(--border);
      background: var(--white); color: var(--text);
      cursor: pointer; font-family: inherit; text-decoration: none;
      transition: background .15s, border-color .15s, color .15s;
    }
    .btn-adm:hover { border-color: var(--primary); color: var(--primary); }
    .btn-adm.danger { border-color: #fecaca; color: var(--danger); }
    .btn-adm.danger:hover { background: #fef2f2; }
    .btn-adm.success { border-color: #a7f3d0; color: var(--success); }
    .btn-adm.success:hover { background: #ecfdf5; }

    .badge-adm {
      display: inline-flex; padding: 2px 9px;
      font-size: 11px; font-weight: 700; border-radius: 20px;
    }
    .badge-proprio  { background: #dbeafe; color: #1e40af; }
    .badge-loueur   { background: #f1f5f9; color: #475569; }
    .badge-admin    { background: #fce7f3; color: #9f1239; }
    .badge-actif    { background: #dcfce7; color: #166534; }
    .badge-inactif  { background: #f1f5f9; color: #475569; }
    .badge-archive  { background: #fef3c7; color: #92400e; }
    .badge-attente  { background: #fef3c7; color: #92400e; }
    .badge-traite   { background: #dcfce7; color: #166534; }
    .badge-rejete   { background: #fee2e2; color: #991b1b; }

    .stats-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px; margin-bottom: 24px;
    }
    .stat-box {
      background: var(--white); border-radius: var(--radius);
      padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .stat-box .stat-label { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
    .stat-box .stat-num { font-size: 2rem; font-weight: 800; color: var(--primary); display: block; margin-top: 6px; line-height: 1; }
    .stat-box .stat-icon {
      float: right; width: 36px; height: 36px; border-radius: 10px;
      background: rgba(48,186,230,.12); color: var(--accent);
      display: flex; align-items: center; justify-content: center;
    }

    .msg-box {
      padding: 12px 16px; border-radius: 8px; margin-bottom: 18px;
      font-size: 13px; border: 1px solid;
    }
    .msg-box.success { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .msg-box.error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    @media (max-width: 900px) {
      .admin-layout { grid-template-columns: 1fr; }
      .admin-sidebar { display: none; }
      .admin-main { padding: 20px; }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="admin-sidebar-head">
      <img src="../img/iconSite.png" alt="logo" />
      <strong>Backoffice</strong>
    </div>

    <nav class="admin-nav">
      <a href="dashboard.php"     class="<?php echo $adminPage==='dashboard'   ?'active':''; ?>"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
      <a href="signalements.php"  class="<?php echo $adminPage==='signalements'?'active':''; ?>"><i class="fa-solid fa-flag"></i> Signalements</a>
      <a href="users.php"         class="<?php echo $adminPage==='users'       ?'active':''; ?>"><i class="fa-solid fa-users"></i> Utilisateurs</a>
      <a href="annonces.php"      class="<?php echo $adminPage==='annonces'    ?'active':''; ?>"><i class="fa-solid fa-house"></i> Annonces</a>
      <a href="gestion_mentions.php" class="<?php echo $adminPage==='mentions' ?'active':''; ?>"><i class="fa-solid fa-scale-balanced"></i> Mentions légales</a>
    </nav>

    <div class="admin-sidebar-foot">
      Connecté : <?php echo htmlspecialchars($_SESSION['nom'] ?? ''); ?><br>
      <a href="../Accueil.php"><i class="fa-solid fa-arrow-left"></i> Retour au site</a>
    </div>
  </aside>

  <main class="admin-main">
