<?php
if (file_exists(__DIR__ . '/../db_connection.php')) {
    include_once(__DIR__ . '/../db_connection.php');
} else {
    include_once('../db_connection.php');
}
$con = OpenSrishringarrCon();

$cnt = isset($_POST["cnt"]) ? intval($_POST["cnt"]) : 1;
$qryitem = mysqli_query($con, "SELECT `category` FROM `categories` ORDER BY `category` ASC");
$category = [];
if ($qryitem) {
    while ($row = mysqli_fetch_row($qryitem)) {
        $category[] = $row[0];
    }
}
CloseCon($con);
?>
<td style="width: 120px;">
    <input type="text" name="item_no[]" class="pm-table-control pm-code-badge-input item_no" 
           id="item_no<?php echo $cnt; ?>" value="" autocomplete="off" readonly placeholder="Auto Code" />
</td>
<td>
    <input type="text" name="myitemid[]" class="pm-table-control item_id" 
           id="textField<?php echo $cnt; ?>" value="" onkeyup="checkUsername(event);" onblur="item_num1(this.id);" 
           placeholder="Enter Item / SKU Name" autocomplete="off" />
</td>
<td style="width: 170px;">
    <select name="item_cat[]" class="pm-table-control item_cat" id="item_cat<?php echo $cnt; ?>">
        <option value="0">Select Category</option>
        <?php foreach ($category as $catName) { ?>
            <option value="<?php echo htmlspecialchars($catName); ?>"><?php echo htmlspecialchars($catName); ?></option>
        <?php } ?>
    </select>
</td>
<td style="width: 130px;">
    <input type="text" name="cprice[]" class="pm-table-control text-right cprice" 
           id="cprice<?php echo $cnt; ?>" onChange="subtotal()" onkeypress="return isNumberKey(event)" 
           placeholder="0.00" autocomplete="off" />
</td>
<td style="width: 130px;">
    <input type="text" name="uprice[]" class="pm-table-control text-right uprice" 
           id="uprice<?php echo $cnt; ?>" onkeypress="return isNumberKey(event)" 
           placeholder="0.00" autocomplete="off" />
</td>
<td style="width: 100px;">
    <input type="text" name="qty[]" class="pm-table-control text-center qty" 
           id="qty<?php echo $cnt; ?>" onChange="subtotal()" onkeypress="return isNumberKey(event)" 
           placeholder="1" autocomplete="off" />
</td>
<td style="width: 140px;">
    <input type="text" name="subtotal[]" class="pm-table-control text-right subtotal" 
           id="subtotal<?php echo $cnt; ?>" readonly style="font-weight: 600; background: #f8fafc;" placeholder="0.00" />
</td>