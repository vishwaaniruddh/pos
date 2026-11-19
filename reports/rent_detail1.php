<?php
//ini_set( "display_errors", 0);
include('config.php');
$cid=$_GET['id'];

 $result1 = mysql_query("SELECT * FROM  `scheme`  where bill_id='$cid'");
	$rowordno = mysql_fetch_row($result1);
	
 $result = mysql_query("SELECT * FROM  `phppos_people` where person_id='$rowordno[1]'");
	$row = mysql_fetch_row($result);
	
	
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

<div id="bill">
<table width="837" border="0" align="center">
<tr>
    <td width="831" height="42">
    
    <table width="852" >
       <tr>
        <td colspan="2" align="center" style="padding-left:100px;">
          <font size="+1">
          <?php
          $result5=mysql_query("select * from   `phppos_app_config`");
$row5 = mysql_fetch_array($result5);
mysql_data_seek($result5,1);
$row6=mysql_fetch_array($result5);
mysql_data_seek($result5,10);

$row7=mysql_fetch_array($result5);

?>
<b><?php echo $row6[1]; ?></b><br><?php echo $row5[1]; ?><br/><?php echo $row7[1]; ?><br/><br/>

            <B><U> Scheme RETURN</U></B></font></td>
         </tr>            
  <tr>
  <td height="42" colspan="2" align="left"><br /></td>
    </tr>
    
  <tr>
    <td width="403" ></td>
    </tr>
    
  <tr>
    <td height="21"><font size="+1" ><strong>M/s.&nbsp;:&nbsp;</strong>&nbsp;<?PHP echo $row[0] . " ".$row[1]; ?></font></td><td width="437"><strong>Date:</strong><?php if(isset($rowordno[2]) and $rowordno[2]!='0000-00-00') echo date('d/m/Y',strtotime($rowordno[2])); ?></td>
    </tr>
    
  <tr>
    <td height="23"><strong>Contact No.: </strong>&nbsp;&nbsp;&nbsp;
      <?php echo $row[2]; ?></td>
    <td><strong>Bill No:</strong><?php echo $cid; ?></td></tr>
    <tr><td><strong>Throught Name:</strong> <?php echo $rowordno[8]; ?><br/>

        <strong>Throught Contact No:</strong> <?php echo $rowordno[14]; ?></td>
    <td><p>&nbsp;</p>
      </td></tr>
     <tr>
    
    <td height="23"><strong>Paid Amount.: </strong>&nbsp;&nbsp;&nbsp;
      <?php echo $rowordno[6]; ?></td>
    <td><b>Balance Amount :</b> <?php echo $rowordno[3]-$rowordno[6]; ?></td>
     </tr>
  </table><font size="2" >
  <form action="updateRent1.php" method="post" >
  
    <table width="851" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="77"><font size="2">ITEM CODE</font></th>
    <th width="83">PARTICULARS</th>
    <th width="75"><font size="2">PRICE</font></th>
   
     <th width="98"><font size="2">Scheme Amount </font></th>

       <th width="81"><font size="2">Discount</font></th>
       <th width="81"><font size="2">Total</font></th>

    
  </tr>
  <?php
 $result2 = mysql_query("SELECT * FROM  `scheme_detail` where bill_id='$cid'");
	while($row2 = mysql_fetch_row($result2))
{
//echo "SELECT * FROM phppos_items WHERE name=".$design[$i];

$sq="SELECT * FROM phppos_items WHERE name='$row2[1]' and is_deleted = 0";
$res2 = mysql_query($sq);
$num2=mysql_num_rows($res2);
$row1=mysql_fetch_row($res2);


//echo $rowordno[0]."/ ".$design[$i]." / ".$prz[$i]." / ".$dep[$i]."<br/>";
  ?>
  <tr>

    <td align="center"><?php echo $row1[0]; ?></td>
    <td align="center"><?php echo $row1[1]; ?></td>
    <td align="center"><?php echo $row1[6] ?></td>

    <td align="center"><?php echo $row2[2] ?></td>
      <td align="center"><?php if($row2[5]=="%"){
		
		echo $row2[6]."%";
		}else {
		echo "Rs.".$row2[6];
		} ?></td>
 <td align="center"><?php echo $row2[7] ?></td>   
  </tr>
  <?php   } ?>
  <input name="id" type="hidden" value="<?php echo $cid ?>" />

<tr><td colspan="8" align="center">
  <strong>Paid Amount : </strong>&nbsp; <input type="text" name="amt" id="amt" />&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="Submit" value="Scheme Return" /> &nbsp;&nbsp;&nbsp;</td></tr>
</table></form></font></td>
    </tr>
     <tr><td>
</td></tr>
</table>

</div><br/><br/><div id="pageNavPosition"></div>
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/pos/home_dashboard.php">Back</a></center>

</body>
</html>