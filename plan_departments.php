<?php
// Ordered department roster for the Annual Plan table, independent of which
// cells actually have P/C entries in plan_entries (so a department with no
// entries at all, like เครื่องจักร, still shows as a row). Order matches the
// source plan spreadsheet. Cell editing (index.php + plan_toggle.php) only
// changes P/C assignments within this roster — it doesn't add/remove rows.
return [
    'GW' => ['บัญชี','ประสานงานขาย','ขาย','การตลาด','ต่างประเทศ','Job control','สำนักงานกลาง','จัดซื้อ','บุคคล','วิศวกรรม','ผลิต','วางแผน','QA-QC','สโตร์','คลังสินค้า','ออกแบบ','ไอที','เครื่องจักร'],
    'IND' => ['บุคคล','บัญชี','ตรวจสอบ','คลังสินค้า','จัดซื้อ','ผลิต','วางแผน'],
];
