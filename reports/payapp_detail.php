<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<?php
//ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$cid=$_REQUEST['id'];

 
	
$result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$cid'");
$row = mysqli_fetch_row($result);

	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SHRINGAAR</title>
<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
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
<center>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"];?>">
<input type="hidden" name="id" value="<?php if(isset($_REQUEST['id']) && $_REQUEST['id']!=""){ echo $_REQUEST['id']; } ?>"/>
<input type="text" name="fdate" id="fdate" onClick="displayDatePicker('fdate');" placeholder="From Date" value="<?php if(isset($_REQUEST['fdate']) && $_REQUEST['fdate']!=""){ echo $_REQUEST['fdate']; } ?>"/>
<input type="text" name="sdate" id="sdate" onClick="displayDatePicker('sdate');" placeholder="To Date" value="<?php if(isset($_REQUEST['sdate']) && $_REQUEST['sdate']!=""){ echo $_REQUEST['sdate']; } ?>"/>
<input type="submit" value="Search"/>
</form>
</center>

<div id="bill">

<table width="521" height="371" align="center"><tr><td width="513" height="170" align="center">
          <font size="+1">
          <?php
          $result5=mysqli_query($con,"select * from `phppos_app_config`");
$row5 = mysqli_fetch_array($result5);
mysqli_data_seek($result5,1);
$row6=mysqli_fetch_array($result5);
mysqli_data_seek($result5,10);

$row7=mysqli_fetch_array($result5);

?>
<img src="bill.PNG" width="408" height="165"/><br/><br/>

 <B><U> APPROVAL RETURN PAYMENT DETAIL</U></B></font>

</td></tr>
<tr><td height="26"><p><font size="+1" >M/s.&nbsp;:&nbsp;&nbsp;<?PHP echo $row[0] . " ".$row[1]; ?></font></p></td></tr>
<tr><td height="42">Contact No.: &nbsp;&nbsp;&nbsp;
<?php echo $row[2]; ?></td></tr>
      <tr><td>
<table width="100%" align="center">
<tr>
  <td width="438" height="46"><b>Approval Return Paid Amount Details</b></td></tr>
<tr>
  <td valign="top"><table width="100%" border="1" cellpadding="4" cellspacing="0">
    <tr>
      <th>Sr.No</th>
      <th>Paid Amount</th>
       <th>Payment By</th>
      <th>Return Date</th>
      </tr>
    <?php $j=1;
    $sum=0;

		
		////echo $rw[0]."<br/>";
			$ql1=mysqli_query($con,"SELECT * FROM  `paid_amount` where bill_id='$cid'");
			$num=mysqli_num_rows($ql1);
			
			if($num==0)
			{ 
			 //   echo "SELECT paid_amount FROM  `approval` where cust_id='$cid'";
			$ql2=mysqli_query($con,"SELECT paid_amount FROM  `approval` where cust_id='$cid'");
			while($rw2=mysqli_fetch_row($ql2)){
			
			if($rw2[0]=="" || $rw2[0]==0){
			}else{?>
				 <tr>
      <td><?php echo $j++; ?></td>
      <td><?php echo $rw2[0]; ?></td>
       <td><?php echo $rw2[0]; ?></td>
      <td></td>
      
      </tr>
      <?php }$sum+=$rw2[0];  }
			}else{		
			$str="SELECT * FROM  `paid_amount` where bill_id='$cid'";
			if(isset($_REQUEST['fdate']) && $_REQUEST['fdate']!="" && isset($_REQUEST['sdate']) && $_REQUEST['sdate']!="")
			{
				$str.=" and return_date between STR_TO_DATE('".$_REQUEST['fdate']."','%d/%m/%Y') and STR_TO_DATE('".$_REQUEST['sdate']."','%d/%m/%Y')";
			}
// 			echo $str;
			$ql1=mysqli_query($con,$str);
			while($rw1=mysqli_fetch_row($ql1)){
			if($rw1[2]=="" || $rw1[2]==0){
			}else{
	?>
    <tr>
      <td><?php echo $j++; ?></td>
      <td><?php echo $rw1[2]; ?></td>
      <td><?php echo $rw1[4]; ?></td>
      <td><?php if(isset($rw1[3]) and $rw1[3]!='0000-00-00') echo date('d/m/Y',strtotime($rw1[3])); ?></td>
      </tr>
    <?php } $sum+=$rw1[2];   }?>
  <tr>
  <?php } ?>
      <td></td>
      <td><?php echo "Total : ".$sum; ?></td>
      <td colspan="2">&nbsp;</td>
      </tr>   
    
  </table>
  </td></tr></table></td></tr></table>
 </div> 
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</center>

</body>
<?php CloseCon($con);?>
</html>