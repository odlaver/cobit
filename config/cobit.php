<?php

return [

    // Matikan Turbo kalau navigasi bermasalah saat demo
    'turbo' => (bool) env('COBIT_TURBO', true),

    // Batas menit dianggap masih online
    'online_minutes' => 2,

];
