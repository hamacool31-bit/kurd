<?php
include 'connect.php';
if (!isLoggedIn()) { header("Location: index.php"); exit(); }
$uid = (int)$_SESSION['user_id'];
$done = false;

if (isset($_POST['submit'])) {
    $brand   = $conn->real_escape_string($_POST['brand']);
    $name    = $conn->real_escape_string($_POST['name']);
    $storage = $conn->real_escape_string($_POST['storage']);
    $color   = $conn->real_escape_string($_POST['color']);
    $price   = (float)$_POST['price'];
    $img = ''; $uploadedImages = [];

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['error'] as $key => $error) {
            if ($error == 0) {
                $filename = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], "uploads/$filename")) {
                    $uploadedImages[] = $filename;
                    if (empty($img)) $img = $filename;
                }
            }
        }
    }
    $conn->query("INSERT INTO products (brand,name,storage,color,price,image,added_by,status) VALUES ('$brand','$name','$storage','$color','$price','$img',$uid,'pending')");
    $pid = $conn->insert_id;
    if ($pid && !empty($uploadedImages)) {
        foreach ($uploadedImages as $imgFile) {
            $imgEsc = $conn->real_escape_string($imgFile);
            $conn->query("INSERT INTO product_images (product_id, image) VALUES ($pid, '$imgEsc')");
        }
    }
    $done = true;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>زیادکردنی بەرهەم — MobileShop</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Noto Sans Arabic',sans-serif}
:root{--bg:#070912;--card:rgba(255,255,255,.04);--bd:rgba(255,255,255,.08);--ac:#7c6fff;--ac2:#ff6b9d;--green:#00d4aa;--text:#f0f0ff;--muted:rgba(255,255,255,.4);--inp:rgba(255,255,255,.06)}
body{background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:28px 16px}
.back-link{align-self:flex-start;margin-bottom:20px;color:var(--muted);text-decoration:none;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:6px;transition:.2s}
.back-link:hover{color:var(--ac)}
.card{background:var(--card);border:1px solid var(--bd);border-radius:24px;width:100%;max-width:460px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4);backdrop-filter:blur(12px)}
.head{background:linear-gradient(135deg,var(--ac),rgba(255,107,157,.6));padding:26px 24px;text-align:center}
.head h2{font-size:1.25rem;font-weight:800;color:#fff}
.head p{font-size:.8rem;color:rgba(255,255,255,.75);margin-top:4px}
.body{padding:28px 24px}
.seller-tag{background:rgba(124,111,255,.1);border:1px solid rgba(124,111,255,.2);border-radius:50px;padding:8px 16px;font-size:.82rem;font-weight:700;color:var(--ac);text-align:center;margin-bottom:22px;display:flex;align-items:center;justify-content:center;gap:6px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.field{margin-bottom:14px}
.field label{display:block;font-size:.72rem;font-weight:700;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px}
.field-inner{position:relative}
.field-icon{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem;pointer-events:none}
input[type=text],input[type=number]{width:100%;padding:11px 38px 11px 12px;background:var(--inp);border:1.5px solid var(--bd);border-radius:11px;color:var(--text);font-family:inherit;font-size:.9rem;outline:none;transition:.2s}
input:focus{border-color:var(--ac);background:rgba(124,111,255,.08);box-shadow:0 0 0 3px rgba(124,111,255,.12)}
input::placeholder{color:var(--muted)}
input[type=file]{padding:10px 12px;cursor:pointer}
.preview-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.preview-item{position:relative;width:72px;height:72px;border-radius:10px;overflow:hidden;border:1.5px solid var(--bd)}
.preview-item img{width:100%;height:100%;object-fit:cover}
.preview-main-badge{position:absolute;bottom:3px;right:3px;background:var(--ac);color:#fff;font-size:8px;font-weight:800;padding:2px 5px;border-radius:4px}
.btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.7));color:#fff;border:none;border-radius:12px;font-family:inherit;font-size:.95rem;font-weight:800;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;box-shadow:0 6px 20px rgba(124,111,255,.3)}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(124,111,255,.45)}

/* سەرکەوتن */
.success-body{padding:40px 28px;text-align:center}
.success-emoji{font-size:4rem;margin-bottom:16px;display:block;animation:bounce .6s ease}
@keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px)}}
.pending-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(255,217,61,.1);border:1px solid rgba(255,217,61,.25);color:#ffd93d;font-size:.8rem;font-weight:700;padding:7px 16px;border-radius:50px;margin-bottom:16px}
.success-body h3{font-size:1.1rem;font-weight:800;margin-bottom:8px}
.success-body p{font-size:.85rem;color:var(--muted);line-height:1.7;margin-bottom:24px}
.s-btns{display:flex;flex-direction:column;gap:8px}
.s-btn{padding:12px;border-radius:11px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;text-decoration:none;text-align:center;border:none;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.s-btn.primary{background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.7));color:#fff;box-shadow:0 4px 14px rgba(124,111,255,.3)}
.s-btn.secondary{background:var(--inp);border:1px solid var(--bd);color:var(--muted)}
.s-btn:hover{opacity:.88;transform:translateY(-1px)}
</style>
</head>
<body>
<a href="mobile.php" class="back-link"><i class="fas fa-arrow-right"></i> گەڕانەوە بۆ فرۆشگا</a>

<div class="card">
  <?php if($done): ?>
    <div class="head"><h2>✅ زیادکرا!</h2></div>
    <div class="success-body">
      <span class="success-emoji">⏳</span>
      <div class="pending-pill">🔔 چاوەڕوانی ئەکسێپت</div>
      <h3>بەرهەمەکەت نێردرا بۆ ئەدمین</h3>
      <p>بەرهەمەکەت تۆمارکرا و ئێستا چاوەڕوانی پەسەندکردنی ئەدمینە. دوای ئەکسێپت لە فرۆشگادا دەردەکەوێت.</p>
      <div class="s-btns">
        <a href="add_product.php" class="s-btn primary"><i class="fas fa-plus"></i> بەرهەمێکی تر زیاد بکە</a>
        <a href="mobile.php" class="s-btn secondary"><i class="fas fa-store"></i> گەڕانەوە بۆ فرۆشگا</a>
      </div>
    </div>
  <?php else: ?>
    <div class="head">
      <h2>➕ زیادکردنی بەرهەم</h2>
      <p>دوای زیادکردن ئەدمین پەسەند دەکات</p>
    </div>
    <div class="body">
      <div class="seller-tag"><i class="fas fa-user"></i> زیادکەر: <b><?=htmlspecialchars($_SESSION['name']??'نەزانراو')?></b></div>
      <form method="POST" enctype="multipart/form-data">
        <div class="row2">
          <div class="field">
            <label>براند</label>
            <div class="field-inner">
              <i class="fas fa-tag field-icon"></i>
              <input type="text" name="brand" placeholder="Apple" required>
            </div>
          </div>
          <div class="field">
            <label>ناو</label>
            <div class="field-inner">
              <i class="fas fa-mobile-alt field-icon"></i>
              <input type="text" name="name" placeholder="iPhone 15" required>
            </div>
          </div>
        </div>
        <div class="row2">
          <div class="field">
            <label>مێموری</label>
            <div class="field-inner">
              <i class="fas fa-hdd field-icon"></i>
              <input type="text" name="storage" placeholder="128GB" required>
            </div>
          </div>
          <div class="field">
            <label>ڕەنگ</label>
            <div class="field-inner">
              <i class="fas fa-palette field-icon"></i>
              <input type="text" name="color" placeholder="سپی" required>
            </div>
          </div>
        </div>
        <div class="field">
          <label>نرخ ($)</label>
          <div class="field-inner">
            <i class="fas fa-dollar-sign field-icon"></i>
            <input type="number" name="price" placeholder="0.00" step="0.01" required>
          </div>
        </div>
        <div class="field">
          <label>وێنەکان (دەتوانی چەندین وێنە هەڵبژێریت)</label>
          <div class="field-inner">
            <input type="file" name="images[]" accept="image/*" required multiple onchange="previewImgs(this)">
          </div>
          <div class="preview-grid" id="imgPreview"></div>
        </div>
        <button type="submit" name="submit" class="btn-submit">
          <i class="fas fa-paper-plane"></i> نێردن بۆ ئەدمین
        </button>
      </form>
    </div>
  <?php endif; ?>
</div>

<script>
function previewImgs(input){
  const pv=document.getElementById('imgPreview');
  pv.innerHTML='';
  Array.from(input.files).forEach((file,i)=>{
    const reader=new FileReader();
    reader.onload=e=>{
      const div=document.createElement('div');div.className='preview-item';
      const img=document.createElement('img');img.src=e.target.result;
      div.appendChild(img);
      if(i===0){const b=document.createElement('span');b.className='preview-main-badge';b.textContent='⭐';div.appendChild(b);}
      pv.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}
</script>
</body>
</html>
