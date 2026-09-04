<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css"  />
<?php
ini_set( "display_errors", 0);
// include('config.php');
include('../db_connection.php') ;
$con=OpenSrishringarrCon();


$cid=$_GET['id'];

$result = mysqli_query($con,"SELECT * FROM  `phppos_people` where person_id='$cid'");
$row = mysqli_fetch_row($result);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SHRINGAAR</title>
<link rel="stylesheet" href="multiSelectDropdown.css">
 <style>
.multiselect {
    width:20em;
    height:15em;
    border:solid 1px #c0c0c0;
    overflow:auto;
}
 
.multiselect label {
    display:block;
}
 
.multiselect-on {
   
  
}
.ms-options-wrap > button > span {
    display: inline-block;
}



.ms-options-wrap > button:focus, .ms-options-wrap > button {
       position: static;
    width: 60% !mportant;
    text-align: left;
    border: 1px solid #aaa;
    background-color: #fff;
    padding: 4px 62px 1px 15px;
    margin-top: -18px;
    font-size: 13px;
    /* margin-right: 65px; */
    /* color: #aaa; */
    /* outline-offset: -2px; */
    white-space: nowrap;
    margin-left: 69px;
}

</style>


</head>


 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<script type="text/javascript" src="paging.js"></script>
<script type="text/javascript">     
        function PrintDiv() {    
           var divToPrint = document.getElementById('bill');
           var popupWin = window.open('', '_blank', 'width=800,height=500');
           popupWin.document.open();
           popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
            popupWin.document.close();
                }
                
                
                function showdata(){
                  var dt=  document.getElementById('show').value;
                 // alert(dt)
                    $.ajax({
   type: 'POST',    
   url:'showdataview.php',
   data:'dt='+dt,
   success: function(msg){
   //alert(msg);
   
 document.getElementById('detail').innerHTML=msg;
 
} })
                
                
                    
                }
</script>
<body>

<div id="bill">
 <form name="listForm" action="update_auditNew.php" method="post" id="frm1">
<table width="798" border="0" align="center">
<tr>
    <td width="792" height="42">
    
    <table width="793" >
       <tr>
        <td colspan="2" align="center" style="padding-left:100px;">
          <font size="+1">
          
<img src="bill.PNG" width="408" height="165"/><br/><br/>

            <B><U> AUDIT ENTRY</U></B></font></td>
         </tr>            
 
        <tr>
    
 <td width="">Audit Date : 
 <select id="show" name="show" onchange="showdata()">                      
<option value=" ">--Select --</option>
  <?php
       
               $sq=mysqli_query($con,"SELECT distinct(current_dt) FROM  `finalAuditNew` ");
	         while($ro=mysqli_fetch_array($sq)){

          ?>
             <option value="<?php echo $ro[0]; ?>"><?php echo $ro[0]; ?></option>
          <?php } ?>

</select>
 </td>
   </tr>
 
      
  </table>
  
  <div id="detail"></div>
  
</form>
</div><br/><br/><div id="pageNavPosition"></div>
<!--<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/pos/home_dashboard.php">Back</a></center>
-->
<center><a href="#" onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="/pos/home_dashboard.php">Back</a></center>
<?php CloseCon($con);?>
</body>
</html>


