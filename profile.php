<?php
include 'connect.php';
if (!isLoggedIn()) { header("Location: index.php"); exit(); }
$uid = (int)$_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
$msg = '';

if (isset($_POST['update'])) {
    $fn = $conn->real_escape_string($_POST['firstName']);
    $ln = $conn->real_escape_string($_POST['lastName']);
    $conn->query("UPDATE users SET FirstName='$fn', LastName='$ln' WHERE id=$uid");
    $_SESSION['name'] = $fn;
    $user['FirstName']=$fn; $user['LastName']=$ln;
    $msg = 'پرۆفایلەکەت نوێکرایەوە ✅';
}
if (isset($_POST['change_pass'])) {
    $old = md5($_POST['old_pass']);
    $new = md5($_POST['new_pass']);
    $r = $conn->query("SELECT id FROM users WHERE id=$uid AND Password='$old' LIMIT 1");
    if ($r->num_rows) { $conn->query("UPDATE users SET Password='$new' WHERE id=$uid"); $msg='پاسوۆردەکەت گۆڕدرا ✅'; }
    else $msg='پاسوۆردی کۆنت هەڵەیە ❌';
}

$my_count  = (int)$conn->query("SELECT COUNT(*) as c FROM products WHERE added_by=$uid")->fetch_assoc()['c'];
$sold_count= (int)$conn->query("SELECT COUNT(*) as c FROM sale_orders WHERE seller_id=$uid AND payment_status='paid'")->fetch_assoc()['c'];
$earning   = (float)($conn->query("SELECT SUM(price) as t FROM sale_orders WHERE seller_id=$uid AND payment_status='paid'")->fetch_assoc()['t']??0);
$avatar = "https://ui-avatars.com/api/?name=".urlencode($user['FirstName'].' '.$user['LastName'])."&size=80&background=7c6fff&color=fff&bold=true";
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>پرۆفایل — MobileShop</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Noto Sans Arabic',sans-serif}
:root{--bg:#070912;--s:#0e1018;--s2:#141520;--bd:rgba(255,255,255,.08);--ac:#7c6fff;--ac2:#ff6b9d;--green:#00d4aa;--text:#f0f0ff;--muted:rgba(255,255,255,.4)}
body{background:var(--bg);color:var(--text);min-height:100vh}
nav{background:rgba(7,9,18,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--bd);padding:0 24px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
.logo{font-size:1rem;font-weight:800;background:linear-gradient(135deg,var(--ac),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav-r{display:flex;gap:6px}
.nb{background:var(--s2);border:1px solid var(--bd);color:var(--muted);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:700;transition:.2s}
.nb:hover{border-color:var(--ac);color:var(--ac)}
.wrap{max-width:720px;margin:0 auto;padding:36px 20px}
/* پرۆفایل هێدەر */
.profile-head{background:var(--s);border:1px solid var(--bd);border-radius:20px;padding:28px;display:flex;align-items:center;gap:20px;margin-bottom:24px;flex-wrap:wrap}
.avatar{width:80px;height:80px;border-radius:50%;border:3px solid var(--ac);flex-shrink:0;box-shadow:0 4px 20px rgba(124,111,255,.3)}
.pname{font-size:1.3rem;font-weight:800;margin-bottom:4px}
.pemail{font-size:.85rem;color:var(--muted)}
.pbadges{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.pbadge{padding:4px 12px;border-radius:50px;font-size:.72rem;font-weight:800}
.pbadge-a{background:rgba(124,111,255,.15);color:var(--ac);border:1px solid rgba(124,111,255,.3)}
.pbadge-v{background:rgba(0,212,170,.12);color:var(--green);border:1px solid rgba(0,212,170,.25)}
/* آمارەکان */
.pstats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px}
.pstat{background:var(--s);border:1px solid var(--bd);border-radius:14px;padding:16px;text-align:center}
.pstat-n{font-size:1.6rem;font-weight:800}
.pstat-l{font-size:.73rem;color:var(--muted);margin-top:4px}
/* فۆرمەکان */
.card{background:var(--s);border:1px solid var(--bd);border-radius:18px;padding:22px;margin-bottom:16px}
.card-title{font-size:.95rem;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.field{margin-bottom:14px}
.field label{display:block;font-size:.72rem;color:var(--muted);font-weight:700;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px}
input[type=text],input[type=password]{width:100%;padding:11px 14px;background:rgba(255,255,255,.05);border:1.5px solid var(--bd);border-radius:11px;color:var(--text);font-family:inherit;font-size:.9rem;outline:none;transition:.2s}
input:focus{border-color:var(--ac);background:rgba(124,111,255,.08)}
input::placeholder{color:var(--muted)}
.btn-save{padding:11px 22px;background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.7));color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.88rem;font-weight:800;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:6px}
.btn-save:hover{transform:translateY(-1px);opacity:.9}
.msg-ok{background:rgba(0,212,170,.1);border:1px solid rgba(0,212,170,.25);color:var(--green);padding:10px 14px;border-radius:10px;font-size:.85rem;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.msg-err{background:rgba(255,107,157,.1);border:1px solid rgba(255,107,157,.25);color:var(--ac2);padding:10px 14px;border-radius:10px;font-size:.85rem;font-weight:600;margin-bottom:18px}
</style>
</head>
<body>
<nav>
  <div class="logo">👤 پرۆفایل</div>
  <div class="nav-r">
    <a href="mobile.php" class="nb"><i class="fas fa-store"></i> فرۆشگا</a>
    <a href="seller_dashboard.php" class="nb"><i class="fas fa-chart-bar"></i> داشبۆرد</a>
    <a href="logout.php" class="nb" style="color:var(--ac2)"><i class="fas fa-sign-out-alt"></i> چوونەدەرەوە</a>
  </div>
</nav>
<div class="wrap">
  <?php if($msg): ?>
    <div class="<?=str_contains($msg,'❌')?'msg-err':'msg-ok'?>">
      <i class="fas fa-<?=str_contains($msg,'❌')?'exclamation-circle':'check-circle'?>"></i><?=$msg?>
    </div>
  <?php endif; ?>

  <!-- پرۆفایل هێدەر -->
  <div class="profile-head">
    <img src="<?=$avatar?>" class="avatar">
    <div style="flex:1">
      <div class="pname"><?=htmlspecialchars($user['FirstName'].' '.$user['LastName'])?></div>
      <div class="pemail"><i class="fas fa-envelope" style="margin-left:6px;font-size:.75rem"></i><?=htmlspecialchars($user['Email'])?></div>
      <div class="pbadges">
        <?php if(!empty($user['is_admin'])&&$user['is_admin']):?>
          <span class="pbadge pbadge-a">🛡 ئەدمین</span>
        <?php endif;?>
        <?php if(!empty($user['is_verified'])&&$user['is_verified']):?>
          <span class="pbadge pbadge-v">✔ ڤێریفاید</span>
        <?php else:?>
          <span class="pbadge" style="background:rgba(255,255,255,.05);color:var(--muted);border:1px solid var(--bd)">— ڤێریفایدنییە</span>
        <?php endif;?>
      </div>
    </div>
  </div>

  <!-- آمارەکان -->
  <div class="pstats">
    <div class="pstat">
      <div class="pstat-n" style="color:var(--ac)"><?=$my_count?></div>
      <div class="pstat-l">📦 بەرهەمەکانم</div>
    </div>
    <div class="pstat">
      <div class="pstat-n" style="color:var(--green)"><?=$sold_count?></div>
      <div class="pstat-l">✅ فرۆشراوەکان</div>
    </div>
    <div class="pstat">
      <div class="pstat-n" style="color:var(--green)">$<?=number_format($earning,0)?></div>
      <div class="pstat-l">💰 کۆی کاسبی</div>
    </div>
  </div>

  <!-- نوێکردنەوەی زانیاری -->
  <div class="card">
    <div class="card-title"><i class="fas fa-user-edit" style="color:var(--ac)"></i> نوێکردنەوەی زانیاری</div>
    <form method="POST">
      <div class="row2">
        <div class="field">
          <label>ناوی یەکەم</label>
          <input type="text" name="firstName" value="<?=htmlspecialchars($user['FirstName'])?>" required>
        </div>
        <div class="field">
          <label>ناوی دووەم</label>
          <input type="text" name="lastName" value="<?=htmlspecialchars($user['LastName'])?>" required>
        </div>
      </div>
      <button type="submit" name="update" class="btn-save"><i class="fas fa-save"></i> پاراستن</button>
    </form>
  </div>

  <!-- گۆڕینی پاسوۆرد -->
  <div class="card">
    <div class="card-title"><i class="fas fa-lock" style="color:var(--ac2)"></i> گۆڕینی پاسوۆرد</div>
    <form method="POST">
      <div class="field">
        <label>پاسوۆردی کۆن</label>
        <input type="password" name="old_pass" placeholder="••••••••" required>
      </div>
      <div class="field">
        <label>پاسوۆردی نوێ</label>
        <input type="password" name="new_pass" placeholder="••••••••" required>
      </div>
      <button type="submit" name="change_pass" class="btn-save"><i class="fas fa-key"></i> گۆڕین</button>
    </form>
  </div>
</div>
</body>
</html>
