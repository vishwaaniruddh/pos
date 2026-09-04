<font size="2" >
 
  
    <table width="795" border="1" cellpadding="4" cellspacing="0" id="results">
  <tr>
    <th width="42"><font size="2">SR NO.</font></th>
    <th width="99"><font size="2">BARCODE</font></th>
    <th width="99"><font size="2">ITEM NAME</font></th>
    <th width="103"><font size="2">CATEGORY</font></th>
    <th width="57"><font size="2">COST PRICE</font></th>
    <th width="88"><font size="2"> PRICE</font></th>
    <th width="130"><font size="2">ORIGINAL QTY</font></th>
    <th width="162"><font size="2">AUDIT QTY</font></th>
    <th width="162"><font size="2">Missing</font></th>
  
   </tr>
<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$data=$_POST['dt'];

       $qry=mysqli_query($con,"select * from  finalAuditNew where current_dt='".$data."'");
       $total = 0 ; 
       while($res=mysqli_fetch_array($qry)){
           
           $qry1=mysqli_query($con,"select unit_price,category,cost_price from  phppos_items where item_number='".$res['barcode']."'");
           $res1=mysqli_fetch_array($qry1);
           $total = $total + $res['qty'] ; 
        
        if($res['org_qty'] < $res['qty']){
            $missing = $res['qty'] - $res['org_qty'] . ' more' ; 
        }elseif($res['org_qty'] > $res['qty']){
            $missing =  $res['org_qty'] - $res['qty'] . ' less' ; 
        }else{
            $missing = '0 Equal' ; 
        }
        
            
  ?>
 
  <tr>
  
    
     <td><?php echo $res['id'];?></td>
   <td><?php echo $res['barcode'];?></td>
   <td><?php echo $res['item_id'];?></td>
   <td><?php echo $res1['category'];?></td>
   <td><?php echo $res1['cost_price'];?></td>
   <td><?php echo $res1['unit_price'];?></td>
   <td><?php echo $res['org_qty'];?></td>
   <td><?php echo $res['qty'];?></td>
    <td><?php echo $missing ;?></td>
    
    
  </tr>


 
 
  
 
  <?php
  
       }
       
?>
       <tr> 
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td>Total : <?php echo $total ; ?></td>

    </tr>
    
</table>
      	 <?php CloseCon($con);?>  	