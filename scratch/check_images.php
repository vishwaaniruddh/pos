<?php
$c = new mysqli('localhost', 'root', '', 'u464193275_srishrinjewels');
$res = $c->query('DESCRIBE product_images_new');
while($r = $res->fetch_assoc()) {
    echo $r['Field'] . ' | ' . $r['Type'] . "\n";
}

echo "\n--- Sample image rows for product YNL347 ---\n";
$iRes = $c->query("SELECT id, pro_code, img_name, product_id, gproduct_id FROM product_images_new WHERE pro_code='YNL347'");
while($iRow = $iRes->fetch_assoc()) {
    print_r($iRow);
}
