<?php
$icons = ["o-calendar","o-calendar-days","o-check","o-check-circle","o-calendar-date-range","o-clock","o-calendar-check","o-check-badge"];
foreach ($icons as $i) {
    try { heroicon($i); echo $i.": OK\n"; } catch (\Throwable $e) { echo $i.": NOT FOUND\n"; }
}
