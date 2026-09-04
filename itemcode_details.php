<?php
ini_set( "display_errors", 0);

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


// include('config.php');
include('db_connection.php') ;
$con=OpenSrishringarrCon();

 $total2=0;     

$res=mysqli_query($con,"Select * from phppos_items where is_deleted = 0 ");                
$num=mysqli_num_rows($res);
mysqli_field_count($con);

?>
	<script type="text/javascript">
		function PrintDiv() {
			var divToPrint = document.getElementById('bill');
			divToPrint.style.fontSize = "10px";
			var popupWin = window.open('', '_blank', 'width=800,height=500');
			popupWin.document.open();
			popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
			popupWin.document.close();
		}

	</script> 
	<title>ItemCode List</title>
	<font size="+1"><center>
<a href="/pos/home_dashboard.php" style="font-size:18px;font-weight:bold;">Back</a>&nbsp;&nbsp;&nbsp;<a href="#" style="font-size:18px;font-weight:bold;" onclick='PrintDiv();'>Print</a></center></font>
	<div style="text-align: center;" id="bill">
		<table align="center">
			<tr>
				<td width="853" align="center"> <img src="reports/bill.PNG" width="408" height="165" />
					<br>
					<br> <u> ItemCode List </u>
					<br> 
				    <br>
					 
				</td>
			</tr>
			<tr>
				<td>
					<table border="1" cellpadding="4" cellspacing="0" width="881" align="left" id="bill">
						<tr>
							<th width='43' height="34">
								<U>Sr.No.</U>
							</th>
							<th width='208'><u>ItemCode</u></th>
							
							<th width='196'><u>Date of Purchase</u></th>
							<th width='188'><u>Quantity</u></th>
							<th width='100'><u>Action</u></th>
						</tr>
						<?php 
                            $i=1;
                            while($row = mysqli_fetch_row($res)) 
                             {
                                 $suppid = $row[2];
                                 
                                $supp_detail = mysqli_query($con,"select * from phppos_suppliers where person_id = '".$suppid."' "); 
                                $supp_res = mysqli_fetch_assoc($supp_detail);
                                $supp_name = $supp_res[1];
                                 
                        ?>
							<tr>
								<td width="43">
									<?php echo $i; ?>
								</td>
								<td width="208" align="left">
									<?php echo $row[0]; ?>
								</td>
								
								<td width="196">
									<?php echo $row[4]; ?>
								</td>
								<td width="188">
									<?php echo $row[7]; ?>
								</td>
								
								<td width="78">
									<a href="/pos/itemcode_delete.php?id=<?php echo $row[9]; ?>" onclick="return confirm('Are you sure??')" style="font-size:18px;font-weight:bold;">Delete</a>&nbsp;&nbsp;&nbsp;
									<br/> <a href="/pos/itemcode_edit.php?id=<?php echo $row[9]; ?>" target="_new" style="font-size:18px;font-weight:bold;">Edit</a></td>
							</tr>
							<?php $i++;   } ?>
					</table>
				</td>
			</tr>
		</table>
	</div>
	<script>
	    function Setcheck(val)
	    {
	        var val= this.val;
	        console.log(val);
	    }
	</script>

