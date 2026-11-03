<?php
/**
 * admin_products.php — Product CRUD (Vibrant Light Mode 2026)
 *
 * Edit flow:
 *   GET  ?edit=ID          → load product into inline edit form (PHP-populated)
 *   POST action=update     → UPDATE products … WHERE id=?  → redirect ?updated=1
 *   POST action=add        → INSERT INTO products …        → redirect ?added=1
 *   GET  ?delete=ID        → DELETE FROM products …        → redirect ?deleted=1
 *
 * Image handling:
 *   - Upload via <input type="file"> → saved to uploads/, stored as relative path
 *   - OR paste an image URL into the URL field
 *   - File upload takes priority; if no new file is chosen, the existing/URL value is kept
 */
session_start();
require_once 'db.php';
requireAdmin();

$admin  = $_SESSION['user'];
$errors = [];

/* ═══════════════════════════════════════════════════════════
   DELETE
═══════════════════════════════════════════════════════════ */
if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: admin_products.php?deleted=1');
    exit;
}

/* ═══════════════════════════════════════════════════════════
   EDIT LOAD  (?edit=ID via GET)
═══════════════════════════════════════════════════════════ */
$edit_product = null;   // populated when ?edit=ID is present
if (isset($_GET['edit']) && ctype_digit((string)$_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $es->execute([(int)$_GET['edit']]);
    $edit_product = $es->fetch() ?: null;
    // If ID doesn't exist in DB, just ignore silently
}

/* ═══════════════════════════════════════════════════════════
   SHARED: handle uploaded image file → returns path or ''
═══════════════════════════════════════════════════════════ */
function handleImageUpload(string $field_name): string
{
    if (empty($_FILES[$field_name]['name'])) {
        return '';
    }
    $file    = $_FILES[$field_name];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        return '__invalid__';   // sentinel — caller adds an error
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        return '__toobig__';
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'prod_' . uniqid('', true) . '.' . strtolower($ext);
    $dest     = __DIR__ . '/uploads/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return '__movefail__';
    }
    return 'uploads/' . $filename;
}

/* ═══════════════════════════════════════════════════════════
   POST — UPDATE (action=update)
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $pid   = (int)($_POST['product_id'] ?? 0);
    $name  = trim($_POST['name']        ?? '');
    $brand = trim($_POST['brand']       ?? '');
    $price = (float)($_POST['price']    ?? 0);
    $specs = trim($_POST['specs']       ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $stock = (int)($_POST['stock']      ?? 0);

    // Image: uploaded file takes priority → fallback to URL field → fallback to existing
    $img_upload = handleImageUpload('image_file');
    if ($img_upload === '__invalid__') { $errors[] = 'Image must be JPEG, PNG, WebP, or GIF.'; $img_upload = ''; }
    if ($img_upload === '__toobig__')  { $errors[] = 'Image file must be under 4 MB.';          $img_upload = ''; }
    if ($img_upload === '__movefail__'){ $errors[] = 'Could not save uploaded image. Check uploads/ permissions.'; $img_upload = ''; }

    $image = $img_upload !== ''
        ? $img_upload
        : (trim($_POST['image_url'] ?? '') ?: trim($_POST['existing_image'] ?? ''));

    // Validation
    if ($pid <= 0)    $errors[] = 'Invalid product ID.';
    if ($name === '') $errors[] = 'Product name is required.';
    if ($brand === '') $errors[] = 'Brand is required.';
    if ($price <= 0)  $errors[] = 'Price must be greater than zero.';
    if ($stock < 0)   $errors[] = 'Stock cannot be negative.';

    if (empty($errors)) {
        $pdo->prepare(
            'UPDATE products
             SET name=?, brand=?, price=?, specs=?, description=?, stock=?, image_url=?
             WHERE id=?'
        )->execute([$name, $brand, $price, $specs, $desc, $stock, $image, $pid]);
        header('Location: admin_products.php?updated=1');
        exit;
    }
    // Re-load the product row so the form stays populated on error
    $es = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $es->execute([$pid]);
    $edit_product = $es->fetch() ?: null;
}

/* ═══════════════════════════════════════════════════════════
   POST — ADD (action=add)
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['name']        ?? '');
    $brand = trim($_POST['brand']       ?? '');
    $price = (float)($_POST['price']    ?? 0);
    $specs = trim($_POST['specs']       ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $stock = (int)($_POST['stock']      ?? 0);

    $img_upload = handleImageUpload('image_file');
    if ($img_upload === '__invalid__') { $errors[] = 'Image must be JPEG, PNG, WebP, or GIF.'; $img_upload = ''; }
    if ($img_upload === '__toobig__')  { $errors[] = 'Image file must be under 4 MB.';          $img_upload = ''; }
    if ($img_upload === '__movefail__'){ $errors[] = 'Could not save uploaded image. Check uploads/ permissions.'; $img_upload = ''; }

    $image = $img_upload !== '' ? $img_upload : trim($_POST['image_url'] ?? '');

    if ($name === '') $errors[] = 'Product name is required.';
    if ($brand === '') $errors[] = 'Brand is required.';
    if ($price <= 0)  $errors[] = 'Price must be greater than zero.';
    if ($stock < 0)   $errors[] = 'Stock cannot be negative.';

    if (empty($errors)) {
        $pdo->prepare(
            'INSERT INTO products (name, brand, price, specs, description, stock, image_url, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([$name, $brand, $price, $specs, $desc, $stock, $image]);
        header('Location: admin_products.php?added=1');
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════
   Fetch product list
═══════════════════════════════════════════════════════════ */
$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_product ? 'Edit ' . htmlspecialchars($edit_product['name']) : 'Manage Products' ?> — TECHMOBILE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50 min-h-screen">

<!-- ── Navbar ────────────────────────────────────────────────── -->
<header class="glass-nav sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="admin_dashboard.php" class="flex items-center gap-2 no-underline shrink-0">
            <img src="uploads/logo.png" alt="TECHMOBILE" class="h-12 w-auto object-contain">
            <span class="font-extrabold text-lg tracking-tight text-slate-900">TECH<span class="text-violet-600">MOBILE</span></span>
            <span class="text-xs font-bold bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full ml-1">Admin</span>
        </a>
        <div class="flex items-center gap-2">
            <span class="hidden sm:flex items-center gap-1.5 text-sm text-slate-600">🛡 <strong class="text-slate-800"><?= htmlspecialchars($admin['name']) ?></strong></span>
            <a href="logout.php" class="text-xs font-semibold text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">Logout</a>
        </div>
    </div>
</header>

<!-- ── Admin Sub-Nav ──────────────────────────────────────────── -->
<div class="bg-gradient-to-r from-violet-950 to-indigo-900 px-4 sm:px-6 py-2">
    <div class="max-w-7xl mx-auto flex items-center gap-1 flex-wrap">
        <a href="admin_dashboard.php" class="admin-link text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">📊 Dashboard</a>
        <a href="admin_products.php"  class="admin-link active text-xs font-bold px-3 py-1.5 rounded-lg text-violet-200">📦 Products</a>
        <a href="admin_orders.php"    class="admin-link text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">🛒 Orders</a>
        <a href="index.php"           class="ml-auto text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">← Storefront</a>
    </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <!-- ── Flash Banners ────────────────────────────────────────── -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold px-4 py-3 rounded-xl mb-5 flex items-center gap-2">🗑 Product deleted.</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold px-4 py-3 rounded-xl mb-5 flex items-center gap-2">✅ Product updated successfully.</div>
    <?php elseif (isset($_GET['added'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold px-4 py-3 rounded-xl mb-5 flex items-center gap-2">✅ Product added successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium px-4 py-3 rounded-xl mb-5">
            <p class="font-bold mb-1">⚠ Please fix the following:</p>
            <ul class="list-disc list-inside space-y-0.5"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($edit_product): ?>
    <!-- ╔══════════════════════════════════════════════════════════╗
         ║  EDIT FORM — PHP-populated, shown when ?edit=ID is set  ║
         ╚══════════════════════════════════════════════════════════╝ -->
    <div class="bg-white border border-violet-200 rounded-2xl shadow-lg shadow-violet-500/8 mb-8 overflow-hidden animate-fade-up">

        <!-- Edit Header -->
        <div class="bg-gradient-to-r from-violet-700 to-indigo-700 px-6 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <!-- Thumbnail -->
                <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 overflow-hidden flex items-center justify-center shrink-0">
                    <?php if (!empty($edit_product['image_url'])): ?>
                        <img src="<?= htmlspecialchars($edit_product['image_url']) ?>"
                             alt="" class="w-full h-full object-contain p-1"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                        <span class="hidden text-xl" aria-hidden="true">📱</span>
                    <?php else: ?>
                        <span class="text-xl" aria-hidden="true">📱</span>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="font-extrabold text-white text-base leading-tight">✏️ Editing: <?= htmlspecialchars($edit_product['name']) ?></h2>
                    <p class="text-violet-200 text-xs mt-0.5">ID #<?= (int)$edit_product['id'] ?> · <?= htmlspecialchars($edit_product['brand']) ?></p>
                </div>
            </div>
            <a href="admin_products.php"
               class="text-xs font-bold text-white/70 hover:text-white border border-white/25 hover:border-white/60 px-3 py-1.5 rounded-lg transition-colors shrink-0">
                ✕ Cancel
            </a>
        </div>

        <!-- Edit Form Body -->
        <form method="POST" action="admin_products.php"
              enctype="multipart/form-data"
              novalidate
              class="p-6 sm:p-8 space-y-5">

            <input type="hidden" name="action"     value="update">
            <input type="hidden" name="product_id" value="<?= (int)$edit_product['id'] ?>">
            <!-- Preserve current image so it survives if no new file/URL is supplied -->
            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_product['image_url'] ?? '') ?>">

            <!-- Row 1: Name -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Product Name <span class="text-rose-400">*</span></label>
                <input type="text" name="name" class="field"
                       value="<?= htmlspecialchars($edit_product['name']) ?>"
                       placeholder="e.g. Samsung Galaxy S25"
                       required autocomplete="off">
            </div>

            <!-- Row 2: Brand + Price -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Brand <span class="text-rose-400">*</span></label>
                    <input type="text" name="brand" class="field"
                           value="<?= htmlspecialchars($edit_product['brand']) ?>"
                           placeholder="Samsung" required autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Price (₹) <span class="text-rose-400">*</span></label>
                    <input type="number" name="price" class="field"
                           value="<?= (float)$edit_product['price'] ?>"
                           placeholder="79999" step="0.01" min="0.01" required>
                </div>
            </div>

            <!-- Row 3: Stock + Specs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Stock Units</label>
                    <input type="number" name="stock" class="field"
                           value="<?= (int)$edit_product['stock'] ?>"
                           placeholder="50" min="0">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Specs <span class="text-slate-400 font-normal normal-case">(pipe-separated)</span></label>
                    <input type="text" name="specs" class="field"
                           value="<?= htmlspecialchars($edit_product['specs'] ?? '') ?>"
                           placeholder="8GB RAM | 256GB | 6.7&quot; OLED">
                </div>
            </div>

            <!-- Row 4: Description -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Description <span class="text-slate-400 font-normal normal-case">(optional)</span></label>
                <textarea name="description" class="field" rows="3"
                          placeholder="Brief product description shown on the detail page..."><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
            </div>

            <!-- Row 5: Image -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                <p class="text-xs font-bold text-slate-600 uppercase tracking-wide">Product Image</p>

                <!-- Current image preview -->
                <?php if (!empty($edit_product['image_url'])): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-16 h-16 rounded-xl border border-slate-200 bg-white overflow-hidden flex items-center justify-center shrink-0">
                            <img src="<?= htmlspecialchars($edit_product['image_url']) ?>"
                                 alt="" class="w-full h-full object-contain p-1"
                                 onerror="this.parentElement.innerHTML='<span class=\'text-2xl\'>📱</span>'">
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-700">Current image</p>
                            <p class="text-xs text-slate-400 truncate max-w-[260px]"><?= htmlspecialchars($edit_product['image_url']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- File upload -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Upload new image <span class="text-slate-400 font-normal">(JPEG / PNG / WebP / GIF, max 4 MB)</span>
                    </label>
                    <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif"
                           class="block w-full text-sm text-slate-600
                                  file:mr-3 file:py-1.5 file:px-3
                                  file:rounded-lg file:border file:border-violet-200
                                  file:text-xs file:font-semibold
                                  file:bg-violet-50 file:text-violet-700
                                  hover:file:bg-violet-100 file:transition-colors cursor-pointer">
                </div>

                <!-- URL fallback -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Or paste image URL <span class="text-slate-400 font-normal">(overrides current if no file is uploaded)</span>
                    </label>
                    <input type="url" name="image_url" class="field"
                           value="<?= htmlspecialchars(str_starts_with($edit_product['image_url'] ?? '', 'http') ? $edit_product['image_url'] : '') ?>"
                           placeholder="https://example.com/image.jpg">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex flex-wrap gap-3 pt-1">
                <button type="submit"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white font-bold px-8 py-3 rounded-xl transition-all duration-200 text-sm shadow-sm shadow-violet-500/20 hover:shadow-md hover:shadow-violet-500/30 active:scale-[0.98]">
                    💾 Save Changes
                </button>
                <a href="admin_products.php"
                   class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 text-slate-600 border border-slate-200 font-semibold px-6 py-3 rounded-xl hover:bg-slate-100 transition-colors text-sm">
                    ✕ Cancel Edit
                </a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- ── Add New Product Button (only when NOT in edit mode) ── -->
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <h1 class="text-2xl font-extrabold text-slate-900">📦 Products</h1>
        <button onclick="openModal('prodModal')"
            class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-bold px-5 py-2.5 rounded-xl transition-colors text-sm shadow-sm shadow-violet-500/20">
            ＋ Add New Product
        </button>
    </div>
    <?php endif; ?>

    <!-- ── Products Table ──────────────────────────────────────── -->
    <?php if (!$edit_product): ?>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3"><?= count($products) ?> Product<?= count($products) !== 1 ? 's' : '' ?> in Catalogue</h2>
    <?php endif; ?>

    <div class="tbl-wrap bg-white shadow-sm">
        <table class="w-full">
            <thead>
                <tr class="bg-gradient-to-r from-violet-950 to-indigo-900">
                    <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 w-10">ID</th>
                    <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Product</th>
                    <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden sm:table-cell">Brand</th>
                    <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Price</th>
                    <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden md:table-cell">Stock</th>
                    <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5 hidden lg:table-cell">Specs</th>
                    <th class="text-left text-xs font-bold text-violet-200 uppercase tracking-wider px-5 py-3.5">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($products)): ?>
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">No products yet. Click "Add New Product" above.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $p):
                    $is_editing = $edit_product && (int)$edit_product['id'] === (int)$p['id'];
                ?>
                    <tr class="transition-colors <?= $is_editing ? 'bg-violet-50 ring-2 ring-violet-400 ring-inset' : 'hover:bg-violet-50/40' ?>">
                        <td class="px-5 py-4 text-xs text-slate-400 font-medium"><?= (int)$p['id'] ?></td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <!-- Mini thumbnail -->
                                <div class="w-9 h-9 rounded-lg bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center shrink-0">
                                    <?php if (!empty($p['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($p['image_url']) ?>"
                                             alt="" class="w-full h-full object-contain p-0.5"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                                        <span class="hidden text-base" aria-hidden="true">📱</span>
                                    <?php else: ?>
                                        <span class="text-base" aria-hidden="true">📱</span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-semibold text-slate-900 text-sm"><?= htmlspecialchars($p['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600 hidden sm:table-cell"><?= htmlspecialchars($p['brand']) ?></td>
                        <td class="px-5 py-4 font-extrabold text-emerald-600 text-sm"><?= formatINR((float)$p['price']) ?></td>
                        <td class="px-5 py-4 hidden md:table-cell">
                            <?php if ((int)$p['stock'] > 0): ?>
                                <span class="inline-flex items-center text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md px-2 py-0.5"><?= (int)$p['stock'] ?> units</span>
                            <?php else: ?>
                                <span class="inline-flex items-center text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-md px-2 py-0.5">Out of stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-400 hidden lg:table-cell max-w-[140px] truncate" title="<?= htmlspecialchars($p['specs'] ?? '') ?>">
                            <?= htmlspecialchars($p['specs'] ?? '—') ?>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <?php if ($is_editing): ?>
                                    <span class="text-xs font-bold text-violet-600 bg-violet-100 px-3 py-1.5 rounded-lg">Editing…</span>
                                <?php else: ?>
                                    <a href="admin_products.php?edit=<?= (int)$p['id'] ?>"
                                       class="text-xs font-semibold text-violet-600 border border-violet-200 px-3 py-1.5 rounded-lg hover:bg-violet-50 transition-colors">Edit</a>
                                <?php endif; ?>
                                <a href="admin_products.php?delete=<?= (int)$p['id'] ?>"
                                   onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\'? This cannot be undone.')"
                                   class="text-xs font-semibold text-rose-500 border border-rose-200 px-3 py-1.5 rounded-lg hover:bg-rose-50 transition-colors">Del</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- ══════════════════════════════════════════════════════════════
     ADD NEW PRODUCT MODAL  (only used when NOT in edit mode)
══════════════════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="prodModal">
    <div class="modal-box bg-white rounded-3xl shadow-2xl shadow-violet-500/20 p-7 w-full max-w-lg relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeModal('prodModal')"
            class="absolute top-4 right-4 w-8 h-8 bg-slate-100 hover:bg-rose-100 hover:text-rose-500 text-slate-500 rounded-full flex items-center justify-center text-base font-bold transition-colors z-10">✕</button>
        <h2 class="text-lg font-extrabold text-slate-900 mb-5">＋ Add New Product</h2>

        <form method="POST" action="admin_products.php"
              enctype="multipart/form-data"
              novalidate class="space-y-4">
            <input type="hidden" name="action" value="add">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Product Name <span class="text-rose-400">*</span></label>
                <input type="text" name="name" class="field" placeholder="e.g. Samsung Galaxy S25" required autocomplete="off">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Brand <span class="text-rose-400">*</span></label>
                    <input type="text" name="brand" class="field" placeholder="Samsung" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Price (₹) <span class="text-rose-400">*</span></label>
                    <input type="number" name="price" class="field" placeholder="79999" step="0.01" min="0.01">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Stock Units</label>
                    <input type="number" name="stock" class="field" placeholder="50" min="0" value="0">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Specs</label>
                    <input type="text" name="specs" class="field" placeholder="8GB | 256GB">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Description <span class="text-slate-400 font-normal normal-case">(optional)</span></label>
                <textarea name="description" class="field" rows="2" placeholder="Brief product description..."></textarea>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                <p class="text-xs font-bold text-slate-600 uppercase tracking-wide">Product Image</p>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Upload file <span class="text-slate-400 font-normal">(JPEG / PNG / WebP / GIF, max 4 MB)</span></label>
                    <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif"
                           class="block w-full text-sm text-slate-600
                                  file:mr-3 file:py-1.5 file:px-3
                                  file:rounded-lg file:border file:border-violet-200
                                  file:text-xs file:font-semibold
                                  file:bg-violet-50 file:text-violet-700
                                  hover:file:bg-violet-100 file:transition-colors cursor-pointer">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Or image URL</label>
                    <input type="url" name="image_url" class="field" placeholder="https://...">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white font-bold py-3 rounded-xl transition-all duration-200 text-sm shadow-sm shadow-violet-500/20 active:scale-[0.98] mt-1">
                ＋ Add Product
            </button>
        </form>
    </div>
</div>

<footer class="bg-slate-900 text-slate-500 text-center py-4 text-xs mt-12">
    <p>&copy; <?= date('Y') ?> <strong class="text-slate-300">TECHMOBILE Admin</strong>.</p>
</footer>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }

// Close modal when clicking the backdrop
document.getElementById('prodModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('prodModal');
});

// Re-open add modal if there were validation errors on POST action=add
<?php if (!empty($errors) && ($_POST['action'] ?? '') === 'add'): ?>
window.addEventListener('load', () => openModal('prodModal'));
<?php endif; ?>

// Auto-scroll to edit form when it's visible
<?php if ($edit_product): ?>
window.addEventListener('load', () => {
    const editForm = document.querySelector('.animate-fade-up');
    if (editForm) editForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
</script>
</body>
</html>
