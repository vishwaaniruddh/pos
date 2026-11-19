<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();

 $letter = $_POST['alphabets'];
// var_dump($letter);

 $total2=0;
 $fromdate = $_POST['fromdate'];
 $todate = $_POST['todate'];
 
// $qry="SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers` ) and `first_name`!='' and `first_name` not Like 'B %' ORDER BY `phppos_people`.`first_name` ASC";
// $qry="SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers` ) and `first_name`!='' ";

// if(isset($_POST['alphabets']) && $_POST['alphabets']!='' && isset($_POST['sort']))
// {
//     $qry .= " "." and `first_name` like '".$letter."%' ORDER BY `phppos_people`.`first_name` ASC ";
//     // echo $qry; die;
// }


// else
// {
//   $qry .= " "."ORDER BY `phppos_people`.`first_name` ASC"; 
// }


$qry = "SELECT * FROM `phppos_people` WHERE `person_id` not in (SELECT `person_id` FROM `phppos_suppliers` )  ";

if(isset($_POST['alphabets']) && $_POST['alphabets']!=''){
   $qry .= " and first_name like '".$letter."%' " ; 
}

// $qry .=" and ( person_id in (select cust_id from approval where bill_date between '".$fromdate."' and '".$todate."')  OR person_id in (select cust_id from phppos_rent where bill_date between '".$fromdate."' and '".$todate."'))";

$qry .=" ORDER BY `phppos_people`.`first_name` ASC" ;


// echo $qry ; 
$res=mysqli_query($con,$qry);                
$num=mysqli_num_rows($res);
	
	
$peoplename = "select * from ";
?>

<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">-->

<style>
    .row {
    --bs-gutter-x: 1.5rem;
    --bs-gutter-y: 0;
    display: flex;
    flex-wrap: wrap;
    margin-top: calc(-1* var(--bs-gutter-y));
    margin-right: calc(-.5* var(--bs-gutter-x));
    margin-left: calc(-.5* var(--bs-gutter-x));
}
</style>
	<script type="text/javascript">
		function PrintDiv() {
			var divToPrint = document.getElementById('bill');
			divToPrint.style.fontSize = "10px";
			var popupWin = window.open('', '_blank', 'width=800,height=500');
			popupWin.document.open();
			popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
			popupWin.document.close();
		}

	</script> <font size="+1"><center>
<a href="/pos/home_dashboard.php" style="font-size:18px;font-weight:bold;">Back</a>&nbsp;&nbsp;&nbsp;<a href="#" style="font-size:18px;font-weight:bold;" onclick='PrintDiv();'>Print</a></center></font>
	<div style="text-align: center;" id="bill">
		<table align="center">
			<tr>
				<td width="853" align="center"> <img src="bill.PNG" width="408" height="165" />
					<br>
					<br> Customer List
					<br/> <a href="people.php?mode=customerList" target="_new">New Customer</a>
					<br/> 
                    <br/>
				    <form action="<?php $_SERVER['PHP_SELF']?>" method="post" name="form" class="form-control">
				        
				        
				        <div class="row">
				            <div class="col-sm-3">
				                <label>From</label>
				                <input type="date" name="fromdate" value="2008-01-01">
				            </div>
				            <div class="col-sm-3">
				                <label>To</label>
				                <input type="date" name="todate" value="<?= date('Y-m-d'); ?>">
				            </div>
				            
				            <div class="col-sm-6">
                                <label>Select Name Initials </label>
                                <select name="alphabets" id="alphabets" class="form-control" >
                                    <option value="">Select</option>
                                    <option value="A" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='A'){ echo 'selected' ;  }} ?>>A</option>
                                    <option value="B" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='B'){ echo 'selected' ;  }} ?>>B</option>
                                    <option value="C" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='C'){ echo 'selected' ;  }} ?>>C</option>
                                    <option value="D" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='D'){ echo 'selected' ;  }} ?>>D</option>
                                    <option value="E" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='E'){ echo 'selected' ;  }} ?>>E</option>
                                    <option value="F" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='F'){ echo 'selected' ;  }} ?>>F</option>
                                    <option value="G" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='G'){ echo 'selected' ;  }} ?>>G</option>
                                    <option value="H" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='H'){ echo 'selected' ;  }} ?>>H</option>
                                    <option value="I" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='I'){ echo 'selected' ;  }} ?>>I</option>
                                    <option value="J" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='J'){ echo 'selected' ;  }} ?>>J</option>
                                    <option value="K" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='K'){ echo 'selected' ;  }} ?>>K</option>
                                    <option value="L" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='L'){ echo 'selected' ;  }} ?>>L</option>
                                    <option value="M" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='M'){ echo 'selected' ;  }} ?>>M</option>
                                    <option value="N" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='N'){ echo 'selected' ;  }} ?>>N</option>
                                    <option value="O" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='O'){ echo 'selected' ;  }} ?>>O</option>
                                    <option value="P" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='P'){ echo 'selected' ;  }} ?>>P</option>
                                    <option value="Q" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='Q'){ echo 'selected' ;  }} ?>>Q</option>
                                    <option value="R" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='R'){ echo 'selected' ;  }} ?>>R</option>
                                    <option value="S" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='S'){ echo 'selected' ;  }} ?>>S</option>
                                    <option value="T" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='T'){ echo 'selected' ;  }} ?>>T</option>
                                    <option value="U" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='U'){ echo 'selected' ;  }} ?>>U</option>
                                    <option value="V" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='V'){ echo 'selected' ;  }} ?>>V</option>
                                    <option value="W" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='W'){ echo 'selected' ;  }} ?>>W</option>
                                    <option value="X" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='X'){ echo 'selected' ;  }} ?>>X</option>
                                    <option value="Y" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='Y'){ echo 'selected' ;  }} ?>>Y</option>
                                    <option value="Z" <? if(isset($_POST['alphabets'])) { if($_POST['alphabets']=='Z'){ echo 'selected' ;  }} ?>>Z</option>
                                </select>


				            </div>
				            
				        </div>
				    
					
					
					
					<input type="submit" name="sort" id="sort" value="Sort">
				    </form>
					 
				</td>
			</tr>

			<tr>
				<td>
					<table  border="1" cellpadding="4" cellspacing="0" width="881" align="left" id="bill">
						<tr>
							<th width='43' height="34">
								<U>Sr.No.</U>
							</th>
							<th width='208'><u>Customer Name</u></th>
							<th width="200">Adress / Last Name</th>
							<th width='73'><u>Mobile No.</u></th>
							<th width='196'><u>Email</u></th>
							<th width='188'><u>Address</u></th>
							<th width='78'><u>DOB</u></th>
							<th width='100'><u>Action</u></th>
						</tr>
						<?php 
                            $i=1;
                            while($row = mysqli_fetch_row($res)) 
                             {
                        ?>
							<tr>
								<td width="43">
									<?php echo $i; ?>
								</td>
								<td width="208" align="left">
									<?php echo $row[0] ; ?>
								</td>
								<td width="200">
								    <?
								    echo $row[1];
								    ?>
								</td>
								<td width="73">
									<?php echo $row[2]; ?>
								</td>
								<td width="196">
									<?php echo $row[3]; ?>
								</td>
								<td width="188">
									<?php 
									
									if($row[4].$row[5]){
    									echo $row[4].$row[5];
									}else{
									    echo $row[1];
									}
									
									
									
									?>
								</td>
								
								<td width="78">
									<?php  if(isset($row[12]) and $row[12]!='0000-00-00') echo date('d/m/Y',strtotime($row[12])); ?>
								</td>
								<td width="78">
									<!--<a href="/pos/reports/custDel.php?id=<?php //echo $row[11]; ?>--><a href="/pos/reports/custDel.php?id=<?php echo $row[11]; ?>" onclick="return confirm('Are you sure??')" style="font-size:18px;font-weight:bold;">Delete</a>&nbsp;&nbsp;&nbsp;
									<br/> <a href="/pos/reports/custEdit.php?id=<?php echo $row[11]; ?>" target="_new" style="font-size:18px;font-weight:bold;">Edit</a></td>
							</tr>
							<?php $i++;   } ?>
					</table>
				</td>
			</tr>
		</table>
			</div>
	</div>
	<script>
	    function Setcheck(val)
	    {
	        var val= this.val;
	        console.log(val);
	    }
	</script>
	<?php CloseCon($con);?>
