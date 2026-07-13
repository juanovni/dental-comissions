<?php
$icons = ["o-check-circle","o-user-circle","o-user-plus","o-document-text","o-calendar-days","o-adjustments-horizontal","o-clock","o-calendar","o-sun","o-moon","o-arrow-up-tray","o-arrow-path-rounded-square","o-calendar-days","o-check"];
foreach ($icons as $i) {
    try { heroicon($i); echo $i.': OK'."\n"; } catch (\Throwable $e) { echo $i.': NOT FOUND - '.$e->getMessage()."\n"; }
}
