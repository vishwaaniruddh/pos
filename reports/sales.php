 <html>
 <head>
<script type="text/javascript" src="export.js">

</script></head>
<body>
<center><table>
<tr><td>
<form name="frm1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table><tr><td>
From Date (dd/mm/yyyy): <input type="text" name="frmdt" id="frmdt" value="<?php if(isset($_POST['frmdt'])){ echo $_POST['frmdt']; }else{ if(date('m')<'3'){ echo date('1/04/Y',strtotime('-1 year')); }else{ echo "01/04/".date('Y'); }} ?>"></td><td>
To Date (dd/mm/yyyy): <input type="text" name="todt" id="todt" value="<?php if(isset($_POST['todt'])){ echo $_POST['todt']; }else{ if(date('m')>'3'){ echo date('31/03/Y',strtotime('+1 year')); }else{ echo date('31/03/Y'); } } ?>"></td><td><input type="submit" name="cmdsub" value="submit"></td></tr></table>
</form></td></tr></table>
<input type="button" name="export" value="export" onclick="tableToExcel('testTable', 'W3C Example Table')" /></center>
 <?php 
 $frmdt=date('Y-04-01');
$todt=date('Y-m-d');
if(date('m')>'3'){ $todt= date('Y-03-31',strtotime('+1 year')); }else{ $todt= date('Y-03-31'); }
if(isset($_POST['frmdt']) && isset($_POST['todt'])){
$frmdt=date('Y-m-d',strtotime(str_replace('/','-',$_POST['frmdt'])));
$todt=date('Y-m-d',strtotime(str_replace('/','-',$_POST['todt'])));
}
 $str="SELECT * FROM  approval where bill_date between '".$frmdt."' and '".$todt."' and status='S' order by bill_date";
// 	  include('config.php'); 
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	  
	  $result = mysqli_query($con,$str);
	  $sn=0;
	  $total=0;
  ?>
   <table width="1096"  border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF"  align="center" id="testTable" >
<tr>
<td align="center" colspan='5' >
<b>Sales Report Till <?php echo date('d/m/Y',strtotime($todt)) ?></b>

</td></tr>	
<tr><td>Sr No.</td><td>Bill Date</td><td>Bill No</td><td>Customer</td><td>Amount</td></tr>
 <?php 
        while($row = mysqli_fetch_row($result)){ 
         $result1 = mysqli_query($con,"SELECT qty,return_qty,amount FROM  approval_detail where bill_id='$row[0]' and not qty=0");
         $sum=0;
              while($row1 = mysqli_fetch_row($result1)){ 
                                                       $rate=$row1[2]/$row1[0];
                                                       $bqty=$row1[0]-$row1[1];
                                                       $sum=$sum+$rate*$bqty;
                                                      } 
         $total=$total+$sum;                                                      
         $result2 = mysqli_query($con,"SELECT first_name,last_name FROM  phppos_people where person_id='$row[1]'");      
         $row2 = mysqli_fetch_row($result2);            
         
  ?>
  <tr><td><?php echo ++$sn; ?></td><td><?php echo $row[2]; ?></td><td><?php echo $row[0]; ?></td><td><?php echo $row2[0]." ".$row2[1]; ?></td><td><?php echo $sum; ?></td></tr>
  <?php } ?>
  <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>TOTAL</td><td><?php echo $total; ?></td>
  
<?php CloseCon($con);?>

  </table>                                    