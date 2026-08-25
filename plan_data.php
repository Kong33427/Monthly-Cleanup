<?php
// Historical only: the original 2026 annual Printer/PC cleaning plan
// (department x month), matching the source plan spreadsheet. The live,
// editable plan now lives in the plan_entries DB table (edited via index.php's
// Annual Plan cells) — this file is only read once, by setup.php, to seed
// plan_entries the first time the table is created. Not read at runtime by
// index.php or seed_plan_2026.php anymore.
return [
    'GW' => [
        'บัญชี'         => ['Printer' => [1,3,5,7,9,11], 'PC' => [4]],
        'ประสานงานขาย'  => ['Printer' => [1,3,5,7,9,11], 'PC' => [4]],
        'ขาย'           => ['PC' => [4]],
        'การตลาด'       => ['PC' => [4]],
        'ต่างประเทศ'     => ['PC' => [4]],
        'Job control'   => ['PC' => [4]],
        'สำนักงานกลาง'   => ['PC' => [4]],
        'จัดซื้อ'        => ['Printer' => [1,3,5,7,9,11], 'PC' => [4]],
        'บุคคล'         => ['PC' => [4]],
        'วิศวกรรม'       => ['PC' => [11]],
        'ผลิต'          => ['Printer' => [1,3,5,7,9,11], 'PC' => [6]],
        'วางแผน'        => ['PC' => [6]],
        'QA-QC'         => ['PC' => [6]],
        'สโตร์'         => ['PC' => [6]],
        'คลังสินค้า'     => ['Printer' => [1,3,5,7,9,11], 'PC' => [6]],
        'ออกแบบ'        => ['PC' => [6]],
        'ไอที'          => ['PC' => [6]],
        'เครื่องจักร'    => [],
    ],
    'IND' => [
        'บุคคล'         => ['PC' => [6]],
        'บัญชี'         => ['Printer' => [1,3,5,7,9,11], 'PC' => [6]],
        'ตรวจสอบ'      => ['PC' => [6]],
        'คลังสินค้า'     => ['Printer' => [1,3,5,7,9,11], 'PC' => [6]],
        'จัดซื้อ'        => ['Printer' => [1,3,5,7,9,11], 'PC' => [6]],
        'ผลิต'          => ['Printer' => [1,3,5,7,9,11], 'PC' => [6]],
        'วางแผน'        => ['PC' => [6]],
    ],
];
