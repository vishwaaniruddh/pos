<script type="text/javascript" 
   src="/jquery/jquery-1.8.3.js"></script>
<body >

<div  style="text-align: center;">

<?php
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$supp=$_GET['supp_id'];
$qry=mysqli_query($con,"select `company_name` from `phppos_suppliers` where person_id='$supp'");
$res=mysqli_fetch_row($qry);

?>


<b>***Add Vendor Outstanding***</b>
      
     <form id="purchse" method="post" action="process_outstand.php" >
       <div id="printdiv"> 
             <table width="50%">
             <tr><td align="center"> Vendor Name : </td><td align="center"><?php echo $res[0]; ?><input type="hidden" name="supp_id" id="supp_id" value="<?php echo $supp;?>"></td>   </tr>
             <tr><td align="center">outstanding : </td><td align="center"><input type="text" name="outstand" id="outstand" /> </td></tr>
             <tr><td colspan="2" align="center"> <input type="submit" value="Add Outstanding" name="submit"  >&nbsp;<input type="button" onClick="javaxcript:window.close();" value="Exit" name="exit"  ></td></tr>
             </table>
       </form>
      
</div>
<div align="center">You are using Point Of Sale Version 10.5 .</div>
<?php CloseCon($con);?>
</body>