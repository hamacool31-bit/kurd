<?php
include 'connect.php';
if (!isLoggedIn() || !isAdmin()) { header("Location: mobile.php"); exit(); }
$uid = (int)$_SESSION['user_id'];

if (isset($_GET['approve'])) { $pid=(int)$_GET['approve']; $conn->query("UPDATE products SET status='approved' WHERE id=$pid"); header("Location: admin.php?tab=pending"); exit(); }
if (isset($_GET['reject']))  { $pid=(int)$_GET['reject'];  $conn->query("UPDATE products SET status='rejected' WHERE id=$pid"); header("Location: admin.php?tab=pending"); exit(); }
if (isset($_GET['delete'])) {
    $pid=(int)$_GET['delete'];
    $conn->query("DELETE FROM product_images WHERE product_id=$pid");
    $conn->query("DELETE FROM products WHERE id=$pid");
    header("Location: admin.php"); exit();
}
if (isset($_GET['del_user'])) {
    $duid=(int)$_GET['del_user'];
    if ($duid!=$uid) {
        $conn->query("DELETE FROM product_images WHERE product_id IN (SELECT id FROM products WHERE added_by=$duid)");
        $conn->query("DELETE FROM products WHERE added_by=$duid");
        $conn->query("DELETE FROM users WHERE id=$duid AND is_admin=0");
    }
    header("Location: admin.php?tab=users"); exit();
}
if (isset($_GET['verify']))   { $v=(int)$_GET['verify'];   $conn->query("UPDATE users SET is_verified=1 WHERE id=$v AND is_admin=0"); header("Location: admin.php?tab=users"); exit(); }
if (isset($_GET['unverify'])) { $v=(int)$_GET['unverify']; $conn->query("UPDATE users SET is_verified=0 WHERE id=$v AND is_admin=0"); header("Location: admin.php?tab=users"); exit(); }

$tab=$_GET['tab']??'pending';
$pending  = $conn->query("SELECT p.*,CONCAT(u.FirstName,' ',u.LastName) AS uname FROM products p LEFT JOIN users u ON u.id=p.added_by WHERE p.status='pending' ORDER BY p.id DESC");
$approved = $conn->query("SELECT p.*,CONCAT(u.FirstName,' ',u.LastName) AS uname FROM products p LEFT JOIN users u ON u.id=p.added_by WHERE p.status='approved' ORDER BY p.id DESC");
$allProds = $conn->query("SELECT p.*,CONCAT(u.FirstName,' ',u.LastName) AS uname FROM products p LEFT JOIN users u ON u.id=p.added_by ORDER BY p.id DESC");
$users    = $conn->query("SELECT u.*,(SELECT COUNT(*) FROM products WHERE added_by=u.id) as pcount FROM users u ORDER BY u.is_admin DESC,u.id ASC");

$cnt_p=$pending->num_rows; $cnt_a=$approved->num_rows;
$cnt_all=$allProds->num_rows; $cnt_u=$users->num_rows;
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>پانێلی ئەدمین — MobileShop</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Noto Sans Arabic',sans-serif}
:root{--bg:#070912;--s:#0e1018;--s2:#141520;--bd:rgba(255,255,255,.07);--ac:#7c6fff;--ac2:#ff6b9d;--green:#00d4aa;--orange:#ffd93d;--text:#f0f0ff;--muted:rgba(255,255,255,.4)}
body{background:var(--bg);color:var(--text);min-height:100vh}
nav{background:rgba(14,16,24,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--bd);padding:0 24px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.logo{font-size:1rem;font-weight:800;color:var(--ac);display:flex;align-items:center;gap:6px}
.nav-links{display:flex;gap:6px}
.nb{background:var(--s2);border:1px solid var(--bd);color:var(--muted);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:700;transition:.2s}
.nb:hover{background:var(--ac);color:#fff;border-color:var(--ac)}
.nb.danger{color:var(--ac2)}
.nb.danger:hover{background:var(--ac2);color:#fff;border-color:var(--ac2)}

.wrap{max-width:1060px;margin:0 auto;padding:28px 20px}

/* آمارەکان */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px}
@media(max-width:700px){.stats{grid-template-columns:repeat(2,1fr)}}
.stat{
  background:var(--s);border:1px solid var(--bd);border-radius:16px;
  padding:18px 16px;text-align:center;cursor:pointer;transition:.25s;
}
.stat:hover,.stat.active{border-color:var(--ac);background:var(--s2)}
.stat-n{font-size:2rem;font-weight:800;line-height:1}
.stat-l{font-size:.75rem;color:var(--muted);margin-top:6px}

/* تابەکان */
.tabs{display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap}
.tab{padding:9px 18px;border-radius:50px;border:1px solid var(--bd);background:var(--s);color:var(--muted);font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer;transition:.2s;text-decoration:none;display:flex;align-items:center;gap:6px}
.tab:hover,.tab.active{background:var(--ac);color:#fff;border-color:var(--ac);box-shadow:0 4px 14px rgba(124,111,255,.3)}
.tab-badge{background:rgba(255,255,255,.2);color:inherit;font-size:.7rem;padding:1px 7px;border-radius:20px;font-weight:800}

/* کارتی پراکتیکال */
.pcard{background:var(--s);border:1px solid var(--bd);border-radius:16px;padding:18px;margin-bottom:12px;transition:.2s}
.pcard:hover{border-color:var(--bd);box-shadow:0 4px 20px rgba(0,0,0,.3)}
.pcard-top{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.pcard-img{width:72px;height:72px;border-radius:12px;object-fit:cover;background:var(--s2);flex-shrink:0}
.pcard-info{flex:1;min-width:140px}
.pcard-name{font-size:1rem;font-weight:800;margin-bottom:4px}
.pcard-meta{font-size:.78rem;color:var(--muted)}
.pcard-owner{background:var(--s2);border:1px solid var(--bd);font-size:.72rem;padding:3px 10px;border-radius:50px;display:inline-flex;align-items:center;gap:4px;margin-top:5px;color:var(--muted)}
.pcard-price{font-size:1.2rem;font-weight:800;color:var(--green);white-space:nowrap}
.pcard-actions{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.btn-approve{padding:9px 20px;background:rgba(0,212,170,.12);border:1px solid rgba(0,212,170,.3);color:var(--green);border-radius:10px;font-family:inherit;font-size:.83rem;font-weight:700;cursor:pointer;text-decoration:none;transition:.2s}
.btn-approve:hover{background:var(--green);color:#000}
.btn-reject{padding:9px 20px;background:rgba(255,107,157,.1);border:1px solid rgba(255,107,157,.25);color:var(--ac2);border-radius:10px;font-family:inherit;font-size:.83rem;font-weight:700;cursor:pointer;text-decoration:none;transition:.2s}
.btn-reject:hover{background:var(--ac2);color:#fff}
.btn-del{padding:9px 14px;background:rgba(255,255,255,.05);border:1px solid var(--bd);color:var(--muted);border-radius:10px;font-family:inherit;font-size:.83rem;cursor:pointer;transition:.2s}
.btn-del:hover{background:rgba(255,75,75,.15);border-color:rgba(255,75,75,.3);color:#ff7b7b}

/* جەدوەل */
.sec{background:var(--s);border:1px solid var(--bd);border-radius:16px;overflow:hidden;margin-bottom:16px}
.sec-head{padding:16px 20px;border-bottom:1px solid var(--bd);font-size:.9rem;font-weight:800;display:flex;align-items:center;justify-content:space-between}
.tbl{width:100%;border-collapse:collapse}
.tbl th{padding:11px 14px;text-align:right;font-size:.75rem;font-weight:700;color:var(--muted);border-bottom:1px solid var(--bd);background:var(--s2)}
.tbl td{padding:12px 14px;border-bottom:1px solid var(--bd);font-size:.85rem;vertical-align:middle}
.tbl tr:last-child td{border:none}
.tbl tr:hover td{background:rgba(255,255,255,.02)}
.timg{width:44px;height:44px;border-radius:8px;object-fit:cover;background:var(--s2)}
.price-cell{font-weight:800;color:var(--green)}
.status-badge{padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:800}
.s-approved{background:rgba(0,212,170,.12);color:var(--green);border:1px solid rgba(0,212,170,.25)}
.s-pending{background:rgba(255,217,61,.1);color:var(--orange);border:1px solid rgba(255,217,61,.25)}
.s-rejected{background:rgba(255,107,157,.1);color:var(--ac2);border:1px solid rgba(255,107,157,.25)}

/* ئەکاونتەکان */
.urow{
  background:var(--s);border:1px solid var(--bd);border-radius:14px;
  padding:14px 16px;display:flex;align-items:center;gap:10px;
  flex-wrap:wrap;margin-bottom:10px;transition:.2s;
}
.urow:hover{border-color:var(--bd);background:var(--s2)}
.uav{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--ac);flex-shrink:0}
.uinfo{flex:1;min-width:120px}
.uname{font-size:.9rem;font-weight:800}
.uemail{font-size:.75rem;color:var(--muted);margin-top:1px}
.ubadge{padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:800}
.ubadge-a{background:rgba(124,111,255,.15);color:var(--ac);border:1px solid rgba(124,111,255,.3)}
.ubadge-u{background:rgba(255,255,255,.06);color:var(--muted);border:1px solid var(--bd)}
.ubadge-v{background:rgba(0,212,170,.12);color:var(--green);border:1px solid rgba(0,212,170,.3);padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:800}
.btn-verify{padding:4px 11px;background:rgba(0,212,170,.1);border:1px solid rgba(0,212,170,.25);color:var(--green);border-radius:50px;font-family:inherit;font-size:.72rem;font-weight:700;cursor:pointer;text-decoration:none;transition:.2s}
.btn-verify:hover{background:var(--green);color:#000}
.btn-unverify{padding:4px 11px;background:rgba(255,107,157,.08);border:1px solid rgba(255,107,157,.2);color:var(--ac2);border-radius:50px;font-family:inherit;font-size:.72rem;font-weight:700;cursor:pointer;text-decoration:none;transition:.2s}
.upc{font-size:.78rem;color:var(--muted);background:var(--s2);border:1px solid var(--bd);padding:3px 10px;border-radius:50px}

.empty{text-align:center;padding:52px 20px;color:var(--muted);font-size:.95rem}

/* مۆدالی پشتڕاستکردن */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:500;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.mbox{background:var(--s);border:1px solid var(--bd);border-radius:20px;padding:32px 28px;text-align:center;max-width:360px;width:90%;animation:mIn .3s}
@keyframes mIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
.mbox-em{font-size:2.5rem;margin-bottom:12px}
.mbox h3{font-size:1.1rem;font-weight:800;margin-bottom:8px}
.mbox p{font-size:.88rem;color:var(--muted);margin-bottom:24px;line-height:1.6}
.mbox-btns{display:flex;gap:10px;justify-content:center}
.mb-cancel{padding:10px 22px;background:var(--s2);border:1px solid var(--bd);color:var(--muted);border-radius:10px;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;transition:.2s}
.mb-cancel:hover{border-color:var(--ac);color:var(--ac)}
.mb-ok{padding:10px 22px;background:rgba(255,75,75,.15);border:1px solid rgba(255,75,75,.3);color:#ff7b7b;border-radius:10px;font-family:inherit;font-size:.88rem;font-weight:700;text-decoration:none;transition:.2s}
.mb-ok:hover{background:#ff4d4d;color:#fff;border-color:#ff4d4d}
</style>
</head>
<body>
<nav>
  <div class="logo">🛡 پانێلی ئەدمین</div>
  <div class="nav-links">
    <a href="mobile.php" class="nb"><i class="fas fa-store"></i> فرۆشگا</a>
    <a href="profile.php" class="nb"><i class="fas fa-user"></i> پرۆفایل</a>
    <a href="logout.php" class="nb danger"><i class="fas fa-sign-out-alt"></i> چوونەدەرەوە</a>
  </div>
</nav>

<div class="wrap">
  <!-- آمارەکان -->
  <div class="stats">
    <div class="stat <?=$tab=='pending'?'active':''?>" onclick="location.href='?tab=pending'">
      <div class="stat-n" style="color:var(--orange)"><?=$cnt_p?></div>
      <div class="stat-l">⏳ چاوەڕوان</div>
    </div>
    <div class="stat <?=$tab=='approved'?'active':''?>" onclick="location.href='?tab=approved'">
      <div class="stat-n" style="color:var(--green)"><?=$cnt_a?></div>
      <div class="stat-l">✅ ئەکسێپتکراو</div>
    </div>
    <div class="stat <?=$tab=='all'?'active':''?>" onclick="location.href='?tab=all'">
      <div class="stat-n" style="color:var(--ac)"><?=$cnt_all?></div>
      <div class="stat-l">📦 هەموو بەرهەم</div>
    </div>
    <div class="stat <?=$tab=='users'?'active':''?>" onclick="location.href='?tab=users'">
      <div class="stat-n" style="color:#00d4ff"><?=$cnt_u?></div>
      <div class="stat-l">👥 ئەکاونتەکان</div>
    </div>
  </div>

  <!-- تابەکان -->
  <div class="tabs">
    <a href="?tab=pending" class="tab <?=$tab=='pending'?'active':''?>">⏳ چاوەڕوان <?php if($cnt_p>0):?><span class="tab-badge"><?=$cnt_p?></span><?php endif;?></a>
    <a href="?tab=approved" class="tab <?=$tab=='approved'?'active':''?>">✅ ئەکسێپتکراو</a>
    <a href="?tab=all" class="tab <?=$tab=='all'?'active':''?>">📦 هەموو بەرهەم</a>
    <a href="?tab=users" class="tab <?=$tab=='users'?'active':''?>">👥 ئەکاونتەکان</a>
  </div>

  <!-- چاوەڕوان -->
  <?php if($tab=='pending'): ?>
    <?php if(!$cnt_p): ?>
      <div class="empty">✅ هیچ بەرهەمێک چاوەڕوان نییە</div>
    <?php else: while($pp=$pending->fetch_assoc()):
      $pimg=(!empty($pp['image'])&&file_exists('uploads/'.$pp['image']))?'uploads/'.$pp['image']:'https://via.placeholder.com/72/141520/7c6fff?text=📱';
    ?>
      <div class="pcard">
        <div class="pcard-top">
          <img src="<?=$pimg?>" class="pcard-img">
          <div class="pcard-info">
            <div class="pcard-name"><?=htmlspecialchars($pp['brand'].' '.$pp['name'])?></div>
            <div class="pcard-meta"><?=htmlspecialchars($pp['storage'])?> · <?=htmlspecialchars($pp['color'])?></div>
            <div class="pcard-owner"><i class="fas fa-user"></i><?=htmlspecialchars($pp['uname']??'نەزانراو')?></div>
          </div>
          <div>
            <div class="pcard-price">$<?=number_format($pp['price'],2)?></div>
            <div style="margin-top:6px"><span class="status-badge s-pending">⏳ چاوەڕوان</span></div>
          </div>
        </div>
        <div class="pcard-actions">
          <a href="?approve=<?=$pp['id']?>" class="btn-approve">✅ ئەکسێپت</a>
          <a href="?reject=<?=$pp['id']?>" class="btn-reject" onclick="return confirm('دڵنیایت؟')">❌ ڕەدکردنەوە</a>
          <button class="btn-del" onclick="askDel('?delete=<?=$pp['id']?>','<?=htmlspecialchars($pp['brand'].' '.$pp['name'])?>')">🗑 سڕینەوە</button>
        </div>
      </div>
    <?php endwhile; endif; ?>

  <!-- ئەکسێپتکراو -->
  <?php elseif($tab=='approved'): ?>
    <div class="sec">
      <div class="sec-head"><span>✅ بەرهەمی ئەکسێپتکراو</span><span class="status-badge s-approved"><?=$cnt_a?></span></div>
      <?php if(!$cnt_a): ?><div class="empty">هیچ بەرهەمێک نییە</div>
      <?php else: ?>
      <table class="tbl"><thead><tr><th>وێنە</th><th>بەرهەم</th><th>فرۆشیار</th><th>نرخ</th><th></th></tr></thead><tbody>
      <?php while($pp=$approved->fetch_assoc()):
        $pimg=(!empty($pp['image'])&&file_exists('uploads/'.$pp['image']))?'uploads/'.$pp['image']:'https://via.placeholder.com/44/141520/7c6fff?text=📱';
      ?>
        <tr>
          <td><img src="<?=$pimg?>" class="timg"></td>
          <td><?=htmlspecialchars($pp['brand'].' '.$pp['name'])?><br><span style="font-size:.72rem;color:var(--muted)"><?=htmlspecialchars($pp['storage'])?> · <?=htmlspecialchars($pp['color'])?></span></td>
          <td><span class="pcard-owner"><i class="fas fa-user"></i><?=htmlspecialchars($pp['uname']??'نەزانراو')?></span></td>
          <td class="price-cell">$<?=number_format($pp['price'],2)?></td>
          <td><button class="btn-del" onclick="askDel('?delete=<?=$pp['id']?>','<?=htmlspecialchars($pp['brand'].' '.$pp['name'])?>')">🗑</button></td>
        </tr>
      <?php endwhile; ?>
      </tbody></table>
      <?php endif; ?>
    </div>

  <!-- هەموو بەرهەم -->
  <?php elseif($tab=='all'): ?>
    <div class="sec">
      <div class="sec-head"><span>📦 هەموو بەرهەمەکان</span><span style="color:var(--ac);font-weight:800"><?=$cnt_all?></span></div>
      <?php if(!$cnt_all): ?><div class="empty">هیچ بەرهەمێک نییە</div>
      <?php else: ?>
      <table class="tbl"><thead><tr><th>وێنە</th><th>بەرهەم</th><th>فرۆشیار</th><th>باری</th><th>نرخ</th><th></th></tr></thead><tbody>
      <?php while($pp=$allProds->fetch_assoc()):
        $pimg=(!empty($pp['image'])&&file_exists('uploads/'.$pp['image']))?'uploads/'.$pp['image']:'https://via.placeholder.com/44/141520/7c6fff?text=📱';
        $stag=$pp['status']=='approved'?'<span class="status-badge s-approved">✅ ئەکسێپت</span>':($pp['status']=='rejected'?'<span class="status-badge s-rejected">❌ ڕەدکرا</span>':'<span class="status-badge s-pending">⏳ چاوەڕوان</span>');
      ?>
        <tr>
          <td><img src="<?=$pimg?>" class="timg"></td>
          <td><?=htmlspecialchars($pp['brand'].' '.$pp['name'])?></td>
          <td><span class="pcard-owner"><i class="fas fa-user"></i><?=htmlspecialchars($pp['uname']??'نەزانراو')?></span></td>
          <td><?=$stag?></td>
          <td class="price-cell">$<?=number_format($pp['price'],2)?></td>
          <td><button class="btn-del" onclick="askDel('?delete=<?=$pp['id']?>','<?=htmlspecialchars($pp['brand'].' '.$pp['name'])?>')">🗑</button></td>
        </tr>
      <?php endwhile; ?>
      </tbody></table>
      <?php endif; ?>
    </div>

  <!-- ئەکاونتەکان -->
  <?php elseif($tab=='users'): ?>
    <?php while($uu=$users->fetch_assoc()):
      $uav="https://ui-avatars.com/api/?name=".urlencode($uu['FirstName'].' '.$uu['LastName'])."&size=40&background=7c6fff&color=fff&bold=true";
      if(!empty($uu['profile_image'])&&file_exists($uu['profile_image'])) $uav=$uu['profile_image'];
    ?>
      <div class="urow">
        <img src="<?=$uav?>" class="uav">
        <div class="uinfo">
          <div class="uname"><?=htmlspecialchars($uu['FirstName'].' '.$uu['LastName'])?></div>
          <div class="uemail"><?=htmlspecialchars($uu['Email'])?></div>
        </div>
        <span class="ubadge <?=$uu['is_admin']?'ubadge-a':'ubadge-u'?>"><?=$uu['is_admin']?'🛡 ئەدمین':'👤 یوزەر'?></span>
        <?php if(!$uu['is_admin']): ?>
          <?php if(!empty($uu['is_verified'])&&$uu['is_verified']==1): ?>
            <span class="ubadge-v">✔ ڤێریفاید</span>
            <a href="?unverify=<?=$uu['id']?>" class="btn-unverify">❌ لابردن</a>
          <?php else: ?>
            <a href="?verify=<?=$uu['id']?>" class="btn-verify">✔ ڤێریفایدکردن</a>
          <?php endif; ?>
        <?php endif; ?>
        <span class="upc">📦 <?=$uu['pcount']?></span>
        <?php if(!$uu['is_admin']): ?>
          <button class="btn-del" onclick="askDelUser('?del_user=<?=$uu['id']?>','<?=htmlspecialchars($uu['FirstName'].' '.$uu['LastName'])?>',<?=$uu['pcount']?>)">🗑 سڕینەوە</button>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<!-- مۆدالی پشتڕاستکردن -->
<div class="modal-bg" id="cm">
  <div class="mbox">
    <div class="mbox-em" id="cEm">⚠️</div>
    <h3 id="cTitle"></h3>
    <p id="cDesc"></p>
    <div class="mbox-btns">
      <button class="mb-cancel" onclick="document.getElementById('cm').classList.remove('open')">پاشگەزبوونەوە</button>
      <a class="mb-ok" id="cBtn" href="#">بەڵێ، بسڕەوە</a>
    </div>
  </div>
</div>
<script>
function askDel(url,name){
  document.getElementById('cEm').textContent='📦';
  document.getElementById('cTitle').textContent='سڕینەوەی بەرهەم';
  document.getElementById('cDesc').textContent='دڵنیایت لە سڕینەوەی '+name+'؟';
  document.getElementById('cBtn').href=url;
  document.getElementById('cm').classList.add('open');
}
function askDelUser(url,name,pc){
  document.getElementById('cEm').textContent='👤';
  document.getElementById('cTitle').textContent='سڕینەوەی ئەکاونت';
  document.getElementById('cDesc').textContent='دڵنیایت لە سڕینەوەی ئەکاونتی '+name+'؟'+(pc>0?' ('+pc+' بەرهەمیشی دەسڕێتەوە)':'');
  document.getElementById('cBtn').href=url;
  document.getElementById('cm').classList.add('open');
}
document.getElementById('cm').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});
</script>
</body>
</html>
