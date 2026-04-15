<?php
include 'connect.php';
if (!isLoggedIn()) { header("Location: index.php"); exit(); }
$uid = (int)$_SESSION['user_id'];
$is_admin = isAdmin();

if (isset($_GET['confirm_payment']) && $is_admin) {
    $oid=(int)$_GET['confirm_payment'];
    $ord=$conn->query("SELECT * FROM sale_orders WHERE id=$oid")->fetch_assoc();
    if($ord){
        $conn->query("UPDATE sale_orders SET payment_status='paid', delivery_status='delivered', delivered_at=NOW() WHERE id=$oid");
        $conn->query("UPDATE products SET order_status='sold' WHERE id={$ord['product_id']}");
        $prod=$conn->query("SELECT brand, name FROM products WHERE id={$ord['product_id']}")->fetch_assoc();
        $pname=$conn->real_escape_string($prod['brand'].' '.$prod['name']);
        $conn->query("INSERT INTO notifications (user_id, type, title, message, product_id) VALUES ({$ord['seller_id']}, 'payment', '💰 پارەت گەیشتە!', 'مۆبایلی $pname فرۆشرا و \${$ord['price']} پارەت لەحساب کرا', {$ord['product_id']})");
    }
    header("Location: seller_dashboard.php?tab=sold"); exit();
}
if (isset($_GET['mark_shipped']) && $is_admin) {
    $oid=(int)$_GET['mark_shipped'];
    $ord=$conn->query("SELECT * FROM sale_orders WHERE id=$oid")->fetch_assoc();
    if($ord){
        $conn->query("UPDATE sale_orders SET delivery_status='shipped' WHERE id=$oid");
        $prod=$conn->query("SELECT brand, name FROM products WHERE id={$ord['product_id']}")->fetch_assoc();
        $pname=$conn->real_escape_string($prod['brand'].' '.$prod['name']);
        $conn->query("INSERT INTO notifications (user_id, type, title, message, product_id) VALUES ({$ord['buyer_id']}, 'delivery', '🚚 مۆبایلەکەت نێردرا!', 'مۆبایلی $pname نێردرا بۆ ئادرەسەکەت.', {$ord['product_id']})");
    }
    header("Location: seller_dashboard.php?tab=orders"); exit();
}
if (isset($_GET['read_notif'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    header("Location: seller_dashboard.php?tab=notif"); exit();
}

$tab=$_GET['tab']??'products';
$my_products=$conn->query("SELECT p.*, (SELECT COUNT(*) FROM chats WHERE product_id=p.id AND receiver_id=$uid AND is_read=0) as unread_chats FROM products p WHERE p.added_by=$uid ORDER BY p.id DESC");
$my_orders=$conn->query("SELECT o.*, p.brand, p.name, p.image, p.storage, p.color, CONCAT(u.FirstName,' ',u.LastName) as buyer_name, u.Email as buyer_email FROM sale_orders o JOIN products p ON p.id=o.product_id JOIN users u ON u.id=o.buyer_id WHERE o.seller_id=$uid ORDER BY o.ordered_at DESC");
$sold_products=$conn->query("SELECT o.*, p.brand, p.name, p.image, p.price, CONCAT(u.FirstName,' ',u.LastName) as buyer_name FROM sale_orders o JOIN products p ON p.id=o.product_id JOIN users u ON u.id=o.buyer_id WHERE o.seller_id=$uid AND o.payment_status='paid' ORDER BY o.delivered_at DESC");
$earnings=$conn->query("SELECT SUM(price) as total FROM sale_orders WHERE seller_id=$uid AND payment_status='paid'")->fetch_assoc();
$pending_e=$conn->query("SELECT SUM(price) as total FROM sale_orders WHERE seller_id=$uid AND payment_status='pending'")->fetch_assoc();
$all_orders=null;
if ($is_admin) {
    $all_orders=$conn->query("SELECT o.*, p.brand, p.name, p.image, p.price, CONCAT(s.FirstName,' ',s.LastName) as seller_name, CONCAT(b.FirstName,' ',b.LastName) as buyer_name, b.Email as buyer_email FROM sale_orders o JOIN products p ON p.id=o.product_id JOIN users s ON s.id=o.seller_id JOIN users b ON b.id=o.buyer_id ORDER BY o.ordered_at DESC");
}
$notifications=$conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 30");
$unread_notif=$conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];
$total_earned=(float)($earnings['total']??0);
$pending_earned=(float)($pending_e['total']??0);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>داشبۆرد — MobileShop</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Noto Sans Arabic',sans-serif}
:root{--bg:#070912;--s:#0e1018;--s2:#141520;--bd:rgba(255,255,255,.08);--ac:#7c6fff;--ac2:#ff6b9d;--green:#00d4aa;--orange:#ffd93d;--cyan:#00d4ff;--text:#f0f0ff;--muted:rgba(255,255,255,.4)}
body{background:var(--bg);color:var(--text);min-height:100vh}
nav{background:rgba(7,9,18,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--bd);padding:0 24px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
.logo{font-weight:800;background:linear-gradient(135deg,var(--ac),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:1rem}
.nav-r{display:flex;gap:6px;align-items:center}
.nb{background:var(--s2);border:1px solid var(--bd);color:var(--muted);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:700;transition:.2s}
.nb:hover{border-color:var(--ac);color:var(--ac)}
.nb.danger{color:var(--ac2)}.nb.danger:hover{background:var(--ac2);color:#fff;border-color:var(--ac2)}
.wrap{max-width:1060px;margin:0 auto;padding:28px 20px}
/* آمارەکان */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px}
@media(max-width:700px){.stats{grid-template-columns:repeat(2,1fr)}}
.stat{background:var(--s);border:1px solid var(--bd);border-radius:16px;padding:18px;transition:.2s}
.stat:hover{border-color:rgba(124,111,255,.3)}
.stat-label{font-size:.72rem;color:var(--muted);font-weight:700;letter-spacing:.5px;text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.stat-n{font-size:1.6rem;font-weight:800;line-height:1}
/* تابەکان */
.tabs{display:flex;gap:6px;margin-bottom:22px;flex-wrap:wrap}
.tab{padding:9px 18px;border-radius:50px;border:1px solid var(--bd);background:var(--s);color:var(--muted);font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer;transition:.2s;text-decoration:none;display:flex;align-items:center;gap:6px}
.tab:hover,.tab.active{background:var(--ac);color:#fff;border-color:var(--ac);box-shadow:0 4px 14px rgba(124,111,255,.3)}
.tb{background:rgba(255,255,255,.2);font-size:.7rem;padding:1px 7px;border-radius:20px}
/* بەرهەمەکان */
.prow{background:var(--s);border:1px solid var(--bd);border-radius:14px;padding:14px;margin-bottom:10px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;transition:.2s}
.prow:hover{border-color:rgba(124,111,255,.25)}
.prow-img{width:60px;height:60px;border-radius:10px;object-fit:cover;background:var(--s2);flex-shrink:0}
.prow-name{font-size:.9rem;font-weight:800}
.prow-meta{font-size:.75rem;color:var(--muted);margin-top:2px}
.status-badge{padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:800}
.s-approved{background:rgba(0,212,170,.12);color:var(--green);border:1px solid rgba(0,212,170,.25)}
.s-pending{background:rgba(255,217,61,.1);color:var(--orange);border:1px solid rgba(255,217,61,.25)}
.s-rejected{background:rgba(255,107,157,.1);color:var(--ac2);border:1px solid rgba(255,107,157,.25)}
.s-sold{background:rgba(0,212,255,.1);color:var(--cyan);border:1px solid rgba(0,212,255,.25)}
.price-tag{font-size:.95rem;font-weight:800;color:var(--green);margin-right:auto}
.chat-btn{background:rgba(124,111,255,.1);border:1px solid rgba(124,111,255,.25);color:var(--ac);padding:6px 12px;border-radius:50px;text-decoration:none;font-size:.78rem;font-weight:700;transition:.2s}
.chat-btn:hover{background:var(--ac);color:#fff}
/* داواکاریەکان */
.orow{background:var(--s);border:1px solid var(--bd);border-radius:14px;padding:16px;margin-bottom:10px;transition:.2s}
.orow:hover{border-color:rgba(124,111,255,.2)}
.orow-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px}
.orow-img{width:52px;height:52px;border-radius:10px;object-fit:cover;background:var(--s2);flex-shrink:0}
.orow-name{font-size:.9rem;font-weight:800}
.orow-meta{font-size:.75rem;color:var(--muted);margin-top:2px}
.orow-price{font-size:1.05rem;font-weight:800;color:var(--green);margin-right:auto}
.orow-info{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.orow-badge{background:var(--s2);border:1px solid var(--bd);font-size:.73rem;padding:4px 10px;border-radius:50px;color:var(--muted);display:flex;align-items:center;gap:4px}
.btn-ship{padding:7px 14px;background:rgba(0,212,170,.1);border:1px solid rgba(0,212,170,.25);color:var(--green);border-radius:50px;font-family:inherit;font-size:.78rem;font-weight:700;text-decoration:none;transition:.2s}
.btn-ship:hover{background:var(--green);color:#000}
.btn-pay{padding:7px 14px;background:rgba(255,217,61,.1);border:1px solid rgba(255,217,61,.25);color:var(--orange);border-radius:50px;font-family:inherit;font-size:.78rem;font-weight:700;text-decoration:none;transition:.2s}
.btn-pay:hover{background:var(--orange);color:#000}
/* نۆتیفیکەیشن */
.nrow{background:var(--s);border:1px solid var(--bd);border-radius:13px;padding:13px;margin-bottom:8px;display:flex;align-items:flex-start;gap:12px;transition:.2s}
.nrow:hover{border-color:rgba(124,111,255,.25)}
.nrow.unread{border-color:rgba(124,111,255,.3);background:var(--s2)}
.n-icon{width:38px;height:38px;border-radius:10px;background:var(--s2);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.n-title{font-size:.88rem;font-weight:800}
.n-msg{font-size:.78rem;color:var(--muted);margin-top:3px;line-height:1.5}
.n-time{font-size:.7rem;color:var(--muted);margin-top:4px}
.notif-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.mark-read{background:rgba(124,111,255,.1);border:1px solid rgba(124,111,255,.2);color:var(--ac);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:700;transition:.2s}
.mark-read:hover{background:var(--ac);color:#fff}
.empty{text-align:center;padding:48px 20px;color:var(--muted);font-size:.9rem}
</style>
</head>
<body>
<nav>
  <div class="logo">📊 داشبۆردی فرۆشیار</div>
  <div class="nav-r">
    <a href="mobile.php" class="nb"><i class="fas fa-store"></i> فرۆشگا</a>
    <a href="profile.php" class="nb"><i class="fas fa-user"></i> پرۆفایل</a>
    <?php if($is_admin):?><a href="admin.php" class="nb" style="color:var(--ac2)">🛡 ئەدمین</a><?php endif;?>
    <a href="logout.php" class="nb danger"><i class="fas fa-sign-out-alt"></i> چوونەدەرەوە</a>
  </div>
</nav>
<div class="wrap">
  <!-- آمارەکان -->
  <div class="stats">
    <div class="stat">
      <div class="stat-label">💰 کاسبی تەواو</div>
      <div class="stat-n" style="color:var(--green)">$<?=number_format($total_earned,0)?></div>
    </div>
    <div class="stat">
      <div class="stat-label">⏳ چاوەڕوان</div>
      <div class="stat-n" style="color:var(--orange)">$<?=number_format($pending_earned,0)?></div>
    </div>
    <div class="stat">
      <div class="stat-label">📦 بەرهەمەکانم</div>
      <div class="stat-n" style="color:var(--ac)"><?=$my_products->num_rows?></div>
    </div>
    <div class="stat">
      <div class="stat-label">🔔 نۆتیفیکەیشن</div>
      <div class="stat-n" style="color:var(--ac2)"><?=$unread_notif?></div>
    </div>
  </div>
  <!-- تابەکان -->
  <div class="tabs">
    <a href="?tab=products" class="tab <?=$tab=='products'?'active':''?>">📦 بەرهەمەکانم</a>
    <a href="?tab=orders" class="tab <?=$tab=='orders'?'active':''?>">🛒 داواکاریەکان</a>
    <a href="?tab=sold" class="tab <?=$tab=='sold'?'active':''?>">✅ فرۆشراوەکان</a>
    <a href="?tab=notif" class="tab <?=$tab=='notif'?'active':''?>">🔔 ئاگادارکردنەوەکان <?php if($unread_notif>0):?><span class="tb"><?=$unread_notif?></span><?php endif;?></a>
    <?php if($is_admin):?><a href="?tab=admin_orders" class="tab <?=$tab=='admin_orders'?'active':''?>">🛡 ئەدمین داواکاریەکان</a><?php endif;?>
  </div>

  <?php if($tab=='products'): ?>
    <?php if(!$my_products->num_rows):?>
      <div class="empty">📦 هیچ بەرهەمێک زیادت نەکردووە. <a href="add_product.php" style="color:var(--ac);text-decoration:none">زیاد بکە ➕</a></div>
    <?php else: while($p=$my_products->fetch_assoc()):
      $pimg=(!empty($p['image'])&&file_exists('uploads/'.$p['image']))?'uploads/'.$p['image']:'https://via.placeholder.com/60/141520/7c6fff?text=📱';
      $stag=$p['status']=='approved'?'<span class="status-badge s-approved">✅ ئەکسێپت</span>':($p['status']=='rejected'?'<span class="status-badge s-rejected">❌ ڕەدکرا</span>':'<span class="status-badge s-pending">⏳ چاوەڕوان</span>');
    ?>
      <div class="prow">
        <img src="<?=$pimg?>" class="prow-img">
        <div style="flex:1;min-width:120px">
          <div class="prow-name"><?=htmlspecialchars($p['brand'].' '.$p['name'])?></div>
          <div class="prow-meta"><?=htmlspecialchars($p['storage'])?> · <?=htmlspecialchars($p['color'])?></div>
        </div>
        <?=$stag?>
        <?php if(!empty($p['order_status'])&&$p['order_status']=='sold'):?><span class="status-badge s-sold">💙 فرۆشراوە</span><?php endif;?>
        <span class="price-tag">$<?=number_format($p['price'],2)?></span>
        <?php if($p['status']=='approved'):?>
          <a href="chat.php?product=<?=$p['id']?>" class="chat-btn">
            💬 چات <?php if($p['unread_chats']>0):?><span style="background:var(--ac2);color:#fff;font-size:.68rem;padding:1px 6px;border-radius:20px"><?=$p['unread_chats']?></span><?php endif;?>
          </a>
        <?php endif;?>
      </div>
    <?php endwhile; endif;?>

  <?php elseif($tab=='orders'): ?>
    <?php if(!$my_orders||!$my_orders->num_rows):?>
      <div class="empty">🛒 هیچ داواکارییەک نییە</div>
    <?php else: while($o=$my_orders->fetch_assoc()):
      $pimg=(!empty($o['image'])&&file_exists('uploads/'.$o['image']))?'uploads/'.$o['image']:'https://via.placeholder.com/52/141520/7c6fff?text=📱';
    ?>
      <div class="orow">
        <div class="orow-head">
          <img src="<?=$pimg?>" class="orow-img">
          <div style="flex:1;min-width:120px">
            <div class="orow-name"><?=htmlspecialchars($o['brand'].' '.$o['name'])?></div>
            <div class="orow-meta"><?=htmlspecialchars($o['storage'])?> · <?=htmlspecialchars($o['color'])?></div>
          </div>
          <div class="orow-price">$<?=number_format($o['price'],2)?></div>
        </div>
        <div class="orow-info">
          <span class="orow-badge"><i class="fas fa-user" style="font-size:.6rem"></i><?=htmlspecialchars($o['buyer_name'])?></span>
          <span class="orow-badge"><i class="fas fa-envelope" style="font-size:.6rem"></i><?=htmlspecialchars($o['buyer_email'])?></span>
          <span class="orow-badge"><?=$o['payment_method']=='fib'?'💳 FIB':'📍 لەشوێن'?></span>
          <?php if(!empty($o['address'])):?><span class="orow-badge"><i class="fas fa-map-marker-alt" style="font-size:.6rem"></i><?=htmlspecialchars(mb_strimwidth($o['address'],0,30,'...'))?></span><?php endif;?>
          <?php
          $ds=$o['delivery_status'];$ps=$o['payment_status'];
          if($ps=='paid') echo '<span class="status-badge s-approved">✅ پارەدراوە</span>';
          elseif($ds=='shipped') echo '<span class="status-badge s-pending">🚚 نێردراوە</span>';
          else echo '<span class="status-badge s-pending">⏳ چاوەڕوان</span>';
          ?>
        </div>
      </div>
    <?php endwhile; endif;?>

  <?php elseif($tab=='sold'): ?>
    <?php if(!$sold_products||!$sold_products->num_rows):?>
      <div class="empty">✅ هیچ فرۆشی تەواونەبووە</div>
    <?php else: $total_s=0; while($o=$sold_products->fetch_assoc()): $total_s+=$o['price'];
      $pimg=(!empty($o['image'])&&file_exists('uploads/'.$o['image']))?'uploads/'.$o['image']:'https://via.placeholder.com/52/141520/7c6fff?text=📱';
    ?>
      <div class="orow">
        <div class="orow-head">
          <img src="<?=$pimg?>" class="orow-img">
          <div style="flex:1;min-width:120px">
            <div class="orow-name"><?=htmlspecialchars($o['brand'].' '.$o['name'])?></div>
            <div class="orow-meta">کریار: <?=htmlspecialchars($o['buyer_name'])?></div>
          </div>
          <div class="orow-price">$<?=number_format($o['price'],2)?></div>
          <span class="status-badge s-approved">✅ تەواوبوو</span>
        </div>
      </div>
    <?php endwhile;?>
      <div style="background:var(--s);border:1px solid rgba(0,212,170,.25);border-radius:14px;padding:16px;display:flex;justify-content:space-between;align-items:center;margin-top:8px">
        <span style="font-weight:800">کۆی گشتی کاسبی</span>
        <span style="font-size:1.3rem;font-weight:800;color:var(--green)">$<?=number_format($total_s,2)?></span>
      </div>
    <?php endif;?>

  <?php elseif($tab=='notif'): ?>
    <div class="notif-bar">
      <span style="font-size:.9rem;font-weight:800">🔔 ئاگادارکردنەوەکان</span>
      <?php if($unread_notif>0):?>
        <a href="?tab=notif&read_notif=1" class="mark-read">✓ هەموو خوێندنەوە</a>
      <?php endif;?>
    </div>
    <?php if(!$notifications||!$notifications->num_rows):?>
      <div class="empty">🔔 هیچ ئاگادارکردنەوەیەک نییە</div>
    <?php else: $icons=['chat'=>'💬','order'=>'🛒','payment'=>'💰','delivery'=>'🚚']; while($n=$notifications->fetch_assoc()):?>
      <div class="nrow <?=$n['is_read']?'':'unread'?>">
        <div class="n-icon"><?=$icons[$n['type']]??'🔔'?></div>
        <div style="flex:1">
          <div class="n-title"><?=htmlspecialchars($n['title'])?></div>
          <div class="n-msg"><?=htmlspecialchars($n['message'])?></div>
          <div class="n-time"><?=date('Y/m/d H:i',strtotime($n['created_at']))?></div>
        </div>
      </div>
    <?php endwhile; endif;?>

  <?php elseif($tab=='admin_orders'&&$is_admin): ?>
    <?php if(!$all_orders||!$all_orders->num_rows):?>
      <div class="empty">🛒 هیچ داواکارییەک نییە</div>
    <?php else: while($o=$all_orders->fetch_assoc()):
      $pimg=(!empty($o['image'])&&file_exists('uploads/'.$o['image']))?'uploads/'.$o['image']:'https://via.placeholder.com/52/141520/7c6fff?text=📱';
    ?>
      <div class="orow">
        <div class="orow-head">
          <img src="<?=$pimg?>" class="orow-img">
          <div style="flex:1;min-width:120px">
            <div class="orow-name"><?=htmlspecialchars($o['brand'].' '.$o['name'])?></div>
            <div class="orow-meta">فرۆشیار: <?=htmlspecialchars($o['seller_name'])?> · کریار: <?=htmlspecialchars($o['buyer_name'])?></div>
          </div>
          <div class="orow-price">$<?=number_format($o['price'],2)?></div>
        </div>
        <div class="orow-info">
          <span class="orow-badge"><?=$o['payment_method']=='fib'?'💳 FIB':'📍 لەشوێن'?></span>
          <?php if(!empty($o['address'])):?><span class="orow-badge"><i class="fas fa-map-marker-alt" style="font-size:.6rem"></i><?=htmlspecialchars(mb_strimwidth($o['address'],0,40,'...'))?></span><?php endif;?>
          <?php
          if($o['payment_status']=='paid') echo '<span class="status-badge s-approved">✅ پارەدراوە</span>';
          elseif($o['delivery_status']=='shipped') echo '<span class="status-badge s-pending">🚚 نێردراوە</span>';
          else echo '<span class="status-badge s-pending">⏳ چاوەڕوان</span>';
          ?>
          <?php if($o['delivery_status']=='pending'):?>
            <a href="?mark_shipped=<?=$o['id']?>" class="btn-ship"><i class="fas fa-truck"></i> نێردراو دادەنێ</a>
          <?php endif;?>
          <?php if($o['payment_status']=='pending'):?>
            <a href="?confirm_payment=<?=$o['id']?>" class="btn-pay"><i class="fas fa-check"></i> پارەدان پشتڕاست بکە</a>
          <?php endif;?>
        </div>
      </div>
    <?php endwhile; endif;?>
  <?php endif;?>
</div>
</body>
</html>
