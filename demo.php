<!-- Add this inside your invoice layout where the billing table is shown -->

<table border="1" cellpadding="5" cellspacing="0" width="100%">
  <tr>
    <th>SR. NO.</th>
    <th>ITEM CODE</th>
    <th>PARTICULARS</th>
    <th>PRICE</th>
    <th>QTY</th>
    <th>AMOUNT</th>
    <th>DISCOUNT</th>
    <th>TAXABLE VALUE</th>
    <th>CGST (9%)</th>
    <th>SGST (9%)</th>
    <th>TOTAL</th>
  </tr>
  <tr>
    <td>1</td>
    <td>T1022</td>
    <td>Tikkas</td>
    <td>2300.00</td>
    <td>1</td>
    <td>2300.00</td>
    <td>300.00</td>
    <td>2000.00</td> <!-- After discount -->
    <td>180.00</td> <!-- 9% of 2000 -->
    <td>180.00</td> <!-- 9% of 2000 -->
    <td>2360.00</td> <!-- Total incl. GST -->
  </tr>
</table>

<br>

<table border="1" cellpadding="5" cellspacing="0" width="50%" align="right">
  <tr>
    <th>Gross Total:</th>
    <td>₹2000.00</td>
  </tr>
  <tr>
    <th>+ CGST (9%):</th>
    <td>₹180.00</td>
  </tr>
  <tr>
    <th>+ SGST (9%):</th>
    <td>₹180.00</td>
  </tr>
  <tr>
    <th>Net Payable:</th>
    <td><strong>₹2360.00</strong></td>
  </tr>
</table>

<br><br><br><br><br>

<table border="1" cellpadding="5" cellspacing="0" width="100%">
  <tr>
    <th>Amount Paid:</th>
    <td>₹2360.00</td>
    <th>Balance:</th>
    <td>₹0.00</td>
  </tr>
</table>

<?php return ; 







  function OpenSrishringarrCon(){
        //$dbhost = "localhost";
        $dbhost = "localhost";
        $dbuser = "u464193275_sarmicropos";
        $dbpass = "Mypos1234";
        $db = "u464193275_srishringarr";
        $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
        
        return $conn;
 }
 

$con = OpenSrishringarrCon() ; 

$datetime = date('Y-m-d H:i:s');
$sql = mysqli_query($con,"select * from phppos_items");
while($sql_result = mysqli_fetch_assoc($sql)){
    
    $sku = $sql_result['name'];
    $qty = $sql_result['quantity'];
    
    mysqli_query($con,"insert into items_log(sku,qty,operation_type,status,created_at) values('".$sku."','".$qty."','update',0,'".$datetime."')");
    
    
    
}

?>