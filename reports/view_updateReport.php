<img src="bill.PNG" width="408" height="165" style="margin-left:400px"/><br/><br/> 
<h3 style="margin-left:600px"> Audit Report </h3>
<center>
<form  action="approval_detail.php" id="frm1" name="frm1" method="POST">
Chooose Category:&nbsp;&nbsp;&nbsp;<select name="cat" id="cat" onchange="GetData();"><option value="-1" >select</option>
      <?php 
      include('../db_connection.php') ;
$con=OpenSrishringarrCon();

	 
	  $cat = mysqli_query($con,"select distinct(category) from phppos_items where name in (SELECT DISTINCT(item_id) FROM  `audit`) order by category ASC");
	  while($catrow = mysqli_fetch_row($cat)){ 
	  ?>
      
      <option value="<?php echo $catrow[0]; ?>" ><?php echo $catrow[0]; ?></option>
      <?php } ?>   </select>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Audit Date:&nbsp;&nbsp;&nbsp;<select name="cid" id="cid" onchange="MakeRequest();"><option value="-1" >select</option>
      <?php 
	 
	  $result = mysqli_query($con,"SELECT DISTINCT(audit_date) FROM  `audit`  order by audit_date");
	  while($row = mysqli_fetch_row($result)){ 
	  ?>
      
      <option value="<?php echo $row[0]; ?>" ><?php  if(isset($row[0]) and $row[0]!='0000-00-00') echo date('d/m/Y',strtotime($row[0])); ?></option>
      <?php } ?>   </select>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <input type="hidden" name="myvar" value="0" id="theValue" /><br/><br/>
     
      <table width="778" border="0" cellpadding="4" cellspacing="0">
  <tr><td>
 <div id="detail"></div>

     </td></tr>
    </table>
      
      <br/>
</form></center>
 </td></tr>
 
 </table>
</div>
<?php CloseCon($con);?>

<div align="center">You are using Point Of Sale Version 10.5 .</div>