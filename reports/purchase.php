 <html>
 <head>
<script type="text/javascript">
var tableToExcel = (function() {
  var uri = 'data:application/vnd.ms-excel;base64,'
    , template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>'
    , base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
    , format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) }
  return function(table, name) {
    if (!table.nodeType) table = document.getElementById(table)
    var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML}
    window.location.href = uri + base64(format(template, ctx))
  }
})()
</script></head>
<body>
<center>
<form name="frm1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table><tr><td>
From Date (dd/mm/yyyy): <input type="text" name="frmdt" id="frmdt" value="<?php if(isset($_POST['frmdt'])){ echo $_POST['frmdt']; }else{ if(date('m')<'3'){ echo date('1/04/Y',strtotime('-1 year')); }else{ echo "01/04/".date('Y'); }} ?>"></td><td>
To Date (dd/mm/yyyy): <input type="text" name="todt" id="todt" value="<?php if(isset($_POST['todt'])){ echo $_POST['todt']; }else{ if(date('m')>'3'){ echo date('31/03/Y',strtotime('+1 year')); }else{ echo date('31/03/Y'); } } ?>"></td><td><input type="submit" name="cmdsub" value="submit"></td></tr></table>
</form>
<input type="button" name="export" value="export" onclick="tableToExcel('testTable', 'W3C Example Table')" /></center>
 <?php 
  $frmdt=date('Y-04-01');
$todt=date('Y-m-d');
if(date('m')>'3'){ $todt= date('Y-03-31',strtotime('+1 year')); }else{ $todt= date('Y-03-31'); }
if(isset($_POST['frmdt']) && isset($_POST['todt'])){
$frmdt=date('Y-m-d',strtotime(str_replace('/','-',$_POST['frmdt'])));
$todt=date('Y-m-d',strtotime(str_replace('/','-',$_POST['todt'])));
}
// 	  include('config.php');
	  include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	  
	 // echo "SELECT * FROM  `phppos_items` WHERE STR_TO_DATE(description,'%d/%m/%Y')>='$frmdt' and STR_TO_DATE(description,'%d/%m/%Y')<='$todt' order by STR_TO_DATE(description,'%d/%m/%Y')";
	  $result = mysqli_query($con,"SELECT * FROM  `phppos_items` WHERE STR_TO_DATE(description,'%d/%m/%Y')>='$frmdt' and STR_TO_DATE(description,'%d/%m/%Y')<='$todt' order by STR_TO_DATE(description,'%d/%m/%Y')");
	  $sn=0;
	  $total=0;
  ?>
   <table width="1096"  border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center" id="testTable" >
  
<tr>
<td align="center" colspan='5' >
<b>Purchase Report Till <?php echo date('d/m/Y',strtotime($todt)) ?></b>

</td></tr>	
<tr><td>Sr No.</td><td>Purchase Date</td><td>Item</td><td>Supplier</td><td>Amount</td></tr>
 <?php 
        while($row = mysqli_fetch_row($result)){ 
         $result1 = mysqli_query($con,"SELECT sum(qty),sum(return_qty) FROM  approval_detail where item_id='$row[0]' and not qty=0");
         $sum=0;
              $row1 = mysqli_fetch_row($result1); 
              $sum=$row1[0]-$row1[1];                                         
              $amt=($sum+$row[7])*$row[5];
              $total=$total+$amt;
         $result2 = mysqli_query($con,"SELECT first_name,last_name FROM  phppos_people where person_id='$row[2]'");      
         $row2 = mysqli_fetch_row($result2);            
         
  ?>
  <tr><td><?php echo ++$sn; ?></td><td><?php echo $row[4]; ?></td><td><?php echo $row[0]." ".$row[1]; ?></td><td><?php echo $row2[0]." ".$row2[1]; ?></td><td><?php echo $amt; ?></td></tr>
  <?php } ?>
  <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>TOTAL</td><td><?php echo $total; ?></td>
  <?php CloseCon($con);?>
  </table>                                    