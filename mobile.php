<?php
include 'connect.php';

if (!isset($_SESSION['cart']))  $_SESSION['cart'] = [];
if (!isset($_SESSION['lang']))  $_SESSION['lang'] = 'ku';
if (!isset($_SESSION['dark']))  $_SESSION['dark'] = true;

$notif_count = 0; $unread_chats = 0;
if (isLoggedIn()) {
    $uid_m = (int)$_SESSION['user_id'];
    $r_notif = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid_m AND is_read=0");
    if ($r_notif) $notif_count = (int)$r_notif->fetch_assoc()['c'];
    $r_chat = $conn->query("SELECT COUNT(*) as c FROM chats WHERE receiver_id=$uid_m AND is_read=0");
    if ($r_chat) $unread_chats = (int)$r_chat->fetch_assoc()['c'];
}

if (isset($_GET['lang'])) { $_SESSION['lang'] = $_GET['lang']; header("Location: mobile.php"); exit(); }
if (isset($_GET['dark'])) { $_SESSION['dark'] = $_GET['dark']=='1'; header("Location: mobile.php"); exit(); }
if (isset($_POST['add_to_cart'])) {
    $id = (int)$_POST['id'];
    if (!in_array($id, $_SESSION['cart'])) $_SESSION['cart'][] = $id;
    header("Location: cart.php"); exit();
}

$lang = $_SESSION['lang'];
$dark = $_SESSION['dark'];
$cart_count = count($_SESSION['cart']);
$t = [
    'ku'=>['home'=>'سەرەکی','add_product'=>'زیادکردنی کاڵا','cart'=>'سەبەتە','search'=>'گەڕان بۆ مۆبایل...','all'=>'هەموو','add_to_cart'=>'زیادکردن','no_products'=>'هیچ بەرهەمێک نەدۆزرایەوە','chat'=>'پرسیار','dashboard'=>'داشبۆردم'],
    'en'=>['home'=>'Home','add_product'=>'Add Product','cart'=>'Cart','search'=>'Search mobiles...','all'=>'All','add_to_cart'=>'Add to Cart','no_products'=>'No products found','chat'=>'Ask','dashboard'=>'Dashboard'],
];
$tr = $t[$lang];

$userData = null;
if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];
    $r = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1");
    if ($r && $r->num_rows) {
        $userData = $r->fetch_assoc();
    }
}

$products = $conn->query("SELECT p.*,CONCAT(u.FirstName,' ',u.LastName) AS added_by_name,u.is_verified FROM products p LEFT JOIN users u ON u.id=p.added_by WHERE p.status='approved' ORDER BY p.id DESC");
$total_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='approved'")->fetch_assoc()['c'];
$brands_count   = $conn->query("SELECT COUNT(DISTINCT brand) as c FROM products WHERE status='approved'")->fetch_assoc()['c'];
$sellers_count  = $conn->query("SELECT COUNT(DISTINCT added_by) as c FROM products WHERE status='approved'")->fetch_assoc()['c'];

$productImages = [];
$imgQuery = $conn->query("SELECT pi.product_id, pi.image FROM product_images pi INNER JOIN products p ON p.id=pi.product_id WHERE p.status='approved' ORDER BY pi.id ASC");
if ($imgQuery) { while ($imgRow = $imgQuery->fetch_assoc()) { $productImages[$imgRow['product_id']][] = $imgRow['image']; } }

$pending_count = 0;
if ($userData && !empty($userData['is_admin']))
    $pending_count = (int)$conn->query("SELECT COUNT(*) as c FROM products WHERE status='pending'")->fetch_assoc()['c'];

// نۆتیفەکان بۆ پاپئەپ
$notif_items = [];
if (isLoggedIn()) {
    $nr = $conn->query("SELECT n.*,p.image as prod_img FROM notifications n LEFT JOIN products p ON p.id=n.product_id WHERE n.user_id=$uid_m ORDER BY n.created_at DESC LIMIT 8");
    if ($nr) while ($ni = $nr->fetch_assoc()) $notif_items[] = $ni;
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$lang=='ku'?'rtl':'ltr'?>" data-theme="<?=$dark?'dark':'light'?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MobileShop — <?=$tr['home']?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
:root{
  --bg:#070912;--bg2:#0e1018;--bg3:#141520;
  --card:#111320;--card2:#161828;
  --border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.12);
  --text:#f0f0ff;--text2:rgba(255,255,255,.5);--text3:rgba(255,255,255,.3);
  --ac:#7c6fff;--ac2:#ff6b9d;--ac3:#00d4aa;
  --yellow:#ffd93d;--r:16px;
  --nav:rgba(7,9,18,.85);
  --shadow:0 8px 32px rgba(0,0,0,.5);
}
[data-theme=light]{
  --bg:#f4f5ff;--bg2:#eceeff;--bg3:#e2e4f8;
  --card:#fff;--card2:#f8f9ff;
  --border:rgba(0,0,20,.08);--border2:rgba(0,0,20,.14);
  --text:#0a0b1e;--text2:rgba(10,11,30,.5);--text3:rgba(10,11,30,.3);
  --nav:rgba(244,245,255,.88);
  --shadow:0 4px 20px rgba(0,0,50,.1);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:'Noto Sans Arabic','Inter',sans-serif;min-height:100vh}

/* ===== NAV ===== */
nav{
  background:var(--nav);
  backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:200;
  padding:0 24px;height:66px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.logo{
  display:flex;align-items:center;gap:10px;text-decoration:none;
  font-size:1.25rem;font-weight:800;
  background:linear-gradient(135deg,var(--ac),var(--ac2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  white-space:nowrap;
}
.logo-dot{width:8px;height:8px;border-radius:50%;background:var(--ac);box-shadow:0 0 10px var(--ac);flex-shrink:0;-webkit-text-fill-color:unset}
.nav-mid{flex:1;max-width:360px;margin:0 20px}
.search-box{
  display:flex;align-items:center;gap:8px;
  background:var(--bg3);border:1px solid var(--border2);
  border-radius:50px;padding:8px 16px;
  transition:.2s;
}
.search-box:focus-within{border-color:var(--ac);box-shadow:0 0 0 3px rgba(124,111,255,.15)}
.search-box input{
  background:none;border:none;outline:none;
  color:var(--text);font-family:inherit;font-size:.9rem;width:100%;
}
.search-box input::placeholder{color:var(--text3)}
.search-box i{color:var(--text3);font-size:.85rem;flex-shrink:0}
.nav-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}
.nav-btn{
  background:var(--bg3);border:1px solid var(--border);
  color:var(--text);padding:8px 14px;
  border-radius:50px;text-decoration:none;
  font-size:.8rem;font-weight:700;cursor:pointer;
  transition:.25s;display:flex;align-items:center;gap:6px;
  white-space:nowrap;
}
.nav-btn:hover,.nav-btn.active{background:var(--ac);color:#fff;border-color:var(--ac);box-shadow:0 4px 16px rgba(124,111,255,.35)}
.nav-icon-btn{
  position:relative;
  width:40px;height:40px;border-radius:50%;
  background:var(--bg3);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  color:var(--text);text-decoration:none;cursor:pointer;
  transition:.2s;font-size:.9rem;
}
.nav-icon-btn:hover{border-color:var(--ac);color:var(--ac)}
.badge{
  position:absolute;top:-4px;right:-4px;
  background:var(--ac2);color:#fff;
  font-size:9px;font-weight:800;
  min-width:17px;height:17px;border-radius:20px;
  display:flex;align-items:center;justify-content:center;
  padding:0 4px;border:2px solid var(--bg);
  animation:pulse 1.8s infinite;
}
.badge-notif{background:var(--yellow);color:#000}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(255,107,157,.5)}60%{box-shadow:0 0 0 6px rgba(255,107,157,0)}}

/* نۆتیف پاپئەپ */
.notif-wrap{position:relative}
.notif-popup{
  display:none;position:absolute;top:52px;
  background:var(--card);border:1px solid var(--border2);
  border-radius:18px;width:300px;
  box-shadow:0 20px 60px rgba(0,0,0,.4);
  z-index:300;overflow:hidden;
}
.notif-popup.open{display:block;animation:popIn .2s ease}
@keyframes popIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
[dir=rtl] .notif-popup{right:0}[dir=ltr] .notif-popup{left:0}
.notif-head{padding:14px 16px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:800;display:flex;align-items:center;justify-content:space-between}
.notif-head a{font-size:.75rem;color:var(--ac);text-decoration:none;font-weight:600}
.notif-item{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:10px;transition:.2s}
.notif-item:hover{background:var(--bg3)}
.notif-item:last-child{border:none}
.notif-icon-wrap{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;background:var(--bg3)}
.notif-body{flex:1;min-width:0}
.notif-title{font-size:.82rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.notif-msg{font-size:.73rem;color:var(--text2);margin-top:2px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.notif-empty{padding:30px;text-align:center;color:var(--text3);font-size:.85rem}

/* ===== هێرۆ ===== */
.hero{
  background:linear-gradient(145deg,var(--bg) 0%,var(--bg2) 50%,var(--bg3) 100%);
  padding:64px 24px 72px;text-align:center;
  position:relative;overflow:hidden;
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(124,111,255,.12),transparent 70%);
}
.hero-title{
  font-size:clamp(2rem,5vw,3.2rem);
  font-weight:800;line-height:1.2;
  position:relative;z-index:1;
}
.hero-title span{
  background:linear-gradient(135deg,var(--ac),var(--ac2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.hero-sub{
  font-size:1rem;color:var(--text2);
  max-width:460px;margin:12px auto 28px;
  line-height:1.7;position:relative;z-index:1;
}
.hero-stats{
  display:flex;justify-content:center;flex-wrap:wrap;gap:16px;
  position:relative;z-index:1;
}
.stat-pill{
  background:var(--card);border:1px solid var(--border2);
  border-radius:50px;padding:10px 20px;
  display:flex;align-items:center;gap:8px;
  font-size:.85rem;font-weight:700;
}
.stat-pill-n{font-size:1.1rem;font-weight:800;color:var(--ac)}

/* فلتەر براند */
.filter-bar{
  background:var(--bg2);border-bottom:1px solid var(--border);
  padding:12px 24px;
  display:flex;align-items:center;gap:8px;
  overflow-x:auto;scrollbar-width:none;
}
.filter-bar::-webkit-scrollbar{display:none}
.filter-btn{
  padding:7px 18px;border-radius:50px;
  border:1px solid var(--border2);background:var(--bg3);
  color:var(--text2);font-family:inherit;font-size:.83rem;font-weight:700;
  cursor:pointer;transition:.2s;white-space:nowrap;flex-shrink:0;
}
.filter-btn:hover,.filter-btn.active{
  background:var(--ac);color:#fff;
  border-color:var(--ac);box-shadow:0 4px 14px rgba(124,111,255,.3);
}

/* ===== گرید بەرهەمەکان ===== */
.products-section{max-width:1280px;margin:0 auto;padding:36px 24px 60px}
.sec-header{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:24px;flex-wrap:wrap;gap:12px;
}
.sec-title{font-size:1.2rem;font-weight:800;display:flex;align-items:center;gap:8px}
.count-tag{
  background:var(--bg3);border:1px solid var(--border2);
  color:var(--text2);font-size:.78rem;font-weight:700;
  padding:4px 12px;border-radius:50px;
}

.grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
  gap:20px;
}

/* ===== کارتی بەرهەم ===== */
.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:20px;overflow:hidden;
  transition:.3s cubic-bezier(.34,1.56,.64,1);
  cursor:pointer;
}
.card:hover{
  transform:translateY(-6px);
  border-color:var(--border2);
  box-shadow:0 20px 48px rgba(0,0,0,.35),0 0 0 1px rgba(124,111,255,.15);
}

/* سلایدەر وێنەکان */
.card-imgs{position:relative;aspect-ratio:1;background:var(--bg3);overflow:hidden}
.card-img{width:100%;height:100%;object-fit:cover;display:block;transition:.4s}
.card-img.hidden{display:none}
.sl-btn{
  position:absolute;top:50%;transform:translateY(-50%);
  background:rgba(0,0,0,.55);backdrop-filter:blur(4px);
  border:none;color:#fff;width:30px;height:30px;border-radius:50%;
  font-size:1rem;cursor:pointer;z-index:5;display:flex;align-items:center;justify-content:center;
  opacity:0;transition:.2s;
}
.card-imgs:hover .sl-btn{opacity:1}
[dir=rtl] .sl-prev{left:8px}[dir=rtl] .sl-next{right:8px}
[dir=ltr] .sl-prev{left:8px}[dir=ltr] .sl-next{right:8px}
.sl-dots{position:absolute;bottom:8px;left:50%;transform:translateX(-50%);display:flex;gap:4px;z-index:5}
.sl-dot{width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,.4);cursor:pointer;transition:.2s}
.sl-dot.active{background:#fff;width:14px;border-radius:5px}

/* بیجەی ستاتوس */
.card-status-badge{
  position:absolute;top:10px;
  padding:4px 10px;border-radius:50px;
  font-size:.7rem;font-weight:800;z-index:5;
  display:flex;align-items:center;gap:4px;
}
[dir=rtl] .card-status-badge{right:10px}[dir=ltr] .card-status-badge{left:10px}
.badge-verified{background:rgba(0,212,170,.15);border:1px solid rgba(0,212,170,.3);color:var(--ac3)}
.badge-sold{background:rgba(255,107,157,.15);border:1px solid rgba(255,107,157,.3);color:var(--ac2)}

/* جەستەی کارت */
.card-body{padding:14px 16px 16px}
.card-brand{font-size:.72rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.card-name{font-size:1rem;font-weight:800;line-height:1.2;margin-bottom:8px}
.card-meta{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px}
.meta-pill{
  background:var(--bg3);border:1px solid var(--border);
  color:var(--text2);font-size:.72rem;font-weight:600;
  padding:3px 10px;border-radius:50px;
  display:flex;align-items:center;gap:4px;
}
.card-footer{display:flex;align-items:center;justify-content:space-between;gap:8px}
.card-price{font-size:1.2rem;font-weight:800;color:var(--ac3)}
.card-actions{display:flex;align-items:center;gap:6px}
.btn-cart{
  background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.7));
  color:#fff;border:none;padding:9px 16px;
  border-radius:10px;font-family:inherit;font-size:.82rem;
  font-weight:700;cursor:pointer;transition:.2s;
  display:flex;align-items:center;gap:5px;
}
.btn-cart:hover{opacity:.85;transform:scale(.97)}
.btn-chat{
  width:36px;height:36px;border-radius:10px;
  background:var(--bg3);border:1px solid var(--border2);
  display:flex;align-items:center;justify-content:center;
  text-decoration:none;color:var(--text2);transition:.2s;font-size:.9rem;
}
.btn-chat:hover{border-color:var(--ac);color:var(--ac)}
.btn-wish{
  width:36px;height:36px;border-radius:10px;
  background:var(--bg3);border:1px solid var(--border2);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:.2s;font-size:.9rem;color:var(--text2);
}
.btn-wish:hover,.btn-wish.on{border-color:var(--ac2);color:var(--ac2)}
.seller-row{
  display:flex;align-items:center;gap:5px;
  font-size:.72rem;color:var(--text3);margin-bottom:8px;
}

/* خوێندنی ئیمەجی مۆدال */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.92);backdrop-filter:blur(10px);
  z-index:1000;align-items:center;justify-content:center;
}
.modal-overlay.open{display:flex}
.modal-inner{position:relative;max-width:90vw;max-height:90vh}
.modal-img{max-width:90vw;max-height:85vh;object-fit:contain;border-radius:12px}
.modal-close{
  position:fixed;top:20px;right:20px;
  width:44px;height:44px;border-radius:50%;
  background:rgba(255,255,255,.1);border:none;color:#fff;
  font-size:1.2rem;cursor:pointer;transition:.2s;
  display:flex;align-items:center;justify-content:center;
}
.modal-close:hover{background:rgba(255,255,255,.2)}
.modal-nav{
  position:fixed;top:50%;transform:translateY(-50%);
  width:48px;height:48px;border-radius:50%;
  background:rgba(255,255,255,.1);border:none;color:#fff;
  font-size:1.4rem;cursor:pointer;transition:.2s;
  display:flex;align-items:center;justify-content:center;
}
.modal-prev{left:20px}.modal-next{right:20px}
.modal-nav:hover{background:rgba(124,111,255,.5)}
.modal-counter{
  position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
  background:rgba(0,0,0,.6);color:#fff;
  padding:5px 16px;border-radius:50px;font-size:.82rem;font-weight:700;
}

/* پووچیل */
.empty-state{
  grid-column:1/-1;text-align:center;
  padding:80px 20px;
}
.empty-icon{
  font-size:4rem;margin-bottom:16px;
  opacity:.3;display:block;
}
.empty-state h3{font-size:1.2rem;font-weight:700;color:var(--text2);margin-bottom:8px}
.empty-state p{color:var(--text3);font-size:.9rem}

/* تۆست */
.toast{
  position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(80px);
  background:var(--card);border:1px solid rgba(0,212,170,.4);
  color:var(--ac3);padding:12px 24px;border-radius:50px;
  font-weight:700;font-size:.88rem;z-index:999;opacity:0;
  transition:.4s cubic-bezier(.34,1.56,.64,1);
  display:flex;align-items:center;gap:8px;pointer-events:none;
  box-shadow:0 8px 32px rgba(0,0,0,.3);
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* فووتەر */
footer{
  background:var(--bg2);border-top:1px solid var(--border);
  padding:48px 24px 28px;
}
.footer-grid{
  max-width:1280px;margin:0 auto;
  display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:36px;margin-bottom:36px;
}
.footer-logo{font-size:1.3rem;font-weight:800;margin-bottom:10px;
  background:linear-gradient(135deg,var(--ac),var(--ac2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.footer-desc{font-size:.85rem;color:var(--text2);line-height:1.7;margin-bottom:16px}
.social-links{display:flex;gap:8px}
.social-link{
  width:36px;height:36px;border-radius:10px;
  background:var(--bg3);border:1px solid var(--border2);
  display:flex;align-items:center;justify-content:center;
  color:var(--text2);text-decoration:none;transition:.2s;
}
.social-link:hover{background:var(--ac);color:#fff;border-color:var(--ac)}
.footer-h{font-size:.75rem;font-weight:800;letter-spacing:1px;color:var(--text3);text-transform:uppercase;margin-bottom:12px}
.footer-links{list-style:none;display:flex;flex-direction:column;gap:8px}
.footer-links a{font-size:.85rem;color:var(--text2);text-decoration:none;display:flex;align-items:center;gap:7px;transition:.2s}
.footer-links a:hover{color:var(--ac)}
.footer-bottom{
  max-width:1280px;margin:0 auto;
  padding-top:24px;border-top:1px solid var(--border);
  text-align:center;color:var(--text3);font-size:.8rem;
}

/* ریسپانسیو */
@media(max-width:640px){
  nav{padding:0 14px;height:58px}
  .nav-mid{display:none}
  .grid{grid-template-columns:repeat(2,1fr);gap:12px}
  .hero{padding:44px 14px 52px}
  .products-section{padding:24px 14px 48px}
}
</style>
</head>
<body>

<!-- ===== ناڤیگەیشن ===== -->
<nav>
  <a href="mobile.php" class="logo">
    <span class="logo-dot"></span>📱 MobileShop
  </a>

  <div class="nav-mid">
    <div class="search-box">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="<?=$tr['search']?>" oninput="filterAll()">
    </div>
  </div>

  <div class="nav-actions">
    <!-- زمان -->
    <a href="?lang=<?=$lang=='ku'?'en':'ku'?>" class="nav-btn" style="padding:8px 12px">
      <?=$lang=='ku'?'EN':'کو'?>
    </a>

    <!-- تاریکی/ڕووناکی -->
    <a href="?dark=<?=$dark?'0':'1'?>" class="nav-icon-btn" title="Theme">
      <i class="fas fa-<?=$dark?'sun':'moon'?>"></i>
    </a>

    <!-- زیادکردنی بەرهەم -->
    <?php if(isLoggedIn()): ?>
    <a href="add_product.php" class="nav-btn">
      <i class="fas fa-plus"></i>
      <span class="hide-sm"><?=$tr['add_product']?></span>
    </a>
    <?php endif; ?>

    <!-- نۆتیفیکەیشن -->
    <?php if(isLoggedIn()): ?>
    <div class="notif-wrap">
      <div class="nav-icon-btn" onclick="toggleNotif()" id="notifBtn">
        <i class="fas fa-bell"></i>
        <?php if($notif_count>0): ?>
          <span class="badge badge-notif"><?=$notif_count?></span>
        <?php endif; ?>
      </div>
      <div class="notif-popup" id="notifPopup">
        <div class="notif-head">
          🔔 <?=$lang=='ku'?'ئاگادارکردنەوەکان':'Notifications'?>
          <a href="seller_dashboard.php?tab=notif&read_notif=1"><?=$lang=='ku'?'هەموو خوێندن':'Mark all read'?></a>
        </div>
        <?php if(empty($notif_items)): ?>
          <div class="notif-empty"><?=$lang=='ku'?'هیچ ئاگادارکردنەوەیەک نییە':'No notifications'?></div>
        <?php else: foreach($notif_items as $ni): ?>
          <div class="notif-item">
            <div class="notif-icon-wrap">
              <?php $icons=['chat'=>'💬','order'=>'🛒','payment'=>'💰','delivery'=>'🚚'];echo $icons[$ni['type']]??'🔔';?>
            </div>
            <div class="notif-body">
              <div class="notif-title"><?=htmlspecialchars($ni['title'])?></div>
              <div class="notif-msg"><?=htmlspecialchars($ni['message'])?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
        <div style="padding:10px 14px;text-align:center">
          <a href="seller_dashboard.php?tab=notif" style="font-size:.8rem;color:var(--ac);text-decoration:none;font-weight:700">
            <?=$lang=='ku'?'بینینی هەمووی':'View all'?>
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- سەبەتە -->
    <a href="cart.php" class="nav-icon-btn">
      <i class="fas fa-shopping-cart"></i>
      <?php if($cart_count>0): ?><span class="badge"><?=$cart_count?></span><?php endif; ?>
    </a>

    <!-- پرۆفایل -->
    <?php if(isLoggedIn()): ?>
      <a href="profile.php" class="nav-icon-btn">
        <i class="fas fa-user"></i>
      </a>
      <?php if(!empty($userData['is_admin'])): ?>
        <a href="admin.php" class="nav-btn" style="background:rgba(255,107,157,.12);border-color:rgba(255,107,157,.3);color:var(--ac2)">
          🛡 <?=$lang=='ku'?'ئەدمین':'Admin'?>
          <?php if($pending_count>0): ?><span class="badge" style="position:relative;top:0;right:0;background:var(--ac2)"><?=$pending_count?></span><?php endif; ?>
        </a>
      <?php endif; ?>
      <a href="logout.php" class="nav-icon-btn" style="color:var(--ac2)" title="<?=$lang=='ku'?'چوونەدەرەوە':'Logout'?>">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    <?php else: ?>
      <a href="index.php" class="nav-btn" style="background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.7));color:#fff;border-color:transparent">
        <i class="fas fa-sign-in-alt"></i> <?=$lang=='ku'?'داخلبوون':'Login'?>
      </a>
    <?php endif; ?>
  </div>
</nav>

<!-- ===== هێرۆ ===== -->
<section class="hero">
  <h1 class="hero-title">
    <?=$lang=='ku'?'کڕین و فرۆشتنی':'Buy & Sell'?><br>
    <span><?=$lang=='ku'?'مۆبایل بە ئاسانی':'Mobile Phones Easily'?></span>
  </h1>
  <p class="hero-sub">
    <?=$lang=='ku'?'باشترین شوێن بۆ دۆزینەوەی مۆبایلی خۆت لە سلێمانی. بە نرخی باش و فرۆشیارێکی متمانەپێکراو.':'The best place to find your mobile in Sulaymaniyah. Great prices, trusted sellers.'?>
  </p>
  <div class="hero-stats">
    <div class="stat-pill">
      <span class="stat-pill-n"><?=$total_products?></span>
      <?=$lang=='ku'?'بەرهەم':'Products'?>
    </div>
    <div class="stat-pill">
      <span class="stat-pill-n"><?=$brands_count?></span>
      <?=$lang=='ku'?'براند':'Brands'?>
    </div>
    <div class="stat-pill">
      <span class="stat-pill-n"><?=$sellers_count?>+</span>
      <?=$lang=='ku'?'فرۆشیار':'Sellers'?>
    </div>
  </div>
</section>

<!-- فلتەر براندەکان -->
<div class="filter-bar" id="filterBar">
  <button class="filter-btn active" onclick="setFilter('all',this)"><?=$tr['all']?></button>
  <?php
  $brands_r=$conn->query("SELECT DISTINCT brand FROM products WHERE status='approved' ORDER BY brand");
  if($brands_r) while($br=$brands_r->fetch_assoc()):
  ?>
    <button class="filter-btn" onclick="setFilter('<?=htmlspecialchars($br['brand'])?>',this)">
      <?=htmlspecialchars($br['brand'])?>
    </button>
  <?php endwhile; ?>
</div>

<!-- ===== بەرهەمەکان ===== -->
<section class="products-section">
  <div class="sec-header">
    <h2 class="sec-title">
      📦 <?=$lang=='ku'?'بەرهەمەکان':'Products'?>
    </h2>
    <span class="count-tag"><?=$total_products?> <?=$lang=='ku'?'بەرهەم':'items'?></span>
  </div>

  <div class="grid" id="productGrid">
    <?php
    if($products && $products->num_rows):
      while($row=$products->fetch_assoc()):
        $is_owner=(isLoggedIn()&&$_SESSION['user_id']==$row['added_by']);
        $allImgs=$productImages[$row['id']]??[];
        if(empty($allImgs)&&!empty($row['image'])) $allImgs=[$row['image']];
        if(empty($allImgs)) $allImgs=[''];
        $hasMultiple=count($allImgs)>1;
        $is_sold=(!empty($row['order_status'])&&$row['order_status']=='sold');
    ?>
    <div class="card" data-cat="<?=htmlspecialchars($row['brand'])?>" data-name="<?=strtolower(htmlspecialchars($row['brand'].' '.$row['name']))?>">
      <!-- سلایدەری وێنەکان -->
      <div class="card-imgs" data-imgs='<?=json_encode(array_map(fn($img)=>(!empty($img)&&file_exists("uploads/$img"))?"uploads/$img":"https://via.placeholder.com/400/141520/7c6fff?text=📱",$allImgs))?>'>
        <?php foreach($allImgs as $idx=>$img):
          $src=(!empty($img)&&file_exists("uploads/$img"))?"uploads/$img":"https://via.placeholder.com/400/141520/7c6fff?text=📱";
        ?>
          <img src="<?=$src?>" class="card-img<?=$idx>0?' hidden':''?>" alt="<?=htmlspecialchars($row['name'])?>">
        <?php endforeach; ?>
        <?php if($hasMultiple): ?>
          <button class="sl-btn sl-prev">&#8249;</button>
          <button class="sl-btn sl-next">&#8250;</button>
          <div class="sl-dots"><?php for($d=0;$d<count($allImgs);$d++): ?><span class="sl-dot<?=$d==0?' active':''?>"></span><?php endfor; ?></div>
        <?php endif; ?>
        <!-- بیجەکان -->
        <?php if(!empty($row['is_verified'])&&$row['is_verified']==1): ?>
          <span class="card-status-badge badge-verified"><i class="fas fa-check-circle"></i> <?=$lang=='ku'?'ڤێریفاید':'Verified'?></span>
        <?php endif; ?>
        <?php if($is_sold): ?>
          <span class="card-status-badge badge-sold" style="top:auto;bottom:10px"><?=$lang=='ku'?'فرۆشراوە':'Sold'?></span>
        <?php endif; ?>
      </div>
      <!-- جەستە -->
      <div class="card-body">
        <div class="card-brand"><?=htmlspecialchars($row['brand'])?></div>
        <div class="card-name"><?=htmlspecialchars($row['name'])?></div>
        <?php if(!empty($row['added_by_name'])): ?>
        <div class="seller-row">
          <i class="fas fa-user" style="font-size:.65rem"></i>
          <?=htmlspecialchars($row['added_by_name'])?>
        </div>
        <?php endif; ?>
        <div class="card-meta">
          <span class="meta-pill"><i class="fas fa-hdd" style="font-size:.6rem"></i><?=htmlspecialchars($row['storage'])?></span>
          <span class="meta-pill">🎨 <?=htmlspecialchars($row['color'])?></span>
        </div>
        <div class="card-footer">
          <span class="card-price">$<?=number_format($row['price'],2)?></span>
          <div class="card-actions">
            <?php if(!$is_owner&&!$is_sold): ?>
              <form method="POST">
                <input type="hidden" name="id" value="<?=(int)$row['id']?>">
                <button type="submit" name="add_to_cart" class="btn-cart">
                  <i class="fas fa-cart-plus"></i><?=$tr['add_to_cart']?>
                </button>
              </form>
              <?php if(isLoggedIn()): ?>
                <a href="chat.php?product=<?=$row['id']?>" class="btn-chat" title="<?=$tr['chat']?>">💬</a>
              <?php endif; ?>
            <?php elseif($is_owner): ?>
              <a href="seller_dashboard.php?tab=products" class="btn-cart" style="text-decoration:none;font-size:.8rem">
                📊 <?=$tr['dashboard']?>
              </a>
            <?php else: ?>
              <span style="font-size:.8rem;color:var(--ac2);font-weight:700">✓ <?=$lang=='ku'?'فرۆشراوە':'Sold'?></span>
            <?php endif; ?>
            <button class="btn-wish" onclick="toggleWish(this)"><i class="far fa-heart"></i></button>
          </div>
        </div>
      </div>
    </div>
    <?php endwhile;
    else: ?>
      <div class="empty-state">
        <span class="empty-icon">📦</span>
        <h3><?=$tr['no_products']?></h3>
        <p><?=$lang=='ku'?'هیچ بەرهەمێک بەردەست نییە':'No products available at the moment'?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- مۆدالی وێنە -->
<div class="modal-overlay" id="imgModal" onclick="closeModal(event)">
  <div class="modal-inner">
    <img class="modal-img" id="modalImg" src="" alt="">
  </div>
  <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
  <button class="modal-nav modal-prev" onclick="modalNav(-1,event)">&#8249;</button>
  <button class="modal-nav modal-next" onclick="modalNav(1,event)">&#8250;</button>
  <div class="modal-counter" id="modalCounter"></div>
</div>

<!-- فووتەر -->
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-logo">📱 MobileShop</div>
      <p class="footer-desc"><?=$lang=='ku'?'باشترین شوێن بۆ کڕینی مۆبایل لە سلێمانی.':'Best place to buy mobiles in Sulaymaniyah.'?></p>
      <div class="social-links">
        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
        <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
        <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
      </div>
    </div>
    <div>
      <div class="footer-h"><?=$lang=='ku'?'بەستەرەکان':'Links'?></div>
      <ul class="footer-links">
        <li><a href="mobile.php"><i class="fas fa-home"></i> <?=$tr['home']?></a></li>
        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> <?=$tr['cart']?></a></li>
        <li><a href="add_product.php"><i class="fas fa-plus"></i> <?=$tr['add_product']?></a></li>
        <li><a href="seller_dashboard.php"><i class="fas fa-chart-bar"></i> <?=$lang=='ku'?'داشبۆرد':'Dashboard'?></a></li>
      </ul>
    </div>
    <div>
      <div class="footer-h"><?=$lang=='ku'?'خزمەتگوزاری':'Services'?></div>
      <ul class="footer-links">
        <li><a href="#"><i class="fas fa-shield-alt"></i> <?=$lang=='ku'?'واڕانتی':'Warranty'?></a></li>
        <li><a href="#"><i class="fas fa-truck"></i> <?=$lang=='ku'?'گەیاندن':'Delivery'?></a></li>
        <li><a href="#"><i class="fas fa-tools"></i> <?=$lang=='ku'?'چاکردنەوە':'Repair'?></a></li>
        <li><a href="#"><i class="fas fa-exchange-alt"></i> <?=$lang=='ku'?'گۆڕینەوە':'Exchange'?></a></li>
      </ul>
    </div>
    <div>
      <div class="footer-h"><?=$lang=='ku'?'پەیوەندی':'Contact'?></div>
      <ul class="footer-links">
        <li><a href="tel:+9647501234567"><i class="fas fa-phone"></i> +964 750 123 4567</a></li>
        <li><a href="mailto:info@mobileshop.iq"><i class="fas fa-envelope"></i> info@mobileshop.iq</a></li>
        <li><a href="#"><i class="fas fa-map-marker-alt"></i> <?=$lang=='ku'?'سلێمانی، عێراق':'Sulaymaniyah, Iraq'?></a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">© 2024 MobileShop · <?=$lang=='ku'?'هەموو مافەکان پارێزراون':'All Rights Reserved'?></div>
</footer>

<div class="toast" id="toast">✓ <?=$lang=='ku'?'زیادکرا بۆ سەبەتە':'Added to Cart'?></div>

<script>
let activeFilter='all';
function setFilter(cat,btn){
  activeFilter=cat;
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  if(btn)btn.classList.add('active');
  filterAll();
}
function filterAll(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#productGrid .card').forEach(c=>{
    const matchCat=activeFilter==='all'||c.dataset.cat===activeFilter;
    const matchQ=!q||c.dataset.name.includes(q);
    c.style.display=matchCat&&matchQ?'':'none';
  });
}
function toggleNotif(){
  const p=document.getElementById('notifPopup');
  p&&p.classList.toggle('open');
}
document.addEventListener('click',e=>{
  const nb=document.getElementById('notifBtn');
  if(nb&&!nb.contains(e.target)){
    const p=document.getElementById('notifPopup');
    if(p)p.classList.remove('open');
  }
});
function toggleWish(b){
  b.classList.toggle('on');
  const i=b.querySelector('i');
  i.classList.toggle('far');i.classList.toggle('fas');
}

// سلایدەر
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.card-imgs').forEach(wrap=>{
    const imgs=wrap.querySelectorAll('.card-img');
    const dots=wrap.querySelectorAll('.sl-dot');
    let cur=0;
    const go=n=>{
      imgs[cur].classList.add('hidden');dots[cur]&&dots[cur].classList.remove('active');
      cur=(n+imgs.length)%imgs.length;
      imgs[cur].classList.remove('hidden');dots[cur]&&dots[cur].classList.add('active');
    };
    wrap.querySelector('.sl-prev')&&wrap.querySelector('.sl-prev').addEventListener('click',e=>{e.stopPropagation();go(cur-1)});
    wrap.querySelector('.sl-next')&&wrap.querySelector('.sl-next').addEventListener('click',e=>{e.stopPropagation();go(cur+1)});
    dots.forEach((d,i)=>d.addEventListener('click',e=>{e.stopPropagation();go(i)}));
    imgs.forEach((img,i)=>img.addEventListener('click',()=>{
      openModal(JSON.parse(wrap.dataset.imgs),i);
    }));
  });
});

// مۆدالی وێنە
let mImgs=[],mIdx=0;
const modal=document.getElementById('imgModal');
const modalImg=document.getElementById('modalImg');
const modalCnt=document.getElementById('modalCounter');
function openModal(imgs,idx){
  mImgs=imgs;mIdx=idx;
  modal.classList.add('open');
  modalImg.src=mImgs[mIdx];
  modalCnt.textContent=mImgs.length>1?(mIdx+1)+'/'+mImgs.length:'';
}
function closeModal(e){
  if(!e||e.target===modal)modal.classList.remove('open');
}
function modalNav(d,e){
  if(e)e.stopPropagation();
  mIdx=(mIdx+d+mImgs.length)%mImgs.length;
  modalImg.src=mImgs[mIdx];
  modalCnt.textContent=mImgs.length>1?(mIdx+1)+'/'+mImgs.length:'';
}
document.addEventListener('keydown',e=>{
  if(!modal.classList.contains('open'))return;
  if(e.key==='Escape')modal.classList.remove('open');
  if(e.key==='ArrowLeft')modalNav(-1,null);
  if(e.key==='ArrowRight')modalNav(1,null);
});
</script>
</body>
</html>
