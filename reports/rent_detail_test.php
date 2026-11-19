<?php
//ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$cid=$_GET['id'];
$qty1=0;
$result1 = mysqli_query($con,"SELECT * FROM  `phppos_rent`  where bill_id='$cid'");
$rowordno = mysqli_fetch_row($result1);
	
$result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$rowordno[1]'");
$row = mysqli_fetch_row($result);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SHRINGAAR</title>
</head>


<script type="text/javascript" src="paging.js"></script>
<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }

       
     </script>
<body>

<div id="bill" style="font-size:12px;">
<table width="837" border="0" align="center">
<tr>
    <td width="831" height="42">
    
    <table width="852" >
       <tr>
        <td colspan="2" align="center" style="padding-left:100px;">
          <font size="+1">
<?php
$result5=mysqli_query($con,"select * from   `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);
$row7=mysqli_fetch_array($result5);

$bal=$rowordno[3]-$rowordno[10];

$sql2=mysqli_query($con,"SELECT * FROM `phppos_people` WHERE `person_id`='$rowordno[8]'");
$row2=mysqli_fetch_row($sql2); 
?>
<img src="bill.PNG" width="408" height="165"/><br/><br/>

<B><U> RENT RETURN</U></B></font>
</td>
</tr>            
  <tr>
  <td height="42" colspan="2" align="left"><br /></td>
  </tr>
    
   
  <tr>
    <td height="21"><font size="+1" ><strong>M/s.&nbsp;:&nbsp;</strong>&nbsp;<?PHP echo $row[0] . " ".$row[1]; ?></font></td><td width="437"><strong>Date:</strong><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></td>
  </tr>
    
  <tr>
    <td height="23"><strong>Contact No.: </strong>&nbsp;&nbsp;&nbsp;<?php echo $row[2]; ?></td>
    <td><strong>Bill No:</strong><?php echo $cid; ?></td>
  </tr>
  
  <tr>
  <td><strong>Throught Name:</strong> <?php echo $row2[0]." ".$row2[1]; ?><br/><strong>Throught Contact No:</strong> <?php echo $rowordno[14]; ?></td>
  <td><p>JUST Dial:</p>Person Name.: &nbsp;&nbsp;&nbsp; <b><?php echo $rowordno[15]; ?></b><br/>Contact No.:<?php echo $rowordno[16]; ?></td>
  </tr>
  
  <tr>
  <td height="23"><strong>Paid Amount.: </strong>&nbsp;&nbsp;&nbsp;<?php echo $rowordno[10]; ?></td>
  <td height="23"><strong>Balance Amount : </strong>&nbsp;&nbsp;&nbsp;<?php echo "Rs.".$bal; ?></td>
  <td>&nbsp;</td>
  </tr>
  </table>
  <font size="2" >
  
  <form action="updateRent_test.php" method="post" >
  
  <table width="851" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
  <th width="77"><font size="2">SR.NO.</font></th>
  <th width="77"><font size="2">ITEM CODE</font></th>
  <th width="83">PARTICULARS</th>
  <th width="83">DESCRIPTION</th>
  <th width="75"><font size="2">PRICE</font></th>
  <th width="75"><font size="2">QTY</font></th>
  <th width="98"><font size="2">RENT</font></th>
  <th width="81"><font size="2"> DEPOSIT</font></th>
  </tr>
  <?php
  $i=1;
  $result2 = mysqli_query($con,"SELECT * FROM  `order_detail` where bill_id='$cid'");
 while($row2 = mysqli_fetch_row($result2))
{
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='$row2[1]' and is_deleted = 0";
$res2 = mysqli_query($con,$sq);
$num2=mysqli_num_rows($res2);
$row1=mysqli_fetch_row($res2);
//echo $rowordno[0]."/ ".$design[$i]." / ".$prz[$i]." / ".$dep[$i]."<br/>";
  ?>
  <tr>
    <td align="center"><?php echo $i; ?></td>
    <td align="center"><?php echo $row1[0]; ?></td>
    <td align="center"><?php echo $row1[1]; ?></td>
    <td align="center"><?php echo $row2[7]; ?></td>
    <td align="center"><?php echo $row1[6] ?></td>
     <td align="center"><?php echo $row2[9]; ?></td>
    <td align="center"><?php echo $row2[2] ?></td>
    <td align="center"><?php echo $row2[3] ?></td>
  </tr>
  <?php  
  $i++; 
  $tr+=$row2[6];
  $qty1+= $row2[9];
  } ?>
  <input name="id" type="hidden" value="<?php echo $cid ?>" />

<tr>
<td colspan="7"><b> Total Rent : </b><?php echo $tr; ?>&nbsp;&nbsp;&nbsp; <b> Total Quantity : </b><input type="text" id="qty1" name="qty1" value="<?php echo $qty1; ?>" />&nbsp;&nbsp;<strong><!--Paid Amount : </strong>&nbsp; <input type="text" name="amt" id="amt" />-->&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="Submit" value="Rent Return" /> &nbsp;&nbsp;&nbsp;</td>
</tr>
</table>
</form>
</font>
</td>
</tr>
</table>

</div><br/><br/><div id="pageNavPosition"></div>
<?php CloseCon($con);?>
<center>
<a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/rent_return.php">Back</a></center>

</body>
</html>