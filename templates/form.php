<!-- Page title -->
<div class="mb-6 flex items-center gap-4">
    <div class="gradient-bg text-white p-3 rounded-2xl shadow-md shrink-0">
        <i class="fas fa-phone-volume text-2xl"></i>
    </div>
    <div>
        <h1 class="text-xl font-bold text-gray-800"><?php echo Config::get('site.main.head'); ?></h1>
        <p class="text-sm text-gray-500">חיפוש שיחות והקלטות</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-md p-6 mb-6" id="form-container">
    <div id="form-loader" class="hidden absolute inset-0 flex items-center justify-center z-10 bg-white/60 rounded-2xl">
        <div class="cssload-loader"><div class="cssload-inner cssload-one"></div><div class="cssload-inner cssload-two"></div><div class="cssload-inner cssload-three"></div></div>
    </div>

    <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <i class="fas fa-search text-purple-500"></i> חיפוש שיחות
    </h2>

    <form method="post" enctype="application/x-www-form-urlencoded" action="" id="cdr-form">

        <!-- שורת תאריכים -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <span class="text-sm font-medium text-gray-600 whitespace-nowrap">טווח תאריכים:</span>
            <div class="flex items-center gap-2">
                <input type="text" id="fp-start" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-44 cursor-pointer focus:outline-none focus:border-purple-400" placeholder="תאריך התחלה" readonly>
                <span class="text-gray-400 text-sm">עד</span>
                <input type="text" id="fp-end" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-44 cursor-pointer focus:outline-none focus:border-purple-400" placeholder="תאריך סיום" readonly>
            </div>
            <div class="flex flex-wrap gap-1">
                <?php
                $ranges = ['td'=>'היום','yd'=>'אתמול','tw'=>'השבוע','pw'=>'שבוע שעבר','tm'=>'החודש','pm'=>'חודש שעבר','3m'=>'3 חודשים'];
                foreach($ranges as $val=>$label):
                ?>
                <button type="button" onclick="selectRange('<?php echo $val; ?>')"
                    class="px-3 py-1.5 text-xs rounded-full border border-gray-300 text-gray-600 hover:border-purple-400 hover:text-purple-600 hover:bg-purple-50 transition">
                    <?php echo $label; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Dropdowns מוסתרים (backend) -->
        <div class="hidden">
            <select name="startmin" id="startmin"><?php for($i=0;$i<=59;$i++) echo '<option value="'.sprintf('%02d',$i).'"'.($i==0?' selected':'').'>'.sprintf('%02d',$i).'</option>'; ?></select>
            <select name="starthour" id="starthour"><?php for($i=0;$i<=23;$i++) echo '<option value="'.$i.'"'.($i==0?' selected':'').'>'.$i.'</option>'; ?></select>
            <select name="startyear" id="startyear"><?php for($i=2000;$i<=date('Y');$i++) echo '<option value="'.$i.'"'.(date('Y')==$i?' selected':'').'>'.$i.'</option>'; ?></select>
            <?php
            $months=['01'=>'ינואר','02'=>'פברואר','03'=>'מרץ','04'=>'אפריל','05'=>'מאי','06'=>'יוני','07'=>'יולי','08'=>'אוגוסט','09'=>'ספטמבר','10'=>'אוקטובר','11'=>'נובמבר','12'=>'דצמבר'];
            ?>
            <select name="startmonth" id="startmonth"><?php foreach($months as $i=>$m) echo '<option value="'.$i.'"'.(date('m')==$i?' selected':'').'>'.$m.'</option>'; ?></select>
            <select name="startday" id="startday"><?php for($i=1;$i<=31;$i++) echo '<option value="'.$i.'"'.(date('d')==$i?' selected':'').'>'.$i.'</option>'; ?></select>
            <select name="endday" id="endday"><?php for($i=1;$i<=31;$i++) echo '<option value="'.$i.'"'.($i==31?' selected':'').'>'.$i.'</option>'; ?></select>
            <select name="endmonth" id="endmonth"><?php foreach($months as $i=>$m) echo '<option value="'.$i.'"'.(date('m')==$i?' selected':'').'>'.$m.'</option>'; ?></select>
            <select name="endyear" id="endyear"><?php for($i=2000;$i<=date('Y');$i++) echo '<option value="'.$i.'"'.(date('Y')==$i?' selected':'').'>'.$i.'</option>'; ?></select>
            <select name="endhour" id="endhour"><?php for($i=0;$i<=23;$i++) echo '<option value="'.$i.'"'.($i==23?' selected':'').'>'.$i.'</option>'; ?></select>
            <select name="endmin" id="endmin"><?php for($i=0;$i<=59;$i++) echo '<option value="'.sprintf('%02d',$i).'"'.($i==59?' selected':'').'>'.sprintf('%02d',$i).'</option>'; ?></select>
        </div>

        <!-- מסננים מתקדמים -->
        <div class="border border-gray-200 rounded-xl overflow-hidden mb-4">
            <button type="button" id="toggle-filters"
                class="w-full flex justify-between items-center px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-sm font-medium text-gray-700">
                <span><i class="fas fa-sliders-h ml-2 text-purple-400"></i>מסננים מתקדמים</span>
                <i class="fas fa-chevron-down text-gray-400" id="filters-chevron" style="transition:transform .2s"></i>
            </button>
            <div id="advanced-filters" class="hidden p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                $filterFields = [
                    'channel'     => ['label'=>'מספר ישיר',  'cond'=>'display.search.channel'],
                    'src'         => ['label'=>'שיחה נכנסת', 'cond'=>'display.search.src'],
                    'clid'        => ['label'=>'שם מתקשר',   'cond'=>'display.search.clid'],
                    'dst'         => ['label'=>'שיחה יוצאת', 'cond'=>'display.search.dst'],
                    'did'         => ['label'=>'DID',         'cond'=>'display.search.did'],
                    'dstchannel'  => ['label'=>'ערוץ יוצא',  'cond'=>'display.search.dstchannel'],
                    'accountcode' => ['label'=>'קוד חשבון',  'cond'=>'display.search.accountcode'],
                    'userfield'   => ['label'=>'פרשנות',     'cond'=>'display.search.userfield'],
                ];
                foreach($filterFields as $field => $cfg):
                    if (!Config::get($cfg['cond'])) continue;
                ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo $cfg['label']; ?></label>
                    <div class="flex gap-2 items-center">
                        <input type="radio" name="order" value="<?php echo $field; ?>" class="accent-purple-500 shrink-0">
                        <input type="text" name="<?php echo $field; ?>" id="<?php echo $field; ?>"
                            class="w-36 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                        <select name="<?php echo $field; ?>_mod" class="w-28 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-purple-400 shrink-0">
                            <option value="begins_with">מתחיל ב</option>
                            <option value="contains">מכיל</option>
                            <option value="ends_with">מסתיים ב</option>
                            <option value="exact">שווה</option>
                        </select>
                        <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap shrink-0">
                            <input type="checkbox" name="<?php echo $field; ?>_neg" value="true"> לא
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (Config::get('display.search.duration')): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">משך שיחה (שניות)</label>
                    <div class="flex gap-2 items-center">
                        <input type="radio" name="order" value="duration" class="accent-purple-500 shrink-0">
                        <input type="number" name="dur_min" placeholder="מ" min="0" class="w-20 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                        <span class="text-gray-400">—</span>
                        <input type="number" name="dur_max" placeholder="עד" min="0" class="w-20 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                    </div>
                </div>
                <?php endif; ?>

                <?php if (Config::get('display.search.disposition')): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">סטטוס שיחה</label>
                    <div class="flex gap-2 items-center">
                        <input type="radio" name="order" value="disposition" class="accent-purple-500 shrink-0">
                        <select name="disposition" class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                            <option value="all">כל אחד</option>
                            <option value="ANSWERED">נענה</option>
                            <option value="NO ANSWER">לא נענה</option>
                            <option value="BUSY">תפוס</option>
                            <option value="FAILED">נכשל</option>
                        </select>
                        <label class="flex items-center gap-1 text-xs text-gray-500 shrink-0">
                            <input type="checkbox" name="disposition_neg" value="true"> לא
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (Config::get('display.search.lastapp')): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">יישום</label>
                    <div class="flex gap-2 items-center">
                        <input type="radio" name="order" value="lastapp" class="accent-purple-500 shrink-0">
                        <select name="lastapp" class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                            <option value="all">הכל</option>
                            <option value="Dial">מחייג</option>
                            <option value="Queue">תורים</option>
                            <option value="Hangup">ניתוק</option>
                            <option value="VoiceMail">תא קולי</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- אפשרויות דוח -->
        <div class="border border-gray-200 rounded-xl overflow-hidden mb-5">
            <button type="button" id="toggle-options"
                class="w-full flex justify-between items-center px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-sm font-medium text-gray-700">
                <span><i class="fas fa-cog ml-2 text-purple-400"></i>אפשרויות דוח</span>
                <i class="fas fa-chevron-down text-gray-400" id="options-chevron" style="transition:transform .2s"></i>
            </button>
            <div id="report-options" class="hidden p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-2">סוג דוח</label>
                        <div class="space-y-1.5">
                            <label class="flex items-center gap-2 text-sm cursor-pointer"><input checked type="checkbox" name="need_html" value="true" class="accent-purple-500"> חיפוש בבסיס הנתונים</label>
                            <?php if(Config::get('display.search.csv')): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="need_csv" value="true" class="accent-purple-500"> ייצוא CSV</label>
                            <?php endif; ?>
                            <?php if(Config::get('display.search.chart')): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="need_chart" value="true" class="accent-purple-500"> גרף שיחות</label>
                            <?php endif; ?>
                            <?php if(Config::get('display.search.minutes_report')): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="need_minutes_report" value="true" class="accent-purple-500"> צריכת דקות</label>
                            <?php endif; ?>
                            <?php if(Config::get('display.search.chart_cc')): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="need_chart_cc" value="true" class="accent-purple-500"> שיחות בו-זמנית</label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-2">מיון וקיבוץ</label>
                        <div class="space-y-2">
                            <select name="sort" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                                <option value="DESC" selected>סדר יורד</option>
                                <option value="ASC">סדר עולה</option>
                            </select>
                            <select name="group" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                                <optgroup label="תאריך/זמן">
                                    <option value="day" selected>יום</option>
                                    <option value="hour">שעה</option>
                                    <option value="week">שבוע</option>
                                    <option value="month">חודש</option>
                                    <option value="hour_of_day">שעה ביום</option>
                                    <option value="day_of_week">יום בשבוע</option>
                                </optgroup>
                                <optgroup label="מספר">
                                    <option value="src">מחייג</option>
                                    <option value="dst">מחוייג</option>
                                    <option value="clid">שם מתקשר</option>
                                    <option value="accountcode">קוד חשבון</option>
                                </optgroup>
                                <optgroup label="סטטוס">
                                    <option value="disposition">סטטוס לפי ימים</option>
                                    <option value="disposition_by_hour">סטטוס לפי שעות</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-2">מספר רשומות</label>
                        <input type="number" name="limit" min="0" step="1" autocomplete="off"
                            value="<?php echo Config::get('display.main.result_limit'); ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                    </div>
                </div>
            </div>
        </div>

        <!-- כפתור חיפוש -->
        <div class="flex items-center gap-4 flex-wrap">
            <input type="hidden" name="form_submitted" value="1">
            <input type="hidden" name="order" value="calldate">
            <button id="form_submit" type="button"
                class="flex items-center gap-2 bg-gradient-to-l from-purple-600 to-indigo-500 hover:from-purple-700 hover:to-indigo-600 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow-md">
                <i class="fas fa-search"></i> חפש
            </button>
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input checked type="radio" name="search_mode" value="all" class="accent-purple-500"> כל המסננים
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="radio" name="search_mode" value="any" class="accent-purple-500"> כל אחד מהמסננים
            </label>
        </div>

    </form>
</div>

<script>
function togglePanel(panelId, chevronId) {
    var panel = document.getElementById(panelId);
    var icon = document.getElementById(chevronId);
    panel.classList.toggle('hidden');
    icon.style.transform = panel.classList.contains('hidden') ? '' : 'rotate(180deg)';
}
document.getElementById('toggle-filters').addEventListener('click', function() { togglePanel('advanced-filters','filters-chevron'); });
document.getElementById('toggle-options').addEventListener('click', function() { togglePanel('report-options','options-chevron'); });
</script>
