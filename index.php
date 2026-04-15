<?php
include 'connect.php';
$err = $ok = '';
if (isset($_POST['login'])) {
    $em = $conn->real_escape_string($_POST['email']);
    $pw = md5($_POST['password']);
    $r = $conn->query("SELECT * FROM users WHERE Email='$em' AND Password='$pw' LIMIT 1");
    if ($r && $r->num_rows) {
        $u = $r->fetch_assoc();
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['name']    = $u['FirstName'];
        $_SESSION['is_admin']= $u['is_admin'];
        header("Location: mobile.php"); exit();
    } else {
        $err = "ئیمێڵ یان پاسوۆرد هەڵەیە";
    }
}
if (isset($_POST['register'])) {
    $em = $conn->real_escape_string($_POST['email']);
    $fn = $conn->real_escape_string($_POST['firstName']);
    $ln = $conn->real_escape_string($_POST['lastName']);
    $pw = md5($_POST['password']);
    if ($conn->query("SELECT id FROM users WHERE Email='$em' LIMIT 1")->num_rows) {
        $err = "ئەم ئیمێڵە پێشتر تۆمارکراوە";
    } else {
        $conn->query("INSERT INTO users(FirstName,LastName,Email,Password) VALUES('$fn','$ln','$em','$pw')");
        $ok = "تۆمارکردن سەرکەوتووبوو! ئێستا داخل ببەرەوە";
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MobileShop — داخلبوون</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#060812;
  --card:rgba(255,255,255,.04);
  --border:rgba(255,255,255,.08);
  --ac:#7c6fff;
  --ac2:#ff6b9d;
  --text:#f0f0ff;
  --muted:rgba(255,255,255,.4);
  --inp:rgba(255,255,255,.06);
  --inp-focus:rgba(124,111,255,.15);
}
body{
  background:var(--bg);
  color:var(--text);
  font-family:'Noto Sans Arabic',sans-serif;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
  position:relative;
}
/* خوشەمەرگی پاشزەوی */
.bg-orbs{position:fixed;inset:0;z-index:0;pointer-events:none}
.orb{position:absolute;border-radius:50%;filter:blur(120px);opacity:.15;animation:drift 12s ease-in-out infinite}
.orb1{width:600px;height:600px;background:var(--ac);top:-200px;right:-200px;animation-delay:0s}
.orb2{width:500px;height:500px;background:var(--ac2);bottom:-150px;left:-150px;animation-delay:-6s}
.orb3{width:300px;height:300px;background:#00d4ff;top:50%;left:50%;transform:translate(-50%,-50%);animation-delay:-3s}
@keyframes drift{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-40px) scale(1.05)}}

/* شەبەکەی پاشزەوی */
.grid-bg{
  position:fixed;inset:0;z-index:0;
  background-image:linear-gradient(rgba(124,111,255,.05) 1px,transparent 1px),
    linear-gradient(90deg,rgba(124,111,255,.05) 1px,transparent 1px);
  background-size:60px 60px;
}

.wrap{
  position:relative;z-index:10;
  width:100%;max-width:420px;
  padding:16px;
}

/* لۆگۆ */
.logo-area{text-align:center;margin-bottom:32px}
.logo-icon{
  width:72px;height:72px;
  background:linear-gradient(135deg,var(--ac),var(--ac2));
  border-radius:20px;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:2rem;
  margin-bottom:12px;
  box-shadow:0 8px 32px rgba(124,111,255,.4);
  animation:iconPop .6s cubic-bezier(.34,1.56,.64,1);
}
@keyframes iconPop{from{transform:scale(0) rotate(-20deg);opacity:0}to{transform:scale(1) rotate(0);opacity:1}}
.logo-name{font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,var(--ac),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo-sub{font-size:.8rem;color:var(--muted);margin-top:4px}

/* کارتی ئەکاونت */
.card{
  background:rgba(255,255,255,.03);
  border:1px solid rgba(255,255,255,.07);
  border-radius:24px;
  padding:32px 28px;
  backdrop-filter:blur(20px);
  box-shadow:0 32px 80px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.05);
  animation:cardIn .5s .2s both ease-out;
}
@keyframes cardIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

/* تابەکان */
.tabs{
  display:flex;
  background:rgba(255,255,255,.04);
  border-radius:12px;
  padding:4px;
  margin-bottom:28px;
  gap:4px;
}
.tab{
  flex:1;padding:10px;border:none;
  background:transparent;color:var(--muted);
  font-family:inherit;font-size:.9rem;font-weight:700;
  cursor:pointer;border-radius:9px;transition:.25s;
}
.tab.on{
  background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.8));
  color:#fff;
  box-shadow:0 4px 16px rgba(124,111,255,.35);
}

/* فۆرم */
.form{display:none;animation:fadeIn .3s ease}
.form.on{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

.field{margin-bottom:14px}
.field-inner{
  position:relative;
  display:flex;align-items:center;
}
.field-icon{
  position:absolute;
  right:14px;
  color:var(--muted);
  font-size:.9rem;
  z-index:1;
  pointer-events:none;
}
input[type=text],input[type=email],input[type=password]{
  width:100%;
  background:var(--inp);
  border:1.5px solid var(--border);
  border-radius:12px;
  color:var(--text);
  padding:13px 40px 13px 14px;
  font-family:inherit;
  font-size:.95rem;
  outline:none;
  transition:.2s;
}
input:focus{
  background:var(--inp-focus);
  border-color:var(--ac);
  box-shadow:0 0 0 3px rgba(124,111,255,.15);
}
input::placeholder{color:var(--muted)}

/* ئەرۆری + سەرکەوتن */
.err-box,.ok-box{
  padding:11px 14px;border-radius:11px;
  font-size:.85rem;font-weight:600;
  display:flex;align-items:center;gap:8px;
  margin-bottom:18px;
  animation:shake .4s;
}
.err-box{background:rgba(255,75,75,.1);border:1px solid rgba(255,75,75,.25);color:#ff7b7b}
.ok-box{background:rgba(0,210,130,.1);border:1px solid rgba(0,210,130,.25);color:#00d284}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}

/* دوگمەی داخلبوون */
.btn-submit{
  width:100%;padding:14px;border:none;
  background:linear-gradient(135deg,var(--ac),rgba(200,100,255,.8));
  color:#fff;border-radius:12px;
  font-family:inherit;font-size:1rem;font-weight:700;
  cursor:pointer;transition:.25s;
  box-shadow:0 8px 24px rgba(124,111,255,.35);
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:6px;
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(124,111,255,.5)}
.btn-submit:active{transform:translateY(0)}

/* خەتی دابەشکەر */
.divider{
  display:flex;align-items:center;gap:12px;
  color:var(--muted);font-size:.8rem;
  margin:20px 0;
}
.divider::before,.divider::after{
  content:'';flex:1;height:1px;background:var(--border);
}

.social-row{display:flex;gap:10px}
.social-btn{
  flex:1;padding:11px;
  background:var(--inp);border:1px solid var(--border);
  color:var(--text);border-radius:11px;
  display:flex;align-items:center;justify-content:center;gap:8px;
  font-family:inherit;font-size:.85rem;font-weight:600;
  cursor:pointer;transition:.2s;text-decoration:none;
}
.social-btn:hover{border-color:var(--ac);background:rgba(124,111,255,.1)}

/* پێپۆی ژێرەوە */
.bottom-links{
  display:flex;justify-content:center;gap:20px;
  margin-top:20px;
}
.bl a{font-size:.8rem;color:var(--muted);text-decoration:none;transition:.2s}
.bl a:hover{color:var(--ac)}

/* چیراخی پاراستن */
.security-note{
  display:flex;align-items:center;justify-content:center;gap:6px;
  color:var(--muted);font-size:.75rem;margin-top:16px;
}
.security-note i{color:var(--ac)}
</style>
</head>
<body>
<div class="bg-orbs">
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>
</div>
<div class="grid-bg"></div>

<div class="wrap">
  <div class="logo-area">
    <div class="logo-icon">📱</div>
    <div class="logo-name">MobileShop</div>
    <div class="logo-sub">باشترین شوێن بۆ کڕین و فرۆشتنی مۆبایل</div>
  </div>

  <div class="card">
    <?php if($err): ?>
      <div class="err-box"><i class="fas fa-exclamation-circle"></i><?= $err ?></div>
    <?php endif; ?>
    <?php if($ok): ?>
      <div class="ok-box"><i class="fas fa-check-circle"></i><?= $ok ?></div>
    <?php endif; ?>

    <div class="tabs">
      <button class="tab on" id="tab-lg" onclick="sw('lg',this)">
        <i class="fas fa-sign-in-alt" style="margin-left:6px"></i>داخلبوون
      </button>
      <button class="tab" id="tab-rg" onclick="sw('rg',this)">
        <i class="fas fa-user-plus" style="margin-left:6px"></i>تۆمارکردن
      </button>
    </div>

    <!-- فۆرمی داخلبوون -->
    <form class="form on" id="lg" method="POST">
      <div class="field">
        <div class="field-inner">
          <i class="fas fa-envelope field-icon"></i>
          <input type="email" name="email" placeholder="ئیمێڵ" required autocomplete="email">
        </div>
      </div>
      <div class="field">
        <div class="field-inner">
          <i class="fas fa-lock field-icon"></i>
          <input type="password" name="password" placeholder="پاسوۆرد" required>
        </div>
      </div>
      <button type="submit" name="login" class="btn-submit">
        <i class="fas fa-arrow-left"></i> داخلبوون
      </button>
    </form>

    <!-- فۆرمی تۆمارکردن -->
    <form class="form" id="rg" method="POST">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="field">
          <div class="field-inner">
            <i class="fas fa-user field-icon"></i>
            <input type="text" name="firstName" placeholder="ناوی یەکەم" required>
          </div>
        </div>
        <div class="field">
          <div class="field-inner">
            <i class="fas fa-user field-icon"></i>
            <input type="text" name="lastName" placeholder="ناوی دووەم" required>
          </div>
        </div>
      </div>
      <div class="field">
        <div class="field-inner">
          <i class="fas fa-envelope field-icon"></i>
          <input type="email" name="email" placeholder="ئیمێڵ" required autocomplete="email">
        </div>
      </div>
      <div class="field">
        <div class="field-inner">
          <i class="fas fa-lock field-icon"></i>
          <input type="password" name="password" placeholder="پاسوۆرد" required>
        </div>
      </div>
      <button type="submit" name="register" class="btn-submit">
        <i class="fas fa-user-plus"></i> دروستکردنی ئەکاونت
      </button>
    </form>

    <div class="security-note">
      <i class="fas fa-shield-alt"></i>
      <span>زانیاریەکانت پارێزراوە</span>
    </div>
  </div>

  <div class="bottom-links">
    <span class="bl"><a href="#">یارمەتی</a></span>
    <span class="bl"><a href="#">پەیوەندیمان پێوە بکە</a></span>
    <span class="bl"><a href="#">نهێنی</a></span>
  </div>
</div>

<script>
function sw(id, btn) {
  document.querySelectorAll('.form').forEach(f => f.classList.remove('on'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
  document.getElementById(id).classList.add('on');
  btn.classList.add('on');
}
</script>
</body>
</html>
