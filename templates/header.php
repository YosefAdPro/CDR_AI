<!doctype html>
<html dir="rtl" lang="he">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo Config::get('site.main.title'); ?></title>
<meta name="robots" content="<?php echo Config::get('site.main.robots'); ?>">
<link rel="icon" type="image/png" sizes="192x192" href="img/favicon.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="img/simptip.min.css">
<link rel="stylesheet" type="text/css" href="img/style.css?<?php echo filemtime('img/style.css'); ?>">
<style>
@import url('https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700&display=swap');
* { font-family: 'Heebo', sans-serif; }
.gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
</style>
<script src="<?php echo Config::get('cdn.js.jquery'); ?>"></script>
<script src="<?php echo Config::get('cdn.js.jquery_object'); ?>"></script>
<script src="<?php echo Config::get('cdn.js.clipboard_js'); ?>"></script>
<script>
var playerAutoplay = <?php echo Config::exists('site.js.player_autoplay') && Config::get('site.js.player_autoplay') == 1 ? 'true' : 'false'; ?>,
    playerTitle    = <?php echo Config::exists('site.js.player_title') && Config::get('site.js.player_title') == 1 ? 'true' : 'false'; ?>,
    playerSymbol   = '<?php echo Config::exists('site.js.player_symbol') ? Config::get('site.js.player_symbol') : ''; ?>',
    scrollShow     = <?php echo Config::exists('site.js.scroll_show') && Config::get('site.js.scroll_show') == 1 ? 'true' : 'false'; ?>,
    userfieldEdit  = <?php echo Config::exists('display.main.userfield_edit') && Config::get('display.main.userfield_edit') == 1 ? 'true' : 'false'; ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/he.js"></script>
<script src="img/script.js?<?php echo filemtime('img/script.js'); ?>"></script>
</head>
<body class="bg-gray-50 min-h-screen">

<nav class="gradient-bg text-white shadow-lg">
    <div class="max-w-screen-xl mx-auto px-6 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <?php if (Config::exists('site.main.logo_path') && Config::get('site.main.logo_path') != ''): ?>
                    <a href="index.php"><img src="<?php echo Config::get('site.main.logo_path'); ?>" class="h-10"></a>
                <?php else: ?>
                    <a href="index.php" class="flex items-center gap-2 text-white no-underline">
                        <i class="fas fa-phone-volume text-2xl"></i>
                        <span class="text-xl font-bold"><?php echo Config::get('site.main.head'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <a href="dashboard.php" class="flex items-center gap-1 bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition text-sm font-medium text-white no-underline">
                    <i class="fas fa-chart-line"></i> דשבורד
                </a>
                <a href="index.php" class="flex items-center gap-1 bg-white/30 hover:bg-white/40 px-4 py-2 rounded-lg transition text-sm font-medium text-white no-underline">
                    <i class="fas fa-search"></i> חיפוש שיחות
                </a>
                <?php if (strlen(getenv('REMOTE_USER'))): ?>
                <a href="index.php?action=logout" class="flex items-center gap-1 bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition text-sm text-white no-underline">
                    <i class="fas fa-sign-out-alt"></i> צא
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-screen-xl mx-auto px-6 py-6">
